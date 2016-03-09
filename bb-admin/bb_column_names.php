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

/* RETRIEVE POST */
$POST = $main->retrieve($con);

//start of code, get row_type
$row_type = $main->post('row_type', $module, $default_row_type);
//get the defintion   
$definition = $main->post('definition', $module, 'bb_brimbox');   
//after posted row type
$layout_name = $arr_layouts[$row_type]['plural'];
//get columns from database, columns are not necessarily set
$arr_core = $main->filter_keys($main->init($arr_columns_json[$row_type], array()));
//get alternative columns from database, not necessarily set
$arr_alternative = $main->filter_keys($main->init($arr_columns_json[$row_type]['alternative'][$definition], array()));

/* END INITIALIZE */
    
//for sorting lists with uasort
function cmp( $a, $b )
    { 
    if ($a['order'] == $b['order'])
        {
        return 0;
        } 
    return ($a['order'] < $b['order']) ? -1 : 1;
    }

//with flow through already existing column values
if ($main->button(1))
	{
    $message = "Columns have been refreshed.";
    }
    
//CORE OR ALTERNATIVE
$core = ($definition == "bb_brimbox") ? true : false;

$arr_core_fields = array('row'=>array('name'=>'Row'),
                         'length'=>array('name'=>'Length'),
                         'order'=>array('name'=>'Order'),
                         'display'=>array('name'=>'Display','hidden'=>0),
                         'type'=>array('name'=>'Type'),
                         'required'=>array('name'=>'Required'),
                         'secure'=>array('name'=>'Secure'),
                         'search'=>array('name'=>'Search'),
                         'relate'=>array('name'=>'Relate'));

$arr_alternative_fields = array('row'=>array('name'=>'Row'),
                                'length'=>array('name'=>'Length'),
                                'order'=>array('name'=>'Order'),
                                'display'=>array('name'=>'Display'));  

