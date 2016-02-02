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
/* class bb_hooks() */
//infolinks
//postback_area
//top_level_records
//parent_record
//quick_links
//submit_buttons
//textarea_load

if (!class_exists('bb_input_module_hooks')) :

class bb_input_module_hooks {		
	
	//top level record selector	
	function top_level_records($main, $module, $arr_layouts, $arr_columns, $arr_state)
		{
		//buttons an record selector		
		$row_type = $main->state("row_type", $arr_state, 0); //should never be < 1
		$row_join = $main->state("row_join", $arr_state, 0); //could be 0
		
		//if (isset($arr_layouts[$row_type]['parent'])) echo "TERT";
		$parent_row_type = $main->init($arr_layouts[$row_type]['parent'], 0);
		
		$insert_or_edit = ($row_type == $row_join) ? "Edit Record" : "Insert Record";
		$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>$insert_or_edit);
		$main->echo_button("top_submit", $params);
		$params = array("class"=>"spaced","number"=>2,"target"=>$module, "passthis"=>true, "label"=>"Reset Form");
		$main->echo_button("top_reset", $params);
		//checked twice by default
		if (!empty($parent_row_type))
			{
			echo "<select name = \"row_type\" class = \"spaced\" onchange=\"bb_reload_on_layout()\">";
			echo "<option value=\"" . $row_type . "\" selected>" . $arr_layouts[$row_type]['plural'] . "&nbsp;</option>";
			echo "</select>";
			}
		//no parent, all possible top level records
		else
			{
			//get top level records
			foreach($arr_layouts as $key => $value)
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
				echo "<select name = \"row_type\" class = \"spaced\" onchange=\"bb_reload()\">";
				foreach ($arr_select as $key => $value)
					{
					echo "<option value=\"" . $key . "\" " . ($key == $row_type ? "selected" : "") . ">" . $value['plural'] . "&nbsp;</option>";
					}
				echo "</select>";
				}
			//no top level records, not common
			}
		//autoload button, not implemented
		if (isset($arr_layouts[$row_type]['autoload']))
			{
			$params = array("class"=>"spaced","number"=>4,"target"=>$module, "passthis"=>true, "label"=>"Autoload");
			$main->echo_button("top_reset", $params);				
			}
			echo "<div class=\"clear\"></div>";
		}
	
	//parent record quick links	
	function parent_record($main, $arr_columns, $arr_state)
		{		
		$row_type = $main->state('row_type', $arr_state, 0);
		$row_join = $main->state('row_join', $arr_state, 0);
		$parent_id = $main->state('parent_id', $arr_state, 0);
		$parent_row_type = $main->state('parent_row_type', $arr_state, 0);
		$parent_primary = $main->state('parent_primary', $arr_state, "");
		if (count($arr_columns) > 0) //checked twice
			{
			$edit_or_insert = ($row_type == $row_join) ? "Edit Mode" : "Insert Mode";
	
			//edit or Insert Record and primary parent column		
			$parent_string = $main->blank($parent_primary) ? "" : " - Parent: <button class=\"link colored\" onclick=\"bb_links.input(" . $parent_id . "," . $parent_row_type . "," . $parent_row_type . ",'bb_input'); return false;\">" . $parent_primary . "</button>";
			echo "<p class=\"spaced\"><span class=\"bold\">" . $edit_or_insert . "</span>" . $parent_string . "</p>";
			}
		}
	
	//quick child and sibling links
	function quick_links($main, $arr_layouts, $arr_columns, $arr_state)
		{
		//$arr_column_reduced = check for some type of record
		$inserted_id = $main->state('inserted_id', $arr_state, 0);
		$inserted_row_type = $main->state('inserted_row_type', $arr_state, 0);
		$inserted_primary = $main->state('inserted_primary', $arr_state, "");
		$parent_id = $main->state('parent_id', $arr_state, 0);
		$parent_row_type = $main->state('parent_row_type', $arr_state, 0);
		$parent_primary = $main->state('parent_primary', $arr_state, "");
		if (count($arr_columns) > 0) //checked twice
			{
			//add children links, empty works no zeros
			if (!empty($inserted_id) && !empty($inserted_row_type))
				{
				if ($main->check_child($inserted_row_type, $arr_layouts))
					{
					echo "<p class=\"spaced\"><span class=\"bold\">Add Child Record</span> - Parent: <span class=\"colored\">" . $inserted_primary . "</span> - ";
					$main->drill_links($inserted_id, $inserted_row_type, $arr_layouts, "bb_input", "Add");
					echo "</p>";
					}
				}
			//add sibling links, empty works no zeros
			if (!empty($inserted_id) && !empty($parent_id) && !empty($parent_row_type))
				{
				if ($main->check_child($parent_row_type, $arr_layouts))
					{
					echo "<p class=\"spaced\"><span class=\"bold\">Add Sibling Record</span> - Parent: <span class=\"colored\">" . $parent_primary . "</span> - ";
					$main->drill_links($parent_id, $parent_row_type, $arr_layouts, "bb_input", "Add");
					echo "</p>";
					}
				}
			}	
		}
		
	function submit_buttons($main, $module, $arr_columns, $arr_state)
		{		
		$row_type = $main->state('row_type', $arr_state, 0);
		$row_join = $main->state('row_join', $arr_state, 0);
		if (count($arr_columns) > 0) //checked twice
            {                
            $insert_or_edit = ($row_type == $row_join) ? "Edit Record" : "Insert Record";
            $params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>$insert_or_edit);
            $main->echo_button("bottom_submit", $params);
            $params = array("class"=>"spaced","number"=>2,"target"=>$module, "passthis"=>true, "label"=>"Reset Form");
            $main->echo_button("bottom_reset", $params);
            }			
		}
		
	function textarea_load($main, $module, $arr_columns)
		{
		//reduce columns
		if (count($arr_columns) > 0) //checked twice
            {
			$textarea_rows = count($arr_columns) > 0 ? count($arr_columns) : 3;
            echo "<div class=\"clear\"></div>";
            echo "<br>";
            //load textarea
            echo "<div align=\"left\">";
            echo "<textarea class=\"spaced\" name = \"input_textarea\" cols=\"80\" rows=\"" . $textarea_rows ."\"></textarea>";
            echo "<div class=\"clear\"></div>";
            $params = array("class"=>"spaced","number"=>5,"target"=>$module, "passthis"=>true, "label"=>"Load Data To Form");
            $main->echo_button("load_textarea", $params);
            echo "</div>";
            }  	
		}

} //end class

endif; //pluggable
?>