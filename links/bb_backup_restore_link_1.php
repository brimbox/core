<?php
/*
Copyright (C) 2012 - 2013  Kermit Will Richardson, Brimbox LLC

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

/* NO HTML OUTPUT ALLOWED */
$main = new bb_main(); //extends bb_database

session_name(DB_NAME);
session_start();

$main->check_permission(5);

/* INITIALIZE */
$backup = "2012.1.2"; //probably change with database design, backup type
$str_encrypt = "";
set_time_limit(0);
$con = $main->connect();

$passwd = $_POST['backup_passwd'];
$type = (int)$_POST['encrypt_method'];
$userrole = $_SESSION['userrole'];
$email = $_SESSION['email'];

//validate password
$valid_password = $main->validate_login($con, $email, $passwd, $userrole);
if (!$valid_password)
	{
	die("Invalid Password");	
	}

//xml can be loaded with new line chars, which cause problems
//used on xml, rest of table should already be clean
function purge_chars($str, $eol = true)
	{
	if ($eol)
		{
		//changes a bunch of control chars to single spaces
		$pattern = "/[\\t\\0\\x0B\\x0C\\r\\n]+/";
		$str = preg_replace($pattern, " ", $str);
		}
	else
		{
		//changes a bunch of control chars to single spaces except for new lines
		$pattern = "/[\\t\\0\\x0B\\x0C\\r]+/";
		$str = trim(preg_replace($pattern, " ", $str)); //trim this one twice here
		//clean up eol spaces
		$pattern = "/ {0,}(\\n{1}) {0,}/";
		$str = preg_replace($pattern, "\n", $str);
		}
	//trim again because truncate could leave ending space, then try to encode
	$str = utf8_encode(trim($str));
	return $str;
	}
	
function encrypt_line($str, $passwd, $iv, $type)
	{
	switch ($type)
		{
		case 0: //basically unencoded
		$str =  base64_encode(gzdeflate($str));
		break;
		case 1: //MCRYPT_3DES + compresssion
		$str = base64_encode(mcrypt_encrypt(MCRYPT_3DES, $passwd, gzdeflate($str), MCRYPT_MODE_CBC, $iv));
		break;
		}
	return $str;
	}

//output headers	
header ("Content-Type: application/octet-stream");
header ("Content-disposition: attachment; filename=backup.bbdb");
header ("Content-Transfer-Encoding: binary");
ob_clean();
flush();

//get hash, salt, and iv
$iv_size = mcrypt_get_iv_size(MCRYPT_3DES, MCRYPT_MODE_CBC);
$salt = md5(microtime());
$hash = hash('sha512', $passwd . $salt);
$iv = substr($salt,0,8);

//encryption type
if ($type == 0) //no encrypt 
	{
	//left 2 digits should be encrypt method
	$hex = "00000000";
	$eol = "\r\n";
	}
elseif ($type == 1) //MCRYPT_3DES + Compression
	{
	$hex = "00000001";
	$eol = "\r\n";
	}
	
//echo first line, not encypted
echo $hex . $salt . $hash . $eol;

//back up stats
$xml_header = simplexml_load_string("<header/>");
$xml_header->backup = $backup;

$xml_version = $main->get_xml($con, "bb_manage_modules");
if (isset($xml_version->program))
	{
	$xml_header->program = (string)$xml_version->program;
	}
if (isset($xml_version->database))
	{
	$xml_header->database = (string)$xml_version->database;
	}
date_default_timezone_set(USER_TIMEZONE);
$datetime = date('m/d/Y h:i:s a', time());
$xml_header->addChild("datetime", $datetime);

//echo second line, backup stats, encrypted	
$str = $xml_header->asXML();
echo encrypt_line($str, $passwd, $iv, $type) . $eol;

/* TABLES ORDERED FOR QUICKER RESTORE */
//since data table is last it can be skipped on upload if not restored

/* XML TABLE */
//lock and get xml table
$query = "BEGIN; LOCK TABLE xml_table;";
$main->query($con,$query);
//get exact columns desired
$query = "SELECT lookup, xmldata, change_date FROM xml_table;";
$result = $main->query($con,$query);
$cnt = pg_num_rows($result);
$query = "COMMIT;";
$main->query($con,$query);

