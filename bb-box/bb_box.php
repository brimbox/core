<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
 * Copyright (C) Kermit Will Richardson, Brimbox LLC
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
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo PAGE_TITLE; ?></title>
<?php
/* STANDARD JAVASCRIPT INCLUDE */
$arr_javascript[] = $webpath . "/bb-utilities/bb_scripts.js";
//box javascript filter
$arr_javascript = $main->filter("bb_box_javascript", $arr_javascript, $webpath);
//javascript inlcude loop
$main->include_file($arr_javascript, "js");
/* custom javascript from bb-config */
$main->include_file($webpath . "/bb-config/bb_javascript.js", "js");

/* STANDARD CSS INCLUDE */
// standard style for customization
$arr_css[] = $webpath . "/bb-utilities/bb_styles.css";
// styles for the box
$arr_css[] = $webpath . "/bb-box/bb_box.css";
//box css filter
$arr_css = $main->filter("bb_box_css", $arr_css, $webpath);
//css include loop
$main->include_file($arr_css, "css");
/* custom css  from bb-config */
$main->include_file($webpath . "/bb-config/bb_css.css", "css");

?>
</head>
<body id="bb_brimbox">
<?php
/* PROCESSING IMAGE */
if (!$main->blank($image)) {
    // seems to flush nicely without explicitly flushing the output buffer
    echo "<div id=\"bb_processor\"><img src=\"" . $image . "\"></div>";
    echo "<script>window.onload = function () { document.getElementById(\"bb_processor\").style.display = \"none\"; }</script>";
}

/* CONTROLLER IMAGE AND MESSAGE */
// echo tabs and links for each module
echo "<div id=\"bb_header\">";
// header image
$controller_image = "<img src=\"" . $webpath . "/bb-config/controller_image.gif\">";
if (!$main->blank($controller_image)) {
    echo "<div id=\"controller_image\">" . $controller_image . "</div>";
}
// global message for all users
$controller_message = "";
if (!$main->blank($controller_message)) {
    echo "<div id=\"controller_message\">" . $controller_message . "</div>";
}

/* CONTROLLER ARRAY */
// query the modules table to set up the interface
// setup initial variables from $array_interface
$arr_interface = $array_interface[$interface];

// module type 0 for hidden modules
$module_types = array(0);

// get the module types for current interface
foreach ($arr_interface as $key => $value) {
    // display appropriate modules, usertypes is array of numeric part of userroles
    if (in_array($userrole, $value['userroles'])) {
        array_push($module_types, $key);
    }
    else {
        // unset interface type if permission invalid
        unset($arr_interface[$key]);
    }
}

// get modules type into string for query
$module_types = implode(",", array_unique($module_types));
// query modules table
$query = "SELECT * FROM modules_table WHERE standard_module IN (0,1,2,4,6) AND interface IN ('" . pg_escape_string($interface) . "') " . "AND module_type IN (" . pg_escape_string($module_types) . ") ORDER BY module_type, module_order;";
// echo "<p>" . $query . "</p>";
$result = pg_query($con, $query);

// populate controller arrays
while ($row = pg_fetch_array($result)) {
    // get the first module
    // check module type not hidden
    // check that file exists
    if (file_exists($row['module_path'])) {
        // set module_path and type for include
        if ($module == $row['module_name']) {
            $path = $row['module_path'];
            $type = $row['module_type'];
        }
        // need to address controller by both module_type and module_name
        if ($row['module_type'] > 0) {
            // $array[key][key] is easiest
            $arr_controller[$row['module_type']][$row['module_name']] = array('friendly_name' => $row['friendly_name'], 'module_path' => $row['module_path']);
        }
    }
}
/* END CONTROLLER ARRAY */

