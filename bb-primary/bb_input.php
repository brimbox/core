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
/* MODULE JAVASCRIPT */
function bb_remove_message()
    {
    document.getElementById('input_message').innerHTML = "";
	return false;
    }
//used in hook top_level_records
function bb_reload()
    {
    var frmobj = document.forms["bb_form"];
    //set a button of 4 for postback
    bb_submit_form(3);
	return false;
    }
</script>
<?php
/* DEAL WITH CONSTANTS */
$input_insert_log = $main->on_constant('BB_INPUT_INSERT_LOG');
$input_update_log = $main->on_constant('BB_INPUT_UPDATE_LOG');
$input_secure_post = $main->on_constant('BB_INPUT_SECURE_POST');
$input_archive_post = $main->on_constant('BB_INPUT_ARCHIVE_POST');

$maxinput = $main->get_constant('BB_STANDARD_LENGTH', 255);
$maxnote = $main->get_constant('BB_NOTE_LENGTH', 65536);
/* END DEAL WITH CONSTANTS */
?>
<?php

/* INITIALIZE */
//find default row_type, $arr_layouts must have one layout set
$arr_layouts = $main->get_json($con, "bb_layout_names");
//get columns
$arr_columns = $main->get_json($con, "bb_column_names");
//get dropdown values while were at it
$arr_dropdowns = $main->get_json($con, "bb_dropdowns");
//get header for guest index
$arr_header = $main->get_json($con, "bb_interface_enable");

$arr_relate = array(41,42,43,44,45,46);
$arr_file = array(47);
$arr_reserved = array(48);
$arr_notes = array(49,50);

$textarea_rows = 4; //minimum
$arr_message = array(); //message pile
$message = ""; //blank return message
/* END INITIALIZE*/

/* INPUT STATE AND POSTBACK */  
$main->retrieve($con, $array_state);
$arr_state = $main->load($module, $array_state);
//hook for postback routines, will include bb_input_extra
$main->hook("postback_area", true);
//returns $post_key, $row_type, $row_join, and $arr_state
//update state
$main->update($array_state, $module, $arr_state);
/*END INPUT STATE AND POSTBACK */

//hook will initialize $post_key, $row_type, $row_join, and $arr_state
//so these are basically global
$arr_layouts_reduced = $main->filter_keys($arr_layouts);
$arr_layout = $arr_layouts_reduced[$row_type];
$arr_column = $main->filter_init($arr_columns[$row_type]);
$arr_column_reduced = $main->filter_keys($arr_column);

/* Standard Buttons */
//Button 1 - update or insert (see below)
//Button 2 - reset form (in postback hook)
//Button 3 - combo change (in javascript, see above)
//Button 4 - autoload form (see below)
//Button 5 - textarea load (in postback hook)

