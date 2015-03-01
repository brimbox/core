<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
Copyright (C) 2012 - 2015  Kermit Will Richardson, Brimbox LLC

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License Version 3 (“GNU GPL v3”)
as published by the Free Software Foundation. 

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU GPL v3 for more details. 

You should have received a copy of the GNU GPL v3 along with this program.
If not, see http://www.gnu.org/licenses/
*/
?>
<?php
$main->check_permission("bb_brimbox", array(3,4,5));
?>
<script type="text/javascript">
/* MODULE JAVASCRIPT */
function reload_on_layout()
    {
    //change row_type, reload appropriate columns
    //this goes off when row_type is changed
    
    var frmobj = document.forms["bb_form"];
    
    frmobj.offset.value = 1;
    bb_submit_form(); //call javascript submit_form function
	return false;
    }
    
function submit_lookup()
    {
    //set vars and submit form, return value is reset to 1
    //this goes off when letter is clicked
    var frmobj = document.forms["bb_form"];  
    frmobj.offset.value = 1;   
    bb_submit_form(); //call javascript submit_form function
	return false;
    }

/* END MODULE JAVASCRIPT */
</script>
<?php
/* INITIALIZE */
//find default row_type, $arr_layouts must have one layout set
$arr_layouts = $main->get_json($con, "bb_layout_names");
$arr_layouts_reduced = $main->filter_keys($arr_layouts);
$arr_columns = $main->get_json($con, "bb_column_names");
$default_row_type = $main->get_default_layout($arr_layouts_reduced);
$arr_messages = array();

/* LOOKUP AND STATE POSTBACK */
//do lookup state and postback
$main->retrieve($con, $array_state); //run first
    
//get archive mode, default Off, show only zeros
$mode = ($archive == 0) ? "1 = 1" : "archive < " . $archive;

//get lookup state variables are use them
$arr_state = $main->load($module, $array_state);

//process offset and archive
$offset = $main->process('offset', $module, $arr_state, 1);
$record_id = $main->process('record_id', $module, $arr_state, "");

// get row_type to find posted layout
$row_type = $main->post('row_type', $module, $default_row_type);
$arr_column = isset($arr_columns[$row_type]) ? $arr_columns[$row_type] : array();

//get default col_type or deal with possibility of no columns, then 1
$default_col_type = $main->get_default_column($arr_column);

//set col_type state from default if postback and row_type changed
if ($main->check('row_type', $module) && $main->post('row_type', $module) <> $main->state('row_type', $arr_state))
	{
	$col_type_1 = $main->set('col_type_1', $arr_state, $default_col_type);
	$col_type_2 = $main->set('col_type_2', $arr_state, $default_col_type);
	}
//else fully process col_type
else
	{
	$col_type_1 = $main->process('col_type_1', $module, $arr_state, $default_col_type);
	$col_type_2 = $main->process('col_type_2', $module, $arr_state, $default_col_type);
	}

//process fields	
$value_1 = $main->process('value_1', $module, $arr_state, "");
$value_2 = $main->process('value_2', $module, $arr_state, "");
$radio_1 = $main->process('radio_1', $module, $arr_state, 1);
$radio_2 = $main->process('radio_2', $module, $arr_state, 1);

//process row_type, earlier just got it from post	
$row_type = $main->process('row_type', $module, $arr_state, $default_row_type);

//get archive flag checkbox
$archive_flag = $main->process('archive_flag', $module, $arr_state, 0);
	
//back to string
$main->update($array_state, $module, $arr_state);
/* END POSTBACK */
?>
<?php 
/* BEGIN TAB */

/* GET COLUMN AND LAYOUT VALUES */
//get column names based on row_type/record types
$arr_column = isset($arr_columns[$row_type]) ? $arr_columns[$row_type] : array();
$arr_column_reduced = $main->filter_keys($arr_column);
$arr_layout = $arr_layouts_reduced[$row_type];
$column_1 = $main->pad("c", $col_type_1);
$column_2 = $main->pad("c", $col_type_2);
/* END COLUMN AND LAYOUT VALUES */	

/* PROCESS RECORD ID */
//must be done before form output, if true then parse row_type for query later
$valid_id = false;
if (!$main->blank($record_id))
	{
	if (preg_match("/^[A-Za-z]\d+/", $record_id))
		{
		//take off integer for test
		$id = substr($record_id,1);
		if (filter_var($id, FILTER_SANITIZE_NUMBER_INT))
			{
			$valid_id = true;	
			}
		else
			{
			array_push($arr_messages, "Record ID integer supplied is too large. Please supply a valid Record ID.");	
			}			
		}
	else
		{
		array_push($arr_messages, "Record ID not in correct format. Must be formatted as a letter following by an integer.");	
		}
	}
/* END PROCESS RECORD ID */

/* START REQUIRED FORM */
$main->echo_form_begin();
$main->echo_module_vars();
	
