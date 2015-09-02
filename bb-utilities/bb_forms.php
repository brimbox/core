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

/* JAVASCRIPT FUNCTIONS */
//related bb_submit_form

/* PHP FUNCTIONS */
/* class bb_form() */
//echo_form_begin
//echo_module_vars
//echo_common_vars
//echo_state
//echo_button
//echo_script_button
//echo_input
//echo_textarea
//echo_form_end
//

class bb_forms extends bb_links {
	
	function echo_form_begin($params = array())
		{
		global $module;
		global $path;
		
		$name = isset($params['name']) ? $params['name'] : "bb_form";
		$type = isset($params['type']) ? $params['type'] : "";
		$autocomplete = isset($params['autocomplete']) ? "autocomplete=\"On\"" : "autocomplete=\"Off\"";
		//optional $type = enctype=\"multipart/form-data\"
		
		//action is important
		if (isset($params['action']))
			{
			$action = $params['action'];
			}
		elseif (file_exists(dirname($path) . "/" . $module . "_post.php"))
			{
			$action = dirname($path) . "/" . $module . "_post.php";	
			}
		else
			{
			$action = "post.php";	
			}
		echo "<form name=\"" . $name . "\" action=\"" . $action . "\" method=\"post\" " . $type . " " . $autocomplete . ">";	
		}
		
	function echo_module_vars()
		{
		//global make the most sense since these are global variables
		global $module;
		global $slug;
		/* These variables support javascript function bb_submit_form() */
		//starts with current module and changes to target, where you're going
		echo "<input rel=\"ignore\" name=\"bb_module\" type=\"hidden\" value=\"" . $module . "\" />";
		//starts with current module and changes to target, where you're going
		echo "<input rel=\"ignore\" name=\"bb_slug\" type=\"hidden\" value=\"" . $slug . "\" />";
		//starts empty and changes to current module, where you're coming from
		echo "<input rel=\"ignore\" name=\"bb_submit\" type=\"hidden\" value=\"\" />";
		//working Brimbox button submitted processed in the controller, always set w/ javascript
		echo "<input rel=\"ignore\" name=\"bb_button\" type=\"hidden\" value=\"\" />";
		//used when logging out or changing state
		echo "<input rel=\"ignore\" name=\"bb_userrole\" type=\"hidden\" value=\"\" />";
		//used for processing image
		echo "<input rel=\"ignore\" name=\"bb_image\" type=\"hidden\" value=\"\" />";
		//the object needed when using file or document downloads
		echo "<input rel=\"ignore\" name=\"bb_object\" type=\"hidden\" value = \"\">";
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
		
	function echo_object_vars()
		{
		//deprcated, barely implemented
		//do nothing
		//object variable (bb_object) no included with module_vars
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
		/* function to output button */
		//class and label
		$class = isset($params['class']) ? $params['class'] : "";
		$label = isset($params['label']) ? $params['label'] : "";		
		//javascript parameters
		$number = isset($params['number']) ? $params['number'] : 0;
		$target = isset($params['target']) ? "'" . $params['target'] . "'" : "undefined";
		$passthis = isset($params['passthis']) ? "this" : "undefined";
		//allows boolean true for default proicessing image		
		if (isset($params['image']))
			{
			if (is_bool($params['image']))
				{
				$image = $params['image'] == true ? "'processing_image.gif'" : "undefined";
				}
			else
				{
				$image = "'" . $params['image'] . "'";	
				}
			}
		else
			{
			$image = "undefined";	
			}
		//implode parameters with comma
		$params_str = implode(",", array($number, $target, $passthis, $image));
		echo "<button class=\"" . $class . "\" name=\"" . $name . "\" onclick=\"bb_submit_form(" . $params_str . "); return false;\">" . $label . "</button>"; 
		}
		
	function echo_script_button($name, $params = array())
		{
		//function to output button
		//make sure button name is different than function name
		$class = isset($params['class']) ? $params['class'] : "";
		$label = isset($params['label']) ? $params['label'] : "";
		//return false included
		$onclick = isset($params['onclick']) ? "onclick=\"" . $params['onclick'] . "; return false;\"" : "";
		
		echo "<button class=\"" . $class . "\" name=\"" . $name . "\" " . $onclick . ">" . $label . "</button>"; 
		}
		
    function echo_input($name, $value = "", $params = array())
		{
		//function to output input html object
		
		//string params
		$type = isset($params['type']) ? $params['type'] : "text";
		$input_class = isset($params['input_class']) ? $params['input_class'] : "";
		//return false not included
		$onclick = isset($params['onclick']) ? "onClick=\"" . $params['onclick'] . ";\"" : "";
		$handler = isset($params['handler']) ? $params['handler'] . ";\"" : "";
		
		//true or false params
		$checked = !empty($params['checked']) ? "checked" : "";
		$readonly = !empty($params['readonly']) ? "readonly" : "";
		
		//integer, 0 will be empty
		$maxlength = !empty($params['maxlength']) ? "maxlength=\"" . $params['maxlength'] . "\"" : "";
		
		$label = isset($params['label']) ? $params['label'] : "";
		$label_class = isset($params['label_class']) ? $params['label_class'] : "";
		$position = isset($params['position']) ? $params['position'] : "";
		
		$begin = "";
		$end = "";
		if (!$this->blank($label))
			{
			if (strcasecmp($position, "end")) //0 is match
				{
				$begin = "<label  class=\"" . $label_class . "\">" . $label;
				$end = "</label>";
				}
			else
				{
				$begin = "<label  class=\"" . $label_class . "\">";
				$end =  $label . "</label>";					
				}
			}
		//zend hack -- give empty checkbox a zero value with a hidden input of same name
		if ($type == "checkbox")
			{
		    echo $begin . "<input type=\"hidden\" name=\"" . $name . "\" value=\"0\"><input type=\"" . $type . "\" class=\"" . $input_class . "\" name=\"" . $name . "\" value=\"" . $value . "\" " . $onclick . " " . $handler . " " . $readonly . " " . $checked . "/>" . $end; 
			}
		else
			{
		    echo $begin . "<input type=\"" . $type . "\" class=\"" . $input_class . "\" name=\"" . $name . "\" value=\"" . $value . "\" " . $maxlength . " " . $onclick . " " . $handler . " "  . $readonly . "/>" . $end; 
			}
		}
		
	function echo_textarea($name, $value = "", $params = array())
		{
		//function to output button
		$class = isset($params['class']) ? $params['class'] : "";
		$readonly = isset($params['readonly']) ? "readonly" : "";
		$onclick = isset($params['onclick']) ? " onclick=\"" . $params['onclick'] . "; return false;\" " : "";
		$rows = isset($params['rows']) ? $params['rows'] : "";
		$cols = isset($params['cols']) ? $params['cols'] : "";		
		
		echo "<textarea class=\"" . $class . "\" name=\"" . $name . "\" value=\"" . $value . "\" rows=\"" . $rows . "\" cols=\"" . $cols . "\" " . $onclick . " " . $readonly . ">" . $value . "</textarea>"; 
		}

		
	function echo_form_end()
		{
		//end form tag, why not
		echo "</form>";	
		}
	} //end class
?>