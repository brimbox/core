<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
Copyright (C) 2012 - 2013  Kermit Will Richardson, Brimbox LLC

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
$main->check_permission("bb_brimbox", 2);

/* BEGIN DATABASE STATS -- AUTOFILL HOOK */
if (isset($array_hooks['bb_viewerinfo']))
    {                                                                                                                                                                                                                                                                                                               
    foreach ($array_hooks['bb_viewerinfo'] as $arr_hook)
        {
        $args_hook = array();
        foreach ($arr_hook[1] as &$value)
            {
            if (substr($value,0,1) == "&") $args_hook[] = &${substr($value,1)}; else  $args_hook[] = ${$value};	
            }
        call_user_func_array($arr_hook[0], $args_hook);
        }
    }
/* END DATABASE STATS */

include("bb-config/bb_viewer_extra.php");
?>

