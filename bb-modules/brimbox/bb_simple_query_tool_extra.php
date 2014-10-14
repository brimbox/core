<?php
/*
@included = bb_simple_query_tool;
*/

/* CUSTOM CLASS FUNCTIONS TO INCLUDE */
class bb_simple_query_tool {
    
    //callback used in dates area
    function date_walk(&$arr)
        {	
        $arr = date("Y-m-d H:i:s A", strtotime($arr));
        }
        
    //check if array contains dates
    function check_date($value)
        {
        $arr_compared = explode(",", $value);
        $arr_compared = array_map('trim', $arr_compared);
        $arr_compared = array_map('strtotime', $arr_compared);
        if (in_array(0, $arr_compared))
            {
            return false;	
            }
        else
            {
            return true;
            }
        }
        
    //checks if array contains numeric strings
    function check_numeric($value)
        {
        $arr_compared = explode(",", $value);
        $arr_compared = array_map('trim', $arr_compared);
        $arr_compared = array_map('is_numeric', $arr_compared);
        if (in_array(0, $arr_compared))
            {
            return false;
            }
        else
            {
            return true;
            }
        }
    
    //compares query definitions	
    function query_compare($arr_query_loop)
        {
        $arr_work = array_merge($arr_query_loop);
        $limit = count($arr_work);
        for ($i=0; $i<$limit; $i++)
            {
            for ($j=$i+1; $j<$limit; $j++)
                {
                if (!count(array_diff($arr_query_loop[$i],$arr_query_loop[$j])))
                    {
                    return true;
                    }
                }
            }
        return false;	
        }
        
    function create_query_columns($row_type)
        {
        //table 1
        //do columns for table 1
        $arr_column = $this->arr_columns[$row_type];
        $arr = array();        
        //get all data columns
        foreach ($arr_column as $key => $value)
            {
            //include row_type because these are check boxes
            $col = $this->main->pad("c", $key);
            $arr[$col] = $value['name'];
            }
        //concatenate arrays
        $arr_all_cols = $this->arr_cols_begin + $arr + $this->arr_cols_end;
        $arr_cols = array();
        foreach ($arr_all_cols as $key => $value)	
            {
            $chkbx = $key . "_" . $row_type;
            if ($main->post($chkbx, $module) == 1)
                {
                array_push($arr_cols, $value);	
                }
            }
        //columns are in database column format
        return $arr_cols;
        }
    
    //creates the xml to store query parameters
    function create_query_array($row_type, $row_type_join, $number_and_clauses, $number_list_clauses)
        {        
        //params
        global $module;        
        
        $arr_query = array();
        //query name
        if ($main->full('query_name', $module))
            {
            $arr_query['query_name'] = $main->post("query_name", $module, "INNER")
            }
        //join_type
        if ($main->check('join_type', $module))
            {
            $arr_query['join_type'] =  $main->post("join_type", $module, "INNER");  
            }
        //table_1
        $arr_query['table_1']['row_type'] = $row_type;
        $arr_query['table_1']['cols'] = $this->create_query_table_def($row_type);
        //table_2
        if ($row_type_join > 0)
            {
            $arr_query['table_2']['row_type'] = $row_type_join;
            $arr_query['table_2']['cols'] = $this->create_query_table_def($row_type_join);            
            }        
            
        //do and clauses
        $arr_and_conditions = array();
        $arr_and_condition['number'] = $number_and_clauses; 
        for ($i=1; $i<=$number_and_clauses; $i++)
            {
            $and = "and_"  . $i;
            $arr_and_condition = array();
            if ($main->check($and . "_value", $module)) //in case number of lists has changed
                {
                //set values one at a time
                $arr_and_condition[$and]['value'] = trim($main->post($and . "_value", $module));
                $arr = explode("-", $main->post($and . "_column", $module));                
                $arr_and_condition[$and]['column'] = $arr[0];
                $arr_and_condition[$and]['row_type'] = $arr[1];
                $arr_and_condition[$and]['comparator'] = $main->post($and . "_comparator", $module);
                $arr_and_condition[$and]['type'] = $main->post($and . "_type", $module, 1);
                }
            array_push($arr_and_conditions, $arr_and_condition)
            }
        $arr_query['ands'] = $arr_and_conditions;       
        
        //do list conditions
        $arr_list_conditions = array();
        $arr_list_conditions['number'] = $number_list_clauses; 
        for ($i=1; $i<=$number_list_clauses; $i++)
            {
            $list = "list_"  . $i;
            $arr_list_condition[$list] = array();
            if ($main->check($list, $module)) //in case number of lists has changed
                {
                $arr = explode("-", trim($main->post($list, $module)));
                $arr_list_condition['list'] = $arr[0];
                $arr_list_condition['row_type'] = $arr[1];
                $arr_list_condition['in'] = trim($main->post($list . "_in", $module, 1));
                }
            array_push($arr_list_conditions, $arr_list_condition)   
            }
        $arr_query['lists'] = $arr_list_conditions;
        
        }
    
