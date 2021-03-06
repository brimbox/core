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
$main->check_permission(array("3_bb_brimbox", "4_bb_brimbox", "5_bb_brimbox"));

/* MODULE INCLUDE */
include ("bb_queue_extra.php");
?>
<script>
/* MODULE JAVASCRIPT */
function bb_get_selected_text() 
	{
	var selText = "";
	if (window.getSelection)  
		{
		var selRange = window.getSelection ();
		selText = selRange.toString ();
		}
	else 
		{
		if (document.selection.createRange) 
			{
			// Internet Explorer
            var range = document.selection.createRange ();
             selText = range.text;			 
            }
         }            
	if (selText !== "") 
		{
		document.getElementById('bb_queue_clipboard').innerHTML = selText;
		}
	return false;
	}
//link on email list function
function bb_set_hidden(em)
	{
	//set vars and submit form
    var frmobj = document.forms["bb_form"];
	
	frmobj.email_number.value = em;
	bb_submit_form();
	return false;
	}        
//clear clipboard
function bb_clear_module()
	{
	var frmobj = document.forms["bb_form"];
	
	document.getElementById("bb_queue_clipboard").innerHTML = "";
	frmobj.email_number.value = -1;
	bb_submit_form();
	return false;
	}
//set the fields on the left
function bb_set_field(col)
	{
	var str = document.getElementById("bb_queue_clipboard").innerHTML;
	var fld = col;
	//full value
	document.forms["bb_form"][fld].value = str;	
	if (str.length > 40)
		{
		str = str.substr(0,40) + "...";
		}
	//display value
	document.getElementById(col).innerHTML = str;
	return false;
	}
</script>
<?php
//email css
/* DEPRECATED */
$queue_module_css = $main->filter("bb_queue_module_css", $queue_module_css);
echo $queue_module_css;
?>
<?php
/* BEGIN QUEUE AND STATE POSTBACK */

// $POST brought in from controller


// get state
$arr_state = $main->load($con, $module);

$email_number = $main->process('email_number', $module, $arr_state, -1);

$main->update($con, $module, $arr_state);
/**
 * * END POSTBACK **
 */
?>

<?php
// get mail constants
// $mailsever is also used in a couple of different calls, namely imap_status
$mailserver = "{" . EMAIL_SERVER . ":" . EMAIL_IMAP_OPTIONS . "}INBOX";
$username = EMAIL_ADDRESS;
$password = EMAIL_PASSWORD;

// get mailbox connection
$mbox = $main->get_mbox($mailserver, $username, $password);
if (!$mbox):
    // exit as gracefully as possible
    // warnings come out inline but notices appear at the end
    // imap error commented out in $main->get_mbox
    echo "<p class=\"message spaced\">" . __t("Unable to connect to mailbox.", $module) . "</p>";
    $main->echo_form_begin();
    $main->echo_module_vars();
    $main->echo_form_end();

