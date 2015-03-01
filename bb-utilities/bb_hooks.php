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
//hook
//autofill
//infolinks
//postback_area
//top_level_records
//parent_record
//quick_links
//submit_buttons
//textarea_load


class bb_hooks extends bb_work {
	
	function hook($name, $append = false)
		{
		//name passed as values so no error if hooks aren't set
		//append is to make hook which which work when module name is changed
		global $array_hooks;
		global $module;
		
		//will use current module name if append = true
		$name = $append ? $module . "_" . $name : $name;
					
		//hook must be set
		if (!isset($array_hooks[$name]))
			return false;
		//hook must be named properly, always good if append = true
		elseif (substr($name, 0, strlen($module) + 1) <> ($module . "_"))
			return false;
				
		//hook loop
		foreach ($array_hooks[$name] as $arr_hook)
			{
			$args_hook = array(); //must initialize
			//build arguments and variables
			foreach ($arr_hook[1] as $var)
				{
				//passed by value
				if (substr($var,0,1) == "&")
					{
					$var = substr($var,1);
					${$var} = isset($GLOBALS[$var]) ? $GLOBALS[$var] : NULL;
					$args_hook[] = &${$var};
					}
				//passed by reference
				else
					{
					${$var} = isset($GLOBALS[$var]) ? $GLOBALS[$var] : NULL;
					$args_hook[] = ${$var};
				   }
				}
			call_user_func_array($arr_hook[0], $args_hook);
			//emulate passed by value
			foreach ($arr_hook[1] as $var)
				{
				if (substr($var,0,1) == "&")
					{
					$var = substr($var,1);
					$GLOBALS[$var] = ${$var};
					}
				}
			}    
		}
		
	function autofill($arr_column_reduced, $row, &$arr_state, $arr_columns, $row_type, $parent_row_type)
		{
		$arr_column_parent = $this->filter_keys($arr_columns[$parent_row_type]);
		$arr_search = array();
        //build array for search
        foreach ($arr_column_reduced as $key => $value)
            {
            $arr_search[$key] = $value['name'];
            }
        foreach ($arr_column_parent as $key1 => $value1)
            {
            $col1 = $this->pad("c", $key1);    
            $key2 = array_search($value1['name'], $arr_search);
            //if found
            if (is_integer($key2))
                {
                $col2 = $this->pad("c", $key2);
                //if autofill column is empty
				if ($this->blank($arr_state[$col2]))
					{
					$arr_state[$col2] = $row[$col1];
					}
                }
            }
		return true;
		} //end function
		
	function infolinks()
		{
		echo "<div class=\"floatright\">";
		$this->logout_link();
		echo "</div>";
		
		echo "<div class=\"floatleft\">";
		$this->database_stats();
		$this->archive_link();
		$this->replicate_link();
		$this->userrole_switch();
		echo "</div>";
		echo "<div class=\"clear\"></div>";
		}
	
	//this posts from the queue, input, and state	
	function postback_area($main, $con, $module, $arr_layouts_reduced, $arr_columns, $default_row_type, &$arr_state, &$row_type, &$row_join, &$post_key)
		{	
		if (file_exists("bb-primary/bb_input_extra.php"))
			{
			include("bb-primary/bb_input_extra.php");
			$input = new bb_input_extra($arr_columns, $arr_state, $main, $con, $module, $default_row_type);
			list($row_type, $row_join, $post_key, $arr_state) = $input->linkspost();
			if ($main->button(2,'bb_queue'))
				{
				//constuct with row type from state
				$queue = new bb_input_queue($arr_layouts_reduced, $arr_columns, $arr_state, $main, $con, $module, $row_type, $row_join, $post_key);
				list($row_type, $row_join, $post_key, $arr_state) = $queue->queuepost();
				}
			}
		}		
	
