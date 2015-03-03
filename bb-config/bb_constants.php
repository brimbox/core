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

/* STANDARD LAYOUT OPTIONS */
define('BB_RETURN_ROWS', 4);
// Standard number pages in the page selector
define('BB_PAGINATION', 15);
//number of layouts possible, must be less than 26
define('BB_NUMBER_LAYOUTS', 12);
//Archive interworking (ON/OFF), allows for quick archive access on lookup and search tabs
define('BB_ARCHIVE_INTERWORKING', 'OFF');
// Default userrole on the manage users page
define('BB_DEFAULT_USERROLE_ASSIGN', '1_bb_brimbox');
// Use processing image in standard modules
define('BB_PROCESSING_IMAGE', 'OFF');
//turn on log for insert
define('BB_INPUT_INSERT_LOG', 'OFF');
//turn on log for update
define('BB_INPUT_UPDATE_LOG', 'OFF');
//turn on log for insert
define('BB_DELETE_LOG', 'OFF');
//turn on log for update
define('BB_ARCHIVE_LOG', 'OFF');
//turn on post override for archive
define('BB_INPUT_ARCHIVE_POST', 'OFF');
//turn on post override for secure
define('BB_INPUT_SECURE_POST', 'OFF');
//comma separated dtring of integers, usually between 1 and 5
define('BB_FILE_DOWNLOAD_PERMISSIONS', '3,4,5');
?>