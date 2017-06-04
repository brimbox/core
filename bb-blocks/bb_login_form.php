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
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo PAGE_TITLE; ?></title>
</head>

<body>
	<div id="bb_index">
		<div id="bb_image"></div>
		<div id="bb_message_notice"><?php if (isset($message)) echo $message; ?></div>
		<?php /* REGULAR LOGIN */ ?>
		<?php if (in_array(strtolower($_GET['action']), array("nomail", "sent", "done")) || empty($_GET['action'])): ?>
		<form id="bb_login" name="bb_login" method="post">
			<div id="bb_wrap">
				<input class="bb_input" name="username" id="username" placeholder="Username" type="text" />
				<input class="bb_input" name="password" id="password" placeholder="Password" type="password" />
			</div>
			<input id="bb_submit" name="bb_submit" type="submit" value="Login">
			<input id="bb_reset" name="bb_reset" type="submit" value="Reset Password">		
		</form>
		<?php /* SEND PASSWORD RESET EMAIL */ ?>
		<?php
elseif (in_array(strtolower($_GET['action']), array("reset", "nouser", "expired"))): ?>
		<form id="bb_login" name="bb_login" method="post">
			<div id="bb_wrap">
				<input class="bb_input" name="username_or_email" id="username_or_email" placeholder="Username or Email" type="text" />
			</div>
			<input id="bb_send" name="bb_send" type="submit" value="Send Reset Email">
			<input id="bb_home" name="bb_home" type="submit" value="Login Home">
		</form>
		<?php /* SET PASSWORD */ ?>
		<?php
elseif (in_array(strtolower($_GET['action']), array("weak", "set"))): ?>
		<?php //generate password with php and put show/hide password anonymous function in javascript event handler
    $possible_chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $required_chars = mb_substr("ABCDEFGHJKLMNPQRSTUVWXYZ", rand(0, 23), 1) . mb_substr("abcdefghijkmnpqrstuvwxyz", rand(0, 23), 1) . mb_substr("23456789", rand(0, 7), 1);
    for ($i = 0, $suggested_password = $required_chars;$i < 5;$i++) {
        $index = rand(0, 55);
        $suggested_password.= mb_substr($possible_chars, $index, 1);
    }
    $suggested_password = str_shuffle($suggested_password);
?>
		<form id="bb_login" name="bb_login" method="post">
			<div id="bb_wrap">
				<input class="bb_input" name="password_set" id="passsword_set" placeholder="New Password" type="text" value="<?php echo $suggested_password; ?>"/>
			</div>
			<input id="bb_set" name="bb_set" type="submit" value="Set Password">
			<input id="bb_hide" name="bb_hide" type="button" value="Hide Password"
			onclick="(function(e) {
			var ele = document.getElementById('passsword_set');
			if (ele.type == 'password') {ele.type = 'text'; e.value = 'Hide Password';}
			else {ele.type = 'password'; e.value = 'Show Password';} })(this);"> - 
			<input id="bb_home" name="bb_home" type="submit" value="Login Home">
		</form>
		<?php
endif; ?>
	</div>
</body>

</html>

