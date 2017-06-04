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
function bb_set_hidden(lt)
    {
    //set vars and submit form, offset value is reset to 1
    //this goes off when letter is clicked
    var frmobj = document.forms["bb_form"];  
    frmobj.letter.value = lt;
    frmobj.offset.value = 1;
    bb_submit_form(); //call javascript submit_form function
	return false;
    }
//standard reload on dropdown change
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
$alphabet = $main->get_alphabet();

/* BROWSE AND STATE POSTBACK */

// $POST brought in from controller
// get archive mode, default Off, show only zeros
$mode = ($archive == 0) ? "1 = 1" : "archive < " . $archive;

// get state from db
$arr_state = $main->load($con, $module);

// process variables from state and postback
$letter = $main->process('letter', $module, $arr_state, mb_substr($alphabet, 0, 1));
$offset = $main->process('offset', $module, $arr_state, 1);

// must get post while preserving row_type state to reset col_type when row_type changes
$row_type = $main->post('row_type', $module, $default_row_type);
// must get arr_column on current row_type before setting default col_type
$arr_columns = $main->columns($con, $row_type);
// get default col_type or deal with possibility of no columns, then 1
$default_col_type = $main->get_default_column($arr_columns);

// if row_type changed and postback (post is different than state) use default column type
if ($main->changed('row_type', $module, $arr_state, $default_row_type)) {
    $col_type = $main->set('col_type', $arr_state, $default_col_type);
}
else {
    $col_type = $main->process('col_type', $module, $arr_state, $default_col_type);
}

// process row_type
$row_type = $main->process('row_type', $module, $arr_state, $default_row_type);
// must get arr_column again on current row_type
$arr_columns = $main->columns($con, $row_type);

// update state, back to db
$main->update($con, $module, $arr_state);
/* END POSTBACK */
?>
<?php
/* BEGIN TAB */
echo "<div class=\"center\">"; // centering
/* START REQUIRED FORM */
// form tag
$main->echo_form_begin();
$main->echo_module_vars();

echo "<div class=\"inlineblock padded larger\">"; // font size
// do alpha and numeric links
// this area make the alphabetic and numeric links including the posting javascript
for ($i = 0;$i < mb_strlen($alphabet);$i++) // alpha
{
    $alpha_number = mb_substr($alphabet, $i, 1);

    // underline and bold chosen letter
    $class = ($alpha_number == $letter) ? "link bold underline" : "link";
    echo "<button class=\"" . $class . "\"  onclick=\"bb_set_hidden('" . $alpha_number . "'); return false;\">";
    echo $alpha_number;
    echo "</button>&nbsp;";
}
echo "</div>";

echo "&nbsp;&nbsp;";

echo "<div class=\"inlineblock padded larger\">";
for ($i = 48;$i <= 57;$i++) // numeric
{
    $alpha_number = chr($i);

    // underline and bold chosen number
    $class = ($alpha_number == $letter) ? "link bold underline" : "link";
    echo "<button class=\"" . $class . "\"  onclick=\"bb_set_hidden('" . $alpha_number . "'); return false;\">";
    echo $alpha_number;
    echo "</button>&nbsp;";
}
echo "</div>"; // end font size
// end do alpha and numeric links
// get column names based on row_type/record types (repeated after state load but why not for clarity)
$col = $main->pad("c", $col_type);

// get column name from "primary" attribute in column array
// this is used to populate the record header link to parent record
// will be default of 0, $arr_columns[$parent_row_type] not set if $parent_row_type = 0
$parent_row_type = $main->reduce($arr_layouts, array($row_type, "parent"));
if ($parent_row_type) {
    $arr_columns_props = $main->columns_properties($con, $parent_row_type);
    $leftjoin = $main->pad("c", $arr_columns_props['primary']);
}
else {
    $leftjoin = "c01";
}

echo "&nbsp;&nbsp;";
echo "<div class=\"inlineblock\">";
// layout types, this produces $row_type
$params = array("onchange" => "bb_reload()");
$main->layout_dropdown($arr_layouts, "row_type", $row_type, $params);
echo "&nbsp;&nbsp;";
// column names, $column is currently selected column
$params = array("onchange" => "bb_reload()");
$main->column_dropdown($arr_columns, "col_type", $col_type, $params);
echo "</div>";

// hidden element containing the current chosen letter
echo "<input type = \"hidden\"  name = \"letter\" value = \"" . $letter . "\">";
// hidden element containing the current return page, this is related to the row offset in the query LIMIT clause
echo "<input type = \"hidden\"  name = \"offset\" value = \"" . $offset . "\">";

// these variable are only set via javascript, when a link is followed
$main->echo_common_vars();
$main->echo_form_end();
/* END FORM */

echo "</div>"; // end align center
/* BROWSE RETURN ROWS OUTPUT */
// This area displays the result set
// uses variables $arr_column, $letter, $column, $offset and $row_type
// $return_rows is a global variable which can be set
// $count_rows contains the number of rows in the query without limit
// calculate lower limit of ordered query, return rows will be dealt with later
// initialize $count_rows in case no rows are returned
$return_rows = $main->get_constant('BB_RETURN_ROWS', 4);
$pagination = $main->get_constant('BB_PAGINATION', 5);
$count_rows = 0;
$lower_limit = ($offset - 1) * $return_rows;
$element = "offset";

$esc_lt = pg_escape_string($letter);
$esc_col1 = pg_escape_string($col);

// return query
$query = "SELECT count(*) OVER () as cnt, T1.*, T2.hdr, T2.row_type_left FROM data_table T1 " . "LEFT JOIN (SELECT id, row_type as row_type_left, " . $leftjoin . " as hdr FROM data_table WHERE row_type = " . $parent_row_type . ") T2 " . "ON T1.key1 = T2.id " . "WHERE UPPER(SUBSTRING(" . $esc_col1 . " FROM 1 FOR 1)) = '" . $esc_lt . "' AND row_type = " . $row_type . " AND " . $mode . " ORDER BY " . $col . ", id LIMIT " . $return_rows . " OFFSET " . $lower_limit . ";";

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
$main->hook("bb_browse_pagination");

/**
 * * END BROWSE OUTPUT **
 */
?>
