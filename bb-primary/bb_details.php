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
<script type="text/javascript">
//clear the textarea dump	
function bb_clear_textarea()
	{
    document.forms["bb_form"].dump_area.value = "";
	return false;
	}
//select div field when label is clicked
function bb_select_field(div_id)
	{
    var node = document.getElementById(div_id);

    if ( document.selection )
        {
        var range = document.body.createTextRange();
        range.moveToElementText(node);
        range.select();
        }
    else if ( window.getSelection )
        {
        var range = document.createRange();
        range.selectNodeContents(node);
        window.getSelection().removeAllRanges();
        window.getSelection().addRange( range );
        }        
 	}
</script>

<?php
/* INITIALIZE */
// find default row_type, $arr_layouts must have one layout set
$arr_layouts = $main->layouts ( $con );
$default_row_type = $main->get_default_layout ( $arr_layouts );

$arr_relate = array (
		41,
		42,
		43,
		44,
		45,
		46 
);
$arr_file = array (
		47 
);
$arr_reserved = array (
		48 
);
$arr_notes = array (
		49,
		50 
);

// message pile
$arr_messages = array ();

/* START STATE AND DETAILS POSTBACK */

// get $POST variable
$POST = $main->retrieve ( $con );

// get archive mode
$mode = ($archive == 1) ? " 1 = 1 " : " archive IN (0)";

// get state
$arr_state = $main->load ( $con, $module );

// coming from an add or edit link, reset $arr_state, row_type and post key should be positive
if (! empty ( $POST ['bb_row_type'] )) {
	// global post variables
	$row_type = $main->set ( 'row_type', $arr_state, $POST ['bb_row_type'] );
	$post_key = $main->set ( 'post_key', $arr_state, $POST ['bb_post_key'] );
	$link_values = $main->set ( 'link_values', $arr_state, "" );
} else // default = nothing, or populate with input_state if coming from other page
{
	// local post variables
	$row_type = $main->process ( 'row_type', $module, $arr_state, $default_row_type );
	$post_key = $main->process ( 'post_key', $module, $arr_state, 0 );
	$link_values = $main->process ( 'link_values', $module, $arr_state, "" );
}

$main->update ( $con, $module, $arr_state );

/**
 * * END DETAILS POSTBACK **
 */
?>
<?php

$text_str = "";

