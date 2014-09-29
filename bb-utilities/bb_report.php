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

/* STANDARD FUNCTIONS*/

/* HTML OUTPUT */

/* PHP FUNCTIONS */
//bb_report_type
//bb_report_post
//bb_report_vars
//bb_output_report
//bb_output_single_row
//bb_result_to_select
//bb_array_to_select

/* JAVASCRIPT FUNCTIONS */
//bb_clear_report
//bb_paginate_table
//bb_clear_textarea
//bb_select_textarea

/* FORM VARS */
//textarea
//page
//report_type


//four standard output types
//textarea new is just a basic text area
//textarea add appends data to textarea
//table full outputs whole table
//paginated table display one page at a time

class bb_report extends bb_work {
	
	function report_type($selected, $params = array())
		{
		global $array_reports;
		
		$select_class = isset($params['select_class']) ? $params['select_class'] : "";
		$label_class = isset($params['label_class']) ? $params['label_class'] : "";
		$label = isset($params['label']) ? $params['label'] : "";
		if (!empty($label))
			{
			echo "<label class = \"" . $label_class . "\">" . $label . "</label>";
			}	
		echo "<select name = \"report_type\" class = \"" . $select_class . "\" onChange=\"bb_reports.clear_report()\">";
		foreach ($array_reports as $key => $value)
			{
			echo "<option value=\"" . $key . "\" " . ($key == $selected ? "selected" : "") . ">" . $value . "&nbsp;</option>";
			}
		echo "</select>";
		}
		
	function report_post(&$xml_state, $module_submit, $module_display, $params = array())
		{
		$maintain_state = isset($params['maintain_state']) ? $params['maintain_state'] : true;
		
		//get values from state
		$report_type = $this->state('report_type', $xml_state, 0);
		$page = $this->state('page', $xml_state, 0);		
		$button = $this->state('button', $xml_state, 0);
		$sort = $this->state('sort', $xml_state, "");
        $order = $this->state('order', $xml_state, "");		
			
		//only if page is set
        //order is taken through a waterfall
		if ($this->check('sort', $module_display) && !empty($module_display))
			{
			$sort_post = $this->post('sort', $module_display, "");
            //sort column has changed
            if ($sort_post <> $sort) 
                {
                $order = "ASC";
                }
            //toggle order (ASC/DESC)
            else
                {
                if (($order == "") || ($order == "DESC"))
                    {
                    $order = "ASC";   
                    }
                else
                    {
                    $order = "DESC";    
                    }
                }
            //populate sort
            $sort = $sort_post;
			}
        //only if page is set   
        if ($this->check('page', $module_display) && !empty($module_display))
			{
			$page_post = (int)$this->post('page', $module_display, 0);
            if ($page_post <> $page) 
                {
                $order =  $this->post('order', $module_display, "");
                }
            $page = $page_post;
			}

		//if report_type is set, postback	
		if ($this->check('report_type', $module_submit) && !empty($module_submit))
			{
			//postback variables used in report structure
			$report_type = $this->post('report_type', $module_submit, 0);
			}		
		//if postback, save in report arr as button
		if ($this->check('report_type', $module_submit) && !empty($module_submit))
			{
			//if button changes set page to zero
			$button_post = $this->post('bb_button', $module_submit, 0);			
			//only reset button if greater than zero
			if (($button_post <> $button) && ($button_post <> 0))
				{
				$button = $button_post;
				$page = 0;
				}
			}
					
		//usually will maintain state
		if ($maintain_state)
			{
			$this->set('report_type', $xml_state, $report_type);
			$this->set('page', $xml_state, $page);
			$this->set('sort', $xml_state, $sort);
            $this->set('order', $xml_state, $order);
			//keeps current button, different than bb_button
			$this->set('button', $xml_state, $button);
			$this->set('module_submit', $xml_state, $module_submit);
			$this->set('module_display', $xml_state, $module_submit);
			}
			
		//set up array
		$current['report_type'] = $report_type;
		$current['page'] = $page;
		$current['sort'] = $sort;
        $current['order'] = $order;
		$current['button'] = $button;
		$current['module_display'] = $module_display;
		$current['module_submit'] = $module_submit;
		
		return $current; //as $current
		}
		
