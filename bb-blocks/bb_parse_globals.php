<?php if (!defined('BASE_CHECK')) exit(); ?>
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
	/* GET HEADER AND GLOBAL ARRAYS */
	/* load header array, addon functions, and global arrays */
	// returns $array_header and by default all functions declared
	/* INCLUDE STANDARD ARRAYS ARRAYS AND GLOBAL FUNCTIONS */
	// global for all interfaces
	
	/* NO HTML OUTPUT */
	$abspath = $_SESSION ['abspath'];
	
	include ($abspath . "/bb-utilities/bb_arrays.php");
	/* INCLUDE INSTALLED */
	$query = "SELECT module_path FROM modules_table WHERE standard_module IN (0,4,6) AND module_type IN (-1) ORDER BY module_order;";
	$result = pg_query ( $con, $query );
	while ( $row = pg_fetch_array ( $result ) ) {
		// will ignore file if missing
		include ($abspath . "/" . $row ['module_path']);
	}
	/* ADHOC ARRAYS AND GLOBAL FUNCTIONS */
	include ($abspath . "/bb-config/bb_functions.php");
	// header stored in SESSION
	// save for use in post side modules
	
	/* UNPACK $array_global for given interface */
	// this creates array from the global array
	if (isset ( $array_global )) {
		foreach ( $array_global as $key => $value ) {
			${'array_' . $key} = $value;
		}
	}
	
?>