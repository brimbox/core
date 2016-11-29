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
/* SET UP MAIN OBJECT AND POST STUFF */
// objects are all daisy chained together
// set up main from last object
// contains bb_database class, extends bb_main
/* NO HTML OUTPUT */
// get from $_SESSION
$abspath = $_SESSION['abspath'];
include_once ($abspath . "/bb-utilities/bb_build.php");
// contains bb_build class, extend bb_database
include_once ($abspath . "/bb-utilities/bb_database.php");
// contains bb_validation class, extend bb_links
include_once ($abspath . "/bb-utilities/bb_validate.php");
// contains bb_work class, extends bb_forms
include_once ($abspath . "/bb-utilities/bb_meta.php");
/* these classes only brought into both $main */
include_once ($abspath . "/bb-utilities/bb_work.php");
/* these classes only brought into both $main */
include_once ($abspath . "/bb-utilities/bb_links.php");
// contains bb_form class, extends bb_validate
include_once ($abspath . "/bb-utilities/bb_forms.php");
// contains bb_report class, extend bb_hooks
include_once ($abspath . "/bb-utilities/bb_reports.php");
// contains bb_main class
include_once ($abspath . "/bb-utilities/bb_main.php");
?>