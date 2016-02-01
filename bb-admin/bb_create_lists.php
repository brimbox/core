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
function bb_reload_1()
	{
    bb_submit_form(); //call javascript submit_form function	
	}
function bb_reload_2()
	{
	var frmobj = document.forms["bb_form"];  
    frmobj.list_number_2.value = 0;
    bb_submit_form(); //call javascript submit_form function	
	}    
function bb_reload_3()
	{
	var frmobj = document.forms["bb_form"];  
    frmobj.list_number_3.value = 0;
    bb_submit_form(); //call javascript submit_form function	
	}
</script>
<?php
$main->check_permission("bb_brimbox", array(4,5));

/*INITIALIZE */
$arr_messages = array();

//start code here
$arr_lists = $main->get_json($con, "bb_create_lists");
$message = "";

//columns
$arr_layouts = $main->get_json($con, "bb_layout_names");
$arr_layouts_reduced = $main->filter_keys($arr_layouts);
$default_row_type = $main->get_default_layout($arr_layouts_reduced);

//hot state used for state

//get $POST
$POST = $main->retrieve($con);

//get state from db
$arr_state = $main->load($con, $module);

/* NEW LIST */
//reset on layout change
if ($main->changed('row_type_1', $module, $arr_state, $default_row_type))
    {
    $row_type_1 = $main->process('row_type_1', $module, $arr_state, $row_type_1);
    $new_value_1 = $new_description_1 = "";
    }
//normal process from state or postback
else
    {
    $row_type_1 = $main->process('row_type_1', $module, $arr_state, $default_row_type);   
    $new_value_1 = $main->process('new_value_1', $module, $arr_state);
    $new_description_1 = $main->process('new_description_1', $module, $arr_state);
    }

/* UPDATE LIST */
//change in layout
if ($main->changed('row_type_2', $module, $arr_state, $default_row_type))
    {
    $row_type_2 = $main->process('row_type_2', $module, $arr_state, $default_row_type);
    $list_number_2 = $main->set('list_number_2', $arr_state,  0);
    $update_list_2 = $update_description_2 = $list_output_2 = "";
    }
//hot state or switch tabs
else
    {
    if ($main->changed('list_number_2', $module, $arr_state, 0))
        {
        $row_type_2 = $main->process('row_type_2', $module, $arr_state, $default_row_type);
        $list_number_2 = $main->process('list_number_2', $module, $arr_state, 0);
        $list_2 = $arr_lists[$row_type_2][$list_number_2];
        $list_output_2 = ($list_number_2 > 0) ? chr($row_type_2 + 64) . $list_number_2 : "";        
        $update_list_2 = $list_2['name'];
        $update_description_2 = $list_2['description'];            
        }
    else
        {
        $row_type_2 = $main->process('row_type_2', $module, $arr_state, $default_row_type);
        $list_number_2 = $main->process('list_number_2', $module, $arr_state, 0);
        $list_output_2 = $main->process('list_output_2', $module, $arr_state, "");        
        $update_list_2 = $main->process('update_list_2', $module, $arr_state, "");
        $update_description_2 = $main->process('update_description_2', $module, $arr_state, "");
        }
    }

/* DELETE OR ARCHIVE LIST */
//change in layout
if ($main->changed('row_type_3', $module, $arr_state, $default_row_type))
    {
    $row_type_3 = $main->process('row_type_3', $module, $arr_state, $default_row_type);
    $list_number_3 = $confirm_remove_3 = $archive_value_3 = 0; 
    }
//postback or state change
else
    {
    $row_type_3 = $main->process('row_type_3', $module, $arr_state, $default_row_type);
    $list_number_3 = $main->process('list_number_3', $module, $arr_state,  0);
    $confirm_remove_3 = $main->process('confirm_remove_3', $module, $arr_state, 0);
    $archive_value_3 = $main->process('archive_value_3', $module, $arr_state, 0);
    }
    
//update state, back to db
$main->update($con, $module, $arr_state);


/* LIST SORTING FUNCTIONS */
//used in sorting lists for the usort function
function cmp( $a, $b )
    { 
    return strcasecmp ($a['name'], $b['name']);
    }
    
