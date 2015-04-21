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

/* class bb_work() */
//button
//name
//blank
//check
//full
//post
//state
//set
//process
//render
//load
//retrieve
//update
//hot_state (private)
//report
//get_relate
//get_key
//get_value


/* POSTBACK ANBD STATE FUNCTIONS */	
class bb_work extends bb_forms {
	
	function button($number, $check = "")
		{
		global $submit;
		global $module;
		global $button;
		
		if (is_int($number)) //convert int to array
			{
			$number = array($number);	
			}		
		if (!$this->blank($check)) //check where it was submitted from
			{
			return (($submit == $check) && in_array($button, $number)) ? true : false;
			}
		else //postback, submit is module
			{
			return (($submit == $module) && in_array($button, $number))? true : false;	
			}
		}
		
    function name($name, $module)
        {
		//returns the name of variable with module prepended
        return $module . "_" . $name;
		}
					
	function blank(&$var)
		{
		//anything that is empty but not identical to the '0' string
		return empty($var) && $var !== '0';
		}
		
	function check($name, $module)
	    {
	    //checks to see if a $_POST variable is set
	    $temp = $module . '_' . $name;
	    if (isset($_POST[$temp]))
			{
			return true;	
			}
		else
			{
			return false;	
			}
	    }		
		
	function full($name, $module)
	    //to check if full, opposite of empty function, returns false if empty
	    //post var must be set or you will get a notice
	    {
	    $temp = $module . '_' . $name;
	    if (!$this->blank(trim($_POST[$temp])))
		    {
		    return true;
			}
		else
			{
			return false;
			}
	    }
		
	function post($name, $module, $default = "")
	    //gets the post value of a variable
	    {
	    $temp = $module . '_' . $name;
	    if (isset($_POST[$temp]))
		    {
		    return $_POST[$temp];
		    }
		else
			{
			return $default;	
			}
	    }	
			
	function state($name, $arr_state, $default = "")
	    {
	    //gets the state value of a variable
	    $var = isset($arr_state[$name]) ? $arr_state[$name] : $default;
	    return $var;	
	    }	
	 
	function set($name, &$arr_state, $value)
	    {
	    //sets the state value from a variable, $module var not needed
	    $arr_state[$name] = $value;
	    return $value;
	    }	
	
	function process($name, $module, &$arr_state, $default = "")
	    //fully processes $_POST variable into state setting with initial value
	    {
	    $var = isset($arr_state[$name]) ? $arr_state[$name] : $default;
	    $temp = $module . '_' . $name;
		if (isset($_POST[$temp]))
			{
			$var = $_POST[$temp];
			}
	    $arr_state[$name] = $var;
	    return $var;	
	    }
		
	function render($con, $name, $module, &$arr_state, $type, &$check, $default = "")
	    //fully processes $_POST variable into state rendering type if validated
	    {
	    $arr_header = $this->get_json($con, "bb_interface_enable");
        $arr_validation = $arr_header['validation'];
	    
	    $var = isset($arr_state[$name]) ? $arr_state[$name] : $default; 
	    $temp = $module . '_' . $name;

		//if variable is set use variable
		if (isset($_POST[$temp]))
			{
			$var = $_POST[$temp];
			}

	    //will format value if valid, otherwise leaves $var untouched
		//check becomes false on valid type, true opn error
	    $check = call_user_func_array($arr_validation[$type]['function'], array(&$var, false));	
	    $arr_state[$name] = $var;
		
	    return $var;	
	    }
		
	function load($module, $array_state)
        {
		//gets and loads $arr_state from global $array_state
	    global $array_state;
		 
	    $temp = $module . "_bb_state";
		//will initialize array state if if not set set, happens in module install
	    $arr_state = isset($array_state[$temp]) ? json_decode($array_state[$temp], true) : array();
	    return $arr_state;	
	    }
		
	function retrieve($con, &$array_state)
	    //retrieves state from $_POST based on known tabs with state from tab table in database
	    {
		global $array_interface;
		global $interface;
		global $userrole;
		
		$array_state = array();		
		$arr_modules_active = array();		
		
		//go through $array_interface and get active modules based on interface
		foreach ($array_interface as $key => $value)
			{
			if (in_array($userrole, $value['usertypes']))
				{
				array_push($arr_modules_active, $value['module_type']);
				}
			}
	    //explode module types
	    $and_clause = " module_type IN (" . implode(",",$arr_modules_active) . ") ";
	    		    
		//get module list from modules table WHERE maintain_state = 1
	    $query = "SELECT module_name FROM modules_table WHERE maintain_state = 1 AND " . $and_clause . " AND standard_module IN (0,1,2,4,6);";
	    //echo "<p>" . $query . "</p>";
		$result = $this->query($con, $query);
	    
		//build $array_state
	    while($row = pg_fetch_array($result))
			{
			$key = $row['module_name'] . '_bb_state';
			if (!empty($_POST[$key])) //get state from post
				{
				$array_state[$key] = base64_decode($_POST[$key]);
				}
			else  //initialize state
				{                
				$array_state[$key] = "";
				}
			}
	    $this->hot_state($array_state, $userrole);
	    }
	
	function update(&$array_state, $module, $arr_state)
		//updates $array_state with $arr_state
        {
		$temp = $module . '_bb_state';
        $array_state[$temp] = json_encode($arr_state);
        }
			
	private function hot_state(&$array_state, $userrole)
	    {
	    //hot state used to update state vars when tabs are switched without postback
	    global $array_hot_state;
	    
	    $arr_work = array();
	    if (isset($array_hot_state[$userrole]))
			{
			$arr_work = $array_hot_state[$userrole];	
			}
	    
	    //do not update the state unless coming from that tab
	    $update = false;
	    //$arr_work contains hot states for appropriate user level
	    foreach ($arr_work as $module => $arr_value)
			{
			//check one value before proceeding, makes the loop inefficiency acceptable
			if ($this->check(current($arr_value), $module))
				{
				$key = $module . "_bb_state";
				$arr_state = json_decode($array_state[$key], true);
				//loop through hot state values
				foreach ($arr_value as $value)
					{
					if ($this->check($value, $module))
						{
						$this->process($value, $module, $arr_state);
						}
					}
				$update = true;
				}
			}
		//update $array_state
	    if ($update)
			{
		    $this->update($array_state, $module, $arr_state);
			}
	    }
	
	//this function duplicated in both reports and work classes under different names	
	function report(&$arr_state, $module_submit, $module_display, $params = array())
	    {
	    //alias of report_post
	    $current = $this->report_post($arr_state, $module_submit, $module_display, $params = array());
	    return $current;
	    }
		
	//get the row_type from a related field	
	function relate_check($related)
		{
		if (preg_match("/^[A-Z]\d+:.*/", $str))
			{
			return true;
			}
		return false;	
		}
		
	//get the row_type from a related field	
	function relate_row_type($related)
		{
		return ord(substr($related,0,1)) - 64;	
		}
		
	//get post_key or id from related field
	function relate_post_key($related)
		{
		return substr($related,1, strpos($related,":") - 1);	
		}
			
	//gets primary string from related field
	function relate_value($related)
		{
		return substr($related, strpos($related,":") + 1);	
		}
		
} //end class

?>