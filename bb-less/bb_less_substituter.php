<?php
/*
 * Copyright (C) Brimbox LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License Version 3 (�GNU GPL v3�)
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

/* This is a LESS type compiler that works just with variables for substitutions and imports of LESS files */
/* This is useful when you just want variable substituions and don't need the rest of the LESS specification */
/* This DOES NOT fully compile LESS and reads the whole LESS file being rendered in one string (careful with large files) */
/* This will allow imports of LESS variable files and renders the variables and substitutions in order declared */
/* This removes comments and whitespace rendering CSS as open styles for inclusion between style tags (not a CSS file) */
/* Dies on 4 errors, bad file extension, bad file or import statement, bad variable name, or empty variable value */
class bb_less_substituter {

    // main function
    function parse_less_file($infile, $outfile) {

        // get primary LESS file
        @$file_string = file_get_contents($infile);
        if (!$file_string) die("Cannot find file: " . $infile);
        $arr_file = preg_split("/(\r\n|\n|\r)/", $file_string);

        // parse primary LESS file
        $arr_stripped = $this->strip($file_string);
        // add eol for ending
        array_push($arr_stripped, "\n");
        $i = 0;

        // descend into recursion to get array_trans
        $this->recurse($arr_stripped, $arr_less_vars, $i);

        //substitute, eliminate and parse less file
        $j = 0;
        $output = "";
        foreach ($arr_file as $string) {
            if (((substr(trim($string), 0, 1) <> "@") || (substr(trim($string), 0, 6) == "@media"))) {
                $string = str_replace(array_keys($arr_less_vars), array_values($arr_less_vars), $string);
                if (trim($string) == "") {
                    $j++;
                }
                if (($j < 2) || (trim($string) <> "")) {
                    $output.= $string . "\r\n";
                    if (trim($string) <> "") {
                        $j = 0;
                    }

                }
            }
        }
        //write new css file
        file_put_contents($outfile, $output);
    }

    // load file into string and explode into array
    protected function strip($file_string) {

        // remove comments
        $file_string = preg_replace('!/\*.*?\*/!s', '', $file_string);
        // turn into tokens
        $arr_exploded = explode(";", $file_string);
        $arr_exploded = array_map('trim', $arr_exploded);
        $arr_exploded = array_filter($arr_exploded);
        return $arr_exploded;
    }

    // recurse and parse file substitutiing, importing and outputing
    protected function recurse(&$arr_stripped, &$arr_less_vars, &$i) {

        $next = $arr_stripped[$i];
        $i++;
        // import line
        if (substr($next, 0, 7) == "@import") {
            preg_match('/"([^"]+)"/', $next, $file_array);
            @$file_string = file_get_contents($file_array[1]);
            if (!$file_string) die("Cannot find @import file: " . $file_array[1]);
            $arr_splice = $this->strip($file_string);
            array_splice($arr_stripped, $i + 1, 0, $arr_splice);
            $this->recurse($arr_stripped, $arr_less_vars, $i);
        }
        elseif (substr($next, 0, 1) == "@") {
            $arr_vars = explode(":", $next, 2);
            $arr_vars = array_map('trim', $arr_vars);
            $arr_less_vars[$arr_vars[0]] = $arr_vars[1];
            $this->recurse($arr_stripped, $arr_less_vars, $i);
        }
        elseif ($next != "\n") {
            $this->recurse($arr_stripped, $arr_less_vars, $i);
        }
        else {
            return false;
        }
    }
} // end class

?>