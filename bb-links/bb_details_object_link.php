<?php
/*
Copyright (C) 2012 - 2015  Kermit Will Richardson, Brimbox LLC

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License Version 3 (“GNU GPL v3”)
as published by the Free Software Foundation. 

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU GPL v3 for more details. 

You should have received a copy of the GNU GPL v3 along with this program.
If not, see http://www.gnu.org/licenses/
*/
/* NO HTML OR BLANK LINE OUTPUT ALLOWED */

define('BASE_CHECK', true);
include("../bb-config/bb_config.php"); // need DB_NAME

//contains bb_main class
include("../bb-utilities/bb_main.php");
include("../bb-utilities/bb_database.php");
include("../bb-utilities/bb_links.php");
include("../bb-utilities/bb_validate.php");
include("../bb-utilities/bb_forms.php");
include("../bb-utilities/bb_work.php");
include("../bb-utilities/bb_hooks.php");
include("../bb-utilities/bb_reports.php");

/* SET UP MAIN OBJECT */
$main = new bb_reports();

//userrole not passed in from controller
session_name(DB_NAME);
session_start();
$main->check_permission("bb_brimbox", array(1,2,3,4,5));
set_time_limit(0);
$con = $main->connect();

/* NO HTML OR BLANK LINE OUTPUT ALLOWED */
//$filename = ($_POST['bb_data_file_name'] == "") ? "default" :  $_POST['bb_data_file_name'];
//$extension =  "txt";
//$text = ($_POST['bb_data_area'] == "") ? "" :  $_POST['bb_data_area'];

    
//Here go the headers
header ("Content-Type: application/octet-stream");
header ("Content-disposition: attachment; filename=\"test.txt\"");
header ("Content-Transfer-Encoding: binary");
ob_clean();
flush();

pg_query ($con, "BEGIN");
$handle = pg_lo_open($con, 1 ,"r") or die("File Error 1");
pg_lo_read_all($handle) or die("File Error 2");
pg_query($con, "COMMIT");
 

?>