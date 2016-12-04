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
/* class bb_main() */
// return_stats
// return_header
// return_rows
// get_default_layout
// get_default_column
// get_default_list
// layout_dropdown
// column_dropdown
// list_dropdown
// pad
// rpad
// build_name
// convert_date
// rconvert_date
// include_file
// get_directory_tree
// array_flatten
// empty_directory
// copy_directory
// replace_root
// check_syntax
// array_iunique
// custom_trim_string
// purge_chars
// echo_messages
// has_error_messages
// check_permission
// validate_password
// logout_link
// archive_link
// database_stats
// replicate_link
// userrole_switch
// infolinks
// build_indexes
// cleanup_database_data
// cleanup_database_layouts
// cleanup_database_columns
// log
// output_links
// check_child
// drill_links
// page_selector
// validate_logic
// validate_required
// validate_dropdown
// document
// make_html_id
//REGULAR PHP FUNCTIONS AFTER CLASS
// __() Format variable and translate
class bb_main extends bb_reports {

    // this quickly returns the query header stats including count and current time...
    function get_return_stats($result, &$str) {
        // count of rows is held in query, must have a "count(*) OVER () as cnt" column in query
        // in the standard modules $cntrows['cnt'] or $cntrows[0] will work
        // cannot use pg_num_rows because that returns the count according to OFFSET, as defined by $return_rows
        $cntrows = pg_fetch_array($result);
        if ($cntrows['cnt'] > 0) {
            date_default_timezone_set(USER_TIMEZONE);
            $str = "<div class = \"spaced left\">Rows: " . $cntrows['cnt'] . " Date: " . date('Y-m-d h:i A', time()) . "</div>";
        }
        // reset back to zero
        pg_result_seek($result, 0);

        return $cntrows;
    }

    function return_stats($result) {
        $cntrows = $this->get_return_stats($result, $str);
        echo $str;
        return $cntrows;
    }

    // this function returns a record header with a view_details link for each record returned
    function get_return_header($row, $target, $params = array()) {
        // params for customization
        $link = isset($params['link']) ? $params['link'] : 1;
        $mark = isset($params['mark']) ? $params['mark'] : 1;

        $str = "<div class = \"italic\">";
        $row_type = $row['row_type'];
        $row_type_left = $row['row_type_left'];
        // do not return link, (ie cascade)
        $str.= "<div class=\"inlineblock rightmargin\">";
        $str.= "<span class=\"bold colored\">" . chr($row_type + 64) . $row['id'] . "</span>";
        // archive or secure > 0
        if ($mark) {
            if ($row['archive'] > 0) {
                $str.= "<span class=\"error bold\">" . str_repeat('*', $row['archive']) . "</span>";
            }
            if ($row['secure'] > 0) {
                $str.= "<span class=\"error bold\">" . str_repeat('+', $row['secure']) . "</span>";
            }
        }
        if (!$this->blank($row['hdr']) && ($link == 1)) {
            // calls javascript in bb_link
            $str.= " / <button class = \"link italic\" onclick=\"bb_links.standard(0, " . ( int )$row['key1'] . "," . ( int )$row['row_type_left'] . ", '" . $target . "'); return false;\">";
            $str.= __($row['hdr']) . "</button>";
        }
        elseif (!$this->blank($row['hdr']) && ($link == - 1)) {
            // non linked row
            $str.= " / <span class = \"colored italic\">";
            $str.= __($row['hdr']) . "</span>";
        }
        $str.= "</div>";
        // else link 0 no output
        $str.= "<div class=\"inlineblock rightmargin\">Created: " . $this->convert_date($row['create_date'], "Y-m-d h:i A") . "</div>";
        $str.= "<div class=\"inlineblock rightmargin\">Modified: " . $this->convert_date($row['modify_date'], "Y-m-d h:i A") . "</div>";
        $str.= "</div>";

        return $str;
    }

