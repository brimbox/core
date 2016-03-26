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

// STANDARD INTERFACE #

if ($interface == "bb_brimbox") {
	$main->add_value ( 'header', "Brimbox" . 'interface_name' );
	$main->add_value ( 'header', array (
			'1_bb_brimbox' => 'Guest',
			'2_bb_brimbox' => 'Viewer',
			'3_bb_brimbox' => 'User',
			'4_bb_brimbox' => 'Superuser',
			'5_bb_brimbox' => 'Admin' 
	), 'userroles' );
	$main->add_value ( 'header', array (
			1 => "Guest",
			2 => "Viewer",
			3 => "Tab",
			4 => "Setup",
			5 => "Admin" 
	), 'module_types' );
	$main->add_value ( 'header', "/box.php", 'controller' );
}

// TYPICALLY GLOBAL #

$main->add_value ( 'header', array (), 'row_archive' );
$main->add_value ( 'header', array (), 'row_security' );
$main->add_value ( 'header', array (), 'layout_security' );
$main->add_value ( 'header', array (), 'column_security' );
$main->add_value ( 'header', array (), 'guest_index' );

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

if ($interface == "bb_brimbox") {
	// STANDARD HOOKS #
	// use static classes or standard functions
	// $main->add_action('hooks'] = array('hook'=>"index_hot_state",'func'=>"bb_index_hot_state",'vars'=>array("con", "main", "interface", "&array_hot_state"), 'file'=>"/bb-blocks/bb_index_hot_state.php");
	
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
					"&arr_state",
					"&arr_columns",
					"&row",
					"params" 
			),
			'file' => "/bb-blocks/bb_input_module_postback.php" 
	), 50 );
	$main->add_action ( 'hooks', "bb_input_module_autofill", array (
			'func' => "bb_input_module_autofill",
			'vars' => array (
					"&arr_state",
					"row",
					"params" 
			),
			'file' => "/bb-blocks/bb_input_module_autofill.php" 
	), 50 );
	$main->add_action ( 'hooks', "bb_input_data_table_render_form", array (
			'func' => "bb_data_table_render_form",
			'vars' => array (
					"&arr_state",
					"params" 
			),
			'file' => "/bb-blocks/bb_data_table_render_form.php" 
	), 50 );
	$main->add_action ( 'hooks', "bb_input_redirect_postback", array (
			'func' => "bb_input_redirect_postback",
			'vars' => array (
					"&arr_state",
					"params" 
			),
			'file' => "/bb-blocks/bb_input_redirect_postback.php" 
	), 50 );
	$main->add_action ( 'hooks', "bb_input_data_table_row_validate", array (
			'func' => "bb_data_table_row_validate",
			'vars' => array (
					"&arr_state",
					"params" 
			),
			'file' => "/bb-blocks/bb_data_table_row_validate.php" 
	), 50 );
	$main->add_action ( 'hooks', "bb_input_data_table_row_input", array (
			'func' => "bb_data_table_row_input",
			'vars' => array (
					"&arr_state",
					"params" 
			),
			'file' => "/bb-blocks/bb_data_table_row_input.php" 
	), 50 );
	
	/* these are the upload data hooks */
	$main->add_action ( 'hooks', "bb_upload_data_row_validation", array (
			'func' => "bb_data_table_row_validate",
			'vars' => array (
					"&arr_layouts",
					"&arr_columns",
					"&arr_dropdowns",
					"&arr_pass",
					"params" 
			),
			'file' => "/bb-blocks/bb_data_table_row_validate.php" 
	), 50 );
	$main->add_action ( 'hooks', "bb_upload_data_row_input", array (
			'func' => "bb_data_table_row_input",
			'vars' => array (
					"&arr_layouts",
					"&arr_columns",
					"&arr_dropdowns",
					"&arr_pass",
					"params" 
			),
			'file' => "/bb-blocks/bb_data_table_row_input.php" 
	), 50 );
	
	/* these are the input hook the are buried in the render form input function */
	/* note the use of include_once */
	$main->add_action ( 'hooks', "bb_input_top_level_records", array (
			'func' => "bb_input_module_hooks::top_level_records",
			'vars' => array (
					"arr_state" 
			),
			'file' => "/bb-blocks/bb_input_module_hooks.php" 
	), 50 );
	$main->add_action ( 'hooks', "bb_input_parent_record", array (
			'func' => "bb_input_module_hooks::parent_record",
			'vars' => array (
					"arr_state" 
			),
			'file' => "/bb-blocks/bb_input_module_hooks.php" 
	), 50 );
	$main->add_action ( 'hooks', "bb_input_quick_links", array (
			'func' => "bb_input_module_hooks::quick_links",
			'vars' => array (
					"arr_state" 
			),
			'file' => "/bb-blocks/bb_input_module_hooks.php" 
	), 50 );
	$main->add_action ( 'hooks', "bb_input_submit_buttons", array (
			'func' => "bb_input_module_hooks::submit_buttons",
			'vars' => array (
					"arr_state" 
			),
			'file' => "/bb-blocks/bb_input_module_hooks.php" 
	), 50 );
	$main->add_action ( 'hooks', "bb_input_textarea_load", array (
			'func' => "bb_input_module_hooks::textarea_load",
			'vars' => array (
					"arr_state" 
			),
			'file' => "/bb-blocks/bb_input_module_hooks.php" 
	), 50 );
	
	$main->add_action ( 'filters', "bb_column_names_definitions", array (
			'func' => 'test_definitions' 
	), 50 );
	
	// STANDARD INTERFACE DEFINITION #
	$main->add_value ( 'interface', array (
			'interface_type' => 'Standard',
			'usertypes' => array (
					1 
			),
			'module_type' => 1 
	) );
	$main->add_value ( 'interface', array (
			'interface_type' => 'Standard',
			'usertypes' => array (
					2 
			),
			'module_type' => 2 
	) );
	$main->add_value ( 'interface', array (
			'interface_type' => 'Standard',
			'usertypes' => array (
					3,
					4,
					5 
			),
			'module_type' => 3 
	) );
	$main->add_value ( 'interface', array (
			'interface_type' => 'Auxiliary',
			'usertypes' => array (
					4,
					5 
			),
			'module_type' => 4,
			'friendly_name' => 'Setup' 
	) );
	$main->add_value ( 'interface', array (
			'interface_type' => 'Auxiliary',
			'usertypes' => array (
					5 
			),
			'module_type' => 5,
			'friendly_name' => 'Admin' 
	) );
	
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
	) ))
		;
	{
		$main->add_value ( 'links', array (
				array (
						$main,
						'standard' 
				),
				array (
						"bb_details",
						"details",
						"Details" 
				) 
		), 10 );
		$main->add_value ( 'links', array (
				'bb_main::standard',
				array (
						"bb_cascade",
						"cascade",
						"Cascade" 
				) 
		), 20 );
		$main->add_value ( 'links', array (
				'bb_main::edit',
				array (
						"bb_input",
						"input",
						"Edit" 
				) 
		), 30 );
		$main->add_value ( 'links', array (
				'bb_main::relate',
				array (
						"bb_input",
						"input",
						"Relate" 
				) 
		), 40 );
		$main->add_value ( 'links', array (
				'bb_main::standard',
				array (
						"bb_listchoose",
						"listchoose",
						"List" 
				) 
		), 50 );
		$main->add_value ( 'links', array (
				'bb_main::standard',
				array (
						"bb_archive",
						"archive",
						"Archive" 
				) 
		), 60 );
		$main->add_value ( 'links', array (
				'bb_main::standard',
				array (
						"bb_delete",
						"delete",
						"Delete" 
				) 
		), 70 );
		$main->add_value ( 'links', array (
				'bb_main::standard',
				array (
						"bb_secure",
						"secure",
						"Secure" 
				) 
		), 80 );
		$main->add_value ( 'links', array (
				'bb_main::children',
				array (
						"bb_input",
						"input",
						"Add",
						"bb_view",
						"view",
						"View" 
				) 
		), 90 );
	}
}

// CURRENT REPORT TYPES USUALLY GLOBAL
$main->add_value ( 'reports', array (
		0 => "" 
), 10 );
$main->add_value ( 'reports', array (
		1 => "Paginated" 
), 20 );
$main->add_value ( 'reports', array (
		2 => "Full" 
), 30 );
$main->add_value ( 'reports', array (
		3 => "Textarea" 
), 40 );

?>