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
$main->check_permission("Guest");
?>
<script type="text/javascript">
function force_logout()
    {
    //set bb_module var and submit form
    var frmobj = document.forms["bb_form"];
    
    frmobj.bb_module.value = "bb_logout";
    bb_submit_form(); //javascript submit function
	return false;
    }
function archive_mode()
    {
    //set bb_module var and submit form
    var frmobj = document.forms["bb_form"];
    
	if (document.getElementById('archive_mode').innerHTML == "On")
		{
		frmobj.mode.value = "Off";
		}
    else
		{
		frmobj.mode.value = "On";		
		}
    bb_submit_form(); //javascript submit function
	return false;
    }
</script>

<?php 
/* GUEST STATE AND POSTBACK */
$main->retrieve($con, $array_state, $userrole);
$xml_state = $main->load($module, $array_state);

$mode = $main->process('mode', $module, $xml_state, "Off");

$main->update($array_state, $module,  $xml_state);

echo "<span class=\"floatleft bold\">Hello <span class=\"colored\">" . $_SESSION['name'] . "</span></span>";
echo "<button class=\"floatright bold link underline\" onclick=\"force_logout()\">Logout</button>";
echo "<div class=\"clear\"></div>";
echo "<span class=\"floatleft bold\">You are logged in as: <span class=\"colored\">" . $email . "</span></span>";	
echo "<div class=\"clear\"></div>";
echo "<span class=\"floatleft bold\">You are using database: <span class=\"colored\">" . DB_NAME . "</span></span>";
echo "<div class=\"clear\"></div>";
echo "<span class=\"floatleft bold\">This database is known as: <span class=\"colored\">" . DB_FRIENDLY_NAME . "</span></span>";
echo "<div class=\"clear\"></div>";
echo "<span class=\"floatleft bold\">This database email address is: <span class=\"colored\">" . EMAIL_ADDRESS . "</span></span>";
echo "<div class=\"clear\"></div>";
echo "<span class=\"floatleft bold\">Archive mode is: <button class=\"link underline\" id=\"archive_mode\" onclick=\"archive_mode(); return false;\">" . $mode . "</button></span>";
echo "<div class=\"clear\"></div>";

include("bb-config/bb_guest_extra.php");
		
/* BEGIN REQUIRED FORM */
$main->echo_form_begin();
$main->echo_module_vars($module);

echo "<input type = \"hidden\"  name=\"mode\" value = \"" . $mode . "\">";

/* In case you need to put a quick link on the guest page */
$main->echo_common_vars();

$main->echo_state($array_state);
$main->echo_form_end();
/* END FORM */
?>

