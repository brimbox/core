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

/* LARGE MUTUALLY EXLUSIVE IF-ELSEIF */
//Load row_type from record links, populate state
//Load if layout dropdown changes, empty state
//Load row_type from state if coming from another tab
/* FIRST PASS */

/* EITHER ADD OR EDIT FROM RECORD LINK */
if (!empty($_POST['bb_row_type'])) 
    {
    //global form vars
    $xml_state = simplexml_load_string("<hold></hold>");//reset $xml_state
	
	$row_type = $main->set('row_type', $xml_state, $_POST['bb_row_type']);
	$row_join = $main->set('row_join', $xml_state, $_POST['bb_row_join']);
	$post_key = $main->set('post_key', $xml_state, $_POST['bb_post_key']);
    
    $layout = $main->pad("l", $row_type);
    $xml_column = $xml_columns->$layout;
    
    //populate from database if edit
    if ($row_type == $row_join)
        {
        $query = "SELECT * FROM data_table " .
                 "WHERE id = " . $post_key . ";";
        $result = $main->query($con, $query);
        $row = pg_fetch_array($result);
        
        
        foreach($xml_column->children() as $child)
            {				
            $col = $child->getName();
            if (in_array($col,$arr_notes))
                {
				$str = $main->custom_trim_string($row[$col], 65536, false);
                }
            else
                {
				$str = $main->custom_trim_string($row[$col],255);
                }
			$main->set($col, $xml_state, $str);
            }
        }
	}
    
/* SUBMIT FORM XML LOAD */
//standard postback from input form submit button -- populate xml_state
elseif ($main->post('bb_button',$module) == 1) //postback
    {
	$row_type = $main->process('row_type', $module, $xml_state, $default_row_type);
	$row_join = $main->process('row_join', $module, $xml_state, -1);
	$post_key = $main->process('post_key', $module, $xml_state, -1);

    $layout = $main->pad("l", $row_type, 2);
    $xml_column = $xml_columns->$layout;

    foreach($xml_column->children() as $child)
        {				
        $col = $child->getName();
        if (in_array($col,$arr_notes))
            {
			$str = $main->custom_trim_string($main->post($col,$module),65536,false);
            }
        else
            {
			$str = $main->custom_trim_string($main->post($col,$module),255);
            }
		$main->set($col, $xml_state, $str);
        }			
    }
/* FORM XML LOAD */

/* CLEAR FORM BUTTON  */
elseif ($main->post('bb_button',$module) == 2)
    {
	//reset to default row_type    
    if ($default_row_type > 0)
        {
        $layout = $main->pad("l", $default_row_type);
        $xml_column = $xml_columns->$layout;
        }
    //reset state
    $xml_state = simplexml_load_string("<hold></hold>");
	$row_type = $main->set("row_type", $xml_state, $default_row_type);
	$row_join = $main->set("row_join", $xml_state, -1);
	$post_key = $main->set("post_key", $xml_state, -1);
    }
/* END CLEAR FORM BUTTON  */
    	
/* TEXTAREA XML LOAD */
//if textarea load get the populated values only, keep values in state
//unset any empty strings
//will get row_type etc from general else above
elseif ($main->post('bb_button', $module) == 3)
    {	
    $row_type = (int)$xml_state->row_type;
    $row_join = (int)$xml_state->row_join;
    $post_key = (int)$xml_state->post_key;
    
    $layout = $main->pad("l", $row_type);
    $xml_column = $xml_columns->$layout;

    $str_textarea = $main->post('input_textarea',$module);
    $arr_textarea =  explode(PHP_EOL, $str_textarea);	
    //load textarea into xml, textarea and queue field mutually exclusive
    $i = 0;
    foreach($xml_column->children() as $child)
        {		
        $col = $child->getName();
		$value = trim($arr_textarea[$i]);
        if (!empty($value))
            {
            if (in_array($col,$arr_notes))
                {
				$str = $main->custom_trim_string($value,65536,false);
                }
            else
                {
				$str = $main->custom_trim_string($value,255);
                }
			$main->set($col, $xml_state, $str);
			$str = "";
            }		
        $i++;
        }
    }
