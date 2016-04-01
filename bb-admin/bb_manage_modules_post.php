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
?>
<?php

/* NO HTML OUTPUT */
define ( 'BASE_CHECK', true );
// need DB_NAME from bb_config, must not have html output including blank spaces
include ("../bb-config/bb_config.php"); // need DB_NAME

session_name ( DB_NAME );
session_start ();
session_regenerate_id ();

if (isset ( $_SESSION ['username'] ) && in_array ( $_SESSION ['userrole'], array (
		"5_bb_brimbox" 
) )) :
	
    // set by controller (index.php)
    $interface = $_SESSION ['interface'];
    $username = $_SESSION ['username'];
    $userrole = $_SESSION ['userrole'];
    $webpath = $_SESSION ['webpath'];
    $keeper = $_SESSION ['keeper'];
    $abspath = $_SESSION ['abspath'];
    
    // set by javascript submit form (bb_submit_form())
    $_SESSION ['button'] = $button = isset ( $_POST ['bb_button'] ) ? $_POST ['bb_button'] : 0;
    $_SESSION ['module'] = $module = isset ( $_POST ['bb_module'] ) ? $_POST ['bb_module'] : "";
    $_SESSION ['slug'] = $slug = isset ( $_POST ['bb_slug'] ) ? $_POST ['bb_slug'] : "";
    $_SESSION ['submit'] = $submit = isset ( $_POST ['bb_submit'] ) ? $_POST ['bb_submit'] : "";
			                                                 
	// constants include -- some constants are used
	include_once ($abspath . "/bb-config/bb_constants.php");
	// include build class object
    if (file_exists ( $abspath .  "/bb-extend/bb_include_main_class.php" ))
        include_once ($abspath . "/bb-extend/bb_include_main_class.php");
    else
        include_once ($abspath . "/bb-blocks/bb_include_main_class.php");
        
	// main object for hooks
	$main = new bb_main ();
	// need connection
	$con = $main->connect ();
	
	// get connection
	$con = $main->connect ();
	
	// load global arrays
	if (file_exists ( $abspath . "/bb-extend/bb_parse_globals.php" )) 
		include_once ($abspath . "/bb-extend/bb_parse_globals.php");
	else 
		include_once ($abspath . "/bb-blocks/bb_parse_globals.php");
	
	/* GET STATE AND $POST */
	$POST = $_POST;
	
	// get $arr_state
	$arr_state = $main->load ( $con, $submit );
	
	$arr_messages = array ();
	
	// state not preserved for this module
	$module_id = $main->post ( 'module_id', $submit, 0 );
	$module_action = $main->post ( 'module_action', $submit, 0 );
	$install_activated = $main->post ( 'install_activated', $submit, 1 );
	
	/* END MODULE VARIABLES FOR OPTIONAL MODULE HEADERS */
	
	// call helper class
	include ("bb_manage_modules_extra.php");
	$manage = new bb_manage_modules ();
	
	/* ACTIVATE, DEACTIVATE, DELETE, DETAILS MODULES */
	// this area handles the links in the table
	if ($module_action != 0) {
		/* ACTIVATE DEACTIVATE */
		if (in_array ( $module_action, array (
				3,
				4,
				5,
				6 
		) )) {
			if (($module_action == 4) || ($module_action == 6)) {
				// deactivate standard modules, standard_module = 1
				$query = "UPDATE modules_table SET standard_module = " . ($module_action - 1) . " WHERE id = " . $module_id . ";";
				$message = "Module has been deactivated.";
			} elseif (($module_action == 3) || ($module_action == 5)) {
				// activate standard modules, standard_module = 2
				$query = "UPDATE modules_table SET standard_module = " . ($module_action + 1) . " WHERE id = " . $module_id . ";";
				$message = "Module has been activated.";
			}
			// echo "<p>" . $query . "</p>";
			$result = $main->query ( $con, $query );
			
			if (pg_affected_rows ( $result ) == 0) // will do nothing if error
{
				array_push ( $arr_messages, "Error: No changes have been made." );
			} else {
				array_push ( $arr_messages, $message );
			}
		}
		
		/* DELETE MODULE */
		if ($module_action == - 2) {
			// delete by id
			$query = "DELETE FROM modules_table WHERE id = " . $module_id . " RETURNING module_name;";
			$result = $main->query ( $con, $query );
			// should return one row
			if (pg_affected_rows ( $result ) == 1) {
				$row = pg_fetch_array ( $result );
				// lookup should start with module name
				$query = "DELETE FROM json_table WHERE lookup LIKE '" . $row ['module_name'] . "%'";
				$main->query ( $con, $query );
				
				// reorder modules without deleted module
				$query = "UPDATE modules_table SET module_order = T1.order " . "FROM (SELECT row_number() OVER (PARTITION BY interface, module_type ORDER BY module_order) " . "as order, id FROM modules_table) T1 " . "WHERE modules_table.id = T1.id;";
				$main->query ( $con, $query );
				
				array_push ( $arr_messages, "Module has been deleted." );
			} else {
				array_push ( $arr_messages, "Error: No changes have been made." );
			}
			
			// reorder modules without deleted module
		}
		
		/* MODULE DETAILS */
		if ($module_action == - 1) {
			$query = "SELECT module_details FROM modules_table WHERE id = " . $module_id . ";";
			$result = $main->query ( $con, $query );
			if (pg_num_rows ( $result ) == 1) // get details
{
				$row = pg_fetch_array ( $result );
				$arr_details = json_decode ( $row ['module_details'], true );
			} else {
				$arr_details = array ();
			}
		}
	}
	/* END ACTIVATE, DEACTIVATE MODULES */
	
	/* BEGIN UPDATE PROGRAM */
	if ($main->button ( array (
			1 
	) )) {
		$valid_password = $main->validate_password ( $con, $main->post ( "install_passwd", $module ), "5_bb_brimbox" );
		if (! $valid_password) {
			// bad password
			array_push ( $arr_messages, "Error: Invalid or missing password." );
		} else {
			$main->empty_directory ( "bb-temp/" );
			// upload zip file to temp directory
			if (! empty ( $_FILES [$main->name ( 'update_file', $module )] ["tmp_name"] )) {
				if (substr ( $_FILES [$main->name ( 'update_file', $module )] ["name"], 0, 14 ) == "brimbox-update") {
					$zip = new ZipArchive ();
					$res = $zip->open ( $_FILES [$main->name ( 'update_file', $module )] ["tmp_name"] );
					if ($res === true) {
						$zip->extractTo ( 'bb-temp/' );
						$zip->close ();
						$main->copy_directory ( "bb-temp/update/", "" );
						include ("bb-utilities/bb_update.php");
						array_push ( $arr_messages, "Brimbox has been updated." );
					} else {
						array_push ( $arr_messages, "Error: Unable to open zip archive." );
					}
				} else {
					$arr_messages [] = "Error: Does not appear to be a Brimbox update.";
				}
			} else {
				$arr_messages [] = "Error: Must specify update file name.";
			}
			$main->empty_directory ( "bb-temp/", "bb-temp/" );
		}
	}
	
	/* BEGIN INSTALL OPTIONAL MODULES */
	if ($main->button ( 2 )) // submit_modules
{
		// empty temp directory
		$main->empty_directory ( "bb-temp/" );
		// upload zip file to temp directory
		if (! empty ( $_FILES [$main->name ( 'module_file', $module )] ["tmp_name"] )) {
			$zip = new ZipArchive ();
			$res = $zip->open ( $_FILES [$main->name ( 'module_file', $module )] ["tmp_name"] );
			if ($res === true) {
				$zip->extractTo ( $abspath . "/bb-temp/" );
				$zip->close ();
			} else {
				$arr_messages [] = "Error: Unable to open zip archive.";
			}
		} else {
			$arr_messages [] = "Error: Must specify module file name.";
		}
		
		// process header with extra class $manage
		if (! count ( $arr_messages )) {
			$arr_paths = $main->get_directory_tree ( $abspath . "/bb-temp/" );
			foreach ( $arr_paths as $path ) {
				$path = substr ( $path, strlen ( $abspath ) + 1 );
				$arr_module = array ();
				$pattern = "/\.php$/";
				// check for php file, then check to look for header
				if (preg_match ( $pattern, $path )) {
					// check PHP files for header, can be multiple headers
					// $arr_module passed as a reference
					$arr_module ['@module_path'] = $path;
					// call bb_manage_modules object
					$message = $manage->get_modules ( $con, $arr_module );
					// check for errors
					// can be true true, string, or has header
					if (is_string ( $message )) {
						$arr_messages [] = $message;
					} // populate if module_name is set, ignore included
elseif (isset ( $arr_module ['@module_name'] )) {
						$arr_modules [] = $arr_module;
					}
				}
			}
			// array should be blank if no PHP files
			if (empty ( $arr_modules )) {
				$arr_messages [] = "Error: Module zip file did not contain any valid Brimbox Module headers.";
			}
		}
		
		// no errors continue
		if (! count ( $arr_messages )) // !$message
{
			// this does insert with not exists lookup in insert cases
			$query = "SELECT module_name from modules_table;";
			$result = $main->query ( $con, $query );
			$arr_module_names = pg_fetch_all_columns ( $result );
			// will update $arr_module['@module_path']
			foreach ( $arr_modules as &$arr_module ) {
				$arr_module ['@module_path'] = $main->replace_root ( $arr_module ['@module_path'], "bb-temp/", "bb-modules/" );
				
				// insert json
				$arr_insert = array ();
				$pattern_1 = "/^@json-.*/";
				foreach ( $arr_module as $key => $value ) {
					if (preg_match ( $pattern_1, $key )) {
						$arr_insert [] = "INSERT INTO json_table (lookup, jsondata) SELECT '" . pg_escape_string ( substr ( $key, 6 ) ) . "' as lookup, '" . pg_escape_string ( $value ) . "' WHERE NOT EXISTS (SELECT 1 FROM json_table WHERE lookup IN ('" . substr ( $key, 6 ) . "'));";
					}
				}
				// should not have excessive json queries
				foreach ( $arr_insert as $value ) {
					$main->query ( $con, $value );
				}
				
				// optional install modules activated or unactivated
				if ($install_activated == 1) {
					$standard_module = ($arr_module ['@module_type'] == 0) ? 2 : 4;
				} else {
					$standard_module = ($arr_module ['@module_type'] == 0) ? 1 : 3;
				}
				
				// Update module
				if (in_array ( $arr_module ['@module_name'], $arr_module_names )) {
					// compensate when module is moved from one module type to another
					$module_order = "(SELECT CASE WHEN module_type <> " . $arr_module ['@module_type'] . " OR interface <> '" . $arr_module ['@module_type'] . "' THEN max(module_order) + 1 ELSE module_order END FROM modules_table " . "WHERE module_name = '" . $arr_module ['@module_name'] . "' GROUP BY interface, module_type, module_order)";
					$update_clause = "UPDATE modules_table SET module_order = " . $module_order . ", module_path = '" . pg_escape_string ( $arr_module ['@module_path'] ) . "',friendly_name = '" . pg_escape_string ( $arr_module ['@friendly_name'] ) . "', " . "interface = '" . pg_escape_string ( $arr_module ['@interface'] ) . "', module_type = " . $arr_module ['@module_type'] . ", module_version = '" . pg_escape_string ( $arr_module ['@module_version'] ) . "', " . "module_url = '" . pg_escape_string ( $arr_module ['@module_url'] ) . "', standard_module = " . $standard_module . ", " . "module_slug = '" . $arr_module ['@module_slug'] . "', module_files = '" . pg_escape_string ( $arr_module ['@module_files'] ) . "', module_details = '" . pg_escape_string ( $arr_module ['@module_details'] ) . "' ";
					$where_clause = "WHERE module_name = '" . pg_escape_string ( $arr_module ['@module_name'] ) . "'";
					$query = $update_clause . $where_clause . ";";
					// echo "<p>" . $query . "</p>";
					$message = "Module " . $arr_module ['@module_name'] . " has been updated.";
					$result = $main->query ( $con, $query );
					// reorder modules without deleted module
					$query = "UPDATE modules_table SET module_order = T1.order " . "FROM (SELECT row_number() OVER (PARTITION BY interface, module_type ORDER BY module_order) " . "as order, id FROM modules_table) T1 " . "WHERE modules_table.id = T1.id;";
					$main->query ( $con, $query );
				}  // Install module
else {
					// $module_order finds next available order number
					$module_order = "(SELECT CASE WHEN max(module_order) > 0 THEN max(module_order) + 1 ELSE 1 END FROM modules_table WHERE interface = '" . $arr_module ['@interface'] . "' AND module_type = " . $arr_module ['@module_type'] . ")";
					// INSERT query when inserting por reinstalling module
					$insert_clause = "(module_order, module_path, module_name, module_slug, friendly_name, interface, module_type, module_version,  module_url, standard_module, module_files, module_details)";
					$select_clause = $module_order . " as module_order, '" . pg_escape_string ( $arr_module ['@module_path'] ) . "' as module_path, '" . pg_escape_string ( $arr_module ['@module_name'] ) . "' as module_name, '" . pg_escape_string ( $arr_module ['@module_slug'] ) . "' as module_slug, " . "'" . pg_escape_string ( $arr_module ['@friendly_name'] ) . "' as friendly_name, '" . pg_escape_string ( $arr_module ['@interface'] ) . "' as interface, " . $arr_module ['@module_type'] . " as module_type, " . "'" . pg_escape_string ( $arr_module ['@module_version'] ) . "' as module_version, '" . pg_escape_string ( $arr_module ['@module_url'] ) . "' as module_url, " . $standard_module . " as standard_module, " . "'" . pg_escape_string ( $arr_module ['@module_files'] ) . "' as module_files, '" . pg_escape_string ( $arr_module ['@module_details'] ) . "'::xml as module_details";
					$query = "INSERT INTO modules_table " . $insert_clause . " " . "SELECT " . $select_clause . " WHERE NOT EXISTS (SELECT 1 FROM modules_table WHERE module_name IN ('" . $arr_module ['@module_name'] . "','bb_logout'));";
					// echo "<p>" . $query . "</p>";
					$result = $main->query ( $con, $query );
					$message = "Module " . $arr_module ['@module_name'] . " has been installed.";
				}
				// install or update modules
				
				// if update or insert worked
				if (pg_affected_rows ( $result ) == 0) {
					$arr_messages [] = "Error: Module " . $arr_module ['@module_name'] . " has not been installed.";
				} else // good install or update
{
					// include the globals so array_master is updated
					$arr_messages [] = $message;
				}
			} // foreach
			  // move it all over
			$main->copy_directory ( $abspath . "/bb-temp/", $abspath . "/bb-modules/" );
			// empty temp directory
			$main->empty_directory ( $abspath . "/bb-temp/", $abspath . "/bb-modules/" );
			
			// include header files before manage modules display
			foreach ( $arr_modules as $value ) {
				if ($value ['@module_type'] == - 3) {
					include ($value ['@module_path']);
				}
			}
		} // install modules
	} // check buttons
	/* END INSTALL OPTIONAL MODULES */
	
	/* BEGIN RESET ORDER */
	if ($main->button ( 3 )) // set_module_order
{
		$query = "SELECT id FROM modules_table ORDER BY id;";
		$result = $main->query ( $con, $query );
		$arr_id = pg_fetch_all_columns ( $result );
		// weird structure to check order integrity
		
		foreach ( $arr_id as $id ) {
			// will else if something changed
			if ($main->check ( 'module_type_' . $id, $module )) {
				// push on order value to $arr_check array
				list ( $type, $interface ) = explode ( "-", $main->post ( 'module_type_' . $id, $module ), 2 );
				$order = $main->post ( 'order_' . $id, $module );
				// $arr_order used in constructing the query
				$arr_order [$interface] [$type] [$id] = $order;
			} else {
				// catch for missing id in post (vs id in table)
				$arr_messages [] = "Error: There has been a change in the modules since last refresh. Order not changed.";
				break;
			}
		}
		// check for unique order values
		if (! count ( $arr_messages )) {
			// all but module type hidden
			foreach ( $arr_order as $key1 => $arr1 ) {
				foreach ( $arr1 as $key2 => $arr2 ) {
					if ($key1 != 0) // ignore hidden values and hooks
{
						if (count ( $arr2 ) != count ( array_unique ( $arr2 ) )) {
							$arr_messages [] = "Error: There are duplicate values in the order choices.";
						}
					}
				}
			}
		}
		if (! count ( $arr_messages )) {
			// build static query with post values
			$query_union = "";
			$union = "";
			{
				foreach ( $arr_id as $id ) {
					list ( $type, $interface ) = explode ( "-", $main->post ( 'module_type_' . $id, $module ), 2 );
					$query_union .= $union . " SELECT " . $id . " as id, " . $arr_order [$interface] [$type] [$id] . " as order ";
					$union = " UNION ";
				}
			}
			// this is a long complex query that will only update modules table
			// if there have been no changes to table since last post
			// if any row has been deleted or inserted there will be a id conflict
			// with modules_table and the post values and the table will not update
			$query = "UPDATE modules_table SET module_order = T1.order " . "FROM (" . $query_union . ") T1 " . "WHERE modules_table.id = T1.id AND EXISTS (SELECT 1 WHERE " . "(SELECT count(*) FROM modules_table) = " . "(SELECT count(*) FROM (SELECT id FROM modules_table) T2 " . "INNER JOIN (" . $query_union . ") T3 ON T2.id = T3.id))";
			$result = $main->query ( $con, $query );
			
			if (pg_affected_rows ( $result ) == 0) {
				$arr_messages [] = "Error: Module order was not updated. There was a change in the table.";
			} else {
				$arr_messages [] = "Module order has been updated.";
			}
		}
	} // end set order
	
	/* END SET ORDER */
	
	$main->set ( 'arr_messages', $arr_state, $arr_messages );
	$main->set ( 'arr_details', $arr_state, $arr_details );
	
	/* UPDATE arr_state */
	// save state, note $submit instead of $module
	// state should be passed on to next code block
	$main->update ( $con, $submit, $arr_state );
	
	// SET $_POST for $POST
	$postdata = json_encode ( $_POST );
	$query = "UPDATE state_table SET postdata = $1 WHERE id = " . $keeper . ";";
	pg_query_params ( $con, $query, array (
			$postdata 
	) );
	/* END UPDATE DATABASE WITH POST STUFF */
	
	/* REDIRECT */
	
	// dirname twice to go up one level, very important for custom posts
	$index_path = "Location: " . $webpath . "/" . $slug;
	header ( $index_path );
	die ();







   

endif;
?>

