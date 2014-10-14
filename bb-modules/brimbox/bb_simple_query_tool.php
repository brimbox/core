<?php
/*
Copyright (C) 2012  Kermit Will Richardson, Brimbox LLC

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

/*
@module_name = bb_simple_query_tool;
@friendly_name = Query;
@interface = bb_brimbox;
@module_type = Guest;
@module_version = 1.2;
@maintain_state = 1;
@description = This is a module for exporting data from simple queries.;
@json-bb_simple_query_tool = [];
*/
?>
<?php
/* NOTES -- MODULE DONE IN SECTIONS */
// 1 - Javascript
// 2 - State and state processing by button
// 3 - Dynamic query construction
// 4 - HTML form output
// 5 - Report (or query) output
// 6 - Included functions
?>
<?php
//always good idea to check permisson first
$main->check_permission(array(3,4,5));

include("bb_simple_query_tool_extra");
?>
<script type="text/javascript">
/* MODULE JAVASCRIPT */
//standard reload layout
function reload_on_change()
    {
    //clear form will button 7 do the trick
    bb_submit_form(7); //call javascript submit_form function
    }
function reload_on_ands_lists()
    {
    //button 6 will do the trick
    bb_submit_form(6); //call javascript submit_form function
    }
/* END MODULE JAVASCRIPT */
</script>
<?php

##################################################
##################################################

/* START STATE AND INITIAL VALUES */
/* get layouts */
$arr_layouts = $main->get_json($con, "bb_layout_names");
$arr_columns = $main->get_json($con, "bb_column_names");
$arr_queries = $main->get_json($con, "bb_simple_query_tool");
$arr_lists = $main->get_json($con, "bb_create_lists");
$default_row_type = $main->get_default_layout($arr_layouts);
$arr_messages = array();

/* module specific vars */
$max_number_and_clauses = 10;
$max_number_list_clauses = 10;
$arr_query_type = array(0=>"", 1=>"Single Layout", 2=>"Double Layout");

/* CALL QUERY CLASS */
//invoke class
$query_obj = new bb_simple_query_tool();
/* standard column defs attached to class*/
$query_obj->arr_cols_begin = array("id"=>"Id", "row_type"=>"Row Type", "key1"=>"Key1", "key2"=>"Key2");
$query_obj->arr_cols_end = array("updater_name"=>"Updater Name", "owner_name"=>"Owner Name", "create_date"=>"Create Date", "modify_date"=>"Modify Date", "secure"=>"Secure", "archive"=>"Archive");
$query_obj->arr_cols_int = array("id"=>"Id", "row_type"=>"Row Type", "key1"=>"Key1", "key2"=>"Key2", "secure"=>"Secure", "archive"=>"Archive");
$query_obj->arr_cols_date = array("create_date"=>"Create Date", "modify_date"=>"Modify Date");
$query_obj->arr_comparators = array(1=>"=",2=>"<>",3=>"<",4=>"<=",5=>">",6=>">=",7=>"BEGINS",8=>"LIKE",9=>"IN",10=>"EMPTY");
//bring in main and standard vars
$query_obj->main = $main;
$query_obj->arr_columns = $arr_columns;
$query_obj->arr_layouts = $arr_layouts;
/* END START STATE AND INITIAL VALUES */

##################################################
##################################################

/* DEAL WITH STATE */
//get state
$main->retrieve($con, $array_state, $userrole);
//load state
$arr_state = $main->load($module, $array_state);

/* uses standard report functionality */
$current = $main->report($arr_state, $module, $module);

//row_type_join dependent on row_type
$row_type = $main->post('row_type', $module, $default_row_type);
$query_type = $main->process('query_type', $module, $arr_state, 0);
$number_and_clauses = $main->process('number_and_clauses', $module, $arr_state, 0);
$number_list_clauses = $main->process('number_list_clauses', $module, $arr_state, 0);
$union = $main->process('union', $module, $arr_state, 0);
//query_name not maintained in state
$query_name = $main->post('query_name', $module, "");

