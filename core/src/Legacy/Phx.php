<?php namespace EvolutionCMS\Legacy;

use EvolutionCMS\Core;

/**
 * @deprecated
 * Name: PHx (Placeholders Xtended)
 * Version: 2.2.1
 * Modified by Agel Nash (modx@agel-nash.ru)
 * Modified by Nick to include external files
 * Modified by Anton Kuzmin for using of modx snippets cache
 * Modified by Temus (temus3@gmail.com)
 * Author: Armand "bS" Pondman (apondman@zerobarrier.nl)
 * Date: November 14, 2018
 */
class Phx
{
    public $placeholders = array();
    public $name = 'PHx';
    public $version = '2.2.0';
    public $user = array();
    public $cache = array(
        'cm' => array(),
        'ui' => array(),
        'mo' => array()
    );
    public $safetags = array(
        array('~(?<![\[]|^\^)\[(?=[^\+\*\(\[]|$)~s', '~(?<=[^\+\*\)\]]|^)\](?=[^\]]|$)~s'),
        array('&_PHX_INTERNAL_091_&', '&_PHX_INTERNAL_093_&'),
        array('[', ']')
    );
    public $console = array();
    public $debug = false;
    public $debugLog = false;
    public $curPass = 0;
    public $maxPasses = 50;
    public $swapSnippetCache = array();
    protected $modx = null;

    /**
     * DLphx constructor.
     * @param Core $modx
     * @param int|bool|string $debug
     * @param int $maxpass
     */
    public function __construct(...$args)
    {
        $modx = get_by_key($args, 0);
        if (! $modx instanceof Core) {
            $modx = evo();
        }

        $this->modx = $modx;
        $this->user["mgrid"] = isset($_SESSION['mgrInternalKey']) ? intval($_SESSION['mgrInternalKey']) : 0;
        $this->user["usrid"] = isset($_SESSION['webInternalKey']) ? intval($_SESSION['webInternalKey']) : 0;
        $this->user["id"] = ($this->user["usrid"] > 0) ? (-$this->user["usrid"]) : $this->user["mgrid"];

        $this->debug = (bool)get_by_key($args, 1, false);

        $this->maxPasses = (int)get_by_key($args, 2, 50);

        $this->modx->setPlaceholder("phx", "&_PHX_INTERNAL_&");
        if (function_exists('mb_internal_encoding')) {
            mb_internal_encoding($this->modx->config['modx_charset']);
        }
    }

    // Plugin event hook for MODx
    public function OnParseDocument()
    {
        // Get document output from MODx
        $template = $this->modx->documentOutput;
        // To the parse cave .. let's go! *insert batman tune here*
        $template = $this->Parse($template);
        // Set processed document output in MODx
        $this->modx->documentOutput = $template;
    }

    // Parser: Preparation, cleaning and checkup

    /**
     * @param string $template
     * @return mixed|string
     */
    public function Parse($template = '')
    {
        // If we already reached max passes don't get at it again.
        if ($this->curPass == $this->maxPasses) {
            return $template;
        }
        // Set template pre-process hash
        $st = md5($template);
        // Replace non-call characters in the template: [, ]
        $template = preg_replace($this->safetags[0], $this->safetags[1], $template);
        // To the parse mobile.. let's go! *insert batman tune here*
        $template = $this->ParseValues($template);
        // clean up unused placeholders that have modifiers attached (MODx can't clean them)
        preg_match_all('~(?:=`[^`@]*?)(\[\+([^:\+\[\]]+)([^\[\]]*?)\+\])~s', $template, $matches);
        if ($matches[0]) {
            $template = str_replace($matches[1], '', $template);
            $this->Log("Cleaning unsolved tags: \n" . implode("\n", $matches[2]));
        }
        // Restore non-call characters in the template: [, ]
        $template = str_replace($this->safetags[1], $this->safetags[2], $template);
        // Set template post-process hash
        $et = md5($template);
        // If template has changed, parse it once more...
        if ($st != $et) {
            $template = $this->Parse($template);
        }
        // Write an event log if debugging is enabled and there is something to log
        if ($this->debug && $this->debugLog) {
            $this->modx->logEvent($this->curPass, 1, $this->createEventLog(), $this->name . ' ' . $this->version);
            $this->debugLog = false;
        }

        // Return the processed template
        return $template;
    }

