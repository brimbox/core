<?php
/* SET UP MAIN OBJECT AND POST STUFF */
//objects are all daisy chained together
//set up main from last object
// contains bb_database class, extends bb_main

/* NO HTML OUTPUT */

//get from $_SESSION
$abspath = $_SESSION['abspath'];

include($abspath . "/bb-utilities/bb_database.php");
//contains bb_validation class, extend bb_links
include($abspath . "/bb-utilities/bb_validate.php");
//contains bb_work class, extends bb_forms
include($abspath . "/bb-utilities/bb_meta.php");		
/* these classes only brought into both $main */
include($abspath . "/bb-utilities/bb_work.php");		
/* these classes only brought into both $main */
include($abspath . "/bb-utilities/bb_links.php");
//contains bb_form class, extends bb_validate
include($abspath . "/bb-utilities/bb_forms.php");
//contains bb_report class, extend bb_hooks
include($abspath . "/bb-utilities/bb_reports.php");
//contains bb_main class
include($abspath . "/bb-utilities/bb_main.php");

?>