	function echo_report_vars()
		{
		//if you add vars here you can custom handle them, report_post handles standard Brimbox vars 
		//other vars = order, report_type and button (a state saved version of bb_button)
		$arr_report_variables = array('page'=>0,'sort'=>'','order'=>'');
		foreach ($arr_report_variables as $key => $value)
			{
			echo "<input type = \"hidden\" name=\"" . $key . "\" value = \"" . $value. "\">";
			}
		}
	
	//large function to output reports	
	function output_report($result, $current, $settings)
		{
		//$report_type must be handled
		//$button is the root button
		//$page is the offset
			
		//$result is database result
		//$module is current module passed in		
		//$settings is the table parameters
		//class is optional array of column classes
		
		//settings array
		//$limit is number of rows per page
		//$shade_rows causes shading of alternate rows
		//$total_row means a total row at bottom of result
		//$start_column is the result position to start at (allows begining rows to be ignored)
		//$classes are for css styles
		
		//multidimensional, reduce with $report_type
		//array should have report_type and row_type keys		
			
		/* PAGED REPORT */
		if ($current['report_type'] == 1)
			{
			$this->paged_report($result, $current, $settings[1]);
			}
			
		/* FULL TABLE */
		elseif ($current['report_type'] == 2)
			{
			$this->full_report($result, $current, $settings[2]);
			}
			
		/* TEXTAREA */
		elseif ($current['report_type'] == 3)
			{
			$this->textarea_report($result, $current, $settings[3]);
			}
		} 
		