//get joinable layouts based on row_type
$arr_layout_join = array()
foreach ($arr_layout as $key => $value)
    {
    if ($value['parent'] == $row_type)
        {
        array_push($arr_join, array($key, $value['plural']));
        }
    }

//postback waterfall, if query_type is set
//this handles the logic for single an double queries
if ($main->check('query_type', $module))
	{
    //start logic
	if ($query_type == 1) //single table query
		{
		if ($row_type <> $main->state('row_type', $arr_state))
			{
			//if changed use post
			$main->set('row_type', $arr_state, $row_type);		
			}
		else
			{
			//otherwise process
			$row_type = $main->process('row_type', $module, $arr_state, $default_row_type);	
			}	
		$row_type_join = $main->set('row_type_join', $arr_state, 0);
		}
	elseif ($query_type == 2) //consider query_type double layour
		{
		//will be nothing if row_type = 0
		//get child layouts based on post row_type
		if (count(array_keys($arr_join)) > 0) //will have JOIN table
			{				
			if ($row_type <> $main->state('row_type', $arr_state)) //row_type changed
				{
				$row_type = $main->set('row_type', $arr_state, $default_row_type);
				$row_type_join = $main->set('row_type_join', $arr_state, key($arr_layout_join));
				}
			else //row_type the same
				{
				$row_type = $main->process('row_type', $module, $arr_state, $default_row_type);
				//set row_type_join from the post
				$row_type_join = $main->set('row_type_join', $arr_state, $main->post('row_type_join', $module, key($arr_layout_join)));	
				}
			}
		else //not possible to have JOIN table
			{
			$row_type_join = $main->set('row_type_join',$arr_state, 0);
			$query_type = $main->set('query_type', $arr_state, 1);
			}	
		}
	else // consider query_type = blank
		{
		$row_type = $main->set('row_type', $arr_state, 0);
		$row_type_join = $main->set('row_type_join', $arr_state, 0);
		}
	}
else //process from state, 0 is default value, not post back
	{
	$row_type = $main->state('row_type', $arr_state, 0);
	$row_type_join = $main->state('row_type_join', $arr_state, 0);		
	}

//checkboxes are a little tricky, this for saved queries
//also used for deleting and running multple queries
$arr_query_loop = array();
foreach ($arr_queries as $key => $value)
		{        
        $chkbx = 'query_' . $key;
        $chkbx_value = $main->process($chkbx, $module, $arr_state, 0)
        if ($chkbx_value == 1)
            {
            $arr_query_loop[$key], $arr_queries[$key]);
            }
		}

//button postback logic and waterfall
if ($main->button(1))
	{
	$current = $main->report_post($xml_state, $module, $module);
	//clear state for run queries, use $arr_loop_query
    $arr_query = array()l
	//clear form state vars
    $row_type = $row_type_join = $number_and_clauses = $number_list_clauses = $query_type = 0;
    }
    
//load query
elseif ($main->button(2))
	{
    //populate node from checkboxes
    if (count($arr_query_loop) == 1)
        {
        $node = key($arr_query_loop);
        $arr_query = $arr_queries[$node];
        }
	elseif (count($arr_query_loop) <> 1)
		{
		array_push($arr_messages, "Query cannot be loaded. Must choose a single query from the list to load query.");	
		}
	}
    
//delete query
elseif ($main->button(3))
	{
    $cnt = count($arr_query_loop)
	if ($cnt >= 1)
		{
		foreach ($arr_query_loop as $key => $value)
			{
			unset($arr_queries[$key]);
			}
		array_push($arr_messages, $cnt . " queries have been deleted.");
		}
	//clear form state vars
	$row_type = $row_type_join = $number_and_clauses = $number_list_clauses = $query_type = 0;
	$main->update_json($con, $arr_queries, "bb_simple_query_tool");
	}
    
