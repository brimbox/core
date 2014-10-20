<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
Copyright (C) 2012 - 2013  Kermit Will Richardson, Brimbox LLC

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
function reload_on_layout()
    {
    //standard submit
	var frmobj = document.forms['bb_form'];
	//1 value will force program to find default value in PHP below	
	frmobj.col_catch.value = 1;
	
	bb_submit_form(0);
	}
</script>
<?php
/* INITIALIZE */
$arr_message = array();
$arr_notes = array("49","50");

/* GET STATE */
$main->retrieve($con, $array_state);

//This area creates the form for choosing the lists
$arr_layouts = $main->get_json($con, "bb_layout_names");
$default_row_type = $main->get_default_layout($arr_layouts);
$row_type = $main->post('row_type', $module, $default_row_type);

$arr_columns = $main->get_json($con, "bb_column_names");
$arr_column = $arr_columns[$row_type];

//this is catch variable from the javascript to initialize
$default_col_type = $main->get_default_column($arr_column);
$col_type = $main->post('col_type', $module, $default_col_type);
if ($main->post('col_catch', $module) == 1)
	{
	$col_type = $default_col_type;
	}

//null value flag
$null_value = $main->post('null_value', $module, 0);
$all_values = $main->post('all_values', $module, 0);      	
$col_text = $arr_column[$col_type]['name'];

//this area populates the textarea
if ($main->button(1)) //populate_dropdown
	{
    //preexisting dropdown xml
    $arr_dropdowns = $main->get_json($con, "bb_dropdowns");   
    $arr_dropdown = isset($arr_dropdowns[$row_type]) ? $arr_dropdowns[$row_type] : array();
    $arr_droplist = isset($arr_dropdown[$col_type]) ? $arr_dropdown[$col_type] : array();

    //get all values in database for the selected column (and row type)
    $column = $main->pad("c",$col_type);
	$query = "SELECT distinct " .  $column . " FROM data_table WHERE row_type = " . $row_type . " AND archive = 0 ORDER BY " . $column . " LIMIT 2000;";
	$result = $main->query($con, $query);
	
	$arr_query = pg_fetch_all_columns($result, 0);

    //values from db and dropdown alphabetized
	if (!empty($arr_droplist) && ($all_values == 0))
		{
		$arr_populate = array_merge($arr_query, $arr_droplist);
		$arr_populate = array_unique($arr_populate);
        $arr_populate = array_filter($arr_populate); //remove empty rows
        array_push($arr_message, "Column " . $col_text . " has a dropdown list.");
        array_push($arr_message, "Textarea populated from both preexisting dropdown list and the database.");
		}
    //values from dropdown not alphabetized
    elseif (!empty($arr_droplist) && ($all_values == 1))
        {
        $arr_populate = $arr_droplist;
        array_push($arr_message, "Column " . $col_text . " has a dropdown list.");
        array_push($arr_message, "Textarea populated from preexisting dropdown list.");
        }
    //values from db alphabetized    
	else 
		{
		array_push($arr_message, "Column " . $col_text . " does not have a preexisting dropdown list.");
		$arr_populate = array_filter($arr_query);
		}
	}

//this area updates the drop down if set
if ($main->button(2)) //submit dropdown
	{
    $arr_txt = preg_split("/[\r\n]+/",  $main->custom_trim_string($main->post('droplist', $module), 65536, false, true));
	$arr_txt = array_filter($arr_txt); //remove empty rows
    if (in_array($col_type ,$arr_notes))
        {
        array_push($arr_message, "Error: Cannot create a dropdown on a note column.");    
        }
    elseif (count($arr_txt) == 0)
        {
        array_push($arr_message, "Error: Cannot populate an empty dropdown.");    
        }    
    else //populate dropdown
        {
        $arr_dropwork = array();
		//add empty or null value
        if ($main->post('null_value', $module) == 1)
            {
            array_push($arr_dropwork, "");    
            }
        foreach ($arr_txt as $value)
            {
			array_push($arr_dropwork, $value);//overload
            }
        $arr_dropdowns[$row_type][$col_type] = $arr_dropwork;
        $main->update_json($con, $arr_dropdowns, "bb_dropdowns");	
        array_push($arr_message, "Column ". $col_text . " has had its dropdown list added or updated.");
        }
 	}
	
//this area removes the dropdown
if ($main->button(3)) //remove_dropdown
	{ 
	$arr_dropdowns = $main->get_json($con, "bb_dropdowns");
    unset($arr_dropdowns[$row_type][$col_type]);
    $main->update_json($con, $arr_dropdowns,"bb_dropdowns");
	
	array_push($arr_message, "Column " . $col_text . " has had its dropdown list removed if it existed.");
	}
	
/* BEGIN REQUIRED FORM */
//populate row_type select combo box
echo "<p class=\"spaced bold larger\">Manage Dropdowns</p>";

echo "<div class=\"spaced\">";
$main->echo_messages($arr_message);
echo "</div>";
    
$main->echo_form_begin();
$main->echo_module_vars();;

//row_type select tag
$params = array("class"=>"spaced","onchange"=>"reload_on_layout()");
$main->layout_dropdown($arr_layouts, "row_type", $row_type, $params);
echo "<br>"; //why not
$params = array("class"=>"spaced");
$main->column_dropdown($arr_column, "col_type", $col_type, $params);
echo "<br>";
echo "<input name=\"col_catch\" type=\"hidden\" value\"0\">";
	
//populate text area
echo "<textarea class=\"spaced\" name=\"droplist\" cols=\"40\" rows=\"10\" wrap=\"off\">";
if (isset($arr_populate))
	{
	foreach($arr_populate as $value)
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
$main->echo_input("all_values", 1, array('type'=>'checkbox','input_class'=>'middle padded','checked'=>$checked));
echo "<label class=\"padded\">Populate With Existing Dropdown</label>";
echo "</span>";
echo "<br>";
$params = array("class"=>"spaced","number"=>2,"target"=>$module, "passthis"=>true, "label"=>"Create Dropdown");
$main->echo_button("create_dropdown", $params);
echo "<span class = \"spaced border rounded padded shaded\">";
$checked =  ($null_value == 1) ? true : false;
$main->echo_input("null_value", 1, array('type'=>'checkbox','input_class'=>'middle padded','checked'=>$checked));
echo "<label class=\"padded\">Include Empty Value</label>";
echo "</span>";
echo "<br>";
$params = array("class"=>"spaced","number"=>3,"target"=>$module, "passthis"=>true, "label"=>"Remove Dropdown");
$main->echo_button("populate_dropdown", $params);
echo "<br>";

$main->echo_state($array_state);
$main->echo_form_end();
/* END FORM */
?>
