<?php
/*
 * Copyright (C) Brimbox LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License Version 3 (GNU GPL v3)
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

if (isset($_SESSION['username']) && in_array($_SESSION['userrole'], array("5_bb_brimbox"))):

    // set by controller (index.php)
    $interface = $_SESSION['interface'];
    $username = $_SESSION['username'];
    $userrole = $_SESSION['userrole'];
    $webpath = $_SESSION['webpath'];
    $keeper = $_SESSION['keeper'];
    $abspath = $_SESSION['abspath'];
    $pretty_slugs = $_SESSION['pretty_slugs'];

    // set by javascript submit form (bb_submit_form())
    $_SESSION['button'] = $button = isset($_POST['bb_button']) ? $_POST['bb_button'] : 0;
    $_SESSION['module'] = $module = isset($_POST['bb_module']) ? $_POST['bb_module'] : "";
    $_SESSION['submit'] = $submit = isset($_POST['bb_submit']) ? $_POST['bb_submit'] : "";

    // constants include -- some constants are used
    include_once ($abspath . "/bb-config/bb_constants.php");
    // include build class object
    if (file_exists($abspath . "/bb-extend/bb_include_main_class.php")) include_once ($abspath . "/bb-extend/bb_include_main_class.php");
    else include_once ($abspath . "/bb-blocks/bb_include_main_class.php");

    // $main object brought in
    $main = new bb_main();
    // need connection
    $con = $main->connect();
    // get slug once $main is set
    $_SESSION['slug'] = $slug = $main->pretty_slugs($module, $pretty_slugs);

    // load global arrays
    if (file_exists($abspath . "/bb-extend/bb_parse_globals.php")) include_once ($abspath . "/bb-extend/bb_parse_globals.php");
    else include_once ($abspath . "/bb-blocks/bb_parse_globals.php");

    /* GET STATE AND $POST */
    $POST = $_POST;

    // get $arr_state
    $arr_state = $main->load($con, $submit);

    $translation = $main->process('translation', $module, $arr_state, "");

    $arr_messages = array();

    /* END MODULE VARIABLES FOR OPTIONAL MODULE HEADERS */

    if ($main->button(1)) {
        if (is_uploaded_file($_FILES[$main->name("upload_translation", $module) ]["tmp_name"])) {
            $fp = fopen($_FILES[$main->name("upload_translation", $module) ]['tmp_name'], 'rb');
            while (($line = fgets($fp)) !== false) {
                $line = trim($line);
                if (preg_match("/^#/", $line)) {
                    $line = str_replace("#", " ", $line);
                    $arr_comment = array_filter(explode(" ", $line));
                    if ($key = array_search("bbpo", $arr_comment)) {
                        if (isset($arr_po)) {
                            $main->process_json($con, $translate . "_translate", $arr_po);
                        }
                        unset($msgid, $msgstr);
                        $module = $arr_comment[$key + 1];
                        $arr_po = $main->process_json($con, $translate . "_translate");
                    }
                }
                elseif (preg_match("/^msgid/", $line)) {
                    $arr_msgid = array_filter(explode(" ", $line));
                    $msgid = trim($arr_msgid[1], "\"");
                }
                elseif (isset($msgid) && preg_match("/^msgstr/", $line)) {
                    $arr_msgstr = array_filter(explode(" ", $line));
                    $msgstr = trim($arr_msgstr[1], "\"");
                }

                if (isset($module) && isset($msgid) && isset($msgstr)) {
                    $arr_po[$msgid] = $msgstr;
                    unset($msgid, $msgstr);
                }
            }
            if (isset($arr_po)) {
                $main->process_json($con, $translate . "_translate", $arr_po);
            }
        }
        else {
            $arr_messages[] = "Must specify file name.";
        }
    }

    if ($main->button(2)) {
        $addkey = $main->post('addkey', $module, "");
        $addvalue = $main->post('addvalue', $module, "");

        echo $translate;

        if (!$main->blank($addkey) && !$main->blank($addvalue) && !$main->blank($translation)) {
            $arr_po = $main->process_json($con, $translation . "_translate");
            $arr_po[$addkey] = $addvalue;
            $main->process_json($con, $translation . "_translate", $arr_po);
        }
    }

    /* END SET ORDER */

    $main->set('arr_messages', $arr_state, $arr_messages);

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

endif;
?>

