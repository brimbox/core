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
$arr_messages = array();

$archive_log = $main->on_constant('BB_ARCHIVE_LOG');

// $POST brought in from controller


$arr_archive = $array_security['row_archive'];

$post_key = $main->init($POST['bb_post_key'], -1);
$row_type = $main->init($POST['bb_row_type'], -1);

/* BEGIN ARCHIVE CASCADE */
if ($main->button(1)) {
    $post_key = $main->post('post_key', $module);
    $row_type = $main->post('row_type', $module);
    $setbit = $main->post('setbit', $module);
    // recursive query for cascading delete
    // needs to updated when postgres 9.* is standard
    // cannot use DELETE in CTE in postgres 8.4, so it is done in 2 steps
    // second step double checks for changes before execution, however one step would be better
    $query = "WITH RECURSIVE t(id) AS (SELECT id FROM data_table WHERE id = " . $post_key . " UNION ALL SELECT T1.id FROM data_table T1, t WHERE t.id = T1.key1) UPDATE data_table SET archive = " . $setbit . " WHERE id IN (SELECT id FROM t);";
    $result = $main->query($con, $query);
    $cnt_affected = pg_affected_rows($result);
    $alphabet = $main->get_alphabet();
    if ($cnt_affected > 0) {
        $alphabet = $main->get_alphabet();
        $alpha = mb_substr($alphabet, $row_type - 1, 1);
        if (empty($arr_archive)) {
            if ($setbit) {
                array_push($arr_messages, __t("This Cascade Archive archived %d rows.", $module, array($cnt_affected)));
                if ($archive_log) {
                    $arr_count = array($alpha, $post_key, $cnt_affected - 1);
                    $message = __t("Record %s%d and %d children archived.", $module, $arr_count);
                    $main->log($con, $message);
                }
            }
            elseif (!$setbit) {
                array_push($arr_messages, __t("This Retrieve Cascade retrieved %d rows.", $module, array($cnt_affected)));
                if ($archive_log) {
                    $arr_count = array($alpha, $post_key, $cnt_affected - 1);
                    $message = __t("Record %s%d and %d children retrieved.", $module, $arr_count);
                    $main->log($con, $message);
                }
            }
        }
        else {
            $arr_count = array($cnt_affected, $arr_archive[$setbit]);
            array_push($arr_messages, "This Cascade set %d rows to archive level \"%s\".");
            if ($archive_log) {
                $arr_count = array($alpha, $post_key, $cnt_affected - 1, $arr_archive[$setbit]);
                $message = __t("Record %s%d and %d children set to archive level %s.", $module, $arr_count);
                $main->log($con, $message);
            }
        }
    }
    else {
        array_push($arr_messages, __t("Error: There may have been an underlying data change.", $module));
    }
} /* END CASCADE */

/* RETURN RECORD */
else
// default behavior
{
    $query = "WITH RECURSIVE t(id) AS (" . "SELECT id FROM data_table WHERE id = " . $post_key . " " . "UNION ALL " . "SELECT T1.id FROM data_table T1, t " . "WHERE t.id = T1.key1)" . "SELECT id FROM t;";
    $result = $main->query($con, $query);
    $cnt_cascade = pg_num_rows($result);

    if ($cnt_cascade == 1) {
        array_push($arr_messages, __t("This record does not have child records.", $module));
        array_push($arr_messages, __t("Clicking \"Archive Cascade\", \"Archive Retreive\", or \"Set Archive To\" will archive or retrieve this record.", $module));
    }
    elseif ($cnt_cascade == 2) {
        array_push($arr_messages, __t("This record has 1 child record.", $module));
        array_push($arr_messages, __t("Clicking \"Archive Cascade\", \"Archive Retreive\", or \"Set Archive To\" will archive or retrieve this record and its child record.", $module));
    }
    elseif ($cnt_cascade > 2) {
        array_push($arr_messages, __t("This record has %d child records.", $module, array($cnt_cascade - 1)));
        array_push($arr_messages, __t("Clicking \"Archive Cascade\", \"Archive Retreive\", or \"Set Archive To\" will archive or retrieve this record and all its child records.", $module));
    }

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

    $query = "SELECT count(*) OVER () as cnt, T1.*, T2.hdr, T2.row_type_left FROM data_table T1 LEFT JOIN (SELECT id, row_type as row_type_left, " . $leftjoin . " as hdr FROM data_table) T2 ON T1.key1 = T2.id WHERE T1.id = " . $post_key . ";";

    $result = $main->query($con, $query);

    $main->return_stats($result);
    $row = pg_fetch_array($result);
    $setbit = $row['archive'];
    echo "<div class =\"margin divider\">";
    // outputs the row we are working with
    $main->return_header($row, "bb_cascade");
    $main->echo_clear();
    $main->return_rows($row, $arr_columns);
    $main->echo_clear();
    echo "</div>";
    echo "<div class =\"margin divider\"></div>";
}
/* END RETURN RECORD */

$main->hook('bb_archive_messages');
echo "<p class=\"spaced padded\">";
$main->echo_messages($arr_messages);
echo "</p>";
echo "<br>";

/* BEGIN REQUIRED FORM */
$main->echo_form_begin();
$main->echo_module_vars();

if (!$main->button(1)) {
    if (empty($arr_archive)) {
        $button_value = ($setbit == 0) ? 1 : 0; // set value is value to set secure to
        $button_text = ($setbit == 0) ? __t("Archive Cascade", $module) : __t("Retrieve Cascade", $module);
        $params = array("class" => "spaced", "number" => 1, "target" => $module, "slug" => $slug, "passthis" => true, "label" => $button_text);
        $main->echo_button("archive_cascade", $params);
        echo "<input type = \"hidden\"  name = \"setbit\" value = \"" . $button_value . "\">";
    }
    else {
        $params = array("class" => "spaced", "number" => 1, "target" => $module, "passthis" => true, "label" => __t("Set Archive To", $module));
        $main->echo_button("archive_cascade", $params);
        echo "<select name=\"setbit\" class=\"spaced\"\">";
        foreach ($arr_archive as $key => $value) {
            echo "<option value=\"" . __($key) . "\" " . ($key == $setbit ? "selected" : "") . ">" . __($value) . "&nbsp;</option>";
        }
        echo "</select>";
    }
}

// local post_key for resubmit
$params = array('type' => "hidden");
$main->echo_input("post_key", $post_key, $params);
$main->echo_input("row_type", $row_type, $params);

$main->echo_common_vars();
$main->echo_form_end();
/* FORM */
?>
