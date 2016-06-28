<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
 * Copyright (C) Brimbox LLC
 *
 * This program is free software: you can redistribute it and/or modifyit under the terms of the GNU
 * General Public License Version 3 (�GNU GPL v3�) as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU GPL v3 for more details.
 *
 * You should have received a copy of the GNU GPL v3 along with this program.
 * If not, see http://www.gnu.org/licenses/
*/
?>
<?php
$main->check_permission(array("4_bb_brimbox", "5_bb_brimbox"));
?>
<script type="text/javascript">     
function bb_reload()
    {
    //change row_join, reload appropriate columns
    //this goes off when row_join is changed    
    var frmobj = document.forms["bb_form"];
    
    bb_submit_form(); //call javascript submit_form function
	return false;
    }
</script>

<?php
/* PRESERVE STATE */

// $POST brought in from controller


// get state from db
$arr_state = $main->load($con, $module);

// get layouts
$arr_layouts = $main->layouts($con);
$default_row_type = $main->get_default_layout($arr_layouts);

$row_join = $main->state('row_join', $arr_state, $default_row_type);
$data_area = $main->state('data_area', $arr_state, "");
$data_stats = $main->state('data_stats', $arr_state, "");
$data_file = $main->state('data_file', $arr_state, "default");
$edit_or_insert = $main->state('edit_or_insert', $arr_state, 0);

$arr_messages = $main->state('arr_messages', $arr_state, array());
$arr_errors_all = $main->state('arr_errors_all', $arr_state, array());
$arr_messages_all = $main->state('arr_messages_all', $arr_state, array());

// update state, back to db
$main->update($con, $module, $arr_state);

// get column names based on row_join/record types
$arr_layout = $arr_layouts[$row_join];

// button 1
// get column names for layout
// button 2
// submit file to textarea
// button 3
// post data to database
// title
echo "<p class=\"spaced bold larger\">Upload Data</p>";
if (count($arr_messages) > 0) {
    echo "<div class=\"spaced\">";
    $main->echo_messages($arr_messages);
    echo "</div>";
}
if (!empty($data_stats['not_validated'])) {
    echo "<div class=\"spaced\">";
    echo "<p>" . $data_stats['not_validated'] . " row(s) rejected because data validation errors.</p>";
    $main->echo_messages($arr_errors_all);
    echo "</div>";
}
if (!empty($data_stats['not_inputted'])) {
    echo "<div class=\"spaced\">";
    echo "<p>" . $data_stats['not_inputted'] . " row(s) rejected by insert algorithm.</p>";
    $main->echo_messages($arr_messages_all);
    echo "</div>";
}
if (!empty($data_stats['inputted'])) {
    echo "<div class=\"spaced\">";
    if ($edit_or_insert == 0) echo "<p>" . $data_stats['inputted'] . " row(s) inserted into database.</p>";
    if ($edit_or_insert == 1) echo "<p>" . $data_stats['inputted'] . " database row(s) edited.</p>";
    if ($edit_or_insert == 2) echo "<p>" . $data_stats['inputted'] . " database row(s) updated.</p>";
    echo "</div>";
}

/* START REQUIRED FORM */
$main->echo_form_begin(array("enctype" => "multipart/form-data"));
$main->echo_module_vars();;

// upload row_join calls dummy function
echo "<div class=\"spaced border floatleft padded\">";
$params = array("class" => "spaced", "onchange" => "bb_reload()");
$main->layout_dropdown($arr_layouts, "row_join", $row_join, $params);
$params = array("class" => "spaced", "number" => 1, "target" => $module, "passthis" => true, "label" => "Get Upload Header");
$main->echo_button("get_header", $params);
echo "</div>";
$main->echo_clear();
echo "<div class=\"spaced border floatleft padded\">";
echo "<label class=\"spaced\">Filename: </label>";
echo "<input class=\"spaced\" type=\"file\" name=\"upload_file\" id=\"file\" />";
$params = array("class" => "spaced", "number" => 2, "target" => $module, "passthis" => true, "label" => "Upload File");
$main->echo_button("submit_file", $params);
$label = "Post " . $arr_layout['plural'];
$params = array("class" => "spaced", "number" => 3, "target" => $module, "passthis" => true, "label" => $label);
$main->echo_button("submit_data", $params);
$arr_select = array(0 => "Insert", 1 => "Update");
$main->array_to_select($arr_select, "edit_or_insert", $edit_or_insert, array(), array('usekey' => true, 'class' => "spaced"));
echo "</div>";
$main->echo_clear();
echo "<textarea class=\"spaced\" name=\"data_area\" cols=\"160\" rows=\"25\" wrap=\"off\">" . $data_area . "</textarea>";
$main->echo_form_end();
/* END FORM */
?>
