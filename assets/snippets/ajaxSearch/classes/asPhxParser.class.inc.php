<?php
/* -----------------------------------------------------------------------------
*
*    Name: PHx (Placeholders Xtended)
*    Version: 2.1.3
*
*    Author: Armand "bS" Pondman (apondman@zerobarrier.nl)
*    Date: July 13, 2007
*
*  Adapted by Coroico for AjaxSearch => AsPHxParser
*  Merge of the chunkie and PHxParser classes
*  cleanVars and unsetPHxVariable functions added
*
*/

class asPHxParser {
    var $placeholders = array();

    function asPHxParser($template='',$maxpass=500) {
        global $modx;
        $this->name = "PHx";
        $this->version = "2.1.3";
        $this->template = $this->getTemplate($template);
        $this->user["mgrid"] = intval($_SESSION['mgrInternalKey']);
        $this->user["usrid"] = intval($_SESSION['webInternalKey']);
        $this->user["id"] = ($this->user["usrid"] > 0 ) ? (-$this->user["usrid"]) : $this->user["mgrid"];
        $this->cache["cm"] = array();
        $this->cache["ui"] = array();
        $this->cache["mo"] = array();
        $this->safetags[0][0] = '~(?<![\[]|^\^)\[(?=[^\+\*\(\[]|$)~s';
        $this->safetags[0][1] = '~(?<=[^\+\*\)\]]|^)\](?=[^\]]|$)~s';
        $this->safetags[1][0] = '&_PHX_INTERNAL_091_&';
        $this->safetags[1][1] = '&_PHX_INTERNAL_093_&';
        $this->safetags[2][0] = '[';
        $this->safetags[2][1] = ']';
        $this->console = array();
        $this->curPass = 0;
        $this->maxPasses = ($maxpass!='') ? $maxpass : 500;
        $this->swapSnippetCache = array();
        $modx->setPlaceholder("phx", "&_PHX_INTERNAL_&");
    }

    // ====================================================================== parser part

    // Plugin event hook for MODx
    function OnParseDocument() {
        global $modx;
        // Get document output from MODx
        $template = $modx->documentOutput;
        // To the parse cave .. let's go! *insert batman tune here*
        $template = $this->Parse($template);
        // Set processed document output in MODx
        $modx->documentOutput = $template;
    }

    // Parser: Preparation, cleaning and checkup
    function Parse($template='') {
        global $modx;
        // If we already reached max passes don't get at it again.
        if ($this->curPass == $this->maxPasses) return $template;
        // Set template pre-process hash
        $st = md5($template);
        // Replace non-call characters in the template: [, ]
        $template = preg_replace($this->safetags[0],$this->safetags[1],$template);
        // To the parse mobile.. let's go! *insert batman tune here*
        $template = $this->ParseValues($template);
        // clean up unused placeholders that have modifiers attached (MODx can't clean them)
        preg_match_all('~\[(\+|\*|\()([^:\+\[\]]+)([^\[\]]*?)(\1|\))\]~s', $template, $matches);
        if ($matches[0]) $template = str_replace($matches[0], '', $template);
        // Restore non-call characters in the template: [, ]
        $template = str_replace($this->safetags[1],$this->safetags[2],$template);
        // Set template post-process hash
        $et = md5($template);
        // If template has changed, parse it once more...
        if ($st!=$et) $template = $this->Parse($template);
        // Return the processed template
        return $template;
    }

