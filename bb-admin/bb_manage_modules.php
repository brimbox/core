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
//this is the submit javascript for standard modules
//sets hidden vars
function module_links(i,a)
    {
    //form names in javascript are hard coded
    var frmobj = document.forms["bb_form"];
    
    //module action, -2 => delete, -1 => details, 0 => nothing, 1,2,3,4 => activate/deactivate,  
    frmobj.module_id.value = i;
    frmobj.module_action.value = a;
    bb_submit_form();
    }

</script>
<?php
include("bb_manage_modules_extra.php");

/* PRESERVE STATE */
//retrieve state after updating database, all state for this module in database
//$main->retrieve called at beginning of form
$arr_message = array();
$main->retrieve($con, $array_state, $userrole);
//state not preserved for this module
$module_id = $main->post('module_id', $module, 0);
$module_action = $main->post('module_action', $module, 0);

/* END MODULE VARIABLES FOR OPTIONAL MODULE HEADERS */

//* uses global $array_userroles *//
//* uses global $array_module_types *//

//if looking at details of module -- there is an empty test
$arr_details = array();

//used in several routines, in
$arr_maintain_state = array(0=>'No',1=>'Yes');
$arr_required = array("@module_name","@module_path","@friendly_name","@userrole","@module_type","@maintain_state","@module_version");
$arr_extras = array("@company","@author","@license","@description");

//make array installed
$arr_installed = array();
$query = "SELECT module_name FROM modules_table;";
$result = $main->query($con,$query);
$arr_installed = pg_fetch_all_columns($result);

//set up to pass as array
$arr_parameters = array();
//globals
$arr_parameters[0] = $array_module_types;
$arr_parameters[1] = $array_userroles;
//local to this module
$arr_parameters[2] = $arr_maintain_state;
$arr_parameters[3] = $arr_required;
$arr_parameters[4] = $arr_extras;
$arr_parameters[5] = $arr_installed;

//thses are used to put together the details routine
$arr_columns = array("module_name","module_path","friendly_name","userrole","module_type","maintain_state","module_version");
$arr_xml_node = array("company","author","license","description");

