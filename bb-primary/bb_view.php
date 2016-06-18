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
<?php
$main->check_permission(array("3_bb_brimbox", "4_bb_brimbox", "5_bb_brimbox"));

?>
<script type="text/javascript">
/* MODULE JAVASCRIPT */
function bb_reload()
    {
    //this goes off when column is changed    
    var frmobj = document.forms["bb_form"];

    frmobj.offset.value = 1;
    bb_submit_form(); //call javascript submit_form function
	return false;
    }
/* END MODULE JAVASCRIPT */
</script>

<?php
// This function displays the result set
/* INITIALIZE */
// find default row_type, $arr_layouts must have one layout set
$arr_layouts = $main->layouts($con);
$default_row_type = $main->get_default_layout($arr_layouts);

// get $POST variable
$POST = $main->retrieve($con);

// get archive mode, default Off, show only zeros
$mode = ($archive == 0) ? "1 = 1" : "archive < " . $archive;

$arr_state = $main->load($con, $module);

// coming from an view link, set $arr_state
// bb_row_type is empty if not set with javascript, empty works here
if (!empty($POST['bb_row_type']) && !$main->back($module)) {
    // global post_key and row_type
    $offset = $main->set('offset', $arr_state, 1);
    $row_type = $main->set('row_type', $arr_state, $POST['bb_row_type']); // parent
    $row_join = $main->set('row_join', $arr_state, $POST['bb_row_join']); // child
    $post_key = $main->set('post_key', $arr_state, $POST['bb_post_key']);
    $col1 = $main->set('col1', $arr_state, "create_date");
    $asc_desc = $main->set('asc_desc', $arr_state, "DESC");
}
else
// get on postback, or populate with input_state if coming from other page
{
    // local post_key and row_type
    $offset = $main->process('offset', $module, $arr_state, 1);
    $row_type = $main->process('row_type', $module, $arr_state, $default_row_type); // parent
    $row_join = $main->process('row_join', $module, $arr_state, 0); // child
    $post_key = $main->process('post_key', $module, $arr_state, 0);
    $col1 = $main->process('col1', $module, $arr_state, "create_date");
    $asc_desc = $main->process('asc_desc', $module, $arr_state, 'DESC');
}

// save state
$main->update($con, $module, $arr_state);
/**
 * * END POSTBACK **
 */
?>
<?php
/**
 * * COLUMN AND LAYOUT INFO **
 */

// get xml_column and sort column type
$arr_columns = $main->columns($con, $row_join);

// for the header left join
// get column name from "primary" attribute in column array
// this is used to populate the record header link to parent record
$parent_row_type = $main->reduce($arr_layouts, array($row_join, "parent")); // will be default of 0, $arr_columns[$parent_row_type] not set if $parent_row_type = 0
if ($parent_row_type) {
    $arr_columns_props = $main->columns_properties($con, $parent_row_type);
    $leftjoin = $main->pad("c", $arr_columns_props['primary']);
}
else {
    $leftjoin = "c01";
}
/**
 * * END COLUMN AND LAYOUT INFO **
 */

/* BEGIN REQUIRED FORM */
$main->echo_form_begin();
$main->echo_module_vars();

echo "<span class=\"spaced\">Order By:</span>";
echo "<select name=\"col1\" class=\"spaced\" onchange=\"bb_reload()\">";
// can't use automatic column function because of create and modify date
// order on create or modify date, use actual column names in this output
echo "<option value=\"create_date\" " . ($col1 == "create_date" ? "selected" : "") . ">Created&nbsp;</option>";
echo "<option value=\"modify_date\" " . ($col1 == "modify_date" ? "selected" : "") . ">Modified&nbsp;</option>";
// build field options for column names
foreach ($arr_columns as $key => $value) {
    $col = $main->pad("c", $key);
    echo "<option value=\"" . $col . "\" " . ($col == $col1 ? "selected" : "") . ">" . htmlentities($value['name']) . "&nbsp;</option>";
}
echo "</select>";
// dropdown for ascending or descending
echo "<select name=\"asc_desc\" class=\"spaced\" onchange=\"bb_reload()\">";
// build field options for column names
echo "<option value=\"ASC\" " . ($asc_desc == "ASC" ? "selected" : "") . ">Ascending&nbsp;</option>";
echo "<option value=\"DESC\" " . ($asc_desc == "DESC" ? "selected" : "") . ">Descending&nbsp;</option>";
echo "</select>";

// local POST variables
echo "<input type=\"hidden\" name=\"offset\" value=\"" . $offset . "\" />";
echo "<input type=\"hidden\" name=\"row_type\" value=\"" . $row_type . "\" />";
echo "<input type=\"hidden\" name=\"row_join\" value=\"" . $row_join . "\" />";
echo "<input type=\"hidden\" name=\"post_key\" value=\"" . $post_key . "\" />";

// global POST variables
$main->echo_common_vars();
$main->echo_form_end();
/* END FORM */

/* BEGIN RETURN ROWS */
// calculate lower limit of ordered query, return rows will be dealt with later
// initialize $count_rows in case no rows are returned
$return_rows = $main->get_constant('BB_RETURN_ROWS', 4);
$pagination = $main->get_constant('BB_PAGINATION', 5);
$count_rows = 0;
$lower_limit = ($offset - 1) * $return_rows;

// this if includes for return_rows -- repeated on page selector
if ($post_key > 0) // viewing children of record
{
    // four int(s) and a string
    $query = "SELECT count(*) OVER () as cnt, T1.*, T2.hdr, T2.row_type_left FROM data_table T1 LEFT JOIN (SELECT id, row_type as row_type_left, " . $leftjoin . " as hdr FROM data_table WHERE row_type = " . $parent_row_type . ") T2 " . "ON T1.key1 = T2.id " . "WHERE T2.id = " . $post_key . " AND row_type = " . $row_join . " AND " . $mode . " ORDER BY " . $col1 . " " . $asc_desc . ", id LIMIT " . $return_rows . " OFFSET " . $lower_limit . ";";
    // echo "<p>" . $query . "</p>";
    $result = $main->query($con, $query);

    // this outputs the return conut
    $main->return_stats($result);

    // this outputs the data blobs
    while ($row = pg_fetch_array($result)) {
        echo "<div class =\"margin divider\">";
        $main->return_header($row, "bb_cascade");
        $main->echo_clear();
        $count_rows = $main->return_rows($row, $arr_columns);
        $main->echo_clear();
        $main->output_links($row, $arr_layouts, $userrole);
        $main->echo_clear();
        echo "</div>";
    }
}
/* END RETURN ROWS */

// create the query depth selector
// also uses $count_rows variable and $return_rows global from script #2
// sets the return variable for query depth with javascript
// creates logic to make prev and next links etc
$main->page_selector("offset", $offset, $count_rows, $return_rows, $pagination);

?>
