<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
 * Copyright (C) Brimbox LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License Version 3 (GNU GPL v3)
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU GPL v3 for more details.
 *
 * You should have received a copy of the GNU GPL v3 along with this program.
 * If not, see http://www.gnu.org/licenses/
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
$main->check_permission(array("3_bb_brimbox", "4_bb_brimbox", "5_bb_brimbox"));

/*
 * IF $row_type = $row_join THEN
 * Use $row_join -- on Edit
*/
/*
 * ELSE $row_join is the child
 * So again use $row_join -- on Insert
*/

// $POST brought in from controller


// get $arr_state
$arr_state = $main->load($con, $module);

// POSTBACK HOOK
// hook to handle entrance into input module creates and updtaes 4arr_state
$main->hook("bb_input_module_postback");

// hook to handle autofill on entrance
// passes and initializes $arr_state
$main->hook("bb_input_module_autofill");

// save state, state is passed around as value
$main->update($con, $module, $arr_state);

/* BEGIN REQUIRED FORM */
// form outputted even if there are no columns
$main->echo_form_begin(array('enctype' => "multipart/form-data"));
$main->echo_module_vars(); // global for all modules
$main->echo_common_vars(); // common to standard interface
// Selector to display even if columns is empty
$main->hook("bb_input_top_level_records");

/* need arr_columns for test */
$row_join = $main->init($arr_state['row_join'], 0);
$arr_columns = $main->columns($con, $arr_state['row_join']);

// render form
if (!empty($arr_columns)):

    // MAIN INPUT DISPLAY FORM
    // for hooking before the form
    //render selector and buttoms, parent links, and quick links
    $main->hook("bb_input_before_render_form");

    // display inpout fields
    $main->hook("bb_input_data_table_render_form");

    // for hooking after the form
    //render buttons, and render textarea load
    $main->hook("bb_input_after_render_form");

endif;

$main->echo_form_end();
?>