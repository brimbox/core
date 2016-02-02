<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
Copyright (C) 2012 - 2015  Kermit Will Richardson, Brimbox LLC

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License Version 3 (�GNU GPL v3�)
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
$arr_messages = array();

//get $_POST var
$POST = $main->retrieve($con);

$post_key = $main->init($POST['bb_post_key'], -1);
$row_type = $main->init($POST['bb_row_type'], -1);

$delete_log = $main->on_constant('BB_DELETE_LOG');

/* BEGIN DELETE CASCADE */
if ($main->button(1))
	{
	$post_key = $main->post('post_key',$module);
    $row_type = $main->post('row_type',$module);   

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
    $ids_delete = implode(",",$arr_ids);
    $union_delete = "SELECT " . implode(" as id UNION SELECT ", $arr_ids) . " as id";
    
    //double checks to make sure nothing changed
    $query = "DELETE FROM data_table WHERE id IN (" . $ids_delete . ") " .
             "AND EXISTS (SELECT 1 " .
             "WHERE (SELECT count(T1.id) FROM data_table T1 INNER JOIN (" . $union_delete . ") T2 ON T1.id = T2.id) = " . $cnt_cascade . ");";
    $result = $main->query($con, $query);
    //echo $query;
    $cnt_affected = pg_affected_rows($result);
    if ($cnt_affected > 0)
        {
        array_push($arr_messages, "This Cascade Delete deleted " . $cnt_affected . " rows.");
        if ($delete_log)
            {
            $message = "Record " . chr($row_type + 64) . $post_key . " and " . ($cnt_affected - 1) . " children deleted.";
            $main->log_entry($con, $message , $username);
            }
        }
    else
        {
        array_push($arr_messages, "Error: There may have been an underlying data change.");      
        }
    
    }
/* END CASCADE */        
        
/* RETURN RECORD */
else
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
        array_push($arr_messages, "This record has " . ($cnt_cascade - 1) . " child records.");
        array_push($arr_messages, "Caution: Clicking \"Delete Cascade\" will delete this record and all its child records. This cannot be undone.");
        }
    else
        {
        array_push($arr_messages, "This record does not have child records.");    
        }
        
    $arr_layouts = $main->layouts($con);    
    $arr_columns = $main->columns($con, $row_type);
    
    $parent_row_type = $main->reduce($arr_layouts, array($row_type, "parent"));  //will be default of 0, $arr_columns[$parent_row_type] not set if $parent_row_type = 0
    if ($parent_row_type)
        $arr_columns_props = $main->lookup($con, 'bb_column_names', $parent_row_type, true);
    $leftjoin = $main->init($arr_columns_props['primary'], "c01");

    $query = "SELECT count(*) OVER () as cnt, T1.*, T2.hdr, T2.row_type_left FROM data_table T1 " .
	     "LEFT JOIN (SELECT id, row_type as row_type_left, " . $leftjoin . " as hdr FROM data_table) T2 " .
	     "ON T1.key1 = T2.id " . 
	     "WHERE T1.id = " . $post_key . ";";	

    $result = $main->query($con, $query);
    
    $main->return_stats($result);
    $row = pg_fetch_array($result);   	
    echo "<div class =\"margin divider\">";
    //outputs the row we are working with
    $main->return_header($row, "bb_cascade");
    echo "<div class=\"clear\"></div>";   
    $main->return_rows($row, $arr_columns);
    echo "<div class=\"clear\"></div>";
    echo "</div>";
	echo "<div class =\"margin divider\"></div>";
    }
/* END RETURN RECORD */

$build->hook('bb_delete_messages');
echo "<p class=\"spaced padded\">";    
$main->echo_messages($arr_messages);
echo "</p>";
echo "<br>";

/* BEGIN REQUIRED FORM */
$main->echo_form_begin();
$main->echo_module_vars();

if (!$main->button(1))
	{
	$params = array("class"=>"spaced","number"=>1,"target"=>$module, "slug"=>$slug, "passthis"=>true, "label"=>"Delete Cascade");
	$main->echo_button("delete_cascade", $params);
 	}

 //post variables        
echo "<input type = \"hidden\"  name = \"post_key\" value = \"" . $post_key . "\">";
echo "<input type = \"hidden\"  name = \"row_type\" value = \"" . $row_type . "\">";

//form vars necessary for header link
$main->echo_common_vars();
$main->echo_form_end();
/* FORM */
?>
