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
//Standard number of rows to return
define('BB_RETURN_ROWS', 4);
//Standard number pages in the page selector
define('BB_PAGINATION', 5);
//number of layouts possible, maximum is 26
define('BB_NUMBER_LAYOUTS', 12);
//number of layouts possible, maximum is 26
define('BB_STANDARD_LENGTH', 255);
//number of layouts possible, maximum is 26
define('BB_NOTE_LENGTH', 65536);
//Archive interworking (ON/OFF), allows for quick archive access on lookup and search tabs
define('BB_ARCHIVE_INTERWORKING', 'OFF');
//Default userrole on the manage users page
//example and default '0_bb_brimbox'
define('BB_DEFAULT_USERROLE_ASSIGN', '');
//Use processing image in standard modules (ON/OFF)
define('BB_PROCESSING_IMAGE', 'OFF');
//Turn on log for insert (ON/OFF)
define('BB_INPUT_INSERT_LOG', 'OFF');
//Turn on log for update (ON/OFF)
define('BB_INPUT_UPDATE_LOG', 'OFF');
//turn on log for delete (ON/OFF)
define('BB_DELETE_LOG', 'OFF');
//turn on log for update (ON/OFF)
define('BB_ARCHIVE_LOG', 'OFF');
//turn on post override for archive when inputting (ON/OFF)
define('BB_INPUT_ARCHIVE_POST', 'OFF');
//turn on post override for secure when inputting (ON/OFF)
define('BB_INPUT_SECURE_POST', 'OFF'); 
//comma separated string of userroles
//example and default '3_bb_brimbox,4_bb_brimbox,5_bb_brimbox'
define('BB_FILE_DOWNLOAD_PERMISSIONS', '');
//comma separated string of of userroles
//example and default '3_bb_brimbox,4_bb_brimbox,5_bb_brimbox'
define('BB_DOCUMENT_DOWNLOAD_PERMISSIONS', '');
//Browser notice, either blank to ignore or string message, standard navigation warning below
//Database users should use tabs, buttons, and links to navigate. Browser back and forward buttons are not recommended.
define('BB_CONTROLLER_MESSAGE', '');
//"word" to search by whole word is default, "begin" to search by beginning of all tokens searched for
define('BB_FULLTEXT_STATE', '');
?>