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
$main->check_permission("5_bb_brimbox");
?>
<script type="text/javascript">
/* MODULE JAVASCRIPT */
function bb_set_hidden(id,ac)
    {
    //set var form links and the add_new_user button on initial page
    var frmobj = document.forms["bb_form"];
    
    frmobj.id.value = id;
    frmobj.action.value = ac;
    bb_submit_form();
    return false;
    }
    
function bb_check_passwd(pwd,nm)
    {
    var frmobj = document.forms['bb_form'];
    var has_letter = new RegExp("[a-z]");
    var has_caps = new RegExp("[A-Z]");
    var has_numbers = new RegExp("[0-9]");
    
    var sum = (has_letter.test(pwd)*1) + (has_caps.test(pwd)*1) + (has_numbers.test(pwd)*1) + ((pwd.length>=8)*1);    
    
    if ((sum==4) || (pwd.length==0))
        {
        frmobj[nm].style.backgroundColor = "#FFFFFF";   
        }
    else
        {
        if (sum==0) frmobj[nm].style.backgroundColor = "#AA0033";
        else if (sum==1) frmobj[nm].style.backgroundColor = "#EE6644";
        else if (sum==2) frmobj[nm].style.backgroundColor = "#FFCC33";
        else if (sum==3) frmobj[nm].style.backgroundColor = "#6699CC";      
        }
	return false;
    }
        
function bb_populate_default(passthis,k,v)
    {
    var opts = document.getElementById("select_default").options;
    for(var i=0; i<opts.length; i++)
        {
        if (opts[i].value == "0_bb_brimbox")
            {
            opts[i] = null;    
            }
        }
    if (passthis.checked == true)
        {       
        opts[opts.length] = new Option(v.concat("\u00A0"),k);
        }
    if (passthis.checked == false)
        {
        for(var i=0; i<opts.length; i++)
            {        
            if(opts[i].value == k)
                {
                opts[i] = null;
                }
            }
        }
    return false;
    }
    
function bb_reload()
    {
    //change filter or sort
    bb_submit_form(); 
    return false;
    }
/* END MODULE JAVASCRIPT */
</script>
<style type="text/css">
/* MODULE CSS */
select.box {
	display: inline;
	width: 150px;
	height: 150px;
}
</style>
<?php
/* LOCAL FUNCTIONS */
// this function leaves an empty $arr_error for logic testing if no errors
function check_is_empty($value, $index, &$arr_error, $error_message) {
    // custom "is empty" function for this module
    if (empty($value) && $value !== '0') {
        $arr_error[$index] = $error_message;
    }
}

function check_password($passwd_work, $repasswd_work, &$arr_error) {

    if (!preg_match("/^.*(?=.{8,})(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).*$/", $passwd_work)) {
        $arr_error['passwd_work'] = "Must contain an uppercase, lowercase, and number and be at least 8 characters.";
    }
    if ($passwd_work != $repasswd_work) {
        $arr_error['repasswd_work'] = "Passwords do not match.";
    }
}

function check_ips($con, $ips_esc, &$ips, &$arr_error) {
    // It's not a good idea to use the database to check for errors
    // but in this case compatibility and integrity it makes sense
    // rely on postgres to check for errors in cidr list
    @$result = pg_query($con, "SELECT array_to_string('" . $ips_esc . "'::cidr[],'\n') as ips");
    if (!$result) {
        $arr_error['ips'] = "Invalid cidr in list.";
    }
    else {
        $row = pg_fetch_array($result);
        if ($row['ips'] == "0.0.0.0/0\n::/0") {
            $ips = "";
        }
        else {
            $ips = $row['ips'];
        }
    }
}
/* END LOCAL FUNCTIONS */
?>
<?php
/* CONSTANTS */
$maxinput = $main->get_constant('BB_STANDARD_LENGTH', 255);
$userrole_constant = $main->get_constant('BB_DEFAULT_USERROLE_ASSIGN', '0_bb_brimbox');
/* END CONSTANTS */

