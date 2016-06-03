<?php
/*
 * Copyright (C) Brimbox LLC
 *
 * This program is free software: you can redistribute it and/or modifyit under the terms of the GNU
 * General Public License Version 3 (“GNU GPL v3”) as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
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

    // state area
    $arr_messages = array();

    $arr_state = $main->load($con, "bb_upload_docs");
    $update_id = $main->process("update_id", $submit, $arr_state, 0);
    $delete_id = $main->post("delete_id", $submit, 0);

    // insert file
    if ($main->button(1)) {
        if (is_uploaded_file($_FILES[$main->name('upload_file', $submit) ]["tmp_name"])) {
            $filename = $_FILES[$main->name('upload_file', $submit) ]["name"];
            $filedata = str_replace(array("\\\\", "''"), array("\\", "'"), pg_escape_bytea(file_get_contents($_FILES[$main->name('upload_file', $submit) ]["tmp_name"])));
            $query = "INSERT INTO docs_table (document, filename, username, level) " . "SELECT $1, $2, $3, $4 WHERE NOT EXISTS (SELECT 1 FROM docs_table WHERE filename = '" . pg_escape_string($filename) . "')";
            $arr_params = array($filedata, $filename, $username, 0);
            $result = $main->query_params($con, $query, $arr_params);
            if (pg_affected_rows($result) == 1) {
                array_push($arr_messages, "Document has been stored.");
            }
            else {
                array_push($arr_messages, "Error: Document not stored. Possible duplicate file name.");
            }
        }
        else {
            array_push($arr_messages, "Error: Must provide file to be uploaded.");
        }
    }

    // update file
    if ($main->button(2)) {
        if (is_uploaded_file($_FILES[$main->name('upload_file', $submit) ]["tmp_name"])) {
            $filename = $_FILES[$main->name('upload_file', $submit) ]["name"];
            $filedata = str_replace(array("\\\\", "''"), array("\\", "'"), pg_escape_bytea(file_get_contents($_FILES[$main->name('upload_file', $submit) ]["tmp_name"])));
            // update file
            if ($update_id) {
                $query = "UPDATE docs_table SET document = $1, filename = $2, username = $3 " . "WHERE id = " . $update_id . " AND EXISTS (SELECT 1 FROM docs_table WHERE id = " . $update_id . ");";
                $arr_params = array($filedata, $filename, $username);
                $result = $main->query_params($con, $query, $arr_params);
                if (pg_affected_rows($result) == 1) {
                    array_push($arr_messages, "Document has been updated.");
                }
            }
            else {
                array_push($arr_messages, "Error: Must specify file to be updated.");
            }
        }
        else {
            array_push($arr_messages, "Error: Must provide file to be uploaded.");
        }
    }

    // delete_file
    if ($main->button(3)) {
        if ($delete_id > 0) {
            $query = "DELETE FROM docs_table WHERE id = " . $delete_id . ";";
            $main->query($con, $query);
            array_push($arr_messages, "Document has been deleted.");
        }
        else {
            array_push($arr_messages, "Error: Unable to delete.");
        }
    }

    $main->set('arr_messages', $arr_state, $arr_messages);
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