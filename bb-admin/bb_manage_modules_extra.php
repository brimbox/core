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
/* PHP FUNCTIONS */
// get_modules (main function)
// build_module_array
// error waterfall
// module has header and is good if false,
// has no header if true,
// message is populated wath error message if header found and error
// There are many error reports in these modules, the appear in waterfall order
class bb_manage_modules {

    function __construct() {

        $this->arr_required = array("@module_path", "@module_name", "@friendly_name", "@interface", "@module_type", "@module_version");
    }

    // This checks the php files calling function build_module_array
    function get_modules($con, &$arr_module) {
        // all possible header values
        $path = $arr_module['@module_path'];

        // check for @module_name
        $message = $this->build_module_array($arr_module);
        if (is_string($message)) {
            // exit on duplicate values in module defination
            return $message;
        }
        // has valid @module_name, continue waterfall
        if (isset($arr_module['@module_name'])) {
            // check for errors in declaration
            $message = $this->error_waterfall($arr_module);
            if (is_string($message)) {
                return $message;
            }
            // format the details
            $message = $this->format_details($arr_module);
            if (is_string($message)) {
                return $message;
            }
            // message will be false if all's well
            return false;
        }
        else {
            return true;
        }
    }

    // this function parses the module header creating $arr_module
    // $arr_module contains all the module variables
    protected function build_module_array(&$arr_module) {
        // path is php path
        $abspath = $_SESSION['abspath'];
        $path = $arr_module['@module_path'];
        $fullpath = $abspath . "/" . $path;

        // this checks all php files for syntax errors
        // there is a check syntax function but would have to bring in $main object
        $fileesc = escapeshellarg($fullpath);
        $output = shell_exec("php-cli -l " . $fileesc);
        // no syntax errors
        if (!preg_match("/^No syntax errors/", trim($output))) {
            // will exit here on good check
            return __t("Error: Syntax error in file %s.", $module, array($path));
        }

        // strip all the /* */ comments out of file
        $file = file_get_contents($fullpath);
        // tricky regex, accounts for new lines
        $count_comments = preg_match_all('/\/\*(.*?)\*\//sm', $file, $comments);

        // no comments found
        if ($count_comments == 0) {
            // no comments, no module definition
            return true;
        }
        else {
            // look for module name or included
            foreach ($comments[1] as $comment) {
                // if if finds @module_name returns with no dups return first first comment block
                // else returns couldn't find module error;
                // this checks first line in comment for valid module name
                // check for installed file name, will check further later
                $pattern = "/^\s*?(@module_name)\s*?=[^\\/?*:;{}\\\\]+;/i";
                if (preg_match($pattern, trim($comment))) {
                    // only entered on first comment with module name
                    // explode on semicolon
                    $arr_pairs = explode(";", trim($comment));
                    // explode produces empty value at end
                    $arr_pairs = array_filter($arr_pairs);
                    foreach ($arr_pairs as $value) {
                        // loop through and explode each pair
                        $arr_pair = explode("=", trim($value), 2);
                        // trim and put into key/value pairs
                        if (isset($arr_module[trim(strtolower($arr_pair[0])) ])) {
                            return __t("Error: Duplicate module variable in %s. Module declarations must be unique.", $module, array($path));
                        }
                        else {
                            $arr_module[trim(strtolower($arr_pair[0])) ] = trim($arr_pair[1]);
                        }
                    } // end foreach
                    // found valid module header
                    return false;
                } // end if
                
            } // end foreach
            
        }
    }
    // end function
    /* ERROR WATERFALL */
    // arr_module passed as a value
    // this function checks $arr_module for errors
    protected function error_waterfall(&$arr_module) {

        global $array_header;
        global $array_interface;

        // check for valid module name
        $path = $arr_module['@module_path'];
        $pattern_name = "/[^A-Za-z0-9_]/";
        $pattern_slug = "/[^A-Za-z0-9-]/";
        // proper version numbers, dots and hypens
        $pattern_version_update = "/[^0-9-\.]/";
        // alphanetic
        $pattern_version_ignore = "/[^A-Za-x_]/";
        if (preg_match($pattern_name, $arr_module['@module_name'])) {
            // any other files should contain the principle php file name + _extra, or _css or _javascript etc
            return __t("Error: Module name in %s must contain only alphanumeric characters, dashes, or underscores.", $module, array($path));
        }

        // check that file name matches module name
        $pattern = "/" . $arr_module['@module_name'] . ".php$/";
        if (!preg_match($pattern, $arr_module['@module_path'])) {
            // module name must be the same as principle php file name wihtout the .php extension
            // any other files should contain the principle php file name + extra, or _css or javascript etc
            return __t("Error: Module name %s does not match file name. Module name must be the file name (without the extension).", $module, array($path));
        }

        // check for the required variables
        $arr_keys = array_keys($arr_module);
        $arr_intersect = array_intersect($this->arr_required, $arr_keys);
        if (count($arr_intersect) != count($this->arr_required)) {
            return __t("Error: Required module variable missing in %s. Certain module variables are required in the module definition.", $module, array($path));
        }

        // interface must be properly named
        if (preg_match($pattern_name, $arr_module['@interface'])) {
            return __t("Error: Interface name in %s must contain only alphanumeric characters or underscores.", $module, array($path));
        }

        // version must be properly named
        if (preg_match($pattern_version_update, $arr_module['@module_version']) && preg_match($pattern_version_ignore, $arr_module['@module_version'])) {
            return __t("Error: Version in %s must numeric with dots and dashes, or alphabetic with underscores.", $module, array($path));
        }

        // checks json declarations, will ignore all declarations not starting with @json
        foreach ($arr_module as $key => $value) {
            $pattern_1 = "/^@json-.*/";
            if (preg_match($pattern_1, $key)) {
                $pattern_2 = "/^" . $arr_module['@module_name'] . ".*/";
                if (!preg_match($pattern_2, substr($key, 6))) {
                    return __t("Error: Invalid JSON lookup specification in %s. Lookup value must start with module name.", $module, array($path));
                }
                // check for valid JSON
                if (!json_decode($value) && ($value != "[]")) {
                    return __t("Error: Invalid JSON markup in %s module header. Please properly form your JSON in module declaration.", $module, array($path));
                }
            }
        }

        // check if global interface array is set, only then can you check userroles and module types
        if (in_array($arr_module['@interface'], array_keys($array_header))) {
            // check the module types
            // tricky to validate ints, deal with value as a string
            if (filter_var(( string )$arr_module['@module_type'], FILTER_VALIDATE_INT)) {
                $arr_keys = array_keys($array_interface[$arr_module['@interface']]);
                $arr_keys = array_unique(array_merge($arr_keys, array(0, -1, -2, -3)));
                if (!in_array($arr_module['@module_type'], $arr_keys)) {
                    print_r($arr_keys);
                    return __t("Error: Invalid module type supplied in %s module header. Module type must correspond to module type keys global array.", $module, array($path));
                }
            }
            else {
                $arr_values = $this->arr_header[$arr_module['@interface']]['module_types'];
                unset($arr_values[0], $arr_values[-1], $arr_values[-2], $arr_values[-3]);
                $arr_values = array_map('strtolower', $arr_values + array(0 => "hidden", -1 => "global", -2 => "function", -3 => "header"));
                if (!in_array(strtolower($arr_module['@module_type']), $arr_values)) {
                    return __t("Error: Invalid module type supplied in %s module header. Module type must correspond to module type keys global array.", $module, array($path));
                }
                // module type set to numeric value for insert/update
                $arr_module['@module_type'] = array_search(strtolower($arr_module['@module_type']), $arr_values);
            }
        }
        // made it
        return false;
    }

    protected function format_details(&$arr_module) {

        /* put extras, description, company etc into xml for details functionality */
        $arr_details = array();
        $pattern_1 = "/^@json-.*/";
        foreach ($arr_module as $key => $value) {
            // not required or json
            if (!in_array($key, $this->arr_required) && (!preg_match($pattern_1, $key))) {
                $str_details = substr($key, 1);
                $arr_details[$str_details] = $value;
                unset($arr_module[$key]);
            }
        }
        $arr_module['@module_details'] = json_encode($arr_details);
        // Not implemented
        $arr_module['@module_files'] = json_encode(array());
        return false;
    }
}
/* END ERROR WATERFALL */
?>