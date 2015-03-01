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
<script>
function bb_reload_row_type_2()
	{
	var frmobj = document.forms["bb_form"];  
    frmobj.list_number_2.value = -1;
    bb_submit_form(0); //call javascript submit_form function	
	}
function bb_reload_list_number_2()
	{
    bb_submit_form(0); //call javascript submit_form function	
	}
    
function bb_reload_row_type_3()
	{
	var frmobj = document.forms["bb_form"];  
    frmobj.list_number_3.value = -1;
    bb_submit_form(0); //call javascript submit_form function	
	}
</script>
<?php
$main->check_permission("bb_brimbox", array(4,5));

/*INITIALIZE */
$arr_message = array();

/* PRESERVE STATE */
$main->retrieve($con, $array_state);

//start code here
$arr_lists = $main->get_json($con, "bb_create_lists");
$message = "";

//columns
$arr_layouts = $main->get_json($con, "bb_layout_names");
$arr_layouts_reduced = $main->filter_keys($arr_layouts);
$default_row_type = $main->get_default_layout($arr_layouts_reduced);

//necessary for load
$list_output =$main->post('list_output', $module, "");
$row_type_1 = $main->post('row_type_1', $module, $default_row_type);
$row_type_2 = $main->post('row_type_2', $module, $default_row_type);
$list_number_2 = $main->post('list_number_2', $module, -1);
$row_type_3 = $main->post('row_type_3', $module, $default_row_type);
$list_number_3 = $main->post('list_number_3', $module, -1);

$update_list = $main->post('update_list', $module, "");
$update_description = $main->post('update_description', $module, "");

//handle remove confirm checkbox
$confirm_remove = $main->post('confirm_remove', $module, 0);

/* LIST SORTING FUNCTIONS */
//used in sorting lists for the usort function
function cmp( $a, $b )
    { 
    return strcasecmp ($a['name'], $b['name']);
    }
    
//add new list
if ($main->button(1))
    {
    if ($main->full('new_value', $module))
        {
        $new_value = $main->custom_trim_string($main->post('new_value', $module),50, true, true);
        $new_description = $main->custom_trim_string($main->post('new_description', $module), 255);
        $row_type = $main->post('row_type_1', $module);
        $arr_list = isset($arr_lists[$row_type_1]) ? $arr_lists[$row_type_1] : array();
        
        //multidimensional too painful to search
        $found = false;
        foreach($arr_list as $value)
            {
            if (!strcasecmp($value['name'], $new_value))
                {
                $found = true;
                break;
                }
            }
        
        if (!$found)  
            {
            $k = $main->get_next_node($arr_list, 2000); //gets next lists number, 1 to limit
            if ($k < 0) //over maximum number of lists
                {
                array_push($arr_message, "Error: Maximum number of lists exceeded."); 
                }
            else//add list
                {
                $arr_list[$k] = array('name'=>$new_value, 'description'=>$new_description, 'archive'=>0);
                uasort($arr_list,'cmp');
                $arr_lists[$row_type_1] = $arr_list;
                $main->update_json($con, $arr_lists,"bb_create_lists");
                //empty list just in case
                $query = "UPDATE data_table SET list_string = list_reset(list_string, " . $k . ") WHERE list_retrieve(list_string, " . $k . ") = 1 AND row_type IN (" . (int)$row_type_1 . ");";
        
                $main->query($con, $query);	
                array_push($arr_message, "List succesfully added.");
                } //end add list
            }//end list does not exist
        else //lists already exists, $bool = true
            {
            array_push($arr_message, "Error: List already exists.");
            }	
        }
    else//button 1 if
        {
        array_push($arr_message, "Error: New list name not supplied.");   
        }
    }

    
//rename or update list    
if ($main->button(2))
    {
    if ($list_number_2 > 0)
        {    
        if ($main->full('update_list', $module))
            {
            $arr_list = $arr_lists[$row_type_2];
			if (isset($arr_list))
				{
				$arr_list[$list_number_2]['name'] = $main->custom_trim_string($update_list, 50, true, true);
                $arr_list[$list_number_2]['description'] =  $main->custom_trim_string($update_description ,255);
                $arr_list[$list_number_2]['archive'] = 0;
                uasort($arr_list,'cmp');
                $arr_lists[$row_type_2] = $arr_list;
                $main->update_json($con, $arr_lists, "bb_create_lists");                
                $update_list = $update_description = $list_output = "";
                $list_number_2 = -1;
                array_push($arr_message, "List definition successfully updated/renamed.");
                }
            else
                {
                array_push($arr_message, "Error: Unable to updated/renamed list.");    
                }
            }
        else
            {
            array_push($arr_message, "Error: List name for update not supplied.");
            }
        }
    } //button 2 if
