<?php
/*
Copyright (C) 2012 - 2013 Kermit Will Richardson, Brimbox LLC

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
date_default_timezone_set(USER_TIMEZONE);

// start session based on db name
session_name(DB_NAME);
session_start();

/* START IF, IF (logged in) THEN (controller) ELSE (login) END */

if (isset($_SESSION['email'])):

/* RESERVED VARIABLES (used in controller)*/
//$userrole -- current security level string including the interface
//$userroles -- valid security permissions
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
//bb_database
//bb_link
//bb_validate
//bb_form
//bb_work
//bb_report
//bb_main
//lessc -- LESS comnpiler

//other vars are all disposed of with unset

//get SESSION STUFF -- designed for one user type per browser
$email = $_SESSION['email']; //login of user
$userrole = $_SESSION['userrole']; //string containing userrole and interface
$userroles = $_SESSION['userroles']; //comma separated string careful with userroles session, used to check for valid userrole
list($usertype, $interface) = explode("_", $_SESSION['userrole'], 2);
$archive = $_SESSION['archive']; //archive mode
$button = isset($_POST['bb_button']) ? $_POST['bb_button'] : 0; //global button

//logout algorithm used for both interface change, userrole change and logout
if (isset($_POST['bb_module']))
	{
	$module = $_POST['bb_module'];
	$submit = $_POST['bb_submit'];
	if ($module == "bb_logout")
		{
		//explode on first dash only allows for dashes in interface name
		list($usertype, $interwork) = explode("-", $_POST['bb_interface'], 2);
		//logout and change interface/userrole could be on different or many pages
		//check for session poisoning, array userroles should not be altered
		//the conversion to string of string of $_POST['bb_submit'] and will stop injection
		//$userroles variable should be protected and not used or altered anywhere		
		if (((int)$usertype <> 0) && in_array($_POST['bb_interface'], explode(",", $_SESSION['userroles'])))
			{
			$_SESSION['userrole'] = $_POST['bb_interface']; 
			$index_path = "Location: " . dirname($_SERVER['PHP_SELF']);
			header($index_path);
			die(); //important to stop script
			}
		//if logout, destroy session and force index, button < 0 or invalid userrrole
		else
			{
			session_destroy();
			$index_path = "Location: " . dirname($_SERVER['PHP_SELF']);
			header($index_path);
			die(); //important to stop script
			}
		}
	}
	
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

/* DO HOOK MODULES */
$arr_work['hooks'] = "SELECT module_path FROM modules_table WHERE standard_module IN (0,4,6) AND module_type IN (-2) ORDER BY module_order;";
$result = pg_query($con, $arr_work['hooks']);
while($row = pg_fetch_array($result))
    {
    include($row['module_path']);
    }
// include adhoc functions
include("bb-config/bb_admin_functions.php");

/* DO GLOBALS */
// Contains initial globals and global setup
include("bb-utilities/bb_globals.php");
//DO GLOBAL MODULES
$arr_work['globals'] = "SELECT module_path FROM modules_table WHERE standard_module IN (0,4,6) AND module_type IN (-1) ORDER BY module_order;";
$result = pg_query($con, $arr_work['globals']);
while($row = pg_fetch_array($result))
    {
    include($row['module_path']);
    }
// include adhoc globals
include("bb-config/bb_admin_globals.php");

//unpack $array_master
foreach($array_master[$interface] as $key => $value)
	{
	${'array_' . $key} = $value;
	}
?>
<?php /* START HTML OUTPUT */ ?>
<!DOCTYPE html>    
<html>
<head>    
<meta http-equiv="Content-Type" content="text/html; charset="utf-8" />

<?php /* JAVASCRIPT AND CSS INCLUDES */ ?>
<script type="text/javascript" src="bb-utilities/bb_scripts.js"></script>
<script type="text/javascript" src="bb-config/bb_admin_javascript.js"></script>

<link rel=StyleSheet href="bb-utilities/bb_styles.css" type="text/css" media=screen>
<link rel=StyleSheet href="bb-config/bb_admin_css.css" type="text/css" media=screen>

<?php
/* SET UP LESS PARSER */
//see included license
include("bb-less/lessc.inc.php");
$less = new lessc();
echo "<style>";
$less->setFormatter("compressed");
echo $less->compileFile("bb-utilities/bb_styles.less");
echo $less->compileFile("bb-config/bb_admin_less.less");
echo "</style>";
/* END LESS PARSER */
?>

