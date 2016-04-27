<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php


/*
 * Copyright (C) Brimbox LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License Version 3 (“GNU GPL v3”)
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU GPL v3 for more details.
 *
 * You should have received a copy of the GNU GPL v3 along with this program.
 * If not, see http://www.gnu.org/licenses/
 */
/* NO HTML OUTPUT */

if (! function_exists ( 'bb_index_hot_state' )) :

    // HOT TAB SWITCH PRESERVE STATE #
    // array that updates state when tabs are switched without postback
    function bb_index_hot_state($con, $main, $interface, &$array_hot_state) {
        
        // Hot State for Details
        if ($main->check ( "link_values", "bb_details" )) {
            $array_hot_state ['5_bb_brimbox'] ['bb_details'] = $array_hot_state ['4_bb_brimbox'] ['bb_details'] = $array_hot_state ['3_bb_brimbox'] ['bb_details'] = array (
                    "link_values" 
            );
        }
        
        // Hot State for Search
        if ($main->hot ( "bb_search" )) {
            $array_hot_state ['bb_search'] ['5_bb_brimbox'] = $array_hot_state ['bb_search'] ['4_bb_brimbox'] = $array_hot_state ['bb_search'] ['3_bb_brimbox'] = array (
                    "search",
                    "row_type" 
            );
        }
        
        // Hot state for Import
        if ($main->hot ( "bb_input" )) {
            $arr = array (); // work array
            $POST = $main->retrieve ( $con );
            $row_type = $main->post ( 'row_type', 'bb_input', 0 );
            if ($main->check ( "security", "bb_input" ))
                array_push ( $arr, "security" );
            if ($main->check ( "archive", "bb_input" ))
                array_push ( $arr, "archive" );
            $arr_columns = $main->columns ( $con, $row_type );
            // removes a warning we there are no columns in a layout
            if (isset ( $arr_columns )) {
                foreach ( $arr_columns as $key => $value ) {
                    array_push ( $arr, $main->pad ( "c", $key ) );
                }
            }
            $array_hot_state ['bb_input'] ['5_bb_brimbox'] = $array ['bb_input'] ['4_bb_brimbox'] = $array ['bb_input'] ['3_bb_brimbox'] = $arr;
            // unset any variable used in global, unset $POST for organization
        }
        
        // Hot state for Manage Users
        if ($main->hot ( "bb_manage_users" )) {
            $arr = array (
                    "action",
                    "id",
                    "usersort",
                    "filterrole",
                    "username_work",
                    "email_work",
                    "userroles_work",
                    "userrole_default",
                    "fname",
                    "minit",
                    "lname",
                    "notes" 
            );
            // work array
            $array_hot_state ['bb_manage_users'] ['5_bb_brimbox'] = $arr;
            // unset any variable used in global, unset $POST for organization
        }
        
        if ($main->hot ( "bb_dropdowns" )) {
            $arr = array (
                    "row_type",
                    "col_type",
                    "dropdown",
                    "multiselect",
                    "empty_value"
            );
            // work array
            $array_hot_state ['bb_dropdowns'] ['5_bb_brimbox'] =  $array_hot_state ['bb_dropdowns'] ['4_bb_brimbox'] = $arr;
            // unset any variable used in global, unset $POST for organization
        }
 
        
}

endif;
?>