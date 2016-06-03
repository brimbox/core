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

/* class bb_work() */
// button
// name
// blank
// init
// hot
// check
// changed
// full
// post
// state
// set
// process
// render
// load
// keeper
// retrieve
// update
// hot_state
// on_constant
// get_constant
/* POSTBACK ANBD STATE FUNCTIONS */
class bb_work extends bb_meta {

    function button($number, $check = "") {

        global $submit;
        global $module;
        global $button;
        // convert int to array
        if (is_int($number)) {
            $number = array($number);
        }
        // check where it was submitted from
        if (!$this->blank($check)) {
            return (($submit == $check) && in_array($button, $number)) ? true : false;
        }
        else {
            return (($submit == $module) && in_array($button, $number)) ? true : false;
        }
    }

    function name($name, $module) {
        // returns the name of variable with module prepended
        return $module . "_" . $name;
    }

    function blank(&$var) {
        // anything that is empty but not identical to the '0' string
        return empty($var) && $var !== '0';
    }

    function init(&$var, $default) {

        return (isset($var)) ? $var : $default;
    }

    function hot($var) {

        global $module;
        global $submit;

        if (($module != $var) && ($submit == $var)) return true;
        else return false;
    }

    function check($name, $module) {
        // psuedo post var
        global $POST;

        // checks to see if a $POST variable is set
        $temp = $this->name($name, $module);
        if (isset($POST[$temp])) {
            return true;
        }
        else {
            return false;
        }
    }

    function changed($name, $module, $arr_state, $default = "") {
        // psuedo post var
        global $POST;

        if ($this->check($name, $module)) {
            if ($this->post($name, $module, $default) != $this->state($name, $arr_state, $default)) {
                return true;
            }
        }
        return false;
    }

    function full($name, $module) {
        // psuedo post var
        global $POST;

        // to check if full, opposite of empty function, returns false if empty
        // post var must be set or you will get a notice
        $temp = $this->name($name, $module);
        if (!$this->blank(trim($POST[$temp]))) {
            return true;
        }
        else {
            return false;
        }
    }

    function post($name, $module, $default = "") {
        // psuedo post var
        global $POST;

        // gets the post value of a variable
        $temp = $this->name($name, $module);
        if (isset($POST[$temp])) {
            return $POST[$temp];
        }
        else {
            return $default;
        }
    }

    function state($name, $arr_state, $default = "") {
        // gets the state value of a variable
        $var = isset($arr_state[$name]) ? $arr_state[$name] : $default;
        return $var;
    }

    function set($name, &$arr_state, $value) {
        // sets the state value from a variable, $module var not needed
        $arr_state[$name] = $value;
        return $value;
    }

    function process($name, $module, &$arr_state, $default = "") {
        // psuedo post var
        global $POST;

        // fully processes $POST variable into state setting with initial value
        $var = isset($arr_state[$name]) ? $arr_state[$name] : $default;
        $temp = $this->name($name, $module);

        if (isset($POST[$temp])) {
            $var = $POST[$temp];
        }

        $arr_state[$name] = $var;
        return $var;
    }

    function render($con, $name, $module, &$arr_state, $type, &$check, $default = "") {
        // psuedo post var
        global $POST;
        global $array_validation;

        $var = isset($arr_state[$name]) ? $arr_state[$name] : $default;
        $temp = $this->name($name, $module);

        // if variable is set use variable
        if (isset($POST[$temp])) {
            $var = $POST[$temp];
        }

        // will format value if valid, otherwise leaves $var untouched
        // check becomes false on valid type, true opn error
        $check = call_user_func_array($array_validation[$type]['func'], array(&$var, false));
        $arr_state[$name] = $var;

        return $var;
    }

    function load($con, $module) {
        // gets and loads $arr_state from global $array_state
        // module doesn't need to be default module
        global $keeper;

        // nasty little implicit unconstrained JOIN
        // under the hood changes could occur
        // However I am designing for 8.4 so JSON is not available
        // Also hstore has a limit in 8.4
        $query = "SELECT T1.statedata[T2.id]  as jsondata FROM state_table T1, modules_table T2 " . "WHERE T1.id IN (" . $keeper . ") AND T2.module_name = '" . $module . "';";
        $result = pg_query($con, $query);
        $row = pg_fetch_array($result);
        $arr_state = json_decode($row['jsondata'], true);

        return $arr_state;
    }

    function keeper($con, $key = "") {

        global $module;
        global $keeper;

        // get module number
        $query = "SELECT postdata FROM state_table WHERE id = " . $keeper . ";";
        $result = $this->query($con, $query);
        $row = pg_fetch_array($result);

        return json_decode($row['postdata'], true);
    }

    function retrieve($con) {

        global $POST;
        global $array_hot_state;
        global $submit;

        $POST = $this->keeper($con);

        // check if module has hot state
        if (isset($array_hot_state[$submit])) $this->hot_state($con);

        return $POST;
    }

    function update($con, $module, $arr_state)
    // updates $array_state with $arr_state
    {

        global $keeper;
        $jsondata = json_encode($arr_state);

        // unconstrained JOIN
        $query = "UPDATE state_table T1 SET statedata[T2.id] = $1 FROM modules_table T2 " . "WHERE T1.id = " . $keeper . " AND T2.module_name = '" . $module . "';";
        $params = array($jsondata);
        $this->query_params($con, $query, $params);
    }

    function hot_state($con) {
        // hot state used to update state vars when tabs are switched without postback
        global $userrole;
        global $array_hot_state;
        global $submit;

        // check usertype set
        if (isset($array_hot_state[$submit][$userrole])) {
            $arr_work = $array_hot_state[$submit][$userrole];
            if (!empty($arr_work)) {
                $arr_state = $this->load($con, $submit);
                foreach ($arr_work as $value) {
                    if ($this->check($value, $submit)) {
                        $value = $this->process($value, $submit, $arr_state);
                    }
                }
                $this->update($con, $submit, $arr_state);
            }
        }
    }

    // checks and processes OFF/ON constants
    function on_constant($constant) {

        if (defined($constant)) {
            if (!strcasecmp(constant($constant), "ON")) {
                return true;
            }
            else {
                // certain things are undefined if layout exceed the natural alphabet
                return false;
            }
        }
        else {
            return false;
        }
    }

    // for numeric and string constants
    function get_constant($constant, $default = "") {
        // if type doesn't match return default
        if (defined($constant)) {
            if ($this->blank(constant($constant))) {
                return $default;
            }
            else {
                return constant($constant);
            }
        }
        else {
            // return default if not set
            return $default;
        }
    }
} // end class

?>