<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
Copyright (C) 2012 - 2013  Kermit Will Richardson, Brimbox LLC

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
<style type="text/css">
/* MODULE CSS */
select.box
	{
    display:inline;
	width:200px;
    height:275px;
	}
</style>
<?php
/* INITIALIZE */
/* BEGIN STATE */
//State vars --- there is no listchoose state
$main->retrieve($con, $array_state);

//get post_key
$post_key = isset($_POST['bb_post_key']) ? $_POST['bb_post_key'] : -1;
$row_type = isset($_POST['bb_row_type']) ? $_POST['bb_row_type'] : -1;

//get postback vars
if ($main->button(1))
    {
    $post_key = $main->post('post_key',$module);
    $row_type = $main->post('row_type',$module);   
    }
/* END STATE */

//update row for adding to the list		
if ($main->check('add_names',$module))
	{
	$add_names = $main->post('add_names',$module);
	foreach($add_names as $value)
		{
		//row_type unnecessary
		$query = "UPDATE data_table SET list_string = list_update(list_string," . $value . ") WHERE id = " . $post_key . ";";
		$main->query($con, $query);
		}
	}

//update row for removing from the list			
if ($main->check('remove_names',$module))
	{
	$remove_names = $main->post('remove_names',$module);
	foreach($remove_names as $value)
		{
		//row_type unnecessary
		$query = "UPDATE data_table SET list_string = list_reset(list_string," . $value . ") WHERE id = " . $post_key . ";";
		$main->query($con, $query);
		}
	}

//get layout   
$arr_layouts = $main->get_json($con, "bb_layout_names");
$arr_columns = $main->get_json($con, "bb_column_names");
$arr_column = $arr_columns[$row_type];
$arr_layout = $arr_layouts[$row_type];

//get column name from "primary" attribute in column array
//this is used to populate the record header link to parent record
$parent_row_type = $arr_layout['parent']; //will be default of 0, $arr_columns[$parent_row_type] not set if $parent_row_type = 0
$leftjoin = isset($arr_columns[$parent_row_type]['primary']) ? $main->pad("c", $arr_columns[$parent_row_type]['primary']) : "c01";
    
//one int and a string
$query = "SELECT count(*) OVER () as cnt, T1.*, T2.hdr, T2.row_type_left FROM data_table T1 " .
     "LEFT JOIN (SELECT id, row_type as row_type_left, " . $leftjoin . " as hdr FROM data_table) T2 " .
     "ON T1.key1 = T2.id " . 
     "WHERE T1.id = " . $post_key . ";";	

$result = $main->query($con, $query);

$main->return_stats($result);
echo "<br>";

//get row, set xml on row_type, echo out details
$row = pg_fetch_array($result);

echo "<div class =\"margin divider\">";
//outputs the row we are working with
$main->return_header($row, "bb_cascade");
echo "<div class=\"clear\"></div>";   
$main->return_rows($row, $arr_column);
echo "<div class=\"clear\"></div>";
echo "</div>";
echo "<div class =\"margin divider\"></div>"; 
 
//get database values for processing
$row_type = $row['row_type'];
$list_string = $row['list_string'];

//get list arr
$arr_lists = $main->get_json($con, "bb_create_lists");
$arr_list = $arr_lists[$row_type];

//start form containing select add and remove boxes
echo "<div class=\"clear\"></div>";
echo "<div class=\"clear\"></div>";
/* BEGIN REQUIRED FORM */
$main->echo_form_begin();
$main->echo_module_vars();

//select add box
echo "<div class=\"table\">";
echo "<div class=\"row\">";
echo "<div class=\"cell padded\"><p class = \"bold colored\">Record Not In List(s)</p></div>";
echo "<div class=\"cell padded\"></div>";
echo "<div class=\"cell padded\"><p class = \"bold colored\">Record In List(s)</p></div>";
echo "</div>"; //row
echo "<div class=\"row\">";
echo "<div class=\"cell padded box\">";

echo "<select class=\"box\" name = \"add_names[]\" multiple>";
//echo the xml lists not set
foreach($arr_list as $key => $value)
    {
	$i = $key - 1; //start string at 0
    if ((int)substr($list_string, $i, 1) == 0)  
        {
        echo "<option value=\"" . $key. "\">" . htmlentities($value['name']) . "</option>";
        }
    }
echo "</select>";
echo "</div>"; // cell

echo "<div class=\"cell padded middle\">";
$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>"<< Move >>");
$main->echo_button("move_list", $params);
echo "</div>"; // cell

//select remove box -- multiselect
echo "<div class=\"cell padded\">";
echo"<select class=\"box\" name=\"remove_names[]\" multiple>";
//echo the xml lists already set
//no need to get $arr_list again
foreach($arr_list as $key => $value)
    {
    $i = $key - 1; //start string at 0
    if ((int)substr($list_string, $i, 1) == 1)  
        {
        echo "<option value=\"" . $key . "\">" . htmlentities($value['name']) . "</option>";
        }
    }
echo "</select>";
echo "</div>"; //cell
echo "</div>"; //row
echo "</div>"; //table
//local post_key for resubmit
echo "<input type = \"hidden\" name = \"post_key\" value=\"" . $post_key . "\" />";
echo "<input type = \"hidden\"  name = \"row_type\" value = \"" . $row_type . "\">"; 

//form vars necessary for header link
$main->echo_common_vars();

$main->echo_state($array_state);
$main->echo_form_end();
/* END FORM */
?>
