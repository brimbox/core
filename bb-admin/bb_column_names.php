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
$main->check_permission("bb_brimbox", 5);
?>
<script type="text/javascript">
//reload for change of row_type
function reload_on_layout()
	{
	//standard submit
    bb_submit_form();
	}
</script>
<?php
/* INITIALIZE */
set_time_limit(0);

//find default row_type, $xml_layouts must have one layout set
$arr_layouts = $main->get_json($con, "bb_layout_names");
$default_row_type = $main->get_default_layout($arr_layouts);
$arr_notes = array(49,50);
$arr_reserved = array(47,48);

/* RETRIEVE STATE */
$main->retrieve($con, $array_state);

//for sorting lists with uasort
function cmp( $a, $b )
    { 
    if ($a['order'] == $b['order'])
        {
        return 0;
        } 
    return ($a['order'] < $b['order']) ? -1 : 1;
    }

//start of code, get row_type
$row_type = $main->post('row_type', $module, $default_row_type);

//get xml, after row type
$arr_columns = $main->get_json($con, "bb_column_names");
$arr_layout = $arr_layouts[$row_type];
$arr_column = $arr_columns[$row_type];
$layout_name = $arr_layout['plural'];

//array of error messages
$arr_message = array();

//with flow through already existing column values
if ($main->button(1))
	{
    $message = "Columns have been refreshed.";
    }

/* SUBMIT COLUMN DATA */	
if ($main->button(2))
    {
    /* CHECK FOR ERRORS */
    $i = 1;
    $error = false; //true means there was errors
    $arr_names = array(); //check for unique names
    $arr_rows = array(); //check for ascending rows
    $arr_order = array(); //check for strict ascending order
    $arr_errors = array(0 => "Error: Row settings contain a blank value when column name is set.",
                    1 => "Error: Column order contains a blank value when column name is set.",
                    2 => "Error: Row values must start at 1 and be strictly ascending when column name is set, records can have multiple columns per row.",
                    3 => "Error: Column order must start at 1, be unique, and be strictly ascending when column name is set.",
                    4 => "Error: Column names must be unique."); 
    for ($i = 1; $i <= 50; $i++)
        {
        //check rows and order for integrity
        $col_name = $main->custom_trim_string($main->post("name_" . $i, $module), 50, true, true);
        if (!$main->blank($col_name))
            {
            array_push($arr_names, $col_name);
            $row_input = "row_" . $i;
            if ($main->post($row_input, $module) == 0) 
                { 
                array_push($arr_message, $arr_errors[0]);
                $error = true;
                }
            else
                {
                array_push($arr_rows, (int)$main->post($row_input, $module));
                }							
            $order_input =  "order_" . $i;
            if ($main->post($order_input, $module) == 0) 
                { 
                array_push($arr_message,$arr_errors[1]);
                $error = true;
                }
            else
                {
                array_push($arr_order,(int)$main->post($order_input, $module));
                }
            } //end col_value if
        }//end for loop
		
        //process arrays
        //columns must be unique
        $cnt = count($arr_names);
        //case insensitive for column names
        $arr_names = $main->array_iunique($arr_names);
        $cnt_unique = count($arr_names);        
        
        //strictly ascending starting at 1
        $arr_rows = array_unique($arr_rows);
        asort($arr_rows);
        $arr_rows = array_merge($arr_rows);
        
        //strictly ascending and unique starting at 1
        $arr_temp = array_unique($arr_order);
        $cnt_order = count($arr_temp); //holds count of unique
        asort($arr_order);
        $arr_order = array_merge($arr_order);
        
        //cleanup $arr_error
        $arr_message = array_unique($arr_message);
        asort($arr_message);
		
        if (count($arr_rows) > 0) //must have at least one value
            {
            if (($arr_rows[0] <> 1) || ($arr_rows[count($arr_rows) - 1] <> count($arr_rows)))	
                {
                array_push($arr_message, $arr_errors[2]);
                $error = true;
                }
            }
        if (count($arr_order) > 0) //must have at least one value
            {
            if (($arr_order[0] <> 1) || ($arr_order[count($arr_order) - 1] <> count($arr_order)) || (count($arr_order) <> $cnt_order))	
                {
                array_push($arr_message, $arr_errors[3]);
                $error = true;
                }
            }
        if ($cnt <> $cnt_unique)
            {
            array_push($arr_message, $arr_errors[4]);
            $error = true;
            }
        /* END CHECK FOR ERRORS */
	
            
        /* BUILD ORDER ARRAY */
        /* array is built for sorting so column xml is stored in order according to column order */
        /* this makes column retrieval faster since it is already in order */
        /* the layout order or header xml is stored last-in at the end of xml */
        $count = 0; //count of columss
        $arr_order= array();
        //put into array for sorting
        for ($i = 1; $i <= 50; $i++)
            {
            //The name of the column
            $col_name = $main->custom_trim_string($main->post("name_" . $i, $module),50, true, true);
            
            //makes life easier, an xpath sort is going to return an array anyway
            if (!$main->blank($col_name))
                {
                $name = (string)$col_name;
                $row = (int)$main->post("row_" . $i, $module);
                $length = (string)$main->post("length_" . $i, $module);
                $order = (int)$main->post("order_" . $i, $module);
                $type = (string)$main->post("type_" . $i, $module, "");
                $required = (int)$main->post("required_" . $i, $module, 0);
                $secure = (int)$main->post("secure_" . $i, $module, 0);
                $search = (int)$main->post("search_" . $i, $module, 0);
                $arr_order[$i] = array('name'=>$name,'row'=>$row,'length'=>$length,'order'=>$order,'type'=>$type,'required'=>$required,'secure'=>$secure,'search'=>$search);
                $count++;
                }
            }
            //uses the cmp function to sort columns by order
            uasort($arr_order,'cmp');
			
            //get unique, count and primary for layout key
            $arr_order['layout'] = array();
            //preserve unique -- not set if no key
            if (isset($arr_column['layout']['unique']))
                {
                $arr_order['layout']['unique'] = $arr_column['layout']['unique'];
                }
            $arr_order['layout']['primary'] = key($arr_order); //Always set
            $arr_order['layout']['count'] = $count; //Always set
                        
            $arr_column = $arr_order;
            $arr_columns[$row_type] =  $arr_order;

			//commit if no error
            if (!$error)
                {//commit XML to database
                $main->update_json($con, $arr_columns, "bb_column_names"); //submit xml
                //update full text indexes for that column $row_type > 0;
                $main->build_indexes($con, $row_type);
                array_push($arr_message, "Columns have been updated and search index has been rebuilt for this layout.");               
                }
                /* END XML */		
	} /* END SUBMIT COLUMN NAMES */
    
