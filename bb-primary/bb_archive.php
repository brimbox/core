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
$arr_message = array();

$archive_log = $main->on_constant(BB_ARCHIVE_LOG);
 
//State vars --- there is no delete state
$main->retrieve($con, $array_state);

$arr_header = $main->get_json($con, "bb_interface_enable");
$arr_archive = $arr_header['row_archive']['value'];

$post_key = isset($_POST['bb_post_key']) ? $_POST['bb_post_key'] : -1;
$row_type = isset($_POST['bb_row_type']) ? $_POST['bb_row_type'] : -1;

//get postback vars
/* BEGIN ARCHIVE CASCADE */
if ($main->button(1))
	{
    $post_key = $main->post('post_key',$module);
    $row_type = $main->post('row_type',$module);
    $setbit = $main->post('setbit',$module);
    //recursive query for cascading delete
    //needs to updated when postgres 9.* is standard
    //cannot use DELETE in CTE in postgres 8.4, so it is done in 2 steps
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
    $query = "UPDATE data_table SET archive = " . $setbit . " " .
         "FROM (" . $union_archive . ") T1 " .
         "WHERE data_table.id = T1.id AND EXISTS (SELECT 1 " .
         "WHERE (SELECT count(T1.id) FROM data_table T1 INNER JOIN (" . $union_archive . ") T2 " .
         "ON T1.id = T2.id) = " . $cnt_cascade . ");";

    $result = $main->query($con, $query);
    $cnt_affected = pg_affected_rows($result);
	if ($cnt_affected > 0)
		{
        if ($archive_log)
            {
            $message = "Record " . chr($row_type + 64) . $post_key . " and " . ($cnt_affected - 1) . " children archived.";
            $main->log_entry($con, $message , $username);
            }
		if (empty($arr_archive))
			{
			if ($setbit)
				{
				array_push($arr_message, "This Cascade Archive archived " . $cnt_affected . " rows.");
                if ($archive_log)
                    {
                    $message = "Record " . chr($row_type + 64) . $post_key . " and " . ($cnt_affected - 1) . " children archived.";
                    $main->log_entry($con, $message , $username);
                    }
				}
			elseif (!$setbit)
				{
				array_push($arr_message, "This Retrieve Cascade retrieved " . $cnt_affected . " rows.");
                if ($archive_log)
                    {
                    $message = "Record " . chr($row_type + 64) . $post_key . " and " . ($cnt_affected - 1) . " children retrieved.";
                    $main->log_entry($con, $message , $username);
                    }
				}
			}
		else
			{
			array_push($arr_message, "This Cascade set " . $cnt_affected . " rows to archive level \"" . $arr_archive[$setbit] . "\".");
            if ($archive_log)
                {
                $message = "Record " . chr($row_type + 64) . $post_key . " and " . ($cnt_affected - 1) . " children set to archive level " . $arr_archive[$setbit] . ".";
                $main->log_entry($con, $message , $username);
                }
			}
		}
    else
        {
        array_push($arr_message, "Error: There may have been an underlying data change.");      
        }    
    }
/* END CASCADE */        
        
/* RETURN RECORD */
else //default behavior
    {
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
    $arr_column = $arr_columns[$row_type];
    $arr_layout = $arr_layouts_reduced[$row_type];

    //get column name from "primary" attribute in column array
    //this is used to populate the record header link to parent record
    $parent_row_type = $arr_layout['parent']; //will be default of 0, $arr_columns[$parent_row_type] not set if $parent_row_type = 0
    $leftjoin = isset($arr_columns[$parent_row_type]['primary']) ? $main->pad("c", $arr_columns[$parent_row_type]['primary']) : "c01";

    $query = "SELECT count(*) OVER () as cnt, T1.*, T2.hdr, T2.row_type_left FROM data_table T1 " .
	     "LEFT JOIN (SELECT id, row_type as row_type_left, " . $leftjoin . " as hdr FROM data_table) T2 " .
	     "ON T1.key1 = T2.id " . 
	     "WHERE T1.id = " . $post_key . ";";	

    $result = $main->query($con, $query);
    
    $main->return_stats($result);
    echo "<br>";
    $row = pg_fetch_array($result);
    $setbit= $row['archive'];
    echo "<div class =\"margin divider\">";
    //outputs the row we are working with
    $main->return_header($row, "bb_cascade");
    echo "<div class=\"clear\"></div>";   
    $main->return_rows($row, $arr_column);
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

if (!$main->button(1)) //not equal to 1
	{
	if (empty($arr_archive))
		{
		$button_value = ($setbit == 0) ? 1 : 0; //set value is value to set secure to
		$button_text = ($setbit == 0) ? "Archive Cascade" : "Retrieve Cascade";
		$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>$button_text);
		$main->echo_button("archive_cascade", $params);
		echo "<input type = \"hidden\"  name = \"setbit\" value = \"" . $button_value . "\">";
		}
	else
		{
		$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>"Set Archive To");
		$main->echo_button("archive_cascade", $params);
		echo "<select name=\"setbit\" class=\"spaced\"\">";
		foreach($arr_archive as $key => $value)
			{
			echo "<option value=\"" . $key . "\" " . ($key == $setbit ? "selected" : "") . ">" . htmlentities($value) . "&nbsp;</option>";
			}
		echo "</select>";
		}
	}

 //post variables        
echo "<input type = \"hidden\"  name = \"post_key\" value = \"" . $post_key . "\">";
echo "<input type = \"hidden\"  name = \"row_type\" value = \"" . $row_type . "\">";

$main->echo_common_vars();

$main->echo_state($array_state);
$main->echo_form_end();
/* FORM */
?>
