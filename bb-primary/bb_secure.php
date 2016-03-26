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

$main->check_permission ( "bb_brimbox", array (
		3,
		4,
		5 
) );
?>
<?php

/* INITIALIZE */
// error message pile
$arr_messages = array ();

// get $POST variable
$POST = $main->retrieve ( $con );

$arr_security = $array_header ['row_security'];

$post_key = $main->init ( $POST ['bb_post_key'], - 1 );
$row_type = $main->init ( $POST ['bb_row_type'], - 1 );

// handle security levels constant
/* BEGIN SECURE CASCADE */
if ($main->button ( 1 )) {
	// get postback vars
	$post_key = $main->post ( 'post_key', $module );
	$row_type = $main->post ( 'row_type', $module );
	$setbit = $main->post ( 'setbit', $module );
	// recursive query for cascading action
	// needs to be updated when postgres 9.* is standard
	// second step double checks for changes before execution, however one step would be better
	$query = "WITH RECURSIVE t(id) AS (" . "SELECT id FROM data_table WHERE id = " . $post_key . " " . "UNION ALL " . "SELECT T1.id FROM data_table T1, t " . "WHERE t.id = T1.key1)" . "SELECT id FROM t;";
	$result = $main->query ( $con, $query );
	$cnt_cascade = pg_num_rows ( $result );
	$arr_ids = pg_fetch_all_columns ( $result, 0 );
	$ids_archive = implode ( ",", $arr_ids );
	$union_archive = "SELECT " . implode ( " as id UNION SELECT ", $arr_ids ) . " as id";
	
	// Update with join and double check nothing has changed
	$query = "UPDATE data_table SET secure = " . $setbit . " " . "FROM (" . $union_archive . ") T1 " . "WHERE data_table.id = T1.id AND EXISTS (SELECT 1 " . "WHERE (SELECT count(T1.id) FROM data_table T1 INNER JOIN (" . $union_archive . ") T2 " . "ON T1.id = T2.id) = " . $cnt_cascade . ");";
	
	$result = $main->query ( $con, $query );
	$cnt_affected = pg_affected_rows ( $result );
	if ($cnt_affected > 0) {
		if (empty ( $arr_secure )) {
			if ($setbit) {
				array_push ( $arr_messages, "This Cascade Secure secured " . $cnt_affected . " rows." );
			} elseif (! $setbit) {
				array_push ( $arr_messages, "This Unsecure Cascade unsecured " . $cnt_affected . " rows." );
			}
		} else {
			array_push ( $arr_messages, "This Cascade action set " . $cnt_affected . " rows to security level \"" . $arr_secure [$setbit] . "\"." );
		}
	} else {
		array_push ( $arr_messages, "Error: There may have been an underlying data change." );
	}
}  /* END CASCADE */

/* RETURN RECORD */
else {
	// get count of records to secure
	$query = "WITH RECURSIVE t(id) AS (" . "SELECT id FROM data_table WHERE id = " . $post_key . " " . "UNION ALL " . "SELECT T1.id FROM data_table T1, t " . "WHERE t.id = T1.key1)" . "SELECT id FROM t;";
	$result = $main->query ( $con, $query );
	$cnt_cascade = pg_num_rows ( $result );
	
	if ($cnt_cascade > 1) {
		array_push ( $arr_messages, "This record has " . ($cnt_cascade - 1) . " child records." );
		array_push ( $arr_messages, "Clicking \"Secure Cascade\", \"Unsecure Cascade\", or \"Set Security To\" will secure this record and all its child records." );
	} else {
		array_push ( $arr_messages, "This record does not have child records." );
	}
	
	$arr_layouts = $main->layouts ( $con );
	$arr_columns = $main->columns ( $con, $row_type );
	
	// get column name from "primary" attribute in column array
	// this is used to populate the record header link to parent record
	$parent_row_type = $main->reduce ( $arr_layouts, array (
			$row_type,
			"parent" 
	) ); // will be default of 0, $arr_columns[$parent_row_type] not set if $parent_row_type = 0
	if ($parent_row_type) {
		$arr_columns_props = $main->properties ( $con, $parent_row_type );
		$leftjoin = $main->pad ( "c", $arr_columns_props ['primary'] );
	} else {
		$leftjoin = "c01";
	}
	
	// return record
	$query = "SELECT count(*) OVER () as cnt, T1.*, T2.hdr, T2.row_type_left FROM data_table T1 " . "LEFT JOIN (SELECT id, row_type as row_type_left, " . $leftjoin . " as hdr FROM data_table) T2 " . "ON T1.key1 = T2.id " . "WHERE T1.id = " . $post_key . ";";
	
	$result = $main->query ( $con, $query );
	
	$main->return_stats ( $result );
	$row = pg_fetch_array ( $result );
	// determine to secure or unsecure
	$setbit = $row ['secure'];
	echo "<div class =\"margin divider\">";
	// outputs the row we are working with
	$main->return_header ( $row, "bb_cascade" );
	$main->echo_clear ();
	$main->return_rows ( $row, $arr_columns );
	$main->echo_clear ();
	echo "</div>";
	echo "<div class =\"margin divider\"></div>";
}
/* END RETURN RECORD */

$main->hook ( 'bb_secure_messages' );
echo "<p class=\"spaced padded\">";
$main->echo_messages ( $arr_messages );
echo "</p>";
echo "<br>";

/* BEGIN REQUIRED FORM */
$main->echo_form_begin ();
$main->echo_module_vars ();

if (! $main->button ( 1 )) {
	if (empty ( $arr_security )) {
		$button_value = ($setbit == 0) ? 1 : 0; // set value is value to set secure to
		$button_text = ($setbit == 0) ? "Secure Cascade" : "Unsecure Cascade";
		$params = array (
				"class" => "spaced",
				"number" => 1,
				"target" => $module,
				"passthis" => true,
				"label" => $button_text 
		);
		$main->echo_button ( "secure_cascade", $params );
		echo "<input type = \"hidden\"  name = \"setbit\" value = \"" . $button_value . "\">";
	} else {
		$params = array (
				"class" => "spaced",
				"number" => 1,
				"target" => $module,
				"slug" => $slug,
				"passthis" => true,
				"label" => "Set Security To" 
		);
		$main->echo_button ( "secure_cascade", $params );
		echo "<select name=\"setbit\" class=\"spaced\"\">";
		foreach ( $arr_security as $key => $value ) {
			echo "<option value=\"" . $key . "\" " . ($key == $setbit ? "selected" : "") . ">" . htmlentities ( $value ) . "&nbsp;</option>";
		}
		echo "</select>";
	}
}

// local post_key for resubmit
$params = array (
		'type' => "hidden" 
);
$main->echo_input ( "post_key", $post_key, $params );
$main->echo_input ( "row_type", $row_type, $params );

// form vars necessary for header link
$main->echo_common_vars ();
$main->echo_form_end ();
/* FORM */
?>
