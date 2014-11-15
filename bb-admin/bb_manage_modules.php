<?php if (!defined('BASE_CHECK')) exit(); ?>
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
$main->check_permission("bb_brimbox", 5);
?>
<script type="text/javascript">
//this is the submit javascript for standard modules
//sets hidden vars
function module_links(i,a)
    {
    //form names in javascript are hard coded
    var frmobj = document.forms["bb_form"];
    
    //module action, -2 => delete, -1 => details, 0 => nothing, 1,2,3,4 => activate/deactivate,  
    frmobj.module_id.value = i;
    frmobj.module_action.value = a;
    //for postback is 0
    bb_submit_form();
    }

</script>
<?php
include("bb_manage_modules_extra.php");
$arr_message = array();

/* PRESERVE STATE */
//retrieve state after updating database, all state for this module in database
//$main->retrieve called at beginning of form
$main->retrieve($con, $array_state);
//state not preserved for this module
$module_id = $main->post('module_id', $module, 0);
$module_action = $main->post('module_action', $module, 0);

/* END MODULE VARIABLES FOR OPTIONAL MODULE HEADERS */

//* uses global $array_userroles *//
//* uses global $array_module_types *//

//if looking at details of module -- there is an empty test
$arr_details = array();

//used in several routines, in
$arr_maintain_state = array(-1=>"Code",0=>'No',1=>'Yes');

//call helper class
$manage = new bb_manage_modules();

/* ACTIVATE, DEACTIVATE, DELETE, DETAILS MODULES */
//this area handles the links in the table
if ($module_action <> 0)
	{
    /* ACTIVATE DEACTIVATE */
    if (in_array($module_action, array(3,4,5,6)))
        {    
        if (($module_action == 4) || ($module_action == 6))
            {
            //deactivate standard modules, standard_module = 1 
            $query = "UPDATE modules_table SET standard_module = " . ($module_action - 1) . " WHERE id = " . $module_id . ";";    
            $message_temp = "Module has been deactivated.";
            }
        elseif (($module_action == 3) || ($module_action == 5))
            {
            //activate standard modules, standard_module = 2 
            $query = "UPDATE modules_table SET standard_module = " . ($module_action + 1) . " WHERE id = " . $module_id . ";";    
            $message_temp = "Module has been activated.";
            }
        //echo "<p>" . $query . "</p>";
        $result = $main->query($con, $query);
        
        if (pg_affected_rows($result) == 0) //will do nothing if error
            {
            $arr_message[] = "Error: No changes have been made.";    
            }
        else
            {
            $arr_message[] = $message_temp;       
            }
        }    
    /* DELETE MODULE */  
    if ($module_action == -2)
        {
        //delete by id
        $query = "DELETE FROM modules_table WHERE id = " . $module_id . " RETURNING module_name;";
        $result = $main->query($con, $query);
        //should return one row
        if (pg_affected_rows($result) == 1)
            {
            $row = pg_fetch_array($result);
            //lookup should start with module name
            $query = "DELETE FROM json_table WHERE lookup LIKE '" . $row['module_name'] . "%'";
            $main->query($con, $query);
             
            //reorder modules without deleted module    
            $query = "UPDATE modules_table SET module_order = T1.order " .
                    "FROM (SELECT row_number() OVER (PARTITION BY interface, module_type ORDER BY module_order) " .
                    "as order, id FROM modules_table) T1 " .
                    "WHERE modules_table.id = T1.id;";
            $main->query($con, $query);
            
            $arr_message[] = "Module has been deleted.";
            }
        else
            {
            $arr_message[] = "Error: No changes have been made."; 
            }
            
        //reorder modules without deleted module
        }        
    /* MODULE DETAILS */
    if ($module_action == -1)
        {
        $query = "SELECT module_details FROM modules_table WHERE id = " . $module_id . ";";
        $result = $main->query($con, $query);
        if (pg_num_rows($result) == 1) //get details
            {
            $row = pg_fetch_array($result);
            $arr_details = json_decode($row['module_details'], true);
            }
        }
    }
