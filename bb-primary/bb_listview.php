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
<script type="text/javascript">
function reload_on_list()
    {
    //this goes off when list is changed    
    var frmobj = document.forms["bb_form"];
    
    frmobj.offset.value = 1;
	bb_submit_form(0); //call javascript submit_form function
	return false;
    }
function reload_on_layout()
    {
    //change row_type, reload appropriate columns
    //this goes off when row_type is changed    
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
$arr_columns = $main->get_json($con, "bb_column_names");
$arr_lists = $main->get_json($con, "bb_create_lists");
$default_row_type = $main->get_default_layout($arr_layouts);

//State vars
//do list view postback, get variables from browse_state
//view value is the list position

/*** BEGIN LISTVIEW POSTBACK ***/
$main->retrieve($con, $array_state, $userrole);

//get archive mode
$mode = ($archive == 1) ? " 1 = 1 " : " archive IN (0)";

$arr_state = $main->load($module, $array_state);

$row_type = $main->state('row_type', $arr_state, $default_row_type);
$list_number = $main->state('list_number', $arr_state, 0);
$offset = $main->process('offset', $module, $arr_state, 1);

//entrance, get first value for default row type or state row_type
if (!$main->check('row_type', $module) && empty($list_number))
    {
    $arr_list = $arr_lists[$row_type];
    if (isset($arr_list[key($arr_list)]))
        {
        $list_number = key($arr_list);
        }
    else
        {
        $list_number = "";    
        }
	$list_number = $main->set('list_number', $arr_state, $list_number);
    $row_type = $main->set('row_type', $arr_state, $row_type);
    }
//change row_type, get first value for that row type
elseif ($main->check('row_type',$module) && ($row_type <> $main->post('row_type',$module)))
    {
    $row_type = $main->post('row_type', $module, $default_row_type);
    if (isset($arr_lists[$row_type])) $arr_list = $arr_lists[$row_type];
    if (isset($arr_list))
        {
        $list_number = key($arr_list);
        }
    else
        {
        $list_number = 0;    
        }
	$list_number = $main->set('list_number', $arr_state, $list_number);
    $row_type = $main->set('row_type', $arr_state, $row_type);
    }
//change list   
elseif ($main->check('list_number',$module) && ($row_type <> $main->post('list_number',$module)))
    {
	$list_number = $main->process('list_number', $module, $arr_state, 0);  
    $row_type = $main->process('row_type', $module, $arr_state, $default_row_type);
    }


/* back to string */
$main->update($array_state, $module, $arr_state);
/*** END POSTBACK ***/
?>
<?php
//get list fields, xml4 is the list fields
//get description
if (!empty($list_number))
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
$params = array("class"=>"spaced","onchange"=>"reload_on_layout()");
$main->layout_select($arr_layouts, "row_type", $row_type, $params);
$params = array("class"=>"spaced","onchange"=>"reload_on_list()");
$main->list_select($arr_list, "list_number", $list_number, $params);

//list return
echo "<input type = \"hidden\"  name = \"offset\" value = \"" . $offset . "\">";
echo "</div>";
echo "<div class=\"cell padded middle\">";
echo "<textarea name = \"description\" rows=\"2\" cols=\"50\" class=\"spaced border\">" . $description . "</textarea><br />";

$main->echo_common_vars();

$main->echo_state($array_state);
$main->echo_form_end();

echo "</div></div></div>"; //end align center, table, row

/* BEGIN RETURN ROWS */
//calculate lower limit of ordered query, return rows will be dealt with later
//initialize $count_rows in case no rows are returned
//initialze query variables to return list in segments	
$return_rows = RETURN_ROWS;
$pagination = PAGINATION;
$count_rows = 0;
$lower_limit = ($offset - 1) * $return_rows;

if (!empty($list_number))
	{
	//if a list has been selected
    $arr_layout = $arr_layouts[$row_type];
    $arr_column = $arr_columns[$row_type];
	$col1 = isset($arr_column['layout']['primary']) ? $arr_column['layout']['primary'] : "c01";    
    
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
  		$count_rows = $main->return_rows($row, $arr_column);
		echo "<div class=\"clear\"></div>";			 
		$main->output_links($row, $arr_layouts, $userrole);
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

