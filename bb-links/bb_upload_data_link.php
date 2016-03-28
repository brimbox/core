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
include ("../bb-config/bb_config.php"); // need DB_NAME

if (file_exists ( "../bb-extend/include_main.php" )) {
	include_once ("/bb-extend/include_main.php");
} else {
	include_once ("../bb-utilities/bb_include_main.php");
}
// main instance
$main = new bb_main ();

// userrole not passed in from controller
session_name ( DB_NAME );
session_start ();
$main->check_permission ( array (
		"4_bb_brimbox",
		"5_bb_brimbox" 
) );
?>
<?php

/* THIS IS A TEXT FILE HEADER OUTPUT */
/* NO HTML OR BLANK LINE OUTPUT ALLOWED */
$filename = ($_POST ['bb_data_file_name'] == "") ? "default" : $_POST ['bb_data_file_name'];
$extension = "txt";
$text = ($_POST ['bb_data_area'] == "") ? "" : $_POST ['bb_data_area'];

// Here go the headers
header ( "Content-Type: application/octet-stream" );
header ( "Content-disposition: attachment; filename=\"" . $filename . "." . $extension . "\"" );
header ( "Content-Transfer-Encoding: binary" );
ob_clean ();
flush ();
echo $text;
?>