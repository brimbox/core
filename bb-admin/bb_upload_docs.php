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
function bb_set_hidden(id)
    {
    //set var form links and the add_new_user button on initial page
    var frmobj = document.forms["bb_form"];
    
    frmobj.id.value = id;
    bb_submit_form(2);
    return false;
    }

</script>

<?php
/* PRESERVE STATE */
$main->retrieve($con, $array_state);

$arr_message = array();	

//submit file to textarea	
if ($main->button(1)) //submit_file
	{
	if (is_uploaded_file($_FILES[$main->name('upload_file', $module)]["tmp_name"]))
		{
        $filename = $main->custom_trim_string($_FILES[$main->name('upload_file', $module)]["name"], 255);
        $query = "INSERT INTO docs_table (document, filename, username, level) " .
                 "SELECT $1, $2, $3, $4 WHERE NOT EXISTS (SELECT 1 FROM docs_table WHERE filename = '" . pg_escape_string($filename) . "')";
        $arr_params = array(pg_escape_bytea(file_get_contents($_FILES[$main->name('upload_file', $module)]["tmp_name"])), $filename, $username, 0);
        $result = $main->query_params($con, $query, $arr_params);
        if (pg_affected_rows($result) == 1)
            {
            array_push($arr_message, "Document has been stored.");
            }
        else
            {
            array_push($arr_message, "Document not stored. Possible duplicate file name.");   
            }
		}
	else
		{
		array_push($arr_message, "Must specify file name.");
		}
	}
if ($main->button(2)) //submit_file
	{
    $id = $main->post("id",$module,0);
	if ($id > 0)
		{        
        $query = "DELETE FROM docs_table WHERE id = " . $id . ";";
        $main->query($con, $query);
        array_push($arr_message, "Document has been deleted.");
		}
	else
		{
		array_push($arr_message, "Unable to delete.");
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
$main->echo_module_vars();
$main->echo_object_vars();
//upload row_type calls dummy function

echo "<div class=\"spaced border floatleft padded\">";
echo "<label class=\"spaced\">Filename: </label>";
echo "<input class=\"spaced\" type=\"file\" name=\"upload_file\" id=\"file\" />";
$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>"Upload File");
$main->echo_button("submit_file", $params);

echo "<input name=\"id\" type=\"hidden\" value=\"\" />";

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
    echo "<div class=\"padded bold cell short middle\">Id</div>";
    echo "<div class=\"padded bold cell long middle\">Filename</div>";
    echo "<div class=\"padded bold cell long middle\">Username</div>";
    echo "<div class=\"padded bold cell short middle\">Level</div>";
    echo "<div class=\"padded bold cell medium middle\">Timestamp</div>";
    echo "<div class=\"padded bold cell long middle\">Action</div>";
    echo "</div>";

    //table rows
    $i = 0;
    while($row = pg_fetch_array($result))
        {
        //row shading
        $shade_class = ($i % 2) == 0 ? "even" : "odd";
        echo "<div class=\"row " . $shade_class . "\">";
        echo "<div class=\"padded cell short middle\">" . $row['id'] . "</div>";
        echo "<div class=\"padded cell medium middle\">" . $row['filename'] . "</div>";
        echo "<div class=\"padded cell long middle\">" . $row['username'] . "</div>";
        echo "<div class=\"padded cell short middle\">" . $row['level'] . "</div>";
        $date = $main->convert_date($row['change_date'],"Y-m-d h:i:s");
        echo "<div class=\"padded cell medium middle\">" . $date . "</div>";
        echo "<div class=\"padded cell long middle\">";
        $onclick = "bb_set_hidden(" . $row['id'] . ")";
        $main->echo_script_button("delete_" . $row['id'], array('class'=>"link spaced", "onclick"=>$onclick, 'label'=>"Delete"));
        echo " - ";
        $onclick = "bb_submit_object('bb-links/bb_object_document_link.php', " . $row['id'] . ")";
        $main->echo_script_button("download_" . $row['id'], array('class'=>"link spaced", "onclick"=>$onclick, 'label'=>"Download"));
        echo "</div>";
        echo "</div>";
        $i++;
        }
echo "</div>";
echo "</div>";

/* END DISPLAY DOCS */

?>
