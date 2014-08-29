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
/* class bb_database() */
//connect
//query
//query_params
//get_mbox
//get_xml
//update_xml
//search_xml
//get_next_xml_node
	
class bb_database {
	
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
				die($query);
				}
			die("Cannot query database.");
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
				die($query);
				}
			die("Cannot query database.");  
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
	
	function get_xml($con, $lookup)
		{
		//gets an xml object from the xml_table
		$query = "SELECT xmldata FROM xml_table WHERE lookup IN ('" . pg_escape_string($lookup) . "');";
	
		$result = $this->query($con, $query);
		
		$row = pg_fetch_array($result);
		$xml = simplexml_load_string($row['xmldata']);
			
		return $xml;		
		}
	
	function update_xml($con, $xml, $lookup)
		{
		//update xml_table with a whole xml object
		$query = "UPDATE xml_table SET xmldata = '" . pg_escape_string($xml->asXML()) . "'::xml WHERE lookup = '" . $lookup . "';";
	
		$this->query($con, $query);
		}
	
	function search_xml($xml, $path)
		{
		//gets xml node based on path
		//double quotes in path will not work
		$node = $xml->xpath($path);
		return $node;    
		}
	    
	function get_next_xml_node($xml, $path, $limit)
		{
		//when there are nodes like c001, c002, c004, c005 finds next empty value ie 3
		//double quotes in path will not work
		$k = 0;  // initialize for first value
		$bool = false;	
		$arr_xpath = $xml->xpath($path);
		$arr_work = array();
		foreach ($arr_xpath as $object)
			{
			//could use bb_rpad but main not invoked
			$l = (int)substr($object->getName(),1);
			array_push($arr_work, $l); //push next 001...007 on list
			}
		sort($arr_work);
		foreach($arr_work as $i => $j)
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
			return $k;//return next value
			}
		}
		
	} //end class
?>