    function return_header($row, $target, $params = array()) {
        echo $this->get_return_header($row, $target, $params);
    }
    // function
    // this outputs a record of data, returning the total number of rows, which is found in the cnt column
    // $row1 is the row number, and $row2 is the catch to see when the row changes
    // $col2 is the actual name of the columns from the xml, $row[$col2] is $row['c03']
    // $child is the visable name of the column the user name of the column
    function get_return_rows($row, $arr_columns, $params = array(), &$str) {
        // params for customization
        $check = isset($params['check']) ? $params['check'] : 0;

        // you could always feed this function only non-secured columns
        $row2 = 1; // to catch row number change
        $str = ""; //string for output
        $output = ""; // string with row data in it
        $secure = false; // must be check = true and secure = 1 to secure column
        $pop = false; // to catch empty rows, pop = true for non-empty rows
        foreach ($arr_columns as $key => $value) {
            if (is_integer($key)) {
                // integer keys reserved for columns
                $row1 = ( int )$value['row']; // current row number
                $col2 = $this->pad("c", $key);
                // always skipped first time
                if ($row2 != $row1) {
                    if ($pop) {
                        $str.= "<div class = \"left\">" . $output . "</div><div class = \"rowclear\"></div>";
                    }
                    $output = ""; // reset row data
                    $pop = false; // start row again with pop = false
                    
                }
                // not secure is $value['secure'] < $check OR $check = 0 (default no check)
                $secure = ($check && ($value['secure'] >= $check)) ? true : false;
                // check secure == 0
                if (!$secure) {
                    if (!empty($row[$col2])) {
                        $pop = true; // field has data, so row will too
                        
                    }
                    // prepare row, if row has data also echo the empty cell spots
                    $output.= "<div class = \"overflow " . $value['length'] . "\">" . __($row[$col2]) . "</div>";
                }
                $row2 = $row1;
            }
        }
        // echo the last row if populated
        if ($pop) {
            $str.= "<div class = \"left\">" . $output . "</div><div class = \"clear\"></div>";
        }

        return $row['cnt'];
    }

    function return_rows($row, $arr_columns, $params = array()) {
        $cntrows = $this->get_return_rows($row, $arr_columns, $params, $str);
        echo $str;
        return $cntrows;
    }

    function get_default_layout($arr_layouts, $check = 0) {
        // layouts are in order, will return first array if $check is false
        // if check is true, $available array of secure values will be considered
        // $available is an array of available securities to allow
        // loop through $arr_layouts
        foreach ($arr_layouts as $key => $value) {
            // not secure is $value['secure'] < $check OR $check = 0 (default no check)
            $secure = ($check && ($value['secure'] >= $check)) ? true : false;
            if (!$secure) {
                // check is true
                return $key;
            }
        }
        return 1;
    }

    function get_default_column($arr_columns, $check = 0) {
        // columns are in order, will return first array if $check is false
        // if check is true, $available array of secure values will be considered
        // $available is an array of available securities to allow
        // loop through $arr_columns
        foreach ($arr_columns as $key => $value) {
            // not secure is $value['secure'] < $check OR $check = 0 (default no check)
            $secure = ($check && ($value['secure'] >= $check)) ? true : false;
            if (!$secure) {
                // check is true
                return $key;
            }
        }
        return 1;
    }

    function get_default_list($arr_lists, $archive = 1) {
        // default only ones that are not archived
        // columns are in order, will return first array if $check is false
        // if check is true, $available array of secure values will be considered
        // $available is an array of available securities to allow
        // loop through $arr_list
        foreach ($arr_lists as $key => $value) {
            // not secure is $value['secure'] < $check OR $check = 0 (default no check)
            $default = ($archive && ($value['archive'] >= $archive)) ? true : false;
            if (!$default) {
                // check is true
                return $key;
            }
        }
        return 1;
    }

    // this returns a standard header combo for selecting record type
    // for this function the javascript function reload_on_layout() is uniquely tailored to the calling module
    function get_layout_dropdown($arr_layouts, $name, $row_type, $params = array()) {

        $params = array('name' => $name) + $params;

        $check = isset($params['check']) ? $params['check'] : 0;
        $empty = isset($params['empty']) ? $params['empty'] : false;
        $all = isset($params['all']) ? $params['all'] : false;
        unset($params['check'], $params['empty'], $params['all']);

        $attributes = $this->attributes($params);

        $str = "<select " . $attributes . ">";
        if ($empty) {
            $str.= "<option value=\"-1\" " . (-1 == $row_type ? "selected" : "") . "></option>";
        }
        if ($all) {
            $str.= "<option value=\"0\" " . (0 == $row_type ? "selected" : "") . ">All&nbsp;</option>";
        }
        foreach ($arr_layouts as $key => $value) {
            // not secure is $value['secure'] < $check OR $check = 0 (default no check)
            $secure = ($check && ($value['secure'] >= $check)) ? true : false;
            if (!$secure) {
                $str.= "<option value=\"" . $key . "\" " . ($key == $row_type ? "selected" : "") . ">" . __($value['plural']) . "&nbsp;</option>";
            }
        }
        $str.= "</select>";

        return $str;
    }

    function layout_dropdown($arr_layouts, $name, $row_type, $params = array()) {
        echo $this->get_layout_dropdown($arr_layouts, $name, $row_type, $params);
    }

