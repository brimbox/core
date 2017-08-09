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
$main->check_permission("5_bb_brimbox");

// input form textarea max length
$maxinput = $main->get_constant('BB_STANDARD_LENGTH', 255);
// check for constant, max is 26
$number_layouts = $main->get_constant('BB_NUMBER_LAYOUTS', 12);

// start of code
$arr_layouts = $main->get_json($con, "bb_layout_names"); // do not reduce
// security
$arr_layout_security = $array_security['layout_security'];
//get alphabet
$alphabet = $main->get_alphabet();

$arr_messages = array();

/* PRESERVE STATE */

// $POST brought in from controller
// sorting function
$arr_layouts_fields = array(
    'parent' => array(
        'name' => __t("Parent", $module)
    ) ,
    'order' => array(
        'name' => __t("Order", $module)
    ) ,
    'secure' => array(
        'name' => __t("Secure", $module)
    ) ,
    'autoload' => array(
        'name' => __t("Autoload", $module)
    ) ,
    'relate' => array(
        'name' => __t("Relate", $module)
    )
);

// waterfall, first layout, second layout, third layout
// will only work if first layout is populated
if ($main->button(1)) {
    // layout_submit
    // one record must be populated
    // use two arrays,
    for ($i = 1;$i <= $number_layouts;$i++) {
        $singular = $main->purge_chars($main->init($main->post('singular_' . $i, $module, "") , ""));
        $plural = $main->purge_chars($main->init($main->post('plural_' . $i, $module, "") , ""));

        // OR condition to save variables in state
        if (!$main->blank($singular) && !$main->blank($plural)) {
            $arr_layouts[$i]['singular'] = $singular;
            $arr_layouts[$i]['plural'] = $plural;
            foreach ($arr_layouts_fields as $key => $value) {
                $arr_layouts[$i][$key] = $main->purge_chars($main->post($key . '_' . $i, $module, 0) , true, true);
            }
        }
        else {
            unset($arr_layouts[$i]);
        }

        //join submit
        $join1 = (int)$main->post('join1_' . $i, $module, 0);
        $join2 = (int)$main->post('join2_' . $i, $module, 0);
        if (($join1 > 0) && ($join2 > 0)) {
            $arr_joins[$i]['join1'] = $join1;
            $arr_joins[$i]['join2'] = $join2;
        }
        else {
            unset($arr_joins[$i]);
        }
    }

    // check for integrity
    $both = false;
    $arr_singular = array();
    $arr_plural = array();
    $arr_order = array();
    $arr_parent = array();
    $arr_checkjoin = array();
    $arr_selfjoin = array();
    $arr_orderjoin = array();

    for ($i = 1;$i <= $number_layouts;$i++) {
        $singular = $arr_layouts[$i]['singular'];
        $plural = $arr_layouts[$i]['plural'];
        // AND condition to save variables in database JSON table
        if (!$main->blank($singular) && !$main->blank($plural)) {
            $both = true; // good
            // test for uniqueness
            $order = $arr_layouts[$i]['order'];
            $parent = $arr_layouts[$i]['parent'];
            $join1 = $arr_joins[$i]['join1'];
            $join2 = $arr_joins[$i]['join2'];
            array_push($arr_singular, $singular);
            array_push($arr_plural, $plural);
            array_push($arr_order, $order);
            // check parent and child are not recursive or circular
            if ($parent > 0) {
                array_push($arr_parent, $i . $parent);
                array_push($arr_parent, $parent . $i);
            }
            if ($join1 > 0) {
                if ($join1 > $join2) {
                    array_push($arr_orderjoin, $join1 . $join2);
                }
                if ($join1 == $join2) {
                    array_push($arr_selfjoin, $join1 . $join2);
                }
                else {
                    array_push($arr_checkjoin, $join1 . $join2);
                    array_push($arr_checkjoin, $join2 . $join1);
                }
            }
        }
    }

    $arr_layouts['joins'] = $arr_joins;

    if ($both) {
        // one layout populated
        // check for distinct values in singular or plural, use $main->array_iunique so check is not case sensitive
        if ((count($arr_singular) != count($main->array_iunique($arr_singular))) || (count($arr_plural) != count($main->array_iunique($arr_plural)))) {
            array_push($arr_messages, __t("Error: Layouts must have distinct singular and plural names.", $module));
        }

        // check that count is in order
        sort($arr_order);
        if (($arr_order[0] != 1) || (count($arr_order) != count(array_unique($arr_order))) || ($arr_order[count($arr_order) - 1] != count($arr_order))) {
            array_push($arr_messages, __t("Error: Layouts order must start at 1 and be strictly ascending.", $module));
        }

        // check for circular relationships
        if (count($arr_parent) != count(array_unique($arr_parent))) {
            array_push($arr_messages, __t("Error: Circular or self-referential Parent/Child relationship between layouts.", $module));
        }

        //check for duplicate self joins
        if (count($arr_selfjoin) != count(array_unique($arr_selfjoin))) {
            array_push($arr_messages, __t("Error: Cannot have more than one self join.", $module));
        }

        // check for multiple joins
        if (count($arr_checkjoin) != count(array_unique($arr_checkjoin))) {
            array_push($arr_messages, __t("Error: Join relationship has been defined more than once.", $module));
        }

        //check if a parent child relationship matches a join relation hip
        if (count(array_intersect($arr_checkjoin, $arr_parent)) > 0) {
            array_push($arr_messages, __t("Error: Cannot have a Join relationship identical to a Parent/Child relationship.", $module));
        }

        //for simplicity make sure lowest row_type is on the left in join defs
        if (count($arr_orderjoin) > 0) {
            array_push($arr_messages, __t("Error: The first join layout cannot have a layout number greater than the second layout.", $module));
        }

    }

    else {
        // must have one layout populated
        array_push($arr_messages, __t("Error: Singular and plural must be populated for at least one layout.", $module));
    }

    // all conditions go
    if (!$main->has_error_messages($arr_messages)) {
        // discard rows without both singular and plural, JSON changed and updated
        // sort on order and update JSON, layouts are stored sorted by order, not row_type
        $main->update_json($con, $arr_layouts, "bb_layout_names");
        array_push($arr_messages, __t("Layouts and Joins have been updated.", $module));
    }
} // submit
if ($main->button(2)) {
    // revert to json in database
    $arr_layouts = $main->get_json($con, "bb_layout_names");
    array_push($arr_messages, __t("Layouts and Joins have been refreshed from database.", $module));
}

