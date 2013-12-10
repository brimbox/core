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
?>
<?php
$main->check_permission(5);
?>
<script type="text/javascript">
//dump standard brimbox backup
function submit_backup()
    {
    var frmobj = document.forms["bb_form"];
    
    frmobj.action = "links/bb_backup_restore_link_1.php";
    frmobj.submit();
    }

//dump tables one type at a time	
function submit_dump()
    {
    var frmobj = document.forms["bb_form"];
    
    frmobj.action = "links/bb_backup_restore_link_2.php";
    frmobj.submit();
    }
//dump list definitions
function submit_listdefs()
    {
    var frmobj = document.forms["bb_form"];
    
    frmobj.action = "links/bb_backup_restore_link_3.php";
    frmobj.submit();
    }
//dump list data with unique row_id
function submit_listdata()
    {
    var frmobj = document.forms["bb_form"];
    
    frmobj.action = "links/bb_backup_restore_link_4.php";
    frmobj.submit();
    }

</script>

<?php
/* INTITALIZE */
$arr_message = array();
set_time_limit(0);
$xml_layouts = $main->get_xml($con,"bb_layout_names");
//set default row_type and process later
$row_type = 0;

/* RETRIEVE STATE */
$main->retrieve($con, $array_state, $userrole);

include("bb_backup_restore_extra.php");

/* ENCRYTION SWITCH */
function decrypt_line($str, $passwd, $iv, $type)
	{
	switch ($type)
		{
		case 0: //bascially unencoded
		$str = gzinflate(base64_decode($str));
		break;
		case 1: //MCRYPT_3DES + Compression
		$str = gzinflate(mcrypt_decrypt(MCRYPT_3DES, $passwd, base64_decode($str), MCRYPT_MODE_CBC, $iv));
		break;
		}
	return $str;	}

//NOTE FIRST BUTTON (the backup button) GOES THROUGH JAVASCRIPT NOT POSTBACK*/

//CLEAN DATABASE DATA
//removes tabs and cleans up new lines
if ($main->post('bb_button', $module) == 1) //submit_file
	{
	$valid_password = $main->validate_login($con, $email, $main->post("backup_passwd", $module), $userrole);
	if (!$valid_password)
		{
		array_push($arr_message, "Invalid Password.");	
		}
	else
		{
		$main->cleanup_database_data($con);
		array_push($arr_message, "Database Data has been cleaned of unwanted tabs and new lines.");
		}
	}
//CLEAN DATABASE COLUMN	
if ($main->post('bb_button', $module) == 2) //clean_up_columns
	{
	$valid_password = $main->validate_login($con, $email, $main->post("backup_passwd", $module), $userrole);
	if (!$valid_password)
		{
		array_push($arr_message, "Invalid Password.");	
		}
	else
		{
		$main->cleanup_database_columns($con);
		array_push($arr_message, "Unused database columns have been emptied and cleaned.");
		}
    }
	
//CLEAN DATABASE LAYOUT	
if ($main->post('bb_button', $module) == 3) //clean_up_columns
	{
	$valid_password = $main->validate_login($con, $email, $main->post("backup_passwd", $module), $userrole);
	if (!$valid_password)
		{
		array_push($arr_message, "Invalid Password.");	
		}
	else
		{
		$main->cleanup_database_layouts($con);
		array_push($arr_message, "Unused database layouts have been removed.");
		}
    }


