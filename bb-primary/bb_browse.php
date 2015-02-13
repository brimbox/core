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
function set_hidden(lt)
    {
    //set vars and submit form, return value is reset to 1
    //this goes off when letter is clicked
    var frmobj = document.forms["bb_form"];  
    frmobj.letter.value = lt;
    frmobj.offset.value = 1;   
    bb_submit_form(); //call javascript submit_form function
	return false;
    }

//standard reload layout
function reload_on_layout()
    {
    //change row_type, reload appropriate columns
    //this goes off when row_type is changed
    
    var frmobj = document.forms["bb_form"];
    
    frmobj.offset.value = 1;
    bb_submit_form(); //call javascript submit_form function
	return false;
    }
//standard reload column
function reload_on_column()
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
//find default row_type, $arr_layouts must have one layout set
$arr_layouts = $main->get_json($con, "bb_layout_names");
$arr_columns = $main->get_json($con, "bb_column_names");
$default_row_type = $main->get_default_layout($arr_layouts);

/* BROWSE AND STATE POSTBACK */
//do browse postback, get variables from state
$main->retrieve($con, $array_state); //run first
    
//get archive mode, default Off, show only zeros
$mode = ($archive == 0) ? "1 = 1" : "archive < " . $archive;
    
//get browse_state variables are set use them
$arr_state = $main->load($module, $array_state);

//get variable from state, or initialize
$letter = $main->process('letter', $module, $arr_state, "A");
$offset = $main->process('offset', $module, $arr_state, 1);

//must get post while preserving row_type state to reset col_type when row_type changes
$row_type = $main->post('row_type', $module, $default_row_type);
//must get arr_column on current row_type before setting default col_type
$arr_column = isset($arr_columns[$row_type]) ? $arr_columns[$row_type] : array();

//get default col_type or deal with possibility of no columns, then 1
$default_col_type = $main->get_default_column($arr_column);

// if row_type changed and postback (post is different than state) use default column type
if ($main->check('row_type', $module) && ($row_type <> $main->state('row_type', $arr_state)))
	{
	$col_type = $main->set('col_type', $arr_state, $default_col_type);
	}
else
	{
	$col_type = $main->process('col_type', $module, $arr_state, $default_col_type);
	}

//process row_type	
$row_type = $main->process('row_type', $module, $arr_state, $default_row_type);

//update state, back to string, get name
$main->update($array_state, $module, $arr_state);
/* END POSTBACK */
?>
<?php 
/* BEGIN TAB */
echo "<div class=\"center\">"; //centering

/* START REQUIRED FORM */
//form tag
$main->echo_form_begin();
$main->echo_module_vars();

echo "<span class=\"padded larger\">"; //font size
//do alpha and numeric links
//this area make the alphabetic and numeric links including the posting javascript
    for ($i = 65; $i <= 90; $i++) //alpha
	{
	$alpha_number = chr($i);
	
	//underline and bold chosen letter
	$class = ($alpha_number == $letter) ? "link bold underline" : "link";
	echo "<button class=\"" . $class . "\"  onclick=\"set_hidden('" . $alpha_number . "'); return false;\">";
	echo $alpha_number;
	echo "</button>&nbsp;";
	}
	  
    echo "&nbsp;&nbsp;";

	  
    for ($i = 48; $i <= 57; $i++) //numeric
	{
	$alpha_number = chr($i);
	
	//underline and bold chosen number
	$class = ($alpha_number == $letter) ? "link bold underline" : "link"; 
	echo "<button class=\"" . $class . "\"  onclick=\"set_hidden('" . $alpha_number . "'); return false;\">";
	echo $alpha_number;
	echo "</button>&nbsp;";
	} 
 echo "</span>"; //end font size
//do alpha and numeric links
	
//$xml is retrieved early in browse module to populate combo boxes	
//this sets the correct column xml -- carries through to browse return

//get column names based on row_type/record types (repeated after state load but why not for clarity)
$column = $main->pad("c", $col_type);
$arr_column = isset($arr_columns[$row_type]) ? $arr_columns[$row_type] : array();
$arr_layout = $arr_layouts[$row_type];

//get column name from "primary" attribute in column array
//this is used to populate the record header link to parent record
$parent_row_type = $arr_layout['parent']; //will be default of 0, $arr_columns[$parent_row_type] not set if $parent_row_type = 0
$leftjoin = isset($arr_columns[$parent_row_type]['primary']) ? $main->pad("c", $arr_columns[$parent_row_type]['primary']) : "c01";

echo "&nbsp;&nbsp;";
//layout types, this produces $row_type
$params = array("onchange"=>"reload_on_layout()");
$main->layout_dropdown($arr_layouts, "row_type", $row_type, $params);
echo "&nbsp;&nbsp;";
//column names, $column is currently selected column
$params = array("onchange"=>"reload_on_column()");
$main->column_dropdown($arr_column, "col_type", $col_type, $params);

//hidden element containing the current chosen letter
echo "<input type = \"hidden\"  name = \"letter\" value = \"" . $letter . "\">";
//hidden element containing the current return page, this is related to the row offset in the query LIMIT clause
echo "<input type = \"hidden\"  name = \"offset\" value = \"" . $offset . "\">";
 
//these variables hold the variables used when a return record link is selected
//these variables are for the links that follow every return record
//these variable are only set via javascript, when a link is followed

$main->echo_common_vars();

//this echos the state variables into the form
$main->echo_state($array_state);
$main->echo_form_end();
/* END FORM */

echo "</div>"; //end align center

/* BROWSE RETURN ROWS OUTPUT */
//This area displays the result set
//uses variables $arr_column, $letter, $column, $offset and $row_type
//$return_rows is a global variable which can be set
//$count_rows contains the number of rows in the query without limit

//calculate lower limit of ordered query, return rows will be dealt with later
//initialize $count_rows in case no rows are returned
$return_rows = defined('RETURN_ROWS') ? RETURN_ROWS : 4;
$pagination = defined('PAGINATION') ? PAGINATION : 5;
$count_rows = 0;
$lower_limit = ($offset - 1) * $return_rows;
$esc_lt = pg_escape_string($letter);
$esc_col1 = pg_escape_string($column);

//return query
$query = "SELECT count(*) OVER () as cnt, T1.*, T2.hdr, T2.row_type_left FROM data_table T1 " .
		 "LEFT JOIN (SELECT id, row_type as row_type_left, " . $leftjoin . " as hdr FROM data_table WHERE row_type = " . $parent_row_type . ") T2 " .
		 "ON T1.key1 = T2.id " .
		 "WHERE UPPER(SUBSTRING(" . $esc_col1 . " FROM 1 FOR 1)) = '" . $esc_lt . "' AND row_type = " . $row_type . " AND " . $mode . " ORDER BY " . $column . ", id LIMIT " . $return_rows . " OFFSET " . $lower_limit . ";";

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
	$count_rows = $main->return_rows($row, $arr_column); 
	echo "<div class=\"clear\"></div>";
	//return the links along the bottom of a record
	$main->output_links($row, $arr_layouts, $userrole);
    echo "</div>";
	echo "<div class=\"clear\"></div>";	
	}  

//record selector at bottom
$main->page_selector("offset", $offset, $count_rows, $return_rows, $pagination);

/*** END BROWSE OUTPUT ***/
?>
