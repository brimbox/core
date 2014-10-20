<?php if (!defined('BASE_CHECK')) exit(); ?>
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
<style type="text/css">
/* MODULE CSS */
/* no colors in css */
.logwrap
	{
	height:400px;
	overflow-y:scroll;
	}

/* END CSS */
</style>

<?php
$main->check_permission("bb_brimbox", 5);
?>
<?php
/* PRESERVE STATE */
$main->retrieve($con, $array_state);

//This area is for truncating the table
if ($main->button(1)) //truncate_table
    {
    $truncate_option = (int)$main->post('truncate_option', $module);
    //switch based on select option
    switch ($truncate_option)
        {
        case 0:
            $interval = "1 day";
            break;
        case 1:
            $interval = "1 week";
            break;
        case 2:
            $interval = "1 month";
            break;
        }
   //delete query
   $query = "DELETE FROM log_table WHERE change_date + interval '" . $interval . "' < now();";
   $main->query($con, $query);
   
   //reset the the table identity upon full truncation
    if ($truncate_option == 3)
        {
        //reset serial id upon truncation
        $query = "SELECT setval('login_table_id_seq', (SELECT max(id) + 1 from login_table));";
        $main->query($con, $query);       
        }
    }

//get all logins, order by date DESC  
$query = "SELECT * FROM log_table ORDER BY change_date DESC;";
$result = $main->query($con, $query);

//title
echo "<p class=\"spaced bold larger\">Manage Log</p>";

//div container with scroll bar
echo "<div class=\"spaced padded border logwrap\">";
echo "<div class=\"table padded\">";

    //table header
    echo "<div class=\"row shaded\">";
    echo "<div class=\"padded bold cell medium middle\">Email/Username</div>";
    echo "<div class=\"padded bold cell medium middle\">IP Address/Bits</div>";
    echo "<div class=\"padded bold cell medium middle\">Login Date/Time</div>";
    echo "<div class=\"padded bold cell medium middle\">Action</div>";
    echo "</div>";

    //table rows
    $i = 0;
    while($row = pg_fetch_array($result))
        {
        //row shading
        $shade_class = ($i % 2) == 0 ? "even" : "odd";
        echo "<div class=\"row " . $shade_class . "\">";
        echo "<div class=\"padded cell medium middle\">" . $row['email'] . "</div>";
        echo "<div class=\"padded cell medium middle\">" . $row['ip_address'] . "</div>";
        $date = $main->convert_date($row['change_date'],"Y-m-d h:i:s.u"); 
        echo "<div class=\"padded cell medium middle\">" . $date . "</div>";
        echo "<div class=\"padded cell medium middle\">" . $row['action'] . "</div>";
        echo "</div>";
        $i++;
        }
echo "</div>";
echo "</div>";

/* BEGIN REQUIRED FORM */
$main->echo_form_begin();
$main->echo_module_vars();;

//truncate_option select tag
$arr_options = array("Preserve 1 Day", "Preserve 1 Week", "Preserve 1 Month");
echo "<select class=\"spaced\" name=\"truncate_option\">";
foreach ($arr_options as $key => $value)
    {
    echo "<option value=\"" . $key. "\">" . $value . "</option>";    
    }
echo "</select>";

//submit button
$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>"Truncate Log");
$main->echo_button("truncate_table", $params);

$main->echo_state($array_state);
$main->echo_form_end();
/* END FORM */
?>

