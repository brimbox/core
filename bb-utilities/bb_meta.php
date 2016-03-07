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
/* class bb_hooks() */
//autofill
//infolinks
//postback_area
//top_level_records
//parent_record
//quick_links
//submit_buttons
//textarea_load

class bb_meta extends bb_validate {    
		
	function layouts($con, $type = false)
		{
		$arr_layouts = $this->get_json($con, "bb_layout_names");
		return $this->filter_keys($arr_layouts, array(), true, $type);		
		}
				
	function columns($con, $row_type, $type = false)
		{
		$arr_columns_json = $this->get_json($con, "bb_column_names");
		$arr_columns = $this->init($arr_columns_json[$row_type], array());
		return $this->filter_keys($arr_columns, array(), true, $type);			
		}
				
	function lists($con, $row_type, $type = false)
		{
		$arr_lists_json = $this->get_json($con, "bb_create_lists");
		$arr_lists = $this->init($arr_lists_json[$row_type], array());
		return $this->filter_keys($arr_lists, array(), true, $type);			
		}
				
	function dropdowns($con, $row_type, $type = false)
		{
		$arr_dropdowns_json = $this->get_json($con, "bb_dropdowns");
		$arr_dropdowns = $this->init($arr_dropdowns_json[$row_type], array());
		return $this->filter_keys($arr_dropdowns, array(), true, $type);			
		}
		
	function reduce($arr, $keys = NULL, $type = NULL)
		{
		//icould be strings or ints
		if (!is_array($keys))
			{
			$keys = array($keys);
			}
		foreach ($keys as $value)
			{
			if (isset($arr[$value]))
				{
				$arr = $arr[$value];
				}
			else
				{
				$arr = array();
				break;
				}
			}
		//default NULL will not reduce to string or integer keys
		if (is_bool($type))
			{
			//false is int, true is string, can also do nothing with NULL
			$arr = $this->filter_keys($arr, array(), true, $type);	
			}
		return $arr;			
		}
		
	function lookup($con, $lookup, $keys = NULL, $type = NULL)
		{
		//default NULL will not reduce to string or integer keys
		$arr = $this->get_json($con, $lookup);
		return $this->reduce($arr, $keys, $type);
		}
		
	function filter_keys ($arr, $filter = array(), $mode = true, $type = false)
        //function to return array with only integer keys by default
		//so far mostly loop on integer keys, so $type is not null by default
		//default behavior is different than functions lookup or reduce
        //will return empty array if $arr is not set for any reason
        {
        if (!empty($arr))
            {
			if (!is_null($type))
				{
				$callback = $type ? 'is_string' : 'is_integer';
				$keys = array_filter(array_keys($arr), $callback);
				$arr = array_intersect_key($arr, array_flip($keys));
				}
            if (!empty($filter))
                {
                if ($mode) //keep the keys in filter
                    {
                    $arr = array_intersect_key($arr, array_flip($filter));   
                    }
                else //discard the keys in filter
                    {
                    $arr = array_diff_key($arr, array_flip($filter)); 
                    }
                }
            return $arr;    
            }
        else
            {
            return array();    
            }
        }

} //end class
?>