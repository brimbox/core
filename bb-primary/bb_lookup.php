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
<script type="text/javascript">
/* MODULE JAVASCRIPT */
function bb_reload()
    {
    //change row_type, reload appropriate columns
    //this goes off when row_type is changed
    
    var frmobj = document.forms["bb_form"];
    
    frmobj.offset.value = 1;
    bb_submit_form(); //call javascript submit_form function
	return false;
    }
/* END MODULE JAVASCRIPT */
</script>
<?php
/* INITIALIZE */
// find default row_type, $arr_layouts must have one layout set
$arr_layouts = $main->layouts($con);
$default_row_type = $main->get_default_layout($arr_layouts);
$arr_messages = array();

/* LOOKUP AND STATE POSTBACK */

// $POST brought in from controller


// get archive mode, default Off, show only zeros
$mode = ($archive == 0) ? "1 = 1" : "archive < " . $archive;

// get state
$arr_state = $main->load($con, $module);

// process offset and archive
$offset = $main->process('offset', $module, $arr_state, 1);
$record_id = $main->process('record_id', $module, $arr_state, "");

// get row_type to find posted layout
$row_type = $main->post('row_type', $module, $default_row_type);
$arr_columns = $main->columns($con, $row_type);
// get default col_type or deal with possibility of no columns, then 1
$default_col_type = $main->get_default_column($arr_columns);

// set col_type state from default if postback and row_type changed
if ($main->changed('row_type', $module, $arr_state, $default_row_type)) {
    $col_type_1 = $main->set('col_type_1', $arr_state, $default_col_type);
    $col_type_2 = $main->set('col_type_2', $arr_state, $default_col_type);
} // else fully process col_type
else {
    $col_type_1 = $main->process('col_type_1', $module, $arr_state, $default_col_type);
    $col_type_2 = $main->process('col_type_2', $module, $arr_state, $default_col_type);
}

// process fields
$value_1 = $main->process('value_1', $module, $arr_state, "");
$value_2 = $main->process('value_2', $module, $arr_state, "");
$radio_1 = $main->process('radio_1', $module, $arr_state, 1);
$radio_2 = $main->process('radio_2', $module, $arr_state, 1);

// process row_type, earlier just got it from post
$row_type = $main->process('row_type', $module, $arr_state, $default_row_type);

// get archive flag checkbox
$archive_flag = $main->process('archive_flag', $module, $arr_state, 0);

// back to string
$main->update($con, $module, $arr_state);
/* END POSTBACK */

/* BEGIN TAB */

/* GET COLUMN AND LAYOUT VALUES */
// get column names based on row_type/record types
$arr_columns = $main->columns($con, $row_type);
$column_1 = $main->pad("c", $col_type_1);
$column_2 = $main->pad("c", $col_type_2);
/* END COLUMN AND LAYOUT VALUES */

/* PROCESS RECORD ID */
// must be done before form output, if true then parse row_type for query later
$valid_id = false;
if (!$main->blank($record_id)) {
    if (preg_match("/^[A-Za-z]\d+/", $record_id)) {
        // take off integer for test
        $id = substr($record_id, 1);
        if (filter_var($id, FILTER_SANITIZE_NUMBER_INT)) {
            $valid_id = true;
        }
        else {
            array_push($arr_messages, __t("Record ID integer supplied is too large. Please supply a valid Record ID.", $module));
        }
    }
    else {
        array_push($arr_messages, __t("Record ID not in correct format. Must be formatted as a letter following by an integer.", $module));
    }
}
/* END PROCESS RECORD ID */

/* START REQUIRED FORM */
$main->echo_form_begin();
$main->echo_module_vars();

// layout types, this produces $row_type
// use a table
echo "<div class=\"spaced\">";
$main->echo_messages($arr_messages);
echo "</div>";

echo "<div id=\"bb_lookup_searchform_wrapper\" class=\"margin1\">";
echo "<div class=\"minusleft spacertop\">";
$params = array("class" => "spacednowrap", "onclick" => "bb_reload()", "label" => __t("Submit Lookup", $module));
$main->echo_script_button("lookup_button", $params);

if ($main->on_constant('BB_ARCHIVE_INTERWORKING')) {
    $checked = "";
    if ($archive_flag == 1) {
        $checked = "checked";
        $mode = " 1 = 1 ";
    }
    echo "<span class = \"spaced border nowrap rounded padded shaded\">";
    $main->echo_input("archive_flag", 1, array('type' => 'checkbox', 'class' => 'spaced', 'checked' => $checked));
    echo "<label class=\"spaced\">" . __t("Check Archives", $module) . "</label>";
    echo "</span><br>";
    echo "</span>";
}
echo "</div>";
echo "<div class=\"minusleft spacertop inlineblock border padded\">";
echo "<div class=\"spaced\">" . __t("Record ID", $module) . "</div>";
echo "<input class=\"spaced maxshort\" type =\"text\" name = \"record_id\" value = \"" . $record_id . "\">";
echo "</div>";

