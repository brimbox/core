<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
//define the header array ($array_header) here
//header arrays are available and used for all interfaces
//header arrays are not reformatted by the controller
//default arrays of this type are found in bb-utilities/bb_headers.php

//PHP include hierarchy in the conbtroller
//bb_admin_headers.php
//bb_admin_functions.php
//bb_admin_globals.php

# STANDARD INTERFACE #
//global header array includes required interface information
$array_header['bb_pad']['interface_name'] = "Brimbox Pad";
//careful, standard install must have 0-5 populated, names may be changed, database stores the integer values
//$array_userroles -- required
$array_header['bb_pad']['userroles'] = array(0=>'Locked',1=>'Guest',2=>'Viewer',3=>'User',4=>'Superuser',5=>'Admin');
//careful, standard install must have 0-5 populated, names may be changed, database stores the integer values
//$array_module_types -- required
$array_header['bb_pad']['module_types'] = array(1=>"Guest",2=>"Viewer",3=>"Tab",4=>"Setup",5=>"Admin");
//$array_module_types -- required
$array_header['bb_pad']['controller'] = "/pad.php";


# DEFAULT SECURITY #
$array_header['bb_pad']['row_security'] = array(); //empty array causes standard 0 & 1 checkboxes
//row_security = array(0 => "Open", 1 => "Guarded", 2 => "Management"); //Populated example
$array_header['bb_pad']['row_archive'] = array(); //empty array causes standard 0 & 1 checkboxes
//row_archive = array(0 => "Current", 1 => "Level 1", 2 => "Level 2"); //Populated example
$array_header['bb_pad']['layout_security'] = array(); //empty array causes standard 0 & 1 checkboxes
//layout_security = array(0 => "Open", 1 => "Guarded", 2 => "Management"); //Populated example
$array_header['bb_pad']['column_security'] = array(); //empty array causes standard 0 & 1 checkboxes
//column_security = array(0 => "Current", 1 => "Level 1", 2 => "Level 2"); //Populated example

# GUEST INDEX #
//data table has one customizable full text column aside from the default column
$array_header['bb_pad']['guest_index'] = array(); //will use default security = 0 and search = 1

?>