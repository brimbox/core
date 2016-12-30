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
?>
<script>
function bb_reload()
    {
    //reload change on translation module change   
    bb_submit_form(); 
	return false;
    }
</script>
<?php
// get state from db
$arr_prepend_po = array("", 'bb_box' => "Box Controller", 'bb_main' => "Main Functions");

$arr_state = $main->load($con, $module);

$translation = $main->state('translation', $arr_state);
$arr_messages = $main->process('arr_messages', $module, $arr_state, array());
unset($arr_state['arr_messages']);

//submiting the translation will create one
$arr_po = $main->get_json($con, $translation . "_translate");
if (!$arr_po) $arr_po = array();

echo "<p class=\"spaced bold larger\">Translation Upload</p>";

echo "<div class=\"spaced\">";
$main->echo_messages($arr_messages);
echo "</div>";

/* BEGIN REQUIRED FORM */
$main->echo_form_begin(array("enctype" => "multipart/form-data"));
$main->echo_module_vars();
$main->echo_common_vars();

$query = "SELECT module_name, friendly_name FROM modules_table ORDER BY module_type, module_order";
$result = $main->query($con, $query);

$arr_modules_po = array();
while ($row = pg_fetch_array($result)) {
    $arr_modules_po[$row['module_name']] = $row['friendly_name'];
}

$main->array_to_select($arr_modules_po, "translation", $translation, $arr_prepend_po, array('usekey' => true, 'onchange' => "bb_reload()"));

echo "<input class=\"spaced\" type=\"file\" name=\"upload_translation\" id=\"file\"/>";
$params = array("class" => "spaced", "number" => 1, "target" => $module, "passthis" => true, "label" => "Upload Translation .po file");
$main->echo_button("submit_translation", $params);

echo "<table class=\"border\">";
echo "<tr colspan=\"2\"><td>";
$params = array("class" => "spaced", "number" => 2, "target" => $module, "passthis" => true, "label" => "Add Translation");
$main->echo_button("submit_translation", $params);
echo "</td></tr><tr><td>";
$main->echo_input('addkey', "", array('class' => "border spaced"));
echo "</td><td>";
$main->echo_input('addvalue', "", array('class' => "border spaced"));
echo "</td></tr>";

if (count($arr_po) > 0) {
    echo "<tr colspan=\"2\"><td>";
    $params = array("class" => "spaced", "number" => 3, "target" => $module, "passthis" => true, "label" => "Update Translations");
    $main->echo_button("update_translation", $params);
    echo "</td></tr>";
}

foreach ($arr_po as $key => $value) {
    echo "<tr><td>";
    $main->echo_input('key', $key, array('class' => "border spaced"));
    echo "</td><td>";
    $main->echo_input('addtrans', $value, array('class' => "border spaced"));
    echo "</td></tr>";
}
echo "</table>";

$main->echo_form_end();
?>