    // Parser: Tag detection and replacements
    function ParseValues($template='') {
        global $modx;

        $this->curPass = $this->curPass + 1;
        $st = md5($template);

        // MODx Chunks
        $template = $modx->mergeChunkContent($template);

        // MODx Snippets
        if ( preg_match_all('~\[(\[)([^\[]*?)(\])\]~s',$template, $matches)) {
            $count = count($matches[0]);
            $var_search = array();
            $var_replace = array();

            // for each detected snippet
            for($i=0; $i<$count; $i++) {
                $snippet = $matches[2][$i]; // snippet call

                // Let MODx evaluate snippet
                $replace = $modx->evalSnippets("[[".$snippet."]]");

                // Replace values
                $var_search[] = $matches[0][$i];
                $var_replace[] = $replace;

            }
            $template = str_replace($var_search, $var_replace, $template);
        }

        // PHx / MODx Tags
        if ( preg_match_all('~\[(\+|\*|\()([^:\+\[\]]+)([^\[\]]*?)(\1|\))\]~s',$template, $matches)) {

            //$matches[0] // Complete string that's need to be replaced
            //$matches[1] // Type
            //$matches[2] // The placeholder(s)
            //$matches[3] // The modifiers
            //$matches[4] // Type (end character)

            $count = count($matches[0]);
            $var_search = array();
            $var_replace = array();
            for($i=0; $i<$count; $i++) {
                $replace = NULL;
                $match = $matches[0][$i];
                $type = $matches[1][$i];
                $type_end = $matches[4][$i];
                $input = $matches[2][$i];
                $modifiers = $matches[3][$i];
                $var_search[] = $match;
                    switch($type) {
                        // Document / Template Variable eXtended
                        case "*":
                            $input = $modx->mergeDocumentContent("[*".$input."*]");
                            $replace = $this->Filter($input,$modifiers);
                            break;
                        // MODx Setting eXtended
                        case "(":
                            $input = $modx->mergeSettingsContent("[(".$input.")]");
                            $replace = $this->Filter($input,$modifiers);
                            break;
                        // MODx Placeholder eXtended
                        default:
                            // Check if placeholder is set
                            if ( !array_key_exists($input, $this->placeholders) && !array_key_exists($input, $modx->placeholders) ) {
                                // not set so try again later.
                                $replace = $match;
                            }
                            else {
                                // is set, get value and run filter
                                $input = $this->getPHxVariable($input);
                                $replace = $this->Filter($input,$modifiers);
                            }
                           break;
                    }
                    $var_replace[] = $replace;
             }
             $template = str_replace($var_search, $var_replace, $template);
        }
        $et = md5($template); // Post-process template hash

        // If this pass is not at maximum passes and the template hash is not the same, get at it again.
        if (($this->curPass < $this->maxPasses) && ($st!=$et))  $template = $this->ParseValues($template);

        return $template;
    }

