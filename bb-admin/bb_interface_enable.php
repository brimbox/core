<?php
/*
Copyright (C) 2012 - 2014  Kermit Will Richardson, Brimbox LLC

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
//it is good idea to check the permission 
$main->check_permission("bb_brimbox", array(3,4,5));

//it is necessary to retrieve the state to echo it back into the form
$main->retrieve($con, $array_state);
$arr_message = array();

$arr_header = $main->get_json($con,"bb_interface_enable");

if ($main->button(1))
    {
    $arr_header = array();
    foreach ($array_header as $key => $value)
        {
        $arr_header[$key]['interface_name']  = $value['interface_name'];
        $arr_header[$key]['userroles'] = $value['userroles'];
        $arr_header[$key]['module_types'] = $value['module_types'];
        
        foreach($value['validation'] as $key1 => &$value1)
            {
            $key2 = $key . "_" . $key1;
            if ($main->post($key2 . "_chkbx", $module, ""))
                {
                if (!$main->blank($main->post($key2 . "_inpt", $module, "")))
                    {
                    $value1['name'] = $main->custom_trim_string($main->post($key2 . "_inpt", $module, ""), 50, true, true);
                    }
                $arr_header['validation'][$key2] = $value1;
                }
            }       
        }
        
    $key2 = $main->post("guest_index", $module, "");
    $arr_header['guest_index'] = array("value"=>$array_header[$key2]['guest_index'],'interface'=>$key);
    $key2 = $main->post("row_security", $module, "");
    $arr_header['row_security'] = array("value"=>$array_header[$key2]['row_security'],'interface'=>$key);
    $key2 = $main->post("row_archive", $module, "");
    $arr_header['row_archive'] = array("value"=>$array_header[$key2]['row_archive'],'interface'=>$key);
    $key2 = $main->post("layout_security", $module, "");
    $arr_header['layout_security'] = array("value"=>$array_header[$key2]['layout_security'],'interface'=>$key);
    $key2 = $main->post("column_security", $module, "");
    $arr_header['column_security'] = array("value"=>$array_header[$key2]['column_security'],'interface'=>$key);

    $main->update_json($con, $arr_header, "bb_interface_enable");
    }
    
echo "<p class=\"spaced bold larger\">Interface Enabler</p>";

echo "<div class=\"spaced\">";
$main->echo_messages($arr_message);
echo "</div>";

/*** BEGIN FORM ***/
$main->echo_form_begin(); 
$main->echo_module_vars($module);


//SUBMIT
$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>"Submit Interface");
$main->echo_button("interface_button", $params);

