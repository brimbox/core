<?php if (!defined('BASE_CHECK')) exit(); ?>
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
  
/* NO HTML OUTPUT */

/* PHP FUNCTIONS */
/* class bb_main() */
//return_stats
//return_header
//return_rows
//get_default_row_type
//layout_dropdown
//column_dropdown
//list_dropdown
//pad
//rpad
//build_name
//convert_date
//rconvert_date
//get_directory_tree
//array_flatten
//empty_directory
//copy_directory
//replace_root
//array_iunique
//custom_trim_string
//echo_messages
//check_permission
//validate password
//build_indexes
//cleanup_database
//log_entry
//output_link
//check_child
//drill_link
//page_selector
//validate_logic
//validate_required
//validate_dropdown
//logout_link
//archive_link
//database_stats
//userrole_switch

class bb_main extends bb_report {
	
	//this quickly returns the query header stats including count and current time...
	function return_stats($result)
		{
		//count of rows is held in query, must have a "count(*) OVER () as cnt" column in query
		//in the standard modules $cntrows['cnt'] or $cntrows[0] will work
		//cannot use pg_num_rows because that returns the count according to OFFSET, as defined by $return_rows
		$cntrows = pg_fetch_array($result);
		if ($cntrows['cnt'] > 0)
			{
			date_default_timezone_set(USER_TIMEZONE);
			echo  "<div class = \"spaced left\">Rows: " . $cntrows['cnt'] . " Date: " . date('Y-m-d h:i A', time()) . "</div>";
			}
		//reset back to zero
		pg_result_seek($result,0);
		
		return $cntrows;
		}
	
	// this function returns a record header with a view_details link for each record returned
	function return_header($row, $target, $link = true, $mark = true)
		 {    
		 echo "<div class = \"left italic nowrap\">";
		 $row_type = $row['row_type'];
		 $row_type_left = $row['row_type_left'];
		 //do not return link, (ie cascade)
		 echo "<span class=\"bold colored\">" . chr($row_type + 64) . $row['id'] . "</span>";
		 //archive or secure > 0
		 if ($mark)
			{
			if ($row['archive'] > 0)
				{
				$str = str_repeat('*', $row['archive']);
				echo "<span class=\"error bold\">" . $str . "</span>";
				}
			if ($row['secure'] > 0)
				{
				$str = str_repeat('+', $row['secure']);
				echo "<span class=\"error bold\">" . $str . "</span>";
				}
			}
		 if (!empty($row['hdr']) && $link)
			 {
			 //calls javascript in bb_link
			 echo " <button class = \"link italic\" onclick=\"bb_links.standard(" . (int)$row['key1'] . "," . (int)$row['row_type_left'] . ", '" . $target . "'); return false;\">";
			 echo htmlentities($row['hdr']) . "</button> / ";
			 }
			 
		echo " Created: " .  $this->convert_date($row['create_date'], "Y-m-d h:i A") . " / ";	
		echo "Modified: " .  $this->convert_date($row['modify_date'], "Y-m-d h:i A") . "</div>";
		} //function
		
	//this outputs a record of data, returning the total number of rows, which is found in the cnt column 
	//$row1 is the row number, and $row2 is the catch to see when the row changes
	//$col2 is the actual name of the columns from the xml, $row[$col2] is  $row['c03']
	//$child is the visable name of the column the user name of the column
	
