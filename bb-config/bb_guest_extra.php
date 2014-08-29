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
?>
<?php
/* BEGIN REQUIRED FORM */
$main->retrieve($con, $array_state, $userrole);
		
$main->echo_form_begin();
$main->echo_module_vars();

/* In case you need to put a quick link on the home page */
$main->echo_common_vars();

$main->echo_state($array_state);
$main->echo_form_end();
/* END FORM */
?>

<!-- Can simply include HTML content -->

<br><br>
<div class="border padded note">This is where you can put custom HTML, company links or frequently used links, help or reference links, links to our forum, FAQs etc. These will be hard links and this page is made to be customizable for the user.</div>
<br><br>
<a class="padded" href="http://www.brimbox.com">Brimbox home page -- http://www.brimbox.com</a>
<br><br>
<a class="padded" href="http://www.postgresql.org/">PostgreSQL home page -- http://www.postgresql.org/</a>
<br><br>
<a class="padded" href="http://www.php.net/">PHP home page -- http://www.php.net/</a>

