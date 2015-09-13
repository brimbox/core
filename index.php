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
/*
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
*/

// constant to verify include files are not accessed directly
define('BASE_CHECK', true);
// need DB_NAME from bb_config, must not have html output including blank spaces
include_once("bb-config/bb_config.php");

//need db name
// start session based on db name
session_name(DB_NAME);
session_start();
session_regenerate_id();

/* START IF, IF (logged in) THEN (controller) ELSE (login) END */

if (isset($_SESSION['username'])):

//other vars are all disposed of with unset

//CONTROLLER VARS
//set by login
$username = $_SESSION['username'];
$email = $_SESSION['email'];
$interface = $_SESSION['interface']; //the current interface
$timeout = $_SESSION['timeout']; //not implemented, for session timout
$userrole = $_SESSION['userrole']; //string containing userrole and interface
$userroles = $_SESSION['userroles']; //comma separated string careful with userroles session, used to check for valid userrole
$archive = $_SESSION['archive']; //archive state
$keeper = $_SESSION['keeper']; //state_table id

//unpack things
list($usertype, $interface) = explode("_", $_SESSION['userrole'], 2);

//set by post.php
//the current module
$module = isset($_SESSION['module']) ? $_SESSION['module'] : "";
//the previous module where the form is submitted
$submit = isset($_SESSION['submit']) ? $_SESSION['submit'] : "";
//the current button, 0 default
$button = isset($_SESSION['button']) ? $_SESSION['button'] : 0;
//the current module id, used in the database statedata variable array
$saver = isset($_SESSION['saver']) ? $_SESSION['saver'] : 0;

//the current slug, the part of the url after the index file path
$slug = substr_replace(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH),"",0, strlen(dirname($_SERVER['SCRIPT_NAME'])) + 1);

//$path controller file path global
//$type controller module type global

/* END CONTROLLER VARS */

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
include("bb-config/bb_constants.php");
/* SET TIME ZONE */
date_default_timezone_set(USER_TIMEZONE);

/* SET UP MAIN OBJECT */
//objects are all daisy chained together
//set up main from last object
// contains bb_database class, extends bb_main
include("bb-utilities/bb_database.php");
//contains bb_validation class, extend bb_links
include("bb-utilities/bb_validate.php");
//contains bb_report class, extend bb_work
include("bb-utilities/bb_hooks.php");
//contains bb_work class, extends bb_forms
include("bb-utilities/bb_work.php");		
/* these classes only brought into both $main */
// contains bb_database class, extends bb_main
include("bb-utilities/bb_links.php");
//contains bb_form class, extends bb_validate
include("bb-utilities/bb_forms.php");
//contains bb_report class, extend bb_hooks
include("bb-utilities/bb_reports.php");
//contains bb_main class
include("bb-utilities/bb_main.php");

$main = new bb_main();

/* GET DATABASE CONNECTION */
//database connection passed into modules globally
$con = $main->connect();

/* USER LOCKED OR DELETED */
//once $con is set check live whether user is locked or deleted
//0_bb_brimbox is only locked userrole, for active lock
$query = "SELECT id FROM users_table WHERE username = '" . pg_escape_string($username) . "' AND NOT '0_bb_brimbox' = ANY (userroles);";
$result = pg_query($con, $query);
if (pg_num_rows($result) <> 1)
    {
    session_destroy();
	$index_path = "Location: " . dirname($_SERVER['PHP_SELF']);
	header($index_path);
	die(); //important to stop script    
    }

/* INCLUDE HEADER MODULES AND FILE */
//global for all interfaces
include("bb-utilities/bb_headers.php");
$query = "SELECT module_path FROM modules_table WHERE standard_module IN (0,4,6) AND module_type IN (-3) ORDER BY module_order;";
$result = pg_query($con, $query);
while($row = pg_fetch_array($result))
    {
    //will ignore file if missing
    $main->include_exists($row['module_path']);
    }
/* ADHOC HEADERS */
$main->include_exists("bb-config/bb_admin_headers.php");

/* DO FUNCTION MODULES AND FILE*/
//only for interface being loaded
$query = "SELECT module_path FROM modules_table WHERE interface IN ('" . pg_escape_string($interface) . "') AND standard_module IN (0,4,6) AND module_type IN (-2) ORDER BY module_order;";
$result = pg_query($con, $query);
while($row = pg_fetch_array($result))
    {
    //will ignore file if missing
    $main->include_exists($row['module_path']);
    }
