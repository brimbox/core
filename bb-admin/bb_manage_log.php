<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
 * Copyright (C) Brimbox LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License Version 3 (“GNU GPL v3”)
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
<style type="text/css">
/* MODULE CSS */
/* no colors in css */
.manage_log_wrap {
	height: 400px;
	overflow-y: scroll;
}
</style>
<script>
function bb_reload()
    {    
    var frmobj = document.forms["bb_form"];
    
    bb_submit_form(); //call javascript submit_form function
	return false;
    }
</script>
<?php
$main->check_permission("5_bb_brimbox");
?>
<?php
$arr_messages = array();
$arr_options = array('1 day' => "Preserve 1 Day", '1 week' => "Preserve 1 Week", '1 month' => "Preserve 1 Month");

end($arr_options);
$default_truncate_option = key($arr_options);
reset($arr_options);

/* PRESERVE STATE */
// $POST brought in from controller


// get state from db
$arr_state = $main->load($con, $module);

// get truncate option
$truncate_option = $main->process("truncate_option", $module, $arr_state, $default_truncate_option);

// update state
$main->update($con, $module, $arr_state);

// This area is for truncating the table
if ($main->button(1)) {
    // truncate_table
    // delete query
    $query = "DELETE FROM log_table WHERE change_date + interval '" . $truncate_option . "' < now();";
    $result = $main->query($con, $query);
    $num_rows = pg_affected_rows($result);
    array_push($arr_messages, $num_rows . " rows deleted from log table.");
}

if ($main->button(2)) {
    // truncate_table
    // delete query
    $query = "DELETE FROM state_table WHERE change_date + interval '" . $truncate_option . "' < now();";
    $result = $main->query($con, $query);
    $num_rows = pg_affected_rows($result);
    array_push($arr_messages, $num_rows . " rows deleted from state table.");
}

// get all logins, order by date DESC
$query = "SELECT * FROM log_table ORDER BY change_date DESC;";
$result = $main->query($con, $query);

// title
echo "<p class=\"spaced bold larger\">Manage Log and State Tables</p>";

echo "<div class=\"padded\">";
$main->echo_messages($arr_messages);
echo "</div>";

// div container with scroll bar
echo "<div class=\"spaced padded bold\">Log Table</div>";
echo "<div class=\"spaced padded border manage_log_wrap\">";
echo "<div class=\"table padded\">";

// table header
echo "<div class=\"row shaded\">";
echo "<div class=\"bold underline extra middle cell\">Username</div>";
echo "<div class=\"bold underline extra middle cell\">Email</div>";
echo "<div class=\"bold underline extra middle cell\">IP Address/Bits</div>";
echo "<div class=\"bold underline extra middle cell\">Log Date/Time</div>";
echo "<div class=\"bold underline extra middle cell\">Action</div>";
echo "</div>";

// table rows
$i = 0;
while ($row = pg_fetch_array($result)) {
    // row shading
    $shade_class = ($i % 2) == 0 ? "even" : "odd";
    echo "<div class=\"row " . $shade_class . "\">";
    echo "<div class=\"extra middle cell\">" . $row['username'] . "</div>";
    echo "<div class=\"extra middle cell\">" . $row['email'] . "</div>";
    echo "<div class=\"extra middle cell\">" . $row['ip_address'] . "</div>";
    $date = $main->convert_date($row['change_date'], "Y-m-d h:i:s.u");
    echo "<div class=\"extra middle cell\">" . $date . "</div>";
    echo "<div class=\"extra middle cell\">" . $row['action'] . "</div>";
    echo "</div>";
    $i++;
}
echo "</div>";
echo "</div>";

/* BEGIN REQUIRED FORM */
$main->echo_form_begin();
$main->echo_module_vars();

// truncate_option select tag
$arr_options = $main->filter("bb_log_truncate_options", $arr_options);
$params = array('select_class' => "spaced", 'onchange' => "bb_reload()", 'usekey' => true);
$main->array_to_select($arr_options, "truncate_option", $truncate_option, array(), $params);

// submit button
$params = array("class" => "spaced", "number" => 1, "target" => $module, "passthis" => true, "label" => "Truncate Log Table");
$main->echo_button("truncate_log", $params);

$params = array("class" => "spaced", "number" => 2, "target" => $module, "passthis" => true, "label" => "Truncate State Table");
$main->echo_button("truncate_state", $params);

$main->echo_form_end();
/* END FORM */
?>

