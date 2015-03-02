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
function bb_reload()
    {
    //this goes off when list is changed    
    var frmobj = document.forms["bb_form"];
    
    frmobj.offset.value = 1;
	bb_submit_form(0); //call javascript submit_form function
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
$arr_lists = $main->get_json($con, "bb_create_lists");
$default_row_type = $main->get_default_layout($arr_layouts_reduced);

//State vars
//do list view postback, get variables from browse_state
//view value is the list position

/*** BEGIN LISTVIEW POSTBACK ***/
$main->retrieve($con, $array_state);

//get archive mode, default Off, show only zeros
$mode = ($archive == 0) ? "1 = 1" : "archive < " . $archive;

$arr_state = $main->load($module, $array_state);

$row_type = $main->state('row_type', $arr_state, $default_row_type);
$offset = $main->process('offset', $module, $arr_state, 1);

//change row_type, get first value for that row type
if ($main->check('row_type',$module) && ($row_type <> $main->post('row_type',$module)))
    {
    $row_type = $main->post('row_type', $module, $default_row_type);
    $list_number = isset($arr_lists[$row_type]) ? $main->get_default_list($arr_lists[$row_type]) : 1;
    $row_type = $main->set('row_type', $arr_state, $row_type);
    }
//change list   
else
    {
    $row_type = $main->process('row_type', $module, $arr_state, $default_row_type);
    $default_list_number = isset($arr_lists[$row_type]) ? $main->get_default_list($arr_lists[$row_type]) : 1;
	$list_number = $main->process('list_number', $module, $arr_state, $default_list_number);  
    }

/* back to string */
$main->update($array_state, $module, $arr_state);
/*** END POSTBACK ***/
?>
<?php
//get list fields, xml4 is the list fields
//get description
if (isset($arr_lists[$row_type][$list_number]))
    {
    $arr_list = $arr_lists[$row_type];
    $list = $arr_list[$list_number];
    $description = $list['description'];
    }
else
    {
    $arr_list = array();
    $list = array();
    $description = "";       
    }

//center
echo "<div class=\"table spaced border tablecenter\"><div class=\"row padded\">";

/* BEGIN REQUIRED FORM */
//form part, based on xml, hidden field return offset
$main->echo_form_begin();
$main->echo_module_vars();

echo "<div class=\"cell padded middle\">";
echo "Choose List: ";
$params = array("class"=>"spaced","onchange"=>"bb_reload()");
$main->layout_dropdown($arr_layouts_reduced, "row_type", $row_type, $params);
$params = array("class"=>"spaced","onchange"=>"bb_reload()");
$main->list_dropdown($arr_list, "list_number", $list_number, $params);

//list return
echo "<input type = \"hidden\"  name = \"offset\" value = \"" . $offset . "\">";
echo "</div>";
echo "<div class=\"cell padded middle\">";
$main->echo_textarea("description", $description, $params = array("rows"=>2,"cols"=>50,"class"=>"spaced border","readonly"=>"readonly"));

$main->echo_common_vars();

$main->echo_state($array_state);
$main->echo_form_end();

echo "</div></div></div>"; //end align center, table, row

/* BEGIN RETURN ROWS */
//calculate lower limit of ordered query, return rows will be dealt with later
//initialize $count_rows in case no rows are returned
//initialze query variables to return list in segments	
$return_rows = $main->set_constant('RETURN_ROWS',4);
$pagination = $main->set_constant('PAGINATION',5);
$count_rows = 0;
$lower_limit = ($offset - 1) * $return_rows;

//list numbers should always be positive
if ($list_number > 0)
	{
	//if a list has been selected
    $arr_layout = $arr_layouts_reduced[$row_type];
    $arr_column = isset($arr_columns[$row_type]) ? $arr_columns[$row_type] : array();
    $arr_column_reduced = $main->filter_keys($arr_column);
	$col1 = isset($arr_column['layout']['primary']) ? $main->pad("c", $arr_column['layout']['primary']) : "c01";
    
    //get column name from "primary" attribute in column array
    //this is used to populate the record header link to parent record
    $parent_row_type = $arr_layout['parent']; //will be default of 0, $arr_columns[$parent_row_type] not set if $parent_row_type = 0
    $leftjoin = isset($arr_columns[$parent_row_type]['primary']) ? $main->pad("c", $arr_columns[$parent_row_type]['primary']) : "c01";

	//query
	$query = "SELECT count(*) OVER () as cnt, T1.*, T2.hdr, T2.row_type_left FROM data_table T1 " .
			 "LEFT JOIN (SELECT id, row_type as row_type_left, " . $leftjoin . " as hdr FROM data_table WHERE row_type = " . $parent_row_type . ") T2 " .
		 	 "ON T1.key1 = T2.id " .
			 "WHERE list_retrieve(list_string, " . $list_number . ") = 1 AND row_type = " . $row_type . " AND " . $mode . " ORDER BY " . $col1 . ", id LIMIT " . $return_rows . " OFFSET ". $lower_limit .";";
    
    //echo "<p>" . $query . "</p>";
	$result = $main->query($con, $query);		
	//this outputs the return conut
	$main->return_stats($result);

    //get row, set xml on row_type, echo out details
	while($row = pg_fetch_array($result))
		{
		echo "<div class =\"margin divider\">";
		$main->return_header($row, "bb_cascade");
		echo "<div class=\"clear\"></div>";
  		$count_rows = $main->return_rows($row, $arr_column_reduced);
		echo "<div class=\"clear\"></div>";			 
		$main->output_links($row, $arr_layouts_reduced, $userrole);
          echo "</div>";		 
		echo "<div class=\"clear\"></div>"; 
		}
	} //end if

/* END RETURN ROWS */
//create the query depth selector
//uses $offset variable from previous script
//also uses $count_rows variable and $return_rows global
//creates logic to make prev and next links etc

$main->page_selector("offset", $offset, $count_rows, $return_rows, $pagination);

?>

