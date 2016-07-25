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
if (!function_exists('bb_data_table_row_validate')):

    function bb_data_table_row_validate(&$arr_state) {
        // session or globals
        global $con, $main, $submit;

        /*
         * IF $row_type = $row_join THEN
         * Use $row_work = $row_join on Edit
        */
        /*
         * ELSE $row_join is the child
         * So again use $row_work = $row_join -- on Insert
        */

        // return error message
        $error = true;

        $arr_layouts = $main->layouts($con);
        $default_row_type = $main->get_default_layout($arr_layouts);

        $row_type = $main->state('row_type', $arr_state, 0);
        $row_join = $main->state('row_join', $arr_state, 0);
        $post_key = $main->state('post_key', $arr_state, 0);

        //row_join could be zero
        $row_work = $row_join ? $row_join : $default_row_type;

        $arr_columns = $main->columns($con, $row_work);
        $arr_dropdowns = $main->dropdowns($con, $row_work);

        // count of arr_errors will indicate validation
        $arr_errors = array(); // empty array
        // reduce columns if desired
        /* WARNING -- filtering required columns can result in blank required values on INSERT */

        foreach ($arr_columns as $key => $value) {
            $col = $main->pad("c", $key);
            $field = $arr_state[$col];

            // start validation
            $type = $value['type']; // validation type
            $required_flag = $value['required'] == 1 ? true : false; // required boolean
            // all validated
            $return_required = $return_validate = false;
            // required field
            if ($required_flag) {
                // false = not required, true = required
                $return_required = $main->validate_required($field, $error);
                if (!is_bool($return_required)) {
                    // key is col_type
                    $arr_errors[$key] = $return_required;
                }
            }
            // validate, field has data, trimmed already, will skip if blank
            if (!$main->blank($field)) {
                if (isset($arr_dropdowns[$key])) {
                    $arr_dropdown = $arr_dropdowns[$key];
                    $return_validate = $main->validate_dropdown($field, $arr_dropdown, $error);
                    if (!is_bool($return_validate)) {
                        $arr_errors[$key] = $return_validate;
                    }
                }
                else {
                    // value is passed a reference and may change in function if formatted
                    $return_validate = $main->validate_logic($con, $type, $field, $error);
                    if (!is_bool($return_validate)) {
                        // key is col_type
                        $arr_errors[$key] = $return_validate;
                    }
                }
            }
            $main->set($col, $arr_state, $field);
        }
        $main->set('arr_errors', $arr_state, $arr_errors);
    }

endif;
?>