if ($post_key > 0) // a detail of a record
{
	// post_key is an int, row_type to find record id
	$letter = strtoupper ( chr ( $row_type + 96 ) );
	$query = "SELECT count(*) OVER () as cnt, * FROM data_table WHERE id = " . $post_key . ";";
	// echo "<p>" . $query . "</p>";
	$result = $main->query ( $con, $query );
	
	// this outputs the return stats
	$main->return_stats ( $result );
	// get row, check cnt for existance, echo out details
	$cnt_rows = pg_num_rows ( $result );
	
	if ($cnt_rows == 1) {
		$row = pg_fetch_array ( $result );
		
		// get columns for details row_type
		$arr_columns = $main->columns ( $con, $row_type );
		
		// call to function that outputs details
		$layout = $main->reduce ( $arr_layouts, $row_type );
		echo "<p class =\"spaced\">Record: " . $letter . $post_key . " - " . htmlentities ( ( string ) $layout ['singular'] ) . "</p>";
		/* return the details */
		echo "<div id=\"bb_details_fields\">";
		foreach ( $arr_columns as $key => $value ) {
			$col2 = $main->pad ( "c", $key );
			$field_id = "details_" . $main->make_html_id ( $row_type, $key );
			if (in_array ( $key, $arr_notes )) // notes
{
				$str_details = str_replace ( "\n", "<br>", htmlentities ( $row [$col2] ) );
				echo "<label class = \"spaced left floatleft overflow medium shaded\" onclick=\"bb_select_field('" . $field_id . "')\">" . htmlentities ( $value ['name'] ) . ":</label>";
				$main->echo_clear ();
				// double it up for emheight
				echo "<div class=\"spaced border half\">";
				echo "<div id=\"" . $field_id . "\" class=\"spaced emheight\">" . $str_details . "</div></div>";
				$main->echo_clear ();
			} elseif (in_array ( $key, $arr_file )) // files
{
				echo "<label class=\"spaced right overflow floatleft medium shaded\" onclick=\"bb_select_field('" . $field_id . "')\">" . htmlentities ( $value ['name'] ) . ":</label>";
				echo "<button id=\"" . $field_id . "\" class=\"link spaced left floatleft\" onclick=\"bb_submit_object('bb-links/bb_object_file_link.php'," . $post_key . ")\">" . htmlentities ( $row [$col2] ) . "</button>";
				$main->echo_clear ();
			} elseif (in_array ( $key, $arr_relate ) && $value ['relate']) {
				$relate_row_type = $main->relate_row_type ( $row [$col2] );
				$relate_post_key = $main->relate_post_key ( $row [$col2] );
				$relate ['id'] = $relate_post_key;
				$relate ['row_type'] = $relate_row_type;
				echo "<label class=\"spaced right overflow floatleft medium shaded\" onclick=\"bb_select_field('" . $field_id . "')\">" . htmlentities ( $value ['name'] ) . ":</label>";
				$main->standard ( $relate, $arr_layouts, "bb_cascade", "cascade", $row [$col2], array (
						'id' => $field_id,
						'class' => "link spaced left floatleft" 
				) );
				$main->echo_clear ();
			} else // regular
{
				echo "<label class=\"spaced right overflow floatleft medium shaded\" onclick=\"bb_select_field('" . $field_id . "')\">" . htmlentities ( $value ['name'] ) . ":</label>";
				echo "<div id=\"" . $field_id . "\" class=\"spaced left floatleft\">" . htmlentities ( $row [$col2] ) . "</div>";
				$main->echo_clear ();
			}
		}
		echo "</div>";
	}
	/* end return the details */
	
	/* get the dump into a string for texterea */
	if ($main->button ( 2 )) {
		$text_str = "";
		foreach ( $arr_columns as $key => $value ) {
			$col2 = $main->pad ( "c", $key );
			$text_str .= $row [$col2] . PHP_EOL;
		}
	}
	/* end dump */
	
	// link records area
	if ($main->button ( 1 )) {
		// intialize
		$arr_link_row_type = array (); // will be empty if either unlinkable or empty link_values
		$link_values = preg_replace ( '/\s+/', '', $link_values );
		if (empty ( $link_values )) // check if link_values is empty
{
			array_push ( $arr_messages, "Error: No values supplied." );
		} else // check to see if record is linkable
{
			foreach ( $arr_layouts as $key => $value ) {
				if ($row_type == $value ['parent']) {
					array_push ( $arr_link_row_type, $key );
				}
			}
			if (empty ( $arr_link_row_type )) {
				array_push ( $arr_messages, "Error: Cannot link records to this type of record." );
			}
		}
		
		// run link values
		if (! empty ( $arr_link_row_type )) // linkable records
{
			// check for valid numbers
			$arr_to_link = explode ( ",", $link_values );
			$arr_to_link_value = array ();
			$arr_to_link_int = array ();
			$arr_not_valid_value = array ();
			foreach ( $arr_to_link as $key => $value ) {
				$valid_id = false;
				if (preg_match ( "/^[A-Za-z]\d+/", $value )) {
					// take off integer for test
					$id = substr ( $value, 1 );
					if (filter_var ( $id, FILTER_VALIDATE_INT )) {
						// preserve key, proper id form
						$arr_to_link_value [$key] = $value;
						// this is the ids that are in proper form
						$arr_to_link_int [$key] = $id;
						$valid_id = true;
					}
				}
				if (! $valid_id) {
					// preserve key, ids not in proper form
					$arr_not_valid_value [$key] = $value;
				}
			}
			
			// this wil check the ids to be updated have matching row_type/id pairs
			// everything will come out in the wash after the update
			$arr_record_ids = array ();
			if (! empty ( $arr_to_link_value )) {
				$arr_union_query = array ();
				foreach ( $arr_to_link_value as $value ) {
					$row_type_link = ord ( strtolower ( substr ( $value, 0, 1 ) ) ) - 96;
					$id = substr ( $value, 1 );
					$str_union_query = "SELECT id FROM data_table WHERE id = " . $id . " AND row_type = " . $row_type_link;
					array_push ( $arr_union_query, $str_union_query );
				}
				$query = implode ( " UNION ", $arr_union_query );
				// echo "<p>" . $query . "</p>";
				$result = $main->query ( $con, $query );
				// fetch valid ids with proper row_type
				$arr_record_ids = pg_fetch_all_columns ( $result, 0 );
			}
			
			// this will come from valid records, post key should always be positive
			$link_values = ! empty ( $arr_record_ids ) ? implode ( ",", $arr_record_ids ) : "-1";
			// this will come from valid layouts
			$str_link_row_type = implode ( ",", $arr_link_row_type );
			
			// no need for pg_escape string on link_values because of regular expression
			// update with returning clause, cannot link to archived records
			$query = "UPDATE data_table SET key1 = " . ( int ) $post_key . " " . "WHERE id IN (" . $link_values . ") AND row_type IN (" . $str_link_row_type . ") " . "AND archive IN (0) AND EXISTS (SELECT 1 FROM data_table WHERE id IN (" . $post_key . ")) RETURNING id;";
			// echo "<p>" . $query . "</p>";
			$result = $main->query ( $con, $query );
			// fetch updated rows
			$arr_linked_int = pg_fetch_all_columns ( $result, 0 );
			// get rows not linked gased on int values
			$arr_not_linked_int = array_diff ( $arr_to_link_int, $arr_linked_int );
			
			// get values not linked using the keys
			$arr_linked = array_diff_key ( $arr_to_link_value, $arr_not_linked_int );
			$arr_not_linked = array_intersect_key ( $arr_to_link_value, $arr_not_linked_int );
			
			// into strings for messages
			$str_linked = implode ( ", ", $arr_linked );
			$str_not_linked = implode ( ", ", $arr_not_linked );
			$str_not_valid_value = implode ( ", ", $arr_not_valid_value );
			
			// messages
			// none linked
			if (empty ( $arr_linked )) {
				array_push ( $arr_messages, "No Records were linked." );
				$link_values = "";
			}  // linked
else {
				array_push ( $arr_messages, "Record(s) " . htmlentities ( $str_linked ) . " were linked to record " . $letter . ( string ) $post_key );
				$link_values = "";
			}
			// not linked
			if (! empty ( $arr_not_linked )) {
				array_push ( $arr_messages, "Record(s) " . htmlentities ( $str_not_linked ) . " were not linked." );
				$link_values = "";
			}
			if (! empty ( $arr_not_valid_value )) {
				array_push ( $arr_messages, "Value(s) " . htmlentities ( $str_not_valid_value ) . " were not valid records and were not linked." );
				$link_values = "";
			}
		} // not empty link row type
		$link_values = "";
	} // end link records area, link button set
} // post key set

