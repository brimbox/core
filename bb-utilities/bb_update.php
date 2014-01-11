<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
Copyright (C) 2012 - 2013  Kermit Will Richardson, Brimbox LLC

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

$main->check_permission(5);
$xml_version = $main->get_xml($con, "bb_manage_modules");

//@unlink("bb-primary/bb_home_extra.php")

unset($xml_version->database);
$xml_version->addChild("database","2013.1.20");
unset($xml_version->program);
$xml_version->addChild("program","2013.2.358");
unset($xml_version->backup);
$xml_version->addChild("backup","2013.1.2");

/*
$body = "\$BODY\$";
$query = <<<EOT
EOT;
*/

$main->update_xml($con, $xml_version, "bb_manage_modules");
?>