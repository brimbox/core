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
class php_boolean_validator {

    /* MAIN FUNCTION */
    function parse_boolean_string(&$boolean_string, &$boolean_tokens = null) {

        global $module;

        /* THINKING AHEAD FOR CUSTOM ERRORS */
        $error_empty = "Enter Search Terms or Tokens";
        $error_beginning = "Error at beginning of boolean expression";
        $error_unbalanced = "Search term(s) have unbalanced parenthesis";
        $error_expression = "Error in boolean expression at token %s near %s";
        $error_ending = "Unexpected end of boolean expression";
        $error_output = "Tokens contain boolean output value to be substituted";

        /* DO NOT SPLICE ORS DEFAULT */
        $this->splice_or_tokens = isset($this->splice_or_tokens) ? $this->splice_or_tokens : false;

        /* CONTAINS WATERFALL RETURNS */
        if (trim($boolean_string) == "") {
            // return and exit on empty string
            return __t($error_empty, $module);
        }

        /* GET IN AND OUT FORMATS */
        $boolean_work = array('and' => '&', 'or' => '|', 'not' => '!', 'open' => '(', 'closed' => ')');
        $boolean_parse = (isset($this->boolean_parse)) ? $this->boolean_parse : $boolean_work;
        $boolean_return = (isset($this->boolean_return)) ? $this->boolean_return : $boolean_parse;

        /* TOKENIZE BOOLEAN STRING */
        // purge unwanted chars
        $boolean_string = str_replace(array("\r", "\n", "\t"), "", $boolean_string);
        // get regex to parse on
        $booleans_regex = implode("|", array_map('preg_quote', $boolean_parse)) . "|\s";
        // split up tokens and operators
        $tokens = preg_split("/(" . $booleans_regex . ")/i", $boolean_string, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        // get rid of space tokens
        $tokens = array_diff($tokens, array(" "));
        // trim everything in case of space problems
        $tokens = array_map('trim', $tokens);
        // trim boolean in for substitution
        $booleans = array_map('trim', $boolean_parse);
        // add eol for simplicity
        $booleans['eol'] = "\n";
        // use new line as eol, after trim
        array_push($tokens, "\n");
        // re-increment array
        $tokens = array_merge($tokens);

        /* CHECK FOR UNBALANCED PARENTHESIS */
        $i = 0; // count parenthesis
        foreach ($tokens as $token) {
            if ($token == ")") {
                $i++;
            }
            elseif ($token == "(") {
                $i--;
            }
        }
        // return and exit on unbalanced parenthesis
        if ($i != 0) {
            return __t($error_unbalanced, $module);
        }

        // SPLICE CONJOINING TOKENS WITH PIPE FOR OR
        if ($this->splice_or_tokens) {
            $arr_splice = array();
            for ($i = 1;$i < count($tokens);$i++) {
                // ajoining tokens
                if (!in_array($tokens[$i - 1], $booleans) && !in_array($tokens[$i], $booleans)) {
                    array_push($arr_splice, $i);
                }
            }
            $i = 0; // increase of offset when splicing
            foreach ($arr_splice as $key) {
                array_splice($tokens, $key + $i, 0, $booleans['or']);
                $i++;
            }
        }

        /* ENTER RECURSIVE DESCENT PARSER */
        // message and tokens passed by value
        $i = 0; // token position
        $next = $tokens[$i];
        $boolean_tokens = array();
        // deal with first token
        if (!in_array($next, $booleans)) {
            // pointer is a token
            // custom splice for brimbox
            $boolean_tokens[] = $next; // save token
            $this->closed($booleans, $tokens, $i, $error, $boolean_tokens);
        }
        elseif (in_array($next, array($booleans['open'], $booleans['not']))) {
            // pointer is an open parenthesis or not
            $this->open($booleans, $tokens, $i, $error, $boolean_tokens);
        }
        else {
            // bad start
            return __t($error_beginning, $module);
        }

        /* CHECK TOKENS FOR EXISTANCE OF OUTPUT SUBSTITUTION TOKENS */
        if (count(array_intersect($boolean_return, $boolean_tokens)) > 0) {
            return __t($error_output, $module);
        }

        /* PROCESS RESULT OF PARSE */
        // error could be boolean, string, or array
        if (is_string($error)) {
            /* BAD BEGINNING OR UNEXPECTED END */
            return __t($error_ending, $module);
        }
        elseif (is_array($error)) {
            /* ERROR IN EXPRESSION */
            if (is_string($error)) {
                /* BAD BEGINNING OR UNEXPECTED END */
                return __($error_ending, $module);
            }
            elseif (is_array($error)) {
                /* ERROR IN EXPRESSION */
                $position = $error[0] + 1;
                if ($key = array_search($error[1], $boolean_work)) {
                    $mistake = $booleans[$key];
                }
                else {
                    $mistake = $error[1];
                }
                return __t($error_expression, $module, array($position, $mistake));
            }
        }
        else {
            /* UNSET EOL ON GOOD PARSE */
            array_pop($tokens);

            /* SUBSTITUTE RETURN FORMAT */
            $arr_callback = array('from' => $booleans, 'to' => $boolean_return);
            array_walk($tokens, array($this, 'substitute'), $arr_callback);

            if ($this->splice_wildcard == 1) {
                $tokens = array_map(array($this, 'wildcard'), $tokens);
            }

            /* SUCCESSFUL PARSE - IMPLODE, TRIM AND RETURN FALSE */
            // $boolean_string passed as a value
            $boolean_string = implode($tokens);

            return false;
        }
    }

    /* END MAIN FUNCTION */
    protected function wildcard(&$item) {

        if (preg_match("/.+\*$/", $item)) {
            $item = substr_replace($item, ":*", -1);
        }
        return $item;
    }

    /* SUBSTITUTION CALLBACK */
    protected function substitute(&$item, $key, $arr) {
        // for substituting boolean tokens
        $key = array_search($item, $arr['from']);
        if ($key) {
            $item = $arr['to'][$key];
        }
    }

    /* RECURSIVE DESCENT PARSING FUNCTIONS */
    protected function open($booleans, $tokens, &$i, &$error, &$boolean_tokens) {
        // comes from an open parenthesis or not
        $i++;
        $next = $tokens[$i];
        if (!in_array($next, $booleans)) {
            // pointer is a token
            // custom splice for brimbox
            $boolean_tokens[] = $next; // save token
            $this->closed($booleans, $tokens, $i, $error, $boolean_tokens);
        }
        elseif (in_array($next, array($booleans['open'], $booleans['not']))) {
            // pointer is open parenthesis or not
            $this->open($booleans, $tokens, $i, $error, $boolean_tokens);
        }
        else {
            // error found
            $error = ($next == "\n") ? "String" : array($i, $next);
        }
    }

    protected function operator($booleans, $tokens, &$i, &$error, &$boolean_tokens) {
        // comes from an operator
        $i++;
        $next = $tokens[$i];
        if (!in_array($next, $booleans)) {
            // pointer is a token
            // custom splice for brimbox
            $boolean_tokens[] = $next;
            $this->closed($booleans, $tokens, $i, $error, $boolean_tokens);
        }
        elseif (in_array($next, array($booleans['open'], $booleans['not']))) {
            // pointer is open parenthesis or not
            $this->open($booleans, $tokens, $i, $error, $boolean_tokens);
        }
        else {
            // error found, string is error at end, array is error in middle
            $error = ($next == "\n") ? "String" : array($i, $next);
        }
    }

    protected function closed($booleans, &$tokens, &$i, &$error, &$boolean_tokens) {
        // comes from closed parenthesis or token
        $i++;
        $next = $tokens[$i];
        if (in_array($next, array($booleans['and'], $booleans['or']))) {
            // pointer is an operator
            $this->operator($booleans, $tokens, $i, $error, $boolean_tokens);
        }
        elseif (in_array($next, array($booleans['closed']))) {
            // pointer is a closed
            $this->closed($booleans, $tokens, $i, $error, $boolean_tokens);
        }
        elseif ($next == "\n") {
            // end of descent, no errors
            $error = false;
        }
        else {
            // error found
            $error = array($i, $next);
        }
    }
}
?>