    // Parser: modifier detection and eXtended processing if needed
    function Filter($input, $modifiers) {
        global $modx;
        $output = $input;
        if (preg_match_all('~:([^:=]+)(?:=`(.*?)`(?=:[^:=]+|$))?~s',$modifiers, $matches)) {
            $modifier_cmd = $matches[1]; // modifier command
            $modifier_value = $matches[2]; // modifier value
            $count = count($modifier_cmd);
            $condition = array();
            for($i=0; $i<$count; $i++) {
                $output = trim($output);
                switch ($modifier_cmd[$i]) {
                    #####  Conditional Modifiers
                    case "input":    case "if": $output = $modifier_value[$i]; break;
                    case "equals": case "is": case "eq": $condition[] = intval(($output==$modifier_value[$i])); break;
                    case "notequals": case "isnot":    case "isnt": case "ne":$condition[] = intval(($output!=$modifier_value[$i]));break;
                    case "isgreaterthan":    case "isgt": case "eg": $condition[] = intval(($output>=$modifier_value[$i]));break;
                    case "islowerthan": case "islt": case "el": $condition[] = intval(($output<=$modifier_value[$i]));break;
                    case "greaterthan": case "gt": $condition[] = intval(($output>$modifier_value[$i]));break;
                    case "lowerthan":    case "lt":$condition[] = intval(($output<$modifier_value[$i]));break;
                    case "isinrole": case "ir": case "memberof": case "mo": // Is Member Of  (same as inrole but this one can be stringed as a conditional)
                        if ($output == "&_PHX_INTERNAL_&") $output = $this->user["id"];
                        $grps = (strlen($modifier_value[$i]) > 0 ) ? explode(",",$modifier_value[$i]) :array();
                        $condition[] = intval($this->isMemberOfWebGroupByUserId($output,$grps));
                        break;
                    case "or":$condition[] = "||";break;
                    case "and":    $condition[] = "&&";break;
                    case "show":
                        $conditional = implode(' ',$condition);
                        $isvalid = intval(eval("return (". $conditional. ");"));
                        if (!$isvalid) { $output = NULL;}
                    case "then":
                        $conditional = implode(' ',$condition);
                        $isvalid = intval(eval("return (". $conditional. ");"));
                        if ($isvalid) { $output = $modifier_value[$i]; }
                        else { $output = NULL; }
                        break;
                    case "else":
                        $conditional = implode(' ',$condition);
                        $isvalid = intval(eval("return (". $conditional. ");"));
                        if (!$isvalid) { $output = $modifier_value[$i]; }
                        break;
                    case "select":
                        $raw = explode("&",$modifier_value[$i]);
                        $map = array();
                        for($m=0; $m<(count($raw)); $m++) {
                            $mi = explode("=",$raw[$m]);
                            $map[$mi[0]] = $mi[1];
                        }
                        $output = $map[$output];
                        break;
                    ##### End of Conditional Modifiers

                    #####  String Modifiers
                    case "lcase": $output = strtolower($output); break;
                    case "ucase": $output = strtoupper($output); break;
                    case "ucfirst": $output = ucfirst($output); break;
                    case "htmlent": $output = htmlentities($output,ENT_QUOTES,$modx->config['etomite_charset']); break;
                    case "esc":
                        $output = preg_replace("/&amp;(#[0-9]+|[a-z]+);/i", "&$1;", htmlspecialchars($output));
                        $output = str_replace(array("[","]","`"),array("&#91;","&#93;","&#96;"),$output);
                        break;
                    case "strip": $output = preg_replace("~([\n\r\t\s]+)~"," ",$output); break;
                    case "notags": $output = strip_tags($output); break;
                    case "length": case "len": $output = strlen($output); break;
                    case "reverse": $output = strrev($output); break;
                    case "wordwrap": // default: 70
                        $wrapat = intval($modifier_value[$i]) ? intval($modifier_value[$i]) : 70;
                        $output = preg_replace("~(\b\w+\b)~e","wordwrap('\\1',\$wrapat,' ',1)",$output);
                        break;
                    case "limit": // default: 100
                        $limit = intval($modifier_value[$i]) ? intval($modifier_value[$i]) : 100;
                        $output = substr($output,0,$limit);
                        break;

                    #####  Special functions
                    // img modifiers added by coroico
                    case "imgwidth":
                        if (@file_exists($output)) {
                            list($width, $height, $type, $attr) = getimagesize($output);
                            $output = $width;
                        }
                        else $output = 0;
                        break;
                    case "imgheight":
                        if (@file_exists($output)) {
                            list($width, $height, $type, $attr) = getimagesize($output);
                            $output = $height;
                        }
                        else $output = 0;
                        break;
                    case "imgattr":
                        if (@file_exists($output)) {
                            list($width, $height, $type, $attr) = getimagesize($output);
                            $output = $attr;
                        }
                        else $output = '';
                        break;
                    case "imgmaxwidth":
                        if (@file_exists($output)) {
                            list($width, $height, $type, $attr) = getimagesize($output);
                            $output = ($width < intval($modifier_value[$i])) ? $width : intval($modifier_value[$i]);
                        }
                        else $output = intval($modifier_value[$i]);
                        break;
                    case "imgmaxheight":
                        if (@file_exists($output)) {
                            list($width, $height, $type, $attr) = getimagesize($output);
                            $output = ($height < intval($modifier_value[$i])) ? $height : intval($modifier_value[$i]);
                        }
                        else $output = intval($modifier_value[$i]);
                        break;
                    case "math":
                        $filter = preg_replace("~([a-zA-Z\n\r\t\s])~","",$modifier_value[$i]);
                        $filter = str_replace("?",$output,$filter);
                        $output = eval("return ".$filter.";");
                        break;
                    case "ifempty": if (empty($output)) $output = $modifier_value[$i]; break;
                    case "nl2br": $output = nl2br($output); break;
                    case "date": $output = strftime($modifier_value[$i],0+$output); break;
                    case "set":
                        $c = $i+1;
                        if ($count>$c&&$modifier_cmd[$c]=="value") $output = preg_replace("~([^a-zA-Z0-9])~","",$modifier_value[$i]);
                        break;
                    case "value":
                        if ($i>0&&$modifier_cmd[$i-1]=="set") { $modx->SetPlaceholder("phx.".$output,$modifier_value[$i]); }
                        $output = NULL;
                        break;
                    case "md5": $output = md5($output); break;
                    case "userinfo":
                        if ($output == "&_PHX_INTERNAL_&") $output = $this->user["id"];
                        $output = $this->ModUser($output,$modifier_value[$i]);
                        break;
                    case "inrole": // deprecated
                        if ($output == "&_PHX_INTERNAL_&") $output = $this->user["id"];
                        $grps = (strlen($modifier_value[$i]) > 0 ) ? explode(",",$modifier_value[$i]) :array();
                        $output = intval($this->isMemberOfWebGroupByUserId($output,$grps));
                        break;
                    default:
                        if (!array_key_exists($modifier_cmd[$i], $this->cache["cm"])) {
                            $phx_snippet_name = 'phx:' . $modx->db->escape($modifier_cmd[$i]);
                            $sql = "SELECT snippet FROM " . $modx->getFullTableName("site_snippets") . " WHERE " . $modx->getFullTableName("site_snippets") . ".name='" . $phx_snippet_name . "';";
                             $result = $modx->db->query($sql);
                             if ($modx->db->getRecordCount($result) == 1) {
                                $row = $modx->db->getRow($result);
                                 $cm = $this->cache["cm"][$modifier_cmd[$i]] = $row["snippet"];
                             } else if ($modx->db->getRecordCount($result) == 0){ // If snippet not found, look in the modifiers folder
                                $filename = $modx->config['rb_base_dir'] . 'plugins/phx/modifiers/'.$modifier_cmd[$i].'.phx.php';
                                if (@file_exists($filename)) {
                                    $file_contents = @file_get_contents($filename);
                                    $file_contents = str_replace('<?php', '', $file_contents);
                                    $file_contents = str_replace('?>', '', $file_contents);
                                    $file_contents = str_replace('<?', '', $file_contents);
                                    $cm = $this->cache["cm"][$modifier_cmd[$i]] = $file_contents;
                                }
                            }
                         } else {
                             $cm = $this->cache["cm"][$modifier_cmd[$i]];
                         }
                         ob_start();
                         $options = $modifier_value[$i];
                         $custom = eval($cm);
                         $msg = ob_get_contents();
                         $output = $msg.$custom;
                         ob_end_clean();
                         break;
                }
            }
        }
        return $output;
    }