    function get_column_dropdown($arr_column, $name, $col_type, $params = array()) {

        $params = array('name' => $name) + $params;

        $check = isset($params['check']) ? $params['check'] : false;
        $empty = isset($params['empty']) ? $params['empty'] : false;
        $all = isset($params['all']) ? $params['all'] : false;
        unset($params['check'], $params['empty'], $params['all']);

        $attributes = $this->attributes($params);

        // Security there should be no way to get column with secured row_type
        $str = "<select " . $attributes . ">";
        // build field options for column names
        if ($empty) {
            $str.= "<option value=\"-1\" " . (-1 == $row_type ? "selected" : "") . "></option>";
        }
        if ($all) {
            $str.= "<option value=\"0\" " . (0 == $col_type ? "selected" : "") . ">All&nbsp;</option>";
        }
        foreach ($arr_column as $key => $value) {
            // not secure is $value['secure'] < $check OR $check = 0 (default no check)
            $secure = ($check && ($value['secure'] >= $check)) ? true : false;
            if (!$secure) {
                $str.= "<option value=\"" . $key . "\" " . ($key == $col_type ? "selected" : "") . ">" . __($value['name']) . "&nbsp;</option>";
            }
        }
        $str.= "</select>";

        return $str;
    }

    function column_dropdown($arr_column, $name, $col_type, $params = array()) {

        echo $this->get_column_dropdown($arr_column, $name, $col_type, $params);
    }

    function get_list_dropdown($arr_lists, $name, $list_number, $params = array()) {
        // Security there should be no way to get secured column or row
        $params = array('name' => $name) + $params;

        $check = isset($params['check']) ? $params['check'] : 1; // default checks
        $empty = isset($params['empty']) ? $params['empty'] : false;
        unset($params['check'], $params['empty']);

        $attributes = $this->attributes($params);

        $str.= "<select " . $attributes . ">";
        // list combo
        if ($empty) {
            $str.= "<option value=\"-1\" " . (-1 == $list_number ? "selected" : "") . "></option>";
        }
        foreach ($arr_lists as $key => $value) {
            // either 1 or 0 for archive
            $archive = ($check && ($value['archive'] >= $check)) ? true : false;
            if (!$archive) {
                $archive_flag = ($value['archive']) ? str_repeat('*', $value['archive']) : "";
                $str.= "<option value=\"" . $key . "\"" . ($key == $list_number ? " selected " : "") . ">" . __($value['name']) . $archive_flag . "&nbsp;</option>";
            }
        }
        $str.= "</select>";

        return $str;
    }

    function list_dropdown($arr_lists, $name, $list_number, $params = array()) {

        return $this->get_list_dropdown($arr_lists, $name, $list_number, $params);
    }

    // pad a number to a column name
    function pad($char, $number, $padlen = 2) {

        return $char . str_pad($number, $padlen, "0", STR_PAD_LEFT);
    }

    // get a number from a column name
    function rpad($padded) {

        return ( int )substr($padded, 1);
    }

    // Function to turn firstname (fname), middle initial(minit) and lastname (into string)
    function build_name($row) {

        $arr_name = array();
        $arr_row = array("fname", "minit", "lname");
        foreach ($arr_row as $value) {
            if (trim($row[$value]) != "") {
                array_push($arr_name, trim($row[$value]));
            }
        }
        $str = implode(" ", $arr_name);
        return $str;
    }

    // function to convert dates to proper time zone and format
    // convert from database time to user time
    function convert_date($date, $format = "Y-m-d") {

        $date = new DateTime($date, new DateTimeZone(DB_TIMEZONE));
        $date->setTimezone(new DateTimeZone(USER_TIMEZONE));
        return $date->format($format);
    }

    // convert from user time to database time
    function rconvert_date($date, $format = "Y-m-d") {

        $date = new DateTime($date, new DateTimeZone(USER_TIMEZONE));
        $date->setTimezone(new DateTimeZone(DB_TIMEZONE));
        return $date->format($format);
    }

    function get_include_file($filepaths, $type) {
        // assumes index file root
        $filepaths = is_string($filepaths) ? array(0 => array('path' => $filepaths, 'version' => BRIMBOX_PROGRAM)) : $filepaths;
        foreach ($filepaths as $filepath) {
            if (!strcasecmp($type, "css")) {
                $str.= "<link rel=StyleSheet href=\"" . $filepath['path'] . "?v=" . $filepath['version'] . "\" type=\"text/css\" media=screen>";
            }
            elseif (!strcasecmp($type, "js")) {
                $str.= "<script type=\"text/javascript\" src=\"" . $filepath['path'] . "?v=" . $filepath['version'] . "\"></script>";
            }
        }

        return $str;
    }

    function include_file($filepaths, $type) {

        echo $this->get_include_file($filepaths, $type);
    }

    // function to get all paths in a directory
    // directory recursion function
    function get_directory_tree($directory) {

        $filter = array(".", "..");
        $dirs = array_diff(scandir($directory), $filter);
        $dir_array = array();
        foreach ($dirs as $d) if (is_dir($directory . $d)) {
            $d = $d . "/";
            $dir_array[$d] = $this->get_directory_tree($directory . $d);
        }
        else {
            $dir_array[$d] = $directory . $d;
        }
        return $this->array_flatten($dir_array);
    }

