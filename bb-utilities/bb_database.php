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

/* NO HTML OUTPUT */

/* PHP FUNCTIONS */
/* class bb_database() */
// connect
// query
// query_params
// get_mbox
// get_json
// update_json
// get_next_node
// full_text
// process_related
// unique_key
// relate_check
// relate_row_type
// relate_post_key
// relate_value
class bb_database extends bb_build {

    function connect() {
        // standard Brimbox connection
        $con_string = "host=" . DB_HOST . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASSWORD;
        $con = pg_connect($con_string);
        if (!$con) {
            die("Cannot connect to database.");
        }
        return $con;
    }

    function query($con, $query, $display = true) {
        // standard query will die on error
        @$result = pg_query($con, $query);
        if ($result === false) {
            if ($display) {
                $arr_messages = array(pg_last_error($con));
                $this->echo_messages($arr_messages);
            }
        }
        return $result;
    }

    function query_params($con, $query, $array, $display = true) {
        // standard query with placeholders, does not die on error
        // if you want it to die use pg_query_params
        @$result = pg_query_params($con, $query, $array);
        if ($result === false) {
            if ($display) {
                $arr_messages = array(pg_last_error($con));
                $this->echo_messages($arr_messages);
            }
        }
        return $result;
    }

    // get mailbox connection
    function get_mbox($mailserver, $username, $password) {
        // returns a mailbox connection
        $mbox = @imap_open($mailserver, $username, $password);

        return $mbox;
    }

    function get_json($con, $lookup) {
        // gets an xml object from the xml_table
        $query = "SELECT jsondata FROM json_table WHERE lookup IN ('" . pg_escape_string($lookup) . "');";

        $result = $this->query($con, $query);

        $row = pg_fetch_array($result);
        $json = $row['jsondata'];

        return json_decode($json, true);
    }

    function update_json($con, $arr, $lookup) {
        // update xml_table with a whole xml object
        $query = "UPDATE json_table SET jsondata = '" . pg_escape_string(json_encode($arr)) . "' WHERE lookup = '" . $lookup . "';";

        $this->query($con, $query);
    }

    function get_next_node($arr, $limit) {
        // finds the next available node in a set of numeric keys
        $arr_keys = array_keys($arr);
        sort($arr_keys);
        $k = 0; // initialize for first value
        $bool = false;
        foreach ($arr_keys as $i => $j) {
            $k = $i + 1; // $i starts at 0, $k start at 1
            if ($k != $j) {
                $bool = true; // insert value in middle
                break;
            }
        }
        if (!$bool) {
            $k = $k + 1; // insert value at end
            
        }
        if ($k > $limit) {
            return -1; // limit exceeded
            
        }
        else {
            return $k; // return value
            
        }
    }

    function full_text(&$arr_ts_vector_fts, &$arr_ts_vector_ftg, $value, $str, $arr_guest_index) {

        $str_esc = pg_escape_string($str);
        $search_flag = $value['search'];
        $secure_flag = $value['secure'];
        if (empty($arr_guest_index)) {
            $guest_flag = (($search_flag == 1) && ($secure_flag == 0)) ? true : false;
        }
        else {
            $guest_flag = (($search_flag == 1) && in_array($secure_flag, $arr_guest_index)) ? true : false;
        }
        // build fts SQL code
        if ($search_flag) {
            array_push($arr_ts_vector_fts, "'" . $str_esc . "' || ' ' || regexp_replace('" . $str_esc . "', E'(\\\\W)+', ' ', 'g')");
        }
        if ($guest_flag) {
            array_push($arr_ts_vector_ftg, "'" . $str_esc . "' || ' ' || regexp_replace('" . $str_esc . "', E'(\\\\W)+', ' ', 'g')");
        }
    }

    function process_related(&$arr_select_where, $arr_layouts_reduced, $value, $str) {
        // process related records/table
        if (isset($value)) {
            // set column part
            // proceed if not blank and relate is set
            if (!$this->blank($str) && ($value['relate'] > 0)) {
                // proper string, else bad
                if ($this->relate_check($str)) {
                    $row_type_relate = $this->relate_row_type($str);
                    $post_key_relate = $this->relate_post_key($str);
                    // proper row_type, else bad
                    // check related
                    if ($value['relate'] == $row_type_relate) {
                        // layout defined, else bad
                        if ($arr_layouts_reduced[$row_type_relate]['relate'] == 1) {
                            // good value
                            $arr_select_where[] = "(id = " . ( int )$post_key_relate . " AND row_type = " . ( int )$row_type_relate . ")";
                        }
                        else { // not properly defined
                            $arr_select_where[] = "(1 = 0)";
                        }
                    }
                    else {
                        $arr_select_where[] = "(1 = 0)";
                    }
                }
                else {
                    $arr_select_where[] = "(1 = 0)";
                }
            }
        }
    }

    function unique_key($edit_or_insert, &$select_where_not, $unique_key, $unique_value, $row_type, $post_key) {
        // Note -- empty string is allowed as single value, use required to eliminate the possibility of an empty string
        $select_where_not = "SELECT 1 WHERE 1 = 0";
        if ($unique_key) {
            // no key = 0
            $unique_column = $this->pad("c", $unique_key);
            // edit
            if ($edit_or_insert) {
                $select_where_not = "SELECT 1 FROM data_table WHERE row_type IN (" . $row_type . ") AND id NOT IN (" . $post_key . ") AND lower(" . $unique_column . ") IN (lower('" . $unique_value . "'))";
            }
            else {
                // insert
                $select_where_not = "SELECT 1 FROM data_table WHERE row_type IN (" . $row_type . ") AND lower(" . $unique_column . ") IN (lower('" . $unique_value . "'))";
            }
        }
    }

    // get the row_type from a related field
    function relate_check($related) {

        if (preg_match("/^[A-Z]\d+:.*/", $related)) {
            return true;
        }
        return false;
    }

    // get the row_type from a related field
    function relate_row_type($related) {

        return ord(substr($related, 0, 1)) - 64;
    }

    // get post_key or id from related field
    function relate_post_key($related) {

        return substr($related, 1, strpos($related, ":") - 1);
    }

    // gets primary string from related field
    function relate_value($related) {

        return substr($related, strpos($related, ":") + 1);
    }
} // end class

?>