else: // long else
    // delete email by UID (not message id)
    if ($main->button(3)) {
        $nbr = imap_num_msg($mbox);
        for ($i = 1;$i <= $nbr;$i++) {
            $f = "f" . ( string )$i;
            $u = "u" . ( string )$i;
            if ($main->check($f, $module)) {
                $uid = $main->post($u, $module);
                imap_delete($mbox, $uid, ST_UID);
            }
        }
        imap_expunge($mbox);
        $email_number = - 1; // not going to display message
        
    }
    // mark emails by UID
    if ($main->button(4)) {
        $nbr = imap_num_msg($mbox);
        for ($i = 1;$i <= $nbr;$i++) {
            $f = "f" . ( string )$i;
            $u = "u" . ( string )$i;
            if ($main->check($f, $module)) {
                $uid = $main->post($u, $module);
                imap_clearflag_full($mbox, $uid, '\\Seen', ST_UID);
            }
        }
        $email_number = - 1; // not going to display message
        
    }

    // set current email to seen
    if ($email_number != - 1) {
        imap_setflag_full($mbox, $email_number, '\\Seen');
    }

    // START REQUIRED FORM
    $main->echo_form_begin();
    $main->echo_module_vars();

    // parse mbox
    if ($mbox) {
        // status for seen/unseen etc
        // status uses $mailserver
        $status = imap_status($mbox, $mailserver, SA_ALL);
        // empty should work here
        $nbr_unseen = empty($status->unseen) ? 0 : ( int )$status->unseen;
        // quick message count
        $nbr = imap_num_msg($mbox);
        // deal with time
        date_default_timezone_set(USER_TIMEZONE);
        $arr_count = array($nbr, $nbr_unseen, date('Y-m-d h:i A', time()));
        echo "<div class = \"spaced\">" . __t("There are %d total messages, %d unread. Date: %s.", $module, $arr_count) . "</div>";

        // this is the email list container -- start container
        echo "<div id=\"bb_queue_inbox_wrap\">";
        echo "<div id=\"bb_queue_inbox_list\" class=\"border spaced\">";
        echo "<div class=\"padded table fill\">";
        for ($i = $nbr;$i >= 1;$i--) {
            $header = imap_header($mbox, $i);
            // bold open emails
            if (($header->Unseen == "U") || ($header->Recent == "N")) {
                $strong = " bold underline ";
            }
            else {
                $strong = "  ";
            }
            // short or long odd or even
            $shaded = ($i % 2) ? "odd" : "even";

            // deal with header -- convert to utf-8 if not default
            $arr_subject = imap_mime_header_decode($header->subject);
            $var_personal = isset($header->from[0]->personal) ? ( string )$header->from[0]->personal : "";
            $var_email = $header->from[0]->mailbox . "@" . $header->from[0]->host;
            $var_date = date('Y-m-d h:i A', strtotime($header->date));
            if ($arr_subject[0]->charset != "default") {
                $var_subject = iconv(( string )$arr_subject[0]->charset, "UTF-8//TRANSLIT//IGNORE", $arr_subject[0]->text);
            }
            else {
                $var_subject = $arr_subject[0]->text;
            }
            // output loop, hidden uid, javascript link, and checkbox
            echo "<div class=\"row " . $shaded . "\">";
            echo "<div class = \"extra cell\"><button onclick=\"bb_set_hidden('" . $i . "'); return false;\" class = \"left link" . $strong . "\">" . $var_subject . "</button></div>";
            echo "<div class = \"extra cell\"><button onclick=\"bb_set_hidden('" . $i . "'); return false;\" class = \"left link" . $strong . "\">" . $var_personal . "</button></div>";
            echo "<div class = \"extra cell\"><button onclick=\"bb_set_hidden('" . $i . "'); return false;\" class = \"left link" . $strong . "\">" . $var_email . "</button></div>";
            echo "<div class = \"extra cell\"><button onclick=\"bb_set_hidden('" . $i . "'); return false;\" class = \"left link" . $strong . "\">" . $var_date . "</button></div>";
            echo "<div class = \"center extra cell\"><input name=\"f" . ( string )$i . "\" type=\"checkbox\" class=\"spaced\" value=\"Y\" /></div>";
            echo "<div class = \"extra cell\"><input name=\"u" . ( string )$i . "\" type=\"hidden\" value=\"" . imap_uid($mbox, $header->Msgno) . "\" /></div>";
            echo "</div>";
        }
        echo "</div>"; // end table
        echo "</div>";
        $main->echo_clear();

        echo "<div class=\"floatleft\">"; // buttons
        $params = array("class" => "spaced", "onclick" => "bb_clear_module()", "label" => __t("Clear Module", $module));
        $main->echo_script_button("clear_module", $params);
        $params = array("class" => "spaced", "number" => 2, "target" => "bb_input", "passthis" => true, "label" => __t("Add To Input", $module));
        $main->echo_button("add_to_input", $params);
        echo "</div>";
        echo "<div class=\"floatright\">";
        $params = array("class" => "spaced", "number" => 3, "target" => "bb_queue", "passthis" => true, "label" => __t("Delete Emails", $module));
        $main->echo_button("delete_emails", $params);
        $params = array("class" => "spaced", "number" => 4, "target" => "bb_queue", "passthis" => true, "label" => __("Mark Emails", $module));
        $main->echo_button("mark_emails", $params);
        echo "</div>";
        echo "</div>";
        $main->echo_clear();
    }
    // hidden values + state
    echo "<input name=\"email_number\" type=\"hidden\" value=\"" . $email_number . "\" />";

    // return selected message
    if ($email_number != - 1) {
        $header = imap_header($mbox, $email_number);
        //$trans_htmlmsg = iconv($msg->charset, "UTF-8//TRANSLIT//IGNORE", $msg->htmlmsg);
        $read_email = new bb_read_email();
        $trans_htmlmsg = $read_email->get_body($mbox, $email_number);
        $var_personal = isset($header->from[0]->personal) ? ( string )$header->from[0]->personal : "";
        $var_email = $header->from[0]->mailbox . "@" . $header->from[0]->host;
        $var_date = date('Y-m-d h:i A', strtotime($header->date));
        $arr_subject = imap_mime_header_decode($header->subject);
        if ($arr_subject[0]->charset != "default") {
            $var_subject = iconv($arr_subject[0]->charset, "UTF-8//TRANSLIT//IGNORE", $arr_subject[0]->text);
        }
        else {
            $var_subject = $arr_subject[0]->text;
        }

        // get row_type and columns
        $arr_layouts = $main->layouts($con);
        $default_row_type = $main->get_default_layout($arr_layouts);
        $arr_state_input = $main->load($con, "bb_input");
        //row_join matches input dropdown
        $row_type = $main->state('row_join', $arr_state_input, $default_row_type);
        $arr_columns = $main->columns($con, $row_type);

        // subject is dependent on email number, no need to include in state
        echo "<input name=\"subject\" type=\"hidden\" value=\"" . $var_subject . "\" />";

        echo "<div id=\"bb_queue_email_container\" class=\"inlineblock spaced border\" >";
        echo "<div id=\"bb_queue_clipboard\" class=\"padded left borderbottom\"></div>";
        echo "<div class=\"padded left borderbottom\" onMouseUp=\"bb_get_selected_text(); return false;\">" . $var_personal . " &lt;" . $var_email . "&gt;</div>";
        echo "<div class=\"padded left borderbottom\" onMouseUp=\"bb_get_selected_text(); return false;\">" . $var_subject . "</div>";
        echo "<div class=\"padded left borderbottom\" onMouseUp=\"bb_get_selected_text(); return false;\">" . $var_date . "</div>";
        echo "<div id=\"bb_queue_email_body\" class=\"inlineblock\" onMouseUp=\"bb_get_selected_text()\">" . $trans_htmlmsg . "</div>";
        echo "</div>";
        $i = 1;
        // visable queue fields
        echo "<div class=\"inlineblock top double\">";
        echo "<ul class=\"nobullets noindent\">";
        if (!empty($arr_columns)) {
            foreach ($arr_columns as $key => $value) {
                $col = $main->pad("c", $key);
                echo "<input name=\"" . $col . "\" type=\"hidden\" value=\"\" />";
                echo "<li class=\"noindent\"><button type=\"button\" class=\"link spaced padded\" onclick=\"bb_set_field('" . $col . "'); return false;\">" . $value['name'] . "</button></li>";
                echo "<li class=\"noindent\"><span id=\"" . $col . "\" class=\"spaced\"></span></li>";
                $i++;
            }
        }
        echo "</ul>";
        echo "</div>";
        $main->echo_clear();
    }
    imap_close($mbox);
    $main->echo_form_end();

    /* END FORM */
endif;

?>