    // Returns the specified field from the user record
    // positive userid = manager, negative integer = webuser
    function ModUser($userid,$field) {
        global $modx;
        if (!array_key_exists($userid, $this->cache["ui"])) {
            if (intval($userid) < 0) {
                $user = $modx->getWebUserInfo(-($userid));
            } else {
                $user = $modx->getUserInfo($userid);
            }
            $this->cache["ui"][$userid] = $user;
        } else {
            $user = $this->cache["ui"][$userid];
        }
        return $user[$field];
    }

     // Returns true if the user id is in one the specified webgroups
     function isMemberOfWebGroupByUserId($userid=0,$groupNames=array()) {
        global $modx;

        // if $groupNames is not an array return false
        if(!is_array($groupNames)) return false;

        // if the user id is a negative number make it positive
        if (intval($userid) < 0) { $userid = -($userid); }

        // Creates an array with all webgroups the user id is in
        if (!array_key_exists($userid, $this->cache["mo"])) {
            $tbl = $modx->getFullTableName("webgroup_names");
            $tbl2 = $modx->getFullTableName("web_groups");
            $sql = "SELECT wgn.name FROM $tbl wgn INNER JOIN $tbl2 wg ON wg.webgroup=wgn.id AND wg.webuser='".$userid."'";
            $this->cache["mo"][$userid] = $grpNames = $modx->db->getColumn("name",$sql);
        } else {
            $grpNames = $this->cache["mo"][$userid];
        }
        // Check if a supplied group matches a webgroup from the array we just created
        foreach($groupNames as $k=>$v)
            if(in_array(trim($v),$grpNames)) return true;

        // If we get here the above logic did not find a match, so return false
        return false;
     }

    // Returns the value of a PHx/MODx placeholder.
    function getPHxVariable($name) {
        global $modx;
        // Check if this variable is created by PHx
        if (array_key_exists($name, $this->placeholders)) {
            // Return the value from PHx
            return $this->placeholders[$name];
        } else {
            // Return the value from MODx
            return $modx->getPlaceholder($name);
        }
    }

    // Sets a placeholder variable which can only be access by PHx
    function setPHxVariable($name, $value) {
        if ($name != "phx") $this->placeholders[$name] = $value;
    }

    // unset all the placeholders - Added by coroico
    function unsetPHxVariable() {
        unset($this->placeholders);
    }

    // ====================================================================== chunkie part

    function CreateVars($value = '', $key = '', $path = '') {
        $keypath = !empty($path) ? $path . "." . $key : $key;
        if (is_array($value)) {
            foreach ($value as $subkey => $subval) {
                $this->CreateVars($subval, $subkey, $keypath);
            }
        } else {
            $this->setPHxVariable($keypath, $value);
        }
    }
    function AddVar($name, $value) {
        $this->CreateVars($value, $name);
    }

    // Added by coroico
    function CleanVars() {
        $this->unsetPHxVariable();
    }

    function Render() {
        $template = $this->Parse($this->template);
        return $template;
    }

    function getTemplate($tpl) {
        global $modx;
        $template = "";
        if ($modx->getChunk($tpl) != "") {
            $template = $modx->getChunk($tpl);
        } else if (substr($tpl, 0, 6) == "@FILE:") {
            $template = $this->get_file_contents($modx->config['base_path'] . substr($tpl, 6));
        } else if (substr($tpl, 0, 6) == "@CODE:") {
            $template = substr($tpl, 6);
        } else {
            $template = FALSE;
        }
        return $template;
    }

    function get_file_contents($filename) {
        // Returns the contents of file name passed
        if (!function_exists('file_get_contents')) {
            $fhandle = fopen($filename, "r");
            $fcontents = fread($fhandle, filesize($filename));
            fclose($fhandle);
        } else {
            $fcontents = file_get_contents($filename);
        }
        return $fcontents;
    }
}
?>
