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

# DEFAULT SESSION LEVEL SECURITY #
//careful, standard install must have 0-5 populated, names may be changed, database stores the integer values
$array_userroles = array(0=>'Locked',1=>'Guest',2=>'Viewer',3=>'User',4=>'Superuser',5=>'Admin');
//careful, standard install must have 0-5 populated, names may be changed, database stores the integer values
$array_module_types = array(0=>"Hidden",1=>"Guest",2=>"Viewer",3=>"Tab",4=>"Setup",5=>"Admin");

# DEFAULT SECURITY #
$array_security = array(); //empty array causes standard 0 & 1 checkboxes
//$array_security = array(0 => "Open", 1 => "Guarded", 2 => "Management"); //Populated example
$array_archive = array(); //empty array causes standard 0 & 1 checkboxes
//$array_archive = array(0 => "Current", 1 => "Level 1", 2 => "Level 2"); //Populated example

# GUEST INDEX #
$array_guest_index = array(); //will use default security = 0 and search = 1
//$array_guest_index = array(0,2,3); //example compiles guest index on security 0, 2, and 3

# COMMON VARS SHARED WITH OTHER TABS #
//will not be processed through the posting engine
$array_common_variables = array();
$array_common_variables[] = 'bb_row_type';
$array_common_variables[] = 'bb_row_join';
$array_common_variables[] = 'bb_post_key';
$array_common_variables[] = 'bb_relate';

# LINK VARS TO DEFINE RECORD LINKS #
// object required
//$row_type and $xml_layouts must be present $main->output_links($row, $xml_layouts, $userrole) call;
$array_links = array();
//for standard guest interface
$array_links[1][] = array(array($main,'standard'), array("bb_guest_details","Details"));
$array_links[1][] = array(array($main,'standard'), array("bb_guest_cascade","Cascade"));
$array_links[1][] = array(array($main,'edit'), array("bb_guest_post","Edit"));
$array_links[1][] = array(array($main,'children'), array("bb_guest_post","Add","bb_guest_view","View",array('check'=>true)));
//for standard viewer interface
$array_links[2][] = array(array($main,'standard'), array("bb_viewer_details","Details"));
$array_links[2][] = array(array($main,'standard'), array("bb_viewer_cascade","Cascade"));
$array_links[2][] = array(array($main,'edit'), array("bb_viewer_post","Edit"));
$array_links[2][] = array(array($main,'children'), array("bb_viewer_post","Add","bb_viewer_view","View"));
//for standard interface
$array_links[3][] = array(array($main,'standard'), array("bb_details","Details"));
$array_links[3][] = array(array($main,'standard'), array("bb_cascade","Cascade"));
$array_links[3][] = array(array($main,'edit'), array("bb_input","Edit"));
$array_links[3][] = array(array($main,'standard'), array("bb_listchoose","List"));
$array_links[3][] = array(array($main,'standard'), array("bb_archive","Archive"));
$array_links[3][] = array(array($main,'standard'), array("bb_delete","Delete"));
$array_links[3][] = array(array($main,'standard'), array("bb_secure","Secure"));
$array_links[3][] = array(array($main,'children'), array("bb_input","Add","bb_view","View"));
//same for admin and superuser
$array_links[4] = $array_links[3];
$array_links[5] = $array_links[3];

# CURRENT REPORT TYPES #
$array_reports[0] = "";
$array_reports[1] = "Paginated";
$array_reports[2] = "Full";
$array_reports[3] = "Textarea";

# DATA VALIDATION ARRAY #
$array_validation = array();
$array_validation['Text']	= array($main,'validate_text');
$array_validation['Number']	= array($main,'validate_numeric');
$array_validation['Date']	= array($main,'validate_date');
$array_validation['Email']	= array($main,'validate_email');
$array_validation['Money']	= array($main,'validate_money');
$array_validation['Yes/No']	= array($main,'validate_yesno');


# HOT TAB SWITCH PRESERVE STATE #
//array that updates state when tab are switched without postback
$array_hot_state[1] = array();
$array_hot_state[2] = array();
$array_hot_state[3] = array();
$array_hot_state[4] = array();
$array_hot_state[5] = array();
/* Hot state for guest post (bb_guest_post)*/
if ($main->check("subject", "bb_guest_post"))
	{
	$array_hot_state[1]['bb_guest_post'] = array("subject","body");
	}
/* Hot state for viewer post (bb_viewer_post)*/
if ($main->check("subject", "bb_viewer_post"))
	{
	$array_hot_state[2]['bb_viewer_post'] = array("subject","body");
	}
/* Hot state for user details (bb_details)*/
if ($main->check("link_values", "bb_details"))
	{
	$array_hot_state[3]['bb_details'] = array("link_values");
	}
	
/* Hot state for user input  (bb_input) */
if ($main->check("row_type", "bb_input"))
	{
	$arr = array(); //work array
	array_push($arr, "row_type");
	array_push($arr, "row_join");
	array_push($arr, "post_key");
	$row_type = $main->post("row_type","bb_input");
	$xml_columns = $main->get_xml($con, "bb_column_names");
	$layout = $main->pad("l",$row_type);
	//removes a warning we there are no columns in a layout
	if (isset($xml_columns->$layout))
		{
		$xml_column = $xml_columns->$layout;
		//loop through column layout for current posts
		foreach ($xml_column->children() as $child)
			{
			array_push($arr, $child->getName());
			}
		}
	$array_hot_state[3]['bb_input'] = $arr;
	unset($arr); //unset any variable used in global
	}
	
$array_hot_state[4] = $array_hot_state[3];
$array_hot_state[5] = $array_hot_state[3];
?>