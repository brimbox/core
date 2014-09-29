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

class bb_manage_modules {
    
    function __construct($params)
        {
        global $array_master;
        
        $this->update_flag = $params['update_flag'];
        $this->arr_maintain_state = $params[0];
        $this->arr_required = $this->arr_total = $params[1];
        $this->arr_installed = $params[2];
        $this->arr_master = $array_master;
        }   


    //This checks the php files calling function build_module_array
    function get_modules($con, &$arr_module)
        {                
        //all possible header values    
        $path = $arr_module['@module_path'];
       
        //MAJOR FUNCTION CALL
        $message = $this->build_module_array($arr_module);        
        if ($message)
            {
            //exit on duplicate values or module name not set
            return $message;
            }
            
        //MAJOR FUNCTION CALL
        $message = $this->error_waterfall($arr_module);
        if ($message)
            {
            return $message;
            }
            
        //MAJOR FUNCTION CALL   
        $message = $this->check_json_values($arr_module);
        if ($message)
            {
            return $message;
            }

            
        //no error detected    
        /* get database representation (the numeric keys) of certain module variables*/
        /* ctype digit will return only positive ints */
        $arr_module['@module_type'] = ctype_digit($arr_module['@module_type']) ? $arr_module['@module_type'] : array_search(strtolower($arr_module['@module_type']), array_map('strtolower', $this->array_module_types));
        $arr_module['@maintain_state'] = ctype_digit($arr_module['@maintain_state']) ? $arr_module['@maintain_state'] : array_search(strtolower($arr_module['@maintain_state']), array_map('strtolower', $this->arr_maintain_state));
                
        //message will be empty if all's well
        return false;     
        }

