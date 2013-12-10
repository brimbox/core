<?php
/*
Copyright (C) 2012 - 2013  Kermit Will Richardson, Brimbox LLC

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License Version 3 (“GNU GPL v3”)
as published by the Free Software Foundation. 

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU GPL v3 for more details. 

You should have received a copy of the GNU GPL v3 along with this program.
If not, see http://www.gnu.org/licenses/
*/
?>
<?php
$main->check_permission(5);
?>
<script type="text/javascript">
/* MODULE JAVASCRIPT */
function set_hidden(id,ac)
    {
    //set var form links and the add_new_user button on initial page
    var frmobj = document.forms["bb_form"];
    
    frmobj.id.value = id;
    frmobj.action.value = ac;
    bb_submit_form(0);
	return false;
    }
    
function checkPasswd(pwd,nm)
    {
    var frmobj = document.forms['bb_form'];
    
    var has_letter = new RegExp("[a-z]");
    var has_caps = new RegExp("[A-Z]");
    var has_numbers = new RegExp("[0-9]");    
    
    if ((has_letter.test(pwd) && has_caps.test(pwd) && has_numbers.test(pwd) && pwd.length >= 8) || (pwd.length == 0))
        {
        frmobj[nm].style.backgroundColor = "#FFFFFF";   
        }
    else
        {
        frmobj[nm].style.backgroundColor = "#FF6666";
        }
	return false;
    }
/* END MODULE JAVASCRIPT */
</script>
<?php
/* INITIAL VALUES */
$arr_error = array(); //if set there is an error in the form data, will display inline
$arr_message = array(); //friendly action and error message

/* GET STATE AND POSTBACK */
$main->retrieve($con, $array_state, $userrole);

//Note that action is for the update and delete links and add new user button in the list view
//bb_button values are used for the update, delete, and enter buttons on individual records

$action = $main->post('action', $module, 0);
$id = $main->post('id', $module, 0);
/* END POSTBACK */

//* uses global $array_userroles *//

//$userrole is a global var, so the userrole to be assigned is $userrole_work
//userrole is userrole in database
//$email is a global var, so the email that is assigned is $email_work
//email is email in database
//default value should probably be set to 1 or 2, guest or viewer
$userrole_work = DEFAULT_USERROLE_ASSIGN;
//$email_work called $email_work because of global $email
$email_work = "";
$passwd = "";
$repasswd = "";
$fname = "";
$minit = "";
$lname = "";
/* END INITIAL VALUES */

/* LOCAL FUNCTIONS */
//this function leaves an empty $arr_error for logic testing if no errors
function check_is_empty($value, $index, &$arr_error, $error_message)
    {
    //custom "is empty" function for this module 
    if (empty($value))
        {
        $arr_error[$index] = $error_message;   
        }
    }
function check_password($passwd, $repasswd, &$arr_error)
    {
    if (!preg_match("/^.*(?=.{8,})(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).*$/", $passwd))
        {
        $arr_error['passwd'] = "Must contain an uppercase, lowercase, and number and be at least 8 characters.";     
        }
    if ($passwd <> $repasswd)
        {
        $arr_error['repasswd'] = "Passwords do not match.";        
        }
    }
/* END FUNCTIONS */

/* POSTBACK */
// note that navigation uses both bb_button (for postback) and assign (for display) 
// add new user or edit user 
if (($main->post('bb_button', $module) == 1) || ($main->post('bb_button', $module) == 2))
    {
    //if add new user on initial page is set, all of these are empty
    //if add new or update info pages are set these will be set, validate populatred values
	
    $email_work = $main->custom_trim_string($main->post('email_work', $module),255);
    $passwd = $main->post('passwd', $module);
    $repasswd = $main->post('repasswd', $module);
    $userrole_work = $main->post('userrole_work', $module);
    $fname = $main->custom_trim_string($main->post('fname', $module),255);
    $minit = $main->custom_trim_string($main->post('minit', $module),255);
    $lname = $main->custom_trim_string($main->post('lname', $module),255);
    }
/* END POSTBACK */

