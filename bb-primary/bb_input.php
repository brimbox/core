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
//find default row_type, $arr_layouts must have one layout set
$arr_layouts = $main->get_json($con, "bb_layout_names");
$default_row_type = $main->get_default_layout($arr_layouts);
//get columns
$arr_columns = $main->get_json($con, "bb_column_names");
//get dropdown values while were at it
$arr_dropdowns = $main->get_json($con, "bb_dropdowns");

$arr_notes = array("49","50");
$textarea_rows = 4; //minimum
$arr_message = array(); //message pile
$unique = "";
$message = ""; //return message

/*INPUT STATE AND POSTBACK */  
$main->retrieve($con, $array_state);
$arr_state = $main->load($module, $array_state);

//include file for postback routines
//this include handles input state and contains class bb_input_extra
include("bb_input_extra.php");

//constuct with default row type
$input_class = new bb_input_extra($arr_columns, $arr_state, $main, $con, $module, $default_row_type);
//process different input options
if (!empty($_POST['bb_row_type'])) //row_type set in global link, should be positive
    {
    $arr_input_class = $input_class->global_row_type();   
    }
elseif ($main->button(1)) //postback
    {
    $arr_input_class = $input_class->input_postback(); 
    }
elseif ($main->button(2)) //clear form
    {
    $arr_input_class = $input_class->clear_form();
    }
elseif ($main->button(3))
    {
    $arr_input_class = $input_class->load_textarea();
    }
elseif ($main->button(4))
    {
    $arr_input_class = $input_class->combo_change();    
    }
else
    {
    $arr_input_class = $input_class->load_from_state();    
    }
//list variables from return    
list($row_type, $row_join, $post_key, $arr_state) = $arr_input_class;

//process if posted from queue page "Add To Input" button
if ($main->button(2,'bb_queue'))
    {
    //constuct with row type from state
    $var_subject = $main->post('subject','bb_queue');
    $input_class = new bb_input_queue($arr_layouts, $arr_columns, $arr_state, $main, $con, $module, $row_type, $row_join, $post_key, $var_subject);
    
    if (substr($var_subject,0,12) == "Record Add: " && preg_match("/^[A-Z][-][A-Z]\d+/", substr($var_subject,12)))
        {
        $arr_input_class = $input_class->queue_record_add();   
        }
    elseif (substr($var_subject,0,13) == "Record Edit: " && preg_match("/^[A-Z]\d+/", substr($var_subject,13)))
        {
        $arr_input_class = $input_class->queue_record_edit();   
        }
    elseif (substr($var_subject,0,12) == "Record New: " && preg_match("/^[A-Z]$/", substr($var_subject,12)))    
        {
        $arr_input_class = $input_class->queue_record_new();
        }
    else
        {
        $arr_input_class = $input_class->queue_record_default();
        }
    //list variables from return 
    list($row_type, $row_join, $post_key, $arr_state) = $arr_input_class;
    }     
    
//$arr_layout will be needed
$arr_layout = $arr_layouts[$row_type];
$arr_column = $arr_columns[$row_type];
$arr_column_reduced = $main->filter_keys($arr_column);

//update state
$main->update($array_state, $module, $arr_state);
/*END INPUT STATE AND POSTBACK */
	