    //this function parses the module header creating $arr_module
    //$arr_module contains all the module variable
    function build_module_array(&$arr_module)
        {
        //path is php path
        $path = $arr_module['@module_path'];
        //strip all the /* */ comments out of file
        $file = file_get_contents($path);
        //Empty error        
        $found_module_name = false;
        
        //tricky regex, accounts for new lines
        $count_comments = preg_match_all('/\/\*(.*?)\*\//sm', $file, $comments);
        
        if ($count_comments == 0)
            {
            $message = "Error: Could not find comment block with Module header.";    
            return $message;    
            }

        foreach ($comments[1] as $comment)
            {        
            //if if finds @module_name returns with no dups return first first comment block
            //else returns couldn't find module error;
            //this checks first line in comment for valid module name
            //check for installed file name, will check further later 
            $pattern = "/^\s*?@module_name\s*?=[^\\/?*:;{}\\\\]+;/i";            
            if (preg_match($pattern, trim($comment)))
                {
                $found_module_name = true;
                //only entered on first comment with module name
                //explode on semicolon
                $arr_pairs = explode(";", trim($comment));
                //explode produces empty value at end
                $arr_pairs = array_filter($arr_pairs);
                foreach($arr_pairs as $value)
                    {
                    //loop through and explode each pair
                    $arr_pair = explode("=", trim($value), 2);
                    //trim and put into key/value pairs
                    if (isset($arr_module[trim(strtolower($arr_pair[0]))]))
                        {
                        $message = "Error: Duplicate module variable name. Module declarations must be unique.";
                        return $message;
                        }
                    else
                        {
                        $arr_module[trim(strtolower($arr_pair[0]))] = trim($arr_pair[1]);    
                        }
                    } //end foreach
                } // end if
            } //end foreach
        //did not find header
        if (!$found_module_name)
            {
            $message = "Error: Module Name not 78n set. Must specify module parameter \"@module_name.\"";
            return $message;
            }
            
        return false;
        } //end function
    
/* ERROR WATERFALL */
//arr_module passed as a value, check arr_module for error
//this function checks $arr_module for errors
function error_waterfall($arr_module)
    {
    //check for valid module name
    $pattern = "/[^A-Za-z0-9_]/";
    if (preg_match($pattern, $arr_module['@module_name']))
        {
        //module name must be the same as principle php file name wihtout the .php extension
        //any other files should contain the principle php file name + _extra, or _css or _javascript etc
        $message = "Error: Module name must contain only alphanumeric characters or underscores.";
        return $message;
        }

    //check that file name matches module name
    $pattern = "/" . $arr_module['@module_name'] . ".php$/";
    if (!preg_match($pattern, $arr_module['@module_path']))
        {
        //module name must be the same as principle php file name wihtout the .php extension
        //any other files should contain the principle php file name + extra, or _css or javascript etc
        $message = "Error: Module name does not match file name. Module name must be the file name (without the extension).";
        return $message;
        }
   
    //checks xml declarations, will ignore all declarations not starting with @xml
    foreach ($arr_module as $key => $value)
        {
        $pattern_1 = "/^@json-.*/";
        if (preg_match($pattern_1, $key))
            {            
            $pattern_2 = "/^" . $arr_module['@module_name'] . ".*/";
            if (preg_match($pattern_2, substr($key,6)))
                {
                array_push($this->arr_total, $key);
                }
            else
                {
                $message = "Error: Invalid JSON lookup specification. Lookup value must start with module name.";
                return $message;
                }
            //check for valid xml
            if (json_decode($value) == null)
                {
                $message = "Error: Invalid JSON markup in module header. Please properly form your JSON in module declaration.";
                return $message;
                }
            }
        }

    //arr_installed must not be populated if install, must exist on update 
    if (in_array($arr_module['@module_name'], $this->arr_installed) && !$this->update_flag) 
        {
        //Module exists on install error, on install module cannot exist
        $message = "Error: Module " . $arr_module['@module_name'] . " has the name of a previously installed module. Module names must be unique on install.";    
        return $message;
        }
    if (!in_array($arr_module['@module_name'], $this->arr_installed) && $this->update_flag) 
        {
        //Module does not exists on update error, on update module must exist
        $message = "Error: Module " . $arr_module['@module_name'] . " not found. Module must exist on update.";
        return $message;
        }    

    //check for the required variables
    $arr_keys = array_keys($arr_module);
    $arr_intersect = array_intersect($this->arr_required, $arr_keys);
    if (count($arr_intersect) <> count($this->arr_required))
        {
        $message = "Error: Required module variable missing. Certain module variables are required in the module definition.";
        return $message;
        }
    
        //check the module types
    if (!in_array($arr_module['@interface'], array_keys($this->arr_master)))
        {
        $message = "Error: Invalid module type supplied in module header. Module type must correspond to module types global array.";
        return $message;
        }    

    //check the module types
    if (!in_array($arr_module['@module_type'], array_keys($this->arr_master[$arr_module['@interface']]['module_types'])))
        {
        $message = "Error: Invalid module type supplied in module header. Module type must correspond to module types global array.";
        return $message;
        }


    //check the maintaion state variable        
    if (!in_array($arr_module['@maintain_state'], array_keys($this->arr_maintain_state)))
        {
        $message = "Error: Invalid maintain state variable supplied in module header. Must be Yes or No.";
        return $message;
        }    
    //made it
    return false;   
    }
    
function check_json_values(&$arr_module)
    {
    foreach ($arr_module as $key => $value)
        {
        $pattern_1 = "/^@json-.*/";
        if (preg_match($pattern_1, $key))
            {
            if (!json_decode($value,true))
                {
                $message = "Error: JSON value not properly encoded.";
                return $message;
                }
            }
        }
         
    /* put extras, description, company etc into xml for details functionality */
    $arr_details = array();
    $pattern_1 = "/^@json-.*/";
    foreach ($arr_module as $key => $value)
        {
        //not required or json
        if (!in_array($key, $this->arr_required) && (!preg_match($pattern_1, $key)))
            {
            $str_details = substr($key, 1);
            $arr_details[$str_details] = $value;
            unset($arr_module[$key]);
            }          
        }            
    $arr_module['@module_details'] = json_encode($arr_details);
    //Not implemented
    $arr_module['@module_files'] = json_encode(array());
    return false;
    }
}
    /* END ERROR WATERFALL */        
?>