<?php
/*
Copyright (C) 2012 - 2015  Kermit Will Richardson, Brimbox LLC

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
define('BASE_CHECK', true);
include("../bb-config/bb_config.php");

/* INCLUDE ALL BRIMBOX STANDARD FUNCTIONS */
include("../bb-utilities/bb_main.php");
include("../bb-utilities/bb_database.php");
include("../bb-utilities/bb_links.php");
include("../bb-utilities/bb_validate.php");
include("../bb-utilities/bb_forms.php");
include("../bb-utilities/bb_work.php");
include("../bb-utilities/bb_hooks.php");
include("../bb-utilities/bb_reports.php");

/* SET UP MAIN OBJECT */
$main = new bb_reports();

//NEED VERSION INFORMATION
include("../bb-utilities/bb_headers.php");

session_name(DB_NAME);
session_start();

$main->check_permission("bb_brimbox", 5);

/* INITIALIZE */
//version 2014.1.4 added ips
$backup = BRIMBOX_BACKUP; //probably change with database design, backup type
$program = BRIMBOX_PROGRAM; //probably change with database design, backup type
$database = BRIMBOX_DATABASE;

$db_user = DB_USER;
$db_name = DB_NAME;

$str_encrypt = "";
set_time_limit(0);
$con = $main->connect();

$passwd = $_POST['backup_passwd'];
$type = $_POST['encrypt_method'];

//validate password
$valid_password = $main->validate_password($con, $passwd, "5_bb_brimbox");
if (!$valid_password)
	{
	die("Invalid Password");	
	}
//encrypt	
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
header ("Content-disposition: attachment; filename=backup.bblo");
header ("Content-Transfer-Encoding: binary");
ob_clean();
flush();

//get hash, salt, and iv
$iv_size = mcrypt_get_iv_size(MCRYPT_3DES, MCRYPT_MODE_CBC);
$salt = md5(microtime());
$hash = hash('sha512', $passwd . $salt);
$iv = substr($salt,0,8);

//encryption type
//00000006 -- no encrypt before userrole => userroles
//00000007 -- encrypt before userrole => userroles
if ($type == 0) //no encrypt 
	{
	//left 2 digits should be encrypt method
	$hex = "00000006";
	$eol = "\r\n";
	}
elseif ($type == 1) //MCRYPT_3DES + Compression
	{
	$hex = "00000007";
	$eol = "\r\n";
	}
	
//echo first line, not encypted
echo $hex . $salt . $hash . $eol;

//back up stats
$json_header = array();
$json_header['backup'] = $backup;
$json_header['program'] = $program;
$json_header['database'] = $database;
$json_header['db_user'] = $db_user;
$json_header['db_name'] = $db_name;
date_default_timezone_set(USER_TIMEZONE);
$datetime = date('m/d/Y h:i:s a', time());
$json_header['datetime'] = $datetime;
//query c47
$query = "SELECT id, c47 FROM data_table WHERE c47 <> ''";
$result = $main->query($con, $query);
//continue header after query
$json_header['total'] = pg_num_rows($result);
$str = json_encode($json_header);
echo encrypt_line($str, $passwd, $iv, $type) . $eol;

while($row = pg_fetch_array($result))
	{
    $id = $row['id'];
    $filename = $row['c47'];
    pg_query($con, "BEGIN");
    $lo = pg_lo_open($con, $row['id'], "r");
    if ($lo)
        {
        //opened and in transaction
        pg_lo_seek($lo, 0, PGSQL_SEEK_END);
        $page = 8092;
        $length = pg_lo_tell($lo);
        $cnt = floor($length / $page);
        $remainder = $length - ($cnt * $page);
        pg_lo_seek($lo, 0, PGSQL_SEEK_SET);
        
        $json_info = array('id'=>$id,'filename'=>$filename,'length'=>$length,'count'=>$cnt,'remainder'=>$remainder,'page'=>$page);
        $str = json_encode($json_info);
        echo encrypt_line($str, $passwd, $iv, $type) . $eol;
        
        for ($i=1; $i<=$cnt; $i++)
            {
            $str = encrypt_line(pg_lo_read($lo, $page), $passwd, $iv, $type) . $eol;
            echo $str;
            }
        $str = encrypt_line(pg_lo_read($lo, $remainder), $passwd, $iv, $type) . $eol;
        echo $str;
        pg_lo_close($lo);
        }
    else
        {
        //unable to open
        $json_info = array('id'=>$id,'filename'=>$filename,'length'=>0,'count'=>0,'remainder'=>0,'page'=>0);
        $str = json_encode($json_info);
        echo encrypt_line($str, $passwd, $iv, $type) . $eol;
        }
    pg_query($con, "COMMIT");
    }

?>