	private function paged_report($result, $current, $setting)
		{
		//output paged report
		//settings array
		$arr = $setting[0];
		
		$ignore = isset($arr['ignore']) ? $arr['ignore'] : false;
		
		$title = isset($arr['title']) ? $arr['title'] : "";		
		$title_class = isset($arr['title_class']) ? $arr['title_class'] : "bold larger spaced";
		$header = isset($arr['header']) ? $arr['header'] : true;
		$page_selector_class = isset($arr['page_selector_class']) ? $arr['page_selector_class'] : "spaced colored bold";
		$page_link_class = isset($arr['page_link_class']) ? $arr['page_link_class'] : "link bold colored";
		$return_rows_class = isset($arr['return_rows_class']) ? $arr['return_rows_class'] : "spaced colored bold";
		
		$limit = isset($arr['limit']) ? $arr['limit'] : 0;
		$shade_rows = isset($arr['shade_rows']) ? $arr['shade_rows'] : false;
		$start_column = isset($arr['start_column']) ? $arr['start_column'] : 0;		
		
		$table_class  = isset($arr['table_class']) ? $arr['table_class'] : "spaced border";
		$row_header_class = isset($arr['row_header_class']) ? $arr['row_header_class'] : "shaded bold";
		$cell_header_class = isset($arr['cell_header_class']) ? $arr['cell_header_class'] : "extra";
		
		//Note: cell_class and row class are returned in the while loop
		
		//button number
		$number = isset($current['button']) ? $current['button'] : 0; 
		
		//only var necessary
		$page = $current['page'];
        $sort = $current['sort']; //overwritten later
        $order = $current['order'];
		
		//get while loop vars and update offset
		$i = $page * $limit;
		$upper = $i + $limit;
		$count_rows = pg_num_rows($result);
		$min = $i + 1;
		//handle upper limit
		if ($count_rows <= $upper)
			{
			$max = $count_rows;
			$next = $page;
			}
		else
			{
			$max = $upper;
			$next = $page + 1;
			}
		//handle lower limit	
		$prev = ($page-1) >= 0 ? ($page-1) : 0;
		
		if (!empty($title))
			{
			echo "<p class=\"" . $title_class  . "\">" . $title . "</p>";
			}
		
		if ($header)
			{
			//if there is a limit do next links
			if ($limit > 0 && ($count_rows > 0))
				{
				echo "<div class=\"" . $page_selector_class . "\">";
				echo "&laquo;<button class = \"" . $page_link_class . "\" onclick=\"bb_reports.paginate_table(" . $number . "," . $prev . ",'" . $sort . "','" . $order . "')\">Previous</button>&nbsp;--&nbsp;";
				echo "<label>Showing Rows " . $min . "-" . $max . " of " . $count_rows . " </label>";
				echo "&nbsp;--&nbsp;<button class = \"" . $page_link_class . "\" onclick=\"bb_reports.paginate_table(" . $number . "," . $next . ",'" . $sort . "','" . $order . "')\">Next</button>&raquo;</div>";
				}
			else
				{
				echo "<div class=\"" . $return_rows_class . "\"><label>Returned " . $count_rows . " Rows</label></div>";	
				}
			}
		
		//start table
		echo "<div class=\"table " . $table_class . "\">";
		//header row
		echo "<div class=\"row " . $row_header_class . "\">";	
		//do header, $num_fields used in while loop
		$num_fields = pg_num_fields($result);
		for ($j = $start_column; $j < $num_fields; $j++)
			{
			$field = htmlentities(ucfirst(pg_field_name($result, $j)));
			$sort = $this->pad("s",$j, 2);
			if 	(isset($arr[$sort]))
				{
				echo "<div class=\"cell " . $cell_header_class . "\"><button class = \"link\" onclick=\"bb_reports.sort_order(" . $number . ",'" . $arr[$sort] . "','" . $order . "')\">" . $field . "</button></div>";
				}
		    else
				{
				echo "<div class=\"cell " . $cell_header_class . "\">" . $field . "</div>";				
				}
			}
		echo "</div>";
		
		//seek result for pagination   
		pg_result_seek($result, $i);
		//while loop from $i to $upper, unless $limit = 0 (full table)		
			//$groupby not empty
		$k = 0;
		while(($row = pg_fetch_array($result)) && (($i < $upper) || ($limit == 0)))
			{
			//data, row[0] must be zero
			if (($row[0] == 0) || $ignore)
				{
				$arr = $setting[0];
				$row_class  = isset($arr['row_class']) ? $arr['row_class'] : "";
				$cell_class = isset($arr['cell_class']) ? $arr['cell_class'] : "extra";			
				$shaded = (($k % 2) && $shade_rows) ? "shaded" : "";
				echo "<div class=\"row " . $shaded . " " . $row_class . "\">";
				for ($j = $start_column; $j < $num_fields; $j++)
					{
					$key = $this->pad("c",$j,2);
					//cell class
					$cell = (isset($arr[$key])) ? $arr[$key] : $cell_class;
					//date convert
					$key = $this->pad("d",$j,2);
					if (isset($arr[$key]))
						{
						$format = $arr[$key]; //consistant default
						$row[$j] = $this->convert_date($row[$j], $format);
						}
					echo "<div class=\"cell " . $cell . "\">" . htmlentities($row[$j]) . "</div>";
					}
				echo "</div>"; //end row
				//increment $i & $k
				$k++;
				$i++;
				}
			//groupby row $row[0] > 0
			else
				{
				//row has grouping
				//group class
				$arr = $setting[$row[0]];
				$row_class  = isset($arr['row_class']) ? $arr['row_class'] : "bold shaded";
				$cell_class = isset($arr['cell_class']) ? $arr['cell_class'] : "extra";
				echo "<div class=\"row " . $row_class . "\">";
				for ($j = $start_column; $j < $num_fields; $j++)
					{
					//get $group array from $groupby
					//get cell class
					$key = $this->pad("c",$j,2);
					$cell = (isset($arr[$key])) ? $arr[$key] : $cell_class;
					//move cell around within row
					$key = $this->pad("m",$j, 2);
					if (isset($arr[$key]))
						{
						$m = $this->rpad($arr[$key]);
						$row[$j] = $row[$m];
						}
					//populate cell of group by $row with text (for labeling)
					$key = $this->pad("t",$j, 2);
					if (isset($arr[$key])) $row[$j] = $arr[$key];
					//convert dates
					$key = $this->pad("d",$j,2);
					if (isset($arr[$key]))
						{
						$format = $arr[$key]; //consistant default
						$row[$j] = $this->convert_date($row[$j], $format);
						}
					echo "<div class=\"cell " . $cell . "\">" . htmlentities($row[$j]) . "</div>";
					}
				echo "</div>";
				$i++;
				//start shade over for each new group
				$k = 0;
				}
			}
		echo "</div>"; //end table
		}
		