else
    {
    //populate list for update
    if ($list_number_2 > 0) 
        {
        $arr_list = $arr_lists[$row_type_2];
        $list_output = ($list_number_2 > 0) ? chr($row_type_2 + 64) . $list_number_2 : "";
        $update_list = $arr_list[$list_number_2]['name'];
        $update_description = $arr_list[$list_number_2]['description'];
        }
    //clear list for update
    elseif ($list_number_2 < 0)
        {
        $update_list = $update_description = $list_output = "";
        }    
    } //button 2 else

//remove list
if ($main->button(3) && ($confirm_remove == 1))
    {
    if ($list_number_3 > 0)
        {
		if (isset($arr_lists[$row_type_3][$list_number_3]))
			{
			//reference parent, wierd but works
			unset($arr_lists[$row_type_3][$list_number_3]);            
			//empty list_bit
			$query = "UPDATE data_table SET list_string = list_reset(list_string, " . $list_number_3 . ") WHERE list_retrieve(list_string, " . $list_number_3 . ") = 1 AND row_type IN (" . (int)$row_type_3 . ");";			
			$main->query($con, $query);	
			$main->update_json($con, $arr_lists, "bb_create_lists");
            //delete list populated for update
            if ($list_number_2 == $list_number_3)
                {
                $update_list = $update_description = $list_output = "";
                $list_number_2 = -1;
                }
			array_push($arr_message, "List successfully removed.");
			}
		else
			{
			array_push($arr_message, "Error: Unable to remove list.");    
			}
		}
	else
		{
		array_push($arr_message, "Error: List not selected."); 
		}
    } //button 3 if
elseif ($main->button(3) && ($confirm_remove <> 1))
	{
	array_push($arr_message, "Error: Please confirm to remove list.");	
	}
	
//archive or retrieve list    
if ($main->button(4))
    {
    if ($list_number_3 > 0)
        {
        if (isset($arr_lists[$row_type_3][$list_number_3])) //underlying data could change, hopefully still there, multiuser problem
            {            
            $archive_flag = ($main->post('archive_value', $module) == 1) ? 1 : 0;
            $arr_lists[$row_type_3][$list_number_3]['archive'] = $archive_flag;
            $main->update_json($con, $arr_lists,"bb_create_lists");
                
            $message = ($archive_flag == 1) ? "List successfully archived." : "List successfully retrieved.";
            array_push($arr_message, $message);
            }
        else
            {
            array_push($arr_message, "Error: Unable to archive/retrieve list");    
            }
		}
	else
		{
		array_push($arr_message, "Error: Unable to find list");   	
        }
    } //button 4 if


/* BEGIN REQUIRED FORM */
echo "<p class=\"spaced bold larger\">Manage Lists</p>";

echo "<div class=\"spaced\">";
$main->echo_messages($arr_message);
echo "</div>";

$main->echo_form_begin();
$main->echo_module_vars();;

//add lists
echo "<span class=\"spaced colored\">Add New List</span>";
echo "<div class=\"table border spaced\">";
echo "<div class=\"row padded\">";
echo "<div class=\"cell padded\">List Type: </div>";
echo "<div class=\"cell padded\">";
$params = array("class"=>"spaced");
$main->layout_dropdown($arr_layouts_reduced, "row_type_1", $row_type_1, $params);
echo "</div>";
echo "</div>";
echo "<div class=\"row padded\">";
echo "<div class=\"cell padded top\">List Name: </div>";
echo "<div class=\"cell padded\">";
$main->echo_input("new_value", "", array('type'=>'text','input_class'=>'spaced'));
echo "</div></div>";
echo "<div class=\"row padded\">";
echo "<div class=\"cell padded top\">Description: </div>";
echo "<div class=\"cell padded\">";
$main->echo_textarea("new_description", "", array('rows'=>4, 'cols'=>60, 'class'=>'spaced'));
echo "</div></div>";
echo "<div class=\"row padded\">";
echo "<div class=\"cell padded\"></div>";
echo "<div class=\"cell padded\">";
$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>"New List");
$main->echo_button("add_list", $params);
echo "</div></div></div>";
echo "<br>";