//run query and maintain state	
elseif ($main->button(4))
	{
	if ($query_type > 0)
		{
		$current = $main->report_post($xml_state, $module, $module);
		$arr_query = $query_obj->create_query_array($row_type, $row_type_join, $number_and_clauses, $number_list_clauses);
        $arr_state['query'] = $arr_query;
		}
	else
		{
		array_push($arr_messages, "Error: Must define query.");
		}
	}
//save query	
elseif ($main->button(5))
	{
    $arr_query = $query_obj->create_query_array($row_type, $row_type_join, $number_and_clauses, $number_list_clauses);
    $arr_state['query'] = $arr_query;
	if ($query_name == "")
		{
		//populate xml_query for form        
        $k = $main->get_next_node($arr_queries, 1000);
        $arr_queries[$k] = $arr_query;
		$main->update_json($con, $arr_queries, "bb_simple_query_tool");
		$query_name = "";
		}
	else // query empty
		{
		array_push($arr_messages, "Query has not been saved. Query name missing.");
		}
	}

//reload on AND change
elseif ($main->button(6))
	{
	//populate xml_query for form
	$arr_query = create_query_array($main, $xml_query, $function_params);
	//save in state
	$arr_state['query'] = $arr_query;
	}
    
//clear form
elseif ($main->button(7));
	{
	//reset xml and state
	$arr_query = array();
	$arr_state['query'] = array();
	$number_and_clauses = 0;
	$number_list_clauses = 0;
	}
    
//not postback, from tab change
else
	{
	//get xml_query from xml state if state, will be reset in waterfall if necessary
    $arr_query = isset($arr_state['query']) ? $arr_state['query']: array();
	}
	
//update state, back to string, get name
$main->update($array_state, $module, $xml_state);
/* END DEAL WITH STATE */

#########################################################
#########################################################

/* BUILD QUERY */
//run from saved queries
if ($button(1))
	{
    $same = false; //inititalize same
	//loop through not sure whether anonymous functions work in 5.3
	if (count($arr_query_loop) > 0)
		{
		//test whether queries have same layout
		foreach ($arr_loop_query as $key => $value)
			{
			//call external function
			$same = $query_obj->query_compare($arr_loop_query);
			if (!$same)
				{
				array_push($arr_messages, "Error: Queries have different column layouts. Columns must be the same for each query.");	
				break;
				}			
			}
		}
	else
		{
		array_push($arr_messages, "Error: No queries selected.");		
		}
	}
    
//run from wording query, make into a single item array
elseif (($post_button == 4) && ($query_type > 0))
	{
    //use loop
	$arr_loop_query[0] = $arr_query;
	$same = true;
	}
	