//OUTER LOOP
foreach ($array_header as $key => $value)
    {
    echo "<div class=\"larger spaced bold\">" . $value['interface_name'] . "</div>";
    echo "<div class=\"table spaced border\">";
    echo "<div class=\"row\">";
    
    //DIPLAY USERROLES AND MODULE TYPES
    echo "<div class=\"cell padded\">";    
    echo "<div class=\"bold spaced\">User Roles</div>";
    foreach ($value['userroles'] as $key1 => $value1)
        {
        echo "<div class=\"padded spaced\"><span class=\"colored\">Key: </span>" . $key1 . "<span class=\"colored\"> -> Name: </span>" . $value1 . "</div>";   
        }    
    echo "</div>";
    
    echo "<div class=\"cell padded borderleft\">";
    echo "<div class=\"bold spaced\">Module Types</div>";
    foreach ($value['module_types'] as $key1 => $value1)
        {
        echo "<div class=\"padded spaced\"><span class=\"colored\">Key: </span>" . $key1 . "<span class=\"colored\"> -> Name: </span>" . $value1 . "</div>";   
        }    
    echo "</div>";
    
    //VALIDATION FUNCTION AREA
    echo "<div class=\"cell padded borderleft\">";
    echo "<div class=\"bold spaced\">Validation</div>";
    foreach ($value['validation'] as $key1 => $value1)
        {
        $key2 = $key . "_" . $key1;
        //set up postback values
        if (isset($arr_header['validation'][$key2]))
            {
            $checked = true;
            $name = ($value1['name'] == $arr_header['validation'][$key2]['name']) ? "" : $arr_header['validation'][$key2]['name'];
            }
        else
            {
            $checked = false;
            $name = "";    
            }
        //echo the line
        if ($key2 == "bb_brimbox_text")
            {
            echo "<div class=\"padded spaced\"><span class=\"colored padded middle spaced\">Default Text -> Does Nothing</span>";
            $main->echo_input($key2 . "_chkbx", 1, array('type'=>"hidden"));
            echo "</div>";               
            }
        else
            {
            echo "<div class=\"padded spaced\"><span class=\"colored padded middle spaced\">";
            $main->echo_input($key2 . "_chkbx", 1, array('type'=>"checkbox",'input_class'=>"holderup middle",'checked'=>$checked));
            echo "<span class=\"colored spaced\"> Key: </span>" . $key1 . "<span class=\"colored spaced\"> -> Name: </span>" . $value1['name'] . "<span class=\"colored spaced\"> - Rename: </span>";
            $main->echo_input($key2 . "_inpt", $name, array('type'=>"text",'input_class'=>"middle spaced"));
            echo "</div>";      
            }
        }    
    echo "</div>";
    
    //GUEST INDEX
    echo "<div class=\"cell padded borderleft\">";
    echo "<div class=\"bold spaced\">Guest Index:";
    $checked = ($arr_header['guest_index']['interface'] == $key) ? "checked" : "";
    echo "<input class=\"holderup middle\"type=\"radio\" name=\"guest_index\" value=\"" . $key . "\" " . $checked . ">";
    echo "</div>";
    //initialize Default or Array
    if (empty($value['guest_index']))
        {
        echo "<div class=\"padded spaced\"><span class=\"colored\">Default</span></div>";
        }
    else
        {
        //array of keys
        foreach ($value['guest_index'] as $key1)
            {
            if (isset($value['column_security'][$key1]))
                {
                echo "<div class=\"padded spaced\"><span class=\"colored\">Key: </span>" . $key1 . "<span class=\"colored\"> -> Name: </span>" . $value['column_security'][$key1] . "</div>";   
                }
            else
                {
                echo "<div class=\"padded spaced\"><span class=\"colored\">Unexpected Column Key</span></div>";
                }
            }
    }     
    echo "</div>";
    echo "</div>"; //row
    
    echo "<div class=\"row\">";
    
    //ROW SECURITY
    echo "<div class=\"cell padded bordertop\">";
    echo "<div class=\"bold spaced\">Row Security:";
    $checked = ($arr_header['row_security']['interface'] == $key) ? "checked" : "";
    echo "<input class=\"holderup middle\"type=\"radio\" name=\"row_security\" value=\"" . $key . "\" " . $checked . ">";
    echo "</div>";
    //initialize Default or Array
    if (empty($value['row_security']))
        {
        echo "<div class=\"padded spaced\"><span class=\"colored\">Default</span></div>";
        }
    else
        {
        foreach ($value['row_security'] as $key1 => $value1)
            {
            echo "<div class=\"padded spaced\"><span class=\"colored\">Key: </span>" . $key1 . "<span class=\"colored\"> -> Name: </span>" . $value1 . "</div>";   
            }    
        } 
    echo "</div>";
    
    //ROW ARCHIVE
    echo "<div class=\"cell padded borderleft bordertop\">";
    echo "<div class=\"bold spaced\">Archive Levels:";
    $checked = ($arr_header['row_archive']['interface'] == $key) ? "checked" : "";
    echo "<input class=\"holderup middle\"type=\"radio\" name=\"row_archive\" value=\"" . $key . "\" " . $checked . ">";
    echo "</div>";
    //initialize Default or Disabled
    if (empty($value['row_archive']))
        {
        echo "<div class=\"padded spaced\"><span class=\"colored\">Default</span></div>";
        }
    else
        {
        foreach ($value['row_archive'] as $key1 => $value1)
            {
            echo "<div class=\"padded spaced\"><span class=\"colored\">Key: </span>" . $key1 . "<span class=\"colored\"> -> Name: </span>" . $value1 . "</div>";   
            }    
        }
    echo "</div>";

    //COLUMN SECURITY
    echo "<div class=\"cell padded borderleft bordertop\">";
    echo "<div class=\"bold spaced\">Layout Security: ";
    $checked = ($arr_header['layout_security']['interface'] == $key) ? "checked" : "";
    echo "<input class=\"holderup middle\"type=\"radio\" name=\"layout_security\" value=\"" . $key . "\" " . $checked . ">";
    echo "</div>";
    //initialize Default or Array
    if (empty($value['layout_security']))
        {
        echo "<div class=\"padded spaced\"><span class=\"colored\">Default</span></div>";
        }
    else
        {
        foreach ($value['layout_security'] as $key1 => $value1)
            {
            echo "<div class=\"padded spaced\"><span class=\"colored\">Key: </span>" . $key1 . "<span class=\"colored\"> -> Name: </span>" . $value1 . "</div>";   
            }    
        }
    echo "</div>";
 
    //LAYOUT SECURITY
    echo "<div class=\"cell padded borderleft bordertop\">";
    echo "<div class=\"bold spaced\">Column Security: ";
    $checked = ($arr_header['column_security']['interface'] == $key) ? "checked" : "";
    echo "<input class=\"holderup middle\"type=\"radio\" name=\"column_security\" value=\"" . $key . "\"  " . $checked . ">";
    echo "</div>";
    //initialize Default or Array
    if (empty($value['column_security']))
        {
        echo "<div class=\"padded spaced\"><span class=\"colored\">Default</span></div>";
        }
    else
        {
        foreach ($value['column_security'] as $key1 => $value1)
            {
            echo "<div class=\"padded spaced\"><span class=\"colored\">Key: </span>" . $key1 . "<span class=\"colored\"> -> Name: </span>" . $value1 . "</div>";   
            }    
        }
    echo "</div>";    

    echo "</div>"; //end row
    echo "</div>"; //end table
    
    echo "<br>";
    }

//echos out the state
$main->echo_state($array_state);
//form end
$main->echo_form_end();
/**** End Form ***/

/**** More Module Output ****/
?>
