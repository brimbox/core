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
$main->check_permission(5);
?>
<script type="text/javascript">
function reload_on_layout()
    {
    bb_submit_form();    
    }
</script>
<?php
$main->check_permission(2);

/* PRESERVE STATE */
$main->retrieve($con, $array_state, $userrole);
$arr_notes = array("c49","c50");

//start code here
$arr_message = array();

//row_type
$xml_layouts = $main->get_xml($con, "bb_layout_names");
$default_row_type = $main->get_default_row_type($xml_layouts);
$row_type = $main->post('row_type', $module, $default_row_type);
	
//layout
$xml_columns = $main->get_xml($con, "bb_column_names");
$layout = $main->pad("l",$row_type);
$xml_column = $xml_columns->$layout;
$xml_layout = $xml_layouts->$layout;

if (isset($xml_column['key']))
    {
    $column = $xml_column['key'];   
    }
else
    {
    $column = (string)$xml_column->children()->getName();
    }

//check_column    
if (($main->post('bb_button', $module) == 1) || ($main->post('bb_button', $module)) == 2) 
    {
    $col_type = $main->post('col_type', $module);
	$column = $main->pad("c", $col_type);
	//key already set
    if (isset($xml_column['key']))
        {
        $unique_key = $xml_column['key'];
        array_push($arr_message, "Error: Unique Key is already set on layout " . (string)$xml_layout['plural'] . ".<Column " . (string)$xml_column->$unique_key . " has a unique key on it.");    
        }
	//no key
    else
        {
		//check for duplicates
        $query = "SELECT 1 FROM (SELECT " . $column . ", count(" . $column . ") FROM data_table WHERE row_type = " . $row_type . " GROUP BY " . $column . " HAVING count(" . $column . ") > 1) T1";              
        $result = $main->query($con, $query);
        if (pg_num_rows($result) > 0)
            {    
            array_push($arr_message, "Error: Column " . (string)$xml_column->$column . " contains duplicate values. Unique key cannot be created.");
            }
        
		//check for empty keys
        $query = "SELECT 1 FROM data_table WHERE " . $column . " = '' AND row_type = " . $row_type . ";";
        $result = $main->query($con, $query);        
		if (pg_num_rows($result) > 0)
            {
            array_push($arr_message, "Error: Column " . (string)$xml_column->$column . " contains empty values. Unique key cannot be created.");
            }
        
		//check if note column 
        if (in_array($column,$arr_notes))
            {
            array_push($arr_message, "Error: Unique Key cannot be created on note column. Unique key cannot be created.");
            }
        }
    }
//if no message, inform administartor or add key
if (($main->post('bb_button', $module) == 1) && !empty($column) && empty($arr_message)) //check_column
    {
    array_push($arr_message, "Unique key can be created on layout " . (string)$xml_layout['plural'] . " column " . (string)$xml_column->$column . ".");       
    }	
elseif (($main->post('bb_button', $module) == 2) && empty($arr_message)) //add_key
	{     
    unset($xml_column['key']);
    $xml_column->addAttribute('key', $column);
    
    //Update xml row explicitly, check for valid key
    $query = "UPDATE xml_table SET xmldata = '" . pg_escape_string($xml_columns->asXML()) . "' WHERE lookup = 'bb_column_names' " .
             "AND NOT EXISTS (SELECT 1 FROM (SELECT " . $column . ", count(" . $column . ") FROM data_table WHERE row_type = " . $row_type . " GROUP BY " . $column . " HAVING count(" . $column . ") > 1) T1)" .
             "AND NOT EXISTS (SELECT 1 FROM data_table WHERE " . $column . " = '' AND row_type = " . $row_type . ");";
    //echo "<p>" . $query . "</p>";
    $result = $main->query($con, $query);
    
    if (pg_affected_rows($result) == 1) //key updated or set
        {
        array_push($arr_message, "Unique Key has been created on layout " . $xml_layout['plural'] . ", column " . (string)$xml_column->$column . ".");     
        }
    else //something changed
        {
        array_push($arr_message, "Unique Key has not been created on layout " . $xml_layout['plural'] . ", column " . (string)$xml_column->$column . ". Underlying data change.");     
        }
	}
	
if ($main->post('bb_button', $module) == 3) //remove_key
    {
	if (isset($xml_column['key']))
		{
		unset($xml_column['key']);
		$main->update_xml($con, $xml_columns, "bb_column_names");
		array_push($arr_message, "Unique Key has been removed for this layout type " . (string)$xml_layout['plural'] . ".");  
		}
	else
		{
		array_push($arr_message, "There is currently no key on layout type " . (string)$xml_layout['plural'] . ".");			
		}
	}
/* BEGIN REQUIRED FORM */
echo "<p class=\"spaced bold larger\">Create Key</p>";

echo "<div class=\"padded\">";
$main->echo_messages($arr_message);
echo "</div>";


$main->echo_form_begin();
$main->echo_module_vars($module);
echo "<div class=\"spaced borderleft bordertop floatleft\">";
$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>"Check Layout");
$main->echo_button("check_column", $params);
echo "<br>";
$params = array("class"=>"spaced","onchange"=>"reload_on_layout()");
$main->layout_dropdown($xml_layouts, "row_type", $row_type, $params);
$params = array("class"=>"spaced");
$main->column_dropdown($xml_column, "col_type", $column, $params);
echo "<br>";
$params = array("class"=>"spaced","number"=>2,"target"=>$module, "passthis"=>true, "label"=>"Create Key");
$main->echo_button("add_key", $params);
$params = array("class"=>"spaced","number"=>3,"target"=>$module, "passthis"=>true, "label"=>"Remove Key");
$main->echo_button("remove_key", $params);
echo "<br>&nbsp;<br>&nbsp;<br></div>";

$main->echo_state($array_state);
$main->echo_form_end();

/* END FORM */
?>

