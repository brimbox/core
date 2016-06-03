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
//this is the submit javascript for standard modules
//sets hidden vars
function bb_module_links(i,a)
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
include_once ("bb-utilities/bb_version.php");

// get $POST variable
$POST = $main->retrieve($con);

// get state from db
$arr_state = $main->load($con, $module);

$arr_messages = $main->process('arr_messages', $module, $arr_state, array());
$arr_details = $main->process('arr_details', $module, $arr_state, array());
unset($arr_state['arr_messages']);
unset($arr_state['arr_details']);

/* BEGIN REQUIRED FORM */
$main->echo_form_begin(array("enctype" => "multipart/form-data"));
$main->echo_module_vars();;

// if details is clicked bascially another page
if (!empty($arr_details)):

    echo "<div class=\"padded margin\">";
    foreach ($arr_details as $key => $value) {
        $name = ucwords(str_replace("_", " ", $key));
        if ($key != "description") {
            echo "<label class=\"margin padded right overflow floatleft medium shaded\">" . htmlentities($name) . ":</label>";
            echo "<label class=\"margin padded left floatleft\">" . htmlentities($value) . "</label>";
            $main->echo_clear();
        }
    }
    if (isset($arr_details['description'])) {
        echo "<label class = \"margin padded left floatleft overflow medium shaded\">" . htmlentities($name) . ":</label>";
        $main->echo_clear();
        echo "<textarea class=\"margin\" cols=\"80\" rows=\"6\" readonly=\"readonly\">" . htmlentities($value) . "</textarea>";
        $main->echo_clear();
    }
    echo "</div>";

