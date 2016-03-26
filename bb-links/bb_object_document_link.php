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

if (isset ( $_SESSION ['username'] )) :
	
	/* SET UP MAIN OBJECT */
	include ("../bb-config/bb_constants.php"); // need FILE PERMISSIONS
	
	if (file_exists ( "../bb-extend/include_main.php" )) {
		include_once ("/bb-extend/include_main.php");
	} else {
		include_once ("../bb-utilities/bb_include_main.php");
	}
	// main instance
	$main = new bb_main ();
	
	$userroles = $main->get_constant ( 'BB_DOCUMENT_DOWNLOAD_PERMISSIONS', "3_bb_brimbox,4_bb_brimbox,5_bb_brimbox" );
	$main->check_permission ( $userroles );
	
	set_time_limit ( 0 );
	$con = $main->connect ();
	
	// convert to integer for security
	if (filter_var ( $_POST ['bb_object'], FILTER_VALIDATE_INT )) {
		$where_clause = " id = " . $_POST ['bb_object'];
	} else {
		$where_clause = " filename = '" . $_POST ['bb_object'] . "'";
	}
	
	$query = "SELECT * FROM docs_table WHERE " . $where_clause . ";";
	$result = $main->query ( $con, $query );
	
	if (pg_num_rows ( $result ) != 1)
		die ( "Unable to find file." );
	
	$row = pg_fetch_array ( $result );
	$filename = $row ['filename'];
	$document = pg_unescape_bytea ( $row ['document'] );
	
	// Here go the headers
	header ( "Content-Type: application/octet-stream" );
	header ( "Content-disposition: attachment; filename=\"$filename\"" );
	header ( "Content-Transfer-Encoding: binary" );
	ob_clean ();
	flush ();
	
	echo $document;



endif;

?>