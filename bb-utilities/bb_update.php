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

include ($abspath . "/bb-less/bb_less_substituter.php");

$query = "INSERT INTO modules_table (module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, module_files, module_details) " . "SELECT 8, 'bb-admin/bb_translate.php', 'bb_translate', 'Translation', 'bb_brimbox', 5, 'Core', 6, '', '{\"company\":\"Brimbox\",\"author\":\"Brimbox Staff\",\"license\":\"GNU GPL v3\",\"description\":\"This is the module for translating Brimbox text, defining existing text strings with aletrnative or foreign text.\"}' WHERE NOT EXISTS (SELECT 1 FROM modules_table WHERE module_name = 'bb_translate')";

$query = "DROP TRIGGER ts1_modify_date ON data_table;";

$query = "CREATE TRIGGER ts1_modify_date
          BEFORE UPDATE OF
          key1, c01, c02, c03, c04, c05, c06, c07, c08, c09, c10, c11, c12, c13, c14, c15, c16, c17, c18, c19, c20, c21, c22, c23, c24, c25, c26, c27, c28, c29, c30,
          c31, c32, c33, c34, c35, c36, c37, c38, c39, c40, c41, c42, c43, c44, c45, c46, c47, c48,  c49, c50, list_string
          ON data_table
          FOR EACH ROW
          EXECUTE PROCEDURE bb_modify_date();";

$less = new bb_less_substituter();

$less->parse_less_file($abspath . "/bb-box/bb_box.less", $abspath . "/bb-box/bb_box.css");

?>