<!DOCTYPE html>
<meta charset="UTF-8">
<html>
<body>
<?php

function __t($var, $module, $substitute = array()) {
    
    global ${$module . "_translate"};
    
    $translate = ${$module . "_translate"};    
    if (isset($translate[$var]) || $translate[$var] !== "")
        $var = $translate[$var];
        
    array_unshift($substitute, $var);
    $var = call_user_func_array('sprintf', $substitute);
        
    return htmlentities($var, ENT_COMPAT | ENT_HTML401, "UTF-8");
}

$module = "bb_box";

$str1 = "Hello \"%s\" %s World";
$str2 = "Hola \"%s\" %s Mundo! 2";

$bb_box_translate = array($str1 => $str2);

echo __t("Hello \"%s\" %s World", $module, array("商","È"));

echo "<br><br>";

//$test = "this \"is\" test.";

//$arr_test[$test] = "Worked";

//var_dump($arr_test);

//echo "<br><br>";

//echo $arr_test[$test];

?>
</body>
</html>