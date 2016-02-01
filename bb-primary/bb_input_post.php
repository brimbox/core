<?php
/*
Copyright (C) 2012 - 2015  Kermit Will Richardson, Brimbox LLC

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License Version 3 (�GNU GPL v3�)
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
/* NO HTML OUTPUT */
define('BASE_CHECK', true);
// need DB_NAME from bb_config, must not have html output including blank spaces
include("../bb-config/bb_config.php"); // need DB_NAME

session_name(DB_NAME);
session_start();

if (isset($_SESSION['username'])):    

    //deal with stored $_SESSION stuff
    $interface =  $_SESSION['interface'];
    $usertype = $_SESSION['usertype'];
    $abspath =  $_SESSION['abspath'];
    $webpath =  $_SESSION['webpath'];    
    $keeper = $_SESSION['keeper'];
    $username = $_SESSION['username'];
        
    //standard $_SESSION post stuff
    $_SESSION['module'] = $module = $_POST['bb_module'];
    $_SESSION['slug'] = $slug = $_POST['bb_slug'];
    $_SESSION['submit'] = $submit = $_POST['bb_submit'];
    $_SESSION['button'] = $button = isset($_POST['bb_button']) ? $_POST['bb_button'] : 0;
    if (($_POST['bb_userrole'] <> "")  && in_array($_POST['bb_userrole'], explode($_SESSION['userroles'])))
        $_SESSION['userrole'] = $_POST['bb_userrole'];  //double checked when build->locked is call in index
    
    //constants include -- some constants are used
    include($abspath . "/bb-config/bb_constants.php");
    //include build class object
    include($abspath . "/bb-utilities/bb_build.php");
    //get build
    $build = new bb_build();
    
    //get connection
    $con = $build->connect();    
     
    //parse global arrays  
    $build->loader($con, $interface);
    
    //include main class
    $build->hook("bb_input_redirect_main_class");
    //get main instance
    $build->hook("bb_input_redirect_return_main");
    
    /* GET STATE AND $POST */
    $POST = $_POST;
   
    //get $arr_state
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
    //include codeblock
    
    //hook to alter codeblock
    $build->hook("bb_input_redirect_postback");
             
    //submit to database        
    if ($main->button(1))
        {
        //hook to alter codeblock
        $build->hook("bb_input_data_table_row_validate");
        
        //hook to alter codeblock
        $build->hook("bb_input_data_table_row_input");
        }
        
    /* END SUBMIT TO DATABASE */
        
    /* AUTOLOAD HOOK */    
    if ($main->button(4))
        {
        //not used by default
        //$filepath = $build->filter("bb_db_database_autoload");
        include_once($filepath);    
        }        
    /* END AUTOLOAD HOOK */
    
    /* UPDATE arr_state */
    //save state, note $submit instead of $module
    //state should be passed on to next code block
    $main->update($con, $submit, $arr_state);
    
    //SET $_POST for $POST
    $postdata = json_encode($_POST);
    $query = "UPDATE state_table SET postdata = $1 WHERE id = " . $keeper . ";";
    pg_query_params($con, $query, array($postdata));
    /* END UPDATE DATABASE WITH POST STUFF */
    
    /* REDIRECT */
    
    //dirname twice to go up one level, very important for custom posts
    $index_path = "Location: " . $webpath  . "/" . $slug;
    header($index_path);
    die();
    
    /* END REDIRECT */
endif;
?>