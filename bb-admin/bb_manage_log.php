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

$arr_messages = array();
$arr_options = array('1 day' => __t("Preserve 1 Day", $module), '1 week' => __t("Preserve 1 Week", $module), '1 month' => __t("Preserve 1 Month", $module));

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
    $arr_printf = array($num_rows);
    array_push($arr_messages, __t("%d rows deleted from log table.", $module, $arr_printf));
}

if ($main->button(2)) {
    // truncate_table
    // delete query
    $query = "DELETE FROM state_table WHERE change_date + interval '" . $truncate_option . "' < now();";
    $result = $main->query($con, $query);
    $num_rows = pg_affected_rows($result);
    $arr_printf = array($num_rows);
    array_push($arr_messages, __t("%d rows deleted from state table.", $module, $arr_printf));
}

// get all logins, order by date DESC
$query = "SELECT * FROM log_table ORDER BY change_date DESC;";
$result = $main->query($con, $query);

// title
echo "<p class=\"spaced bold larger\">" . __t("Manage Log and State Tables", $module) . "</p>";

echo "<div class=\"padded\">";
$main->echo_messages($arr_messages);
echo "</div>";

// div container with scroll bar
echo "<div class=\"spaced padded bold\">" . __t("Log Table", $module) . "</div>";
echo "<div id=\"bb_manage_log_wrap\" class=\"spaced padded border\">";
echo "<div class=\"table padded\">";

// table header
echo "<div class=\"row shaded\">";
echo "<div class=\"bold underline extra middle cell\">" . __t("Username", $module) . "</div>";
echo "<div class=\"bold underline extra middle cell\">" . __t("Email", $module) . "</div>";
echo "<div class=\"bold underline extra middle cell\">" . __t("IP Address/Bits", $module) . "</div>";
echo "<div class=\"bold underline extra middle cell\">" . __t("Log Date/Time", $module) . "</div>";
echo "<div class=\"bold underline extra middle cell\">" . __t("Action", $module) . "</div>";
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
$params = array('class' => "spaced", 'onchange' => "bb_reload()", 'usekey' => true);
$main->array_to_select($arr_options, "truncate_option", $truncate_option, array(), $params);

// submit button
$params = array("class" => "spaced", "number" => 1, "target" => $module, "passthis" => true, "label" => __t("Truncate Log Table", $module));
$main->echo_button("truncate_log", $params);

$params = array("class" => "spaced", "number" => 2, "target" => $module, "passthis" => true, "label" => __t("Truncate State Table", $module));
$main->echo_button("truncate_state", $params);

$main->echo_form_end();
/* END FORM */
?>

