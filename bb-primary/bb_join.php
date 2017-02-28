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
<script>
function bb_remove(k)
    {
    //change row_type, reload appropriate columns
    //this goes off when row_type is changed
    
    var frmobj = document.forms["bb_form"];
    
    frmobj.remove.value = k;
    bb_submit_form([1]); //call javascript submit_form function
	return false;
    }
</script>
<?php
/* INITIALIZE */

// get state from db
$arr_messages = array();
$arr_state = $main->load($con, $module);

$post_key = $main->init($POST['bb_post_key'], -1);
$row_type = $main->init($POST['bb_row_type'], -1);

$remove = $main->post('remove', $module, "");

if (!$main->button(array(1, 2, 3))) {
    if (($main->state('post_key_1', $arr_state, -1) < 0) && ((int)($post_key > 0))) {
        $main->set('post_key_1', $arr_state, $post_key);
        $main->set('row_type_1', $arr_state, $row_type);
    }
    else {
        if (($main->state('post_key_2', $arr_state, -1) < 0) && (int)($post_key > 0)) {
            $main->set('post_key_2', $arr_state, $post_key);
            $main->set('row_type_2', $arr_state, $row_type);
        }
        else {
            //get message
            array_push($arr_messages, __t("Error: Only two records are allowed to be joined together. Please remove one before setting the join again.", $module));
        }
    }
    $main->update($con, $module, $arr_state);
}

//remove record
if ($main->button(1)) {

    if ($main->state('post_key_1', $arr_state, -1) == $remove) {
        unset($arr_state['post_key_1']);
        unset($arr_state['row_type_1']);
    }
    else {
        if ($main->state('post_key_2', $arr_state, -1) == $remove) {
            unset($arr_state['post_key_2']);
            unset($arr_state['row_type_2']);
        }
    }
    $main->update($con, $module, $arr_state);
}

//insert join
if ($main->button(2)) {

    $main->hook('bb_join_table_row_input');

    //get messages
    $arr_messages = $main->process('arr_messages', $module, $arr_state, array());
    //unset messages
    unset($arr_state['arr_messages']);
    // update state, back to db
    $main->update($con, $module, $arr_state);
}

//delete join
if ($main->button(3)) {

    $post_key_1 = (int)$arr_state['post_key_1'];
    $post_key_2 = (int)$arr_state['post_key_2'];

    $query = "DELETE FROM join_table WHERE (join1 = " . $post_key_1 . " AND join2 = " . $post_key_2 . ") OR (join2 = " . $post_key_1 . " AND join1 = " . $post_key_2 . ");";
    $result = $main->query($con, $query);

    //get message
    if (pg_affected_rows($result) > 0) {
        array_push($arr_messages, __t("Join has been deleted.", $module));
    }
    else {
        array_push($arr_messages, __t("Error: Join not found. Possible underlying data change.", $module));
    }

    unset($arr_state['row_type_1']);
    unset($arr_state['post_key_1']);
    unset($arr_state['row_type_2']);
    unset($arr_state['post_key_2']);
    $main->update($con, $module, $arr_state);
}

$arr_layouts = $main->layouts($con);
$arr_columns = $main->columns($con, $row_type);

$arr_query = array();
if ($arr_state['post_key_1'] > 0) $arr_query[] = array('row_type' => $arr_state['row_type_1'], 'post_key' => $arr_state['post_key_1']);
if ($arr_state['post_key_2'] > 0) $arr_query[] = array('row_type' => $arr_state['row_type_2'], 'post_key' => $arr_state['post_key_2']);

$post_key_1 = (int)$arr_state['post_key_1'];
$post_key_2 = (int)$arr_state['post_key_2'];

//where also used in delete
$query = "SELECT 1 FROM join_table WHERE (join1 = " . $post_key_1 . " AND join2 = " . $post_key_2 . ") OR (join2 = " . $post_key_1 . " AND join1 = " . $post_key_2 . ");";

$result = $main->query($con, $query);

//corresponds to button
if (pg_affected_rows($result) > 0) {
    array_push($arr_messages, __t("These rows already are joined.", $module));
    $action = 3;
}
else {
    $action = 2;
}

// start module output
echo "<div class=\"spaced\">";
$main->echo_messages($arr_messages);
echo "</div>";

foreach ($arr_query as $value) {

    $row_type = $value['row_type'];
    $post_key = $value['post_key'];

    $arr_layouts = $main->layouts($con);
    $arr_columns = $main->columns($con, $row_type);

    if ($post_key > 0) {

        // get column name from "primary" attribute in column array
        // this is used to populate the record header link to parent record
        $parent_row_type = $main->reduce($arr_layouts, array($row_type, "parent")); // will be default of 0, $arr_columns[$parent_row_type] not set if $parent_row_type = 0
        if ($parent_row_type) {
            $arr_columns_props = $main->columns_properties($con, $parent_row_type);
            $leftjoin = $main->pad("c", $arr_columns_props['primary']);
            $query = "SELECT count(*) OVER () as cnt, T1.*, T2.hdr, T2.row_type_left FROM data_table T1 LEFT JOIN (SELECT id, row_type as row_type_left, " . $leftjoin . " as hdr FROM data_table) T2 ON T1.key1 = T2.id WHERE T1.id = " . $post_key . ";";
        }
        else {
            $query = "SELECT count(*) OVER () as cnt, T1.* FROM data_table T1 WHERE T1.id = " . $post_key . ";";
        }

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
        $main->echo_script_button('remove_' . $post_key, array('label' => 'Remove', 'onclick' => "bb_remove(" . $post_key . ")", 'class' => "link"));
        echo "</div>";
        echo "<div class =\"margin divider\"></div>";

    }
}

/* BEGIN REQUIRED FORM */
$main->echo_form_begin();
$main->echo_module_vars();

$main->echo_input("remove", "", array('type' => "hidden"));

if (!empty($arr_query)) {
    if ($action == 2) {
        $main->echo_button('join', array('label' => __t("Join Records", $module), 'number' => "2", 'passthis' => true));
    }
    elseif ($action == 3) {
        $main->echo_button('join', array('label' => __t("Delete Join", $module), 'number' => "3", 'passthis' => true));
    }
}

// form vars necessary for header link
$main->echo_common_vars();
$main->echo_form_end();
/* FORM */
?>