<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
Copyright (C) 2012 - 2013  Kermit Will Richardson, Brimbox LLC

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

/* VERSION INFORMATION */
define('BRIMBOX_PROGRAM', '1.6');
define('BRIMBOX_DATABASE', '1.24');
define('BRIMBOX_BACKUP', '1.4');

# STANDARD INTERFACE #
//global header array includes required interface information
$array_header['bb_brimbox']['interface_name'] = "Brimbox";
//careful, standard install must have 0-5 populated, names may be changed, database stores the integer values
//$array_userroles -- required
$array_header['bb_brimbox']['userroles'] = array(0=>'Locked',1=>'Guest',2=>'Viewer',3=>'User',4=>'Superuser',5=>'Admin');
//careful, standard install must have 0-5 populated, names may be changed, database stores the integer values
//$array_module_types -- required
$array_header['bb_brimbox']['module_types'] = array(1=>"Guest",2=>"Viewer",3=>"Tab",4=>"Setup",5=>"Admin");

# DEFAULT SECURITY #
$array_header['bb_brimbox']['row_security'] = array(); //empty array causes standard 0 & 1 checkboxes
//row_security = array(0 => "Open", 1 => "Guarded", 2 => "Management"); //Populated example
$array_header['bb_brimbox']['row_archive'] = array(); //empty array causes standard 0 & 1 checkboxes
//row_archive = array(0 => "Current", 1 => "Level 1", 2 => "Level 2"); //Populated example
$array_header['bb_brimbox']['layout_security'] = array(); //empty array causes standard 0 & 1 checkboxes
//layout_security = array(0 => "Open", 1 => "Guarded", 2 => "Management"); //Populated example
$array_header['bb_brimbox']['column_security'] = array(); //empty array causes standard 0 & 1 checkboxes
//column_security = array(0 => "Current", 1 => "Level 1", 2 => "Level 2"); //Populated example

# DATA VALIDATION TYPES #
//can be added to
$array_header['bb_brimbox']['validation']['text'] = array('function'=>'bb_validate::validate_text','name'=>"Text",'use'=>"Required");
$array_header['bb_brimbox']['validation']['numeric'] = array('function'=>'bb_validate::validate_numeric','name'=>"Number",'use'=>"Required");
$array_header['bb_brimbox']['validation']['date'] = array('function'=>'bb_validate::validate_date','name'=>"Date",'use'=>"Required");
$array_header['bb_brimbox']['validation']['email']	= array('function'=>'bb_validate::validate_email','name'=>"Email",'use'=>"Required");
$array_header['bb_brimbox']['validation']['money']	= array('function'=>'bb_validate::validate_money','name'=>"Money",'use'=>"Required");
$array_header['bb_brimbox']['validation']['yesno'] = array('function'=>'bb_validate::validate_yesno','name'=>"Yes/No",'use'=>"Required");

# GUEST INDEX #
//data table has one customizable full text column aside from the default column
$array_header['bb_brimbox']['guest_index'] = array(); //will use default security = 0 and search = 1
?>