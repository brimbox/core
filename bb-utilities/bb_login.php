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
?>
<style>
/* These are the login styles */
/* You can customize this by dropping a file called login.css in the root of the Brimbox instance */

#bb_index { margin: auto; position: absolute; top: 50%; left: 50%; margin: -80px 0 0 -150px; width: 300px; height: 160px; text-align: center; }    
#bb_image { background-image: url("../bb-config/login_image.gif"); width: 300px; height: 50px; background-repeat:no-repeat; background-position:center; }
#bb_table { width: 298px; border: 1px solid #A070B6; }
#bb_table td.right { width: 60%; text-align: left; }
#bb_table td.left { width: 40%; text-align: right; background-color: #F2EAFF;}
#bb_table td, #bb_login input, #bb_submit { font-size: 12px; font-family: Arial, Helvetica, sans-serif; line-height: 140%; }
#bb_login input { padding: 2px; width: 200px; border: 1px solid #A070B6; background-color: #FFFFFF; }
#bb_message {text-align: center; font-size: 12px; font-family: Arial, Helvetica, sans-serif; line-height: 140%; }
#bb_submit { background-color: #F2EAFF; border: 1px solid #A070B6; margin: 5px; padding: 3px; border-radius: 2px; -moz-border-radius: 2px; -webkit-border-radius: 2px; -o-border-radius: 2px;}
@media screen and (min-height: 600px) {
        #bb_index  { margin-top: -200px; }
    }
</style>
    
<!DOCTYPE html>   
<html>
    
    <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo PAGE_TITLE; ?></title>
    <?php if (file_exists("login.css")) : ?>
        <link rel="stylesheet" type="text/css" href="login.css">
    <?php else : ?>
        <link rel="stylesheet" type="text/css" href="bb-utilities/bb_login.css">
    <?php endif; ?>
    </head>

    <body>
    <div id="bb_index">
    <div id="bb_image"></div>
        <form id="bb_login" name="bb_login" method="post">
        <table id="bb_table"><tr><td class="left"><label for="username">Username: </label></td>
        <td class="right"><input name="username" id="username" class="long" type="text" /></td></tr>
        <tr><td class="left"><label for="password">Password: </label></td>
        <td class="right"><input name="password" id="password" class="long" type="password" /></td></tr>
        </table>
        <button id="bb_submit" name="bb_submit" type="submit" value="submit" />Login</button>
        <div id="bb_message"><?php echo $message; ?></div>
        </form>
    </div>
    </body>
    
</html>