if ($main->button(3)) {
    // vaccum database
    $query = "VACUUM;";
    $main->query($con, $query);
    array_push($arr_messages, __t("Database has been vacuumed.", $module));
}

/* START REQUIRED FORM */
echo "<p class=\"padded bold larger\">" . __t("Layout Names", $module) . "</p>";

echo "<div class=\"padded\">";
$main->echo_messages($arr_messages);
echo "</div>";

$main->echo_form_begin();
$main->echo_module_vars();

$params = array(
    "class" => "spaced",
    "number" => 1,
    "target" => $module,
    "passthis" => true,
    "label" => __t("Submit Layouts and Joins", $module)
);
$main->echo_button("layout_submit", $params);
$params = array(
    "class" => "spaced",
    "number" => 2,
    "target" => $module,
    "passthis" => true,
    "label" => __t("Refresh Layouts and Joins", $module)
);
$main->echo_button("refresh_layout", $params);
$params = array(
    "class" => "spaced",
    "number" => 3,
    "target" => $module,
    "passthis" => true,
    "label" => __t("Vacuum Database", $module)
);
$main->echo_button("vacuum_database", $params);

echo "<div class=\"table spaced border\">";
echo "<div class=\"row\">";
echo "<div class=\"cell shaded\"></div>";
echo "<div class=\"bold underline shaded extra middle cell\"><label class=\"padded\">" . __t("Singular", $module) . "</label></div>";
echo "<div class=\"bold underline shaded extra middle cell\"><label class=\"padded\">" . __t("Plural", $module) . "</label></div>";
foreach ($arr_layouts_fields as $key => $value) {
    echo "<div class=\"bold underline shaded extra middle cell \"><label class=\"padded\">" . $value['name'] . "</label></div>";
}
echo "</div>";
for ($i = 1;$i < $number_layouts;$i++) {

    $alpha = mb_substr($alphabet, $i - 1, 1);

    echo "<div class=\"row\">"; // begin row
    // label
    echo "<div class=\"extra middle cell\">";
    echo "<label>" . __t("Layout", $module) . " " . $alpha . "</label>";
    echo "</div>";

    // singular
    echo "<div class=\"extra middle cell\">";
    $value = isset($arr_layouts[$i]) ? __($arr_layouts[$i]['singular']) : "";
    $main->echo_input("singular_" . $i, $value, array(
        'type' => "input",
        'maxlength' => $maxinput
    ));
    echo "</div>";

    // plural
    echo "<div class=\"extra middle cell\">";
    $value = isset($arr_layouts[$i]) ? __($arr_layouts[$i]['plural']) : "";
    $main->echo_input("plural_" . $i, $value, array(
        'type' => "input",
        'maxlength' => $maxinput
    ));
    echo "</div>";

    foreach ($arr_layouts_fields as $key => $value) {
        switch ($key) {
            case "parent":
                echo "<div class=\"extra middle cell\">";
                echo "<select name=\"parent_" . $i . "\">";
                echo "<option value=\"0\">&nbsp;</option>";
                for ($j = 1;$j <= $number_layouts;$j++) {
                    $selected = "";
                    if (isset($arr_layouts[$i]['parent'])) {
                        $selected = ($arr_layouts[$i]['parent'] == $j) ? "selected" : "";
                    }
                    echo "<option value=\"" . $j . "\" " . $selected . ">" . chr($j + 64) . $j . "&nbsp;</option>";
                }
                echo "</select>";
                echo "</div>";
            break;

            case "order":
                echo "<div class=\"extra middle cell\">";
                echo "<select name=\"order_" . $i . "\">";
                echo "<option value=\"0\"0>&nbsp;</option>";
                for ($j = 1;$j <= $number_layouts;$j++) {
                    $selected = "";
                    if (isset($arr_layouts[$i]['order'])) {
                        $selected = ($arr_layouts[$i]['order'] == $j) ? "selected" : "";
                    }
                    echo "<option value=\"" . $j . "\" " . $selected . ">" . $j . "&nbsp;</option>";
                }
                echo "</select>";
                echo "</div>";
            break;

            case "secure":
                // secure checkbox
                if (empty($arr_layout_security)) {
                    echo "<div class=\"extra center middle cell\">";
                    // has a zero or 1 value
                    $checked = false;
                    if (isset($arr_layouts[$i]['secure'])) {
                        $checked = ($arr_layouts[$i]['secure']) == 1 ? true : false;
                    }
                    $main->echo_input("secure_" . $i, 1, array(
                        'type' => 'checkbox',
                        'class' => 'holderdown',
                        'checked' => $checked
                    ));
                    echo "</div>";
                }
                else {
                    echo "<div class = \"extra middle cell\">";
                    echo "<select name=\"secure_" . $i . "\">";
                    foreach ($arr_layout_security as $key => $value) {
                        $selected = "";
                        if (isset($arr_layouts[$i]['secure'])) {
                            $selected = ($arr_layouts[$i]['secure'] == $key) ? "selected" : "";
                        }
                        echo "<option value = \"" . $key . "\" " . $selected . ">" . __($value) . "&nbsp;</option>";
                    }
                    echo "</select>";
                    echo "</div>";
                }
            break;

            case "autoload":
                // autoload
                echo "<div class=\"extra center middle cell\">";
                $checked = false;
                if (isset($arr_layouts[$i]['autoload'])) {
                    $checked = ($arr_layouts[$i]['autoload'] == 1) ? true : false;
                }
                $main->echo_input("autoload_" . $i, 1, array(
                    'type' => 'checkbox',
                    'class' => 'holderdown',
                    'checked' => $checked
                ));
                echo "</div>";
            break;

            case "relate":
                // relate checkbox
                echo "<div class=\"cell extra middle center\">";
                $checked = false;
                if (isset($arr_layouts[$i]['relate'])) {
                    $checked = ($arr_layouts[$i]['relate'] == 1) ? true : false;
                }
                $main->echo_input("relate_" . $i, 1, array(
                    'type' => 'checkbox',
                    'class' => 'holderdown',
                    'checked' => $checked
                ));
                echo "</div>";
            break;
        } // end switch
        
    } // end foreach
    echo "</div>"; // end row
    
} // end for
echo "</div>"; // end table
/* START JOIN AREA */
echo "<p class=\"spacertop padded bold larger\">" . __t("Join Relationships", $module) . "</p>";

