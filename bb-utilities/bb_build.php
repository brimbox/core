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
/* class bb_hooks() */
// filter
// hook
// add_action
// remove_action
// add_value
// remove_value
// locked
/* STANDARD INDEX MAIN INCLUDE */
// can be hacked in bb_constants if necessary
// will be overwritten
class bb_build {

    // will not return anything
    // create and pass global vars
    /* BRIMBOX'S HOOK */
    function hook($hookname, &$locals = array()) {

        global $array_hooks;
        global $abspath;

        // hook must be set
        if (isset($array_hooks[$hookname])) {
            //specific hook
            $arr_hooks = $array_hooks[$hookname];
            //priority
            usort($arr_hooks, array($this, 'priority_sort'));
            // hook loop
            foreach ($arr_hooks as $arr_hook) {
                // simple function with no parameters
                if (is_callable($arr_hook)) {
                    // used for simple hooks, can actually be an array
                    call_user_func($arr_hook);
                }
                elseif (is_array($arr_hook)) {
                    // process with parameters etc
                    $args_hook = array();
                    // get global variables
                    if (isset($arr_hook['vars'])) $arr_vars = is_string($arr_hook['vars']) ? array($arr_hook['vars']) : $arr_hook['vars'];
                    else $arr_vars = NULL;

                    // get local variables
                    if (isset($arr_hook['locals'])) $arr_locals = is_string($arr_hook['locals']) ? array($arr_hook['locals']) : $arr_hook['locals'];
                    else $arr_locals = NULL;

                    // get the callable function
                    if (isset($arr_hook['func'])) $arr_func = $arr_hook['func'];
                    else $arr_func = NULL;

                    // get the include file
                    if (isset($arr_hook['file'])) $str_file = $arr_hook['file'];
                    else $str_file = NULL;

                    // process global vars and values for passing
                    if (!empty($arr_vars)) {
                        // foreach will not iterate on empty array
                        foreach ($arr_vars as $var) {
                            // passed by reference
                            if (substr($var, 0, 1) == "&") {
                                $var = substr($var, 1);
                                if (isset($GLOBALS[$var])) $ {
                                    $var
                                } = $GLOBALS[$var];
                                else $ {
                                    $var
                                } = NULL;
                                $args_hook[] = & $ {
                                    $var
                                };
                            }
                            else {
                                // passed by value
                                if (isset($GLOBALS[$var])) $ {
                                    $var
                                } = $GLOBALS[$var];
                                else $ {
                                    $var
                                } = NULL;
                                $args_hook[] = $ {
                                    $var
                                };
                            }
                        }
                    }

                    // process local vars and values for passing
                    if (!empty($arr_locals)) {
                        foreach ($arr_locals as $var) {
                            // passed by reference
                            if (substr($var, 0, 1) == "&") {
                                $var = substr($var, 1);
                                if (isset($locals[$var])) $ {
                                    $var
                                } = $locals[$var];
                                else $ {
                                    $var
                                } = NULL;
                                $args_hook[] = & $ {
                                    $var
                                };
                            }
                            else {
                                // passed by value
                                if (isset($locals[$var])) $ {
                                    $var
                                } = $locals[$var];
                                else $ {
                                    $var
                                } = NULL;
                                $args_hook[] = $ {
                                    $var
                                };
                            }
                        }
                    }

                    // include file, can inlcude hooked function
                    if ($str_file) if (file_exists($abspath . $str_file)) include_once ($abspath . $str_file);

                    if (is_callable($arr_func)) {
                        // hook function call
                        $return = call_user_func_array($arr_func, $args_hook);

                        //indexed call_user_func_array array
                        $i = 0;
                        // global pass by value emulation, variables updated
                        if (!empty($arr_vars)) {
                            foreach ($arr_vars as $var) {
                                if (substr($var, 0, 1) == "&") {
                                    $var = substr($var, 1);
                                    $GLOBALS[$var] = $args_hook[$i];
                                }
                                $i++;
                            }
                            // update or create a global variable from the retrun value - careful
                            if (isset($arr_hook['global'])) {
                                $GLOBALS[$arr_hook['global']] = $return;
                            }
                        }
                        // local pass by value emulation, variables updated
                        if (!empty($arr_locals)) {
                            //locals only conatin pass by reference
                            foreach ($arr_locals as $var) {
                                if (substr($var, 0, 1) == "&") {
                                    $var = substr($var, 1);
                                    $locals[$var] = $args_hook[$i];
                                }
                                $i++;
                            }
                            // update or create a local variable from the retrun value - careful
                            if (isset($arr_hook['filter'])) {
                                $locals[$arr_hook['filter']] = $return;
                            }
                        }
                    } //hook function is callable
                    
                } //foreach hook array value
                
            } //end hook loop
            return $return;
        } // isset hook array
        else {
            //empty locals to avoid loop after hook when hook inside function
            $locals = array();
        }
    } //end function
    function priority_sort($a, $b) {
        // would be quicker to do this when defining
        if ($a['priority'] == $b['priority']) {
            return 0;
        }
        return ($a['priority'] < $b['priority']) ? -1 : 1;
    }

