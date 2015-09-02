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
<?php 
/* INITIALIZE */
//error message pile
$arr_message = array();

/* GET STATE */
$POST = $main->retrieve($con, $array_state);

$arr_header = $main->get_json($con, "bb_interface_enable");
$arr_security = $arr_header['row_security']['value'];

$post_key = isset($POST['bb_post_key']) ? $POST['bb_post_key'] : -1;
$row_type = isset($POST['bb_row_type']) ? $POST['bb_row_type'] : -1;

//handle security levels constant
/* BEGIN SECURE CASCADE */
if ($main->button(1))
	{
        //get postback vars
    $post_key = $main->post('post_key',$module);
    $row_type = $main->post('row_type',$module);
    $setbit = $main->post('setbit',$module);
    //recursive query for cascading action
    //needs to be updated when postgres 9.* is standard
    //second step double checks for changes before execution, however one step would be better
    $query = "WITH RECURSIVE t(id) AS (" .
                "SELECT id FROM data_table WHERE id = " . $post_key . " " .
                "UNION ALL " .
                "SELECT T1.id FROM data_table T1, t " .
                "WHERE t.id = T1.key1)" . 
             "SELECT id FROM t;";
    $result = $main->query($con, $query);
    $cnt_cascade = pg_num_rows($result);
    $arr_ids = pg_fetch_all_columns($result,0);
    $ids_archive = implode(",",$arr_ids);
    $union_archive = "SELECT " . implode(" as id UNION SELECT ", $arr_ids) . " as id";
    
    //Update with join and double check nothing has changed
    $query = "UPDATE data_table SET secure = " . $setbit . " " .
         "FROM (" . $union_archive . ") T1 " .
         "WHERE data_table.id = T1.id AND EXISTS (SELECT 1 " .
         "WHERE (SELECT count(T1.id) FROM data_table T1 INNER JOIN (" . $union_archive . ") T2 " .
         "ON T1.id = T2.id) = " . $cnt_cascade . ");";

    $result = $main->query($con, $query);
    $cnt_affected = pg_affected_rows($result);
	if ($cnt_affected > 0)
		{
		if (empty($arr_secure))
			{
			if ($setbit)
				{
				array_push($arr_message, "This Cascade Secure secured " . $cnt_affected . " rows.");   
				}
			elseif (!$setbit)
				{
				array_push($arr_message, "This Unsecure Cascade unsecured " . $cnt_affected . " rows.");   
				}
			}
		else
			{
			array_push($arr_message, "This Cascade action set " . $cnt_affected . " rows to security level \"" . $arr_secure[$setbit] . "\"."); 	
			}
		}			
	else
		{
		array_push($arr_message, "Error: There may have been an underlying data change.");      
		}
    }
/* END CASCADE */        
        
/* RETURN RECORD */
else
    {
    //get count of records to secure
    $query = "WITH RECURSIVE t(id) AS (" .
                "SELECT id FROM data_table WHERE id = " . $post_key . " " .
                "UNION ALL " .
                "SELECT T1.id FROM data_table T1, t " .
                "WHERE t.id = T1.key1)" . 
             "SELECT id FROM t;";
    $result = $main->query($con, $query);
    $cnt_cascade = pg_num_rows($result);  
    
    if ($cnt_cascade > 1)
        {
        array_push($arr_message, "This record has " . ($cnt_cascade - 1) . " child records.");
        }
    else
        {
        array_push($arr_message, "This record does not have child records.");    
        }
    
    $arr_layouts = $main->get_json($con, "bb_layout_names");
    $arr_layouts_reduced = $main->filter_keys($arr_layouts);
    $arr_columns = $main->get_json($con, "bb_column_names");
    $arr_column_reduced = $main->filter_keys($arr_columns[$row_type]);

    //get column name from "primary" attribute in column array
    //this is used to populate the record header link to parent record
    $arr_layout = $arr_layouts_reduced[$row_type];
    $parent_row_type = $arr_layout['parent']; //will be default of 0, $arr_columns[$parent_row_type] not set if $parent_row_type = 0
    $leftjoin = isset($arr_columns[$parent_row_type]['primary']) ? $main->pad("c", $arr_columns[$parent_row_type]['primary']) : "c01";
    
    //return record
    $query = "SELECT count(*) OVER () as cnt, T1.*, T2.hdr, T2.row_type_left FROM data_table T1 " .
	     "LEFT JOIN (SELECT id, row_type as row_type_left, " . $leftjoin . " as hdr FROM data_table) T2 " .
	     "ON T1.key1 = T2.id " . 
	     "WHERE T1.id = " . $post_key . ";";	

    $result = $main->query($con, $query);
    
    $main->return_stats($result);
    echo "<br>";
    $row = pg_fetch_array($result);
    //determine to secure or unsecure
    $setbit= $row['secure'];
    echo "<div class =\"margin divider\">";
    //outputs the row we are working with
    $main->return_header($row, "bb_cascade");
    echo "<div class=\"clear\"></div>";   
    $main->return_rows($row, $arr_column_reduced);
    echo "<div class=\"clear\"></div>";
    echo "</div>";
    echo "<div class =\"margin divider\"></div>";
    }
/* END RETURN RECORD */

echo "<br>";
$main->echo_messages($arr_message);
echo "<br>";

/* BEGIN REQUIRED FORM */
$main->echo_form_begin();
$main->echo_module_vars();

if (!$main->button(1))
	{
	if (empty($arr_security))
		{
		$button_value = ($setbit == 0) ? 1 : 0; //set value is value to set secure to
		$button_text = ($setbit == 0) ? "Secure Cascade" : "Unsecure Cascade";
		$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>$button_text);
		$main->echo_button("secure_cascade", $params);
		echo "<input type = \"hidden\"  name = \"setbit\" value = \"" . $button_value . "\">";
		}
	else
		{
		$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>"Set Security To");
		$main->echo_button("secure_cascade", $params);
		echo "<select name=\"setbit\" class=\"spaced\"\">";
		foreach($arr_security as $key => $value)
			{
			echo "<option value=\"" . $key . "\" " . ($key == $setbit ? "selected" : "") . ">" . htmlentities($value) . "&nbsp;</option>";
			}
		echo "</select>";
		}
	}

 //post variables        
echo "<input type = \"hidden\"  name = \"post_key\" value = \"" . $post_key . "\">";
echo "<input type = \"hidden\"  name = \"row_type\" value = \"" . $row_type . "\">";

//form vars necessary for header link
$main->echo_common_vars();

$main->echo_state($array_state);
$main->echo_form_end();
/* FORM */
?>