//loop through making queries array
//this area builds the SQL
if ($same)
	{
    //multiple queries
	foreach ($arr_loop_query as $arr_query_work)
		{
		//build columns and arr_row_type
        $i = 0; //used for the date formating options
        $arr_tables = array("table_1","table_2"); //why not loop
		foreach ($arr_tables as $table)
			{
			if (isset($arr_query_work[$table]))
				{
				$arr_row_type[]= $arr_query_work[$table]['row_type'];
				$arr_dates = array(); //used to format create an modify in report
				foreach ($arr_query_work[$table]['cols'] as $key => $value)
					{
					//limited reverse explode since some of the column names contain underscores
					//convert create date amd modify date
					if (in_array($col, array('create_date', 'modify_date')))
						{
						$key = $main->pad("d",$i);
						$arr_dates[$key] = "Y-m-d H:i:s A";
						}
					array_push($arr_names, "T" . $row_type_work . "." . $key . " as \"" . $value . "\"");
					$i++;
					}
				}
			}
        //no columns
		$str_output_names = empty($arr_names) ? "'No Columns Specified' as \"No Columns Specified\"" :  implode(", ", $arr_names);
		
		//build list clauses
		$arr_list_clauses = $arr_query_work['lists'];
		if ($arr_list_clauses['number'] > 0)
			{
			$arr_inner_list = array();
			foreach($arr_list_clauses as $key => $value)
				{
				$list_number = $value['list'];
				$in = $value['in'];
				$row_type_work = $value['row_type'];
				$arr_inner_list[$row_type_work][] = "list_retrieve(list_string, " . $list_number . ") = " . $in . " AND row_type = " . $row_type_work;
				}
			//build list clauses
			foreach ($arr_inner_list as $key => $value)
				{                
				$arr_query_lists[$key] = implode(" AND " , $arr_inner_list[$key]);
                $arr_query_lists[$key] = ($arr_query_lists_string[$key] <> "") ? $arr_query_lists_string[$key] : "1 = 1";
				}		
			}
			
		//build and clauses
		$arr_and_clauses = $arr_query_work['and'];
		if ($arr_and_clauses['number'] > 0)
			{
			$arr_inner_and = array();
			$arr_outer_and = array();
			foreach($arr_and_clauses as $key => $value)
				{
                //get row type to separate
                $row_type_work = $value['row_type']
				//inner ands for checking type so no errors in query when outer and are compared
				$arr_inner_and[$row_type_work][] = create_inner_and($value, $arr_messages);
				//these are the ands that do the actual comparison
				$arr_outer_and[] =  create_outer_and($value, $arr_messages);
				}                
			//build and clauses
			foreach ($arr_inner_and as $key => $value)
				{
				//will creat a bunch of 1 = 1s in query
				$arr_inner_ands[$key] = implode(" AND " , $arr_inner_and[$key]);
                $arr_inner_ands[$key] = ($arr_inner_ands[$key] <> "") ? $arr_inner_ands[$key] : "1 = 1";
				}
			$str_outer_ands  = implode(" AND ", $arr_outer_and) <> "" ?	 implode(" AND ", $arr_outer_and) : 1 = 1;	
			}		

		//check is there have been errors
		$arr_error = preg_grep("/^Error:/", $arr_messages);
        
		//build the query if no errors
		if (count($arr_error) == 0)
			{           
			if (!isset($arr_query_work['table_2']))
				{
                $distinct = (($union == 1) && (count($arr_query_loop) == 1)) ? "DISTINCT" : "";
                $row_type_work = $arr_query_work['table_1']['row_type'];
				$sub_select[0] = "(SELECT * FROM data_table WHERE row_type = " . $row_type_work . " AND " . $arr_inner_ands[$row_type_work] . " AND " . $arr_inner_ands[$row_type_work] . ") T" . $row_type_work;
				$queries[] = "SELECT " . $distinct . " " . $str_output_names . " FROM " . $sub_select[0] . " WHERE " . $str_outer_ands;		
				}
			else
				{
                $arr_row_type = array(0=>$arr_query_work['table_1']['row_type'], 1=>$arr_query_work['table_2']['row_type']);
				$sub_select[0] = "(SELECT * FROM data_table WHERE row_type = " . $arr_row_type[0] . " AND " . $arr_inner_ands[$arr_row_type[0]] . " AND " . $arr_inner_lists[$arr_row_type[0]] . ") T" . $arr_row_type[0];
				$sub_select[1] = "(SELECT * FROM data_table WHERE row_type = " . $arr_row_type[1] . " AND " . $arr_inner_ands[$arr_row_type[1]] . " AND " . $arr_inner_lists[$arr_row_type[1]] . ") T" . $arr_row_type[1];
				$queries[] = "SELECT " . $distinct . " " . $str_output_names . " FROM " . $sub_select[0] . " " . $join_type . " JOIN " . $sub_select[1] . " " .
					 "ON T" . $arr_row_type[0] . ".id = T" . $arr_row_type[1] . ".key1 WHERE " . $str_outer_ands;		
				}
			}
		} //array_loop_query        
    //master query
    $str_union = $union ? " UNION " : " UNION ALL ";
    $query = implode($str_union, $queries);
	} //if same    
/* END BUILD QUERY */

#################################################
#################################################

