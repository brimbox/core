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

/* PHP FUNCTIONS */
/* Class bb_validate() */
//bb_validate_text
//bb_validate_number
//bb_validate_date
//bb_validate_email
//bb_validate_money
//bb_validate_yesno
	
	
class bb_validate extends bb_link {	
	
	function validate_text(&$value, $error = false)
		{
		//validate text -- do nothing
		return false;
		}
		
	function validate_numeric(&$value, $error = false)
		{
		//validate numeric	
		if (!is_numeric($value))
			{
			$return_value = $error ? "Error: Must be numeric." : true;
			}
		else
			{
			$return_value = false;    
			}
		return $return_value;
		}
			
	function validate_date(&$value, $error = false)
		{
		//validate date
		date_default_timezone_set(USER_TIMEZONE);
		$new_value = strtotime($value);
		//blank is valid, handled at top if must be populated
		if ($new_value == false)
			{
			$return_value = $error ? "Error: Value is not a valid date." : true;
			}			
		else
			{
			//reformat value
			$value = date("Y-m-d",$new_value);
			$return_value = false;
			}
		return $return_value;	
		}
		
	function validate_email(&$value, $error = false)
		{
		//validate email	
		if (filter_var($value, FILTER_VALIDATE_EMAIL) == false)
			{
			$return_value = $error ? "Error: Value is not a valid email." : true;
			}
		else
			{
			$return_value = false;
			}
		return $return_value;	
		}
		
	function validate_money(&$value, $error = false)
		{
		//validate money	
		if (!is_numeric($value))
			{
			$return_value = $error ? "Error: Value must be monetary." : true;
			}
		else
			{
			$value = round($value,2);
			$value = (string)number_format($value, 2, '.', '');
			$return_value = false;
			}
		return $return_value;	
		}
			
	function validate_yesno(&$value, $error  = false)
		{
		//validate yes or no
		if (!in_array(strtolower($value),array("yes","no")))
			{
			$return_value = $error ? "Error: Value must be Yes or No." : true;
			}
		else
			{
			$value = ucfirst($value);
			$return_value = false;
			}
		//do not use else
		return $return_value;
		}
	
	} //end class
?>