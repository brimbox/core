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
<script>
function bb_reload_1()
	{
    bb_submit_form(); //call javascript submit_form function	
	}
function bb_reload_2()
	{
	var frmobj = document.forms["bb_form"];  
    frmobj.list_number_update.value = 0;
    bb_submit_form(); //call javascript submit_form function	
	}    
function bb_reload_3()
	{
	var frmobj = document.forms["bb_form"];  
    frmobj.list_number_delete.value = 0;
    bb_submit_form(); //call javascript submit_form function	
	}
</script>
<?php
$main->check_permission(array("4_bb_brimbox", "5_bb_brimbox"));

$arr_definitions = array('new' => array('keys' => array("name", "description", "archive")), 'update' => array('keys' => array("name", "description", "archive")), 'delete' => array());

/* INITIALIZE */
$arr_messages = array();

// start code here
$arr_lists_json = $main->get_json($con, "bb_create_lists");

// columns
$arr_layouts = $main->layouts($con);
$default_row_type = $main->get_default_layout($arr_layouts);

// hot state used for state
// get $POST variable
$POST = $main->retrieve($con);

// get state from db
$arr_state = $main->load($con, $module);

/* NEW LIST */
// reset on layout change
foreach ($arr_definitions as $key => $value) {
    switch ($key) {
        case "new":
            $arr_create_lists['new']['row_type'] = $main->post('row_type_new', $module, $default_row_type);
            $arr_create_lists['new']['name'] = $main->post('name_new', $module);
            $arr_create_lists['new']['description'] = $main->post('description_new', $module);
            $arr_create_lists['new']['archive'] = 0;
        break;
        case "update":
            $arr_create_lists['update']['row_type'] = $main->post('row_type_update', $module, $default_row_type);
            $arr_create_lists['update']['list_number'] = $main->post('list_number_update', $module);
            if ($arr_create_lists['update']['list_number'] > 0) {
                $arr_lists = $main->lists($con, $arr_create_lists['update']['row_type']);
                if ($main->button(2)) {
                    $arr_create_lists['update']['name'] = $main->post('name_update', $module);
                    $arr_create_lists['update']['description'] = $main->post('description_update', $module);
                    $arr_create_lists['update']['archive'] = $arr_lists[$arr_create_lists['update']['list_number']]['archive'];
                    $arr_create_lists['update']['identifier'] = chr($arr_create_lists['update']['row_type'] + 64) . $arr_create_lists['update']['list_number'];
                }
                else {
                    $arr_create_lists['update']['name'] = $arr_lists[$arr_create_lists['update']['list_number']]['name'];
                    $arr_create_lists['update']['description'] = $arr_lists[$arr_create_lists['update']['list_number']]['description'];
                    $arr_create_lists['update']['archive'] = $arr_lists[$arr_create_lists['update']['list_number']]['archive'];
                    $arr_create_lists['update']['identifier'] = chr($arr_create_lists['update']['row_type'] + 64) . $arr_create_lists['update']['list_number'];
                }
            }
            else {
                $arr_create_lists['update']['identifier'] = $arr_create_lists['update']['name'] = $arr_create_lists['update']['description'] = "";
            }
        break;
        case "delete":
            $arr_create_lists['delete']['row_type'] = $main->post('row_type_delete', $module, $default_row_type);
            $arr_create_lists['delete']['list_number'] = $main->post('list_number_delete', $module);
            $arr_create_lists['delete']['confirm_remove'] = $main->post('confirm_remove_delete', $module);
            $arr_create_lists['delete']['archive'] = $main->post('archive_delete', $module);
        break;
    }
}

// add new list
if ($main->button(1)) {
    if ($main->full('name_new', $module)) {
        // get values
        // reduced for the foreach loop
        $name = $arr_create_lists['new']['name'];
        $row_type = $arr_create_lists['new']['row_type'];
        $arr_lists = $main->lists($con, $row_type, false, false);

        // multidimensional too painful to search
        $found = false;
        foreach ($arr_lists as $value) {
            if (!strcasecmp($value['name'], $name)) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $list_number = $main->get_next_node($arr_lists, 2000); // gets next lists number, 1 to limit
            if ($list_number < 0) {
                array_push($arr_messages, "Error: Maximum number of lists exceeded.");
            }
            else {
                foreach ($arr_definitions['new']['keys'] as $value) {
                    $arr_list[$value] = $arr_create_lists['new'][$value];
                }
                $arr_lists_json[$row_type][$list_number] = $arr_list;
                $main->update_json($con, $arr_lists_json, "bb_create_lists");
                // empty list just in case
                $query = "UPDATE data_table SET list_string = bb_list_unset(list_string, " . $list_number . ") WHERE bb_list(list_string, " . $list_number . ") = 1 AND row_type IN (" . ( int )$row_type . ");";
                $main->query($con, $query);
                array_push($arr_messages, "List succesfully added.");
            }
        }
        else {
            // lists already exists, $bool = true
            array_push($arr_messages, "Error: List already exists.");
        }
    }
    else {
        array_push($arr_messages, "Error: New list name not supplied.");
    }
}

