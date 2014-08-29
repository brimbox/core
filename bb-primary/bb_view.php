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
<script type="text/javascript">
/* MODULE JAVASCRIPT */
function bb_reload_on_column()
    {
    //this goes off when column is changed    
    var frmobj = document.forms["bb_form"];

    frmobj.offset.value = 1;
    bb_submit_form(); //call javascript submit_form function
	return false;
    }
function bb_reload_on_asc_desc()
    {
    //this goes off when asc_desc is changed    
    var frmobj = document.forms["bb_form"];

    frmobj.offset.value = 1;
    bb_submit_form(); //call javascript submit_form function
	return false;
    }
/* END MODULE JAVASCRIPT */
</script>

<?php
//This function displays the result set

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

//coming from an view link, set $xml_state
//bb_row_type is empty if not set with javascript
if (!empty($_POST['bb_row_type']))
        {
		//global post_key and row_type
		$offset = $main->set('offset', $xml_state, 1);
		$row_type = $main->set('row_type', $xml_state, $_POST['bb_row_type']);
		$post_key = $main->set('post_key', $xml_state, $_POST['bb_post_key']);
		$col1 = $main->set('col1', $xml_state, "create_date");
		$asc_desc = $main->set('asc_desc', $xml_state, "DESC");
        }		
else //get on postback, or populate with input_state if coming from other page
        {
        //local post_key and row_type
		$offset = $main->process('offset', $module, $xml_state, 1);
		$row_type = $main->process('row_type', $module, $xml_state, $default_row_type);
		$post_key = $main->process('post_key', $module, $xml_state, 0);
		$col1 = $main->process('col1', $module, $xml_state, "create_date");
		$asc_desc = $main->process('asc_desc', $module, $xml_state, 'DESC');
       }
        
//save state
$main->update($array_state, $module, $xml_state);
/*** END POSTBACK ***/
?>
<?php
/*** COLUMN AND LAYOUT INFO ***/
            
//get xml_column and sort column type
$xml_columns = $main->get_xml($con, "bb_column_names");
$layout = $main->pad("l", $row_type);
$xml_column = $xml_columns->$layout;
$xml_layout = $xml_layouts->$layout;

//for the header left join
$parent_row_type = (int)$xml_layout['parent'];
$parent_layout = $main->pad("l", $parent_row_type);
$xml_column_parent = $xml_columns->$parent_layout;
$leftjoin = isset($xml_column_parent['primary']) ? $xml_column_parent['primary'] : "c01";
/*** END COLUMN AND LAYOUT INFO ***/

/* BEGIN REQUIRED FORM */
$main->echo_form_begin();
$main->echo_module_vars($module);

echo "<span class=\"spaced\">Order By:</span>";
echo "<select name=\"col1\" class=\"spaced\" onchange=\"bb_reload_on_column()\">";
//can't use automatic column function because of create and modify date
//order on create or modify date, use actual column names in this output
echo "<option value=\"create_date\" " . ($col1 == "create_date" ? "selected" : "") . ">Created&nbsp;</option>";
echo "<option value=\"modify_date\" " . ($col1 == "modify_date" ? "selected" : "") . ">Modified&nbsp;</option>";
//build field options for column names
foreach($xml_column->children() as $child)
    {
    $col = $child->getName();
    echo "<option value=\"" . $col . "\" " . ($col == $col1 ? "selected" : "") . ">" . htmlentities((string)$child) . "&nbsp;</option>";
    }
echo "</select>";
//dropdown for ascending or descending
echo "<select name=\"asc_desc\" class=\"spaced\" onchange=\"bb_reload_on_asc_desc()\">";
//build field options for column names
echo "<option value=\"ASC\" " . ($asc_desc == "ASC" ? "selected" : "") . ">Ascending&nbsp;</option>";
echo "<option value=\"DESC\" " . ($asc_desc == "DESC" ? "selected" : "") . ">Descending&nbsp;</option>";
echo "</select>";

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
if ($post_key > 0) //viewing children of record
	{	
 	//four int(s) and a string
	$query = "SELECT count(*) OVER () as cnt, T1.*, T2.hdr, T2.row_type_left FROM data_table T1 ".
			 "LEFT JOIN (SELECT id, row_type as row_type_left, " . $leftjoin . " as hdr FROM data_table WHERE row_type = " . $parent_row_type . ") T2 " .
		 	 "ON T1.key1 = T2.id " .
			 "WHERE key1 = " . $post_key . " AND row_type = " . $row_type . " AND " . $mode . " ORDER BY " . $col1 . " " . $asc_desc . ", id LIMIT " . $return_rows . " OFFSET " . $lower_limit . ";";
    //echo "<p>" . $query . "</p>"; 
	$result = $main->query($con, $query);

	//this outputs the return conut
	$main->return_stats($result);

	//this outputs the data blobs
	while($row = pg_fetch_array($result))
		{
		echo "<div class =\"margin divider\">";
		$main->return_header($row, "bb_cascade");
		echo "<div class=\"clear\"></div>";	
  		$count_rows = $main->return_rows($row, $xml_column);
		echo "<div class=\"clear\"></div>";		 
		$main->output_links($row, $xml_layouts, $userrole);
		echo "<div class=\"clear\"></div>";
        echo "</div>";	 
  		}
	}
/* END RETURN ROWS */
	
//create the query depth selector
//also uses $count_rows variable and $return_rows global from script #2
//sets the return variable for query depth with javascript
//creates logic to make prev and next links etc

$main->page_selector("offset", $offset, $count_rows, $return_rows, $pagination);

?>
