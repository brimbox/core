<?php
/*
Copyright (C) 2012 - 2015 Kermit Will Richardson, Brimbox LLC

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

//cause no cache -- important for security

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// constant to verify include files are not accessed directly
define('BASE_CHECK', true);
// need DB_NAME from bb_config, must not have html output including blank spaces
include_once("bb-config/bb_config.php");

//need db name
// start session based on db name
session_name(DB_NAME);
session_start();
session_regenerate_id();

if (isset($_SESSION['username'])): /* START IF, IF (logged in) THEN (controller) ELSE (login) END */

//other vars are all disposed of with unset

/* SESSION/CONTROLLER VARS */
//set by login
$username = $_SESSION['username'];
$email = $_SESSION['email'];
$timeout = $_SESSION['timeout']; //not implemented, for session timout
$userrole = $_SESSION['userrole']; //string containing userrole and interface
$userroles = $_SESSION['userroles']; //comma separated string careful with userroles session, used to check for valid userrole
$archive = $_SESSION['archive']; //archive state
$keeper = $_SESSION['keeper']; //state_table id
//unpack usertype and interface
list($usertype, $interface) = explode("_", $_SESSION['userrole'], 2);
$_SESSION['interface'] = $interface;
$_SESSION['usertype'] = $usertype;

//set by post.php
//the current module
$module = isset($_SESSION['module']) ? $_SESSION['module'] : "";
//the previous module where the form is submitted
$submit = isset($_SESSION['submit']) ? $_SESSION['submit'] : "";
//the current button, 0 default
$button = isset($_SESSION['button']) ? $_SESSION['button'] : 0;
//the current module id, used in the database state data variable array

//get slug split on last forward slash
list($webpath, $slug) = preg_split("/[\/](?=[^\/]*$)/", parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 2);
//index & slug, slug cannot contain forward slashes
//index is the absolute path to the root index file/controller (not including host or protocol)
$_SESSION['webpath'] = $webpath;
//slug is a CMS slug, not really a directory, file, or normal part of a url
$slug = ($_SESSION['slug'] <> "") ? $slug : "";
//the absolute path to the index file no trailing forward slash, used for includes
$_SESSION['abspath'] = $abspath = dirname(__FILE__);
//note: $index and dir do not contain trailing forward slash

//$path controller file path global
//$type controller module type global

/* END SESSION/CONTROLLER VARS */

//logout algorithm used for interface and userrole change, userrole change and logout
if (isset($_SESSION['module']))
	{
    //sets the module and submit
	if ($module == "bb_logout")
		{
		//logout and change interface/userrole could be on different or many pages
		//check for session poisoning, userroles string should not be altered
		//$userroles variable should be protected and not used or altered anywhere
        // non-integer or empty usertype will convert to 0
		if (((int)$usertype <> 0) && in_array($_POST['bb_userrole'], explode(",", $_SESSION['userroles'])))
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
	}
    
/* INCLUDE CONSTANTS */    
include_once("bb-config/bb_constants.php");

/* INCLUDE BUILD OBJECT */    
include_once("bb-utilities/bb_build.php");
//build object for hooks
$build = new bb_build();

/* GET DATABASE CONNECTION */
//get standard connection
$con = $build->connect();

/* CHECK IF USER IS LOCKED */
//die if locked user
$build->locked($con, $username, $userrole);

/* GET HEADER AND GLOBAL ARRAYS */
/* load header array, addon functions, and global arrays */
//returns $array_header and by default all functions declared
/* also busts up $array_global, for current interface, by default */
//$array_actions
//$array_interface
//$array_common_variables
//$array_links
//$array_reports
$build->loader($con, $interface);

/* SET TIME ZONE */
date_default_timezone_set(USER_TIMEZONE);

/* SET UP MAIN OBJECT */
//objects are all daisy chained together
//set up main from last object chained
//main object is fully static
//variable $main appears as global
//if you want extend main you can
$build->hook("index_main_class"); //includes main classes
$build->hook("index_return_main"); //creates main instance

//this javascript necessary for the standard main class functions
//all other javascript function included in specific modules by default
$javascript = $webpath . "/bb-utilities/bb_scripts.js";
$javascript = $build->filter("index_main_javascript", $javascript, $params);
echo "<script src=\"" . $javascript . "\"></script>";
unset($javascript); //clean up

/* SET UP HOT STATE */
//hot state based in $interface
//one hot state per user/module
$build->hook("index_hot_state");

/* INCLUDE HACKS FILE LAST after all other bb-config includes */
//hacks file useful for debugging
include_once($abspath . "/bb-config/bb_admin_hacks.php");

/* CONTROLLER INCLUDE */
//a custom controller contains several standard include files
include_once($abspath . $array_header[$interface]['controller']);

else: /* MIDDLE ELSE, IF (logged in) THEN (controller) ELSE (login) END */

/* LOGIN SECTION */
//this for including a custom login screen
//in turn the login file includes custom login css
if (file_exists("verify.php"))
    {
    include_once("verify.php");   
    }
else
    {
    include_once("bb-utilities/bb_verify.php");
    }
/* END LOGIN SECTION */

endif; /* ENDIF, IF (controller) ELSE (login) */

?>