//RESTORE DATABASE
if ($main->post('bb_button', $module) == 4) //submit_file
	{
	//file must be populated
	if (!empty($_FILES[$main->name('backup_file', $module)]["tmp_name"]))
		{
		/* VERY LONG IFS FOR RESTORING DATABASE */
		$handle = fopen($_FILES[$main->name('backup_file', $module)]["tmp_name"], "r");		
		$str = rtrim(fgets($handle)); //get first line without encryption, has salt and hash
		if (strlen($str) == 168) // correct header length
			{
			//get password
			$passwd = $main->post('restore_passwd', $module);
			//split up hash, salt and iv
			$iv_size = mcrypt_get_iv_size(MCRYPT_3DES, MCRYPT_MODE_CBC);
			$iv = substr($str, 8 , $iv_size); //from the salt
			$hex = substr($str, 0, 8);
			$salt = substr($str, 8, 32);
			$hash = substr($str, 32 + 8, 128);
			//check password
			if (hash('sha512', $passwd . $salt) == $hash)
				{					
				if ($hex == "00000000")
					{
					$type = 0;
					}
				elseif ($hex == "00000001")
					{
					$type = 1;
					}
				//get next line, xml_backup has version and time stats	
				$str = rtrim(fgets($handle));	
				$xml_backup = simplexml_load_string(decrypt_line($str, $passwd, $iv, $type));
				
				/* TABLES ORDERED FOR QUICKER RESTORE */
				//since data table is last it can be skipped on upload if not restored

				/* XML TABLE */
				//get next line, xml has xml table count				
				$str = rtrim(fgets($handle));
				$xml_xml = simplexml_load_string(decrypt_line($str, $passwd, $iv, $type));
				$cnt = (int)$xml_xml->count;
				//restore xml table if	
				if ($main->post('xml_table_checkbox', $module) == 1)
					{
					//cascaded drop
					$query = "DROP TABLE IF EXISTS xml_table CASCADE";
					$main->query($con, $query);
					//install new table					
					$query = $xml_before_eot;
					$main->query($con, $query);
					//populate table
					for ($i=0; $i<$cnt; $i++)
						{
						//get next line
						$str = rtrim(fgets($handle));
						//decrypt and split
						$row = explode("\t", decrypt_line($str, $passwd, $iv, $type));
						$query = "INSERT INTO xml_table (lookup, xmldata, change_date) " .
								 "VALUES ($1,$2,$3);";
						//echo "<p>" . htmlentities($query) . "</p><br>";
						$main->query_params($con, $query, $row);		 
						}
					//install triggers indexes etc
					$query = $xml_after_eot;
					$main->query($con, $query);
					array_push($arr_message, "XML table has been restored from backup.");
					}
				else //advance file pointer
					{
					for ($i=0; $i<$cnt; $i++)
						{
						//read in lines and do nothing
						$str = fgets($handle);
						}
					}
				/* USER. MODULES AND LOG TABLES TABLES */
				/* see xml comments, since they are the same as following table */
				
				/* USERS TABLE */
				$str = rtrim(fgets($handle));
				$xml_users = simplexml_load_string(decrypt_line($str, $passwd, $iv, $type));
				$cnt = (int)$xml_users->count;
					
				if ($main->post('users_table_checkbox', $module) == 1)
					{
					$query = "DROP TABLE IF EXISTS users_table CASCADE";
					$main->query($con, $query);
										
					$query = $users_before_eot;
					$main->query($con, $query);
					
					for ($i=0; $i<$cnt; $i++)
						{
						$str = rtrim(fgets($handle));
						$row = explode("\t", decrypt_line($str, $passwd, $iv, $type));
						$query = "INSERT INTO users_table (email, hash, salt, attempts, userrole, fname, minit, lname, change_date) " .
								 "VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9);";	
						//echo "<p>" . htmlentities($query) . "</p><br>";
						$main->query_params($con, $query, $row);		 
						}
					$query = $users_after_eot;
					$main->query($con, $query);
					array_push($arr_message, "Users table has been restored from backup.");
					}
				else
					{
					for ($i=0; $i<$cnt; $i++)
						{
						fgets($handle);
						}
					}				
				
				/* MODULES TABLE */
				$str = rtrim(fgets($handle));
				$xml_modules = simplexml_load_string(decrypt_line($str, $passwd, $iv, $type));
				$cnt = (int)$xml_modules->count;	
			
				if ($main->post('modules_table_checkbox', $module) == 1)
					{
					$query = "DROP TABLE IF EXISTS modules_table CASCADE";
					$main->query($con, $query);
										
					$query = $modules_before_eot;
					$main->query($con, $query);
					
					for ($i=0; $i<$cnt; $i++)
						{
						$str = rtrim(fgets($handle));
						$row = explode("\t", decrypt_line($str, $passwd, $iv, $type));
						$query = "INSERT INTO modules_table (module_order, module_path, module_name, friendly_name, module_version, module_type, " .
								 "userrole, standard_module, maintain_state, module_files, module_details, change_date) " .
								 "VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12);";	
						//echo "<p>" . htmlentities($query) . "</p><br>";
						//use query params because not updating or inserting full text columns
						$main->query_params($con, $query, $row);		 
						}
					$query = $modules_after_eot;
					$main->query($con, $query);
					array_push($arr_message, "Modules table has been restored from backup.");
					}
				else
					{
					for ($i=0; $i<$cnt; $i++)
						{
						fgets($handle);	
						}
					}

				/* LOG TABLE */					
				$str = rtrim(fgets($handle));
				$xml_log = simplexml_load_string(decrypt_line($str, $passwd, $iv, $type));
				$cnt = (int)$xml_log->count;
								
				if ($main->post('log_table_checkbox', $module) == 1)
					{
					$query = "DROP TABLE IF EXISTS log_table CASCADE";
					$main->query($con, $query);
										
					$query = $log_before_eot;
					$main->query($con, $query);
					
					for ($i=0; $i<$cnt; $i++)
						{
						$str = rtrim(fgets($handle));
						$row = explode("\t", decrypt_line($str, $passwd, $iv, $type));
						$query = "INSERT INTO log_table (email, ip_address, action, change_date) " .
								 "VALUES ($1,$2,$3,$4);";
						//echo "<p>" . htmlentities($query) . "</p><br>";
						$main->query_params($con, $query, $row);		 
						}
					$query = $log_after_eot;
					$main->query($con, $query);
					array_push($arr_message, "Log table has been restored from backup.");
					}
				else
					{
					for ($i=0; $i<$cnt; $i++)
						{
						fgets($handle);	
						}
					}

				/* DATA TABLE */
				/* slightly different than last foru tables */
				//get count from header xml
				$str = rtrim(fgets($handle));
				$xml_data = simplexml_load_string(decrypt_line($str, $passwd, $iv, $type));
				$cnt = (int)$xml_data->count;
				//restore data table	
				if ($main->post('data_table_checkbox', $module) == 1)
					{
					//drop both table and sequence
					$query = "DROP TABLE IF EXISTS data_table CASCADE";
					$main->query($con, $query);
					$query = "DROP SEQUENCE IF EXISTS data_table_id_seq CASCADE";
					$main->query($con, $query);
					//install table					
					$query = $data_before_eot;
					$main->query($con, $query);
					//build insert clause (c01,c02...c50)
					$arr_cols = array();
					for ($i=1; $i<=50; $i++)
						{
						$col = "c" . str_pad((string)$i, 2, "0", STR_PAD_LEFT);
						array_push($arr_cols, $col);
						}
					$str_cols = implode(",",$arr_cols);
					//build values clause
					$arr_params = array();
						for ($i=1; $i<=61; $i++)
						{
						$param = "\$" .$i;
						array_push($arr_params, $param);
						}
					$str_params = implode(",",$arr_params);	
					//restore data from file
					for ($i=0; $i<$cnt; $i++)
						{
						//get string and decrypt
						$str = rtrim(fgets($handle));
						$row = explode("\t", decrypt_line($str, $passwd, $iv, $type));
						//these are note rows, $main->query_params will handle new lines
						//however the php splits will be wrong if new lines are not escaped, so unescape
						$row[52] = str_replace("\\n","\n", $row[52]);
						$row[53] = str_replace("\\n","\n", $row[53]);
						
						$query = "INSERT INTO data_table (id, row_type, key1, key2," . $str_cols . ", archive, secure, create_date, modify_date, owner_name, updater_name, list_string) " .
								 "VALUES (" . $str_params . ");";	
						//echo "<p>" . htmlentities($query) . "</p><br>";
						//use query params because not updating or inserting full text columns
						$main->query_params($con, $query, $row);		 
						}
					//install triggers, indexes, and sequence
					$query = $data_after_eot;
					$main->query($con, $query);
					
					array_push($arr_message, "Data table has been restored from backup.");
					}
				else //close file if not restoring data table
					{
					fclose($handle);
					}
				} //hash password test
			else //bad password
				{
				array_push($arr_message, "Error: Password for backup not verified.");	
				}
			} //first line check
		else //bad first line
			{
			array_push($arr_message, "Error: File is not a valid backup file.");	
			}
		} //file exists
	else //no file at all
		{
		array_push($arr_message, "Error: Must choose backup file.");
		}
	}
	
