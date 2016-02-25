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
?>
<script type="text/javascript">
//disappearing message so record input
function bb_remove_message()
    {
    document.getElementById('input_message').innerHTML = "";
	return false;
    }
//used for changing top level records
function bb_reload()
    {
    var frmobj = document.forms["bb_form"];
    //set a button of 4 for postback
    bb_submit_form([3]);
	return false;
    }
</script>
<?php
$main->check_permission("bb_brimbox", array(3,4,5));
?>
<?php
//get the $POST
$POST = $main->retrieve($con);

//get $arr_state
$arr_state = $main->load($con, $module);

//POSTBACK HOOK
//hook to handle entrance into input module
$build->hook("bb_input_module_postback");
//hook to handle autofill on entrance
$build->hook("bb_input_module_autofill");

//save state, state is passed around as value
$main->update($con, $module, $arr_state);

/* BEGIN REQUIRED FORM */
//form outputted even if there are no columns
$main->echo_form_begin(array('type'=>"enctype=\"multipart/form-data\""));
$main->echo_module_vars(); //global for all modules
$main->echo_common_vars(); //common to standard interface

/* DISPLAY INPUT FORM */
// ENTRANCE HOOKS
$build->hook("bb_input_top_level_records");

//$arr_columns can be empty
if (!empty($arr_columns)) :

//this when inserting child record
$build->hook("bb_input_parent_record");
//this to add quick child and sibling links
$build->hook("bb_input_quick_links");    
 //for hooking archive or security levels
$build->hook("bb_input_begin_archive_secure");
//for setting readonly and hidden values
$build->hook("bb_input_before_render");

//MAIN DISPLAY FORM HOOK
$build->hook("bb_input_data_table_render_form");

//EXIT HOOKS
//for hooking archive or security levels
$build->hook("bb_input_end_archive_secure");
//submit button
$build->hook("bb_input_submit_buttons");
//textarea load
$build->hook("bb_input_textarea_load");

endif;

//form end
$main->echo_form_end();
?>