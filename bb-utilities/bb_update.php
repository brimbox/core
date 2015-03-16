<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
Copyright (C) 2012 - 2015  Kermit Will Richardson, Brimbox LLC

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

$main->check_permission("bb_brimbox", 5);

@unlink("bb-less/lessc.inc.php");
@unlink("bb-less/license.txt");
@unlink("bb-config/bb_admin_index.css");
@unlink("bb-config-default/bb_admin_index.css");

$query = "ALTER TABLE users_table ADD CONSTRAINT users_table_unique_username UNIQUE (username);";
@pg_query($con, $query);

$query = "INSERT INTO modules_table(module_order, module_path, module_name, friendly_name, interface, module_type, module_version, standard_module, maintain_state, module_files, module_details) " .
         "SELECT (SELECT max(module_order) + 1 FROM modules_table), 'bb-admin/bb_upload_docs.php', 'bb_upload_docs', 'Upload Documents', 'bb_brimbox', 4, 'Core', 6, 0, '', '{\"company\":\"Brimbox\",\"author\":\"Brimbox Staff\",\"license\":\"GNU GPL v3\",\"description\":\"This is the admin module used for uploading documents, usually support documents, to the database.\"}' WHERE NOT EXISTS (SELECT 1 FROM modules_table WHERE module_name = 'bb_upload_docs')";
@pg_query($con, $query);
?>