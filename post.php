<?php
/* NO HTML OUTPUT */
define('BASE_CHECK', true);
// need DB_NAME from bb_config, must not have html output including blank spaces
include("bb-config/bb_config.php"); // need DB_NAME

session_name(DB_NAME);
session_start();

if (isset($_SESSION['username'])):

	include("bb-utilities/bb_database.php");
	//contains bb_validation class, extend bb_links
	include("bb-utilities/bb_validate.php");
	//contains bb_report class, extend bb_work
	include("bb-utilities/bb_hooks.php");
	//contains bb_work class, extends bb_forms
	include("bb-utilities/bb_work.php");		
    
    $work = new bb_work();    
    $con = $work->connect();

    $_SESSION['button'] = isset($_POST['bb_button']) ? $_POST['bb_button'] : 0;
    $_SESSION['module'] = $module = $_POST['bb_module'];
    $_SESSION['submit'] = $submit = $_POST['bb_submit'];
    if ($_POST['bb_userrole'] <> "")  $_SESSION['userrole'] = $_POST['bb_userrole'];
        
    $keeper = $_SESSION['keeper'];
    
    $POST = $_POST;
    $postdata = json_encode($POST);
    
    //need both slug and saver
    $query = "SELECT id, module_name, module_slug FROM modules_table WHERE module_name IN ('" . $module . "');";
    $result = pg_query($con, $query);
    $row = pg_fetch_array($result);
    $slug = $row['module_slug'];
    $_SESSION['saver'] = $row['id'];
    
    //set $_POST for $POST
    $query = "UPDATE state_table SET postdata = $1 WHERE id = " . $keeper . ";";
    pg_query_params($con, $query, array($postdata));
    
    //REDIRECT
    $index_path = "Location: " . dirname($_SERVER['PHP_SELF'])  . "/" . $slug;
    header($index_path);
    die();
endif;
?>