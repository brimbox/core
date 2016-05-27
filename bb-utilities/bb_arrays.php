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

/* PRODUCES */

// $array_header
// $array_userroles
// $array_security
// $array_validation
// $array_interface
// $array_reports

// STANDARD HEADER
// key is interface
$main->add_value ( 'header', array (
		'name' => "Brimbox",
		'controller' => "/bb-box/bb_box.php" 
), 'bb_brimbox' );

// USERROLES HEADER ARRAYS
// key is userrole
$main->add_value ( 'userroles', array (
		'name' => 'Guest' 
), '1_bb_brimbox' );
$main->add_value ( 'userroles', array (
		'name' => 'Viewer' 
), '2_bb_brimbox' );
$main->add_value ( 'userroles', array (
		'name' => 'User' 
), '3_bb_brimbox' );
$main->add_value ( 'userroles', array (
		'name' => 'Superuser' 
), '4_bb_brimbox' );
$main->add_value ( 'userroles', array (
		'name' => 'Admin' 
), '5_bb_brimbox' );

// SECURITY HEADER ARRAYS
// key is security type
$main->add_value ( 'security', array (), 'row_archive' );
$main->add_value ( 'security', array (), 'row_security' );
$main->add_value ( 'security', array (), 'layout_security' );
$main->add_value ( 'security', array (), 'column_security' );
$main->add_value ( 'security', array (), 'guest_index' );

// VALIDATION HEADER ARRAY
// key is validation key
$main->add_value ( 'validation', array (
		'func' => array (
				$main,
				'validate_text' 
		),
		'name' => "Text",
		'use' => "Required" 
), "bb_brimbox_text" );
$main->add_value ( 'validation', array (
		'func' => array (
				$main,
				'validate_numeric' 
		),
		'name' => "Number",
		'use' => "Required" 
), "bb_brimbox_numeric" );
$main->add_value ( 'validation', array (
		'func' => array (
				$main,
				'validate_date' 
		),
		'name' => "Date",
		'use' => "Required" 
), "bb_brimbox_date" );
$main->add_value ( 'validation', array (
		'func' => array (
				$main,
				'validate_email' 
		),
		'name' => "Email",
		'use' => "Required" 
), "bb_brimbox_email" );
$main->add_value ( 'validation', array (
		'func' => array (
				$main,
				'validate_money' 
		),
		'name' => "Money",
		'use' => "Required" 
), "bb_brimbox_money" );
$main->add_value ( 'validation', array (
		'func' => array (
				$main,
				'validate_yesno' 
		),
		'name' => "Yes/No",
		'use' => "Required" 
), "bb_brimbox_yesno" );

// STANDARD INTERFACE DEFINITION #
// key is module type
// add_action sets up a array $array_interface[$interface][$module_type]
$main->add_action ( 'interface', 'bb_brimbox', array (
		'interface_type' => 'Standard',
		'userroles' => array (
				'1_bb_brimbox' 
		),
		'module_type_name' => 'Guest' 
), 1 );
$main->add_action ( 'interface', 'bb_brimbox', array (
		'interface_type' => 'Standard',
		'userroles' => array (
				'2_bb_brimbox' 
		),
		'module_type_name' => 'Viewer' 
), 2 );
$main->add_action ( 'interface', 'bb_brimbox', array (
		'interface_type' => 'Standard',
		'userroles' => array (
				'3_bb_brimbox',
				'4_bb_brimbox',
				'5_bb_brimbox' 
		),
		'module_type_name' => 'User' 
), 3 );
$main->add_action ( 'interface', 'bb_brimbox', array (
		'interface_type' => 'Auxiliary',
		'userroles' => array (
				'4_bb_brimbox',
				'5_bb_brimbox' 
		),
		'module_type_name' => 'Setup' 
), 4 );
$main->add_action ( 'interface', 'bb_brimbox', array (
		'interface_type' => 'Auxiliary',
		'userroles' => array (
				'5_bb_brimbox' 
		),
		'module_type_name' => 'Admin' 
), 5 );

