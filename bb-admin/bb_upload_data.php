<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
Copyright (C) 2012 - 2013  Kermit Will Richardson, Brimbox LLC

This program is free software: you can redistribute it and/or modifyit under the terms of the GNU
General Public License Version 3 (“GNU GPL v3”) as published by the Free Software Foundation. 

This program is distributed in the hope that it will be useful,but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU GPL v3 for more details. 

You should have received a copy of the GNU GPL v3 along with this program.
If not, see http://www.gnu.org/licenses/
*/
?>
<?php
$main->check_permission("bb_brimbox", array(4,5));
?>
<script type="text/javascript">     
function dump_data()
    {
    var frmobj = document.forms["bb_form"];
    
    frmobj.action = "bb-links/bb_upload_data_link.php";
    frmobj.submit();
	return false;
    }
function reload_on_layout()
    {
    //change row_type, reload appropriate columns
    //this goes off when row_type is changed    
    var frmobj = document.forms["bb_form"];
    
    bb_submit_form(); //call javascript submit_form function
	return false;
    }
</script>

<?php
/* PRESERVE STATE */
$main->retrieve($con, $array_state);

$arr_message = array();	
$arr_notes = array("49","50");

//start of code
function check_header($arr_column_reduced, $str, $parent)
	{
    $arr_row = explode("\t", $str);
    $i = 0;	
    if ($parent <> 0)
        {
        if (strtolower($arr_row[0]) <> "link")
            {
            return false;
            }
        $i = 1;
        }   
	foreach($arr_column_reduced as $value)
		{
		if (strtolower($value['name']) <> strtolower($arr_row[$i]))
			{
			return false;
			}
		$i++;
		}
	return true;
	}
		
//get layouts
$arr_layouts = $main->get_json($con, "bb_layout_names");
$default_row_type = $main->get_default_layout($arr_layouts);

//will handle postback
$row_type = $main->post('row_type', $module, $default_row_type); 
$data = $main->post('bb_data_area', $module);
$data_file = $main->post('bb_data_file_name', $module, "default");

//get column names based on row_type/record types
$arr_columns = $main->get_json($con, "bb_column_names");
$arr_layout = $arr_layouts[$row_type];
$parent = $arr_layout['parent']; 
$arr_column = $arr_columns[$row_type];
$arr_column_reduced = $main->filter_keys($arr_column);
//get dropdowns for validation
$arr_dropdowns = $main->get_json($con, "bb_dropdowns");

if ($main->button(1)) //get column names for layout
	{
    if ($parent == 0)
        {
        $arr_implode = array();    
        }
    else
        {
        $arr_implode = array("Link");    
        }
    foreach ($arr_column_reduced as $value)
        {
        array_push($arr_implode, $value['name']); 
        }
    $data = implode("\t", $arr_implode) . PHP_EOL;
	}


//submit file to textarea	
if ($main->button(2)) //submit_file
	{
	if (!empty($_FILES[$main->name('upload_file', $module)]["tmp_name"]))
		{
		$data = file_get_contents($_FILES[$main->name('upload_file', $module)]["tmp_name"]);
		}
	else
		{
		$message = "Must specify file name.";
		}
	}

