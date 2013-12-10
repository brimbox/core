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
$main->check_permission(3);
?>
<script type="text/javascript">
/* MODULE JAVASCRIPT */
function remove_message()
    {
    document.getElementById('input_message').innerHTML = "";
	return false;
    }
function bb_reload_on_layout()
    {
    var frmobj = document.forms["bb_form"];
    //set a button of 4 for postback
    bb_submit_form(4);
	return false;
    }
</script>

<?php
/* INITIALIZE */
//find default row_type, $xml_layouts must have one layout set
$xml_layouts = $main->get_xml($con, "bb_layout_names");
$default_row_type = $main->get_default_row_type($xml_layouts);
$arr_notes = array("c49","c50");
$textarea_rows = 4; //minimum

//message pile
$arr_message = array();

/*INPUT STATE AND POSTBACK */  
//this is a major/long part in module
$main->retrieve($con, $array_state, $userrole);

$xml_state = $main->load($module, $array_state);

//need to get row_number before getting $xml_column
//$xml_column returned when $row_type is popluated
$xml_columns = $main->get_xml($con, "bb_column_names");
//get dropdown values while were at it
$xml_dropdowns = $main->get_xml($con, "bb_dropdowns");

//arr_notes used several times including in postback extra file
$unique_key = "";
$message = ""; //return message

//include file for postback routines
//this include handles input state
include("bb_input_extra.php");

//$layout and $xml_column set in bb_input_extra

//$xml_layout will be needed
$xml_layout = $xml_layouts->$layout;

/* back to string */
$main->update($array_state, $module, $xml_state);
/*END INPUT STATE AND POSTBACK */
	
