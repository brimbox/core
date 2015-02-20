<?php
/*
Copyright (C) 2012 - 2015 Kermit Will Richardson, Brimbox LLC

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License Version 3 (“GNU GPL v3”)
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
include("bb-config/bb_config.php"); // need DB_NAME
//set PHP and User/Interface timezone based on bb_config

// start session based on db name
session_name(DB_NAME);
session_start();
session_regenerate_id();

/* START IF, IF (logged in) THEN (controller) ELSE (login) END */

if (isset($_SESSION['username'])):

/* RESERVED VARIABLES (used in controller)*/
//$userrole -- current security level string including the interface
//$userroles -- valid security permissions, comma delimited string
//$usertype -- current userrole integer without interface
//$interface -- current interface
//$email -- the current email/username
//$module -- the current module
//$con -- the connection to the database
//$array_master -- contains all the interface arrays
//$controller_path -- the path to the current module
//$controller_type -- the current module type
//$index_path - not unset because used in a header redirect

//$less -- LESS parser invocations
//$main -- Brimbox objects invoked

/* RESERVED OBJECTS */
//bb_main
//bb_database
//bb_links
//bb_validate
//bb_forms
//bb_work
//bb_hooks
//bb_reports
//bb_hooks

//lessc -- LESS comnpiler

//other vars are all disposed of with unset

//SESSION STUFF
$username = $_SESSION['username'];
$email = $_SESSION['email'];
$timeout = $_SESSION['timeout'];
$userrole = $_SESSION['userrole']; //string containing userrole and interface
$userroles = $_SESSION['userroles']; //comma separated string careful with userroles session, used to check for valid userrole
list($usertype, $interface) = explode("_", $_SESSION['userrole'], 2);
$archive = $_SESSION['archive']; //archive mode
//button post
$button = isset($_POST['bb_button']) ? $_POST['bb_button'] : 0; //global button