<title><?php echo PAGE_TITLE; ?></title> 
</head>

<body>
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
	if (in_array($usertype, $value['userroles']))
		{
		array_push($arr_work['module_types'], $value['module_type']);
		}
	else
		{
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
    //double check that user permission is consistant with module type
	if ($row['module_type'] <> 0)
		{
		if (!empty($module))
			{
			if ($row['module_name'] == $module)
				{
				$controller_path = $row['module_path'];
				$controller_type = $row['module_type']; 
				}
			}
		$arr_controller[$row['module_type']][$row['module_name']] = array('friendly_name'=>$row['friendly_name'],'module_path'=>$row['module_path']);
		}
    }

//get default module to display initially	
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
			echo "<button class=\"tabs " . $arr_work['selected'] . "\" onclick=\"bb_submit_form(-1,'" . $key . "')\">" . $value['friendly_name'] . "</button>";
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
			echo "<button class=\"tabs " . $arr_work['selected'] . "\"  onclick=\"bb_submit_form(-1,'" . $arr_work['module'] . "')\">" . $value['friendly_name'] . "</button>";
			}			
		}		
	}
/* END ECHO TABS */


//line either set under chosen tab or below all tabs and a hidden module
$arr_work['lineclass'] = ($controller_type == 0) ? "line" : "under";
echo "<div class=\"" . $arr_work['lineclass'] . "\"></div>";
echo "</div>"; //bb_header
/* END ECHO TABS */

/* UNSET UNNEEDED VARS BEFORE INCLUDE */
/* so not passed to modules */

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
    include($controller_path);
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
    include($controller_path);
    echo "</div>";
	
    echo "<div class=\"clear\"></div>";
    }
echo "</div>"; //bb_wrapper

/* END INCLUDE MODULE */

//close connection 
pg_close($con);
?>
</body>
</html>
<?php

/* MIDDLE ELSE, IF (logged in) THEN (controller) ELSE (login) END */

else:

/* LOGIN SECTION */
/* check login and set session if not already set */
/* all var are local in this section */

//no html output in these libraries because of header calls
//objects extended together
include("bb-utilities/bb_main.php");
include("bb-utilities/bb_database.php");

//set up main oject from second class (bb_database)
$main = new bb_database();

//initialize
$email = "";
$passwd = "";
$message = "";