echo "<div class=\"table spaced border\">";
echo "<div class=\"row\">";
echo "<div class=\"cell shaded\"></div>";
echo "<div class=\"bold underline shaded extra middle cell\"><label class=\"padded\">" . __t("Layout", $module) . "</label></div>";
echo "<div class=\"bold underline shaded extra middle cell\"><label class=\"padded\">" . __t("Layout", $module) . "</label></div>";
echo "</div>";
//set up dropdown field array
for ($i = 1;$i <= $number_layouts;$i++) {
    $alpha = mb_substr($alphabet, $i - 1, 1);
    $arr_select[$i] = $alpha . $i;
}
//output selects
for ($i = 1;$i <= $number_layouts;$i++) {

    echo "<div class=\"row\">"; // begin row
    // label
    echo "<div class=\"extra middle cell\">";
    echo "<label>" . __t("Join", $module) . " " . $i . "</label>";
    echo "</div>";

    // join1
    echo "<div class=\"extra middle cell\">";
    $join1 = (int)$arr_layouts['joins'][$i]['join1'];
    $main->array_to_select($arr_select, "join1_" . $i, $join1, array(
        0 => ""
    ) , array(
        'usekey' => true
    ));
    echo "</div>";

    // join2
    echo "<div class=\"extra middle cell\">";
    $join2 = (int)$arr_layouts['joins'][$i]['join2'];
    $main->array_to_select($arr_select, "join2_" . $i, $join2, array(
        0 => ""
    ) , array(
        'usekey' => true
    ));
    echo "</div>";

    echo "</div>"; // end row
    
} // end for
echo "</div>"; // end table
$main->echo_form_end();
/* END FORM */
?>
