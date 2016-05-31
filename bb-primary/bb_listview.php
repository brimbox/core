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

$main->check_permission ( array (
		"3_bb_brimbox",
		"4_bb_brimbox",
		"5_bb_brimbox" 
) );

?>
<script type="text/javascript">
function bb_reload()
    {
    //this goes off when list is changed    
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
$arr_layouts = $main->layouts ( $con );
$default_row_type = $main->get_default_layout ( $arr_layouts );

// State vars
// do list view postback, get variables from browse_state
// view value is the list position

/**
 * * BEGIN LISTVIEW POSTBACK **
 */

// get $POST variable
$POST = $main->retrieve ( $con );

// get archive mode, default Off, show only zeros
$mode = ($archive == 0) ? "1 = 1" : "archive < " . $archive;

// get state
$arr_state = $main->load ( $con, $module );

$offset = $main->process ( 'offset', $module, $arr_state, 1 );
// change row_type, get first value for that row type
if ($main->changed ( 'row_type', $module, $arr_state, $default_row_type )) {
	$row_type = $main->process ( 'row_type', $module, $arr_state, $default_row_type );
	// use default
	$arr_lists = $main->lists ( $con, $row_type );
	$list_number = $main->get_default_list ( $arr_lists );
}  // change list
else {
	$row_type = $main->process ( 'row_type', $module, $arr_state, $default_row_type );
	$arr_lists = $main->lists ( $con, $row_type );
	// find default
	$default_list_number = $main->get_default_list ( $arr_lists );
	$list_number = $main->process ( 'list_number', $module, $arr_state, $default_list_number );
	// check in case archived since last refresh
	if (isset ( $arr_lists [$list_number] )) {
		$list_number = ! $arr_lists [$list_number] ['archive'] ? $list_number : $default_list_number;
		$main->set ( 'list_number', $arr_state, $list_number );
	}
}

/* back to string */
$main->update ( $con, $module, $arr_state );
/**
 * * END POSTBACK **
 */
?>
<?php
// get list fields, xml4 is the list fields
// get description
if (isset ( $arr_lists [$list_number] )) {
	$list = $main->reduce ( $arr_lists, $list_number );
	$description = $list ['description'];
} else {
	$list = array ();
	$description = "";
}

$arr_columns = $main->columns ( $con, $row_type );

// center
echo "<div class=\"table spaced border tablecenter\"><div class=\"row padded\">";

/* BEGIN REQUIRED FORM */
// form part, based on xml, hidden field return offset
$main->echo_form_begin ();
$main->echo_module_vars ();

echo "<div class=\"cell padded middle\">";
echo "<label class=\"spaced\">Choose List: </label>";
$params = array (
		"class" => "spaced",
		"onchange" => "bb_reload()" 
);
$main->layout_dropdown ( $arr_layouts, "row_type", $row_type, $params );
$params = array (
		"class" => "spaced",
		"onchange" => "bb_reload()" 
);
$main->list_dropdown ( $arr_lists, "list_number", $list_number, $params );
echo "</div>";

// list return
echo "<input type = \"hidden\"  name = \"offset\" value = \"" . $offset . "\">";
echo "<div class=\"cell padded middle\">";
$main->echo_textarea ( "description", $description, $params = array (
		"rows" => 2,
		"cols" => 50,
		"class" => "spaced border",
		"readonly" => "readonly" 
) );
echo "</div>";

$main->echo_common_vars ();
$main->echo_form_end ();

echo "</div></div>"; // end align center, table, row

/* BEGIN RETURN ROWS */
// calculate lower limit of ordered query, return rows will be dealt with later
// initialize $count_rows in case no rows are returned
// initialze query variables to return list in segments
$return_rows = $main->get_constant ( 'BB_RETURN_ROWS', 4 );
$pagination = $main->get_constant ( 'BB_PAGINATION', 5 );
$count_rows = 0;
$lower_limit = ($offset - 1) * $return_rows;

// list numbers should always be positive
if ($list_number > 0) {
	// if a list has been selected
	// get column name from "primary" attribute in column array
	// this is used to populate the record header link to parent record
	$parent_row_type = $main->reduce ( $arr_layouts, array (
			$row_type,
			"parent" 
	) ); // will be default of 0, $arr_columns[$parent_row_type] not set if $parent_row_type = 0
	if ($parent_row_type) {
		$arr_columns_props = $main->column_properties ( $con, $parent_row_type );
		$leftjoin = $main->pad ( "c", $arr_columns_props ['primary'] );
	} else {
		$leftjoin = "c01";
	}
	
	// query
	$query = "SELECT count(*) OVER () as cnt, T1.*, T2.hdr, T2.row_type_left FROM data_table T1 " . "LEFT JOIN (SELECT id, row_type as row_type_left, " . $leftjoin . " as hdr FROM data_table WHERE row_type = " . $parent_row_type . ") T2 " . "ON T1.key1 = T2.id " . "WHERE bb_list(list_string, " . $list_number . ") = 1 AND row_type = " . $row_type . " AND " . $mode . " ORDER BY " . $leftjoin . ", id LIMIT " . $return_rows . " OFFSET " . $lower_limit . ";";
	
	// echo "<p>" . $query . "</p>";
	$result = $main->query ( $con, $query );
	// this outputs the return conut
	$main->return_stats ( $result );
	
	// get row, set xml on row_type, echo out details
	while ( $row = pg_fetch_array ( $result ) ) {
		echo "<div class =\"margin divider\">";
		$main->return_header ( $row, "bb_cascade" );
		$main->echo_clear ();
		$count_rows = $main->return_rows ( $row, $arr_columns );
		$main->echo_clear ();
		$main->output_links ( $row, $arr_layouts, $userrole );
		echo "</div>";
		$main->echo_clear ();
	}
} // end if

/* END RETURN ROWS */
// create the query depth selector
// uses $offset variable from previous script
// also uses $count_rows variable and $return_rows global
// creates logic to make prev and next links etc

$main->page_selector ( "offset", $offset, $count_rows, $return_rows, $pagination );

?>