/* END ACTIVATE, DEACTIVATE MODULES */

/* BEGIN UPDATE PROGRAM */
$valid_password = $main->validate_password($con, $main->post("install_passwd", $module), "5_bb_brimbox");
if (!$valid_password)
    {
    //bad password
    array_push($arr_message, "Invalid Password.");	
    }
else
    {
    //good password
    if ($main->button(1)) //submit_update
        {
        $main->empty_directory("bb-temp/");
        
        //upload zip file to temp directory
        if (!empty($_FILES[$main->name('update_file', $module)]["tmp_name"]))
            {
            if (substr($_FILES[$main->name('update_file', $module)]["name"],0,14) == "Brimbox_Update")
                {
                $zip = new ZipArchive;
                $res = $zip->open($_FILES[$main->name('update_file', $module)]["tmp_name"]);
                if ($res === true)
                    {
                    $zip->extractTo('bb-temp/');
                    $zip->close();
                    $main->copy_directory("bb-temp/update/", "");                
                    include("bb-utilities/bb_update.php");
                    }
                 else
                    {
                    array_push($arr_message, "Error: Unable to open zip archive.");
                    }
                }
            else
                {
                $arr_message[] = "Error: Does not appear to be a Brimbox update.";    
                }
            }
        else
            {
            $arr_message[] = "Error: Must specify update file name.";
            }
        $main->empty_directory("bb-temp/", "bb-temp/");
        }
    /* END UPDATE PROGRAM */
    
    
    /* BEGIN INSTALL OPTIONAL MODULES */
    if ($main->button(2)) //submit_module
        {                    
        //empty temp directory	
        $main->empty_directory("bb-temp/");
        //upload zip file to temp directory
        if (!empty($_FILES[$main->name('module_file', $module)]["tmp_name"]))
            {
            $zip = new ZipArchive;
            $res = $zip->open($_FILES[$main->name('module_file', $module)]["tmp_name"]);
            if ($res === true)
                {
                $zip->extractTo('bb-temp/');
                $zip->close();
                }
             else
                {
                $arr_message[] = "Error: Unable to open zip archive.";
                }
            }
        else
            {
            $arr_message[] = "Error: Must specify module file name.";
            }
           
        //process header with extra class $manage
        if (!count($arr_message))
            {
            $arr_paths = $main->get_directory_tree("bb-temp/");
            foreach ($arr_paths as $path)
                {
                $arr_module = array();
                $pattern = "/\.php$/";
                //check for php file, then check to look for header
                if (preg_match($pattern, $path))
                    {
                    //check PHP files for header, can be multiple headers
                    //$arr_module passed as a reference
                    $arr_module['@module_path'] = $path;
                    //call bb_manage_modules object
                    $message_temp = $manage->get_modules($con, $arr_module);
                    //check for errors
                    if (!$main->blank($message_temp))
                        {
                        $arr_message[] = $message_temp;
                        }
                    //populate if module_name is set, ignore included 
                    elseif (isset($arr_module['@module_name']))
                        {
                        $arr_modules[] = $arr_module;
                        }					
                    }
                }            
            }        
        //no error continue
        if (!count($arr_message)) //!$message
            {
            //this does insert with not exists lookup in insert cases
            $query = "SELECT module_name from modules_table;";
            $result = $main->query($con, $query);
            $arr_module_names = pg_fetch_all_columns($result);
            foreach($arr_modules as $arr_module)
                {            
                //to reinstall xml you must delete the plugin                   
                $arr_module['@module_path'] = $main->replace_root($arr_module['@module_path'], "bb-temp/", "bb-modules/");
                            
                //insert json
                $arr_insert = array();
                $pattern_1 = "/^@json-.*/";
                foreach ($arr_module as $key => $value)
                    {
                    if (preg_match($pattern_1, $key))
                        { 
                        $arr_insert[] = "INSERT INTO json_table (lookup, jsondata) SELECT '" . pg_escape_string(substr($key,6)) . "' as lookup, '" . pg_escape_string($value) . "' WHERE NOT EXISTS (SELECT 1 FROM json_table WHERE lookup IN ('" . substr($key,6) ."'));";               
                        }
                    }
                //should not have excessive json queries
                foreach ($arr_insert as $value)
                    {
                    $main->query($con, $value);
                    }
                
                //optional hidden or optional regular    
                $standard_module = ($arr_module['@module_type'] == 0) ? 1 : 3;
                //headers, functions and globals start activated
                $standard_module = in_array($arr_module['@module_type'], array(-1,-2,-3)) ? 4 : $standard_module;
    
                //Update module
                if (in_array($arr_module['@module_name'], $arr_module_names))
                    {
                    //compensate when module is moved from one module type to another
                    $module_order = "(SELECT CASE WHEN module_type <> " . $arr_module['@module_type'] . " OR interface <> '" . $arr_module['@module_type'] . "' THEN max(module_order) + 1 ELSE module_order END FROM modules_table " .
                                    "WHERE module_name = '" . $arr_module['@module_name'] . "' GROUP BY interface, module_type, module_order)";    
                    $update_clause = "UPDATE modules_table SET module_order = " . $module_order . ", module_path = '" . pg_escape_string($arr_module['@module_path']) . "',friendly_name = '" . pg_escape_string($arr_module['@friendly_name']) . "', " .
                                     "interface = '" . pg_escape_string($arr_module['@interface']) . "', module_type = " . $arr_module['@module_type'] . ", module_version = '" . pg_escape_string($arr_module['@module_version']) . "', standard_module = " . $standard_module . ", " .
                                     "maintain_state = " . $arr_module['@maintain_state'] . ", module_files = '" . pg_escape_string($arr_module['@module_files']) . "', module_details = '" . pg_escape_string($arr_module['@module_details']) . "' "; 
                    $where_clause =  "WHERE module_name = '" . pg_escape_string($arr_module['@module_name']) . "'";
                    $query = $update_clause . $where_clause . ";";
                    $message_temp = "Module " . $arr_module['@module_name'] . " has been updated.";
                    $result = $main->query($con, $query);                
                    //reorder modules without deleted module    
                    $query = "UPDATE modules_table SET module_order = T1.order " .
                        "FROM (SELECT row_number() OVER (PARTITION BY interface, module_type ORDER BY module_order) " .
                        "as order, id FROM modules_table) T1 " .
                        "WHERE modules_table.id = T1.id;";
                    $main->query($con, $query);
                    }
                //Install module
                else
                    {          
                    //$module_order finds next available order number
                    $module_order = "(SELECT CASE WHEN max(module_order) > 0 THEN max(module_order) + 1 ELSE 1 END FROM modules_table WHERE interface = '" . $arr_module['@interface'] . "' AND module_type = " .  $arr_module['@module_type'] . ")";        
                    //INSERT query when inserting por reinstalling module
                    $insert_clause = "(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details)";
                    $select_clause = $module_order . " as module_order, '" . pg_escape_string($arr_module['@module_path']) . "' as module_path, '" . pg_escape_string($arr_module['@module_name']) . "' as module_name, '" . pg_escape_string($arr_module['@friendly_name']) . "' as friendly_name, '" .
                                     pg_escape_string($arr_module['@interface']) . "' as interface, " . $arr_module['@module_type'] . " as module_type, '" . pg_escape_string($arr_module['@module_version']) . "' as module_version, " . $standard_module . " as standard_module, " .
                                     $arr_module['@maintain_state'] . " as maintain_state, '" . pg_escape_string($arr_module['@module_files']) . "'::xml as module_files, '" . pg_escape_string($arr_module['@module_details']) . "'::xml as module_details";
                    $query = "INSERT INTO modules_table " . $insert_clause . " " .
                               "SELECT " . $select_clause . " WHERE NOT EXISTS (SELECT 1 FROM modules_table WHERE module_name IN ('" . $arr_module['@module_name'] . "','bb_logout'));";
                    $result = $main->query($con, $query); 
                    $message_temp = "Module " . $arr_module['@module_name'] . " has been installed.";
                    }                
                 //install or update modules
                           
                //if update or insert worked
                if (pg_affected_rows($result) == 0)
                    {
                    $arr_message[] = "Error: Module " . $arr_module['@module_name'] . " information has not been installed.";                 
                    }
                else //good install or update
                    {
                    //include the globals so array_master is updated
                    if ($arr_module['@module_type'] == -3)
                        {
                        include($arr_module['@module_path']);   
                        }
                    //update message
                    $arr_message[] = $message_temp;   
                    }
                } //foreach
            //move it all over
            $main->copy_directory("bb-temp/", "bb-modules/");
            //empty temp directory
            $main->empty_directory("bb-temp/", "bb-temp/");
            }
        } //install modules
    /* END INSTALL OPTIONAL MODULES */
    
    /* BEGIN RESET ORDER */
    if ($main->button(3)) //set_module_order
        {
        $query = "SELECT id FROM modules_table ORDER BY id;";
        $result = $main->query($con, $query);
        $arr_id = pg_fetch_all_columns($result);
        //weird structure to check order integrity
        
        foreach ($arr_id as $id)
            {
            //will else if something changed
            if ($main->check('module_type_' . $id, $module))
                {
                //push on order value to $arr_check array
                list($type, $interface)= explode("-", $main->post('module_type_' . $id, $module), 2);
                $order = $main->post('order_' . $id, $module);
                //$arr_order used in constructing the query
                $arr_order[$interface][$type][$id] = $order;
                }
            else
                {
                //catch for missing id in post (vs id in table)
                $arr_message[] = "Error: There has been a change in the modules since last refresh. Order not changed.";
                break;
                }
            }
        //check for unique order values
        if (!count($arr_message))
            {        
            //all but module type hidden
            foreach ($arr_order as $key1 => $arr1)
                {
                foreach ($arr1 as $key2 => $arr2)
                    {
                    if ($key1 <> 0) //ignore hidden values and hooks
                        {
                        if (count($arr2) <> count(array_unique($arr2)))
                            {
                            $arr_message[] = "Error: There are duplicate values in the order choices.";
                            }
                        }
                    }
                }
            }
        if (!count($arr_message))
            {
            //build static query with post values
            $query_union = "";
            $union = "";
                {
                foreach ($arr_id as $id)
                    {
                    list($type, $interface)= explode("-", $main->post('module_type_' . $id, $module), 2);
                    $query_union .= $union . " SELECT " . $id . " as id, " . $arr_order[$interface][$type][$id] . " as order ";
                    $union = " UNION ";
                    }
                } 
            //this is a long complex query that will only update modules table
            //if there have been no changes to table since last post
            //if any row has been deleted or inserted there will be a id conflict
            //with modules_table and the post values and the table will not update
            $query = "UPDATE modules_table SET module_order = T1.order " .
                     "FROM (" . $query_union . ") T1 " .
                     "WHERE modules_table.id = T1.id AND EXISTS (SELECT 1 WHERE " .
                        "(SELECT count(*) FROM modules_table) = " .
                        "(SELECT count(*) FROM (SELECT id FROM modules_table) T2 " .
                        "INNER JOIN (" . $query_union . ") T3 ON T2.id = T3.id))";
            $result = $main->query($con, $query);
            
            if (pg_affected_rows($result) == 0)
                {
                $arr_message[] = "Error: Module order was not updated. There was a change in the table.";
                }
            else
                {
                $arr_message[] = "Module order has been updated.";  
                }
            }
        } // end set order
    }//end password good