    //creates the inner WHERE clauses based on data types	
    function create_inner_and($arr_and, &$arr_messages)
        {        
        $str_inner_and = "1 = 0"; //default nothing
        if (($arr_and['comparator'] <> 9 && in_array($col, array_keys($this->arr_cols_int)) && !is_numeric($arr_and['value'])))
            {
            array_push($arr_messages, "Error: Value in AND clause must be an integer to match column data type.");
            }
        elseif (($arr_and['comparator'] == 9) && in_array($arr_and['col'], array_keys($this->arr_cols_int)) && !check_numeric($arr_and['value']))
            {
            array_push($arr_messages, "Error: Values for IN type AND clause must be comma separated integers.");
            }
        elseif (($arr_and['comparator'] <> 9 && in_array($arr_and['col'], array_keys($this->arr_cols_date)) && !strtotime($arr_and['value'])))
            {
            array_push($arr_messages, "Error: Error: Value in AND clause must be a date to match column data type.");
            }
        elseif (($arr_and['comparator'] == 9) && in_array($arr_and['col'], array_keys($this->arr_cols_date)) && !check_date($arr_and['value']))
            {
            array_push($arr_messages, "Error: Values for IN type AND clause must be comma separated dates.");
            }
        else
            {
        //deal with data type options
            if ($arr_and['type'] == 1)
                {
                $str_inner_and = "1 = 1";	
                }
            elseif ($arr_and['type'] == 2)
                {
                $str_inner_and = "(is_number(" . $col . "::text) = 1)";
                }
            elseif ($arr_and['type'] == 3)
                {
                $str_inner_and = "(is_date(" . $col . "::text) = 1)";
                }
            }
        return $str_inner_and;
        }
    
