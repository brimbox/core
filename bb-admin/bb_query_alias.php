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
?>
<script type="text/javascript">     
function bb_reload()
    {
    //set var form links and the add_new_user button on initial page
    var frmobj = document.forms["bb_form"];

    bb_submit_form();
    return false;
    }
</script>
<?php
/* PRESERVE STATE */
$arr_messages = array();

$POST = $main->retrieve($con);

//get state from db
$arr_state = $main->load($con, $module);

//check that there are enough form areas for sub_queries
$number_sub_queries =  $main->process("number_sub_queries", $module, $arr_state, 1);

//process the form
$arr_sub_queries = array();
for ($i=1;$i<=10; $i++)
    {
    $name = $main->pad("s",$i);
    $subquery = $main->pad("q",$i);
    if ($i <= $number_sub_queries)
        {
        $arr_sub_queries[$i]['name'] = $main->process($name, $module, $arr_state, "");
        $arr_sub_queries[$i]['subquery'] = $main->process($subquery, $module, $arr_state, "");
        }
    else
        {
        unset($arr_state[$name]);
        unset($arr_state[$subquery]); 
        }
    }

print_r($arr_sub_queries);

$current['report_type'] = 2;

//process the form
$substituter =  $main->process("substituter", $module, $arr_state, "");     

//update state, back to db
$main->update($con, $module, $arr_state);

//title
echo "<p class=\"spaced bold larger\">Query Alias</p>";

echo "<div class=\"spaced\">";
$main->echo_messages($arr_messages);
echo "</div>";

/* BEGIN REQUIRED FORM */
$main->echo_form_begin();
$main->echo_module_vars();

$params = array("class"=>"spaced","number"=>-1,"target"=>$module, "passthis"=>true, "label"=>"Submit Full Query");
$main->echo_button("submit_query", $params);

for ($i=0;$i<=10;$i++)
    {
    $arr_number[] = $i;    
    }
$params = array("select_class"=>"spaced","onchange"=>"bb_reload()");
echo "<div class=\"spaced\"><span>Number of Subqueries: </span>";
$main->array_to_select($arr_number, "number_sub_queries", $number_sub_queries, $params);
echo "</div>";

//loop through subqueries
if ($number_sub_queries > 0)
    {
    echo "<table style=\"border-collapse: collapse;\" class=\"spaced\">";
    echo "<tr><td class=\"padded border\"></td><td class=\"padded border\">SubQuery Alias</td><td class=\"padded border\">SubQuery Value</td></tr>";
    for ($i=1;$i<=$number_sub_queries;$i++)
        {        
        echo "<tr><td class=\"padded border top\">";
        $params = array("class"=>"spaced","number"=>$i,"target"=>$module, "passthis"=>true, "label"=>"Submit SubQuery");
        $main->echo_button("submit_subquery_" . $i, $params);
        echo "</td><td class=\"padded border top\">";
        $name = $main->pad("s",$i);
        $main->echo_input($name, $arr_sub_queries[$i]['name'], array('input_class'=>"spaced medium"));
        echo "</td><td class=\"padded border top\">";
        $subquery = $main->pad("q",$i);
        $main->echo_textarea($subquery , $arr_sub_queries[$i]['subquery'], array('class'=>"spaced",'cols'=>120, 'rows'=>2));
        echo "</td></tr>";                     
        }
    echo "</table>";
    }
//display main query  
$main->echo_textarea("substituter", $substituter, array('class'=>"spaced",'cols'=>160,'rows'=>7));

//build full query
if ($main->button(-1))
    {
    $fullquery = $substituter; 
    for ($i=1;$i<=$number_sub_queries;$i++)
        {
        if ($p = stripos($fullquery, $arr_sub_queries[$i]['name']))
            {
            $l = strlen($arr_sub_queries[$i]['name']);  //token length
            $t = strlen($fullquery); //total length
            $right_splice = substr($fullquery,0, $p);
            $left_splice = substr($fullquery, $p + $l, $t - 1);
            $fullquery = $right_splice . "(" . $arr_sub_queries[$i]['subquery'] . ")" . $left_splice; 
            }
        }
    }
//or run subquery
else
    {
    $fullquery = $arr_sub_queries[$button]['subquery'];    
    }

//display query
if ($fullquery <> "")
    {
    if (substr(strtoupper(trim($fullquery)), 0, 6 ) == "SELECT")
        {
        echo "<div class=\"spaced padded border\">" . $fullquery . "</div>";
        @$result = pg_query($con, $fullquery);
        $settings[2][0] = array('start_column'=>0,'ignore'=>true,'shade_rows'=>true,'title'=>'Return Meeting List');
        if ($result === false)
            {
            array_push($arr_messages, pg_last_error($con));
            echo "<div class=\"spaced\">";
            $main->echo_messages($arr_messages);
            echo "</div>";
            }
        }
    else
        {
        array_push($arr_messages, "Error: Only SELECT queries are allowed to execute");
        echo "<div class=\"spaced\">";
        $main->echo_messages($arr_messages);
        echo "</div>";
        }
    }
    
//echo report
if (isset($result))
    {
    echo "<br>";    
    $main->echo_report_vars();
    //output report	
    if ($result)
        {
        $main->output_report($result, $current, $settings);
        }
    }

$main->echo_form_end();
/* END FORM */
?>

