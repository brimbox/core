<?php
/* NO HTML OUTPUT */
define('BASE_CHECK', true);
// need DB_NAME from bb_config, must not have html output including blank spaces
include("bb-config/bb_config.php"); // need DB_NAME

session_name(DB_NAME);
session_start();

if (isset($_SESSION['username'])):
    
    //needed for this algorythm
    $webpath = $_SESSION['webpath'];
    $keeper = $_SESSION['keeper'];

    //dela with module variables the post
    $_SESSION['button'] = $button = isset($_POST['bb_button']) ? $_POST['bb_button'] : 0;
    $_SESSION['module'] = $module = $_POST['bb_module'];
    $_SESSION['slug'] = $slug = $_POST['bb_slug'];
    $_SESSION['submit'] = $submit = $_POST['bb_submit'];
    if (($_POST['bb_userrole'] <> "")  && in_array($_POST['bb_userrole'], explode(",", $_SESSION['userroles'])))
        $_SESSION['userrole'] = $_POST['bb_userrole'];  //double checked when build->locked is call in index
    
    //include build object
    include_once("bb-utilities/bb_build.php");
    //build object for hooks
    $build = new bb_build();
    //need connection
    $con = $build->connect();
    
    $POST = $_POST;
    
    $postdata = json_encode($POST);
    
    //set $_POST for $POST
    $query = "UPDATE state_table SET postdata = $1 WHERE id = " . $keeper . ";";
    pg_query_params($con, $query, array($postdata));
    
    //REDIRECT
    header("Location: " . $webpath . "/" . $slug);
    
    die();
    
endif;
?>