    // Parser: Tag detection and replacements

    /**
     * @param string $template
     * @return mixed|string
     */
    public function ParseValues($template = '')
    {
        $this->curPass = $this->curPass + 1;
        $st = md5($template);

        $this->LogPass();
        // MODX Chunks
        if (preg_match_all('~(?<!(?:then|else)=`){{([^:\+{}]+)([^{}]*?)}}~s', $template, $matches)) {
            $this->Log('MODX Chunks -> Merging all chunk tags');
            $count = count($matches[0]);
            $var_search = array();
            $var_replace = array();
            for ($i = 0; $i < $count; $i++) {
                $var_search[] = $matches[0][$i];
                $input = $matches[1][$i];
                $this->Log('MODX Chunk: ' . $input);
                $input = $this->modx->mergeChunkContent('{{' . $input . '}}');
                $var_replace[] = $this->Filter($input, $matches[2][$i]);
            }
            $template = str_replace($var_search, $var_replace, $template);
        }

        // MODx Snippets
        //if ( preg_match_all('~\[(\[|!)([^\[]*?)(!|\])\]~s',$template, $matches)) {
        if (preg_match_all('~(?<!(?:then|else)=`)\[(\[)([^\[]*?)(\])\]~s', $template, $matches)) {
            $count = count($matches[0]);
            $var_search = array();
            $var_replace = array();

            // for each detected snippet
            for ($i = 0; $i < $count; $i++) {
                $snippet = $matches[2][$i]; // snippet call
                $this->Log("MODx Snippet -> " . $snippet);

                // Let MODx evaluate snippet
                $replace = $this->modx->evalSnippets("[[" . $snippet . "]]");
                $this->LogSnippet($replace);

                // Replace values
                $var_search[] = $matches[0][$i];
                $var_replace[] = $replace;
            }
            $template = str_replace($var_search, $var_replace, $template);
        }

        // PHx / MODx Tags
        if (preg_match_all('~\[(\+|\*|\()([^:\+\[\]]+)([^\[\]]*?)(\1|\))\]~s', $template, $matches)) {
            //$matches[0] // Complete string that's need to be replaced
            //$matches[1] // Type
            //$matches[2] // The placeholder(s)
            //$matches[3] // The modifiers
            //$matches[4] // Type (end character)

            $count = count($matches[0]);
            $var_search = array();
            $var_replace = array();
            for ($i = 0; $i < $count; $i++) {
                $input = $matches[2][$i];
                $modifiers = $matches[3][$i];
                $var_search[] = $matches[0][$i];
                switch ($matches[1][$i]) {
                    // Document / Template Variable eXtended
                    case "*":
                        $this->Log("MODx TV/DV: " . $input);
                        $input = $this->modx->mergeDocumentContent("[*" . $input . "*]");
                        $replace = $this->Filter($input, $modifiers);
                        break;
                    // MODx Setting eXtended
                    case "(":
                        $this->Log("MODx Setting variable: " . $input);
                        $input = $this->modx->mergeSettingsContent("[(" . $input . ")]");
                        $replace = $this->Filter($input, $modifiers);
                        break;
                    // MODx Placeholder eXtended
                    default:
                        $this->Log("MODx / PHx placeholder variable: " . $input);
                        // Check if placeholder is set
                        if (! array_key_exists($input, $this->placeholders) &&
                            ! array_key_exists($input, $this->modx->placeholders)
                        ) {
                            // not set so try again later.
                            $input = '';
                        } else {
                            // is set, get value and run filter
                            $input = $this->getPHxVariable($input);
                        }
                        $replace = $this->Filter($input, $modifiers);
                        break;
                }
                $var_replace[] = $replace;
            }
            $template = str_replace($var_search, $var_replace, $template);
        }
        $et = md5($template); // Post-process template hash

        // Log an event if this was the maximum pass
        if ($this->curPass == $this->maxPasses) {
            $this->Log("Max passes reached. infinite loop protection so exiting.\n If you need the extra passes set the max passes to the highest count of nested tags in your template.");
        }
        // If this pass is not at maximum passes and the template hash is not the same, get at it again.
        if (($this->curPass < $this->maxPasses) && ($st != $et)) {
            $template = $this->ParseValues($template);
        }

        return $template;
    }

    // Parser: modifier detection and eXtended processing if needed