	private function full_report($result, $current, $setting)
		{
		//output full report
		$arr = $setting[0];
		
		$ignore = isset($arr['ignore']) ? $arr['ignore'] : false;
		
		$title = isset($arr['title']) ? $arr['title'] : "";
		$title_class = isset($arr['title_class']) ? $arr['title_class'] : "bold larger spaced";
		$header = isset($arr['header']) ? $arr['header'] : true;
		$return_rows_class = isset($arr['return_rows_class']) ? $arr['return_rows_class'] : "spaced colored bold";
	
		$shade_rows = isset($arr['shade_rows']) ? $arr['shade_rows'] : false;
		$start_column = isset($arr['start_column']) ? $arr['start_column'] : 0;		
		
		$table_class  = isset($arr['table_class']) ? $arr['table_class'] : "spaced border";
		$row_header_class = isset($arr['row_header_class']) ? $arr['row_header_class'] : "shaded bold";
		$row_class  = isset($arr['row_class']) ? $arr['row_class'] : "";
		$cell_header_class = isset($arr['cell_header_class']) ? $arr['cell_header_class'] : "extra";
		$cell_class  = isset($arr['cell_class']) ? $arr['cell_class'] : "extra";

		//button number
		$number = isset($current['button']) ? $current['button'] : 0;
        $sort = $current['sort']; //overwritten later
        $order = $current['order'];
		
		$count_rows = pg_num_rows($result);
		
		//output title
		if (!empty($title))
			{
			echo "<p class=\"" . $title_class . "\">" . $title . "</p>";
			}
		//output row_count
		if ($header)
			{
			echo "<div class=\"" . $return_rows_class . "\"><label>Returned " . $count_rows . " Rows</label></div>";	
			}
		//start table
		echo "<div class=\"table " . $table_class . "\">";		
		echo "<div class=\"row " . $row_header_class . "\">";
		//do header, $num_fields used in while loop
		$num_fields = pg_num_fields($result);
		for ($j = $start_column; $j < $num_fields; $j++)
			{
			$field = htmlentities(ucfirst(pg_field_name($result, $j)));
			$sort = $this->pad("s",$j, 2);
			if 	(isset($arr[$sort]))
				{
				echo "<div class=\"cell " . $cell_header_class . "\"><button class = \"link\" onclick=\"bb_reports.sort_order(" . $number . ",'" . $arr[$sort] . "','" . $order . "')\">" . $field . "</button></div>";
				}
		    else
				{
				echo "<div class=\"cell " . $cell_header_class . "\">" . $field . "</div>";				
				}
			}
		echo "</div>";
		
		//while loop from $k to $upper, unless $limit = 0 (full table)
		$k = 0;
		while ($row = pg_fetch_array($result))
			{
			//data, row[0] must be zero
			if (($row[0] == 0) || $ignore)
				{
				$arr = $setting[0];
				$row_class  = isset($arr['row_class']) ? $arr['row_class'] : "";
				$cell_class = isset($arr['cell_class']) ? $arr['cell_class'] : "extra";			
				$shaded = (($k % 2) && $shade_rows) ? "shaded" : "";
				echo "<div class=\"row " . $shaded . " " . $row_class . "\">";
				for ($j = $start_column; $j < $num_fields; $j++)
					{
					$key = $this->pad("c",$j,2);
					//cell class
					$cell = (isset($arr[$key])) ? $arr[$key] : $cell_class;
					$key = $this->pad("d",$j,2);
					if (isset($arr[$key]))
						{
						$format = $arr[$key]; //consistant default
						$row[$j] = $this->convert_date($row[$j], $format);
						}
					echo "<div class=\"cell " . $cell . "\">" . htmlentities($row[$j]) . "</div>";
					}
				echo "</div>"; //end row
				//increment $i & $k
				$k++;
				}
			//groupby row $row[0] > 0
			else
				{
				//row has grouping
				//group class
				$arr = $setting[$row[0]];
				$row_class  = isset($arr['row_class']) ? $arr['row_class'] : "bold shaded";
				$cell_class = isset($arr['cell_class']) ? $arr['cell_class'] : "extra";
				echo "<div class=\"row " . $row_class . "\">";
				for ($j = $start_column; $j < $num_fields; $j++)
					{
					//get $group array from $groupby
					//get cell class
					$key = $this->pad("c",$j,2);
					$cell = (isset($arr[$key])) ? $arr[$key] : $cell_class;
					//move cell around within row
					$key = $this->pad("m",$j, 2);
					if (isset($arr[$key]))
						{
						$m = $this->rpad($arr[$key]);
						$row[$j] = $row[$m];
						}
					//populate cell of group by $row with text (for labeling)
					$key = $this->pad("t",$j, 2);
					if (isset($arr[$key])) $row[$j] = $arr[$key];
					//deal with dates
					$key = $this->pad("d",$j, 2);
					if (isset($arr[$key]))
						{
						$format = $arr[$key];
						$row[$j] = $this->convert_date($row[$j], $format);
						}				
					echo "<div class=\"cell " . $cell . "\">" . htmlentities($row[$j]) . "</div>";
					}
				echo "</div>";
				//start shade over for each new group
				$k = 0;
				}
			}
		echo "</div>"; //table div	
		}
		