//BUILD INDEXES
//full text indexes do not exist after data table restore
if ($main->post('bb_button', $module) == 5) //submit_file
	{
	$main->build_indexes($con, 0);
	array_push($arr_message, "Indexes have been rebuilt.");
	}

$arr_message = array_unique($arr_message);
echo "<div class=\"spaced\">";
$main->echo_messages($arr_message);
echo "</div>";	
	
/* START REQUIRED FORM */
$main->echo_form_begin(array("type"=>"enctype=\"multipart/form-data\""));
$main->echo_module_vars($module);

/* BACKUP AREA */
echo "<p class=\"spaced bold larger\">Backup Database</p>";
echo "<div class=\"spaced border floatleft padded\">";
$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>"Clean Database Data");
$main->echo_button("add_list", $params);
$params = array("class"=>"spaced","number"=>2,"target"=>$module, "passthis"=>true, "label"=>"Clean Database Columns");
$main->echo_button("clean_up_columns", $params);
$params = array("class"=>"spaced","number"=>3,"target"=>$module, "passthis"=>true, "label"=>"Clean Database Layouts");
$main->echo_button("clean_up_columns", $params);
echo "<br>";
$params = array("class"=>"spaced","label"=>"Backup Database","onclick"=>"submit_backup()");
$main->echo_script_button("backup_database", $params);
echo "<label class=\"spaced\">Password: </label>";
echo "<input class=\"spaced\" type=\"password\" name=\"backup_passwd\"/>";
echo "<label class=\"spaced\"> Encrypt: </label>";
echo "<input class=\"spaced\" type=\"checkbox\" name=\"encrypt_method\" value=\"1\" checked/>";
echo "</div>";
echo "<div class=\"clear\"></div>";

