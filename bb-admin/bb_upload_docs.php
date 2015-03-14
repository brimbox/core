<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
Copyright (C) 2012 - 2015  Kermit Will Richardson, Brimbox LLC

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
function bb_submit_link(f)
    {
    var frmobj = document.forms["bb_form"];
    
    frmobj.action = f;
    frmobj.submit();
	return false;
    }
function bb_reload()
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
$arr_layouts_reduced = $main->filter_keys($arr_layouts);
$default_row_type = $main->get_default_layout($arr_layouts_reduced);
//get guest index
$arr_header = $main->get_json($con, "bb_interface_enabler");
$arr_guest_index = $arr_header['guest_index']['value'];

//will handle postback
$row_type = $main->post('row_type', $module, $default_row_type); 
$data = $main->post('bb_data_area', $module);
$data_file = $main->post('bb_data_file_name', $module, "default");

//get column names based on row_type/record types
$arr_columns = $main->get_json($con, "bb_column_names");
$arr_layout = $arr_layouts_reduced[$row_type];
$parent = $arr_layout['parent']; 
$arr_column = $arr_columns[$row_type];
$arr_column_reduced = $main->filter_keys($arr_column);
//get dropdowns for validation
$arr_dropdowns = $main->get_json($con, "bb_dropdowns");

//submit file to textarea	
if ($main->button(1)) //submit_file
	{
	if (is_uploaded_file($_FILES[$main->name('upload_file', $module)]["tmp_name"]))
		{
        $query = "INSERT INTO docs_table (document, filename, username, level) " .
                 "VALUES ($1, $2, $3, $4)";
        $arr_params = array(pg_escape_bytea(file_get_contents($_FILES[$main->name('upload_file', $module)]["tmp_name"])), $_FILES[$main->name('upload_file', $module)]["name"], $username, 0);
        $main->query_params($con, $query, $arr_params);
		}
	else
		{
		$message = "Must specify file name.";
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
echo "<label class=\"spaced\">Filename: </label>";
echo "<input class=\"spaced\" type=\"file\" name=\"upload_file\" id=\"file\" />";
$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>"Upload File");
$main->echo_button("submit_file", $params);

$main->echo_state($array_state);
$main->echo_form_end();
/* END FORM */

/* DISPLAY DOCS */
//get all logins, order by date DESC  
$query = "SELECT * FROM docs_table ORDER BY change_date DESC;";
$result = $main->query($con, $query);

//title
echo "<p class=\"spaced bold larger\">Documents List</p>";

//div container with scroll bar
echo "<div class=\"spaced padded border logwrap\">";
echo "<div class=\"table padded\">";

    //table header
    echo "<div class=\"row shaded\">";
    echo "<div class=\"padded bold cell medium middle\">Id</div>";
    echo "<div class=\"padded bold cell medium middle\">Filename</div>";
    echo "<div class=\"padded bold cell long middle\">Username</div>";
    echo "<div class=\"padded bold cell long middle\">Level</div>";
    echo "<div class=\"padded bold cell long middle\">Timestamp</div>";
    echo "<div class=\"padded bold cell long middle\">Action</div>";
    echo "</div>";

    //table rows
    $i = 0;
    while($row = pg_fetch_array($result))
        {
        //row shading
        $shade_class = ($i % 2) == 0 ? "even" : "odd";
        echo "<div class=\"row " . $shade_class . "\">";
        echo "<div class=\"padded cell medium middle\">" . $row['id'] . "</div>";
        echo "<div class=\"padded cell medium middle\">" . $row['filename'] . "</div>";
        echo "<div class=\"padded cell long middle\">" . $row['username'] . "</div>";
        echo "<div class=\"padded cell long middle\">" . $row['level'] . "</div>";
        $date = $main->convert_date($row['change_date'],"Y-m-d h:i:s.u"); 
        echo "<div class=\"padded cell long middle\">" . $date . "</div>";
        echo "<div class=\"padded cell long middle\">Delete</div>";
        echo "</div>";
        $i++;
        }
echo "</div>";
echo "</div>";

/* END DISPLAY DOCS */

?>
