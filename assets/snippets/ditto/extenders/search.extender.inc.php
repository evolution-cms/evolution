<?php

/*
 * Title:       Search Filter for Ditto
 * Version:     2.0
 * Purpose:     Expands Ditto's functionality to include filtering search results,
 *              using simple text search, regular expressions or php code snippets
 *              for advanced search functions
 *
 * License:     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * Author:      sam (sam@gmx-topmail.de)
 * www:         https://github.com/Sammyboy/MODx-Ditto-Extenders
 *
 * Installation:
 *      Copy this file into "assets/snippets/ditto/extenders/" of your MODx
 *      installation folder
 *
 * Usage:
 *      [!Ditto? &extenders=`search` &searchString=`my search string` &searchFields=`content,tv1,tv2` &searchOptions=`caseSensitive` ... !]
*/

// ---------------------------------------------------
// Search Parameters
// ---------------------------------------------------

$searchFields = isset($searchFields) ?
    preg_replace('/[\n|\r|\t]+/', '', $searchFields) :
    "content";
/*
    Param:      searchFields
    Purpose:    Fields to search in
    Options:    Comma separated list of document variables and/or template variables
                used by Ditto
    Default:    "content"
*/

$searchOptions = isset($searchOptions) ? $searchOptions : "";
/*
    Param:      searchOptions
    Purpose:    Search Options

    Options:

        "caseSensitive": Get case sensitive results only
                Example call:
                    &searchOptions=`caseSensitive`
                    &searchString=`test`

        "regex":    Search for regular expressions
                Example call:
                    &searchOptions=`regex`
                    &searchString=`/test/i`
                    (case insensitive search for "test")

        "snippet":  Search use Snippet to get search results
                Example call:
                    &searchOptions=`snippet: mySearchSnippet, param1: value1, param2: value2, paramTree: another value`
                    &searchString=`test`
                The same call using JSON configuration string:
                    &searchOptions=`{"snippet":"mySearchSnippet","param1":"value1","param2":"value2","paramTree":"another value"}`
                    &searchString=`test`
                    
                Using @FILE for including snippet file:
                    &searchOptions=`snippet: @FILE assets/snippets/ditto/extenders/searchRule.php, param1: value1, param2: value2`
                    &searchString=`test`
                Or use JSON string:
                    &searchOptions=`{"snippet":"@FILE assets/snippets/ditto/extenders/searchRule.php","param1":"value1","param2":"value2"}`
                    &searchString=`test`

                Using @EVAL and @CHUNK:
                    &searchOptions=`@CHUNK dittoSearchOptions`
                    &searchString=`test`
                    &searchOptionsSeparators=`{"outer":"||","inner":"::"}`

                    Content of the chunk "dittoSearchOptions":
                        snippet::@EVAL return (stripos($searchContent, $searchString) === false) ? false : ((stripos($searchContent, $and_not) === false) ? true : false);||and_not::sub

                Or use JSON string:
                    &searchOptions=`@CHUNK dittoSearchOptions`
                    &searchString=`test`

                    Content of the chunk "dittoSearchOptions":
                        {"snippet":"@EVAL return (stripos($searchContent, $searchString) === false) ? false : ((stripos($searchContent, $and_not) === false) ? true : false);","and_not":"sub"}
*/

$searchString = isset($searchString) ? $searchString : "";
/*
    Param:      searchString
    Purpose:    JSON string with separators for the options list
    Options:    Any string or
                
        @FILE -   Search for the content of a file
                    Example call:
                        &searchOptions=`regex`
                        &searchString=`@FILE assets/snippets/ditto/extenders/regexSearch.txt`

        @CHUNK -  Search for the content of a chunk
                    Example call:
                        &searchOptions=`regex`
                        &searchString=`@CHUNK regexSearchChunk`


    Default:    ""
*/

$searchOptionsSeparators = isset($searchOptionsSeparators) ? $searchOptionsSeparators : null;
/*
    Param:      searchOptionsSeparators
    Purpose:    JSON string with separators for the options list
    Options:    Any string
                @FILE
                @CHUNK
    Default:    '{"outer":",","inner":"="}'
*/

// ---------------------------------------------------
// Search Filter Class
// ---------------------------------------------------