    // flattens the array returned in $main->get_directory_tree
    function array_flatten($a) {

        foreach ($a as $k => $v) {
            if (!empty($v)) {
                $a[$k] = ( array )$v;
            }
        }
        if (!empty($a)) {
            return call_user_func_array('array_merge', $a);
        }
        else {
            return array();
        }
    }

    function empty_directory($directory, $delete = false) {
        // works with or without trailing slash
        $directory = rtrim($directory, '/');
        if (is_dir($directory)) {
            $objects = array_diff(scandir($directory), array(".", ".."));
            foreach ($objects as $object) {
                if (is_dir($directory . '/' . $object)) {
                    // not empty recurse
                    $this->empty_directory($directory . '/' . $object, true);
                }
                else {
                    // remove file
                    @unlink($directory . '/' . $object);
                }
            }
        }
        if ($delete) {
            @rmdir($directory);
        }
    }

    function copy_directory($from_directory, $to_directory) {
        // works with or without trailing slash
        $from_directory = rtrim($from_directory, '/');
        $to_directory = rtrim($to_directory, '/');
        $objects = array_diff(scandir($from_directory), array(".", ".."));
        foreach ($objects as $object) {
            if (is_dir($from_directory . '/' . $object)) {
                @mkdir($to_directory . '/' . $object);
                // recurse
                $this->copy_directory($from_directory . '/' . $object, $to_directory . '/' . $object);
            }
            else {
                // copy file
                @copy($from_directory . '/' . $object, $to_directory . '/' . $object);
            }
        }
    }

    function replace_root($dir, $search, $replace) {
        // works with or without trailing slash
        $search = rtrim($search, '/');
        $replace = rtrim($replace, '/');

        return $replace . '/' . substr($dir, strlen($search . '/'));
    }

    function check_syntax($filepath) {
        // return true is bad, false in good - false is no syntax errors
        // parameters set up for controller
        if (file_exists($filepath)) {
            $fileesc = escapeshellarg($filepath);
            $output = shell_exec("php-cli -l " . $fileesc);
            if (preg_match("/^No syntax errors/", trim($output))) {
                // will exit here on good check
                return false;
            }
            return "Error: Syntax error in file " . $filepath . ".";
        }
        else {
            return "Error: File " . $filepath . " missing.";
        }
    }

    // array_unique not case sensitive for testing
    function array_iunique($array) {

        return array_unique(array_map('strtolower', $array));
    }

    // function to strip tabs and new lines from string
    function custom_trim_string($str, $length, $eol = true, $quotes = false) {
        /* DEPRACATED DO NOT USE */
        if ($eol) {
            // changes a bunch of control chars to single spaces
            $pattern = "/[\\t\\0\\x0B\\x0C\\r\\n]+/";
            $str = preg_replace($pattern, " ", $str);
            // purge new line with nothing, default purge
            
        }
        else {
            // changes a bunch of control chars to single spaces except for new lines
            $pattern = "/[\\t\\0\\x0B\\x0C\\r]+/";
            $str = trim(preg_replace($pattern, " ", $str)); // trim this one three times
            $pattern = "/ {0,}(\\n{1}) {0,}/";
            $str = preg_replace($pattern, "\n", $str);
        }
        if ($quotes) {
            // purge double quotes
            $str = str_replace('"', "", $str);
        }
        // trim and truncate
        $str = substr(trim($str), 0, $length);
        // trim again because truncate could leave ending space, then try to encode
        $str = utf8_encode(trim($str));
        return $str;
    }

    function purge_chars($str, $eol = true, $quotes = false) {

        // replace chars with a space, eol is false for note fields
        $pattern = "\t\x0B\x0C\r";
        if ($eol) $pattern = $pattern . "\n";
        $pattern = "/[" . $pattern . "]+/";
        $pattern = $this->filter("bb_main_purge_chars_space", $pattern, $eol, $quotes);
        if ($pattern !== "") //shut off with empty string
        $str = preg_replace($pattern, " ", $str);

        // remove chars, quotes are purged in meta data like column names
        if ($quotes) $pattern = "/[\"]+/";
        else $pattern = "";
        $pattern = $this->filter("bb_main_purge_chars_nospace", $pattern, $eol, $quotes);
        if ($pattern !== "") //shut off with empty string
        $str = preg_replace($pattern, "", $str);

        // PHP trim hooked in by default
        $str = $this->filter("bb_main_purge_chars_format", $str);

        return $str;
    }

