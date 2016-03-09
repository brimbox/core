<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
Copyright (C) 2012 - 2015  Kermit Will Richardson, Brimbox LLC

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License Version 3 (“GNU GPL v3”)
as published by the Free Software Foundation. 

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU GPL v3 for more details. 

You should have received a copy of the GNU GPL v3 along with this program.
If not, see http://www.gnu.org/licenses/
*/

/* NO HTML OUTPUT */

/* PHP FUNCTIONS */
/* class bb_hooks() */
//filter
//hook
//parse
//connect
//lock

/* STANDARD INDEX MAIN INCLUDE */
//can be hacked in bb_constants if necessary
//will be overwritten

class bb_build {
	
	//will not return anything
	//create and pass global vars
    
    function filter()
        {
        //Standard Wordpress style Filter
        global $array_filters;
        global $abspath;
        
        $args = func_get_args();
        $filter = $args[1];
        
        if (isset($array_filters[$args[0]]))
            {
            foreach ($array_filters[$args[0]] as $arr_filter)
                {         
                //get the callable function
                if (isset($arr_filter['func']))
                    $arr_func = $arr_filter['func'];	
                elseif (isset($arr_filter[0]))
                    $arr_func = $arr_filter[0];	
                else
                    $arr_func = NULL;	
                                
                //get the include file
                if (isset($arr_filter['file']))
                    $str_file = $arr_filter['file'];	
                elseif (isset($arr_filter[1]))
                    $str_file = $arr_filter[1];	
                else
                    $str_file = NULL;
                
                if ($str_file)
                    {
                    if (file_exists($abspath . $str_file))
                        //include file
                        include_once($abspath . $str_file);
                    }
                if (is_callable($arr_func))
                    {
                    $params = array_slice($args, 1, count($args) - 1);
                    if (isset($filter)) $params[0] = $filter;
                    $filter = call_user_func_array($arr_func, $params);
                    }
                }
            }
        return $filter;
        }

	
	function hook($hookname)
        //Brimbox style Global Hook
		{
		//name passed as values so no error if hooks aren't set
		//append is to make hook which which work when module name is changed
		//hooks can loop and have multiple declaration and executions
		global $array_hooks;
        global $abspath;        
					
		//hook must be set
		if (isset($array_hooks[$hookname]))
			{				
			//hook loop
			foreach ($array_hooks[$hookname] as $arr_hook)
				{
                //simple function with no parameters
                //or include file for some reason
				//handles callable array and strings
				if (is_callable($arr_hook))
                    {
					//used for simple hooks, can actually be an array
					call_user_func($arr_hook);
                    }
				elseif (is_string($arr_hook))
					{
                    if (file_exists($abspath . $arr_hook))
                        //include file
                        include_once($abspath . $arr_hook);
                    }
				//process with parameters etc
				else
					{				
					//array, most likely has variables
					//not callable function array 
					if (is_array($arr_hook))
						{
						$args_hook = array();
						//process variables
						//turn arr_hook[1] into array
						if (isset($arr_hook['vars']))
							//asscoiative
							$arr_vars = is_string($arr_hook['vars']) ? array($arr_hook['vars']) : $arr_hook['vars'];
						elseif (isset($arr_hook[1]))
							//numeric keys
							$arr_vars = is_string($arr_hook[1]) ? array($arr_hook[1]) : $arr_hook[1];
						else
							$arr_vars = NULL;
                            
                        //get the callable function
						if (isset($arr_hook['func']))
							$arr_func = $arr_hook['func'];	
						elseif (isset($arr_hook[0]))
							$arr_func = $arr_hook[0];	
						else
							$arr_func = NULL;	
						
						//get the include file
						if (isset($arr_hook['file']))
							$str_file = $arr_hook['file'];	
						elseif (isset($arr_hook[2]))
							$str_file = $arr_hook[2];	
						else
							$str_file = NULL;
						
						//process vars and values for passing
						if (!empty($arr_vars))
							{
							//foreach will not iterate on empty array
							foreach ($arr_vars as $var)
								{
								//passed by reference
								if (substr($var,0,1) == "&")
									{
									$var = substr($var,1);
									if(isset($GLOBALS[$var]))
										${$var} = $GLOBALS[$var];
									else
										${$var} = NULL;										
									$args_hook[] = &${$var};
									}
								//passed by value
								else
									{
									if(isset($GLOBALS[$var]))
										${$var} = $GLOBALS[$var];
									else
										${$var} = NULL;										
									$args_hook[] = ${$var};
								   }
								}
							}
												
						if ($str_file)
							if (file_exists($abspath . $str_file))
								//include file
								include_once($abspath . $str_file);
						if (is_callable($arr_func))
							//callable function
							call_user_func_array($arr_func, $args_hook);
							
						//pass by value emulation, variables updated
						//will bring variables out of blocks and functions
						//$arr_hook[1] should be array
						if (!empty($arr_vars))
							{
							foreach ($arr_vars as $var)
								{
								if (substr($var,0,1) == "&")
									{
									$var = substr($var,1);
									$GLOBALS[$var] = ${$var};
									}
								}
							}
						}
					}
				}
			}
		}

		
	function loader ($con, $interface)
		{
		
		global $array_header;
		global $array_global;
		
		$abspath = $_SESSION['abspath'];
		
		/* INCLUDE STANDARD ARRAYS ARRAYS AND GLOBAL FUNCTIONS */
		//global for all interfaces
		include($abspath . "/bb-utilities/bb_arrays.php");
        /* INCLUDE INSTALLED  */
		$query = "SELECT module_path FROM modules_table WHERE standard_module IN (0,4,6) AND module_type IN (-1) ORDER BY module_order;";
		$result = pg_query($con, $query);
		while($row = pg_fetch_array($result))
			{
			//will ignore file if missing
			include($abspath . "/" . $row['module_path']);
			}
		/* ADHOC ARRAYS AND GLOBAL FUNCTIONS */
		include($abspath . "/bb-config/bb_functions.php");
		//header stored in SESSION
		//save for use in post side modules
				
		/* UNPACK $array_global for given interface */
		//this creates array from the global array
		if (isset($array_global))
			{
			foreach($array_global[$interface] as $key => $value)
				{
				$GLOBALS['array_' . $key] = $value;
				}
			}
		}
			
	function locked($con, $username, $userrole)
		{
		/* CHECK IF USER LOCKED OR DELETED */
		//once $con is set check live whether user is locked or deleted
		//0_bb_brimbox is only locked userrole, for active lock
		$query = "SELECT id FROM users_table " .
				 "WHERE username = '" . pg_escape_string($username) . "' AND '" . pg_escape_string($userrole) . "' = ANY (userroles) AND NOT '0_bb_brimbox' = ANY (userroles);";
		$result = pg_query($con, $query);
		if (pg_num_rows($result) <> 1)
			{
			session_destroy();
			$index_path = "Location: " . dirname($_SERVER['PHP_SELF']);
			header($index_path);
			die(); //important to stop script    
			}
		}
} //end class
?>