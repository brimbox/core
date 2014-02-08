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
<script>
function bb_reload_on_layout_2()
	{
	var frmobj = document.forms["bb_form"];  
    frmobj.list_number_2.value = -1;
    bb_submit_form(0); //call javascript submit_form function	
	}
function bb_reload_on_layout_3()
	{
	var frmobj = document.forms["bb_form"];  
    frmobj.list_number_3.value = -1;
    bb_submit_form(0); //call javascript submit_form function	
	}
</script>
<?php
$main->check_permission(4);

/*INITIALIZE */
$arr_message = array();

/* PRESERVE STATE */
$main->retrieve($con, $array_state, $userrole);

//start code here
$xml_lists = $main->get_xml($con, "bb_create_lists");
$message = "";

//columns
$xml_layouts = $main->get_xml($con, "bb_layout_names");
$default_row_type = $main->get_default_row_type($xml_layouts);

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
    return strcmp ($a->value, $b->value);
    }
    
//must keep list sorted, no easy way
function sort_list($xml_in)
    {
    $arr_sort = array();
    $i = 0;
    foreach ($xml_in->children() as $child)
        {
		$arr_sort[$i] = new stdClass();
        $arr_sort[$i]->node = (string)$child->getName();        
        $arr_sort[$i]->value = (string)$child;
        $arr_sort[$i]->row_type = (string)$child['row_type'];
        $arr_sort[$i]->archive = (string)$child['archive'];
        $arr_sort[$i]->description = (string)$child['description'];
        $i++;
        }
    usort($arr_sort,'cmp');
    $xml_out = simplexml_load_string("<lists/>");
    foreach ($arr_sort as $value)
        {		
		$node = (string)$value->node; //convert object to string
		$child = $xml_out->addChild($node);
		$child->{0} = $value->value;
        $child->addAttribute("row_type", $value->row_type);
        $child->addAttribute("archive", $value->archive);
        $child->addAttribute("description", $value->description);
        }
    return $xml_out;
    }
/* END LIST SORTING FUNCTIONS */

