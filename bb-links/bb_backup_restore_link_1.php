<?php
/*
 * Copyright (C) Brimbox LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License Version 3 (GNU GPL v3)
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU GPL v3 for more details.
 *
 * You should have received a copy of the GNU GPL v3 along with this program.
 * If not, see http://www.gnu.org/licenses/
*/
define('BASE_CHECK', true);
include ("../bb-config/bb_config.php");

// NEED VERSION INFORMATION
include ("../bb-utilities/bb_version.php");

session_name(DB_NAME);
session_start();
session_regenerate_id();

$abspath = $_SESSION['abspath'];
include_once ($abspath . "/bb-config/bb_constants.php");
// include build class object
if (file_exists($abspath . "/bb-extend/bb_include_main_class.php")) include_once ($abspath . "/bb-extend/bb_include_main_class.php");
else include_once ($abspath . "/bb-blocks/bb_include_main_class.php");

// main instance
$main = new bb_main();

// will check SESSION
$main->check_permission("5_bb_brimbox");

/* INITIALIZE */
// version 2014.1.4 added ips
$backup = BRIMBOX_BACKUP; // probably change with database design, backup type
$program = BRIMBOX_PROGRAM; // probably change with database design, backup type
$database = BRIMBOX_DATABASE;

$db_user = DB_USER;
$db_name = DB_NAME;

$str_encrypt = "";
set_time_limit(0);
$con = $main->connect();

$passwd = $_POST['backup_passwd'];
$type = ( int )$_POST['encrypt_method'];

// validate password
$valid_password = $main->validate_password($con, $passwd, "5_bb_brimbox");
if (!$valid_password) {
    die("Invalid Password");
}

function encrypt_line($str, $passwd, $iv, $type) {

    switch ($type) {
        case 0: // basically unencoded
            $str = base64_encode(gzdeflate($str));
        break;
        case 1: // MCRYPT_3DES + compresssion
            $str = base64_encode(mcrypt_encrypt(MCRYPT_3DES, $passwd, gzdeflate($str), MCRYPT_MODE_CBC, $iv));
        break;
    }
    return $str;
}

// output headers
header("Content-Type: application/octet-stream");
header("Content-disposition: attachment; filename=backup.bbdb");
header("Content-Transfer-Encoding: binary");
ob_clean();
flush();

// get hash, salt, and iv
$iv_size = mcrypt_get_iv_size(MCRYPT_3DES, MCRYPT_MODE_CBC);
$salt = md5(microtime());
$hash = hash('sha512', $passwd . $salt);
$iv = substr($salt, 0, 8);

// encryption type
// 00000000 -- no encrypt before userrole => userroles
// 00000001 -- encrypt before userrole => userroles
if ($type == 0) // no encrypt
{
    // left 2 digits should be encrypt method
    $hex = "0000000A";
    $eol = "\r\n";
}
elseif ($type == 1) // MCRYPT_3DES + Compression
{
    $hex = "0000000B";
    $eol = "\r\n";
}

// echo first line, not encypted
echo $hex . $salt . $hash . $eol;

// back up stats
$json_header = array();
$json_header['backup'] = $backup;
$json_header['program'] = $program;
$json_header['database'] = $database;
$json_header['db_user'] = $db_user;
$json_header['db_name'] = $db_name;

date_default_timezone_set(USER_TIMEZONE);
$datetime = date('m/d/Y h:i:s a', time());
$json_header['datetime'] = $datetime;

// echo second line, backup stats, encrypted
$str = json_encode($json_header);
echo encrypt_line($str, $passwd, $iv, $type) . $eol;

/* TABLES ORDERED FOR QUICKER RESTORE */
// since data table is last it can be skipped on upload if not restored
/* JSON TABLE */
// lock and get json table
$query = "BEGIN; LOCK TABLE json_table;";
$main->query($con, $query);
// get exact columns desired
$query = "SELECT lookup, jsondata, change_date FROM json_table;";
$result = $main->query($con, $query);
$cnt = pg_num_rows($result);
$query = "COMMIT;";
$main->query($con, $query);

// json table stats in xml form, count is important
$json_json = array();
$json_json['count'] = $cnt;
$str = json_encode($json_json);
echo encrypt_line($str, $passwd, $iv, $type) . $eol;

