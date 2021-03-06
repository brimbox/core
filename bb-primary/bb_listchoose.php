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
<?php
$main->check_permission(array("3_bb_brimbox", "4_bb_brimbox", "5_bb_brimbox"));

?>
<?php
/* INITIALIZE */
/* BEGIN STATE */

// $POST brought in from controller


// get post_key
$post_key = $main->init($POST['bb_post_key'], -1);
$row_type = $main->init($POST['bb_row_type'], -1);

// get postback vars
if ($main->button(1)) {
    $post_key = $main->post('post_key', $module);
    $row_type = $main->post('row_type', $module);
}
/* END STATE */

// update row for adding to the list
if ($main->check('add_names', $module)) {
    $add_names = $main->post('add_names', $module);
    foreach ($add_names as $value) {
        // row_type unnecessary
        $query = "UPDATE data_table SET list_string = bb_list_set(list_string," . $value . ") WHERE id = " . $post_key . ";";
        $main->query($con, $query);
    }
}

// update row for removing from the list
if ($main->check('remove_names', $module)) {
    $remove_names = $main->post('remove_names', $module);
    foreach ($remove_names as $value) {
        // row_type unnecessary
        $query = "UPDATE data_table SET list_string = bb_list_unset(list_string," . $value . ") WHERE id = " . $post_key . ";";
        $main->query($con, $query);
    }
}

// get layout
$arr_layouts = $main->layouts($con);
$arr_columns = $main->columns($con, $row_type);

// get column name from "primary" attribute in column array
// this is used to populate the record header link to parent record
$parent_row_type = $main->reduce($arr_layouts, array($row_type, "parent")); // will be default of 0, $arr_columns[$parent_row_type] not set if $parent_row_type = 0
if ($parent_row_type) {
    $arr_columns_props = $main->columns_properties($con, $parent_row_type);
    $leftjoin = $main->pad("c", $arr_columns_props['primary']);
}
else {
    $leftjoin = "c01";
}

// one int and a string
$query = "SELECT count(*) OVER () as cnt, T1.*, T2.hdr, T2.row_type_left FROM data_table T1 " . "LEFT JOIN (SELECT id, row_type as row_type_left, " . $leftjoin . " as hdr FROM data_table) T2 " . "ON T1.key1 = T2.id " . "WHERE T1.id = " . $post_key . ";";
$result = $main->query($con, $query);

// get row, set xml on row_type, echo out details
$main->return_stats($result);
$row = pg_fetch_array($result);
echo "<div class =\"margin divider\">";
// outputs the row we are working with
$main->return_header($row, "bb_cascade");
$main->echo_clear();
$main->return_rows($row, $arr_columns);
$main->echo_clear();
echo "</div>";
echo "<div class =\"margin divider\"></div>";

// get database values for processing
$row_type = $row['row_type'];
$list_string = $row['list_string'];

// get list arr
$arr_lists = $main->lists($con, $row_type);

// start form containing select add and remove boxes
$main->echo_clear();
$main->echo_clear();
/* BEGIN REQUIRED FORM */
$main->echo_form_begin();
$main->echo_module_vars();

// select add box
echo "<div class=\"bb_listchoose_lists_table table\">";
echo "<div class=\"row\">";
echo "<div class=\"bb_listchoose_lists_header cell padded\"><p class = \"bold colored\">" . __t("Record Not In List(s)", $module) . "</p></div>";
echo "<div class=\"bb_listchoose_lists_button cell padded\"></div>";
echo "<div class=\"bb_listchoose_lists_header cell padded\"><p class = \"bold colored\">" . __t("Record In List(s)", $module) . "</p></div>";
echo "</div>";

// row
echo "<div class=\"row\">";
echo "<div class=\"bb_listchoose_lists_cell cell padded\">";
echo "<select class=\"bb_listchoose_lists_select\" name = \"add_names[]\" multiple>";
// echo the xml lists not set
foreach ($arr_lists as $key => $value) {
    $i = $key - 1; // start string at 0
    if (( int )substr($list_string, $i, 1) == 0) {
        echo "<option value=\"" . $key . "\">" . __($value['name']) . "</option>";
    }
}
echo "</select>";
echo "</div>"; // cell
echo "<div class=\"bb_listchoose_lists_button cell padded middle\">";
$params = array("class" => "spaced", "number" => 1, "target" => $module, "slug" => $slug, "passthis" => true, "label" => __t("<< Move >>", $module));
$main->echo_button("move_list", $params);
echo "</div>"; // cell
// select remove box -- multiselect
echo "<div class=\"bb_listchoose_lists_cell cell padded\">";
echo "<select class=\"bb_listchoose_lists_select\" name=\"remove_names[]\" multiple>";
// echo the xml lists already set
// no need to get $arr_list again
foreach ($arr_lists as $key => $value) {
    $i = $key - 1; // start string at 0
    if (( int )substr($list_string, $i, 1) == 1) {
        echo "<option value=\"" . $key . "\">" . __($value['name']) . "</option>";
    }
}
echo "</select>";
echo "</div>"; // cell
echo "</div>"; // row
echo "</div>"; // table
// local post_key for resubmit
$params = array('type' => "hidden");
$main->echo_input("post_key", $post_key, $params);
$main->echo_input("row_type", $row_type, $params);

// form vars necessary for header link
$main->echo_common_vars();
$main->echo_form_end();
/* END FORM */
?>
