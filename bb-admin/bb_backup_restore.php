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
/* INTITALIZE */
$arr_messages = array();

$arr_layouts = $main->layouts($con);
$row_type = $main->get_default_layout($arr_layouts);

// $POST brought in from controller


// get state from db
$arr_state = $main->load($con, $module);

$arr_messages = $main->state('arr_messages', $arr_state, array());

echo "<div class=\"spaced\">";
$main->echo_messages($arr_messages);
echo "</div>";

/* START REQUIRED FORM */
$main->echo_form_begin(array('enctype' => "multipart/form-data"));
$main->echo_module_vars();

/* BACKUP AREA */
echo "<p class=\"spaced bold larger\">" . __t("Backup Database", $module) . "</p>";
echo "<div class=\"inlineblock spaced border padded\">";
$params = array("class" => "spaced", "number" => 1, "passthis" => true, "label" => __t("Clean Database Data", $module));
$main->echo_button("add_list", $params);
$params = array("class" => "spaced", "number" => 2, "passthis" => true, "label" => __t("Clean Database Columns", $module));
$main->echo_button("clean_up_columns", $params);
$params = array("class" => "spaced", "number" => 3, "passthis" => true, "label" => __t("Clean Database Layouts", $module));
$main->echo_button("clean_up_columns", $params);
echo "<br>";
$params = array("class" => "spaced", "label" => __t("Backup Database", $module), "onclick" => "bb_submit_link('bb-links/bb_backup_restore_link_1.php'); return false;");
$main->echo_script_button("backup_database", $params);
$params = array("class" => "spaced", "label" => __t("Backup Files", $module), "onclick" => "bb_submit_link('bb-links/bb_backup_restore_link_2.php'); return false;");
$main->echo_script_button("backup_files", $params);
echo "<label class=\"spaced\">" . __t("Password:", $module) . " </label>";
echo "<input class=\"spaced\" type=\"password\" name=\"backup_passwd\"/>";
echo "<label class=\"spaced\"> " . __t("Encrypt:", $module) . " </label>";
$main->echo_input("encrypt_method", 1, array('type' => "checkbox", 'class' => "spaced", 'checked' => true));
echo "</div>";
echo "<br>";

echo "<p class=\"spaced bold larger\">" . __t("Database Dump", $module) . "</p>";
echo "<div class=\"inlineblock spaced border padded\">";
$params = array("class" => "spaced", "label" => __t("Download Layout", $module), "onclick" => "bb_submit_link('bb-links/bb_backup_restore_link_3.php'); return false;");
$main->echo_script_button("dump_database", $params);
$main->layout_dropdown($arr_layouts, "row_type", $row_type);
echo "<select class=\"spaced\" name=\"column_names\"><option value=\"0\">" . __t("Use Friendly Names", $module) . "&nbsp;</option><option value=\"1\">" . __t("Use Generic Names", $module) . "&nbsp;</option></select>";
echo "<select class=\"spaced\" name=\"new_lines\"><option value=\"0\">" . __t("Escape New Lines", $module) . "&nbsp;</option><option value=\"1\">" . __t("Purge New Lines", $module) . "&nbsp;</option></select>";
echo "<br>";
$params = array("class" => "spaced", "label" => __t("Download List Definitions", $module), "onclick" => "bb_submit_link('bb-links/bb_backup_restore_link_4.php'); return false;");
$main->echo_script_button("dump_listdefs", $params);
$params = array("class" => "spaced", "label" => __t("Download List Data", $module), "onclick" => "bb_submit_link('bb-links/bb_backup_restore_link_5.php'); return false;");
$main->echo_script_button("dump_listdata", $params);
echo "<br><label class=\"spaced\">" . __t("Password:", $module) . " </label>";
echo "<input class=\"spaced\" type=\"password\" name=\"dump_passwd\"/>";
echo "</div>";
echo "<br>";