/* SUBMIT TO DATABASE */
//validation error arr_error_msg
if ($main->post('bb_button',$module) == 1)
	{
    $obj_errors = new stdClass(); //empty object
	$errors = false;
	foreach($xml_column->children() as $child)
        {
        /* START VALIDATION */
		$type = (string)$child['type']; //validation type 
        $required_flag = $child['req'] == 1 ? true : false; //required boolean       
        $col = $child->getName();
            
        //textarea no validation
		if (in_array($col,$arr_notes))
			{
            //long column
			$main->set($col, $xml_state, $main->custom_trim_string($main->post($col,$module),65536, false));
			}                
		else
			{
            //regular column
            $value = $main->custom_trim_string($main->post($col,$module),255);
            $return_required = false;
			$return_validate = false;
            //required field  
            if ($required_flag) //false=not required, true=required
                {
                $return_required = $main->validate_required($value, true);    
                }
            //populated string = error, boolean is good
            if (!is_bool($return_required)) 
                {
                $obj_errors->$col = $return_required;
				$errors = true;
                } 
            elseif (!empty($value)) //field has data, trimmed already
                {
				//value is passed a reference and may change in function if formatted
                $return_validate = $main->validate_logic($type, $value, true);
                if (!is_bool($return_validate))
                    {
                    $obj_errors->$col = $return_validate;
					$errors = true;
                    }
                }
			$main->set($col, $xml_state, $value);
			}
		}
        /* END VALIDATION */
        
    /* INSERT OR UPDATE ROW */       
    if (!$errors) //no errors
        {
        //produce empty form since we are going to load the data
        $owner = $main->custom_trim_string($_SESSION['email'],255); //used in both if and else
        $unique_key = isset($xml_column['key']) ? (string)$xml_column['key'] : "";  //used in both update and insert
        if ($row_type == $row_join) // update preexisting row
            {
            $update_clause = "updater_name = '" . pg_escape_string($owner) . "'";
			$arr_ts_vector_fts = array();
			$arr_ts_vector_ftg = array();
            foreach($xml_column->children() as $child)
				{
				$col = $child->getName();
				$str = pg_escape_string((string)$xml_state->$col);
				$update_clause .= "," . $col . " =  '" . $str . "'";;
				//prepare fts and ftg
				$search_flag = ($child['search'] == 1) ? true : false;
				//guest flag
				if (empty($array_guest_index))
					{
					$guest_flag = (($child['search'] == 1) && ($child['secure'] == 0)) ? true : false;
					}
				else
					{
					$guest_flag = (($child['search'] == 1) && in_array((int)$child['secure'], $array_guest_index)) ? true : false;						
					}
				//build fts SQL code
				if ($search_flag)
					{
					array_push($arr_ts_vector_fts, "'" . $str . "' || ' ' || regexp_replace('" . $str . "', E'(\\\\W)+', ' ', 'g')");
					}
				if ($guest_flag)
					{
					array_push($arr_ts_vector_ftg, "'" . $str . "' || ' ' || regexp_replace('" . $str . "', E'(\\\\W)+', ' ', 'g')");
					}
				}
			$str_ts_vector_fts = !empty($arr_ts_vector_fts) ? implode(" || ' ' || ", $arr_ts_vector_fts) : "''";
			$str_ts_vector_ftg = !empty($arr_ts_vector_ftg) ? implode(" || ' ' || ", $arr_ts_vector_ftg) : "''";
			
            //update query
            //check that row exists in update because of multiuser situation
			$select_where_not = "SELECT 1 WHERE 1 = 0";
            if (!empty($unique_key))
                {
                $unique_value = isset($xml_state->$unique_key) ? (string)$xml_state->$unique_key : "";
                if (!empty($unique_value))
                    {
                    $select_where_not = "SELECT 1 FROM data_table WHERE row_type IN (" . $row_type . ") AND id NOT IN (" . $post_key . ") AND lower(" . $unique_key . ") IN (lower('" . $unique_value . "'))";                        
                    }
				else
					{
					$select_where_not = "SELECT 1";	
					}
				}                
            //will not allow a blank if $unique_key is set
			$return_primary = $xml_column['primary'];
            $query = "UPDATE data_table SET " . $update_clause . ", fts = to_tsvector(" . $str_ts_vector_fts . "), ftg = to_tsvector(" . $str_ts_vector_ftg . ") " .
				     "WHERE id IN (" . $post_key . ") AND archive = 0 AND NOT EXISTS (" . $select_where_not . ") RETURNING id, " . $return_primary . " as primary;";                 
            $result = $main->query($con, $query);
            if (pg_affected_rows($result) == 1)
                {
				$row = pg_fetch_array($result);
                array_push($arr_message, "Record Succesfully Edited.");
                $main->update($array_state, $module, $xml_state);												
				//will find $parent_id, $inserted_id when finding parent
                }
            else //bad edit
                {
				$result = $main->query($con, $select_where_not);
				if (pg_num_rows($result) == 1)
					{
					//retain state values
					array_push($arr_message, "Error: Record not updated. Duplicate or empty key value in input form on column \"" . $xml_column->$unique_key . "\"."); 
					}
				else
					{
					//dispose of post vars
					$row_join = -1;
					$post_key = -1;
					$row_type = 0;
					//dispose of $xml_state and update state
					$xml_state = simplexml_load_string("<hold></hold>");            
					$main->update($array_state, $module, $xml_state);
	
					array_push($arr_message, "Error: Record not updated. Record archived or underlying data change possible.");
					}
                }
            }
        else //insert new row
            {
            $insert_clause = "row_type, key1, owner_name, updater_name";
            $select_clause = $row_type . " as row_type, " . $post_key . " as key1, '" . $owner . "' as owner_name, '" . $owner . "' as updater_name";

			$arr_ts_vector_fts = array();
			$arr_ts_vector_ftg = array(); 
            foreach($xml_column->children() as $child)
                {
                $col = $child->getName();
                $str = pg_escape_string((string)$xml_state->$col);
                $insert_clause .= "," . $col;
                $select_clause .= ", '" . $str . "'";
				$search_flag = ($child['search'] == 1) ? true : false;
				//guest flag
				if (empty($array_guest_index))
					{
					$guest_flag = (($child['search'] == 1) && ($child['secure'] == 0)) ? true : false;
					}
				else
					{
					$guest_flag = (($child['search'] == 1) && in_array((int)$child['secure'], $array_guest_index)) ? true : false;						
					}
				//build fts SQL code

				if ($search_flag)
					{
					array_push($arr_ts_vector_fts, "'" . $str . "' || ' ' || regexp_replace('" . $str . "', E'(\\\\W)+', ' ', 'g')");
					}
				if ($guest_flag)
					{
					array_push($arr_ts_vector_ftg, "'" . $str . "' || ' ' || regexp_replace('" . $str . "', E'(\\\\W)+', ' ', 'g')");
					}
                }		
			
			$str_ts_vector_fts = !empty($arr_ts_vector_fts) ? implode(" || ' ' || ", $arr_ts_vector_fts) : "''";
			$str_ts_vector_ftg = !empty($arr_ts_vector_ftg) ? implode(" || ' ' || ", $arr_ts_vector_ftg) : "''";
			
			$insert_clause .= ", fts, ftg, secure ";
			$select_clause .= ", to_tsvector(" . $str_ts_vector_fts . ") as fts, to_tsvector(" . $str_ts_vector_ftg . ") as ftg, ";
            $select_clause .= "(CASE WHEN (SELECT secure FROM data_table WHERE id IN (" . $post_key . ")) = 1 THEN 1 ELSE 0 END) as secure";
            $select_where_exists = "SELECT 1";
            $select_where_not = "SELECT 1 WHERE 1 = 0";
            //key exists must check for duplicate value
            if (!empty($unique_key))
                {
                $unique_value = isset($xml_state->$unique_key) ? (string)$xml_state->$unique_key : ""; 
                //key, will not insert on empty value, key must be populated
                if (!empty($unique_value))
                    {
                    $select_where_not = "SELECT 1 FROM data_table WHERE row_type IN (" . $row_type . ") AND lower(" . $unique_key . ") IN (lower('" . $unique_value . "'))";
                    }
				else
					{
					$select_where_not = "SELECT 1";	
					}
                }            
             //parent row has been deleted, multiuser situation, check on insert
            if ($post_key > 0)
                {
                $select_where_exists = "SELECT 1 FROM data_table WHERE archive = 0 AND id IN (" . $post_key . ")";
                }
				
			$return_primary = $xml_column['primary'];           
            $query = "INSERT INTO data_table (" . $insert_clause	. ") SELECT " . $select_clause . " WHERE NOT EXISTS (" . $select_where_not . ") AND EXISTS (" . $select_where_exists . ") RETURNING id, " . $return_primary . " as primary;";
            //echo "<p>" . $query . "</p>";
            $result = $main->query($con, $query);
			
            if (pg_affected_rows($result) == 1)
                {
				$row = pg_fetch_array($result);
                array_push($arr_message, "Record Succesfully Entered.");
                //dispose of $xml_state and update state
                $xml_state = simplexml_load_string("<hold></hold>");         
                $main->update($array_state, $module, $xml_state);
				
				//to drill down, $inserted_row_type become $row_join or the parent row when used
				$inserted_row_type = $row_type;
				$inserted_id = $row['id'];
				$inserted_primary = $row['primary'];
				
				//$post_key is key1, the parent key/id
				$parent_id = $post_key;
                }
            else //bad insert
                {
				//check for key problem
				$result = $main->query($con, $select_where_not);
				if (pg_num_rows($result) == 1)
					{
					//retain state values
					array_push($arr_message, "Error: Record not updated. Duplicate or empty key value in input form on column \"" . $xml_column->$unique_key . "\"."); 
					}
				else
					{
					//dispose of post vars				
					$row_join = -1;
					$post_key = -1;
					$row_type = 0;
					//dispose of $xml_state and update state
					$xml_state = simplexml_load_string("<hold></hold>");            
					$main->update($array_state, $module, $xml_state);
	
					array_push($arr_message, "Error: Record not inserted. Parent record archived or underlying data change possible.");    
					}
				}                  		
            } //else insert row
        } //if no error message
    }// end if submit/enter data

    