    // $input can be either array or string
    function echo_messages($messages) {
        // could be string or array
        if (!$this->blank($messages)) {
            if (is_string($messages)) {
                $messages = array($messages);
            }
            if (is_array($messages)) {
                foreach ($messages as $message) {
                    if (preg_match("/^Error:|^Warning:|^Caution:/i", $message)) {
                        $class = "error";
                    }
                    else {
                        $class = "message";
                    }
                    echo "<p class=\"" . $class . "\">" . $message . "</p>";
                }
            }
        }
    }

    function has_error_messages($messages) {
        // could be string or array
        if (is_string($messages)) {
            $messages = array($messages);
        }
        if (is_array($messages)) {
            $arr = preg_grep("/^Error:/i", $messages);
            if (empty($arr)) {
                return false;
            }
            return true;
        }
    }

    function check_permission($userroles) {

        /* IMPORTANT FUNCTION SHOULD BE CALLED AT TOP OF EVERY MODULE */
        // dies on everything except good permission
        // should be invoked at the top of every module
        // waterfall function for single user and admin mode
        // this check that session is set
        // $usertypes can be int, array of int, or null if optional is string of userroles
        // $optional can be interface or string of userroles
        if (isset($_SESSION['username'])) {
            $arr_userroles = is_array($userroles) ? $userroles : array($userroles);
            if (!in_array($_SESSION['userrole'], $arr_userroles)) {
                echo "Insufficient Permission.";
                session_destroy();
                die();
            }
            // single user takes precedence over admin only
            // this for when administrators lock the db down
            if (!strcasecmp(ADMIN_ONLY, "YES") && SINGLE_USER_ONLY == '') {
                $arr_userroles = explode(",", $_SESSION['userroles']);
                if (!in_array("5_bb_brimbox", $arr_userroles)) {
                    echo "Program switched to Admin Only mode.";
                    session_destroy();
                    die();
                }
            }
            elseif (SINGLE_USER_ONLY != '') {
                if (strcasecmp($_SESSION['username'], SINGLE_USER_ONLY)) {
                    echo "Program switched to Single User mode.";
                    session_destroy();
                    die();
                }
            }
            // end up here valid permission
            
        }
        else {
            // empty die, no 404 in case there is html output before check permission
            session_destroy();
            die();
        }
    }

    function validate_password($con, $passwd, $userroles) {
        // waterfall
        // this will also check that session is set
        $userrole = $_SESSION['userrole'];
        $username = $_SESSION['username'];
        if (!is_array($userroles)) {
            $userroles = array($userroles);
        }
        // waterfall
        if (in_array($userrole, $userroles)) {
            $query = "SELECT * FROM users_table WHERE '" . $userrole . "' = ANY (userroles) AND UPPER(username) = UPPER('" . pg_escape_string($username) . "');";
            $result = $this->query($con, $query);
            if (pg_num_rows($result) == 1) {
                $row = pg_fetch_array($result);
                if (hash('sha512', $passwd . $row['salt']) == $row['hash']) {
                    return true;
                }
            }
        }
        return false;
    }

    function get_logout_link($class = "bold link underline", $label = "Logout") {

        $params = array("class" => $class, "label" => $label, "onclick" => "bb_logout_selector('0_bb_brimbox')");
        return $this->get_script_button("logout", $params);
    }

    function logout_link($class = "bold link underline", $label = "Logout") {
        echo $this->get_logout_link($class, $label);
    }

    function get_archive_link($class_button = "link underline bold", $class_span = "bold") {
        // careful not to use -1 on on pages with archive link
        global $button;
        global $module;
        // on postback toggle
        if ($this->button(-1)) {
            if ($_SESSION['archive'] == 0) {
                $_SESSION['archive'] = 1;
            }
            else {
                $_SESSION['archive'] = 0;
            }
        }

        $label = ($_SESSION['archive'] == 0) ? "On" : "Off";

        $str = "<span class=\"" . $class_span . "\">Archive mode is: ";
        $params = array("class" => $class_button, "number" => - 1, "target" => $module, "passthis" => true, "label" => $label);
        $str.= $this->get_button("archive", $params);
        $str.= "</span>";

        return $str;
    }

    function archive_link($class_button = "link underline bold", $class_span = "bold") {
        echo $this->get_archive_link($class_button, $class_span);
    }

    function get_database_stats($class_div = "bold", $class_span = "colored") {

        $str = "<div class=\"" . $class_div . "\">Hello <span class=\"" . $class_span . "\">" . $_SESSION['name'] . "</span></div>";
        $str.= "<div class=\"" . $class_div . "\">You are logged in as: <span class=\"" . $class_span . "\">" . $_SESSION['username'] . "</span></div>";
        $str.= "<div class=\"" . $class_div . "\">You are using database: <span class=\"" . $class_span . "\">" . DB_NAME . "</span></div>";
        $str.= "<div class=\"" . $class_div . "\">This database is known as: <span class=\"" . $class_span . "\">" . DB_FRIENDLY_NAME . "</span></div>";
        $str.= "<div class=\"" . $class_div . "\">This database email address is: <span class=\"" . $class_span . "\">" . EMAIL_ADDRESS . "</span></div>";

        return $str;
    }

