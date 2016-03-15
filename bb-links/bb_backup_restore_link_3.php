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
include("../bb-config/bb_config.php");

session_name(DB_NAME);
session_start();

//extendable include
if (file_exists("../bb-extend/include_main.php"))
    {
    include_once("/bb-extend/include_main.php");   
    }
else
    {
    include_once("../bb-utilities/bb_include_main.php");
    }
//main instance   
$main = new bb_main();

$main->check_permission("bb_brimbox", 5);

/* INITIALIZE */
$con = $main->connect();
set_time_limit(0);

//standard eol
$eol = "\r\n";
//this will replace control chars which should not exist
$pattern = "/[\\t\\0\\x0B\\x0C\\r\\n]+/";

$passwd = $_POST['dump_passwd'];
$column_names = $_POST['column_names'];
$new_lines = $_POST['new_lines'];

$valid_password = $main->validate_password($con, $passwd, "5_bb_brimbox");

if (!$valid_password)
	{
	die("Invalid Password");
	}
?>
<?php
/* THIS IS A TEXT FILE HEADER OUTPUT */
/* NO HTML OR BLANK LINE OUTPUT ALLOWED */
$arr_layouts = $main->get_json($con, "bb_layout_names");
$arr_layouts_reduced = $main->filter_keys($arr_layouts);
$arr_columns = $main->get_json($con, "bb_column_names");
$default_row_type = $main->get_default_layout($arr_layouts_reduced);

$row_type = (empty($_POST['row_type'])) ? $default_row_type :  $_POST['row_type'];

$arr_column = $arr_columns[$row_type];
$arr_layout = $arr_layouts_reduced[$row_type];
$arr_column_reduced = $main->filter_keys($arr_column); 

$filename = $arr_layout['singular'] . "_Dump.txt";
    
//Here go the headers
header ("Content-Type: application/octet-stream");
header ("Content-disposition: attachment; filename=" . $filename . "");
header ("Content-Transfer-Encoding: binary");
ob_clean();
flush();

$query = "SELECT * FROM data_table WHERE row_type IN (" . $row_type . ");";
$result = $main->query($con, $query);

//header row
$arr_row = array();
array_push($arr_row, "id");
array_push($arr_row,"row_type");
array_push($arr_row, "key1");
array_push($arr_row, "key2");
foreach ($arr_column_reduced as $key => $value)
    {
	$column = $column_names ? $main->pad("c", $key) : $value['name'];
    array_push($arr_row, trim(preg_replace($pattern, " ", $column)));
    }
array_push($arr_row,"owner_name");
array_push($arr_row,"updater_name");
array_push($arr_row,"create_date");
array_push($arr_row,"modify_date");
array_push($arr_row,"archive");
array_push($arr_row,"secure");
$str = implode("\t", $arr_row) . $eol;
echo $str;
        
while ($row = pg_fetch_array($result))
	{
	$arr_row = array();
	array_push($arr_row, $row['id']);
	array_push($arr_row,$row['row_type']);
	array_push($arr_row, $row['key1']);
	array_push($arr_row, $row['key2']);
	foreach ($arr_column_reduced as $key => $value)
		{
		$col = $main->pad("c", $key);
		//push onto stack purging characters which will be problems
		if (in_array($col, array("c49","c50")))
			{
			$row[$col] = (int)$new_lines ?  $row[$col] : str_replace("\n", "\\n", $row[$col]); 	
			}
		array_push($arr_row, trim(preg_replace($pattern, " ", $row[$col])));
		}
	array_push($arr_row,$row['owner_name']);
	array_push($arr_row,$row['updater_name']);
	array_push($arr_row,$row['create_date']);
	array_push($arr_row,$row['modify_date']);
	array_push($arr_row,$row['archive']);
	array_push($arr_row,$row['secure']);
	$str = implode("\t", $arr_row) . $eol;
	echo $str;
	}

?>