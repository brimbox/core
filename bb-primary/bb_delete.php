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

// $POST brought in from controller
$alphabet = $main->get_alphabet();

$post_key = $main->init($POST['bb_post_key'], -1);
$row_type = $main->init($POST['bb_row_type'], -1);

$delete_log = $main->on_constant('BB_DELETE_LOG');

/* BEGIN DELETE CASCADE */
if ($main->button(1)) {
    $post_key = $main->post('post_key', $module);
    $row_type = $main->post('row_type', $module);
    // recursive query for large object delete
    $query = "WITH RECURSIVE t(id) AS (SELECT id FROM data_table WHERE id = " . $post_key . " UNION ALL SELECT T1.id FROM data_table T1, t WHERE t.id = T1.key1) SELECT id FROM data_table WHERE id IN (SELECT id FROM t);";
    $result = $main->query($con, $query);
    $arr_ids = pg_fetch_all_columns($result);
    foreach ($arr_ids as $id) {
        pg_query($con, "BEGIN");
        // delete with prejudice will ignore a not exists warning
        // again, web users don't have access to pg_largeobjects only superusers do
        @pg_lo_unlink($con, $id);
        pg_query($con, "END");
    }
    //recursive join table delete
    $query = "WITH RECURSIVE t(id) AS (SELECT id FROM data_table WHERE id = " . $post_key . " UNION ALL SELECT T1.id FROM data_table T1, t WHERE t.id = T1.key1) DELETE FROM join_table WHERE join1 IN (SELECT id FROM t) OR join2 IN (SELECT id FROM t);";
    $result = $main->query($con, $query);
    $cnt_joins = pg_affected_rows($result);
    // recursive query for cascading delete
    $query = "WITH RECURSIVE t(id) AS (SELECT id FROM data_table WHERE id = " . $post_key . " UNION ALL SELECT T1.id FROM data_table T1, t WHERE t.id = T1.key1) DELETE FROM data_table WHERE id IN (SELECT id FROM t);";
    $result = $main->query($con, $query);
    $cnt_affected = pg_affected_rows($result);
    if ($cnt_affected > 0) {
        array_push($arr_messages, __t("This Cascade Delete deleted %d rows and %d joins.", $module, array($cnt_affected, $cnt_joins)));
        if ($delete_log) {
            $alpha = mb_substr($alphabet, $row_type - 1, 1);
            $message = __t("Record %s%d and %d children deleted.", $module, array($alpha, $postkey, $cnt_affected - 1));
            $main->log($con, $message);
        }

    }
    else {
        array_push($arr_messages, __t("Error: There may have been an underlying data change.", $module));
    }
} /* END CASCADE */

/* RETURN RECORD */
else {
    $query = "WITH RECURSIVE t(id) AS (SELECT id FROM data_table WHERE id = " . $post_key . " UNION ALL SELECT T1.id FROM data_table T1, t WHERE t.id = T1.key1) SELECT id FROM t;";
    $result = $main->query($con, $query);
    $cnt_cascade = pg_num_rows($result);

    $query = "WITH RECURSIVE t(id) AS (SELECT id FROM data_table WHERE id = " . $post_key . " UNION ALL SELECT T1.id FROM data_table T1, t WHERE t.id = T1.key1) SELECT id FROM join_table WHERE join1 IN (SELECT id FROM t) OR join2 IN (SELECT id FROM t);";
    $result = $main->query($con, $query);
    $cnt_joins = pg_num_rows($result);

    if ($cnt_cascade == 1) {
        array_push($arr_messages, __t("This record does not have child records.", $module));
    }
    elseif ($cnt_cascade == 2) {
        array_push($arr_messages, __t("This record has 1 child record.", $module));
    }
    elseif ($cnt_cascade > 2) {
        array_push($arr_messages, __t("This record has %d child records.", $module, array($cnt_cascade - 1)));
    }

    if ($cnt_joins == 0) {
        array_push($arr_messages, __t("This delete will not delete joins.", $module));
    }
    elseif ($cnt_joins == 1) {
        array_push($arr_messages, __t("This delete will delete 1 join.", $module));
    }
    elseif ($cnt_cascade > 1) {
        array_push($arr_messages, __t("This delete will delete %d joins.", $module, array($cnt_joins)));
    }

    if ($cnt_cascade > 0) {
        array_push($arr_messages, __t("Clicking \"Delete Cascade\" will delete this record, its children, and all joins associated with this record and its children. ", $module));
        array_push($arr_messages, __t("Caution: This cannot be undone.", $module));
    }

    $arr_layouts = $main->layouts($con);
    $arr_columns = $main->columns($con, $row_type);

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

$main->hook('bb_delete_messages');
echo "<p class=\"spaced padded\">";
$main->echo_messages($arr_messages);
echo "</p>";
echo "<br>";

/* BEGIN REQUIRED FORM */
$main->echo_form_begin();
$main->echo_module_vars();

if (!$main->button(1)) {
    $params = array("class" => "spaced", "number" => 1, "target" => $module, "slug" => $slug, "passthis" => true, "label" => __t("Delete Cascade", $module));
    $main->echo_button("delete_cascade", $params);
}

// local post_key for resubmit
$params = array('type' => "hidden");
$main->echo_input("post_key", $post_key, $params);
$main->echo_input("row_type", $row_type, $params);

// form vars necessary for header link
$main->echo_common_vars();
$main->echo_form_end();
/* FORM */
?>