    function database_stats($class_div = "bold", $class_span = "colored") {
        echo $this->get_database_stats($class_div, $class_span);
    }

    function get_replicate_link($class_div = "bold", $class_link = "colored") {

        $str = "<div class=\"" . $class_div . "\">To open in new window: ";
        $str.= "<a class=\"" . $class_link . "\" href=\"" . dirname($_SERVER['PHP_SELF']) . "\" target=\"_blank\">Click here</a>";
        $str.= "</div>";

        return $str;
    }

    function replicate_link($class_div = "bold", $class_link = "colored") {
        echo $this->get_replicate_link($class_div, $class_link);
    }

    function get_userrole_switch($class_span = "bold", $class_button = "link underline") {

        global $array_userroles;
        global $userroles;
        global $userrole;

        $arr_userroles = explode(",", $userroles);
        $cnt = count($arr_userroles);
        if ($cnt > 1) {
            $str = "<span class=\"" . $class_span . "\">Current userrole is: </span>";
            $i = 1;
            foreach ($arr_userroles as $value) {
                // careful with the globals
                $bold = ($value == $userrole) ? " bold" : "";
                $params = array("class" => $class_button . $bold, "label" => $array_userroles[$value]['name'], "onclick" => "bb_userrole_switch('" . $value . "', '" . $array_userroles[$value]['home'] . "'); return false;");
                $str.= $this->get_script_button("role" . $value, $params);
                $separator = ($i != $cnt) ? ", " : "";
                $str.= $separator;
                $i++;
            }
        }
        return $str;
    }

    function userrole_switch($class_span = "bold", $class_button = "link underline") {
        echo $this->get_userrole_switch($class_span, $class_button);
    }

    function get_infolinks() {
        $str = "<div class=\"floatleft\">";
        $str.= $this->get_database_stats();
        $str.= $this->get_archive_link();
        $str.= $this->get_replicate_link();
        $str.= $this->get_userrole_switch();
        $str.= "</div>";
        $str.= $this->get_clear();

        return $str;
    }

    function infolinks() {
        echo $this->get_infolinks();
    }

    function build_indexes($con, $row_type = 0) {
        // reduce xml_layout to include only 1 row_type for column update, all for rebuild indexes
        global $array_guest_index;

        $arr_union_query = array();
        $arr_layouts = $this->get_json($con, "bb_layout_names");
        $arr_layouts_reduced = $this->filter_keys($arr_layouts);
        $arr_columns = $this->get_json($con, "bb_column_names");

        $arr_row_type = array();
        if ($row_type == 0) {
            // all
            foreach ($arr_layouts_reduced as $key => $value) {
                array_push($arr_row_type, $key);
            }
        }
        else {
            array_push($arr_row_type, $row_type);
        }

        $arr_ts_vector_fts = array();
        $arr_ts_vector_ftg = array();
        foreach ($arr_row_type as $row_type) {
            $arr_column = $this->filter_keys($arr_columns[$row_type]);
            // loop through searchable columns
            foreach ($arr_column as $key => $value) {
                $col = $this->pad("c", $key);
                $search_flag = ($value['search'] == 1) ? true : false;
                // guest flag
                if (empty($array_guest_index)) {
                    $guest_flag = (($value['search'] == 1) && ($value['secure'] == 0)) ? true : false;
                }
                else {
                    $guest_flag = (($value['search'] == 1) && in_array(( int )$value['secure'], $array_guest_index)) ? true : false;
                }
                // build fts SQL code
                if ($search_flag) {
                    array_push($arr_ts_vector_fts, $col . " || ' ' || regexp_replace(" . $col . ", E'(\\\\W)+', ' ', 'g')");
                }
                if ($guest_flag) {
                    array_push($arr_ts_vector_ftg, $col . " || ' ' || regexp_replace(" . $col . ", E'(\\\\W)+', ' ', 'g')");
                }
            } // $xml_column
            // implode arrays with guest column full text query definitions
            $str_ts_vector_fts = !empty($arr_ts_vector_fts) ? implode(" || ' ' || ", $arr_ts_vector_fts) : "''";
            $str_ts_vector_ftg = !empty($arr_ts_vector_ftg) ? implode(" || ' ' || ", $arr_ts_vector_ftg) : "''";

            // build the union query array
            $union_query = "SELECT id, to_tsvector(" . $str_ts_vector_fts . ") as fts, to_tsvector(" . $str_ts_vector_ftg . ") as ftg FROM data_table WHERE row_type = " . $row_type;
            array_push($arr_union_query, $union_query);
        }

        // implode the union query
        $str_union_query = implode(" UNION ALL ", $arr_union_query);

        // update joined with union on id
        $query = "UPDATE data_table SET fts = T1.fts, ftg = T1.ftg " . "FROM (" . $str_union_query . ") T1 " . "WHERE data_table.id = T1.id";
        // echo $query . "<br><br>";
        $this->query($con, $query);
    }

