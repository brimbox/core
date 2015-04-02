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
$main->check_permission("bb_brimbox", array(3,4,5));
?>
<script  type="text/javascript">
//clear the textarea dump	
function bb_clear_textarea()
	{
    document.forms["bb_form"].dump_area.value = "";
	return false;
	}
</script>

<?php
/* INITIALIZE */
//find default row_type, $arr_layouts must have one layout set
$arr_layouts = $main->get_json($con, "bb_layout_names");
$arr_layouts_reduced = $main->filter_keys($arr_layouts);
$default_row_type = $main->get_default_layout($arr_layouts_reduced);

$arr_relate = array(41,42,43,44,45,46);
$arr_file = array(47);
$arr_reserved = array(48);
$arr_notes = array(49,50);

//message pile
$arr_message = array();

/* START STATE AND DETAILS POSTBACK */
//do details postback, get variables from state
$main->retrieve($con, $array_state);

//get archive mode
$mode = ($archive == 1) ? " 1 = 1 " : " archive IN (0)";
  
//get details state variables are set use them
$arr_state = $main->load($module, $array_state);

//coming from an add or edit link, reset $arr_state, row_type and post key should be positive
if (!empty($_POST['bb_row_type']))
        {
        //global post variables		
		$row_type = $main->set('row_type', $arr_state, $_POST['bb_row_type']);
		$post_key = $main->set('post_key', $arr_state, $_POST['bb_post_key']);
		$link_values = $main->set('link_values', $arr_state, "");
        }		
else //default = nothing, or populate with input_state if coming from other page
        {
        //local post variables
		$row_type = $main->process('row_type', $module, $arr_state, $default_row_type);
		$post_key = $main->process('post_key', $module, $arr_state, 0);
		$link_values = $main->process('link_values', $module, $arr_state, "");
        }
           
$main->update($array_state, $module, $arr_state);

/*** END DETAILS POSTBACK ***/		
?>
<?php

$text_str = "";
	
