<?php
/* NO HTML OUTPUT */
define('BASE_CHECK', true);
// need DB_NAME from bb_config, must not have html output including blank spaces
include("bb-config/bb_config.php"); // need DB_NAME

session_name(DB_NAME);
session_start();

$_SESSION['button'] = isset($_POST['bb_button']) ? $_POST['bb_button'] : 0;
$_SESSION['module'] = $_POST['bb_module'];
$_SESSION['submit'] = $_POST['bb_submit'];


//sets the module and submit
if ($module == "0_bb_logout")
    {
    //logout and change interface/userrole could be on different or many pages
    //check for session poisoning, userroles string should not be altered
    //$userroles variable should be protected and not used or altered anywhere
    // non-integer or empty usertype will convert to 0
    if (((int)$usertype <> 0) && in_array($_POST['bb_userrole'], explode(",",$_SESSION['userroles'])))
        {
        $_SESSION['userrole'] = $_POST['bb_userrole']; 
        $index_path = "Location: " . dirname($_SERVER['PHP_SELF']);
        header($index_path);
        die(); //important to stop script
        }
    //if logout, destroy session and force index, invalid $userrrole or $usertpye
    else
        {
        session_destroy();
        $index_path = "Location: " . dirname($_SERVER['PHP_SELF']);
        header($index_path);
        die(); //important to stop script
        }
    }

$keeper = $_SESSION['keeper'];

$arraydata['post'] = $_POST;
$jsondata = json_encode($arraydata);

$con_string = "host=" . DB_HOST . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASSWORD;
$con = pg_connect($con_string);
$query = "UPDATE state_table SET jsondata = $1 WHERE id = " . $keeper . ";";
pg_query_params($con, $query, array($jsondata));

$index_path = "Location: " . dirname($_SERVER['PHP_SELF']);
//$index_path = "Location: " . dirname($_SERVER['PHP_SELF']) . "?i=" . time();
header($index_path);
?>