/* PARENT ROW */
// $post_key > 0 only on edit or insert with parent
$parent_row_type = 0;
if ($post_key > 0)
    {
    $parent_row_type = (int)$xml_layout['parent'];
    $parent_layout = "l" . str_pad($parent_row_type,2,"0",STR_PAD_LEFT);
	$xml_column_parent = $xml_columns->$parent_layout;
    $primary_parent = isset($xml_columns->$parent_layout) ? $xml_column_parent['primary'] : "c01";
	 
	 //edit, must join to parent
    if ($row_type == $row_join)
        {       
		$primary_child = isset($xml_column) ? $xml_column['primary'] : "c01";		
		
        $query = "SELECT T2.id, T2." . $primary_parent . " as parent, T1." . $primary_child . " as child, T2.archive FROM data_table T1 LEFT JOIN data_table T2 " .
                 "ON T2.id = T1.key1 WHERE T1.id = " . $post_key . ";";
        $result = $main->query($con, $query);
		//find parent information after edit
		if (pg_num_rows($result) == 1)
			{
			$row = pg_fetch_array($result);
			$parent_primary = isset($row['parent']) ? $row['parent'] : "";
			$archive_flag = ($row['archive'] == 0) ? "" : "*";
			//used to find the parent of sibling, becomes row_join, see edit successful
			$inserted_row_type = $row_type;
			$inserted_id = $post_key;
			$inserted_primary = $row['child'];

			$parent_id = $row['id'];			
			$link_id = $parent_id; 
			}
        }
    else
		//input, have parent id
        {        
        $query = "SELECT *, " . $primary_parent . " as parent FROM data_table WHERE id = " . $post_key . " AND row_type = " . $parent_row_type . ";";    
        //echo "<p>" . $query . "</p>";
		$result = $main->query($con, $query);
		$cnt_rows = pg_num_rows($result);
		if ($cnt_rows == 1)
			{
			$row = pg_fetch_array($result);
			$parent_primary = isset($row['parent']) ? $row['parent'] : "";
			$link_id = $post_key;
			if (isset($row['archive']))
				{
				$archive_flag = ($row['archive'] == 0) ? "" : "*";
				}
			}
			
		/* AUTOFILL */
        //autofill area, on post_key set or after insert with parent
        if ($cnt_rows == 1)
            {
            //loop through each node
            foreach ($xml_column_parent->children() as $child1)
                {
                $col1 = $child1->getName();
                //xpath each node for existance
				$path = "//" . $layout . "/*[.=\"". (string)$child1 . "\"]";
                $node = $main->search_xml($xml_column, $path);
                //if found
                if (!empty($node[0]))
                    {
                    $col2 = $node[0]->getName();
                    //if autofill column is empty
                    if (empty($xml_state->$col2))
                        {
                        $xml_state->$col2 = $row[$col1];   
                        }
                    }
                }
            }
            /* END AUTOFILL */
        }
    }