/* ADHOC FUNCTIONS */
//will ignore file if missing
$main->include_exists("bb-config/bb_admin_functions.php");

/* DO GLOBAL MODULES AND FILE */
//only for interface being loaded
include("bb-utilities/bb_globals.php");
$query = "SELECT module_path FROM modules_table WHERE  interface IN ('" . pg_escape_string($interface) . "') AND standard_module IN (0,4,6) AND module_type IN (-1) ORDER BY module_order;";
$result = pg_query($con, $query);
while($row = pg_fetch_array($result))
    {
    //will ignore file that does not exists so can debug by deleting file
    $main->include_exists($row['module_path']);
    }
/* ADHOC GLOBALS */
//will ignore file if missing
$main->include_exists("bb-config/bb_admin_globals.php");

/* UNPACK $array_global for given interface */
//this creates array from the global array
if (isset($array_global))
    {
    foreach($array_global[$interface] as $key => $value)
        {
        ${'array_' . $key} = $value;
        }
    }
    
//initialize module and slug if empty
if (($module == "") || ($slug == ""))
    {
    $module = $array_landing[$usertype]['landing_module'];
    $slug = $array_landing[$usertype]['landing_slug'];
    }
?>
<?php /* START HTML OUTPUT */ ?>
<!DOCTYPE html>    
<html>
<head>    
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<?php /* JAVASCRIPT AND CSS INCLUDES */
//include file deals with path
$main->include_file("bb-utilities/bb_scripts.js", "js");
$main->include_file("bb-config/bb_admin_javascript.js", "js");
$main->include_file("bb-utilities/bb_styles.css", "css");
$main->include_file("bb-config/bb_admin_css.css", "css");

/* INCLUDE HACKS FILE LAST after all other bb-config includes */
//hacks file useful for debugging
$main->include_exists("bb-config/bb_admin_hacks.php");

/* SET UP LESS SUBSTITUTER */
include("bb-less/bb_less_substituter.php");
$less = new bb_less_substituter();
echo "<style>";
echo $less->parse_less_file("bb-utilities/bb_styles.less");
echo "</style>";
/* END LESS LESS SUBSTITUTER */
unset($query, $result, $row);
?>
<title><?php echo PAGE_TITLE; ?></title> 
</head>
<?php /* END HEAD */ ?>

<body id="bb_brimbox">
<?php
/* PROCESSING IMAGE */
if (!$main->blank($image))
    {
    //seems to flush nicely without explicitly flushing the output buffer
    echo "<div id=\"bb_processor\"><img src=\"bb-config/" . $image . "\"></div>";
    echo "<script>window.onload = function () { document.getElementById(\"bb_processor\").style.display = \"none\"; }</script>";
    }
    
/* CONTROLLER ARRAY*/
//query the modules table to set up the interface
//setup initial variables from $array_interface
$arr_interface = $array_interface;
//module type 0 for hidden modules
$module_types = array(0);
//get the module types for current interface
foreach($array_interface as $key => $value)
	{
    //display appropriate modules, usertypes is array of numeric part of userroles
	if (in_array($usertype, $value['usertypes']))
		{
		array_push($module_types, $value['module_type']);
		}
	else
		{
        //unset interface type
		unset($arr_interface[$key]);	
		}
	};
//get modules type into string for query
$module_types = implode(",", array_unique($module_types));
//query modules table
$query = "SELECT * FROM modules_table WHERE standard_module IN (0,1,2,4,6) AND interface IN ('" . pg_escape_string($interface) . "') " .
         "AND module_type IN (" . pg_escape_string($module_types) . ") ORDER BY module_type, module_order;";
//echo "<p>" . $query . "</p>";
$result = pg_query($con, $query);

//populate controller arrays
while($row = pg_fetch_array($result))
    {
    //get the first module
    //check module type not hidden
    //check that file exists
    if (file_exists($row['module_path']))
        {
        //work with slug
        if ($slug == $row['module_slug'])
            {
            if ($module == $row['module_name']) //module and slug match
                {
                list($path, $type) = array($row['module_path'], $row['module_type']);
                }
            else //module and slug don't match, get module also
                {
                list($module, $path, $type) = array($row['module_name'], $row['module_path'], $row['module_type']);    
                }
            }
        //need to address controller by both module_type and module_name            
        if ($row['module_type'] > 0)
            {
            //$array[key][key] is easiest
            $arr_controller[$row['module_type']][$row['module_name']] = array('friendly_name'=>$row['friendly_name'],'module_path'=>$row['module_path']);
            }
        }		
    }