	function return_rows($row, $xml_column, $check = false)
		{
		//you could always feed this function only non-secured columns
		$row2 = 1;  //to catch row number change
		$row3 = ""; //string with row data in it
		$secure = false; //must be check = true and secure = 1 to secure column
		$pop = false; //to catch empty rows, pop = true for non-empty rows
		foreach($xml_column->children() as $child)
			{
			$col2 = $child->getName(); //node name
			$row1 = (int)$child['row']; //current row number
			//always skipped first time
			if ($row2 != $row1) 
				{
				if ($pop)
					{
					echo "<div class = \"nowrap left\">" . $row3 . "</div><div class = \"clear\"></div>";                
					}
				$row3 = ""; //reset row data
				$pop = false; //start row again with pop  = false
				}
			//secure > 0 means true
			$secure = ($check && ($child['secure'] > 0)) ? true : false;
			//check secure == 0
			if (!$secure)
				{
				if (!empty($row[$col2]))
					{
					$pop = true; //field has data, so row will too
					}
				//prepare row, if row has data also echo the empty cell spots
				$row3 .= "<div class = \"overflow " . $child['leng'] . "\">" . htmlentities($row[$col2]) . "</div>";
				}
			$row2 = $row1;
			}
		//echo the last row if populated
		if ($pop)
			{
			echo "<div class = \"nowrap left\">" . $row3 . "</div><div class = \"clear\"></div>";                
			}
		return (int)$row['cnt'];
		}
		
	function get_default_row_type($xml_layouts, $check = false)
		{
		//at least one layout should be set
		//this finds the first layout as the default value
		$i = 0; // no layouts
		//no security implemented
		$path = "//*[@secure>=0]";
		if ($check)
			{
			//layout security implemented
			$path = "//*[@secure=0]";	
			}
		$arr_simple = $xml_layouts->xpath($path);
		if (count($arr_simple) > 0) //check if empty
			{
			$i = (int)substr($arr_simple[0]->getName(),1); //get row_type
			}
		return $i;
	   }
			
	//this returns a standard header combo for selecting record type
	//for this function the javascript function reload_on_layout() is uniquely tailored to the calling module    
	function layout_dropdown($xml_layouts, $name, $row_type, $params = array())
		{
		$class = isset($params['class']) ? $params['class'] : "";
		$onchange = isset($params['onchange']) ? $params['onchange'] . "; return false;" : "";
		$check = isset($params['check']) ? $params['check'] : false;
		$empty = isset($params['empty']) ? $params['empty'] : false;
		$all = isset($params['all']) ? $params['all'] : false;
		$label_class = isset($params['label_class']) ? $params['label_class'] : "";
		$label = isset($params['label']) ? $params['label'] : "";
		
		if (!empty($label))
			{
			echo "<label class = \"" . $label_class . "\">" . $label . "</label>";
			}
			
		echo "<select name = \"" . $name . "\" class = \"" . $class . "\" onchange=\"" . $onchange  . "\">";
		if ($empty)
			{
			echo "<option value=\"-1\" " . (-1 == $row_type ? "selected" : "") . "></option>";
			}
		if ($all)
			{
			echo "<option value=\"0\" " . (0 == $row_type ? "selected" : "") . ">All&nbsp;</option>";
			}
		 foreach ($xml_layouts->children() as $child)
				{
				$secure = ($check && ($child['secure'] > 0)) ? 1 : 0;
				if (!($secure))
					{
					$i = $this->rpad($child->getName());
					echo "<option value=\"" . $i . "\" " . ($i == $row_type ? "selected" : "") . ">" . htmlentities((string)$child['plural']) . "&nbsp;</option>";
					}
				}
		 echo "</select>";
		}
			 
	function column_dropdown($xml_column, $name, $col_type, $params = array())
		{
		$class = isset($params['class']) ? $params['class'] : "";
		$onchange  = isset($params['onchange']) ? $params['onchange'] . "; return false;" : "";
		$check = isset($params['check']) ? $params['check'] : false;
		$empty = isset($params['empty']) ? $params['empty'] : false;
		$all = isset($params['all']) ? $params['all'] : false;
		$label_class = isset($params['label_class']) ? $params['label_class'] : "";
		$label = isset($params['label']) ? $params['label'] : "";
		
		if (!empty($label))
			{
			echo "<label class = \"" . $label_class . "\">" . $label . "</label>";
			}

		//Security there should be no way to get column with secured row_type
		echo "<select name=\"". $name . "\" class=\"". $class . "\" onchange=\"" . $onchange  . "\">";
		//build field options for column names
		if ($empty)
			{
			echo "<option value=\"-1\" " . (-1 == $row_type ? "selected" : "") . "></option>";
			}
		if ($all)
			{
			echo "<option value=\"0\" " . (0 == $col_type ? "selected" : "") . ">All&nbsp;</option>";
			}
		foreach($xml_column->children() as $child)
			{
			$secure = ($check && ($child['secure'] > 0)) ? 1 : 0;
			if (!($secure))
				{
				$i = $this->rpad($child->getName());
				echo "<option value=\"" . $i . "\" " . ($i == $col_type ? "selected" : "") . ">" . htmlentities((string)$child) . "&nbsp;</option>";
				}
			}
		echo "</select>";
		}
		
