<?php
include ("bb-classes/phpmailer/PHPMailerAutoload.php");

$email_host = defined(SMTP_SERVER) ? SMTP_SERVER : EMAIL_SERVER;
$email_port = defined(SMTP_PORT) ? SMTP_PORT : 465;
$email_subject = PAGE_TITLE . " Password Reset";
$email_address = EMAIL_ADDRESS;
$email_password = EMAIL_PASSWORD;
$email_from = "Password Reset";

$mail = new PHPMailer();
$mail->IsSMTP(); // set mailer to use SMTP
$mail->SMTPAuth = true;
$mail->SMTPSecure = "ssl";

$mail->Host = $email_host; // specify main and backup server
$mail->Username = $email_address; // SMTP username
$mail->Password = $email_password; // SMTP password
$mail->From = $email_address;
$mail->Port = $email_port;

$mail->FromName = $email_from;
$mail->AddAddress($email, $name);

$mail->IsHTML(true); //set email format to HTML
$mail->Subject = $email_subject;
$mail->Body = "<p>Password reset link for $name:</p><p>Username: $username <br>Email: $email </p><p>$reset_link</p>";
$mail->AltBody = "Password reset link for:\r\n\r\nUsername: $username\r\nEmail: $email\r\n\r\n$resetlink";

?>