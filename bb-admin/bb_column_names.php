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
//reload for change of row_type
function bb_reload()
	{
	//standard submit
    bb_submit_form();
	}
</script>
<?php

/* DEFINITIONS */

$arr_fields = array('row'=>array('name'=>'Row','alternative'=>true),
                    'length'=>array('name'=>'Length','alternative'=>true),
                    'order'=>array('name'=>'Order','alternative'=>true),
                    'type'=>array('name'=>'Type'),
                    'display'=>array('name'=>'Display','alternative'=>true),
                    'required'=>array('name'=>'Required'),
                    'secure'=>array('name'=>'Secure'),
                    'search'=>array('name'=>'Search'),
                    'relate'=>array('name'=>'Relate'));

$arr_properties = array('primary'=>array('name'=>'Primary'),
                        'count'=>array('name'=>'Count'),
                        'unique'=>array('name'=>'Unique'));

/* END DEFINITIONS */

/* INITIALIZE */

set_time_limit(0);

//find default row_type, $arr_layouts must have one layout set
$arr_layouts_json = $main->get_json($con, "bb_layout_names");
$arr_columns_json = $main->get_json($con, "bb_column_names");
$arr_header = $main->get_json($con, "bb_interface_enable");

$arr_layouts = $main->filter_keys($arr_layouts_json);
$default_row_type = $main->get_default_layout($arr_layouts);

//get validation and security info 
$arr_validation = $arr_header['validation'];
$arr_column_security = $arr_header['column_security']['value'];

$arr_relate = array(41,42,43,44,45,46);
$arr_file = array(47);
$arr_reserved = array(48);
$arr_notes = array(49,50);
$arr_messages = array();

//deal with constants
$maxinput = $main->get_constant('BB_STANDARD_LENGTH', 255);

//get $POST variable
$POST = $main->retrieve($con);

//start of code, get row_type
$row_type = $main->post('row_type', $module, $default_row_type);
//get the defintion   
$definition = $main->post('definition', $module, 'bb_brimbox');   
//after posted row type
$layout_name = $arr_layouts[$row_type]['plural'];

$core = ($definition == "bb_brimbox") ? true : false;

// core, props and alternative
//get numeric columns by column from database, columns are not necessarily set
$arr_core = $main->filter_keys($main->init($arr_columns_json[$row_type], array()));
//get numeric alternative columns  by column from database, not necessarily set
$arr_alternative = $main->filter_keys($main->init($arr_columns_json[$row_type]['alternative'][$definition], array()));
//save all the alternatives for updating the JSON
$arr_alternative_full = $main->init($arr_columns_json[$row_type]['alternative'], array());
//properties or string values
$arr_props = $main->properties($con, $row_type);

/* END INITIALIZE */

/* REFRESH */

if ($main->button(1))
	{
    $message = "Columns have been refreshed.";
    }
    
/* END REFRESH */

/* SUBMIT COLUMN DATA */

