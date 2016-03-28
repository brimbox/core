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

$main->check_permission ( array (
		"4_bb_brimbox",
		"5_bb_brimbox" 
) );

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
.query_alias_query_table {
	width: 800px;
	border-collapse: collapse;
}

.query_alias_sub_query {
	width: 492px;
	border-collapse: collapse;
}

.query_alias_sub_textarea {
	width: 488px;
}

.query_alias_full_textarea {
	width: 792px;
}

.query_alias_return_query {
	width: 792px;
}
</style>
<?php

$arr_limit = array (
		"50" => "50",
		"100" => "100",
		"500" => "500",
		"1000" => "1000",
		"0" => "All" 
);

$row_limit = key ( $arr_limit );

/* PRESERVE STATE */
$arr_messages = array ();

// get $POST variable
$POST = $main->retrieve ( $con );

// get state from db
$arr_state = $main->load ( $con, $module );

// check that there are enough form areas for sub_queries
$number_sub_queries = $main->process ( "number_sub_queries", $module, $arr_state, 1 );
$row_limit = $main->process ( "row_limit", $module, $arr_state, 50 );

// process the form
$arr_sub_queries = array ();
for($i = 1; $i <= 10; $i ++) {
	$name = $main->pad ( "s", $i );
	$subquery = $main->pad ( "q", $i );
	if ($i <= $number_sub_queries) {
		$arr_sub_queries [$i] ['name'] = $main->process ( $name, $module, $arr_state, "" );
		$arr_sub_queries [$i] ['subquery'] = $main->process ( $subquery, $module, $arr_state, "" );
	} else {
		unset ( $arr_state [$name] );
		unset ( $arr_state [$subquery] );
	}
}

$current ['report_type'] = 2;

// process the form
$substituter = $main->process ( "substituter", $module, $arr_state, "" );

// update state, back to db
$main->update ( $con, $module, $arr_state );

// build main query

$tokenized = preg_split ( "/( |\(|\))/", $substituter, NULL, PREG_SPLIT_DELIM_CAPTURE );

// build full query
if ($main->button ( - 1 )) {
	for($i = 1; $i <= $number_sub_queries; $i ++) {
		foreach ( $tokenized as $key => &$value ) {
			if (! strcasecmp ( $value, $arr_sub_queries [$i] ['name'] )) {
				$value = "(" . $arr_sub_queries [$i] ['subquery'] . ")";
			}
		}
	}
	$fullquery = implode ( $tokenized );
}  // or run subquery
else {
	if ($button > 0) {
		$fullquery = $arr_sub_queries [$button] ['subquery'];
	}
}

// display query
if (isset ( $fullquery )) {
	if (substr ( strtoupper ( trim ( $fullquery ) ), 0, 6 ) == "SELECT") {
		@$result = pg_query ( $con, $fullquery . " LIMIT " . $row_limit );
		$settings [2] [0] = array (
				'start_column' => 0,
				'ignore' => true,
				'shade_rows' => true,
				'title' => 'Query Results' 
		);
		if ($result === false) {
			array_push ( $arr_messages, pg_last_error ( $con ) );
		}
	} else {
		array_push ( $arr_messages, "Error: Only SELECT queries are allowed to execute" );
	}
}

// title
echo "<p class=\"spaced bold larger\">Query Alias</p>";

echo "<div class=\"spaced\">";
$main->echo_messages ( $arr_messages );
echo "</div>";

/* BEGIN REQUIRED FORM */
$main->echo_form_begin ();
$main->echo_module_vars ();

$params = array (
		"class" => "spaced",
		"number" => - 1,
		"target" => $module,
		"passthis" => true,
		"label" => "Submit Full Query" 
);
$main->echo_button ( "submit_query", $params );

$main->echo_clear ();

for($i = 0; $i <= 10; $i ++) {
	$arr_number [] = $i;
}
$params = array (
		"select_class" => "spaced",
		"onchange" => "bb_reload()" 
);
echo "<div class=\"spaced floatleft\"><span>Number of Subqueries: </span>";
$main->array_to_select ( $arr_number, "number_sub_queries", $number_sub_queries, $params );
echo "</div>";

$params = array (
		"select_class" => "spaced",
		"usekey" => true,
		"onchange" => "bb_reload()" 
);

echo "<div class=\"spaced floatleft\"><span>Limit Return Rows: </span>";
$main->array_to_select ( $arr_limit, "row_limit", $row_limit, $params );
echo "</div>";

$main->echo_clear ();

// loop through subqueries
if ($number_sub_queries > 0) {
	echo "<table class=\"spaced query_alias_query_table\" >";
	echo "<tr><td class=\"padded border\"></td><td class=\"padded border\">SubQuery Alias</td><td class=\"padded border\">SubQuery Value</td></tr>";
	for($i = 1; $i <= $number_sub_queries; $i ++) {
		echo "<tr><td class=\"padded border medium top\">";
		$params = array (
				"class" => "spaced",
				"number" => $i,
				"target" => $module,
				"passthis" => true,
				"label" => "Submit SubQuery" 
		);
		$main->echo_button ( "submit_subquery_" . $i, $params );
		echo "</td><td class=\"padded border medium top\">";
		$name = $main->pad ( "s", $i );
		$main->echo_input ( $name, $arr_sub_queries [$i] ['name'], array (
				'type' => "text",
				'class' => "spaced" 
		) );
		echo "</td><td class=\"padded border top query_alias_sub_query\">";
		$subquery = $main->pad ( "q", $i );
		$main->echo_textarea ( $subquery, $arr_sub_queries [$i] ['subquery'], array (
				'class' => "spaced query_alias_sub_textarea" 
		) );
		echo "</td></tr>";
	}
	echo "<tr><td class=\"padded border top\" colspan=\"3\">";
	$main->echo_textarea ( "substituter", $substituter, array (
			'class' => "spaced query_alias_full_textarea",
			'rows' => 7 
	) );
	
	if (isset ( $fullquery )) {
		echo "<tr><td class=\"padded border top query_alias_return_query\" colspan=\"3\">";
		echo "<div class=\"padded\">" . $fullquery . "</div>";
		echo "</td></tr>";
	}
	echo "</table>";
}

// echo report
if (isset ( $result )) {
	$main->echo_report_vars ();
	// output report
	if ($result) {
		$main->output_report ( $result, $current, $settings );
	}
}

$main->echo_form_end ();
/* END FORM */
?>