echo "<p class=\"spaced bold larger\">Database Dump</p>";
echo "<div class=\"spaced border floatleft padded\">";
$params = array("class"=>"spaced","label"=>"Download Layout","onclick"=>"submit_dump()");
$main->echo_script_button("dump_database", $params);
$main->layout_dropdown($xml_layouts, "row_type", $row_type);
echo "<select class=\"spaced\" name=\"column_names\"><option value=\"0\">Use Friendly Names&nbsp;</option><option value=\"1\">Use Generic Names&nbsp;</option></select>";
echo "<select class=\"spaced\" name=\"new_lines\"><option value=\"0\">Escape New Lines&nbsp;</option><option value=\"1\">Purge New Lines&nbsp;</option></select>";
echo "<br>";
$params = array("class"=>"spaced","label"=>"Download List Definitions","onclick"=>"submit_listdefs()");
$main->echo_script_button("dump_listdefs", $params);
$params = array("class"=>"spaced","label"=>"Download List Data","onclick"=>"submit_listdata()");
$main->echo_script_button("dump_listdata", $params);
echo "<br><label class=\"spaced\">Password: </label>";
echo "<input class=\"spaced\" type=\"password\" name=\"dump_passwd\"/>";
echo "</div>";
echo "<div class=\"clear\"></div>";

/* RESTORE AREA */
echo "<p class=\"spaced bold larger\">Restore Database</p>";
echo "<div class=\"spaced border floatleft padded\">";
echo "<label class=\"spaced\">Filename: </label>";
echo "<input class=\"spaced\" type=\"file\" name=\"backup_file\" id=\"file\" /><br>";
echo "<div class=\"spaced border floatleft padded\">";
$params = array("class"=>"spaced","number"=>4,"target"=>$module, "passthis"=>true, "label"=>"Restore Database");
$main->echo_button("restore_database", $params);
echo "<div class=\"spaced\">Password: ";
echo "<input class=\"spaced\" type=\"password\" name=\"restore_passwd\"/></div>";
echo "<div class=\"table\">";
echo "<div class=\"row\">";
echo "<div class=\"cell middle padded\"><input class=\"spaced\" type=\"checkbox\" name=\"xml_table_checkbox\" value=\"1\"/></div>";
echo "<div class=\"cell middle padded\">Restore XML Table</div>";
echo "</div>";
echo "<div class=\"row\">";
echo "<div class=\"cell middle padded\"><input class=\"spaced\" type=\"checkbox\" name=\"users_table_checkbox\" value=\"1\"/></div>";
echo "<div class=\"cell middle padded\">Restore Users Table</div>";
echo "</div>";
echo "<div class=\"row\">";
echo "<div class=\"cell middle padded\"><input class=\"spaced\" type=\"checkbox\" name=\"modules_table_checkbox\" value=\"1\"/></div>";
echo "<div class=\"cell middle padded\">Restore Modules Table</div>";
echo "</div>";
echo "<div class=\"row\">";
echo "<div class=\"cell middle padded\"><input class=\"spaced\" type=\"checkbox\" name=\"log_table_checkbox\" value=\"1\"/></div>";
echo "<div class=\"cell middle padded\">Restore Log Table</div>";
echo "</div>";
echo "<div class=\"row\">";
echo "<div class=\"cell middle padded\"><input class=\"spaced\" type=\"checkbox\" name=\"data_table_checkbox\" value=\"1\"/></div>";
echo "<div class=\"cell middle padded\">Restore Data Table</div>";
echo "</div>";
echo "</div>";
$params = array("class"=>"spaced","number"=>5,"target"=>$module, "passthis"=>true, "label"=>"Build Indexes");
$main->echo_button("build_indexes", $params);
echo "</div><br>";

$main->echo_state($array_state);
$main->echo_form_end();
/* END FORM */
?>