/* INITIAL VALUES */
$arr_error = array(); // if set there is an error in the form data, will display inline
$arr_messages = array(); // friendly action and error message
// $POST brought in from controller


// get state from db
$arr_state = $main->load($con, $module);

// Note that action is for the update and delete links and add new user button in the list view
// bb_button values are used for the update, delete, and enter buttons on individual records
if ($main->button(-1)) // reset button = -1
{
    $arr_state = array();
    $action = $main->set('action', $arr_state, 0);
    $id = $main->set('id', $arr_state, 0);
    $usersort = $main->set('usersort', $arr_state, "lname");
    $filterrole = $main->set('filterrole', $arr_state, "all");
    $username_work = $email_work = $passwd_work = $repasswd_work = $fname = $minit = $lname = $ips = $notes = "";
}
else {
    $action = $main->process('action', $module, $arr_state, 0);
    $id = $main->process('id', $module, $arr_state, 0);
    $usersort = $main->process('usersort', $module, $arr_state, "lname");
    $filterrole = $main->process('filterrole', $module, $arr_state, "all");
    if (in_array($action, array(1, 2))) {
        // if add new user on initial page is set, all of these are empty
        // if add new or update info pages are set these will be set, validate populated values
        $username_work = $main->purge_chars($main->process('username_work', $module, $arr_state, ""));
        $email_work = $main->purge_chars($main->process('email_work', $module, $arr_state, ""));
        $userroles_work = $main->process('userroles_work', $module, $arr_state, array());
        $passwd_work = $main->post('passwd_work', $module, "");
        $repasswd_work = $main->post('repasswd_work', $module, "");
        sort($userroles_work);
        $userrole_default = $main->process('userrole_default', $module, $arr_state, $userrole_constant);
        $fname = $main->purge_chars($main->process('fname', $module, $arr_state, ""));
        $minit = $main->purge_chars($main->process('minit', $module, $arr_state, ""));
        $lname = $main->purge_chars($main->process('lname', $module, $arr_state, ""));
        $ips = $main->post('ips', $module);
        $notes = $main->purge_chars($main->process('notes', $module, $arr_state, ""), false);
    }
}

// update state, back to db
$main->update($con, $module, $arr_state);
/* END POSTBACK */

/* POSTBACK FOR NEW USER AND UPDATE USER */
// note that navigation uses both bb_button (for postback) and assign (for display)
// add new user or edit user
if ($main->button(array(1, 2))) {
    // if add new user on initial page is set, all of these are empty
    // if add new or update info pages are set these will be set, validate populated values
    if (!empty($userroles_work)) {
        if ($userrole_default != "0_bb_brimbox") {
            $userroles_work = array_diff($userroles_work, array($userrole_default));
            array_unshift($userroles_work, $userrole_default);
        }
    }
    else {
        $userroles_work = array($userrole_default);
    }
    // split, trim and remove empty values
    $arr_ips = array_filter(array_map('trim', preg_split("/\n|\r\n?/", $ips)));
}
/* END POSTBACK FOR NEW USER AND UPDATE USER */