/* END SET ORDER */


/* BEGIN REQUIRED FORM */
$main->echo_form_begin(array("type"=>"enctype=\"multipart/form-data\""));
$main->echo_module_vars();;

//if details is clicked bascially another page
if (!empty($arr_details)):
    
    echo "<div class=\"padded margin\">";
    foreach ($arr_details as $key => $value)
        {
        $name = ucwords(str_replace("_", " " , $key));
        if ($key <> "description")
			{
			echo "<div class=\"clear\"><label class=\"margin padded right overflow floatleft medium shaded\">" . htmlentities($name) . ":</label>";
            echo "<label class=\"margin padded left floatleft\">" . htmlentities($value) . "</label>";
			echo "</div>";
			}    
        }
    if (isset($arr_details['description']))
        {
		echo "<div class = \"clear\"><label class = \"margin padded left floatleft overflow medium shaded\">" . htmlentities($name) . ":</label>";
		echo "<div class = \"clear\"></div>";
        echo "<textarea class=\"margin\" cols=\"80\" rows=\"6\" readonly=\"readonly\">" . htmlentities($value) . "</textarea>";				
		echo "</div>";            
        }
    echo "</div>";

else:   
//get the module information, cnt used to for order update 
$query = "SELECT T1.*, T2.cnt FROM modules_table T1 " .
         "INNER JOIN (SELECT interface, module_type, count(module_type) as cnt FROM modules_table GROUP BY interface, module_type) T2 " .
         "ON T1.module_type = T2.module_type AND T1.interface = T2.interface ORDER BY T1.interface, T1.module_type, T1.module_order;";
