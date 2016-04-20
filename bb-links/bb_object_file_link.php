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
                                        
// userrole not passed in from controller
session_name ( DB_NAME );
session_start ();
session_regenerate_id ();

/* SET UP WORK OBJECT AND POST STUFF */
// objects are all daisy chained together
// set up work from last object
// contains bb_database class, extends bb_main
// constants include -- some constants are used
$abspath = $_SESSION ['abspath'];
include_once ($abspath . "/bb-config/bb_constants.php");
// include build class object
if (file_exists ( $abspath . "/bb-extend/bb_include_main_class.php" ))
	include_once ($abspath . "/bb-extend/bb_include_main_class.php");
else
	include_once ($abspath . "/bb-blocks/bb_include_main_class.php");
	
// main object for hooks
$main = new bb_main ();

$userroles = $main->get_constant ( 'BB_DOCUMENT_FILE_PERMISSIONS', '3_bb_brimbox,4_bb_brimbox,5_bb_brimbox' );
$main->check_permission ( explode ( ",", $userroles ) );

set_time_limit ( 0 );
$con = $main->connect ();

// convert to integer for security
$post_key = ( int ) $_POST ['bb_object'];

$query = "SELECT c47 FROM data_table WHERE id = " . $post_key . ";";
$result = $main->query ( $con, $query );

if (pg_num_rows ( $result ) != 1)
	die ( "Unable to find file." );

$row = pg_fetch_array ( $result );
$filename = $row ['c47'];

// Here go the headers
header ( "Content-Type: application/octet-stream" );
header ( "Content-disposition: attachment; filename=\"$filename\"" );
header ( "Content-Transfer-Encoding: binary" );
ob_clean ();
flush ();

pg_query ( $con, "BEGIN" );
$handle = pg_lo_open ( $con, $post_key, "r" ) or die ( "File Error" );
pg_lo_read_all ( $handle ) or die ( "File Error" );
pg_query ( $con, "COMMIT" );

?>