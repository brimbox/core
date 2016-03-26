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

$main->check_permission ( "bb_brimbox", 5 );

// input form textarea max length
$maxinput = $main->get_constant ( 'BB_STANDARD_LENGTH', 255 );
// check for constant, max is 26
$number_layouts = $main->get_constant ( 'BB_NUMBER_LAYOUTS', 12 );

// start of code
$arr_layouts = $main->get_json ( $con, "bb_layout_names" ); // do not reduce
$arr_layout_security = $array_header ['layout_security'];

$arr_messages = array ();

/* PRESERVE STATE */

// get $POST variable
$POST = $main->retrieve ( $con );

// sorting function
function cmp($a, $b) {

	if ($a ['order'] == $b ['order']) {
		return 0;
	}
	return ($a ['order'] < $b ['order']) ? - 1 : 1;
}

$arr_layouts_fields = array (
		'parent' => array (
				'name' => "parent" 
		),
		'order' => array (
				'name' => "Order" 
		),
		'secure' => array (
				'name' => "Secure" 
		),
		'autoload' => array (
				'name' => "Autoload" 
		),
		'relate' => array (
				'name' => "Relate" 
		) 
);

// waterfall, first layout, second layout, third layout
// will only work if first layout is populated
if ($main->button ( 1 )) // layout_submit
{
	// one record must be populated
	// use two arrays,
	for($i = 1; $i <= $number_layouts; $i ++) {
		$singular = $main->purge_chars ( $main->init ( $main->post ( 'singular_' . $i, $module, "" ), "" ) );
		$plural = $main->purge_chars ( $main->init ( $main->post ( 'plural_' . $i, $module, "" ), "" ) );
		
		// OR condition to save variables in state
		if (! $main->blank ( $singular ) && ! $main->blank ( $plural )) {
			$arr_layouts [$i] ['singular'] = $singular;
			$arr_layouts [$i] ['plural'] = $plural;
			foreach ( $arr_layouts_fields as $key => $value ) {
				$arr_layouts [$i] [$key] = $main->purge_chars ( $main->post ( $key . '_' . $i, $module, 0 ), true, true );
			}
			$populated = $i;
		}
	}
	
	// check for integrity
	$both = false;
	$arr_singular = array ();
	$arr_plural = array ();
	$arr_order = array ();
	$arr_parent = array ();
	
	for($i = 1; $i <= $populated; $i ++) {
		$singular = $arr_layouts [$i] ['singular'];
		$plural = $arr_layouts [$i] ['plural'];
		// AND condition to save variables in database JSON table
		if (! $main->blank ( $singular ) && ! $main->blank ( $plural )) {
			$both = true; // good
			              // test for uniqueness
			$parent = $arr_layouts [$i] ['parent'];
			$order = $arr_layouts [$i] ['order'];
			// check parent and child are not recursive or circular
			$parent_forward = $i . $parent;
			$parent_reverse = $parent . $i;
			
			array_push ( $arr_singular, $singular );
			array_push ( $arr_plural, $plural );
			array_push ( $arr_order, $order );
			// check for circular references
			array_push ( $arr_parent, $parent_forward );
			array_push ( $arr_parent, $parent_reverse );
		}
	}
	
	if ($both) // one layout populated
{
		// check for distinct values in singular or plural, use $main->array_iunique so check is not case sensitive
		if ((count ( $arr_singular ) != count ( $main->array_iunique ( $arr_singular ) )) || (count ( $arr_plural ) != count ( $main->array_iunique ( $arr_plural ) ))) {
			array_push ( $arr_messages, "Error: Layouts must have distinct singular and plural names." );
		}
		
		// check that count is in order
		sort ( $arr_order );
		if (($arr_order [0] != 1) || (count ( $arr_order ) != count ( array_unique ( $arr_order ) )) || ($arr_order [count ( $arr_order ) - 1] != count ( $arr_order ))) {
			array_push ( $arr_messages, "Error: Layouts order must start at 1 and be strictly ascending." );
		}
		
		// check for circular relationships
		$relationship = true;
		if (count ( $arr_parent ) != count ( array_unique ( $arr_parent ) )) {
			array_push ( $arr_messages, "Error: Circular or self-referential relationship between layouts." );
		}
	} 	

	// must have one layout populated
	else // empty
{
		array_push ( $arr_messages, "Error: Singular and plural must be populated for at least one layout." );
	}
	
	// all conditions go
	if (! $main->has_error_messages ( $arr_messages )) {
		// discard rows without both singular and plural, JSON changed and updated
		// sort on order and update JSON, layouts are stored sorted by order, not row_type
		uasort ( $arr_layouts, 'cmp' );
		$main->update_json ( $con, $arr_layouts, "bb_layout_names" );
		array_push ( $arr_messages, "Layouts have been updated." );
	}
} // submit

if ($main->button ( 2 )) // revert to json in database
{
	$arr_layouts = $main->get_json ( $con, "bb_layout_names" );
	array_push ( $arr_messages, "Layouts have been refreshed from database." );
}

if ($main->button ( 3 )) // vaccum database
{
	$query = "VACUUM;";
	$main->query ( $con, $query );
	array_push ( $arr_messages, "Database has been vacuumed." );
}

/* START REQUIRED FORM */
echo "<p class=\"spaced bold larger\">Layout Names</p>";

echo "<div class=\"padded\">";
$main->echo_messages ( $arr_messages );
echo "</div>";

$main->echo_form_begin ();
$main->echo_module_vars ();
;

