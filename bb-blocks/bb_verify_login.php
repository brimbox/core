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
/* This is the login routine */
/* You can customize this by place a file called login.php in the root of the Brimbox instance */

if (isset($_POST['bb_submit'])) {
    // get connection
    $con_string = "host=" . DB_HOST . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASSWORD;
    $con = pg_connect($con_string);
    // no connection die
    if (!$con) die();

    // initialize
    $email = $password = $userrole = $message = "";

    // get form variables
    $username = substr($_POST['username'], 0, 255); // email and password must be < 255 by definition
    $password = substr($_POST['password'], 0, 255); // do not want to process big post
    $userrole = !empty($_POST['userrole']) ? substr($_POST['userrole'], 0, 255) : "";

    // default error message, information only provided with accurate credentials
    $message = "Login Failure: Bad Username and Password, Invalid IP, or Account Locked";

    if (filter_var($ip = $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
        // query users table
        $query = "SELECT username, email, hash, salt, attempts, array_to_string(userroles,',') as userroles, fname, minit, lname FROM users_table WHERE NOT ('0_bb_brimbox' = ANY (userroles)) AND ('" . pg_escape_string($ip) . "' <<= ANY (ips)) AND UPPER(username) = UPPER('" . pg_escape_string($username) . "') AND array_length(userroles,1) > 0 AND attempts <= 10;";

        // get result
        $result = pg_query($con, $query);

        $num_rows = pg_num_rows($result);

        // 1 row, definate database //known username
        if ($num_rows == 1) {
            $set_session = $set_attempts = false;
            $row = pg_fetch_array($result);
            $arr_userroles = explode(",", $row['userroles']);

            // go through single user and admin waterfall
            if (hash('sha512', $password . $row['salt']) == $row['hash']) // good password
            {
                // single user takes precedence
                if (SINGLE_USER_ONLY != "") // single user
                {
                    if (!strcasecmp(SINGLE_USER_ONLY, $row['email'])) {
                        $set_session = true;
                        $message = $log_message = "Login Success/Single User";
                    }
                    else {
                        $message = $log_message = "Program in Single User mode"; // only if failure
                        
                    }
                }
                elseif (!strcasecmp(ADMIN_ONLY, "YES") && SINGLE_USER_ONLY == "") {
                    // admin only
                    if (in_array("5_bb_brimbox", $arr_userroles)) {
                        $set_session = true;
                        $message = $log_message = "Login Success/Admin Only";
                    }
                    else {
                        $message = $log_message = "Program in Admin Only mode";
                    } // only if failure
                    
                }
                else {
                    // regular login
                    if (in_array($userrole, $arr_userroles)) {
                        // userrole already set
                        $set_session = true;
                        $message = $log_message = "Login Success/Selected Userrole";
                    }
                    else {
                        $userrole = $arr_userroles[0];
                        $set_session = true;
                        $message = $log_message = "Login Success/Default Userrole";
                    }
                }
            }
            else {
                // bad password
                // $set_session is false
                $set_attempts = true;
                // only one bad login message
                $log_message = "Bad Password";
            }

            if ($set_session) {
                // good login and mode
                // set attempts to zero
                $query = "UPDATE users_table SET attempts = 0 WHERE UPPER(username) = UPPER('" . pg_escape_string($username) . "');";
                pg_query($con, $query);
                // set username and email
                $_SESSION['username'] = $username = $row['username'];
                $_SESSION['email'] = $email = $row['email'];
                // set session timeout variable
                date_default_timezone_set(USER_TIMEZONE);
                $_SESSION['timeout'] = time();
                // build name for display
                $arr_name = array($row["fname"], $row["minit"], $row["lname"]);
                $arr_name = array_filter(array_map('trim', $arr_name));
                $_SESSION['name'] = implode(" ", $arr_name);
                // this holds the possible permissions, be careful altering on the fly
                $_SESSION['userroles'] = $row['userroles']; // userroles string from db
                // userrole set above
                $arr_userroles = explode(",", $row['userroles']);
                // userrole is posted on login
                if (!empty($userrole) && (in_array($userrole, $arr_userroles))) {
                    $_SESSION['userrole'] = $userrole;
                }
                else {
                    // userrole is first item
                    $_SESSION['userrole'] = $arr_userroles[0]; // first item of array
                    
                }
                $_SESSION['archive'] = 1; // archive mode is off
                // state and post data row, keeper is id
                $query = "INSERT INTO state_table (statedata, postdata) VALUES ('{}','') RETURNING id;";
                $result = pg_query($con, $query);
                $row = pg_fetch_array($result);
                $_SESSION['keeper'] = $row['id'];
                // log entry
                $arr_log = array($username, $email, $ip, $log_message);
                $query = "INSERT INTO log_table (username, email, ip_address, action) VALUES ($1,$2,$3,$4)";
                pg_query_params($con, $query, $arr_log);
                // redirect with header call to index with session set
                $index_path = "Location: " . dirname($_SERVER['PHP_SELF']);
                header($index_path);
                die(); // important to stop script
                
            }
            elseif ($set_attempts) {
                // bad password
                $query = "UPDATE users_table SET attempts = attempts + 1 WHERE UPPER(username) = UPPER('" . pg_escape_string($username) . "') RETURNING attempts;";
                $result = pg_query($con, $query);
                $row = pg_fetch_array($result);
                if ($row['attempts'] >= 10) {
                    $query = "UPDATE users_table SET userroles = '{0_bb_brimbox}' WHERE UPPER(username) = UPPER('" . pg_escape_string($username) . "');";
                    pg_query($con, $query);
                }
                $arr_log = array($username, $email, $ip, $log_message);
                $query = "INSERT INTO log_table (username, email, ip_address, action) VALUES ($1,$2,$3,$4)";
                pg_query_params($con, $query, $arr_log);
                // delay if invalid login
                $rnd = rand(100000, 200000);
                $username = $password = "";
                usleep($rnd);
            }
            else {
                // admin or single user
                $arr_log = array($email, $ip, $log_message);
                $arr_log = array($username, $email, $ip, $log_message);
                $query = "INSERT INTO log_table (username, email, ip_address, action) VALUES ($1,$2,$3,$4)";
                pg_query_params($con, $query, $arr_log);
                // delay if invalid login
                $username = $password = "";
            }
        }
        else {
            // no rows, bad username or locked
            // only one bad login message
            $log_message = "Login Failure: Bad Username, Invalid IP, or Account Locked";
            $arr_log = array($username, $email, $ip, $log_message);
            $query = "INSERT INTO log_table (username, email, ip_address, action) VALUES ($1,$2,$3,$4)";
            pg_query_params($con, $query, $arr_log);
            // delay if invalid login
            $rnd = rand(100000, 200000);
            $username = $password = "";
            usleep($rnd);
        }
    }
    else {
        // just in case there is something awry with the ip, not really possible
        $log_message = "Malformed IP";
        $arr_log = array($username, $email, $ip, $log_message);
        $query = "INSERT INTO log_table (username, email, ip_address, action) VALUES ($1,$2,$3,$4)";
        pg_query_params($con, $query, $arr_log);
        // delay if invalid login
        $rnd = rand(100000, 200000);
        $username = $password = "";
        usleep($rnd);
    }
} // end post

?>