if ($post_key > 0) // a detail of a record
	{
	//post_key is an int, row_type to find record id
	$letter = strtoupper(chr($row_type + 96));
	$query = "SELECT count(*) OVER () as cnt, * FROM data_table WHERE id = " . $post_key . ";";
	
	//echo "<p>" . $query . "</p>";
	$result = $main->query($con, $query);

	//this outputs the return stats
	$main->return_stats($result);
	//get row, check cnt for existance, echo out details
    $cnt_rows = pg_num_rows($result);
    
    if ($cnt_rows == 1)
        {
        $row = pg_fetch_array($result);
                
        //get columns for details row_type
        $arr_columns = $main->get_json($con, "bb_column_names");
        $arr_column = $arr_columns[$row_type];
        $arr_layout =  $arr_layouts_reduced[$row_type];
    
         //call to function that outputs details
        echo "<p class =\"spaced\">Record: " . $letter . $post_key . " - " . htmlentities((string)$arr_layout['singular']) . "</p>";
        //strip non-integer keys
        $arr_column = $main->filter_keys($arr_column);
        /* return the details */
        foreach($arr_column as $key => $value)
            {
            $col2 = $main->pad("c", $key);  
            if (in_array($key, $arr_notes)) //notes
                {
                $str_details = str_replace("\n", "<br>",  htmlentities($row[$col2]));
                echo "<div class = \"clear\"><label class = \"spaced left floatleft overflow medium shaded\">" . htmlentities($value['name']) . ":</label>";
                echo "<div class = \"clear\"></div>";
                //double it up for emheight
                echo "<div class=\"spaced border half\">";
                echo "<div class=\"spaced emheight\">" . $str_details . "</div></div>";				
                echo "</div>";
                }
            elseif (in_array($key, $arr_file)) //files
                {
                echo "<div class=\"clear\"><label class=\"spaced right overflow floatleft medium shaded\">" . htmlentities($value['name']) . ":</label>";
                echo "<button class=\"link spaced left floatleft\" onclick=\"bb_submit_object('bb-links/bb_object_file_link.php'," . $post_key . ")\">" . htmlentities($row[$col2]) . "</button>";
                echo "</div>";
                }
            else //regular
                {
                echo "<div class=\"clear\"><label class=\"spaced right overflow floatleft medium shaded\">" . htmlentities($value['name']) . ":</label>";
                echo "<div class=\"spaced left floatleft\">" . htmlentities($row[$col2]) . "</div>";
                echo "</div>";
                }
            }
        }
    /* end return the details */
	
    /* get the dump into a string for texterea */
	if ($main->button(2))
		{
       	$text_str = ""; 	
        foreach($arr_column as $key => $value)
            { 	 
            $col2 = $main->pad("c", $key);     	
            $text_str .= $row[$col2] . PHP_EOL;	 	
            }
		}
    /* end dump */
        
    //link records area       
    if ($main->button(1))
        {
        //intialize		
		$arr_link_row_type = array(); //will be empty if either unlinkable or empty link_values
        $link_values = preg_replace('/\s+/', '', $link_values);        
		if (empty($link_values)) //check if link_values is empty
			{
			array_push($arr_message, "Error: No values supplied.");	
			}
		else //check to see if record is linkable
			{
			foreach($arr_layouts_reduced as $key => $value)
				{
				if ($row_type == $value['parent'])
					{
					array_push($arr_link_row_type, $key);                
					}
				}
			 if (empty($arr_link_row_type))
				{
				array_push($arr_message, "Error: Cannot link records to this type of record.");
				}
			}        
        
        //run link values
		if (!empty($arr_link_row_type)) //linkable records
			{
			//check for valid numbers
			$arr_to_link = explode(",", $link_values);
			$arr_to_link_value = array();
			$arr_to_link_int = array();
			$arr_not_valid_value = array();
			foreach ($arr_to_link as $key => $value)
				{
				$valid_id = false;
				if (preg_match("/^[A-Za-z]\d+/", $value))
					{
					//take off integer for test
					$id = substr($value,1);
					if (filter_var($id, FILTER_VALIDATE_INT))
						{
						//preserve key, proper id form
						$arr_to_link_value[$key] = $value;
						//this is the ids that are in proper form
						$arr_to_link_int[$key] = $id;
						$valid_id = true;	
						}
					}
				if (!$valid_id)
					{
					//preserve key, ids not in proper form
					$arr_not_valid_value[$key] = $value;
					}
				}
			
			//this wil check the ids to be updated have matching row_type/id pairs
			//everything will come out in the wash after the update
			$arr_record_ids = array();
			if (!empty($arr_to_link_value))
				{
				$arr_union_query = array();
				foreach ($arr_to_link_value as $value)
					{
					$row_type_link = ord(strtolower(substr($value, 0, 1))) - 96;
					$id = substr($value,1);
					$str_union_query = "SELECT id FROM data_table WHERE id = " . $id . " AND row_type = " . $row_type_link;
					array_push($arr_union_query, $str_union_query);
					}
				$query = implode(" UNION ", $arr_union_query);
				//echo "<p>" . $query . "</p>";
				$result = $main->query($con,$query);
				//fetch valid ids with proper row_type
				$arr_record_ids = pg_fetch_all_columns($result, 0);
				}
				
			//this will come from valid records, post key should always be positive
			$link_values = !empty($arr_record_ids) ? implode(",",$arr_record_ids) : "-1";
			//this will come from valid layouts
			$str_link_row_type = implode(",", $arr_link_row_type);  			
			   
			//no need for pg_escape string on link_values because of regular expression
			//update with returning clause, cannot link to archived records
			$query = "UPDATE data_table SET key1 = " . (int)$post_key . " " .
					 "WHERE id IN (" . $link_values . ") AND row_type IN (" . $str_link_row_type . ") " .
					 "AND archive IN (0) AND EXISTS (SELECT 1 FROM data_table WHERE id IN (" . $post_key . ")) RETURNING id;";
			//echo "<p>" . $query . "</p>";
			$result = $main->query($con,$query);
			//fetch updated rows
			$arr_linked_int = pg_fetch_all_columns($result, 0);
			//get rows not linked gased on int values
			$arr_not_linked_int = array_diff($arr_to_link_int, $arr_linked_int);
			
			//get values not linked using the keys
			$arr_linked = array_diff_key($arr_to_link_value, $arr_not_linked_int);
			$arr_not_linked = array_intersect_key($arr_to_link_value, $arr_not_linked_int);
			
			//into strings for messages
			$str_linked = implode(", ", $arr_linked);
			$str_not_linked = implode(", ",$arr_not_linked);
			$str_not_valid_value =  implode(", ",$arr_not_valid_value);
			
			//messages
			//none linked
			if (empty($arr_linked))
				{
				array_push($arr_message, "No Records were linked.");
				$link_values = "";
				}
			//linked
			else 
				{
				array_push($arr_message, "Record(s) " . htmlentities($str_linked) . " were linked to record " . $letter . (string)$post_key);   
				$link_values = "";
				}
			//not linked
			if (!empty($arr_not_linked))
				{
				array_push($arr_message, "Record(s) " . htmlentities($str_not_linked) . " were not linked.");
				$link_values = "";
				}
			if (!empty($arr_not_valid_value))
				{
				array_push($arr_message, "Value(s) " . htmlentities($str_not_valid_value) . " were not valid records and were not linked.");
				$link_values = "";
				}
            }//not empty link row type
		$link_values = "";
        }//end link records area, link button set        
	}// post key set


/* BEGIN REQUIRED FORM */
$main->echo_form_begin();
$main->echo_module_vars();
 
//message and textarea align left
echo "<div class=\"left\">";
if (($post_key > 0) && ($cnt_rows == 1))
    {
    echo "<div class = \"clear\"></div>";
    echo "<br>";    
    echo "<div class = \"clear\"></div>";   
    echo "<p class =\"spaced\">Link these records to this record</p>";
    echo "<input type=\"text\" name=\"link_values\" class =\"spaced\" size=\"50\" value=\"" . htmlentities($link_values) . "\" />";
    echo "<div class = \"clear\"></div>";
	echo "<div class = \"spaced\">";
    $main->echo_messages($arr_message);
	echo "</div>";
	$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>"Link Values");
	$main->echo_button("link_button", $params);
    echo "<div class = \"clear\"></div>";
    echo "<br>";
    echo "<textarea class=\"spaced\" name=\"dump_area\"rows=\"8\" cols=\"80\">" . $text_str . "</textarea>";
    echo "<div class = \"clear\"></div>";
	$params = array("class"=>"spaced","number"=>2,"target"=>$module, "passthis"=>true, "label"=>"Dump Data");
	$main->echo_button("dump_button", $params);
	$params = array("class"=>"spaced","onclick"=>"bb_clear_textarea();", "label"=>"Clear");
	$main->echo_script_button("dump_clear", $params);
    }

//row_type and post key
echo "<input type=\"hidden\" name=\"row_type\" value=\"" . $row_type . "\" />";
echo "<input type=\"hidden\" name=\"post_key\" value=\"" . $post_key . "\" />";

echo "</div>";
$main->echo_object_vars();
$main->echo_state($array_state);
$main->echo_form_end();
/* END FORM */
?>