/* SUBMIT COLUMN DATA */	
if ($main->button(2))
    {
    //get postback
    for ($i = 1; $i<=50; $i++)
        {
        $name = $main->purge_chars($main->post('name_'. $i, $module, ""), true, true);
         if (!$main->blank($name)) //readonly input for not core
            {
            if ($core)
                {
                $arr_core_work[$i]['name'] = $name;  
                foreach ($arr_core_fields as $key => $value)
                    {
                    //array set for particular purposes
                    //blank string defaults for JSON
                    $default = $main->init($arr_core[$i][$key], "");
                    $arr_core_work[$i][$key] = $main->post($key . '_' . $i, $module, $default);      
                    }
                }
            foreach ($arr_alternative_fields as $key => $value)
                {
                //array set for particular purposes
                //blank string defaults for JSON
                $default = $main->init($arr_alternative[$i][$key], "");
                $arr_alternative_work[$i][$key] = $main->post($key . '_' . $i, $module, $default);
                }                
            }
        }
        
    /* CHECK FOR ERRORS */
    $arr_check = $core ? $arr_core_work : $arr_alternative_work;
    
    $i = 1;
    $error = false; //true means there was errors
    $arr_names = array(); //check for unique names
    $arr_rows = array(); //check for ascending rows
    $arr_order = array(); //check for strict ascending order
    $arr_related = array(); //check related layouts for integrity
    $arr_errors = array(0 => "Error: Row settings contain a blank value when column name is set.",
                    1 => "Error: Column order contains a blank value when column name is set.",
                    2 => "Error: Row values must start at 1 and be strictly ascending when column name is set, records can have multiple columns per row.",
                    3 => "Error: Column order must start at 1, be unique, and be strictly ascending when column name is set.",
                    4 => "Error: Column names must be unique.",
                    5 => "Error: Can only relate a table to a table once.");
    
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
                array_push($arr_names, $arr_check[$i]['name']);
                if (in_array($i, $arr_relate))
                    {
                    if ($arr_check[$i]['relate'] > 0)
                        {
                        array_push($arr_related, (int)$arr_check[$i]['relate']);    
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
        $cnt_related = count($arr_related);
        //case insensitive for column names
        $arr_related = $main->array_iunique($arr_related);
        $cnt_unique_related = count($arr_related);
        if ($cnt_related <> $cnt_unique_related)
            {
            array_push($arr_messages, $arr_errors[5]);
            }
        }
    
    //cleanup $arr_error
    $arr_messages = array_unique($arr_messages);
    asort($arr_messages);
    /* END CHECK FOR ERRORS */
        
    /* BUILD COLUMN ARRAY IN ORDER  */
    //uses the cmp function to sort columns by order
    if ($core) uasort($arr_core_work, 'cmp');
    uasort($arr_alternative_work, 'cmp');
         
    //display working values if error
    if ($core) $arr_core = $arr_core_work;
    $arr_alternative = $arr_alternative_work;
    
    //commit if no error
    if (!$main->has_error_messages($arr_messages))
        {//commit JSON to database
        if ($core)
            {            
            $arr_columns_json[$row_type] = $arr_core_work;
            $arr_columns_json[$row_type]['primary'] = key($main->filter_keys($arr_core_work)) >= 1 ? key($arr_core_work) : 1; //Always set
            $arr_columns_json[$row_type]['count'] = count($arr_core_work); //Always set
            }
        //do non core for everything including core
        $arr_columns_json[$row_type]['alternative'][$definition] = $arr_alternative_work;
        $main->update_json($con, $arr_columns_json, "bb_column_names"); //submit xml
        //update full text indexes for that column $row_type > 0;
        $main->build_indexes($con, $row_type);
        array_push($arr_messages, "Columns have been updated and search index has been rebuilt for this layout.");               
        }	
    } /* END SUBMIT COLUMN NAMES */
    
//rebuild all indexes, row_type = 0 for full text search update
if ($main->button(3))
    {
    //full text update
    $main->build_indexes($con, 0);
    //rebuild indexes
    array_push($arr_messages,"All data table indexes have been rebuilt.");
    }

//module header
echo "<p class=\"spaced bold larger\">Column Names</p>";
/* BEGIN REQUIRED FORM */	
$main->echo_form_begin();
$main->echo_module_vars();

echo "<div class=\"spaced\">";
$main->echo_messages($arr_messages);
echo "</div>";

//row_type select tag
$params = array("class"=>"spaced","onchange"=>"bb_reload()");
$main->layout_dropdown($arr_layouts, "row_type", $row_type, $params);

foreach ($array_header as $key => $value)
    {
    //$arr_header is reduced array_header from JSON
    $arr_definition[$key] = $array_header[$key]['interface_name'];
    }
$arr_definition['bb_test'] = "Test";

$params = array("class"=>"spaced", "onchange"=>"bb_reload()", "usekey"=>true);
$main->array_to_select($arr_definition, "definition", $definition, $params);

//choose core or alternative
if ($core)
    {
    $arr_fields = $arr_core_fields;
    $arr_columns = $arr_core;
    }
else
    {
    $arr_fields = $arr_alternative_fields;
    $arr_columns = $arr_alternative;
    }
    
//display table head
echo "<div class=\"table spaced border\">";
echo "<div class=\"row\">";
echo "<div class=\"padded cell shaded middle\">Column</div>";
echo "<div class=\"padded cell shaded middle\">Name</div>";
foreach ($arr_fields as $value)
    {
    if (!isset($value['hidden']))
        {
        echo "<div class=\"padded cell shaded middle\">" .  $value['name']  . "</div>";
        }
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
    $name = $main->init($arr_core[$m]['name'], "");
    echo "<div class = \"cell middle\"><input name=\"name_" . $m . "\" class = \"spaced\" type=\"text\" value=\"" . htmlentities($name) . "\" size=\"25\" maxlength=\"" . $maxinput . "\" " . $readonly . "/></div>"; 	    

    foreach ($arr_fields as $key => $value)
        {
        if (isset($value['hidden']))
            {
            echo "<input name=\"" . $key . "_" . $m . "\" type=\"hidden\" value=\"" . $value['hidden'] . "\"/>";  
            }
        else
            {
            switch ($key)
                {
                case "row":
                //row dropdown
                    $value = $main->init($arr_columns[$m]['row'], 0);
                    echo "<div class = \"cell middle\"><select name=\"row_" . $m . "\" class = \"spaced\"/>";
                    echo "<option value = \"0\"></option>";
                        for ($i = 1; $i <= 50; $i++)
                            { 
                            echo "<option value=\"" . $i . "\" " . ($i == $value ? "selected" : "") . ">" . $i . "&nbsp;</option>";
                            }
                    echo "</select></div>";
                    break;
                case "length":
                    $value = $main->init($arr_columns[$m]['length'], "");
                    $arr_column_css_class = array("short"=>"Short","medium"=>"Medium","long"=>"Long", "note"=>"Note");
                    echo "<div class = \"cell middle\"><select name = \"length_" . $m . "\" class = \"spaced\">";
                    foreach ($arr_column_css_class as $key => $value)
                        {
                        echo "<option value=\"short\" " . ($key == $value ? "selected" : "") . ">" . $value . "</option>";
                        }
                    echo "</select></div>";
                    break;
                case "order":
                    //order dropdown
                    $value = $main->init($arr_columns[$m]['order'], 0);
                    echo "<div class = \"cell middle\"><select name = \"order_" . $m. "\" class = \"spaced\">";	
                    echo "<option value = \"0\"></option>";
                            for ($i = 1; $i <= 50; $i++)
                                { 
                                echo "<option value=\"" . $i . "\" " . ($i == $value ? "selected" : "") . ">" . $i . "&nbsp;</option>";
                                }
                    echo "</select></div>";
                    break;
                case "display":
                    //display dropdown
                    $value = $main->init($arr_columns[$m]['display'], 0);
                    echo "<div class = \"cell middle\">";
                    echo "<select name = \"display_" . $m. "\" class = \"spaced\">";
                    $arr_display = array(0=>"",1=>"Readonly",2=>"Hidden");
                    foreach ($arr_display as $key => $value)
                        {
                        $selected = ($key == $value) ? "selected" : "";
                        echo "<option value = \"" . $key . "\" " . $selected . ">" . $value . "&nbsp;</option>";
                        }
                    echo "</select>";
                    echo "</div>";
                    break;
                case "type":
                    $value = $main->init($arr_columns[$m]['type'], "");
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
                        echo "<div class = \"cell middle\"><select name = \"type_" . $m. "\" class=\"spaced\">";
                        //global $array_validation
                        foreach ($arr_validation as $key => $value)
                            {
                            echo "<option value=\"" . $key . "\"" . ($key == $value ? "selected" : "") . " >" . $value['name'] . "</option>";
                            }
                        echo "</select></div>";
                        }
                    break;
                case "required":
                    //required checkbox
                    $value = $main->init($arr_columns[$m]['display'], 0);
                    echo "<div class = \"padded cell center middle\">";
                    $checked = ($value == 1) ? true : false;
                    $main->echo_input("required_" . $m, 1, array('type'=>'checkbox','input_class'=>'holderdown','checked'=>$checked));
                    echo "</div>";
                    break;
                case "secure":
                    //secure checkbox
                    $value = $main->init($arr_columns[$m]['secure'], 0);
                    if (empty($arr_column_security))
                        {
                        echo "<div class = \"padded cell center middle\">";
                        $checked = ($value == 1) ? true : false;
                        $main->echo_input("secure_" . $m, 1, array('type'=>'checkbox','input_class'=>'holderdown','checked'=>$checked));
                        echo "</div>";
                        }
                    else
                        {
                        echo "<div class = \"cell middle\"><select name=\"secure_" . $m . "\"class = \"spaced\">";
                        foreach ($arr_column_security as $key => $value)
                            {
                            echo "<option value = \"" . $key . "\" " . ($value == $key ? "selected" : "") . ">" . htmlentities($value) . "&nbsp;</option>";
                            }
                        echo "</select></div>";
                        }
                    break;
                case "search":
                    //search checkbox
                    $value = $main->init($arr_columns[$m]['search'], 0);
                    echo "<div class = \"padded cell center middle\">";
                    $checked = ($value == 1) ? true : false;
                    $main->echo_input("search_" . $m, 1, array('type'=>'checkbox','input_class'=>'holderdown','checked'=>$checked));
                    echo "</div>";
                    break;
                case "relate":
                    if (in_array($m, $arr_relate))
                        {
                        $value = $main->init($arr_columns[$m]['relate'], 0);
                        echo "<div class = \"cell middle\"><select name=\"relate_" . $m . "\"class = \"spaced\">";
                        echo "<option value = \"0\"></option>";
                        foreach ($arr_layouts_reduced as $key => $value)
                            {
                            if ($value['relate'])
                                {
                                echo "<option value = \"" . $key . "\" " . ($value == $key ? "selected" : "") . ">" . htmlentities(chr($key + 64) . $key) . "&nbsp;</option>";
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
            } //hidden
        }//foreach
    echo "</div>"; //row
    } //for loop
echo "</div>"; //table

$params = array("class"=>"spaced","number"=>2, "passthis"=>true, "label"=>"Submit Columns");
$main->echo_button("submit_columnnames", $params);
$params = array("class"=>"spaced","number"=>1, "passthis"=>true, "label"=>"Refresh Columns");
$main->echo_button("refresh_columnnames", $params);
$params = array("class"=>"spaced","number"=>3, "passthis"=>true, "label"=>"Rebuild Indexes");
$main->echo_button("rebuild_indexes", $params);
$main->echo_form_end();
/* END FORM */
?>