// CURRENT REPORT TYPES ARRAY
// key is a priority
$main->add_value ( 'reports', array (
		'type' => 0,
		'name' => "" 
), 10 );
$main->add_value ( 'reports', array (
		'type' => 1,
		'name' => "Paginated" 
), 20 );
$main->add_value ( 'reports', array (
		'type' => 2,
		'name' => "Full" 
), 30 );
$main->add_value ( 'reports', array (
		'type' => 3,
		'name' => "Textarea" 
), 40 );

// BRIMBOX INTERFACE SPECIFIC

if ($interface == "bb_brimbox") :
	
	/* PRODUCES */
	
	// $array_hooks
	// $array_filters
	// $array_common_variables
	// $array_links
	
	// STANDARD HOOKS #
	// use static classes or standard functions
	$main->add_action ( 'hooks', 'index_hot_state', array (
			'func' => "bb_index_hot_state",
			'vars' => array (
					"con",
					"main",
					"interface",
					"&array_hot_state" 
			),
			'file' => "/bb-pluggables/bb_index_hot_state.php" 
	) );
	
	$main->add_action ( 'hooks', "bb_guest_infolinks", array (
			'func' => 'bb_main::infolinks' 
	) );
	$main->add_action ( 'hooks', "bb_viewer_infolinks", array (
			'func' => 'bb_main::infolinks' 
	) );
	$main->add_action ( 'hooks', "bb_home_infolinks", array (
			'func' => 'bb_main::infolinks' 
	) );
	
	/* these are the primary input and input redirect hooks */
	$main->add_action ( 'hooks', "bb_input_module_postback", array (
			'func' => "bb_input_module_postback",
			'vars' => array (
					"&arr_state" 
			),
			'file' => "/bb-pluggables/bb_input_module_postback.php" 
	), 50 );
	$main->add_action ( 'hooks', "bb_input_module_autofill", array (
			'func' => "bb_input_module_autofill",
			'vars' => array (
					"&arr_state" 
			),
			'file' => "/bb-pluggables/bb_input_module_autofill.php" 
	), 50 );
	$main->add_action ( 'hooks', "bb_input_data_table_render_form", array (
			'func' => "bb_data_table_render_form",
			'vars' => array (
					"&arr_state" 
			),
			'file' => "/bb-pluggables/bb_data_table_render_form.php" 
	), 50 );
	$main->add_action ( 'hooks', "bb_input_redirect_postback", array (
			'func' => "bb_input_redirect_postback",
			'vars' => array (
					"&arr_state" 
			),
			'file' => "/bb-pluggables/bb_input_redirect_postback.php" 
	), 50 );
	$main->add_action ( 'hooks', "bb_input_data_table_row_validate", array (
			'func' => "bb_data_table_row_validate",
			'vars' => array (
					"&arr_state" 
			),
			'file' => "/bb-pluggables/bb_data_table_row_validate.php" 
	), 50 );
	$main->add_action ( 'hooks', "bb_input_data_table_row_input", array (
			'func' => "bb_data_table_row_input",
			'vars' => array (
					"&arr_state" 
			),
			'file' => "/bb-pluggables/bb_data_table_row_input.php" 
	), 50 );
	
	/* these are the upload data hooks */
	$main->add_action ( 'hooks', "bb_upload_data_row_validation", array (
			'func' => "bb_data_table_row_validate",
			'vars' => array (
					"&arr_pass" 
			),
			'file' => "/bb-pluggables/bb_data_table_row_validate.php" 
	), 50 );
	$main->add_action ( 'hooks', "bb_upload_data_row_input", array (
			'func' => "bb_data_table_row_input",
			'vars' => array (
					"&arr_pass" 
			),
			'file' => "/bb-pluggables/bb_data_table_row_input.php" 
	), 50 );
	
	/* these are the input hook the are buried in the render form input function */
	/* note the use of include_once */
	$main->add_action ( 'hooks', "bb_input_top_level_records", array (
			'func' => "bb_input_module_hooks::top_level_records",
			'vars' => array (
					"arr_state" 
			),
			'file' => "/bb-pluggables/bb_input_module_hooks.php" 
	), 50 );
	$main->add_action ( 'hooks', "bb_input_parent_record", array (
			'func' => "bb_input_module_hooks::parent_record",
			'vars' => array (
					"arr_state" 
			),
			'file' => "/bb-pluggables/bb_input_module_hooks.php" 
	), 50 );
	$main->add_action ( 'hooks', "bb_input_quick_links", array (
			'func' => "bb_input_module_hooks::quick_links",
			'vars' => array (
					"arr_state" 
			),
			'file' => "/bb-pluggables/bb_input_module_hooks.php" 
	), 50 );
	$main->add_action ( 'hooks', "bb_input_submit_buttons", array (
			'func' => "bb_input_module_hooks::submit_buttons",
			'vars' => array (
					"arr_state" 
			),
			'file' => "/bb-pluggables/bb_input_module_hooks.php" 
	), 50 );
	$main->add_action ( 'hooks', "bb_input_textarea_load", array (
			'func' => "bb_input_module_hooks::textarea_load",
			'vars' => array (
					"arr_state" 
			),
			'file' => "/bb-pluggables/bb_input_module_hooks.php" 
	), 50 );
	
	$main->add_action ( 'filters', "bb_column_names_definitions", array (
			'func' => 'test_definitions' 
	), 50 );
	
	// COMMON VARS SHARED WITH OTHER TABS #
	// will not be processed through the form posting engine
	$main->add_value ( 'common_variables', "bb_row_type" );
	$main->add_value ( 'common_variables', "bb_row_join" );
	$main->add_value ( 'common_variables', "bb_post_key" );
	$main->add_value ( 'common_variables', "bb_relate" );
	
	// LINK VARS TO DEFINE RECORD LINKS #
	// use statis classes or standard functions
	// $row_type and $arr_layouts must be present $main->output_links($row, $arr_layouts, $userrole) call;
	if (in_array ( $userrole, array (
			"3_bb_brimbox",
			"4_bb_brimbox",
			"5_bb_brimbox" 
	) )) {
		$main->add_value ( 'links', array (
				'func' => array (
						$main,
						'standard' 
				),
				'params' => array (
						'target' => "bb_details",
						'slug' => "details",
						'text' => "Details" 
				) 
		), 10 );
		$main->add_value ( 'links', array (
				'func' => array (
						$main,
						'standard' 
				),
				'params' => array (
						'target' => "bb_cascade",
						'slug' => "cascade",
						'text' => "Cascade" 
				) 
		), 20 );
		$main->add_value ( 'links', array (
				'func' => array (
						$main,
						'edit' 
				),
				'params' => array (
						'target' => "bb_input",
						'slug' => "input",
						'text' => "Edit" 
				) 
		), 30 );
		$main->add_value ( 'links', array (
				'func' => array (
						$main,
						'relate' 
				),
				'params' => array (
						'target' => "bb_input",
						'slug' => "input",
						'text' => "Relate" 
				) 
		), 40 );
		$main->add_value ( 'links', array (
				'func' => array (
						$main,
						'standard' 
				),
				'params' => array (
						'target' => "bb_listchoose",
						'slug' => "listchoose",
						'text' => "List" 
				) 
		), 50 );
		$main->add_value ( 'links', array (
				'func' => array (
						$main,
						'standard' 
				),
				'params' => array (
						'target' => "bb_archive",
						'slug' => "archive",
						'text' => "Archive" 
				) 
		), 60 );
		$main->add_value ( 'links', array (
				'func' => array (
						$main,
						'standard' 
				),
				'params' => array (
						'target' => "bb_delete",
						'slug' => "delete",
						'text' => "Delete" 
				) 
		), 70 );
		$main->add_value ( 'links', array (
				'func' => array (
						$main,
						'standard' 
				),
				'params' => array (
						'target' => "bb_secure",
						'slug' => "secure",
						'text' => "Secure" 
				) 
		), 80 );
		$main->add_value ( 'links', array (
				'func' => array (
						$main,
						'children' 
				),
				'params' => array (
						'target_add' => "bb_input",
						'slug_add' => "input",
						'text_add' => "Add",
						'target_view' => "bb_view",
						'slug_view' => "view",
						'text_view' => "View" 
				) 
		), 90 );
	}
















endif; // interface bb_brimbox

?>