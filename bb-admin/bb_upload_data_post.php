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
/* NO HTML OUTPUT */
define('BASE_CHECK', true);
// need DB_NAME from bb_config, must not have html output including blank spaces
include ("../bb-config/bb_config.php"); // need DB_NAME
session_name(DB_NAME);
session_start();
session_regenerate_id();

if (isset($_SESSION['username']) && in_array($_SESSION['userrole'], array("4_bb_brimbox", "5_bb_brimbox"))):

    // set by controller (index.php)
    $interface = $_SESSION['interface'];
    $username = $_SESSION['username'];
    $userrole = $_SESSION['userrole'];
    $webpath = $_SESSION['webpath'];
    $keeper = $_SESSION['keeper'];
    $abspath = $_SESSION['abspath'];

    // set by javascript submit form (bb_submit_form())
    $_SESSION['button'] = $button = isset($_POST['bb_button']) ? $_POST['bb_button'] : 0;
    $_SESSION['module'] = $module = isset($_POST['bb_module']) ? $_POST['bb_module'] : "";
    if ($_SESSION['pretty_slugs'] == 1) {
        list(, $slug) = explode("_", $module, 2);
        $_SESSION['slug'] = $slug = str_replace("_", "-", $slug);
    }
    else {
        $_SESSION['slug'] = $slug = $module;
    }
    $_SESSION['submit'] = $submit = isset($_POST['bb_submit']) ? $_POST['bb_submit'] : "";

    /* SET UP WORK OBJECT AND POST STUFF */
    // objects are all daisy chained together
    // set up work from last object
    // contains bb_database class, extends bb_main
    // constants include -- some constants are used
    include_once ($abspath . "/bb-config/bb_constants.php");
    // include build class object
    if (file_exists($abspath . "/bb-extend/bb_include_main_class.php")) include_once ($abspath . "/bb-extend/bb_include_main_class.php");
    else include_once ($abspath . "/bb-blocks/bb_include_main_class.php");

    // main object for hooks
    $main = new bb_main();
    // need connection
    $con = $main->connect();

    // load global arrays
    if (file_exists($abspath . "/bb-extend/bb_parse_globals.php")) include_once ($abspath . "/bb-extend/bb_parse_globals.php");
    else include_once ($abspath . "/bb-blocks/bb_parse_globals.php");

    $POST = $_POST;

    $arr_state = $main->load($con, $submit);

    // initial values
    $arr_messages = array();
    $arr_relate = array(41, 42, 43, 44, 45, 46);
    $arr_file = array(47);
    $arr_reserved = array(48);
    $arr_notes = array(49, 50);

    // get layouts
    $layouts = $main->layouts($con);
    $default_row_type = $main->get_default_layout($layouts);
    // get guest index
    $arr_header = $main->get_json($con, "bb_interface_enabler");
    $arr_guest_index = $arr_header['guest_index']['value'];

    // will handle postback
    if ($main->changed('row_type', $submit, $arr_state, $default_row_type)) {
        $row_type = $main->process('row_type', $submit, $arr_state, $default_row_type);
        $data_area = "";
        $data_file = "default";
        $edit_or_insert = 0;
    }
    else {
        $row_type = $main->process('row_type', $submit, $arr_state, $default_row_type);
        $data_area = $main->process('data_area', $submit, $arr_state, "");
        $data_file = $main->process('data_file', $submit, $arr_state, "default");
        $edit_or_insert = $main->process('edit_or_insert', $submit, $arr_state, 0);
    }

    // get column names based on row_type/record types
    $parent_row_type = $layouts[$row_type]['parent']; // should be set
    // need unreduced column
    $arr_columns = $main->columns($con, $row_type);
    // get dropdowns for validation
    $arr_dropdowns = $main->dropdowns($con, $row_type);
    // get has_link
    $has_link = $parent_row_type || $edit_or_insert ? true : false;

    // button 1 -- get column names for layout
    if ($main->button(1)) {
        if (!$has_link) {
            $arr_implode = array();
        }
        else {
            $arr_implode = array("Link");
        }
        foreach ($arr_columns as $value) {
            array_push($arr_implode, $value['name']);
        }
        $data_area = implode("\t", $arr_implode) . PHP_EOL;
    }

    // button 2 -- submit_file
    if ($main->button(2)) {
        if (is_uploaded_file($_FILES[$main->name('upload_file', $submit) ]["tmp_name"])) {
            $data_area = file_get_contents($_FILES[$main->name('upload_file', $submit) ]["tmp_name"]);
        }
        else {
            array_push($arr_messages, "Error: Must specify file name.");
        }
    }

    /* $edit_or_insert */
    // 0 - INSERT
    // 1 - UPDATE POPULATED VALUES
    // button 3 -- post data to database
    if ($main->button(3)) {
        // $i is used to check header
        // $j is the number of rows of data, 0 is header row, 1 starts data
        // $k is the line item count
        // $l is is the item in the line
        $arr_lines = preg_split("/\r\n|\n|\r/", $data_area);
        $arr_lines = array_filter($arr_lines);
        $cnt_lines = count($arr_lines);

        // check header
        $check_header = true;
        $i = 0;
        $arr_row = explode("\t", trim($arr_lines[0]));
        if ($has_link) {
            // link corresponds to database id
            if (strcasecmp($arr_row[0], "Link")) {
                $check_header = false;
            }
            $i++;
        }
        foreach ($arr_columns as $value) {
            // there is a value to check
            if (isset($arr_row[$i])) {
                if (strcasecmp($value['name'], $arr_row[$i])) {
                    $check_header = false;
                    break;
                }
            }
            else {
                // no value to check
                $check_header = false;
                break;
            }
            $i++;
        }
        // end check header
        // determine $p
        $line_items = ($has_link) ? count($arr_columns) + 1 : count($arr_columns);
        /* End Check Header */

        // check header checks that the first line of data matches $xml_column
        // $arr_lines may need trim function
        if ($check_header) {
            // $inputted is count of rows entered
            // $not_validated is rows rejected on validation
            // $not_inputted is rows rejected on insert or update
            /* START LOOP */
            // loops through each row of data
            $arr_errors_all = $arr_messages_all = $arr_messages_grep = array();
            $inputted = $not_validated = $not_inputted = 0; // count of rows entered
            for ($j = 1;$j < $cnt_lines;$j++) {
                $arr_line = explode("\t", $arr_lines[$j]);
                for ($k = count($arr_line);$k < $line_items;$k++) {
                    // if a line is shorter than it is supposed to be
                    $arr_line[$i] = "";
                }

                // BUILD ARRAY TO PASS
                $arr_pass = array();
                // INSERT RECORDS
                if ($edit_or_insert == 0) {
                    if ($has_link) {
                        $arr_pass['row_type'] = $row_type;
                        $arr_pass['row_join'] = $parent_row_type;
                        // convert every non-integer to be zero
                        // zero will cause INSERT to fail
                        $arr_pass['post_key'] = ( int )$arr_line[0];
                        $l = 1;
                    }
                    else {
                        $arr_pass['row_type'] = $row_type;
                        $arr_pass['row_join'] = 0;
                        $arr_pass['post_key'] = - 1;
                        $l = 0;
                    }
                }
                else {
                    $l = 1;
                    $arr_pass['row_type'] = $row_type;
                    $arr_pass['row_join'] = $row_type;
                    $arr_pass['post_key'] = ( int )$arr_line[0];
                }

                /* ENFORCE UPLOAD POLICY */
                foreach ($arr_columns as $key => $value) {
                    $arr_line[$l] = isset($arr_line[$l]) ? $arr_line[$l] : "";
                    if (in_array($edit_or_insert, array(0, 2)) || !$main->blank($arr_line[$l])) {
                        $col = $main->pad("c", $key);
                        if (in_array($key, $arr_notes)) {
                            $arr_pass[$col] = $main->purge_chars($arr_line[$l], false);
                        }
                        elseif (in_array($key, $arr_file)) {
                            // not files
                            $arr_pass[$col] = "";
                        } // everthing else {
                        $arr_pass[$col] = $main->purge_chars($arr_line[$l]);
                    }
                    $l++;
                }

                /* DO VALIDATION */
                $main->hook("bb_upload_data_row_validation");

                if (count($arr_pass['arr_errors']) != 0) {
                    // FAILURE
                    $not_validated++;
                    foreach ($arr_pass['arr_errors'] as $value) {
                        array_push($arr_errors_all, $value);
                    }
                    $arr_errors_all = array_unique($arr_errors_all);
                }
                else {
                    /* DO INPUT */
                    $main->hook("bb_upload_data_row_input");

                    $arr_messages_grep = preg_grep("/^Error:/i", $arr_pass['arr_messages']);
                    if (count($arr_messages_grep) > 0) {
                        // FAILURE
                        $not_inputted++;
                        $arr_messages_all = array_unique($arr_messages_all + $arr_messages_grep);
                    }
                    else {
                        // SUCCESS
                        // remove line on success
                        unset($arr_lines[$j]);
                        $inputted++;
                    }
                }
            }
        }
        else {
            array_push($arr_messages, "Error: Header row does not match the column names of layout chosen.");
        }
        if (count($arr_lines) > 1) {
            $data_area = implode("\r\n", $arr_lines);
        }
        else {
            $data_area = "";
        }
    }

    // pass back values
    $main->set('arr_messages', $arr_state, $arr_messages);
    $main->set('arr_errors_all', $arr_state, $arr_errors_all);
    $main->set('arr_messages_all', $arr_state, $arr_messages_all);
    $main->set('data_area', $arr_state, $data_area);
    $main->set('data_stats', $arr_state, array('inputted' => $inputted, 'not_validated' => $not_validated, 'not_inputted' => $not_inputted));

    // update state, back to db
    $main->update($con, $submit, $arr_state);

    $postdata = json_encode($POST);

    // set $_POST for $POST
    $query = "UPDATE state_table SET postdata = $1 WHERE id = " . $keeper . ";";
    pg_query_params($con, $query, array($postdata));

    // REDIRECT
    $index_path = "Location: " . $webpath . "/" . $slug;
    header($index_path);
    die();

else:

    header("Location: " . dirname(dirname($_SERVER['PHP_SELF'])));
    die();

endif;
?>