    /**
     * @param $input
     * @param $modifiers
     * @return mixed|null|string
     */
    public function Filter($input, $modifiers)
    {
        $output = $input;
        $this->Log("  |--- Input = '" . $output . "'");
        if (preg_match_all('~:([^:=]+)(?:=`(.*?)`(?=:[^:=]+|$))?~s', $modifiers, $matches)) {
            $modifier_cmd = $matches[1]; // modifier command
            $modifier_value = $matches[2]; // modifier value
            $count = count($modifier_cmd);
            $condition = array();
            for ($i = 0; $i < $count; $i++) {
                $output = trim($output);
                $this->Log("  |--- Modifier = '" . $modifier_cmd[$i] . "'");
                if ($modifier_value[$i] != '') {
                    $this->Log("  |--- Options = '" . $modifier_value[$i] . "'");
                }
                switch ($modifier_cmd[$i]) {
                    #####  Conditional Modifiers
                    case "input":
                    case "if":
                        $output = $modifier_value[$i];
                        break;
                    case "equals":
                    case "is":
                    case "eq":
                        $condition[] = intval(($output == $modifier_value[$i]));
                        break;
                    case "empty":
                        $condition[] = intval(empty($output));
                        break;
                    case "notequals":
                    case "isnot":
                    case "isnt":
                    case "ne":
                        $condition[] = intval(($output != $modifier_value[$i]));
                        break;
                    case "isgreaterthan":
                    case "isgt":
                    case "eg":
                        $condition[] = intval(($output >= $modifier_value[$i]));
                        break;
                    case "islowerthan":
                    case "islt":
                    case "el":
                        $condition[] = intval(($output <= $modifier_value[$i]));
                        break;
                    case "greaterthan":
                    case "gt":
                        $condition[] = intval(($output > $modifier_value[$i]));
                        break;
                    case "lowerthan":
                    case "lt":
                        $condition[] = intval(($output < $modifier_value[$i]));
                        break;
                    case "isinrole":
                    case "ir":
                    case "memberof":
                    case "mo": // Is Member Of  (same as inrole but this one can be stringed as a conditional)
                        if ($output == "&_PHX_INTERNAL_&") {
                            $output = $this->user["id"];
                        }
                        $grps = ($this->strlen($modifier_value[$i]) > 0) ? explode(",", $modifier_value[$i]) : array();
                        $condition[] = intval($this->isMemberOfWebGroupByUserId($output, $grps));
                        break;
                    case "or":
                        $condition[] = "||";
                        break;
                    case "and":
                        $condition[] = "&&";
                        break;
                    case "show":
                        $conditional = implode(' ', $condition);
                        $isvalid = intval($this->runCode($conditional));
                        if (!$isvalid) {
                            $output = null;
                        }
                        break;
                    case "then":
                        $conditional = implode(' ', $condition);
                        $isvalid = intval($this->runCode($conditional));
                        if ($isvalid) {
                            $output = $modifier_value[$i];
                        } else {
                            $output = null;
                        }
                        break;
                    case "else":
                        $conditional = implode(' ', $condition);
                        $isvalid = intval($this->runCode($conditional));
                        if (!$isvalid) {
                            $output = $modifier_value[$i];
                        }
                        break;
                    case "select":
                        $raw = explode("&", $modifier_value[$i]);
                        $map = array();
                        $count = count($raw);
                        for ($m = 0; $m < $count; $m++) {
                            $mi = explode("=", $raw[$m]);
                            $map[$mi[0]] = $mi[1];
                        }
                        $output = $map[$output];
                        break;
                    ##### End of Conditional Modifiers

                    #####  String Modifiers
                    case "default":
                        $output = ($output === '') ? $modifier_value[0] : $output;
                        break;
                    case "lcase":
                    case "strtolower":
                        $output = $this->strtolower($output);
                        break;
                    case "ucase":
                    case "strtoupper":
                        $output = $this->strtoupper($output);
                        break;
                    case "ucfirst":
                        $output = $this->ucfirst($output);
                        break;
                    case "lcfirst":
                        $output = $this->lcfirst($output);
                        break;
                    case "ucwords":
                        $output = $this->ucwords($output);
                        break;
                    case "htmlent":
                    case "htmlentities":
                        $output = htmlentities($output, ENT_QUOTES, $this->modx->config['modx_charset']);
                        break;
                    case "html_entity_decode":
                        $output = html_entity_decode($output, ENT_QUOTES, $this->modx->config['modx_charset']);
                        break;
                    case "esc":
                        $output = preg_replace("/&amp;(#[0-9]+|[a-z]+);/i", "&$1;", e($output));
                        $output = str_replace(array("[", "]", "`"), array("&#91;", "&#93;", "&#96;"), $output);
                        break;
                    case "strip":
                        $output = preg_replace("~([\n\r\t\s]+)~", " ", $output);
                        break;
                    case "notags":
                    case "strip_tags":
                        $output = strip_tags($output);
                        break;
                    case "length":
                    case "len":
                    case "strlen":
                        $output = $this->strlen($output);
                        break;
                    case "reverse":
                    case "strrev":
                        $output = $this->strrev($output);
                        break;
                    case "wordwrap": // default: 70
                        $wrapat = intval($modifier_value[$i]) ? intval($modifier_value[$i]) : 70;
                        $output = preg_replace_callback("@(\b\w+\b)@", function ($m) use ($wrapat) {
                            return wordwrap($m[1], $wrapat, ' ', 1);
                        }, $output);
                        break;
                    case "limit": // default: 100
                        $limit = intval($modifier_value[$i]) ? intval($modifier_value[$i]) : 100;
                        $output = $this->substr($output, 0, $limit);
                        break;
                    case "str_shuffle":
                    case "shuffle":
                        $output = $this->str_shuffle($output);
                        break;
                    case "str_word_count":
                    case "word_count":
                    case "wordcount":
                        $output = $this->str_word_count($output);
                        break;

                    #####  Special functions
                    case "math":
                        $filter = preg_replace("~([a-zA-Z\n\r\t\s])~", "", $modifier_value[$i]);
                        $filter = str_replace("?", $output, $filter);
                        $output = eval("return " . $filter . ";");
                        break;
                    case "isnotempty":
                        if (!empty($output)) {
                            $output = $modifier_value[$i];
                        }
                        break;
                    case "isempty":
                    case "ifempty":
                        if (empty($output)) {
                            $output = $modifier_value[$i];
                        }
                        break;
                    case "nl2br":
                        $output = nl2br($output);
                        break;
                    case "date":
                        $output = strftime($modifier_value[$i], (int)$output);
                        break;
                    case "set":
                        $c = $i + 1;
                        if ($count > $c && $modifier_cmd[$c] == "value") {
                            $output = preg_replace("~([^a-zA-Z0-9])~", "", $modifier_value[$i]);
                        }
                        break;
                    case "value":
                        if ($i > 0 && $modifier_cmd[$i - 1] == "set") {
                            $this->modx->SetPlaceholder("phx." . $output, $modifier_value[$i]);
                        }
                        $output = null;
                        break;
                    case "md5":
                        $output = md5($output);
                        break;
                    case 'inarray':
                    case 'in_array':
                    case 'in':
                        $modifier_value[$i] = explode(',', $modifier_value[$i]);
                        $condition[] = (int)(in_array($output, $modifier_value[$i]) !== false);
                        break;
                    case "userinfo":
                        if ($output == "&_PHX_INTERNAL_&") {
                            $output = $this->user["id"];
                        }
                        $output = $this->ModUser($output, $modifier_value[$i]);
                        break;
                    case "inrole": // deprecated
                        if ($output == "&_PHX_INTERNAL_&") {
                            $output = $this->user["id"];
                        }
                        $grps = ($this->strlen($modifier_value[$i]) > 0) ? explode(",", $modifier_value[$i]) : array();
                        $output = intval($this->isMemberOfWebGroupByUserId($output, $grps));
                        break;

                    // If we haven't yet found the modifier, let's look elsewhere
                    default:
                        $snippet = '';
                        // modified by Anton Kuzmin (23.06.2010) //
                        $snippetName = 'phx:' . $modifier_cmd[$i];
                        if (array_key_exists($snippetName, $this->modx->snippetCache)) {
                            $snippet = $this->modx->snippetCache[$snippetName];
                        } else {
// not in cache so let's check the db
                            $snippetObject = $this->modx->getSnippetFromDatabase($snippetName);
                            $this->modx->snippetCache[$snippetObject['name']] = $snippetObject['content'];
                            $this->modx->snippetCache[$snippetObject['name'] . 'Props'] = $snippetObject['properties'];
                            if ($snippetObject['content'] !== null) {
                                $snippet = $snippetObject['content'];
                                $this->Log("  |--- DB -> Custom Modifier");
                            } else {
                                // If snippet not found, look in the modifiers folder
                                $filename = $this->modx->getConfig('rb_base_dir') . 'plugins/phx/modifiers/' . $modifier_cmd[$i] . '.phx.php';
                                if (@file_exists($filename)) {
                                    $file_contents = @file_get_contents($filename);
                                    $file_contents = str_replace('<' . '?php', '', $file_contents);
                                    $file_contents = str_replace('?' . '>', '', $file_contents);
                                    $file_contents = str_replace('<?', '', $file_contents);
                                    $snippet = $this->modx->snippetCache[$snippetName] = $file_contents;
                                    $this->modx->snippetCache[$snippetName . 'Props'] = '';
                                    $this->Log("  |--- File ($filename) -> Custom Modifier");
                                } else {
                                    $this->Log("  |--- PHX Error:  {$modifier_cmd[$i]} could not be found");
                                }
                            }
                        }
                        if (!empty($snippet)) {
                            $output = $this->modx->runSnippet($snippetName, array(
                                'input'   => $output,
                                'output'  => $output,
                                'options' => $modifier_value[$i]
                            ));
                        } else {
                            $output = '';
                        }
                        break;
                }
                if (count($condition)) {
                    $this->Log("  |--- Condition = '" . $condition[count($condition) - 1] . "'");
                }
                $this->Log("  |--- Output = '" . $output . "'");
            }
        }

        return $output;
    }

