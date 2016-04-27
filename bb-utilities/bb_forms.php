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

/* JAVASCRIPT FUNCTIONS */
// related bb_submit_form

/* PHP FUNCTIONS */
/* class bb_form() */
// echo_form_begin
// echo_module_vars
// echo_common_vars
// echo_button
// echo_script_button
// echo_input
// echo_textarea
// attributes
// echo_clear
// echo_tag
// echo_form_end
//
class bb_forms extends bb_links {

	function echo_form_begin($params = array()) {

		global $module;
		global $path;
		
		$params ['name'] = isset ( $params ['name'] ) ? $params ['name'] : "bb_form";
		$params ['method'] = isset ( $params ['method'] ) ? $params ['method'] : "post";
		
		// can do a custom post page
		if (! isset ( $params ['action'] )) {
			if (file_exists ( dirname ( $path ) . "/" . $module . "_post.php" )) {
				$params ['action'] = dirname ( $path ) . "/" . $module . "_post.php";
			} else {
				$params ['action'] = "post.php";
			}
		}
		
		$attributes = $this->attributes ( $params );
		
		echo "<form " . $attributes . ">";
	}

	function echo_module_vars() {
		// global make the most sense since these are global variables
		global $module, $slug;
		
		// should not be alter, how the controller works
		$arr_module_variables = array (
				'bb_module' => $module,
				'bb_submit' => "",
				'bb_button' => "",
				'bb_userrole' => "",
				'bb_object' => "" 
		);
		
		foreach ( $arr_module_variables as $name => $value ) {
			$params = array (
					'rel' => "ignore",
					'type' => "hidden" 
			);
			$this->echo_input ( $name, $value, $params );
		}
	}

	function echo_common_vars() {
		// echos common variables to support links
		// bb_post_key is the record id or drill down record key (two uses)
		// bb_row_type is the type of the primary record, or the parent record
		// bb_row_join is tthe type of the child record
		// bb_relate is the related record key
		global $array_common_variables;
		
		foreach ( $array_common_variables as $value ) {
			$params = array (
					'rel' => "ignore",
					'type' => "hidden" 
			);
			$this->echo_input ( $value, "", $params );
		}
	}

	function echo_button($name, $params = array()) {

		/* function to output button */
		$params = array (
				'name' => $name 
		) + $params;
		
		// javascript parameters
		$number = isset ( $params ['number'] ) ? $params ['number'] : 0;
		// no target attribute for button
		$target = isset ( $params ['target'] ) ? "'" . $params ['target'] . "'" : "undefined";
		$slug = isset ( $params ['slug'] ) ? "'" . $params ['slug'] . "'" : "undefined";
		$passthis = isset ( $params ['passthis'] ) ? "this" : "undefined";
		$javascript_params = "[$number, $target, $passthis]";
		unset ( $params ['number'], $params ['target'], $params ['passthis'] );
		
		// onclick very specific with this item
		$params ['onclick'] = "bb_submit_form(" . $javascript_params . "); return false;";
		
		// label is special
		$label = isset ( $params ['label'] ) ? $params ['label'] : "";
		unset ( $params ['label'] );
		
		// implode attributes
		$attributes = $this->attributes ( $params );
		
		echo "<button " . $attributes . ">" . $label . "</button>";
	}

	function echo_script_button($name, $params = array()) {
		// function to output button
		$params = array (
				'name' => $name 
		) + $params;
		$label = $params ['label'];
		unset ( $params ['label'] );
		
		$attributes = $this->attributes ( $params );
		
		echo "<button " . $attributes . ">" . $label . "</button>";
	}

	function echo_input($name, $value = "", $params = array()) {
		// function to output input html object
		
		// string params
		$params = array (
				'name' => $name 
		) + $params + array (
				'value' => $value 
		);
		
		// true or false params
		if (isset ( $params ['checked'] ) && $params ['checked'])
			$attr_item = " checked";
		elseif (isset ( $params ['readonly'] ) && $params ['readonly'])
			$attr_item = " readonly";
		else
			$attr_item = "";
		unset ( $params ['checked'], $params ['readonly'] );
		
		$attributes_input = $this->attributes ( $params );
		
		// zend hack -- give empty checkbox a zero value with a hidden input of same name
		if ($params ['type'] == "checkbox") {
			$params ['type'] = "hidden";
			$params ['value'] = 0;
			$attributes_hidden = $this->attributes ( $params );
			echo "<input " . $attributes_hidden . "/>";
		}
		echo "<input " . $attributes_input . $attr_item . "/>";
	}

	function echo_textarea($name, $value = "", $params = array()) {
		// function to output button
		$params = array (
				'name' => $name 
		) + $params;
		
		// readonly is special
		$readonly = isset ( $params ['readonly'] ) ? " readonly" : "";
		unset ( $params ['readonly'] );
		
		$attributes = $this->attributes ( $params );
		
		echo "<textarea " . $attributes . $readonly . ">" . $value . "</textarea>";
	}

	function attributes($params) {

		$arr_implode = array ();
		foreach ( $params as $key => $value ) {
			$arr_implode [] = $key . "=\"" . $value . "\"";
		}
		return implode ( " ", $arr_implode );
	}

	function echo_clear() {

		echo "<div class=\"clear\"></div>";
	}

	function echo_tag($tag, $content = "", $params = array()) {

		$attributes = $this->attributes ( $params );
		echo "<" . $tag . " " . $attributes . ">" . $content . "</" . $tag . ">";
	}

	function echo_form_end() {
		// end form tag, why not
		echo "</form>";
	}
} // end class
?>