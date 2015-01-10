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

class bb_input_extra {
    
/* LARGE MUTUALLY EXLUSIVE IF-ELSEIF */
//Load row_type from record links, populate state
//Load if layout dropdown changes, empty state
//Load row_type from state if coming from another tab

    /* BRING IN VARIABLES */
    function __construct($arr_columns, $arr_state, $main, $con, $module, $default_row_type)
        {
        $this->main = $main;
        $this->con = $con;
        $this->module = $module;
        $this->arr_columns = $arr_columns;
        $this->arr_state = $arr_state;
        $this->arr_notes = array(49,50);
        $this->default_row_type = $default_row_type;
        }
    
    /* MAIN FUNCTION FOR RECORD LINKS */    
    function linkspost()
        {        
        if (!empty($_POST['bb_row_type'])) //row_type set in global link, should be positive
            {
            return $this->global_row_type();   
            }
        elseif ($this->main->button(1)) //postback
            {
            return $this->input_postback(); 
            }
        elseif ($this->main->button(2)) //clear form
            {
            return $this->clear_form();
            }
        elseif ($this->main->button(3))
            {
            return $this->load_textarea();
            }
        elseif ($this->main->button(4))
            {
            return $this->combo_change();    
            }
        else
            {
            return $this->load_from_state();    
            }
        //list variables from return
        }
        
    /* EITHER ADD OR EDIT FROM RECORD LINK */    
    function global_row_type()
        {
        $arr_state = array();
	
        $row_type = $this->main->set('row_type', $arr_state, $_POST['bb_row_type']);
        $row_join = $this->main->set('row_join', $arr_state, $_POST['bb_row_join']);
        $post_key = $this->main->set('post_key', $arr_state, $_POST['bb_post_key']);
        
        $arr_column = $this->arr_columns[$row_type];
        
        //populate from database if edit
        if ($row_type == $row_join)
            {
            $query = "SELECT * FROM data_table " .
                     "WHERE id = " . $post_key . ";";
            $result = $this->main->query($this->con, $query);
            $row = pg_fetch_array($result);
            
            $arr_column_reduced = $this->main->filter_keys($arr_column);
            foreach($arr_column_reduced as $key => $value)
                {				
                $col = $this->main->pad("c", $key);
                if (in_array($key, $this->arr_notes))
                    {
                    $str = $this->main->custom_trim_string($row[$col], 65536, false);
                    }
                else
                    {
                    $str = $this->main->custom_trim_string($row[$col],255);
                    }
                $this->main->set($col, $arr_state, $str);
                }
            }
        else
            {
            $arr_column_reduced = $this->main->filter_keys($arr_column);    
            }
        return array($row_type, $row_join, $post_key, $arr_state);
        }

    /* STANDARD POSTBACK FROM SUBMIT BUTTON */
    function input_postback()
        {
        $row_type = $this->main->process('row_type', $this->module, $arr_state, $this->default_row_type);
        $row_join = $this->main->process('row_join', $this->module, $arr_state, -1);
        $post_key = $this->main->process('post_key', $this->module, $arr_state, -1);
    
        $arr_column = $this->arr_columns[$row_type];
        $arr_state = $this->arr_state;
        
        $arr_column_reduced = $this->main->filter_keys($arr_column);
        foreach($arr_column_reduced as $key => $value)
            {				
            $col = $this->main->pad("c", $key);
            if (in_array($key,$this->arr_notes))
                {
                $str = $this->main->custom_trim_string($this->main->post($col,$this->module),65536,false);
                }
            else
                {
                $str = $this->main->custom_trim_string($this->main->post($col,$this->module),255);
                }
            $this->main->set($col, $arr_state, $str);
            }
        return array($row_type, $row_join, $post_key, $arr_state);
        }


