<?php if (!defined('BASE_CHECK')) exit(); ?>
<!DOCTYPE html>    
<html>
<head>    
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo PAGE_TITLE; ?></title>
<?php
/* STANDARD JAVASCRIPT INCLUDE */
//this javascript necessary for the standard main class functions
//all other javascript generally included in specific modules by default
$javascript = $webpath . "/bb-utilities/bb_scripts.js";
$javascript = $main->filter("index_main_javascript", $javascript);
echo "<script src=\"" . $javascript . "\"></script>";
unset($javascript); //clean up

/* CUSTOM JAVASCRIPT INCLUDE */
//include file deals with path
$main->include_file($webpath . "/bb-config/bb_javascript.js", "js");

/* GET LESS SUBSTITUTER */
include($abspath . "/bb-less/bb_less_substituter.php");
$less = new bb_less_substituter();

/* LOAD CSS AND SUBSTITUTE LESS STYLES */
//standard style for customization
$main->include_file($webpath . "/bb-utilities/bb_styles.css", "css");
$less->parse_less_file($abspath . "/bb-utilities/bb_styles.less");

//styles for the box
$main->include_file($webpath . "/bb-utilities/bb_box.css", "css");
$less->parse_less_file($abspath . "/bb-utilities/bb_box.less");

/* CUSTOM CSS */
$main->include_file($webpath . "/bb-config/bb_css.css", "css");

?>
</head>
<body id="bb_brimbox">
<?php
/* PROCESSING IMAGE */
if (!$main->blank($image))
    {
    //seems to flush nicely without explicitly flushing the output buffer
    echo "<div id=\"bb_processor\"><img src=\"" . $image . "\"></div>";
    echo "<script>window.onload = function () { document.getElementById(\"bb_processor\").style.display = \"none\"; }</script>";
    }
 
/* CONTROLLER IMAGE AND MESSAGE */   
//echo tabs and links for each module
echo "<div id=\"bb_header\">";
//header image
echo "<div id=\"controller_image\"></div>";
//global message for all users
$controller_message = $main->get_constant('BB_CONTROLLER_MESSAGE', '');
if (!$main->blank($controller_message))
    {
    echo "<div id=\"controller_message\">" .  $controller_message . "</div>";    
    }

/* CONTROLLER ARRAY*/
//query the modules table to set up the interface
//setup initial variables from $array_interface
$arr_interface = $array_interface;
//module type 0 for hidden modules
$module_types = array(0);
//get the module types for current interface
foreach($array_interface as $key => $value)
	{
    //display appropriate modules, usertypes is array of numeric part of userroles
	if (in_array($usertype, $value['usertypes']))
		{
		array_push($module_types, $value['module_type']);
		}
	else
		{
        //unset interface type if permission invalid
		unset($arr_interface[$key]);	
		}
	};
//get modules type into string for query
$module_types = implode(",", array_unique($module_types));
//query modules table
$query = "SELECT * FROM modules_table WHERE standard_module IN (0,1,2,4,6) AND interface IN ('" . pg_escape_string($interface) . "') " .
         "AND module_type IN (" . pg_escape_string($module_types) . ") ORDER BY module_type, module_order;";
//echo "<p>" . $query . "</p>";
$result = pg_query($con, $query);

//populate controller arrays
while($row = pg_fetch_array($result))
    {
    //get the first module
    //check module type not hidden
    //check that file exists
    if (file_exists($row['module_path']))
        {
        //set module_path and type for include
        if ($module == $row['module_name'])
            {
            list($path, $type) = array($row['module_path'], $row['module_type']);    
            }
        //need to address controller by both module_type and module_name            
        if ($row['module_type'] > 0)
            {
            //$array[key][key] is easiest
            $arr_controller[$row['module_type']][$row['module_name']] = array('friendly_name'=>$row['friendly_name'],'module_path'=>$row['module_path'],'module_slug'=>$row['module_slug']);
            }
        }		
    }
/* END CONTROLLER ARRAY */

