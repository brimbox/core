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

/* PRESERVE STATE */
$main->retrieve($con, $array_state, $userrole);

//start of code
$xml_layouts = $main->get_xml($con, "bb_layout_names");
$xml_columns = $main->get_xml($con, "bb_column_names");
$arr_message = array();

$number_layouts = (NUMBER_LAYOUTS <= 26) ? NUMBER_LAYOUTS : 26;

function cmp( $a, $b )
    { 
    if ($a->order == $b->order)
        {
        return 0;
        } 
    return ($a->order < $b->order) ? -1 : 1;
    }

//waterfall, first layout, second layout, third layout
//will only work if first layout is populated
if ($main->post('bb_button', $module) == 1) //layout_submit
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
        
        if (!empty($singular) && !empty($plural))
            {
            $empty = false;
            array_push($arr_singular, $singular);
            array_push($arr_plural, $plural);
            array_push($arr_order, $order);
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
        
        //put in array to sort, no real better way to sort XML
        $arr_order = array();
        for ($i=1; $i<=$number_layouts; $i++)
            {
             //check again both plural and singular must be populated
            $singular = $main->custom_trim_string($main->post('singular_'. (string)$i, $module),50,true, true);
            $plural = $main->custom_trim_string($main->post('plural_'. (string)$i, $module),50, true, true);
            if (!empty($singular) && !empty($plural))
                {
				$arr_order[$i] = new stdClass();	
                $arr_order[$i]->row_type = $i;
                $arr_order[$i]->singular = $singular;
                $arr_order[$i]->plural = $plural;
                $arr_order[$i]->parent = $main->post('parent_' . (string)$i, $module);
                $arr_order[$i]->order = $main->post('order_' . (string)$i, $module);
                $secure = (int)$main->post('secure_' . (string)$i, $module);
                $arr_order[$i]->secure = $secure;
				$arr_order[$i]->autoload = 0;
                }
            }
            
        usort($arr_order,'cmp');
        $xml_layouts = simplexml_load_string("<layouts></layouts>");
            
        foreach ($arr_order as $value)
            {
            $layout = $main->pad("l",(int)$value->row_type);
			//overload
            $xml_layouts->$layout = "";
			$child = $xml_layouts->$layout;
            $child->addAttribute("singular",$value->singular);
            $child->addAttribute("plural",$value->plural);
            $child->addAttribute("parent",$value->parent);
            $child->addAttribute("order",$value->order);
            $child->addAttribute("secure",$value->secure);
			$child->addAttribute("autoload",$value->autoload);
            }
        }
    else //empty
        {
        array_push($arr_message,"Error: Singular and plural must be populated for at least one layout.");
        }
    
    //not empty and unique, update xml
    if ($unique && !$empty && $count && $relationship)
        {
        $main->update_xml($con, $xml_layouts, "bb_layout_names");
        array_push($arr_message,"Layouts have been updated.");    
        }
    } //submit

if ($main->post('bb_button', $module) == 2) //revert to xml in database
	{
    $xml_layouts = $main->get_xml($con, "bb_layout_names");
    array_push($arr_message,"Layouts have been refreshed from database.");
    }

if ($main->post('bb_button', $module) == 3) //vaccum database
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
$main->echo_module_vars($module);

echo "<div class=\"table spaced border\">";
echo "<div class=\"row\">";
    echo "<div class=\"cell shaded middle\"><label class=\"spaced padded\">&nbsp;</label></div>";
    echo "<div class=\"cell shaded middle\"><label class=\"spaced padded\">Singular</label></div>";
    echo "<div class=\"cell shaded middle\"><label class=\"spaced padded\">Plural</label></div>";
    echo "<div class=\"cell shaded middle\"><label class=\"spaced padded\">Parent</label></div>";
    echo "<div class=\"cell shaded middle\"><label class=\"spaced padded\">Order</label></div>";
    echo "<div class=\"cell shaded middle\"><label class=\"spaced padded\">Secure</label></div>";
echo "</div>";
for ($i=1; $i<=$number_layouts; $i++)
    {
    $layout = $main->pad("l", $i);
    $xml_layout = $xml_layouts->$layout;
    echo "<div class=\"row\">";
	echo "<div class=\"cell middle\"><label class=\"spaced\">Layout " . chr($i + 64) . $i . "</label></div>";
	echo "<div class=\"cell middle\"><input class=\"spaced\" name=\"singular_" . $i . "\" type=\"text\" value = \"" . (isset($xml_layout) ? htmlentities((string)$xml_layout['singular']) : "") . "\"/></div>";
	echo "<div class=\"cell middle\"><input class=\"spaced\" name=\"plural_" . $i . "\" type=\"text\" value = \"" . (isset($xml_layout) ? htmlentities((string)$xml_layout['plural']) : "") . "\"/></div>";
	echo "<div class=\"cell middle\">";
	echo "<select class=\"spaced\" name=\"parent_" . $i . "\">";
	echo "<option value=\"0\">&nbsp;</option>";
	for ($j=1; $j<=$number_layouts; $j++)
		{
		$select  = ((int)$xml_layout['parent'] == $j) ? "selected" : "";
		echo "<option value=\"" . $j . "\" " . $select . ">" .  chr($j + 64) . $j . "&nbsp;</option>";
		}
	echo "</select>";
	echo "</div>";
	echo "<div class=\"cell middle\">";
	echo "<select class=\"spaced\" name=\"order_" . $i . "\">";
	echo "<option value=\"0\"0>&nbsp;</option>";
	for ($j=1; $j<=$number_layouts; $j++)
		{
		$select = ((int)$xml_layout['order'] == $j) ? "selected" : "";
		echo "<option value=\"" . $j . "\" " . $select . ">" .  $j . "&nbsp;</option>";
		}
	echo "</select>";
	echo "</div>";
	
	//secure checkbox
	$secure = (int)$xml_layout['secure'];	    
	if (empty($array_security))
		{
		echo "<div class=\"cell middle center spaced\">";
		echo "<input class=\"spaced\" type=\"checkbox\" name=\"secure_" . $i . "\" value=\"1\"  " .  (($secure == 0) ? "" : "checked") . " />";
		echo "</div>";
		}
	else
		{
		echo "<div class = \"cell middle\"><select name=\"secure_" . $i . "\"class = \"spaced\">";
		foreach ($array_security as $key => $value)
			{
			echo "<option value = \"" . $key . "\" " . ($secure == $key ? "selected" : "") . ">" . htmlentities($value) . "&nbsp;</option>";
			}
		echo "</select></div>";
		}		
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