	private function textarea_report($result, $current, $setting)
		{
		//textarea report
		//$ows and columns for size of textarea
		$arr = $setting[0];
		
		$ignore = isset($arr['ignore']) ? $arr['ignore'] : false;
		
		$title = isset($arr['title']) ? $arr['title'] : "";
		$title_class = isset($arr['title_class']) ? $arr['title_class'] : "bold larger spaced";
		$header = isset($arr['header']) ? $arr['header'] : true;
		$return_rows_class = isset($arr['return_rows_class']) ? $arr['return_rows_class'] : "spaced colored bold";
	
		$button_class = isset($arr['button_class']) ? $arr['button_class'] : "link bold underline spaced";
		$textarea_class = isset($arr['textarea_class']) ? $arr['textarea_class'] : "link bold spaced";
		
		$rows =  isset($arr['rows']) ? $arr['rows'] : 40;
		$columns =  isset($arr['columns']) ? $arr['columns'] : 120;
		$start_column = isset($arr['start_column']) ? $arr['start_column'] : 0;		
	
		//get row count
		$count_rows = pg_num_rows($result);
		//data_out is output data to textarea
		$data_out = "";
		//chars to be purged
		$arr_purge = array("\n","\r");
		
		//$num_fields is used in while loop
		$num_fields = pg_num_fields($result);
		//do header
		$arr_fields = array();
		for ($j = $start_column; $j < $num_fields; $j++)
			{
			$field = pg_field_name($result, $j);
			array_push($arr_fields, $field);
			}
		//append header
		$data_out .= implode("\t",$arr_fields) . PHP_EOL;		
		
		//do while loop because of limit
		while ($row = pg_fetch_row($result))
			{
			if (($row[0] == 0) || $ignore)
				{
				$arr = $setting[0];			
				$arr_data = array();	
				for ($j = $start_column; $j < $num_fields; $j++)
					{
					$row[$j] = str_replace($arr_purge,"",trim($row[$j]));
					//deal with dates
					$key = $this->pad("d",$j, 2);
					if (isset($arr[$key]))
						{
						$format = $arr[$key];
						$row[$j] = $this->convert_date($row[$j], $format);
						}				
					array_push($arr_data, $row[$j]);
					}
				$data_out .= implode("\t", $arr_data) . PHP_EOL;
				}
			else
				{
				$arr = $setting[$row[0]];
				$arr_data = array();	
				for ($j = $start_column; $j < $num_fields; $j++)
					{
					$row[$j] = str_replace($arr_purge,"",trim($row[$j]));
					//move
					$key = $this->pad("m",$j, 2);
					if (isset($arr[$key]))
						{
						$m = $this->rpad($arr[$key]);
						$row[$j] = $row[$m];
						}
					//populate cell with text (for labeling)
					$key = $this->pad("t",$j, 2);
					if (isset($arr[$key])) $row[$j] = $arr[$key];
					//dates
					$key = $this->pad("d",$j, 2);
					if (isset($arr[$key]))
						{
						$format = $arr[$key];
						$row[$j] = $this->convert_date($row[$j], $format);
						}				
					array_push($arr_data, $row[$j]);
					}
				$data_out .= implode("\t", $arr_data) . PHP_EOL;
				}
			}
		
		//output title
		if (!empty($title))
			{
			echo "<p class=\"" . $title_class . "\">" . $title . "</p>";
			}
		//output header
		if ($header)
			{
			echo "<div class=\"" . $return_rows_class . "\"><label>Returned " . $count_rows . " Rows</label> -- ";	
			echo "<button type=\"button\" class=\"" . $button_class . "\" name=\"select_textarea\" value=\"select_textarea\" onclick=\"bb_reports.select_textarea();\">Select Textarea</button> -- ";	
			echo "<button type=\"button\" class=\"" . $button_class . "\" name=\"clear_textarea\" value=\"clear_textarea\" onclick=\"bb_reports.clear_textarea();\">Clear Textarea</button><br>";
			echo "</div>";
			}		
		echo "<textarea id=\"txtarea\" class=\"" . $textarea_class . "\" name=\"txtarea\" rows=\"" . $rows . "\" cols=\"" . $columns . "\"  wrap=\"off\">" . $data_out . "</textarea>";
		echo "<div class=\"clear\"></div>";	
		}