	//top level record selector	
	function top_level_records($module, $arr_layouts_reduced, &$arr_column_reduced, $row_type, $row_join, $parent_row_type)
		{
		//buttons an record selector
		$update_or_insert = ($row_type == $row_join) ? "Update Record" : "Insert Mode";
		$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>$update_or_insert);
		$this->echo_button("top_submit", $params);
		$params = array("class"=>"spaced","number"=>2,"target"=>$module, "passthis"=>true, "label"=>"Reset Form");
		$this->echo_button("top_reset", $params);
		if (!empty($parent_row_type))
			{
			echo "<select name = \"row_type\" class = \"spaced\" onchange=\"bb_reload_on_layout()\">";
			echo "<option value=\"" . $row_type . "\" selected>" . $arr_layouts_reduced[$row_type]['plural'] . "&nbsp;</option>";
			echo "</select>";
			}
		//no parent, all possible top level records
		else
			{
			//get top level records
			foreach($arr_layouts_reduced as $key => $value)
				{
				if ($value['parent'] == 0)
					{
					$arr_select[$key] = $value;
					}
				}
			//has top level records
			if (count($arr_select) > 0)
				{
				//on reset, $arr_column already set if changing top level from select
				echo "<select name = \"row_type\" class = \"spaced\" onchange=\"bb_reload_on_layout()\">";
				foreach ($arr_select as $key => $value)
					{
					echo "<option value=\"" . $key . "\" " . ($key == $row_type ? "selected" : "") . ">" . $value['plural'] . "&nbsp;</option>";
					}
				echo "</select>";
				}
			else
				{
				$arr_column_reduced = array();
				}
			//no top level records, not common
			}
			echo "<div class=\"clear\"></div>";
		}
	
	//parent record quick links	
	function parent_record($arr_column_reduced, $row_type, $row_join, $parent_id, $parent_row_type, $parent_primary)
		{
		//$arr_column_reduced = check for some type of record
		if (!empty($arr_column_reduced))
			{
			$edit_or_insert = ($row_type == $row_join) ? "Edit Mode" : "Insert Mode";
	
			//edit or insert mode and primary parent column		
			$parent_string = $this->blank($parent_primary) ? "" : " - Parent: <button class=\"link colored\" onclick=\"bb_links.input(" . $parent_id . "," . $parent_row_type . "," . $parent_row_type . ",'bb_input'); return false;\">" . $parent_primary . "</button>";
			echo "<p class=\"bold spaced\">" . $edit_or_insert . $parent_string . "</p>";
			}
		}
	
	//quick child and sibling links
	function quick_links($arr_column_reduced, $arr_layouts_reduced, $inserted_id, $inserted_row_type, $inserted_primary, $parent_id, $parent_row_type,  $parent_primary)
		{
		//$arr_column_reduced = check for some type of record
		if (!empty($arr_column_reduced))
			{
			//add children links, empty works no zeros
			if (!empty($inserted_id) && !empty($inserted_row_type))
				{
				if ($this->check_child($inserted_row_type, $arr_layouts_reduced))
					{
					echo "<p class=\"spaced bold\">Add Child Record - Parent: <span class=\"colored\">" . $inserted_primary . "</span> - ";
					$this->drill_links($inserted_id, $inserted_row_type, $arr_layouts_reduced, "bb_input", "Add");
					echo "</p>";
					}
				}	
			//add sibling links, empty works no zeros
			if (!empty($inserted_id) && !empty($parent_id) && !empty($parent_row_type))
				{
				if ($this->check_child($parent_row_type, $arr_layouts_reduced))
					{
					echo "<p class=\"spaced bold\">Add Sibling Record - Parent: <span class=\"colored\">" . $parent_primary . "</span> - ";
					$this->drill_links($parent_id, $parent_row_type, $arr_layouts_reduced, "bb_input", "Add");
					echo "</p>";
					}
				}
			}	
		}
		
	function submit_buttons($arr_column_reduced, $module, $row_type, $row_join)
		{
		if (!empty($arr_column_reduced))
            {                
            $update_or_insert = ($row_type == $row_join) ? "Update Record" : "Insert Mode";
            $params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>$update_or_insert);
            $this->echo_button("bottom_submit", $params);
            $params = array("class"=>"spaced","number"=>2,"target"=>$module, "passthis"=>true, "label"=>"Reset Form");
            $this->echo_button("bottom_reset", $params);
            }			
		}
		
	function textarea_load($arr_column_reduced, $arr_column, $module)
		{
		if (!empty($arr_column_reduced))
            {
			$textarea_rows = (int)$arr_column['layout']['count'] > 4 ? (int)$arr_column['layout']['count'] : 4;
            echo "<div class=\"clear\"></div>";
            echo "<br>";
            //load textarea
            echo "<div align=\"left\">";
            echo "<textarea class=\"spaced\" name = \"input_textarea\" cols=\"80\" rows=\"" . ($textarea_rows) ."\"></textarea>";
            echo "<div class=\"clear\"></div>";
            $params = array("class"=>"spaced","number"=>3,"target"=>$module, "passthis"=>true, "label"=>"Load Data To Form");
            $this->echo_button("load_textarea", $params);
            echo "</div>";
            }  	
		}


} //end class
?>