/* END PARENT ROW */ 
    

/* BEGIN REQUIRED FORM */
$main->echo_form_begin();
$main->echo_module_vars($module);

//echos input select object if row_type exists


if ($row_type >= 0):

    //has parent only one select value
	$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>"Submit Record");
	$main->echo_button("top_submit", $params);
	$params = array("class"=>"spaced","number"=>2,"target"=>$module, "passthis"=>true, "label"=>"Reset Form");
	$main->echo_button("top_reset", $params);

    if ($parent_row_type > 0)
        {
        echo "<select name = \"row_type\" class = \"spaced\" onchange=\"bb_reload_on_layout()\">";
        echo "<option value=\"" . $row_type . "\" selected>" . $xml_layout['plural'] . "&nbsp;</option>";
        echo "</select>";
        }
	//no parent, all possible top level records
    else
        {
		$arr_select = $xml_layouts->xpath("//*[@parent=0]");
		//has top level records
		if (count($arr_select) > 0)
			{
			//on reset, $xml_column already set if changing top level from select
			if ($row_type == 0)
				{
				$layout = $arr_select[0]->getName();
				$xml_column = $xml_columns->$layout;
				}
			//output
			echo "<select name = \"row_type\" class = \"spaced\" onchange=\"bb_reload_on_layout()\">";
			foreach ($arr_select as $child)
				{
				$i = (int)substr($child->getName(),1);
				echo "<option value=\"" . $i . "\" " . ($i == $row_type ? "selected" : "") . ">" . $child['plural'] . "&nbsp;</option>";
				}
			echo "</select>";
			}
		//no top level records
		else
			{
			unset($xml_column);
			}
        }
	echo "<div class=\"clear\"></div>";    

if (!empty($xml_column))
	{
	//edit or insert mode and primaryt parent column		
	$edit_or_insert = ($row_type == $row_join) ? "Edit Mode" : "Insert Mode";
	$parent_string = empty($parent_primary) ? "" : " - Parent: <button class=\"link colored\" onclick=\"bb_links.input(" . $link_id . "," . $parent_row_type . "," . $parent_row_type . ",'bb_input'); return false;\">" . $parent_primary . "</button>";
	echo "<p class=\"bold spaced\">" . $edit_or_insert . $parent_string . "</p>";
	}

//add children links
if (!empty($inserted_id) && ($inserted_row_type > 0))
	{
	if ($main->check_child($inserted_row_type, $xml_layouts))
		{
		echo "<p class=\"spaced bold\">Add Child Record - Parent: <span class=\"colored\">" . $inserted_primary . "</span> - ";
		$main->drill_links($inserted_id, $inserted_row_type, $xml_layouts, "bb_input", "Add");
		echo "</p>";
		}
	}
	
