<?php
/*
Copyright (C) 2012 - 2015  Kermit Will Richardson, Brimbox LLC

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

/* This is a LESS type compiler that works just with variables for substitutions and imports of LESS files */
/* This is useful when you just want variable substituions and don't need the rest of the LESS specification */
/* This DOES NOT fully compile LESS and reads the whole LESS file being rendered in one string (careful with large files) */
/* This will allow imports of LESS variable files and renders the variables and substitutions in order declared */
/* This removes comments and whitespace rendering CSS as open styles for inclusion between style tags (not a CSS file) */
/* Dies on 4 errors, bad file extension, bad file or import statement, bad variable name, or empty variable value */

class bb_less_substituter {
    
    //main function
    function parse_less_file($filename)
        {
        //get primary LESS file
        $arr_css = $this->load($filename);
        //add eol for ending
        array_push($arr_css, "\n");
        $i = 0;
        
        //descend into recursion
        $this->recurse($arr_css, $arr_trans, $i);
        }
        
    //load file into string and explode into array    
    protected function load($filename)
        {
        $file_parts = pathinfo($filename);
        if (strcasecmp($file_parts['extension'],"less"))
            {
            die("File must have LESS (.less) extension");    
            }
        $file_string = file_get_contents($filename);
        if (!$file_string)
            {
            die ("Missing LESS Variable File");
            }
        //remove comments
        $file_string = preg_replace('!/\*.*?\*/!s', '', $file_string);
        //turn into tokens
        $arr_less = explode(";", $file_string);
        $arr_less = array_map('trim', $arr_less);
        $arr_less = array_filter($arr_less);
        return $arr_less;
        }
    
    //recurse and parse file substitutiing, importing and outputing     
    protected function recurse(&$arr_css, &$arr_trans, &$i)
        {
        $next = $arr_css[$i];
        $i++;
        //import line
        if  (substr($next,0,7) == "@import")
            {
            preg_match('/"([^"]+)"/', $next, $file_array);
            $arr_splice = $this->load($file_array[1]);
            array_splice($arr_css,$i+1,0,$arr_splice);
            $this->recurse($arr_css, $arr_trans, $i);   
            }
        //variable
        elseif (substr($next,0,1) == "@")
            {
            $arr_vars = explode(":", $next, 2);
            $arr_vars = array_map('trim', $arr_vars);
            if (preg_match("/[^@A-Za-z0-9_-]/" ,$arr_vars[0]))
                {
                die("Invalid LESS character(s) in variable name. ($arr_vars[0])");    
                }
            if (empty($arr_vars[1]) && ($arr_vars[1] !== "0"))
                {
                die("Empty LESS variable value. ($arr_vars[1])");     
                }
            $arr_trans[$arr_vars[0]] = $arr_vars[1];
            $this->recurse($arr_css, $arr_trans, $i);
            }
        //substitution
        elseif ($next <> "\n")
            {
            $next = strtr($next, $arr_trans);
            echo $next . ";";
            $this->recurse($arr_css, $arr_trans, $i);
            }
        //sucessful parse
        else
            {
            return false;   
            }
        }
} //end class


?>