$result = $main->query($con, $query);

echo "<p class=\"spaced bold larger\">Manage Modules</p>";

echo "<div class=\"padded\">";
$main->echo_messages($arr_message);
echo "</div>";

//update program
echo "<div class=\"spaced border padded floatleft\">";
echo "<label class=\"spaced\">Update Brimbox: </label>";
echo "<input class=\"spaced\" type=\"file\" name=\"update_file\" id=\"file\" />";
$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>"Update Brimbox");
$main->echo_button("submit_update", $params);
echo "<br>";
echo "<div class=\"spaced border padded floatleft\">";
echo "<label class=\"spaced\">Program Version: " . BRIMBOX_PROGRAM . "</label>";
echo "<label class=\"spaced\"> -- Database Version: " . BRIMBOX_DATABASE . "</label>";
echo "</div>";
echo "<div class=\"clear\"></div>";

//install module
echo "<label class=\"spaced\">Install/Update Module(s): </label>";
echo "<input class=\"spaced\" type=\"file\" name=\"module_file\" id=\"file\" />";
$params = array("class"=>"spaced","number"=>2,"target"=>$module, "passthis"=>true, "label"=>"Install Module");
$main->echo_button("submit_module", $params);
echo "<br>";

//check password
echo "<div class=\"spaced border padded floatleft\">";
echo "<div class=\"spaced floatleft\">Admin Password: ";
echo "<input class=\"spaced\" type=\"password\" name=\"install_passwd\"/></div>";
echo "</div>";

