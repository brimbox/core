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
function parse_search_string($str)
    {
    /* MAIN FUNCTION */
    //check for empty string	
    if (trim($str) == "")
	{
	return array("","Enter Search Terms or Tokens");
	}
    else
        {
        //split up tokens
        $tokens= preg_split('/([\|&!\)\(\s])/', $str, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $tokens = array_diff($tokens, array(" "));
        $tokens = array_merge($tokens);
        
        //initial values
        $i = 0;
        $n = 0;
        $next = $tokens[$i];
        $message = "";
        
        //set up entrance to recursive descent parser
        //message and tokens passed by reference
        if ($next == "(") //open parentheses
            {
            $return = (int)open($tokens,$next,$i,$n,$message);
            }
        else //token or boolean not (!)
            {
            $return = (int)token($tokens,$next,$i,$n,$message);    
            }
        
        //deal with return values
        //unbalanced parentheses
        //error in boolean expression
        if (!empty($message))
            {
            return array("",$message);       
            }
        elseif ($n <> 0)
            {
            return array("","Search term(s) have unbalanced parenthesis");    
            }        
        //good
        else
            {
            $str_parsed = implode($tokens);
            //echo $str_parsed;
            return array($str_parsed,"Parsed");                     
            }        
        }    
    }
    /* END MAIN FUNCTION */


/* RECURSIVE DESCENT PARSING FUNCTIONS */
//also checks for balanced parentheses and adds ORs between search toekns
function same($regex, &$next, &$i, $tokens)
    {
    //check condition and advance pointer
    //echo "N" . $next ." ";
    //echo "S" . $regex."<br>";
    if (preg_match($regex, $next))
        {
        $i++;
        //echo "I" . $i . "<br>";
        $next = isset($tokens[$i]) ? $tokens[$i] : "";
        return true;
        }
    else
        {
        return false;
        }
    }    
 
function token(&$tokens, $next ,$i, &$n, &$message)
    {
    //pointer was a open parenthese, NOT, or operator
    if (same("/[^&!\|\(\)]/", $next, $i, $tokens))
        {
        operator($tokens, $next ,$i, $n, $message);
        }
    elseif (same("/!{1}/", $next, $i, $tokens))
        {
        token($tokens, $next ,$i, $n, $message);
        }
    else
        {
        $token = empty($tokens[$i-1]) ? $next : $tokens[$i-1];
        $message = "Error in boolean expression near " . $token . ".";
        return false;
        }
    }
    
function open(&$tokens,$next,$i,&$n, &$message)
    {
    //pointer was an boolean operator or NOT
    if (same("/\({1}/", $next, $i, $tokens))
        {
        $n++;    
        open($tokens, $next ,$i, $n, $message);
        }
    elseif (same("/!{1}/", $next, $i, $tokens))
        {
        open($tokens, $next ,$i, $n, $message);
        }    
    elseif (same("/[^&!\|\(\)]/", $next, $i, $tokens))
        {
        operator($tokens, $next ,$i, $n, $message);
        }
    else
        {
        $token = empty($tokens[$i-1]) ? $next : $tokens[$i-1];
        $message = "Error in boolean expression near " . $token . ".";
        return false;
        }
    }
    
function operator(&$tokens,$next,$i,&$n,&$message)
    {
    //pointer was a token or closed parentheses
    if (same("/[\|&]{1}/", $next, $i, $tokens))
        {
        open($tokens, $next ,$i, $n, $message);
        }
    elseif (same("/\){1}/", $next, $i, $tokens))
        {
        $n--;
        operator($tokens, $next ,$i, $n, $message);
        }
    elseif (same("/[^&!\|\(\)]/", $next, $i, $tokens))
        {
        array_splice($tokens,$i-1,0,"|");
        $i++;
        operator($tokens, $next ,$i, $n, $message);
        }
    elseif (empty($next))
        {
        $message = "";
        return false;    
        }
    else
        {
        $token = empty($tokens[$i-1]) ? $next : $tokens[$i-1];
        $message = "Error in boolean expression near " . $token . ".";
        return false;
        }
    }
?>