	function list_dropdown($xml_lists, $name, $list_number, $row_type, $params = array())
		{
		//Security there should be no way to get column with secured row_type
		$class = isset($params['class']) ? $params['class'] : "";
		$onchange  = isset($params['onchange']) ? $params['onchange'] . "; return false;" : "";
		$archive = isset($params['archive']) ? $params['archive'] : false;
		$empty = isset($params['empty']) ? $params['empty'] : false;
		$label_class = isset($params['label_class']) ? $params['label_class'] : "";
		$label = isset($params['label']) ? $params['label'] : "";
		
		if (!empty($label))
			{
			echo "<label class = \"" . $label_class . "\">" . $label . "</label>";
			}

		echo "<select name = \"" . $name . "\" class=\"" . $class . "\" onchange=\"" . $onchange  . "\">";
		//list combo
		if ($empty)
			{
			echo "<option value=\"-1\" " . (-1 == $row_type ? "selected" : "") . "></option>";
			}
		$path = "//*[@row_type = " . $row_type . "]";
		$arr_lists = $xml_lists->xpath($path);
		foreach($arr_lists as $child)
			{
			//either 1 or 0 for archive
			if (!(int)$child['archive'] || $archive)
				{
				$i = $this->rpad($child->getName());
				$archive_flag = ((int)$child['archive']) ? "*" : "";
				echo "<option value=\"" . $i. "\"" . ($i == $list_number   ? " selected " : "") . ">" . htmlentities((string)$child) . $archive_flag . "&nbsp;</option>";
				}
			}
		echo "</select>";    }
	
	//pad a number to a column name	
	function pad($char, $number, $padlen = 2)
		{
		return $char . str_pad($number, $padlen,"0",STR_PAD_LEFT);
		}
		
	//get a number from a column name	
	function rpad($padded)
		{
		return (int)substr($padded,1);	
		}
		
	//Function to turn firstname (fname), middle initial(minit) and lastname (into string)
	function build_name($row)
		{
		$arr_name = array();
		$arr_row = array("fname","minit","lname");
		foreach ($arr_row as $value)
			{
			 if (trim($row[$value]) <> "")
				{
				array_push($arr_name,trim($row[$value]));
				}
			}
		$str = implode(" ", $arr_name);
		return $str;
		}
	
	//function to convert dates to proper time zone and format
	//convert from program time to user time
	function convert_date($date, $format = "Y-m-d")
		{
		$date = new DateTime($date, new DateTimeZone(DB_TIMEZONE));
		$date->setTimezone(new DateTimeZone(USER_TIMEZONE));
		return $date->format($format);
		}
	//convert from user time to program time	
	function rconvert_date($date, $format = "Y-m-d")
		{
		$date = new DateTime($date, new DateTimeZone(USER_TIMEZONE));
		$date->setTimezone(new DateTimeZone(DB_TIMEZONE));
		return $date->format($format);
		}
	
	
	//function to get all paths in a directory
	//directory recursion function
	function get_directory_tree($directory)
		{
		$filter = array(".","..","Thumbs.db",".svn");
		$dirs = array_diff(scandir($directory),$filter);
		$dir_array = Array();
		foreach($dirs as $d)
		if (is_dir($directory . $d))
			{
			$d = $d . "/";
			$dir_array[$d] = $this->get_directory_tree($directory . $d);
			}
		else
			{
			$dir_array[$d] = $directory . $d;
			}
		return $this->array_flatten($dir_array);
		}		
			