/* SUBMIT TO DATABASE */
//validation error arr_error_msg
if ($main->button(1))
	{
    $arr_errors = array(); //empty array
	foreach($arr_column_reduced as $key => $value)
        {
        /* START VALIDATION */
		$type = $value['type']; //validation type 
        $required_flag = $value['required'] == 1 ? true : false; //required boolean       
        $col = $main->pad("c", $key);
            
        //textarea no validation
		if (in_array($key,$arr_notes))
			{
            //long column
			$main->set($col, $arr_state, $main->custom_trim_string($main->post($col,$module),65536, false));
			}                
		else
			{
            //regular column
            $field = $main->custom_trim_string($main->post($col,$module),255);
            $return_required = false;
			$return_validate = false;
            //required field  
            if ($required_flag) //false=not required, true=required
                {
                $return_required = $main->validate_required($field, true);
                if (!is_bool($return_required)) 
                    {
                    $arr_errors[$col] = $return_required;
                    }
                }            
            //validate, field has data, trimmed already, will skip if blank
            if (!$main->blank($field)) 
                {
				//value is passed a reference and may change in function if formatted
                $return_validate = $main->validate_logic($type, $field, true);
                if (!is_bool($return_validate))
                    {
                    $arr_errors[$col] = $return_validate;
                    }
                }
			$main->set($col, $arr_state, $field);
			}
		}
        /* END VALIDATION */
        
    /* INSERT OR UPDATE ROW */       
    if (empty($arr_errors)) //no errors
        {
        //produce empty form since we are going to load the data
        $owner = $main->custom_trim_string($_SESSION['email'],255); //used in both if and else
        // update preexisting row
        if ($row_type == $row_join) 
            {
            $update_clause = "updater_name = '" . pg_escape_string($owner) . "'";
			$arr_ts_vector_fts = array();
			$arr_ts_vector_ftg = array();
            foreach($arr_column_reduced as $key => $value)
				{
				$col = $main->pad("c", $key);
				$str = pg_escape_string((string)$arr_state[$col]);
				$update_clause .= "," . $col . " =  '" . $str . "'";;
				//prepare fts and ftg
				$search_flag = ($value['search'] == 1) ? true : false;
				//guest flag
				if (empty($array_guest_index))
					{
					$guest_flag = (($value['search'] == 1) && ($value['secure'] == 0)) ? true : false;
					}
				else
					{
					$guest_flag = (($value['search'] == 1) && in_array($value['secure'], $array_guest_index)) ? true : false;						
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
            if (isset($arr_column['layout']['unique'])) //no key = unset
                {
                //get the vlaue to be checked
                $unique_key = $arr_column['layout']['unique'];
                $unique_column = $main->pad("c", $unique_key);
                $unique_value = isset($arr_state[$unique_column]) ? $arr_state[$unique_column] : "";
                if (!$main->blank($unique_value))
                    {
                    $select_where_not = "SELECT 1 FROM data_table WHERE row_type IN (" . $row_type . ") AND id NOT IN (" . $post_key . ") AND lower(" . $unique_column . ") IN (lower('" . $unique_value . "'))";                        
                    }
				else
					{
					$select_where_not = "SELECT 1";	
					}
				}
            //will not allow a blank if $unique is set
			$return_primary = $main->pad("c", $arr_column['layout']['primary']);
            $query = "UPDATE data_table SET " . $update_clause . ", fts = to_tsvector(" . $str_ts_vector_fts . "), ftg = to_tsvector(" . $str_ts_vector_ftg . ") " .
				     "WHERE id IN (" . $post_key . ") AND archive = 0 AND NOT EXISTS (" . $select_where_not . ") RETURNING id, " . $return_primary . " as primary;";                 
            $result = $main->query($con, $query);
            if (pg_affected_rows($result) == 1)
                {
				$row = pg_fetch_array($result);
                array_push($arr_message, "Record Succesfully Updated.");
                $main->update($array_state, $module, $arr_state);												
				//will find $parent_id, $inserted_id when finding parent
                }
            else //bad edit
                {
				$result = $main->query($con, $select_where_not);
				if (pg_num_rows($result) == 1)
					{
					//retain state values
					array_push($arr_message, "Error: Record not updated. Duplicate or empty key value in input form on column \"" . $arr_column_reduced[$unique_key]['name'] . "\"."); 
					}
				else
					{
					//dispose of post vars
					$row_join = -1;
					$post_key = -1;
					$row_type = 0;
					//dispose of $arr_state and update state
					$arr_state = array();            
					$main->update($array_state, $module, $arr_state);
	
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
            foreach($arr_column_reduced as $key => $value)
                {
                $col = $main->pad("c", $key);
                $str = pg_escape_string($arr_state[$col]);
                $insert_clause .= "," . $col;
                $select_clause .= ", '" . $str . "'";
                //search flag
				$search_flag = ($value['search'] == 1) ? true : false;
				//guest flag
				if (empty($array_guest_index))
					{
					$guest_flag = (($value['search'] == 1) && ($value['secure'] == 0)) ? true : false;
					}
				else
					{
					$guest_flag = (($value['search'] == 1) && in_array($value['secure'], $array_guest_index)) ? true : false;						
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
			
			$insert_clause .= ", fts, ftg, secure, archive ";
			$select_clause .= ", to_tsvector(" . $str_ts_vector_fts . ") as fts, to_tsvector(" . $str_ts_vector_ftg . ") as ftg, ";
            $select_clause .= "CASE WHEN (SELECT  coalesce(secure,0) FROM data_table WHERE id IN (" . $post_key . ")) > 0 THEN (SELECT secure FROM data_table WHERE id IN (" . $post_key . ")) ELSE 0 END as secure, "; 
            $select_clause .= "CASE WHEN (SELECT  coalesce(archive,0) FROM data_table WHERE id IN (" . $post_key . ")) > 0 THEN (SELECT archive FROM data_table WHERE id IN (" . $post_key . ")) ELSE 0 END as archive ";
            $select_where_exists = "SELECT 1";
            $select_where_not = "SELECT 1 WHERE 1 = 0";
            //key exists must check for duplicate value
            if (isset($arr_column['layout']['unique'])) //no key = unset
                {
                //get the vlaue to be checked
                $unique_key = $arr_column['layout']['unique'];
                $unique_column = $main->pad("c", $unique_key);
                $unique_value = isset($arr_state[$unique_column]) ? (string)$arr_state[$unique_column] : ""; 
                //key, will not insert on empty value, key must be populated
                if (!$main->blank($unique_value))
                    {
                    $select_where_not = "SELECT 1 FROM data_table WHERE row_type IN (" . $row_type . ") AND lower(" . $unique_column . ") IN (lower('" . $unique_value . "'))";
                    }
				else
					{
					$select_where_not = "SELECT 1";	
					}
                }
             //if parent row has been deleted, multiuser situation, check on insert
            if ($post_key > 0)
                {
                $select_where_exists = "SELECT 1 FROM data_table WHERE archive = 0 AND id IN (" . $post_key . ")";
                }
				
			$return_primary = $main->pad("c", $arr_column['layout']['primary']);
            $query = "INSERT INTO data_table (" . $insert_clause	. ") SELECT " . $select_clause . " WHERE NOT EXISTS (" . $select_where_not . ") AND EXISTS (" . $select_where_exists . ") RETURNING id, " . $return_primary . " as primary;";
            //echo "<p>" . $query . "</p>";
            $result = $main->query($con, $query);
			
            if (pg_affected_rows($result) == 1)
                {
				$row = pg_fetch_array($result);
                array_push($arr_message, "Record Succesfully Inserted.");
                //dispose of $arr_state
                $arr_state = array();         
                $main->update($array_state, $module, $arr_state);
				
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
					array_push($arr_message, "Error: Record not updated. Duplicate or empty key value in input form on column \"" . $arr_column_reduced[$unique_key]['name'] . "\"."); 
					}
				else
					{
					//dispose of post vars				
					$row_join = -1;
					$post_key = -1;
					$row_type = 0;
					//dispose of $arr_state and update state
					$arr_state = array();            
					$main->update($array_state, $module, $arr_state);
	
					array_push($arr_message, "Error: Record not inserted. Parent record archived or underlying data change possible.");    
					}
				}                  		
            } //else insert row
        } //if no error message
    }// end if submit/enter data

    
/* PARENT ROW */
// $post_key > 0 only on edit or insert with parent
if ($post_key > 0)
    {
    $parent_row_type = $arr_layout['parent'];
    $primary_parent = isset($arr_columns[$parent_row_type]['primary']) ? $main->pad("c", $arr_columns[$parent_row_type]['primary']) : "c01";
	 
	 //edit, must join to parent
    if ($row_type == $row_join)
        {       
		$primary_child = isset($arr_column['layout']['primary']) ? $main->pad("c", $arr_column['layout']['primary']) : "c01";		
		
        $query = "SELECT T2.id, T2." . $primary_parent . " as parent, T1." . $primary_child . " as child, T2.archive FROM data_table T1 LEFT JOIN data_table T2 " .
                 "ON T2.id = T1.key1 WHERE T1.id = " . $post_key . ";";
        $result = $main->query($con, $query);
		//find parent information after edit
		if (pg_num_rows($result) == 1)
			{
			$row = pg_fetch_array($result);
			$parent_primary = isset($row['parent']) ? $row['parent'] : "";
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
                
            /* AUTOFILL HOOK */
            if ($arr_hooks = $main->hook("bb_input_autofill"))
                {
                foreach ($arr_hooks as $arr_hook)
                    {
                    $args_hook = array();
                    foreach ($arr_hook[1] as &$value)
                        {
                        if (substr($value,0,1) == "&") $args_hook[] = &${substr($value,1)}; else  $args_hook[] = ${$value};	
                        }
                    call_user_func_array($arr_hook[0], $args_hook);
                    }
                }
            /* END AUOTFILL HOOK */
			}			
        }
    }
/* END PARENT ROW */ 
    

/* BEGIN REQUIRED FORM */
$main->echo_form_begin();
$main->echo_module_vars();

//echos input select object if row_type exists
if ($row_type > 0):

    //has parent only one select value
    if ($row_type == $row_join)
        {
        $update_or_insert = "Update Record";
        $edit_or_insert = "Edit Mode";
        }
    else
        {
        $update_or_insert = "Insert Record";
        $edit_or_insert = "Insert Mode";
        }
        
	$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>$update_or_insert);
	$main->echo_button("top_submit", $params);
	$params = array("class"=>"spaced","number"=>2,"target"=>$module, "passthis"=>true, "label"=>"Reset Form");
	$main->echo_button("top_reset", $params);
    
    //empty works no zeros
    if (!empty($parent_row_type))
        {
        echo "<select name = \"row_type\" class = \"spaced\" onchange=\"bb_reload_on_layout()\">";
        echo "<option value=\"" . $row_type . "\" selected>" . $arr_layout['plural'] . "&nbsp;</option>";
        echo "</select>";
        }
	//no parent, all possible top level records
    else
        {
        //get top level records
        foreach($arr_layouts as $key => $value)
            {
            if ($value['parent'] == 0)
                {
                $arr_select[$key] = $value;
                }
            }
		//has top level records
		if (count($arr_select) > 0)
			{
			//on reset, $arr_column already set if changing top level from select
			echo "<select name = \"row_type\" class = \"spaced\" onchange=\"bb_reload_on_layout()\">";
			foreach ($arr_select as $key => $value)
				{
				echo "<option value=\"" . $key . "\" " . ($key == $row_type ? "selected" : "") . ">" . $value['plural'] . "&nbsp;</option>";
				}
			echo "</select>";
			}
		//no top level records, not common
		else
			{
			unset($arr_column);
			}
        }
	echo "<div class=\"clear\"></div>";    

if (!empty($arr_column))
	{
	//edit or insert mode and primaryt parent column		
	$parent_string = $main->blank($parent_primary) ? "" : " - Parent: <button class=\"link colored\" onclick=\"bb_links.input(" . $link_id . "," . $parent_row_type . "," . $parent_row_type . ",'bb_input'); return false;\">" . $parent_primary . "</button>";
	echo "<p class=\"bold spaced\">" . $edit_or_insert . $parent_string . "</p>";
	}

//add children links, empty works no zeros
if (!empty($inserted_id) && !empty($inserted_row_type))
	{
	if ($main->check_child($inserted_row_type, $arr_layouts))
		{
		echo "<p class=\"spaced bold\">Add Child Record - Parent: <span class=\"colored\">" . $inserted_primary . "</span> - ";
		$main->drill_links($inserted_id, $inserted_row_type, $arr_layouts, "bb_input", "Add");
		echo "</p>";
		}
	}
	
//add sibling links, empty works no zeros
if (!empty($parent_id) && !empty($parent_row_type))
	{
	if ($main->check_child($parent_row_type, $arr_layouts))
		{
		echo "<p class=\"spaced bold\">Add Sibling Record - Parent: <span class=\"colored\">" . $parent_primary . "</span> - ";
		$main->drill_links($parent_id, $parent_row_type, $arr_layouts, "bb_input", "Add");
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
if (!empty($arr_column))
	{
	$textarea_rows = (int)$arr_column['layout']['count'] > 4 ? (int)$arr_column['layout']['count'] : 4;
	foreach($arr_column_reduced as $key => $value)
		{
        $col = $main->pad("c", $key);
		$input = (isset($arr_state[$col])) ? $arr_state[$col] : "";
		$error = (isset($arr_errors[$col])) ? $arr_errors[$col] : "";
		if (isset($arr_dropdowns[$row_type][$key]))
				{
				echo "<div class=\"clear\"><label class = \"spaced padded floatleft right overflow medium shaded\">" . htmlentities($value['name']) . ": </label>";
				echo "<select class = \"spaced\" name = \"" . $col . "\" onFocus=\"remove_message(); return false;\">";
				$arr_dropdown = $arr_dropdowns[$row_type][$key];
				foreach ($arr_dropdown as $dropdown)
						{                            
						echo "<option value=\"" . htmlentities($dropdown) . "\" " . ((strtolower($input) == strtolower($dropdown)) ? "selected" : "" ) . ">" . htmlentities($dropdown) . "&nbsp;</option>";
						}
				echo "</select><label class=\"error\">" . $error . "</label></div>";
				}
		elseif (in_array($key, $arr_notes))
				{
				echo "<div class = \"clear\"><label class = \"spaced padded floatleft left overflow medium shaded\">" . htmlentities($value['name']) . ": </label>";
				echo "<div class=\"clear\"></div>";
				echo "<textarea class=\"spaced notearea\" maxlength=\"65536\" name=\"" . $col . "\" onFocus=\"remove_message(); return false;\">" . $input . "</textarea></div>";				
				}				
		else
				{			
				echo "<div class=\"clear\"><label class = \"spaced padded floatleft right overflow medium shaded\">" . htmlentities($value['name']) . ": </label>";
				echo "<input class = \"spaced textbox\" maxlength=\"255\" name=\"" . $col . "\" type=\"text\" value = \""  . htmlentities($input) .  "\" onFocus=\"remove_message(); return false;\" />";
				echo "<label class=\"error\">" . $error . "</label></div>";
				}		
		}
	//submit button
	echo "<div class=\"clear\"></div>";
	//check if children not populated
	//this is dependent on admin module "Set Column Names"    
	if (!empty($arr_column))
		{
		$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>$update_or_insert);
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