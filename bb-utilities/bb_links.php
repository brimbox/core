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

/* NO HTML OUTPUT */

/* PHP AND JAVASCRIPT FUNCTIONS */

/* PHP */
/* class bb_links() */
// standard
// edit
// relate
// children
// drill
// related PHP class
class bb_links extends bb_work {

    function get_standard($row, $arr_layouts, $target, $text, $params = array()) {

        global $con;

        // standard row_type and post_key for a target
        // commonly used in linking things
        $button = isset($params['button']) ? $params['button'] : 0;
        $filter = isset($params['layouts']) ? $params['layouts'] : array();
        $class = isset($params['class']) ? $params['class'] : "link rightmargin";
        $id = isset($params['id']) ? "id=\"" . $params['id'] . "\"" : "";

        //setup filter for join links
        //probably should moive to a hook
        if ($target == 'bb_join') {
            $arr_joins = $this->joins($con);
            //give array a value
            $filter = array(0);
            foreach ($arr_joins as $value) {
                if ($value['join1'] == $row['row_type']) array_push($filter, $value['join1']);
                if ($value['join2'] == $row['row_type']) array_push($filter, $value['join2']);
            }
        }
        //place hook here
        if (in_array($row['row_type'], $filter) || empty($filter)) {
            $str = "<button " . $id . " class = \"" . $class . "\" onclick=\"bb_links.standard(" . $button . ", " . $row['id'] . "," . $row['row_type'] . ",'" . $target . "'); return false;\">";
            $str.= $text . "</button>";
        }

        return $str;
    }

    function standard($row, $arr_layouts, $target, $text, $params = array()) {

        echo $this->get_standard($row, $arr_layouts, $target, $text, $params);
    }

    function get_edit($row, $arr_layouts, $target, $text, $params = array()) {
        // edit row, row_type and row_join are the same and from row
        // target is input and text is editable, uses js input function
        $button = isset($params['button']) ? $params['button'] : 0;
        $filter = isset($params['layouts']) ? $params['layouts'] : array();
        $class = isset($params['class']) ? $params['class'] : "link rightmargin";
        if (!$row['archive'] && (in_array($row['row_type'], $filter) || empty($filter))) {
            $str = "<button class = \"" . $class . "\" onclick=\"bb_links.join(" . $button . ", " . $row['id'] . "," . $row['row_type'] . "," . $row['row_type'] . ",'" . $target . "'); return false;\">";
            $str.= $text . "</button>";
        }

        return $str;
    }

    function edit($row, $arr_layouts, $target, $text, $params = array()) {

        echo $this->get_edit($row, $arr_layouts, $target, $text, $params);
    }

    function get_relate($row, $arr_layouts, $target, $text, $params = array()) {
        // edit row, row_type and row_join are the same and from row
        // target is input and text is editable, uses js input function
        $button = isset($params['button']) ? $params['button'] : 0;
        $filter = isset($params['layouts']) ? $params['layouts'] : array();
        $class = isset($params['class']) ? $params['class'] : "link rightmargin";

        if (!$row['archive'] && $arr_layouts[$row['row_type']]['relate'] && (in_array($row['row_type'], $filter) || empty($filter))) {
            $str = "<button class = \"" . $class . "\" onclick=\"bb_links.relate(" . $button . ", " . $row['id'] . ",'" . $target . "'); return false;\">";
            $str.= $text . "</button>";
        }

        return $str;
    }

    function relate($row, $arr_layouts, $target, $text, $params = array()) {
        echo $this->get_relate($row, $arr_layouts, $target, $text, $params);
    }