//layout types, this produces $row_type
//use a table
echo "<div class=\"spaced\">";
$main->echo_messages($arr_messages);
echo "</div>";

echo "<table class=\"border\" cellpadding=\"0\" cellspacing=\"0\">";
//use a table to organized form, headers follow
echo "<tr><td class=\"middle nowrap padded\" rowspan=\"2\">";
$params = array("class"=>"spaced middle","onclick"=>"submit_lookup()", "label"=>"Submit Lookup");
$main->echo_script_button("lookup_button", $params);
echo "</td>";
echo "<td class=\"borderleft nowrap padded\"><span class=\"spaced\">Record ID</span></td>";
echo "<td class=\"borderleft nowrap padded\"><span class=\"spaced\">Layout</span></td>";
echo "<td class=\"borderleft nowrap padded\"><span class=\"spaced\">First Lookup Column</span></td>";
echo "<td class=\"borderleft nowrap padded\"><span class=\"spaced\">Second Lookup Column</span></td>";
if ($main->on_constant('ARCHIVE_INTERWORKING'))
	{
	$checked = "";
	if ($archive_flag == 1)
		{
		$checked =  "checked";
		$mode = " 1 = 1 ";
		}
	echo "<td class=\"borderleft nowrap padded middle\" rowspan=\"2\">";
    echo "<span class = \"border rounded padded shaded\">";
    $main->echo_input("archive_flag", 1, array('type'=>'checkbox','input_class'=>'middle padded','checked'=>$checked));
    echo "<label class=\"padded\">Check Archives</label>";
	echo "</span><br>";
	echo "</span>";
	echo "</td>";
	}
echo "</tr>";

echo "<tr>";
//submit button
echo "<td class=\"borderleft nowrap padded\">";
echo "<input type =\"text\" class=\"spaced short\" name = \"record_id\" value = \"" . $record_id . "\">";
echo "</td>";
echo "<td class=\"borderleft nowrap padded\">";
$params = array("onchange"=>"reload_on_layout()");
$main->layout_dropdown($arr_layouts_reduced, "row_type", $row_type, $params);
echo "</td>";
echo "<td class=\"borderleft nowrap padded\">";
//column 1 values
echo "<input type=\"text\" class=\"spaced medium\" name = \"value_1\" value = \"" . $value_1 . "\">";
echo "<span class=\"spaced middle\">Begins:</span><input type=\"radio\" class=\"middle\" name=\"radio_1\" value=\"1\"" . ($radio_1 == 1 ? "checked" : "") . " >";
echo "<span class=\"spaced middle\">Exact:</span><input type=\"radio\" class=\"middle\" name=\"radio_1\" value=\"2\"" . ($radio_1 == 2 ? "checked" : "") . ">";
echo "<span class=\"spaced middle\">Like:</span><input type=\"radio\" class=\"middle\" name=\"radio_1\" value=\"3\"" . ($radio_1 == 3 ? "checked" : "") . ">";
echo "<span class=\"spaced middle\">Empty:</span><input type=\"radio\" class=\"middle\" name=\"radio_1\" value=\"4\"" . ($radio_1 == 4 ? "checked" : "") . ">";
echo "&nbsp;";
$params = array("class"=>"spaced");
$main->column_dropdown($arr_column_reduced, "col_type_1", $col_type_1, $params);
echo "</td>";

//column 2 values
echo "<td class=\"borderleft nowrap padded\">";
echo "<input type = \"text\" class=\"spaced medium\" name = \"value_2\" value = \"" . $value_2 . "\">";
echo "<span class=\"spaced\">Begins:</span><input type=\"radio\" class=\"middle\" name=\"radio_2\" value=\"1\"" . ($radio_2 == 1 ? "checked" : "") . ">";
echo "<span class=\"spaced\">Exact:</span><input type=\"radio\" class=\"middle\" name=\"radio_2\" value=\"2\"" . ($radio_2 == 2 ? "checked" : "") . ">";
echo "<span class=\"spaced\">Like:</span><input type=\"radio\" class=\"middle\" name=\"radio_2\" value=\"3\"" . ($radio_2 == 3 ? "checked" : "") . ">";
echo "<span class=\"spaced\">Empty:</span><input type=\"radio\" class=\"middle\" name=\"radio_2\" value=\"4\"" . ($radio_2 == 4 ? "checked" : "") . ">";
echo "&nbsp;";
$params = array("class"=>"spaced");
$main->column_dropdown($arr_column_reduced, "col_type_2", $col_type_2, $params);
echo "</td>";

echo "</tr></table>"; //table 1

//hidden element containing the current return page, this is related to the row offset in the query LIMIT clause
echo "<input type = \"hidden\"  name = \"offset\" value = \"" . $offset . "\">";
 
//these variables hold the variables used when a return record link is selected
//these variables are for the links that follow every return record
//these variable are only set via javascript, when a link is followed

//post_key is the record id or drill down record key (two uses)
$main->echo_common_vars();