/* BEGIN FORM & HTML OUTPUT */
echo "<p class=\"padded bold larger\">Simple Query Tool</p>";

echo "<div class=\"spaced\">";
$main->echo_messages($arr_messages);
echo "</div>";

//start form
$main->echo_form_begin();
$main->echo_module_vars($module);
$main->echo_report_vars();

//this is the big table
echo "<table class=\"bordertop\" cellspacing=\"0\" cellpadding=\"0\"><tr><td>";
//left buttons
echo "<div class=\"spaced nowrap\">";
$params = array("class"=>"spaced","number"=>1,"target"=>$module, "passthis"=>true, "label"=>"Run Queries");
$main->echo_button("run_query_1", $params);
$params = array("class"=>"spaced","number"=>2,"target"=>$module, "passthis"=>true, "label"=>"Load");
$main->echo_button("load_query", $params);
$params = array("class"=>"spaced","number"=>3,"target"=>$module, "passthis"=>true, "label"=>"Delete");
$main->echo_button("delete_query", $params);
echo "<span class = \"border rounded shaded padded spaced\">";
echo "<label class=\"middle padded\">Distinct: </label><input class=\"middle padded\" type=\"checkbox\" name=\"union\" value =\"1\" " . ($union ? "checked" : "") . "/>";
echo "</span>";
echo "</div>";
//saved queries
echo "<table cellspacing=\"0\" cellpadding=\"0\">";
foreach ($arr_queries as $key => $value)
	{	
	$checked = isset($arr_query_loop[$key]) ? "checked" : "";
	echo "<tr><td class=\"padded\"><input class=\"middle spaced\" type=\"checkbox\" name=\"query_" . $key . "\" value=\"1\" " . $checked . "/></td>";
	echo "<td class=\"padded\"><label class=\"middle spaced\">" . $value['name'] . "</label></td></tr>";
	}
echo "</table>";
echo "</td>";//end big td

//this is the other big td
echo "<td class=\"borderleft\">";
//right buttons and form objects
echo "<table><tr><td>";
$params = array("class"=>"spaced middle","number"=>4,"target"=>$module, "passthis"=>true, "label"=>"Run Query");
$main->echo_button("run_query_2", $params);
echo "&nbsp;|&nbsp;";
$params = array("select_class"=>"padded middle","label_class"=>"padded middle","label"=>"Query Type: ");
$main->report_type($current['report_type'], $params);
echo "&nbsp;|&nbsp;";
$params = array("usekey"=>true, "onchange"=>"reload_on_change()", "select_class"=>"spaced middle"); 
$main->array_to_select($arr_query_type, "query_type", $query_type, $params);
echo "&nbsp;|&nbsp;";
//save area
$params = array("class"=>"spaced middle","number"=>5,"target"=>$module, "passthis"=>true, "label"=>"Save Query");
$main->echo_button("save_query", $params);
echo "<input class=\"medium middle\" type=\"text\" name =\"query_name\" value\"" . $query_name . "\" />";
echo "&nbsp;|&nbsp;";
//bb_button 6 set in javascript
$params = array("class"=>"spaced middle","number"=>7,"target"=>$module, "passthis"=>true, "label"=>"Clear Query");
$main->echo_button("clear_query", $params);