/* ADD NEW USER */
// correct header length
if ($main->button(1)) {
    $action = 1; // in case of validation error
    // email work
    check_is_empty($username_work, "username_work", $arr_error, "Username cannot be empty.");
    // non-empty email
    if (!isset($arr_error['email_work'])) {
        // username must be 5 characters or more
        if (strlen($username_work) < 5) {
            $arr_error['username_work'] = "Username must be 5 characters or longer.";
        }
    }
    check_is_empty($email_work, "email_work", $arr_error, "Email cannot be empty.");
    // non-empty email
    if (!isset($arr_error['email_work'])) {
        // check for valid email or that email matches username
        if (!filter_var($email_work, FILTER_VALIDATE_EMAIL) && ($username_work !== $email_work)) {
            $arr_error['email_work'] = "Email is not valid.";
        }
    }
    // password
    check_password($passwd_work, $repasswd_work, $arr_error);
    // names
    // check that they are non-empty
    check_is_empty($fname, "fname", $arr_error, "Firstname cannot be empty");
    check_is_empty($lname, "lname", $arr_error, "Lastname cannot be empty");
    // ips
    $ips_esc = empty($arr_ips) ? "{0.0.0.0/0,0:0:0:0:0:0:0:0/0}" : pg_escape_string("{" . implode(",", $arr_ips) . "}");
    check_ips($con, $ips_esc, $ips, $arr_error);

    // insert user
    if (empty($arr_error)) {
        $salt = md5(microtime());
        $where_not_exists = "SELECT 1 from users_table WHERE username = '" . pg_escape_string($username_work) . "' OR email = '" . pg_escape_string($email_work) . "'";
        $query = "INSERT INTO users_table (username, email, hash, salt, userroles, fname, minit, lname, notes, ips) " . "SELECT '" . pg_escape_string($username_work) . "', '" . pg_escape_string($email_work) . "', '" . hash('sha512', pg_escape_string($passwd_work) . $salt) . "', '" . $salt . "', '{" . implode(",", array_map('pg_escape_string', $userroles_work)) . "}', " . "'" . pg_escape_string($fname) . "', '" . pg_escape_string($minit) . "', '" . pg_escape_string($lname) . "', '" . pg_escape_string($notes) . "', '" . $ips_esc . "' " . "WHERE NOT EXISTS (" . $where_not_exists . ");";
        $result = $main->query($con, $query);
        $cnt = pg_affected_rows($result);

        if (pg_affected_rows($result) == 0) {
            // only get here with good email
            array_push($arr_messages, "Error: Username \"" . $username_work . "\" or email \"" . $email_work . "\" already exists for another user.");
        }
        else {
            // since action is zero, only email_work needs to be blanked
            $email_work = "";
            array_push($arr_messages, "User \"" . $username_work . "\" has been added.");
            $action = 0; // success
            
        }
    }
}
/* END ADD NEW USER */

/* UPDATE INFO */
if ($main->button(2)) {
    // postback update
    // updates based on id
    $action = 2; // in case of validation error
    check_is_empty($username_work, "username_work", $arr_error, "Username cannot be empty.");
    if (!isset($arr_error['email_work'])) {
        // non-empty email
        // username must be 5 characters or more
        if (strlen($username_work) < 5) {
            // check for valid email
            $arr_error['username_work'] = "Username must be 5 characters or longer.";
        }
    }
    check_is_empty($email_work, "email_work", $arr_error, "Email cannot be empty.");
    if (!isset($arr_error['email_work'])) {
        // non-empty email
        // check for valid email or that email matches username
        if (!filter_var($email_work, FILTER_VALIDATE_EMAIL) && ($username_work !== $email_work)) {
            $arr_error['email_work'] = "Email is not valid.";
        }
    }

    // query_add_clause only if password is being updated
    $query_add_clause = "";
    if (!$main->blank($passwd_work) || !$main->blank($repasswd_work)) {
        // call password function at top
        check_password($passwd_work, $repasswd_work, $arr_error);
        // will not be used upon error
        $salt = md5(microtime());
        $query_add_clause = ", hash = '" . hash('sha512', $passwd_work . $salt) . "', salt = '" . $salt . "'";
    }
    // names
    check_is_empty($fname, "fname", $arr_error, "Firstname cannot be empty");
    check_is_empty($lname, "lname", $arr_error, "Lastname cannot be empty");
    // ips
    $ips_esc = empty($arr_ips) ? "{0.0.0.0/0,0:0:0:0:0:0:0:0/0}" : pg_escape_string("{" . implode(",", $arr_ips) . "}");
    check_ips($con, $ips_esc, $ips, $arr_error);

    // do the update
    if (empty($arr_error)) {
        $where_not_exists = "SELECT 1 from users_table WHERE id <> " . pg_escape_string($id) . " AND (username = '" . pg_escape_string($username_work) . "' OR email = '" . pg_escape_string($email_work) . "')";
        $query = "UPDATE users_table " . "SET username = '" . pg_escape_string($username_work) . "', email = '" . pg_escape_string($email_work) . "', fname = '" . pg_escape_string($fname) . "', minit = '" . pg_escape_string($minit) . "', lname = '" . pg_escape_string($lname) . "', " . "userroles = '{" . implode(",", array_map('pg_escape_string', $userroles_work)) . "}', attempts = 0, notes = '" . pg_escape_string($notes) . "', ips = '" . $ips_esc . "' " . $query_add_clause . " " . "WHERE id = " . pg_escape_string($id) . " AND NOT EXISTS (" . $where_not_exists . ");";
        $result = $main->query($con, $query);
        $cnt = pg_affected_rows($result);

        if (pg_affected_rows($result) == 0) {
            // only get here with good email
            array_push($arr_messages, "Error: Cannot update, username or email duplicated or underlying data change possible.");
        }
        else {
            // since action is zero, only email needs to be blanked
            array_push($arr_messages, "User \"" . $username_work . "\" information has been updated.");
            // dispose of state
            $arr_state = array();
            $action = $main->set('action', $arr_state, 0);
            $id = $main->set('id', $arr_state, 0);
            $usersort = $main->set('usersort', $arr_state, "lname");
            $filterrole = $main->set('filterrole', $arr_state, "all");
            $username_work = $email_work = $passwd_work = $repasswd_work = $fname = $minit = $lname = $ips = $notes = "";
            $main->update($con, $module, $arr_state);
        }
    }
}
/* UPDATE INFO */