if ($main->button(2))
    {
        
    /* COLUMN DATA POSTBACK */
    
    $arr_core_work = $arr_alternative_work = array();
    for ($i = 1; $i<=50; $i++)
        {
        $name = $main->purge_chars($main->post('name_'. $i, $module, ""), true, true);
         if (!$main->blank($name)) //readonly input for not core
            {
            if ($core)
                $arr_core_work[$i]['name'] = $name;
            foreach ($arr_fields as $key => $value)
                {
                //array set for particular purposes
                $default = $main->init($arr_core[$i][$key], "");
                $arr_core_work[$i][$key] = $main->post($key . '_' . $i, $module, $default);
                if (isset($value['alternative']) && $value['alternative'])
                    {
                    $default = $main->init($arr_alternative[$i][$key], "");
                    $arr_alternative_work[$i][$key] = $main->post($key . '_' . $i, $module, $default);
                    }
                }
            }
        }
        
     /* END COLUMN DATA POSTBACK */
        
    /* CHECK FOR ERRORS */
    
    $arr_check = $core ? $arr_core_work : $arr_alternative_work;
    
    $i = 1;
    $error = false; //true means there was errors
    $error_relate_to_itself = false; //no realted record to itself
    $arr_names = array(); //check for unique names
    $arr_rows = array(); //check for ascending rows
    $arr_order = array(); //check for strict ascending order
    $arr_related = array(); //check related layouts for integrity
    $arr_errors = array(0 => "Error: Row settings contain a blank value when column name is set.",
                    1 => "Error: Column order contains a blank value when column name is set.",
                    2 => "Error: Row values must start at 1 and be strictly ascending when column name is set, records can have multiple columns per row.",
                    3 => "Error: Column order must start at 1, be unique, and be strictly ascending when column name is set.",
                    4 => "Error: Column names must be unique.",
                    5 => "Error: Can only relate a table to a table once.",
                    6 => "Error: Cannot relate a table to itself.");
    
    for ($i = 1; $i <= 50; $i++)
        {
        //check rows and order for integrity
        //both core and alternative
        if (isset($arr_check[$i]))
            {
            //number of populated columns
            //check unique names            
            //check rows
            if ($arr_check[$i]['row'] == 0) 
                { 
                array_push($arr_messages, $arr_errors[0]);
                }
            else
                {
                array_push($arr_rows, (int)$arr_check[$i]['row']);
                }
            //check unique order
            if ($arr_check[$i]['order'] == 0) 
                { 
                array_push($arr_messages, $arr_errors[1]);
                }
            else
                {
                array_push($arr_order, (int)$arr_check[$i]['order']);
                }
                
            //check relate and distinct names
            if ($core)
                {
                //to check names
                array_push($arr_names, $arr_check[$i]['name']);
                //initialize
                if (in_array($i, $arr_relate))
                    {
                    //check if record has been related twice
                    if ($arr_check[$i]['relate'] > 0)
                        {
                        array_push($arr_related, (int)$arr_check[$i]['relate']);    
                        }
                    //check that not related to self
                    if ($arr_check[$i]['relate'] == $row_type)
                        {
                        $error_relate_to_itself = true;
                        }
                    }                
                }
            } //end col_value if
        }//end for loop		
      
    //check on core and alternative         
    //rows strictly ascending starting at 1
    $arr_rows = array_unique($arr_rows);
    asort($arr_rows);
    $arr_rows = array_merge($arr_rows);
    if (count($arr_rows) > 0) //must have at least one value
        {
        if (($arr_rows[0] <> 1) || ($arr_rows[count($arr_rows) - 1] <> count($arr_rows)))	
            {
            array_push($arr_messages, $arr_errors[2]);
            }
        }
    
    //strictly ascending and unique starting at 1
    $arr_temp = array_unique($arr_order);
    $cnt_order = count($arr_temp); //holds count of unique
    asort($arr_order);
    $arr_order = array_merge($arr_order);
    if (count($arr_order) > 0) //must have at least one value
        {
        if (($arr_order[0] <> 1) || ($arr_order[count($arr_order) - 1] <> count($arr_order)) || (count($arr_order) <> $cnt_order))	
            {
            array_push($arr_messages, $arr_errors[3]);
            }
        }
    
    //check on core only
    if ($core)
        {
        //column names must be unique
        $cnt_names = count($arr_names);
        //case insensitive for column names
        $arr_names = $main->array_iunique($arr_names);
        $cnt_unique_names = count($arr_names);
        if ($cnt_names <> $cnt_unique_names)
            {
            array_push($arr_messages, $arr_errors[4]);
            } 
            
        //can only relate a layout once
        if (count($arr_related) <> count($main->array_iunique($arr_related)))
            {
            array_push($arr_messages, $arr_errors[5]);
            }
            
        //cannot relate a table to itself
        if ($error_relate_to_itself)
            {
            array_push($arr_messages, $arr_errors[6]);    
            }
        }
    
    //cleanup $arr_error
    $arr_messages = array_unique($arr_messages);
    asort($arr_messages);
    
    /* END CHECK FOR ERRORS */
    
    /* READY VALUES */
    
    //display working values if error
    if ($core) $arr_core = $arr_core_work;
    $arr_alternative = $arr_alternative_work;
    
    $arr_properties_work = array();
    foreach ($arr_properties as $key => $value)
        {
        switch ($key)
            {
            case "primary":
            //row dropdown
                $arr_properties_work['primary'] = key($arr_core_work);
                foreach ($arr_core_work as $key => $value)
                    {
                    if ($value['order'] == 1)
                        {
                        $arr_properties_work['primary'] = $key;
                        break;
                        }
                    }
                break;
            case "count":
                $arr_properties_work['count'] = count($arr_core_work);
                break;
            case "unique":
                if (isset($arr_props['unique']))
                    {
                    $arr_properties_work['unique'] = $arr_props['unique'];
                    }
                break;
            }
        }
    
    /* END READY VALUES */
    
    /* UPDATE DATABASE */
    
    if (!$main->has_error_messages($arr_messages))
        {
        //commit JSON to database
        //fully rewrites array
        $arr_alternative_full[$definition] = $arr_alternative_work;
        if ($core)
            {          
            $arr_columns_json[$row_type] = $arr_core_work;
            $arr_columns_json[$row_type]['fields'] = $arr_fields;
            $arr_columns_json[$row_type]['properties'] = $arr_properties;
            $arr_columns_json[$row_type]['alternative'] = $arr_alternative_full;
            $arr_columns_json[$row_type] = $arr_columns_json[$row_type]  + $arr_properties_work;
            }
        else
            {
            $arr_columns_json[$row_type]['alternative'] = $arr_alternative_full;    
            }
        //do non core for everything including core
        $main->update_json($con, $arr_columns_json, "bb_column_names"); //submit xml
        //update full text indexes for that column $row_type > 0;
        $main->build_indexes($con, $row_type);
        array_push($arr_messages, "Columns have been updated and search index has been rebuilt for this layout.");               
        }
        
    /* END UPDATE DATABASE */
    }
    
