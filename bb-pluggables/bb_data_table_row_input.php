<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
 * Copyright (C) Brimbox LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License Version 3 (“GNU GPL v3”)
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU GPL v3 for more details.
 *
 * You should have received a copy of the GNU GPL v3 along with this program.
 * If not, see http://www.gnu.org/licenses/
*/
if (!function_exists('bb_data_table_row_input')):

    function bb_data_table_row_input(&$arr_state) {
        // session or globals
        global $con, $main, $submit, $username;

        /*
         * IF $row_type = $row_join THEN
         * Use $row_work = $row_join on Edit
        */
        /*
         * ELSE $row_join is the child
         * So again use $row_work = $row_join -- on Insert
        */

        /* DEAL WITH CONSTANTS */
        $input_insert_log = $main->on_constant('BB_INPUT_INSERT_LOG');
        $input_update_log = $main->on_constant('BB_INPUT_UPDATE_LOG');
        $input_secure_post = $main->on_constant('BB_INPUT_SECURE_POST');
        $input_archive_post = $main->on_constant('BB_INPUT_ARCHIVE_POST');

        $maxinput = $main->get_constant('BB_STANDARD_LENGTH', 255);
        $maxnote = $main->get_constant('BB_NOTE_LENGTH', 65536);

        $delimiter = $main->get_constant('BB_MULTISELECT_DELIMITER', ",");

        $arr_layouts = $main->layouts($con);
        $default_row_type = $main->get_default_layout($arr_layouts);
        $arr_header = $main->get_json($con, "bb_interface_enable");

        // INSERT or UPDATE database row
        $arr_messages = array(); // empty array
        $arr_errors = $main->state('arr_errors', $arr_state, array());
        $errors = count($arr_errors);

        $row_type = $main->state('row_type', $arr_state, 0);
        $row_join = $main->state('row_join', $arr_state, 0);
        $post_key = $main->state('post_key', $arr_state, 0);

        //row_join could be zero
        $row_work = $row_join ? $row_join : $default_row_type;
        //edit = true, insert = false
        $edit_or_insert = ($row_type == $row_join) ? 1 : 0;

        $arr_columns = $main->columns($con, $row_work);
        $arr_dropdowns = $main->dropdowns($con, $row_work);

        if (!$errors) {
            // no errors
            // produce empty form since we are going to load the data
            $arr_columns_props = $main->columns_properties($con, $row_work);

            $unique_key = isset($arr_columns_props['unique']) ? $arr_columns_props['unique'] : 0;
            $arr_ts_vector_fts = array();
            $arr_ts_vector_ftg = array();
            $arr_select_where = array();

            // edit preexisting row
            if ($edit_or_insert) {
                $update_clause = "updater_name = '" . pg_escape_string($username) . "'";
                foreach ($arr_columns as $key => $value) {
                    $col = $main->pad("c", $key);
                    $str = $arr_state[$col];
                    // multiselect dropdown
                    if (is_array($str)) $str = implode($delimiter, $str);
                    $update_clause.= "," . $col . " =  '" . pg_escape_string($str) . "'";
                    // prepare fts and ftg
                    $search_flag = ($value['search'] == 1) ? true : false;
                    // populate guest index array, can be reassigned in module Interface Enable
                    $arr_guest_index = $arr_header['guest_index']['value'];

                    // local function call, see top of module
                    $main->full_text($arr_ts_vector_fts, $arr_ts_vector_ftg, $value, $str, $arr_guest_index);

                    // local function call, see top of module
                    if (in_array($key, array(41, 42, 43, 44, 45, 46))) {
                        $main->process_related($arr_select_where, $arr_layouts, $value, $str);
                    }
                } // end column loop
                // explode full text update
                $str_ts_vector_fts = !empty($arr_ts_vector_fts) ? implode(" || ' ' || ", $arr_ts_vector_fts) : "''";
                $str_ts_vector_ftg = !empty($arr_ts_vector_ftg) ? implode(" || ' ' || ", $arr_ts_vector_ftg) : "''";

                // explode relate check array
                $select_where = empty($arr_select_where) ? "SELECT 1" : "SELECT 1 FROM data_table WHERE (" . implode(" OR ", $arr_select_where) . ") HAVING count(*) = " . ( int )count($arr_select_where);

                // will detect secure and archive form values
                // secure can be updated if there is a form value being posted and constant is set
                $archive_clause = $secure_clause = "";
                if ($input_secure_post) {
                    $secure = ( int )$arr_state['secure'];
                    $secure_clause = ", secure = " . $secure . " ";
                }
                // archive is wired in for update, not generally used for standard interface
                if ($input_archive_post) {
                    $archive = ( int )$arr_state['archive'];
                    $archive_clause = ", archive = " . $archive . " ";
                }

                // key exists must check for duplicate value
                // $select_where_not & $unique_key passed and created as reference
                $col_unique_key = $main->pad("c", $unique_key);
                if (in_array($unique_key, array_keys($arr_columns))) $unique_value = $arr_state[$col_unique_key];
                else
                // not updating key column, unique key of 0 returns no key action
                $unique_key = 0;

                $unique_value = isset($arr_state[$col_unique_key]) ? $arr_state[$col_unique_key] : "";
                $main->unique_key(true, $select_where_not, $unique_key, $unique_value, $row_work, $post_key);

                $return_primary = isset($arr_columns_props['primary']) ? $main->pad("c", $arr_columns_props['primary']) : "c01";
                $query = "UPDATE data_table SET " . $update_clause . ", fts = to_tsvector(" . $str_ts_vector_fts . "), ftg = to_tsvector(" . $str_ts_vector_ftg . ") " . $secure_clause . " " . $archive_clause . " WHERE id IN (" . $post_key . ") AND NOT EXISTS (" . $select_where_not . ") AND EXISTS (" . $select_where . ") RETURNING id, " . $return_primary . " as primary;";
                /* STEP 1 */
                // die($query);
                $result = $main->query($con, $query);
                // echo "<p>" . $query . "</p>";
                // good edit, deal with large object and partial record edit
                if (pg_affected_rows($result) == 1) {
                    $row = pg_fetch_array($result);
                    // process large object
                    if (isset($arr_columns[47])) {
                        if (is_uploaded_file($_FILES[$main->name('c47', $submit) ]["tmp_name"]) && !$main->blank($_FILES[$main->name('c47', $submit) ]["name"])) {
                            pg_query($con, "BEGIN");
                            /* IMPORTANT */
                            // if there's a better way I'd do it, superusers control large objects since 9.0
                            // on standard hosting ignoring a delete warning on object not found is the best way
                            // web users don't have access to pg_largeobjects only superusers do
                            // large objects are owned by their creator, in this case the web user
                            // only superusers can use GRANT on large object since Postgres 9.
                            /* STEP 2a */
                            @pg_lo_unlink($con, $row['id']);
                            pg_query($con, "END");
                            pg_query($con, "BEGIN");
                            pg_lo_import($con, $_FILES[$main->name('c47', $submit) ]["tmp_name"], $row['id']);
                            pg_query($con, "END");
                        }
                        // Remove existing large object
                        // for remove flag
                        $remove = isset($arr_state['remove']) ? $arr_state['remove'] : 0;
                        if ($remove) {
                            /* STEP 2b */
                            pg_query($con, "BEGIN");
                            // delete with prejudice will ignore a not exists warning
                            // again, web users don't have access to pg_largeobjects only superusers do
                            @pg_lo_unlink($con, $row['id']);
                            pg_query($con, "END");
                        }
                    }
                    // Return message and log, recordkeeping
                    array_push($arr_messages, "Record Succesfully Edited.");
                    if ($input_update_log) {
                        $message = "Record " . chr($row_work + 64) . $post_key . " updated.";
                        $main->log($con, $message);
                    }
                    // put fundemental variables into state
                    $main->set('row_type', $arr_state, $row_type);
                    $main->set('row_join', $arr_state, $row_join);
                    $main->set('post_key', $arr_state, $post_key);

                    // parent and inserted information
                    $parent_row_type = $arr_layouts[$row_join]['parent'];
                    // have to look it up
                    $arr_columns_props_parent = $main->columns_properties($con, $parent_row_type);
                    $parent = isset($arr_columns_props_parent['primary']) ? $main->pad("c", $arr_columns_props_parent['primary']) : "c01";
                    $child = isset($arr_columns_props['primary']) ? $main->pad("c", $arr_columns_props['primary']) : "c01";
                    // query to find relationships
                    $query = "SELECT T2.id, T2." . $parent . " as parent, T1." . $child . " as child, T2.archive FROM data_table T1 LEFT JOIN data_table T2 " . "ON T2.id = T1.key1 WHERE T1.id = " . $post_key . ";";
                    $result = $main->query($con, $query);
                    $row = pg_fetch_array($result);

                    // frrom second query
                    $main->set('inserted_id', $arr_state, $post_key);
                    $main->set('inserted_row_type', $arr_state, $row_join);
                    $main->set('inserted_primary', $arr_state, $row['child']);

                    $main->set('parent_id', $arr_state, $row['id']);
                    $main->set('parent_row_type', $arr_state, $parent_row_type);
                    $parent_primary = isset($row['parent']) ? $row['parent'] : "";
                    $main->set('parent_primary', $arr_state, $parent_primary);

                    // can add a recursive query to update child record when secure is altered
                    // should do a recursive update cascade
                    // end good edit
                    
                }
                else {
                    // bad edit
                    // deal with lo
                    if (isset($arr_columns[47])) {
                        //use row_type index
                        $query_lo = "SELECT c47 FROM data_table WHERE row_type = " . (int)$row_work . " AND id = " . (int)$post_key . ";";
                        $result_lo = $main->query($con, $query_lo);
                        if (pg_affected_rows($result_lo) == 1) {
                            //revert back to orginal lo
                            $row = pg_fetch_array($result_lo);
                            $arr_state['lo'] = $row['c47'];
                        }
                        else {
                            //underlying data change, row is gone
                            unset($arr_state['lo']);
                        }
                    }
                    //look for error type if not validatation
                    $result_where_not = $main->query($con, $select_where_not);
                    $result_where = $main->query($con, $select_where);
                    if (pg_num_rows($result_where_not) == 1) {
                        // retain state values, usually a key error
                        array_push($arr_messages, "Error: Record not updated. Duplicate value in input form on column \"" . $arr_columns[$unique_key]['name'] . "\".");
                        if ($input_update_log) {
                            $message = "WHERE NOT EXISTS error updating record " . chr($row_work + 64) . $post_key . ".";
                            $main->log($con, $message);
                        }
                    }
                    elseif (pg_num_rows($result_where) == 0) {
                        array_push($arr_messages, "Error: Record not updated. Missing or malformed related record or records.");
                        if ($input_update_log) {
                            $message = "WHERE EXISTS error updating record " . chr($row_work + 64) . $post_key . ".";
                            $main->log($con, $message);
                        }
                    }
                    else {
                        // dispose of $arr_state and update state
                        $arr_state = array();
                        // wierd error
                        array_push($arr_messages, "Error: Record not updated. Record archived or underlying data change possible.");
                        if ($input_update_log) {
                            $message = "Error updating record " . chr($row_work + 64) . $post_key . ".";
                            $main->log($con, $message);
                        }
                    }
                    // add message
                    
                }
            }
            else {
                // insert new row
                // $row_type <> $row_join
                $insert_clause = "row_type, key1, owner_name, updater_name";
                $select_clause = $row_work . " as row_type, " . $post_key . " as key1, '" . $username . "' as owner_name, '" . $username . "' as updater_name";
                foreach ($arr_columns as $key => $value) {
                    $col = $main->pad("c", $key);
                    $str = $arr_state[$col];
                    // multiselect dropdown
                    if (is_array($str)) $str = implode($delimiter, $str);
                    $insert_clause.= "," . $col;
                    $select_clause.= ", '" . pg_escape_string(( string )$str) . "'";
                    // search flag
                    $search_flag = ($value['search'] == 1) ? true : false;
                    // populate guest index array, can be reassigned in module Interface Enable
                    $arr_guest_index = $arr_header['guest_index']['value'];

                    // local function call, see top of module
                    $main->full_text($arr_ts_vector_fts, $arr_ts_vector_ftg, $value, $str, $arr_guest_index);

                    // local function call, see top of module
                    if (in_array($key, array(41, 42, 43, 44, 45, 46))) {
                        $main->process_related($arr_select_where, $arr_layouts, $value, $str);
                    }
                } // end column loop
                // explode full text search stuff
                $str_ts_vector_fts = !empty($arr_ts_vector_fts) ? implode(" || ' ' || ", $arr_ts_vector_fts) : "''";
                $str_ts_vector_ftg = !empty($arr_ts_vector_ftg) ? implode(" || ' ' || ", $arr_ts_vector_ftg) : "''";

                // explode relate check array
                $select_where = empty($arr_select_where) ? "SELECT 1" : "SELECT 1 FROM data_table WHERE (" . implode(" OR ", $arr_select_where) . ") HAVING count(*) = " . ( int )count($arr_select_where);

                $insert_clause.= ", fts, ftg, secure, archive ";
                $select_clause.= ", to_tsvector(" . $str_ts_vector_fts . ") as fts, to_tsvector(" . $str_ts_vector_ftg . ") as ftg, ";

                // will detect secure and archive form values
                // secure can be inserted if there is a form value being posted
                // inherit archive + secure from parent
                $secure_clause = "CASE WHEN (SELECT coalesce(secure,0) FROM data_table WHERE id IN (" . $post_key . ")) > 0 THEN (SELECT secure FROM data_table WHERE id IN (" . $post_key . ")) ELSE 0 END as secure, ";
                $archive_clause = "CASE WHEN (SELECT coalesce(archive,0) FROM data_table WHERE id IN (" . $post_key . ")) > 0 THEN (SELECT archive FROM data_table WHERE id IN (" . $post_key . ")) ELSE 0 END as archive ";
                // if constants are set
                if ($input_secure_post) {
                    $secure = ( int )$arr_state['secure'];
                    $secure_clause = " " . $secure . " as secure, ";
                }
                if ($input_archive_post) {
                    $archive = ( int )$arr_state['archive'];
                    $archive_clause = " " . $archive . " as archive ";
                }
                // key exists must check for duplicate value
                // $select_where_not & $unique_key passed and created as reference
                // still check key for blank on insert
                $col_unique_key = $main->pad("c", $unique_key);
                if (in_array($unique_key, array_keys($arr_columns))) $unique_value = $arr_state[$col_unique_key];
                else
                // not updating key column, unique key of 0 returns no key action
                $unique_key = 0;

                $unique_value = isset($arr_state[$col_unique_key]) ? $arr_state[$col_unique_key] : "";
                $main->unique_key(false, $select_where_not, $unique_key, $unique_value, $row_work, $post_key);

                // if parent row has been deleted, multiuser situation, check on insert
                $select_where_exists = "SELECT 1";
                if ($post_key > 0 || $row_type > 0) {
                    $select_where_exists = "SELECT 1 FROM data_table WHERE archive = 0 AND row_type IN (" . $row_type . ") AND id IN (" . $post_key . ")";
                }

                /* EXECUTE QUERY */
                $return_primary = isset($arr_columns[$row_work]['primary']) ? $main->pad("c", $arr_columns[$row_work]['primary']) : "c01";
                $query = "INSERT INTO data_table (" . $insert_clause . ") SELECT " . $select_clause . $secure_clause . $archive_clause . " WHERE NOT EXISTS (" . $select_where_not . ") AND EXISTS (" . $select_where_exists . ") AND EXISTS (" . $select_where . ") RETURNING id, " . $return_primary . " as inserted_primary;";
                /* STEP 1 */
                // die($query);
                $result = $main->query($con, $query);

                // good insert
                if (pg_affected_rows($result) == 1) {
                    $row = pg_fetch_array($result);
                    // process large object
                    if (isset($arr_columns[47])) {
                        if (is_uploaded_file($_FILES[$main->name('c47', $submit) ]["tmp_name"]) && !$main->blank($_FILES[$main->name('c47', $submit) ]["name"])) {
                            /* STEP 2 */
                            // see important notes on large objects in update area
                            pg_query($con, "BEGIN");
                            pg_lo_import($con, $_FILES[$main->name('c47', $submit) ]["tmp_name"], $row['id']);
                            pg_query($con, "END");
                        }
                    }
                    // Return message and log, recordkeeping
                    array_push($arr_messages, "Record Succesfully Inserted.");
                    if ($input_insert_log) {
                        $message = "New " . chr($row_work + 64) . $row['id'] . " record entered.";
                        $main->log($con, $message);
                    }

                    // dispose of $arr_state
                    $arr_state = array();

                    // set up fundemental variables
                    $main->set('row_type', $arr_state, $row_type);
                    $main->set('row_join', $arr_state, $row_join);
                    $main->set('post_key', $arr_state, $post_key);

                    // parent and inserted information
                    // from main insert
                    $main->set('inserted_id', $arr_state, $row['id']);
                    $main->set('inserted_row_type', $arr_state, $row_join);
                    $main->set('inserted_primary', $arr_state, $row['inserted_primary']);

                    // second query
                    $parent_row_type = $arr_layouts[$row_join]['parent'];
                    // have to look it up
                    $arr_columns_props_parent = $main->columns_properties($con, $parent_row_type);
                    $parent = isset($arr_columns_props_parent['primary']) ? $main->pad("c", $arr_columns_props_parent['primary']) : "c01";
                    $query = "SELECT *, " . $parent . " as parent FROM data_table WHERE id = " . $post_key . ";";
                    $result = $main->query($con, $query);
                    $row = pg_fetch_array($result);

                    $main->set('parent_id', $arr_state, $post_key);
                    $main->set('parent_row_type', $arr_state, $parent_row_type);
                    $parent_primary = isset($row['parent']) ? $row['parent'] : "";
                    $main->set('parent_primary', $arr_state, $parent_primary);
                }
                else {
                    // bad insert
                    // deal with lo, just unset lo
                    unset($arr_state['lo']);
                    // check for key problem
                    $result_where_not = $main->query($con, $select_where_not);
                    $result_where = $main->query($con, $select_where);
                    if (pg_num_rows($result_where_not) == 1) {
                        // retain state values
                        array_push($arr_messages, "Error: Record not updated. Duplicate value in input form on column \"" . $arr_columns[$unique_key]['name'] . "\".");
                        if ($input_insert_log) {
                            $message = "WHERE NOT insert error on type " . chr($row_work + 64) . " record.";
                            $main->log($con, $message);
                        }
                    }
                    elseif (pg_num_rows($result_where) == 0) {
                        array_push($arr_messages, "Error: Record not updated. Missing or malformed related record or records.");
                        if ($input_update_log) {
                            $message = "WHERE EXISTS error updating record " . chr($row_work + 64) . $post_key . ".";
                            $main->log($con, $message);
                        }
                    }
                    else {
                        // dispose of $arr_state and update state
                        $arr_state = array();
                        array_push($arr_messages, "Error: Record not inserted. Parent record archived or underlying data change possible.");
                        if ($input_insert_log) {
                            $message = "Insert error entering type " . chr($row_work + 64) . " record.";
                            $main->log($con, $message);
                        }
                    }
                    // add message
                    
                }
            } // else insert row
            // whatever the messages are set them
            
        } // if noerror message
        // populate]
        // will be empty array if no messages
        $main->set('arr_messages', $arr_state, $arr_messages);
    }

endif;
?>