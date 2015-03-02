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

/* PRESERVE STATE */
$main->retrieve($con, $array_state);

//start of code
$arr_layouts = $main->get_json($con, "bb_layout_names"); //do not reduce
$arr_columns = $main->get_json($con, "bb_column_names"); //to set emmty vale

$arr_header = $main->get_json($con, "bb_interface_enable");
$arr_layout_security = $arr_header['layout_security']['value'];

$arr_message = array();
//check for constant
$number_layouts = $main->set_constant('BB_NUMBER_LAYOUTS', 12, 26);

function cmp( $a, $b )
    { 
    if ($a['order'] == $b['order'])
        {
        return 0;
        } 
    return ($a['order'] < $b['order']) ? -1 : 1;
    }

//waterfall, first layout, second layout, third layout
//will only work if first layout is populated
if ($main->button(1)) //layout_submit
    {
    //one record must be populated
    $empty = true;
    $arr_singular = array();
    $arr_plural = array();
    $arr_order = array();
	$arr_parent = array();

    for ($i=1; $i<=$number_layouts; $i++)
        {
        $attrib1 = "singular_" . $i;
        $attrib2 = "plural_" . $i;
        $order1 = "order_" . $i;
		$parent1 =  "parent_" . $i;
        $singular = $main->custom_trim_string($main->post($attrib1, $module), 50, true, true);
        $plural = $main->custom_trim_string($main->post($attrib2, $module), 50, true, true);
        $order = $main->post($order1, $module);
		$parent= $main->post($parent1, $module);
		$parent_forward = (string)$i . (string)$parent;
		$parent_reverse = (string)$parent . (string)$i;
        
        if (!$main->blank($singular) && !$main->blank($plural))
            {
            $empty = false;
            array_push($arr_singular, $singular);
            array_push($arr_plural, $plural);
            array_push($arr_order, $order);
            //check for circular references
			array_push($arr_parent, $parent_forward);
			array_push($arr_parent, $parent_reverse);
            }
        $arr_all_names = array_merge($arr_singular, $arr_plural);
        }
        
    if (!$empty)
        {
        //check for distinct values in singular or plural, use $main->array_iunique so check is not case sensitive
        if ((count($arr_singular) == count($main->array_iunique($arr_singular))) && (count($arr_plural) == count($main->array_iunique($arr_plural))))
            {
            $unique = true;
            }
        else
            {
            $unique = false;
            array_push($arr_message,"Error: Layouts must have distinct singular and plural names.");
            }
            
        asort($arr_order);
        $arr_order = array_merge($arr_order);
        if (($arr_order[0] <> 1) || (count($arr_order) <> count(array_unique($arr_order))) || ($arr_order[count($arr_order) - 1] <> count($arr_order)))
            {
            $count = false;
            array_push($arr_message,"Error: Layouts order must start at 1 and be strictly ascending.");    
            }
        else
            {
            $count = true;
            }
		$arr_parent_unique = array_unique($arr_parent);	
		if (count($arr_parent) == count($arr_parent_unique))
			{
			$relationship = true;
			}
		else
			{
			array_push($arr_message,"Error: Circular or self-referential relationship between layouts.");
			$relationship = false;
			}
        
        //put in array 
        $arr_order = array();
        for ($i=1; $i<=$number_layouts; $i++)
            {
             //check again both plural and singular must be populated
            $singular = $main->custom_trim_string($main->post('singular_'. (string)$i, $module),50,true, true);
            $plural = $main->custom_trim_string($main->post('plural_'. (string)$i, $module),50, true, true);
            if (!$main->blank($singular) && !$main->blank($plural))
                {                
                $parent = $main->post('parent_' . (string)$i, $module, 0); //not set = 0
                $order = $main->post('order_' . (string)$i, $module); //always set
                $secure = $main->post('secure_' . (string)$i, $module, 0); //not set = 0
                $autoload = $main->post('autoload_' . (string)$i, $module, 0); //not set = 0
                $related = $main->post('related_' . (string)$i, $module, 0); //not set = 0
                $arr_order[$i] = array('singular'=>$singular,'plural'=>$plural,'parent'=>$parent,'order'=>$order,'secure'=>$secure,'autoload'=>$autoload,'related'=>$related);
                //initialize empty array for columns
                //this is important to avoid unset notices
                //can test whether column are empty
                if (!isset($arr_columns[$i]))
                    {
                    $arr_columns[$i] = array();    
                    }
                }
            }
        //sort  
        uasort($arr_order,'cmp');
        //update array_layout
        $arr_layouts = $arr_order; 
        }
    else //empty
        {
        array_push($arr_message,"Error: Singular and plural must be populated for at least one layout.");
        }
    
    //not empty and unique, update json
    if ($unique && !$empty && $count && $relationship)
        {
        $main->update_json($con, $arr_layouts, "bb_layout_names");
        $main->update_json($con, $arr_columns, "bb_column_names");
        array_push($arr_message,"Layouts have been updated.");    
        }
    } //submit

if ($main->button(2)) //revert to xml in database
	{
    $arr_layouts = $main->get_json($con, "bb_layout_names");
    array_push($arr_message,"Layouts have been refreshed from database.");
    }

if ($main->button(3)) //vaccum database
    {
    $query = "VACUUM;";
    $main->query($con, $query);
    array_push($arr_message,"Database has been vacuumed.");
    }

/* START REQUIRED FORM */
echo "<p class=\"spaced bold larger\">Layout Names</p>";

