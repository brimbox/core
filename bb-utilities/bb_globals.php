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

# STANDARD HOOKS #
//use static classes or standard functions
$array_global[$interface]['hooks']['index_main_class'][] = "/bb-blocks/bb_main_include.php";
$array_global[$interface]['hooks']['index_return_main'][] = array('func'=>"bb_main::return_main",'vars'=>array("&main"));
$array_global['bb_brimbox']['hooks']['index_hot_state'][] = array('func'=>"bb_index_hot_state",'vars'=>array("con", "main", "interface", "&array_hot_state"), 'file'=>"/bb-blocks/bb_index_hot_state.php");

$array_global['bb_brimbox']['hooks']['bb_guest_infolinks'][] = 'bb_main::infolinks';
$array_global['bb_brimbox']['hooks']['bb_viewer_infolinks'][] = 'bb_main::infolinks';
$array_global['bb_brimbox']['hooks']['bb_home_infolinks'][] = 'bb_main::infolinks';

/* $main object being brought into a redirect module */
$array_global['bb_brimbox']['hooks']['bb_input_redirect_main_class'][] = "/bb-blocks/bb_main_include.php";
$array_global['bb_brimbox']['hooks']['bb_input_redirect_return_main'][] = array('func'=>"bb_main::return_main",'vars'=>array("&main"));
$array_global['bb_brimbox']['hooks']['bb_upload_data_redirect_main_class'][] = "/bb-blocks/bb_main_include.php";
$array_global['bb_brimbox']['hooks']['bb_upload_data_redirect_return_main'][] = array('func'=>"bb_main::return_main",'vars'=>array("&main"));
$array_global['bb_brimbox']['hooks']['bb_upload_docs_redirect_main_class'][] = "/bb-blocks/bb_main_include.php";
$array_global['bb_brimbox']['hooks']['bb_upload_docs_redirect_return_main'][] = array('func'=>"bb_main::return_main",'vars'=>array("&main"));
$array_global['bb_brimbox']['hooks']['bb_manage_modules_redirect_main_class'][] = "/bb-blocks/bb_main_include.php";
$array_global['bb_brimbox']['hooks']['bb_manage_modules_redirect_return_main'][] = array('func'=>"bb_main::return_main",'vars'=>array("&main"));

/* these are the primary input and input redirect hooks */
$array_global['bb_brimbox']['hooks']['bb_input_module_postback'][] = array('func'=>"bb_input_module_postback",'vars'=>array("&arr_layouts", "&arr_columns", "&arr_dropdowns", "&arr_state", "&row"),  'file'=>"/bb-blocks/bb_input_module_postback.php");
$array_global['bb_brimbox']['hooks']['bb_input_module_autofill'][] = array('func'=>"bb_input_module_autofill",'vars'=>array("&arr_layouts", "&arr_state", "row"),  'file'=>"/bb-blocks/bb_input_module_autofill.php");
$array_global['bb_brimbox']['hooks']['bb_input_data_table_render_form'][] = array('func'=>"bb_data_table_render_form",'vars'=>array("&arr_layouts", "&arr_columns", "&arr_dropdowns", "&arr_state", "params"),  'file'=>"/bb-blocks/bb_data_table_render_form.php");
$array_global['bb_brimbox']['hooks']['bb_input_redirect_postback'][] = array('func'=>"bb_input_redirect_postback",'vars'=>array("&arr_layouts", "&arr_columns", "&arr_dropdowns", "&arr_state"),  'file'=>"/bb-blocks/bb_input_redirect_postback.php");
$array_global['bb_brimbox']['hooks']['bb_input_data_table_row_validate'][] = array('func'=>"bb_data_table_row_validate",'vars'=>array("&arr_layouts", "&arr_columns", "&arr_dropdowns", "&arr_state", "params"),  'file'=>"/bb-blocks/bb_data_table_row_validate.php");
$array_global['bb_brimbox']['hooks']['bb_input_data_table_row_input'][] = array('func'=>"bb_data_table_row_input",'vars'=>array("&arr_layouts", "&arr_columns", "&arr_dropdowns", "&arr_state", "params"),  'file'=>"/bb-blocks/bb_data_table_row_input.php");

