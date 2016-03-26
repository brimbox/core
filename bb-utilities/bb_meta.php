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

/* NO HTML OUTPUT */

/* PHP FUNCTIONS */
/* class bb_hooks() */
// layouts
// infolinks
// postback_area
// top_level_records
// parent_record
// quick_links
// submit_buttons
// textarea_load
class bb_meta extends bb_validate {

	function layouts($con, $key_type = false, $sort = true) {

		$arr_layouts_json = $this->get_json ( $con, "bb_layout_names" );
		$arr_layouts = $this->filter_keys ( $arr_layouts_json, array (), true, $key_type );
		if ($key_type === false && $sort) {
			uasort ( $arr_layouts, array (
					$this,
					'order_sort' 
			) );
		}
		return $arr_layouts;
	}

	function columns($con, $row_type, $key_type = false, $sort = true) {

		$arr_columns_json = $this->get_json ( $con, "bb_column_names" );
		$arr_columns = $this->init ( $arr_columns_json [$row_type], array () );
		$arr_columns = $this->filter_keys ( $arr_columns, array (), true, $key_type );
		if ($key_type === false && $sort) {
			uasort ( $arr_columns, array (
					$this,
					'order_sort' 
			) );
		}
		return $arr_columns;
	}

	function alternative($con, $row_type, $definition, $key_type = false, $sort = true) {
		// get core column info
		$arr_columns_core = $this->columns ( $con, $row_type, NULL );
		// get alternative columns
		
		$arr_columns_alt = $this->init ( $arr_columns_core ['alternative'] [$definition], array () );
		$arr_field_keys = array_keys ( $this->init ( $arr_columns_core ['fields'], array () ) );
		$arr_properties = $this->filter_keys ( $arr_columns_core, array_keys ( $this->init ( $arr_columns_core ['properties'], array () ) ), true, true );
		// loop to keep order
		$arr_columns = array ();
		if (! empty ( $arr_columns_alt )) {
			foreach ( $arr_columns_alt as $key => $value ) {
				foreach ( $arr_field_keys as $field ) {
					if (isset ( $arr_columns_alt [$key] [$field] )) {
						$arr_columns [$key] [$field] = $arr_columns_alt [$key] [$field];
					} else {
						$arr_columns [$key] [$field] = $arr_columns_core [$key] [$field];
					}
				}
			}
			if (! empty ( $arr_properties )) {
				$arr_columns = $arr_columns + $arr_properties;
				echo "<br><br>";
				// print_r($arr_columns_alt);
			}
		}
		$arr_columns = $this->filter_keys ( $arr_columns, array (), true, $key_type );
		if ($key_type === false && $sort) {
			uasort ( $arr_columns, array (
					$this,
					'order_sort' 
			) );
		}
		return $arr_columns;
	}

	function properties($con, $row_type) {

		$arr_columns = $this->columns ( $con, $row_type, NULL );
		$arr_props_keys = $this->init ( $arr_columns ['properties'], array () );
		return $this->filter_keys ( $arr_columns, array_keys ( $arr_props_keys ), true, true );
	}

	function lists($con, $row_type, $key_type = false, $sort = true) {

		$arr_lists_json = $this->get_json ( $con, "bb_create_lists" );
		$arr_lists = $this->init ( $arr_lists_json [$row_type], array () );
		$arr_lists = $this->filter_keys ( $arr_lists, array (), true, $key_type );
		if ($key_type === false && $sort) {
			uasort ( $arr_lists, array (
					$this,
					'name_sort' 
			) );
		}
		return $arr_lists;
	}

	function dropdowns($con, $row_type, $key_type = false) {

		$arr_dropdowns_json = $this->get_json ( $con, "bb_dropdowns" );
		$arr_dropdowns = $this->init ( $arr_dropdowns_json [$row_type], array () );
		return $this->filter_keys ( $arr_dropdowns, array (), true, $key_type );
	}

	function order_sort($a, $b) {
		// would be quicker to do this when defining
		if ($a ['order'] == $b ['order']) {
			return 0;
		}
		return ($a ['order'] < $b ['order']) ? - 1 : 1;
	}

	function name_sort($a, $b) {
		// would be quicker to do this when defining
		if ($a ['name'] == $b ['name']) {
			return 0;
		}
		return ($a ['name'] < $b ['name']) ? - 1 : 1;
	}

	function reduce($arr, $keys = NULL, $key_type = NULL) {
		// icould be strings or ints
		if (! is_array ( $keys )) {
			$keys = array (
					$keys 
			);
		}
		foreach ( $keys as $value ) {
			if (isset ( $arr [$value] )) {
				$arr = $arr [$value];
			} else {
				$arr = array ();
				break;
			}
		}
		// default NULL will not reduce to string or integer keys
		if (is_bool ( $key_type )) {
			// false is int, true is string, can also do nothing with NULL
			$arr = $this->filter_keys ( $arr, array (), true, $key_type );
		}
		return $arr;
	}

	function filter_keys($arr, $filter = array(), $keep_mode = true, $key_type = false) 
	// function to return array with only integer keys by default
	// so far mostly loop on integer keys, so $key_type is not null by default
	// default behavior is different than functions lookup or reduce
	// will return empty array if $arr is not set for any reason
	{

		if (! empty ( $arr )) {
			if (! is_null ( $key_type )) {
				// true string, false integer
				$callback = $key_type ? 'is_string' : 'is_integer';
				$keys = array_filter ( array_keys ( $arr ), $callback );
				$arr = array_intersect_key ( $arr, array_flip ( $keys ) );
			}
			// empty filter to skip
			if (! empty ( $filter )) {
				if ($keep_mode) // keep the keys in filter
{
					$arr = array_intersect_key ( $arr, array_flip ( $filter ) );
				} else // discard the keys in filter
{
					$arr = array_diff_key ( $arr, array_flip ( $filter ) );
				}
			}
			return $arr;
		} else {
			return array ();
		}
	}
} // end class
?>