echo "<div class=\"minusleft spacertop inlineblock border padded\">";
echo "<div class=\"spaced\">" . __t("Layout", $module) . "</div>";
echo "<div class=\"inlineblock margin3\">";
$params = array("onchange" => "bb_reload()");
$main->layout_dropdown($arr_layouts, "row_type", $row_type, $params);
echo "</div>";
echo "</div>";

echo "<div class=\"minusleft spacertop inlineblock border padded\">";
echo "<div class=\"spaced\">" . __t("First Lookup Column", $module) . "</div>";
echo "<input class=\"spaced maxmedium\" type=\"text\" name = \"value_1\" value = \"" . $value_1 . "\">";
echo "<div class=\"inlineblock spaced nowrap\">";
echo "<span>" . __t("Begins:", $module) . "</span><input type=\"radio\" class=\"middle\" name=\"radio_1\" value=\"1\"" . ($radio_1 == 1 ? "checked" : "") . " >";
echo "<span> " . __t("Exact:", $module) . "</span><input type=\"radio\" class=\"middle\" name=\"radio_1\" value=\"2\"" . ($radio_1 == 2 ? "checked" : "") . ">";
echo "<span> " . __t("Like:", $module) . "</span><input type=\"radio\" class=\"middle\" name=\"radio_1\" value=\"3\"" . ($radio_1 == 3 ? "checked" : "") . ">";
echo "<span> " . __t("Empty:", $module) . "</span><input type=\"radio\" class=\"middle\" name=\"radio_1\" value=\"4\"" . ($radio_1 == 4 ? "checked" : "") . ">";
echo "</div>";
echo "<div class=\"inlineblock spaced\">";
$main->column_dropdown($arr_columns, "col_type_1", $col_type_1);
echo "</div>";
echo "</div>";

echo "<div class=\"minusleft spacertop inlineblock border padded\">";
echo "<div class=\"spaced\">" . __t("Second Lookup Column", $module) . "</div>";
echo "<input class=\"spaced maxmedium\" type=\"text\" name = \"value_2\" value = \"" . $value_2 . "\">";
echo "<div class=\"inlineblock spaced nowrap\">";
echo "<span>" . __t("Begins:", $module) . "</span><input type=\"radio\" class=\"middle\" name=\"radio_2\" value=\"1\"" . ($radio_2 == 1 ? "checked" : "") . " >";
echo "<span> " . __t("Exact:", $module) . "</span><input type=\"radio\" class=\"middle\" name=\"radio_2\" value=\"2\"" . ($radio_2 == 2 ? "checked" : "") . ">";
echo "<span> " . __t("Like:", $module) . "</span><input type=\"radio\" class=\"middle\" name=\"radio_2\" value=\"3\"" . ($radio_2 == 3 ? "checked" : "") . ">";
echo "<span> " . __t("Empty:", $module) . "</span><input type=\"radio\" class=\"middle\" name=\"radio_2\" value=\"4\"" . ($radio_2 == 4 ? "checked" : "") . ">";
echo "</div>";
echo "<div class=\"inlineblock spaced\">";
$main->column_dropdown($arr_columns, "col_type_2", $col_type_2);
echo "</div>";
echo "</div>";

echo "</div>";

// hidden element containing the current return page, this is related to the row offset in the query LIMIT clause
echo "<input type = \"hidden\"  name = \"offset\" value = \"" . $offset . "\">";

// these variables hold the variables used when a return record link is selected
// these variables are for the links that follow every return record
// these variable are only set via javascript, when a link is followed
// post_key is the record id or drill down record key (two uses)
$main->echo_common_vars();
$main->echo_form_end();
/* END FORM */

/* LOOKUP RETURN ROWS OUTPUT */
// This area displays the result set
// calculate lower limit of ordered query, return rows will be dealt with later
// initialize $count_rows in case no rows are returned
$return_rows = $main->get_constant('BB_RETURN_ROWS', 4);
$pagination = $main->get_constant('BB_PAGINATION', 5);
$count_rows = 0;
$lower_limit = ($offset - 1) * $return_rows;
$element = "offset";