	//flattens the array returned in $main->get_directory_tree
	function array_flatten($a)
		{
		foreach($a as $k=>$v)
			  {
			  if (!empty($v))
				   {
				   $a[$k]=(array)$v;
				   }
			  }
		 if (!empty($a))
			  {
			  return call_user_func_array('array_merge',$a);
			  }
		 else
			  {
			  return array();    
			  }
		}
			
	function empty_directory($dir, $root = "")
		{
		 //true means to keep directory
		 //false means to delete directory
		if (is_dir($dir))
			{
			$objects = scandir($dir);
			foreach ($objects as $object)
				{
				if (substr($object,0,1) != ".")
					{                    
					if (is_dir($dir . $object))
						{
						//not empty recurse
						$object = $object . "/";
						$this->empty_directory($dir . $object, $root);
						}
					else
						{
						//remove file
						@unlink($dir . $object);
						}
					}
				}
			reset($objects);
			}
		$rmdir_bool = ($dir == $root) ? false : true;
		if ($rmdir_bool)
			{
			@rmdir(trim($dir,"/"));
			}
		}
			  
	function copy_directory($from_directory, $to_directory)
		 {
		 $filter = array(".","..","Thumbs.db");
		 $dirs = array_diff(scandir($from_directory),$filter);
		 $dir_array = Array();
		 foreach($dirs as $d)
			  {         
			  if (is_dir($from_directory . $d))
				  {
				  $d = $d . "/";
				  @mkdir( $to_directory . $d);
				  $dir_array[$d] = $this->copy_directory($from_directory . $d, $to_directory . $d);
				  }
			  else
				  {
				  @copy($from_directory . $d, $to_directory . $d);
				  }
			   }
		 }
		 
	function replace_root($dir, $search, $replace)
		{
		return $replace . substr($dir, strlen($search));
		}
	
	//array_unique not case sensitive for testing
	function array_iunique($array)
		{
		return array_unique(array_map('strtolower',$array));
		}
			
	//function to strip tabs and new lines from string
	function custom_trim_string($str, $length, $eol = true, $quotes = false)
		{
		if ($eol)
			{
			//changes a bunch of control chars to single spaces
			$pattern = "/[\\t\\0\\x0B\\x0C\\r\\n]+/";
			$str = preg_replace($pattern, " ", $str);
			//purge new line with nothing, default purge
			}
		else
			{
			//changes a bunch of control chars to single spaces except for new lines
			$pattern = "/[\\t\\0\\x0B\\x0C\\r]+/";
			$str = trim(preg_replace($pattern, " ", $str)); //trim this one three times
			$pattern = "/ {0,}(\\n{1}) {0,}/";
			$str = preg_replace($pattern, "\n", $str);
			}
		if ($quotes)
			{
			//purge double quotes
			$str = str_replace('"', "", $str);	
			}
		//trim and truncate
		$str = substr(trim($str), 0, $length);
		//trim again because truncate could leave ending space, then try to encode
		$str = utf8_encode(trim($str));
		return $str;
		}
			
	//$input can be either array or string   
	function echo_messages($input)
		{
		if (!empty($input))
			{
			if (is_string($input))
				{
				$message = $input;
				$class = (strncasecmp($message, "Error:", 6) == 1) ? "error": "message";
				echo "<p class=\"" . $class . "\">" . $message . "</p>";
				}
			elseif (is_array($input))
				{
				foreach ($input as $message)
					{
					$class = (strncasecmp($message, "Error:", 6) == 0) ? "error": "message";
					echo "<p class=\"" . $class . "\">" . $message . "</p>";
					}
				}
			}  
		}	
	
