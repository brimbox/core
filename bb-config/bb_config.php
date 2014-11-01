<?php
/*
Copyright (C) 2012  Kermit Will Richardson, Brimbox LLC

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
  
/* NO HTML OUTPUT */

/* DATABASE SETTINGS */
/** Postgres Friendly database name */
define('DB_FRIENDLY_NAME', 'Contactagon');  
/** Postgres hostname */
define('DB_HOST', 'localhost');  
/** The name of the master database for Brimbox */
define('DB_NAME', 'contact1_test');
/** Postgres database username */
define('DB_USER', 'contact1_test');
/** Postgres database username */
define('DB_OWNER', 'contact1');
/** Postgres database password */
define('DB_PASSWORD', 'tRout456'); //tRout456

/* EMAIL SETTINGS, get this information from system administrator */  
// Email server
define('EMAIL_SERVER', 'mail.contactagon.com');
// Email address
define('EMAIL_ADDRESS', 'test@contactagon.com');
// Email password
define('EMAIL_PASSWORD', 'trout187');
// Email configuration option (port and then flags separated by "/")
// Might have to play around to find the fastest loading configuration
// see http://php.net/manual/en/function.imap-open.php
// Example: 993/imap/ssl/novalidate-cert
// Example: 143/imap/novalidate-cert
// Example: 110/pop3/novalidate-cert
define('EMAIL_IMAP_OPTIONS', '993/imap/ssl/novalidate-cert');

/* PROGRAM VARIABLES AND DEFAULTS*/
// Browser page title
define('PAGE_TITLE', 'Contactagon');
// Database Timezone, any valid PHP timezone 
define('DB_TIMEZONE', 'America/New_York');
// User Timezone, any valid PHP timezone 
define('USER_TIMEZONE', 'America/New_York');
// Standard number of rows returned at once

/*PROGRAM LOCK FOR ADMINISTRATORS FUNCTIONALITY */
// Can be both ADMIN_ONLY and SINGLE_USER_ONLY
// Allow only admins to use the database (YES/NO)
define('ADMIN_ONLY', 'NO');
// Allow only a single user to user the database (empty string/username)
define('SINGLE_USER_ONLY', '');
?>