//add new list
if ($main->post('bb_button', $module) == 1)
    {
    if ($main->full('new_value', $module))
        {
        $new_value = $main->custom_trim_string($main->post('new_value', $module),50, true, true);
        $new_description = $main->custom_trim_string($main->post('new_description', $module), 255);
        $row_type = $main->post('row_type', $module);
        
        $path = "//*[.=\"". $new_value . "\" and @row_type=" . $row_type_1 . "]";
        $node = $main->search_xml($xml_lists, $path);
        $bool = (count($node) == 0) ? true : false; //search true if list already exists
            
        if ($bool && ($row_type_1 > 0)) //list does not exist, valid row_type
            {
			$path = "/lists/*[@row_type=" . $row_type_1 . "]";
            $k = $main->get_next_xml_node($xml_lists, $path, 2000); //gets next lists number
            if ($k < 0) //over maximum number of lists
                {
                array_push($arr_message, "Error: Maximum number of lists exceeded."); 
                }
            else//add list
                {
                $node = $main->pad("l", $k, 4);
				//wierd but works, 3 equals
				$child = $xml_lists->addChild($node);
				$child->{0} = $new_value;
                $child->addAttribute("row_type",$row_type_1);
                $child->addAttribute("archive",0);
                $child->addAttribute("description",$new_description);
                //sort the xml, no easy way
                $xml_lists = sort_list($xml_lists);
                //update the xml table
                $main->update_xml($con, $xml_lists,"bb_create_lists");
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

//populate list for update
if (($main->post('bb_button', $module) == 2) && ($list_number_2 > 0)) 
    {
	$list_post = $main->pad("l", $list_number_2, 4);
	$list_output = ($list_number_2 > 0) ? chr($row_type_2 + 64) . $main->rpad($list_post) : "";
	$path = "//" . $list_post . "[@row_type = " . $row_type_2 . "]";
	$arr_list = $main->search_xml($xml_lists, $path);
	$xml_list = $arr_list[0];
	$update_list = (string)$xml_list;
	$update_description = (string)$xml_list['description'];
	array_push($arr_message, "Form has been populated for update with selected list.");
    }
elseif ($main->post('bb_button', $module) == 2)
	{
	array_push($arr_message, "Error: Unable to find list.");	
	}

//clear list for update
if ($main->post('bb_button', $module) == 3)
    {
    $update_list = "";
    $update_description = "";
	$list_output = "";
	array_push($arr_message, "Form has been cleared.");	
    }
    
//rename or update list    
if ($main->post('bb_button', $module) == 4)
    {
    if ($list_number_2 > 0)
        {    
        if ($main->full('update_list', $module))
            {
			$list_post = $main->pad("l", $list_number_2, 4);
			$path = "//" . $list_post . "[@row_type = " . $row_type_2 . "]";
			$arr_list = $main->search_xml($xml_lists, $path);
			if (count($arr_list == 1))
				{
				$xml_list = $arr_list[0];
                $xml_list->{0} = (string)$main->custom_trim_string($update_list, 50, true, true);
                $xml_list['description'] = (string)$main->custom_trim_string($update_description ,255);
                $xml_lists = sort_list($xml_lists);
                $main->update_xml($con, $xml_lists,"bb_create_lists");                
                $update_list = "";
                $update_description = "";
				$list_output = "";
                array_push($arr_message, "List successfully renamed.");
                }
            else
                {
                array_push($arr_message, "Error: Unable to rename list.");    
                }
            }
        else
            {
            array_push($arr_message, "Error: List name for update not supplied.");
            }
        }
    } //button 4 if    

//remove list
if (($main->post('bb_button', $module) == 5) && ($confirm_remove == 1))
    {
    if ($list_number_3 > 0)
        {        
        $list_post = $main->pad("l", $list_number_3, 4);
		$path = "//" . $list_post . "[@row_type = " . $row_type_3 . "]";
		$arr_list = $main->search_xml($xml_lists, $path);
		if (count($arr_list) == 1)
			{
			//reference parent, wierd but works
			$xml_list = $arr_list[0];
			unset($xml_list[0]);            
			//empty list_bit
			$query = "UPDATE data_table SET list_string = list_reset(list_string, " . $list_number_3 . ") WHERE list_retrieve(list_string, " . $list_number_3 . ") = 1 AND row_type IN (" . (int)$row_type_3 . ");";			
			$main->query($con, $query);	
			$main->update_xml($con, $xml_lists, "bb_create_lists");        
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
    } //button 5 if
elseif (($main->post('bb_button', $module) == 5) && ($confirm_remove <> 1))
	{
	array_push($arr_message, "Error: Please confirm to remove list.");	
	}
	
//archive or retrieve list    
if ($main->post('bb_button', $module) == 6)
    {
    if ($list_number_3 > 0)
        {
		$list_post = $main->pad("l", $list_number_3, 4);
		$path = "//" . $list_post . "[@row_type = " . $row_type_3 . "]";
		$arr_list = $main->search_xml($xml_lists, $path);
        if (count($arr_list) == 1) //underlying data could change, hopefully still there, multiuser problem
            {            
            $archive_flag = ($main->post('archive_value', $module) == 1) ? 1 : 0;
			$child = $arr_list[0];
            $child['archive'] = $archive_flag;
            $main->update_xml($con, $xml_lists,"bb_create_lists");
                
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
    } //button 5 if


/* BEGIN REQUIRED FORM */
echo "<p class=\"spaced bold larger\">Manage Lists</p>";

echo "<div class=\"spaced\">";
$main->echo_messages($arr_message);
echo "</div>";

$main->echo_form_begin();
$main->echo_module_vars($module);

//add lists
echo "<span class=\"spaced colored\">Add New List</span>";
echo "<div class=\"table border spaced\">";
echo "<div class=\"row padded\">";
echo "<div class=\"cell padded\">List Type: </div>";
echo "<div class=\"cell padded\">";
$params = array("class"=>"spaced");
$main->layout_dropdown($xml_layouts, "row_type_1", $row_type_1, $params);
echo "</div>";
echo "</div>";
echo "<div class=\"row padded\">";
echo "<div class=\"cell padded top\">List Name: </div>";
echo "<div class=\"cell padded\"><input class=\"spaced textbox\" name=\"new_value\" type=\"text\" /></div>";
echo "</div>";
echo "<div class=\"row padded\">";
echo "<div class=\"cell padded top\">Description: </div>";
echo "<div class=\"cell padded\"><textarea class=\"spaced\" rows\"4\" cols=\"60\" name=\"new_description\"></textarea></div>";
echo "</div>";
echo "<div class=\"row padded\">";
echo "<div class=\"cell padded\"></div>";
echo "<div class=\"cell padded\">";
$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>"New List");
$main->echo_button("add_list", $params);
echo "</div>";
echo "</div>";
echo "</div>";
echo "<br>";

//Rename List or Update Lists
echo "<span class=\"spaced colored\">Rename List, Update Description and Find List Number</span>";
echo "<div class=\"border table\">"; //border
echo "<div class=\"table spaced\">";
echo "<div class=\"row padded\">";
echo "<div class=\"cell padded\">";
$params = array("class"=>"spaced","onchange"=>"bb_reload_on_layout_2()");
$main->layout_dropdown($xml_layouts, "row_type_2", $row_type_2, $params);
$params = array("class"=>"spaced","empty"=>true,"archive"=>true);
$main->list_dropdown($xml_lists, "list_number_2", $list_number_2, $row_type_2, $params);
$params = array("class"=>"spaced","number"=>2,"target"=>$module, "passthis"=>true, "label"=>"Populate List");
$main->echo_button("populate_list", $params);
$params = array("class"=>"spaced","number"=>3,"target"=>$module, "passthis"=>true, "label"=>"Clear List");
$main->echo_button("clear_update", $params);
echo "</div>";
echo "</div>";
echo "</div>";
echo "<div class=\"spaced table padded\">";
echo "<div class=\"row padded\">";
echo "<div class=\"spaced cell padded\">List Number: </div>";
echo "<div class=\"cell padded\"><input class=\"spaced textbox\" name=\"list_output\" type=\"text\" value=\"" . htmlentities($list_output) . "\" readonly/></div>";
echo "</div>";
echo "<div class=\"row padded\">";
echo "<div class=\"spaced cell padded top\">List Name: </div>";
echo "<div class=\"cell padded\"><input class=\"spaced textbox\" name=\"update_list\" type=\"text\" value=\"" . htmlentities($update_list) . "\"/></div>";
echo "</div>";
echo "<div class=\"row padded\">";
echo "<div class=\"spaced cell padded\">Description: </div>";
echo "<div class=\"cell padded\"><textarea class=\"spaced\" rows\"4\" cols=\"60\" name=\"update_description\">" . $update_description . "</textarea></div>";
echo "</div>";
echo "<div class=\"row padded\">";
echo "<div class=\"cell padded\"></div>";
echo "<div class=\"cell padded\">";
$params = array("class"=>"spaced","number"=>4,"target"=>$module, "passthis"=>true, "label"=>"Update List");
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
$params = array("class"=>"spaced","onchange"=>"bb_reload_on_layout_3()");
$main->layout_dropdown($xml_layouts, "row_type_3", $row_type_3, $params);
$params = array("class"=>"spaced","empty"=>true,"archive"=>true);
$main->list_dropdown($xml_lists, "list_number_3", $list_number_3, $row_type_3, $params);
echo " | ";
echo "</div>";
echo "<div class=\"cell padded nowrap\">";
$params = array("class"=>"spaced","number"=>5,"target"=>$module, "passthis"=>true, "label"=>"Remove List");
$main->echo_button("remove_list", $params);
echo "<span class = \"spaced border rounded padded shaded\">";
echo "<label class=\"padded\">Confirm Remove: </label><input class=\"middle padded\" type=\"checkbox\" name=\"confirm_remove\" value =\"1\" />";
echo "</span>";
echo " | ";
echo "</div>";
echo "<div class=\"cell padded nowrap\">";
$params = array("class"=>"spaced","number"=>6,"target"=>$module, "passthis"=>true, "label"=>"Archive/Retrieve List");
$main->echo_button("archive_list", $params);
echo "</div>";
echo "<div class=\"cell padded nowrap\"><input class=\"spaced middle\" name=\"archive_value\" value=\"1\" type=\"checkbox\" />";
echo "<span class=\"spaced\">Check to Archive/Uncheck to Retrieve</span></div>";
echo "</div>";
echo "</div>";

$main->echo_state($array_state);
$main->echo_form_end();
/* END FORM */
?>

