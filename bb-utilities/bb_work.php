<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
Copyright (C) 2012 - 2013  Kermit Will Richardson, Brimbox LLC

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
//name
//load
//check
//full
//post
//state
//set
//process
//retrieve
//update
//hot_state (private)


/* POSTBACK ANBD STATE FUNCTIONS */	
class bb_work extends bb_form {	
			
    function name($name, $module)
        {
		//returns the name of variable with module prepended
        return $module . "_" . $name;
		}
			
	function load($module, $array_state)
        {
		global $array_state;
		//gets and loads xml from array state 
		$key = $module . "_bb_state";
		$xml = simplexml_load_string($array_state[$key]);
		return $xml;	
		}
		
	function check($name, $module)
		{
		//checks to see if a $_POST variable is set
		$temp = $module . '_' . $name;
		$bool = false;
		if (isset($_POST[$temp]))
			{
			$bool = true;	
			}
		return $bool;	
		}
		
	function full($name, $module)
		//to check if full, opposite of empty function, returns false if empty
		//post var must be set or you will get a notice
		{
		$var = false;
		$temp = $module . '_' . $name;
		$post_var = trim($_POST[$temp]);
		if (!empty($post_var))
			{
			$var = true;
			}
		return $var;	
		}
		
	function post($name, $module, $default = "")
		//gets the post value of a variable
		{
		$var = (string)$default; 
		$temp = $module . '_' . $name;
		if (isset($_POST[$temp]))
			{
			$var = $_POST[$temp];
			}
		return $var;	
		}	
			
	function state($name, $xml_state, $initial = "")
		{
		//gets the state value of a variable
		$var = isset($xml_state->$name) ? (string)$xml_state->$name : (string)$initial;
		return $var;	
		}	
	 
	function set($name, &$xml_state, $value)
		{
		//sets the state value from a variable, $module var not needed
		$value = (string)$value;
		unset($xml_state->$name);
		$xml_state->$name = $value;
		return $value;
		}	
	
	function process($name, $module, &$xml_state, $default = "")
		//fully processes $_POST variable into state setting with initial value
		{
		$var = isset($xml_state->$name) ? (string)$xml_state->$name : (string)$default;
		$temp = $module . '_' . $name;
		if (isset($_POST[$temp])) 
			{
			$var = (string)$_POST[$temp];
			}
		unset($xml_state->$name);
		$xml_state->$name = $var;
		return $var;	
		}
		
	function render($name, $module, &$xml_state, $type, $default = "")
		//fully processes $_POST variable into state setting with initial value
		{
		global $array_validation;
		
		$var = isset($xml_state->$name) ? (string)$xml_state->$name : (string)$default; 
		$temp = $module . '_' . $name;
		if (isset($_POST[$temp])) 
			{
			$var = (string)$_POST[$temp];
			}
		//will format value if valid, otherwise leaves $var untouched
		call_user_func_array($array_validation[$type], array(&$var));	
		unset($xml_state->$name);
		$xml_state->$name = $var;
		return $var;	
		}
			
	function retrieve($con, &$array_state, $userrole)
		//retrieves state from $_POST based on known tabs with state from tab table in database
        {
        //This function will also initalize state if not set
        $array_state = array();
        
        //if guest condition, else other condition
        switch ($userrole)
            {
            case 1:
            $and_clause =  "userrole IN (1)";
            break;
            case 2:
            $and_clause =  "userrole IN (2)";
            break;
            case 3:
            $and_clause =  "userrole IN (3)";
            break;               
            case 4:
            $and_clause =  "userrole IN (3,4)";
            break;
            case 5:
            $and_clause =  "userrole IN (3,4,5)";
            break;
            }
		
		//to create this as a base class call query with die
        $query = "SELECT module_name FROM modules_table WHERE maintain_state = 1 AND " . $and_clause . " AND standard_module IN (0,1,2,4,6);";
        //$this = new bb_database();
		$result = $this->query($con, $query);
        
        while($row = pg_fetch_array($result))
            {
            $key = $row['module_name'] . '_bb_state';
            if (!empty($_POST[$key])) //get state from post
                {
                $array_state[$key] = base64_decode($_POST[$key]);
                }
            else  //initialize state
                {                
                $array_state[$key] = "<hold></hold>";
                }
            }
		$this->hot_state($array_state, $userrole);
		}
	
	function update(&$array_state, $module, $xml_state)
		//updates array_state when xml_state is updated with new state variables
        {
		$key = $module . '_bb_state';
        $array_state[$key] = $xml_state->asXML();
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
				$xml_state = simplexml_load_string($array_state[$key]);
				//loop through hot state values
				foreach ($arr_value as $value)
					{				
					if ($this->check($value, $module))
						{
						$this->process($value, $module, $xml_state);
						}
					}
				$update = true;
				}
			}		
		if ($update)
			{
			$this->update($array_state, $module, $xml_state);
			}
        }
	
	//this function duplicated in both reports and work classes under different names	
	function report(&$xml_state, $module_submit, $module_display, $params = array())
		{
		//need to get things from post		
		$maintain_state = isset($params['maintain_state']) ? $params['maintain_state'] : true;
		
		//get values from state
		$report_type = $this->state('report_type', $xml_state, 0);
		$page = $this->state('page', $xml_state, 0);
		$button = $this->state('button', $xml_state, 0);
			
		//only if page is set
		if ($this->check('page', $module_display) && !empty($module_display))
			{
			$page = (int)$this->post('page', $module_display, 0);
			}
		//if report_type is set, postback	
		if ($this->check('report_type', $module_submit) && !empty($module_submit))
			{
			//postback variables used in report structure
			$report_type = $this->post('report_type', $module_submit, 0);
			}		
		//if postback, save in report arr as button
		if ($this->check('report_type', $module_submit) && !empty($module_submit))
			{
			//if button changes set page to zero
			$button = $this->state('button', $xml_state, 0);
			$button_temp = $this->post('bb_button', $module_submit, 0);		
			//only reset button if greater than zero
			if ($button_temp > 0)
				{
				$button = $button_temp;
				$page = 0;
				}
			}
			
		//usually will maintain state
		if ($maintain_state)
			{
			$this->set('report_type', $xml_state, $report_type);
			$this->set('page', $xml_state, $page);
			//keeps current button, different than bb_button
			$this->set('button', $xml_state, $button);
			$this->set('module_submit', $xml_state, $module_submit);
			$this->set('module_display', $xml_state, $module_submit);
			}
			
		//set up array
		$arr['report_type'] = $report_type;
		$arr['page'] = $page;
		$arr['button'] = $button;
		$arr['module_display'] = $module_display;
		$arr['module_submit'] = $module_submit;
		
		return $arr;	
		}
		
    } //end class

?>