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

if (isset($_SESSION['username']) && in_array($_SESSION['userrole'], array("3_bb_brimbox", "4_bb_brimbox", "5_bb_brimbox"))):

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

    /* GET STATE AND $POST */
    $POST = $_POST;

    /*
     * IF $row_type = $row_join THEN
     * Use $row_join -- on Edit
    */
    /*
     * ELSE $row_join is the child
     * So again use $row_join -- on Insert
    */

    // get $arr_state
    $arr_state = $main->load($con, $submit);

    /* DEAL WITH CONSTANTS */
    $input_insert_log = $main->on_constant('BB_INPUT_INSERT_LOG');
    $input_update_log = $main->on_constant('BB_INPUT_UPDATE_LOG');
    $input_secure_post = $main->on_constant('BB_INPUT_SECURE_POST');
    $input_archive_post = $main->on_constant('BB_INPUT_ARCHIVE_POST');

    $maxinput = $main->get_constant('BB_STANDARD_LENGTH', 255);
    $maxnote = $main->get_constant('BB_NOTE_LENGTH', 65536);
    $delimiter = $main->get_constant('BB_MULTISELECT_DELIMITER', ",");
    /* END DEAL WITH CONSTANTS */

    /* POSTBACK HOOK */
    // include codeblock
    // hook to alter codeblock
    $main->hook("bb_input_redirect_postback");

    // submit to database
    if ($main->button(1)) {
        // hook to alter codeblock
        $main->hook("bb_input_data_table_row_validate");

        // hook to alter codeblock
        $main->hook("bb_input_data_table_row_input");
    }

    /* END SUBMIT TO DATABASE */

    /* AUTOLOAD HOOK */
    if ($main->button(4)) {
        // not used by default
        // $filepath = $main->filter("bb_db_database_autoload");
        include_once ($filepath);
    }
    /* END AUTOLOAD HOOK */

    /* UPDATE arr_state */
    // save state, note $submit instead of $module
    // state should be passed on to next code block
    $main->update($con, $submit, $arr_state);

    // SET $_POST for $POST
    $postdata = json_encode($_POST);
    $query = "UPDATE state_table SET postdata = $1 WHERE id = " . $keeper . ";";
    pg_query_params($con, $query, array($postdata));
    /* END UPDATE DATABASE WITH POST STUFF */

    /* REDIRECT */

    // dirname twice to go up one level, very important for custom posts
    $index_path = "Location: " . $webpath . "/" . $slug;
    header($index_path);
    die();

else:

    header("Location: " . dirname(dirname($_SERVER['PHP_SELF'])));
    die();

    /* END REDIRECT */
endif;
?>