    //creates the outer WHERE clauses based on data types	
    function create_outer_and($arr_and &$arr_messages)
        {        
        $str_outer_and = "1 = 0"; //default nothing		
    
        if ($type == 1)
            {
            $arr_and['value'] = (string)$arr_and['value'];    
            if ($arr_and['value'] <> "" || (($comparator == 10) && (trim($arr_and['value']) == "")))
                {
                switch($arr_and['comparator'])
                    {
                    case 1:
                    case 2:
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                        $str_outer_and = "(UPPER(T" . $row_type . "." . $col .  "::text) " . $this->arr_comparators[$arr_and['comparator']] .  " UPPER('" . pg_escape_string($arr_and['value']) . "'))";						
                    break;
                    case 7:
                        //begins
                        $str_outer_and = "(T" . $row_type . "." . $col . "::text " . ILIKE . " '" . pg_escape_string($arr_and['value']) . "%')";				
                    break;
                    case 8:
                        //like
                        $str_outer_and = "(T" . $row_type . "." . $col . "::text " . ILIKE . " '%" . pg_escape_string($arr_and['value']) . "%')";
                    break;
                    case 9:
                        //in
                        $arr_in = explode(",", $arr_and['value']);
                        $arr_in = array_map('trim', $arr_in);
                        $arr_in = array_map('pg_escape_string', $arr_in);
                        $arr_in = array_map('strtoupper', $arr_in);
                        $str_in = implode("','", $arr_in);
                        $str_outer_and = "(UPPER(T" . $row_type . "." . $col . ") IN ('" . $str_in . "'))";					
                    break;
                    case 10:
                        //empty
                        $str_outer_and = "(T" . $row_type . "." . $col . " = '')";					
                    break;
                    }
                }
            else
                {
                array_push($arr_messages, "Error: String comparator cannot be empty.");	
                }
            }
        elseif ($type == 2)
            {
            if ((is_numeric($arr_and['value']) && ($comparator <> 9)) ||
                (($comparator == 9) && check_numeric($arr_and['value'])) || 
                (($comparator == 10) && ((float)$comparator == 0)))
                {
                switch($arr_and['comparator'])
                    {
                    case 1:
                    case 2:
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                        $str_outer_and = "(T" . $row_type . "." . $col .  "::float " . $this->arr_comparators[$arr_and['comparator']] .  " " . $arr_and['value'] . "::float)";
                    break;
                    case 7:
                    case 8:
                        //begins
                        array_push($arr_messages, "Error: Cannot use BEGINS or LIKE with numeric data.");
                    break;
                    case 9:
                        $arr_in = explode(",", $arr_and['value']);
                        $arr_in = array_map('trim', $arr_in);
                        $arr_in = array_map('pg_escape_string', $arr_in);
                        $str_in = implode(",", $arr_in);
                        $str_outer_and = "(T" . $row_type . "." . $col . "::float IN (" . $str_in . "))";			
        
                    break;
                    case 10:
                        //empty
                        $str_outer_and = "(T" . $row_type . "." . $col . "::float = 0)";					
                    break;
                    }
                }
            else
                {
                if (($arr_and['comparator'] == 9) && (count(preg_grep("/^Error:/", $arr_messages)) == 0))
                    {
                    array_push($arr_messages, "Error: IN type AND clause must have comma separated numeric value(s).");	
                    }
                elseif (count(preg_grep("/^Error:/", $arr_messages)) == 0)
                    {
                    array_push($arr_messages, "Error: Non-numeric value in AND clause.");
                    }
                }
            }
        elseif ($type == 3)
            {
            if ((strtotime($arr_and['value']) && ($comparator <> 9)) ||
                (($comparator == 9) && check_date($arr_and['value'])) ||
                (($comparator == 10) && (trim($arr_and['value']) == "")))
                {
                $arr_compared = explode(",", $arr_and['value']);
                $arr_compared = array_map('strtotime', $arr_compared);
                array_walk($arr_compared, array($this,'date_walk'));
                switch($arr_and['comparator'])
                    {
                    case 1:
                    case 2:
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                        $str_outer_and = "(T" . $row_type . "." . $col .  "::date " . $this->arr_comparators[$arr_and['comparator']] .  " '" . $arr_and['value'] . "')";
                    break;
                    case 7:
                    case 8:
                        //like
                        array_push($arr_messages, "Error: Cannot use BEGINS or LIKE with dates.");
                    break;
                    case 9:
                        $arr_in = explode(",", $arr_and['value']);
                        $arr_in = array_map('trim', $arr_in);
                        $arr_in = array_map('pg_escape_string', $arr_in);
                        array_walk($arr_in, array($this,'date_walk'));
                        $str_in = implode("','", $arr_in);
                        $str_outer_and = "((T" . $row_type . "." . $col . ")::date IN ('" . $str_in . "'))";			
                    break;
                    case 10:
                        //empty
                        $str_outer_and = "(T" . $row_type . "." . $col . " = '')";					
                    break;
                    }			
                }
            else
                {
                if (($arr_and['comparator'] == 9) && (count(preg_grep("/^Error:/", $arr_messages)) == 0))
                    {
                    array_push($arr_messages, "Error: IN type AND clause must have comma separated date value(s).");	
                    }
                elseif (count(preg_grep("/^Error:/", $arr_messages)) == 0)
                    {
                    array_push($arr_messages, "Error: Non-date value in AND clause.");
                    }	
                }
            }
        return $str_outer_and;
        }
}

/* END CUSTOM MODULE FUNCTIONS TO INCLUDE */
?>