/* END CONTROLLER ARRAY */

/* ECHO TABS */
//echo tabs and links for each module
echo "<div id=\"bb_header\">";
//header image
echo "<div id=\"controller_image\"></div>";
//global message for all users
$controller_message = $main->get_constant('BB_CONTROLLER_MESSAGE', '');
if (!$main->blank($controller_message))
    {
    echo "<div id=\"controller_message\">" .  $controller_message . "</div>";    
    }
//set up standard tab and auxiliary header tabs
foreach ($arr_interface as $value)
	{
	$selected = ""; //reset selected
    //active module type
	if ($value['module_type'] == $type)
		{
		$interface_type = $value['interface_type'];
		}
    //layout standard tabs   
	if ($value['interface_type'] == 'Standard')
		{
		foreach ($arr_controller[$value['module_type']] as $module_work => $value)       
			{
			$selected = ($module == $module_work) ? "chosen" : "";
			echo "<button class=\"tabs " . $selected . "\" onclick=\"bb_submit_form(0,'" . $module_work . "')\">" . $value['friendly_name'] . "</button>";
			}
		}
    //layout auxiliary header tab
	elseif ($value['interface_type'] == 'Auxiliary')
		{
		//this section
		if (array_key_exists($value['module_type'], $arr_controller))
			{
			if (array_key_exists($module, $arr_controller[$value['module_type']]))
				{
				$selected = "chosen";
                $module_work = $module;
				}
			else
				{
				$module_work = key($arr_controller[$value['module_type']]);
				}
			echo "<button class=\"tabs " . $selected . "\"  onclick=\"bb_submit_form(0,'" . $module_work . "')\">" . $value['friendly_name'] . "</button>";
			}			
		}		
	}
/* END ECHO TABS */

/* LINE UNDER TABS */
//line either set under chosen tab or below all tabs and a hidden module
$lineclass = ($type == 0) ? "line" : "under";
echo "<div class=\"" . $lineclass  . "\"></div>";
echo "</div>"; //bb_header
/* END LINE UNDER TABS */

/* INCLUDE APPROPRIATE MODULE */
echo "<div id=\"bb_wrapper\">";
//Auxiliary tabs and links
if ($interface_type == 'Auxiliary')
    {
    echo "<div id=\"bb_admin_menu\">";
    //echo auxiliary buttons on the side
    foreach ($arr_controller[$type] as $module_work => $value)
        {
        echo "<button class=\"menu\" name=\"" . $module_work . "_name\" value=\"" . $module_work . "_value\"  onclick=\"bb_submit_form(0,'" . $module_work . "')\">" . $value['friendly_name'] . "</button>";
        }
    echo "</div>";
    
    //clean up before include
	unset($key, $value, $arr_interface, $arr_controller, $interface_type, $lineclass, $module_work);
	//module include this is where modules are included
    echo "<div id=\"bb_admin_content\">";
    //$path is reserved, this "include" includes the current module    
    //the include must be done globally, will render standard php errors
    //if it bombs it bombs, the controller should still execute
    //Auxiliary type module is included here
    if (file_exists($path)) include($path);
    
    echo "</div>";
    echo "<div class=\"clear\"></div>";
    }
//Standard tabs
else 
    {
    //clean up before include
    unset($key, $value, $arr_interface, $arr_controller, $interface_type, $lineclass, $module_work);
	//module include this is where modules are included
    echo "<div id=\"bb_content\">";
    //$path is reserved, this "include" includes the current module    
    //the include must be done globally, will render standard php errors
    //if it bombs it bombs, the controller should still execute
    //Standard type module is included here
    if (file_exists($path)) include($path);
    
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
    $interface = substr($_POST['interface'],0,255);
    
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
                //state and post data row, keeper is id
                $_SESSION['interface'] = $interface;
                $query = "INSERT INTO state_table (statedata, postdata) VALUES ('{}','') RETURNING id;";
                $result = pg_query($con, $query);
                $row = pg_fetch_array($result);
                $_SESSION['keeper'] = $row['id'];               
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
echo "<td class=\"right\"><input name=\"password\" id=\"password\"class=\"long\" type=\"password\" /></td></tr>";
echo "<tr><td class=\"left\"><label for=\"interface\">Interface: </label></td>";
echo "<td class=\"right\"><input name=\"interface\" id=\"interface\"class=\"long\" type=\"hidden\" value=\"bb_brimbox\"/></td></tr></table>";
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

