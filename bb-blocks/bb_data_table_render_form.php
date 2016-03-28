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

if (! function_exists ( 'bb_data_table_render_form' )) :

	function bb_data_table_render_form(&$arr_state, $params = array()) {
		// session or global vars, superglobals
		global $con, $main, $submit;
		
		// standard values
		$arr_relate = array (
				41,
				42,
				43,
				44,
				45,
				46 
		);
		$arr_file = array (
				47 
		);
		$arr_reserved = array (
				48 
		);
		$arr_notes = array (
				49,
				50 
		);
		$textarea_rows = 4; // minimum
		$delimiter = $main->get_constant ( 'BB_MULTISELECT_DELIMITER', "," );
		$maxinput = $main->get_constant ( 'BB_STANDARD_LENGTH', 255 );
		$maxnote = $main->get_constant ( 'BB_NOTE_LENGTH', 65536 );
		
		// because of hook default value must be set here
		if (! is_array ( $params ))
			$params = array ();
			// unpack params into variables
		foreach ( $params as $key => $value ) {
			${$key} = $value;
		}
		// readonly at the form level
		$arr_readonly = isset ( $arr_readonly ) ? $arr_readonly : array ();
		// hidden at the form level, WILL BE PRESENT in source code
		$arr_hidden = isset ( $arr_hidden ) ? $arr_hidden : array ();
		
		// set readonly and hidden flags
		foreach ( $arr_readonly as $value )
			$arr_columns [$value] ['readonly'] = true;
		foreach ( $arr_hidden as $value )
			$arr_columns [$value] ['hidden'] = true;
			
			// $ayouts must have one layout set
		$arr_layouts = $main->layouts ( $con );
		$default_row_type = $main->get_default_layout ( $arr_layouts );
		
		// bring in everything from state
		$row_type = $main->state ( 'row_type', $arr_state, $default_row_type );
		$row_join = $main->state ( 'row_join', $arr_state, 0 );
		$post_key = $main->state ( 'post_key', $arr_state, 0 );
		
		$arr_columns = $main->columns ( $con, $row_type );
		$arr_dropdowns = $main->dropdowns ( $con, $row_type );
		
		// get the error and regular messages, populated form redirect
		$arr_messages = $main->state ( 'arr_messages', $arr_state, array () );
		$arr_errors = $main->state ( 'arr_errors', $arr_state, array () );
		
		echo "<div class=\"spaced\" id=\"input_message\">";
		$main->echo_messages ( $arr_messages );
		echo "</div>";
		/* END MESSAGES */
		
		/* POPULATE INPUT FIELDS */
		// check if empty, could be either empty or children not populated
		// this is dependent on admin module "Set Column Names"
		echo "<div id=\"bb_input_fields\">"; // id wrapper
		foreach ( $arr_columns as $key => $value ) {
			// key is col_type, $value is array
			$col = $main->pad ( "c", $key );
			$input = (isset ( $arr_state [$col] )) ? $arr_state [$col] : "";
			$error = (isset ( $arr_errors [$key] )) ? $arr_errors [$key] : "";
			$readonly = $hidden = false;
			$display = isset ( $arr_columns [$key] ['display'] ) ? $arr_columns [$key] ['display'] : 0;
			switch ($display) {
				case 1 :
					$readonly = true;
					break;
				case 2 :
					$hidden = true;
					break;
			}
			$filtername = "bb_input_" . $main->make_html_id ( $row_type, $key );
			$field_id = "bb_input_" . $main->make_html_id ( $row_type, $key );
			// different field types
			// dropdown type, multiselect possible
			if (isset ( $arr_dropdowns [$key] )) {
				$arr_dropdown = $arr_dropdowns [$key];
				$multiselect = $main->init ( $arr_dropdown ['multiselect'], false );
				$dropdown = $main->filter_keys ( $arr_dropdown );
				$input = is_array ( $input ) ? $input : array (
						$input 
				); // convert to array
				$size = $multiple = $disabled = "";
				if ($readonly) {
					$dropdown = $input;
					$disabled = "disabled";
				}
				$hide = ($hidden) ? "hidden" : ""; // hidden class
				                                   // mutliselect
				if ($multiselect) // set and true
{
					$multiple = "multiple=\"multiple\"";
					$size = "size=4";
					$col = $col . "[]";
				}
				$field_output = "<div class=\"clear " . $hide . "\">";
				$field_output .= "<label class = \"spaced padded floatleft right overflow medium shaded " . $hidden . "\" for=\"" . $col . "\">" . htmlentities ( $value ['name'] ) . ": </label>";
				$field_output .= "<select id=\"" . $field_id . "\" class = \"spaced\" name = \"" . $col . "\" " . $size . " " . $multiple . " onFocus=\"bb_remove_message(); return false;\">";
				foreach ( $dropdown as $value ) {
					$selected = is_int ( array_search ( strtolower ( $value ), array_map ( 'strtolower', $input ) ) ) ? "selected" : "";
					$field_output .= "<option value=\"" . htmlentities ( $value ) . "\" " . $selected . " " . $disabled . ">" . htmlentities ( $value ) . "&nbsp;</option>";
				}
				$field_output .= "</select><label class=\"error\">" . $error . "</label></div>";
			} // possible related record type, could be straight text
elseif (in_array ( $key, $arr_relate )) {
				$attribute = $readonly ? "readonly" : ""; // readonly attribute
				$hide = ($hidden) ? "hidden" : ""; // hidden class
				$field_output = "<div class = \"clear " . $hide . "\">";
				$field_output .= "<label class = \"spaced padded floatleft right overflow medium shaded " . $hide . "\" for=\"" . $col . "\">" . htmlentities ( $value ['name'] ) . ": </label>";
				$field_output .= "<input id=\"" . $field_id . "\" class = \"spaced textbox\" maxlength=\"" . $maxinput . "\" name=\"" . $col . "\" type=\"text\" value = \"" . htmlentities ( $input ) . "\" " . $attribute . " onFocus=\"bb_remove_message(); return false;\" />";
				$field_output .= "<label class=\"error\">" . $error . "</label></div>";
			} // file type
elseif (in_array ( $key, $arr_file )) {
				$attribute = $readonly ? "readonly" : "";
				$hide = ($hidden) ? "hidden" : ""; // hidden class
				$lo = isset ( $arr_state ['lo'] ) ? $arr_state ['lo'] : "";
				$field_output = "<div class = \"clear " . $hide . "\">";
				$field_output .= "<label class = \"spaced padded floatleft left overflow medium shaded " . $hide . "\" for=\"" . $col . "\">" . htmlentities ( $value ['name'] ) . ": </label>";
				$field_output .= "<input id=\"" . $field_id . "\" class = \"spaced padded textbox noborder\" name=\"lo\" type=\"text\" value = \"" . htmlentities ( $lo ) . "\" readonly/><label class=\"error\">" . $error . "</label>";
				$field_output .= "</div>";
				$field_output .= "<div class = \"clear " . $hide . "\">";
				$field_output .= "<input id=\"" . $field_id . "\" class=\"spaced textbox\" type=\"file\" name=\"" . $col . "\" id=\"file\"/>";
				if (! $value ['required']) {
					$field_output .= "<span class = \"spaced border rounded padded shaded\">";
					$field_output .= "<label class=\"padded\">Remove: </label>";
					$main->echo_input ( "remove", 1, array (
							'type' => 'checkbox',
							'input_class' => 'middle holderup' 
					) );
					$field_output .= "</span>";
				}
				$field_output .= "</div>";
			} // note type, will be textarea
elseif (in_array ( $key, $arr_notes )) {
				$attribute = $readonly ? "readonly" : ""; // readonly attribute
				$hide = ($hidden) ? "hidden" : ""; // hidden class
				$field_output = "<div class = \"clear " . $hide . "\">";
				$field_output .= "<label class = \"spaced padded floatleft left overflow medium shaded " . $hide . "\" for=\"" . $col . "\">" . htmlentities ( $value ['name'] ) . ": </label><label class=\"error spaced padded floatleft left overflow\">" . $error . "</label>";
				$field_output .= "<div class=\"clear " . $hide . "\"></div>";
				$field_output .= "<textarea id=\"" . $field_id . "\" class=\"spaced notearea\" maxlength=\"" . $maxnote . "\" name=\"" . $col . "\" " . $attribute . " onFocus=\"bb_remove_message(); return false;\">" . $input . "</textarea></div>";
			} else {
				// standard input/textbox
				$attribute = $readonly ? "readonly" : ""; // readonly attribute
				$hide = ($hidden) ? "hidden" : ""; // hidden class
				$field_output = "<div class=\"clear " . $hide . "\">";
				$field_output .= "<label class = \"spaced padded floatleft right overflow medium shaded\" for=\"" . $col . "\">" . htmlentities ( $value ['name'] ) . ": </label>";
				$field_output .= "<input id=\"" . $field_id . "\" class = \"spaced textbox\" maxlength=\"" . $maxinput . "\" name=\"" . $col . "\" type=\"text\" value = \"" . htmlentities ( $input ) . "\" " . $attribute . " onFocus=\"bb_remove_message(); return false;\" />";
				$field_output .= "<label class=\"error\">" . $error . "</label></div>";
			}
			// filter to echo the field output
			$field_output = $main->filter ( 'bb_input_field_output', $field_output );
			echo $field_output;
		}
		
		echo "</div>";
		echo "<div class=\"clear\"></div>";
		/* END POPULATE INPUT FIELDS */
		
		// hidden vars, $row_type is contained in the layout dropdown
		echo "<input type=\"hidden\"  name=\"post_key\" value = \"" . $post_key . "\">";
		echo "<input type=\"hidden\"  name=\"row_join\" value = \"" . $row_join . "\">";
		/* END FORM */
	}




        
endif; // pluggable
?>