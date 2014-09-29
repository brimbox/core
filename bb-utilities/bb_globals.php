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
/* VERSION INFORMATION */

define('BRIMBOX_PROGRAM', '2014.5.400');
define('BRIMBOX_DATABASE', '2014.1.23');
define('BRIMBOX_BACKUP', '2014.1.4');

  
/* NO HTML OUTPUT */

# DEFAULT SESSION LEVEL SECURITY #
//You should declare an interface name
//$array_interface_name -- required
$array_master['bb_brimbox']['interface_name'] = "Brimbox";
//careful, standard install must have 0-5 populated, names may be changed, database stores the integer values
//$array_userroles -- required
$array_master['bb_brimbox']['userroles'] = array(0=>'Locked',1=>'Guest',2=>'Viewer',3=>'User',4=>'Superuser',5=>'Admin');
//careful, standard install must have 0-5 populated, names may be changed, database stores the integer values
//$array_module_types -- required
$array_master['bb_brimbox']['module_types'] = array(0=>"Hidden",1=>"Guest",2=>"Viewer",3=>"Tab",4=>"Setup",5=>"Admin");
//this defines the tab layout
//$array_interface_name -- required
//type -> string
//root -> string
//userroles -> array of integer
//module -> integer
//hidden -> array of integer

$array = array();
$array[] = array('interface_type'=>'Standard','userroles'=>array(1),'module_type'=>1);
$array[] = array('interface_type'=>'Standard','userroles'=>array(2),'module_type'=>2);
$array[] = array('interface_type'=>'Standard','userroles'=>array(3,4,5),'module_type'=>3);
$array[] = array('interface_type'=>'Auxiliary','userroles'=>array(4,5),'module_type'=>4,'friendly_name'=>'Setup');
$array[] = array('interface_type'=>'Auxiliary','userroles'=>array(5),'module_type'=>5,'friendly_name'=>'Admin');
$array_master['bb_brimbox']['interface'] = $array;

# INTERFACE MODULE TYPES #
$array_master['bb_brimbox']['module_types'] = array(0=>'Hidden',1=>'Guest', 2=>'Viewer', 3=>'User', 4=>'Setup', 5=>'Admin');

# DEFAULT SECURITY #
//$array_security
$array_master['bb_brimbox']['security'] = array(); //empty array causes standard 0 & 1 checkboxes
//$array_security = array(0 => "Open", 1 => "Guarded", 2 => "Management"); //Populated example
//$array_archive
$array_master['bb_brimbox']['archive'] = array(); //empty array causes standard 0 & 1 checkboxes
//$array_archive = array(0 => "Current", 1 => "Level 1", 2 => "Level 2"); //Populated example

# GUEST INDEX #
//$array_guest_index
$array_master['bb_brimbox']['guest_index'] = array(); //will use default security = 0 and search = 1
//$array_guest_index = array(0,2,3); //example compiles guest index on security 0, 2, and 3

# COMMON VARS SHARED WITH OTHER TABS #
//will not be processed through the posting engine
//$array_common_variables
$array = array();
$array[] = 'bb_row_type';
$array[] = 'bb_row_join';
$array[] = 'bb_post_key';
$array[] = 'bb_relate';
$array_master['bb_brimbox']['common_variables'] = $array;

# LINK VARS TO DEFINE RECORD LINKS #
// object required
//$row_type and $xml_layouts must be present $main->output_links($row, $xml_layouts, $userrole) call;
//$array_links
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
$array_master['bb_brimbox']['links'] = $array;

# CURRENT REPORT TYPES #
//$array_reports
$array = array();
$array[0] = "";
$array[1] = "Paginated";
$array[2] = "Full";
$array[3] = "Textarea";
$array_master['bb_brimbox']['reports'] = $array;


# DATA VALIDATION ARRAY #
//$array_validation
$array = array();
$array['Text']	= array($main,'validate_text');
$array['Number'] = array($main,'validate_numeric');
$array['Date']	= array($main,'validate_date');
$array['Email']	= array($main,'validate_email');
$array['Money']	= array($main,'validate_money');
$array['Yes/No'] = array($main,'validate_yesno');
$array_master['bb_brimbox']['validation'] = $array;


# HOT TAB SWITCH PRESERVE STATE #
//array that updates state when tab are switched without postback
$array = array();
$array[1] = array();
$array[2] = array();
$array[3] = array();
$array[4] = array();
$array[5] = array();
/* Hot state for guest post (bb_guest_post)*/
if ($main->check("subject", "bb_guest_post"))
	{
	$array[1]['bb_guest_post'] = array("subject","body");
	}
/* Hot state for viewer post (bb_viewer_post)*/
if ($main->check("subject", "bb_viewer_post"))
	{
	$array[2]['bb_viewer_post'] = array("subject","body");
	}
/* Hot state for user details (bb_details)*/
if ($main->check("link_values", "bb_details"))
	{
	$array[3]['bb_details'] = array("link_values");
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
	$array[3]['bb_input'] = $arr;
	unset($arr); //unset any variable used in global
	}
	
$array[4] = $array[3];
$array[5] = $array[3];
$array_master['bb_brimbox']['hot_state'] = $array;

//clean up array, initialization does it previously in this file
unset($array);
?>