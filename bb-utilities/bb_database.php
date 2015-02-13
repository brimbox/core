<?php if (!defined('BASE_CHECK')) exit(); ?>
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
  
/* NO HTML OUTPUT */

/* PHP FUNCTIONS */
/* class bb_database() */
//connect
//query
//query_params
//get_mbox
//get_xml
//update_xml
//search_xml
//get_next_xml_node
	
class bb_database extends bb_main {
	
	function connect()
		{
		//standard Brimbox connection
		$con_string = "host=" . DB_HOST . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASSWORD;
		$con = pg_connect($con_string); 
		if (!$con)
			{
			die("Cannot connect to database.");   
			}
		return $con;
		}
	
	function query($con, $query, $display = false)
		{
		//standard query will die on error
		$result = pg_query($con, $query);
		if (!$result)
			{
			if ($display)
				{
				$string = "<p>Error: " . pg_last_error($con) . "</p><p>Query: " . $query . "</p>";
				die($string);
				}
			die();
			}
		return $result;
		}
		
	function query_params($con, $query, $array, $display = false)
		{
		//standard query with placeholders, will die on error
		$result = pg_query_params($con, $query, $array);
		if (!$result)
			{
			if ($display)
				{
				$string = "<p>Error: " . pg_last_error($con) ."</p><p>Query: " . $query . "</p>";
				die($string);
				}
			die();  
			}
		return $result;    
		}
		
	//get mailbox connection	
	function get_mbox($mailserver, $username, $password)
		{
		//returns a mailbox connection
		$mbox = @imap_open($mailserver, $username, $password);
				
		return $mbox;
		}
		
	function get_json($con, $lookup)
		{
		//gets an xml object from the xml_table
		$query = "SELECT jsondata FROM json_table WHERE lookup IN ('" . pg_escape_string($lookup) . "');";
	
		$result = $this->query($con, $query);
		
		$row = pg_fetch_array($result);
		$json = $row['jsondata'];
			
		return json_decode($json, true);		
		}
	
	function update_json($con, $arr, $lookup)
		{
		//update xml_table with a whole xml object
		$query = "UPDATE json_table SET jsondata = '" . pg_escape_string(json_encode($arr)) . "' WHERE lookup = '" . $lookup . "';";
	
		$this->query($con, $query);
		}
		
	function get_next_node($arr, $limit)
		{
		//finds the next available node in a set of numeric keys
        $arr_keys = array_keys($arr);
        sort($arr_keys);
		$k = 0;  // initialize for first value
		$bool = false;	
		foreach($arr_keys as $i => $j)
			{
			$k = $i + 1; //$i starts at 0, $k start at 1
			if ($k <> $j)
				{
				$bool = true; //insert value in middle
				break;
				}			
			}            
		if (!$bool)
			{
			$k = $k + 1; //insert value at end
			}			
		if ($k > $limit)
			{
			return -1; //limit exceeded
			}
		else
			{
			return $k;//return value
			}
		}		
	} //end class
?>