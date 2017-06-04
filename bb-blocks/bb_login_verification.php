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
/* This is the login routine */
/* You can customize this by place a file called login.php in the root of the Brimbox instance */

if (isset($_POST['bb_submit'])) {
    // get connection
    $email = $password = $userrole = $message = "";

    // get form variables
    $username = substr($_POST['username'], 0, 255); // email and password must be < 255 by definition
    $password = substr($_POST['password'], 0, 255); // do not want to process big post
    $userrole = !empty($_POST['userrole']) ? substr($_POST['userrole'], 0, 255) : "";

    // default error message, information only provided with accurate credentials
    $set_session = $deny_session = false;

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
                        $log_message = "Login Success/Single User";
                    }
                    else {
                        $deny_session = true;
                        $log_message = "Program in Single User mode";
                    }
                }
                elseif (!strcasecmp(ADMIN_ONLY, "YES") && SINGLE_USER_ONLY == "") {
                    // admin only
                    if (in_array("5_bb_brimbox", $arr_userroles)) {
                        $set_session = true;
                        $message = 3;
                    }
                    else {
                        $deny_session = true;
                        $log_message = "Program in Admin Only mode";
                    } // only if failure
                    
                }
                else {
                    // regular login
                    if (in_array($userrole, $arr_userroles)) {
                        // userrole already set
                        $set_session = true;
                        $log_message = "Login Success/Selected Userrole";
                    }
                    else {
                        $userrole = $arr_userroles[0];
                        $set_session = true;
                        $log_message = "Login Success/Default Userrole";
                    }
                }
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
            elseif ($deny_session) {
                // in admin or single user mode
                $message = __t("Program in Admin or Single User mode for maintenance and administration.", "bb_login");
                //log_message already set
                $arr_log = array($username, $email, $ip, $log_message);
                $query = "INSERT INTO log_table (username, email, ip_address, action) VALUES ($1,$2,$3,$4)";
                pg_query_params($con, $query, $arr_log);
                // delay if invalid login
                $username = $password = "";
            }
            else {
                // bad password set attempts
                $message = __t("Login Failure: Bad Username and Password, Invalid IP, or Account Locked.", "bb_login");
                $log_message = __t("Bad Password/Attempt Incremented", "bb_login");
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
        }
        else {
            // no rows, bad username or locked
            // only one bad login message
            $message = __t("Login Failure: Bad Username and Password, Invalid IP, or Account Locked.", "bb_login");
            $log_message = __t("Login Failure: Bad Username", "bb_login");
            $arr_log = array($username, $email, $ip, $log_message);
            $query = "INSERT INTO log_table (username, email, ip_address, action) VALUES ($1,$2,$3,$4)";
            pg_query_params($con, $query, $arr_log);
            // delay if invalid login
            $rnd = rand(100000, 200000);
            $username = $password = "";
            usleep($rnd);
        }
    } // end post
    
}

elseif (isset($_POST['bb_reset'])) {
    $index_path = "Location: " . dirname($_SERVER['PHP_SELF']) . "?action=reset";
    header($index_path);
    die();
}