/* BEGIN RESET ORDER */
if ($main->post('bb_button', $module) == 3) //set_module_order
	{
    $query = "SELECT id FROM modules_table ORDER BY id;";
    $result = $main->query($con, $query);
    $arr_id = pg_fetch_all_columns($result);
    //weird structure to check order integrity
    $arr_check = array(0=>array(),1=>array(),2=>array(),3=>array(),4=>array(),5=>array());
    $error = false;
    
    foreach ($arr_id as $value)
        {
        if ($main->check('module_type_' . (string)$value, $module))
            {
            //push on order value to $arr_check array
            $module_type = $main->post('module_type_' . (string)$value, $module);
            $order = $main->post('order_' . (string)$value, $module);
            //$arr_order used in constructing the query
            $arr_order[$value] = $order;
            array_push($arr_check[$module_type], $order);
            }
        else
            {
            //catch for missing id in post (vs id in table)
            array_push($arr_message,"Error: There has been a change in the modules since last refresh. Order not changed.");
            $error = true;
            break;
            }
        }
    if (!$error)
        {
        //check for ascending unique order values
        //all but module type hidden
        //module types 1 to 4, skip hidden
        for ($i=1;$i<=4;$i++)
            {
            if (count($arr_check[$i]) <> count(array_unique($arr_check[$i])))
                {
                array_push($arr_message,"Error: There are duplicate values in the order choices.");
                $error = true;
                }
            }
        }
    if (!$error)
        {
        //build static query with post values
        $query_union = "";
        $union = "";
            {
            foreach ($arr_id as $value)
                {
                $query_union .= $union . " SELECT " . $value . " as id, " . $arr_order[$value] . " as order ";
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
            array_push($arr_message,"Error: Module order was not updated. There was a change in the table.");
            }
        else
            {
            array_push($arr_message,"Module order has been updated.");  
            }
        }
    } // end set order
/* END SET ORDER */


/* ACTIVATE, DEACTIVATE, DELETE, DETAILS MODULES */
//this area handles the links in the table
if ($module_action <> 0)
	{
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
            array_push($arr_message,"Error: No changes have been made.");    
            }
        else
            {
            array_push($arr_message, $message_temp);       
            }
        }
    
    /* DELETE MODULE */  
    if ($module_action == -2)
        {
        $query = "DELETE FROM modules_table WHERE id = " . $module_id . " RETURNING module_files;";
        $result = $main->query($con, $query);
        
        if (pg_affected_rows($result) == 1) //will do nothing if error
            {
            array_push($arr_message, "Module has been deleted.");
            $row = pg_fetch_array($result);
            //contains the files to delete
            $xml_delete = simplexml_load_string($row['module_files']);
            
            $arr_lookup = array();
            foreach ($xml_delete->children() as $child)
                {
                if ((string)$child->getName() == "file")
                    {
                    @unlink($child);
                    }
                elseif ((string)$child->getName() == "xml")
                    {
                    array_push($arr_lookup, "'" . (string)$child . "'");
                    }
                }                
            //delete the xml rows
            if (!empty($arr_lookup))
                {
                $in_clause = implode(",", $arr_lookup);
                $query = "DELETE FROM xml_table WHERE lookup IN (" . $in_clause . ");";
                $result = $main->query($con, $query);
                }                
            //reorder modules without deleted module    
            $query = "UPDATE modules_table SET module_order = T1.order " .
                    "FROM (SELECT row_number() OVER (PARTITION BY module_type ORDER BY module_order) " .
                    "as order, id FROM modules_table) T1 " .
                    "WHERE modules_table.id = T1.id;";
            $result = $main->query($con, $query);                 
            }
        else
            {
            array_push($arr_message,"Error: No changes have been made."); 
            }
            
        //reorder modules without deleted module
        }
        
    /* MODULE DETAILS */
    if ($module_action == -1)
        {
        $query = "SELECT * FROM modules_table WHERE id = " . $module_id . ";";
        $result = $main->query($con, $query);
        if (pg_num_rows($result) == 1) //get details
            {
            $row = pg_fetch_array($result);
            foreach ($arr_columns as $value)
                {
                $arr_details[$value]= $row[$value];
                }
            $xml_details =  simplexml_load_string($row['module_details']);

            foreach ($arr_xml_node as $value)
                {
                $arr_details[$value] = (string)$xml_details->children()->$value;;
                }
            $arr_details['module_type'] = $array_module_types[$arr_details['module_type']];
            $arr_details['userrole'] = $array_userroles[$arr_details['userrole']];
            $arr_details['maintain_state'] = $arr_maintain_state[$arr_details['maintain_state']];
            }
        }
    }
/* END ACTIVATE, DEACTIVATE MODULES */