    /* CLEAR FORM BUTTON  */
    function clear_form()
        {
        //reset to default row_type
        $arr_column = array();
        if ($this->default_row_type > 0)
            {
            $arr_column = $this->arr_columns[$this->default_row_type];
            }
        $arr_column_reduced = $this->main->filter_keys($arr_column);
        //reset state
        $arr_state = array();
        $row_type = $this->main->set("row_type", $arr_state, $this->default_row_type);
        $row_join = $this->main->set("row_join", $arr_state, -1);
        $post_key = $this->main->set("post_key", $arr_state, -1);
        
        return array($row_type, $row_join, $post_key, $arr_state);
        }
    /* END CLEAR FORM BUTTON  */
    	
    /* TEXTAREA LOAD */
    //textarea load gets the populated values only, keep values in state
    function load_textarea()
        {
        $arr_state = $this->arr_state;
        $arr_columns = $this->arr_columns;
        
        $row_type = $arr_state['row_type'];
        $row_join = $arr_state['row_join'];
        $post_key = $arr_state['post_key'];
        
        $arr_column = $arr_columns[$row_type];
    
        $str_textarea = $this->main->post('input_textarea',$this->module);
        $arr_textarea =  explode(PHP_EOL, $str_textarea);	
        //load textarea into xml, textarea and queue field mutually exclusive
        $i = 0;
        
        $arr_column_reduced = $this->main->filter_keys($arr_column);
        foreach($arr_column_reduced as $key => $value)
            {				
            $col = $this->main->pad("c", $key);
            $textarea = isset($arr_textarea[$i]) ? trim($arr_textarea[$i]) : "";
            if ($textarea <> "")
                {
                if (in_array($key,$this->arr_notes))
                    {
                    $str = $this->main->custom_trim_string($textarea, 65536,false);
                    }
                else
                    {
                    $str = $this->main->custom_trim_string($textarea, 255);
                    }
                $this->main->set($col, $arr_state, $str);
                $str = "";
                }		
            $i++;
            }
        return array($row_type, $row_join, $post_key, $arr_state);
        }

    /* SELECT COMBO CHANGE CLEAR*/
    // basically reset form, combo change through javascript
    function combo_change()
        {      
        //get row_type from combo box
        $arr_state = array();
        $row_type = $this->main->process('row_type', $this->module, $arr_state, $this->default_row_type);
        $row_join = $this->main->set('row_join', $arr_state, -1);
        $post_key = $this->main->set('post_key', $arr_state, -1);
        
        if ($row_type > 0)
            {
            $arr_column = $arr_columns[$row_type];
            }
        $arr_column_reduced = $this->main->filter_keys($arr_column);
        return array($row_type, $row_join, $post_key, $arr_state);
        }
    /* END SELECT COMBO CHANGE CLEAR*/

    /* GET VALUES FROM STATE */
    function load_from_state()
        {
        //default deal with $row_type = 0 later
        $arr_state = $this->arr_state;
        
        $row_type = $this->main->state('row_type', $arr_state, $this->default_row_type);
        $row_join = $this->main->state('row_join', $arr_state, -1);
        $post_key = $this->main->state('post_key', $arr_state, -1);
        
        if ($row_type > 0)
            {
            $arr_column = $this->arr_columns[$row_type];
            }
        $arr_column_reduced = $this->main->filter_keys($arr_column);   
        return array($row_type, $row_join, $post_key, $arr_state);
        }
} //end class bb_input extra

class bb_input_queue {
    
/* QUEUE LOAD */
//from queue straight into input page
//only set when input page is called from queue page
//post from queue page if posted from queue form
//will get row_type etc from general else above

    /* BRING IN VARIABLES */
    function __construct($arr_layouts, $arr_columns, $arr_state, $main, $con, $module, $row_type, $row_join, $post_key, $var_subject)
        {
        $this->main = $main;
        $this->con = $con;
        $this->module = $module;
        $this->arr_layouts = $arr_layouts;
        $this->arr_columns = $arr_columns;
        $this->arr_state = $arr_state;
        $this->arr_notes = array(49,50);
        $this->row_type = $row_type;
        $this->row_join = $row_join;
        $this->post_key = $post_key;
        $this->var_subject = $var_subject;
        }
        