    /**
     * @param string $code
     * @return mixed
     */
    private function runCode($code)
    {
        return eval("return (" . $code . ");");
    }

    // Event logging (debug)

    /**
     * @return string
     */
    public function createEventLog()
    {
        $out = '';
        if (!empty($this->console)) {
            $console = implode("\n", $this->console);
            $this->console = array();

            $out = '<pre style="overflow: auto;">' . $console . '</pre>';
        }

        return $out;
    }

    // Returns a cleaned string escaping the HTML and special MODx characters

    /**
     * @param $string
     * @return array|mixed|string
     */
    public function LogClean($string)
    {
        $string = preg_replace("/&amp;(#[0-9]+|[a-z]+);/i", "&$1;", $string);
        $string = replace_array($string);

        return $string;
    }

    // Simple log entry

    /**
     * @param $string
     */
    public function Log($string)
    {
        if ($this->debug) {
            $this->debugLog = true;
            $this->console[] = (count($this->console) + 1 - $this->curPass) . " [" . strftime(
                    "%H:%M:%S",
                    time()
                ) . "] " . $this->LogClean($string);
        }
    }

    // Log snippet output

    /**
     * @param $string
     */
    public function LogSnippet($string)
    {
        if ($this->debug) {
            $this->debugLog = true;
            $this->console[] = (count($this->console) + 1 - $this->curPass) . " [" . strftime(
                    "%H:%M:%S",
                    time()
                ) . "] " . "  |--- Returns: <div style='margin: 10px;'>" . $this->LogClean($string) . "</div>";
        }
    }

