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

# STABDARD HOOKS #
$array_global['bb_brimbox']['hooks']['bb_input_autofill'][] = array(array($main,"autofill"), array("row", "&arr_state", "arr_columns", "row_type", "parent_row_type"));
$array_global['bb_brimbox']['hooks']['bb_guest_infolinks'][] = array(array($main,"infolinks"), array());
$array_global['bb_brimbox']['hooks']['bb_viewer_infolinks'][] = array(array($main,"infolinks"), array());
$array_global['bb_brimbox']['hooks']['bb_home_infolinks'][] = array(array($main,"infolinks"), array());
$array_global['bb_brimbox']['hooks']['bb_input_postback_area'][] = array(array($main,"postback_area"), array("main", "con", "module", "arr_layouts", "arr_columns", "default_row_type", "&arr_state", "&row_type", "&row_join", "&post_key"));
$array_global['bb_brimbox']['hooks']['bb_input_top_level_records'][] = array(array($main,"top_level_records"), array("module", "arr_layouts", "&arr_column_reduced", "row_type", "row_join", "parent_row_type"));
$array_global['bb_brimbox']['hooks']['bb_input_parent_record'][] = array(array($main,"parent_record"), array("arr_column_reduced", "row_type", "row_join", "parent_id", "parent_row_type", "parent_primary"));
$array_global['bb_brimbox']['hooks']['bb_input_quick_links'][] = array(array($main,"quick_links"), array("arr_column_reduced", "arr_layouts", "inserted_id", "inserted_row_type", "inserted_primary", "parent_row_type", "parent_string", "parent_primary"));
$array_global['bb_brimbox']['hooks']['bb_input_submit_buttons'][] = array(array($main,"submit_buttons"), array("arr_column_reduced", "module", "row_type", "row_join"));
$array_global['bb_brimbox']['hooks']['bb_input_textarea_load'][] = array(array($main,"textarea_load"), array("arr_column_reduced", "arr_column", "module"));



# STANDARD INTERFACE DEFINITION #
$array = array();
$array[] = array('interface_type'=>'Standard','usertypes'=>array(1),'module_type'=>1);
$array[] = array('interface_type'=>'Standard','usertypes'=>array(2),'module_type'=>2);
$array[] = array('interface_type'=>'Standard','usertypes'=>array(3,4,5),'module_type'=>3);
$array[] = array('interface_type'=>'Auxiliary','usertypes'=>array(4,5),'module_type'=>4,'friendly_name'=>'Setup');
$array[] = array('interface_type'=>'Auxiliary','usertypes'=>array(5),'module_type'=>5,'friendly_name'=>'Admin');
$array_global['bb_brimbox']['interface'] = $array;

# COMMON VARS SHARED WITH OTHER TABS #
//will not be processed through the form posting engine
$array = array();
$array[] = 'bb_row_type';
$array[] = 'bb_row_join';
$array[] = 'bb_post_key';
$array[] = 'bb_relate';
$array_global['bb_brimbox']['common_variables'] = $array;

# LINK VARS TO DEFINE RECORD LINKS #
//$row_type and $arr_layouts must be present $main->output_links($row, $arr_layouts, $userrole) call;
$array = array();
//for standard guest interface
$array[1][] = array(array($main,'standard'), array("bb_guest_details","Details"));
$array[1][] = array(array($main,'standard'), array("bb_guest_cascade","Cascade"));
$array[1][] = array(array($main,'edit'), array("bb_guest_post","Edit"));
$array[1][] = array(array($main,'children'), array("bb_guest_post","Add","bb_guest_view","View",array('check'=>true)));
//for standard viewer interface
$array[2][] = array(array($main,'standard'), array("bb_viewer_details","Details"));
$array[2][] = array(array($main,'standard'), array("bb_viewer_cascade","Cascade"));
$array[2][] = array(array($main,'edit'), array("bb_viewer_post","Edit"));
$array[2][] = array(array($main,'children'), array("bb_viewer_post","Add","bb_viewer_view","View"));
//for standard interface
$array[3][] = array(array($main,'standard'), array("bb_details","Details"));
$array[3][] = array(array($main,'standard'), array("bb_cascade","Cascade"));
$array[3][] = array(array($main,'edit'), array("bb_input","Edit"));
$array[3][] = array(array($main,'standard'), array("bb_listchoose","List"));
$array[3][] = array(array($main,'standard'), array("bb_archive","Archive"));
$array[3][] = array(array($main,'standard'), array("bb_delete","Delete"));
$array[3][] = array(array($main,'standard'), array("bb_secure","Secure"));
$array[3][] = array(array($main,'children'), array("bb_input","Add","bb_view","View"));
//same for admin and superuser
$array[4] = $array[3];
$array[5] = $array[3];
$array_global['bb_brimbox']['links'] = $array;

# CURRENT REPORT TYPES #
//must be declared when using report functionality
$array = array();
$array[0] = "";
$array[1] = "Paginated";
$array[2] = "Full";
$array[3] = "Textarea";
$array_global['bb_brimbox']['reports'] = $array;

# HOT TAB SWITCH PRESERVE STATE #
//array that updates state when tab are switched without postback
$array = array();
/* Hot state for guest post (bb_guest_post)*/
if ($main->check("subject", "bb_guest_post"))
	{
	$array['1_bb_brimbox']['bb_guest_post'] = array("subject","body");
	}
/* Hot state for viewer post (bb_viewer_post)*/
if ($main->check("subject", "bb_viewer_post"))
	{
	$array['2_bb_brimbox']['bb_viewer_post'] = array("subject","body");
	}
/* Hot state for user details (bb_details)*/
if ($main->check("link_values", "bb_details"))
	{
	$array['5_bb_brimbox']['bb_details'] = $array['4_bb_brimbox']['bb_details'] = $array['3_bb_brimbox']['bb_details'] = array("link_values");
	}
	
/* Hot state for user input  (bb_input) */
if ($main->check("row_type", "bb_input"))
	{
	$arr = array(); //work array
    array_push($arr, "row_type");
    array_push($arr, "row_join");
    array_push($arr, "post_key");
	$row_type = $main->post("row_type", "bb_input");
	$arr_columns = $main->get_json($con, "bb_column_names");
	//removes a warning we there are no columns in a layout
	if (isset($arr_columns[$row_type]))
		{
		$arr_column = $arr_columns[$row_type];
		//loop through column layout for current posts
		foreach ($arr_column as $key => $value)
			{
			array_push($arr, $main->pad("c", $key));
			}
		}
	$array['5_bb_brimbox']['bb_input'] = $array['4_bb_brimbox']['bb_input'] = $array['3_bb_brimbox']['bb_input'] = $arr;
	unset($arr); //unset any variable used in global
	}
$array_global['bb_brimbox']['hot_state'] = $array;

//clean up array, initialization does it previously in this file
unset($array);
?>