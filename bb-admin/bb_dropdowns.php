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
$main->check_permission("bb_brimbox", array(4,5));
?>
<script type="text/javascript">
//reload on layout change
function bb_reload()
    {
    //standard submit
	var frmobj = document.forms['bb_form'];
	//1 value will force program to find default value in PHP below		
	bb_submit_form();
	}
</script>
<?php
/* INITIALIZE */
$arr_messages = array();
$arr_notes = array("49","50");

$delimiter = $main->get_constant('BB_MULTISELECT_DELIMITER', ",");

/* GET STATE */
$POST = $main->retrieve($con);

//get state from db
$arr_state = $main->load($con, $module);

//This area creates the form for choosing the lists
$arr_layouts = $main->get_json($con, "bb_layout_names");
$arr_layouts_reduced = $main->filter_keys($arr_layouts);  
$default_row_type = $main->get_default_layout($arr_layouts_reduced);
//deal with columns
$arr_columns = $main->get_json($con, "bb_column_names");
$arr_column_reduced = $main->filter_keys($arr_columns[$row_type]);
//get dropdowns
$arr_dropdowns = $main->get_json($con, "bb_dropdowns");

//get default col_type
$row_type = $main->post('row_type', $module, $default_row_type);
$arr_column_reduced = $main->filter_keys($arr_columns[$row_type]);
$default_col_type = $main->get_default_column($arr_column_reduced);

if ($main->changed('row_type', $module, $arr_state, $default_row_type))
    {
    $row_type = $main->process('row_type', $module, $arr_state, $default_row_type);
    $col_type = $main->set('col_type', $arr_state, $default_col_type);
    $multiselect = $main->set('multiselect', $arr_state, $arr_dropdowns[$row_type][$col_type]['multiselect']);
    $all_values = $empty_value = 0;
    $dropdowns = "";
    }
else
    {
    if ($main->changed('col_type', $module, $arr_state, $default_col_type))
        {
        $row_type = $main->process('row_type', $module, $arr_state, $default_row_type);
        $col_type = $main->process('col_type', $module, $arr_state, $col_type);
        $multiselect = $main->set('multiselect', $arr_state, $arr_dropdowns[$row_type][$col_type]['multiselect']);
        $all_values = $empty_value = 0;
        $dropdowns = "";
        }    
    else
        {
        $row_type = $main->process('row_type', $module, $arr_state, $default_row_type);
        $col_type = $main->process('col_type', $module, $arr_state, $default_col_type);
        $multiselect = $main->process('multiselect', $module, $arr_state, 0);
        $all_values = $main->process('all_values', $module, $arr_state, 0);
        $empty_value = $main->process('empty_value', $module, $arr_state, 0);
        $dropdowns = $main->process('dropdowns', $module, $arr_state, "");
        }
    }
    
//update state, back to db
$main->update($con, $module, $arr_state);
   	
$col_text = isset($arr_column[$col_type]['name']) ? $arr_column[$col_type]['name'] : "";

//this area populates the textarea
if ($main->button(1)) //populate_dropdown
	{
    //preexisting dropdown xml
    $dropdown_reduced = $main->filter_keys($arr_dropdowns[$row_type][$col_type]);

    //get all values in database for the selected column (and row type)
    $column = $main->pad("c",$col_type);
	$query = "SELECT distinct " .  $column . " FROM data_table WHERE row_type = " . $row_type . " AND archive = 0 ORDER BY " . $column . " LIMIT 2000;";
	$result = $main->query($con, $query);
	
	$arr_query = pg_fetch_all_columns($result, 0);

    //values from db and dropdown alphabetized
	if (!empty($dropdown_reduced) && ($all_values == 0))
		{
		$arr_populate = array_merge($arr_query, $dropdown_reduced);
        if ($arr_dropdowns[$row_type][$col_type]['multiselect'])
            {
            $arr_delimiter =  preg_grep("/[" . preg_quote($delimiter) . "]/", $arr_populate);
            $arr_display = array();
            foreach ($arr_delimiter as $value)
                {
                $arr_display = $arr_display + explode($delimiter, $value);    
                }
            $arr_single =  preg_grep("/[" . preg_quote($delimiter) . "]/", $arr_populate, PREG_GREP_INVERT);
            $arr_display = $arr_display + $arr_single;
            }
        else
            {
            $arr_display = $arr_populate;   
            }
        $arr_display = array_filter($arr_display); //remove empty rows
        $arr_display = array_unique($arr_display); //unique
        sort($arr_display); //sort
        array_push($arr_messages, "Column " . $col_text . " has a dropdown list.");
        array_push($arr_messages, "Textarea populated from both preexisting dropdown list and the database.");
		}
    //values from dropdown not alphabetized
    elseif (!empty($dropdown_reduced) && ($all_values == 1))
        {
        $arr_display = $dropdown_reduced;
        array_push($arr_messages, "Column " . $col_text . " has a dropdown list.");
        array_push($arr_messages, "Textarea populated from preexisting dropdown list.");
        }
    //values from db alphabetized    
	else 
		{
		array_push($arr_messages, "Column " . $col_text . " does not have a preexisting dropdown list.");
		$arr_display = array_filter($arr_query);
		}
	}