/* END TEXTAREA XML LOAD */

/* SELECT COMBO CHANGE CLEAR*/
// basically reset form, combo change through javascript
elseif ($main->post('bb_button',$module) == 4)
    {      
 	//get row_type from combo box
	$xml_state = simplexml_load_string("<hold></hold>");
	$row_type = $main->process('row_type', $module, $xml_state, $default_row_type);
	$row_join = $main->set('row_join', $xml_state, -1);
	$post_key = $main->set('post_key', $xml_state, -1);
	
	if ($row_type > 0)
        {
        $layout = $main->pad("l", $row_type);
        $xml_column = $xml_columns->$layout;
		}    
    }
/* END SELECT COMBO CHANGE CLEAR*/

/* GET VALUES FROM STATE */
else
    {
	//default deal with $row_type = 0 later
    $row_type = $main->state('row_type', $xml_state, $default_row_type);
    $row_join = $main->state('row_join', $xml_state, -1);
    $post_key = $main->state('post_key', $xml_state, -1);
    
    if ($row_type > 0)
        {
        $layout = "l" . str_pad($row_type,2,"0",STR_PAD_LEFT);
        $xml_column = $xml_columns->$layout;
        }
    }
/* END GET VALUES FROM STATE */

/* END LARGE MUTUALLY EXLUSIVE IF-ELSEIF */

