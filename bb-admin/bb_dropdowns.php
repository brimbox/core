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
$main->check_permission(4);
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
$arr_notes = array("c49","c50");

/* GET STATE */
$main->retrieve($con, $array_state, $userrole);

//This area creates the form for choosing the lists
$xml_layouts = $main->get_xml($con, "bb_layout_names");
$default_row_type = $main->get_default_row_type($xml_layouts);
$row_type = $main->post('row_type', $module, $default_row_type);

$xml_columns = $main->get_xml($con, "bb_column_names");
$layout = $main->pad("l", $row_type);
$xml_column = $xml_columns->$layout;

//this is catch variable from the javascript to initialize
$default_col_type = (count($xml_column->children()) > 0) ? $main->rpad($xml_column->children()->getName()) : 1;
$col_type = $main->post('col_type', $module, $default_col_type);
if ($main->post('col_catch', $module) == 1)
	{
	$col_type = $default_col_type;
	}

//null value flag
$null_value = $main->post('null_value', $module, 0);
//alphabetize flag
$alpha = $main->post('alpha', $module, 0);
    
$xml_columns = $main->get_xml($con, "bb_column_names");
$layout = $main->pad("l", $row_type, 2);
$xml_column = $xml_columns->$layout;

$column = $main->pad("c", $col_type, 2);   	
$col_text = (string)$xml_column->$column;

//this area populates the textarea
if ($main->post('bb_button', $module) == 1) //populate_dropdown
	{
    //preexisting dropdown xml
    $xml_dropdowns = $main->get_xml($con, "bb_dropdowns");   
    $xml_dropdown = $xml_dropdowns->$layout;
    
	//get all drop downs for row_type
	$arr_xml = array();
    if (isset($xml_dropdown->$column))
        {
        foreach($xml_dropdown->$column->children() as $child)
            {
            array_push($arr_xml,(string)$child);
            if ((string)$child == "")
                {
                $null_value = 1;    
                }
            }
        }
 
    //get all values in database for the selected column (and row type) 	
	$query = "SELECT distinct " .  $column . " FROM data_table WHERE row_type = " . $row_type . " AND archive = 0 ORDER BY " . $column . " LIMIT 2000;";
	$result = $main->query($con, $query);
	
	$arr_query = pg_fetch_all_columns($result, 0);

    //if array is set for the column in question merge and unique it, else just get unique from database
	if (!empty($arr_xml))
		{
		array_push($arr_message, "Column " . $col_text . " has a dropdown list.");
		$arr_pop = array_merge($arr_query, $arr_xml);
		$arr_pop = array_unique($arr_pop);
        $arr_pop = array_filter($arr_pop); //remove empty rows
		}
	else 
		{
		array_push($arr_message, "Column " . $col_text . " does not have a preexisting dropdown list.");
		$arr_pop = array_filter($arr_query);
		}
	}

//this area updates the drop down if set
if ($main->post('bb_button', $module) == 2) //submit dropdown
	{
    $arr_txt = preg_split("/[\r\n]+/",  $main->custom_trim_string($main->post('droplist', $module), 65536, false, true));
	$arr_txt = array_filter($arr_txt); //remove empty rows
    if (in_array($column,$arr_notes))
        {
        array_push($arr_message, "Error: Cannot create a dropdown on a note column.");    
        }
    elseif (count($arr_txt) == 0)
        {
        array_push($arr_message, "Error: Cannot populate an empty dropdown.");    
        }    
    else //populate dropdown
        {
        $xml_dropdowns = $main->get_xml($con, "bb_dropdowns");
        //add a layout if necessary
        //layouts may or may not be empty (or exist)
        if (!isset($xml_dropdowns->$layout))
            {
            $xml_dropdowns->$layout = "";   
            }
        $xml_dropdown = $xml_dropdowns->$layout;
        //will remove all the column instances
        unset($xml_dropdown->$column);
		$xml_dropdown->$column = "";
        $child = $xml_dropdown->$column;
       
		//* NOTE THIS WIERD SYNTAX OVERLOADS MULTIPLE NODES WITH THE SAME NODE NAME */
		//* $child->addChild("value")->{0} = $value */
		//* THIS WORKS WITH SPECIAL CHARACTERS *//
		
		//add empty or null value
        if ($main->post('null_value', $module) == 1)
            {
            $child->addChild("value")->{0} = "";    
            }
        //add values
		if ($alpha == 1)
			{
			sort($arr_txt);
			}
        foreach ($arr_txt as $value)
            {
			$child->addChild("value")->{0} = $value; //overload
            }
    
        $main->update_xml($con, $xml_dropdowns, "bb_dropdowns");	
        array_push($arr_message, "Column ". $col_text . " has had its dropdown list added or updated.");
        }
 	}
	
//this area removes the dropdown
if ($main->post('bb_button', $module) == 3) //remove_dropdown
	{ 
	$xml_dropdowns = $main->get_xml($con, "bb_dropdowns");
    unset($xml_dropdowns->$layout->$column);
    $main->update_xml($con, $xml_dropdowns,"bb_dropdowns");
	
	array_push($arr_message, "Column " . $col_text . " has had its dropdown list removed if it existed.");
	}
	
/* BEGIN REQUIRED FORM */
//populate row_type select combo box
echo "<p class=\"spaced bold larger\">Manage Dropdowns</p>";

echo "<div class=\"spaced\">";
$main->echo_messages($arr_message);
echo "</div>";
    
$main->echo_form_begin();
$main->echo_module_vars($module);

//row_type select tag
$params = array("class"=>"spaced","onchange"=>"reload_on_layout()");
$main->layout_dropdown($xml_layouts, "row_type", $row_type, $params);
echo "<br>"; //why not
$params = array("class"=>"spaced");
$main->column_dropdown($xml_column, "col_type", $col_type, $params);
echo "<br>";
echo "<input name=\"col_catch\" type=\"hidden\" value\"0\">";
	
//populate text area
echo "<textarea class=\"spaced\" name=\"droplist\" cols=\"40\" rows=\"10\" wrap=\"off\">";
if (isset($arr_pop))
	{
	foreach($arr_pop as $value)
  		{   
  		echo $value . "\r\n";  
  		}
	}
echo "</textarea>";

echo "<div class=\"clear\"></div>";

//buttons
$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>"Populate Form");
$main->echo_button("populate_dropdown", $params);
echo "<br>";
$params = array("class"=>"spaced","number"=>2,"target"=>$module, "passthis"=>true, "label"=>"Create Dropdown");
$main->echo_button("create_dropdown", $params);
echo "<span class = \"spaced border rounded padded shaded\">";
echo "<input type=\"checkbox\" class=\"middle padded\" name=\"null_value\" value=\"1\" " . ($null_value == 1 ? "checked" : "") . "/>";
echo "<label class=\"padded\">Include Empty Value</label>";
echo "</span>";
echo "<span class = \"spaced border rounded padded shaded\">";
echo "<input type=\"checkbox\" class=\"middle padded\" name=\"alpha\" value=\"1\" " . ($alpha == 1 ? "checked" : "") . "/>";
echo "<label class=\"padded\">Alphabetize</label>";
echo "</span><br>";
$params = array("class"=>"spaced","number"=>3,"target"=>$module, "passthis"=>true, "label"=>"Remove Dropdown");
$main->echo_button("populate_dropdown", $params);
echo "<br>";

$main->echo_state($array_state);
$main->echo_form_end();
/* END FORM */
?>
