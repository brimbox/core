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
?>
<?php
/* Postback when entering the input module */
if (!function_exists('bb_input_module_postback')):

    function bb_input_module_postback(&$arr_state) {
        // session or global vars, superglobals
        global $POST, $con, $main, $module, $submit, $button;

        /*
         * IF $row_type = $row_join THEN
         * Use $row_join -- on Edit
        */
        /*
         * ELSE $row_join is the child
         * So again use $row_join -- on Insert
        */

        // standard values
        $arr_relate = array(41, 42, 43, 44, 45, 46);
        $arr_file = array(47);
        $arr_reserved = array(48);
        $arr_notes = array(49, 50);
        $textarea_rows = 4; // minimum
        $delimiter = $main->get_constant('BB_MULTISELECT_DELIMITER', ",");

        // does not set messages
        /* INITIALIZE */
        // get layout and properties
        $arr_layouts = $main->layouts($con);
        $default_row_type = $main->get_default_layout($arr_layouts);

        // entering the input from a link, most likely with $row_type, $post_key, and $row_join
        if (!empty($POST['bb_row_type'])) {
            $arr_state = array();
            // set from $POST
            $row_type = $main->set('row_type', $arr_state, $POST['bb_row_type']);
            $row_join = $main->set('row_join', $arr_state, $POST['bb_row_join']);
            $post_key = $main->set('post_key', $arr_state, $POST['bb_post_key']);

            // get the columns and dropdowns for given row_type
            $arr_columns = $main->columns($con, $row_join);
            $arr_dropdowns = $main->dropdowns($con, $row_join);
            $arr_props = $main->column_properties($con, $row_join);

            // get the record, either for edit or parent record info
            // first result
            $query = "SELECT * FROM data_table WHERE id = " . $post_key . ";";
            $result = $main->query($con, $query);
            $row = pg_fetch_array($result);

            // populate from database if edit
            if ($row_type == $row_join) {
                // loop through columns
                foreach ($arr_columns as $key => $value) {
                    $col = $main->pad("c", $key);
                    if (isset($arr_dropdowns[$key])) {
                        $arr = explode($delimiter, $row[$col]);
                        $main->set($col, $arr_state, $arr);
                    }
                    else {
                        if (in_array($key, $arr_notes)) {
                            $str = $main->purge_chars($row[$col], false);
                            $main->set($col, $arr_state, $str);
                        }
                        elseif (in_array($key, $arr_file)) {
                            $str = $main->purge_chars($row[$col]);
                            $main->set("lo", $arr_state, $str);
                        }
                        else {
                            $str = $main->purge_chars($row[$col]);
                            $main->set($col, $arr_state, $str);
                        }
                    }
                }

                // see if there is a parent record
                $query = "SELECT * FROM data_table WHERE id IN (SELECT key1 FROM data_table WHERE id = " . $post_key . ");";
                $result = $main->query($con, $query);
                // second result for parent of edit record
                // parent
                if (pg_num_rows($result) == 1) {
                    $row = pg_fetch_array($result);
                    $main->set('parent_id', $arr_state, $row['id']);
                    $main->set('parent_row_type', $arr_state, $row['row_type']);
                    $col_type_primary = $arr_props['primary'];
                    $column_primary = $main->pad("c", $col_type_primary);
                    $main->set('parent_primary', $arr_state, $row[$column_primary]);
                    // no parent
                    
                }
                else {
                    $parent_id = $main->set('parent_id', $arr_state, 0);
                    $parent_row_type = $main->set('parent_row_type', $arr_state, 0);
                    $main->set('parent_primary', $arr_state, "");
                }
            } // first result for parent when adding a child record
            // insert
            elseif (pg_num_rows($result) == 1) {
                $main->set('parent_id', $arr_state, $row['id']);
                $main->set('parent_row_type', $arr_state, $row['row_type']);
                $arr_props = $main->column_properties($con, $row['row_type']);
                $col_type_primary = $arr_props['primary'];
                $column_primary = $main->pad("c", $col_type_primary);
                $main->set('parent_primary', $arr_state, $row[$column_primary]);
            }
            // get archive and secure
            $main->set('secure', $arr_state, $row['secure']);
            $main->set('archive', $arr_state, $row['archive']);
        }

        // if relating a record when there is a relate field
        elseif (!empty($POST['bb_relate'])) {
            // populate from state
            $row_type = $main->state('row_type', $arr_state, 0);
            $row_join = $main->state('row_join', $arr_state, $default_row_type);
            $post_key = $main->state('post_key', $arr_state, 0);
            $relate = $POST['bb_relate'];

            // get the columns and dropdowns for given row_type
            $arr_columns = $main->columns($con, $row_join);
            $arr_dropdowns = $main->dropdowns($con, $row_join);

            // get related record row
            $query = "SELECT * FROM data_table WHERE id = " . ( int )$relate . ";";
            $result = $main->query($con, $query);
            if (pg_num_rows($result) == 1) {
                $row = pg_fetch_array($result);
                $relate_columns = $main->columns($con, $row['row_type']);
                $relate_col_type = $main->get_default_column($relate_columns);
                $relate_column = $main->pad("c", $relate_col_type);
                for ($i = 41;$i <= 46;$i++) {
                    if (isset($arr_columns[$i])) {
                        if ($arr_columns[$i]['relate'] == $row['row_type']) {
                            $str = $main->purge_chars(chr($row['row_type'] + 64) . $relate . ":" . $row[$relate_column], false);
                            $state_column = $main->pad("c", $i);
                            $arr_state[$state_column] = $str;
                        }
                    }
                }
            }
        }
        elseif ($main->button(2, "bb_queue")) {
            $row_type = $arr_state['row_type'];
            $row_join = $arr_state['row_join'];
            $post_key = $arr_state['post_key'];

            $arr_columns = $main->columns($con, $row_join);
            $arr_dropdowns = $main->dropdowns($con, $row_join);
            $delimiter = $main->get_constant('BB_MULTISELECT_DELIMITER', ",");

            foreach ($arr_columns as $key => $value) {
                $col = $main->pad("c", $key);
                if ($main->full($col, $submit)) {
                    $str = $main->post($col, $submit, "");
                    if (in_array($key, $arr_notes)) {
                        $str = $main->purge_chars($str, false);
                    }
                    elseif (in_array($key, $arr_file)) {
                        // do nothing
                        
                    }
                    elseif (isset($arr_dropdowns[$key])) {
                        if ($arr_dropdowns[$key]['multiselect']) {
                            // will be an array
                            $str = explode($delimiter, $str);
                            $str = array_map(array($main, "purge_chars"), $textarea);
                        }
                        else {
                            $str = $main->purge_chars($str);
                        }
                    }
                    else {
                        $str = $main->purge_chars($str);
                    }
                    $main->set($col, $arr_state, $str);
                }
            }
        }
        elseif ($main->button(2)) {
            // clear form
            // reset state
            $arr_state = array();
            // reset to default row_type
            // set some state vars
            $row_type = $main->set("row_type", $arr_state, 0);
            $row_join = $main->set("row_join", $arr_state, $default_row_type);
            $post_key = $main->set("post_key", $arr_state, 0);
            /* END CLEAR FORM BUTTON */
        }

        /* SELECT COMBO CHANGE CLEAR */
        // basically reset form, combo change through javascript
        elseif ($main->button(3)) {
            // get row_type from combo box
            $arr_state = array();
            $row_type = $main->set('row_type', $arr_state, 0);
            $row_join = $main->process('row_join', $module, $arr_state, $default_row_type);
            $post_key = $main->set('post_key', $arr_state, 0);
        } /* END SELECT COMBO CHANGE CLEAR */

        // $main->button(4) reserved for autoload
        /* TEXTAREA LOAD */
        // textarea load gets the populated values only, keep values in state
        elseif ($main->button(5)) {
            $row_type = $arr_state['row_type'];
            $row_join = $arr_state['row_join'];
            $post_key = $arr_state['post_key'];

            $arr_columns = $main->columns($con, $row_join);
            $arr_dropdowns = $main->dropdowns($con, $row_join);
            $delimiter = $main->get_constant('BB_MULTISELECT_DELIMITER', ",");

            $str_textarea = $main->post('input_textarea', $module, "");
            $arr_textarea = preg_split("/\r\n|\n|\r/", $str_textarea);

            // load textarea into xml, textarea and queue field mutually exclusive
            $i = 0;

            foreach ($arr_columns as $key => $value) {
                $col = $main->pad("c", $key);
                $textarea = isset($arr_textarea[$i]) ? trim($arr_textarea[$i]) : "";
                if ($textarea != "") {
                    if (in_array($key, $arr_notes)) {
                        $str = $main->purge_chars($textarea, false);
                    }
                    elseif (in_array($key, $arr_file)) {
                        // do nothing
                        
                    }
                    elseif (isset($arr_dropdowns[$key])) {
                        if ($arr_dropdowns[$key]['multiselect']) {
                            // will be an array
                            $str = explode($delimiter, $textarea);
                            $str = array_map(array($main, "purge_chars"), $textarea);
                        }
                        else {
                            $str = $main->purge_chars($textarea);
                        }
                    }
                    else {
                        $str = $main->purge_chars($textarea);
                    }
                    $main->set($col, $arr_state, $str);
                    $str = "";
                }
                $i++;
            }
        }

        // enter the tab without link or relate
        else {
            // once layout is had
            $row_type = $main->process('row_type', $module, $arr_state, 0);
            $row_join = $main->process('row_join', $module, $arr_state, $default_row_type);
            $post_key = $main->process('post_key', $module, $arr_state, 0);
        }
    }

endif;
?>