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
<style>
/* Query Alias */
.sub_textarea {
	width: 400px;
	overflow-y: scroll;
}

.full_textarea {
	width: 700px;
	overflow-y: scroll;
}

.return_textarea {
	width: 700px;
	background-color: #F0F0F0;
	overflow-y: scroll;
}
</style>
<?php
$arr_limit = array("50" => "50", "100" => "100", "500" => "500", "1000" => "1000", "0" => "All");

$row_limit = key($arr_limit);

/* PRESERVE STATE */
$arr_messages = array();

// $POST brought in from controller


// get state from db
$arr_state = $main->load($con, $module);

// check that there are enough form areas for sub_queries
$number_sub_queries = $main->process("number_sub_queries", $module, $arr_state, 1);
$row_limit = $main->process("row_limit", $module, $arr_state, 50);

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

$current['report_type'] = 2;

// process the form
$substituter = $main->process("substituter", $module, $arr_state, "");

// update state, back to db
$main->update($con, $module, $arr_state);

// build main query
$tokenized = preg_split("/( |\(|\))/", $substituter, NULL, PREG_SPLIT_DELIM_CAPTURE);

// build full query
if ($main->button(-1)) {
    for ($i = 1;$i <= $number_sub_queries;$i++) {
        foreach ($tokenized as $key => & $value) {
            if (!strcasecmp($value, $arr_sub_queries[$i]['name'])) {
                $value = "(" . $arr_sub_queries[$i]['subquery'] . ")";
            }
        }
    }
    $fullquery = implode($tokenized);
} // or run subquery
else {
    if ($button > 0) {
        $fullquery = $arr_sub_queries[$button]['subquery'];
    }
}

// display query
if (isset($fullquery)) {
    if (substr(strtoupper(trim($fullquery)), 0, 6) == "SELECT") {
        $limit_string = ($row_limit > 0) ? " LIMIT " . $row_limit : "";
        @$result = pg_query($con, $fullquery . $limit_string);
        $settings[2][0] = array('start_column' => 0, 'ignore' => true, 'shade_rows' => true, 'title' => 'Query Results');
        if ($result === false) {
            array_push($arr_messages, pg_last_error($con));
        }
    }
    else {
        array_push($arr_messages, "Error: Only SELECT queries are allowed to execute");
    }
}

// title
echo "<p class=\"spaced bold larger\">Query Alias</p>";

echo "<div class=\"spaced\">";
$main->echo_messages($arr_messages);
echo "</div>";

/* BEGIN REQUIRED FORM */
$main->echo_form_begin();
$main->echo_module_vars();

$params = array("class" => "spaced", "number" => - 1, "target" => $module, "passthis" => true, "label" => "Submit Full Query");
$main->echo_button("submit_query", $params);

$main->echo_clear();

for ($i = 0;$i <= 10;$i++) {
    $arr_number[] = $i;
}
$params = array("select_class" => "spaced", "onchange" => "bb_reload()");
echo "<div class=\"spaced floatleft\"><span>Number of Subqueries: </span>";
$main->array_to_select($arr_number, "number_sub_queries", $number_sub_queries, array(), $params);
echo "</div>";

$params = array("select_class" => "spaced", "usekey" => true, "onchange" => "bb_reload()");

echo "<div class=\"spaced floatleft\"><span>Limit Return Rows: </span>";
$main->array_to_select($arr_limit, "row_limit", $row_limit, array(), $params);
echo "</div>";

$main->echo_clear();

// loop through subqueries
if ($number_sub_queries > 0) {
    echo "<div class=\"table spaced\" >";
    echo "<div class=\"row\"><div class=\"padded border cell\"></div><div class=\"padded border cell\">SubQuery Alias</div><div class=\"padded border cell\">SubQuery Value</div></div>";
    for ($i = 1;$i <= $number_sub_queries;$i++) {
        echo "<div class=\"row\"><div class=\"padded border top cell\">";
        $params = array("class" => "spaced", "number" => $i, "target" => $module, "passthis" => true, "label" => "Submit SubQuery");
        $main->echo_button("submit_subquery_" . $i, $params);
        echo "</div><div class=\"padded border top cell\">";
        $name = $main->pad("s", $i);
        $main->echo_input($name, $arr_sub_queries[$i]['name'], array('type' => "text", 'class' => "spaced"));
        echo "</div><div class=\"padded border top cell\">";
        $subquery = $main->pad("q", $i);
        $main->echo_textarea($subquery, $arr_sub_queries[$i]['subquery'], array('class' => "spaced sub_textarea"));
        echo "</div>";
        echo "</div>";
    }
    echo "</div>";
    echo "<div class=\"top\">";
    $main->echo_textarea("substituter", $substituter, array('class' => "spaced full_textarea", 'rows' => 5));
    echo "</div>";

    echo "<div class=\"top\">";
    if (isset($fullquery)) {
        $main->echo_textarea("fullquery", $fullquery, array('class' => "spaced return_textarea", 'rows' => 5, 'readonly' => true));
        echo "</div>";
    }
}

// echo report
if (isset($result)) {
    $main->echo_report_vars();
    // output report
    if ($result) {
        $main->output_report($result, $current, $settings);
    }
}

$main->echo_form_end();
/* END FORM */
?>