/* END SUBMIT COLUMN DATA */
 
/* REBUILD INDEXES */

//rebuild all indexes, row_type = 0 for full text search update
if ($main->button(3))
    {
    //full text update
    $main->build_indexes($con, 0);
    //rebuild indexes
    array_push($arr_messages,"All data table indexes have been rebuilt.");
    }
    
/* END REBUILD INDEXES */

/* BEGIN REQUIRED FORM - HTML OUTPUT */	
//module header
echo "<p class=\"spaced bold larger\">Column Names</p>";

$main->echo_form_begin();
$main->echo_module_vars();

echo "<div class=\"spaced\">";
$main->echo_messages($arr_messages);
echo "</div>";

/* DROPDOWN - ALTERNATIVE DEFINTIONS */

//row_type select tag
$params = array("class"=>"spaced","onchange"=>"bb_reload()");
$main->layout_dropdown($arr_layouts, "row_type", $row_type, $params);

//get alternative definitions 
foreach ($array_header as $key => $value)
    {
    //$arr_header is reduced array_header from JSON
    $arr_definition[$key] = $array_header[$key]['interface_name'];
    }
$arr_definition['bb_test'] = "Test";

$params = array("class"=>"spaced", "onchange"=>"bb_reload()", "usekey"=>true);
$main->array_to_select($arr_definition, "definition", $definition, $params);

/* END DROPDOWN - ALTERNATIVE DEFINTIONS */

/* READY DATA FOR OUTPUT */ 

//choose core or alternative
if ($core)
    {
    $arr_columns = $arr_core;
    $arr_fields_work = $arr_fields;
    }
else
    {
    $arr_columns = $arr_alternative;
    foreach ($arr_fields as $key => $value)
        {
        if (isset($value['alternative']) && $value['alternative'])
            {
            $arr_fields_work[$key] = $value;    
            }
        }
    }
    
