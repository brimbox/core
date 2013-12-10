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

/* JAVASCRIPT FUNCTIONS */
//related bb_submit_form

/* PHP FUNCTIONS */
/* class bb_form() */
//echo_form_begin
//echo_module_vars
//echo_common_vars
//echo_state
//echo_button
//echo_form_end

class bb_form extends bb_validate {
	
	function echo_form_begin($params = array())
		{
		$name = isset($params['name']) ? $params['name'] : "bb_form";
		$type = isset($params['type']) ? $params['type'] : "";
		$action = isset($params['action']) ? $params['action'] : "";
		$autocomplete = isset($params['autocomplete']) ? "autocomplete=\"On\"" : "autocomplete=\"Off\"";
		//optional $type = enctype=\"multipart/form-data\"
		echo "<form name=\"" . $name . "\" action=\"" . $action . "\" method=\"post\" " . $type . " " . $autocomplete . ">";	
		}
		
	function echo_module_vars($module)
		{
		//echos bb_module and bb_button hidden vars
		echo "<input rel=\"ignore\" name=\"bb_module\" type=\"hidden\" value=\"" . $module . "\" />";
		echo "<input name=\"bb_button\" type=\"hidden\" value=\"\" />";
		}
				
	function echo_common_vars()
		{
		//echos common variables to support links
		//bb_post_key is the record id or drill down record key (two uses)
		//bb_row_type is the type of the primary record, or the parent record
		//bb_row_join is tthe type of the child record
		//bb_relate is the related record key
		
		global $array_common_variables;

		foreach ($array_common_variables as $value)
			{
			echo "<input rel=\"ignore\" type=\"hidden\"  name=\"" . $value . "\" value = \"\">";	
			}
		}
		
	function echo_state($array_state)
        {
		//this echos the state into the form for posting
        foreach ($array_state as $key => $value)
            {
            echo "<input rel=\"ignore\" type = \"hidden\"  name = \"" . $key . "\" value = \"" . base64_encode($value) . "\">";
            }
        }
	
	function echo_button($name, $params = array())
		{
		//function to output button
		$class = isset($params['class']) ? $params['class'] : "";
		$label = isset($params['label']) ? $params['label'] : "";
		$number = isset($params['number']) ? $params['number'] : 0;
		$target = isset($params['target']) ? ", '" . $params['target'] . "'" : "";
		$passthis = isset($params['passthis']) ? ", this" : "";	
		
		echo "<button class=\"" . $class . "\" name=\"" . $name . "\" onclick=\"bb_submit_form(" . $number . $target . $passthis . "); return false;\">" . $label . "</button>"; 
		}
		
	function echo_script_button($name, $params = array())
		{
		//function to output button
		//make sure button name is different than function name
		$class = isset($params['class']) ? $params['class'] : "";
		$label = isset($params['label']) ? $params['label'] : "";
		$onclick = isset($params['onclick']) ? $params['onclick'] : "";
		
		echo "<button class=\"" . $class . "\" name=\"" . $name . "\" onclick=\"" . $onclick . "; return false;\">" . $label . "</button>"; 
		}
		
	function echo_form_end()
		{
		//end form tag, why not
		echo "</form>";	
		}
	} //end class
?>