/* ECHO TABS */
// set up standard tab and auxiliary header tabs
echo "<div id=\"bb_mobile_header\">";
echo "<label for=\"bb_show_menu\" id=\"bb_mobile_logo\">Brimbox</label>";
$mobile_image = "<img src=\"" . $webpath . "/bb-config/mobile_image.png\">";
echo "<label for=\"bb_show_menu\" id=\"bb_mobile_button\">" . $mobile_image . "</label>";
echo "</div>";
echo "<div id=\"bb_menu_header\">";
echo "<input type=\"checkbox\" id=\"bb_show_menu\" role=\"button\">";
echo "<ul id=\"bb_menu\" class=\"clearfix\">";
foreach ($arr_interface as $key => $value) {
    $selected = ""; // reset selected
    // active module type
    if ($key == $type) {
        $interface_type = $value['interface_type'];
    }
    // layout standard tabs
    if ($value['interface_type'] == 'Standard') {
        foreach ($arr_controller[$key] as $module_work => $value_work) {
            $selected = ($module == $module_work) ? "chosen" : "";
            $submit_form_params = "[0,'$module_work', this]";
            echo "<li><button class=\"" . $selected . "\" onclick=\"bb_submit_form(" . $submit_form_params . ")\">" . $value_work['friendly_name'] . "</button></li>";
        }
    }
    elseif ($value['interface_type'] == 'Auxiliary') {
        // this section
        if (array_key_exists($key, $arr_controller)) {
            if (array_key_exists($module, $arr_controller[$key])) {
                $selected = "chosen";
                $module_work = $module;
                $submit_form_params = "[0,'$module_work', this]";
            }
            else {
                $module_work = key($arr_controller[$key]);
                $submit_form_params = "[0,'$module_work', this]";
            }
            echo "<li><button class=\"" . $selected . "\"  onclick=\"bb_submit_form(" . $submit_form_params . ")\">" . $value['module_type_name'] . "</button></li>";
        }
    }
}
echo "</ul>";
echo "</div>";
/* END ECHO TABS */

/* LINE UNDER TABS */
// line either set under chosen tab or below all tabs and a hidden module
echo "<div id=\"bb_line\"></div>";
/* END LINE UNDER TABS */

// bb_header
echo "</div>";

/* INCLUDE APPROPRIATE MODULE */
echo "<div id=\"bb_wrapper\">";
// Auxiliary tabs and links,
if (isset($interface_type) && ($interface_type == 'Auxiliary')) {
    echo "<ul id=\"bb_admin_menu\">";
    // echo auxiliary buttons on the side
    foreach ($arr_controller[$type] as $module_work => $value) {
        $selected = ($module == $module_work) ? "chosen" : "";
        $submit_form_params = "[0,'$module_work', this]";
        echo "<li><button class=\"" . $selected . "\" name=\"" . $module_work . "_name\" value=\"" . $module_work . "_value\"  onclick=\"bb_submit_form(" . $submit_form_params . ")\">" . $value['friendly_name'] . "</button></li>";
    }
    echo "</ul>";

    // clean up before include
    unset($arr_interface, $controller_message, $interface_type, $javascript, $key, $lineclass, $module_types, $module_work, $query, $result, $row, $slug_work, $submit_form_params, $value, $type);
    // module include this is where modules are included
    echo "<div id=\"bb_admin_content\">";
    // $path is reserved, this "include" includes the current module
    // the include must be done globally, will render standard php errors
    // if it bombs it bombs, the controller should still execute
    // Auxiliary type module is included here
    if (file_exists($path)) include ($path);

    echo "</div>";
    echo "<div class=\"clear\"></div>";
} // Standard and Hidden tabs
else {
    // clean up before include
    unset($arr_interface, $controller_message, $interface_type, $javascript, $key, $lineclass, $module_types, $module_work, $query, $result, $row, $slug_work, $submit_form_params, $value, $type);
    // module include this is where modules are included
    echo "<div id=\"bb_content\">";
    // $path is reserved, this "include" includes the current module
    // the include must be done globally, will render standard php errors
    // if it bombs it bombs, the controller should still execute
    // Standard type module is included here
    if (file_exists($path)) include ($path);

    echo "</div>";
    echo "<div class=\"clear\"></div>";
}
echo "</div>"; // bb_wrapper
/* END INCLUDE MODULE */

// close connection -- make the database happy
pg_close($con);
?>
</body>
</html>