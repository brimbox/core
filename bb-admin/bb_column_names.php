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
$xml_layouts = $main->get_xml($con, "bb_layout_names");
$default_row_type = $main->get_default_row_type($xml_layouts);
$arr_notes = array("c49","c50");
$arr_reserved = array("c47","c48");

/* RETRIEVE STATE */
$main->retrieve($con, $array_state, $userrole);

function cmp( $a, $b )
    { 
    if ($a->order == $b->order)
        {
        return 0;
        } 
    return ($a->order < $b->order) ? -1 : 1;
    }

//start of code, get row_type
$row_type = $main->post('row_type', $module, $default_row_type);

//get xml, after row type
$xml_columns = $main->get_xml($con, "bb_column_names");
$layout = $main->pad("l", $row_type);
$xml_layout = $xml_layouts->$layout;
$xml_column = $xml_columns->$layout;
$layout_name = (string)$xml_layout['plural'];

//array of error messages
$arr_message = array();

//with flow through already existing column values
if ($main->post('bb_button', $module) == 1)
	{
    $message = "Columns have been refreshed.";
    }

/* SUBMIT COLUMN DATA */	
if ($main->post('bb_button', $module) == 2)
    {
    /* CHECK FOR ERRORS */
    $i = 1;
    $error = false; //true means there was errors
    $arr_columns = array();
    $arr_rows = array();
    $arr_order = array();
    $arr_errors = array(0 => "Error: Row settings contain a blank value when column name is set.",
                    1 => "Error: Column order contains a blank value when column name is set.",
                    2 => "Error: Row values must start at 1 and be strictly ascending when column name is set, records can have multiple columns per row.",
                    3 => "Error: Column order must start at 1, be unique, and be strictly ascending when column name is set.",
                    4 => "Error: Column names must be unique."); 
    for ($i = 1; $i <= 50; $i++)
        {
        //$col_name = "c" . str_pad((string)$i, 2, "0", STR_PAD_LEFT);
        $col_input = "column_" . $i;
        $col_value = $main->custom_trim_string($main->post($col_input, $module), 50, true, true);
        if (!empty($col_value))
            {
            array_push($arr_columns, $col_value);
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
        $cnt = count($arr_columns);
        //case insensitive for column names
        $arr_columns = $main->array_iunique($arr_columns);
        $cnt_unique = count($arr_columns);        
        
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
        $a = 0;
        $arr_order= array();
        //put into array for sorting
        for ($i = 1; $i <= 50; $i++)
            {
            $col_name = "c" . str_pad((string)$i, 2, "0", STR_PAD_LEFT);
            $col_input = "column_" . $i;
            $col_value = $main->custom_trim_string($main->post($col_input, $module),50, true, true);
            
            //makes life easier, an xpath sort is going to return an array anyway
            if (!empty($col_value))
                {
                $a++;
				$arr_order[$i] = new stdClass();
                $arr_order[$i]->num = $i;
                $arr_order[$i]->val = $col_value;
                $arr_order[$i]->leng = $main->post("leng_" . $i, $module);
                $arr_order[$i]->row = (int)$main->post("row_" . $i, $module);
                $arr_order[$i]->order = (int)$main->post("order_" . $i, $module);
                $arr_order[$i]->type = $main->post("type_" . $i, $module, "");
				//convert if not set to int
                $arr_order[$i]->req = (int)$main->post("req_" . $i, $module, 0);
                $arr_order[$i]->secure = (int)$main->post("secure_" . $i, $module, 0);
                $arr_order[$i]->search = (int)$main->post("search_" . $i, $module, 0); 
                }
            }
            //uses the cmp function to sort columns by order
            usort($arr_order,'cmp');
            
            /* SET XML */
			//get key
			$xml_column = $xml_columns->$layout;
			$key = (string)$xml_column['key'];
            /* initialize $xml_column */
			unset($xml_column);
			$main->set($layout, $xml_columns, "");
            $xml_column = $xml_columns->$layout;

            foreach ($arr_order as $value)
                {
                /* column definitions */
                $col_name = "c" . str_pad((string)$value->num, 2, "0", STR_PAD_LEFT);   
                $xml_column->$col_name = $value->val;
				$child = $xml_column->$col_name;
                $child->addAttribute("leng", $value->leng);
                $child->addAttribute("row", $value->row);
                $child->addAttribute("order", $value->order);
                $child->addAttribute("type", $value->type);
                $child->addAttribute("req", (int)$value->req);
                $child->addAttribute("secure", (int)$value->secure);
                $child->addAttribute("search", (int)$value->search);
                   
                //set primary, inside loop   
                if ($value->order == 1)
                    {
                    //Error posibility of 2 column order 1's, just set 1
                    //does not matter because xml will not load, error situation
                    unset($xml_column['primary']);
                    $xml_column->addAttribute("primary", $col_name);
                    }                
                }
			//outside loop
            if ($a > 0) //don't set count if blank xml
                {
                $xml_column->addAttribute("count", $a);
                }
			//set unique key
			if (!empty($key))
				{
				$xml_column->addAttribute("key", $key);	
				}
			//commit if no error
            if (!$error)
                {//commit XML to database
                $main->update_xml($con, $xml_columns, "bb_column_names"); //submit xml
                //update full text indexes for that column $row_type > 0;
                $main->build_indexes($con, $row_type);
                array_push($arr_message, "Columns have been updated and search index has been rebuilt for this layout.");               
                }
                /* END XML */		
	} /* END SUBMIT COLUMN NAMES */
    
//rebuild all indexes, row_type = 0 for full text search update
if ($main->post('bb_button', $module) == 3)
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
$main->echo_module_vars($module);

echo "<div class=\"spaced\">";
$main->echo_messages($arr_message);
echo "</div>";

//row_type select tag
$params = array("class"=>"spaced","onchange"=>"reload_on_layout()");
$main->layout_dropdown($xml_layouts, "row_type", $row_type, $params);

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
    $value = "";
    $row = 0;
    $leng = "";
    $order = 0;
    $type = "";
    $req = 0;
    $secure = 0;
    $search = 0;
    
    $col = "c" . str_pad((string)$m, 2, "0", STR_PAD_LEFT);
    if (!empty($xml_column->$col)) //empty condition
        {
        //if reset or initial load populates from xml table      
        $child = $xml_column->$col;		
        $value = (string)$child;	
        $row = (int)$child['row'];		
        $leng = (string)$child['leng'];
        $order = (int)$child['order'];
        $type = (string)$child['type'];
        $req = (int)$child['req'];
        $secure = (int)$child['secure'];
        $search = (int)$child['search'];
        }
	
	//this is for reserved columns
	$readonly = in_array($col, $arr_reserved) ? "readonly" : "";
 
	echo "<div class=\"row\">";        
	echo "<div class = \"padded cell middle\">" . htmlentities($layout_name) . " " . str_pad((string)$m, 2, "0", STR_PAD_LEFT) . "</div>";
	echo "<div class = \"cell middle\"><input name=\"column_" . $m . "\" class = \"spaced\" type=\"text\" value=\"" . htmlentities($value) . "\" size=\"25\" maxlength=\"50\" " . $readonly . "/></div>"; 	    

	echo "<div class = \"cell middle\"><select name=\"row_" . $m . "\" class = \"spaced\"/>";
	echo "<option value = \"0\"></option>";
		for ($i = 1; $i <= 50; $i++)
			{ 
			echo "<option value=\"" . $i . "\" " . ($i == $row ? "selected" : "") . ">" . $i . "&nbsp;</option>";
			}
	 echo "</select></div>";		

	echo "<div class = \"cell middle\"><select name = \"leng_" . $m . "\" class = \"spaced\">";
		echo "<option value=\"short\" " . ("short" == $leng ? "selected" : "") . ">Short</option>";
		echo "<option value=\"medium\" " . ("medium" == $leng ? "selected" : "") . ">Medium</option>";
		echo "<option value=\"long\" " . ("long" == $leng ? "selected" : "") . ">Long</option>";
		echo "<option value=\"note\" " . ("note" == $leng ? "selected" : "") . ">Note</option>";
	echo "</select></div>";
				
	echo "<div class = \"cell middle\"><select name = \"order_" . $m. "\" class = \"spaced\">";	
	echo "<option value = \"0\"></option>";
            for ($i = 1; $i <= 50; $i++)
                { 
                echo "<option value=\"" . $i . "\" " . ($i == $order ? "selected" : "") . ">" . $i . "&nbsp;</option>";
                }
	echo "</select></div>";
	if (in_array($col, $arr_notes))
		{
		echo "<div class = \"padded cell middle center colored\">Note</div>";
		}
	elseif (in_array($col, $arr_reserved))
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
	echo "<input name=\"req_" . $m . "\" type=\"checkbox\"  class=\"spaced\" value=\"1\" " . ($req == 1 ? "checked" : "") . "/>";
    echo "</div>";
    //secure checkbox
	if (empty($array_security))
		{
		echo "<div class = \"padded cell center middle\">";
		echo "<input name=\"secure_" . $m . "\" type=\"checkbox\"  class=\"spaced\" value=\"1\" " . (($secure == 0) ? "" : "checked") . "/>";
		echo "</div>";
		}
	else
		{
		echo "<div class = \"cell middle\"><select name=\"secure_" . $m . "\"class = \"spaced\">";
		foreach ($array_security as $key => $value)
			{
			echo "<option value = \"" . $key . "\" " . ($secure == $key ? "selected" : "") . ">" . htmlentities($value) . "&nbsp;</option>";
			}
		echo "</select></div>";
		}
    //search checkbox
    echo "<div class = \"padded cell center middle\">";
    echo "<input name=\"search_" . $m . "\" type=\"checkbox\"  class=\"spaced\" value=\"1\" " . ($search == 1 ? "checked" : "") . "/>";
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