    // Log pass
    public function LogPass()
    {
        $this->console[] = "<div style='margin: 5px 2px 2px;border-bottom: 1px solid black;'>Pass " . $this->curPass . "</div>";
    }

    // Log pass

    /**
     * @param $string
     */
    public function LogSource($string)
    {
        $this->console[] = "<div style='margin: 5px 2px 2px;border-bottom: 1px solid black;'>Source:</div>" . $this->LogClean($string);
    }

    // Returns the specified field from the user record
    // positive userid = manager, negative integer = webuser

    /**
     * @param $userid
     * @param $field
     * @return mixed
     */
    public function ModUser($userid, $field)
    {
        if (!array_key_exists($userid, $this->cache["ui"])) {
            if (intval($userid) < 0) {
                $user = $this->modx->getWebUserInfo(-($userid));
            } else {
                $user = $this->modx->getUserInfo($userid);
            }
            $this->cache["ui"][$userid] = $user;
        } else {
            $user = $this->cache["ui"][$userid];
        }

        return $user[$field];
    }

    // Returns true if the user id is in one the specified webgroups

    /**
     * @param int $userid
     * @param array $groupNames
     * @return bool
     */
    public function isMemberOfWebGroupByUserId($userid = 0, $groupNames = array())
    {
        $userid = (int)$userid;
        // if $groupNames is not an array return false
        if (!is_array($groupNames)) {
            return false;
        }

        // if the user id is a negative number make it positive
        if (intval($userid) < 0) {
            $userid = -($userid);
        }

        // Creates an array with all webgroups the user id is in
        if (!array_key_exists($userid, $this->cache["mo"])) {
            $tbl = $this->modx->getDatabase()->getFullTableName("webgroup_names");
            $tbl2 = $this->modx->getDatabase()->getFullTableName("web_groups");
            $sql = "SELECT `wgn`.`name` FROM {$tbl} `wgn` INNER JOIN {$tbl2} `wg` ON `wg`.`webgroup`=`wgn`.`id` AND `wg`.`webuser`={$userid}";
            $this->cache["mo"][$userid] = $grpNames = $this->modx->getDatabase()->getColumn("name", $sql);
        } else {
            $grpNames = $this->cache["mo"][$userid];
        }
        // Check if a supplied group matches a webgroup from the array we just created
        foreach ($groupNames as $k => $v) {
            if (in_array(trim($v), $grpNames)) {
                return true;
            }
        }

        // If we get here the above logic did not find a match, so return false
        return false;
    }