//add sibling links
if (!empty($parent_id) && ($parent_row_type > 0))
	{
	if ($main->check_child($parent_row_type, $xml_layouts))
		{
		echo "<p class=\"spaced bold\">Add Sibling Record - Parent: <span class=\"colored\">" . $parent_primary . "</span> - ";
		$main->drill_links($parent_id, $parent_row_type, $xml_layouts, "bb_input", "Add");
		echo "</p>";
		}
	}	

echo "<div class=\"spaced\" id=\"input_message\">";
$main->echo_messages($arr_message);
echo "</div>";
/* END MESSAGES */

/* POPULATE INPUT FIELDS */
//check if empty, could be either empty or children not populated
//this is dependent on admin module "Set Column Names"
if (!empty($xml_column))
	{
	$textarea_rows = (int)$xml_column['count'] > 4 ? (int)$xml_column['count'] : 4;
	foreach($xml_column->children() as $child)
		{
		$col = $child->getName();
		$value = (isset($xml_state->$col)) ? (string)$xml_state->$col : "";
		$error = (isset($obj_errors->$col)) ? (string)$obj_errors->$col : "";
		if (isset($xml_dropdowns->$layout->$col))
				{
				$path = $layout . "/" . $col . "/value";
				echo "<div class=\"clear\"><label class = \"spaced padded floatleft right overflow medium shaded\">" . htmlentities($child) . ": </label>";
				echo "<select class = \"spaced\" name = \"" . $col . "\" onFocus=\"remove_message(); return false;\">";
				$arr_dropdown = $xml_dropdowns->xpath($path);
				foreach ($arr_dropdown as $dropdown)
						{                            
						echo "<option value=\"" . htmlentities($dropdown) . "\" " . ((strtolower($value) == strtolower($dropdown)) ? "selected" : "" ) . ">" . htmlentities($dropdown) . "&nbsp;</option>";
						}
				echo "</select><label class=\"error\">" . $error . "</label></div>";
				}
		elseif (in_array($col,$arr_notes))
				{
				echo "<div class = \"clear\"><label class = \"spaced padded floatleft left overflow medium shaded\">" . htmlentities($child) . ": </label>";
				echo "<div class=\"clear\"></div>";
				echo "<textarea class=\"spaced notearea\" maxlength=\"65536\" name=\"" . $col . "\" onFocus=\"remove_message(); return false;\">" . $value . "</textarea></div>";				
				}				
		else
				{			
				echo "<div class=\"clear\"><label class = \"spaced padded floatleft right overflow medium shaded\">" . htmlentities($child) . ": </label>";
				echo "<input class = \"spaced textbox\" maxlength=\"255\" name=\"" . $col . "\" type=\"text\" value = \""  . htmlentities($value) .  "\" onFocus=\"remove_message(); return false;\" />";
				echo "<label class=\"error\">" . $error . "</label></div>";
				}		
		}
	//submit button
	echo "<div class=\"clear\"></div>";
	//check if children not populated
	//this is dependent on admin module "Set Column Names"    
	if (!empty($xml_column))
		{
		$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>"Submit Record");
		$main->echo_button("bottom_submit", $params);
		$params = array("class"=>"spaced","number"=>2,"target"=>$module, "passthis"=>true, "label"=>"Reset Form");
		$main->echo_button("bottom_reset", $params);
		
		echo "<div class=\"clear\"></div>";
		echo "<br>";
		//load textarea
		echo "<div align=\"left\">";
		echo "<textarea class=\"spaced\" name = \"input_textarea\" cols=\"80\" rows=\"" . ($textarea_rows) ."\"></textarea>";
		echo "<div class=\"clear\"></div>";
		$params = array("class"=>"spaced","number"=>3,"target"=>$module, "passthis"=>true, "label"=>"Load Data To Form");
		$main->echo_button("load_textarea", $params);
		echo "</div>";
		}    
	}
/* END POPULATE INPUT FIELDS */    

//hidden vars
//$row_type is contained in the layout dropdown
echo "<input type=\"hidden\"  name=\"post_key\" value = \"" . $post_key . "\">";
echo "<input type=\"hidden\"  name=\"row_join\" value = \"" . $row_join . "\">";

//for Drill or quick edit links
$main->echo_common_vars();

//state variables
$main->echo_state($array_state);
$main->echo_form_end();

endif;
/* END FORM */ 
?>