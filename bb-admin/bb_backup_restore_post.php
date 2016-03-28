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

if (isset ( $_SESSION ['username'] ) && in_array ( $_SESSION ['userrole'], array (
		"5_bb_brimbox" 
) )) :
	
	/* STANDARD POST MODULE STUFF */
	$interface = $_SESSION ['interface'];
	$abspath = $_SESSION ['abspath'];
	$webpath = $_SESSION ['webpath'];
	$keeper = $_SESSION ['keeper'];
	$username = $_SESSION ['username'];
	
	// standard $_SESSION post stuff
	$_SESSION ['module'] = $module = $_POST ['bb_module'];
	$_SESSION ['slug'] = $slug = $_POST ['bb_slug'];
	$_SESSION ['submit'] = $submit = $_POST ['bb_submit'];
	$_SESSION ['button'] = $button = isset ( $_POST ['bb_button'] ) ? $_POST ['bb_button'] : 0;
	if (($_POST ['bb_userrole'] != "") && in_array ( $_POST ['bb_userrole'], explode ( $_SESSION ['userroles'] ) ))
		$_SESSION ['userrole'] = $_POST ['bb_userrole']; // double checked when build->locked is call in index
		
	/* SET UP WORK OBJECT AND POST STUFF */
		// objects are all daisy chained together
		// set up work from last object
		// contains bb_database class, extends bb_main
		// constants include -- some constants are used
	include_once ($abspath . "/bb-config/bb_constants.php");
	// include build class object
	if (file_exists ( $abspath . "/bb-extend/include_main.php" )) {
		include_once ($abspath . "/bb-extend/include_main.php");
	} else {
		include_once ($abspath . "/bb-utilities/bb_include_main.php");
	}
	// main object for hooks
	$main = new bb_main ();
	// need connection
	$con = $main->connect ();
	
	// load global arrays
	if (file_exists ( $abspath . "/bb-extend/bb_include_globals.php" )) {
		include_once ($abspath . "/bb-extend/bb_include_globals.php");
	} else {
		include_once ($abspath . "/bb-utilities/bb_include_globals.php");
	}
	
	$POST = $_POST;
	
	$arr_state = $main->load ( $con, $submit );
	
	/* END STANDARD POST MODULE STUFF */
	
	/* BEGIN MODULE */
	/* INTITALIZE */
	$arr_messages = array ();
	set_time_limit ( 0 );
	$arr_layouts = $main->get_json ( $con, "bb_layout_names" );
	$arr_layouts_reduced = $main->filter_keys ( $arr_layouts );
	
	// set default row_type and process later
	$row_type = 0;
	
	/* INCLUDE DATABASE CREATION STUFF */
	include ("bb_backup_restore_extra.php");

	/* ENCRYTION SWITCH */
	function decrypt_line($str, $passwd, $iv, $type) {

		switch ($type) {
			case 0 : // bascially unencoded
				$str = gzinflate ( base64_decode ( $str ) );
				break;
			case 1 : // MCRYPT_3DES + Compression
				$str = gzinflate ( mcrypt_decrypt ( MCRYPT_3DES, $passwd, base64_decode ( $str ), MCRYPT_MODE_CBC, $iv ) );
				break;
		}
		return $str;
	}
	
	// NOTE FIRST BUTTON (the backup button) GOES THROUGH JAVASCRIPT NOT POSTBACK*/
	
	// CLEAN DATABASE DATA
	// removes tabs and cleans up new lines
	if ($main->button ( 1 )) // submit_file
{
		$valid_password = $main->validate_password ( $con, $main->post ( "backup_passwd", $module ), "5_bb_brimbox" );
		if (! $valid_password) {
			array_push ( $arr_messages, "Invalid Password." );
		} else {
			$main->cleanup_database_data ( $con );
			array_push ( $arr_messages, "Database Data has been cleaned of unwanted tabs and new lines." );
		}
	}
	// CLEAN DATABASE COLUMN
	if ($main->button ( 2 )) // clean_up_columns
{
		$valid_password = $main->validate_password ( $con, $main->post ( "backup_passwd", $module ), "5_bb_brimbox" );
		if (! $valid_password) {
			array_push ( $arr_messages, "Invalid Password." );
		} else {
			$main->cleanup_database_columns ( $con );
			array_push ( $arr_messages, "Unused database columns have been emptied and cleaned." );
		}
	}
	
	// CLEAN DATABASE LAYOUT
	if ($main->button ( 3 )) // clean_up_columns
{
		$valid_password = $main->validate_password ( $con, $main->post ( "backup_passwd", $module ), "5_bb_brimbox" );
		if (! $valid_password) {
			array_push ( $arr_messages, "Invalid Password." );
		} else {
			$main->cleanup_database_layouts ( $con );
			array_push ( $arr_messages, "Unused database layouts have been removed." );
		}
	}
	
	// RESTORE DATABASE
	if ($main->button ( 4 )) // submit_file
{
		// admin password
		$valid_password = $main->validate_password ( $con, $main->post ( "admin_passwd_1", $module ), "5_bb_brimbox" );
		if (! $valid_password) {
			$arr_messages [] = "Error: Admin password not verified.";
		} else {
			// file must be populated
			if (is_uploaded_file ( $_FILES [$main->name ( 'backup_file', $module )] ["tmp_name"] )) {
				/* VERY LONG IFS FOR RESTORING DATABASE */
				$handle = fopen ( $_FILES [$main->name ( 'backup_file', $module )] ["tmp_name"], "r" );
				$str = rtrim ( fgets ( $handle ) ); // get first line without encryption, has salt and hash
				if (strlen ( $str ) == 168) // correct header length
{
					// get backup file password
					$passwd = $main->post ( 'file_passwd_1', $module );
					// split up hash, salt and iv
					$iv_size = mcrypt_get_iv_size ( MCRYPT_3DES, MCRYPT_MODE_CBC );
					$iv = substr ( $str, 8, $iv_size ); // from the salt
					$hex = substr ( $str, 0, 8 );
					$salt = substr ( $str, 8, 32 );
					$hash = substr ( $str, 32 + 8, 128 );
					// check password
					// 00000000 -- no encrypt before userrole => userroles
					// 00000001 -- encrypt before userrole => userroles
					if (hash ( 'sha512', $passwd . $salt ) == $hash) {
						if (in_array ( $hex, array (
								"00000000",
								"00000002",
								"00000004",
								"00000006",
								"00000008" 
						) )) {
							$type = 0;
						} elseif (in_array ( $hex, array (
								"00000001",
								"00000003",
								"00000005",
								"00000007",
								"00000009" 
						) )) {
							$type = 1;
						}
						// get next line, xml_backup has version and time stats
						$str = rtrim ( fgets ( $handle ) );
						$json_header = json_decode ( decrypt_line ( $str, $passwd, $iv, $type ), true );
						
						/* TABLES ORDERED FOR QUICKER RESTORE */
						// since data table is last it can be skipped on upload if not restored
						
						/* JSON TABLE */
						// get next line, xml has xml table count
						$str = rtrim ( fgets ( $handle ) );
						$json_json = json_decode ( decrypt_line ( $str, $passwd, $iv, $type ), true );
						$cnt = $json_json ['count'];
						
						// restore json table if
						if ($main->post ( 'json_table_checkbox', $module ) == 1) {
							// cascaded drop
							$query = "DROP TABLE IF EXISTS json_table CASCADE";
							$main->query ( $con, $query );
							// install new table
							$query = $json_before_eot;
							$main->query ( $con, $query );
							// populate table
							for($i = 0; $i < $cnt; $i ++) {
								// get next line
								$str = rtrim ( fgets ( $handle ) );
								// decrypt and split
								$row = explode ( "\t", decrypt_line ( $str, $passwd, $iv, $type ) );
								$query = "INSERT INTO json_table (lookup, jsondata, change_date) " . "VALUES ($1,$2,$3);";
								// echo "<p>" . htmlentities($query) . "</p><br>";
								$main->query_params ( $con, $query, $row );
							}
							// install triggers indexes etc
							$query = $json_after_eot;
							$main->query ( $con, $query );
							array_push ( $arr_messages, "JSON table has been restored from backup." );
						} else // advance file pointer
{
							for($i = 0; $i < $cnt; $i ++) {
								// read in lines and do nothing
								$str = fgets ( $handle );
							}
						}
						/* USER. MODULES AND LOG TABLES TABLES */
						/* see xml comments, since they are the same as following table */
						
						/* USERS TABLE */
						$str = rtrim ( fgets ( $handle ) );
						$json_users = json_decode ( decrypt_line ( $str, $passwd, $iv, $type ), true );
						$cnt = $json_users ['count'];
						
						if ($main->post ( 'users_table_checkbox', $module ) == 1) {
							$query = "DROP TABLE IF EXISTS users_table CASCADE";
							$main->query ( $con, $query );
							
							$query = $users_before_eot;
							$main->query ( $con, $query );
							
							for($i = 0; $i < $cnt; $i ++) {
								$str = rtrim ( fgets ( $handle ) );
								$row = explode ( "\t", decrypt_line ( $str, $passwd, $iv, $type ) );
								$query = "INSERT INTO users_table (username, email, hash, salt, attempts, userroles, fname, minit, lname, notes, ips, change_date) " . "VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12);";
								// echo "<p>" . htmlentities($query) . "</p><br>";
								$main->query_params ( $con, $query, $row );
							}
							$query = $users_after_eot;
							$main->query ( $con, $query );
							array_push ( $arr_messages, "Users table has been restored from backup." );
						} else {
							for($i = 0; $i < $cnt; $i ++) {
								fgets ( $handle );
							}
						}
						
						/* MODULES TABLE */
						$str = rtrim ( fgets ( $handle ) );
						$json_modules = json_decode ( decrypt_line ( $str, $passwd, $iv, $type ), true );
						$cnt = $json_modules ['count'];
						
						if ($main->post ( 'modules_table_checkbox', $module ) == 1) {
							$query = "DROP TABLE IF EXISTS modules_table CASCADE";
							$main->query ( $con, $query );
							
							$query = $modules_before_eot;
							$main->query ( $con, $query );
							
							for($i = 0; $i < $cnt; $i ++) {
								$str = rtrim ( fgets ( $handle ) );
								$row = explode ( "\t", decrypt_line ( $str, $passwd, $iv, $type ) );
								$query = "INSERT INTO modules_table (module_order, module_path, module_name, module_slug, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details, change_date) " . "VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12);";
								// echo "<p>" . htmlentities($query) . "</p><br>";
								// use query params because not updating or inserting full text columns
								$main->query_params ( $con, $query, $row );
							}
							$query = $modules_after_eot;
							$main->query ( $con, $query );
							array_push ( $arr_messages, "Modules table has been restored from backup." );
						} else {
							for($i = 0; $i < $cnt; $i ++) {
								fgets ( $handle );
							}
						}
						
						/* LOG TABLE */
						$str = rtrim ( fgets ( $handle ) );
						$json_log = json_decode ( decrypt_line ( $str, $passwd, $iv, $type ), true );
						$cnt = $json_log ['count'];
						
						if ($main->post ( 'log_table_checkbox', $module ) == 1) {
							$query = "DROP TABLE IF EXISTS log_table CASCADE";
							$main->query ( $con, $query );
							
							$query = $log_before_eot;
							$main->query ( $con, $query );
							
							for($i = 0; $i < $cnt; $i ++) {
								$str = rtrim ( fgets ( $handle ) );
								$row = explode ( "\t", decrypt_line ( $str, $passwd, $iv, $type ) );
								$query = "INSERT INTO log_table (username, email, ip_address, action, change_date) " . "VALUES ($1,$2,$3,$4,$5);";
								// echo "<p>" . htmlentities($query) . "</p><br>";
								$main->query_params ( $con, $query, $row );
							}
							$query = $log_after_eot;
							$main->query ( $con, $query );
							array_push ( $arr_messages, "Log table has been restored from backup." );
						} else {
							for($i = 0; $i < $cnt; $i ++) {
								fgets ( $handle );
							}
						}
						
						/* DATA TABLE */
						/* slightly different than last foru tables */
						// get count from header xml
						$str = rtrim ( fgets ( $handle ) );
						$json_data = json_decode ( decrypt_line ( $str, $passwd, $iv, $type ), true );
						$cnt = $json_data ['count'];
						// restore data table
						if ($main->post ( 'data_table_checkbox', $module ) == 1) {
							// drop both table and sequence
							$query = "DROP TABLE IF EXISTS data_table CASCADE";
							$main->query ( $con, $query );
							$query = "DROP SEQUENCE IF EXISTS data_table_id_seq CASCADE";
							$main->query ( $con, $query );
							// install table
							$query = $data_before_eot;
							$main->query ( $con, $query );
							// build insert clause (c01,c02...c50)
							$arr_cols = array ();
							for($i = 1; $i <= 50; $i ++) {
								$col = "c" . str_pad ( ( string ) $i, 2, "0", STR_PAD_LEFT );
								array_push ( $arr_cols, $col );
							}
							$str_cols = implode ( ",", $arr_cols );
							// build values clause
							$arr_params = array ();
							for($i = 1; $i <= 61; $i ++) {
								$param = "\$" . $i;
								array_push ( $arr_params, $param );
							}
							$str_params = implode ( ",", $arr_params );
							// restore data from file
							for($i = 0; $i < $cnt; $i ++) {
								// get string and decrypt
								$str = rtrim ( fgets ( $handle ) );
								$row = explode ( "\t", decrypt_line ( $str, $passwd, $iv, $type ) );
								// these are note rows, $main->query_params will handle new lines
								// however the php splits will be wrong if new lines are not escaped, so unescape
								$row [52] = str_replace ( "\\n", "\n", $row [52] );
								$row [53] = str_replace ( "\\n", "\n", $row [53] );
								
								$query = "INSERT INTO data_table (id, row_type, key1, key2," . $str_cols . ", archive, secure, create_date, modify_date, owner_name, updater_name, list_string) " . "VALUES (" . $str_params . ");";
								// echo "<p>" . htmlentities($query) . "</p><br>";
								// use query params because not updating or inserting full text columns
								$main->query_params ( $con, $query, $row );
							}
							// install triggers, indexes, and sequence
							$query = $data_after_eot;
							$main->query ( $con, $query );
							
							array_push ( $arr_messages, "Data table has been restored from backup." );
						} else // close file if not restoring data table
{
							fclose ( $handle );
						}
					}  // hash password test
else // bad password
{
						array_push ( $arr_messages, "Error: Password for backup file not verified." );
					}
				}  // first line check
else // bad first line
{
					array_push ( $arr_messages, "Error: File is not a valid backup file." );
				}
			}  // file exists
else // no file at all
{
				array_push ( $arr_messages, "Error: Must choose backup file." );
			}
		} // check admin password
	}
	
	// BUILD INDEXES
	// full text indexes do not exist after data table restore
	if ($main->button ( 5 )) // submit_file
{
		$main->build_indexes ( $con, 0 );
		array_push ( $arr_messages, "Indexes have been rebuilt." );
	}
	
	if ($main->button ( 6 )) // submit_file
{
		// admin password
		$valid_password = $main->validate_password ( $con, $main->post ( "admin_passwd_2", $module ), "5_bb_brimbox" );
		if (! $valid_password) {
			$arr_messages [] = "Error: Admin password not verified.";
		} else {
			// file must be populated
			if (is_uploaded_file ( $_FILES [$main->name ( 'lo_file', $module )] ["tmp_name"] )) {
				/* VERY LONG IFS FOR RESTORING DATABASE */
				$handle = fopen ( $_FILES [$main->name ( 'lo_file', $module )] ["tmp_name"], "r" );
				$str = rtrim ( fgets ( $handle ) ); // get first line without encryption, has salt and hash
				if (strlen ( $str ) == 168) // correct header length
{
					// get backup file password
					$passwd = $main->post ( 'file_passwd_2', $module );
					// split up hash, salt and iv
					$iv_size = mcrypt_get_iv_size ( MCRYPT_3DES, MCRYPT_MODE_CBC );
					$iv = substr ( $str, 8, $iv_size ); // from the salt
					$hex = substr ( $str, 0, 8 );
					$salt = substr ( $str, 8, 32 );
					$hash = substr ( $str, 32 + 8, 128 );
					// check password
					// 00000000 -- no encrypt before userrole => userroles
					// 00000001 -- encrypt before userrole => userroles
					if (hash ( 'sha512', $passwd . $salt ) == $hash) {
						if (in_array ( $hex, array (
								"00000000",
								"00000002",
								"00000004",
								"00000006" 
						) )) {
							$type = 0;
						} elseif (in_array ( $hex, array (
								"00000001",
								"00000003",
								"00000005",
								"00000007" 
						) )) {
							$type = 1;
						}
						// get next line, xml_backup has version and time stats
						$str = rtrim ( fgets ( $handle ) );
						$json_header = json_decode ( decrypt_line ( $str, $passwd, $iv, $type ), true );
						
						$total = $json_header ['total'];
						for($i = 1; $i <= $total; $i ++) {
							$str = rtrim ( fgets ( $handle ) );
							$json_info = json_decode ( decrypt_line ( $str, $passwd, $iv, $type ), true );
							
							$id = $json_info ['id'];
							$cnt = $json_info ['count'];
							$page = $json_info ['page'];
							$length = $json_info ['length'];
							$remainder = $json_info ['remainder'];
							$filename = $json_info ['filename'];
							
							if ($length > 0) {
								pg_query ( $con, "BEGIN" );
								@pg_lo_unlink ( $con, $id );
								pg_query ( $con, "COMMIT" );
								pg_query ( $con, "BEGIN" );
								$query = "UPDATE data_table SET c47 = '" . pg_escape_string ( $filename ) . "' WHERE id = " . pg_escape_string ( $id ) . ";";
								$result = $main->query ( $con, $query );
								if (pg_affected_rows ( $result ) == 1) {
									pg_lo_create ( $con, $id );
									$lo = pg_lo_open ( $con, $id, "w" );
									pg_lo_seek ( $lo, 0, PGSQL_SEEK_SET );
									for($j = 1; $j <= $cnt; $j ++) {
										$str = decrypt_line ( rtrim ( fgets ( $handle ) ), $passwd, $iv, $type );
										pg_lo_write ( $lo, $str, $page );
									}
									$str = decrypt_line ( rtrim ( fgets ( $handle ) ), $passwd, $iv, $type );
									pg_lo_write ( $lo, $str, $remainder );
									pg_lo_close ( $lo );
								}
								pg_query ( $con, "COMMIT" );
								array_push ( $arr_messages, "Files have been restored from backup." );
							} // count or lo_open
						} // total
					} else // bad password
{
						array_push ( $arr_messages, "Error: Password for backup file not verified." );
					}
				}  // first line check
else // bad first line
{
					array_push ( $arr_messages, "Error: File is not a valid backup file." );
				}
			}  // file exists
else // no file at all
{
				array_push ( $arr_messages, "Error: Must choose backup file." );
			}
		}
	}
	
	$arr_messages = array_unique ( $arr_messages );
	$main->set ( 'arr_messages', $arr_state, $arr_messages );
	
	// update state, back to db
	$main->update ( $con, $submit, $arr_state );
	
	$postdata = json_encode ( $POST );
	
	// set $_POST for $POST
	$query = "UPDATE state_table SET postdata = $1 WHERE id = " . $keeper . ";";
	pg_query_params ( $con, $query, array (
			$postdata 
	) );
	
	// REDIRECT
	$index_path = "Location: " . $webpath . "/" . $slug;
	header ( $index_path );
	die ();






    
endif;
?>
