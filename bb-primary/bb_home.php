<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
Copyright (C) 2012 - 2015  Kermit Will Richardson, Brimbox LLC

The GNU GPL v3 license does not grant licensee any rights in the trademarks, service marks,
or logos of any Contributor except as may be necessary to comply with the notice requirements
of the GNU GPL v3 license.  The GNU GPL v3 license does not grant licensee permission to copy,
modify, or distribute this program’s documentation for any purpose. 

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU GPL v3 for more details. 

You should have received a copy of the GNU GPL v3 along with this program.
If not, see http://www.gnu.org/licenses/
*/
?>
<?php
$main->check_permission("bb_brimbox", array(3,4,5));

/* DATABASE STATS -- AUTOFILL HOOK */
$main->hook("bb_home_infolinks");

include("bb-config/bb_home_extra.php");


?>

