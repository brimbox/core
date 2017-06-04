<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php
/*
 * Copyright (C) Brimbox LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License Version 3 (GNU GPL v3)
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU GPL v3 for more details.
 *
 * You should have received a copy of the GNU GPL v3 along with this program.
 * If not, see http://www.gnu.org/licenses/
*/

/* Regular Functions */
//this is where translation happens
//string only
function __($var) {

    return htmlentities($var, ENT_COMPAT | ENT_HTML401, "UTF-8");
}

//string only
function __e($var) {

    echo __($var);
}

//string only
function __t($var, $module, $substitute = array()) {

    global $ {
        $module . "_translate"
    };

    $translate = $ {
        $module . "_translate"
    };
    if (isset($translate[$var]) && $translate[$var] !== "") $var = $translate[$var];

    array_unshift($substitute, $var);
    $var = call_user_func_array('sprintf', $substitute);

    return __($var);
}

//string only
function __te($var, $module, $substitute = array()) {

    echo __t($var, $module, $substitute = array());
}

//option, string or array
function __a($var) {

    if (is_string($var)) return __($var);
    elseif (is_array($var)) return array_map('__', $var);
}

?>