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
?>
<?php
if (!function_exists('bb_data_table_render_form')):

    function bb_data_table_render_form(&$arr_state) {
        // session or global vars, superglobals
        global $con, $main, $submit;

        /*
         * IF $row_type = $row_join THEN
         * Use $row_work = $row_join on Edit
        */
        /*
         * ELSE $row_join is the child
         * So again use $row_work = $row_join -- on Insert
        */

        // standard values
        $arr_relate = array(41, 42, 43, 44, 45, 46);
        $arr_file = array(47);
        $arr_reserved = array(48);
        $arr_notes = array(49, 50);
        $textarea_rows = 4; // minimum
        $delimiter = $main->get_constant('BB_MULTISELECT_DELIMITER', ",");
        $maxinput = $main->get_constant('BB_STANDARD_LENGTH', 255);
        $maxnote = $main->get_constant('BB_NOTE_LENGTH', 65536);

        // layouts must have one layout set
        $arr_layouts = $main->layouts($con);
        $default_row_type = $main->get_default_layout($arr_layouts);

        // bring in everything from state
        $row_type = $main->state('row_type', $arr_state, 0);
        $row_join = $main->state('row_join', $arr_state, 0);
        $post_key = $main->state('post_key', $arr_state, 0);

        //row_join could be zero
        $row_work = $row_join ? $row_join : $default_row_type;

        $arr_columns = $main->columns($con, $row_work);
        /* FILTER */
        $arr_columns = $main->filter("bb_input_render_form_columns", $arr_columns);

        $arr_dropdowns = $main->dropdowns($con, $row_work);
        /* FILTER */
        $arr_dropdowns = $main->filter("bb_input_render_form_dropdowns", $arr_dropdowns);

        // get the error and regular messages, populated form redirect
        $arr_messages = $main->state('arr_messages', $arr_state, array());
        $arr_errors = $main->state('arr_errors', $arr_state, array());

        echo "<div class=\"spaced\" id=\"input_message\">";
        $main->echo_messages($arr_messages);
        echo "</div>";
        /* END MESSAGES */

        /* POPULATE INPUT FIELDS */
        // check if empty, could be either empty or children not populated
        // this is dependent on admin module "Set Column Names"
        echo "<div id=\"bb_input_fields\">"; // id wrapper
        foreach ($arr_columns as $key => $value) {
            // key is col_type, $value is array
            $col = $main->pad("c", $key);

            $input = (isset($arr_state[$col])) ? __($arr_state[$col]) : "";
            $error = (isset($arr_errors[$key])) ? $arr_errors[$key] : "";
            // display 0 normal, 1 readonly, 2 hidden
            $display = isset($arr_columns[$key]['display']) ? $arr_columns[$key]['display'] : 0;

            switch ($display) {

                case 0:
                    // normal display for different field types
                    if (isset($arr_dropdowns[$key])) {
                        $arr_dropdown = $arr_dropdowns[$key];
                        $multiselect = $main->init($arr_dropdown['multiselect'], 0);
                        $dropdown = $main->filter_keys($arr_dropdown);
                        $input = is_array($input) ? $input : array($input); // convert to array
                        $field_output = "<div class=\"clear\">";
                        $field_output.= "<label class = \"spaced padded right pad_left overflow medium pad_textbox shaded\" for=\"" . $col . "\">" . __($value['name']) . ": </label>";
                        if ($multiselect) {
                            $field_output.= "<select id=\"" . $field_id . "\" class = \"spaced pad_textbox\" name = \"" . $col . "[]\" size=\"5\" multiple onFocus=\"bb_remove_message(); return false;\">";
                            foreach ($dropdown as $item) {
                                $selected = is_int(array_search(strtolower($item), array_map('strtolower', $input))) ? "selected" : "";
                                $field_output.= "<option value=\"" . $item . "\" " . $selected . ">" . $item . "&nbsp;</option>";
                            }
                            $field_output.= "</select>";
                        }
                        else {
                            $field_output.= "<select id=\"" . $field_id . "\" class = \"spaced pad_textbox\" name = \"" . $col . "\" onFocus=\"bb_remove_message(); return false;\">";
                            foreach ($dropdown as $item) {
                                $selected = is_int(array_search(strtolower($item), array_map('strtolower', $input))) ? "selected" : "";
                                $field_output.= "<option value=\"" . $item . "\" " . $selected . ">" . $item . "&nbsp;</option>";
                            }
                            $field_output.= "</select>";
                        }
                        $field_output.= "<label class=\"error\">" . $error . "</label>";
                        $field_output.= "</div>";
                    }
                    elseif (in_array($key, $arr_relate)) {
                        // possible related record type, could be straight text
                        $field_output = "<div class = \"clear\">";
                        $field_output.= "<label class = \"spaced padded right pad_left overflow medium pad_textbox shaded\" for=\"" . $col . "\">" . __($value['name']) . ": </label>";
                        $field_output.= "<input id=\"" . $field_id . "\" class = \"spaced textbox\" maxlength=\"" . $maxinput . "\" name=\"" . $col . "\" type=\"text\" value = \"" . $input . "\" onFocus=\"bb_remove_message(); return false;\" />";
                        $field_output.= "<label class=\"error\">" . $error . "</label>";
                        $field_output.= "</div>";
                    }
                    elseif (in_array($key, $arr_file)) {
                        // file type
                        $lo = isset($arr_state['lo']) ? $arr_state['lo'] : "";
                        $field_output = "<div class = \"clear\">";
                        $field_output.= "<label class = \"spaced padded left overflow medium pad_textbox shaded\" for=\"" . $col . "\">" . __($value['name']) . ": </label>";
                        $field_output.= "<input id=\"" . $field_id . "\" class = \"spaced padded textbox noborder\" name=\"lo\" type=\"text\" value = \"" . __($lo) . "\" readonly/>";
                        $field_output.= "<label class=\"error\">" . $error . "</label>";
                        $field_output.= "</div>";
                        $field_output.= "<div class = \"clear\">";
                        $field_output.= "<input id=\"" . $field_id . "\" class=\"spaced textbox\" type=\"file\" name=\"" . $col . "\"/>";
                        if (!$value['required']) {
                            $field_output.= "<span class = \"spaced border rounded padded shaded\">";
                            $field_output.= "<label class=\"padded\">Remove: </label>";
                            $field_output.= "<input type=\"checkbox\" name=\"remove\" class=\"middle holderup\" />";
                            $field_output.= "</span>";
                        }
                        $field_output.= "</div>";
                    }
                    elseif (in_array($key, $arr_notes)) {
                        // note type, will be textarea
                        $field_output = "<div class=\"clear\">";
                        $field_output.= "<label class = \"spaced padded left overflow medium pad_textbox shaded\" for=\"" . $col . "\">" . __($value['name']) . ": </label><label class=\"error spaced padded floatleft left overflow\">" . $error . "</label>";
                        $field_output.= "<div class=\"clear\"></div>";
                        $field_output.= "<textarea id=\"" . $field_id . "\" class=\"spaced notearea pad_notearea\" maxlength=\"" . $maxnote . "\" name=\"" . $col . "\" onFocus=\"bb_remove_message(); return false;\">" . $input . "</textarea>";
                        $field_output.= "</div>";
                    }
                    else {
                        // standard input/textbox
                        $field_output = "<div class=\"clear\">";
                        $field_output.= "<label class = \"spaced padded right pad_left overflow medium pad_textbox shaded\" for=\"" . $col . "\">" . __($value['name']) . ": </label>";
                        $field_output.= "<input id=\"" . $field_id . "\" class = \"spaced textbox\" maxlength=\"" . $maxinput . "\" name=\"" . $col . "\" type=\"text\" value = \"" . $input . "\" onFocus=\"bb_remove_message(); return false;\" />";
                        $field_output.= "<label class=\"error spaced\">" . $error . "</label>";
                        $field_output.= "</div>";
                    }

                break;

                case 1:

                    // readonly field types
                    if (isset($arr_dropdowns[$key])) {
                        $arr_dropdown = $arr_dropdowns[$key];
                        $multiselect = $main->init($arr_dropdown['multiselect'], 0);
                        $dropdown = $main->filter_keys($arr_dropdown);
                        $input = is_array($input) ? $input : array($input); // convert to array
                        $field_output = "<div class=\"clear\">";
                        $field_output.= "<label class = \"spaced padded floatleft right pad_left overflow medium pad_textbox shaded\" for=\"" . $col . "\">" . __($value['name']) . ": </label>";
                        if ($multiselect) {
                            foreach ($input as $value) {
                                $field_output.= "<input type=\"hidden\" name=\"" . $col . "[]\" value=\"" . __($value) . "\" />";
                            }
                            $field_output.= "<textarea class=\"spaced floatleft\" readonly>" . implode("\r\n", $input) . "</textarea>";
                            $field_output.= "<div class=\"error spaced floatleft\">" . $error . "</div></div>";
                        }
                        else {
                            $field_output.= "<select id=\"" . $field_id . "\" class = \"spaced\" name = \"" . $col . "\" onFocus=\"bb_remove_message(); return false;\">";
                            foreach ($input as $value) {
                                $field_output.= "<option value=\"" . __($value) . "\" " . $selected . ">" . $value . "&nbsp;</option>";
                            }
                            $field_output.= "</select>";
                            $field_output.= "<label class=\"error\">" . $error . "</label>";
                            $field_output.= "</div>";
                        }
                    }
                    elseif (in_array($key, $arr_relate)) {
                        // possible related record type, could be straight text
                        $field_output = "<div class = \"clear\">";
                        $field_output.= "<label class = \"spaced padded right pad_left overflow medium pad_textbox shaded\" for=\"" . $col . "\">" . __($value['name']) . ": </label>";
                        $field_output.= "<input id=\"" . $field_id . "\" class = \"spaced textbox\" maxlength=\"" . $maxinput . "\" name=\"" . $col . "\" type=\"text\" value = \"" . __($input) . "\" onFocus=\"bb_remove_message(); return false;\" readonly/>";
                        $field_output.= "<label class=\"error\">" . $error . "</label>";
                        $field_output.= "</div>";
                    }
                    elseif (in_array($key, $arr_file)) {
                        // file type
                        $lo = isset($arr_state['lo']) ? $arr_state['lo'] : "";
                        $field_output = "<div class = \"clear\">";
                        $field_output.= "<label class = \"spaced padded left overflow medium pad_textbox shaded\" for=\"" . $col . "\">" . __($value['name']) . ": </label>";
                        $field_output.= "<input id=\"" . $field_id . "_lo\" class = \"spaced padded textbox noborder\" name=\"lo\" type=\"text\" value = \"" . __($lo) . "\" readonly/>";
                        $field_output.= "<label class=\"error\">" . $error . "</label>";
                        $field_output.= "</div>";
                        $field_output.= "<div class = \"clear\">";
                        $field_output.= "<input id=\"" . $field_id . "\" class=\"spaced textbox\" type=\"file\" name=\"" . $col . "\"  disabled/>";
                        if (!$value['required']) {
                            $field_output.= "<span class = \"spaced border rounded padded shaded\">";
                            $field_output.= "<label class=\"padded\">Remove: </label>";
                            $field_output.= "<input type=\"checkbox\" name=\"remove\" class=\"middle holderup\" />";
                            $field_output.= "</span>";
                        }
                        $field_output.= "</div>";
                    }
                    elseif (in_array($key, $arr_notes)) {
                        // note type, will be textarea
                        $field_output = "<div class=\"clear\">";
                        $field_output.= "<label class = \"spaced padded left overflow medium pad_textbox shaded\" for=\"" . $col . "\">" . __($value['name']) . ": </label><label class=\"error spaced padded floatleft left overflow\">" . $error . "</label>";
                        $field_output.= "<div class=\"clear\"></div>";
                        $field_output.= "<textarea id=\"" . $field_id . "\" class=\"spaced notearea pad_notearea\" maxlength=\"" . $maxnote . "\" name=\"" . $col . "\" onFocus=\"bb_remove_message(); return false;\" readonly>" . $input . "</textarea>";
                        $field_output.= "</div>";
                    }
                    else {
                        // standard input/textbox
                        $field_output = "<div class=\"clear\">";
                        $field_output.= "<label class = \"spaced padded right pad_left overflow medium pad_textbox shaded\" for=\"" . $col . "\">" . __($value['name']) . ": </label>";
                        $field_output.= "<input id=\"" . $field_id . "\" class = \"spaced textbox\" maxlength=\"" . $maxinput . "\" name=\"" . $col . "\" type=\"text\" value = \"" . $input . "\" onFocus=\"bb_remove_message(); return false;\" readonly/>";
                        $field_output.= "<label class=\"error spaced\">" . $error . "</label>";
                        $field_output.= "</div>";
                    }

                break;

                case 2:
                    // hidden field types
                    // also set to readonly since data is in form
                    if (isset($arr_dropdowns[$key])) {
                        $arr_dropdown = $arr_dropdowns[$key];
                        $multiselect = $main->init($arr_dropdown['multiselect'], 0);
                        $dropdown = $main->filter_keys($arr_dropdown);
                        $input = is_array($input) ? $input : array($input); // convert to array
                        $field_output = "<div class=\"clear hidden\">";
                        $field_output.= "<label class = \"spaced padded floatleft right pad_left overflow medium pad_textbox shaded\" for=\"" . $col . "\">" . __($value['name']) . ": </label>";
                        if ($multiselect) {
                            foreach ($input as $value) {
                                $field_output.= "<input type=\"hidden\" name=\"" . $col . "[]\" value=\"" . $value . "\" />";
                            }
                            $field_output.= "<textarea class=\"spaced floatleft\" readonly>" . implode("\r\n", $input) . "</textarea>";
                            $field_output.= "<div class=\"error spaced floatleft\">" . $error . "</div></div>";
                        }
                        else {
                            $field_output.= "<select id=\"" . $field_id . "\" class = \"spaced\" name = \"" . $col . "\" onFocus=\"bb_remove_message(); return false;\">";
                            foreach ($input as $value) {
                                $field_output.= "<option value=\"" . $value . "\" " . $selected . ">" . $value . "&nbsp;</option>";
                            }
                            $field_output.= "</select>";
                            $field_output.= "<label class=\"error\">" . $error . "</label>";
                            $field_output.= "</div>";
                        }
                    }
                    elseif (in_array($key, $arr_relate)) {
                        // possible related record type, could be straight text
                        $field_output = "<div class = \"clear hidden\">";
                        $field_output.= "<label class = \"spaced padded right pad_left overflow medium pad_textbox shaded\" for=\"" . $col . "\">" . __($value['name']) . ": </label>";
                        $field_output.= "<input id=\"" . $field_id . "\" class = \"spaced textbox\" maxlength=\"" . $maxinput . "\" name=\"" . $col . "\" type=\"text\" value = \"" . $input . "\" onFocus=\"bb_remove_message(); return false;\" readonly/>";
                        $field_output.= "<label class=\"error\">" . $error . "</label>";
                        $field_output.= "</div>";
                    }
                    elseif (in_array($key, $arr_file)) {
                        // file type
                        $lo = isset($arr_state['lo']) ? $arr_state['lo'] : "";
                        $field_output = "<div class = \"clear hidden\">";
                        $field_output.= "<label class = \"spaced padded left overflow medium pad_textbox shaded\" for=\"" . $col . "\">" . __($value['name']) . ": </label>";
                        $field_output.= "<input id=\"" . $field_id . "_lo\" class = \"spaced padded textbox noborder\" name=\"lo\" type=\"text\" value = \"" . __($lo) . "\" readonly/>";
                        $field_output.= "<label class=\"error\">" . $error . "</label>";
                        $field_output.= "</div>";
                        $field_output.= "<div class = \"clear\">";
                        $field_output.= "<input id=\"" . $field_id . "\" class=\"spaced textbox hidden\" type=\"file\" name=\"" . $col . "\"  disabled/>";
                        if (!$value['required']) {
                            $field_output.= "<span class = \"spaced border rounded padded shaded\">";
                            $field_output.= "<label class=\"padded\">Remove: </label>";
                            $field_output.= "<input type=\"checkbox\" name=\"remove\" class=\"middle holderup\" />";
                            $field_output.= "</span>";
                        }
                        $field_output.= "</div>";
                    }
                    elseif (in_array($key, $arr_notes)) {
                        // note type, will be textarea
                        $field_output = "<div class=\"clear hidden\">";
                        $field_output.= "<label class = \"spaced padded left overflow medium pad_textbox shaded\" for=\"" . $col . "\">" . __($value['name']) . ": </label><label class=\"error spaced padded floatleft left overflow\">" . $error . "</label>";
                        $field_output.= "<div class=\"clear\"></div>";
                        $field_output.= "<textarea id=\"" . $field_id . "\" class=\"spaced notearea pad_notearea\" maxlength=\"" . $maxnote . "\" name=\"" . $col . "\" onFocus=\"bb_remove_message(); return false;\" readonly>" . $input . "</textarea>";
                        $field_output.= "</div>";
                    }
                    else {
                        // standard input/textbox
                        $field_output = "<div class=\"clear hidden\">";
                        $field_output.= "<label class = \"spaced padded right pad_left overflow medium pad_textbox shaded\" for=\"" . $col . "\">" . __($value['name']) . ": </label>";
                        $field_output.= "<input id=\"" . $field_id . "\" class = \"spaced textbox\" maxlength=\"" . $maxinput . "\" name=\"" . $col . "\" type=\"text\" value = \"" . $input . "\" onFocus=\"bb_remove_message(); return false;\" readonly/>";
                        $field_output.= "<label class=\"error spaced\">" . $error . "</label>";
                        $field_output.= "</div>";
                    }
                break;
            } // switch
            // filter to echo the field output
            $field_output = $main->filter('bb_input_field_output', $field_output, $input, $key, $value);
            echo $field_output;
        }

        echo "</div>";
        echo "<div class=\"clear\"></div>";
        /* END POPULATE INPUT FIELDS */

        // hidden vars, $row_join is contained in the layout dropdown
        echo "<input type=\"hidden\"  name=\"post_key\" value = \"" . $post_key . "\">";
        echo "<input type=\"hidden\"  name=\"row_type\" value = \"" . $row_type . "\">";
        /* END FORM */
    }

endif;
?>