/* SUBMIT TO DATABASE */
//validation error arr_errors
if ($main->button(1))
	{
    $arr_errors = array(); //empty array
	foreach($arr_column_reduced as $key => $value)
        {
        /* START VALIDATION */
		$type = $value['type']; //validation type 
        $required_flag = $value['required'] == 1 ? true : false; //required boolean       
        $col = $main->pad("c", $key);
            
        //all validated
        $field = $arr_state[$col];
        $return_required = $return_validate = false;
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
            $return_validate = $main->validate_logic($con, $type, $field, true);
            if (!is_bool($return_validate))
                {
                $arr_errors[$col] = $return_validate;
                }
            }
        
        //validation hook loop, by record type and field, that is the discrete level of this loop
        //you will need to pass $arr_errors and $col as variables and update if there is a validation error
        //if you need to validate one field using another do it here
        $filtername = "validation" . "_" . $main->make_html_id($row_type, $key);
        $field = $main->hook($filtername, $field, true);
        //set the column with validated and formatted field
        $main->set($col, $arr_state, $field);
		}
        /* END VALIDATION */
        
    /* INSERT OR UPDATE ROW */       
    if (empty($arr_errors)) //no errors
        {
        //produce empty form since we are going to load the data
        $owner = $main->purge_chars($username); //used in both if and else
        $unique_key = isset($arr_column['layout']['unique']) ? $arr_column['layout']['unique'] : 0;
        
        $arr_ts_vector_fts = array();
        $arr_ts_vector_ftg = array();
        $arr_select_where = array();
       
        if ($row_type == $row_join)  // update preexisting row
            {
            $update_clause = "updater_name = '" . pg_escape_string($owner) . "'";
            foreach($arr_column_reduced as $key => $value)
				{
				$col = $main->pad("c", $key);
				$str = $arr_state[$col];
				$update_clause .= "," . $col . " =  '" . pg_escape_string($str) . "'";
				//prepare fts and ftg
				$search_flag = ($value['search'] == 1) ? true : false;				
                //populate guest index array, can be reassigned in module Interface Enable
                $arr_guest_index = $arr_header['guest_index']['value'];
                
                //local function call, see top of module
                $main->full_text($arr_ts_vector_fts, $arr_ts_vector_ftg, $value, $str, $arr_guest_index);
                
                //local function call, see top of module
                if (in_array($key, array(41,42,43,44,45,46)))
                    {
                    $main->process_related($arr_select_where, $arr_layouts_reduced, $value, $str);    
                    }
                } //end column loop
                
            //explode full text update
			$str_ts_vector_fts = !empty($arr_ts_vector_fts) ? implode(" || ' ' || ", $arr_ts_vector_fts) : "''";
			$str_ts_vector_ftg = !empty($arr_ts_vector_ftg) ? implode(" || ' ' || ", $arr_ts_vector_ftg) : "''";
            
            //explode relate check array
            $select_where = empty($arr_select_where) ? "SELECT 1" : "SELECT 1 FROM data_table WHERE (" . implode(" OR ", $arr_select_where) . ") HAVING count(*) = " . (int)count($arr_select_where);
            
            //will detect secure and archive form values
            //secure can be updated if there is a form value being posted and constant is set
            if ($input_secure_post) $secure_clause = $main->check('secure', $module) ? ", secure = " . $secure . " "  : "";
            else $secure_clause = "";
            //archive is wired in for update, not generally used for standard interface
            if ($input_archive_post) $archive_clause = $main->check('archive', $module) ? ", archive = " . $archive . " "  : "";
            else $archive_clause = "";
            
            //key exists must check for duplicate value
            //$select_where_not & $unique_key passed and created as reference
            $unique_value = isset($arr_state[$main->pad("c", $unique_key)]) ? $arr_state[$main->pad("c", $unique_key)] : "";
            $main->unique_key(true, $select_where_not, $unique_key, $unique_value, $row_type, $post_key);
            
			$return_primary = isset($arr_column['layout']['primary']) ? $main->pad("c", $arr_column['layout']['primary']) : "c01";
            $query = "UPDATE data_table SET " . $update_clause . ", fts = to_tsvector(" . $str_ts_vector_fts . "), ftg = to_tsvector(" . $str_ts_vector_ftg . ") " . $secure_clause . " " .
				     "WHERE id IN (" . $post_key . ") AND NOT EXISTS (" . $select_where_not . ") AND EXISTS (" . $select_where . ") RETURNING id, " . $return_primary . " as primary;";                 
            $result = $main->query($con, $query);
            //echo "<p>" . $query . "</p>";
            
            //good edit
            if (pg_affected_rows($result) == 1)
                {
				$row = pg_fetch_array($result);
                //process large object
                if (isset($arr_column_reduced[47]))
                    {
                    if (is_uploaded_file($_FILES[$main->name('c47', $module)]["tmp_name"]) && !$main->blank($_FILES[$main->name('c47', $module)]["name"]))
                        {
                        pg_query($con, "BEGIN");
                        /* IMPORTANT */
                        //if there's a better way I'd do it, superusers control large objects since 9.0
                        //on standard hosting ignoring a delete warning on object not found is the best way   
                        //web users don't have access to pg_largeobjects only superusers do
                        //large objects are owned by their creator, in this case the web user
                        //only superusers can use GRANT on large object since Postgres 9.
                        @pg_lo_unlink($con, $row['id']);
                        pg_query($con, "END");
                        pg_query($con, "BEGIN");
                        pg_lo_import($con, $_FILES[$main->name('c47', $module)]["tmp_name"], $row['id']);
                        pg_query($con, "END");
                        }
                    //Remove existing large object
                    $remove = isset($arr_state['remove']) ? $arr_state['remove'] : 0; 
                    if ($remove)
                        {
                        pg_query($con, "BEGIN");
                        //delete with prejudice will ignore a not exists warning
                        //again, web users don't have access to pg_largeobjects only superusers do
                        @pg_lo_unlink($con, $row['id']);
                        pg_query($con, "END");
                        }
                    }
                //Return message and log
                array_push($arr_message, "Record Succesfully Updated.");
                if ($input_update_log)
                    {
                    $message = "Record " . chr($row_type + 64) . $post_key . " updated.";
                    $main->log($con, $message);
                    }
                $main->update($array_state, $module, $arr_state);
                //can add a recursive query to update child record when secure is altered
                $main->hook("update_cascade", true);
                }
            //bad edit
            else 
                {
				$result_where_not = $main->query($con, $select_where_not);
                $result_where = $main->query($con, $select_where);
				if (pg_num_rows($result_where_not) == 1)
					{
					//retain state values, usually a key error
					array_push($arr_message, "Error: Record not updated. Duplicate value in input form on column \"" . $arr_column_reduced[$unique_key]['name'] . "\".");
                    if ($input_update_log)
                        {
                        $message = "WHERE NOT EXISTS error updating record " . chr($row_type + 64) . $post_key . "." ;
                        $main->log($con, $message);
                        }
					}
                elseif (pg_num_rows($result_where) == 0)
                    {
					array_push($arr_message, "Error: Record not updated. Missing or malformed related record or records.");
                    if ($input_update_log)
                        {
                        $message = "WHERE EXISTS error updating record " . chr($row_type + 64) . $post_key . "." ;
                        $main->log($con, $message);
                        }                        
                    }
				else
					{
					//dispose of $arr_state and update state
					$arr_state = array();            
					$main->update($array_state, $module, $arr_state);
                    //wierd error
					array_push($arr_message, "Error: Record not updated. Record archived or underlying data change possible.");
                    if ($input_update_log)
                        {
                        $message = "Error updating record " . chr($row_type + 64) . $post_key . "."; 
                        $main->log($con, $message);
                        }
					}
                }
            }
        //insert new row    
        else //$row_type <> $row_join
            {
            $insert_clause = "row_type, key1, owner_name, updater_name";
            $select_clause = $row_type . " as row_type, " . $post_key . " as key1, '" . $owner . "' as owner_name, '" . $owner . "' as updater_name";
            foreach($arr_column_reduced as $key => $value)
                {
                $col = $main->pad("c", $key);
                $str = $arr_state[$col];
                $insert_clause .= "," . $col;
                $select_clause .= ", '" . pg_escape_string((string)$str) . "'";
                //search flag
				$search_flag = ($value['search'] == 1) ? true : false;
                //populate guest index array, can be reassigned in module Interface Enable
                $arr_guest_index = $arr_header['guest_index']['value'];
                
                //local function call, see top of module
                $main->full_text($arr_ts_vector_fts, $arr_ts_vector_ftg, $value, $str, $arr_guest_index);
                
                //local function call, see top of module
                if (in_array($key, array(41,42,43,44,45,46)))
                    {
                    $main->process_related($arr_select_where, $arr_layouts_reduced, $value, $str);    
                    }				                    
                } //end column loop	
			
            //explode full text search stuff
			$str_ts_vector_fts = !empty($arr_ts_vector_fts) ? implode(" || ' ' || ", $arr_ts_vector_fts) : "''";
			$str_ts_vector_ftg = !empty($arr_ts_vector_ftg) ? implode(" || ' ' || ", $arr_ts_vector_ftg) : "''";
            
            //explode relate check array
            $select_where = empty($arr_select_where) ? "SELECT 1" : "SELECT 1 FROM data_table WHERE (" . implode(" OR ", $arr_select_where) . ") HAVING count(*) = " . (int)count($arr_select_where);
            
			$insert_clause .= ", fts, ftg, secure, archive ";
			$select_clause .= ", to_tsvector(" . $str_ts_vector_fts . ") as fts, to_tsvector(" . $str_ts_vector_ftg . ") as ftg, ";
            
            //will detect secure and archive form values
            //secure can be inserted if there is a form value being posted
            
            if ($input_secure_post) $secure_clause = $main->check('secure', $module) ? " " . $secure . " as secure, "  : "CASE WHEN (SELECT coalesce(secure,0) FROM data_table WHERE id IN (" . $post_key . ")) > 0 THEN (SELECT secure FROM data_table WHERE id IN (" . $post_key . ")) ELSE 0 END as secure, ";
            else $secure_clause = "CASE WHEN (SELECT coalesce(secure,0) FROM data_table WHERE id IN (" . $post_key . ")) > 0 THEN (SELECT secure FROM data_table WHERE id IN (" . $post_key . ")) ELSE 0 END as secure, ";
            //archive is wired in for insert, not generally used for standard interface
            if ($input_archive_post) $archive_clause = $main->check('archive', $module) ? " " . $archive . " as archive "  : "CASE WHEN (SELECT coalesce(archive,0) FROM data_table WHERE id IN (" . $post_key . ")) > 0 THEN (SELECT archive FROM data_table WHERE id IN (" . $post_key . ")) ELSE 0 END as archive ";
            else $archive_clause = "CASE WHEN (SELECT coalesce(archive,0) FROM data_table WHERE id IN (" . $post_key . ")) > 0 THEN (SELECT archive FROM data_table WHERE id IN (" . $post_key . ")) ELSE 0 END as archive ";
            
            //key exists must check for duplicate value
            //$select_where_not & $unique_key passed and created as reference
            $unique_value = isset($arr_state[$main->pad("c", $unique_key)]) ? $arr_state[$main->pad("c", $unique_key)] : "";
            $main->unique_key(true, $select_where_not, $unique_key, $unique_value, $row_type, $post_key);
                
           //if parent row has been deleted, multiuser situation, check on insert
            $select_where_exists = "SELECT 1";
            if ($post_key > 0)
                {
                $select_where_exists = "SELECT 1 FROM data_table WHERE archive = 0 AND id IN (" . $post_key . ")";
                }
			        
            /* EXECUTE QUERY */
			$return_primary = isset($arr_column['layout']['primary']) ? $main->pad("c", $arr_column['layout']['primary']) : "c01";
            $query = "INSERT INTO data_table (" . $insert_clause	. ") SELECT " . $select_clause . $secure_clause . $archive_clause . " WHERE NOT EXISTS (" . $select_where_not . ") AND EXISTS (" . $select_where_exists . ") AND EXISTS (" . $select_where . ") RETURNING id, " . $return_primary . " as primary;";
            //echo "<p>" . $query . "</p>";
            $result = $main->query($con, $query);
            
            //good insert
            if (pg_affected_rows($result) == 1)
                {
				$row = pg_fetch_array($result);
                //process large object
                if (isset($arr_column_reduced[47]))
                    {
                    if (is_uploaded_file($_FILES[$main->name('c47', $module)]["tmp_name"]) && !$main->blank($_FILES[$main->name('c47', $module)]["name"]))
                        {
                        //see important notes on large objects in update area
                        pg_query($con, "BEGIN");
                        pg_lo_import($con, $_FILES[$main->name('c47', $module)]["tmp_name"], $row['id']);
                        pg_query($con, "END");
                        }
                    }
                array_push($arr_message, "Record Succesfully Inserted.");
                if ($input_insert_log)
                    {
                    $message = "New " . chr($row_type + 64) . $row['id'] . " record entered."; 
                    $main->log($con, $message);
                    }
                //hook for cascading a change in security
                $main->hook("insert_cascade", true);
                //dispose of $arr_state
                $arr_state = array();         
                $main->update($array_state, $module, $arr_state);
				
				//to drill down, $inserted_row_type become $row_join or the parent row when used
				$inserted_row_type = $row_type;
				$inserted_id = $row['id'];
				$inserted_primary = $row['primary'];
                }
            //bad insert
            else 
                {
				//check for key problem
				$result_where_not = $main->query($con, $select_where_not);
                $result_where = $main->query($con, $select_where);
				if (pg_num_rows($result_where_not) == 1)
					{
					//retain state values
					array_push($arr_message, "Error: Record not updated. Duplicate value in input form on column \"" . $arr_column_reduced[$unique_key]['name'] . "\".");
                    if ($input_insert_log)
                        {
                        $message = "WHERE NOT insert error on type " . chr($row_type + 64) . " record."; 
                        $main->log($con, $messagee);
                        }
					}
                elseif (pg_num_rows($result_where) == 0)
                    {
					array_push($arr_message, "Error: Record not updated. Missing or malformed related record or records.");
                    if ($input_update_log)
                        {
                        $message = "WHERE EXISTS error updating record " . chr($row_type + 64) . $post_key . "." ;
                        $main->log($con, $message);
                        }                        
                    }
				else
					{
                    //dispose of $arr_state and update state
					$arr_state = array();            
					$main->update($array_state, $module, $arr_state);	
					array_push($arr_message, "Error: Record not inserted. Parent record archived or underlying data change possible.");
                    if ($input_insert_log)
                        {
                        $message = "Insert error entering type " . chr($row_type + 64) . " record."; 
                        $main->log($con, $message , $username);
                        }
					}
				}                  		
            } //else insert row
        } //if no error message
    }// end if submit/enter data
    
