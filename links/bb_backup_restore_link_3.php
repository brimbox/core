<?php
/*
Copyright (C) 2012 - 2013  Kermit Will Richardson, Brimbox LLC

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
/* NO HTML OR BLANK LINE OUTPUT ALLOWED */

include("../bb-config/bb_config.php");
define('BASE_CHECK', true);
//objects extended together
include("../bb-utilities/bb_database.php");
include("../bb-utilities/bb_link.php");
include("../bb-utilities/bb_validate.php");
include("../bb-utilities/bb_form.php");
include("../bb-utilities/bb_work.php");
include("../bb-utilities/bb_report.php");
include("../bb-utilities/bb_main.php");

$main = new bb_main(); //extends bb_database

//userrole not passed in from controller
session_name(DB_NAME);
session_start();

$main->check_permission(5);

/* INITIALIZE */
$con = $main->connect();
set_time_limit(0);

//standard eol
$eol = "\r\n";
//this will remove control chars which should not exist
$pattern = "/[\\t\\0\\x0B\\x0C\\r\\n]+/";

$passwd = $_POST['dump_passwd'];
$userrole = $_SESSION['userrole'];
$email = $_SESSION['email'];

$valid_password = $main->validate_login($con, $email, $passwd, $userrole);

if (!$valid_password)
	{
	die("Invalid Password");
	}
?>
<?php
/* THIS IS A TEXT FILE HEADER OUTPUT */
/* NO HTML OR BLANK LINE OUTPUT ALLOWED */
$filename = "List_Definitions_Dump.txt";
    
//Here go the headers
header ("Content-Type: application/octet-stream");
header ("Content-disposition: attachment; filename=" . $filename . "");
header ("Content-Transfer-Encoding: binary");
ob_clean();
flush();

$xml_lists = $main->get_xml($con, "bb_create_lists");

$arr_row = array();
array_push($arr_row, "list_number");
array_push($arr_row, "list_name");
array_push($arr_row,"row_type");
array_push($arr_row, "description");
array_push($arr_row, "archive");

$str = implode("\t", $arr_row) . $eol;
echo $str;

foreach ($xml_lists->children() as $child)
	{
    $arr_row = array();
	$list_number = $main->rpad($child->getName());
	array_push($arr_row, $list_number);
	array_push($arr_row, trim(preg_replace($pattern, " ", (string)$child)));
	array_push($arr_row, $child['row_type']);
	array_push($arr_row, trim(preg_replace($pattern, " ", $child['description'])));
	array_push($arr_row, $child['archive']);
	
	$str = implode("\t", $arr_row) . $eol;
	echo $str;	
	}
?>