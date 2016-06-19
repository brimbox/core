<?php
/*
 * Copyright (C) Kermit Will Richardson, Brimbox LLC
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

// cause no cache -- important for security
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// constant to verify include files are not accessed directly
define('BASE_CHECK', true);
// need DB_NAME from bb_config, must not have html output including blank spaces
include_once ("bb-config/bb_config.php");

// need db name
// start session based on db name
session_name(DB_NAME);
session_start();

if (isset($_SESSION['username'])): /* START IF, IF (logged in) THEN (controller) ELSE (login) END */

    // other vars are all disposed of with unset
    /* SESSION/CONTROLLER VARS */
    // set by login
    $username = $_SESSION['username'];
    $email = $_SESSION['email'];
    $timeout = $_SESSION['timeout']; // not implemented, for session timout
    $userrole = $_SESSION['userrole']; // string containing userrole and interface
    $userroles = $_SESSION['userroles']; // comma separated string careful with userroles session, used to check for valid userrole
    $archive = $_SESSION['archive']; // archive state
    $keeper = $_SESSION['keeper']; // state_table id
    // get interface from userrole
    list(, $interface) = explode("_", $_SESSION['userrole'], 2);
    $_SESSION['interface'] = $interface;

    // set by post.php
    // the current module
    $module = isset($_SESSION['module']) ? $_SESSION['module'] : "";
    // the previous module where the form is submitted
    $submit = isset($_SESSION['submit']) ? $_SESSION['submit'] : "";
    // the current button, 0 default
    $button = isset($_SESSION['button']) ? $_SESSION['button'] : 0;
    // the current module id, used in the database state data variable array
    // get slug split on last forward slash
    list($webpath, $slug) = preg_split("/[\/](?=[^\/]*$)/", parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 2);
    // index & slug, slug cannot contain forward slashes
    // index is the absolute path to the root index file/controller (not including host or protocol)
    $_SESSION['webpath'] = $webpath;
    // slug is a CMS slug, not really a directory, file, or normal part of a url
    $_SESSION['slug'] = $slug;
    // the absolute path to the index file no trailing forward slash, used for includes
    $_SESSION['abspath'] = $abspath = dirname(__FILE__);
    // note: $index and dir do not contain trailing forward slash
    // $path controller file path global
    // $type controller module type global
    /* END SESSION/CONTROLLER VARS */

    //include version information
    include_once ("bb-utilities/bb_version.php");

    // logout algorithm used for interface and userrole change
    // verfiy userrole with login directed userroles
    if (isset($_SESSION['userrole']) && in_array($_SESSION['userrole'], explode(",", $_SESSION['userroles']))):

        /* LOGOUT */
        // if logout, destroy session and force index
        if ($module == "bb_logout") {
            session_destroy();
            $index_path = "Location: " . dirname($_SERVER['PHP_SELF']);
            header($index_path);
            die(); // important to stop script
            
        }

        /* INCLUDE CONSTANTS */
        include_once ("bb-config/bb_constants.php");

        /* SET TIME ZONE */
        date_default_timezone_set(USER_TIMEZONE);

        /* SET UP MAIN OBJECT */
        // objects are all daisy chained together
        // set up main from last object chained
        // main object is fully static
        // variable $main appears as global
        // if you want extend main you can
        if (file_exists("bb-extend/bb_include_main_class.php")) include_once ("bb-extend/bb_include_main_class.php");
        else include_once ("bb-blocks/bb_include_main_class.php");

        // main instance
        $main = new bb_main();

        /* GET DATABASE CONNECTION */
        // get standard connection
        $con = $main->connect();

        /* CHECK IF USER IS LOCKED */
        // die if locked user
        $main->locked($con, $username, $userrole);

        /* GET $POST for userrole switch */
        $POST = $main->retrieve($con);

        //change userroles on the fly
        //relies on session userroles
        //change only change userroles if in $_SESSION['userroles']
        if (!$main->blank($POST['bb_userrole'])) {
            if (in_array($POST['bb_userrole'], explode(",", $_SESSION['userroles']))) {
                $userrole = $_SESSION['userrole'] = $POST['bb_userrole'];
                // get interface from userrole
                list(, $interface) = explode("_", $_SESSION['userrole'], 2);
                $_SESSION['interface'] = $interface;
            }
            else {
                //bad userrole, could hack into
                session_destroy();
                $index_path = "Location: " . dirname($_SERVER['PHP_SELF']);
                header($index_path);
                die();
            }
        }

        /* GET HEADER AND GLOBAL ARRAYS */
        /* UNPACK ARRAY INTO SEPARATE UNITS */
        if (file_exists("bb-extend/bb_parse_globals.php")) include_once ("bb-extend/bb_parse_globals.php");
        else include_once ("bb-blocks/bb_parse_globals.php");

        /* RECONCILE SLUG AND MODULE */
        // get module types for current user and interface
        $module_types = array(0);
        foreach ($array_interface[$interface] as $key => $value) {
            if (in_array($userrole, $value['userroles'])) {
                // (int) cast for security
                array_push($module_types, $key);
            }
        };
        // get modules type into string for query
        $module_types = implode(",", array_unique($module_types));

        /* RECONCILE SLUG AND MODULE USING SQL */
        $_SESSION['pretty_slugs'] = $pretty_slugs = $main->get_constant('BB_PRETTY_SLUGS', 0);
        // get slug and module, in order of precedence, 1 good slug and module, 2 good slug (back button), 3 empty slug (on login)
        if ($pretty_slugs == 1) {
            //pretty slugs on for standard install
            $query = "SELECT id, module_name, module_slug FROM
                           (SELECT 1 as id, module_name, replace(substr(module_name, strpos(module_name, '_') + 1), '_', '-') as module_slug, module_type, module_order FROM modules_table
                            WHERE module_type IN (" . $module_types . ") AND replace(substr(module_name, strpos(module_name, '_') + 1), '_', '-') = '" . pg_escape_string($slug) . "' AND module_name = '" . pg_escape_string($module) . "' AND interface = '" . pg_escape_string($interface) . "'
                      UNION ALL
                            SELECT 2 as id, module_name, replace(substr(module_name, strpos(module_name, '_') + 1), '_', '-') as module_slug, module_type, module_order FROM modules_table
                            WHERE module_type IN (" . $module_types . ") AND replace(substr(module_name, strpos(module_name, '_') + 1), '_', '-') = '" . pg_escape_string($slug) . "' AND interface = '" . pg_escape_string($interface) . "'
                      UNION ALL
                            SELECT 3 as id, module_name, replace(substr(module_name, strpos(module_name, '_') + 1), '_', '-') as module_slug, module_type, module_order FROM modules_table
                            WHERE module_type IN (" . $module_types . ") AND interface = '" . pg_escape_string($interface) . "') T1
                      ORDER BY id, module_type = 0 ASC, module_type ASC, module_order LIMIT 1";
        }
        else {
            //default module name
            $query = "SELECT id, module_name, module_slug FROM
                           (SELECT 1 as id, module_name, module_name as module_slug, module_type, module_order FROM modules_table
                            WHERE module_type IN (" . $module_types . ") AND module_name = '" . pg_escape_string($slug) . "' AND module_name = '" . pg_escape_string($module) . "' AND interface = '" . pg_escape_string($interface) . "'
                      UNION ALL
                            SELECT 2 as id, module_name, module_name as module_slug, module_type, module_order FROM modules_table
                            WHERE module_type IN (" . $module_types . ") AND  module_name = '" . pg_escape_string($slug) . "' AND interface = '" . pg_escape_string($interface) . "'
                      UNION ALL
                            SELECT 3 as id, module_name, module_name as module_slug, module_type, module_order FROM modules_table
                            WHERE module_type IN (" . $module_types . ") AND interface = '" . pg_escape_string($interface) . "') T1
                      ORDER BY id, module_type = 0 ASC, module_type ASC, module_order LIMIT 1";
        }
        $result = $main->query($con, $query);
        $row = pg_fetch_array($result);
        $module = $_SESSION['module'] = $row['module_name'];
        $slug = $_SESSION['slug'] = $row['module_slug'];

        /* REDIRECT WITH DEFAULT SLUG AND MODULE ON LOGIN */
        // this redirect has to happen after global array and hooks are loaded
        if (in_array($row['id'], array(2, 3))) {
            $index_path = "Location: " . dirname($_SERVER['PHP_SELF']) . "/" . $slug;
            // 302 redirect
            header($index_path);
        }

        // cleanup
        unset($key, $value, $module_types, $query, $result, $row, $index_path);

        /* SET UP HOT STATE */
        // hot state based in $interface
        // one hot state per user/module
        $main->hook("index_hot_state");

        /* CONTROLLER INCLUDE */
        // a custom controller will contain several standard include files, bb_javascript, bb_css, bb_less if desired
        include_once ($abspath . $array_header[$interface]['controller']);

    else:

        //bad userrole, unlikely
        session_destroy();
        $index_path = "Location: " . dirname($_SERVER['PHP_SELF']);
        header($index_path);
        die(); //stop script
        
    endif;

else: /* MIDDLE ELSE, IF (logged in) THEN (controller) ELSE (login) END */

    /* LOGIN SECTION */
    /* INCLUDES THE LOGIN PHP VERIFICATION */
    if (file_exists("bb-extend/bb_verify_login.php")) include_once ("bb-extend/bb_verify_login.php");
    else include_once ("bb-blocks/bb_verify_login.php");

    /* INCLUDE LOGIN CSS AND HTML FOR THE MOST PART */
    if (file_exists("bb-extend/bb_login_form.php")) include_once ("bb-extend/bb_login_form.php");
    else include_once ("bb-blocks/bb_login_form.php");

    /* END LOGIN SECTION */

endif; /* ENDIF, IF (controller) ELSE (login) */

?>


