<?php
/*
 * Copyright (C) Brimbox LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License Version 3 (“GNU GPL v3”)
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
/* NO HTML OR BLANK LINE OUTPUT ALLOWED */
define ( 'BASE_CHECK', true );
include ("../bb-config/bb_config.php");

// userrole not passed in from controller
session_name ( DB_NAME );
session_start ();

// extendable include
if (file_exists ( "../bb-extend/include_main.php" )) {
	include_once ("/bb-extend/include_main.php");
} else {
	include_once ("../bb-utilities/bb_include_main.php");
}
// main instance
$main = new bb_main ();

$main->check_permission ( "bb_brimbox", 5 );

/* INITIALIZE */
$con = $main->connect ();
set_time_limit ( 0 );

// standard eol
$eol = "\r\n";
// this will remove control chars which should not exist
$pattern = "/[\\t\\0\\x0B\\x0C\\r\\n]+/";

$passwd = $_POST ['dump_passwd'];

$valid_password = $main->validate_password ( $con, $passwd, "5_bb_brimbox" );

if (! $valid_password) {
	die ( "Invalid Password" );
}
?>
<?php

/* THIS IS A TEXT FILE HEADER OUTPUT */
/* NO HTML OR BLANK LINE OUTPUT ALLOWED */
$filename = "List_Definitions_Dump.txt";

// Here go the headers
header ( "Content-Type: application/octet-stream" );
header ( "Content-disposition: attachment; filename=" . $filename . "" );
header ( "Content-Transfer-Encoding: binary" );
ob_clean ();
flush ();

$arr_lists = $main->get_json ( $con, "bb_create_lists" );

$arr_row = array ();
array_push ( $arr_row, "list_number" );
array_push ( $arr_row, "list_name" );
array_push ( $arr_row, "row_type" );
array_push ( $arr_row, "description" );
array_push ( $arr_row, "archive" );

$str = implode ( "\t", $arr_row ) . $eol;
echo $str;
foreach ( $arr_lists as $row_type => $arr_list ) {
	foreach ( $arr_list as $key2 => $value ) {
		$arr_row = array ();
		array_push ( $arr_row, $key2 );
		array_push ( $arr_row, trim ( preg_replace ( $pattern, " ", $value ['name'] ) ) );
		array_push ( $arr_row, $row_type );
		array_push ( $arr_row, trim ( preg_replace ( $pattern, " ", $value ['description'] ) ) );
		array_push ( $arr_row, $value ['archive'] );
		
		$str = implode ( "\t", $arr_row ) . $eol;
		echo $str;
	}
}
?>