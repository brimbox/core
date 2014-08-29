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

// start session based on db name
session_name(DB_NAME);
session_start();

/* START IF, IF (logged in) THEN (controller) ELSE (login) END */

if (isset($_SESSION['userrole'])):

/* RESERVED VARIABLES (used in controller)*/
//$userrole -- the security level
//$userroles -- valisd security permissions
//$email -- the current email/username
//$array_state -- the state array, commonly passed into functions as reference
//$module -- the current module
//$con -- the connection to the database
//$controller_path -- the path to the current module
//$index_path - not unset because used in a header redirect

//these other vars are all disposed of with unset
//$controller_type //unset after tabs are echoed
//$selected //unset after tabs are echoed
//$lineclass //unset after tabs are echoed
//$arr_controller -- unset after final use

//get userrole and module
$userroles = $_SESSION['userroles']; //careful with userroles session, used to check for valid userrole
$userrole = $_SESSION['userrole']; //once set, can be set with user input, checked against userroles
$email = $_SESSION['email']; //login of user
$archive = $_SESSION['archive']; //archive mode

//logout algorithm used for both userrole change and logout
if (isset($_POST['bb_module']))
	{
	//die($_POST['bb_module']); 
	if ($_POST['bb_module'] == "bb_logout")
		{
		$button = $_POST['bb_submit'] . "_bb_button";
		//check for session poisoning, array userroles should not be altered
		//the conversion to int of $_POST[$button] will stop injection
		//$userroles should be protected and not used or altered anywhere
		if (($_POST[$button] > 0) && in_array($_POST[$button], $userroles))
			{
			$_SESSION['userrole'] = $_POST[$button];
			$index_path = "Location: " . dirname($_SERVER['PHP_SELF']);
			header($index_path);
			die(); //important to stop script
			}
		//if logout, destroy session and force index
		else
			{
			session_destroy();
			$index_path = "Location: " . dirname($_SERVER['PHP_SELF']);
			header($index_path);
			die(); //important to stop script
			}
		}
	else
		{
		$module = $_POST['bb_module']; 
		}
	}
//default module from login is bb_guest or bb_home
else
    {
	switch($userrole)
		{
		case 1:
		$module = "bb_guest";
		break;
		case 2:
		$module = "bb_viewer";
		break;
		case 3:
		case 4:
		case 5:
		$module = "bb_home";
		break;		
		}
    }
	
/* INCLUDE ALL BRIMBOX STANDARD FUNCTIONS */
// contains bb_database class
include("bb-utilities/bb_database.php");
// contains bb_links class extends bb_database
include("bb-utilities/bb_link.php");
//contains bb_validation class extend bb_link
include("bb-utilities/bb_validate.php");
//contains bb_form class extends bb_validate
include("bb-utilities/bb_form.php");
//contains bb_work class extends bb_form
include("bb-utilities/bb_work.php");
//contains bb_report class extend bb_work
include("bb-utilities/bb_report.php");
//contains bb_main class extends bb_report
include("bb-utilities/bb_main.php");

// User function include
include("bb-config/bb_admin_functions.php");

/* SET UP OBJECT */
//objects are all daisy chained together
$main = new bb_main();
//constructs main and work, extends form
/* END OBJECTS */

/* GET DATABASE CONNECTION */
//database connection passed into modules globally
$con = $main->connect();
/* END DATABASE CONNECTION */

// Contains initial globals and global setup
include("bb-utilities/bb_globals.php");
//Contains the user defined globals
include("bb-config/bb_admin_globals.php");

//START HTML OUTPUT
?>
<!DOCTYPE html>    
<html>
<head>    
<meta http-equiv="Content-Type" content="text/html; charset="utf-8" />

<script type="text/javascript" src="bb-utilities/bb_script.js"></script>
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

<script type="text/javascript" src="bb-config/bb_admin_javascript.js"></script>
<link rel=StyleSheet href="bb-config/bb_admin_css.css" type="text/css" media=screen>

<title><?php echo PAGE_TITLE; ?></title> 