	function check_permission($permission)
		{
		global $array_userroles;
		//waterfall
		//this will also check that session is set
		$userrole = $_SESSION['userrole'];
        $email = $_SESSION['email'];
        if (is_string($permission)) //string input
			{
			$permission = (int)array_search($permission, $array_userroles);	
			}
        if (is_int($permission)) //either int or string input
            {
            $permission = array($permission);    
            }            
		if (!in_array($userrole, $permission))
			{
			echo "Insufficient Permission.";
            session_destroy();
			die();
			}
        
		//will have sufficient permission
		//this for when administratprs lock the db down
		if (SINGLE_USER_ONLY <> '')
			{
            if ($email <> SINGLE_USER_ONLY)
                {
                echo "Program switched to single user mode.";
                session_destroy();
                die();
                }
			}
		if (ADMIN_ONLY == "YES")
			{
			if ($userrole <> 5)
				{
				echo "Program switched to admin only mode.";
                session_destroy();
				die();    
				}
			}    
		}
	
	function validate_login($con, $email, $passwd, $userlevels)
		{
		global $array_userroles;
		//waterfall
		//this will also check that session is set
        $userrole = (int)$_SESSION['userrole'];
		if (!is_array($userlevels))
			{
			$userlevels = array($userlevels);	
			}
        //waterfall
        if (in_array($userrole, $userlevels))
            {
            $query = "SELECT * FROM users_table WHERE " . $userrole . " = ANY (userroles) AND UPPER(email) = UPPER('". pg_escape_string($email) . "');";
            $result = $this->query($con, $query);
            if (pg_num_rows($result) == 1)
                {
                $row = pg_fetch_array($result);
                if (hash('sha512', $passwd . $row['salt']) == $row['hash'])
                    {
                    return true;
                    }
                }
			}		
		return false; 
		}
        
	function build_indexes($con, $row_type)
		{
		//reduce xml_layout to include only 1 row_type for column update, all for rebuild indexes
		global $array_guest_index;
		
		$arr_union_query = array();
		$xml_layouts = $this->get_xml($con, "bb_layout_names");
		$xml_columns = $this->get_xml($con, "bb_column_names");
		
		$arr_row_type = array();    
		if ($row_type == 0)
			{
			foreach ($xml_layouts->children() as $child) 
				{
				$row_type = (int)substr($child->getName(),1);
				array_push($arr_row_type, $row_type);
				}
			}
		else
			{
			array_push($arr_row_type ,$row_type);   
			}
			
		$arr_ts_vector_fts = array();
		$arr_ts_vector_ftg = array();
		foreach ($arr_row_type as $row_type)
			{
			$layout = "l" . str_pad($row_type,2,"0",STR_PAD_LEFT);
			$xml_column = $xml_columns->$layout;
			//loop through searchable columns
			foreach($xml_column->children() as $child)
				{
				$col = $child->getName();
				$search_flag = ($child['search'] == 1) ? true : false;
				//guest flag
				if (empty($array_guest_index))
					{
					$guest_flag = (($child['search'] == 1) && ($child['secure'] == 0)) ? true : false;
					}
				else
					{
					$guest_flag = (($child['search'] == 1) && in_array((int)$child['secure'], $array_guest_index)) ? true : false;						
					}
				//build fts SQL code
				if ($search_flag)
					{
					array_push($arr_ts_vector_fts,  $col . " || ' ' || regexp_replace(" . $col . ", E'(\\\\W)+', ' ', 'g')");
					}
				if ($guest_flag)
					{
					array_push($arr_ts_vector_ftg,  $col . " || ' ' || regexp_replace(" . $col . ", E'(\\\\W)+', ' ', 'g')");
					}                
				} //$xml_column
			//implode arrays with guest column full text query definitions
			$str_ts_vector_fts = !empty($arr_ts_vector_fts) ? implode(" || ' ' || ", $arr_ts_vector_fts) : "''";
			$str_ts_vector_ftg = !empty($arr_ts_vector_ftg) ? implode(" || ' ' || ", $arr_ts_vector_ftg) : "''";
				
			//build the union query array
			$union_query = "SELECT id, to_tsvector(" . $str_ts_vector_fts . ") as fts, to_tsvector(" . $str_ts_vector_ftg . ") as ftg FROM data_table WHERE row_type = " . $row_type;  
			array_push($arr_union_query, $union_query);            
			}
					
		//implode the union query   
		$str_union_query = implode(" UNION ALL ",$arr_union_query);
		
		//update joined with union on id
		$query = "UPDATE data_table SET fts = T1.fts, ftg = T1.ftg " .
				 "FROM (" . $str_union_query . ") T1 " .
				 "WHERE data_table.id = T1.id";
		//echo $query . "<br><br>";
		$this->query($con, $query);    
		}
		