/* BEGIN REQUIRED FORM */
$main->echo_form_begin ();
$main->echo_module_vars ();

// message and textarea align left
echo "<div class=\"left\">";
if (($post_key > 0) && ($cnt_rows == 1)) {
	$main->echo_clear ();
	echo "<br>";
	$main->echo_clear ();
	echo "<p class =\"spaced\">Link these records to this record</p>";
	echo "<input type=\"text\" name=\"link_values\" class =\"spaced\" size=\"50\" value=\"" . htmlentities ( $link_values ) . "\" />";
	$main->echo_clear ();
	echo "<div class = \"spaced\">";
	$main->echo_messages ( $arr_messages );
	echo "</div>";
	$params = array (
			"class" => "spaced",
			"number" => 1,
			"target" => $module,
			"passthis" => true,
			"label" => "Link Values" 
	);
	$main->echo_button ( "link_button", $params );
	$main->echo_clear ();
	echo "<br>";
	echo "<textarea class=\"spaced\" name=\"dump_area\"rows=\"8\" cols=\"80\">" . $text_str . "</textarea>";
	$main->echo_clear ();
	$params = array (
			"class" => "spaced",
			"number" => 2,
			"target" => $module,
			"passthis" => true,
			"label" => "Dump Data" 
	);
	$main->echo_button ( "dump_button", $params );
	$params = array (
			"class" => "spaced",
			"onclick" => "bb_clear_textarea();",
			"label" => "Clear" 
	);
	$main->echo_script_button ( "dump_clear", $params );
}

// row_type and post key
echo "<input type=\"hidden\" name=\"row_type\" value=\"" . $row_type . "\" />";
echo "<input type=\"hidden\" name=\"post_key\" value=\"" . $post_key . "\" />";

echo "</div>";
$main->echo_common_vars ();
$main->echo_form_end ();
/* END FORM */
?>