/* ECHO TABS */    
//set up standard tab and auxiliary header tabs
foreach ($arr_interface as $value)
	{
	$selected = ""; //reset selected
    //active module type
	if ($value['module_type'] == $type)
		{
		$interface_type = $value['interface_type'];
		}
    //layout standard tabs   
	if ($value['interface_type'] == 'Standard')
		{
		foreach ($arr_controller[$value['module_type']] as $module_work => $value)       
			{
			$selected = ($module == $module_work) ? "chosen" : "";
            $slug_work = $value['module_slug'];
            $submit_form_params = "[0,'$module_work','$slug_work', this]";
			echo "<button class=\"tabs " . $selected . "\" onclick=\"bb_submit_form(" . $submit_form_params . ")\">" . $value['friendly_name'] . "</button>";
			}
		}
    //layout auxiliary header tab
	elseif ($value['interface_type'] == 'Auxiliary')
		{
		//this section
		if (array_key_exists($value['module_type'], $arr_controller))
			{
			if (array_key_exists($module, $arr_controller[$value['module_type']]))
				{
				$selected = "chosen";
                $module_work = $module;
                $slug_work = $arr_controller[$value['module_type']][$module_work]['module_slug'];
                $submit_form_params = "[0,'$module_work','$slug_work', this]";
				}
			else
				{
				$module_work = key($arr_controller[$value['module_type']]);
                $slug_work = $arr_controller[$value['module_type']][$module_work]['module_slug'];
                $submit_form_params = "[0,'$module_work','$slug_work', this]";
				}
			echo "<button class=\"tabs " . $selected . "\"  onclick=\"bb_submit_form(" . $submit_form_params . ")\">" . $value['friendly_name'] . "</button>";
			}			
		}		
	}
/* END ECHO TABS */

/* LINE UNDER TABS */
//line either set under chosen tab or below all tabs and a hidden module
$lineclass = ($type == 0) ? "line" : "under";
echo "<div class=\"" . $lineclass  . "\"></div>";
echo "</div>"; //bb_header
/* END LINE UNDER TABS */

/* INCLUDE APPROPRIATE MODULE */
echo "<div id=\"bb_wrapper\">";
//Auxiliary tabs and links, 
if (isset($interface_type) &&  ($interface_type == 'Auxiliary'))
    {
    echo "<div id=\"bb_admin_menu\">";
    //echo auxiliary buttons on the side
    foreach ($arr_controller[$type] as $module_work => $value)
        {
        $slug_work = $value['module_slug'];
        $submit_form_params = "[0,'$module_work','$slug_work', this]";
        echo "<button class=\"menu\" name=\"" . $module_work . "_name\" value=\"" . $module_work . "_value\"  onclick=\"bb_submit_form(" . $submit_form_params . ")\">" . $value['friendly_name'] . "</button>";
        }
    echo "</div>";
    
    //clean up before include
	unset($result, $query, $key, $value, $arr_interface, $arr_controller, $interface_type, $lineclass, $module_work, $slug_work, $submit_form_params);
	//module include this is where modules are included
    echo "<div id=\"bb_admin_content\">";
    //$path is reserved, this "include" includes the current module    
    //the include must be done globally, will render standard php errors
    //if it bombs it bombs, the controller should still execute
    //Auxiliary type module is included here
    if (file_exists($path)) include($path);
    
    echo "</div>";
    echo "<div class=\"clear\"></div>";
    }
//Standard and Hidden tabs
else 
    {
    //clean up before include
    unset($result, $query, $key, $value, $arr_interface, $arr_controller, $interface_type, $lineclass, $module_work, $slug_work, $submit_form_params);
	//module include this is where modules are included
    echo "<div id=\"bb_content\">";
    //$path is reserved, this "include" includes the current module    
    //the include must be done globally, will render standard php errors
    //if it bombs it bombs, the controller should still execute
    //Standard type module is included here
    if (file_exists($path)) include($path);
    
    echo "</div>";	
    echo "<div class=\"clear\"></div>";
    }
echo "</div>"; //bb_wrapper
/* END INCLUDE MODULE */

//close connection -- make the database happy 
pg_close($con);
?>
</body>
</html>