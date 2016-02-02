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
/* NO HTML OUTPUT */

# HOT TAB SWITCH PRESERVE STATE #
//array that updates state when tabs are switched without postback
function bb_index_hot_state($con, $main, $interface, &$array_hot_state)
    {    
    //Hot State for Deta  ils 
    if ($main->check("link_values", "bb_details"))
        {
        $array_hot_state['5_bb_brimbox']['bb_details'] = $array_hot_state['4_bb_brimbox']['bb_details'] = $array_hot_state['3_bb_brimbox']['bb_details'] = array("link_values");
        }
    
    //Hot State for Search    
    if ($main->hot("bb_search"))
        {
        $array_hot_state['bb_search'][5] = $array_hot_state['bb_search'][4] = $array_hot_state['bb_search'][3] = array("search","row_type");    
        }
        
    // Hot state for Import  
    if ($main->hot("bb_input"))
        {
        $arr = array(); //work array
        $POST = $main->retrieve($con);
        $row_type = $main->post('row_type', 'bb_input' ,0);
        if ($main->check("security", "bb_input")) array_push($arr, "security");
        if ($main->check("archive", "bb_input")) array_push($arr, "archive");
        $arr_columns = $main->columns($con, $row_type);
        //removes a warning we there are no columns in a layout
        if (isset($arr_columns))
            {
            foreach ($arr_columns as $key => $value)
                {
                array_push($arr, $main->pad("c", $key));
                }
            }
        $array_hot_state['bb_input'][5] = $array['bb_input'][4] = $array['bb_input'][3] = $arr;
        //unset any variable used in global, unset $POST for organization 
        }
        
    // Hot state for Create Lists 
    if ($main->hot("bb_create_lists"))
        {
        $arr = array("row_type_1","new_value_1","new_description_1",
                     "row_type_2","list_number_2","list_output_2","update_list_2","update_description_2",
                     "row_type_3","list_number_3","confirm_remove_3","archive_value_3"); //work array
        $array_hot_state['bb_create_lists'][5] = $array['bb_create_lists'][4] = $arr;
        //unset any variable used in global, unset $POST for organization
        }
    
    // Hot state for Dropdowns
    if ($main->hot("bb_dropdowns"))
        {
        $arr = array("row_type","col_type","multiselect","dropdown","all_values","empty_value"); //work array
        $array_hot_state['bb_dropdowns'][5] = $array['bb_dropdowns'][4] = $arr;
        //unset any variable used in global, unset $POST for organization
        }
        
    // Hot state for Manage Users
    if ($main->hot("bb_manage_users"))
        {
        $arr = array("action","id","usersort","filterrole","username_work","email_work","userroles_work","userrole_default","fname","minit","lname","notes");
        //work array
        $array_hot_state['bb_manage_users'][5] = $arr;
        //unset any variable used in global, unset $POST for organization
        }
    // Hot state for Layout Names  
    if ($main->hot("bb_layout_names"))
        {
        $number_layouts = $main->get_constant('BB_NUMBER_LAYOUTS', 12);
        $POST = $main->retrieve($con);
        $arr = array();
        for ($i = 1; $i<=$number_layouts; $i++)
            {        
            if ($main->full('singular_' . $i, 'bb_layout_names') || $main->full('plural_' . $i, 'bb_layout_names'))
                {
                array_push($arr, 'singular_' . $i, 'plural_' . $i, 'parent_' . $i, 'order_' . $i, 'secure_' . $i, 'autoload_' . $i, 'relate_' . $i);   
                }
            }
        $array_hot_state['bb_layout_names'][5] = $arr;
        }
    }
?>