</head>
<body>
	
<?php
/* CONTROLLER ARRAYS*/
//query the modules table to set up the tabs and setyup and admin links
$query = "SELECT * FROM modules_table WHERE standard_module IN (0,1,2,4,6) AND userrole BETWEEN 1 AND " . (int)$userrole . " ORDER BY module_type, module_order;";
$result = pg_query($con, $query);

//put the tab info into arrays for processing
$arr_controller = array(1=>array(),2=>array(),3=>array(),4=>array(),5=>array());
//controller type (module type) and controller path need defaults
//like a hidden tab
$controller_type = 0;
//default tabs, should not be deactivated
switch ($userrole)
    {
    case 1:
        $controller_path = "bb-primary/bb_guest.php";
        break;
    case 2:
        $controller_path = "bb-primary/bb_viewer.php";
        break;
    case 3:
    case 4:
    case 5:
        $controller_path = "bb-primary/bb_home.php";
        break;        
    }

//setup controller array
while($row = pg_fetch_array($result))
    {
    if ($row['module_name'] == $module)
        {
        $controller_type = $row['module_type'];
        $controller_path = $row['module_path'];
        } 
    //double check that user permission is consistant with module type
    if ($row['module_type'] <= $userrole)
        {
        switch ($row['module_type'])
            {
            case 0:
                 //hidden, do nothing
                 break;
            case 1:
                //guest tab
                $key = $row['module_type'];
                $arr_controller[$key][$row['module_name']] = $row['friendly_name'];
                break;
            case 2:
                //viewer tab
                $key = $row['module_type'];
                $arr_controller[$key][$row['module_name']] = $row['friendly_name'];
                break;
            case 3:
                //user tab
                $key = $row['module_type'];
                $arr_controller[$key][$row['module_name']] = $row['friendly_name'];
             break;
            case 4:
                //setup tab
                $key = $row['module_type'];
                $arr_controller[$key][$row['module_name']]= $row['friendly_name'];
            break;
            case 5:
                //admin tab
                $key = $row['module_type'];
                $arr_controller[$key][$row['module_name']] = $row['friendly_name'];
             break;
            }
        }
    }
/* END CONTROLLER AARAYS */

/* ECHO TABS */
//echo tabs and links for each module
echo "<div id=\"bb_header\">";
//header image
echo "<div id=\"controller_image\"></div>";

echo "<nav>"; //html5 nav tag
    
if ($userrole == 1)
    {
    //guest tabs
    foreach ($arr_controller[1] as $key => $value)       
        {
        $selected = ($module == $key) ? "chosen" : "";
        echo "<button class=\"tabs " . $selected . " \" onclick=\"bb_submit_form(0,'" . $key . "')\">" . $value . "</button>";
        }
    }
if ($userrole == 2)
    {
    //viewer tabs
    foreach ($arr_controller[2] as $key => $value)       
        {
        $selected = ($module == $key) ? "chosen" : "";
        echo "<button class=\"tabs " . $selected . " \" onclick=\"bb_submit_form(0,'" . $key . "')\">" . $value . "</button>";
        }
    }   
else
    {
    //standard, setup, & admin tabs
    //standard tabs
    foreach ($arr_controller[3] as $key => $value)       
        {
        $selected = ($module == $key) ? "chosen" : "";
        $selected = trim("tabs " . $selected);
        echo "<button class=\"" . $selected . "\" onclick=\"bb_submit_form(0,'" . $key . "')\">" . $value . "</button>";
        }
   
   //setup tab
   //get module and set selected for setup
    if (array_key_exists($module, $arr_controller[4]))
        {
        $setup_tab = $module;
        }
    else
        {
        reset($arr_controller[4]);
        $setup_tab = key($arr_controller[4]); //to return first value
        } 
    $selected = ($controller_type == 4) ? "chosen" : "";
    $selected = trim("tabs " . $selected);
    if (!empty($arr_controller[4]))
        {
        echo "<button class=\"" . $selected . "\"  onclick=\"bb_submit_form(0,'" . $setup_tab . "')\">Setup</button>";
        }
      
   //admin tab
   //get module and set selected for admin
    if (array_key_exists($module, $arr_controller[5]))
        {
        $admin_tab = $module;
        }
    else
        {
        reset($arr_controller[5]);
        $admin_tab = key($arr_controller[5]); //to return first value
        } 
   $selected = ($controller_type == 5) ? "chosen" : "";
   $selected = trim("tabs " . $selected);
   if (!empty($arr_controller[5]))
        {
        echo "<button class=\"" . $selected . "\" onclick=\"bb_submit_form(0,'" . $admin_tab . "')\">Admin</button>";
        }
    }  //end else
    