/* LOCK USER */
if ($main->button(3)) {
    // postback delete_user
    // deletes based on email
    $action = 0; // no validation
    $query = "UPDATE users_table SET userroles = '{0_bb_brimbox}' WHERE id = " . pg_escape_string($id) . ";";
    $result = $main->query($con, $query);
    $cnt = pg_affected_rows($result);

    if ($cnt == 0) {
        // only get here with good email
        array_push($arr_messages, "Error: Login record not found in database.");
    }
    else {
        // since action is zero, only email needs to be blanked
        array_push($arr_messages, "User has been locked.");
        // dispose of state
        $arr_state = array();
        $action = $main->set('action', $arr_state, 0);
        $id = $main->set('id', $arr_state, 0);
        $usersort = $main->set('usersort', $arr_state, "lname");
        $filterrole = $main->set('filterrole', $arr_state, "all");
        $username_work = $email_work = $passwd_work = $repasswd_work = $fname = $minit = $lname = $ips = $notes = "";
        $main->update($con, $module, $arr_state);
    }
}
/* END LOCK USER */

/* DELETE USER */
if ($main->button(4)) {
    // postback delete_user
    // deletes based on email
    $action = 0; // no validation
    $query = "DELETE FROM users_table WHERE id = " . pg_escape_string($id) . ";";
    $result = $main->query($con, $query);
    $cnt = pg_affected_rows($result);

    if ($cnt == 0) {
        // only get here with good email
        array_push($arr_messages, "Error: Login record not found in database.");
    }
    else {
        // since action is zero, only email needs to be blanked
        array_push($arr_messages, "User has been deleted.");
        $arr_state = array();
        $action = $main->set('action', $arr_state, 0);
        $id = $main->set('id', $arr_state, 0);
        $usersort = $main->set('usersort', $arr_state, "lname");
        $filterrole = $main->set('filterrole', $arr_state, "all");
        $username_work = $email_work = $passwd_work = $repasswd_work = $fname = $minit = $lname = $ips = $notes = "";
        $main->update($con, $module, $arr_state);
    }
}
/* END DELETE USER */

/* POPULATE FORM IF DELETE, EDIT OR LOCK FROM INITIAL PAGE */
// get all possible userroles from $arr_header
if (in_array($action, array(0, 1, 2, 3, 4))) {
    $arr_userroles_loop = array();
    foreach ($array_userroles as $key => $value) {
        // interface info
        $arr_userroles_loop[] = array('userrole_name' => $value['name'], 'userrole_value' => $key);
    }
}

/* HTML OUTPUT */
echo "<p class=\"spaced bold larger\">Manage Users</p>";

