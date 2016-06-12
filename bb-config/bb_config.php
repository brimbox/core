<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
 * Copyright (C) Brimbox LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License Version 3 (“GNU GPL v3”)
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

/* NO HTML OUTPUT */

/* DATABASE SETTINGS */
// Postgres friendly database name
define('DB_FRIENDLY_NAME', 'Brimbox');
// Postgres hostname */
define('DB_HOST', 'localhost');
// Postgres database name */
define('DB_NAME', 'database');
// Postgres database username */
define('DB_USER', 'user');
// Postgres database password */
define('DB_PASSWORD', 'password');
// Postgres database owner, for standard cPanel installs */
define('DB_OWNER', 'owner');

/* EMAIL SETTINGS, get this information from system administrator */
// Email server
define('EMAIL_SERVER', 'incoming_server');
// Email address
define('EMAIL_ADDRESS', 'email_address');
// Email password
define('EMAIL_PASSWORD', 'email_password');
// Email configuration option (port and then flags separated by "/")
// Might have to play around to find the fastest loading configuration
// see http://php.net/manual/en/function.imap-open.php
// Example: 993/imap/ssl/novalidate-cert
// Example: 143/imap/novalidate-cert
// Example: 110/pop3/novalidate-cert
define('EMAIL_IMAP_OPTIONS', '993/imap/ssl/novalidate-cert');

/* PROGRAM VARIABLES */
// Browser page title
define('PAGE_TITLE', 'Brimbox');
// Database Timezone, any valid PHP timezone
define('DB_TIMEZONE', 'America/New_York');
// User Timezone, any valid PHP timezone
define('USER_TIMEZONE', 'America/New_York');

/* PROGRAM LOCK FOR ADMINISTRATORS FUNCTIONALITY */
// Allow only admins to use the database (YES/NO)
define('ADMIN_ONLY', 'NO');
// Allow only a single user to user the database (empty string/username)
define('SINGLE_USER_ONLY', '');
?>
