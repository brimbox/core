<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
Copyright (C) 2012 - 2015  Kermit Will Richardson, Brimbox LLC

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
$main->check_permission("bb_brimbox", 5);
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
        
function populateDefault(passthis,k,v)
    {
    var select = document.getElementById("select_default");
    if (passthis.checked == true)
        {
        select.options[select.options.length] = new Option(v,k);
        }
    if (passthis.checked == false)
        {
        for(var i=0; i<select.options.length; i++)
            {        
            if(select.options[i].value == k)
                {
                select.options[i] = null;
                }
            }
        }
    }
    
function reload_on_select()
    {
    //change filter or sort
    bb_submit_form(0); 
    return false;
    }
/* END MODULE JAVASCRIPT */
</script>
<style type="text/css">
/* MODULE CSS */
select.box
    {
    display:inline;
    width:150px;
    height:150px;
    }
</style>
<?php
/* LOCAL FUNCTIONS */
//this function leaves an empty $arr_error for logic testing if no errors
function check_is_empty($value, $index, &$arr_error, $error_message)
    {
    //custom "is empty" function for this module 
    if (empty($value) && $value !== '0')
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
    
function check_ips($con, $ips_esc, &$ips, &$arr_error)
    {
    //It's not a good idea to use the database to check for errors
    //but in this case compatibility and integrity it makes sense
    //rely on postgres to check for errors in cidr list
    @$result = pg_query($con, "SELECT array_to_string('" . $ips_esc . "'::cidr[],'\n') as ips");
    if (!$result)
        {
        $arr_error['ips'] = "Invalid cidr in list.";
        }
    else
        {
        $row = pg_fetch_array($result);
        if ($row['ips'] == "0.0.0.0/0\n::/0")
            {
            $ips = "";   
            }
        else
            {
            $ips = $row['ips'];
            }
        }
    }
/* END LOCAL FUNCTIONS */
?>
<?php
/* INITIAL VALUES */
$arr_error = array(); //if set there is an error in the form data, will display inline
$arr_message = array(); //friendly action and error message

/* GET STATE AND POSTBACK */
$main->retrieve($con, $array_state);

//Note that action is for the update and delete links and add new user button in the list view
//bb_button values are used for the update, delete, and enter buttons on individual records

$action = $main->post('action', $module, 0);
$id = $main->post('id', $module, 0);
$usersort = $main->post('usersort', $module, "lname");
$filterrole = $main->post('filterrole', $module, "all");
/* END POSTBACK */

/* INITIAL VALUES */
//module uses global $array_userroles
//$userrole is a global var, so the userrole to be assigned is $userroles_work
//userroles is userroles in database
//$email is a global var, so the email that is assigned is $email_work
//email is email in database
 
//initialize for add new user
//$email_work called $email_work because of global $email
$username_work = $email_work = $passwd = $repasswd = $fname = $minit = $lname = $ips = $notes = "";
//hack for original constants file
$userrole_constant = $main->set_constant('DEFAULT_USERROLE_ASSIGN', '1_bb_brimbox');    
/* END INITIAL VALUES */

/* POSTBACK FOR NEW USER AND UPDATE USER */
// note that navigation uses both bb_button (for postback) and assign (for display) 
// add new user or edit user 
if ($main->button(1) || $main->button(2))
    {
    //if add new user on initial page is set, all of these are empty
    //if add new or update info pages are set these will be set, validate populatred values
    //input form only allows 255 chars
	$username_work = $main->custom_trim_string($main->post('username_work', $module),255);
    $email_work = $main->custom_trim_string($main->post('email_work', $module),255);
    $passwd = $main->custom_trim_string($main->post('passwd', $module), 255);
    $repasswd = $main->custom_trim_string($main->post('repasswd', $module), 255);
    $userroles_work = $main->post('userroles_work', $module, array($userrole_constant));
    $notes = $main->custom_trim_string($main->post('notes', $module), 65536, false);
    sort($userroles_work);
    $userrole_default = $main->post('userrole_default', $module, $userrole_constant);
    $arr_userrole_default = array($userrole_default);
    if ($userrole_default <> "0_bb_brimbox")
        {
        $userroles_work = array_diff($userroles_work, $arr_userrole_default);
        array_unshift($userroles_work , $userrole_default);
        }
    $fname = $main->custom_trim_string($main->post('fname', $module),255);
    $minit = $main->custom_trim_string($main->post('minit', $module),255);
    $lname = $main->custom_trim_string($main->post('lname', $module),255);
    $ips = $main->post('ips', $module);
    //split, trim and remove empty values
    $arr_ips = array_filter(array_map('trim',preg_split("/\n|\r\n?/", $ips)));
    }
/* END POSTBACK FOR NEW USER AND UPDATE USER */

/* ADD NEW USER */
if ($main->button(1)) //postback add new user
    {    
    $action = 1; //in case of validation error    
    //email work
    check_is_empty($username_work, "username_work", $arr_error, "Username cannot be empty.");    
    if (!isset($arr_error['email_work'])) //non-empty email
        {
        //username must be 5 characters or more
        if (strlen($username_work) < 5) //check for valid email
            {
            $arr_error['username_work'] = "Username must be 5 characters or longer.";     
            }   
        }        
    check_is_empty($email_work, "email_work", $arr_error, "Email cannot be empty.");    
    if (!isset($arr_error['email_work'])) //non-empty email
        {
        //check for valid email or that email matches username
        if (!filter_var($email_work, FILTER_VALIDATE_EMAIL) && ($username_work !== $email_work)) 
            {
            $arr_error['email_work'] = "Email is not valid.";     
            }   
        }   
    //password
    check_password($passwd, $repasswd, $arr_error);
    //names
    //check that they are non-empty
    check_is_empty($fname, "fname", $arr_error, "Firstname cannot be empty");
    check_is_empty($lname, "lname", $arr_error, "Lastname cannot be empty");
    //ips
    $ips_esc = empty($arr_ips) ? "{0.0.0.0/0,0:0:0:0:0:0:0:0/0}" : pg_escape_string("{" . implode(",",$arr_ips) . "}");
    check_ips($con, $ips_esc, $ips, $arr_error);
   
    //insert user
    if (empty($arr_error))
        {
        $salt = md5(microtime());
        $where_not_exists = "SELECT 1 from users_table WHERE username = '" .  pg_escape_string($username_work) . "' OR email = '" .  pg_escape_string($email_work) . "'";
        $query = "INSERT INTO users_table (username, email, hash, salt, userroles, fname, minit, lname, notes, ips) " .
                 "SELECT '" . pg_escape_string($username_work) . "', '" . pg_escape_string($email_work) . "', '" . hash('sha512', pg_escape_string($passwd) . $salt) . "', '" . $salt . "', '{" . implode(",", array_map('pg_escape_string', $userroles_work)) . "}', " .
                 "'" . pg_escape_string($fname) . "', '" . pg_escape_string($minit) . "', '" . pg_escape_string($lname) . "', '" . pg_escape_string($notes) . "', '" . $ips_esc . "' " .
                 "WHERE NOT EXISTS (" . $where_not_exists . ");";
        $result = $main->query($con,$query);
        $cnt = pg_affected_rows($result);
        
        if (pg_affected_rows($result) == 0)
            {
            //only get here with good email
            array_push($arr_message, "Error: Username \"" . $username_work . "\" or email \"" . $email_work . "\" already exists for another user.");      
            }
        else
            {
            //since action is zero, only email_work needs to be blanked
            $email_work = "";
            array_push($arr_message, "User \"" . $username_work . "\" has been added.");
            $action = 0; //success
            }       
        }
    }
/* END ADD NEW USER*/

/* UPDATE INFO */
if ($main->button(2)) //postback update
    {
	//updates based on id
    $action = 2; //in case of validation error
    check_is_empty($username_work, "username_work", $arr_error, "Username cannot be empty.");    
    if (!isset($arr_error['email_work'])) //non-empty email
        {
        //username must be 5 characters or more
        if (strlen($username_work) < 5) //check for valid email
            {
            $arr_error['username_work'] = "Username must be 5 characters or longer.";     
            }   
        }        
    check_is_empty($email_work, "email_work", $arr_error, "Email cannot be empty.");    
    if (!isset($arr_error['email_work'])) //non-empty email
        {
        //check for valid email or that email matches username
        if (!filter_var($email_work, FILTER_VALIDATE_EMAIL) && ($username_work !== $email_work)) 
            {
            $arr_error['email_work'] = "Email is not valid.";     
            }   
        } 
	
    //query_add_clause only if password is being updated
    $query_add_clause = "";
    if (!$main->blank($passwd) || !$main->blank($repasswd))
        {
        //call password function at top
        check_password($passwd, $repasswd, $arr_error);
        //will not be used upon error
        $salt = md5(microtime());    
        $query_add_clause = ", hash = '" . hash('sha512', $passwd . $salt) . "', salt = '" . $salt . "'";
        }   
    //names    
    check_is_empty($fname, "fname", $arr_error, "Firstname cannot be empty");
    check_is_empty($lname, "lname", $arr_error, "Lastname cannot be empty");
    //ips
    $ips_esc = empty($arr_ips) ? "{0.0.0.0/0,0:0:0:0:0:0:0:0/0}" : pg_escape_string("{" . implode(",",$arr_ips) . "}");
    check_ips($con, $ips_esc, $ips, $arr_error);
    
    //do the update   
    if (empty($arr_error))
        {
        $where_not_exists = "SELECT 1 from users_table WHERE id <> " .  pg_escape_string($id) . " AND (username = '" .  pg_escape_string($username_work) . "' OR email = '" .  pg_escape_string($email_work) . "')";
        $query = "UPDATE users_table " .
                 "SET username = '" .  pg_escape_string($username_work) . "', email = '" .  pg_escape_string($email_work) . "', fname = '" . pg_escape_string($fname) . "', minit = '" . pg_escape_string($minit) . "', lname = '" . pg_escape_string($lname) . "', " .
                 "userroles = '{" . implode(",", array_map('pg_escape_string', $userroles_work)) . "}', attempts = 0, notes = '" . pg_escape_string($notes) . "', ips = '" .  $ips_esc . "' " . $query_add_clause . " " .
                 "WHERE id = " .  pg_escape_string($id) . " AND NOT EXISTS (" . $where_not_exists . ");";
        $result = $main->query($con,$query);
        $cnt = pg_affected_rows($result);
        
        if (pg_affected_rows($result) == 0)
            {
            //only get here with good email
            array_push($arr_message, "Error: Cannot update, username or email duplicated or underlying data change possible.");     
            }
        else
            {
            //since action is zero, only email needs to be blanked            
            array_push($arr_message, "User \"" . $username_work . "\" information has been updated.");
            $email_work = "";
            $action = 0; //successful update
            }       
        }    
    }
/* UPDATE INFO */

/* LOCK USER */  
if ($main->button(3)) //postback delete_user
    {
    //deletes based on email
    $action = 0; // no validation

    $query = "UPDATE users_table SET userroles = '{0_bb_brimbox}' WHERE id = " .  pg_escape_string($id) . ";";
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
        array_push($arr_message, "User " . $email_work . " has been locked.");
        $email_work = "";
        }       
    }