echo "<div class=\"padded\">";
$main->echo_messages($arr_messages);
echo "</div>";

/* START REQUIRED FORM */
$main->echo_form_begin();
$main->echo_module_vars();;

/* MAIN PAGE */
// gets the list of users for the administrator
// also has add_new_user button
// action and button 0
if ($action == 0) {
    // retrieve everything
    $where_clause = ($filterrole == "all") ? "1 = 1" : "'" . pg_escape_string($filterrole) . "' = ANY (userroles)";
    switch ($usersort) {
        case 'lname':
            $order_clause = "lname, fname, id";
        break;
        case 'fname':
            $order_clause = "fname, lname, id";
        break;
        case 'email':
            $order_clause = " email, id";
        break;
        case 'username':
            $order_clause = " username, id";
        break;
    }
    $query = "SELECT id, username, email, array_to_string(userroles,',') as userroles, fname, minit, lname FROM users_table " . "WHERE " . $where_clause . " ORDER BY " . $order_clause . ";";
    // echo "<p>" . $query . "</p>";
    $result = $main->query($con, $query);

    echo "<div class=\"floatleft\">";
    echo "<button class=\"floatleft spaced\" name=\"add_new_user\" onclick=\"bb_set_hidden(0,1)\">Add New User</button>";

    // floatright in reverse order
    echo "<div class=\"table floatright\">";
    echo "<div class=\"row\">";
    echo "<div class=\"padded cell\">Filter By: </div>";
    echo "<div class=\"padded cell\">";
    echo "<select name=\"filterrole\" onChange=\"bb_reload()\">";
    echo "<option value=\"all\">All</option>";
    foreach ($arr_userroles_loop as $arr_userrole_loop) {
        $key = $arr_userrole_loop['userrole_value'];
        $value = $arr_userrole_loop['userrole_name'];
        $selected = ($filterrole == $key) ? "selected" : "";
        echo "<option value=\"" . $key . "\" " . $selected . ">" . $value . "</option>";
    }
    echo "</select>";
    echo "</div>";

    echo "<div class=\"padded cell\"> Sort By: </div>";
    echo "<div class=\"padded cell\">";
    echo "<select name=\"usersort\" onChange=\"bb_reload()\">";
    $arr_usersort = array('lname' => "Last Name", 'fname' => "First Name", 'username' => "Username", 'email' => "Email");
    foreach ($arr_usersort as $key => $value) {
        $selected = ($usersort == $key) ? "selected" : "";
        echo "<option value=\"" . $key . "\" " . $selected . ">" . $value . "&nbsp;</option>";
    }
    echo "</select>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "<div class=\"clear\"></div>";

    // user table
    echo "<div class=\"table spaced border\">";
    echo "<div class=\"row shaded\">";
    echo "<div class=\"extra middle bold cell\">Username</div>";
    echo "<div class=\"extra middle bold cell\">Email</div>";
    echo "<div class=\"extra middle bold cell\">Full Name</div>";
    echo "<div class=\"extra middle bold cell\">Userrole</div>";
    echo "<div class=\"cell\"></div>";
    echo "<div class=\"cell\"></div>";
    echo "<div class=\"cell\"></div>";
    echo "</div>";

    $i = 0;
    // iterate through all current users
    while ($row = pg_fetch_array($result)) {
        $shaded = ($i % 2) == 0 ? "even" : "odd";
        echo "<div class=\"" . $shaded . " row\">";
        $name = $main->build_name($row);
        $id = $row['id'];
        $username_work = $row['username'];
        $email_work = $row['email'];
        $userroles_work = explode(",", $row['userroles']);
        echo "<div class=\"extra middle cell\">" . __($username_work) . "</div>";
        echo "<div class=\"extra middle cell\">" . __($email_work) . "</div>";
        echo "<div class=\"extra middle cell\">" . __($name) . "</div>";
        echo "<div class=\"extra middle cell\">";
        $arr_display = array();
        foreach ($userroles_work as $value) {
            if ($value == "0_bb_brimbox") array_push($arr_display, "Locked");
            else array_push($arr_display, $array_userroles[$value]['name']);
        }
        echo implode(", ", $arr_display);
        echo "</div>";
        echo "<div class=\"extra right middle cell\"><button class=\"link\" onclick=\"bb_set_hidden(" . $id . ",2); return false;\">Edit</button></div>";
        echo "<div class=\"extra right middle cell\"><button class=\"link\" onclick=\"bb_set_hidden(" . $id . ",3); return false;\">Lock</button></div>";
        echo "<div class=\"extra right middle cell\"><button class=\"link\" onclick=\"bb_set_hidden(" . $id . ",4); return false;\">Delete</button></div>";
        echo "</div>";
        $i++;
    }
    echo "</div>";

    // these hidden forms are used on the initial page
    echo "<input name=\"action\" type=\"hidden\" value=\"\" />";
    echo "<input name=\"id\" type=\"hidden\" value=\"\" />";

    echo "</div>";
}
/* END MAIN */

