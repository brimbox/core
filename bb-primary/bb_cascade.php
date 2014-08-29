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
$main->check_permission(array(3,4,5));
?>

<?php
/* INITIALIZE */
//find default row_type, $xml_layouts must have one layout set
$xml_layouts = $main->get_xml($con, "bb_layout_names");
$default_row_type = $main->get_default_row_type($xml_layouts);

/***START STATE AND VIEW POSTBACK***/
$main->retrieve($con, $array_state, $userrole);

//get archive mode
$xml_home = $main->load('bb_home', $array_state);
$mode = ($xml_home->mode == "On") ? " 1 = 1 " : " archive IN (0)";

$xml_state = $main->load($module, $array_state);

//coming from an add or edit link, reset $xml_state
//bb_row_type is empty if not set with javascript
if (!empty($_POST['bb_row_type']))
        {
		$offset = $main->set('offset', $xml_state, 1);
		$row_type = $main->set('row_type', $xml_state, $_POST['bb_row_type']);
		$post_key = $main->set('post_key', $xml_state, $_POST['bb_post_key']);
        }		
else //default = nothing, or populate with input_state if coming from other page
        {
		$offset = $main->process('offset', $module, $xml_state, 1);
		$row_type = $main->process('row_type', $module, $xml_state, $default_row_type);
		$post_key = $main->process('post_key', $module, $xml_state, 0);
        }

$main->update($array_state, $module, $xml_state);
/*** END POSTBACK ***/
?>
<?php
/*** COLUMN AND LAYOUT INFO ***/
//get xml_column and sort column type
$xml_columns = $main->get_xml($con, "bb_column_names");

$layout = "l" . str_pad($row_type,2,"0",STR_PAD_LEFT);
$xml_layout = $xml_layouts->$layout;
$xml_column = $xml_columns->$layout;

//for the header left join
$parent_row_type = $xml_layout['parent'];
$parent_layout = "l" . str_pad($parent_row_type,2,"0",STR_PAD_LEFT);
$xml_column_parent = $xml_columns->$parent_layout;
$leftjoin = isset($xml_column_parent['primary']) ? $xml_column_parent['primary'] : "c01";
/*** END COLUMN AND LAYOUT INFO ***/

/* BEGIN REQUIRED FORM */
$main->echo_form_begin();
$main->echo_module_vars($module);

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

$return_rows = RETURN_ROWS;
$pagination = PAGINATION;
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
        foreach ($xml_layouts->children() as $child)
            {
            $i = (int)substr($child->getName(),1);
            $str_union = "SELECT " . $i . " as row_type_union, " . (int)$child['order'] . " as sort";
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
            $row_type = (int)$row['row_type'];
            $layout = "l" . str_pad($row_type,2,"0",STR_PAD_LEFT);
            $xml_column = $xml_columns->$layout;
            
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
            $count_rows = $main->return_rows($row, $xml_column);
            echo "<div class=\"clear\"></div>";				
            $main->output_links($row, $xml_layouts, $userrole);
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
