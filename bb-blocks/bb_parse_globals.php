<?php if (!defined('BASE_CHECK')) exit(); ?>
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
?>
<?php
/* INCLUDE CUSTOM FUNCTIONS AND GLOBAL ARRAYS */
// include default global arrays
include ($abspath . "/bb-utilities/bb_arrays.php");

/* load and include function modules  */
$query = "SELECT module_path FROM modules_table WHERE standard_module IN (0,4,6) AND module_type IN (-1) ORDER BY module_order;";
$result = pg_query($con, $query);
while ($row = pg_fetch_array($result)) {
    // will ignore file if missing
    if (file_exists($abspath . "/" . $row['module_path'])) include ($abspath . "/" . $row['module_path']);
}

//include configuration functions file
include ($abspath . "/bb-config/bb_functions.php");

// unpack global array into standard arrays
$main->unpack_global_array();
?>