    /* MAIN FROM QUEUE TAB */
    function queuepost($var_subject)
        {
        if (substr($var_subject,0,12) == "Record Add: " && preg_match("/^[A-Z][-][A-Z]\d+/", substr($var_subject,12)))
            {
            return $this->queue_record_add();   
            }
        elseif (substr($var_subject,0,13) == "Record Edit: " && preg_match("/^[A-Z]\d+/", substr($var_subject,13)))
            {
            return $this->queue_record_edit();   
            }
        elseif (substr($var_subject,0,12) == "Record New: " && preg_match("/^[A-Z]$/", substr($var_subject,12)))    
            {
            return $this->queue_record_new();
            }
        else
            {
            return $this->queue_record_default();
            }
        }
    
    /* SUBJECT RECORD ADD */
    //child record only
    function queue_record_add()
        {
        $arr_state = array();
        $var_subject = $this->var_subject;
        $arr_layouts = $this->arr_layouts;
        $arr_columns = $this->arr_columns;
        
        $row_type = ord(substr($var_subject,12,1)) - 64;
        $row_join = ord(substr($var_subject,14,1)) - 64;
        $post_key = (int)substr($var_subject,15);
        
		$arr_layout = $arr_layouts[$row_type];
        $arr_column = $arr_columns[$row_type];
        
        $arr_column_reduced = $this->main->filter_keys($arr_column);
        
		if (!empty($arr_column) && ($arr_layout['parent'] == $row_join))
			{
			foreach($arr_column_reduced as $key => $value)
				{		
				$col = $this->main->pad("c", $key);            
				if ($this->main->check($col,'bb_queue'))
					{
					if ($this->main->full($col,'bb_queue'))
						{
						unset($arr_state[$col]);
						//html specials chars added in javascript
						if (in_array($col,$this->arr_notes))
							{
							$this->main->set($col, $arr_state, $this->main->custom_trim_string($this->main->post($col,'bb_queue'),65536, false));
							}
						else
							{
							$this->main->set($col, $arr_state, $this->main->custom_trim_string($this->main->post($col,'bb_queue'),255));
							}
						}			
					}
				}
			}
		else
			{
			$row_type = 0;
			foreach ($arr_layouts as $key => $value)
				 {
				 if ($value['parent'] == 0)
					 {
					 $row_type = $key;
					 break;
					 }
				 }
            
            $arr_column_reduced = $this->main->filter_keys($arr_column);   
            $arr_column = $arr_columns[$row_type];

			$post_key = -1;
			$row_join = -1;
			}
            
		$this->main->set("row_type", $arr_state, $row_type);
		$this->main->set("row_join", $arr_state, $row_join);
		$this->main->set("post_key", $arr_state, $post_key);
        
        return array($row_type, $row_join, $post_key, $arr_state);
        }
        
    /* SUBJECT RECORD EDIT */   
    function queue_record_edit()
        {
		$arr_state = array();
        $var_subject = $this->var_subject;
        $arr_layouts = $this->arr_layouts;
        $arr_columns = $this->arr_columns;
		
        $row_type = ord(substr($var_subject,13,1)) - 64;
        $post_key = (int)substr($var_subject,14);
        $row_join = $row_type;
        
        $arr_column = $arr_columns[$row_type];
        
        $query = "SELECT * FROM data_table " .
             "WHERE row_type = " . $row_type . " AND id = " . $post_key . ";";
        $result = $this->main->query($this->con, $query);
		$numrows = pg_num_rows($result);
		
        $arr_column_reduced = $this->main->filter_keys($arr_column);
		if (!empty($arr_column_reduced) && ($numrows == 1))
			{
			$row = pg_fetch_array($result);
			foreach($arr_column_reduced as $key => $value)
				{				
				$col = $this->main->pad("c", $key);
				//get data from bb_queue
				if ($this->main->check($col,'bb_queue'))
					{
					if ($this->main->full($col,'bb_queue'))
						{
						 if (in_array($key, $this->arr_notes))
							{    
							$row[$col] =  $row[$col] . " " . $this->main->post($col,'bb_queue');
							}
						else
							{
							$row[$col] = $this->main->post($col,'bb_queue');    
							}
						}
					}
				//update xml
				if (in_array($col,$this->arr_notes))
					{
					$this->main->set($col, $arr_state, $this->main->custom_trim_string($row[$col], 65536, false));
				    }
				else
					{
					$this->main->set($col, $arr_state, $this->main->custom_trim_string($row[$col], 255));	
					}
				}
			}
		else
			{
			$row_type = 0;
			foreach ($arr_layouts as $key => $value)
				 {
				 if ($value['parent'] == 0)
					 {
					 $row_type = $key;
					 break;
					 }
				 }
			$arr_column = $arr_columns[$row_type];
            $arr_column_reduced = $this->main->filter_keys($arr_column);

			$post_key = -1;
			$row_join = -1;
			}
		$this->main->set("row_type", $arr_state, $row_type);
		$this->main->set("row_join", $arr_state, $row_join);
		$this->main->set("post_key", $arr_state, $post_key);
        
        return array($row_type, $row_join, $post_key, $arr_state);
        }
        