//this area updates the drop down if set
if ($main->button(2)) //submit dropdown
	{
    $arr_txt = preg_split("/[\r\n]+/", $main->purge_chars($main->post('dropdown', $module), false));
	$arr_txt = array_filter($arr_txt); //remove empty rows
    $arr_txt = array_map(array($main, "purge_chars"), $arr_txt);
    if (in_array($col_type ,$arr_notes))
        {
        array_push($arr_messages, "Error: Cannot create a dropdown on a note column.");    
        }
    elseif (count($arr_txt) == 0)
        {
        array_push($arr_messages, "Error: Cannot populate an empty dropdown.");    
        }
    elseif (preg_grep("/[" . preg_quote($delimiter) . "]/", $arr_txt) && $multiselect)
        {
        array_push($arr_messages, "Error: Cannot populate an multiselect dropdown containing the delimiter (" . $delimiter . ").");   
        }
    else //populate dropdown
        {
        $arr_dropwork = array();
		//add empty or null value
        if ($main->post('empty_value', $module) == 1)
            {
            array_push($arr_dropwork, "");    
            }
        foreach ($arr_txt as $value)
            {
			array_push($arr_dropwork, $value);//overload
            }
        $arr_dropdowns[$row_type][$col_type] = $arr_dropwork;
        if ($multiselect == 1) $arr_dropdowns[$row_type][$col_type]['multiselect'] = true;
        $main->update_json($con, $arr_dropdowns, "bb_dropdowns");	
        array_push($arr_messages, "Column ". $col_text . " has had its dropdown list added or updated.");
        
        $row_type = $main->set('row_type', $arr_state, $default_row_type);
        $col_type = $main->set('col_type', $module, $arr_state, $default_col_type);
        $multiselect = $all_values = $empty_value = 0;
        $dropdowns = "";       
        }
 	}
	
//this area removes the dropdown
if ($main->button(3)) //remove_dropdown
	{ 
    unset($arr_dropdowns[$row_type][$col_type]);
    $main->update_json($con, $arr_dropdowns,"bb_dropdowns");
	
	array_push($arr_messages, "Column " . $col_text . " has had its dropdown list removed if it existed.");
	}
	
/* BEGIN REQUIRED FORM */
//populate row_type select combo box
echo "<p class=\"spaced bold larger\">Manage Dropdowns</p>";

echo "<div class=\"spaced\">";
$main->echo_messages($arr_messages);
echo "</div>";
    
$main->echo_form_begin();
$main->echo_module_vars();;

//row_type select tag
$params = array("class"=>"spaced","onchange"=>"bb_reload()");
$main->layout_dropdown($arr_layouts_reduced, "row_type", $row_type, $params);
echo "<br>"; //why not
$params = array("class"=>"spaced","onchange"=>"bb_reload()");
$main->column_dropdown($arr_column_reduced, "col_type", $col_type, $params);
echo "<br>";
echo "<div class=\"spaced\">";
echo "<span class = \"border rounded padded shaded\">";
echo "<label class=\"padded\">Create Multiselect Dropdown: </label>";
$main->echo_input("multiselect", 1, array('type'=>'checkbox','input_class'=>'middle holderup','checked'=>$multiselect));
echo "</span>";
echo "</div>";
//populate text area
echo "<textarea class=\"spaced\" name=\"dropdown\" cols=\"40\" rows=\"10\" wrap=\"off\">";
if (isset($arr_display))
	{
	foreach($arr_display as $value)
  		{   
  		echo $value . "\r\n";  
  		}
	}
echo "</textarea>";

echo "<div class=\"clear\"></div>";

//buttons
$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>"Populate Form");
$main->echo_button("populate_dropdown", $params);
echo "<span class = \"spaced border rounded padded shaded\">";
$checked =  ($all_values == 1) ? true : false;
$main->echo_input("all_values", 1, array('type'=>'checkbox','input_class'=>'middle holderup','checked'=>$checked));
echo "<label class=\"padded\">Populate With Existing Dropdown</label>";
echo "</span>";
echo "<br>";
$params = array("class"=>"spaced","number"=>2,"target"=>$module, "passthis"=>true, "label"=>"Create Dropdown");
$main->echo_button("create_dropdown", $params);
echo "<span class = \"spaced border rounded padded shaded\">";
$checked =  ($empty_value == 1) ? true : false;
$main->echo_input("empty_value", 1, array('type'=>'checkbox','input_class'=>'middle holderup','checked'=>$checked));
echo "<label class=\"padded\">Include Empty Value</label>";
echo "</span>";
echo "<br>";
$params = array("class"=>"spaced","number"=>3,"target"=>$module, "passthis"=>true, "label"=>"Remove Dropdown");
$main->echo_button("populate_dropdown", $params);
echo "<br>";

$main->echo_form_end();
/* END FORM */
?>