if (!class_exists("searchFilter")) {
    class searchFilter {
        var $searchFunction;
        private $sourceFields, $searchOptions, $searchString, $snippet, $options, $function_code, $source, $separators;

        function searchFilter($searchString = "", $sourceFields = "content", $searchOptions = "", $separators = null) {
            global $modx;

            $functions = array('snippet', 'regex', 'case_sensitive');
            $this->searchOptions = $this->getSource($searchOptions);
            if (!($this->options = json_decode($this->searchOptions, true))) {
                $this->separators = (isset($separators) && ($new_separators = json_decode($this->getSource($separators), true))) ?
                    $new_separators : array('outer' => ',', 'inner' => ':');
                $this->options = $this->parseOptions($this->searchOptions, $this->separators["outer"], $this->separators["inner"]);
            }

            if ($func = array_intersect_key($this->options, array_flip($functions))) {
                $this->searchFunction = key($func);
                $this->options[$this->searchFunction] = $this->getSource($this->options[$this->searchFunction]);
                if (($this->searchFunction === 'snippet') && isset($this->source))
                    $this->searchFunction = 'eval';

            } else
                $this->searchFunction = "default";

            $this->searchString = $this->getSource($searchString);

            if ($this->searchFunction === 'eval')
                $this->function_code = trim($this->options['snippet'], " <>?ph\n\r\t");
            elseif ($this->searchFunction === 'snippet') {
                $this->snippet = $this->options[$this->searchFunction];
                unset($this->options['snippet']);
            }

            if (($this->searchFunction === 'eval') || ($this->searchFunction === 'snippet')) {
                if (!isset($this->options["searchString"]))
                    $this->options["searchString"] = $this->searchString;
            }

            $this->sourceFields = explode(",", $this->getSource($sourceFields));
            $this->searchFunction = ucfirst($this->searchFunction);
        }

        private function parseOptions($options) {
            $new_options = array();
            $options = explode($this->separators['outer'], $options);
            foreach ($options as $option) {
                list($key, $val) = (($pos = strpos($option, $this->separators['inner'])) === false) ?
                    array(trim($option), true) :
                    array(trim(substr($option, 0, $pos)), ltrim(substr($option, $pos + strlen($this->separators['inner']))));
                $new_options[$key] = $val;
            }
            return $new_options;
        }

        private function getSource($string) {
            global $modx;

            $this->source = 1;
            if (stripos(($string = ltrim($string)), "@file") === 0)
                $string = (($content = file_get_contents($name = trim(substr($string, 5), ": "))) === false) ? $name : $content;
            elseif (stripos($string, "@chunk") === 0)
                $string = (($content = $modx->getChunk($name = trim(substr($string, 6), ": "))) === false) ? $name : $content;
            elseif (stripos($string, "@eval") === 0) {
                $string = trim(substr($string, 5), ": ");
                return $string;
            } else {
                $this->source = null;
                return $string;
            }
            
            return $this->getSource($string);
        }

        function executeSnippet($resource) {
            global $modx;

            $result = 0;

            foreach ($this->sourceFields as $field) {
                $this->options["searchContent"] = $resource[$field];
                if ($modx->runSnippet($this->custom_function, $this->options))
                    $result = 1;
            }
            return $result;
        }

        function executeEval($resource) {
            global $modx;
            $result = 0;

            extract($this->options);
            foreach ($this->sourceFields as $field) {
                $searchContent = $resource[$field];
                if (eval($this->function_code))
                    $result = 1;
            }
            return $result;
        }

        function executeRegex($resource) {
            $result = 0;

            foreach ($this->sourceFields as $field) {
                if (preg_match($this->searchString, $resource[$field]))
                    $result = 1;
            }

            return $result;
        }

        function executeCase_sensitive($resource) {
            $result = 0;

            foreach ($this->sourceFields as $field) {
                if (strpos($resource[$field], $this->searchString) !== false)
                    $result = 1;
            }

            return $result;
        }

        function executeDefault($resource) {
            $result = 0;

            foreach ($this->sourceFields as $field) {
                 if (mb_stripos($resource[$field], $this->searchString,0,"UTF-8") !== false)
                    $result = 1;
            }

            return $result;
        }
    }
}

// ---------------------------------------------------
// Search Filter Execution
// ---------------------------------------------------
if (isset($searchString)) {
    $searchFilter = new searchFilter($searchString, $searchFields, $searchOptions, $searchOptionsSeparators);
    $filters["custom"]["searchFilter"] = array($searchFields,array($searchFilter,"execute".$searchFilter->searchFunction));
}

?>