/* ADD OPTIONAL MODULES */
if ($main->post('bb_button', $module) == 2) //submit_module
    {                    
    //empty temp directory
	
    $error = false;
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
            array_push($arr_message,"Error: Unable to open zip archive.");
            $error = true;
            }
        }
    else
        {
        array_push($arr_message, "Error: Must specify module file name.");
        $error = true;
        }
    
    $arr_module = array();        
    //check for errors
    if (!$error)
        {
        $update_flag = ($main->post('update', $module) == 1) ? true : false;
        $arr_paths = $main->get_directory_tree("bb-temp/");
        $i = 0;
        foreach ($arr_paths as $path)
            {
            $arr_module = array();
            $pattern = "/\.php$/";
            //check for php file, then check to look for header
            if (preg_match($pattern, $path))
                {
				//check PHP files for header, can be multiple headers
				//$arr_module passed as a reference
				$message = get_modules($con, $arr_module, $path, $arr_parameters, $update_flag);
				if (!empty($message))
					{
					array_push($arr_message, $message);
					$error = true;
					break;
					}
				if (isset($arr_module['@module_name']))
					{
					$arr_modules[$i] = $arr_module;
					}
				}
            $i++;
            }            
        }
    //no error continue

    if (!$error)
        {
        //move it all over
        $main->copy_directory("bb-temp/", "bb-modules/");
        //sets up an array of xml_table insert queries
        //this does insert with not exists lookup in both update and insert cases
        foreach($arr_modules as $arr_module)
            {
            if (!isset($arr_module['@phpfile']))
                {
                //to reinstall xml you must delete the plugin
                $arr_insert = array();
                       
                $arr_module['@module_path'] = $main->replace_root($arr_module['@module_path'], "bb-temp/", "bb-modules/");
                 
                if ($main->post('update', $module) == 1)
                    {
                    //update query if module is being updated
                    //standard module and module name not changed
                    $update_clause = "UPDATE modules_table SET module_path = '" . pg_escape_string($arr_module['@module_path']) . "',friendly_name = '" . pg_escape_string($arr_module['@friendly_name']) . "', " .
                                     "module_version = '" . pg_escape_string($arr_module['@module_version']) . "', module_type = " . $arr_module['@module_type'] . ", userrole = " . $arr_module['@userrole'] . ", " .
                                     "maintain_state = " . $arr_module['@maintain_state'] . ", module_files = '" . pg_escape_string($arr_module['@module_files']) . "'::xml, module_details = '" . pg_escape_string($arr_module['@module_details']) . "'::xml "; 
                    $where_clause =  "WHERE module_name = '" . pg_escape_string($arr_module['@module_name']) . "' AND standard_module NOT IN (0,2,5,6)";
                    $query = $update_clause . $where_clause . ";";
                    $message_temp = "Module " . $arr_module['@module_name'] . " has been updated.";
                    }
                else
                    { 
                    //$standard_module = 3, deactived
                    $standard_module = ($arr_module['@module_type'] == 0) ? 1 : 3;
                    foreach ($arr_module as $key => $value)
                        {
                        if (substr($key,0,5) == "@xml-")
                            {
                            array_push($arr_insert, "INSERT INTO xml_table (lookup, xmldata) SELECT '" . pg_escape_string(substr($key,5)) . "' as lookup, '" . pg_escape_string($value) . "'::xml as xmldata WHERE NOT EXISTS (SELECT 1 FROM xml_table WHERE lookup IN ('" . substr($key,5) ."'));");
                            }               
                        }   
                    //$module_order finds next available order number
                    $module_order = "(SELECT max(module_order) + 1 FROM modules_table WHERE module_type = " .  $arr_module['@module_type'] . ")";        
                    //INSERT query when inserting por reinstalling module
                    $insert_clause = "(module_order, module_path, module_name, friendly_name, module_version, module_type, userrole, standard_module, maintain_state, module_files, module_details)";
                    $select_clause = $module_order . " as module_order, '" . pg_escape_string($arr_module['@module_path']) . "' as module_path, '" . pg_escape_string($arr_module['@module_name']) . "' as module_name, '" . pg_escape_string($arr_module['@friendly_name']) . "' as friendly_name, '" .
                                     pg_escape_string($module_version) . "' as module_version, " . $arr_module['@module_type'] . " as module_type, " . $arr_module['@userrole'] . " as userrole, " . $standard_module . " as standard_module, " .
                                     $arr_module['@maintain_state'] . " as maintain_state, '" . pg_escape_string($arr_module['@module_files']) . "'::xml as module_files, '" . pg_escape_string($arr_module['@module_details']) . "'::xml as module_details";
                    $query = "INSERT INTO modules_table " . $insert_clause . " " .
                               "SELECT " . $select_clause . " WHERE NOT EXISTS (SELECT 1 FROM modules_table WHERE module_name IN ('" . $arr_module['@module_name'] . "'));";
                    $message_temp = "Module " . $arr_module['@module_name'] . " has been installed.";
                    }
                    
                //should not have excessive xml queries
                foreach ($arr_insert as $value)
                    {
                    $main->query($con, $value);
                    }
                $result = $main->query($con, $query);
                
                //if update or insert worked
                if (pg_affected_rows($result) == 0)
                    {
                    if ($main->post('update', $module) == 1)
                        {
                        array_push($arr_message, "Error: Module information in module table has not been updated. Module might not exist.");
                        }
                    else
                        {
                         array_push($arr_message, "Error: Module information has not been entered into module table. Module might already exist.");    
                        }
                    }
                else //installed or updated module
                    {
                    array_push($arr_message, $message_temp);   
                    }
                } //if not plain php file
            } //foreach        
        $main->empty_directory("bb-temp/", "bb-temp/");
        }
    } //install modules
/* END ADD OPTIONAL MODULES */

/* BEGIN UPDATE PROGRAM */
if ($main->post('bb_button', $module) == 1) //submit_update
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
            array_push($arr_message, "Error: Does not appear to be a Brimbox update.");    
            }
        }
    else
        {
        array_push($arr_message, "Error: Must specify update file name.");
        }
    $main->empty_directory("bb-temp/", "bb-temp/");
    }
/* END UPDATE PROGRAM */

