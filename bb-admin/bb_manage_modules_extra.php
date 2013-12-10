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
?>
<?php
/* PHP FUNCTIONS */
//get_modules (main function)
//build_module_array
//error waterfall

//message is empty if no error, populated if error

//There are many error reports in these modules, the appear in waterfall order

//This checks the php files calling function build_module_array
function get_modules($con, &$arr_module, $path, $arr_parameters, $update_flag)
    {
    //error message
    $message = "";
    
    $array_module_types = $arr_parameters[0];
    $array_userroles = $arr_parameters[1];
    $arr_maintain_state = $arr_parameters[2];
    $arr_required = $arr_parameters[3];
    $arr_extras = $arr_parameters[4];
    $arr_installed = $arr_parameters[5];
    
    //all possible header values    
    $arr_total = array_merge($arr_required, $arr_extras);
    $arr_module['@module_path'] = $path;
   
    //MAJOR FUNCTION CALL
    $message = build_module_array($arr_module, $path);            
    //array only populated if @module_name is set, blank it if not
    
    if (!isset($arr_module['@module_name']))
        {
        $arr_module = array();
        $arr_module['@phpfile'] = $path;   
        }
    else
        {
        //MAJOR FUNCTION CALL
        $message = error_waterfall($arr_module, $arr_parameters, $update_flag);    
        if (empty($message) && isset($arr_module['@module_name']))
            {
            /* get database representation (the numeric keys) of certain module variables*/
			/* ctype digit will return only positive ints */
			$arr_module['@module_type'] = ctype_digit($arr_module['@module_type']) ? $arr_module['@module_type'] : array_search(strtolower($arr_module['@module_type']), array_map('strtolower', $array_module_types));
			$arr_module['@maintain_state'] = ctype_digit($arr_module['@maintain_state']) ? $arr_module['@maintain_state'] : array_search(strtolower($arr_module['@maintain_state']), array_map('strtolower', $arr_maintain_state));
			$arr_module['@userrole'] = ctype_digit($arr_module['@userrole']) ? $arr_module['@userrole'] : array_search(strtolower($arr_module['@userrole']), array_map('strtolower', $array_userroles));
            
            /* put file paths and xml lookups into xml for deletion functionality*/
            $xml_files = simplexml_load_string("<files/>");
			//replace root
			$path = "bb-modules/" . substr($path, strlen("bb-temp/"));
            $xml_files->file = $path;
            
            foreach ($arr_module as $key => $value)
                {
                $pattern_1 = "/^@xml-.*/";
                if (preg_match($pattern_1, $key))
                    {
					$xml_files->addChild("xml")->{0} = substr($key,5);
                    }
                }
            //save in $arr_module
            $arr_module['@module_files'] = $xml_files->asXML();
            
            /* put extras, description, company etc into xml for details functionality */
            $xml_details = simplexml_load_string("<extras/>");
            foreach ($arr_extras as $value)
                {
                if (isset($arr_module[$value]))
                    {
					$str_extras = substr($value, 1);
                    $xml_details->$str_extras = $arr_module[$value];
                    unset($arr_module[$value]);
                    }            
                }
            //save in $arr_module
            $arr_module['@module_details'] = $xml_details->asXML();         
            }
        }
    //message will be empty if all's well
    return $message;     
    }

//this function parses the module header creating $arr_module
//$arr_module contains all the module variable
function build_module_array(&$arr_module, $path)
    {
	$message = "";
    //path is php path
    $arr_module['@module_path'] = $path;
    //strip all the /* */ comments out of file
    $file = file_get_contents($path);
    //tricky regex, accounts for new lines
    preg_match_all('/\/\*(.*?)\*\//sm', $file, $comments);
    
    foreach ($comments[1] as $comment)
        {        
        //if it cannot find module name it ignores the comment
        //this checks first line in comment for valid module name
      
        //check for installed file name, will check further later 
        $pattern = "/^\s*?@module_name\s*?=[^\\/?*:;{}\\\\]+;/i";
        if (preg_match($pattern, trim($comment)))
            {            
            //explode on semicolon
            $arr_pairs = explode(";", trim($comment));
            //explode produces empty value at end
            $arr_pairs = array_filter($arr_pairs);
            foreach($arr_pairs as $value)
                {
                //loop through and explode each pair
                $arr_pair = explode("=", trim($value), 2);
                if (array_key_exists(trim($arr_pair[0]),$arr_module))
                    {
                    //cannot build array with duplicate values, return error
                    $message = "Error: Duplicate module variable name. Module declarations must be unique.";
                    break;
                    }
                else
                    {
                    //trim and put into key/value pairs
                    $arr_module[trim(strtolower($arr_pair[0]))] = trim($arr_pair[1]);    
                    }
                } //end foreach
            } // end if
        } //end foreach
    return $message;
    } //end function
    