/* END LOCK USER */ 
    
/* DELETE USER */  
if ($main->button(4)) //postback delete_user
    {
    //deletes based on email
    $action = 0; // no validation

    $query = "DELETE FROM users_table WHERE id = " .  pg_escape_string($id) . ";";
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

/* POPULATE FORM IF DELETE, EDIT OR LOCK FROM INITIAL PAGE */
//get all possible userroles from $arr_header    
if (in_array($action, array(0,1,2,3,4)))
    {
    $arr_userroles_loop = array();
    $arr_userroles_loop[] = array('interface_name'=>"Brimbox", 'interface_value'=>"bb_brimbox", 'userrole_name'=>"Locked", 'userrole_value'=>0);  
    foreach ($array_header as $key1 => $value1)
        {
        //interface info
        $arr_master_work = $array_header[$key1];
        unset($arr_master_work['userroles'][0]);        
        $interface_name = $arr_master_work['interface_name'];
        foreach ($arr_master_work['userroles'] as $key2 => $value2)
            {
            $arr_userroles_loop[] = array('interface_name'=>$interface_name, 'interface_value'=>$key1, 'userrole_name'=>$value2, 'userrole_value'=>$key2);    
            }
        }
    }
        
/* HTML OUTPUT */
echo "<p class=\"spaced bold larger\">Manage Users</p>";

echo "<div class=\"padded\">";
$main->echo_messages($arr_message);
echo "</div>";
   
/* START REQUIRED FORM */    
$main->echo_form_begin();
$main->echo_module_vars();;

/* MAIN PAGE */
//gets the list of users for the administrator
//also has add_new_user button
//action and button 0
if ($action == 0)
    {
    //retrieve everything
    $where_clause = ($filterrole == "all") ? "1 = 1" : "'" . pg_escape_string($filterrole) . "' = ANY (userroles)";
    switch($usersort)
        {
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
    $query = "SELECT id, username, email, array_to_string(userroles,',') as userroles, fname, minit, lname FROM users_table " .
             "WHERE " . $where_clause ." ORDER BY " . $order_clause . ";";
    //echo "<p>" . $query . "</p>";
    $result = $main->query($con, $query);   

    echo "<table class=\"spaced\" cellpadding=\"0\" cellspacing=\"0\">";
    echo "<thead><tr colspan=\"6\"><td>";
    echo "<button class=\"floatleft spaced\" name=\"add_new_user\" onclick=\"set_hidden(0,1)\">Add New User</button>";
    echo "<table class=\"floatright\" cellpadding=\"0\" cellspacing=\"0\"><tr><td class=\"padded middle\">";
    echo "<span>Filter By: </span>";
    echo "<select class=\"middle\" name=\"filterrole\" onChange=\"reload_on_select()\">";
    echo "<option value=\"all\">All</option>";
    foreach ($arr_userroles_loop as $arr_userrole_loop)
        {
        $key = $arr_userrole_loop['userrole_value'] . "_" . $arr_userrole_loop['interface_value'];
        $value = $arr_userrole_loop['interface_name'] . ":" . $arr_userrole_loop['userrole_name'];
        $selected = ($filterrole == $key) ? "selected" : "";
        echo "<option value=\"" . $key . "\" " . $selected . ">" . $value . "</option>";  
        }
    echo "</select>";
    echo "</td><td class=\"padded middle\">";
    echo "<span class=\"middle spaced padded\">&nbsp;&nbsp;&nbsp;</span>";
    echo "<span class=\"middle spaced padded\">Sort By: </span>";
    echo "<select class=\"middle\" name=\"usersort\" onChange=\"reload_on_select()\">";
    $arr_usersort = array('lname'=>"Last Name", 'fname'=>"First Name", 'username'=>"username", 'email'=>"Email");
    foreach ($arr_usersort as $key => $value)
        {
        $selected = ($usersort == $key) ? "selected" : "";
        echo "<option value=\"" . $key . "\" " . $selected . ">" . $value . "&nbsp;</option>";  
        }
    echo "</select>";
    echo "</td></tr></table>";
    echo "</td></tr></thead>";
    
    echo "<tbody class=\"spaced block border\">";
    echo "<tr class=\"shaded\">";
    echo "<td class=\"padded medium middle bold\">Username</td>";
    echo "<td class=\"padded medium middle bold\">Email</td>";
    echo "<td class=\"padded medium middle bold\">Full Name</td>";
    echo "<td class=\"padded short middle bold\">Userrole</td>";
    echo "<td class=\"padded\"></td>";
    echo "<td class=\"padded\"></td>";
    echo "<td class=\"padded\"></td>";
    echo "</tr>";

    $i = 0;
    //iterate through all current users
    while($row = pg_fetch_array($result))
        {
        $shaded = ($i % 2) == 0 ? "even" : "odd";
        echo "<tr class=\"" . $shaded . "\">";
        $name = $main->build_name($row);
        $id = $row['id'];
        $username_work = $row['username'];
        $email_work = $row['email'];
        $userroles_work = explode(",",$row['userroles']);
        echo "<td class=\"padded medium middle\">" . htmlentities($username_work) . "</td>";
        echo "<td class=\"padded medium middle\">" . htmlentities($email_work) . "</td>";
        echo "<td class=\"padded medium middle\">" . htmlentities($name) . "</td>";
        $arr_display = array();
        echo "<td class=\"padded long middle\">";
        foreach ($userroles_work as $value)
            {
            $arr_explode = explode("_" ,$value, 2);
            $str_interface = isset($array_header[$arr_explode[1]]['interface_name']) ? $array_header[$arr_explode[1]]['interface_name'] : "Undefined";
            $str_userrole = isset($array_header[$arr_explode[1]]['userroles'][$arr_explode[0]]) ? $array_header[$arr_explode[1]]['userroles'][$arr_explode[0]] : "User";
            $str_name =  $str_interface . ":" . $str_userrole;   
            array_push($arr_display, $str_name);
            }
        echo implode(", ", $arr_display);
        echo "</td>";
        echo "<td class=\"shorter padded right middle\"><button class=\"link\" onclick=\"set_hidden(" . $id . ",2); return false;\">Edit</button></td>";
        echo "<td class=\"shorter padded right middle\"><button class=\"link\" onclick=\"set_hidden(" . $id . ",3); return false;\">Lock</button></td>";        
        echo "<td class=\"shorter padded right middle\"><button class=\"link\" onclick=\"set_hidden(" . $id . ",4); return false;\">Delete</button></td>";
        echo "</tr>";
        $i++;
        }
    echo "</tbody></table>";
    
    //these hidden forms are used on the initial page
    echo "<input name=\"action\" type=\"hidden\" value=\"\" />";
    echo "<input name=\"id\" type=\"hidden\" value=\"\" />";
    }
/* END MAIN */

/* ADD, EDIT, DELETE and LOCK */ 
if (in_array($action, array(1,2,3,4))):
    //get the specific record for edit, delete, or lock, not on postback
    if (in_array($action, array(2,3,4)))
        {
        //edit or delete  
        $query = "SELECT id, username, email, array_to_string(userroles,',') as userroles, fname, minit, lname, notes, array_to_string(ips,'\n') as ips FROM users_table WHERE id IN (" . pg_escape_string($id) . ");";
    
        $result = $main->query($con, $query);
        
        $row = pg_fetch_array($result);
        
        if (pg_num_rows($result) == 1)
            {
            $username_work = $row['username'];
            $email_work = $row['email'];
            $fname = $row['fname'];
            $minit = $row['minit'];
            $lname = $row['lname'];
            $notes = $row['notes'];
            $ips = $row['ips'] == "0.0.0.0/0\n::/0" ? "" : $row['ips']; 
            //called assign since userrole is current (admin) user
            $userroles_work = explode(",",$row['userroles']);
            }
        }

    //add user, edit user, and delete, email work is key
    //this is the form page, button are all postback
	
    //hidden field to keep the id of record to alter
    echo "<input name=\"id\" type=\"hidden\" value=\"" . $id . "\" />";
    //for delete form output
    $readonly = in_array($action, array(3,4)) ?  "readonly=\"readonly\"" : "";
    
    //echo out form
    echo "<div class=\"table spaced\">";
    
    echo "<div class=\"row\">";    
    echo "<div class=\"cell middle\">Username:</div>";    
    echo "<div class=\"cell middle\">";
    $main->echo_input("username_work", htmlentities($username_work), array('input_class'=>'spaced long','readonly'=>$readonly,'maxlength'=>255));
    echo "</div>";
    echo "<div class=\"cell error middle\"> " . (isset($arr_error['username_work'] )? $arr_error['username_work'] : "") . "</div>";    
    echo "</div>";
    
    echo "<div class=\"row\">";    
    echo "<div class=\"cell middle\">Email:</div>";    
    echo "<div class=\"cell middle\">";
    $main->echo_input("email_work", htmlentities($email_work), array('input_class'=>'spaced long','readonly'=>$readonly,'maxlength'=>255));
    echo "</div>";
    echo "<div class=\"cell error middle\"> " . (isset($arr_error['email_work'] )? $arr_error['email_work'] : "") . "</div>";    
    echo "</div>";
     
    //passwords, md5 so never repopulate, no readonly, has error msgs
    echo "<div class=\"row\">"; 
    echo "<div class=\"cell middle\">Password:</div>";
    echo "<div class=\"cell middle\">";
    $handler = "onKeyUp=\"checkPasswd(this.value,'passwd')\"";
    $main->echo_input("passwd", "", array('type'=>'password','input_class'=>'spaced long','handler'=>$handler,'maxlength'=>255));
    echo "</div>";
    echo "<div class=\"cell error middle\"> " . (isset($arr_error['passwd'] )? $arr_error['passwd'] : "") . "</div>"; 
    echo "</div>";

    echo "<div class=\"row spaced\">"; 
    echo "<div class=\"cell middle\">Re-Enter Password:</div>";
    echo "<div class=\"cell middle\">";
    $handler = "onKeyUp=\"checkPasswd(this.value,'repasswd')\"";
    $main->echo_input("repasswd", "", array('type'=>'password','input_class'=>'spaced long','handler'=>$handler,'maxlength'=>255));
    echo "</div>";
    echo "<div class=\"cell error middle\"> " . (isset($arr_error['repasswd'] )? $arr_error['repasswd'] : "") . "</div>";    
    echo "</div>";
    
    //names, readonly for delete, has first and last name error messages for edit
    //delete will have no error msgs but is tested anyway
    echo "<div class=\"row\">"; 
    echo "<div class=\"cell middle\">First Name:</div>";
    echo "<div class=\"cell middle\">";
    $main->echo_input("fname", htmlentities($fname), array('input_class'=>'spaced long','readonly'=>$readonly,'maxlength'=>255));
    echo "</div>";
    echo "<div class=\"cell error middle\"> " . (isset($arr_error['fname'] )? $arr_error['fname'] : "") . "</div>";    
    echo "</div>";
    
    echo "<div class=\"row\">"; 
    echo "<div class=\"cell middle\">Middle Initial:</div>";
    echo "<div class=\"cell middle\">";
    $main->echo_input("minit", htmlentities($minit), array('input_class'=>'spaced long','readonly'=>$readonly,'maxlength'=>255));
    echo "</div>";
    echo "<div class=\"cell middle\"></div>";
    echo "</div>";
    
    echo "<div class=\"row\">"; 
    echo "<div class=\"cell middle\"\">Last Name:</div>";
    echo "<div class=\"cell middle\">";
    $main->echo_input("lname", htmlentities($lname), array('input_class'=>'spaced long','readonly'=>$readonly,'maxlength'=>255));
    echo "</div>";
    echo "<div class=\"cell error middle\"> " . (isset($arr_error['lname'] )? $arr_error['lname'] : "") . "</div>";    
    echo "</div>";
    
    //lock and delete
    if (in_array($action, array(3,4)))
        {    
        //display roles
        echo "<div class=\"row\">"; 
        echo "<div class=\"cell middle\"\">Roles:</div>";
        echo "<div class=\"cell middle\">";
        echo "<div class=\"padded spaced border\">";
        $arr_display = array();
        foreach ($userroles_work as $value)
            {
            $arr_explode = explode("_" ,$value, 2);
            $str_interface = isset($array_header[$arr_explode[1]]['interface_name']) ? $array_header[$arr_explode[1]]['interface_name'] : "Undefined";
            $str_userrole = isset($array_header[$arr_explode[1]]['userroles'][$arr_explode[0]]) ? $array_header[$arr_explode[1]]['userroles'][$arr_explode[0]] : "User";
            $str_name =  $str_interface . ": " . $str_userrole;   
            array_push($arr_display, $str_name);
            }
        echo implode("<br>", $arr_display);
        echo "</div></div>";
        echo "<div class=\"cell middle\"></div>";    
        echo "</div>";
        echo "<div class=\"row\">";
        echo "<div class=\"cell middle\"\">IPs (cidr):</div>";
        $ips_display = ($ips == "") ? "&nbsp" :  str_replace("\n", "<br>", $ips);
        echo "<div class=\"padded spaced border\">" . $ips_display . "</div>";
        echo "<div class=\"cell middle\"></div>";
        echo "</div>";        
        echo "<div class=\"row\">";
        echo "<div class=\"cell middle\"\">Notes:</div>";
        $notes_display = ($notes == "") ? "&nbsp" :  str_replace("\n", "<br>", htmlentities($notes));
        echo "<div class=\"padded spaced border\">" . $notes_display . "</div>";
        echo "<div class=\"cell middle\"></div>";
        echo "</div>";        
        }
               
    //add or edit user
    if (in_array($action, array(1,2)))
        {    
        //default role select
        echo "<div class=\"row\">"; 
        echo "<div class=\"cell middle\"\">Default Role:</div>";
        echo "<div class=\"cell middle\"><select name=\"userrole_default\" id=\"select_default\"  class=\"spaced\" />";
        //if add user
        if (in_array($action, array(1)))
            {
            $userroles_work = array($userrole_constant);    
            }
        foreach ($userroles_work as $value)
            {
            $arr_explode = explode("_" ,$value, 2);
            if (isset($array_header[$arr_explode[1]]['interface_name']) && isset($array_header[$arr_explode[1]]['userroles']))
                {
                $str_interface = $array_header[$arr_explode[1]]['interface_name'];
                $str_userrole =  $array_header[$arr_explode[1]]['userroles'][$arr_explode[0]];
                $str_name =  $str_interface . ": " . $str_userrole;   
                $selected = ($value == $userroles_work[0]) ? "selected" : "";
                echo "<option value=\"" . $value . "\" " . $selected . ">" . $str_name . "&nbsp;</option>";
                }
            }    
        echo "</select></div>";
        echo "<div class=\"cell error middle\"></div>";    
        echo "</div>";
 
        //userroles select 
        echo "<div class=\"row\">"; 
        echo "<div class=\"cell middle\">Roles:</div>";
        echo "<div class=\"cell middle\">";
        echo "<div class=\"spaced border padded\">";          
        //unset locked value
        unset($arr_userroles_loop[0]);
        foreach ($arr_userroles_loop as $value)
            {
            $userrole_value = $value['userrole_value'] . "_" . $value['interface_value'];
            $userrole_name = $value['interface_name'] . ":" . $value['userrole_name'];
            $checked = in_array($userrole_value, $userroles_work) ? "checked" : "";
            $handler = "onClick=\"populateDefault(this, '" . $userrole_value . "', '" . $userrole_name . " ')\"";
            echo "<div class=\"padded\">";
            //custom checkbox because of array
            echo "<input type=\"checkbox\" value=\"" . $userrole_value . "\" name=\"userroles_work[]\" class=\"middle holderup\" " . $checked . " " . $handler . "/>";
            echo "<span class=\"middle padded\">" . $userrole_name . "</span>";
            echo "</div>";
            }
        echo "</div>";
        echo "</div>";
        echo "<div class=\"cell middle\"></div>"; 
        echo "</div>";
        
        //id addresses textarea
        echo "<div class=\"row\">";
        echo "<div class=\"cell middle\">IP Addresses or Ranges (cidr):</div>";
        echo "<div class=\"cell\">";
        $main->echo_textarea("ips", $ips, array('class'=>"spaced",'rows'=>7,'cols'=>27));
        echo "</div>";
        echo "<div class=\"cell error middle\"> " . (isset($arr_error['ips'] )? $arr_error['ips'] : "") . "</div>";          
        echo "</div>";
        
        echo "<div class=\"row\">";
        echo "<div class=\"cell middle\">Notes (cidr):</div>";
        echo "<div class=\"cell\">";
        $main->echo_textarea("notes", $notes, array('class'=>"spaced",'rows'=>8,'cols'=>35));
        echo "</div>";
        echo "<div class=\"cell middle\"></div>";          
        echo "</div>";  
        }
    
    echo "</div>"; //end table
    
    //buttons
    if ($action == 1)
        {
        $params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>"Add User");
        $main->echo_button("add_user", $params);
        }
    elseif ($action == 2)
        {
        $params = array("class"=>"spaced","number"=>2,"target"=>$module, "passthis"=>true, "label"=>"Update Information");
        $main->echo_button("update_info", $params);
        }
    elseif ($action == 3)
        {
        $params = array("class"=>"spaced","number"=>3,"target"=>$module, "passthis"=>true, "label"=>"Lock User");
        $main->echo_button("lock_user", $params);
        }
    elseif ($action == 4)
        {
        $params = array("class"=>"spaced","number"=>4,"target"=>$module, "passthis"=>true, "label"=>"Delete User");
        $main->echo_button("delete_user", $params);
        }
    echo "<p class=\"spaced\">Password must contain numbers, uppercase and lowercase letters, and length must be 8 or greater.</p>";

endif;

$main->echo_state($array_state);
$main->echo_form_end();
/* END FORM */
?>