/* END READY DATA FOR OUTPUT */

/* TABLE OUTPUT */
    
//display table head
echo "<div class=\"table spaced border\">";
echo "<div class=\"row\">";
echo "<div class=\"padded cell shaded middle\">Column</div>";
echo "<div class=\"padded cell shaded middle\">Name</div>";
foreach ($arr_fields_work as $key => $value)
    {
    echo "<div class=\"padded cell shaded middle\">" .  $value['name']  . "</div>";
    }
echo "</div>";

for ($m = 1; $m <= 50; $m++)
    {
    //columns by row 
    echo "<div class=\"row\">";
    //layout and column number
    echo "<div class = \"padded cell middle\">" . htmlentities($layout_name) . " " . str_pad((string)$m, 2, "0", STR_PAD_LEFT) . "</div>";
    
    //name, alternative is readonly, always use core array
    $readonly = $core ? "" : "readonly";
    $formvalue = $main->init($arr_core[$m]['name'], "");
    echo "<div class = \"cell middle\"><input name=\"name_" . $m . "\" class = \"spaced\" type=\"text\" value=\"" . htmlentities($formvalue) . "\" size=\"25\" maxlength=\"" . $maxinput . "\" " . $readonly . "/></div>"; 	    

    foreach ($arr_fields_work as $key => $value)
        {
        switch ($key)
            {
            case "row":
            //row dropdown
                $formvalue = $main->init($arr_columns[$m]['row'], 0);
                echo "<div class = \"cell middle\"><select name=\"row_" . $m . "\" class = \"spaced\"/>";
                echo "<option value = \"0\"></option>";
                    for ($i = 1; $i <= 50; $i++)
                        {
                        $selected = ($i == $formvalue) ? "selected" : "";
                        echo "<option value=\"" . $i . "\" " . $selected . ">" . $i . "&nbsp;</option>";
                        }
                echo "</select></div>";
                break;
            case "length":
                $formvalue = $main->init($arr_columns[$m]['length'], "");
                $arr_column_css_class = array("short"=>"Short","medium"=>"Medium","long"=>"Long", "note"=>"Note");
                echo "<div class = \"cell middle\"><select name = \"length_" . $m . "\" class = \"spaced\">";
                foreach ($arr_column_css_class as $key2 => $value2)
                    {
                    $selected = ($key2 == $formvalue) ? "selected" : "";
                    echo "<option value=\"short\" " . $selected . ">" . htmlentities($value2) . "</option>";
                    }
                echo "</select></div>";
                break;
            case "order":
                //order dropdown
                $formvalue = $main->init($arr_columns[$m]['order'], 0);
                echo "<div class = \"cell middle\"><select name = \"order_" . $m. "\" class = \"spaced\">";	
                echo "<option value = \"0\"></option>";
                        for ($i = 1; $i <= 50; $i++)
                            {
                            $selected = ($i == $formvalue) ? "selected" : "";
                            echo "<option value=\"" . $i . "\" " . $selected . ">" . $i . "&nbsp;</option>";
                            }
                echo "</select></div>";
                break;
            case "type":
                if (in_array($m, $arr_notes))
                    {
                    echo "<div class = \"padded cell middle center colored\">Note</div>";
                    }
                elseif (in_array($m, $arr_reserved))
                    {
                    echo "<div class = \"padded cell middle center colored\">Reserved</div>";
                    }
                elseif (in_array($m, $arr_file))
                    {
                    echo "<div class = \"padded cell middle center colored\">File</div>";
                    }
                else
                    {
                    $formvalue = $main->init($arr_columns[$m]['type'], "");
                    echo "<div class = \"cell middle\"><select name = \"type_" . $m. "\" class=\"spaced\">";
                    //global $array_validation
                    foreach ($arr_validation as $key2 => $value2)
                        {
                        $selected = ($key2 == $formvalue) ? "selected" : "";
                        echo "<option value=\"" . $key2 . "\" " . $selected . ">" . $value2['name'] . "</option>";
                        }
                    echo "</select></div>";
                    }
                break;
            case "display":
                    $formvalue = $main->init($arr_columns[$m]['display'], 0);
                    echo "<div class = \"cell middle\">";
                    echo "<select name = \"display_" . $m. "\" class = \"spaced\">";
                    $arr_display = array(0=>"",1=>"Readonly",2=>"Hidden");
                    foreach ($arr_display as $key2 => $value2)
                        {
                        $selected = ($key2 == $formvalue) ? "selected" : "";
                        echo "<option value = \"" . $key2 . "\" " . $selected . ">" . $value2 . "&nbsp;</option>";
                        }
                    echo "</select>";
                    echo "</div>";
                break;
            case "required":
                //required checkbox
                $formvalue = $main->init($arr_columns[$m]['required'], 0);
                echo "<div class = \"padded cell center middle\">";
                $checked = ($formvalue == 1) ? true : false;
                $main->echo_input("required_" . $m, 1, array('type'=>'checkbox','input_class'=>'holderdown','checked'=>$checked));
                echo "</div>";
                break;
            case "secure":
                //secure checkbox
                $formvalue = $main->init($arr_columns[$m]['secure'], 0);
                if (empty($arr_column_security))
                    {
                    echo "<div class = \"padded cell center middle\">";
                    $checked = ($formvalue == 1) ? true : false;
                    $main->echo_input("secure_" . $m, 1, array('type'=>'checkbox','input_class'=>'holderdown','checked'=>$checked));
                    echo "</div>";
                    }
                else
                    {
                    echo "<div class = \"cell middle\"><select name=\"secure_" . $m . "\"class = \"spaced\">";
                    foreach ($arr_column_security as $key2 => $value2)
                        {
                        $selected = ($key2 == $formvalue) ? "selected" : "";
                        echo "<option value = \"" . $key2 . "\" " . $selected . ">" . htmlentities($value2) . "&nbsp;</option>";
                        }
                    echo "</select></div>";
                    }
                break;
            case "search":
                //search checkbox
                $formvalue = $main->init($arr_columns[$m]['search'], 0);
                echo "<div class = \"padded cell center middle\">";
                $checked = ($formvalue == 1) ? true : false;
                $main->echo_input("search_" . $m, 1, array('type'=>'checkbox','input_class'=>'holderdown','checked'=>$checked));
                echo "</div>";
                break;
            case "relate":
                if (in_array($m, $arr_relate))
                    {
                    $formvalue = $main->init($arr_columns[$m]['relate'], 0);
                    echo "<div class = \"cell middle\"><select name=\"relate_" . $m . "\"class = \"spaced\">";
                    echo "<option value = \"0\"></option>";
                    foreach ($arr_layouts as $key2 => $value2)
                        {
                        $selected = ($key2 == $formvalue) ? "selected" : "";
                        if ($value['relate'])
                            {
                            echo "<option value = \"" . $key2 . "\" " . $selected . ">" . htmlentities(chr($key2 + 64) . $key2) . "&nbsp;</option>";
                            }
                        }
                    echo "</select></div>";    
                    }
                else
                    {
                    echo "<div class = \"cell middle\"></div>";   
                    }
                break;
            } //switch
        }//foreach
    echo "</div>"; //row
    } //for loop
echo "</div>"; //table

/* END TABLE OUTPUT */

$params = array("class"=>"spaced","number"=>2, "passthis"=>true, "label"=>"Submit Columns");
$main->echo_button("submit_columnnames", $params);
$params = array("class"=>"spaced","number"=>1, "passthis"=>true, "label"=>"Refresh Columns");
$main->echo_button("refresh_columnnames", $params);
$params = array("class"=>"spaced","number"=>3, "passthis"=>true, "label"=>"Rebuild Indexes");
$main->echo_button("rebuild_indexes", $params);
$main->echo_form_end();

/* END FORM AND HTML OUTPUT */

?>

