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
	include("bb-utilities/bb_post.php");		
    
    $work = new bb_post();

    $_SESSION['button'] = isset($_POST['bb_button']) ? $_POST['bb_button'] : 0;
    $_SESSION['module'] = $_POST['bb_module'];
    $_SESSION['submit'] = $_POST['bb_submit'];
    
    $module = $_POST['bb_module'];
    if ($_POST['bb_userrole'] <> "")  $_SESSION['userrole'] = $_POST['bb_userrole'];
    $slug = $_POST['bb_slug'];
    $keeper = $_SESSION['keeper'];    
    
    //get the actual querystring
    if (!empty($_GET))
        {
        $arrayget = array();
        foreach ($_GET as $var)
            {
            if ($work->check($var, $module))
                {
                $value = $work->post($var, $module);
                $push = $var . "=" . $value;
                array_push($arrayget, $var . "="  . $value);
                }
            }
        }
        
    /* YOU HAVE TO MAKE THE STATE RULE SOMEWHERE */
    // pockback rely on get, change tabs update state    
    if (($_POST['bb_module'] == $_POST['bb_submit']) && ($work->blank($querystring)))
        {
        $querystring = "?" . implode("&", $arrayget);
        }
    else 
        {
        $arraydata['post'] = $_POST;
        $jsondata = json_encode($arraydata);
    
        $con_string = "host=" . DB_HOST . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASSWORD;
        $con = pg_connect($con_string);
        $query = "UPDATE state_table SET jsondata = $1 WHERE id = " . $keeper . ";";
        pg_query_params($con, $query, array($jsondata));
        }
    
    $index_path = "Location: " . dirname($_SERVER['PHP_SELF'])  . "/" . $slug . $querystring;
    header($index_path);
    die();
endif;
?>