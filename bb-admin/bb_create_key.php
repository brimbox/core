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
$main->check_permission("5_bb_brimbox");
?>
<script type="text/javascript">
function bb_reload()
    {
    bb_submit_form();    
    }
</script>
<?php
/* PRESERVE STATE */

// $POST brought in from controller


$arr_notes = array("49", "50");

// start code here
$arr_messages = array();

// layouts
$arr_layouts = $main->layouts($con);
$default_row_type = $main->get_default_layout($arr_layouts);
$arr_columns_json = $main->get_json($con, 'bb_column_names');

/* PRESERVE STATE */
$POST = $main->retrieve($con);;

// get state from db
$arr_state = $main->load($con, $module);

// columns -- need row_type
$row_type = $main->post('row_type', $module, $default_row_type);
$arr_columns = $main->columns($con, $row_type);

$arr_layout = $arr_layouts[$row_type];
$default_col_type = $main->get_default_column($arr_columns);

// get col_type
if ($main->changed('row_type', $module, $arr_state, $default_row_type)) {
    $col_type = $main->set('col_type', $arr_state, $default_col_type);
}
else {
    $col_type = $main->process("col_type", $module, $arr_state, $default_col_type);
}

// process row_type
$row_type = $main->process('row_type', $module, $arr_state, $default_row_type);

// update state
$main->update($con, $module, $arr_state);

// check_column
if ($main->button(1) || $main->button(2)) {
    $unique_key = isset($arr_columns_json[$row_type]['unique']) ? $arr_columns_json[$row_type]['unique'] : false;
    $column = $main->pad("c", $col_type);
    /* CHECK IF KEY SET OR POSSIBLE */
    if ($unique_key) {
        if ($main->button(1)) {
            $arr_printf = array($arr_columns[$col_type]['name'], $arr_layout['plural']);
            array_push($arr_messages, __t("Column \"%s\" on layout \"%s\" has a unique key on it.", $module, $arr_printf));
        }
        elseif ($main->button(2)) {
            $arr_printf = array($arr_layout['plural'], $arr_columns_json[$row_type][$unique_key]['name']);
            array_push($arr_messages, __t("Error: Unique Key is already set on layout \"%s\" . Column \"%s\" has a unique key on it.", $module, $arr_printf));
        }
    } // no key
    elseif ($col_type) {
        // check for duplicates
        $query = "SELECT 1 FROM (SELECT " . $column . ", count(" . $column . ") FROM data_table WHERE row_type = " . $row_type . " GROUP BY " . $column . " HAVING count(" . $column . ") > 1) T1";
        $result = $main->query($con, $query);
        if (pg_num_rows($result) > 0) {
            $arr_printf = array($arr_columns[$col_type]['name']);
            array_push($arr_messages, __t("Error: Column \"%s\" contains duplicate values. Unique key cannot be created.", $module, $arr_printf));
        }
        // check if note column
        if (in_array($column, $arr_notes)) {
            array_push($arr_messages, __t("Error: Unique Key cannot be created on Note column. Unique key cannot be created.", $module));
        }
    }
    else {
        array_push($arr_messages, __t("Error: No column available for key creation.", $module));
    }

    /* UPDATE OR REPORT ON KEY */
    // if no message, inform administartor or add key, col_type > 0 so empty works
    if (empty($arr_messages) && $col_type) {
        if ($main->button(1)) {
            // check_column
            $arr_printf = array($arr_layout['plural'], $arr_columns[$col_type]['name']);
            array_push($arr_messages, __t("Unique key can be created on layout \"%s\" column \"%s\".", $module, $arr_printf));
        }
        elseif ($main->button(2)) {
            // add_key, $col_type <> 0
            $arr_columns_json[$row_type]['unique'] = $col_type;

            // Update json row explicitly, check for valid key
            $query = "UPDATE json_table SET jsondata = '" . json_encode($arr_columns_json) . "' WHERE lookup = 'bb_column_names' " . "AND NOT EXISTS (SELECT 1 FROM data_table WHERE row_type = " . $row_type . " GROUP BY " . $column . " HAVING count(" . $column . ") > 1);";
            // echo "<p>" . $query . "</p>";
            $result = $main->query($con, $query);

            if (pg_affected_rows($result) == 1) {
                // key updated or set
                $arr_printf = array($arr_layout['plural'], $arr_columns[$col_type]['name']);
                array_push($arr_messages, __t("Unique Key has been created on layout \"%s\", column \"%s\".", $module, $arr_printf));
            }
            else {
                // something changed
                $arr_printf = array($arr_layout['plural'], $arr_columns[$col_type]['name']);
                array_push($arr_messages, __t("Unique Key has not been created on layout \"%s\", column \"%s\". Underlying data change.", $module, $arr_printf));
            }
        }
    }
}

/* REMOVE KEY */
if ($main->button(3)) {
    // remove_key
    unset($arr_columns_json[$row_type]['unique']);
    foreach ($arr_columns_json[$row_type]['alternative'] as $key => $value) {
        unset($value['unique']);
    }
    $main->update_json($con, $arr_columns_json, "bb_column_names");
    $arr_printf = array($arr_layout['plural']);
    array_push($arr_messages, __t("Unique Key has been removed for this layout type \"%s\".", $module, $arr_printf));
}
/* BEGIN REQUIRED FORM */
echo "<p class=\"spaced bold larger\">" . __t("Create Key", $module) . "</p>";

echo "<div class=\"padded\">";
$main->echo_messages($arr_messages);
echo "</div>";

$main->echo_form_begin();
$main->echo_module_vars();;
$params = array("class" => "spaced", "number" => 1, "target" => $module, "passthis" => true, "label" => __t("Check Layout", $module));
$main->echo_button("check_column", $params);
echo "<br>";
$params = array("class" => "spaced", "onchange" => "bb_reload()");
$main->layout_dropdown($arr_layouts, "row_type", $row_type, $params);
$params = array("class" => "spaced", "onchange" => "bb_reload()");
$main->column_dropdown($arr_columns, "col_type", $col_type, $params);
echo "<br>";
$params = array("class" => "spaced", "number" => 2, "target" => $module, "passthis" => true, "label" => __t("Create Key", $module));
$main->echo_button("add_key", $params);
$params = array("class" => "spaced", "number" => 3, "target" => $module, "passthis" => true, "label" => __t("Remove Key", $module));
$main->echo_button("remove_key", $params);
echo "<br><br><br>";
$main->echo_form_end();

/* END FORM */
?>

