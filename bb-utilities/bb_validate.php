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

/* PHP FUNCTIONS */
/* class bb_validate() */
// validate_text
// validate_numeric
// validate_date
// validate_email
// validate_money
// validate_yesno
class bb_validate extends bb_database {

    function validate_text(&$field, $error = false) {
        // validate text -- do nothing
        return false;
    }

    function validate_numeric(&$field, $error = false) {
        // validate numeric
        if (!is_numeric($field)) {
            $return_value = $error ? __t("Error: Must be numeric.", "bb_main") : true;
        }
        else {
            $return_value = false;
        }
        return $return_value;
    }

    function validate_date(&$field, $error = false) {
        // validate date
        date_default_timezone_set(USER_TIMEZONE);
        $new_value = strtotime($field);
        // blank is valid, handled at top if must be populated
        if ($new_value == false) {
            $return_value = $error ? __t("Error: Value is not a valid date.", "bb_main") : true;
        }
        else {
            // reformat value
            $field = date("Y-m-d", $new_value);
            $return_value = false;
        }
        return $return_value;
    }

    function validate_email(&$field, $error = false) {
        // validate email
        if (filter_var($field, FILTER_VALIDATE_EMAIL) == false) {
            $return_value = $error ? __t("Error: Value is not a valid email.", "bb_main") : true;
        }
        else {
            $return_value = false;
        }
        return $return_value;
    }

    function validate_money(&$field, $error = false) {
        // validate money
        if (!is_numeric($field)) {
            $return_value = $error ? __t("Error: Value must be monetary.", "bb_main") : true;
        }
        else {
            $field = round($field, 2);
            $field = ( string )number_format($field, 2, '.', '');
            $return_value = false;
        }
        return $return_value;
    }

    function validate_yesno(&$field, $error = false) {
        // validate yes or no
        if (!in_array(strtolower($field), array("yes", "no"))) {
            $return_value = $error ? __t("Error: Value must be Yes or No.", "bb_main") : true;
        }
        else {
            $field = ucfirst($field);
            $return_value = false;
        }
        // do not use else
        return $return_value;
    }
} // end class

?>