// rename or update list
if ($main->button(2)) {
    if ($arr_create_lists['update']['list_number'] > 0) {
        if ($main->full('name_update', $module)) {
            // do not reduce
            $name = $arr_create_lists['update']['name'];
            $row_type = $arr_create_lists['update']['row_type'];
            $list_number = $arr_create_lists['update']['list_number'];
            $arr_lists = $main->lists($con, $row_type, false, false);

            $found = false;
            foreach ($arr_lists as $key => $value) {
                if (!strcasecmp($value['name'], $name) && ($key != $list_number)) {
                    $found = true;
                    break;
                }
            }

            if ((!$found) && isset($arr_lists[$list_number])) {
                foreach ($arr_definitions['update']['keys'] as $value) {
                    $arr_list[$value] = $arr_create_lists['update'][$value];
                }
                $arr_lists_json[$row_type][$list_number] = $arr_list;
                $main->update_json($con, $arr_lists_json, "bb_create_lists");
                array_push($arr_messages, "List definition successfully updated.");
            }
            else {
                array_push($arr_messages, "Error: Unable to updated/renamed list.");
            }
        }
        else {
            array_push($arr_messages, "Error: List name for update not supplied.");
        }
    }
} // button 2 if
// remove list
if ($main->button(3)) {
    if ($arr_create_lists['delete']['list_number']) {
        $list_number = $arr_create_lists['delete']['list_number'];
        $row_type = $arr_create_lists['delete']['row_type'];
        if (isset($arr_lists_json[$row_type][$list_number])) {
            // reference parent, wierd but works
            unset($arr_lists_json[$row_type][$list_number]);
            // empty list_bit
            $query = "UPDATE data_table SET list_string = bb_list_unset(list_string, " . $list_number . ") WHERE bb_list(list_string, " . $list_number . ") = 1 AND row_type IN (" . ( int )$row_type . ");";
            $main->query($con, $query);
            $main->update_json($con, $arr_lists_json, "bb_create_lists");
            array_push($arr_messages, "List successfully removed.");
        }
        else {
            array_push($arr_messages, "Error: Unable to remove list.");
        }
    }
    else {
        array_push($arr_messages, "Error: List not selected.");
    }
} // button 3 if
elseif ($main->button(3) && ($arr_create_lists['delete']['confirm_remove'] != 1)) {
    array_push($arr_messages, "Error: Please confirm to remove list.");
}

// archive or retrieve list
if ($main->button(4)) {
    // underlying data could change, hopefully still there, multiuser problem
    $list_number = $arr_create_lists['delete']['list_number'];
    $row_type = $arr_create_lists['delete']['row_type'];
    if ($list_number > 0) {
        $list_number = $arr_create_lists['delete']['list_number'];
        $row_type = $arr_create_lists['delete']['row_type'];
        if (isset($arr_lists_json[$row_type][$list_number])) {
            $archive = $arr_create_lists['delete']['archive'];
            $arr_lists_json[$row_type][$list_number]['archive'] = $archive;
            $main->update_json($con, $arr_lists_json, "bb_create_lists");
            $message = ($archive == 1) ? "List successfully archived." : "List successfully retrieved.";
            array_push($arr_messages, $message);
        }
        else {
            array_push($arr_messages, "Error: Unable to archive/retrieve list");
        }
    }
    else {
        array_push($arr_messages, "Error: Unable to find list");
    }
} // button 4 if
/* BEGIN REQUIRED FORM */
echo "<p class=\"spaced bold larger\">Manage Lists</p>";

echo "<div class=\"spaced\">";
$main->echo_messages($arr_messages);
echo "</div>";

$main->echo_form_begin();
$main->echo_module_vars();

