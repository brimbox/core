<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
 * Copyright (C) Brimbox LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License Version 3 (GNU GPL v3)
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
if (!function_exists('bb_join_table_row_input')):

    function bb_join_table_row_input(&$arr_state) {
        // session or globals
        global $con, $main, $submit, $username;

        $row_type_1 = (int)$arr_state['row_type_1'];
        $post_key_1 = (int)$arr_state['post_key_1'];
        $row_type_2 = (int)$arr_state['row_type_2'];
        $post_key_2 = (int)$arr_state['post_key_2'];

        $arr_messages = array();

        //check if join relationship matches row_types
        $joinable = $main->joins($con, $row_type_1, $row_type_2);

        //no join relatioship
        if (!$joinable) {
            array_push($arr_messages, __t("Error: Unable to join, layouts and join relationships do not match.", $module));
        }

        //valid integer join and row_types
        if (($row_type_1 <= 0) || ($row_type_2 <= 0)) {
            array_push($arr_messages, __t("Error: Invalid layout or join type supplied, layouts and join values must be positive integers.", $module));
        }

        //valid positve post_keys
        if (($post_key_1 <= 0) || ($post_key_2 <= 0)) {
            array_push($arr_messages, __t("Error: Invalid layout or join type supplied, layouts and join values must be positive integers.", $module));
        }

        if (!$main->has_error_messages($arr_messages)) {
            //lower valued row_type in join1 to keep them straight
            //join row_type come in any order regarding row-type_1 and row_type_2
            if ($row_type_1 > $row_type_2) {
                $join1 = $post_key_2;
                $join2 = $post_key_1;
            }
            else {
                $join1 = $post_key_1;
                $join2 = $post_key_2;
            }

            $query = "INSERT INTO join_table (join1, join2) SELECT " . $join1 . ", " . $join2 . " " . "WHERE EXISTS (SELECT 1 FROM data_table WHERE row_type = " . $row_type_1 . " AND id = " . $post_key_1 . ") " . "AND EXISTS (SELECT 1 FROM data_table WHERE row_type = " . $row_type_1 . " AND id = " . $post_key_1 . ") " . "AND NOT EXISTS (SELECT 1 FROM join_table WHERE join1 = " . $join1 . " AND join2 = " . $join2 . ")";

            //echo "<p>" . $query . "</p>";
            $result = $main->query($con, $query);

            //join occured
            if (pg_affected_rows($result) == 1) {
                unset($arr_state['row_type_1']);
                unset($arr_state['post_key_1']);
                unset($arr_state['row_type_2']);
                unset($arr_state['post_key_2']);

                array_push($arr_messages, __t("Records succesfully joined.", $module));

            }
            else {
                //no join
                $query = "SELECT 1 FROM join_table WHERE join1 = " . $join1 . " AND join2 = " . $join2;
                $result = $main->query($con, $query);
                if (pg_affected_rows($result) == 1) {
                    array_push($arr_messages, __t("Error: Records have already been joined. Join already exists.", $module));
                }
                else {
                    array_push($arr_messages, __t("Error: Unable to join. Underlying data change possible.", $module));
                }
            }
        }
        $main->set('arr_messages', $arr_state, $arr_messages);
    }

endif;
?>