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
<?php
$main->check_permission("bb_brimbox", array(3,4,5));
?>
<?php /* MODULE INCLUDES */
include("bb_queue_extra.php");
?>

<style type="text/css">
/* MODULE CSS */
/* no colors in css */
.queue_super_short
	{
	width:25px;
	}
.queue_div_width
	{
	width:900px; 
	}
.queue_div_container
	{
	width:890px;
	height:150px;
	overflow:auto;
	}
.queue_clipboard
	{
	height:50px;
	width:635px;
	}
 .queue_subject
	{
	width:635px;
	margin-bottom:-1px;
	margin-left:2px;
	margin-right:2px;
	}
.queue_email_body
	{
	margin-left: 2px;
	width:680px;
	height:680px;
	overflow:auto;
	}
/* END CSS */
</style>

<script type="text/javascript">
/* MODULE JAVASCRIPT */
//put selected text into clipboard
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
		document.getElementById('clipboard').innerHTML = selText;
		}
	return false;
	}
//link on email list function
function bb_set_hidden(em)
	{
	//set vars and submit form
    var frmobj = document.forms["bb_form"];
	
	frmobj.email_number.value = em;
	bb_submit_form(0);
	return false;
	}        
//clear clipboard
function bb_clear_module()
	{
	var frmobj = document.forms["bb_form"];
	
	document.getElementById("clipboard").innerHTML = "";
	frmobj.email_number.value = -1;
	bb_submit_form(0);
	return false;
	}
