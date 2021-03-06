<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
 * Copyright (C) Brimbox LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License Version 3 (GNU GPL v3)
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
// class bb_meta
// layouts
// columns
// alternative
// columns_properties
// lists
// dropdowns
// dropdown_properties
// order_sort
// name_sort
// reduce
// filter_keys
/* PHP FUNCTIONS */
class bb_meta extends bb_validate {

    function layouts($con, $key_type = false, $sort = true) {

        $arr_layouts_json = $this->get_json($con, "bb_layout_names");
        $arr_layouts = $this->filter_keys($arr_layouts_json, array(), true, $key_type);
        if ($key_type === false && $sort) {
            uasort($arr_layouts, array($this, 'order_sort'));
        }
        return $arr_layouts;
    }

    function columns($con, $row_type, $key_type = false, $sort = true) {

        $arr_columns_json = $this->get_json($con, "bb_column_names");
        $arr_columns = $this->init($arr_columns_json[$row_type], array());
        $arr_columns = $this->filter_keys($arr_columns, array(), true, $key_type);
        if ($key_type === false && $sort) {
            uasort($arr_columns, array($this, 'order_sort'));
        }
        return $arr_columns;
    }

    function columns_alternative($con, $row_type, $definition, $key_type = false, $sort = true) {
        // get core column info
        $arr_columns_json = $this->get_json($con, "bb_column_names");
        // get core and alternative columns
        $arr_columns_core = $this->filter_keys($this->init($arr_columns_json[$row_type], array()), array(), false);
        $arr_columns_alt = $this->filter_keys($this->init($arr_columns_json[$row_type]['alternative'][$definition], array()), array(), false);
        $arr_properties = $this->filter_keys($arr_columns_json[$row_type], array_keys($this->init($arr_columns_json['properties'], array())), true, true);
        //gte field defs
        $arr_field_keys = array_keys($this->init($arr_columns_json['fields'], array()));
        array_unshift($arr_field_keys, 'name');
        // loop to keep order
        $arr_columns = array();
        if (!empty($arr_columns_alt)) {
            foreach ($arr_columns_alt as $key => $value) {
                foreach ($arr_field_keys as $field) {
                    if (isset($arr_columns_alt[$key][$field])) {
                        $arr_columns[$key][$field] = $arr_columns_alt[$key][$field];
                    }
                    else {
                        $arr_columns[$key][$field] = $arr_columns_core[$key][$field];
                    }
                }
            }
            if (!empty($arr_properties)) {
                $arr_columns = $arr_columns + $arr_properties;
            }
        }
        $arr_columns = $this->filter_keys($arr_columns, array(), true, $key_type);
        if ($key_type === false && $sort) {
            uasort($arr_columns, array($this, 'order_sort'));
        }
        return $arr_columns;
    }

    function columns_properties($con, $row_type) {

        $arr_columns_json = $this->get_json($con, "bb_column_names");
        $arr_props_keys = array_keys($arr_columns_json['properties']);
        return $this->filter_keys($this->init($arr_columns_json[$row_type], array()), $arr_props_keys, true, true);
    }

    function lists($con, $row_type, $key_type = false, $sort = true) {

        $arr_lists_json = $this->get_json($con, "bb_create_lists");
        $arr_lists = $this->init($arr_lists_json[$row_type], array());
        $arr_lists = $this->filter_keys($arr_lists, array(), true, $key_type);
        if ($key_type === false && $sort) {
            uasort($arr_lists, array($this, 'name_sort'));
        }
        return $arr_lists;
    }

    function dropdowns($con, $row_type, $key_type = false) {

        $arr_dropdowns_json = $this->get_json($con, "bb_dropdowns");
        $arr_dropdowns = $this->init($arr_dropdowns_json[$row_type], array());
        return $this->filter_keys($arr_dropdowns, array(), true, $key_type);
    }

    function dropdown_properties($con, $row_type, $col_type) {

        $arr_dropdowns_json = $this->get_json($con, "bb_dropdowns");
        $arr_props_keys = array_keys($arr_dropdowns_json['properties']);
        $arr_reduced = $this->init($arr_dropdowns_json[$row_type][$col_type], array());
        return $this->filter_keys($arr_reduced, $arr_props_keys, true, true);
    }

    function joins($con, $row_type_1 = NULL, $row_type_2 = NULL) {

        $arr_layouts_json = $this->get_json($con, "bb_layout_names");
        $arr_joins = $this->init($this->reduce($arr_layouts_json, 'joins'), array());
        $arr_row_types = array($row_type_1, $row_type_2);

        //check if join exists or valid, boolean return
        if (($row_type_1 > 0) && ($row_type_2 > 0)) {
            foreach ($arr_joins as $value) {
                if ($row_type_1 <> $row_type_2) {
                    if (in_array($value['join1'], $arr_row_types) && in_array($value['join2'], $arr_row_types)) {
                        return true;
                    }
                }
                elseif ($row_type_1 == $row_type_2) {
                    if (($value['join1'] == $row_type_1) == ($value['join2'] == $row_type_2)) {
                        return true;
                    }
                }
            }
            return false;
        }
        //return corresponding joins as an array of integers
        elseif (($row_type_1 > 0) && is_null($row_type_2)) {
            $arr_return = array();
            foreach ($arr_joins as $value) {
                if (in_array($row_type_1, $value)) {
                    $arr_return[] = $value['join1'];
                    $arr_return[] = $value['join2'];
                }
            }
            $arr_return = array_diff($arr_return, array($row_type_1));
            return array_unique($arr_return);
        }
        //Or return the full join subarray from arr_layouts
        else {
            return $arr_joins;
        }
    }

    function order_sort($a, $b) {
        // would be quicker to do this when defining
        if ($a['order'] == $b['order']) {
            return 0;
        }
        return ($a['order'] < $b['order']) ? -1 : 1;
    }

    function name_sort($a, $b) {
        // would be quicker to do this when defining
        if ($a['name'] == $b['name']) {
            return 0;
        }
        return ($a['name'] < $b['name']) ? -1 : 1;
    }

    function reduce($arr, $keys = NULL, $key_type = NULL) {
        // icould be strings or ints
        if (!is_array($keys)) {
            $keys = array($keys);
        }
        foreach ($keys as $value) {
            if (isset($arr[$value])) {
                $arr = $arr[$value];
            }
            else {
                $arr = array();
                break;
            }
        }
        // default NULL will not reduce to string or integer keys
        if (is_bool($key_type)) {
            // false is int, true is string, can also do nothing with NULL
            $arr = $this->filter_keys($arr, array(), true, $key_type);
        }
        return $arr;
    }

    function filter_keys($arr, $filter = array(), $keep_mode = true, $key_type = false)
    // function to return array with only integer keys by default
    // so far mostly loop on integer keys, so $key_type is not null by default
    // default behavior is different than functions lookup or reduce
    // will return empty array if $arr is not set for any reason
    {

        if (!empty($arr)) {
            if (!is_null($key_type)) {
                // true string, false integer
                $callback = $key_type ? 'is_string' : 'is_integer';
                $keys = array_filter(array_keys($arr), $callback);
                $arr = array_intersect_key($arr, array_flip($keys));
            }
            // empty filter to skip
            if (!empty($filter)) {
                if ($keep_mode) // keep the keys in filter
                {
                    $arr = array_intersect_key($arr, array_flip($filter));
                }
                else
                // discard the keys in filter
                {
                    $arr = array_diff_key($arr, array_flip($filter));
                }
            }
            return $arr;
        }
        else {
            return array();
        }
    }
} // end class

?>