/* QUEUE XML LOAD */
//from queue straight into input page
//only set when input page is called from queue page
//post from queue page if posted from queue form
//will get row_type etc from general else above
if ($main->post('bb_button','bb_queue') == 2)
    {
    $var_subject = $main->post('subject','bb_queue');
    if (substr($var_subject,0,12) == "Record Add: " && preg_match("/^[A-Z][-][A-Z]\d+/", substr($var_subject,12)))
        {
        $xml_state = simplexml_load_string("<hold></hold>");
        
        $row_type = ord(substr($var_subject,12,1)) - 64;
        $row_join = ord(substr($var_subject,14,1)) - 64;
        $post_key = (int)substr($var_subject,15);
        
        $layout = $main->pad("l",$row_type);
		$xml_layout = $xml_layouts->$layout;
        $xml_column = $xml_columns->$layout;		
        
		if (!empty($xml_column) && ($xml_layout['parent'] == $row_join))
			{
			foreach($xml_column->children() as $child)
				{		
				$col = $child->getName();            
				if ($main->check($col,'bb_queue'))
					{
					if ($main->full($col,'bb_queue'))
						{
						unset($xml_state->$col);
						//html specials chars added in javascript
						if (in_array($col,$arr_notes))
							{
							$main->set($col, $xml_state, $main->custom_trim_string($main->post($col,'bb_queue'),65536, false));
							}
						else
							{
							$main->set($col, $xml_state, $main->custom_trim_string($main->post($col,'bb_queue'),255));
							}
						}			
					}
				}
			}
		else
			{
			$row_type = 0;
			foreach ($xml_layouts->children() as $child)
				 {
				 if ((int)$child['parent'] == 0)
					 {
					 $i = $main->rpad($child->getName());
					 $row_type = $i;
					 break;
					 }
				 }
 			$layout = $main->pad("l",$row_type);
			$xml_column = $xml_columns->$layout;

			$post_key = -1;
			$row_join = -1;
			}
		$main->set("row_type", $xml_state, $row_type);
		$main->set("row_join", $xml_state, $row_join);
		$main->set("post_key", $xml_state, $post_key);
        }
    elseif (substr($var_subject,0,13) == "Record Edit: " && preg_match("/^[A-Z]\d+/", substr($var_subject,13)))
        {
		$xml_state = simplexml_load_string("<hold></hold>");
		
        $row_type = ord(substr($var_subject,13,1)) - 64;
        $post_key = (int)substr($var_subject,14);
        $row_join = $row_type;
        
        $layout = $main->pad("l", $row_type);
        $xml_column = $xml_columns->$layout;
        
        $query = "SELECT * FROM data_table " .
             "WHERE row_type = " . $row_type . " AND id = " . $post_key . ";";
        $result = $main->query($con, $query);
		$numrows = pg_num_rows($result);
		
		if (!empty($xml_column) && ($numrows > 0))
			{
			$row = pg_fetch_array($result);
			foreach($xml_column->children() as $child)
				{				
				$col = $child->getName();
				//get data from bb_queue
				if ($main->check($col,'bb_queue'))
					{
					if ($main->full($col,'bb_queue'))
						{
						 if (in_array($col,$arr_notes))
							{    
							$row[$col] =  $row[$col] . " " . $main->post($col,'bb_queue');
							}
						else
							{
							$row[$col] = $main->post($col,'bb_queue');    
							}
						}
					}
				//update xml
				if (in_array($col,$arr_notes))
					{
					$main->set($col, $xml_state, $main->custom_trim_string($row[$col], 65536, false));
				    }
				else
					{
					$main->set($col, $xml_state, $main->custom_trim_string($row[$col], 255));	
					}
				}
			}
		else
			{
			$row_type = 0;
			foreach ($xml_layouts->children() as $child)
				 {
				 if ((int)$child['parent'] == 0)
					 {
					 $i = (int)substr($child->getName(),1);
					 $row_type = $i;
					 break;
					 }
				 }
			$layout = $main->pad("l", $row_type);
			$xml_column = $xml_columns->$layout;

			$post_key = -1;
			$row_join = -1;
			}
		$main->set("row_type", $xml_state, $row_type);
		$main->set("row_join", $xml_state, $row_join);
		$main->set("post_key", $xml_state, $post_key);
        }
	elseif (substr($var_subject,0,12) == "Record New: " && preg_match("/^[A-Z]$/", substr($var_subject,12)))
		{
		$xml_state = simplexml_load_string("<hold></hold>");
		
        $row_type = ord(substr($var_subject,12,1)) - 64;
        $post_key = -1;
        $row_join = -1;
        
        $layout = "l" . str_pad($row_type,2,"0",STR_PAD_LEFT);
        $xml_column = $xml_columns->$layout;
		$child = $xml_layouts->$layout;
        
		if (!empty($xml_column) && empty($child['parent']))
			{        
			foreach($xml_column->children() as $child)
				{				
				$col = $child->getName();
				//get data from bb_queue
				if (in_array($col,$arr_notes))
					{
					$main->set($col, $xml_state, $main->custom_trim_string($main->post($col,'bb_queue'),65536, false));
				    }
				else
					{
					$main->set($col, $xml_state, $main->custom_trim_string($main->post($col,'bb_queue'),255));
					}
				}
			}
		else
			{
			$row_type = 0;
			foreach ($xml_layouts->children() as $child)
				 {
				 if ((int)$child['parent'] == 0)
					 {
					 $i = (int)substr($child->getName(),1);
					 $row_type = $i;
					 break;
					 }
				 }
			$layout = $main->pad("l", $row_type);
			$xml_column = $xml_columns->$layout;

			$post_key = -1;
			$row_join = -1;
			}
		$main->set("row_type", $xml_state, $row_type);
		$main->set("row_join", $xml_state, $row_join);
		$main->set("post_key", $xml_state, $post_key);
		}
    else
        {
        $layout = $main->pad("l", $row_type);
        $xml_column = $xml_columns->$layout;
       
        foreach($xml_column->children() as $child)
            {		
            $col = $child->getName();			
            if ($main->check($col,'bb_queue'))
                {
                if ($main->full($col,'bb_queue'))
                    {
                    //html specials chars added in javascript
                    if (in_array($col,$arr_notes))
                        {
                        $temp_note = $main->custom_trim_string((string)$xml_state->$col . " " . $main->post($col,'bb_queue'),65536, false);
                        $main->set($col, $xml_state, $temp_note);
                        }
                    else
                        {
                        $main->set($col, $xml_state, $main->custom_trim_string($main->post($col,'bb_queue'),255));
                        }
                    }			
                }
            }
        }        
    } // if queue set
/* END QUEUE XML LOAD */

//echo "P" . $post_key . "R" . $row_type . "J" . $row_join  . "<br>";
?>