/* ADD NEW USER POSTBACK */
if ($main->post('bb_button', $module) == 1) //postback add new user
    {    
    $action = 1; //in case of validation error    
    //email work
    check_is_empty($email_work, "email_work", $arr_error, "Email cannot be empty");
    
    if (!isset($arr_error['email_work'])) //non-empty email
        {            
        if (!filter_var($email_work, FILTER_VALIDATE_EMAIL)) //check for valid email
            {
            $arr_error['email_work'] = " Email is not valid";     
            }   
        }   
    //password    
    check_password($passwd, $repasswd, $arr_error);
    //names
    //check that they are non-empty
    check_is_empty($fname, "fname", $arr_error, "Firstname cannot be empty");
    check_is_empty($lname, "lname", $arr_error, "Lastname cannot be empty");
   
    //insert user
    if (empty($arr_error))
        {
        $salt = md5(microtime());
        $query = "INSERT INTO users_table (email, hash, salt, userrole, fname, minit, lname) " .
                 "SELECT '" . pg_escape_string($email_work) . "', '" . hash('sha512', $passwd . $salt) . "', '" . $salt . "', " . (int)$userrole_work . ", '" . pg_escape_string($fname) . "', '" . pg_escape_string($minit) . "', '" . pg_escape_string($lname) . "' " .
                 "WHERE NOT EXISTS (SELECT 1 FROM users_table WHERE email = '" . pg_escape_string($email_work) . "')";
        $result = $main->query($con,$query);
        $cnt = pg_affected_rows($result);
        
        if (pg_affected_rows($result) == 0)
            {
            //only get here with good email
            $arr_error['email_work'] = "Email " . $email_work . " already exists for another user.";      
            }
        else
            {
            //since action is zero, only email_work needs to be blanked
            $email_work = "";
            array_push($arr_message, "User " . $email_work . " has been added.");
            $action = 0; //success
            }       
        }
    }
/* END ADD NEW USER POSTBACK */

/* UPDATE INFO POSTBACK */
if ($main->post('bb_button', $module) == 2) //postback update
    {
	//updates based on id
    $action = 2; //in case of validation error
	
    //query_add_clause only if password is being updated
    $query_add_clause = "";
    if (!(empty($passwd) && empty($repasswd)))
        {
        //call password function at top
        check_password($passwd, $repasswd, $arr_error);
        //will not be used upon error
        $salt = md5(microtime());    
        $query_add_clause = ", hash = '" . hash('sha512', $passwd . $salt) . "', salt = '" . $salt . "'";
        }   
        
    check_is_empty($fname, "fname", $arr_error, "Firstname cannot be empty");
    check_is_empty($lname, "lname", $arr_error, "Lastname cannot be empty");
    
    //do the update   
    if (empty($arr_error))
        {
		$where_not_exists = "SELECT 1 from users_table WHERE  id <> " .  pg_escape_string($id) . " AND  email = '" .  pg_escape_string($email_work) . "'";
        $query = "UPDATE users_table " .
                 "SET email = '" .  pg_escape_string($email_work) . "', fname = '" . pg_escape_string($fname) . "', minit = '" . pg_escape_string($minit) . "', lname = '" . pg_escape_string($lname) . "', userrole = " . (int)$userrole_work . ", attempts = 0 " . $query_add_clause . " " .
                 "WHERE id = " .  pg_escape_string($id) . " AND NOT EXISTS (" . $where_not_exists . ");";
        $result = $main->query($con,$query);
        $cnt = pg_affected_rows($result);
        
        if (pg_affected_rows($result) == 0)
            {
            //only get here with good email
			array_push($arr_message, "Error: Cannot update, email duplicated or underlying data change possible.");     
            }
        else
            {
            //since action is zero, only email needs to be blanked            
            array_push($arr_message, "User " . $email_work . " information has been updated.");
            $email_work = "";
            $action = 0; //successful update
            }       
        }    
    }
/* UPDATE INFO POSTBACK */
    
/* DELETE USER */  
if ($main->post('bb_button', $module) == 3) //postback delete_user
    {
	//deletes based on email
    $action = 0; // no validation

    $query = "DELETE FROM users_table WHERE id = " .  (int)$id . ";";
    $result = $main->query($con,$query);
    $cnt = pg_affected_rows($result);
   
    if ($cnt == 0)
        {
        //only get here with good email
        array_push($arr_message, "Error: Login record not found in database.");      
        }
    else
        {
        //since action is zero, only email needs to be blanked        
        array_push($arr_message, "User " . $email_work . " has been deleted.");
        $email_work = "";
        }       
    }