// echo json rows
while ($row = pg_fetch_row($result)) {
    // implode into string
    $str = implode("\t", $row);
    // encrypt and compress
    $str = encrypt_line($str, $passwd, $iv, $type) . $eol;
    echo $str;
}

/* USERS TABLE */
$query = "BEGIN; LOCK TABLE users_table;";
$main->query($con, $query);
$query = "SELECT username, email, hash, salt, attempts, userroles, fname, minit, lname, notes, ips, change_date FROM users_table;";
$result = $main->query($con, $query);
$cnt = pg_num_rows($result);
$query = "COMMIT;";
$main->query($con, $query);

$json_users = array();
$json_users['count'] = $cnt;
$str = json_encode($json_users);
echo encrypt_line($str, $passwd, $iv, $type) . $eol;

while ($row = pg_fetch_row($result)) {
    $str = implode("\t", $row);
    $str = encrypt_line($str, $passwd, $iv, $type) . $eol;
    echo $str;
}

/* MODULES TABLE */
$query = "BEGIN; LOCK TABLE modules_table;";
$main->query($con, $query);
$query = "SELECT module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details, change_date FROM modules_table;";
$result = $main->query($con, $query);
$cnt = pg_num_rows($result);
$query = "COMMIT;";
$main->query($con, $query);

$json_modules = array();
$json_modules['count'] = $cnt;
$str = json_encode($json_modules);
echo encrypt_line($str, $passwd, $iv, $type) . $eol;

while ($row = pg_fetch_row($result)) {
    $str = implode("\t", $row);
    $str = encrypt_line($str, $passwd, $iv, $type) . $eol;
    echo $str;
}

/* LOG TABLE */
$query = "BEGIN; LOCK TABLE log_table;";
$main->query($con, $query);
$query = "SELECT username, email, ip_address, action, change_date FROM log_table;";
$result = $main->query($con, $query);
$cnt = pg_num_rows($result);
$query = "COMMIT;";
$main->query($con, $query);

$json_log = array();
$json_log['count'] = $cnt;
$str = json_encode($json_log);
echo encrypt_line($str, $passwd, $iv, $type) . $eol;

while ($row = pg_fetch_row($result)) {
    $str = implode("\t", $row);
    $str = encrypt_line($str, $passwd, $iv, $type) . $eol;
    echo $str;
}

/* JOIN TABLE */
$query = "BEGIN; LOCK TABLE join_table;";
$main->query($con, $query);
$query = "SELECT join1, join2, join_date FROM join_table;";
$result = $main->query($con, $query);
$cnt = pg_num_rows($result);
$query = "COMMIT;";
$main->query($con, $query);

$json_join = array();
$json_join['count'] = $cnt;
$str = json_encode($json_join);
echo encrypt_line($str, $passwd, $iv, $type) . $eol;

while ($row = pg_fetch_row($result)) {
    $str = implode("\t", $row);
    $str = encrypt_line($str, $passwd, $iv, $type) . $eol;
    echo $str;
}

/* DATA TABLE */
$arr_cols = array();
for ($i = 1;$i <= 50;$i++) {
    $col = "c" . str_pad(( string )$i, 2, "0", STR_PAD_LEFT);
    array_push($arr_cols, $col);
}
$str_cols = implode(",", $arr_cols);
$query = "BEGIN; LOCK TABLE data_table;";
$main->query($con, $query);
$query = "SELECT id, row_type, key1, key2," . $str_cols . ", archive, secure, create_date, modify_date, owner_name, updater_name, list_string FROM data_table;";
$result = $main->query($con, $query);
$cnt = pg_num_rows($result);
$query = "COMMIT;";
$main->query($con, $query);

$json_data = array();
$json_data['count'] = $cnt;
$str = json_encode($json_data);
echo encrypt_line($str, $passwd, $iv, $type) . $eol;

while ($row = pg_fetch_row($result)) {
    // we absolutely don't want backup file messed up
    // slow but necessary
    for ($i = 4;$i <= 53;$i++) {
        $row[$i] = str_replace("\t", "", $row[$i]);
    }

    $str = implode("\t", $row);
    $str = encrypt_line($str, $passwd, $iv, $type) . $eol;
    echo $str;
}
?>