//post data to database	
if ($main->button(3)) //submit_data
    {
    $arr_lines = explode(PHP_EOL, trim($data));
    $cnt = count($arr_lines);
    
    $unique_key = isset($arr_column['key']) ? $arr_column['key'] : "";
    
    //check header checks that the first line of data matches $xml_column
    //$arr_lines may need trim function
	if (check_header($arr_column_reduced, trim($arr_lines[0]), $parent))
		{
        $data = "";
        //$i counts the current number of columns and is used to set up query params
        //$j is the number of rows of data, 0 is header row, 1 starts data
        //$m is the unique key position in the line array
        //$l is the position in line array when validating
        //$k is used when line is short, to add empty array vars
		
        $i = 1;		
		foreach ($arr_column_reduced as $key => $value)
			{
			//used later $i is number of columns  
            //also find unique value/key index
            if ($key == $unique_key)
                {
                $m = $i;
                }
            $i++;		 
			}	
        
        /* START LOOP */
        //loops through each row of data
        $a = 0; //count of rows entered
        for ($j=1; $j<$cnt; $j++)
			{
            //bad line boolean
            $line_error = false;
            //trim and add key1 if no link
            $line = ($parent == 0) ? "-1" . "\t" . trim($arr_lines[$j]) : $line = trim($arr_lines[$j]);
			
            //put data row into array            
			$arr_line = explode("\t", $line);
            
            if (count($arr_line) > $i)
                {
                //line too long
                $line_error = true;
                }    
            else
                {
                //add onto short line
                for ($k=count($arr_line); $k<$i; $k++) 
                    {
                    //if a line is shorter than a 
                    $arr_line[$k] = ""; //tricky, k happens to be the array value, one less
                    }
                //continue inside else
                //validate key1 if link is set                
                if (!ctype_digit($arr_line[0]) && ($parent <> 0))
                    {
                    //check that link is int
                    $line_error = true;
                    }
                else //another else
                    {                
                    $l = 1;
                    foreach($arr_column_reduced as $key => $value)
                        {
                        /* START VALIDATION */
                        $type = $value['type'];
                        $required_flag = $value['required'] == 1 ? true : false;       
                        //actual database column name
                        if (in_array($key,$arr_notes))
                            {
                            //no validation
                            $arr_line[$l] = $main->custom_trim_string($arr_line[$l], 65536, false);
                            } 
                        else //validate, another else                       
                            {
                            //regular column
                            $arr_line[$l] = $main->custom_trim_string($arr_line[$l],255);
                            $return_required = false; //boolean false for not required
                            $return_validate = false; //why not initialize                            
                            //check required field  
                            if ($required_flag)
                                {
                                $return_required = $main->validate_required($arr_line[$l], true);    
                                }
                            //populated string = error
                            //boolean true is populated
                            if (!is_bool($return_required))
                                {
                                $line_error = true;
                                break;
                                }
                            else  //another else
                                {
                                //standard validatation on non empty rows
								//empty row always valid in this sense
								if (!$main->blank($arr_line[$l]))
									{
									$return_validate = $main->validate_logic($type, $arr_line[$l], true);
									//string is error
									//$arr_line[$l] passed as a reference and may change
									if (!is_bool($return_validate))
										{
										$line_error = true;
										break;
                                        }
									}
                                //dropdown validation could could check for empty value
                                //not used in input routine, could both validate on dropdown and type
                                if (isset($arr_dropdowns[$row_type][$key]))
                                    {
                                    $arr_dropdown = $arr_dropdowns[$row_type][$key];
                                    $return_validate = $main->validate_dropdown($arr_line[$l], $arr_dropdown, true);
                                    if (!is_bool($return_validate))
                                        {
                                        $line_error = true;
                                        break;   
                                        }
                                    }
                                } //if required
                            } //end validate and required                         
                        $l++;
                        }//loop through columns and line
                    } //check that key is integer
                } //line too long
				
             if (!$line_error)
                {
                $owner = $main->custom_trim_string($email,255);
                $post_key = $arr_line[0];
                $insert_clause = "row_type, key1, owner_name, updater_name";
                $select_clause = $row_type . " as row_type, " . $post_key . " as key1, '" . $owner . "' as owner_name, '" . $owner . "' as updater_name";
    
                $arr_ts_vector_fts = array();
                $arr_ts_vector_ftg = array();
                $i = 1;
                foreach($arr_column_reduced as $key => $child)
                    {
                    $col = $main->pad("c",$key);
                    $str = pg_escape_string($arr_line[$i]);
                    $insert_clause .= "," . $col;
                    $select_clause .= ", '" . $str . "'";
                    $search_flag = ($child['search'] == 1) ? true : false;
					//guest flag
					if (empty($array_guest_index))
						{
						$guest_flag = (($child['search'] == 1) && ($child['secure'] == 0)) ? true : false;
						}
					else
						{
						$guest_flag = (($child['search'] == 1) && in_array((int)$child['secure'], $array_guest_index)) ? true : false;						
						}
					//build fts SQL code
                    if ($search_flag)
                        {
                        array_push($arr_ts_vector_fts, "'" . $str . "' || ' ' || regexp_replace('" . $str . "', E'(\\\\W)+', ' ', 'g')");
                        }
                    if ($guest_flag)
                        {
                        array_push($arr_ts_vector_ftg, "'" . $str . "' || ' ' || regexp_replace('" . $str . "', E'(\\\\W)+', ' ', 'g')");
                        }
                    $i++;
                    }		
                
                $str_ts_vector_fts = !empty($arr_ts_vector_fts) ? implode(" || ' ' || ", $arr_ts_vector_fts) : "''";
                $str_ts_vector_ftg = !empty($arr_ts_vector_ftg) ? implode(" || ' ' || ", $arr_ts_vector_ftg) : "''";
                
                $insert_clause .= ", fts, ftg, secure, archive ";
                $select_clause .= ", to_tsvector(" . $str_ts_vector_fts . ") as fts, to_tsvector(" . $str_ts_vector_ftg . ") as ftg, ";
                $select_clause .= "CASE WHEN (SELECT  coalesce(secure,0) FROM data_table WHERE id IN (" . $post_key . ")) > 0 THEN (SELECT secure FROM data_table WHERE id IN (" . $post_key . ")) ELSE 0 END as secure, "; 
                $select_clause .= "CASE WHEN (SELECT  coalesce(archive,0) FROM data_table WHERE id IN (" . $post_key . ")) > 0 THEN (SELECT archive FROM data_table WHERE id IN (" . $post_key . ")) ELSE 0 END as archive ";
               
                $select_where_exists = "SELECT 1";
                $select_where_not = "SELECT 1 WHERE 1 = 0";
                //key exists must check for duplicate value
                if (isset($arr_column['layout']['unique']))
                    {
                    $unique_key = $arr_column['layout']['unique'];
                    $unique_column = $main->pad("c", $unique_key);
                    $unique_value = isset($arr_state[$unique_column]) ? $arr_state[$unique_column] : "";
                    //key, will not insert on empty value, key must be populated
                    if ($unique_value <> "")
                        {
                        $select_where_not = "SELECT 1 FROM data_table WHERE row_type IN (" . $row_type . ") AND lower(" . $unique_column . ") IN (lower('" . $unique_value . "'))";
                        }
                    else
                        {
                        $select_where_not = "SELECT 1";	
                        }
                    }            
                 //parent row has been deleted, multiuser situation, check on insert
                if ($post_key > 0)
                    {
                    $select_where_exists = "SELECT 1 FROM data_table WHERE id IN (" . $post_key . ") AND row_type IN (" . $parent . ")";
                    }
                //main query
                $query = "INSERT INTO data_table (" . $insert_clause	. ") SELECT " . $select_clause . " WHERE NOT EXISTS (" . $select_where_not . ") AND EXISTS (" . $select_where_exists . ");";
                //echo "<p>" . $query . "</p>";
                //print_r($arr_insert);
                $result = $main->query($con, $query);
                if (pg_affected_rows($result) == 1)
                    {
                    $a++;
                    }
                else
                    {
                    array_push($arr_message,"Error: Some rows returned because of duplicate keys or invalid links.");
                    $data .= $arr_lines[$j] . PHP_EOL;
                    }                  		
                } //else insert row
            else
                {
                //validation error
                array_push($arr_message,"Error: Some rows returned because data not validated or required values missing."); 
                $data .= $arr_lines[$j] . PHP_EOL;   
                }	
			} /* END FOR LOOP */
			
        if (!empty($data))
            {
            $data = $arr_lines[0] . PHP_EOL . $data;
            }
        if ($a > 0)
            {
            array_push($arr_message, $a ." row(s) entered into the database.");
            }
		}        
	else
		{
		array_push($arr_message,"Error: Header row does not match the column names of layout chosen.");
		}
	}