elseif (isset($_POST['bb_send'])) {

    $username_or_email = substr($_POST['username_or_email'], 0, 255);

    if (!filter_var($ip = $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) die();

    $query = "SELECT id, username, email, fname, lname, hash FROM users_table WHERE NOT ('0_bb_brimbox' = ANY (userroles)) AND ('" . pg_escape_string($ip) . "' <<= ANY (ips)) AND (UPPER(username) = UPPER('" . pg_escape_string($username_or_email) . "') OR UPPER(email) = UPPER('" . pg_escape_string($username_or_email) . "')) AND array_length(userroles,1) > 0 AND attempts <= 10;";
    //echo "<p>" . $query . "</p>";
    $result = pg_query($con, $query);
    $num_rows = pg_num_rows($result);

    if ($num_rows == 1) {
        //get result vars
        $row = pg_fetch_array($result);
        $username = $row['username'];
        $email = $row['email'];
        $name = $row['fname'] . " " . $row['lname'];
        $id = (int)$row['id'];

        $hash = md5($row['hash']) . md5(rand());

        //set reset column
        $query = "UPDATE users_table SET reset = NOW()::text || '+" . $hash . "' WHERE id = " . $id . ";";
        pg_query($con, $query);

        $reset_link = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER[HTTP_HOST] . strtok($_SERVER[REQUEST_URI], "?") . "?action=set&id=" . $id . "&key=" . $hash;

        //bring in SMTP mailer
        if (file_exists("bb-extend/bb_login_reset_mailer.php")) include_once ("bb-extend/bb_login_reset_mailer.php");
        else include_once ("bb-blocks/bb_login_reset_mailer.php");

        //email program error
        if (!$mail->Send()) {
            //echo "Mailer Error: " . $mail->ErrorInfo;
            $index_path = "Location: " . dirname($_SERVER['PHP_SELF']) . "?action=nomail";
        }
        else {
            //email message sent
            $index_path = "Location: " . dirname($_SERVER['PHP_SELF']) . "?action=sent";
        }

    }
    else {
        //username or email not found
        $index_path = "Location: " . dirname($_SERVER['PHP_SELF']) . "?action=nouser";
    }
    header($index_path);
    die();
}

elseif (isset($_POST['bb_set'])) {

    $id = (int)$_GET['id'];
    $key = substr($_GET['key'], 0, 64);
    $password_set = substr($_POST['password_set'], 0, 255);

    if (preg_match("/^.*(?=.{8,})(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).*$/", $password_set)) {

        $salt = md5(microtime());
        $hash = hash('sha512', $password_set . $salt);

        $query = "UPDATE users_table SET salt = '$salt', hash = '$hash', attempts = 0
                  WHERE EXTRACT(EPOCH FROM now() - left(reset, strpos(reset, '+') - 1)::timestamp) <= 3600
                  AND right(reset, 64) = '" . pg_escape_string($key) . "'
                  AND id = $id AND attempts <= 25";
        $result = pg_query($con, $query);

        if (pg_affected_rows($result) == 1) {
            $index_path = "Location: " . dirname($_SERVER['PHP_SELF']) . "?action=done";
        }
        else {
            $query = "UPDATE users_table SET attempts = attempts + 1 WHERE id = $id RETURNING attempts;";
            $result = pg_query($con, $query);
            $index_path = "Location: " . dirname($_SERVER['PHP_SELF']) . "?action=expired";
        }
        header($index_path);
        die();
    }
    else {
        $index_path = "Location: " . dirname($_SERVER['PHP_SELF']) . "?action=weak&id=$id&key=$key";
        header($index_path);
        die();
    }
}

elseif (isset($_POST['bb_home'])) {
    $index_path = "Location: " . dirname($_SERVER['PHP_SELF']);
    header($index_path);
    die();
}

if (isset($_GET['action'])) {
    $arr_messages = array('reset' => __t("Enter your account email or username to have password reset emailed to you.", "bb_login"), 'nomail' => __t("Email has not been sent with password reset link. Email server most likely not properly configured.", "bb_login"), 'sent' => __t("Email has been sent with password reset link. Please allow a couple of minutes for propagation and check bulk folders if necessary.", "bb_login"), 'nouser' => __t("Email or username not found in account list. Please check your records and try again.", "bb_login"), 'expired' => __t("Invalid or expired password reset link or account locked. Please try again or contact administrator.", "bb_login"), 'done' => __t("Password has been updated. Please login with your username and new password.", "bb_login"), 'weak' => __t("Password too weak. Password must have a length of 8 and at least 1 uppercase, lowercase and number.", "bb_login"));
    if (isset($arr_messages[$_GET['action']])) $message = $arr_messages[$_GET['action']];
}

?>
