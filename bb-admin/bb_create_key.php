<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
Copyright (C) 2012 - 2015  Kermit Will Richardson, Brimbox LLC

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License Version 3 (“GNU GPL v3”)
as published by the Free Software Foundation. 

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU GPL v3 for more details. 

You should have received a copy of the GNU GPL v3 along with this program.
If not, see http://www.gnu.org/licenses/
*/
?>
<?php
$main->check_permission("bb_brimbox", 5);
?>
<script type="text/javascript">
function bb_reload()
    {
    bb_submit_form();    
    }
</script>
<?php

/* PRESERVE STATE */
$POST = $main->retrieve($con);
$arr_notes = array("49","50");

//start code here
$arr_messages = array();

//layouts
$arr_layouts = $main->get_json($con, "bb_layout_names");
$arr_layouts_reduced = $main->filter_keys($arr_layouts);
$default_row_type = $main->get_default_layout($arr_layouts_reduced);

/* PRESERVE STATE */
$POST = $main->retrieve($con);

//get state from db
$arr_state = $main->load($con, $module);

//columns -- need row_type
$row_type = $main->post('row_type', $module, $default_row_type);
$arr_columns = $main->get_json($con, "bb_column_names");
$arr_column_reduced = $main->filter_keys($arr_columns['bb_brimbox'][$row_type]);
$arr_layout = $arr_layouts_reduced[$row_type];
$default_col_type = $main->get_default_column($arr_column_reduced);

//get col_type
if ($main->changed('row_type', $module, $arr_state, $default_row_type))
    {
    $col_type = $main->set('col_type', $arr_state, $default_col_type);   
    }
else
    {
    $col_type = $main->process("col_type", $module, $arr_state, $default_col_type);    
    }

//process row_type
$row_type = $main->process('row_type', $module, $arr_state, $default_row_type);

//update state
$main->update($con, $module, $arr_state);

//check_column    
if ($main->button(1) || $main->button(2)) 
    {
    $unique_key = isset($arr_columns['bb_brimbox'][$row_type]['unique']) ? $arr_columns['bb_brimbox'][$row_type]['unique'] : false;
	$column = $main->pad("c", $col_type);
	//unique key already set
    if ($unique_key)
        {
        if ($main->button(1))
            {
            array_push($arr_messages, "Column \"" . $arr_columns['bb_brimbox'][$row_type][$unique_key]['name'] . "\" on layout \"" . $arr_layout['plural'] . "\" has a unique key on it.");
            }
        elseif ($main->button(2))
            {
            array_push($arr_messages, "Error: Unique Key is already set on layout \"" . $arr_layout['plural'] . "\" . Column \"" . $arr_columns['bb_brimbox'][$row_type][$unique_key]['name'] . "\" has a unique key on it.");
            }
        }
	//no key
    elseif ($col_type)
        {
		//check for duplicates
        $query = "SELECT 1 FROM (SELECT " . $column . ", count(" . $column . ") FROM data_table WHERE row_type = " . $row_type . " GROUP BY " . $column . " HAVING count(" . $column . ") > 1) T1";              
        $result = $main->query($con, $query);
        if (pg_num_rows($result) > 0)
            {    
            array_push($arr_messages, "Error: Column \"" . $arr_column_reduced[$col_type]['name'] . "\" contains duplicate values. Unique key cannot be created.");
            }               
		//check if note column 
        if (in_array($column,$arr_notes))
            {
            array_push($arr_messages, "Error: Unique Key cannot be created on Note column. Unique key cannot be created.");
            }
        }
    else
        {
        array_push($arr_messages, "Error: No column available for key creation.");
        }
    }
//if no message, inform administartor or add key, col_type > 0 so empty works
if ($main->button(1) && $col_type && empty($arr_messages)) //check_column
    {
    array_push($arr_messages, "Unique key can be created on layout \"" . $arr_layout['plural'] . "\" column \"" . $arr_column_reduced[$col_type]['name'] . "\".");       
    }	
elseif ($main->button(2) && $col_type && empty($arr_messages)) //add_key, $col_type <> 0
	{
    $arr_columns['bb_brimbox'][$row_type]['unique'] = $col_type;
    
    //Update json row explicitly, check for valid key
    $query = "UPDATE json_table SET jsondata = '" . json_encode($arr_columns) . "' WHERE lookup = 'bb_column_names' " .
             "AND NOT EXISTS (SELECT 1 FROM data_table WHERE row_type = " . $row_type . " GROUP BY " . $column . " HAVING count(" . $column . ") > 1);";
    //echo "<p>" . $query . "</p>";
    $result = $main->query($con, $query);
    
    if (pg_affected_rows($result) == 1) //key updated or set
        {
        array_push($arr_messages, "Unique Key has been created on layout \"" . $arr_layout['plural'] . "\", column \"" . $arr_column_reduced[$col_type]['name'] . "\".");     
        }
    else //something changed
        {
        array_push($arr_messages, "Unique Key has not been created on layout \"" . $arr_layout['plural'] . "\", column \"" . $arr_column_reduced[$col_type]['name'] . "\". Underlying data change.");     
        }
	}
	
if ($main->button(3)) //remove_key
    {
	if (isset($arr_columns['bb_brimbox'][$row_type]['unique']))
		{
        unset($arr_columns['bb_brimbox'][$row_type]['unique']);
		$main->update_json($con, $arr_columns, "bb_column_names");
		array_push($arr_messages, "Unique Key has been removed for this layout type \"" . $arr_layout['plural'] . "\".");  
		}
	else
		{
		array_push($arr_messages, "There is currently no key on layout type \"" . $arr_layout['plural'] . "\".");			
		}
	}
/* BEGIN REQUIRED FORM */
echo "<p class=\"spaced bold larger\">Create Key</p>";

echo "<div class=\"padded\">";
$main->echo_messages($arr_messages);
echo "</div>";


$main->echo_form_begin();
$main->echo_module_vars();;
echo "<div class=\"spaced borderleft bordertop floatleft\">";
$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>"Check Layout");
$main->echo_button("check_column", $params);
echo "<br>";
$params = array("class"=>"spaced","onchange"=>"bb_reload()");
$main->layout_dropdown($arr_layouts_reduced, "row_type", $row_type, $params);
$params = array("class"=>"spaced","onchange"=>"bb_reload()");
$main->column_dropdown($arr_column_reduced, "col_type", $col_type, $params);
echo "<br>";
$params = array("class"=>"spaced","number"=>2,"target"=>$module, "passthis"=>true, "label"=>"Create Key");
$main->echo_button("add_key", $params);
$params = array("class"=>"spaced","number"=>3,"target"=>$module, "passthis"=>true, "label"=>"Remove Key");
$main->echo_button("remove_key", $params);
echo "<br>&nbsp;<br>&nbsp;<br></div>";
$main->echo_form_end();

/* END FORM */
?>