	function cleanup_database_data($con)
		{
		for ($i=1; $i<=48; $i++)
			{
			$col= "c" . str_pad($i,2,"0",STR_PAD_LEFT);
			//POSIX regex, no null because db text fields cannot have nulls
			//change tabs, form feeds, new lines, returns and vertical tabs to space and trim
			$query = "UPDATE data_table SET " . $col . " =  trim(both FROM regexp_replace(" . $col . ", E'[\\t\\x0B\\x0C\\r\\n]+', ' ', 'g' )) WHERE " . $col . " <> '';";
			$this->query($con, $query);
			}
		for ($i=49; $i<=50; $i++)
			{
			$col= "c" . str_pad($i,2,"0",STR_PAD_LEFT);
			//POSIX regex, no null because db text fields cannot have nulls
			//replace all tab, form feeds and return with a space and then remove all space from end of line keeping new lines
			$query = "UPDATE data_table SET " . $col . " =  trim(both FROM regexp_replace(col, E' {0,}\\n{1} {0,}', E'\n', 'g' )) " .
				     "FROM (SELECT id, regexp_replace(" . $col . ", E'[\\t\\x0B\\x0C\\r]+', ' ', 'g' ) as col FROM data_table WHERE " . $col . " <> '') T1 WHERE data_table.id = T1.id;";
			$this->query($con, $query);
			}
		}
	function cleanup_database_layouts($con)
		{
		$xml_layouts = $this->get_xml($con,"bb_layout_names");
		$xml_columns = $this->get_xml($con,"bb_column_names");
		$xml_dropdowns = $this->get_xml($con, "bb_dropdowns");
		for ($i=1; $i<=26; $i++)
			{
			$layout = $this->pad("l", $i);
			if (!isset($xml_layouts->$layout)) //clean up rows
				{
				unset($xml_columns->$layout);
				unset($xml_dropdowns->$layout);
				$query = "DELETE FROM data_table WHERE row_type IN (" . $i . ");";
				$this->query($con, $query);
				}
			}
		$this->update_xml($con, $xml_dropdowns, "bb_dropdowns");
		$this->update_xml($con, $xml_columns, "bb_column_names");
		}
		
	function cleanup_database_columns($con)
		{
		$xml_columns = $this->get_xml($con,"bb_column_names");
		$xml_dropdowns = $this->get_xml($con, "bb_dropdowns");
		for ($i=1; $i<=26; $i++)
			{
			$layout = $this->pad("l", $i);
			$xml_column = $xml_columns->$layout;
			for ($j=1; $j<=50; $j++)
				{
				$col = $this->pad("c", $j);
				if (!isset($xml_column->$col))
					{
					$set_clause = $col . " = ''";
					if ($j == 46)
						{
						$set_clause = $col . " = '', key2 = -1";
						}
					$query = "UPDATE data_table SET " .  $set_clause . " WHERE row_type = " . $i . " AND " . $col . " <> '';";
					$this->query($con, $query);
					if (isset($xml_dropdowns->$layout))
						{
						unset($xml_dropdowns->$layout->$col);	
						}
					}
				}
			}
		$this->update_xml($con, $xml_dropdowns, "bb_dropdowns"); 	
		}

	
	function log_entry($con, $message, $email = "")
		{
		if (isset($_SESSION['email']))
			{
			$email = $_SESSION['email'];    
			}
		$ip = $_SERVER['REMOTE_ADDR'];
		$arr_log = array($email,$ip, $message);
		$query = "INSERT INTO log_table (email, ip_address, action) VALUES ($1,$2,$3)";
		$this->query_params($con, $query, $arr_log);
		}
		