/* END DELETE USER */  

/* POPULATE FORM IF DELETE OR EDIT FROM INITIAL PAGE */
//use action in this area, do bb_button = 0
//skip if delete or edit buttons are set, action either edit(2) or delete (3)
//get the specific record for edit or delete links on initial page from database to populate form 
if (in_array($action, array(2,3)) && !(in_array((int)$main->post('bb_button', $module), array(2,3))))
    {
    //edit or delete  
    $query = "SELECT * FROM users_table WHERE id IN (" . pg_escape_string($id) . ");";

    $result = $main->query($con, $query);
	
    $row = pg_fetch_array($result);
    
    if (pg_num_rows($result) == 1)
        {
        $email_work = $row['email'];
        $fname = $row['fname'];
        $minit = $row['minit'];
        $lname = $row['lname'];
        //called assign since userrole is current (admin) user
        $userrole_work = $row['userrole'];
        }
    }
        
/* HTML OUTPUT */
echo "<p class=\"spaced bold larger\">Manage Users</p>";

echo "<div class=\"padded\">";
$main->echo_messages($arr_message);
echo "</div>";
   
/* START REQUIRED FORM */    
$main->echo_form_begin();
$main->echo_module_vars($module);

/* INITIAL PAGE */
//gets the list of users for the administrator
//also has add_new_user button

if ($action == 0)
    {
    //retrieve everything      
    $query = "SELECT * FROM users_table ORDER BY lname, fname, id;";        
    $result = $main->query($con, $query);   

    echo "<button class=\"spaced\" name=\"add_new_user\" value=\"add_new_user\" onclick=\"set_hidden(0,1)\">Add New User</button><div class=\"clear_float\"></div>";   
    echo "<div class=\"spaced padded auto border thinarea\">";
    echo "<div class=\"table spaced\">";
    echo "<div class=\"row shaded\">";
    echo "<div class=\"cell padded medium middle bold\">Username/Email</div>";
    echo "<div class=\"cell padded medium middle bold\">Full Name</div>";
    echo "<div class=\"cell padded short middle bold\">Userrole</div>";
    echo "<div class=\"cell padded short\"></div>";
    echo "<div class=\"cell padded short\"></div>";
    echo "</div>";

    $i = 0;
    //iterate through all current users
    while($row = pg_fetch_array($result))
        {
        $shaded = ($i % 2) == 0 ? "even" : "odd";
        echo "<div class=\"row " . $shaded . "\">";
        $name = $main->build_name($row);
		$id = $row['id'];
        $email_work = $row['email'];
        $userrole_work = $row['userrole'];
        echo "<div class=\"cell padded medium middle\">" . htmlentities($email_work) . "</div>";
        echo "<div class=\"cell padded medium middle\">" . htmlentities($name) . "</div>";
        echo "<div class=\"cell padded short middle\">" . $array_userroles[$userrole_work] . "</div>";
        echo "<div class=\"cell padded short right middle\"><button class=\"link\" onclick=\"set_hidden(" . $id . ",2); return false;\">Edit</button></div>";
        echo "<div class=\"cell padded short right middle\"><button class=\"link\" onclick=\"set_hidden(" . $id . ",3); return false;\">Delete</button></div>";
        echo "</div>";
        $i++;
        }
    echo "</div>";
    echo "</div>";
    
    //these hidden forms are used on the initial page
    echo "<input name=\"action\" type=\"hidden\" value=\"\" />";
    echo "<input name=\"id\" type=\"hidden\" value=\"\" />";
    }   
 