//add new list
if ($main->button(1))
    {
    if ($main->full('new_value_1', $module))
        {
        //reduced for the foreach loop
        $arr_list_1 = $arr_lists[$row_type_1];
        //reduce for foreach loop
        $arr_list_1_reduced = $main->filter_keys($arr_list_1);

        
        //multidimensional too painful to search
        $found = false;
        foreach($arr_list_1_reduced as $value)
            {
            if (!strcasecmp($value['name'], $new_value_1))
                {
                $found = true;
                break;
                }
            }
        
        if (!$found)  
            {
            $k = $main->get_next_node($arr_list_1_reduced, 2000); //gets next lists number, 1 to limit
            if ($k < 0) //over maximum number of lists
                {
                array_push($arr_messages, "Error: Maximum number of lists exceeded."); 
                }
            else//add list
                {
                $arr_list_1[$k] = array('name'=>$new_value_1, 'description'=>$new_description_1, 'archive'=>0);
                uasort($arr_list_1,'cmp');
                $arr_lists[$row_type_1] = $arr_list_1;
                $main->update_json($con, $arr_lists,"bb_create_lists");
                //empty list just in case
                $query = "UPDATE data_table SET list_string = bb_list_unset(list_string, " . $k . ") WHERE bb_list(list_string, " . $k . ") = 1 AND row_type IN (" . (int)$row_type_1 . ");";
                $main->query($con, $query);
                $new_value_1 = $new_description_1 = "";
                unset($arr_state['new_value_1'],$arr_state['new_description_1']);
                $main->update($con, $module, $arr_state);
                array_push($arr_messages, "List succesfully added.");
                } //end add list
            }//end list does not exist
        else //lists already exists, $bool = true
            {
            array_push($arr_messages, "Error: List already exists.");
            }	
        }
    else//button 1 if
        {
        array_push($arr_messages, "Error: New list name not supplied.");   
        }
    }
    
//rename or update list    
if ($main->button(2))
    {
    if ($list_number_2 > 0)
        {    
        if ($main->full('update_list_2', $module))
            {
            //do not reduce
            $arr_list_2 = $arr_lists[$row_type_2];
			if (isset($arr_list_2))
				{
				$arr_list_2[$list_number_2]['name'] = $update_list_2;
                $arr_list_2[$list_number_2]['description'] = $update_description_2;
                $arr_list_2[$list_number_2]['archive'] = 0;
                uasort($arr_list_2,'cmp');
                $arr_lists[$row_type_2] = $arr_list_2;
                $main->update_json($con, $arr_lists, "bb_create_lists");
                $list_number_2 = 0;
                $update_list_2 = $update_description_2 = $list_output_2 = "";
                unset($arr_state['list_number_2'],$arr_state['update_list_2'],$arr_state['update_description_2']);
                $main->update($con, $module, $arr_state);
                //unset($arr_state['row_type_2'], $arr_state['$list_number_2'], $arr_state['update_list_2'], $arr_state['update_description_2'], $arr_state['list_output_2']);                
                array_push($arr_messages, "List definition successfully updated/renamed.");
                }
            else
                {
                array_push($arr_messages, "Error: Unable to updated/renamed list.");    
                }
            }
        else
            {
            array_push($arr_messages, "Error: List name for update not supplied.");
            }
        }
    } //button 2 if

//remove list
if ($main->button(3) && ($confirm_remove_3 == 1))
    {
    if ($list_number_3 > 0)
        {
		if (isset($arr_lists[$row_type_3][$list_number_3]))
			{
			//reference parent, wierd but works
			unset($arr_lists[$row_type_3][$list_number_3]);            
			//empty list_bit
			$query = "UPDATE data_table SET list_string = bb_list_unset(list_string, " . $list_number_3 . ") WHERE bb_list(list_string, " . $list_number_3 . ") = 1 AND row_type IN (" . (int)$row_type_3 . ");";			
			$main->query($con, $query);	
			$main->update_json($con, $arr_lists, "bb_create_lists");
            //delete list populated for update
            if ($list_number_2 == $list_number_3)
                {
                $update_list_2 = $update_description_2 = $list_output_2 = "";
                }
            $list_number_3 = $confirm_remove_3 = 0;
			array_push($arr_messages, "List successfully removed.");
			}
		else
			{
			array_push($arr_messages, "Error: Unable to remove list.");    
			}
		}
	else
		{
		array_push($arr_messages, "Error: List not selected."); 
		}
    } //button 3 if
elseif ($main->button(3) && ($confirm_remove_3 <> 1))
	{
	array_push($arr_messages, "Error: Please confirm to remove list.");	
	}
	
//archive or retrieve list    
if ($main->button(4))
    {
    if ($list_number_3 > 0)
        {
        if (isset($arr_lists[$row_type_3][$list_number_3])) //underlying data could change, hopefully still there, multiuser problem
            {            
            $arr_lists[$row_type_3][$list_number_3]['archive'] = $archive_value_3;
            $main->update_json($con, $arr_lists,"bb_create_lists");
                
            $message = ($archive_value_3 == 1) ? "List successfully archived." : "List successfully retrieved.";
            $archive_value_3 = $list_number_3 = 0;
            array_push($arr_messages, $message);
            }
        else
            {
            array_push($arr_messages, "Error: Unable to archive/retrieve list");    
            }
		}
	else
		{
		array_push($arr_messages, "Error: Unable to find list");   	
        }
    } //button 4 if


/* BEGIN REQUIRED FORM */
echo "<p class=\"spaced bold larger\">Manage Lists</p>";

echo "<div class=\"spaced\">";
$main->echo_messages($arr_messages);
echo "</div>";

$main->echo_form_begin();
$main->echo_module_vars();;

