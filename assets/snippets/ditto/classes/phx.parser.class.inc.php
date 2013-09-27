<?php
/*####
#
#	Name: PHx (Placeholders Xtended)
#	Version: 2.1.2
#	Author: Armand "bS" Pondman (apondman@zerobarrier.nl)
#	Date: Feb 13, 2007 8:52 CET
#
####*/

class PHxParser {
	var $placeholders = array();
	
	function PHxParser($debug=0,$maxpass=50) {
		global $modx;
		$this->name = "PHx";
		$this->version = "2.1.2";
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
		$this->debug = ($debug!='') ? $debug : 0;
		$this->debugLog = false;
		$this->curPass = 0;
		$this->maxPasses = ($maxpass!='') ? $maxpass : 50;
		$this->swapSnippetCache = array();
		$modx->setPlaceholder("phx", "&_PHX_INTERNAL_&");
	}
	
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
    	if ($matches[0]) {
			$template = str_replace($matches[0], '', $template);
			$this->Log("Cleaning unsolved tags: \n" . implode("\n",$matches[2]) );
		}
		// Restore non-call characters in the template: [, ]
		$template = str_replace($this->safetags[1],$this->safetags[2],$template);
		// Set template post-process hash
		$et = md5($template);
		// If template has changed, parse it once more...
		if ($st!=$et) $template = $this->Parse($template);
		// Write an event log if debugging is enabled and there is something to log
		if ($this->debug && $this->debugLog) {
			$modx->logEvent($this->curPass,1,$this->createEventLog(), $this->name.' '.$this->version);
			$this->debugLog = false;
		}
		// Return the processed template
		return $template;
	}
	
	// Parser: Tag detection and replacements
	function ParseValues($template='') {
		global $modx;
		
		$this->curPass = $this->curPass + 1;
		$st = md5($template);

		//$this->LogSource($template);
		$this->LogPass();
						
		// MODx Chunks
		$this->Log("MODx Chunks -> Merging all chunk tags");
		$template = $modx->mergeChunkContent($template);
		
		// MODx Snippets
		//if ( preg_match_all('~\[(\[|!)([^\[]*?)(!|\])\]~s',$template, $matches)) {
		if ( preg_match_all('~\[(\[)([^\[]*?)(\])\]~s',$template, $matches)) {
				$count = count($matches[0]);
				$var_search = array();
				$var_replace = array();
				
				// for each detected snippet
				for($i=0; $i<$count; $i++) {
					$snippet = $matches[2][$i]; // snippet call
					$this->Log("MODx Snippet -> ".$snippet);
					
					// Let MODx evaluate snippet
					$replace = $modx->evalSnippets("[[".$snippet."]]");
					$this->LogSnippet($replace);
					
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
							$this->Log("MODx TV/DV: " . $input);
							$input = $modx->mergeDocumentContent("[*".$input."*]");
							$replace = $this->Filter($input,$modifiers);
							break;
						// MODx Setting eXtended
						case "(":
							$this->Log("MODx Setting variable: " . $input);
							$input = $modx->mergeSettingsContent("[(".$input.")]");
							$replace = $this->Filter($input,$modifiers);
							break;
						// MODx Placeholder eXtended
						default:
							$this->Log("MODx / PHx placeholder variable: " . $input);
							// Check if placeholder is set
							if ( !array_key_exists($input, $this->placeholders) && !array_key_exists($input, $modx->placeholders) ) {
								// not set so try again later.
								$replace = $match;
								$this->Log("  |--- Skipping - hasn't been set yet.");
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
		
		// Log an event if this was the maximum pass
		if ($this->curPass == $this->maxPasses) $this->Log("Max passes reached. infinite loop protection so exiting.\n If you need the extra passes set the max passes to the highest count of nested tags in your template.");
		// If this pass is not at maximum passes and the template hash is not the same, get at it again.
		if (($this->curPass < $this->maxPasses) && ($st!=$et))  $template = $this->ParseValues($template);

		return $template;
	}
	
	// Parser: modifier detection and eXtended processing if needed
	function Filter($input, $modifiers) {
		global $modx;
		$output = $input;
		$this->Log("  |--- Input = '". $output ."'");
		if (preg_match_all('~:([^:=]+)(?:=`(.*?)`(?=:[^:=]+|$))?~s',$modifiers, $matches)) {
			$modifier_cmd = $matches[1]; // modifier command
			$modifier_value = $matches[2]; // modifier value
			$count = count($modifier_cmd);
			$condition = array();
			for($i=0; $i<$count; $i++) {
				$output = trim($output);
				$this->Log("  |--- Modifier = '". $modifier_cmd[$i] ."'");
				if ($modifier_value[$i] != '') $this->Log("  |--- Options = '". $modifier_value[$i] ."'");
				switch ($modifier_cmd[$i]) {
					#####  Conditional Modifiers 
					case "input":	case "if": $output = $modifier_value[$i]; break;
					case "equals": case "is": case "eq": $condition[] = intval(($output==$modifier_value[$i])); break;
					case "notequals": case "isnot":	case "isnt": case "ne":$condition[] = intval(($output!=$modifier_value[$i]));break;
					case "isgreaterthan":	case "isgt": case "eg": $condition[] = intval(($output>=$modifier_value[$i]));break;
					case "islowerthan": case "islt": case "el": $condition[] = intval(($output<=$modifier_value[$i]));break;
					case "greaterthan": case "gt": $condition[] = intval(($output>$modifier_value[$i]));break;
					case "lowerthan":	case "lt":$condition[] = intval(($output<$modifier_value[$i]));break;
					case "isinrole": case "ir": case "memberof": case "mo": // Is Member Of  (same as inrole but this one can be stringed as a conditional)
						if ($output == "&_PHX_INTERNAL_&") $output = $this->user["id"];
						$grps = (strlen($modifier_value) > 0 ) ? explode(",",$modifier_value[$i]) :array();
						$condition[] = intval($this->isMemberOfWebGroupByUserId($output,$grps));
						break;
					case "or":$condition[] = "||";break;
					case "and":	$condition[] = "&&";break;
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
						$grps = (strlen($modifier_value) > 0 ) ? explode(",",$modifier_value[$i]) :array();
						$output = intval($this->isMemberOfWebGroupByUserId($output,$grps));
						break;
					default:
						if (!array_key_exists($modifier_cmd[$i], $this->cache["cm"])) {
							$sql = "SELECT snippet FROM " . $modx->getFullTableName("site_snippets") . " WHERE " . $modx->getFullTableName("site_snippets") . ".name='phx:" . $modifier_cmd[$i] . "';";
			             	$result = $modx->db->query($sql);
			             	if ($modx->db->getRecordCount($result) == 1) {
								$row = $modx->db->getRow($result);
						 		$cm = $this->cache["cm"][$modifier_cmd[$i]] = $row["snippet"];
						 		$this->Log("  |--- DB -> Custom Modifier");
						 	}
						 } else {
						 	$cm = $this->cache["cm"][$modifier_cmd[$i]];
						 	$this->Log("  |--- Cache -> Custom Modifier");
						 }
						 ob_start();
						 $options = $modifier_value[$i];
		        	     $custom = eval($cm);
		    		     $msg = ob_get_contents();
						 $output = $msg.$custom;
				         ob_end_clean();	
						 break;
				} 
				if (count($condition)) $this->Log("  |--- Condition = '". $condition[count($condition)-1] ."'");
				$this->Log("  |--- Output = '". $output ."'");
			}
		}	
		return $output;
	}
	
	// Event logging (debug)
	function createEventLog() {
		if($this->console) { 
			$console = implode("\n",$this->console);
			$this->console = array();
			return '<pre style="overflow: auto;">' . $console . '</pre>';
		}
	}
	
	// Returns a cleaned string escaping the HTML and special MODx characters
	function LogClean($string) {
		$string = preg_replace("/&amp;(#[0-9]+|[a-z]+);/i", "&$1;", htmlspecialchars($string));
		$string = str_replace(array("[","]","`"),array("&#91;","&#93;","&#96;"),$string);
		return $string;
	}
	
	// Simple log entry
	function Log($string) {
		if ($this->debug) {$this->debugLog = true; $this->console[] = (count($this->console)+1-$this->curPass). " [". strftime("%H:%M:%S",time()). "] " . $this->LogClean($string);}
	}
	
	// Log snippet output
	function LogSnippet($string) {
		if ($this->debug) {$this->debugLog = true; $this->console[] = (count($this->console)+1-$this->curPass). " [". strftime("%H:%M:%S",time()). "] " . "  |--- Returns: <div style='margin: 10px;'>".$this->LogClean($string)."</div>";}
	}
	
	// Log pass
	function LogPass() {
		$this->console[] = "<div style='margin: 2px;margin-top: 5px;border-bottom: 1px solid black;'>Pass " . $this->curPass . "</div>";
	}
	
	// Log pass
	function LogSource($string) {
		$this->console[] = "<div style='margin: 2px;margin-top: 5px;border-bottom: 1px solid black;'>Source:</div>" . $this->LogClean($string);
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

}
?>