if (in_array($action, array(1,2,3))):
    //add user, edit user, and delete, email work is key
    //this is the form page, button are all postback
	
	//hidden field to keep the id of record to alter
	echo "<input name=\"id\" type=\"hidden\" value=\"" . $id . "\" />";
	//for delete form output
	$readonly = ($action == 3) ?  "readonly=\"readonly\"" : "";
	
	//echo out form
    echo "<div class=\"table spaced\">";
    
    echo "<div class=\"row\">";    
    echo "<div class=\"cell middle\">Email/Username:</div>";    
    echo "<div class=\"cell middle\"><input name=\"email_work\"  class=\"spaced\" type=\"text\" size=\"50\" " . $readonly . " value=\"" . htmlentities($email_work) . "\" /></div>";
    echo "<div class=\"cell error middle\"> " . (isset($arr_error['email_work'] )? $arr_error['email_work'] : "") . "</div>";    
    echo "</div>";
     
    //passwords, md5 so never repopulate, no readonly, has error msgs
    echo "<div class=\"row\">"; 
    echo "<div class=\"cell middle\">Password:</div>";
    echo "<div class=\"cell middle\"><input name=\"passwd\"  class=\"spaced\" type=\"password\" size=\"50\" value=\"\" onKeyUp=\"checkPasswd(this.value,'passwd')\"/></div>";
    echo "<div class=\"cell error middle\"> " . (isset($arr_error['passwd'] )? $arr_error['passwd'] : "") . "</div>"; 
    echo "</div>";

    echo "<div class=\"row spaced\">"; 
    echo "<div class=\"cell middle\">Re-Enter Password:</div>";
    echo "<div class=\"cell middle\"><input name=\"repasswd\"  class=\"spaced\" type=\"password\" size=\"50\" value=\"\" onKeyUp=\"checkPasswd(this.value,'repasswd')\"/></div>";
    echo "<div class=\"cell error middle\"> " . (isset($arr_error['repasswd'] )? $arr_error['repasswd'] : "") . "</div>";    
    echo "</div>";
    
    //names, readonly for delete, has first and last name error messages for edit
    //delete will have no error msgs but is tested anyway
    echo "<div class=\"row\">"; 
    echo "<div class=\"cell middle\">First Name:</div>";
    echo "<div class=\"cell middle\"><input name=\"fname\"  class=\"spaced\" type=\"text\" size=\"50\" " . $readonly . " value=\"" . htmlentities($fname) . "\" /></div>";
    echo "<div class=\"cell error middle\"> " . (isset($arr_error['fname'] )? $arr_error['fname'] : "") . "</div>";    
    echo "</div>";
    
    echo "<div class=\"row\">"; 
    echo "<div class=\"cell middle\">Middle Initial:</div>";
    echo "<div class=\"cell middle\"><input name=\"minit\"  class=\"spaced\" type=\"text\" size=\"50\" " . $readonly . " value=\"" . htmlentities($minit) . "\" /></div>";
    echo "<div class=\"cell middle\"></div>";
    echo "</div>";
    
    echo "<div class=\"row\">"; 
    echo "<div class=\"cell middle\"\">Last Name:</div>";
    echo "<div class=\"cell middle\"><input name=\"lname\"  class=\"spaced\" type=\"text\" size=\"50\" " . $readonly . " value=\"" . htmlentities($lname) . "\" /></div>";
    echo "<div class=\"cell error middle\"> " . (isset($arr_error['lname'] )? $arr_error['lname'] : "") . "</div>";    
    echo "</div>";
 
    echo "<div class=\"row\">"; 
    echo "<div class=\"cell middle\">Role:</div>";
    echo "<div class=\"cell middle\">";
    echo "<select name = \"userrole_work\" class=\"spaced\">";    
        foreach ($array_userroles as $key => $value)
            {
            echo "<option value=\"" . $key . "\" " . ($userrole_work == $key ? "selected" : "") . ">" . $value . "</option>";
            }
    echo "</select></div>";
    echo "<div class=\"cell middle\"></div>";
    echo "</div>";
    echo "</div>";
     
    if ($action == 1)
        {
		$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>"Add User");
		$main->echo_button("add_user", $params);
        }
    if ($action == 2)
        {
		$params = array("class"=>"spaced","number"=>2,"target"=>$module, "passthis"=>true, "label"=>"Update Information");
		$main->echo_button("update_info", $params);
        }
    elseif ($action == 3)
        {
		$params = array("class"=>"spaced","number"=>3,"target"=>$module, "passthis"=>true, "label"=>"Delete User");
		$main->echo_button("delete_user", $params);
        }
    echo "<p class=\"spaced\">Password must contain numbers and uppercase, letters, length must be 8 or greater.</p>";

endif;

$main->echo_state($array_state);
$main->echo_form_end();
/* END FORM */
?>