//rebuild all indexes, row_type = 0 for full text search update
if ($main->button(3))
    {
    //full text update
    $main->build_indexes($con, 0);
    //rebuild indexes
    array_push($arr_message,"All data table indexes have been rebuilt.");
    }

//module header
echo "<p class=\"spaced bold larger\">Column Names</p>";
/* BEGIN REQUIRED FORM */	
$main->echo_form_begin();
$main->echo_module_vars();;

echo "<div class=\"spaced\">";
$main->echo_messages($arr_message);
echo "</div>";

//row_type select tag
$params = array("class"=>"spaced","onchange"=>"reload_on_layout()");
$main->layout_dropdown($arr_layouts, "row_type", $row_type, $params);

//populate row_type select combo box from xml columns
$arr_head = array("Column","Name","Row","Length","Order","Type","Required","Secure","Search");

//table header
echo "<div class=\"table spaced border\">";
echo "<div class=\"row\">";
foreach ($arr_head as $value)
    {
    echo "<div class=\"padded cell shaded middle\">" .  $value  . "</div>"; 
    }
echo "</div>";

//table rows
for ($m = 1; $m <= 50; $m++)
    {
    //defaults
    $column = $m;
    $name = "";
    $row = 0;
    $leng = "";
    $order = 0;
    $type = "";
    $required = 0;
    $secure = 0;
    $search = 0;
    
    if (isset($arr_column[$m])) //empty condition
        {
        //if reset or initial load populates from xml table
        $name = $arr_column[$m]['name'];
        $row = $arr_column[$m]['row'];
        $length = $arr_column[$m]['length'];
        $order = $arr_column[$m]['order'];
        $type = $arr_column[$m]['type'];
        $required = $arr_column[$m]['required'];
        $secure = $arr_column[$m]['secure'];
        $search = $arr_column[$m]['search'];
        }
	
	//this is for reserved columns
	$readonly = in_array($column, $arr_reserved) ? "readonly" : "";
 
	echo "<div class=\"row\">";        
	echo "<div class = \"padded cell middle\">" . htmlentities($layout_name) . " " . str_pad((string)$m, 2, "0", STR_PAD_LEFT) . "</div>";
	echo "<div class = \"cell middle\"><input name=\"name_" . $m . "\" class = \"spaced\" type=\"text\" value=\"" . htmlentities($name) . "\" size=\"25\" maxlength=\"50\" " . $readonly . "/></div>"; 	    

	echo "<div class = \"cell middle\"><select name=\"row_" . $m . "\" class = \"spaced\"/>";
	echo "<option value = \"0\"></option>";
		for ($i = 1; $i <= 50; $i++)
			{ 
			echo "<option value=\"" . $i . "\" " . ($i == $row ? "selected" : "") . ">" . $i . "&nbsp;</option>";
			}
	 echo "</select></div>";		

	echo "<div class = \"cell middle\"><select name = \"length_" . $m . "\" class = \"spaced\">";
		echo "<option value=\"short\" " . ("short" == $length ? "selected" : "") . ">Short</option>";
		echo "<option value=\"medium\" " . ("medium" == $length ? "selected" : "") . ">Medium</option>";
		echo "<option value=\"long\" " . ("long" == $length ? "selected" : "") . ">Long</option>";
		echo "<option value=\"note\" " . ("note" == $length ? "selected" : "") . ">Note</option>";
	echo "</select></div>";
				
	echo "<div class = \"cell middle\"><select name = \"order_" . $m. "\" class = \"spaced\">";	
	echo "<option value = \"0\"></option>";
            for ($i = 1; $i <= 50; $i++)
                { 
                echo "<option value=\"" . $i . "\" " . ($i == $order ? "selected" : "") . ">" . $i . "&nbsp;</option>";
                }
	echo "</select></div>";
	if (in_array($column, $arr_notes))
		{
		echo "<div class = \"padded cell middle center colored\">Note</div>";
		}
	elseif (in_array($column, $arr_reserved))
		{
		echo "<div class = \"padded cell middle center colored\">Reserved</div>";
		}
	else
		{
		echo "<div class = \"cell middle\"><select name = \"type_" . $m. "\" class=\"spaced\">";
		//global $array_validation
		foreach ($array_validation as $key => $value)
			{
			echo "<option value=\"" . htmlentities($key) . "\"" . ($key == $type ? "selected" : "") . ">" . htmlentities($key) . "</option>";
			}
		echo "</select></div>";
		}
    //required checkbox
    echo "<div class = \"padded cell center middle\">";
	echo "<input name=\"required_" . $m . "\" type=\"checkbox\"  class=\"spaced\" value=\"1\" " . ($required == 1 ? "checked" : "") . "/>";
    echo "</div>";
    //secure checkbox
	if (empty($array_security['column_security']))
		{
		echo "<div class = \"padded cell center middle\">";
        $checked = ($secure == 1) ? true : false;
        $main->echo_input("secure_" . $m, 1, array('type'=>'checkbox','class'=>'spaced','checked'=>$checked));
		echo "</div>";
		}
	else
		{
		echo "<div class = \"cell middle\"><select name=\"secure_" . $m . "\"class = \"spaced\">";
		foreach ($array_security['column_security'] as $key => $value)
			{
			echo "<option value = \"" . $key . "\" " . ($secure == $key ? "selected" : "") . ">" . htmlentities($value) . "&nbsp;</option>";
			}
		echo "</select></div>";
		}
    //search checkbox
    echo "<div class = \"padded cell center middle\">";
    $checked = ($search == 1) ? true : false;
    $main->echo_input("search_" . $m, 1, array('type'=>'checkbox','class'=>'spaced','checked'=>$checked));
    echo "</div>";
    echo "</div>";
    }
echo "</div>";

$params = array("class"=>"spaced","number"=>2,"target"=>$module, "passthis"=>true, "label"=>"Submit Columns");
$main->echo_button("submit_columnnames", $params);
$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>"Refresh Columns");
$main->echo_button("refresh_columnnames", $params);
$params = array("class"=>"spaced","number"=>3,"target"=>$module, "passthis"=>true, "label"=>"Rebuild Indexes");
$main->echo_button("rebuild_indexes", $params);

$main->echo_state($array_state);
$main->echo_form_end();
/* END FORM */
?>