//this echos the state variables into the form
$main->echo_state($array_state);
$main->echo_form_end();
/* END FORM */


/* LOOKUP RETURN ROWS OUTPUT */
//This area displays the result set

//calculate lower limit of ordered query, return rows will be dealt with later
//initialize $count_rows in case no rows are returned

$return_rows = $main->set_constant('RETURN_ROWS',4);
$pagination = $main->set_constant('PAGINATION',5);
$count_rows = 0;
$lower_limit = ($offset - 1) * $return_rows;

/* BUILD QUERY */
//set and clauses defaults, $and_clause_3 assures no return values if empty
$and_clause_1 = " 1 = 1 "; //value_1
$and_clause_2 = " 1 = 1 "; //value_2
$and_clause_3 = " 1 = 0 "; //test for value_1 or value_2
$and_clause_4 = " 1 = 1 "; //record_id

//must test for '0' string which will evaluate as empty
//$test_1 & $test_2 true if populated
if ($valid_id) //record_id
	{
	//deal with different row_type and column xml
	$row_type =  ord(strtolower(substr($record_id, 0, 1))) - 96;
	$layout = $main->pad("l", $row_type);
	//reset column for output, and layout for parent row type, state will remain
	$arr_layout = $arr_layouts_reduced[$row_type];
	$arr_column_reduced = $main->filter_keys($arr_columns[$row_type]);
	$and_clause_3 = " 1 = 1 ";
	$and_clause_4 = " id = " . $id . " ";
	}
else //value_1 or value_2
	{
	$test_1 = (boolean)(!$main->blank($value_1) || ($radio_1 == 4));
	$test_2 = (boolean)(!$main->blank($value_2) || ($radio_2 == 4));
	
	// $and_clause_1, based on radio type
	if ($test_1)
		{
		switch ($radio_1)
			{
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
	if ($test_2)
		{
		switch ($radio_2)
			{
			case 1:
				$and_clause_2 = " " . $column_2 . " ILIKE '" .  pg_escape_string($value_2) . "%' ";
				break;
			case 2:
				$and_clause_2 = " UPPER(" . $column_2 . ") = UPPER('" .  pg_escape_string($value_2) . "')";
				break;    
			case 3:
				$and_clause_2 = " " . $column_2 . " ILIKE '%" .  pg_escape_string($value_2) . "%' ";
				break;
			case 4:
				$and_clause_2 = " trim(both FROM " . $column_2 . ") = '' ";
				break;
			}
		}
	//$and_clause_3 set to 1 = 1 if not empty    
	if ($test_1 || $test_2)
		{
		$and_clause_3 = " 1 = 1 ";
		}
	}
	
//this must be done after row_type settled, row_type now set for query
//this does not need to be done before the form
//get column name from "primary" attribute in column xml
//this is used to populate the record header link to parent record for all queries

//get column name from "primary" attribute in column array
//this is used to populate the record header link to parent record
$parent_row_type = $arr_layout['parent']; //will be default of 0, $arr_columns[$parent_row_type] not set if $parent_row_type = 0
$leftjoin = isset($arr_columns[$parent_row_type]['primary']) ? $main->pad("c", $arr_columns[$parent_row_type]['primary']) : "c01";

//return query, order by column 1 and column 2
$query = "SELECT count(*) OVER () as cnt, T1.*, T2.hdr, T2.row_type_left FROM (SELECT * FROM data_table WHERE " . $and_clause_4 . ") T1 " .
		 "LEFT JOIN (SELECT id, row_type as row_type_left, " . $leftjoin . " as hdr FROM data_table WHERE row_type = " . $parent_row_type . ") T2 " .
		 "ON T1.key1 = T2.id " .
		 "WHERE  " . $and_clause_1 . " AND " .  $and_clause_2 . " AND " . $and_clause_3 . " AND row_type = " . $row_type . " AND " . $mode . " ORDER BY " . $column_1 . " ASC, " . $column_2 . " ASC , id LIMIT " . $return_rows . " OFFSET " . $lower_limit . ";";
/* END BUILD QUERY */

//echo "<p>" . $query . "</p>";
$result = $main->query($con, $query);

//this outputs the return count and time of query
$main->return_stats($result);

//this outputs the table
//will repetatively output records by while loop based on the $offset from query

while($row = pg_fetch_array($result))
	{
	echo "<div class =\"margin divider\">";
	//returns header with parent link
	$main->return_header($row, "bb_cascade");
	echo "<div class=\"clear\"></div>";
	//returns the record data in appropriate row
	$count_rows = $main->return_rows($row, $arr_column_reduced); 
	echo "<div class=\"clear\"></div>";
	//return the links along the bottom of a record
	$main->output_links($row, $arr_layouts_reduced, $userrole);
    echo "</div>";
	echo "<div class=\"clear\"></div>";	
	}

//record selector at bottom
$main->page_selector("offset", $offset, $count_rows, $return_rows, $pagination);

/*** END LOOKUP OUTPUT ***/
?>