/* BEGIN REQUIRED FORM */
$main->echo_form_begin(array("type"=>"enctype=\"multipart/form-data\""));
$main->echo_module_vars($module);

//if details is clicked bascially another page
if (!empty($arr_details)):
    
    echo "<div class=\"padded margin\">";
    foreach ($arr_details as $key => $value)
        {
        $name = ucwords(str_replace("_", " " , $key));
        if ($key == "description")
			{
			echo "<div class = \"clear\"><label class = \"margin padded left floatleft overflow medium shaded\">" . htmlentities($name) . ":</label>";
			echo "<div class = \"clear\"></div>";
            echo "<textarea class=\"margin\" cols=\"80\" rows=\"6\" readonly=\"readonly\">" . htmlentities($value) . "</textarea>";				
			echo "</div>";
			}		
		else
			{
			echo "<div class=\"clear\"><label class=\"margin padded right overflow floatleft medium shaded\">" . htmlentities($name) . ":</label>";
            echo "<label class=\"margin padded left floatleft\">" . htmlentities($value) . "</label>";
			echo "</div>";
			}    
        }
    echo "</div>";

else:   
//get the module information, cnt used to for order update 
$query = "SELECT T1.*, T2.cnt FROM modules_table T1 " .
         "INNER JOIN (SELECT module_type, count(module_type) as cnt FROM modules_table GROUP BY module_type) T2 " .
         "ON T1.module_type = T2.module_type ORDER BY T1.module_type, T1.module_order;";
$result = $main->query($con, $query);
$arr_installed = pg_fetch_all_columns($result,3);

$xml_version = $main->get_xml($con, "bb_manage_modules");

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
echo "<label class=\"spaced\">Program Version: " . (string)$xml_version->program . "</label>";
echo "<label class=\"spaced\"> -- Database Version: " . (string)$xml_version->database . "</label>";
echo "</div>";
echo "<div class=\"clear\"></div>";

//install module
echo "<div class=\"spaced border padded floatleft\">";
echo "<label class=\"spaced\">Install Module: </label>";
echo "<input class=\"spaced\" type=\"file\" name=\"module_file\" id=\"file\" />";
$params = array("class"=>"spaced","number"=>2,"target"=>$module, "passthis"=>true, "label"=>"Install Module");
$main->echo_button("submit_module", $params);
echo "<span class = \"border rounded padded shaded\">";
echo "<input type=\"checkbox\" class=\"middle padded\" name=\"update\" value=\"1\" />";
echo "<label class=\"padded\">Update Module</label>";
echo "</span>";
echo "</div>";
echo "<div class=\"clear\"></div>";

//submit order button
echo "<div class=\"spaced border padded floatleft\">";
$params = array("class"=>"spaced","number"=>3,"target"=>$module, "passthis"=>true, "label"=>"Set Module Order");
$main->echo_button("set_module_order", $params);
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
    echo "<div class=\"padded bold cell middle\">Version</div>";
    echo "<div class=\"padded bold cell middle\">Userrole</div>";
    echo "<div class=\"padded bold cell middle\">Type</div>";
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
        //row shading
        $shade_class = (($i % 2) == 0) ? "even" : "odd";
        echo "<div class=\"row " . $shade_class . "\">";
        echo "<div class=\"padded cell long middle\">" . $row['module_path'] . "</div>";
        echo "<div class=\"padded cell medium middle\">" . $row['module_name'] . "</div>";
        echo "<div class=\"padded cell long middle\">" . $row['friendly_name'] . "</div>";
        echo "<div class=\"padded cell short middle\">" . $row['module_version'] . "</div>";
        echo "<div class=\"padded cell short middle\">" . $array_userroles[$row['userrole']] . "</div>";
        echo "<div class=\"padded cell short middle\">" . $array_module_types[$row['module_type']] . "</div>";
        echo "<div class=\"padded cell short middle\">" . $arr_maintain_state[$row['maintain_state']] . "</div>";
        echo "<input type=\"hidden\"  name=\"module_type_" . $row['id'] . "\" value = \"" . $row['module_type'] . "\">";
        echo "<div class=\"padded cell short middle\">";
        if ($row['module_type'] <> 0)
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
            //keep rows same height
            echo "<select name=\"order_" . $row['id'] . "\">";
            echo "<option value=\"0\">0&nbsp;</option>";
            echo "</select>";
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