/* PARENT ROW */
//$post_key > 0 only on edit or add with parent
//this gets parent information before insert or update
if ($post_key > 0)
    {
    $parent_row_type = $arr_layout['parent'];
    $parent = isset($arr_columns[$parent_row_type]['layout']['primary']) ? $main->pad("c", $arr_columns[$parent_row_type]['layout']['primary']) : "c01";
	 
	 //edit, must join to parent
    if ($row_type == $row_join)
        {       
		$child = isset($arr_column['layout']['primary']) ? $main->pad("c", $arr_column['layout']['primary']) : "c01";		
		
        $query = "SELECT T2.id, T2." . $parent . " as parent, T1." . $child . " as child, T2.archive FROM data_table T1 LEFT JOIN data_table T2 " .
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
			}
        }
    else
		//input, have parent id
        {        
        $query = "SELECT *, " . $parent . " as parent FROM data_table WHERE id = " . $post_key . " AND row_type = " . $parent_row_type . ";";    
        //echo "<p>" . $query . "</p>";
		$result = $main->query($con, $query);
		if (pg_num_rows($result) == 1)
			{
			$row = pg_fetch_array($result);
			$parent_primary = isset($row['parent']) ? $row['parent'] : "";
			$parent_id = $post_key;
                
            /* AUTOFILL HOOK */
            //loads on basis of parent record
            $main->hook("autofill", true);
			}			
        }
    }
