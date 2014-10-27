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
    
    function __construct()
        {
        global $array_master;
        
        $this->arr_maintain_state = $arr_maintain_state = array(0=>'No',1=>'Yes');
        $this->arr_required = array("@module_path","@module_name","@friendly_name","@interface","@module_type","@module_version","@maintain_state");;
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
        if (isset($arr_module['@module_name']))
            {
            $message = $this->error_waterfall($arr_module);
            if ($message)
                {
                return $message;
                }
                
            //MAJOR FUNCTION CALL   
            $message = $this->format_details($arr_module);
            if ($message)
                {
                return $message;
                }
                /* get database representation (the numeric keys) of certain module variables*/
            }            
                
        //message will be empty if all's well
        return false;     
        }

    //this function parses the module header creating $arr_module
    //$arr_module contains all the module variable
    protected function build_module_array(&$arr_module)
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
            return "Error: Could not find comment block with Module header.";       
            }

        foreach ($comments[1] as $comment)
            {
            //if if finds @module_name returns with no dups return first first comment block
            //else returns couldn't find module error;
            //this checks first line in comment for valid module name
            //check for installed file name, will check further later
            $pattern = "/^\s*?(@module_name|@included)\s*?=[^\\/?*:;{}\\\\]+;/i";            
            if (preg_match($pattern, trim($comment)))
                {
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
                        return "Error: Duplicate module variable name. Module declarations must be unique.";
                        }
                    else
                        {
                        $arr_module[trim(strtolower($arr_pair[0]))] = trim($arr_pair[1]);    
                        }
                    } //end foreach
                //check for multiple declarations
                if (count(array_intersect(array_keys($arr_module),array("@module_name", "@included"))) > 1)
                    {
                    return "Error: Can only define one of @module_name or @included.";   
                    }
                //found header
                return false; 
                } // end if
            } //end foreach
        //did not find header
        return  "Error: Module Name or Included not set. Must specify module parameter \"@module_name\" or  \"@included\" in all optional PHP module files.";        
        } //end function
    
    /* ERROR WATERFALL */
    //arr_module passed as a value, check arr_module for error
    //this function checks $arr_module for errors
    protected function error_waterfall(&$arr_module)
        {
        //check for valid module name
        $pattern = "/[^A-Za-z0-9_]/";
        if (preg_match($pattern, $arr_module['@module_name']))
            {
            //module name must be the same as principle php file name wihtout the .php extension
            //any other files should contain the principle php file name + _extra, or _css or _javascript etc
            return "Error: Module name must contain only alphanumeric characters or underscores.";
            }
            
        if (preg_match($pattern, $arr_module['@interface']))
            {
            //module name must be the same as principle php file name wihtout the .php extension
            //any other files should contain the principle php file name + _extra, or _css or _javascript etc
            return"Error: Interface name must contain only alphanumeric characters or underscores.";
            }
    
        //check that file name matches module name
        $pattern = "/" . $arr_module['@module_name'] . ".php$/";
        if (!preg_match($pattern, $arr_module['@module_path']))
            {
            //module name must be the same as principle php file name wihtout the .php extension
            //any other files should contain the principle php file name + extra, or _css or javascript etc
            return "Error: Module name does not match file name. Module name must be the file name (without the extension).";
            }
       
        //checks json declarations, will ignore all declarations not starting with @json
        foreach ($arr_module as $key => $value)
            {
            $pattern_1 = "/^@json-.*/";
            if (preg_match($pattern_1, $key))
                {            
                $pattern_2 = "/^" . $arr_module['@module_name'] . ".*/";
                if (!preg_match($pattern_2, substr($key,6)))
                    {
                    return "Error: Invalid JSON lookup specification. Lookup value must start with module name.";
                    }
                //check for valid xml
                if (!json_decode($value) && ($value <> "[]"))
                    {
                    return "Error: Invalid JSON markup in module header. Please properly form your JSON in module declaration.";
                    }
                }
            }   
    
        //check for the required variables
        $arr_keys = array_keys($arr_module);
        $arr_intersect = array_intersect($this->arr_required, $arr_keys);
        if (count($arr_intersect) <> count($this->arr_required))
            {
            return "Error: Required module variable missing. Certain module variables are required in the module definition.";
            }
        
        //check if global interface array is set, only then can you check userroles and module types
        if (in_array($arr_module['@interface'], array_keys($this->arr_master)))
            {     
            //check the module types
            //tricky to validate ints, deal with value as a string
             if (filter_var((string)$arr_module['@module_type'], FILTER_VALIDATE_INT))
                {
                $arr_keys = array_keys($this->arr_master[$arr_module['@interface']]['module_types']);
                $arr_keys = array_unique($arr_keys + array(0,-1,-2));
                if (!in_array((string)$arr_module['@module_type'], array_map('strval',$arr_keys)))
                    {
                    return "Error: Invalid module type supplied in module header. Module type must correspond to module type keys global array.";
                    }
                }
            else
                {
                $arr_values = $this->arr_master[$arr_module['@interface']]['module_types'];
                unset($arr_values[0], $arr_values[-1], $arr_values[-2]);
                $arr_values = array_map('strtolower', $arr_values + array(0=>"hidden", -1=>"global", -2=>"function"));
                if (!in_array(strtolower($arr_module['@module_type']), $arr_values))
                    {
                    return "Error: Invalid module type supplied in module header. Module type must correspond to module type keys global array.";        
                    }
                //module type set to numeric value for insert/update
                $arr_module['@module_type'] = array_search(strtolower($arr_module['@module_type']), $arr_values);
                }
            }

        //check the maintaion state variable
        //tricky to validate ints, deal with value as a string
        if (filter_var((string)$arr_module['@maintain_state'], FILTER_VALIDATE_INT))
            {
            if (!in_array((string)$arr_module['@maintain_state'], array_map('strval',array(1,0))))
                {
                return "Error: Invalid maintain state variable supplied in module header. Must be 1 or 0.";
                }
            }
        else
            {
            $arr_values = array(0=>"no", 1=>"yes");
            if (!in_array(strtolower($arr_module['@maintain_state']), $arr_values))
                {
                return "Error: Invalid module type supplied in module header. Module type must correspond to module type keys global array.";        
                }
            //maintain state set to 0 or 1
            $arr_module['@maintain_state'] = array_search(strtolower($arr_module['@maintain_state']), $arr_values);
            }            
            
        //made it
        return false;   
        }
        
    protected function format_details(&$arr_module)
        {         
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