<?php
/*
 * Copyright (C) Brimbox LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License Version 3 (�GNU GPL v3�)
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
?>
<?php

/* NO HTML OUTPUT */
define ( 'BASE_CHECK', true );
// need DB_NAME from bb_config, must not have html output including blank spaces
include ("bb-config/bb_config.php"); // need DB_NAME

session_name ( DB_NAME );
session_start ();

if (isset ( $_SESSION ['username'] )) :

    /* keep as lightweight as possible */
	
	// set by controller (index.php)
	$keeper = $_SESSION ['keeper'];
    $webpath = $_SESSION ['webpath'];
    
    // set by javascript submit form (bb_submit_form())
    $_SESSION ['button'] = $button = isset ( $_POST ['bb_button'] ) ? $_POST ['bb_button'] : 0;
    $_SESSION ['module'] = $module = isset ( $_POST ['bb_module'] ) ? $_POST ['bb_module'] : "";
    $_SESSION ['slug'] = $slug = isset ( $_POST ['bb_slug'] ) ? $_POST ['bb_slug'] : "";
    $_SESSION ['submit'] = $submit = isset ( $_POST ['bb_submit'] ) ? $_POST ['bb_submit'] : "";
	
	// set by javascript submit form (bb_submit_form())
	$_SESSION ['slug'] = $slug = isset ( $_POST ['bb_slug'] ) ? $_POST ['bb_slug'] : "";
    
	// include build class object
	$con_string = "host=" . DB_HOST . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASSWORD;
	$con = pg_connect ( $con_string );
	
	$POST = $_POST;
	
	$postdata = json_encode ( $POST );
	
	// set $_POST for $POST
	$query = "UPDATE state_table SET postdata = $1 WHERE id = " . $keeper . ";";
	pg_query_params ( $con, $query, array (
			$postdata 
	) );
	
	// REDIRECT
	header ( "Location: " . $webpath . "/" . $slug );
	
	die ();
    
endif;
?>