    /* NEW RECORD */
    //parent only
	function queue_record_new()
		{
		$arr_state = array();
        $var_subject = $this->var_subject;
        $arr_layouts = $this->arr_layouts;
        $arr_columns = $this->arr_columns;
		
        $row_type = ord(substr($var_subject,12,1)) - 64;
        $post_key = -1;
        $row_join = -1;
    
        $arr_layout = $arr_layouts[$row_type];
        $arr_column = $arr_columns[$row_type];
        $arr_column_reduced = $this->main->filter_keys($arr_column);
		
        
		if (!empty($arr_column_reduced) && empty($arr_layout['parent']))
			{        
			foreach($arr_column_reduced as $key => $value)
				{				
				$col = $this->main->pad("c", $key);
				//get data from bb_queue
				if (in_array($key,$this->arr_notes))
					{
					$this->main->set($col, $arr_state, $this->main->custom_trim_string($this->main->post($col,'bb_queue'),65536, false));
				    }
				else
					{
					$this->main->set($col, $arr_state, $this->main->custom_trim_string($this->main->post($col,'bb_queue'),255));
					}
				}
			}
		else
			{
			$row_type = 0;
			foreach ($arr_layouts as $key => $value)
				 {
				 if ($value['parent'] == 0)
					 {
					 $row_type = $key;
					 break;
					 }
				 }
			$arr_column = $arr_columns[$row_type];

			$post_key = -1;
			$row_join = -1;
			}
		$this->main->set("row_type", $arr_state, $row_type);
		$this->main->set("row_join", $arr_state, $row_join);
		$this->main->set("post_key", $arr_state, $post_key);
        
        return array($row_type, $row_join, $post_key, $arr_state);
		}
        
    /* POPULATE CURRENT RECORD */
    function queue_record_default()
        {
        $row_type = $this->row_type;
        $row_join = $this->row_join;
        $post_key = $this->post_key;
        $arr_state = $this->arr_state;
        $arr_columns = $this->arr_columns;

        $arr_column = $arr_columns[$row_type];
        $arr_column_reduced = $this->main->filter_keys($arr_column);
       
        foreach($arr_column_reduced as $key => $value)
            {		
            $col = $this->main->pad("c", $key);			
            if ($this->main->check($col,'bb_queue'))
                {
                if ($this->main->full($col,'bb_queue'))
                    {
                    //html specials chars added in javascript
                    if (in_array($key,$this->arr_notes))
                        {
                        $temp_note = $this->main->custom_trim_string((string)$arr_state->$col . " " . $this->main->post($col,'bb_queue'),65536, false);
                        $this->main->set($col, $arr_state, $temp_note);
                        }
                    else
                        {
                        $this->main->set($col, $arr_state, $this->main->custom_trim_string($this->main->post($col,'bb_queue'),255));
                        }
                    }			
                }
            }
        return array($row_type, $row_join, $post_key, $arr_state);
        }
} // end class bb_input_queue
?>