/* these are the upload data hooks */
$array_global['bb_brimbox']['hooks']['bb_upload_data_row_validation'][] = array('func'=>"bb_data_table_row_validate",'vars'=>array("&arr_layouts", "&arr_columns", "&arr_dropdowns", "&arr_pass", "params"),  'file'=>"/bb-blocks/bb_data_table_row_validate.php");
$array_global['bb_brimbox']['hooks']['bb_upload_data_row_input'][] = array('func'=>"bb_data_table_row_input",'vars'=>array("&arr_layouts", "&arr_columns", "&arr_dropdowns", "&arr_pass", "params"),  'file'=>"/bb-blocks/bb_data_table_row_input.php");

/* these are the input hook the are buried in the render form input function */
/* note the use of include_once */
$array_global['bb_brimbox']['hooks']['bb_input_top_level_records'][] = array('func'=>"bb_input_module_hooks::top_level_records", 'vars'=>array("main", "module", "arr_layouts", "arr_columns", "arr_state"), 'file'=>"/bb-blocks/bb_input_module_hooks.php");
$array_global['bb_brimbox']['hooks']['bb_input_parent_record'][] = array('func'=>"bb_input_module_hooks::parent_record", 'vars'=>array("main", "arr_columns", "arr_state"), 'file'=>"/bb-blocks/bb_input_module_hooks.php");
$array_global['bb_brimbox']['hooks']['bb_input_quick_links'][] = array('func'=>"bb_input_module_hooks::quick_links", 'vars'=>array("main", "arr_layouts", "arr_columns", "arr_state"), 'file'=>"/bb-blocks/bb_input_module_hooks.php");
$array_global['bb_brimbox']['hooks']['bb_input_submit_buttons'][] = array('func'=>"bb_input_module_hooks::submit_buttons", 'vars'=>array("main", "arr_columns", "arr_state"), 'file'=>"/bb-blocks/bb_input_module_hooks.php");
$array_global['bb_brimbox']['hooks']['bb_input_textarea_load'][] = array('func'=>"bb_input_module_hooks::textarea_load", 'vars'=>array("main", "module", "arr_columns"), 'file'=>"/bb-blocks/bb_input_module_hooks.php");

# STANDARD INTERFACE DEFINITION #
$array = array();
$array[] = array('interface_type'=>'Standard','usertypes'=>array(1),'module_type'=>1,'landing_module'=>'bb_guest');
$array[] = array('interface_type'=>'Standard','usertypes'=>array(2),'module_type'=>2,'landing_module'=>'bb_viewer');
$array[] = array('interface_type'=>'Standard','usertypes'=>array(3,4,5),'module_type'=>3,'landing_module'=>'bb_home');
$array[] = array('interface_type'=>'Auxiliary','usertypes'=>array(4,5),'module_type'=>4,'friendly_name'=>'Setup','landing_module'=>'bb_home');
$array[] = array('interface_type'=>'Auxiliary','usertypes'=>array(5),'module_type'=>5,'friendly_name'=>'Admin','landing_module'=>'bb_home');
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
//use statis classes or standard functions
//$row_type and $arr_layouts must be present $main->output_links($row, $arr_layouts, $userrole) call;
$array = array();
//for standard guest interface
$array[1][] = array('bb_main::standard', array("bb_guest_details","Details"));
$array[1][] = array('bb_main::standard', array("bb_guest_cascade","Cascade"));
$array[1][] = array('bb_main::edit', array("bb_guest_post","Edit"));
$array[1][] = array('bb_main::children', array("bb_guest_post","Add","bb_guest_view","View",array('check'=>true)));
//for standard viewer interface
$array[2][] = array('bb_main::standard', array("bb_viewer_details","Details"));
$array[2][] = array('bb_main::standard', array("bb_viewer_cascade","Cascade"));
$array[2][] = array('bb_main::edit', array("bb_viewer_post","Edit"));
$array[2][] = array('bb_main::children', array("bb_viewer_post","Add","bb_viewer_view","View"));
//for standard interface
$array[3][] = array('bb_main::standard', array("bb_details","details","Details"));
$array[3][] = array('bb_main::standard', array("bb_cascade","cascade","Cascade"));
$array[3][] = array('bb_main::edit', array("bb_input","input","Edit"));
$array[3][] = array('bb_main::relate', array("bb_input","input","Relate"));
$array[3][] = array('bb_main::standard', array("bb_listchoose","listchoose","List"));
$array[3][] = array('bb_main::standard', array("bb_archive","archive","Archive"));
$array[3][] = array('bb_main::standard', array("bb_delete","delete","Delete"));
$array[3][] = array('bb_main::standard', array("bb_secure","secure","Secure"));
$array[3][] = array('bb_main::children', array("bb_input","input","Add","bb_view","view","View"));
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

?>