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
?>
<?php
$main->check_permission(array("4_bb_brimbox", "5_bb_brimbox"));

?>
<script type="text/javascript">     
function bb_reload()
    {
    //set var form links and the add_new_user button on initial page
    var frmobj = document.forms["bb_form"];
    bb_submit_form();
    return false;
    }
</script>
<?php
$arr_pagination = array(10, 25, 50, 100, 500, 1000);

/* PRESERVE STATE */
$arr_messages = array();

$arr_layouts = $main->layouts($con);

// get state from db
$arr_state = $main->load($con, $module);

// check that there are enough form areas for sub_queries
$number_sub_queries = $main->process("number_sub_queries", $module, $arr_state, 1);
$pagination = $main->process("pagination", $module, $arr_state, 50);
$row_type = $main->process("row_type", $module, $arr_state, 0);

$arr_columns = $main->columns($con, $row_type);

// process the form
$arr_sub_queries = array();
for ($i = 1;$i <= 10;$i++) {
    $name = $main->pad("s", $i);
    $subquery = $main->pad("q", $i);
    if ($i <= $number_sub_queries) {
        $arr_sub_queries[$i]['name'] = $main->process($name, $module, $arr_state, "");
        $arr_sub_queries[$i]['subquery'] = $main->process($subquery, $module, $arr_state, "");
    }
    else {
        unset($arr_state[$name]);
        unset($arr_state[$subquery]);
    }
}

$current = $main->report_post($arr_state, $module, $module);
// process the form
$substituter = $main->process("substituter", $module, $arr_state, "");

// update state, back to db
$main->update($con, $module, $arr_state);;

// build full query
if ($main->button(-1)) {
    $fullquery = $substituter;
    for ($i = 1;$i <= $number_sub_queries;$i++) {
        $value = "(" . $arr_sub_queries[$i]['subquery'] . ")";
        $fullquery = str_ireplace($arr_sub_queries[$i]['name'], $value, $fullquery);
    }
}
// or run subquery
else {
    if ($button > 0) {
        $fullquery = $arr_sub_queries[$button]['subquery'];
    }
}

// display query
if (isset($fullquery)) {
    if (substr(strtoupper(trim($fullquery)), 0, 6) == "SELECT") {
        @$result = pg_query($con, $fullquery);
        $settings[1][0] = array('ignore' => true, 'limit' => $pagination, 'shade_rows' => true, 'title' => __t('Query Results', $module));
        $settings[2][0] = array('ignore' => true, 'shade_rows' => true, 'title' => __t('Query Results', $module));
        $settings[3][0] = array('rows' => 60, 'columns' => 80, 'title' => __t('Query Results', $module));
        if ($result === false) {
            array_push($arr_messages, pg_last_error($con));
        }
    }
    else {
        array_push($arr_messages, __t("Error: Only SELECT queries are allowed to execute.", $module));
    }
}

// title
echo "<p class=\"spaced bold larger\">" . __t("Query Alias", $module) . "</p>";

echo "<div class=\"spaced\">";
$main->echo_messages($arr_messages);
echo "</div>";

/* BEGIN REQUIRED FORM */
$main->echo_form_begin();
$main->echo_module_vars();

// echo form report vars for report functions
$main->echo_report_vars();

echo "<div class=\"inlineblock spaced\"><span>" . __t("Show Columns:", $module) . "</span>";
$params = array("onchange" => "bb_reload()", "empty" => "true");
$main->layout_dropdown($arr_layouts, "row_type", $row_type, $params);
echo "</div>";

for ($i = 0;$i <= 10;$i++) {
    $arr_number[] = $i;
}
$params = array("select_class" => "spaced", "onchange" => "bb_reload()");
echo "<div class=\"spaced inlineblock\"><span>" . __t("Number of Subqueries:", $module) . " </span>";
$main->array_to_select($arr_number, "number_sub_queries", $number_sub_queries, array(), $params);
echo "</div>";

$params = array("class" => "spaced", "onchange" => "bb_reload()");
echo "<div class=\"inlineblock spaced\"><span>" . __("Pagination:", $module) . " </span>";
$main->array_to_select($arr_pagination, "pagination", $pagination, array(), $params);
echo "</div>";
$main->echo_tag("label", __t("Report Type:", $module) . " ", array('class' => "padded"));
$params = array("class" => "margin");
$main->report_type($current['report_type'], $params);

echo "<br>";

if ($row_type > 0) {
    echo "<div class=\"bb_query_alias_columns_wrapper inlineblock twice spaced border\">";
    ksort($arr_columns);
    foreach ($arr_columns as $key => $value) {
        $arr_display_columns[] = $main->pad("c", $key) . "=>" . $value['name'];
    }
    $str_display_columns = implode(" | ", $arr_display_columns);
    echo $str_display_columns;
    echo "</div>";
}

// loop through subqueries
echo "<div id=\"bb_query_alias_query_wrapper\">";

echo "<div class=\"divider spaced\"></div>";

if ($number_sub_queries > 0) {
    for ($i = 1;$i <= $number_sub_queries;$i++) {
        echo "<div class=\"inlineblock top\">";
        $params = array("class" => "spaced", "number" => $i, "target" => $module, "passthis" => true, "label" => __t("Submit SubQuery", $module));
        $main->echo_button("submit_subquery_" . $i, $params);
        $name = $main->pad("s", $i);
        $main->echo_input($name, $arr_sub_queries[$i]['name'], array('type' => "text", 'placeholder' => "SubQuery Alias", 'class' => "spaced medium"));
        echo "</div>";
        $subquery = $main->pad("q", $i);
        $main->echo_textarea($subquery, $arr_sub_queries[$i]['subquery'], array('placeholder' => "SubQuery Definition", 'class' => "bb_query_alias_subquery_textarea spaced top"));
    }

    $main->echo_textarea("substituter", $substituter, array('id' => "bb_query_alias_substituter_textarea", 'class' => "spaced", 'placeholder' => "Full Query Definition with Aliases"));

    $params = array("class" => "spaced", "number" => - 1, "target" => $module, "passthis" => true, "label" => __t("Submit Full Query", $module));
    $main->echo_button("submit_query", $params);
    /*
    $params = array("class" => "spaced", "onchange" => "bb_reload()");
    echo "<div class=\"inlineblock spaced\"><span>" . __("Pagination:", $module) ." </span>";
    $main->array_to_select($arr_pagination, "pagination", $pagination, array(), $params);
    echo "</div>";
    $main->echo_tag("label", __t("Report Type:", $module) . " ", array('class'=>"padded"));
    $params = array ("class" => "margin");
    $main->report_type ( $current ['report_type'], $params );
    */

    if (isset($fullquery)) {
        $main->echo_textarea("fullquery", $fullquery, array('id' => "bb_query_alias_fullquery_textarea", 'class' => "spaced", 'readonly' => true));
    }
}

// echo report
if (isset($result)) {
    // output report
    if ($result) {
        $main->output_report($result, $current, $settings);
    }
}
echo "</div>";

$main->echo_form_end();
/* END FORM */
?>