//Rename List or Update Lists
echo "<span class=\"spaced colored\">Rename List, Update Description and Find List Number</span>";
echo "<div class=\"border table\">"; //border
echo "<div class=\"table spaced\">";
echo "<div class=\"row padded\">";
echo "<div class=\"cell padded\">";
$params = array("class"=>"spaced","onchange"=>"bb_reload_row_type_2()");
$main->layout_dropdown($arr_layouts_reduced, "row_type_2", $row_type_2, $params);
$params = array("class"=>"spaced","empty"=>true,"check"=>1,"onchange"=>"bb_reload_list_number_2()");
$arr_pass = isset($arr_lists[$row_type_2]) ? $arr_lists[$row_type_2] : array();
$main->list_dropdown($arr_pass, "list_number_2", $list_number_2, $params);
echo "</div>";
echo "</div>";
echo "</div>";
echo "<div class=\"spaced table padded\">";
echo "<div class=\"row padded\">";
echo "<div class=\"spaced cell padded\">List Number: </div>";
echo "<div class=\"cell padded\">";
$main->echo_input("list_output", htmlentities($list_output), array('type'=>'text','input_class'=>'spaced textbox','readonly'=>true));
echo "</div></div>";
echo "<div class=\"row padded\">";
echo "<div class=\"spaced cell padded top\">List Name: </div>";
echo "<div class=\"cell padded\">";
$main->echo_input("update_list", htmlentities($update_list), array('type'=>'text','input_class'=>'spaced textbox'));
echo "</div></div>";
echo "<div class=\"row padded\">";
echo "<div class=\"spaced cell padded\">Description: </div>";
echo "<div class=\"cell padded\">";
$main->echo_textarea("update_description", $update_description, array('rows'=>4, 'cols'=>60, 'class'=>'spaced'));
echo "</div></div>";
echo "<div class=\"row padded\">";
echo "<div class=\"cell padded\"></div>";
echo "<div class=\"cell padded\">";
$params = array("class"=>"spaced","number"=>2,"target"=>$module, "passthis"=>true, "label"=>"Update List");
$main->echo_button("update_list", $params);
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>"; //border
echo "<br>";

//Remove and archive lists
echo "<span class=\"spaced colored\">Remove or Archive List</span>";
echo "<div class=\"table border spaced\">";
echo "<div class=\"row padded\">";
echo "<div class=\"cell padded nowrap\">";
$params = array("class"=>"spaced","onchange"=>"bb_reload_row_type_3()");
$main->layout_dropdown($arr_layouts_reduced, "row_type_3", $row_type_3, $params);
$params = array("class"=>"spaced","empty"=>true,"check"=>0);
$arr_pass = isset($arr_lists[$row_type_3]) ? $arr_lists[$row_type_3] : array();
$main->list_dropdown($arr_pass, "list_number_3", $list_number_3, $params);
echo " | ";
echo "</div>";
echo "<div class=\"cell padded nowrap\">";
$params = array("class"=>"spaced","number"=>3,"target"=>$module, "passthis"=>true, "label"=>"Remove List");
$main->echo_button("remove_list", $params);
echo "<span class = \"spaced border rounded padded shaded\">";
echo "<label class=\"padded\">Confirm Remove: </label>";
$main->echo_input("confirm_remove", 1, array('type'=>'checkbox','input_class'=>'middle holderup'));
echo "</span>";
echo " | ";
echo "</div>";
echo "<div class=\"cell padded nowrap\">";
$params = array("class"=>"spaced","number"=>4,"target"=>$module, "passthis"=>true, "label"=>"Archive/Retrieve List");
$main->echo_button("archive_list", $params);
echo "</div>";
echo "<div class=\"cell padded nowrap\">";
$main->echo_input("archive_value", 1, array('type'=>'checkbox','input_class'=>'middle holderup'));
echo "<span class=\"spaced\">Check to Archive/Uncheck to Retrieve</span></div>";
echo "</div>";
echo "</div>";

$main->echo_state($array_state);
$main->echo_form_end();
/* END FORM */
?>