//submit order button
echo "<div class=\"holderdown padded floatright\">";
$params = array("class"=>"spaced","number"=>3,"target"=>$module, "passthis"=>true, "label"=>"Set Module Order");
$main->echo_button("set_module_order", $params);
echo "</div>";
echo "</div>";
echo "<div class=\"clear\"></div>";

/* hidden vars for javascript button link submits */
echo "<input type=\"hidden\"  name=\"module_id\" value = \"0\">";
echo "<input type=\"hidden\"  name=\"module_action\" value = \"0\">";

/* BEGIN STANDARD CONTAINER */
//could be guest on both of these
//div container with scroll bar
//table
echo "<div class=\"table spaced border\">";
    //table header
    echo "<div class=\"row shaded\">";
    echo "<div class=\"padded bold cell middle\">Path</div>";
    echo "<div class=\"padded bold cell middle\">Module Name</div>";
    echo "<div class=\"padded bold cell middle\">Friendly Name</div>";
    echo "<div class=\"padded bold cell middle\">Interface: Type</div>";
    echo "<div class=\"padded bold cell middle\">Version</div>";
    echo "<div class=\"padded bold cell middle\">Maintain State</div>";
    echo "<div class=\"padded bold cell middle\">Order</div>";
    echo "<div class=\"padded bold cell middle\">Action</div>";
    echo "<div class=\"padded bold cell middle\">Delete</div>";
    echo "<div class=\"padded bold cell middle\">Details</div>";
    echo "</div>";

    //table rows
    $i = 0;
    while($row = pg_fetch_array($result))
        {
        //Hidden, functions and globals defined permanently
        switch ($row['module_type'])
            {
            case 0:
                $module_type = "Hidden";
                break;
            case -1:
                $module_type = "Global";
                break;
            case -2:
                $module_type = "Function";
                break;
            case -3:
                $module_type = "Header";
                break;
            default:
                //user defined
                $module_type = $array_header[$row['interface']]['module_types'][$row['module_type']];
                break;
            }
        //row shading
        $shade_class = (($i % 2) == 0) ? "even" : "odd";
        echo "<div class=\"row " . $shade_class . "\">";
        echo "<div class=\"twice cell long middle\">" . $row['module_path'] . "</div>";
        echo "<div class=\"twice cell medium middle\">" . $row['module_name'] . "</div>";
        echo "<div class=\"twice cell long middle\">" . $row['friendly_name'] . "</div>";
        //combine interface and module type
        echo "<div class=\"twice cell long middle\">" . $array_header[$row['interface']]['interface_name'] . ": " . $module_type . "</div>";
        echo "<div class=\"twice cell short middle\">" . $row['module_version'] . "</div>";
        echo "<div class=\"twice cell short middle\">" . $arr_maintain_state[$row['maintain_state']] . "</div>";
        //form elements
        echo "<input type=\"hidden\"  name=\"module_type_" . $row['id'] . "\" value = \"" . $row['module_type'] . "-" . $row['interface'] . "\">";
        echo "<div class=\"cell short middle\">";
        if ($row['module_type'] <> 0) //not hidden, globals and functions have an order
            {
            echo "<select name=\"order_" . $row['id'] . "\">";
            for ($j=1; $j<=$row['cnt']; $j++) //reuse j
                {
                echo "<option value=\"" . $j . "\" " . (($j == $row['module_order']) ? "selected" : "" ) . " >" . $j . "&nbsp;</option>";    
                }
            echo "</select>";
            }
        else
            {
            echo "<input name=\"order_" . $row['id'] . "\" type=\"hidden\" value=\"0\" />";
            }
         echo "</div>";
          
        //does not present a deactivate/activate link for home or guest pages
        //guest is used as a default page for $controller_path in index.php
        //there is a possibility a deactivated link can be clicked on so there needs to be a default page
        //home and guest also has the logout link so they are necessary pages
        switch ((int)$row['standard_module'])
            {
            case 0:
            case 1:
            case 2:
                $str_standard = "";
                break;
            case 3:
            case 5:
                //optional modules are always 0
                $str_standard = "<button class=\"link\" onclick=\"module_links(" . $row['id'] . "," . (int)$row['standard_module'] . ")\">Activate</button>";
                break;
            case 4:
            case 6:
                //standard modules uninstalled
                $str_standard = "<button class=\"link\" onclick=\"module_links(" . $row['id'] . "," . (int)$row['standard_module'] . ")\">Deactivate</button>";
                break;
            }       
        echo "<div class=\"padded cell short middle\">" . $str_standard . "</div>";
        
        if ((int)$row['standard_module'] == 3 || (int)$row['standard_module'] == 1)
            {
            echo "<div class=\"padded cell short middle\"><button class=\"link\" onclick=\"module_links(" . $row['id'] . ", -2)\">Delete</button></div>";
            }
        else
            {
            echo "<div class=\"padded cell short\"></div>";   
            }
       
        echo "<div class=\"padded cell short middle\"><button class=\"link\" onclick=\"module_links(" . $row['id'] . ", -1)\">Details</button></div>";    
        echo "</div>"; //end row
        $i++;
        }
echo "</div>"; //end table
/* END STANDARD CONTAINER */

endif;

$main->echo_state($array_state);
$main->echo_form_end();
/* END FORM */
?>