	function output_links($row, $xml_layouts, $userrole)
		{
		global $array_links;
		
		$arr_work = array();
		switch ($userrole)
            {
            case 1: //guest
			if (isset($array_links[1]))
				{
				$arr_work = $array_links[1];
				}
            break;
            case 2: //viewer
			if (isset($array_links[2]))
				{
				$arr_work = $array_links[2];
				}	
            break;
            case 3: //user
            if (isset($array_links[3]))
				{
				$arr_work = $array_links[3];
				}
            break;               
            case 4: //user, setup
			if (isset($array_links[4]))
				{
				$arr_work = $array_links[4];
				}
            break;
            case 5: //user, setup, admin
            if (isset($array_links[5]))
				{
				$arr_work = $array_links[5];
				}
            break;
            }
		
		foreach ($arr_work as $arr)
			{
			array_unshift($arr[1], $xml_layouts);	
			array_unshift($arr[1], $row);
			call_user_func_array($arr[0], $arr[1]);
			}
		}
		
	function check_child($row_type, $xml_layouts)
		{
		//checks for child records or record
		$test = false;
		foreach($xml_layouts->children() as $child)
			{
			if ($row_type == (int)$child['parent'])
				{
				$test = true;
				break;
				}
			}
		return $test;
		}
		
	function drill_links($post_key, $row_type, $xml_layouts, $module, $text)
		{
		//call function add drill links in class bb_link
		call_user_func_array(array($this, "drill") , array($post_key, $row_type, $xml_layouts, $module, $text));
		}
		
	function page_selector($element, $offset, $count_rows, $return_rows, $pagination)
		{
		$half = floor($pagination/2);
		$max_return = floor(($count_rows - 1)/$return_rows) + 1;
		//set top and bottom
		$bottom = $offset - $half;
		$top = $offset + $half;
		//adjust if $bottom less than zero
		while ($bottom < 1)
			{
			$bottom++;
			if ($top < $max_return)
				{
				$top++;
				}
			}
		//adjust if top greater then max
		while ($top > $max_return)
			{
			$top--;
			if ($bottom > 1)
				{
				$bottom--;
				}
			}
			
		echo "<br>";
		echo "<div class=\"center\">";
		if ($top > $bottom) //skip only one page
			{
			//echo page selector out...
			echo "<button class=\"link none\" onclick=\"bb_page_selector('". $element ."','1')\">First</button>&nbsp;&nbsp;&nbsp;";
			if ($offset > 1)
				{
				echo "<button class=\"link none\" onclick=\"bb_page_selector('". $element ."','" . ($offset- 1) . "')\">Prev</button>&nbsp;&nbsp;&nbsp;";  
				}	
			for ($i=$bottom; $i<=$top; $i++)
				{
				if ($i==$offset) 
					{
					$class = "class=\"link bold underline\"";
					}
				else
					{
					$class = "class=\"link none\"";	
					}
				echo "<button " . $class . " onclick=\"bb_page_selector('". $element ."','" . $i . "')\">";
				echo $i . "</button>&nbsp;&nbsp;&nbsp;";
				}	
			if ($offset < $max_return)
				{
				echo "<button class=\"link none\" onclick=\"bb_page_selector('". $element ."','" . ($offset + 1) . "')\">Next</button>&nbsp;&nbsp;&nbsp;";
				}
			echo "<button class=\"link none\" onclick=\"bb_page_selector('". $element ."','" . $max_return . "')\">Last</button>";  	
			}
		echo "</div>"; 
		echo "<br>";
		}
		