foreach ($arr_definitions as $key => $value) {
    switch ($key) {
        case "new":
            // add lists
            echo "<span class=\"spaced colored\">Add New List</span>";
            echo "<div class=\"table border spaced\">";
            echo "<div class=\"row padded\">";
            echo "<div class=\"padded cell\">List Type: </div>";
            echo "<div class=\"padded cell\">";
            $params = array("class" => "spaced", "onchange" => "bb_reload_1()");
            $main->layout_dropdown($arr_layouts, "row_type_new", $arr_create_lists['new']['row_type'], $params);
            echo "</div>";
            echo "</div>";
            echo "<div class=\"row padded\">";
            echo "<div class=\"padded top cell\">List Name: </div>";
            echo "<div class=\"padded cell\">";
            $main->echo_input("name_new", "", array('type' => 'text', 'class' => 'spaced'));
            echo "</div></div>";
            echo "<div class=\"row padded\">";
            echo "<div class=\"padded top cell\">Description: </div>";
            echo "<div class=\"padded cell\">";
            $main->echo_textarea("description_new", "", array('rows' => 4, 'cols' => 60, 'class' => 'spaced'));
            echo "</div></div>";
            echo "<div class=\"row padded\">";
            echo "<div class=\"padded cell\"></div>";
            echo "<div class=\"padded cell\">";
            $params = array("class" => "spaced", "number" => 1, "target" => $module, "passthis" => true, "label" => "New List");
            $main->echo_button("add_list", $params);
            echo "</div></div></div>";
            echo "<br>";
        break;

        case "update":
            // Rename List or Update Lists
            $row_type = $arr_create_lists['update']['row_type'];
            $arr_lists = $main->lists($con, $row_type);

            echo "<span class=\"spaced colored\">Rename List, Update Description and Find List Number</span>";
            echo "<div class=\"border table\">"; // border
            echo "<div class=\"table spaced\">";
            echo "<div class=\"row padded\">";
            echo "<div class=\"padded cell\">";
            $params = array("class" => "spaced", "onchange" => "bb_reload_2()");
            $main->layout_dropdown($arr_layouts, "row_type_update", $row_type, $params);
            $params = array("class" => "spaced", "empty" => true, "check" => 1, "onchange" => "bb_reload_1()");
            $main->list_dropdown($arr_lists, "list_number_update", $arr_create_lists['update']['list_number'], $params);
            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "<div class=\"spaced table padded\">";
            echo "<div class=\"row padded\">";
            echo "<div class=\"spaced padded cell\">List Number: </div>";
            echo "<div class=\"padded cell\">";
            $main->echo_input("identifier_update", htmlentities($arr_create_lists['update']['identifier']), array('type' => 'text', 'class' => 'spaced textbox', 'readonly' => true));
            echo "</div></div>";
            echo "<div class=\"row padded\">";
            echo "<div class=\"spaced padded top cell\">List Name: </div>";
            echo "<div class=\"padded cell\">";
            $main->echo_input("name_update", htmlentities($arr_create_lists['update']['name']), array('type' => 'text', 'class' => 'spaced textbox'));
            echo "</div></div>";
            echo "<div class=\"row padded\">";
            echo "<div class=\"spaced padded cell\">Description: </div>";
            echo "<div class=\"padded cell\">";
            $main->echo_textarea("description_update", $arr_create_lists['update']['description'], array('rows' => 4, 'cols' => 60, 'class' => 'spaced'));
            echo "</div></div>";
            echo "<div class=\"row padded\">";
            echo "<div class=\"padded cell\"></div>";
            echo "<div class=\"padded cell\">";
            $params = array("class" => "spaced", "number" => 2, "target" => $module, $arr_state, "passthis" => true, "label" => "Update List");
            $main->echo_button("update_list_2", $params);
            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>"; // border
            echo "<br>";
        break;

        case "delete":
            // Remove and archive lists
            $row_type = $arr_create_lists['delete']['row_type'];
            $arr_lists = $main->lists($con, $row_type);

            echo "<span class=\"spaced colored\">Remove or Archive List</span>";
            echo "<div class=\"table border spaced\">";
            echo "<div class=\"row padded\">";
            echo "<div class=\"padded cell nowrap\">";
            $params = array("class" => "spaced", "onchange" => "bb_reload_3()");
            $main->layout_dropdown($arr_layouts, "row_type_delete", $arr_create_lists['delete']['row_type'], $params);
            $params = array("class" => "spaced", "empty" => true, "check" => 0, "onchange" => "bb_reload_1()");
            $main->list_dropdown($arr_lists, "list_number_delete", $arr_create_lists['delete']['list_number'], $params);
            echo " | ";
            echo "</div>";
            echo "<div class=\"padded cell nowrap\">";
            $params = array("class" => "spaced", "number" => 3, "target" => $module, $arr_state, "passthis" => true, "label" => "Remove List");
            $main->echo_button("remove_list", $params);
            echo "<span class = \"spaced border rounded padded shaded\">";
            echo "<label class=\"padded\">Confirm Remove: </label>";
            $main->echo_input("confirm_remove_delete", 1, array('type' => 'checkbox', 'class' => 'middle holderup'));
            echo "</span>";
            echo " | ";
            echo "</div>";
            echo "<div class=\"padded cell nowrap\">";
            $params = array("class" => "spaced", "number" => 4, "target" => $module, $arr_state, "passthis" => true, "label" => "Archive/Retrieve List");
            $main->echo_button("archive_list", $params);
            echo "</div>";
            echo "<div class=\"padded cell nowrap\">";
            $main->echo_input("archive_delete", 1, array('type' => 'checkbox', 'class' => 'middle holderup'));
            echo "<span class=\"spaced\">Check to Archive/Uncheck to Retrieve</span></div>";
            echo "</div>";
            echo "</div>";
        break;
    }
}

$main->echo_form_end();
/* END FORM */
?>

