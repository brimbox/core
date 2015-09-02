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
//find default row_type, $arr_layouts must have one layout set
$arr_layouts = $main->get_json($con, "bb_layout_names");
$arr_layouts_reduced = $main->filter_keys($arr_layouts);
$default_row_type = $main->get_default_layout($arr_layouts_reduced);

/***START STATE AND VIEW POSTBACK***/
$POST = $main->retrieve($con, $array_state);

//get archive mode, default Off, show only zeros
$mode = ($archive == 0) ? "1 = 1" : "archive < " . $archive;

$arr_state = $main->load($module, $array_state);

//coming from an add or edit link, reset $arr_state
//bb_row_type is empty if not set with javascript, row_type should be 1 to 26, 0 will render as empty 
if (!empty($POST['bb_row_type']))
        {
		$offset = $main->set('offset', $arr_state, 1);
		$row_type = $main->set('row_type', $arr_state, $POST['bb_row_type']);
		$post_key = $main->set('post_key', $arr_state, $POST['bb_post_key']);
        }		
else //default = nothing, or populate with input_state if coming from other page
        {
		$offset = $main->process('offset', $module, $arr_state, 1);
		$row_type = $main->process('row_type', $module, $arr_state, $default_row_type);
		$post_key = $main->process('post_key', $module, $arr_state, 0);
        }

$main->update($array_state, $module, $arr_state);
/*** END POSTBACK ***/
?>
<?php
/*** COLUMN AND LAYOUT INFO ***/
//get xml_column and sort column type
$arr_columns = $main->get_json($con, "bb_column_names");

//for the header left join
//get column name from "primary" attribute in column array
//this is used to populate the record header link to parent record
$arr_layout = $arr_layouts_reduced[$row_type];
$parent_row_type = $arr_layout['parent']; //will be default of 0, $arr_columns[$parent_row_type] not set if $parent_row_type = 0
$leftjoin = isset($arr_columns[$parent_row_type]['primary']) ? $main->pad("c", $arr_columns[$parent_row_type]['primary']) : "c01";
/*** END COLUMN AND LAYOUT INFO ***/

/* BEGIN REQUIRED FORM */
$main->echo_form_begin();
$main->echo_module_vars();

//local POST variables
echo "<input type=\"hidden\" name=\"offset\" value=\"" . $offset . "\" />";
echo "<input type=\"hidden\" name=\"row_type\" value=\"" . $row_type . "\" />";
echo "<input type=\"hidden\" name=\"post_key\" value=\"" . $post_key . "\" />";

//global POST variables
$main->echo_common_vars();
$main->echo_state($array_state);
$main->echo_form_end();
/* END FORM */

/* BEGIN RETURN ROWS */
//calculate lower limit of ordered query, return rows will be dealt with later
//initialize $count_rows in case no rows are returned

$return_rows = $main->get_constant('BB_RETURN_ROWS',4);
$pagination = $main->get_constant('BB_PAGINATION',5);
$count_rows = 0;
$lower_limit = ($offset - 1) * $return_rows;

//this if includes for return_rows -- repeated on page selector
if ($post_key > 0) //cascade children of record
	{
     //done in steps since it is a return
    $query = "WITH RECURSIVE t(id) AS (" .
             "SELECT id FROM data_table WHERE id = " . $post_key . " " .
             "UNION ALL " .
             "SELECT T1.id FROM data_table T1, t " .
             "WHERE t.id = T1.key1)" . 
             "SELECT id FROM t";
    $result = $main->query($con, $query);
    
    $cnt_rows = pg_num_rows($result);
    if ($cnt_rows > 0) //after delete bb_post_key could be set on tabs
        {        
        $arr_ids = pg_fetch_all_columns($result,0);
        $ids_cascade = implode(",",$arr_ids);
        $union_cascade = "SELECT " . implode(" as id UNION SELECT ", $arr_ids) . " as id";
            
        $arr_union = array();
        foreach ($arr_layouts_reduced as $key => $value)
            {
            $str_union = "SELECT " . $key . " as row_type_union, " . $value['order'] . " as sort";
            array_push($arr_union, $str_union);
            }
        $str_union_query = implode(" UNION ", $arr_union);
    
        //four int(s) and a string
        $query = "SELECT * FROM (SELECT count(*) OVER () as cnt, T1.*, T2.* FROM data_table T1 ".
                 "LEFT JOIN (SELECT id as id_left, row_type as row_type_left, " . $leftjoin . " as hdr FROM data_table) T2 " .
                 "ON T1.key1 = T2.id_left " .
                 "WHERE T1.id IN (" . $ids_cascade . ") AND " . $mode . ") T3 " .
                 "INNER JOIN (" . $str_union_query . ") T4 ON T3.row_type = T4.row_type_union ORDER BY T4.sort, T3.row_type, T3.id DESC LIMIT " . $return_rows . " OFFSET " . $lower_limit . ";";
        //echo "<p>" . $query . "</p>"; 
        $result = $main->query($con, $query);
    
        //this outputs the return count
        $main->return_stats($result);
    
        //this outputs the data blobs
        $row_type_catch = 0;
        while($row = pg_fetch_array($result))
            {
            //this sets the correct column xml -- each iteration requires new columns
            //$xml is global so there is only one round trip to the db per page load
            //get row type from returned rows
            $row_type = $row['row_type'];
            $arr_column_reduced = $main->filter_keys($arr_columns[$row_type]);
            
            if ($row_type <> $row_type_catch)
                {
                echo "<div class =\"margin divider\">";
                }
            else
                {
                echo "<div class =\"margin darkline\">";   
                }        
    
            //echo records
            //only return header link on first record, details of parent records are available on cascade
            $bool_header = ($row['id'] == $post_key) ? true : false;
            $main->return_header($row, "bb_cascade", $bool_header);
            echo "<div class=\"clear\"></div>";
            $count_rows = $main->return_rows($row, $arr_column_reduced);
            echo "<div class=\"clear\"></div>";				
            $main->output_links($row, $arr_layouts_reduced, $userrole);
            echo "<div class=\"clear\"></div>";
            echo "</div>";
            $row_type_catch = $row_type;
            }
        } //count rows if
	}
/* END RETURN ROWS */
	
//create the query depth selector
//also uses $count_rows variable and $return_rows global from script #2
//sets the return variable for query depth with javascript
//creates logic to make prev and next links etc

$main->page_selector("offset", $offset, $count_rows, $return_rows, $pagination);

?>
