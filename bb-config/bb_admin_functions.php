<?php
# DEFAULT SESSION LEVEL SECURITY #
//You should declare an interface name
//$array_interface_name -- required
$array_master['bb_test']['interface_name'] = "Test";
//careful, standard install must have 0-5 populated, names may be changed, database stores the integer values
//$array_userroles -- required
$array_master['bb_test']['userroles'] = array(0=>'Locked',1=>'User');
//careful, standard install must have 0-5 populated, names may be changed, database stores the integer values
//$array_module_types -- required
$array_master['bb_test']['module_types'] = array(0=>"Hidden",1=>"Used",2=>"Unused");
//this defines the tab layout
//$array_interface_name -- required
//type -> string
//root -> string
//userroles -> array of integer
//module -> integer
//hidden -> array of integer

$array = array();
$array[] = array('interface_type'=>'Standard','userroles'=>array(1),'module_type'=>1);
$array_master['bb_test']['interface'] = $array;

?>