    function build_sort($current)
		{
		if (preg_match('/[,]/', $current['sort']))
            {
            $order_by = (!empty($current['sort'])) ? "ORDER BY " . $current['sort'] : "";
            }
        else
            {
            $order_by = (!empty($current['sort'])) ? "ORDER BY " . $current['sort'] . " " . $current['order'] : "";    
            }
		return $order_by;
		}
	
			
	function output_single_row($result, $params = array())
		{
		//this echos out a single row vertically, or the first row, styled like the details tab
		$label_class = isset($params['label_class']) ? $params['label_class'] : "medium margin padded right overflow floatleft shaded";
		$value_class = isset($params['value_class']) ? $params['value_class'] : "margin padded left floatleft";
		
		$num_fields = pg_num_fields($result);
		$row = pg_fetch_array($result);
	
		for ($j = 0; $j < $num_fields; $j++)
			{
			$field = pg_field_name($result, $j);
			echo "<div class=\"clear\"><label class=\"" . $label_class . "\">" . htmlentities(ucfirst($field)) . ":</label>";
			echo "<label class=\"" . $value_class . "\">" . htmlentities($row[$j]) . "</label>";
			echo "</div>";
			}
		}
				
	function result_to_select($result, $name, $selected, $params = array())
		{
		//turns a single column result into a select dropdown, all and onchange js optional
		//use pg_fetch_all_columns to get result
		$all = isset($params['all']) ? $params['all'] : false;
		$onchange = isset($params['onchange']) ? $params['onchange'] : ""; 
		$select_class = isset($params['select_class']) ? $params['select_class'] : "";
		$label_class = isset($params['label_class']) ? $params['label_class'] : "";
		$label = isset($params['label']) ? $params['label'] : "";
		if (!empty($label))
			{
			echo "<label class = \"" . $label_class . "\">" . $label . "</label>";
			}
		echo "<select name = \"" . $name . "\" class = \"" . $select_class . "\" onchange=\"" . $onchange . "\">";
		if ($all)
			  {
			  echo "<option value=\"All\" " . ($selected == "All" ? "selected" : "") . ">All&nbsp;</option>";
			  }
		while($row = pg_fetch_array($result))
			  {
			  echo "<option value=\"" . htmlentities($row[0]) . "\" " . ($selected == $row[0] ? "selected" : "") . ">" . htmlentities($row[0]). "&nbsp;</option>";
			  }
		echo "</select>";
		pg_result_seek($result, 0);
		}
		