    /* FUNCTIONS TO ADD TO AND MANIPULATE TO $array_global */

    function unpack_global_array($name = NULL) {
        global $array_global;

        if (isset($array_global)) {
            if (is_null($name)) {
                foreach ($array_global as $key => $value) {
                    $GLOBALS['array_' . $key] = $value;
                }
            }
            else {
                $GLOBALS['array_' . $name] = $array_global[$name];
            }
        }
    }

    //callable items
    function add_action($name, $action, $value, $priority = 0) {

        if (is_array($name)) {
            global $name;

            $value['priority'] = $priority;
            $name[$action][] = $value;
        }

        elseif (is_string($name)) {
            global $array_global;

            $value['priority'] = $priority;
            $array_global[$name][$action][] = $value;
        }
    }

    function remove_action($name, $action, $callable, $priority = NULL) {

        global $array_global;

        foreach ($array_global[$name][$action] as $key => $value) {
            if (is_callable($value)) {
                if ($value == $callable) $arr_keys[] = $key;
            }
            elseif (is_array($value)) {
                if (in_array($callable, $value)) {
                    if (is_null($priority)) $arr_keys[] = $key;
                    else if ($priority == $value['priority']) $arr_keys[] = $key;
                }
            }
        }
        foreach ($arr_keys as $key) unset($array_global[$name][$action][$key]);

    }

    //array items with unique key
    function add_array($name, $identifier, $value, $key = NULL) {

        global $array_global;

        //best way no overwirte
        if (is_null($key)) {
            $array_global[$name][$identifier][] = $value;
        }
        else {
            $array_global[$name][$identifier][$key] = $value;
        }

    }

    function remove_array($name, $identifier, $key) {

        global $array_global;

        unset($array_global[$name][$identifier][$key]);

    }

    //items with value only
    function add_value($name, $value, $key = NULL) {

        if (is_array($name)) {
            global $name;

            //best way no overwrite
            if (is_null($key)) {
                $name[] = $value;
            }
            else {
                $name[$key] = $value;
            }
        }

        elseif (is_string($name)) {
            global $array_global;

            //best way no overwrite
            if (is_null($key)) {
                $array_global[$name][] = $value;
            }
            else {
                $array_global[$name][$key] = $value;
            }
        }
    }

    function remove_value($name, $key) {

        if (is_array($name)) {

            unset($name[$key]);
        }

        elseif (is_string($name)) {
            global $array_global;

            unset($array_global[$name][$key]);
        }
    }

    function locked($con, $username, $userrole) {

        /* CHECK IF USER LOCKED OR DELETED */
        // once $con is set check live whether user is locked or deleted
        // 0_bb_brimbox is only locked userrole, for active lock
        $query = "SELECT id FROM users_table " . "WHERE username = '" . pg_escape_string($username) . "' AND '" . pg_escape_string($userrole) . "' = ANY (userroles) AND NOT '0_bb_brimbox' = ANY (userroles);";
        $result = pg_query($con, $query);
        if (pg_num_rows($result) != 1) {
            session_destroy();
            $index_path = "Location: " . dirname($_SERVER['PHP_SELF']);
            header($index_path);
            die(); // important to stop script
            
        }
    }

    /* DEPRECATED */
    function filter() {
        // Standard Wordpress style Filter
        global $array_filters;
        global $abspath;

        $args = func_get_args();
        $filter = $args[1];

        if (isset($array_filters[$args[0]])) {
            $arr_filters = $array_filters[$args[0]];
            ksort($arr_filters);
            foreach ($arr_filters as $arr_filter) {
                // get the callable function
                if (isset($arr_filter['func'])) $arr_func = $arr_filter['func'];
                elseif (isset($arr_filter[0])) $arr_func = $arr_filter[0];
                else $arr_func = NULL;

                // get the include file
                if (isset($arr_filter['file'])) $str_file = $arr_filter['file'];
                elseif (isset($arr_filter[1])) $str_file = $arr_filter[1];
                else $str_file = NULL;

                if ($str_file) {
                    if (file_exists($abspath . $str_file))
                    // include file
                    include_once ($abspath . $str_file);
                }
                if (is_callable($arr_func)) {
                    $params = array_slice($args, 1, count($args) - 1);
                    if (isset($filter)) $params[0] = $filter;
                    $filter = call_user_func_array($arr_func, $params);
                }
            }
        }
        return $filter;
    }
} // end class

?>