/* RESTORE AREA */
echo "<p class=\"spaced bold larger\">" . __t("Restore Database", $module) . "</p>";
echo "<div class=\"inlineblock spaced border padded\">";
echo "<p class=\"spaced\">" . __t("Note: When restoring post_max_size and upload_max_filesize must be bigger than your backup files.", $module) . "</p>";
echo "</div>";
echo "<br>";
echo "<div class=\"spaced border floatleft padded\">";
echo "<p class=\"spaced bold\">" . __t("Restore Tables", $module) . "</p>";
echo "<div class=\"spaced border floatleft padded\">";
echo "<label class=\"spaced\">" . __t("Filename (bbdb):", $module) . " </label>";
echo "<input class=\"spaced\" type=\"file\" name=\"backup_file\" id=\"file\" /><br>";
echo "<div class=\"table\">";
echo "<div class=\"row\">";
echo "<div class=\"padded middle cell\">";
$main->echo_input("json_table_checkbox", 1, array('type' => 'checkbox', 'class' => 'spaced'));
echo " " . __t("Restore JSON Table", $module) . "</div>";
echo "</div>";
echo "<div class=\"row\">";
echo "<div class=\"padded middle cell\">";
$main->echo_input("users_table_checkbox", 1, array('type' => 'checkbox', 'class' => 'spaced'));
echo " " . __t("Restore Users Table", $module) . "</div>";
echo "</div>";
echo "<div class=\"row\">";
echo "<div class=\"padded middle cell\">";
$main->echo_input("modules_table_checkbox", 1, array('type' => 'checkbox', 'class' => 'spaced'));
echo " " . __t("Restore Modules Table", $module) . "</div>";
echo "</div>";
echo "<div class=\"row\">";
echo "<div class=\"padded middle cell\">";
$main->echo_input("log_table_checkbox", 1, array('type' => 'checkbox', 'class' => 'spaced'));
echo " " . __t("Restore Log Table", $module) . "</div>";
echo "</div>";
echo "<div class=\"row\">";
echo "<div class=\"padded middle cell\">";
$main->echo_input("join_table_checkbox", 1, array('type' => 'checkbox', 'class' => 'spaced'));
echo " " . __t("Restore Join Table", $module) . "</div>";
echo "</div>";
echo "<div class=\"row\">";
echo "<div class=\"padded middle cell\">";
$main->echo_input("data_table_checkbox", 1, array('type' => 'checkbox', 'class' => 'spaced'));
echo " " . __t("Restore Data Table", $module) . "</div>";
echo "</div>";
echo "</div>";
echo "<div class=\"spaced\">" . __t("Admin Password:", $module) . " ";
echo "<input class=\"spaced\" type=\"password\" name=\"admin_passwd_1\"/></div>";
echo "<div class=\"spaced\">" . __t("File Password:", $module) . " ";
echo "<input class=\"spaced\" type=\"password\" name=\"file_passwd_1\"/></div>";
$params = array("class" => "spaced", "number" => 4, "passthis" => true, "label" => __t("Restore Database", $module));
$main->echo_button("restore_database", $params);
$params = array("class" => "spaced", "number" => 5, "passthis" => true, "label" => __t("Build Indexes", $module));
$main->echo_button("build_indexes", $params);
echo "<br>";
echo "</div><br>";
echo "<div class=\"spaced border floatleft padded\">";
echo "<p class=\"spaced bold\">Restore Files</p>";
echo "<label class=\"spaced\">Filename (bblo): </label>";
echo "<input class=\"spaced\" type=\"file\" name=\"lo_file\" id=\"file\" /><br>";
echo "<div class=\"spaced\">" . __t("Admin Password:", $module) . " ";
echo "<input class=\"spaced\" type=\"password\" name=\"admin_passwd_2\"/></div>";
echo "<div class=\"spaced\">" . __t("File Password:", $module) . " ";
echo "<input class=\"spaced\" type=\"password\" name=\"file_passwd_2\"/></div>";
$params = array("class" => "spaced", "number" => 6, "passthis" => true, "label" => __t("Restore Files", $module));
$main->echo_button("restore_lo", $params);
echo "</div><br>";
$main->echo_form_end();
/* END FORM */
?>
