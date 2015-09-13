<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
Copyright (C) 2012 - 2015  Kermit Will Richardson, Brimbox LLC

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License Version 3 (�GNU GPL v3�)
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
class bb_work extends bb_hooks {
	
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
		//psuedo post var
		global $POST;
		
	    //checks to see if a $POST variable is set
	    $temp = $this->name($name, $module);
	    if (isset($POST[$temp]))
			{
			return true;	
			}
		else
			{
			return false;	
			}
	    }		
		
	function full($name, $module)
	    {
		//psuedo post var
		global $POST;
		
		//to check if full, opposite of empty function, returns false if empty
	    //post var must be set or you will get a notice

	    $temp = $this->name($name, $module);
	    if (!$this->blank(trim($POST[$temp])))
		    {
		    return true;
			}
		else
			{
			return false;
			}
	    }
		
	function post($name, $module, $default = "")
	    {
		//psuedo post var
		global $POST;
		
		//gets the post value of a variable
	    $temp = $this->name($name, $module);
	    if (isset($POST[$temp]))
		    {
		    return $POST[$temp];
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
	    {
		//psuedo post var
		global $POST;
		
		//fully processes $POST variable into state setting with initial value
	    $var = isset($arr_state[$name]) ? $arr_state[$name] : $default;
		$temp = $this->name($name, $module);

		if (isset($POST[$temp]))
			{
			$var = $POST[$temp];
			}
			
	    $arr_state[$name] = $var;
	    return $var;	
	    }
		
	function render($con, $name, $module, &$arr_state, $type, &$check, $default = "")
	    {
		//psuedo post var
		global $POST;
			
		//fully processes $POST variable into state setting with initial value		
	    $arr_header = $this->get_json($con, "bb_interface_enable");
        $arr_validation = $arr_header['validation'];
	    
	    $var = isset($arr_state[$name]) ? $arr_state[$name] : $default; 
	    $temp = $this->name($name, $module);

		//if variable is set use variable
		if (isset($POST[$temp]))
			{
			$var = $POST[$temp];
			}

	    //will format value if valid, otherwise leaves $var untouched
		//check becomes false on valid type, true opn error
	    $check = call_user_func_array($arr_validation[$type]['function'], array(&$var, false));	
	    $arr_state[$name] = $var;
		
	    return $var;	
	    }
		
	function load($con, $saver)
        {
		//gets and loads $arr_state from global $array_state
		//module doesn't need to be defalut module
	    global $keeper;
		
		$query = "SELECT statedata[" . $saver . "] as jsondata FROM state_table WHERE id IN (" . $keeper . ");";
		$result = pg_query($con, $query);
		$row = pg_fetch_array($result);
		$arr_state = json_decode($row['jsondata'], true);
		
	    return $arr_state;	
	    }
		
	function saver($con, $module)
		{
		$query = "SELECT id, module_name, module_slug FROM modules_table WHERE module_name IN ('" . $module . "');";
		$result = pg_query($con, $query);
		$row = pg_fetch_array($result);
		$saver = $row['id'];
	
		return $saver;
		}
		
	function keeper($con, $key = "")
		{
		global $module;
		global $keeper;
		
		//get module number
		$query = "SELECT postdata FROM state_table WHERE id = " . $keeper . ";";		
		$result = $this->query($con, $query);
		$row = pg_fetch_array($result);
		
		return json_decode($row['postdata'], true);	
		}
		
	function retrieve($con)
	    {
		global $POST;
			
		$POST = $this->keeper($con);
	    
	    $this->hot_state($array_state, $userrole);
		
		return $POST;
	    }
	
	function update($con, $arr_state, $saver)
		//updates $array_state with $arr_state
        {
		global $keeper;
		$jsondata = json_encode($arr_state);
		
		$query = "UPDATE state_table SET statedata[$1] = $2 WHERE id = " . $keeper . ";";
		$params = array($saver, $jsondata);
		$this->query_params($con, $query, $params);
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
		if (preg_match("/^[A-Z]\d+:.*/", $related))
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