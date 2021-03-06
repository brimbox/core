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
/* Contains recursive descent parser for boolean expression */
include ("bb_search_extra.php");

?>
<script type="text/javascript">
//reset hidden offset to 1 when search button is submitted
//standard search submit
function bb_reload()
    {
    //form names in javascript are hard coded
    var frmobj = document.forms["bb_form"];
    
    frmobj.offset.value = 1;
    bb_submit_form([1]);
	return false;
    }
</script>
<?php
/* INITIALIZE */
// find default row_type, $arr_layouts must have one layout set
$arr_layouts = $main->layouts($con);

// default row_type is 0, for all layouts
$fulltext_state = strtolower($main->get_constant('BB_FULLTEXT_STATE', 'word'));

// message pile
$arr_messages = array();

/* BEGIN STATE AND POSTBACK PROCESS */

// $POST brought in from controller


// get archive mode, default Off, show only zeros
$mode = ($archive == 0) ? "1 = 1" : "archive < " . $archive;

// get state
$arr_state = $main->load($con, $module);

$search = $main->process('search', $module, $arr_state, "");
// make a copy
$search_parsed = $search;
$offset = $main->process('offset', $module, $arr_state, 1);
$row_type = $main->process('row_type', $module, $arr_state, 0);

// archive flag checkbox
$archive_flag = $main->process('archive_flag', $module, $arr_state, 0);

// save state
$main->update($con, $module, $arr_state);
/* END STATE PROCESS */

/* PARSE SEARCH STRING */
// function parse_boolean_string calls four non-object functions, advance, token, open, operator
$boolean_parse = new php_boolean_validator();
$boolean_parse->splice_or_tokens = true;
// use 0 & 1 in case other states evolve
$boolean_parse->splice_wildcard = ($fulltext_state == "begin") ? 1 : 0;
$message = $boolean_parse->parse_boolean_string($search_parsed);
array_push($arr_messages, $message);

/* GET LAYOUT */
$arr_columns_json = $main->get_json($con, "bb_column_names");

/* BEGIN REQUIRED FORM */
// search form
$main->echo_form_begin();
$main->echo_module_vars();

echo "<div class=\"center\">";
// search vars
echo "<input type=\"text\" name=\"search\" class = \"spaced\" size=\"35\" value = \"" . __($search) . "\"/>";
$params = array("all" => true);
$main->layout_dropdown($arr_layouts, "row_type", $row_type, $params);
echo "<input type = \"hidden\"  name = \"offset\" value = \"" . $offset . "\">";

// button and end form
$params = array("class" => "spaced", "onclick" => "bb_reload()", "label" => __t("Search Database", $module));
$main->echo_script_button("post_search", $params);

// archive interworking allows quick access to archived records
if ($main->on_constant('BB_ARCHIVE_INTERWORKING')) {
    $checked = "";
    if ($archive_flag == 1) {
        $checked = "checked";
        $mode = " 1 = 1 ";
    }
    echo "<span class = \"border spaced rounded padded shaded\">";
    $main->echo_input("archive_flag", 1, array('type' => 'checkbox', 'class' => 'spaced', 'checked' => $checked));
    echo "<label class=\"spaced\">" . __t("Check Archives", $module) . "</label>";
    echo "</span><br>";
}

echo "</div>"; // end align center
// echo state variables into form
// variables to hold the $POST variables for the links
$main->echo_common_vars();
$main->echo_form_end();
/* END FORM */
?>

<?php
/* BEGIN RETURN ROWS */
// this function returns the repetitive rows from the search query
$return_rows = $main->get_constant('BB_RETURN_ROWS', 4);
$pagination = $main->get_constant('BB_PAGINATION', 5);
$count_rows = 0;
$lower_limit = ($offset - 1) * $return_rows;
$element = "offset";

// search array successfully parsed
if ($main->blank($message)) {
    $escaped_search_parsed = pg_escape_string($search_parsed); // search array is decoded
    $and_clause = ($row_type == 0) ? " 1=1 " : " row_type = " . $row_type . " ";

    // parent columns could come from a variety of rows
    // left join cols, get all possible columns, and then make them distict with _left
    $arr_parent_columns = array(0 => "c01"); // default value, if not set
    foreach ($arr_layouts as $value) // loop through arr_layout
    {
        if ($value['parent'] > 0) // indicator set to zero when xml made into array
        {
            $parent_row_type = $value['parent']; // parent row type will be zero if not set
            if (isset($arr_columns_json[$parent_row_type]['primary'])) {
                $column = $main->pad("c", $arr_columns_json[$parent_row_type]['primary']);
                array_push($arr_parent_columns, $column); // push on stack
                
            }
        }
    }

    // start building query
    $arr_parent_columns = array_unique(array_filter($arr_parent_columns)); // distinct, no empty values
    foreach ($arr_parent_columns as & $value) {
        $value = $value . " as " . $value . "_left";
    }
    $leftjoin = implode(", ", $arr_parent_columns); // implode on comma
    // must have full layout during return rows
    $query = "SELECT count(*) OVER () as cnt, T1.*, T4.*, ts_rank_cd(fts, to_tsquery('" . $escaped_search_parsed . "'), 1) as rnk, '' as hdr FROM  " . "(SELECT * FROM data_table WHERE fts @@ to_tsquery('" . $escaped_search_parsed . "')) T1 " . "LEFT JOIN " . "(SELECT id as id_left, row_type as row_type_left, " . $leftjoin . " FROM data_table) T4 " . "ON T1.key1 = T4.id_left " . "WHERE " . $mode . " AND " . $and_clause . " " . "ORDER BY rnk DESC, id DESC LIMIT " . $return_rows . " OFFSET " . $lower_limit . ";";

    // echo "<p>" . $query . "</p>";
    $result = $main->query($con, $query);

    // echo the number of rows returned -- return stats
    $cnt_rows = $main->return_stats($result);
    if ($cnt_rows == 0) {
        array_push($arr_messages, "No rows have been found");
    }

    while ($row = pg_fetch_array($result)) {
        // this sets the correct column xml -- each iteration requires new columns
        // $xml is global so there is only one round trip to the db per page load
        // get row type from returned rows
        $arr_columns = $main->columns($con, $row['row_type']);

        // get the primary column and set $row['hdr'] based on primary header
        $parent_row_type = $arr_layouts[$row['row_type']]['parent'];
        if ($parent_row_type) {
            $arr_columns_props = $main->columns_properties($con, $parent_row_type);
            $leftjoin = $main->pad("c", $arr_columns_props['primary']);
        }
        else {
            $leftjoin = "c01";
        }

        // seems to work, left will sometimes be set from previous row
        $row['hdr'] = $row[$leftjoin . "_left"];

        // echo records
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

echo "<div class=\"center\">";
$main->echo_messages($arr_messages);
echo "</div>";

$main->hook("bb_search_pagination");
?>