//columns selector area, first table
echo "<table cellspacing=\"0\" cellpadding=\"0\" class=\"border spaced\">";
//standard layout selector for table 1
if ($row_type > 0)
	{
	echo "<tr><td>"; //outer	
	echo "<table cellspacing=\"0\" cellpadding=\"0\" class=\"spaced padded\"><tr>";
	$params = array("onchange"=>"reload_on_change()", "class"=>"spaced"); 
	$main->layout_dropdown($arr_layouts, "row_type", $row_type, $params);
	
	//column to set for table 1
	$arr_column = $arr_columns[$row_type];
	$m = 0;
	$arr = array();
	foreach ($arr_column as $key => $value)
		{
        $col = $this->main->pad("c", $key);    
		$arr[$col] = $value['name'];
		}
	$arr = $query->arr_cols_begin + $arr + $query->arr_cols_end;
	foreach ($arr as $key => $value)
		{
		$col = $key . "_" . $row_type;
		$table = ($m % 5 == 0) ? "<tr><td>" : "<td>";
		echo $table;
		//use xml_query
		$checked = (isset($arr_query['table_1'][$col]) ? "checked" : "";
		echo "<div class=\"padded middle\"><input class=\"middle padded\" type=\"checkbox\" name=\"" . $col . "\" value=\"1\" " . $checked . "/><label class=\"middle padded nowrap\">" . htmlentities($value['name']) . "</label></div>";
		$table = (($m + 1) % 5 == 0) ? "</td></tr>" : "</td>";
		echo $table;
		//update counter
		if ($m % 5 == 0) {
			$m = 1; }
		else {
			$m++; }
		}
	$table = ($m % 5 <> 0) ? "</tr>" : "";
	echo $table;
	echo "</table>";
	echo "</td></tr>"; //outer
	} //row_type <> 0

//standard layout selector for second table
if ($row_type_join > 0)
	{
	echo "<tr><td class=\"bordertop\">"; //outer	
	echo "<table cellspacing=\"0\" cellpadding=\"0\" class=\"spaced padded\"><tr>";
	
	$params = array("usekey"=>true, "onchange"=>"reload_on_change()", "select_class"=>"spaced");  
	$main->array_to_select($arr_layouts_join, "row_type_join", $row_type_join, $params);

	//column set for table_2
	$arr_column = $arr_columns[$row_type_join];
	$m = 0;
	$arr = array();
	foreach ($arr_column as $key => $value)
		{
        $col = $this->main->pad("c", $key);    
		$arr[$col] = $value['name'];
		}
	$arr = $query->arr_cols_begin + $arr + $query->arr_cols_end;
	foreach ($arr as $key => $value)
		{
		$table = ($m % 5 == 0) ? "<tr><td>" : "<td>";
		echo $table;
		$col = $key . "_" . $row_type_join;
		//use xml_query
		$checked = (isset($arr_query['table_2'][$col])) ? "checked" : "";
		echo "<div class=\"padded middle\"><input class=\"middle padded\" type=\"checkbox\" name=\"" . $col . "\" value=\"1\" " . $checked . "/><label class=\"middle padded nowrap\">" . htmlentities($value['name'] . "</label></div>";
			$table = (($m + 1) % 5 == 0) ? "</td></tr>" : "</td>";
		echo $table;
		//update counter
		if ($m % 5 == 0) {
			$m = 1; }
		else {
			$m++; }
		}
	echo "</table>";
	echo "</td></tr>"; //outer
	}
echo "</table>"; //outer
echo "<input type=\"hidden\" name=\"check\" value=\"0\" />";

//Number of clauses, only if there are tables
//also join type and distinct
if ($row_type > 0)
	{
	echo "<div class=\"table\"><div class=\"row\"><div class=\"cell middle\">";
	//number of list clauses
	$arr_number = array();
	for ($i=0; $i<=$max_number_list_clauses; $i++)
		{
		array_push($arr_number, $i);
		}
	//$number_and_clauses from postback if xml_ands is empty
	$params = array("usekey"=>true, "onchange"=>"reload_on_ands_lists()", "select_class"=>"spaced padded middle", "label_class"=>"spaced padded middle", "label"=>"Number of List clauses:");  
	$main->array_to_select($arr_number, "number_list_clauses", $number_list_clauses, $params);
	echo "</div>";
	
	//number of and clauses
	echo "<div class = \"cell middle\">";
	$arr_number = array();
	for ($i=0; $i<=$max_number_and_clauses; $i++)
		{
		array_push($arr_number, $i);
		}
	$params = array("usekey"=>true, "onchange"=>"reload_on_ands_lists()", "select_class"=>"middle padded spaced", "label_class"=>"spaced padded middle", "label"=>"Number of AND clauses:");  
	$main->array_to_select($arr_number, "number_and_clauses", $number_and_clauses, $params);	
	echo "</div>";
	
	//join type if double query
	echo "<div class = \"cell middle\">";
	if ($row_type_join > 0)
		{
		$arr_join_type = array("INNER","LEFT");
		$join_type = $arr_query['join_type'];
		$params = array("select_class"=>"spaced padded middle", "label_class"=>"spaced padded middle", "label"=>"Join Type:");  
		$main->array_to_select($arr_join_type, "join_type", $join_type, $params);
		}
	echo "</div></div></div>";
	
	//list conditions	
	echo "<table cellspacing=\"0\" cellpadding=\"0\" class=\"border spaced\">";
	if (isset($arr_query['lists']))
		{
		$arr_list_conditions = $arr_query['lists'];
		$number_list_clauses = $arr_query['lists']['number'];            
        for ($i=1; $i<=$number_list_clauses; $i++)
            {
            echo "<tr>";
            echo "<td class=\"padded\">";
            $layout = $main->pad("l", $row_type);
            $arr_layout = $arr_layouts[$row_type];
            $arr_list = $arr_list[$row_type];
            $list = "list_" . $i;
            
            //set default values -- to remove notices
            $arr_values = array('list'=>0,'row_type'=>0, 'in'=>1);
            if (isset($arr_query['lists'][$list]))
                {
                $arr_values = $arr_query['lists'][$list];
                }
    
            echo "<select class=\"spaced middle\" name=\"" . $list . "\">";
            //use standard columns
            $arr = array();
            $arr_list = $arr_lists[$row_type];
            foreach ($arr_list as $key => $value)
                {
                $selected = ($arr_values['list'] == $key) && ($arr_values['row_type'] == $row_type) ? "selected" : "";
                echo "<option value=\"" . $option . "\" " . $selected . ">" . htmlentities($arr_layout['plural']) . ": " . htmlentities($arr_list[$i]['name') . "&nbsp;</option>";
                }
            if ($row_type_join > 0)
                {
                $arr_layout = $arr_layouts[$row_type_join];
                $arr_list = $arr_lists[$row_type_join];
                foreach ($arr_list as $key => $value)
                    {
                    $selected = ($arr_values['list'] == $key) && ($arr_values['row_type'] == $row_type_join) ? "selected" : "";
                    echo "<option value=\"" . $option . "\" " . $selected . ">" . htmlentities($arr_layout['plural']) . ": " . htmlentities($arr_list[$i]['name') . "&nbsp;</option>";
                    }
                }
            echo "</select>";
		
            echo "<select class=\"spaced middle\" name=\"" . $list . "_in\">";
            $in = isset($arr_values['in']) ? $arr_values['in'] : 1;
            echo "<option value=\"1\" " . ($in == 1 ? "selected" : "") . ">In List&nbsp;</option>";
            echo "<option value=\"0\" " . ($in == 0 ? "selected" : "") . ">Not In List&nbsp;</option>";
            echo "</select>";	
            echo "</td>";
            } //loop
		} //isset lists	

	//and conditions
    if (isset($arr_query['ands']))
		{
		$arr_and_conditions = $arr_query['ands'];
		$number_and_clauses = $arr_query['ands']['number'];  
        for ($i=1; $i<=$number_and_clauses; $i++)
            {
            echo "<tr>";
            echo "<td class=\"padded\">";
            $arr_layout = $arr_layouts['row_type'];
            $arr_column = $arr_columns['row_type'];
            $and = "and_" . $i;
            
            $arr_values = array('value'=>'','row_type'=>0,'column'=>'','comparator'=>1,'type'=>1);
            if (isset($arr_query['ands'][$and]))
                {
                $arr_values = $arr_query['ands'][$and];
                }    
            echo "<select class=\"spaced middle\" name=\"" . $and . "_column\">";
            
            //use standard columns
            $arr = array();
            foreach ($arr_column as $key => $value)
                {
                $arr[$main->pad("c",$key)] = $value['name'];
                }
            $arr = $query->arr_cols_begin + $arr + $query->arr_cols_end;
            
            foreach ($arr as $key => $value)
                {
                //must have row_type for ands
                $option = $key . "-" . $row_type;
                $column =  $arr_values['column'] . "-" . $arr_values['row_type'];
                $selected = ($option == $column) ? "selected" : "";
                echo "<option value=\"" . $option . "\" " . $selected . ">" . htmlentities($arr_layout['plural']) . ": " . htmlentities($value['name']) . "&nbsp;</option>";
                }
                
            if ($row_type_join > 0)
                {
                $arr_layout = $arr_layouts[$row_type_join];
                $arr_column = $arr_columns[$row_type_join];

                $arr = array();
                foreach ($arr_column as $key => $value)
                    {
                    $arr[$main->pad("c",$key)] = $value['name'];
                    }
                $arr = $query->arr_cols_begin + $arr + $query->arr_cols_end;
               
                foreach ($arr as $key => $value)
                    {
                    $option = $key . "-" . $row_type_join;
                    $column = $arr_values['column'] . "-" . $arr_values['row_type'];
                    $selected = ($option == $column) ? "selected" : "";
                    echo "<option value=\"" . $option . "\" " . $selected . ">" . htmlentities($arr_layout['plural']) . ": " . htmlentities($value['name']) . "</option>";
                    }
                }
            echo "</select>";
    
            echo "<select class=\"spaced middle\" name=\"" . $and . "_comparator\">";
            foreach ($arr_comparators as $key => $value)
                {
                $selected = ($arr_values['comparator'] == $key)  ? "selected" : "";
                echo "<option value=\"" . $key . "\" " . $selected . ">" . $value . "&nbsp;</option>";
                }
            echo "</select>";	
    
            echo "<td class=\"padded\">";
            echo "<input name=\"" . $and . "_value\" class=\"spaced middle medium\" type=\"text\" value=\"" . htmlentities((string)$arr_values['value']) . "\" />";
    
            $type = $arr_values['type'];
            echo "<label class=\"spaced middle padded\">Data Type: </label>";
            echo "<span class=\"spaced middle\">Text:</span><input type=\"radio\" class=\"middle\" name=\"" . $and . "_type\" value=\"1\"" . ($type == 1 ? "checked" : "") . ">";
            echo "<span class=\"spaced middle\">Numeric:</span><input type=\"radio\" class=\"middle\" name=\"" . $and . "_type\" value=\"2\"" . ($type == 2 ? "checked" : "") . ">";
            echo "<span class=\"spaced middle\">Date:</span><input type=\"radio\" class=\"middle\" name=\"" . $and . "_type\" value=\"3\"" . ($type == 3 ? "checked" : "") . ">";
            echo "</td>";
        
            echo "</tr>";
        
            } //for loop
        } //and conditions
	echo "</table>"; //end of table
	} //row_type > 0

//echos out the state
$main->echo_state($array_state);
//form end
$main->echo_form_end();
/* END FORM & HTML OUTPUT */

##############################################
##############################################

/* OUTPUT QUERY USING REPORTS FUNCTIONALITY */
if ($query <> "")
	{
	//echo "<p>" . $query . "</p>";
	$result = $main->query($con, $query);
	
	$settings[1][0] = array('ignore'=>true,'limit'=>10,'shade_rows'=>true,'title'=>'Query Return') + $arr_dates;
	$settings[2][0] = array('ignore'=>true,'shade_rows'=>true,'title'=>'Query Return') + $arr_dates;
	$settings[3][0] = array('rows'=>40,'columns'=>100,'title'=>'Query Return') + $arr_dates;
	
	if ($result)
		{
		$main->output_report($result, $current, $settings);
		}
	}
//end outer big table
echo "</td></tr><table>";

/* END OUTPUT QUERY */

?>
