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
<?php
// get state from db
$arr_state = $main->load($con, $module);

$arr_messages = $main->process('arr_messages', $module, $arr_state, array());
unset($arr_state['arr_messages']);

echo "<p class=\"spaced bold larger\">Translation Upload</p>";

echo "<div class=\"spaced\">";
$main->echo_messages($arr_messages);
echo "</div>";

/* BEGIN REQUIRED FORM */
$main->echo_form_begin(array("enctype" => "multipart/form-data"));
$main->echo_module_vars();
$main->echo_common_vars();

echo "<input class=\"spaced\" type=\"file\" name=\"upload_translation\" id=\"file\"/>";
$params = array("class" => "spaced", "number" => 1, "target" => $module, "passthis" => true, "label" => "Upload Translation .po file");
$main->echo_button("submit_translation", $params);

$main->echo_form_end();
?>

