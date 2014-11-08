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

/* INCLUDE ALL BRIMBOX STANDARD FUNCTIONS */
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

$main->check_permission("bb_brimbox", 5);

/* INITIALIZE */
$con = $main->connect();
set_time_limit(0);

//standard eol
$eol = "\r\n";

$passwd = $_POST['dump_passwd'];

$valid_password = $main->validate_password($con, $passwd, "5_bb_brimbox");

if (!$valid_password)
	{
	die("Invalid Password");
	}
?>
<?php
/* THIS IS A TEXT FILE HEADER OUTPUT */
/* NO HTML OR BLANK LINE OUTPUT ALLOWED */
$filename = "List_Data_Dump.txt";
    
//Here go the headers
header ("Content-Type: application/octet-stream");
header ("Content-disposition: attachment; filename=" . $filename . "");
header ("Content-Transfer-Encoding: binary");
ob_clean();
flush();

$arr_lists = $main->get_json($con, "bb_create_lists");

$arr_row = array();
array_push($arr_row, "row_id");
array_push($arr_row, "list_number");
array_push($arr_row, "row_type");

$str = implode("\t", $arr_row) . $eol;
echo $str;

foreach ($arr_lists as $row_type => $arr_list)
    {
    foreach ($arr_list as $key2 => $value)
        {        
        $query = "SELECT id FROM data_table WHERE list_retrieve(list_string, " . $key2 . ") = 1 AND row_type = " . $row_type . ";";
        $result = $main->query($con, $query);
        
        while ($row = pg_fetch_array($result))
            {
            $arr_row = array();
            array_push($arr_row, $row['id']);
            array_push($arr_row, $key2);
            array_push($arr_row, $row_type);
            
            $str = implode("\t", $arr_row) . $eol;
            echo $str;
            }
        }
	}
?>