echo "<div class=\"padded\">";
$main->echo_messages($arr_message);
echo "</div>";

$main->echo_form_begin();
$main->echo_module_vars();;

echo "<div class=\"table spaced border\">";
echo "<div class=\"row\">";
    echo "<div class=\"cell shaded middle\"><label class=\"spaced padded\">&nbsp;</label></div>";
    echo "<div class=\"cell shaded middle\"><label class=\"spaced padded\">Singular</label></div>";
    echo "<div class=\"cell shaded middle\"><label class=\"spaced padded\">Plural</label></div>";
    echo "<div class=\"cell shaded middle\"><label class=\"spaced padded\">Parent</label></div>";
    echo "<div class=\"cell shaded middle\"><label class=\"spaced padded\">Order</label></div>";
    echo "<div class=\"cell shaded middle\"><label class=\"spaced padded\">Secure</label></div>";
    echo "<div class=\"cell shaded middle\"><label class=\"spaced padded\">Autoload</label></div>";
    echo "<div class=\"cell shaded middle\"><label class=\"spaced padded\">Related</label></div>";
echo "</div>";
for ($i=1; $i<=$number_layouts; $i++)
    {
    echo "<div class=\"row\">"; //begin row
	echo "<div class=\"cell middle\"><label class=\"spaced\">Layout " . chr($i + 64) . $i . "</label></div>";
	echo "<div class=\"cell middle\">";
    $value = isset($arr_layouts[$i]) ? htmlentities($arr_layouts[$i]['singular']) : "";
    $main->echo_input("singular_" . $i, $value, array('input_class'=>'spaced'));
    echo "</div>";
	echo "<div class=\"cell middle\">";
    $value = isset($arr_layouts[$i]) ? htmlentities($arr_layouts[$i]['plural']) : "";
    $main->echo_input("plural_" . $i, $value, array('input_class'=>'spaced'));
    echo "</div>";
	echo "<div class=\"cell middle\">";
	echo "<select class=\"spaced\" name=\"parent_" . $i . "\">";
	echo "<option value=\"0\">&nbsp;</option>";
	for ($j=1; $j<=$number_layouts; $j++)
		{
        $selected = "";
        if (isset($arr_layouts[$i]['parent']))
            {
            $selected  = ($arr_layouts[$i]['parent'] == $j) ? "selected" : "";
            }
		echo "<option value=\"" . $j . "\" " . $selected . ">" .  chr($j + 64) . $j . "&nbsp;</option>";
		}
	echo "</select>";
	echo "</div>";
	echo "<div class=\"cell middle\">";
	echo "<select class=\"spaced\" name=\"order_" . $i . "\">";
	echo "<option value=\"0\"0>&nbsp;</option>";
	for ($j=1; $j<=$number_layouts; $j++)
		{
        $selected = "";
        if (isset($arr_layouts[$i]['order']))
            {
            $selected = ($arr_layouts[$i]['order'] == $j) ? "selected" : "";
            }
		echo "<option value=\"" . $j . "\" " . $selected . ">" .  $j . "&nbsp;</option>";
		}
	echo "</select>";
	echo "</div>";
	
	//secure checkbox	    
	if (empty($arr_layout_security))
		{
        //has a zero or 1 value
        $checked = false;
        if (isset($arr_layouts[$i]['secure']))
            {
            $checked = ($arr_layouts[$i]['secure'] == 1) ? true : false;
            }
		echo "<div class=\"cell padded middle center\">";
        $main->echo_input("secure_" . $i, 1, array('type'=>'checkbox','input_class'=>'holderdown','checked'=>$checked));
		echo "</div>";
		}
	else
		{
		echo "<div class = \"cell middle\"><select name=\"secure_" . $i . "\"class = \"spaced\">";
		foreach ($arr_layout_security as $key => $value)
			{
            $selected = "";
            if (isset($arr_layouts[$i]['secure']))
                {
                $selected = ($arr_layouts[$i]['secure'] == $key) ? "selected" : "";
                }
            echo "<option value = \"" . $key . "\" " . $selected . ">" . htmlentities($value) . "&nbsp;</option>";
			}
		echo "</select></div>";
		}
    //autoload    
    $checked = false;
    if (isset($arr_layouts[$i]['autoload']))
        {
        $checked = ($arr_layouts[$i]['autoload'] == 1) ? true : false;
        }
    echo "<div class=\"cell padded middle center\">";
    $main->echo_input("autoload_" . $i, 1, array('type'=>'checkbox','input_class'=>'holderdown','checked'=>$checked));
    echo "</div>";
    //related checkbox
    $checked = false;
    if (isset($arr_layouts[$i]['related']))
        {
        $checked = ($arr_layouts[$i]['related'] == 1) ? true : false;
        }
    echo "<div class=\"cell padded middle center\">";
    $main->echo_input("related_" . $i, 1, array('type'=>'checkbox','input_class'=>'holderdown','checked'=>$checked));
    echo "</div>";    
	echo "</div>"; //end row
    }
echo "</div>"; //end table

$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>"Submit Layouts");
$main->echo_button("layout_submit", $params);
$params = array("class"=>"spaced","number"=>2,"target"=>$module, "passthis"=>true, "label"=>"Refresh Layouts");
$main->echo_button("refresh_layout", $params);
$params = array("class"=>"spaced","number"=>3,"target"=>$module, "passthis"=>true, "label"=>"Vacuum Database");
$main->echo_button("vacuum_database", $params);

$main->echo_state($array_state);
$main->echo_form_end();
/* END FORM */
?>
