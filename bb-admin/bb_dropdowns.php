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
$main->check_permission(array("4_bb_brimbox", "5_bb_brimbox"));

?>
<script type="text/javascript">
//reload on layout change
function bb_reload()
    {
    //standard submit
	var frmobj = document.forms['bb_form'];
	//1 value will force program to find default value in PHP below		
	bb_submit_form();
	}
</script>
<?php
/* INITIALIZE */
$arr_messages = array();
$arr_notes = array("49", "50");

$delimiter = $main->get_constant('BB_MULTISELECT_DELIMITER', ",");

$arr_properties = array('multiselect' => array('name' => __t('Multiselect', $module)));

// $POST brought in from controller


// get state from db
$arr_state = $main->load($con, $module);

// This area creates the form for choosing the lists
$arr_layouts = $main->layouts($con);
$default_row_type = $main->get_default_layout($arr_layouts);

// get dropdowns
$arr_dropdowns_json = $main->get_json($con, "bb_dropdowns");

// get row_type from post
$row_type = $main->post('row_type', $module, $default_row_type);

// get default col_type
$arr_columns = $main->columns($con, $row_type);
$default_col_type = $main->get_default_column($arr_columns);

// process multiselect
$multiselect = $main->process('multiselect', $module, $arr_state, 0);
// process dropdown
$dropdown = $main->process('dropdown', $module, $arr_state, "");

// if row_type changed and postback (post is different than state) use default column type
if ($main->changed('row_type', $module, $arr_state, $default_row_type)) {
    $col_type = $main->set('col_type', $arr_state, $default_col_type);
    $all_values = $empty_value = $multiselect = 0;
    $dropdown = "";
}
else {
    if ($main->changed('col_type', $module, $arr_state, $default_col_type)) {
        $all_values = $empty_value = $multiselect = 0;
        $dropdown = "";
    }
    else {
        $all_values = $main->process('all_values', $module, $arr_state, 0);
        $empty_value = $main->process('empty_value', $module, $arr_state, 0);
    }
    $col_type = $main->process('col_type', $module, $arr_state, $default_col_type);
}

// process row_type
$row_type = $main->process('row_type', $module, $arr_state, $default_row_type);
// reprocess columns + multiselect
$arr_columns = $main->columns($con, $row_type);

// update state, back to db
$main->update($con, $module, $arr_state);

$col_text = isset($arr_columns[$col_type]['name']) ? $arr_columns[$col_type]['name'] : "";

// this area populates the textarea
if ($main->button(1)) {
    // populate_dropdown
    // preexisting dropdown xml
    $arr_dropdown = $main->reduce($arr_dropdowns_json, array($row_type, $col_type), false);

    // get all values in database for the selected column (and row type)
    $column = $main->pad("c", $col_type);
    $query = "SELECT distinct " . $column . " FROM data_table WHERE row_type = " . $row_type . " AND archive = 0 ORDER BY " . $column . " LIMIT 2000;";
    $result = $main->query($con, $query);

    $arr_query = pg_fetch_all_columns($result, 0);

    $arr_props = $main->dropdown_properties($con, $row_type, $col_type);
    $multiselect = $main->init($arr_props['multiselect'], 0);

    // values from db and dropdown alphabetized
    if (!empty($arr_dropdown) && ($all_values == 0)) {
        $arr_populate = array_merge($arr_query, $arr_dropdown);
        if ($multiselect) {
            $arr_delimiter = preg_grep("/[" . preg_quote($delimiter) . "]/", $arr_populate);
            $arr_display = array();
            foreach ($arr_delimiter as $value) {
                $arr_display = $arr_display + explode($delimiter, $value);
            }
            $arr_single = preg_grep("/[" . preg_quote($delimiter) . "]/", $arr_populate, PREG_GREP_INVERT);
            $arr_display = $arr_display + $arr_single;
        }
        else {
            $arr_display = $arr_populate;
        }
        $arr_display = array_filter($arr_display); // remove empty rows
        $arr_display = array_unique($arr_display); // unique
        sort($arr_display); // sort
        $arr_printf = array($col_text);
        array_push($arr_messages, __t("Column %s has a dropdown list.", $module, $arr_printf));
        array_push($arr_messages, __t("Textarea populated from both preexisting dropdown list and the database.", $module));
    }
    elseif (!empty($arr_dropdown) && ($all_values == 1)) {
        // values from dropdown not alphabetized
        $arr_display = $arr_dropdown;
        $arr_printf = array($col_text);
        array_push($arr_messages, __t("Column %s has a dropdown list.", $module, $arr_printf));
        array_push($arr_messages, __t("Textarea populated from preexisting dropdown list.", $module));
    }
    else {
        // values from db alphabetized
        $arr_printf = array($col_text);
        array_push($arr_messages, __t("Column %s does not have a preexisting dropdown list.", $module, $arr_printf));
        $arr_display = array_filter($arr_query);
    }
    $dropdown = implode("\r\n", $arr_display);
}