/* END PARENT ROW */

/* AUTOLOAD HOOK */
if ($main->button(4))
    {
    //loads on basis of button
    $main->hook("autoload", true);    
    }
/* END AUTOLOAD HOOK */
    

/* BEGIN REQUIRED FORM */
$main->echo_form_begin(array('type'=>"enctype=\"multipart/form-data\""));
$main->echo_module_vars();

//echos input select object if row_type exists
//this means there is a layout
if ($row_type > 0):
    //must have row_type
          
    // HOOKS */
    $main->hook("top_level_records", true);    
    //this when inserting child record
    $main->hook("parent_record", true);
    //this to add quick child and sibling links
    $main->hook("quick_links", true);    
    //for hooking archive or security levels
    $main->hook("begin_archive_secure", true);
    //for setting readonly and hidden values
    $main->hook("before_render", true);

    echo "<div class=\"spaced\" id=\"input_message\">";
    $main->echo_messages($arr_message);
    echo "</div>";
    /* END MESSAGES */
    
    /* POPULATE INPUT FIELDS */
    //check if empty, could be either empty or children not populated
    //this is dependent on admin module "Set Column Names"
    echo "<div id=\"bb_input_fields\">"; //id wrapper
    if (!empty($arr_column_reduced))
        {
        foreach($arr_column_reduced as $key => $value)
            {
            $col = $main->pad("c", $key);
            $input = (isset($arr_state[$col])) ? $arr_state[$col] : "";
            $error = (isset($arr_errors[$col])) ? $arr_errors[$col] : "";
            $readonly = isset($arr_column_reduced[$key]['readonly']) ? $arr_column_reduced[$key]['readonly'] : false;
            $hidden =  isset($arr_column_reduced[$key]['hidden']) ? $arr_column_reduced[$key]['hidden'] : false;
            $field_id = "input_" . $main->make_html_id($row_type, $key);
            if (isset($arr_dropdowns[$row_type][$key]))
                {
                //dropdown
                $arr_droplist_reduced = $readonly ? array($input) : $main->filter_keys($arr_dropdowns[$row_type][$key]); //single item select for readonly
                echo "<div class=\"clear " . $hidden . "\">";
                echo "<label class = \"spaced padded floatleft right overflow medium shaded " . $hidden . "\" for=\"" . $col . "\">" . htmlentities($value['name']) . ": </label>";
                echo "<select id=\"" . $field_id . "\" class = \"spaced\" name = \"" . $col . "\" onFocus=\"bb_remove_message(); return false;\">";
                foreach ($arr_droplist_reduced as $dropdown)
                    {                            
                    echo "<option value=\"" . htmlentities($dropdown) . "\" " . ((strtolower($input) == strtolower($dropdown)) ? "selected" : "" ) . ">" . htmlentities($dropdown) . "&nbsp;</option>";
                    }
                echo "</select><label class=\"error\">" . $error . "</label></div>";
                }
            elseif (in_array($key, $arr_relate))
                {
                //textarea
                $attribute = $readonly ? "readonly" : ""; //readonly attribute    
                echo "<div class = \"clear " . $hidden . "\">";
                echo "<label class = \"spaced padded floatleft right overflow medium shaded " . $hidden . "\" for=\"" . $col . "\">" . htmlentities($value['name']) . ": </label>";
                echo "<input id=\"" . $field_id . "\" class = \"spaced textbox\" maxlength=\"" . $maxinput . "\" name=\"" . $col . "\" type=\"text\" value = \""  . htmlentities($input) .  "\" " . $attribute . " onFocus=\"bb_remove_message(); return false;\" />";
                echo "<label class=\"error\">" . $error . "</label></div>";
                }
            elseif (in_array($key, $arr_file))
                {
                //textarea
                $attribute = $readonly ? "readonly" : "";
                $lo = isset($arr_state['lo']) ? $arr_state['lo'] : "";
                echo "<div class = \"clear " . $hidden . "\">";
                echo "<label class = \"spaced padded floatleft left overflow medium shaded " . $hidden . "\" for=\"" . $col . "\">" . htmlentities($value['name']) . ": </label>";
                echo "<input id=\"" . $field_id . "\" class = \"spaced padded textbox noborder\" name=\"lo\" type=\"text\" value = \""  . htmlentities($lo) .  "\" readonly/><label class=\"error\">" . $error . "</label>";
                echo "</div>";
                echo "<div class = \"clear " . $hidden . "\">";
                echo "<input id=\"" . $field_id . "\" class=\"spaced textbox\" type=\"file\" name=\"" . $col . "\" id=\"file\"/>";
                if (!$value['required'])
                    {
                    echo "<span class = \"spaced border rounded padded shaded\">";                
                    echo "<label class=\"padded\">Remove: </label>";
                    $main->echo_input("remove", 1, array('type'=>'checkbox','input_class'=>'middle holderup'));
                    echo "</span>";
                    }
                echo "</div>";                
                }
            elseif (in_array($key, $arr_notes))
                {
                //textarea
                $attribute = $readonly ? "readonly" : ""; //readonly attribute    
                echo "<div class = \"clear " . $hidden . "\">";
                echo "<label class = \"spaced padded floatleft left overflow medium shaded " . $hidden . "\" for=\"" . $col . "\">" . htmlentities($value['name']) . ": </label><label class=\"error spaced padded floatleft left overflow\">" . $error . "</label>";
                echo "<div class=\"clear " . $hidden . "\"></div>";
                echo "<textarea id=\"" . $field_id . "\" class=\"spaced notearea\" maxlength=\"" . $maxnote . "\" name=\"" . $col . "\" " . $attribute . " onFocus=\"bb_remove_message(); return false;\">" . $input . "</textarea></div>";				
                }				
            else
                {
                //standard input
                $attribute = $readonly ? "readonly" : "";  //readonly attribute
                echo "<div class=\"clear " . $hidden . "\">";
                echo "<label class = \"spaced padded floatleft right overflow medium shaded\" for=\"" . $col . "\">" . htmlentities($value['name']) . ": </label>";
                echo "<input id=\"" . $field_id . "\" class = \"spaced textbox\" maxlength=\"" . $maxinput . "\" name=\"" . $col . "\" type=\"text\" value = \""  . htmlentities($input) .  "\" " . $attribute . " onFocus=\"bb_remove_message(); return false;\" />";
                echo "<label class=\"error\">" . $error . "</label></div>";
                }
            }
        echo "</div>";
        echo "<div class=\"clear\"></div>";
        //submit button and textarea load
        //for hooking archive or security levels
        $main->hook("end_archive_secure", true);
        $main->hook("submit_buttons", true);        
        $main->hook("textarea_load", true);  
        }
    /* END POPULATE INPUT FIELDS */    
    
    //hidden vars, $row_type is contained in the layout dropdown
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