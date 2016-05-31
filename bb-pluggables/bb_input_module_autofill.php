<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php

/*
 * Copyright (C) Brimbox LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License Version 3 (“GNU GPL v3”)
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU GPL v3 for more details.
 *
 * You should have received a copy of the GNU GPL v3 along with this program.
 * If not, see http://www.gnu.org/licenses/
 */
?>
<?php

/* Postback when entering the input module */
if (! function_exists ( 'bb_input_module_autofill' )) :

	function bb_input_module_autofill(&$arr_state) {

		global $main, $con;
		
		/*
		 * IF $row_type = $row_join THEN
		 * Use $row_join -- on Edit
		 */
		/*
		 * ELSE $row_join is the child
		 * So again use $row_join -- on Insert
		 */
		
		$arr_layouts = $main->layouts ( $con );
		
		$parent_row_type = $main->init ( $arr_state ['parent_row_type'], 0 );
		$parent_id = $main->init ( $arr_state ['parent_id'], 0 );
		$parent_primary = $main->init ( $arr_state ['parent_primary'], "" );
		
		$row_type = $main->init ( $arr_state ['row_type'], 0 );
		$row_join = $main->init ( $arr_state ['row_join'], 0 );
		$post_key = $main->init ( $arr_state ['post_key'], 0 );
		
		$query = "SELECT * FROM data_table WHERE id = " . $parent_id . ";";
		$result = $main->query ( $con, $query );
		$row = pg_fetch_array ( $result );
		
		if (($row_type != $row_join) && ($row_type == $parent_row_type) && ($parent_row_type > 0)) {
			$arr_columns = $main->columns ( $con, $row_join );
			$arr_columns_parent = $main->columns ( $con, $parent_row_type );
			
			foreach ( $arr_columns as $key => $value ) {
				$arr_search [$key] = $value ['name'];
			}
			foreach ( $arr_columns_parent as $key1 => $value1 ) {
				$col1 = $main->pad ( "c", $key1 );
				$key2 = array_search ( $value1 ['name'], $arr_search );
				// if found
				if (is_integer ( $key2 )) {
					$col2 = $main->pad ( "c", $key2 );
					// if autofill column is empty
					if ($main->blank ( $arr_state [$col2] )) {
						$arr_state [$col2] = $row [$col1];
					}
				}
			}
		}
	}





endif;
?>