    // Returns the value of a PHx/MODx placeholder.

    /**
     * @param $name
     * @return mixed|string
     */
    public function getPHxVariable($name)
    {
        // Check if this variable is created by PHx
        if (array_key_exists($name, $this->placeholders)) {
            // Return the value from PHx
            return $this->placeholders[$name];
        } else {
            // Return the value from MODx
            return $this->modx->getPlaceholder($name);
        }
    }

    // Sets a placeholder variable which can only be access by PHx

    /**
     * @param $name
     * @param $value
     */
    public function setPHxVariable($name, $value)
    {
        if ($name != "phx") {
            $this->placeholders[$name] = $value;
        }
    }

    //mbstring

    /**
     * @param $str
     * @param $s
     * @param null $l
     * @return string
     */
    public function substr($str, $s, $l = null)
    {
        if (function_exists('mb_substr')) {
            return mb_substr($str, $s, $l);
        }

        return substr($str, $s, $l);
    }

    /**
     * @param $str
     * @return int
     */
    public function strlen($str)
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($str);
        }

        return strlen($str);
    }

    /**
     * @param $str
     * @return string
     */
    public function strtolower($str)
    {
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($str);
        }

        return strtolower($str);
    }

    /**
     * @param $str
     * @return string
     */
    public function strtoupper($str)
    {
        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($str);
        }

        return strtoupper($str);
    }

    /**
     * @param $str
     * @return string
     */
    public function ucfirst($str)
    {
        if (function_exists('mb_strtoupper') && function_exists('mb_substr') && function_exists('mb_strlen')) {
            return mb_strtoupper(mb_substr($str, 0, 1)) . mb_substr($str, 1, mb_strlen($str));
        }

        return ucfirst($str);
    }

    /**
     * @param $str
     * @return string
     */
    public function lcfirst($str)
    {
        if (function_exists('mb_strtolower') && function_exists('mb_substr') && function_exists('mb_strlen')) {
            return mb_strtolower(mb_substr($str, 0, 1)) . mb_substr($str, 1, mb_strlen($str));
        }

        return lcfirst($str);
    }

    /**
     * @param $str
     * @return string
     */
    public function ucwords($str)
    {
        if (function_exists('mb_convert_case')) {
            return mb_convert_case($str, MB_CASE_TITLE);
        }

        return ucwords($str);
    }

    /**
     * @param $str
     * @return string
     */
    public function strrev($str)
    {
        preg_match_all('/./us', $str, $ar);

        return implode(array_reverse($ar[0]));
    }

    /**
     * @param $str
     * @return string
     */
    public function str_shuffle($str)
    {
        preg_match_all('/./us', $str, $ar);
        shuffle($ar[0]);

        return implode($ar[0]);
    }

    /**
     * @param $str
     * @return int
     */
    public function str_word_count($str)
    {
        return count(preg_split('~[^\p{L}\p{N}\']+~u', $str));
    }
}
