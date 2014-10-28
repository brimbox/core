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
/* class bb_hooks() */
//autofill

class bb_hooks extends bb_work {
			
	function autofill($row, &$arr_state, $arr_columns, $row_type, $parent_row_type)
		{
		$arr_column_parent = $this->filter_keys($arr_columns[$parent_row_type]);
		$arr_column_reduced = $this->filter_keys($arr_columns[$row_type]);
        //build array for search
        foreach ($arr_column_reduced as $key => $value)
            {
            $arr_search[$key] = $value['name'];
            }
        foreach ($arr_column_parent as $key1 => $value1)
            {
            $col1 = $this->pad("c", $key1);    
            $key2 = array_search($value1['name'], $arr_search);
                //if found
            if (is_integer($key2))
                {
                $col2 = $this->pad("c", $key2);
                //if autofill column is empty
                if (empty($arr_state[$col2]))
                    {
                    $arr_state[$col2] = $row[$col1];   
                    }
                }
            }
		} //end function
} //end class
?>