	function array_to_select($array, $name, $selected, $params = array())
		{
		//turns an array to a select dropdown, all and onchange js optional
		$all = isset($params['all']) ? $params['all'] : false;
		$onchange = isset($params['onchange']) ? $params['onchange'] : ""; 
		$usekey = isset($params['usekey']) ? $params['usekey'] : false;
		$select_class = isset($params['select_class']) ? $params['select_class'] : "";
		$label_class = isset($params['label_class']) ? $params['label_class'] : "";
		$label = isset($params['label']) ? $params['label'] : "";
		if (!empty($label))
			{
			echo "<label class = \"" . $label_class . "\">" . $label . "</label>";
			}
		echo "<select name = \"" . $name . "\" class = \"" . $select_class . "\" onchange=\"" . $onchange . "\">";
		if ($all)
			  {
			  echo "<option value=\"All\" " . ($selected == "All" ? "selected" : "") . ">All&nbsp;</option>";
			  }
		foreach ($array as $key => $value)
			  {
			  $key = $usekey ? $key : $value;
			  echo "<option value=\"" . htmlentities($key) . "\" " . ($selected == $key ? "selected" : "") . ">" . htmlentities($value). "&nbsp;</option>";
			  }
		echo "</select>";
		}
		
	function xml_to_select($xml, $name, $selected, $params = array())
		{
		//turns children of dropdown XML into select, all and onchange js optional
		$all = isset($params['all']) ? $params['all'] : false;
		$onchange = isset($params['onchange']) ? $params['onchange'] : ""; 
		$usenode = isset($params['usenode']) ? $params['usenode'] : false;
		$select_class = isset($params['select_class']) ? $params['select_class'] : "";
		$label_class = isset($params['label_class']) ? $params['label_class'] : "";
		$label = isset($params['label']) ? $params['label'] : "";
		
		if (!empty($label))
			{
			echo "<label class = \"" . $label_class . "\">" . $label . "</label>";
			}
		echo "<select name = \"" . $name . "\" class = \"" . $select_class . "\" onchange=\"" . $onchange . "\">";
		if ($all)
			  {
			  echo "<option value=\"All\" " . ($selected == "All" ? "selected" : "") . ">All&nbsp;</option>";
			  }
		foreach ($xml->children() as $child)
			  {
			  $node = $child->getName();
			  $key = $usenode ? $node : $child;
			  echo "<option value=\"" . htmlentities($key) . "\" " . ($selected == $key ? "selected" : "") . ">" . htmlentities($child). "&nbsp;</option>";
			  }
		echo "</select>";
		}
	
	} //end class
?>