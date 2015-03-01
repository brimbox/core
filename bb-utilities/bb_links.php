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

/* PHP AND JAVASCRIPT FUNCTIONS */

/* Javascript */
/* related javascript in bb_script.js */
//bb_links.standard
//bb_links.input

/* PHP */
/* class bb_links() */
//standard
//edit
//children
//add_drill_links

//related PHP class
class bb_links extends bb_database {
	
	function standard($row, $arr_layouts_reduced, $target, $text, $params = array())
		{
		//standard row_type and post_key for a target
		$filter = isset($params['layouts']) ? $params['layouts'] : array();
		if (in_array($row['row_type'], $filter) || empty($filter))
			{
			echo "<button class = \"link rightmargin\" onclick=\"bb_links.standard(" . $row['id'] . "," . $row['row_type'] . ",'" .  $target . "'); return false;\">";
			echo $text . "</button>";
			}
		}
			
	function edit($row, $arr_layouts_reduced, $target, $text, $params = array())
		{
		//edit row, row_type and row_join are the same and from row
		//target is input and text is editable, uses js input function
		$filter = isset($params['layouts']) ? $params['layouts'] : array();
		if (!$row['archive'] && (in_array($row['row_type'], $filter) || empty($filter)))
			{
			echo "<button class = \"link rightmargin\" onclick=\"bb_links.input(" . $row['id'] . "," . $row['row_type'] . "," . $row['row_type'] . ",'" . $target . "'); return false;\">";
			echo $text . "</button>";
			}
		}
	
	function children($row, $arr_layouts_reduced, $target_add, $text_add, $target_view, $text_view, $params = array())
		{
		//view children and add child links, outputted at once
		//row_join is row_type of current row
		//row_type is form the child array
		//post_key is the parent
		$check = isset($params['check']) ? $params['check'] : false;
		//find all the children
		$arr_children = array();
		foreach($arr_layouts_reduced as $key => $value)
			{
			$secure = ($check && ($value['secure'] > 0)) ? 1 : 0;
			if (($row['row_type'] == $value['parent']) && !$secure) 
				{
				$i = $key;
				$plural = $value['plural'];
				$singular = $value['singular'];
				array_push($arr_children, array("row_type"=>$i, "singular"=>$singular, "plural"=> $plural));    
				}
			}
		//only if there are children
		if (!empty($arr_children))
			{
			foreach ($arr_children as $arr_child)
				{
				//view link, sues standard js function			
				echo "<button class = \"link rightmargin\" onclick=\"bb_links.standard(" . $row['id'] . "," . $arr_child['row_type'] . ",'" . $target_view . "'); return false;\">";
				echo $text_view . " " . $arr_child['plural'] . "</button>";
				//add link, not available when archived
				if (!$row['archive'])
					{		
					echo "<button class = \"link rightmargin\" onclick=\"bb_links.input(" . $row['id'] . "," . $row['row_type'] . "," . $arr_child['row_type'] . ",'" . $target_add . "'); return false;\">";
					echo $text_add . " " . $arr_child['singular'] . "</button>";
					}
				}
			}
		}
		
	function drill($post_key, $row_type, $arr_layouts_reduced, $target_add, $text_add)
		{
		//used for adding drill links to the standard input form
		//row_join is row_type of parent or inserted row
		//row_type is from the child array
		//post_key is the parent or inserted id
		$arr_children = array();
		foreach($arr_layouts_reduced as $key => $value)
			{
			if ($row_type == $value['parent'])
				{
				array_push($arr_children, array("row_type"=>$key, "singular"=>$value['singular'], "plural"=> $value['plural']));    
				}
			}
		if (!empty($arr_children))
			{
			foreach ($arr_children as $arr_child)
				{
				//add link, not available when archived
				echo "<button class = \"link rightmargin\" onclick=\"bb_links.input(" . $post_key . "," . $row_type . "," . $arr_child['row_type'] . ",'" . $target_add . "'); return false;\">";
				echo $text_add . " " . $arr_child['singular'] . "</button>";
				}
			}
		}		

	} //end class
?>