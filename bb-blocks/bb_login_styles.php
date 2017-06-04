<style>
/* These are the login styles */
body, input {
	font-size: 13pt;
	font-family: Arial, Helvetica, sans-serif;
	line-height: 140%;
}

#bb_index {
	margin: auto;
	position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
	width: 320px;
	height: 320px;
	text-align: center;
}

#bb_image {
	margin: auto;
	background-image: url("bb-config/login_image.gif");
	width: 300px;
	height: 50px;
	background-repeat: no-repeat;
	background-position: center;
}

#bb_wrap {
	width: 310px;
	padding: 2px 4px; 
	border: 1px solid #A070B6;
}

.bb_input {
	border: 1px solid #A070B6;
	padding: 4px;
	margin: 2px 0;
	width: 100%;
}

#bb_message_error {
	text-align: left;
	border-left: 2px solid red;
	margin: 20px 0;
	padding-left: 5px;
}

#bb_message_notice {
	text-align: left;
	border-left: 2px solid #A070B6;
	margin: 20px 0;
	padding-left: 5px;
}

#bb_submit[type=submit], #bb_send[type=submit], #bb_set[type=submit] {
	background-color: #F2EAFF;
	border: 1px solid #A070B6;
	width: 320px;
	display: block;
	margin: 10px auto;
	padding: 6px;
	cursor: pointer;
	border-radius: 2px;
	-moz-border-radius: 2px;
	-webkit-border-radius: 2px;
	-o-border-radius: 2px;
}

#bb_reset[type=submit], #bb_home[type=submit], #bb_hide[type=button] {
	margin:auto;
	padding: 3px;
	border: 0;
	background: transparent;
	color: blue;
	text-decoration: underline;
	cursor: pointer;
	outline:none;
}
</style>