//set the fields on the left
function bb_set_field(col)
	{
	var str = document.getElementById("clipboard").innerHTML;
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
/* END MODULE JAVASCRIPT */
</script>


<?php
/* BEGIN QUEUE AND STATE POSTBACK  */
$main->retrieve($con, $array_state);

$arr_state = $main->load($module, $array_state);
$email_number = $main->process('email_number', $module, $arr_state, -1);
	
$main->update($array_state, $module,  $arr_state);
/*** END POSTBACK ***/
?>

<?php
//get mail constants

//$mailsever is also used in a couple of different calls, namely imap_status
$mailserver = "{" . EMAIL_SERVER . ":" . EMAIL_IMAP_OPTIONS . "}INBOX";
$username = EMAIL_ADDRESS;
$password = EMAIL_PASSWORD;

//get mailbox connection	
$mbox = $main->get_mbox($mailserver, $username, $password);
if (!$mbox):
    //exit as gracefully as possible
    //warnings come out inline but notices appear at the end
    //imap error commented out in $main->get_mbox
    echo "<form method=\"post\" name=\"queue_form\">";
    echo "<p>Unable to connect to mailbox</p>";
    echo "<input name=\"current_tab\" type=\"hidden\" value=\"queue\" />";
    $main->echo_state($array_state);
    echo "</form>";
else: //long else
    //delete email by UID (not message id)	
	if ($main->button(3))
		{
		$nbr = imap_num_msg($mbox);
			for ($i=1; $i<=$nbr; $i++)
				{
				$f = "f" . (string)$i;
				$u = "u" . (string)$i;
				if ($main->check($f,$module))
					{
					$uid = $main->post($u,$module);
					imap_delete($mbox, $uid, ST_UID);
					}
				}
		imap_expunge($mbox);			
		$email_number = -1;  //not going to display message
		}    
    //mark emails by UID
    
	if ($main->button(4))
		{
		$nbr = imap_num_msg($mbox);
		for ($i=1; $i<=$nbr; $i++)
			{
			$f = "f" . (string)$i;
			$u = "u" . (string)$i;
			if ($main->check($f,$module))
				{
				$uid = $main->post($u,$module);
				imap_clearflag_full($mbox, $uid,'\\Seen', ST_UID);
				}
			}
		$email_number = -1; //not going to display message	
		}
    
    //set current email to seen
	if ($email_number <> -1)
		{
		imap_setflag_full($mbox, $email_number,'\\Seen');
		}
    
//START REQUIRED FORM
$main->echo_form_begin();
$main->echo_module_vars();
    
    //parse mbox	
	if ($mbox)
		{
		//status for seen/unseen etc
		//status uses $mailserver
		$status = imap_status($mbox, $mailserver, SA_ALL);
		//empty should work here
		$nbr_unseen = empty($status->unseen) ? 0 : (int)$status->unseen;
		//quick message count
		$nbr = imap_num_msg($mbox);
		//deal with time
		date_default_timezone_set(USER_TIMEZONE); 
		echo "<div class = \"spaced\">There are " . $nbr . " total messages, " . $nbr_unseen . " unread. Date: " . date('Y-m-d h:i A', time()) . "</div>";
		
		//this is the email list container -- start container
		echo "<div class=\"queue_div_container border spaced padded\">";
		for ($i=$nbr; $i>=1; $i--)
			{
			$header = imap_header($mbox, $i);
			//bold open emails
			if (($header->Unseen == "U") || ($header->Recent == "N"))
				{
				$strong = " bold underline ";
				}
			else
				{
				$strong = "  ";
				}
			//short or long odd or even 
			$class_shade = ($i % 2 == 1) ? " odd " : " even ";
			
			//deal with header -- convert to utf-8 if not default
			$arr_subject = imap_mime_header_decode($header->subject);
			$var_personal = isset($header->from[0]->personal) ? (string)$header->from[0]->personal : "";
			$var_email = $header->from[0]->mailbox . "@" . $header->from[0]->host;
			$var_date = date('Y-m-d h:i A', strtotime($header->date));
			if ($arr_subject[0]->charset <> "default")
				{
				$var_subject = iconv((string)$arr_subject[0]->charset,  "UTF-8//TRANSLIT//IGNORE", $arr_subject[0]->text);
				}
			else
				{
				$var_subject = $arr_subject[0]->text;
				}							    
			//output loop, hidden uid, javascript link, and checkbox
			echo "<div class = \"long spaced overflow floatleft\"><button onclick=\"bb_set_hidden('" . $i . "'); return false;\" class = \"link" . $strong . "\">" . $var_subject . "</button></div>";
			echo "<div class = \"long spaced overflow floatleft\"><button onclick=\"bb_set_hidden('" . $i . "'); return false;\" class = \"link" . $strong . "\">" . $var_personal . "</button></div>";
			echo "<div class = \"long spaced overflow floatleft\"><button onclick=\"bb_set_hidden('" . $i . "'); return false;\" class = \"link" . $strong . "\">" . $var_email . "</button></div>";
			echo "<div class = \"long spaced overflow floatleft\"><button onclick=\"bb_set_hidden('" . $i . "'); return false;\" class = \"link" . $strong . "\">" . $var_date . "</button></div>";
			echo "<input name=\"f" . (string)$i . "\" type=\"checkbox\" class=\"queue_super_short spaced \" value=\"Y\" />";
			echo "<input name=\"u" . (string)$i . "\" type=\"hidden\" value=\"" . imap_uid($mbox, $header->Msgno) . "\" />";
			echo "<div class = \"clear\"></div>";
			}
		echo "</div>"; //end container
		echo "<div class=\"queue_div_width\">"; //buttons
		echo "<div class=\"floatleft\">"; //buttons
		$params = array("class"=>"spaced","onclick"=>"bb_clear_module()", "label"=>"Clear Module");
		$main->echo_script_button("clear_module", $params);
		$params = array("class"=>"spaced","number"=>2,"target"=>"bb_input", "passthis"=>true, "label"=>"Add To Input");
		$main->echo_button("add_to_input", $params);
   		echo "</div>"; 
		echo "<div class=\"floatright\">";
		$params = array("class"=>"spaced","number"=>3,"target"=>"bb_queue", "passthis"=>true, "label"=>"Delete Emails");
		$main->echo_button("delete_emails", $params);
		$params = array("class"=>"spaced","number"=>4,"target"=>"bb_queue", "passthis"=>true, "label"=>"Mark Emails");
		$main->echo_button("mark_emails", $params);
		echo "</div>";
		echo "</div>";
		echo "<div class = \"clear\"></div>";	
		}
	 //hidden values + state
	 echo "<input name=\"email_number\" type=\"hidden\" value=\"" . $email_number . "\" />";
	 		     
    //return selected message
	if ($email_number <> -1)
		{
		$header = imap_header($mbox, $email_number);
		$msg = getmsg($mbox, $email_number);
		$trans_htmlmsg = iconv($msg->charset,  "UTF-8//TRANSLIT//IGNORE", $msg->htmlmsg);
		$var_personal = isset($header->from[0]->personal) ? (string)$header->from[0]->personal : "";
		$var_email = $header->from[0]->mailbox . "@" . $header->from[0]->host;
		$var_date = date('Y-m-d h:i A', strtotime($header->date));
		$arr_subject = imap_mime_header_decode($header->subject);
		if ($arr_subject[0]->charset <> "default")
			{
			$var_subject = iconv($arr_subject[0]->charset,  "UTF-8//TRANSLIT//IGNORE", $arr_subject[0]->text);
			}
		else
			{
			$var_subject = $arr_subject[0]->text;
			}
			
		//get glean row_type -- standard email headers for guest pack and viewer pack
		$row_type = 0;
		$post_record = false;
		$arr_layouts = $main->get_json($con, "bb_layout_names");
		$arr_layouts_reduced = $main->filter_keys($arr_layouts);
		$arr_columns = $main->get_json($con, "bb_column_names");		
		
		if (substr($var_subject,0,12) == "Record Add: " && preg_match("/^[A-Z][-][A-Z]\d+/", substr($var_subject,12)))
			{
			$post_record = true;
			$post_key = substr($var_subject,15);
			if (ctype_digit($post_key))
				{
				$query = "SELECT 1 FROM data_table WHERE id = " . $post_key . ";";
				$result = $main->query($con, $query);
				$cnt_rows = pg_num_rows($result);
				if ($cnt_rows)
					{
					$row_type = ord(substr($var_subject,12,1)) - 64;
					$arr_layout = $arr_layouts_reduced[$row_type];
					if ($arr_layout['parent'] == 0)
						{
						$row_type = 0;	
						}					
					}
				}  
			}
		elseif (substr($var_subject,0,13) == "Record Edit: " && preg_match("/^[A-Z]\d+/", substr($var_subject,13)))
			{
			$post_record = true;
			$post_key = substr($var_subject,14);
			if (ctype_digit($post_key))
				{
				$query = "SELECT 1 FROM data_table WHERE id = " . $post_key . ";";
				$result = $main->query($con, $query);
				$cnt_rows = pg_num_rows($result);
				$row_type = ($cnt_rows == 0) ? 0 : ord(substr($var_subject,13,1)) - 64;
				}
			}
		elseif (substr($var_subject,0,12) == "Record New: " && preg_match("/^[A-Z]$/", substr($var_subject,12)))
			{
			$post_record = true;			
			$row_type = ord(substr($var_subject,12,1)) - 64;
			$arr_layout = $arr_layouts_reduced[$row_type];
			if ($arr_layout['parent'] > 0)
				{
				$row_type = 0;	
				}
			}
		
		//Record Add, New or Edit
		if ($row_type > 0)
			{
			$arr_column_reduced = $main->filter_keys($arr_columns[$row_type]);
			}
		
		//Check input state
		if ((!$post_record) && ($row_type == 0))
			{
			$temp_state = json_decode($array_state['bb_input_bb_state'], true);
			$row_type = isset($temp_state['row_type']) ? $temp_state['row_type'] : 0;
			if ($row_type > 0)
				{
				$arr_column_reduced = $main->filter_keys($arr_columns[$row_type]);
				}
			}
		
		//subject is dependent on email number, no need to include in state  
	    echo "<input name=\"subject\" type=\"hidden\" value=\"" . $var_subject . "\" />";
        
		echo "<div id=\"clipboard\" class=\"spaced padded floatleft border overflow queue_clipboard\"></div>";
		echo "<div class=\"clear\"></div>";
		echo "<div class=\"padded left border queue_subject\" onMouseUp=\"bb_get_selected_text(); return false;\">" . $var_personal . " &lt;" . $var_email . "&gt;</div>";	
		echo "<div class=\"padded left border queue_subject\" onMouseUp=\"bb_get_selected_text(); return false;\">" . $var_subject . "</div>";
		echo "<div class=\"padded left border queue_subject\" onMouseUp=\"bb_get_selected_text(); return false;\">" . $var_date . "</div>";
		
		echo "<div class=\"border padded floatleft queue_email_body\" onMouseUp=\"bb_get_selected_text()\">" . $trans_htmlmsg . "</div>";
		$i = 1;
	    //visable queue fields
		echo "<div class=\"floatleft\"><ul class=\"nobullets noindent\">";
		if (!empty($arr_column_reduced))
			{
			foreach($arr_column_reduced as $key => $value)
				{
				$col = $main->pad("c", $key);
				echo "<input name=\"" . $col . "\" type=\"hidden\" value=\"\" />";
				echo "<li class=\"noindent\"><button type=\"button\" class=\"link spaced padded\" onclick=\"bb_set_field('" . $col . "'); return false;\">" . $value['name'] . "</button></li>";
				echo "<li class=\"noindent\"><span id=\"" . $col . "\" class=\"spaced\"></span></li>";
				$i++;
				}
			}
		echo "</ul></div>";
		echo "<div class=\"clear\"></div>";
		}    
	imap_close($mbox);
	endif; //long colon if
$main->echo_state($array_state);
$main->echo_form_end();
/* END FORM */
?>