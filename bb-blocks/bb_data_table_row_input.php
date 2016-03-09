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
if (!function_exists ('bb_input_module_postback')) :

    function bb_data_table_row_input($arr_layouts, $arr_columns, $arr_dropdowns, &$arr_state, $params = array())
        {
        //session or globals
        global $con, $main, $submit, $username;
        
        //because of hook default value must be set here
        if (!is_array($params)) $params = array();
        //unpack params into variables
        foreach ($params as $key => $value)
            {
            ${$key} = $value;
            }
        //filter default empty array
        $filter = isset($filter) ? $filter : array();
        //mode default is true, keep cols not in filter
        $mode = isset($mode) ? $mode : true;
        
        //filter the columns
        $arr_columns_filtered = $main->filter_keys($arr_columns, $filter, $mode);
    
        //INSERT or UPDATE database row    
        $arr_messages = array(); //empty array
        
        $arr_errors = $main->state('arr_errors', $arr_state, array());
        $errors = count($arr_errors);
        
        $row_type = $main->state('row_type', $arr_state, $default_row_type);
        $row_join = $main->state('row_join', $arr_state, 0);
        $post_key = $main->state('post_key', $arr_state, 0);
        
        if (!$errors) //no errors
            {
            //produce empty form since we are going to load the data
            $arr_columns_props = $main->lookup($con, 'bb_column_names', array($row_type));
            
            $unique_key = isset($arr_columns_props['unique']) ? $arr_columns_props['unique'] : 0;        
            $arr_ts_vector_fts = array();
            $arr_ts_vector_ftg = array();
            $arr_select_where = array();
            
            //additional character policy filter
            $arr_state = $main->filter("bb_row_input_character_policy", $arr_state);
           
            // update preexisting row
            if ($row_type == $row_join)  
                {            
                $update_clause = "updater_name = '" . pg_escape_string($username) . "'";
                foreach($arr_columns_filtered as $key => $value)
                    {
                    $col = $main->pad("c", $key);
                    $str = $arr_state[$col];
                    //multiselect dropdown
                    if (is_array($str)) $str = implode($delimiter, $str);
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
                        $main->process_related($arr_select_where, $arr_layouts, $value, $str);    
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
                $col_unique_key = $main->pad("c", $unique_key);
                if (in_array($unique_key, array_keys($arr_columns_filtered)))
                    $unique_value = $arr_state[$col_unique_key];
                else //not updating key column, unique key of 0 returns no key action
                    $unique_key = 0;    
    
                $unique_value = isset($arr_state[$col_unique_key]) ? $arr_state[$col_unique_key] : "";
                $main->unique_key(true, $select_where_not, $unique_key, $unique_value, $row_type, $post_key);
                
                $return_primary = isset($arr_columns_props['primary']) ? $main->pad("c", $arr_columns_props['primary']) : "c01";
                $query = "UPDATE data_table SET " . $update_clause . ", fts = to_tsvector(" . $str_ts_vector_fts . "), ftg = to_tsvector(" . $str_ts_vector_ftg . ") " . $secure_clause . " " .
                         "WHERE id IN (" . $post_key . ") AND NOT EXISTS (" . $select_where_not . ") AND EXISTS (" . $select_where . ") RETURNING id, " . $return_primary . " as primary;";                 
                /* STEP 1 */
                $result = $main->query($con, $query);
                //echo "<p>" . $query . "</p>";
                                
                //good edit, deal with large object and partial record edit
                if (pg_affected_rows($result) == 1)
                    {
                    $row = pg_fetch_array($result);
                    //process large object
                    if (isset($arr_columns[47]))
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
                            /* STEP 2a */
                            @pg_lo_unlink($con, $row['id']);
                            pg_query($con, "END");
                            pg_query($con, "BEGIN");
                            pg_lo_import($con, $_FILES[$main->name('c47', $module)]["tmp_name"], $row['id']);
                            pg_query($con, "END");
                            }
                        //Remove existing large object
                        //for remove flag
                        $remove = isset($arr_state['remove']) ? $arr_state['remove'] : 0; 
                        if ($remove)
                            {
                            /* STEP 2b */
                            pg_query($con, "BEGIN");
                            //delete with prejudice will ignore a not exists warning
                            //again, web users don't have access to pg_largeobjects only superusers do
                            @pg_lo_unlink($con, $row['id']);
                            pg_query($con, "END");
                            }
                        }
                    //in the case of editing filtered columns
                    //may need to update full text field
                    //will only happen if using filter
                    /* OPTIONAL STEP 3 */
                    if (!empty($filter))
                        {
                        $arr_return = array();
                        foreach($arr_columns as $key => $value)
                            {
                            $col = $main->pad("c", $key);
                            array_push($arr_return, $col);
                            $search_flag = ($value['search'] == 1) ? true : false;
                            //guest flag
                            if (empty($array_guest_index))
                                {
                                $guest_flag = (($value['search'] == 1) && ($value['secure'] == 0)) ? true : false;
                                }
                            else
                                {
                                $guest_flag = (($value['search'] == 1) && in_array((int)$value['secure'], $array_guest_index)) ? true : false;						
                                }
                            //build fts SQL code
                            if ($search_flag)
                                {
                                array_push($arr_ts_vector_fts,  $col . " || ' ' || regexp_replace(" . $col . ", E'(\\\\W)+', ' ', 'g')");
                                }
                            if ($guest_flag)
                                {
                                array_push($arr_ts_vector_ftg,  $col . " || ' ' || regexp_replace(" . $col . ", E'(\\\\W)+', ' ', 'g')");
                                }                
                            } //$xml_column
                        //implode arrays with guest column full text query definitions
                        $str_ts_vector_fts = !empty($arr_ts_vector_fts) ? implode(" || ' ' || ", $arr_ts_vector_fts) : "''";
                        $str_ts_vector_ftg = !empty($arr_ts_vector_ftg) ? implode(" || ' ' || ", $arr_ts_vector_ftg) : "''";
                            
                        //build the union query array
                        $query = "UPDATE data_table SET fts = to_tsvector(" . $str_ts_vector_fts . "), ftg = to_tsvector(" . $str_ts_vector_ftg . ") WHERE id = " . $post_key . " RETURNING " . implode(", ", $arr_return) . ";";
                        $result = $main->query($con, $query);
                        $row = pg_fetch_array($result);
                        foreach ($arr_return as $value)
                            {
                            //$value = $col
                            $arr_state[$value] = $row[$value];
                            }
                        }
                    //Return message and log, recordkeeping
                    array_push($arr_messages, "Record Succesfully Edited.");
                    if ($input_update_log)
                        {
                        $message = "Record " . chr($row_type + 64) . $post_key . " updated.";
                        $main->log($con, $message);
                        }                                        
                    //put fundemental variables into state
                    $main->set('row_type', $arr_state, $row_type);
                    $main->set('row_join', $arr_state, $row_join);
                    $main->set('post_key', $arr_state, $post_key);
                    
                    //parent and inserted information
                    $parent_row_type = $arr_layouts[$row_type]['parent'];
                    //have to look it up
                    $arr_columns_props_parent = $main->lookup($con, 'bb_column_names', array($parent_row_type, "layout"));                
                    $parent = isset($arr_columns_props_parent['primary']) ? $main->pad("c", $arr_columns_props_parent['primary']) : "c01";
                    $child = isset($arr_columns_props['primary']) ? $main->pad("c", $arr_columns_props['primary']) : "c01";		
                    //query to find relationships
                    $query = "SELECT T2.id, T2." . $parent . " as parent, T1." . $child . " as child, T2.archive FROM data_table T1 LEFT JOIN data_table T2 " .
                    "ON T2.id = T1.key1 WHERE T1.id = " . $post_key . ";";
                    $result = $main->query($con, $query);
                    $row = pg_fetch_array($result);
                    
                    //frrom second query
                    $main->set('inserted_id', $arr_state, $post_key);
                    $main->set('inserted_row_type', $arr_state, $row_type);
                    $main->set('inserted_primary', $arr_state, $row['child']);
                    
                    $main->set('parent_id', $arr_state, $row['id']);
                    $main->set('parent_row_type', $arr_state, $parent_row_type);
                    $parent_primary = isset($row['parent']) ? $row['parent'] : "";
                    $main->set('parent_primary', $arr_state, $parent_primary);
                    
                    //can add a recursive query to update child record when secure is altered
                    //should do a recursive update cascade
                    //end good edit
                    }
                //bad edit
                else 
                    {
                    $result_where_not = $main->query($con, $select_where_not);
                    $result_where = $main->query($con, $select_where);
                    if (pg_num_rows($result_where_not) == 1)
                        {
                        //retain state values, usually a key error
                        array_push($arr_messages, "Error: Record not updated. Duplicate value in input form on column \"" . $arr_columns[$unique_key]['name'] . "\".");
                        if ($input_update_log)
                            {
                            $message = "WHERE NOT EXISTS error updating record " . chr($row_type + 64) . $post_key . "." ;
                            $main->log($con, $message);
                            }
                        }
                    elseif (pg_num_rows($result_where) == 0)
                        {
                        array_push($arr_messages, "Error: Record not updated. Missing or malformed related record or records.");
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
                        //wierd error
                        array_push($arr_messages, "Error: Record not updated. Record archived or underlying data change possible.");
                        if ($input_update_log)
                            {
                            $message = "Error updating record " . chr($row_type + 64) . $post_key . "."; 
                            $main->log($con, $message);
                            }
                        }
                    //add message
                    }
                }
            //insert new row    
            else //$row_type <> $row_join
                {
                $insert_clause = "row_type, key1, owner_name, updater_name";
                $select_clause = $row_type . " as row_type, " . $post_key . " as key1, '" . $username . "' as owner_name, '" . $username . "' as updater_name";
                foreach($arr_columns_filtered as $key => $value)
                    {
                    $col = $main->pad("c", $key);
                    $str = $arr_state[$col];
                    //multiselect dropdown
                    if (is_array($str)) $str = implode($delimiter, $str);
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
                        $main->process_related($arr_select_where, $arr_layouts, $value, $str);    
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
                //still check key for blank on insert
                $col_unique_key = $main->pad("c", $unique_key);
                $unique_value = isset($arr_state[$col_unique_key]) ? $arr_state[$col_unique_key] : "";
                $main->unique_key(true, $select_where_not, $unique_key, $unique_value, $row_type, $post_key);
                    
               //if parent row has been deleted, multiuser situation, check on insert
                $select_where_exists = "SELECT 1";
                if ($post_key > 0 || $row_join > 0)
                    {
                    $select_where_exists = "SELECT 1 FROM data_table WHERE archive = 0 AND row_type IN (" . $row_join . ") AND id IN (" . $post_key . ")";
                    }
                        
                /* EXECUTE QUERY */
                $return_primary = isset($arr_columns[$row_type]['primary']) ? $main->pad("c", $arr_columns[$row_type]['primary']) : "c01";
                $query = "INSERT INTO data_table (" . $insert_clause	. ") SELECT " . $select_clause . $secure_clause . $archive_clause . " WHERE NOT EXISTS (" . $select_where_not . ") AND EXISTS (" . $select_where_exists . ") AND EXISTS (" . $select_where . ") RETURNING id, " . $return_primary . " as inserted_primary;";
                /* STEP 1 */
                //echo "<p>" . $query . "</p>";
                $result = $main->query($con, $query);
                
                //good insert
                if (pg_affected_rows($result) == 1)
                    {
                    $row = pg_fetch_array($result);
                    //process large object
                    if (isset($arr_columns[47]))
                        {
                        if (is_uploaded_file($_FILES[$main->name('c47', $module)]["tmp_name"]) && !$main->blank($_FILES[$main->name('c47', $module)]["name"]))
                            {
                            /* STEP 2 */
                            //see important notes on large objects in update area
                            pg_query($con, "BEGIN");
                            pg_lo_import($con, $_FILES[$main->name('c47', $module)]["tmp_name"], $row['id']);
                            pg_query($con, "END");
                            }
                        }
                    //Return message and log, recordkeeping
                    array_push($arr_messages, "Record Succesfully Inserted.");
                    if ($input_insert_log)
                        {
                        $message = "New " . chr($row_type + 64) . $row['id'] . " record entered."; 
                        $main->log($con, $message);
                        }
                    
                    //dispose of $arr_state
                    $arr_state = array();
                    
                    //set up fundemental variables
                    $main->set('row_type', $arr_state, $row_type);
                    $main->set('row_join', $arr_state, $row_join);
                    $main->set('post_key', $arr_state, $post_key);
                    
                    //parent and inserted information
                    //from main insert
                    $main->set('inserted_id', $arr_state, $row['id']);
                    $main->set('inserted_row_type', $arr_state, $row_type);
                    $main->set('inserted_primary', $arr_state, $row['inserted_primary']);
                    
                    //second query
                    $parent_row_type = $arr_layouts[$row_type]['parent'];
                    //have to look it up
                    $arr_columns_props_parent = $main->lookup($con, 'bb_column_names', array($parent_row_type, "layout"));
                    $parent = isset($arr_columns_props_parent['primary']) ? $main->pad("c", $arr_columns_props_parent['primary']) : "c01";                    
                    $query = "SELECT *, " . $parent . " as parent FROM data_table WHERE id = " . $post_key . ";";
                    $result = $main->query($con, $query);
                    $row = pg_fetch_array($result);
                    
                    $main->set('parent_id', $arr_state, $post_key);
                    $main->set('parent_row_type', $arr_state, $parent_row_type);
                    $parent_primary = isset($row['parent']) ? $row['parent'] : "";
                    $main->set('parent_primary', $arr_state, $parent_primary);               
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
                        array_push($arr_messages, "Error: Record not updated. Duplicate value in input form on column \"" . $arr_columns[$unique_key]['name'] . "\".");
                        if ($input_insert_log)
                            {
                            $message = "WHERE NOT insert error on type " . chr($row_type + 64) . " record."; 
                            $main->log($con, $message);
                            }
                        }
                    elseif (pg_num_rows($result_where) == 0)
                        {
                        array_push($arr_messages, "Error: Record not updated. Missing or malformed related record or records.");
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
                        array_push($arr_messages, "Error: Record not inserted. Parent record archived or underlying data change possible.");
                        if ($input_insert_log)
                            {
                            $message = "Insert error entering type " . chr($row_type + 64) . " record."; 
                            $main->log($con, $message , $username);
                            }
                        }
                    //add message
                    }                  		
                } //else insert row
            //whatever the messages are set them
            } //if noerror message
        //populate]
        //will be empty array if no messages
        $main->set('arr_messages', $arr_state, $arr_messages);
        }
        
endif; //pluggable
?>