//logout algorithm used for interface change, userrole change and logout
if (isset($_POST['bb_module']))
	{
    //sets the module and submit
	$module = $_POST['bb_module'];
	$submit = $_POST['bb_submit'];
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
include("bb-config/bb_constants.php");
/* SET TIME ZONE */
date_default_timezone_set(USER_TIMEZONE);
	
/* INCLUDE ALL BRIMBOX STANDARD FUNCTIONS */
//contains bb_main class
include("bb-utilities/bb_main.php");
// contains bb_database class, extends bb_main
include("bb-utilities/bb_database.php");
// contains bb_links class, extends bb_database
include("bb-utilities/bb_links.php");
//contains bb_validation class, extend bb_links
include("bb-utilities/bb_validate.php");
//contains bb_form class, extends bb_validate
include("bb-utilities/bb_forms.php");
//contains bb_work class, extends bb_forms
include("bb-utilities/bb_work.php");
//contains bb_report class, extend bb_work
include("bb-utilities/bb_hooks.php");
//contains bb_report class, extend bb_hooks
include("bb-utilities/bb_reports.php");

/* SET UP MAIN OBJECT */
//objects are all daisy chained together
//set up main from last object
$main = new bb_reports();

/* GET DATABASE CONNECTION */
//database connection passed into modules globally
$con = $main->connect();

/* USER LOCKED OR DELETED */
//once $con is set check live whether user is locked or deleted
//0_bb_brimbox is only locked userrole, for active lock
$arr_work['locked'] = "SELECT id FROM users_table WHERE username = '" . pg_escape_string($username) . "' AND NOT '0_bb_brimbox' = ANY (userroles);";
$result = pg_query($con, $arr_work['locked']);
if (pg_num_rows($result) <> 1)
    {
    session_destroy();
	$index_path = "Location: " . dirname($_SERVER['PHP_SELF']);
	header($index_path);
	die(); //important to stop script    
    }
    
//NOTE: file_exists checked before header and module includes, therefore no missing file errors allowed

/* INCLUDE HEADER FILES */
//do not want controller to bomb
//global for all interfaces
include("bb-utilities/bb_headers.php");
$arr_work['headers'] = "SELECT module_path FROM modules_table WHERE standard_module IN (0,4,6) AND module_type IN (-3) ORDER BY module_order;";
$result = pg_query($con, $arr_work['headers']);
while($row = pg_fetch_array($result))
    {
    //will ignore file that does not exists so can debug by deleting file
    //checking syntax would be too much overhead
    if (file_exists($row['module_path'])) include($row['module_path']);
    }
/* ADHOC HEADERS */
if (file_exists("bb-config/bb_admin_headers.php")) include("bb-config/bb_admin_headers.php");

/* DO FUNCTION MODULES */
//only for interface being loaded
$arr_work['functions'] = "SELECT module_path FROM modules_table WHERE interface IN ('" . pg_escape_string($interface) . "') AND standard_module IN (0,4,6) AND module_type IN (-2) ORDER BY module_order;";
$result = pg_query($con, $arr_work['functions']);
while($row = pg_fetch_array($result))
    {
    //will ignore file that does not exists so can debug by deleting file
    //checking syntax would be too much overhead
    if (file_exists($row['module_path'])) include($row['module_path']);
    }
/* ADHOC FUNCTIONS */
//will ignore file if missing
if (file_exists("bb-config/bb_admin_functions.php")) include("bb-config/bb_admin_functions.php");

/* DO GLOBAL MODULES */
//only for interface being loaded
include("bb-utilities/bb_globals.php");
$arr_work['globals'] = "SELECT module_path FROM modules_table WHERE  interface IN ('" . pg_escape_string($interface) . "') AND standard_module IN (0,4,6) AND module_type IN (-1) ORDER BY module_order;";
$result = pg_query($con, $arr_work['globals']);
while($row = pg_fetch_array($result))
    {
    //will ignore file that does not exists so can debug by deleting file
    //checking syntax would be too much overhead
    if (file_exists($row['module_path'])) include($row['module_path']);
    }
/* ADHOC GLOBALS */
//will ignore file if missing
if (file_exists("bb-config/bb_admin_globals.php")) include("bb-config/bb_admin_globals.php");

/* UNPACK $array_global for given interface */
//will overwrite existing arrays
if (isset($array_global))
    {
    foreach($array_global[$interface] as $key => $value)
        {
        ${'array_' . $key} = $value;
        }
    }
?>
<?php /* START HTML OUTPUT */ ?>
<!DOCTYPE html>    
<html>
<head>    
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<?php /* JAVASCRIPT AND CSS INCLUDES */ ?>
<script type="text/javascript" src="bb-utilities/bb_scripts.js"></script>
<script type="text/javascript" src="bb-config/bb_admin_javascript.js"></script>

<link rel=StyleSheet href="bb-utilities/bb_styles.css" type="text/css" media=screen>
<link rel=StyleSheet href="bb-config/bb_admin_css.css" type="text/css" media=screen>
<?php
/* INCLUDE HACKS LAST after all other bb-config includes */
//hacks file useful for debugging
if (file_exists("bb-config/bb_admin_hacks.php")) include("bb-config/bb_admin_hacks.php");

/* SET UP LESS SUBSTITUTER */
include("bb-less/bb_less_substituter.php");
$less = new bb_less_substituter();
echo "<style>";
echo $less->parse_less_file("bb-utilities/bb_styles.less");
echo "</style>";
/* END LESS LESS SUBSTITUTER */
?>

<title><?php echo PAGE_TITLE; ?></title> 
</head>

<body id="bb_controller">
<?php
/* PROCESSING IMAGE */
if (!$main->blank($_POST['bb_image']))
    {
    //seems to flush nicely without explicitly flushing the output buffer
    $processing_image = $_POST['bb_image'];
    echo "<div id=\"bb_processor\"><img src=\"bb-config/" . $processing_image . "\"></div>";
    echo "<script>window.onload = function () { document.getElementById(\"bb_processor\").style.display = \"none\"; }</script>";
    }
?>
<?php
/* CONTROLLER ARRAYS*/
//query the modules table to set up the interface according to $array_master

//setup initial variables
//arr_reduce is part of interface for current userrole
$arr_reduce = $array_interface;
//arr_work is an array of temp variables for quick disposal
$arr_work['interface_type'] = "";
$arr_work['module_types'] = array();
//get the module types for current interface
foreach($array_interface as $key => $value)
	{
    //display appropriate modules, usertypes is array of numeric part of userroles
	if (in_array($usertype, $value['usertypes']))
		{
		array_push($arr_work['module_types'], $value['module_type']);
		}
	else
		{
        //unset interface type
		unset($arr_reduce[$key]);	
		}
	};
//get modules type into string for query, reuse variable
$arr_work['module_types'] = implode(",", array_unique($arr_work['module_types']));

//query modules table
$arr_work['query'] = "SELECT * FROM modules_table WHERE standard_module IN (0,4,6) AND interface IN ('" . pg_escape_string($interface) . "') AND module_type IN (" . pg_escape_string($arr_work['module_types']) . ") ORDER BY module_type, module_order;";
$result = pg_query($con, $arr_work['query']);

//populate controller arrays
$controller_path = ""; //path to included module
while($row = pg_fetch_array($result))
    {
    //check module type not hidden
	if ($row['module_type'] > 0)
		{
        //check that file exists
        if (file_exists($row['module_path']))
            {
            if (!empty($module))
                {
                //set current module
                if ($row['module_name'] == $module)
                    {
                    $controller_path = $row['module_path'];
                    $controller_type = $row['module_type']; 
                    }
                }
            $arr_controller[$row['module_type']][$row['module_name']] = array('friendly_name'=>$row['friendly_name'],'module_path'=>$row['module_path']);
            }		
        }
    }

//get default module to display initially
//will be first file where file exists
if (empty($module))
	{
	//use $array[key($array)] to find first value
	$arr_work['key'] = $arr_reduce[key($arr_reduce)]['module_type'];
	$module = key($arr_controller[$arr_work['key']]);
	$controller_path = $arr_controller[$arr_work['key']][$module]['module_path'];
	$controller_type = key($arr_controller);
	}

//if hidden, query hidden modules
if (empty($controller_path))
	{	
	$arr_work['query'] = "SELECT * FROM modules_table WHERE standard_module IN (1,2) AND module_type IN (0) AND module_name = '" . $module . "';";
	$result = pg_query($con, $arr_work['query']);
	$row = pg_fetch_array($result);
	$controller_path = $row['module_path'];
	$controller_type = $row['module_type'];
	}
/* END CONTROLLER AARAYS */

/* ECHO TABS */
//echo tabs and links for each module
echo "<div id=\"bb_header\">";
//header image
echo "<div id=\"controller_image\"></div>";
echo "<nav>"; //html5 nav tag

foreach ($arr_reduce as $value)
	{
	$arr_work['selected'] = ""; //reset selected
	if ($value['module_type'] == $controller_type)
		{
		$arr_work['interface_type'] = $value['interface_type'];
		}
	if ($value['interface_type'] == 'Standard')
		{
		foreach ($arr_controller[$value['module_type']] as $key => $value)       
			{
			$arr_work['selected'] = ($module == $key) ? "chosen" : "";
			echo "<button class=\"tabs " . $arr_work['selected'] . "\" onclick=\"bb_submit_form(0,'" . $key . "')\">" . $value['friendly_name'] . "</button>";
			}
		}
	elseif ($value['interface_type'] == 'Auxiliary')
		{
		//this section
		if (array_key_exists($value['module_type'], $arr_controller))
			{
			if (array_key_exists($module, $arr_controller[$value['module_type']]))
				{
				$arr_work['selected'] = "chosen";
				$arr_work['module'] = $module;
				}
			else
				{
				$arr_work['module'] = key($arr_controller[$value['module_type']]);
				}
			echo "<button class=\"tabs " . $arr_work['selected'] . "\"  onclick=\"bb_submit_form(0,'" . $arr_work['module'] . "')\">" . $value['friendly_name'] . "</button>";
			}			
		}		
	}
/* END ECHO TABS */

/* LINE UNDER TABS */
//line either set under chosen tab or below all tabs and a hidden module
$arr_work['lineclass'] = ($controller_type == 0) ? "line" : "under";
echo "<div class=\"" . $arr_work['lineclass'] . "\"></div>";
echo "</div>"; //bb_header
/* END LINE UNDER TABS */

/* INCLUDE APPROPRIATE MODULE */
echo "<div id=\"bb_wrapper\">";
//Auxiliary tabs
if ($arr_work['interface_type'] == 'Auxiliary')
    {
    echo "<div id=\"bb_admin_menu\">";
    //echo Auxiliary buttons on the side
    foreach ($arr_controller[$controller_type] as $key => $value)
        {
        echo "<button class=\"menu\" name=\"" . $key . "_name\" value=\"" . $key . "_value\"  onclick=\"bb_submit_form(0,'" . $key . "')\">" . $value['friendly_name'] . "</button>";
        }
    echo "</div>";
    
    //clean up before include
	unset($key);
    unset($value);
	unset($arr_work);
	unset($arr_reduce);
	unset($result);
    unset($arr_controller);
	//module include this is where modules are included
    echo "<div id=\"bb_admin_content\">";
    //$controller_path is reserved, this "include" includes the current module    
    //the include must be done globally, will render standard php errors
    //if it bombs it bombs, the controller should still execute
    //Auxiliary type module is included here
    if (file_exists($controller_path)) include($controller_path);
    
    echo "</div>";
    echo "<div class=\"clear\"></div>";
    }
//Standard tabs
else 
    {
    //clean up before include
	unset($key);
    unset($value);
	unset($arr_reduce);
	unset($arr_work);
	unset($result);
	unset($arr_controller);
	//module include this is where modules are included
    echo "<div id=\"bb_content\">";
    //$controller_path is reserved, this "include" includes the current module    
    //the include must be done globally, will render standard php errors
    //if it bombs it bombs, the controller should still execute
    //Standard type module is included here
    if (file_exists($controller_path)) include($controller_path);
    
    echo "</div>";	
    echo "<div class=\"clear\"></div>";
    }
echo "</div>"; //bb_wrapper
/* END INCLUDE MODULE */

//close connection -- make the database happy 
pg_close($con);
?>
</body>
</html>

<?php

/* MIDDLE ELSE, IF (logged in) THEN (controller) ELSE (login) END */

else:

/* LOGIN SECTION */
/* all php, no libraries etc */
/* check login and set session if valid */
/* all var are local in this section */

//initialize
$email = $password = $message = "";

//postback, attempt to login	
if (isset($_POST['index_login']))
    {
    //get connection
    $con_string = "host=" . DB_HOST . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASSWORD;
	$con = pg_connect($con_string);    
    //no connection die
    if (!$con) die();    
    
    //get form variables
    $username = substr($_POST['username'],0,255); //email and password must be < 255 by definition
    $password = substr($_POST['password'],0,255); //do not want to process big post
    
    //default error message, information only provided with accurate credentials
    $message = "Login Failure: Bad Username and Password, Invalid IP, or Account Locked";
    
    if (filter_var($ip = $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP))
        {
        //query users table
        $query = "SELECT username, email, hash, salt, attempts, array_to_string(userroles,',') as userroles, fname, minit, lname FROM users_table WHERE NOT ('0_bb_brimbox' = ANY (userroles)) AND ('" . pg_escape_string($ip) . "' <<= ANY (ips)) AND UPPER(username) = UPPER('". pg_escape_string($username) . "') AND attempts <= 10;";
        
        //get result
        $result = pg_query($con, $query);
                    
        $num_rows = pg_num_rows($result);	
         
        //1 row, definate database //known username
        if ($num_rows == 1)
            {
            $set_session = $set_attempts = false;
            $row = pg_fetch_array($result);
            
            //go through single user and admin waterfall
            if (hash('sha512', $password . $row['salt']) == $row['hash']) //good password
                {
                //single user takes precedence
                if (SINGLE_USER_ONLY <> "") //single user
                    {
                    if (!strcasecmp(SINGLE_USER_ONLY,$row['email']))
                        {
                        $set_session = true;
                        $message = $log_message = "Login Success/Single User";
                        }
                    else
                        {
                        $message = $log_message = "Program in Single User mode"; //only if failure
                        }
                    }
                elseif (!strcasecmp(ADMIN_ONLY, "YES") && SINGLE_USER_ONLY == "") //admin only
                    {
                    $arr_userroles = explode(",", $row['userroles']);
                    if (in_array("5_bb_brimbox", $arr_userroles))
                        {
                        $set_session = true;
                        $message = $log_message = "Login Success/Admin Only";
                        }
                    else
                        {
                        $message = $log_message = "Program in Admin Only mode";
                        }//only if failure
                    }
                else //regular login
                    {
                    $set_session = true;
                    $message = $log_message = "Login Success";
                    }
                }
            else //bad password
                {
                //$set_session is false
                $set_attempts = true;
                //only one bad login message
                $log_message = "Bad Password";
                }
            
            if ($set_session) //good login and mode
                {
                //set attempts to zero
                $query = "UPDATE users_table SET attempts = 0 WHERE UPPER(username) = UPPER('". pg_escape_string($username) . "');";
                pg_query($con, $query);
                //set username and email
                $_SESSION['username'] = $username = $row['username'];
                $_SESSION['email'] = $email = $row['email'];
                //set session timeout variable
                date_default_timezone_set(USER_TIMEZONE);
                $_SESSION['timeout'] = time();
                //build name for display
                $arr_name = array($row["fname"],$row["minit"],$row["lname"]);
                $arr_name = array_filter(array_map('trim',$arr_name));  
                $_SESSION['name'] = implode(" ", $arr_name);
                //this holds the possible permissions, be careful altering on the fly
                $_SESSION['userroles'] = $row['userroles']; //userroles string from db
                $arr_userroles = explode(",",$row['userroles']);
                $_SESSION['userrole'] =  $arr_userroles[0]; //first item of array
                $_SESSION['archive'] = 1; //archive mode is off
                //log entry
                $arr_log = array($username, $email, $ip, $log_message);
                $query = "INSERT INTO log_table (username, email, ip_address, action) VALUES ($1,$2,$3,$4)";
                pg_query_params($con, $query, $arr_log);
                //redirect with header call to index with session set
                $index_path = "Location: " . dirname($_SERVER['PHP_SELF']);
                header($index_path);
                die(); //important to stop script
                }		
            elseif ($set_attempts) //bad password
                {
                $query = "UPDATE users_table SET attempts = attempts + 1 WHERE UPPER(username) = UPPER('". pg_escape_string($username) . "') RETURNING attempts;";
                $result = pg_query($con, $query);
                $row = pg_fetch_array($result);
                if ($row['attempts'] >= 10)
                    {
                    $query = "UPDATE users_table SET userroles = '{0_bb_brimbox}' WHERE UPPER(username) = UPPER('". pg_escape_string($username) . "');";
                    pg_query($con, $query);
                    }
                $arr_log = array($username, $email, $ip, $log_message);
                $query = "INSERT INTO log_table (username, email, ip_address, action) VALUES ($1,$2,$3,$4)";
                pg_query_params($con, $query, $arr_log);
                //delay if invalid login
                $rnd = rand(100000,200000);
                $username = $password = "";
                usleep($rnd);
                }
            else  //admin or single user
                {               
                $arr_log = array($email, $ip, $log_message);
                $arr_log = array($username, $email, $ip, $log_message);
                $query = "INSERT INTO log_table (username, email, ip_address, action) VALUES ($1,$2,$3,$4)";
                pg_query_params($con, $query, $arr_log);
                //delay if invalid login
                $username = $password = "";   
                }
            } //end row found   
        else //no rows, bad username or locked
            {
             //only one bad login message
            $log_message = "Login Failure: Bad Username, Invalid IP, or Account Locked";
            $arr_log = array($username, $email, $ip, $log_message);
            $query = "INSERT INTO log_table (username, email, ip_address, action) VALUES ($1,$2,$3,$4)";
            pg_query_params($con, $query, $arr_log);
            //delay if invalid login
            $rnd = rand(100000,200000);
            $username = $password = "";
            usleep($rnd);
            }
        }
    //just in case there is something awry with the ip, not really possible
    else
        {
        $log_message = "Malformed IP";
        $arr_log = array($username, $email, $ip, $log_message);
        $query = "INSERT INTO log_table (username, email, ip_address, action) VALUES ($1,$2,$3,$4)";
        pg_query_params($con, $query, $arr_log);
        //delay if invalid login
        $rnd = rand(100000,200000);
        $username = $password = "";
        usleep($rnd);                
        }
	} //end post

//echo html output
?>
<!DOCTYPE html>    
<html>   
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<link rel=StyleSheet href="bb-utilities/bb_index.css" type="text/css" media=screen>
<?php
include("bb-less/bb_less_substituter.php");
$less = new bb_less_substituter();
echo "<style>";
echo $less->parse_less_file("bb-utilities/bb_index.less");
echo "</style>";
?>

<title><?php echo PAGE_TITLE; ?></title>

</head>
<body>

<?php
/* LOGIN FORM (in table form, index_image is index image*/
echo "<div id=\"bb_index\">";
echo "<form name=\"index_form\" method=\"post\">";
echo "<div id=\"index_image\"></div>";

//since it is centered use table
echo "<div id=\"index_holder\">";
echo "<table><tr><td class=\"left\"><label for=\"username\">Username: </label></td>";
echo "<td class=\"right\"><input name=\"username\" id=\"username\" class=\"long\" type=\"text\" /></td></tr>";
echo "<tr><td class=\"left\"><label for=\"password\">Password: </label></td>";
echo "<td class=\"right\"><input name=\"password\" id=\"password\"class=\"long\" type=\"password\" /></td></tr></table>";
echo "</div>";
echo "<button id=\"index_button\" name=\"index_login\" type=\"submit\" value=\"index_login\" />Login</button>";
echo "<div id=\"index_message\">" . $message . "</div>";
echo "</form>";

echo "</div>"; //end wrapper div
/* END FORM */
?>
</body>
</html>
<?php

/* ENDIF, IF (controller) ELSE (login) */
endif;

?>