    function get_children($row, $arr_layouts, $target_add, $text_add, $target_view, $text_view, $params = array()) {
        // view children and add child links, outputted at once
        // row_join is row_type of current row
        // row_type is form the child array
        // post_key is the parent
        $button = isset($params['button']) ? $params['button'] : 0;
        $check = isset($params['check']) ? $params['check'] : false;
        $class = isset($params['class']) ? $params['class'] : "link rightmargin";

        // find all the children
        $str = "";
        $arr_children = array();
        foreach ($arr_layouts as $key => $value) {
            $secure = ($check && ($value['secure'] > 0)) ? 1 : 0;
            if (($row['row_type'] == $value['parent']) && !$secure) {
                $i = $key;
                $plural = $value['plural'];
                $singular = $value['singular'];
                array_push($arr_children, array("row_type" => $i, "singular" => $singular, "plural" => $plural));
            }
        }
        // only if there are children
        if (!empty($arr_children)) {
            foreach ($arr_children as $arr_child) {
                // view link, sues standard js function
                $str.= "<button class = \"" . $class . "\" onclick=\"bb_links.join(" . $button . ", " . $row['id'] . "," . $row['row_type'] . "," . $arr_child['row_type'] . ",'" . $target_view . "'); return false;\">";
                $str.= $text_view . " " . $arr_child['plural'] . "</button>";
                // add link, not available when archived
                if (!$row['archive']) {
                    $str.= "<button class = \"" . $class . "\" onclick=\"bb_links.join(" . $button . ", " . $row['id'] . "," . $row['row_type'] . "," . $arr_child['row_type'] . ",'" . $target_add . "'); return false;\">";
                    $str.= $text_add . " " . $arr_child['singular'] . "</button>";
                }
            }
        }

        return $str;
    }

    function children($row, $arr_layouts, $target_add, $text_add, $target_view, $text_view, $params = array()) {

        echo $this->get_children($row, $arr_layouts, $target_add, $text_add, $target_view, $text_view, $params);
    }

    function get_joinlinks($row, $arr_layouts, $target, $text, $params = array()) {

        global $con;

        // post_key is the parent
        $button = isset($params['button']) ? $params['button'] : 0;
        $check = isset($params['check']) ? $params['check'] : false;
        $class = isset($params['class']) ? $params['class'] : "link rightmargin";

        //get joins with meta function
        $arr_joins = $this->joins($con, $row['row_type']);

        // only if there are children
        foreach ($arr_joins as $join) {
            $secure = ($check && ($arr_layouts[$join]['secure'] > 0)) ? 1 : 0;
            $plural = $arr_layouts[$join]['plural'];
            if (!$secure) {
                // view link, sues standard js function
                $str.= "<button class = \"" . $class . "\" onclick=\"bb_links.join(" . $button . ", " . $row['id'] . "," . $row['row_type'] . "," . $join . ",'" . $target . "'); return false;\">";
                $str.= $text . " " . $plural . "</button>";
                // add link, not available when archived
                
            }
        }

        return $str;
    }

    function joinlinks($row, $arr_layouts, $target, $text, $params = array()) {

        echo $this->get_joinlinks($row, $arr_layouts, $target, $text, $params);
    }

    function get_drill($post_key, $row_type, $arr_layouts, $target_add, $text_add, $params = array()) {
        // used for adding drill links to the standard input form
        // row_join is row_type of parent or inserted row
        // row_type is from the child array
        // post_key is the parent or inserted id
        $button = isset($params['button']) ? $params['button'] : 0;
        $arr_children = array();
        $str = "";
        foreach ($arr_layouts as $key => $value) {
            if ($row_type == $value['parent']) {
                array_push($arr_children, array("row_type" => $key, "singular" => $value['singular'], "plural" => $value['plural']));
            }
        }
        if (!empty($arr_children)) {
            foreach ($arr_children as $arr_child) {
                // add link, not available when archived
                $str.= "<button class = \"link rightmargin\" onclick=\"bb_links.join(" . $button . ", " . $post_key . "," . $row_type . "," . $arr_child['row_type'] . ",'" . $target_add . "'); return false;\">";
                $str.= $text_add . " " . $arr_child['singular'] . "</button>";
            }
        }

        return $str;
    }

    function drill($post_key, $row_type, $arr_layouts, $target_add, $text_add, $params = array()) {
        echo $this->get_drill($post_key, $row_type, $arr_layouts, $target_add, $text_add, $params);
    }
} // end class

?>