else:
    // get the module information, cnt used to for order update
    $query = "SELECT T1.*, T2.cnt FROM modules_table T1 " . "INNER JOIN (SELECT interface, module_type, count(module_type) as cnt FROM modules_table GROUP BY interface, module_type) T2 " . "ON T1.module_type = T2.module_type AND T1.interface = T2.interface ORDER BY T1.interface, T1.module_type, T1.module_order;";
    $result = $main->query($con, $query);

    echo "<p class=\"spaced bold larger\">Manage Modules</p>";

    echo "<div class=\"padded\">";
    $main->echo_messages($arr_messages);
    echo "</div>";

    // update program
    // check password
    echo "<div class=\"cell spaced bottom border padded floatleft\">";
    echo "<div class=\"spaced border padded floatleft\">";
    echo "<label class=\"spaced\">Program Version: " . BRIMBOX_PROGRAM . "</label>";
    echo "<label class=\"spaced\"> -- Database Version: " . BRIMBOX_DATABASE . "</label>";
    echo "</div>";
    $main->echo_clear();
    // update brimbox
    echo "<input class=\"spaced\" type=\"file\" name=\"update_file\" id=\"file\" />";
    $params = array("class" => "spaced", "number" => 1, "target" => $module, "passthis" => true, "label" => "Update Brimbox / Root Access");
    $main->echo_button("submit_update", $params);
    $main->echo_clear();
    echo "<div class=\"spaced padded floatleft\">";
    echo "<div class=\"spaced floatleft\">Admin Password: ";
    echo "<input class=\"spaced\" type=\"password\" name=\"install_passwd\"/></div>";
    echo "</div>";
    $main->echo_clear();
    echo "</div>";
    $main->echo_clear();

    echo "<div class=\"cell spaced bottom border padded floatleft\">";
    // install module
    echo "<input class=\"spaced\" type=\"file\" name=\"module_file\" id=\"file\" />";
    $params = array("class" => "spaced", "number" => 2, "target" => $module, "passthis" => true, "label" => "Install / Update Module(s)");
    $main->echo_button("submit_module", $params);

    echo "<span class = \"spaced border padded rounded shaded\">";
    echo "<label class=\"padded\">Install Activated: </label>";
    $main->echo_input("install_activated", 1, array('type' => 'checkbox', 'class' => 'middle', 'checked' => true));
    echo "</span>";
    echo "</div>";

    $main->echo_clear();

    // submit order button
    echo "<div class=\"spaced border padded floatleft\">";
    echo "<div class=\"spaced padded floatright\">";
    $params = array("class" => "spaced", "number" => 3, "target" => $module, "passthis" => true, "label" => "Set Module Order");
    $main->echo_button("set_module_order", $params);
    echo "</div>";
    echo "</div>";

    // submit order button
    echo "<div class=\"spaced border padded floatleft\">";
    echo "<div class=\"spaced padded floatright\">";
    $params = array("class" => "spaced", "number" => 4, "target" => $module, "passthis" => true, "label" => "Build Custom CSS");
    $main->echo_button("build_custom_css", $params);
    echo "</div>";
    echo "</div>";
    $main->echo_clear();

    /* hidden vars for javascript button link submits */
    echo "<input type=\"hidden\"  name=\"module_id\" value = \"0\">";
    echo "<input type=\"hidden\"  name=\"module_action\" value = \"0\">";

    /* BEGIN STANDARD CONTAINER */
    // could be guest on both of these
    // div container with scroll bar
    // table
    echo "<div class=\"table spaced border\">";
    // table header
    echo "<div class=\"row shaded\">";
    echo "<div class=\"underline extra bold middle cell\">Path</div>";
    echo "<div class=\"underline extra bold middle cell\">Module Name</div>";
    echo "<div class=\"underline extra bold middle cell\">Friendly Name</div>";
    echo "<div class=\"underline extra bold middle cell\">Interface: Type</div>";
    echo "<div class=\"underline extra bold middle cell\">Version</div>";
    echo "<div class=\"underline extra bold middle cell\">Order</div>";
    echo "<div class=\"underline extra bold middle cell\">Action</div>";
    echo "<div class=\"underline extra bold middle cell\">Delete</div>";
    echo "<div class=\"underline extra bold middle cell\">Details</div>";
    echo "</div>";

    // table rows
    $i = 0;
    while ($row = pg_fetch_array($result)) {
        // Hidden, functions and globals defined permanently
        switch ($row['module_type']) {
            case 0:
                $module_type = "Hidden";
            break;
            case -1:
                $module_type = "Functions";
            break;
            default:
                // user defined
                // account for possibility of unknown or undefined
                $module_type = isset($array_interface[$row['interface']][$row['module_type']]['module_type_name']) ? $array_interface[$row['interface']][$row['module_type']]['module_type_name'] : "Unknown";
            break;
        }
        // row shading
        $shade_class = (($i % 2) == 0) ? "even" : "odd";
        echo "<div class=\"row " . $shade_class . "\">";
        echo "<div class=\"extra middle cell\">" . $row['module_path'] . "</div>";
        echo "<div class=\"extra middle cell\">" . $row['module_name'] . "</div>";
        echo "<div class=\"extra middle cell\">" . $row['friendly_name'] . "</div>";
        // combine interface and module type, account for possibility of unknown or undefined
        $interface_name = isset($array_header[$row['interface']]['name']) ? $array_header[$row['interface']]['name'] : "Unknown";
        echo "<div class=\"extra middle cell\">" . $interface_name . ": " . $module_type . "</div>";
        echo "<div class=\"extra middle cell\">" . $row['module_version'] . "</div>";
        // form elements
        echo "<input type=\"hidden\"  name=\"module_type_" . $row['id'] . "\" value = \"" . $row['module_type'] . "-" . $row['interface'] . "\">";
        echo "<div class=\"extra middle cell\">";
        // not hidden, globals and functions have an order
        if ($row['module_type'] != 0) {
            echo "<select name=\"order_" . $row['id'] . "\">";
            // reuse j
            for ($j = 1;$j <= $row['cnt'];$j++) {
                echo "<option value=\"" . $j . "\" " . (($j == $row['module_order']) ? "selected" : "") . " >" . $j . "&nbsp;</option>";
            }
            echo "</select>";
        }
        else {
            echo "<input name=\"order_" . $row['id'] . "\" type=\"hidden\" value=\"0\" />";
        }
        echo "</div>";

        // does not present a deactivate/activate link for home or guest pages
        // guest is used as a default page for $controller_path in index.php
        // there is a possibility a deactivated link can be clicked on so there needs to be a default page
        // home and guest also has the logout link so they are necessary pages
        switch (( int )$row['standard_module']) {
            case 0:
            case 1:
            case 2:
                $str_standard = "";
            break;
            case 3:
            case 5:
                // optional modules are always 0
                $str_standard = "<button class=\"link\" onclick=\"bb_module_links(" . $row['id'] . "," . ( int )$row['standard_module'] . ")\">Activate</button>";
            break;
            case 4:
            case 6:
                // standard modules uninstalled
                $str_standard = "<button class=\"link\" onclick=\"bb_module_links(" . $row['id'] . "," . ( int )$row['standard_module'] . ")\">Deactivate</button>";
            break;
        }
        echo "<div class=\"extra middle cell\">" . $str_standard . "</div>";

        if (( int )$row['standard_module'] == 3 || ( int )$row['standard_module'] == 1) {
            echo "<div class=\"extra middle cell\"><button class=\"link\" onclick=\"bb_module_links(" . $row['id'] . ", -2)\">Delete</button></div>";
        }
        else {
            echo "<div class=\"extra middle cell\"></div>";
        }

        echo "<div class=\"extra middle cell\"><button class=\"link\" onclick=\"bb_module_links(" . $row['id'] . ", -1)\">Details</button></div>";
        echo "</div>"; // end row
        $i++;
    }
    echo "</div>";
    // end table
    /* END STANDARD CONTAINER */

endif;

$main->echo_form_end();
/* END FORM */
?>