//add lists
echo "<span class=\"spaced colored\">Add New List</span>";
echo "<div class=\"table border spaced\">";
echo "<div class=\"row padded\">";
echo "<div class=\"cell padded\">List Type: </div>";
echo "<div class=\"cell padded\">";
$params = array("class"=>"spaced","onchange"=>"bb_reload_1()");
$main->layout_dropdown($arr_layouts_reduced, "row_type_1", $row_type_1, $params);
echo "</div>";
echo "</div>";
echo "<div class=\"row padded\">";
echo "<div class=\"cell padded top\">List Name: </div>";
echo "<div class=\"cell padded\">";
$main->echo_input("new_value_1", $new_value_1, array('type'=>'text','input_class'=>'spaced'));
echo "</div></div>";
echo "<div class=\"row padded\">";
echo "<div class=\"cell padded top\">Description: </div>";
echo "<div class=\"cell padded\">";
$main->echo_textarea("new_description_1", $new_description_1, array('rows'=>4, 'cols'=>60, 'class'=>'spaced'));
echo "</div></div>";
echo "<div class=\"row padded\">";
echo "<div class=\"cell padded\"></div>";
echo "<div class=\"cell padded\">";
$params = array("class"=>"spaced","number"=>1,"target"=>$module, $arr_state,  "passthis"=>true, "label"=>"New List");
$main->echo_button("add_list", $params);
echo "</div></div></div>";
echo "<br>";

//Rename List or Update Lists
echo "<span class=\"spaced colored\">Rename List, Update Description and Find List Number</span>";
echo "<div class=\"border table\">"; //border
echo "<div class=\"table spaced\">";
echo "<div class=\"row padded\">";
echo "<div class=\"cell padded\">";
$params = array("class"=>"spaced","onchange"=>"bb_reload_2()");
$main->layout_dropdown($arr_layouts_reduced, "row_type_2", $row_type_2, $params);
$params = array("class"=>"spaced","empty"=>true,"check"=>1,"onchange"=>"bb_reload_1()");
$arr_pass = $main->filter_keys($arr_lists[$row_type_2]);
$main->list_dropdown($arr_pass, "list_number_2", $list_number_2, $params);
echo "</div>";
echo "</div>";
echo "</div>";
echo "<div class=\"spaced table padded\">";
echo "<div class=\"row padded\">";
echo "<div class=\"spaced cell padded\">List Number: </div>";
echo "<div class=\"cell padded\">";
$main->echo_input("list_output_2", htmlentities($list_output_2), array('type'=>'text','input_class'=>'spaced textbox','readonly'=>true));
echo "</div></div>";
echo "<div class=\"row padded\">";
echo "<div class=\"spaced cell padded top\">List Name: </div>";
echo "<div class=\"cell padded\">";
$main->echo_input("update_list_2", htmlentities($update_list_2), array('type'=>'text','input_class'=>'spaced textbox'));
echo "</div></div>";
echo "<div class=\"row padded\">";
echo "<div class=\"spaced cell padded\">Description: </div>";
echo "<div class=\"cell padded\">";
$main->echo_textarea("update_description_2", $update_description_2, array('rows'=>4, 'cols'=>60, 'class'=>'spaced'));
echo "</div></div>";
echo "<div class=\"row padded\">";
echo "<div class=\"cell padded\"></div>";
echo "<div class=\"cell padded\">";
$params = array("class"=>"spaced","number"=>2,"target"=>$module, $arr_state,  "passthis"=>true, "label"=>"Update List");
$main->echo_button("update_list_2", $params);
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
$params = array("class"=>"spaced","onchange"=>"bb_reload_3()");
$main->layout_dropdown($arr_layouts_reduced, "row_type_3", $row_type_3, $params);
$params = array("class"=>"spaced","empty"=>true,"check"=>0,"onchange"=>"bb_reload_1()");
$arr_pass = $main->filter_keys($arr_lists[$row_type_3]);
$main->list_dropdown($arr_pass, "list_number_3", $list_number_3, $params);
echo " | ";
echo "</div>";
echo "<div class=\"cell padded nowrap\">";
$params = array("class"=>"spaced","number"=>3,"target"=>$module, $arr_state,  "passthis"=>true, "label"=>"Remove List");
$main->echo_button("remove_list", $params);
echo "<span class = \"spaced border rounded padded shaded\">";
echo "<label class=\"padded\">Confirm Remove: </label>";
$main->echo_input("confirm_remove_3", 1, array('type'=>'checkbox','input_class'=>'middle holderup','checked'=>$confirm_remove_3));
echo "</span>";
echo " | ";
echo "</div>";
echo "<div class=\"cell padded nowrap\">";
$params = array("class"=>"spaced","number"=>4,"target"=>$module, $arr_state,  "passthis"=>true, "label"=>"Archive/Retrieve List");
$main->echo_button("archive_list", $params);
echo "</div>";
echo "<div class=\"cell padded nowrap\">";
$main->echo_input("archive_value_3", 1, array('type'=>'checkbox','input_class'=>'middle holderup','checked'=>$archive_value_3));
echo "<span class=\"spaced\">Check to Archive/Uncheck to Retrieve</span></div>";
echo "</div>";
echo "</div>";
$main->echo_form_end();
/* END FORM */
?>