	function validate_logic($type, &$value, $error = false)
		{
		//validates a data type set in "Set Column Names"
		//returns false on good, true or error string if bad
		global $array_validation;
		$return_value = call_user_func_array($array_validation[$type], array(&$value, $error));	
		return $return_value;
		}
		
	function validate_required(&$value, $error = false)
		{
		//Checks to see that a field has some data
		//returns false on good, true or error string if bad
		$value = trim($value);
		if (!empty($value) || ($value === '0'))
			{
			$return_value = false;
			}
		else
			{
			$return_value = $error ? "Error: This value is required." : true;   
			}
		return $return_value;
		}
				
	function validate_dropdown(&$value, $path, $xml, $error = false)
		{
		//validates dropdowns, primarily used in bulk loads (Upload Data)
		//returns false on good, true or error string if bad
		$test_value = strtolower($value);
		//would be nice to have the lower case function, available in xPath 2.0
		$test_path = $path . "[translate(text(), \"ABCDEFGHJIKLMNOPQRSTUVWXYZ\", \"abcdefghjiklmnopqrstuvwxyz\") = \"" . $test_value . "\"]";
		$arr = $xml->xpath($test_path);
		if (isset($arr[0]))
			{
			//update $value, return false for no error
			$value = (string)$arr[0];
			$return_value = false;
			}
		else
			{
			//return string error or boolean true depending on error flag
			$return_value = $error ? "Value not found in dropdown list." : true;
			}
		return $return_value;
		}
        
    function logout_link($class = "bold link underline", $label = "Logout")
        {
        $params = array("class"=>$class,"number"=>0,"target"=>"bb_logout", "passthis"=>true, "label"=>$label);
        $this->echo_button("logout", $params);   
        }
        
    function archive_link($class_button = "link underline",  $class_span = "bold")
        {
        global $module;
        
        if ($this->post('bb_button', $module) == -1)
            {
            if ($_SESSION['archive'] == 0)
                {
                $_SESSION['archive'] = 1;
                }
            else
                {
                $_SESSION['archive'] = 0;           
                }
            }
            
        $label = ($_SESSION['archive'] == 0) ? "Off" : "On";
        
        echo "<span class=\"" . $class_span . "\">Archive mode is: ";
        $params = array("class"=>$class_button,"number"=>-1,"target"=>$module, "passthis"=>true, "label"=>$label);
        $this->echo_button("archive", $params);
        echo "</span>";
        }
        
    function database_stats($class_div = "bold", $class_span = "colored")
        {
        echo "<div class=\"" . $class_div . "\">Hello <span class=\"" . $class_span . "\">" . $_SESSION['name'] . "</span></div>";
        echo "<div class=\"" . $class_div . "\">You are logged in as: <span class=\"" . $class_span . "\">" . $_SESSION['email'] . "</span></div>";	
        echo "<div class=\"" . $class_div . "\">You are using database: <span class=\"" . $class_span . "\">" . DB_NAME . "</span></div>";
        echo "<div class=\"" . $class_div . "\">This database is known as: <span class=\"" . $class_span . "\">" . DB_FRIENDLY_NAME . "</span></div>";
        echo "<div class=\"" . $class_div . "\">This database email address is: <span class=\"" . $class_span . "\">" . EMAIL_ADDRESS . "</span></div>";
        }
        
    function userrole_switch($class_span = "bold", $class_button = "link underline")
        {
        global $array_userroles;
        global $userrole;
        global $userroles;
        
        $cnt = count($userroles);
        if ($cnt > 1)
            {
            echo "<span class=\"" . $class_span . "\">Current userrole is: ";
            $i = 1;
            foreach ($userroles as $value)
                {
                $bold = ($value == $userrole) ? " bold" : "";
                $params = array("class"=>$class_button . $bold,"number"=>$value,"target"=>"bb_logout", "passthis"=>true, "label"=>$array_userroles[$value]);
                $this->echo_button("role" . $value, $params);
                $separator = ($i <> $cnt) ? ", " : "";
                echo $separator;
                $i++;
                }
            echo "</span>";
            }
        }	
	} //end class
?>