//title
echo "<p class=\"spaced bold larger\">Upload Data</p>";

$arr_message = array_unique($arr_message);
echo "<div class=\"spaced\">";
$main->echo_messages($arr_message);
echo "</div>";


/* START REQUIRED FORM */
$main->echo_form_begin(array("type"=>"enctype=\"multipart/form-data\""));
$main->echo_module_vars();;

//upload row_type calls dummy function
echo "<div class=\"spaced border floatleft padded\">";
$params = array("class"=>"spaced","onchange"=>"reload_on_layout()");
$main->layout_dropdown($arr_layouts, "row_type", $row_type, $params);
$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>"Get Upload Header");
$main->echo_button("get_header", $params);
echo "</div>";
echo "<div class=\"clear\"></div>";
echo "<div class=\"spaced border floatleft padded\">";
echo "<label class=\"spaced\">Filename: </label>";
echo "<input type=\"text\" name=\"bb_data_file_name\" value=\"" . $data_file . "\" class=\"spaced\">";
echo "<input type=\"hidden\" name=\"bb_data_file_extension\" value=\"txt\" class=\"spaced\">";
$params = array("class"=>"spaced","onclick"=>"dump_data()", "label"=>"Download Data Area");
$main->echo_script_button("dump_button", $params);
echo "</div>";
echo "<div class=\"clear\"></div>";
echo "<div class=\"spaced border floatleft padded\">";
echo "<label class=\"spaced\">Filename: </label>";
echo "<input class=\"spaced\" type=\"file\" name=\"upload_file\" id=\"file\" />";
$params = array("class"=>"spaced","number"=>2,"target"=>$module, "passthis"=>true, "label"=>"Upload File");
$main->echo_button("submit_file", $params);
$label = "Post " . $arr_layout['plural'];
$params = array("class"=>"spaced","number"=>3,"target"=>$module, "passthis"=>true, "label"=>$label);
$main->echo_button("submit_data", $params);
echo "</div>";
echo "<div class=\"clear\"></div>";
echo "<textarea class=\"spaced\" name=\"bb_data_area\" cols=\"80\" rows=\"25\" wrap=\"off\">" . $data . "</textarea>";

$main->echo_state($array_state);
$main->echo_form_end();
/* END FORM */
?>
