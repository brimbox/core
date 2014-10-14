<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
Copyright (C) 2012 - 2013  Kermit Will Richardson, Brimbox LLC

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
/* Contains recursive descent parser for boolean expression */
include("bb_search_extra.php");

?>
<script type="text/javascript">
//reset hidden offset to 1 when search button is submitted
//standard search submit
function reset_return()
    {
    //form names in javascript are hard coded
    var frmobj = document.forms["bb_form"];
    
    frmobj.offset.value = 1;
    bb_submit_form(1);
	return false;
    }
</script>
<?php
/* INITIALIZE */
//find default row_type, $arr_layouts must have one layout set
$arr_layouts = $main->get_json($con, "bb_layout_names");
//default row_type is 0, for all layouts

//message pile
$arr_message = array();

/* BEGIN STATE AND POSTBACK PROCESS */
//do search postback
$main->retrieve($con, $array_state, $userrole);

//initialize values
$search_array = array("","No Search Terms Entered");

//get archive mode
$mode = ($archive == 1) ? " 1 = 1 " : " archive IN (0)";
    
//get search state variables are set use them
$arr_state = $main->load($module, $array_state);

$search = $main->process('search', $module, $arr_state, "");
//make a copy
$search_parsed = $search;
$offset = $main->process('offset', $module, $arr_state, 1);
$row_type = $main->process('row_type', $module, $arr_state, 0);

//archive flag checkbox
$archive_flag = $main->process('archive_flag', $module, $arr_state, 0);

//back to string
$main->update($array_state, $module,  $arr_state);
/* END STATE PROCESS */

/* PARSE SEARCH STRING */
//function parse_boolean_string calls four non-object functions, advance, token, open, operator
$message = parse_boolean_string($search_parsed);
array_push($arr_message, $message); 

/* GET LAYOUT */
$arr_columns = $main->get_json($con, "bb_column_names");

/* BEGIN REQUIRED FORM */
//search form
$main->echo_form_begin();
$main->echo_module_vars();

echo "<div class=\"center\">";
//search vars
echo "<input type=\"text\" name=\"search\" class = \"spaced\" size=\"35\" value = \"" . htmlentities($search) . "\"/>";
$params = array("all"=>true);
$main->layout_select($arr_layouts, "row_type", $row_type, $params);
echo "<input type = \"hidden\"  name = \"offset\" value = \"" . $offset . "\">";

//echo state variables into form
//variables to hold the $_POST variables for the links
$main->echo_common_vars();

//button and end form
$params = array("class"=>"spaced","onclick"=>"reset_return()", "label"=>"Search Database");
$main->echo_script_button("post_search", $params);

//archive interworking allows quick access to archived records
if (ARCHIVE_INTERWORKING == "ON")
	{
	$checked = "";
	if ($archive == 1)
		{
		$checked =  "checked";
		$mode = " 1 = 1 ";
		}
	echo "<span class = \"border rounded padded shaded\">";
    $main->echo_input("archive_flag", 1, array('type'=>'checkbox','class'=>'middle padded','checked'=>$checked));
    echo "<label class=\"padded\">Check Archives</label>";
	echo "</span><br>";
	}
	
echo "</div>"; //end align center

//echo state variables into form
$main->echo_state($array_state);
$main->echo_form_end();
/* END FORM */ ?>

<?php
/* BEGIN RETURN ROWS */
//this function returns the repetitive rows from the search query

$return_rows = RETURN_ROWS;
$pagination = PAGINATION;
$count_rows = 0;

//search array successfully parsed			
if (empty($message))
	{	
	$lower_limit = (($offset - 1) * $return_rows);
	$escaped_search_parsed = pg_escape_string($search_parsed); //search array is decoded
    
    $and_clause = ($row_type == 0) ? " 1=1 " : " row_type = " . $row_type . " ";
	
    //parent columns could come from a variety of rows
	//left join cols, get all possible columns, and then make them distict with _left
    $arr_parent_columns = array(0=>"c01"); //default value, if not set
    foreach ($arr_layouts as $value)  //loop through arr_layout
        {
        if ($value['parent'] > 0) //indicator set to zero when xml made into array
            {
            $parent_row_type = $value['parent']; //parent row type will be zero if not set
            if (isset($arr_columns[$parent_row_type]['primary']))
                {
                $column = $main->pad("c", $arr_columns[$parent_row_type]['primary']);
                array_push($arr_parent_columns, $column); //push on stack
                }             
            }
        }
    
    //start building query   
    $arr_parent_columns = array_unique(array_filter($arr_parent_columns)); //distinct, no empty values
    foreach ($arr_parent_columns as &$value)
        {
        $value = $value  . " as " . $value  . "_left"; 
        }
    $leftjoin = implode(", ",$arr_parent_columns);  //implode on comma
        
    //must have full layout during return rows
    
    $query = "SELECT count(*) OVER () as cnt, T3.*, T4.*, ts_rank_cd(fts, qry, 1) as rnk, '' as hdr FROM  " .
             "((SELECT * FROM data_table) T1 " .
             "INNER JOIN " .
             "(SELECT to_tsquery('" . $escaped_search_parsed . "') qry) T2 " .
             "ON T1.fts @@ T2.qry) T3 " .
             "LEFT JOIN " .
             "(SELECT id as id_left, row_type as row_type_left, " . $leftjoin . " FROM data_table) T4 " .
             "ON T3.key1 = T4.id_left " .
             "WHERE " . $mode . " AND " . $and_clause . " " .
             "ORDER BY rnk DESC, id DESC LIMIT " . $return_rows . " OFFSET ". $lower_limit .";";
             
    //echo "<p>" . $query . "</p>";
    $result = $main->query($con, $query);
    
    //echo the number of rows returned -- return stats
    $cnt_rows = $main->return_stats($result);
    if ($cnt_rows == 0)
        {
        array_push($arr_message, "No rows have been found");    
        }
        
    while($row = pg_fetch_array($result))
        {
        //this sets the correct column xml -- each iteration requires new columns
        //$xml is global so there is only one round trip to the db per page load
        //get row type from returned rows
        $arr_column = $arr_columns[$row['row_type']];
        
        //get the primary column and set $row['hdr'] based on primary header      
        $parent_row_type = $arr_layouts[$row['row_type']]['parent'];
        $leftjoin = isset($arr_columns[$parent_row_type]['primary']) ? $main->pad("c", $arr_columns[$parent_row_type]['primary']) : "c01";
     
        $row['hdr'] = $row[$leftjoin . "_left"];
        
        //echo records
        echo "<div class =\"margin divider\">";
        $main->return_header($row, "bb_cascade");
        echo "<div class=\"clear\"></div>";
        $count_rows = $main->return_rows($row, $arr_column);
        echo "<div class=\"clear\"></div>";				
        $main->output_links($row, $arr_layouts, $userrole);
        echo "<div class=\"clear\"></div>";
        echo "</div>";	 
        }
    } 
/*END RETURN ROWS */

echo "<div class=\"center\">";
$main->echo_messages($arr_message);
echo "</div>";

$main->page_selector("offset", $offset, $count_rows, $return_rows, $pagination);
?>