/* ERROR WATERFALL */
//arr_module passed as a value, check arr_module for error
//this function checks $arr_module for errors
function error_waterfall($arr_module, $arr_parameters, $update_flag)
    {
	$message = "";
	 
    $array_module_types= $arr_parameters[0];
    $array_userroles = $arr_parameters[1];
    $arr_maintain_state = $arr_parameters[2];
    $arr_required = $arr_parameters[3];
    $arr_extras = $arr_parameters[4];
    $arr_installed = $arr_parameters[5];
        
    //get all standard header values
    $arr_total = array_merge($arr_required, $arr_extras);   

    if (empty($message))
        {
        //check for valid module name
        $pattern = "/[^A-Za-z0-9_]/";
        if (preg_match($pattern, $arr_module['@module_name']))
            {
            //module name must be the same as principle php file name wihtout the .php extension
            //any other files should contain the principle php file name + _extra, or _css or _javascript etc
            $message = "Error: Module name must contains only alphanumeric characters or underscores.";    
            }
        }
    if (empty($message))
        {
        //check that file name matches module name
        $pattern = "/" . $arr_module['@module_name'] . ".php$/";
        if (!preg_match($pattern, $arr_module['@module_path']))
            {
            //module name must be the same as principle php file name wihtout the .php extension
            //any other files should contain the principle php file name + extra, or _css or javascript etc
            $message = "Error: Module name does not match file name. Module name must be the file name (without the extension).";    
            }
        }     
    if (empty($message))
        {
        //checks xml declarations, will ignore all declarations not starting with @xml
        foreach ($arr_module as $key => $value)
            {
            $pattern_1 = "/^@xml-.*/";
            if (preg_match($pattern_1, $key))
                {            
                $pattern_2 = "/^" . $arr_module['@module_name'] . ".*/";
                if (preg_match($pattern_2, substr($key,5)))
                    {
                    array_push($arr_total, $key);
                    }
                else
                    {
                    $message = "Error: Invalid xml lookup specification. Lookup value must start with module name.";    
                    }
                //check for valid xml
                @$xml_test = simplexml_load_string($value);
                if ($xml_test === false)
                    {
                    $message = "Error: Invalid xml markup in module header. Please properly form your xml in module declaration.";      
                    }
                }
            }
        }
    //arr_installed must not be populated if install, must exist on update
    if (empty($message))
        {    
        if (in_array($arr_module['@module_name'], $arr_installed) && !$update_flag) 
            {
            //Module exists on install error, on install module cannot exist
            $message = "Error: Module " . $arr_module['@module_name'] . " has the name of a previously installed module. Module names must be unique on install.";    
            }
        if (!in_array($arr_module['@module_name'], $arr_installed) && $update_flag) 
            {
            //Module does not exists on update error, on update module must exist
            $message = "Error: Module " . $arr_module['@module_name'] . " not found. Module must exist on update.";    
            }
        }    
    if (empty($message))
        {
        //check for the required variables
        $arr_keys = array_keys($arr_module);
        $arr_intersect = array_intersect($arr_required, $arr_keys);
        if (count($arr_intersect) <> count($arr_required))
            {
            $message = "Error: Required module variable missing. Certain module variables are required in the module definition.";
            }
        }
    if (empty($message))
        {
        $arr_keys = array_keys($arr_module);
        //check that variable are from possible variables
        $arr_diff = array_diff($arr_keys, $arr_total);
        if (count($arr_diff) > 0)
            {
            $message = "Error: Unknown module variable supplied. Only certain modules declarations are allowed.";    
            }
        }
        
    if (empty($message))
        {
        //check the module types
        if (!in_array($arr_module['@module_type'], array_keys($array_module_types)))
            {
            $message = "Error: Invalid module type supplied in module header. Module type must correspond to module types global array.";    
            }
        }
    if (empty($message))
        {
        //check the maintaion state variable        
        if (!in_array($arr_module['@maintain_state'], array_keys($arr_maintain_state)))
            {
            $message = "Error: Invalid maintain state variable supplied in module header. Must be Yes or No.";    
            }
        }
    if (empty($message))
        {
        //check the userrole
        if (!in_array($arr_module['@userrole'], array_keys($array_userroles)))
            {
            $message = "Error: Invalid userrole supplied in module header. Userrole must correspond to userroles global array.";    
            }
        }
    return $message;   
    }
    /* END ERROR WATERFALL */        
?>