/* BUILD QUERY */
// set and clauses defaults, $and_clause_3 assures no return values if empty
$and_clause_1 = " 1 = 1 "; // value_1
$and_clause_2 = " 1 = 1 "; // value_2
$and_clause_3 = " 1 = 0 "; // test for value_1 or value_2
$and_clause_4 = " 1 = 1 "; // record_id
// must test for '0' string which will evaluate as empty
// $test_1 & $test_2 true if populated
if ($valid_id) // record_id
{
    // deal with different row_type and column xml
    $row_type = ord(strtolower(substr($record_id, 0, 1))) - 96;
    $layout = $main->pad("l", $row_type);
    // reset column for output, and layout for parent row type, state will remain
    $layout = $main->reduce($layout, $row_type);
    $arr_columns = $main->columns($con, $row_type);
    $and_clause_3 = " 1 = 1 ";
    $and_clause_4 = " id = " . $id . " ";
}
else
// value_1 or value_2
{
    $test_1 = ( boolean )(!$main->blank($value_1) || ($radio_1 == 4));
    $test_2 = ( boolean )(!$main->blank($value_2) || ($radio_2 == 4));

    // $and_clause_1, based on radio type
    if ($test_1) {
        switch ($radio_1) {
            case 1:
                $and_clause_1 = " " . $column_1 . " ILIKE '" . pg_escape_string($value_1) . "%' ";
            break;
            case 2:
                $and_clause_1 = " UPPER(" . $column_1 . ") = UPPER('" . pg_escape_string($value_1) . "')";
            break;
            case 3:
                $and_clause_1 = " " . $column_1 . " ILIKE '%" . pg_escape_string($value_1) . "%' ";
            break;
            case 4:
                $and_clause_1 = " trim(both FROM " . $column_1 . ") = '' ";
            break;
        }
    }
    // $and_clause_2, switch on radio type
    if ($test_2) {
        switch ($radio_2) {
            case 1:
                $and_clause_2 = " " . $column_2 . " ILIKE '" . pg_escape_string($value_2) . "%' ";
            break;
            case 2:
                $and_clause_2 = " UPPER(" . $column_2 . ") = UPPER('" . pg_escape_string($value_2) . "')";
            break;
            case 3:
                $and_clause_2 = " " . $column_2 . " ILIKE '%" . pg_escape_string($value_2) . "%' ";
            break;
            case 4:
                $and_clause_2 = " trim(both FROM " . $column_2 . ") = '' ";
            break;
        }
    }
    // $and_clause_3 set to 1 = 1 if not empty
    if ($test_1 || $test_2) {
        $and_clause_3 = " 1 = 1 ";
    }
}

// this must be done after row_type settled, row_type now set for query
// this does not need to be done before the form
// get column name from "primary" attribute in column xml
// this is used to populate the record header link to parent record for all queries
// get column name from "primary" attribute in column array
// this is used to populate the record header link to parent record
$layout = $main->reduce($arr_layouts, $row_type);
$parent_row_type = $layout['parent']; // will be default of 0, $arr_columns[$parent_row_type] not set if $parent_row_type = 0
if ($parent_row_type) {
    $arr_columns_props = $main->columns_properties($con, $parent_row_type);
    $leftjoin = $main->pad("c", $arr_columns_props['primary']);
}
else {
    $leftjoin = "c01";
}

// return query, order by column 1 and column 2
$query = "SELECT count(*) OVER () as cnt, T1.*, T2.hdr, T2.row_type_left FROM (SELECT * FROM data_table WHERE " . $and_clause_4 . ") T1 " . "LEFT JOIN (SELECT id, row_type as row_type_left, " . $leftjoin . " as hdr FROM data_table WHERE row_type = " . $parent_row_type . ") T2 " . "ON T1.key1 = T2.id " . "WHERE  " . $and_clause_1 . " AND " . $and_clause_2 . " AND " . $and_clause_3 . " AND row_type = " . $row_type . " AND " . $mode . " ORDER BY " . $column_1 . " ASC, " . $column_2 . " ASC , id LIMIT " . $return_rows . " OFFSET " . $lower_limit . ";";
/* END BUILD QUERY */

// echo "<p>" . $query . "</p>";
$result = $main->query($con, $query);

// this outputs the return count and time of query
$main->return_stats($result);

// this outputs the table
// will repetatively output records by while loop based on the $offset from query
while ($row = pg_fetch_array($result)) {
    echo "<div class =\"margin divider\">";
    // returns header with parent link
    $main->return_header($row, "bb_cascade");
    $main->echo_clear();
    // returns the record data in appropriate row
    $count_rows = $main->return_rows($row, $arr_columns);
    $main->echo_clear();
    // return the links along the bottom of a record
    $main->output_links($row, $arr_layouts, $userrole);
    echo "</div>";
    $main->echo_clear();
}

// record selector at bottom
$main->hook("bb_lookup_pagination");

/**
 * * END LOOKUP OUTPUT **
 */
?>