//postback, attempt to login	
if (isset($_POST['index_enter']))
    {
    //get connection
    $con = $main->connect();
    
    //get form variables
    $email = $main->custom_trim_string($_POST['username'],255);
    $passwd = $main->custom_trim_string($_POST['passwd'],255);
    
    //query master database
    $query = "SELECT email, hash, salt, attempts, array_to_string(userroles,',') as userroles, fname, minit, lname FROM users_table WHERE NOT ('0%' = ANY (userroles)) AND UPPER(email) = UPPER('". pg_escape_string($email) . "') AND attempts <= 10;";
    
    //get result
    $result = $main->query($con, $query);
    $num_rows = pg_num_rows($result);	
     
    //1 row, definate database //known username
    if ($num_rows == 1)
		{
		$set_session = false;
		$row = pg_fetch_array($result);
		
		//go through single user and admin waterfall
		if (SINGLE_USER_ONLY <> '')
			{
			if ((SINGLE_USER_ONLY ==  $row['email']) && (hash('sha512', $passwd . $row['salt']) == $row['hash']))
				{
				$set_session = true;
				}
			$message = "Program in single user mode."; //only if failure
			}
		else //single user empty
			{
			if (ADMIN_ONLY == 'YES')
				{
				$arr_userroles = explode(",", $row['userroles']);
				if (in_array(5,$arr_userroles) && (hash('sha512', $passwd . $row['salt']) == $row['hash']))
					{
					$set_session = true;
					}
				$message = "Program in Admin only mode."; //only if failure
				}
			else //regular check password admin only not YES
				{
				if (hash('sha512', $passwd . $row['salt']) == $row['hash'])
					{
					$set_session = true;	
					}
				$message = "Invalid Login/Password."; //only if failure	
				}
			}
		
		if ($set_session) //good login
			{
			//set attempts to zero
			$query = "UPDATE users_table SET attempts = 0 WHERE UPPER(email) = UPPER('". pg_escape_string($email) . "');";
			$main->query($con, $query);
			//set sessions
			$_SESSION['email'] = $row['email'];
			$_SESSION['name'] = $main->build_name($row);
			//this holds the possible permissions, be careful altering on the fly
            $_SESSION['userroles'] = $row['userroles']; //userroles string from db
            $arr_userroles = explode(",",$row['userroles']);
            $_SESSION['userrole'] =  $arr_userroles[0]; //first item of array
			$_SESSION['archive'] = 1; //archive mode is off
			//log entry
			$main->log_entry($con, "Login Success");
			//redirect with header call to index with session set
			$index_path = "Location: " . dirname($_SERVER['PHP_SELF']);
			header($index_path);
			die(); //important to stop script
			}		
		else //bad password or admin mode
			{
			$query = "UPDATE users_table SET attempts = attempts + 1 WHERE UPPER(email) = UPPER('". pg_escape_string($email) . "') RETURNING attempts;";
			$result = $main->query($con, $query);
			$row = pg_fetch_array($result);
			if ($row['attempts'] >= 10)
				{
				$query = "UPDATE users_table SET userrole = 0 WHERE UPPER(email) = UPPER('". pg_escape_string($email) . "');";
				$main->query($con, $query);
				}
			$main->log_entry($con, rtrim($message, ".") , $email);
			//delay if invalid login
			$rnd = rand(100000,200000);
			$email = "";
			$passwd = "";
			usleep($rnd);
			}
		} //end row found
		
	else //no rows, bad username or locked
		{
		//bad username
		$message = "Login Failure: Bad Username or Account Locked.";
		$main->log_entry($con, rtrim($message, ".") , $email);
		//delay if invalid login
		$rnd = rand(100000,200000);
		$email = "";
		$passwd = "";
		usleep($rnd);
		}			
	} //end post


    
//echo html output
?>
<!DOCTYPE html>    
<html>   
<head>
<meta http-equiv="Content-Type" content="text/html; charset="utf-8" />

<link rel=StyleSheet href="bb-utilities/bb_styles.css" type="text/css" media=screen>
<?php
/* SET UP LESS PARSER */
//see included license
include("bb-less/lessc.inc.php");
$less = new lessc();
echo "<style>";
$less->setFormatter("compressed");
echo $less->compileFile("bb-utilities/bb_styles.less");
echo "</style>";
/* END LESS PARSER */
?>
<title><?php echo PAGE_TITLE; ?></title>
</head>

<body>
<div id="bb_index">
<?php
/* LOGIN FORM (in table form, index_image is index image*/
echo "<form id=\"index_form\" name=\"index_form\" method=\"post\">";
echo "<div class=\"table\">";
echo "<div class=\"row padded\">";
echo "<div class=\"cell center\">";
echo "<div id=\"index_image\"></div>";
echo "</div>";
echo "</div>";

//since it is centered use table....no float:center
echo "<div class=\"table\">";
echo "<div class=\"table border padded\">";
echo "<div class=\"row\">";
echo "<div class=\"cell\">";
echo "<div class=\"padded shaded right\"><p class=\"short\">Username: </p></div>";
echo "</div>";
echo "<div class=\"cell\">";
echo "<div class=\"padded left \"><input name=\"username\" class=\"long\" type=\"text\" /></div>";
echo "</div>";
echo "</div>";
echo "<div class=\"row\">";
echo "<div class=\"cell\">";
echo "<div class=\"padded shaded right\"><p class=\"short\">Password: </p></div>";
echo "</div>";
echo "<div class=\"cell\">";
echo "<div class=\"padded left\"><input name=\"passwd\" class=\"long\" type=\"password\" /></div>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "<div class=\"row padded\">";
echo "<div class=\"cell padded center\">";
echo "<button name=\"index_enter\" type=\"submit\" value=\"index_enter\" />Login</button>";
echo "</div>";
echo "</div>";
echo "<div class=\"row padded\">";
echo "<div class=\"cell padded center\">";
echo "<p class=\"error\">" . $message . "</p>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</form>";
/* END FORM */
?>
</div>
</body>
</html>
<?php

/* ENDIF, IF (controller) ELSE (login) */
endif;

?>