echo "<div class=\"table spaced border\">";
echo "<div class=\"row\">";
echo "<div class=\"cell shaded middle\"><label class=\"spaced padded\">&nbsp;</label></div>";
echo "<div class=\"cell shaded middle\"><label class=\"spaced padded\">Singular</label></div>";
echo "<div class=\"cell shaded middle\"><label class=\"spaced padded\">Plural</label></div>";
foreach ( $arr_layouts_fields as $key => $value ) {
	echo "<div class=\"cell shaded middle\"><label class=\"spaced padded\">" . $value ['name'] . "</label></div>";
}
echo "</div>";
for($i = 1; $i <= $number_layouts; $i ++) {
	echo "<div class=\"row\">"; // begin row
	                            
	// label
	echo "<div class=\"cell middle\">";
	echo "<label class=\"spaced\">Layout " . chr ( $i + 64 ) . $i . "</label>";
	echo "</div>";
	
	// required
	echo "<div class=\"cell middle\">";
	$value = isset ( $arr_layouts [$i] ) ? htmlentities ( $arr_layouts [$i] ['singular'] ) : "";
	$main->echo_input ( "singular_" . $i, $value, array (
			'type' => "input",
			'class' => "spaced",
			'maxlength' => $maxinput 
	) );
	echo "</div>";
	
	// required
	echo "<div class=\"cell middle\">";
	$value = isset ( $arr_layouts [$i] ) ? htmlentities ( $arr_layouts [$i] ['plural'] ) : "";
	$main->echo_input ( "plural_" . $i, $value, array (
			'type' => "input",
			'class' => "spaced",
			'maxlength' => $maxinput 
	) );
	echo "</div>";
	
	foreach ( $arr_layouts_fields as $key => $value ) {
		switch ($key) {
			case "parent" :
				echo "<div class=\"cell middle\">";
				echo "<select class=\"spaced\" name=\"parent_" . $i . "\">";
				echo "<option value=\"0\">&nbsp;</option>";
				for($j = 1; $j <= $number_layouts; $j ++) {
					$selected = "";
					if (isset ( $arr_layouts [$i] ['parent'] )) {
						$selected = ($arr_layouts [$i] ['parent'] == $j) ? "selected" : "";
					}
					echo "<option value=\"" . $j . "\" " . $selected . ">" . chr ( $j + 64 ) . $j . "&nbsp;</option>";
				}
				echo "</select>";
				echo "</div>";
				break;
			
			case "order" :
				echo "<div class=\"cell middle\">";
				echo "<select class=\"spaced\" name=\"order_" . $i . "\">";
				echo "<option value=\"0\"0>&nbsp;</option>";
				for($j = 1; $j <= $number_layouts; $j ++) {
					$selected = "";
					if (isset ( $arr_layouts [$i] ['order'] )) {
						$selected = ($arr_layouts [$i] ['order'] == $j) ? "selected" : "";
					}
					echo "<option value=\"" . $j . "\" " . $selected . ">" . $j . "&nbsp;</option>";
				}
				echo "</select>";
				echo "</div>";
				break;
			
			case "secure" :
				// secure checkbox
				if (empty ( $arr_layout_security )) {
					echo "<div class=\"cell padded middle center\">";
					// has a zero or 1 value
					$checked = false;
					if (isset ( $arr_layouts [$i] ['secure'] )) {
						$checked = ($arr_layouts [$i] ['secure']) == 1 ? true : false;
					}
					$main->echo_input ( "secure_" . $i, 1, array (
							'type' => 'checkbox',
							'class' => 'holderdown',
							'checked' => $checked 
					) );
					echo "</div>";
				} else {
					echo "<div class = \"cell middle\">";
					echo "<select name=\"secure_" . $i . "\"class = \"spaced\">";
					foreach ( $arr_layout_security as $key => $value ) {
						$selected = "";
						if (isset ( $arr_layouts [$i] ['secure'] )) {
							$selected = ($arr_layouts [$i] ['secure'] == $key) ? "selected" : "";
						}
						echo "<option value = \"" . $key . "\" " . $selected . ">" . htmlentities ( $value ) . "&nbsp;</option>";
					}
					echo "</select>";
					echo "</div>";
				}
				break;
			
			case "autoload" :
				// autoload
				echo "<div class=\"cell padded middle center\">";
				$checked = false;
				if (isset ( $arr_layouts [$i] ['autoload'] )) {
					$checked = ($arr_layouts [$i] ['autoload'] == 1) ? true : false;
				}
				$main->echo_input ( "autoload_" . $i, 1, array (
						'type' => 'checkbox',
						'class' => 'holderdown',
						'checked' => $checked 
				) );
				echo "</div>";
				break;
			
			case "relate" :
				// relate checkbox
				echo "<div class=\"cell padded middle center\">";
				$checked = false;
				if (isset ( $arr_layouts [$i] ['relate'] )) {
					$checked = ($arr_layouts [$i] ['relate'] == 1) ? true : false;
				}
				$main->echo_input ( "relate_" . $i, 1, array (
						'type' => 'checkbox',
						'class' => 'holderdown',
						'checked' => $checked 
				) );
				echo "</div>";
				break;
		} // end switch
	} // end foreach
	echo "</div>"; // end row
} // end for
echo "</div>"; // end table

$params = array (
		"class" => "spaced",
		"number" => 1,
		"target" => $module,
		"passthis" => true,
		"label" => "Submit Layouts" 
);
$main->echo_button ( "layout_submit", $params );
$params = array (
		"class" => "spaced",
		"number" => 2,
		"target" => $module,
		"passthis" => true,
		"label" => "Refresh Layouts" 
);
$main->echo_button ( "refresh_layout", $params );
$params = array (
		"class" => "spaced",
		"number" => 3,
		"target" => $module,
		"passthis" => true,
		"label" => "Vacuum Database" 
);
$main->echo_button ( "vacuum_database", $params );

$main->echo_form_end ();
/* END FORM */
?>
