<?php
/* NO HTML OUTPUT */
define('BASE_CHECK', true);
// need DB_NAME from bb_config, must not have html output including blank spaces
include("../bb-config/bb_config.php"); // need DB_NAME

session_name(DB_NAME);
session_start();

if (isset($_SESSION['username'])):

    //deal with stored $_SESSION stuff
    $interface =  $_SESSION['interface'];
    $abspath =  $_SESSION['abspath'];
    $webpath =  $_SESSION['webpath'];    
    $keeper = $_SESSION['keeper'];
    $username = $_SESSION['username'];
        
    //standard $_SESSION post stuff
    $_SESSION['module'] = $module = $_POST['bb_module'];
    $_SESSION['slug'] = $slug = $_POST['bb_slug'];
    $_SESSION['submit'] = $submit = $_POST['bb_submit'];
    $_SESSION['button'] = $button = isset($_POST['bb_button']) ? $_POST['bb_button'] : 0;
    if (($_POST['bb_userrole'] <> "")  && in_array($_POST['bb_userrole'], explode($_SESSION['userroles'])))
        $_SESSION['userrole'] = $_POST['bb_userrole'];  //double checked when build->locked is call in index


    /* SET UP WORK OBJECT AND POST STUFF */
    //objects are all daisy chained together
    //set up work from last object
    // contains bb_database class, extends bb_main
    //constants include -- some constants are used
    include($abspath . "/bb-config/bb_constants.php");
    //include build class object
    include($abspath . "/bb-utilities/bb_build.php");
    //get build
    $build = new bb_build();
    
    //get connection
    $con = $build->connect();    
    
    //parse global arrays  
    $build->loader($con, $interface);

    //include main class
    $build->hook("bb_upload_data_redirect_main_class");
    //get work instance
    $build->hook("bb_upload_data_redirect_return_main");
    
    $POST = $_POST;
    
    $arr_state = $main->load($con, $submit);
    
    //initial values
    $arr_messages = array();	
    $arr_relate = array(41,42,43,44,45,46);
    $arr_file = array(47);
    $arr_reserved = array(48);
    $arr_notes = array(49,50);
    
    //get layouts
    $layouts = $main->layouts($con);
    $default_row_type = $main->get_default_layout($layouts);
    //get guest index
    $arr_header = $main->get_json($con, "bb_interface_enabler");
    $arr_guest_index = $arr_header['guest_index']['value'];

    //will handle postback
    if ($main->changed('row_type', $submit, $arr_state, $default_row_type))
        {
        $row_type = $main->process('row_type', $submit, $arr_state, $default_row_type); 
        $data_area = "";
        $data_file = "default";
        $edit_or_insert = 0;
        }
    else
        {
        $row_type = $main->process('row_type', $submit, $arr_state, $default_row_type); 
        $data_area = $main->process('data_area', $submit, $arr_state, "");
        $data_file = $main->process('data_file', $submit, $arr_state, "default");
        $edit_or_insert = $main->process('edit_or_insert', $submit, $arr_state, 0);
        }
        
    //get column names based on row_type/record types
    $parent_row_type = $layouts[$row_type]['parent']; //should be set
    //need unreduced column
    $arr_colums = $main->columns($con, $row_type);
    //get dropdowns for validation
    $arr_dropdowns = $main->dropdowns($con, $row_type);
    //get has_link
    $has_link = $parent_row_type || $edit_or_insert ? true : false;
    
    //button 1 -- get column names for layout    
    if ($main->button(1)) 
        {
        if (!$has_link)
            {
            $arr_implode = array();    
            }
        else
            {
            $arr_implode = array("Link");    
            }
        foreach ($arr_colums as $value)
            {
            array_push($arr_implode, $value['name']); 
            }
        $data_area = implode("\t", $arr_implode) . PHP_EOL;
        }
    
    //button 2 -- submit_file     
    if ($main->button(2)) 
        {
        if (is_uploaded_file($_FILES[$main->name('upload_file', $submit)]["tmp_name"]))
            {
            $data_area = file_get_contents($_FILES[$main->name('upload_file', $submit)]["tmp_name"]);
            }
        else
            {
            array_push($arr_messages, "Error: Must specify file name.");
            }
        }
        
    /* $edit_or_insert */
    //0 - INSERT
    //1 - UPDATE POPULATED VALUES
        
    //button 3 -- post data to database	
    if ($main->button(3)) //submit_data
        {
        //$i is used to check header       
        //$j is the number of rows of data, 0 is header row, 1 starts data
        //$k is the line item count
        //$l is is the item in the line

        $arr_lines =  preg_split("/\r\n|\n|\r/", $data_area);
        $arr_lines = array_filter($arr_lines);
        $cnt_lines = count($arr_lines);
        
        // check header 
        $check_header = true;
        $i = 0;
        $arr_row = explode("\t", trim($arr_lines[0]));
        if ($has_link)
            {
            //link corresponds to database id
            if (strcasecmp($arr_row[0], "Link"))
                {
                $check_header = false;
                }
            $i++;
            }   
        foreach($arr_colums as $value)
            {
            //there is a value to check
            if (isset($arr_row[$i]))
                {
                if (strcasecmp($value['name'], $arr_row[$i]))
                    {
                    $check_header = false;
                    break;
                    }
                }
            //no value to check
            else
                {
                $check_header = false;
                break;
                }
            $i++;
            }
        //end check header
            
        //determine $p
        $line_items = ($has_link) ?  count($arr_colums) + 1 : count($arr_colums);
        /* End Check Header */
        
        //check header checks that the first line of data matches $xml_column
        //$arr_lines may need trim function
        if ($check_header)
            {
            //$inputted is count of rows entered
            //$not_validated is rows rejected on validation
            //$not_inputted is rows rejected on insert or update
                            
            /* START LOOP */
            //loops through each row of data
            $arr_errors_all = $arr_messages_all = $arr_messages_grep = array();
            $inputted = $not_validated = $not_inputted = 0; //count of rows entered
            for ($j=1; $j<$cnt_lines; $j++)
                {
                $arr_line = explode("\t", $arr_lines[$j]);
                for ($k=count($arr_line); $k<$line_items; $k++) 
                    {
                    //if a line is shorter than it is supposed to be 
                    $arr_line[$i] = "";
                    }
                    
                //BUILD ARRAY TO PASS
                $arr_pass = array();                   
                //INSERT RECORDS
                if ($edit_or_insert == 0)
                    {
                    if ($has_link)
                        {
                        $arr_pass['row_type'] = $row_type;
                        $arr_pass['row_join'] = $parent_row_type;
                        //convert every non-integer to be zero
                        //zero will cause INSERT to fail
                        $arr_pass['post_key'] = (int)$arr_line[0];
                        $l = 1;
                        }
                    else
                        {
                        $arr_pass['row_type'] = $row_type;    
                        $arr_pass['row_join'] = 0;
                        $arr_pass['post_key'] = -1;
                        $l = 0;
                        }
                    }
                //EDIT OR UPDATE RECORD
                else //$edit_or_insert = 1 or 2
                    {
                    $l = 1;
                    $arr_pass['row_type'] = $row_type;
                    $arr_pass['row_join'] = $row_type;
                    $arr_pass['post_key'] = (int)$arr_line[0];
                    }
                
                //set up array to pass, $arr_pass
                //also build filter if editing
                $filter = array();
                foreach ($arr_colums as $key => $value)
                    {
                    $col = $main->pad("c", $key);
                    $arr_pass[$col] = $arr_line[$l];
                    //setup filter for edit, filter is columns to be edited
                    if ($edit_or_insert == 1)
                        if (!$main->blank($arr_line[$l]))
                            array_push($filter, $key);
                    $l++;
                    }  
                                
                //setup params to pass, $mode left to default
                //edit columns in $filter array
                $params = array();    
                if ($edit_or_insert == 1)
                    $params['filter'] = $filter;
                
            
                /* ENFORCE UPLOAD POLICY */
                foreach ($arr_colums as $key => $value)
                    {
                    $col = $main->pad("c", $key);
                    if (in_array($key, $arr_notes))
                        {
                        $arr_pass[$col] = $main->purge_chars($arr_pass[$col], false);
                        }
                    elseif (in_array($key, $arr_file))
                        {
                        //not files
                        $arr_pass[$col] = "";
                        }
                    else //everthing else
                        {
                        $arr_pass[$col] = $main->purge_chars($arr_pass[$col]);
                        }
                    }                           

               /* DO VALIDATION */
                $build->hook("bb_upload_data_row_validation");
                
                if (count($arr_pass['arr_errors']) <> 0)
                    {
                    //FAILURE
                    $not_validated++;
                    foreach($arr_pass['arr_errors'] as $value)
                        {
                        array_push($arr_errors_all, $value);
                        }
                    $arr_errors_all = array_unique($arr_errors_all);
                    }
                else
                    {
                    /* DO INPUT */
                    $build->hook("bb_upload_data_row_input");
                    
                    $arr_messages_grep = preg_grep("/^Error:/i", $arr_pass['arr_messages']);                        
                    if (count($arr_messages_grep) > 0)
                        {
                        //FAILURE
                        $not_inputted++;
                        $arr_messages_all = array_unique($arr_messages_all + $arr_messages_grep);
                        }
                    else
                        {
                        //SUCCESS
                        //remove line on success
                        unset($arr_lines[$j]);
                        $inputted++;    
                        }
                    }                              
                }
            if (count($arr_lines) > 1)
                {
                $data_area = implode("\r\n", $arr_lines);
                }
            else
                {
                $data_area = "";    
                }
            }        
        else
            {
            array_push($arr_messages, "Error: Header row does not match the column names of layout chosen.");
            }
        }
        
    //pass back values
    $main->set('arr_messages', $arr_state, $arr_messages);
    $main->set('arr_errors_all', $arr_state, $arr_errors_all);
    $main->set('arr_messages_all', $arr_state, $arr_messages_all);
    $main->set('data_area', $arr_state, $data_area);
    $main->set('data_stats', $arr_state, array('inputted'=>$inputted, 'not_validated'=>$not_validated, 'not_inputted'=>$not_inputted));
    
    //update state, back to db
    $main->update($con, $submit, $arr_state);
   
    $postdata = json_encode($POST);
    
    //set $_POST for $POST
    $query = "UPDATE state_table SET postdata = $1 WHERE id = " . $keeper . ";";
    pg_query_params($con, $query, array($postdata));
    
    //REDIRECT
    $index_path = "Location: " . $webpath  . "/" . $slug;
    header($index_path);
    die();
endif;
?>