<?php if (!defined('BASE_CHECK')) exit(); ?>
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
?>
<?php
if (!function_exists('bb_input_redirect_postback')) :
    
    function bb_input_redirect_postback(&$arr_layouts, &$arr_columns, &$arr_dropdowns, &$arr_state)
        {
        //session or globals
        global $POST, $con, $main, $submit;
    
        //bring in from redirect
        //will alter arr_state, layouts, columns, dropdowns
            
        /* INITIALIZE */
        //$arr_layouts must have one layout set
        $arr_layouts = $main->layouts($con);
        $default_row_type = $main->get_default_layout($arr_layouts);
        
        //standard values
        $arr_relate = array(41,42,43,44,45,46);
        $arr_file = array(47);
        $arr_reserved = array(48);
        $arr_notes = array(49,50);
        $textarea_rows = 4; //minimum
        $delimiter = $main->get_constant('BB_MULTISELECT_DELIMITER', ",");
        
        /* Standard Buttons */
        //Button 1 - update or insert
        //Button 2 - reset form
        //Button 3 - combo change (through javascript)
        //Button 4 - autoload form, (not implemented)
        //Button 5 - textarea load
        //also default else
        
        /* STANDARD POSTBACK FROM SUBMIT BUTTON */
        if ($main->button(1))
            {
            //this bring the form into arr_state
            $row_type = $main->process('row_type', $submit, $arr_state, $default_row_type);
            $row_join = $main->process('row_join', $submit, $arr_state, 0);
            $post_key = $main->process('post_key', $submit, $arr_state, 0); 
            
            $arr_columns = $main->columns($con, $row_type);
            $arr_dropdowns = $main->dropdowns($con, $row_type);
            
            //loop through columns
            foreach($arr_columns as $key => $value)
                {
                $col = $main->pad("c", $key);
                if (in_array($key, $arr_notes))
                    {
                    $str = $main->purge_chars($main->post($col, $submit), false);
                    }
                elseif (in_array($key, $arr_file))
                    {
                    //deal with remove and lo, present when doing files
                    $remove = $main->post('remove', $submit, 0);
                    $main->set("remove", $arr_state, $remove);
                    if (is_uploaded_file($_FILES[$main->name('c47', $submit)]["tmp_name"]))
                        {
                        $str1 = $_FILES[$main->name($col, $submit)]["name"];
                        }
                    $str2 = $main->post("lo", $submit);
                    $str = $main->blank($str1) ? $str2 : $str1;
                    $str = $remove ? "" : $str;
                    $main->set("lo", $arr_state, $str);
                    $main->set($col, $arr_state, $str);
                    }
                else
                    {
                    if (isset($arr_dropdowns[$key]['multiselect']))
                        {
                        //will be an array
                        $str = $main->post($col, $submit, array());
                        $str = array_map(array($main, "purge_chars"), $str);   
                        }
                    else
                        {
                        $str = $main->purge_chars($main->post($col,$submit));
                        }  
                    }
                $main->set($col, $arr_state, $str);
                }			
            $archive = $main->process('archive', $submit, $arr_state, 0); 
            $secure = $main->process('secure', $submit, $arr_state, 0);
            }
       }
        
endif; //pluggable
?>