// this area updates the drop down if set
if ($main->button(2)) {
    // submit dropdown
    $arr_txt = preg_split("/[\r\n]+/", $main->purge_chars($main->post('dropdown', $module), false));
    $arr_txt = array_filter($arr_txt); // remove empty rows
    $arr_txt = array_map(array($main, "purge_chars"), $arr_txt);
    if (in_array($col_type, $arr_notes)) {
        array_push($arr_messages, __t("Error: Cannot create a dropdown on a note column.", $module));
    }
    elseif (count($arr_txt) == 0) {
        array_push($arr_messages, __t("Error: Cannot populate an empty dropdown.", $module));
    }
    elseif (preg_grep("/[" . preg_quote($delimiter) . "]/", $arr_txt) && $multiselect) {
        $arr_printf = array($delimiter);
        array_push($arr_messages, __t("Error: Cannot populate an multiselect dropdown containing the delimiter (%s).", $module));
    }
    else {
        // populate dropdown
        $arr_dropwork = array();
        // add empty or null value
        if ($main->post('empty_value', $module) == 1) {
            array_push($arr_dropwork, "");
        }
        foreach ($arr_txt as $value) {
            array_push($arr_dropwork, $value); // overload
            
        }

        // display working values if error
        foreach ($arr_properties as $key => $value) {
            switch ($key) {
                case "multiselect":
                    // row dropdown
                    $arr_props['multiselect'] = $multiselect;
                break;
            }

            // HOOK
            
        }

        $arr_dropdowns_json[$row_type][$col_type] = $arr_dropwork + $arr_props;
        $arr_dropdowns_json['properties'] = $arr_properties;
        $main->update_json($con, $arr_dropdowns_json, "bb_dropdowns");
        $arr_printf = array($col_text);
        array_push($arr_messages, __t("Column %s has had its dropdown list added or updated.", $module, $arr_printf));

        $row_type = $main->set('row_type', $arr_state, $default_row_type);
        $col_type = $main->set('col_type', $arr_state, $default_col_type);
        $arr_columns = $main->columns($con, $row_type);
        $multiselect = $all_values = $empty_value = 0;
        $dropdown = "";
    }
}

if ($main->button(3)) // remove_dropdown
{
    // this area removes the dropdown
    unset($arr_dropdowns_json[$row_type][$col_type]);
    $main->update_json($con, $arr_dropdowns_json, "bb_dropdowns");
    $arr_printf = array($col_text);
    array_push($arr_messages, __t("Column %d has had its dropdown list removed if it existed.", $module, $arr_printf));
}

/* BEGIN REQUIRED FORM */
// populate row_type select combo box
echo "<p class=\"spaced bold larger\">" . __t("Manage Dropdowns", $module) . "</p>";

echo "<div class=\"spaced\">";
$main->echo_messages($arr_messages);
echo "</div>";

$main->echo_form_begin();
$main->echo_module_vars();

// row_type select tag
$params = array("class" => "spaced", "onchange" => "bb_reload()");
$main->layout_dropdown($arr_layouts, "row_type", $row_type, $params);
echo "<br>"; // why not
$params = array("class" => "spaced", "onchange" => "bb_reload()");
$main->column_dropdown($arr_columns, "col_type", $col_type, $params);
echo "<br>";
echo "<div class=\"spaced\">";
echo "<span class = \"spaced border rounded padded shaded\">";
echo "<label class=\"spaced padded\">" . __t("Create Multiselect Dropdown:", $module) . " </label>";
$main->echo_input("multiselect", 1, array('type' => 'checkbox', 'class' => 'spaced', 'checked' => $multiselect));
echo "</span>";
echo "</div>";
// populate text area
$main->echo_textarea("dropdown", $dropdown, array('id' => "bb_dropdowns_textarea", 'class' => "spaced boxsizing", 'wrap' => "off"));

// buttons
$params = array("class" => "spaced", "number" => 1, "target" => $module, "passthis" => true, "label" => __t("Populate Form", $module));
$main->echo_button("populate_dropdown", $params);
echo "<span class = \"spaced border rounded padded shaded\">";
$checked = ($all_values == 1) ? true : false;
$main->echo_input("all_values", 1, array('type' => 'checkbox', 'class' => 'spaced', 'checked' => $checked));
echo "<label class=\"spaced padded\">" . __t("Populate With Existing Dropdown", $module) . "</label>";
echo "</span>";
echo "<br>";
$params = array("class" => "spaced", "number" => 2, "target" => $module, "passthis" => true, "label" => __t("Create Dropdown", $module));
$main->echo_button("create_dropdown", $params);
echo "<span class = \"spaced border rounded padded shaded\">";
$checked = ($empty_value == 1) ? true : false;
$main->echo_input("empty_value", 1, array('type' => 'checkbox', 'class' => 'spaced', 'checked' => $checked));
echo "<label class=\"spaced padded\">" . __t("Include Empty Value", $module) . "</label>";
echo "</span>";
echo "<br>";
$params = array("class" => "spaced", "number" => 3, "target" => $module, "passthis" => true, "label" => __t("Remove Dropdown", $module));
$main->echo_button("populate_dropdown", $params);
echo "<br>";

$main->echo_form_end();
/* END FORM */
?>