//xml table stats in xml form, count is important
$xml_xml = simplexml_load_string("<xml/>");
$xml_xml->count = $cnt;
$str = $xml_xml->asXML();
echo encrypt_line($str, $passwd, $iv, $type) . $eol;

//echo xml rows
while ($row = pg_fetch_row($result))
	{
	//implode into string
	$str =  implode("\t", $row);
	//encrypt and compress
	$str = encrypt_line($str, $passwd, $iv, $type) . $eol;
	echo $str;
	}

/* USERS, MODULES, LOG, AND DATA TABLES */
/* ALL FOLLOW COMMENTS OF XML TABLE */

/* USERS TABLE */
$query = "BEGIN; LOCK TABLE users_table;";
$main->query($con,$query);
$query = "SELECT email, hash, salt, attempts, userrole, fname, minit, lname, change_date FROM users_table;";
$result = $main->query($con,$query);
$cnt = pg_num_rows($result);
$query = "COMMIT;";
$main->query($con,$query);

$xml_users = simplexml_load_string("<users/>");
$xml_users->count = $cnt;
$str = $xml_users->asXML();
echo encrypt_line($str, $passwd, $iv, $type) . $eol;

while ($row = pg_fetch_row($result))
	{
	$str =  implode("\t", $row);
	$str = encrypt_line($str, $passwd, $iv, $type) . $eol;
	echo $str;	
	}

/* MODULES TABLE */
$query = "BEGIN; LOCK TABLE modules_table;";
$main->query($con,$query);
$query = "SELECT module_order, module_path, module_name, friendly_name, module_version, module_type, userrole, standard_module, " .
		 "maintain_state, module_files, module_details, change_date FROM modules_table;";
$result = $main->query($con,$query);
$cnt = pg_num_rows($result);
$query = "COMMIT;";
$main->query($con,$query);

$xml_modules = simplexml_load_string("<modules/>");
$xml_modules->count = $cnt;
$str = $xml_modules->asXML();
echo encrypt_line($str, $passwd, $iv, $type) . $eol;

while ($row = pg_fetch_row($result))
	{
	$str =  implode("\t", $row);
	$str = encrypt_line($str, $passwd, $iv, $type) . $eol;
	echo $str;	
	}

/* LOG TABLE */
$query = "BEGIN; LOCK TABLE log_table;";
$main->query($con,$query);
$query = "SELECT email, ip_address, action, change_date FROM log_table;";
$result = $main->query($con,$query);
$cnt = pg_num_rows($result);
$query = "COMMIT;";
$main->query($con,$query);


$xml_log = simplexml_load_string("<log/>");
$xml_log->count = $cnt;
$str = $xml_log->asXML();
echo encrypt_line($str, $passwd, $iv, $type) . $eol;

while ($row = pg_fetch_row($result))
	{
	$str =  implode("\t", $row);
	$str = encrypt_line($str, $passwd, $iv, $type) . $eol;
	echo $str;
	}

/* DATA TABLE */
$arr_cols = array();
for ($i=1; $i<=50; $i++)
	{
	$col = "c" . str_pad((string)$i, 2, "0", STR_PAD_LEFT);
	array_push($arr_cols, $col);
	}
$str_cols = implode(",",$arr_cols);
$query = "BEGIN; LOCK TABLE data_table;";
$main->query($con,$query);
$query = "SELECT id, row_type, key1, key2," . $str_cols . ", archive, secure, create_date, modify_date, owner_name, updater_name, list_string FROM data_table;";
$result = $main->query($con,$query);
$cnt = pg_num_rows($result);
$query = "COMMIT;";
$main->query($con,$query);


$xml_data = simplexml_load_string("<log/>");
$xml_data->count = $cnt;
$str = $xml_data->asXML();
echo encrypt_line($str, $passwd, $iv, $type) . $eol;		

while ($row = pg_fetch_row($result))
	{
	//we absolutely don't want backup file messed up
	//slow but necessary
	for ($i=4; $i<=51; $i++)
		{
		$row[$i] = purge_chars($row[$i]);	
		}
	//do not purge \n
	$row[52] = purge_chars($row[52], false);
	$row[53] = purge_chars($row[53], false);

	$str = implode("\t", $row);
	$str = encrypt_line($str, $passwd, $iv, $type)  . $eol;
	echo $str;
	}
?>