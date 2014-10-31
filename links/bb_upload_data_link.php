<?php
/*
Copyright (C) 2012 - 2013  Kermit Will Richardson, Brimbox LLC

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
include("../bb-config/bb_config.php");
//objects extended together
include("../bb-utilities/bb_database.php");
include("../bb-utilities/bb_link.php");
include("../bb-utilities/bb_validate.php");
include("../bb-utilities/bb_form.php");
include("../bb-utilities/bb_work.php");
include("../bb-utilities/bb_report.php");
include("../bb-utilities/bb_main.php");

$main = new bb_main(); //extends bb_database

//userrole not passed in from controller
session_name(DB_NAME);
session_start();
$main->check_permission(array(4,5));
?>
<?php
/* THIS IS A TEXT FILE HEADER OUTPUT */
/* NO HTML OR BLANK LINE OUTPUT ALLOWED */
$filename = (empty($_POST['bb_data_file_name'])) ? "default" :  $_POST['bb_data_file_name'];
$extension = (empty($_POST['bb_data_file_extension'])) ? "txt" :  $_POST['bb_data_file_extension'];
$text = (empty($_POST['bb_data_area'])) ? "" :  $_POST['bb_data_area'];

    
//Here go the headers
header ("Content-Type: application/octet-stream");
header ("Content-disposition: attachment; filename=" . $filename . "." . $extension . "\"");
header ("Content-Transfer-Encoding: binary");
ob_clean();
flush();
echo $text;
?>