    function cleanup_database_data($con) {

        for ($col_type = 1;$col_type <= 50;$col_type++) {
            $col = "c" . str_pad($col_type, 2, "0", STR_PAD_LEFT);
            if ($i <= 48) {
                $query = "UPDATE data_table SET " . $col . " =  trim(both FROM regexp_replace(" . $col . ", E'[\\t\\x0B\\x0C\\r\\n]+', ' ', 'g' )) WHERE " . $col . " <> '';";
            }
            else {
                $query = "UPDATE data_table SET " . $col . " =  trim(both FROM regexp_replace(col, E' {0,}\\n{1} {0,}', E'\n', 'g' )) " . "FROM (SELECT id, regexp_replace(" . $col . ", E'[\\t\\x0B\\x0C\\r]+', ' ', 'g' ) as col FROM data_table WHERE " . $col . " <> '') T1 WHERE data_table.id = T1.id;";
            }
            // POSIX regex, no null because db text fields cannot have nulls
            // change tabs, form feeds, new lines, returns and vertical tabs to space and trim
            $query = $this->filter("bb_main_cleanup_database_data", $query, $col_type);
            $this->query($con, $query);
        }
    }

    function cleanup_database_layouts($con) {

        $arr_layouts = $this->get_json($con, "bb_layout_names");
        $arr_layouts_reduced = $this->filter_keys($arr_layouts);
        $arr_columns = $this->get_json($con, "bb_column_names");
        $arr_dropdowns = $this->get_json($con, "bb_dropdowns");
        for ($i = 1;$i <= 26;$i++) {
            if (!isset($arr_layouts_reduced[$i])) {
                // clean up rows
                unset($arr_columns[$i]);
                unset($arr_dropdowns[$i]);
                $query = "DELETE FROM data_table WHERE row_type IN (" . $i . ");";
                $this->query($con, $query);
            }
        }
        $this->update_json($con, $arr_dropdowns, "bb_dropdowns");
        $this->update_json($con, $arr_columns, "bb_column_names");
    }

    function cleanup_database_columns($con) {

        $arr_columns = $this->get_json($con, "bb_column_names");
        $arr_dropdowns = $this->get_json($con, "bb_dropdowns");
        for ($i = 1;$i <= 26;$i++) {
            $arr_column = isset($arr_columns[$i]) ? $arr_columns[$i] : array();
            for ($j = 1;$j <= 50;$j++) {
                if (!isset($arr_column[$j])) {
                    $col = $this->pad("c", $j);
                    $set_clause = $col . " = ''";
                    $query = "UPDATE data_table SET " . $set_clause . " WHERE row_type = " . $i . " AND " . $col . " <> '';";
                    $this->query($con, $query);
                    if (isset($arr_dropdowns[$i][$j])) {
                        unset($arr_dropdowns[$i][$j]);
                    }
                }
            }
        }
        $this->update_json($con, $arr_dropdowns, "bb_dropdowns");
    }

    function log($con, $message, $username = NULL, $email = NULL) {

        if (is_null($username)) $username = $_SESSION['username'];
        if (is_null($email)) $email = $_SESSION['email'];
        $ip = $_SERVER['REMOTE_ADDR'];
        $arr_log = array($username, $email, $ip, $message);
        $query = "INSERT INTO log_table (username, email, ip_address, action) VALUES ($1,$2,$3,$4)";
        $this->query_params($con, $query, $arr_log);
    }

    function output_links($row, $arr_layouts, $userrole) {
        // for standard interface
        global $array_links;

        // not sorted by default
        $arr_links = $array_links[$userrole];
        ksort($arr_links);
        foreach ($arr_links as $arr) {
            array_unshift($arr['params'], $arr_layouts);
            array_unshift($arr['params'], $row);
            call_user_func_array($arr['func'], $arr['params']);
        }
    }

    function check_child($row_type, $layouts) {
        // checks for child records or record
        foreach ($layouts as $key => $value) {
            if ($row_type == $value['parent']) {
                return true;
                break;
            }
        }
        return false;
    }

    function drill_links($post_key, $row_type, $arr_layouts, $module, $text) {
        // call function add drill links in class bb_link
        call_user_func_array(array($this, 'drill'), array($post_key, $row_type, $arr_layouts, $module, $text));
    }