echo "</nav>"; //closing nav tag

//line either set under chosen tab or below all tabs and a hidden module
$lineclass = ($controller_type == 0) ? "line" : "under";
echo "<div class=\"" . $lineclass . "\"></div>";
echo "</div>"; //bb_header
/* END ECHO TABS */

/* UNSET UNNEEDED VARS BEFORE INCLUDE */
/* so not passed to modules */
unset($selected);
unset($lineclass);
unset($admin_tab);
unset($setup_tab);
unset($query);
unset($result);

/* INCLUDE APPROPRIATE MODULE */
//end id = header, end tab sections
//chose out whatever module you want to load

echo "<div id=\"bb_wrapper\">";
if (array_key_exists($module, $arr_controller[4]))
    //setup tab
    {
    echo "<div id=\"bb_admin_menu\">";
    //echo setup buttons side menu
    foreach ($arr_controller[4] as $key => $value)
        {     
        echo "<button class=\"menu\" name=\"" . $key . "_name\" value=\"" . $key . "_value\"  onclick=\"bb_submit_form(0,'" . $key . "')\">" . $value . "</button>";
        }
    echo "</div>";
    
    //$arr_controller no longer needed
    unset($arr_controller);
    unset($key);
    unset($value);
    echo "<div id=\"bb_admin_content\">";
    //$controller_path is reserved, this "include" includes the current module
    include($controller_path);
    echo "</div>";
    echo "<div class=\"clear\"></div>";
    }
elseif (array_key_exists($module, $arr_controller[5]))
    //admin tab
    {
    echo "<div id=\"bb_admin_menu\">";
    //echo admin buttons side menu
    foreach ($arr_controller[5] as $key => $value)
        {
        echo "<button class=\"menu\" name=\"" . $key . "_name\" value=\"" . $key . "_value\" onclick=\"bb_submit_form(0,'" . $key . "')\">" . $value . "</button>";
        }
    echo "</div>";
    //$arr_controller no longer needed
    unset($arr_controller);
    unset($key);
    unset($value);
    echo "<div id=\"bb_admin_content\">";
    //$controller_path is reserved, this "include" includes the current module
    include($controller_path);    
    echo "</div>";
    echo "<div class=\"clear\"></div>";
    }
else 
    {
    //include for all primary tabs
    //works for both guest and user tabs
    echo "<div id=\"bb_content\">";
    //$controller_path is reserved, this "include" includes the current module
    unset($key);
    unset($value);
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
include("bb-utilities/bb_database.php");
include("bb-utilities/bb_link.php");
include("bb-utilities/bb_validate.php");
include("bb-utilities/bb_form.php");
include("bb-utilities/bb_work.php");
include("bb-utilities/bb_report.php");
include("bb-utilities/bb_main.php");

$main = new bb_main();

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
    $query = "SELECT email, hash, salt, attempts, array_to_string(userroles,',') as userroles, fname, minit, lname FROM users_table WHERE NOT (0 = ANY (userroles)) AND UPPER(email) = UPPER('". pg_escape_string($email) . "') AND attempts <= 10;";
    
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
			$_SESSION['userroles'] = explode(",", $row['userroles']);
			$_SESSION['userrole'] = $_SESSION['userroles'][0];
			$_SESSION['archive'] = 0;
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