/* ADD, EDIT, DELETE and LOCK */
if (in_array($action, array(1, 2, 3, 4))):

    // buttons
    if ($action == 1) {
        $params = array("class" => "spaced", "number" => 1, "target" => $module, "passthis" => true, "label" => "Add User");
        $main->echo_button("add_user", $params);
        // Reset Button
        $params = array("class" => "spaced", "number" => - 1, "target" => $module, "passthis" => true, "label" => "Reset Module");
        $main->echo_button("clear_form", $params);
    }
    elseif ($action == 2) {
        $params = array("class" => "spaced", "number" => 2, "target" => $module, "passthis" => true, "label" => "Edit User");
        $main->echo_button("update_info", $params);
        $params = array("class" => "spaced", "number" => - 1, "target" => $module, "passthis" => true, "label" => "Reset Module");
        $main->echo_button("clear_form", $params);
    }
    elseif ($action == 3) {
        $params = array("class" => "spaced", "number" => 3, "target" => $module, "passthis" => true, "label" => "Lock User");
        $main->echo_button("lock_user", $params);
        $params = array("class" => "spaced", "number" => - 1, "target" => $module, "passthis" => true, "label" => "Reset Module");
        $main->echo_button("clear_form", $params);
    }
    elseif ($action == 4) {
        $params = array("class" => "spaced", "number" => 4, "target" => $module, "passthis" => true, "label" => "Delete User");
        $main->echo_button("delete_user", $params);
        $params = array("class" => "spaced", "number" => - 1, "target" => $module, "passthis" => true, "label" => "Reset Module");
        $main->echo_button("clear_form", $params);
    }

    // get the specific record for edit, delete, or lock, not on postback
    // manage users a little tricky because of multiple functionalites
    // check action is either delet or lock, or that it is from edit link, and also postback (rather than tab switch)
    if (in_array($action, array(3, 4)) || (in_array($action, array(2)) && $main->button(0) && ($submit == "bb_manage_users"))) {
        // edit or delete
        $query = "SELECT id, username, email, array_to_string(userroles,',') as userroles, fname, minit, lname, notes, array_to_string(ips,'\n') as ips FROM users_table WHERE id IN (" . pg_escape_string($id) . ");";

        $result = $main->query($con, $query);

        $row = pg_fetch_array($result);

        if (pg_num_rows($result) == 1) {
            $username_work = $row['username'];
            $email_work = $row['email'];
            $fname = $row['fname'];
            $minit = $row['minit'];
            $lname = $row['lname'];
            $notes = $row['notes'];
            $ips = $row['ips'] == "0.0.0.0/0\n::/0" ? "" : $row['ips'];
            // called assign since userrole is current (admin) user
            $userroles_work = explode(",", $row['userroles']);
        }
    }

    // add user, edit user, and delete, email work is key
    // this is the form page, button are all postback
    // hidden field to keep the id of record to alter
    echo "<input name=\"id\" type=\"hidden\" value=\"" . $id . "\" />";
    // for delete form output
    $readonly = in_array($action, array(3, 4)) ? true : false;

    // echo out form
    echo "<div class=\"table spaced\">";

    echo "<div class=\"row\">";
    echo "<div class=\"middle cell\">Username:</div>";
    echo "<div class=\"middle cell\">";
    $main->echo_input("username_work", __($username_work), array('type' => "text", 'class' => "spaced long", 'readonly' => $readonly, 'maxlength' => $maxinput));
    echo "</div>";
    echo "<div class=\"error middle cell\"> " . (isset($arr_error['username_work']) ? $arr_error['username_work'] : "") . "</div>";
    echo "</div>";

    echo "<div class=\"row\">";
    echo "<div class=\"middle cell\">Email:</div>";
    echo "<div class=\"middle cell\">";
    $main->echo_input("email_work", __($email_work), array('type' => "text", 'class' => "spaced long", 'readonly' => $readonly, 'maxlength' => $maxinput));
    echo "</div>";
    echo "<div class=\"error middle cell\"> " . (isset($arr_error['email_work']) ? $arr_error['email_work'] : "") . "</div>";
    echo "</div>";

    // passwords, md5 so never repopulate, no readonly, has error msgs
    echo "<div class=\"row\">";
    echo "<div class=\"middle cell\">Password:</div>";
    echo "<div class=\"middle cell\">";
    $handler = "onKeyUp=\"bb_check_passwd(this.value,'passwd_work')\"";
    $main->echo_input("passwd_work", "", array('type' => 'password', 'class' => 'spaced long', 'handler' => $handler, 'maxlength' => $maxinput));
    echo "</div>";
    echo "<div class=\"error middle cell\"> " . (isset($arr_error['passwd_work']) ? $arr_error['passwd_work'] : "") . "</div>";
    echo "</div>";

    echo "<div class=\"row spaced\">";
    echo "<div class=\"middle cell\">Re-Enter Password:</div>";
    echo "<div class=\"middle cell\">";
    $handler = "onKeyUp=\"bb_check_passwd(this.value,'repasswd_work')\"";
    $main->echo_input("repasswd_work", "", array('type' => 'password', 'class' => 'spaced long', 'handler' => $handler, 'maxlength' => $maxinput));
    echo "</div>";
    echo "<div class=\"error middle cell\"> " . (isset($arr_error['repasswd_work']) ? $arr_error['repasswd_work'] : "") . "</div>";
    echo "</div>";

    // names, readonly for delete, has first and last name error messages for edit
    // delete will have no error msgs but is tested anyway
    echo "<div class=\"row\">";
    echo "<div class=\"middle cell\">First Name:</div>";
    echo "<div class=\"middle cell\">";
    $main->echo_input("fname", __($fname), array('type' => "input", 'class' => "spaced long", 'readonly' => $readonly, 'maxlength' => $maxinput));
    echo "</div>";
    echo "<div class=\"error middle cell\"> " . (isset($arr_error['fname']) ? $arr_error['fname'] : "") . "</div>";
    echo "</div>";

    echo "<div class=\"row\">";
    echo "<div class=\"middle cell\">Middle Initial:</div>";
    echo "<div class=\"middle cell\">";
    $main->echo_input("minit", __($minit), array('type' => "input", 'class' => "spaced long", 'readonly' => $readonly, 'maxlength' => $maxinput));
    echo "</div>";
    echo "<div class=\"middle cell\"></div>";
    echo "</div>";

    echo "<div class=\"row\">";
    echo "<div class=\"middle cell\"\">Last Name:</div>";
    echo "<div class=\"middle cell\">";
    $main->echo_input("lname", __($lname), array('type' => "input", 'class' => "spaced long", 'readonly' => $readonly, 'maxlength' => $maxinput));
    echo "</div>";
    echo "<div class=\"error middle cell\"> " . (isset($arr_error['lname']) ? $arr_error['lname'] : "") . "</div>";
    echo "</div>";

    // lock and delete
    if (in_array($action, array(3, 4))) {
        // display roles
        echo "<div class=\"row\">";
        echo "<div class=\"middle cell\"\">Roles:</div>";
        echo "<div class=\"middle cell\">";
        echo "<div class=\"padded spaced border\">";
        $arr_display = array();
        foreach ($userroles_work as $value) {

            array_push($arr_display, $array_header['userroles'][$value]);
        }
        echo implode("<br>", $arr_display);
        echo "</div></div>";
        echo "<div class=\"middle cell\"></div>";
        echo "</div>";
        echo "<div class=\"row\">";
        echo "<div class=\"middle cell\"\">IPs (cidr):</div>";
        echo "<div class=\"padded spaced border minnote\">" . nl2br($ips) . "</div>";
        echo "<div class=\"middle cell\"></div>";
        echo "</div>";
        echo "<div class=\"row\">";
        echo "<div class=\"middle cell\"\">Notes:</div>";
        echo "<div class=\"padded spaced border minnote\">" . nl2br($notes) . "</div>";
        echo "<div class=\"middle cell\"></div>";
        echo "</div>";
    }

    // add or edit user
    if (in_array($action, array(1, 2))) {
        // default role select
        echo "<div class=\"row\">";
        echo "<div class=\"middle cell\"\">Default Role:</div>";
        echo "<div class=\"middle cell\"><select name=\"userrole_default\" id=\"select_default\"  class=\"spaced\" />";
        // if add user
        if (in_array($action, array(1)) && empty($userroles_work)) {
            $userroles_work = array($userrole_constant);
        }

        foreach ($userroles_work as $value) {
            $selected = ($value == $userroles_work[0]) ? "selected" : "";
            echo "<option value=\"" . $value . "\" " . $selected . ">" . $array_userroles[$value]['name'] . "&nbsp;</option>";
        }

        echo "</select></div>";
        echo "<div class=\"error middle cell\"></div>";
        echo "</div>";

        // userroles select
        echo "<div class=\"row\">";
        echo "<div class=\"middle cell\">Roles:</div>";
        echo "<div class=\"middle cell\">";
        echo "<div class=\"spaced border padded\">";
        // unset locked value
        unset($arr_userroles_loop['0_bb_brimbox']);
        foreach ($arr_userroles_loop as $arr_userrole_loop) {
            $userrole_value = $arr_userrole_loop['userrole_value'];
            $userrole_name = $arr_userrole_loop['userrole_name'];
            $checked = in_array($userrole_value, $userroles_work) ? "checked" : "";
            $handler = "onClick=\"bb_populate_default(this, '" . $userrole_value . "', '" . $userrole_name . " ')\"";
            echo "<div class=\"padded\">";
            // custom checkbox because of array
            echo "<input type=\"checkbox\" value=\"" . $userrole_value . "\" name=\"userroles_work[]\" class=\"middle holderup\" " . $checked . " " . $handler . "/>";
            echo "<span class=\"middle padded\">" . $userrole_name . "</span>";
            echo "</div>";
        }
        echo "</div>";
        echo "</div>";
        echo "<div class=\"middle cell\"></div>";
        echo "</div>";

        // id addresses textarea
        echo "<div class=\"row\">";
        echo "<div class=\"middle cell\">IP Addresses or Ranges (cidr):</div>";
        echo "<div class=\"cell\">";
        $main->echo_textarea("ips", $ips, array('class' => "spaced", 'rows' => 7, 'cols' => 27));
        echo "</div>";
        echo "<div class=\"error middle cell\"> " . (isset($arr_error['ips']) ? $arr_error['ips'] : "") . "</div>";
        echo "</div>";

        echo "<div class=\"row\">";
        echo "<div class=\"middle cell\">Notes:</div>";
        echo "<div class=\"cell\">";
        $main->echo_textarea("notes", $notes, array('class' => "spaced", 'rows' => 8, 'cols' => 35));
        echo "</div>";
        echo "<div class=\"middle cell\"></div>";
        echo "</div>";
    }

    echo "</div>"; // end table
    if (in_array($action, array(1, 2))) echo "<p class=\"spaced italic\">Password must contain numbers, uppercase and lowercase letters, and length must be 8 or greater.</p>";

endif;

$main->echo_form_end();
/* END FORM */
?>