    function page_selector($element, $offset, $count_rows, $return_rows, $pagination) {

        $half = floor($pagination / 2);
        $max_return = floor(($count_rows - 1) / $return_rows) + 1;
        // set top and bottom
        $bottom = $offset - $half;
        $top = $offset + $half;
        // adjust if $bottom less than zero
        while ($bottom < 1) {
            $bottom++;
            if ($top < $max_return) {
                $top++;
            }
        }
        // adjust if top greater then max
        while ($top > $max_return) {
            $top--;
            if ($bottom > 1) {
                $bottom--;
            }
        }

        echo "<br>";
        echo "<div class=\"center\">";
        if ($top > $bottom) {
            // skip only one page
            // echo page selector out...
            echo "<button class=\"link none\" onclick=\"bb_page_selector('" . $element . "','1')\">First</button>&nbsp;&nbsp;&nbsp;";
            if ($offset > 1) {
                echo "<button class=\"link none\" onclick=\"bb_page_selector('" . $element . "','" . ($offset - 1) . "')\">Prev</button>&nbsp;&nbsp;&nbsp;";
            }
            for ($i = $bottom;$i <= $top;$i++) {
                if ($i == $offset) {
                    $class = "class=\"link bold underline\"";
                }
                else {
                    $class = "class=\"link none\"";
                }
                echo "<button " . $class . " onclick=\"bb_page_selector('" . $element . "','" . $i . "')\">";
                echo $i . "</button>&nbsp;&nbsp;&nbsp;";
            }
            if ($offset < $max_return) {
                echo "<button class=\"link none\" onclick=\"bb_page_selector('" . $element . "','" . ($offset + 1) . "')\">Next</button>&nbsp;&nbsp;&nbsp;";
            }
            echo "<button class=\"link none\" onclick=\"bb_page_selector('" . $element . "','" . $max_return . "')\">Last</button>";
        }
        echo "</div>";
        echo "<br>";
    }

    function validate_logic($con, $type, &$field, $error = false) {
        // validates a data type set in "Set Column Names"
        // returns false on good, true or error string if bad
        global $array_validation;

        // parse function
        // if not set call default text
        if (isset($array_validation[$type]['func']) && is_callable($array_validation[$type]['func'])) {
            $return_value = call_user_func_array($array_validation[$type]['func'], array(&$field, $error));
        }
        else {
            $return_value = call_user_func_array(array($this, 'validate_text'), array(&$field, $error));
        }
        return $return_value;
    }

    function validate_required(&$field, $error = false) {
        // Checks to see that a field has some data
        // returns false on good, true or error string if bad
        if (is_string($field) && !$this->blank(trim($field))) {
            return false;
        }
        elseif (is_array($field) && !empty($field)) {
            return false;
        }
        else {
            $return_value = $error ? "Error: This value is required." : true;
            return $return_value;
        }
    }

    function validate_dropdown(&$field, $arr_dropdown, $error = false) {
        // validates dropdowns, primarily used in bulk loads (Upload Data)
        // returns false on good, true or error string if bad
        $multiselect = $this->init($arr_dropdown['multiselect'], 0);
        $arr_dropdown = $this->filter_keys($arr_dropdown);
        $arr_values = (!is_array($field)) ? array($field) : $field;
        // field built from dropdown for return values
        $arr_formatted = array();
        foreach ($arr_values as $value) {
            $key = array_search(strtolower($value), array_map('strtolower', $arr_dropdown));
            if ($key === false) {
                $return_value = $error ? "Error: Value not found in dropdown list." : true;
                return $return_value;
            }
            else {
                array_push($arr_formatted, $arr_dropdown[$key]);
            }
        }
        // field formatted with actual dropdown values
        $field = (!$multiselect) ? implode($arr_formatted) : $arr_formatted;
        // false is no error
        return false;
    }

    function document($object, $text = "", $class = "link spaced") {

        if ($this->blank($text)) {
            $text = $object;
        }
        echo "<button class=\"" . $class . "\" onclick=\"bb_submit_object('bb-links/bb_object_document_link.php', '" . $object . "'); return false;\">" . $text . "</button>";
    }

    function make_html_id($row_type, $col_type = 0) {

        if ($col_type) {
            return chr($row_type + 96) . $row_type . "_" . $this->pad("c", $col_type);
        }
        else {
            return chr($row_type + 96) . $row_type;
        }
    }
} // end class

?>
<?php
/* Regular Functions */

//standard convert to utf output
//this will eventially lead to translation
function __($var) {
    if (is_string($var)) {
        $var = htmlentities($var, ENT_COMPAT | ENT_HTML401, "UTF-8");
    }
    elseif (is_array($var)) {
        foreach ($var as & $value) {
            $value = htmlentities($value, ENT_COMPAT | ENT_HTML401, "UTF-8");
        }
    }
    return $var;
}

?>