<?php

/*
 * Title: Main Class
 * Purpose:
 *  	The Ditto class contains all functions relating to Ditto's
 *  	functionality and any supporting functions they need
*/

class ditto {
	var $template,$resource,$format,$debug,$advSort,$sqlOrderBy,$customReset,$fields,$constantFields,$prefetch,$sortOrder,$customPlaceholdersMap;

	function ditto($dittoID,$format,$language,$debug) {
		$this->format = $format;
		$GLOBALS["ditto_lang"] = $language;
		$this->prefetch = false;
		$this->advSort = false;
		$this->sqlOrderBy = array();
		$this->customReset = array();
		$this->constantFields[] = array("db","tv");
		$this->constantFields["db"] = array("id","type","contentType","pagetitle","longtitle","description","alias","link_attributes","published","pub_date","unpub_date","parent","isfolder","introtext","content","richtext","template","menuindex","searchable","cacheable","createdby","createdon","editedby","editedon","deleted","deletedon","deletedby","publishedon","publishedby","menutitle","donthit","haskeywords","hasmetatags","privateweb","privatemgr","content_dispo","hidemenu");
		$this->constantFields["tv"] = $this->getTVList();
		$GLOBALS["ditto_constantFields"] = $this->constantFields;
		$this->fields = array("display"=>array(),"backend"=>array("tv"=>array(),"db"=>array("id", "published")));
		$this->sortOrder = false;
		$this->customPlaceholdersMap = array();
		$this->template = new template();
		
		if (!is_null($debug)) {$this->debug = new debug($debug);}
	}

	// ---------------------------------------------------
	// Function: getTVList
	// Get a list of all available TVs
	// ---------------------------------------------------
		
	function getTVList() {
		global $modx;
		$table = $modx->getFullTableName("site_tmplvars");
		$tvs = $modx->db->select("name", $table);
			// TODO: make it so that it only pulls those that apply to the current template
		$dbfields = array();
		while ($dbfield = $modx->db->getRow($tvs))
			$dbfields[] = $dbfield['name'];
		return $dbfields;
	}
	
	// ---------------------------------------------------
	// Function: addField
	// Add a field to the internal field detection system
	// ---------------------------------------------------
	
	function addField($name,$location,$type=false) {
		if ($type === false) {
			$type = $this->getDocVarType($name);
		}
		if ($type == "tv:prefix") {
			$type = "tv";
			$name = substr($name, 2);
		}
		if ($location == "*") {
			$this->fields["backend"][$type][] = $name;
			$this->fields["display"][$type][] = $name;
		} else {
			$this->fields[$location][$type][] = $name;
		}
	}
	
	// ---------------------------------------------------
	// Function: addFields
	// Add a field to the internal field detection system
	// from an array or delimited string
	// ---------------------------------------------------
	
	function addFields($fields,$location='*',$delimiter=',',$callback=false) {
		if (empty($fields)) return false; 
		if  (!is_array($fields)) {
			if (strpos($fields,$delimiter) !== false) {
				$fields = explode($delimiter,$fields);
			} else {
				$fields = array($fields);
			}
		}
		foreach ($fields as $field) {
			if (is_array($field)) {
				$type = isset($field[2]) ? $field[2] : false;
				$name = $field[0];
			} else {
				$name = $field;
				$type = false;
			}
			
			$this->addField($name,$location,$type);
			if ($callback !== false) {
				call_user_func_array($callback, array($name));
			}
		}
		return true;
	}
	
	// ---------------------------------------------------
	// Function: removeField
	// Remove a field to the internal field detection system
	// ---------------------------------------------------
	
	function removeField($name,$location,$type) {
		$key = array_search ($name, $this->fields[$location][$type]);
		if ($key !== false) {
			unset($this->fields[$location][$type][$key]);
		}
	}
	
	// ---------------------------------------------------
	// Function: setDisplayFields
	// Move the detected fields into the Ditto fields array
	// ---------------------------------------------------
	
	function setDisplayFields($fields,$hiddenFields) {
		$this->fields["display"] = $fields;
		if (count($this->fields["display"]['qe']) > 0) {
			$this->addField("pagetitle","display","db");
		}
		if ($hiddenFields) {
			$this->addFields($hiddenFields,"display");
		}
	}

	// ---------------------------------------------------
	// Function: getDocVarType
	// Determine if the provided field is a tv, a database field, or something else
	// ---------------------------------------------------
	
	function getDocVarType($field) {
		global $ditto_constantFields;
		$tvFields = $ditto_constantFields["tv"];
		$dbFields = $ditto_constantFields["db"];
		if(in_array($field, $tvFields)){
			return "tv";
		}else if(in_array(substr($field,2), $tvFields)) {
			return "tv:prefix";
				// TODO: Remove TV Prefix support
		} else if(in_array($field, $dbFields)){
			return "db";
		} else {
			return "unknown";
		}
	}

	// ---------------------------------------------------
	// Function: parseOrderBy
	// Parse out orderBy parameter string
	// ---------------------------------------------------

	function parseOrderBy($orderBy,$randomize) {
		if ($randomize != 0) {return false;}
		$orderBy['sql'] = array();

		foreach ($orderBy['parsed'] as $item) {
			$this->addFields($item[0],'backend');
			$this->checkAdvSort($item[0],$item[1]);
		}
		
		foreach ($orderBy['custom'] as $item) {
			$this->addFields($item[0],'backend');
			$this->checkAdvSort($item[0]);
		}

		if (!is_null($orderBy['unparsed'])) {
			$inputs = explode(',',$orderBy['unparsed']);
			foreach ($inputs as $input) {
				$input = trim($input);
				$position = strrpos($input,' ');
					// find last space
				$sortBy = substr($input,0,$position);
					$sortBy = !empty($sortBy) ? $sortBy : 'id';
				$sortDir = substr($input,$position);
					$sortDir = !empty($sortDir) ? trim($sortDir) : 'asc';
				$sortBy = $this->checkAdvSort($sortBy,$sortDir);
				$this->addField($sortBy,"backend");
				$orderBy['parsed'][] = array($sortBy,strtoupper($sortDir));
			}
		}
		$orderBy['sql'] = implode(', ',$this->sqlOrderBy);
		unset($orderBy['unparsed']);
		return $orderBy;
	}
	
	// ---------------------------------------------------
	// Function: checkAdvSort
	// Check the advSortString
	// ---------------------------------------------------
	function checkAdvSort($sortBy,$sortDir='asc') {
		$advSort = array ("pub_date","unpub_date","editedon","deletedon","publishedon");
		$type = $this->getDocVarType($sortBy);
		switch($type) {
			case "tv:prefix":
				$sortBy = substr($sortBy, 2);
				$this->advSort = true;
			break;
			case "tv":
				$this->advSort = true;
			break;
			case "db":
				if (in_array($sortBy, $advSort)) {
					$this->advSort = true;
					$this->customReset[] = $sortBy;
				} else {
					$this->sqlOrderBy[] = 'sc.'.$sortBy.' '.$sortDir;
				}
			break;
		}
		return $sortBy;
	}
	
	// ---------------------------------------------------
	// Function: parseFilters
	// Split up the filters into an array and add the required fields to the fields array
	// ---------------------------------------------------

	function parseFilters($filter=false,$cFilters=false,$pFilters = false,$globalDelimiter,$localDelimiter) {
		$parsedFilters = array("basic"=>array(),"custom"=>array());
		$filters = explode($globalDelimiter, $filter);
		if ($filter && count($filters) > 0) {
			foreach ($filters AS $filter) {
				if (!empty($filter)) {
					$filterArray = explode($localDelimiter, $filter);
					$source = $filterArray[0];
					$this->addField($source,"backend");
					$value = $filterArray[1];
					$mode = (isset ($filterArray[2])) ? $filterArray[2] : 1;
					$parsedFilters["basic"][] = array("source"=>$source,"value"=>$value,"mode"=>$mode);
				}
			}
		}
		if ($cFilters) {
			foreach ($cFilters as $name=>$value) {
				if (!empty($name) && !empty($value)) {
					$parsedFilters["custom"][$name] = $value[1];
					$this->addFields($value[0],"backend");					
				}
			}	// TODO: Replace addField with addFields with callback
		}
		if($pFilters) {	
			foreach ($pFilters as $filter) {
				foreach ($filter as $name=>$value) {	
					$parsedFilters["basic"][] = $value;
					$this->addFields($value["source"],"backend");
				}
			}	// TODO: Replace addField with addFields with callback
		}
		return $parsedFilters;
	}

	// ---------------------------------------------------
	// Function: render
	// Render the document output
	// ---------------------------------------------------
	
	function render($resource, $template, $removeChunk,$dateSource,$dateFormat,$ph=array(),$phx=1,$x=0,$stop) {
		global $modx,$ditto_lang;

		if (!is_array($resource)) {
			return $ditto_lang["resource_array_error"];
		}
		$placeholders = array();
		$contentVars = array();
		foreach ($resource as $name=>$value) {
			$placeholders["$name"] = $value;
			$contentVars["[*$name*]"] = $value;
		}

		// set author placeholder
		if (in_array("author",$this->fields["display"]["custom"])) {
			$placeholders['author'] = $this->getAuthor($resource['createdby']);		
		}

		// set title placeholder
		if (in_array("title",$this->fields["display"]["custom"])) {
			$placeholders['title'] = $resource['pagetitle'];
		}

		// set sequence placeholder
		if (in_array("ditto_iteration",$this->fields["display"]["custom"])) {
			$placeholders['ditto_iteration'] = $x;
		}
		
		//Added by Andchir
		//if (in_array("ditto_index",$this->fields["display"]["custom"])) {
			$r_start = isset($_GET['start']) ? $_GET['start'] : 0;
      $placeholders['ditto_index'] = $r_start+$x+1;
		//}
		
        //Added by Dmi3yy placeholder ditto_class
         if ($x % 2 == 0) {$class="even";} else {$class="odd";}
             if ($x==0) $class.=" first";
             if ($x==($stop -1)) $class.=" last";
             if ($resource['id'] == $modx->documentObject['id']) $class.=" current";
             $placeholders['ditto_class'] = $class;

		// set url placeholder
		if (in_array("url",$this->fields["display"]["custom"])) {
			if($resource['id']==$modx->config['site_start'])
				$placeholders['url'] = $modx->config['site_url'];
			else
			$placeholders['url'] = $modx->makeURL($resource['id'],'','','full');
		}

		if (in_array("date",$this->fields["display"]["custom"])) {
			$timestamp = ($resource[$dateSource] != "0") ? $resource[$dateSource] : $resource["createdon"];	
			if (is_array($timestamp)) {
			    $timestamp[1] = is_int($timestamp[1]) ? $timestamp[1] : strtotime($timestamp[1]);
                $timestamp = $timestamp[1] + $timestamp[0];
            }   
			$placeholders['date'] = strftime($dateFormat,$timestamp);
		}
		
		if (in_array("content",$this->fields["display"]["db"]) && $this->format != "html") {
            $placeholders['content'] = $this->relToAbs($resource['content'], $modx->config['site_url']);
        }
         
        if (in_array("introtext",$this->fields["display"]["db"]) && $this->format != "html") {
            $placeholders['introtext'] = $this->relToAbs($resource['introtext'], $modx->config['site_url']);
        }
		
		$customPlaceholders = $ph;
		// set custom placeholder
		foreach ($ph as $name=>$value) {
			if ($name != "*") {
				$placeholders[$name] = call_user_func($value[1],$resource);
				unset($customPlaceholders[$name]);
			}
		}
		
		foreach ($customPlaceholders as $name=>$value) {
			$placeholders = call_user_func($value,$placeholders);
		}
		
		if (count($this->fields["display"]['qe']) > 0) {
			$placeholders = $this->renderQELinks($this->template->fields['qe'],$resource,$ditto_lang["edit"]." : ".$resource['pagetitle']." : ",$placeholders);
				// set QE Placeholders
		}
		
		if ($phx == 1) {
			$PHs = $placeholders;
			foreach($PHs as $key=>$output) {
				$placeholders[$key] = str_replace( array_keys( $contentVars ), array_values( $contentVars ), $output );
			}
			unset($PHs);
			$phx = new prePHx($template);
			$phx->setPlaceholders($placeholders);
			$output = $phx->output();
		} else {
		 	$output = $this->template->replace($placeholders,$template);
			$output = $this->template->replace($contentVars,$output);
		}
		if ($removeChunk) {
			foreach ($removeChunk as $chunk) {
				$output = str_replace('{{'.$chunk.'}}',"",$output);
				$output = str_replace($modx->getChunk($chunk),"",$output);
					// remove chunk that is not wanted			
			}
		}

		return $output;
	}
	
	// ---------------------------------------------------
	// Function: parseFields
	// Find the fields that are contained in the custom placeholders or those that are needed in other functions
	// ---------------------------------------------------
	
	function parseFields($placeholders,$seeThruUnpub,$dateSource,$randomize) {
		$this->parseCustomPlaceholders($placeholders);
		$this->parseDBFields($seeThruUnpub);
		if ($randomize != 0) {
			$this->addField($randomize,"backend");
		}
		$this->addField("id","display","db");
		$this->addField("pagetitle","display","db");
		$checkOptions = array("pub_date","unpub_date","editedon","deletedon","publishedon");
		if (in_array($dateSource,$checkOptions)) {
			$this->addField("createdon","display");
		}
		if (in_array("date",$this->fields["display"]["custom"])) {
			$this->addField($dateSource,"display");
		}
		$this->fields = $this->arrayUnique($this->fields);
	}


	// ---------------------------------------------------
	// Function: arrayUnique
	// Make fields array unique
	// ---------------------------------------------------
		
	function arrayUnique($array) {
		foreach($array as $u => $a) {
			foreach ($a as $n => $b) {
				$array[$u][$n] = array_unique($b);
			}
		}
		return $array;
	}
	  	
	// ---------------------------------------------------
	// Function: parseCustomPlaceholders
	// Parse the required fields out of the custom placeholders
	// ---------------------------------------------------
	
	function parseCustomPlaceholders($placeholders) {
		foreach ($placeholders as $name=>$value) {
			$this->addField($name,"display","custom");
			$this->removeField($name,"display","unknown");
			$source = $value[0];
			$qe = $value[2];
	
			if(is_array($source)) {
				if(strpos($source[0],",")!==false){
					$fields = explode(",",$source[0]);
					foreach ($fields as $field) {
						if (!empty($field)) {
							$this->addField($field,$source[1]);	
							$this->customPlaceholdersMap[$name] = $field;	
						}
					}
				} else {
					$this->addField($source[0],$source[1]);	
					$this->customPlaceholdersMap[$name] = $source[0];				
				}	// TODO: Replace addField with addFields with callback
			} else if(is_array($value)) {
				$fields = explode(",",$source);
				foreach ($fields as $field) {
					if (!empty($field)) {
						$this->addField($field,"display");
						$this->customPlaceholdersMap[$name] = $field;
					}
				}
			}

			if (!is_null($qe)) {
				$this->customPlaceholdersMap[$name] = array('qe',$qe);
			}
		
		}
	}
	
	// ---------------------------------------------------
	// Function: parseDBFields
	// Parse out the fields required for each state
	// ---------------------------------------------------
	
	function parseDBFields($seeThruUnpub) {
		if (!$seeThruUnpub) {
			$this->addField("parent","backend","db");
		}
		
		if (in_array("author",$this->fields["display"]["custom"])) {
			$this->fields["display"]["db"][] = "createdby";			
		}
		
		if (count($this->fields["display"]["tv"]) >= 0) {
			$this->addField("published","display","db");
		}
	}
	
	// ---------------------------------------------------
	// Function: renderQELinks
	// Render QE links when needed
	// ---------------------------------------------------

	function renderQELinks($fields, $resource, $QEPrefix,$placeholders) {
		global $modx,$dittoID;
		$table = $modx->getFullTableName("site_modules");
		$idResult = $modx->db->select("id", $table,"name='QuickEdit'","id","1");
		$id = $modx->db->getRow($idResult);
		$id = $id["id"];
		$custom = array("author","date","url","title");
		$set = $modx->hasPermission('exec_module');
		foreach ($fields as $dv) {
			$ds = $dv;
			if ($dv == "title") {
				$ds = "pagetitle";
			}
			if (!in_array($dv,$custom) && in_array($dv,$this->fields["display"]["custom"])) {
				$value =  $this->customPlaceholdersMap[$dv];
				$ds = $value;
				if (is_array($value) && $value[0] == "qe") {
					$value = $value[1];
					if (substr($value,0,7) == "@GLOBAL") {
						$key = trim(substr($value,7));
						$ds = $GLOBALS[$key];
					}
				}
			}
			
			$js = "window.open('".MODX_MANAGER_URL."index.php?a=112&id=".$id."&doc=".$resource["id"]."&var=".$ds."', 'QuickEditor', 'width=525, height=300, toolbar=0, menubar=0, status=0, alwaysRaised=1, dependent=1');";
			$url = $this->buildURL("qe_open=true",$modx->documentIdentifier,$dittoID);
			
			unset($custom[3]);
			if ($set && !in_array($dv,$custom)) {
				$placeholders["#$dv"] = $placeholders["$dv"].'<a href="'.$url.'#" onclick="javascript: '.$js.'" title="'.$QEPrefix.$dv.'" class="QE_Link">&laquo; '.$QEPrefix.$dv.'</a>';
			} else {
				$placeholders["#$dv"] = $placeholders["$dv"];
			}
		}
		return $placeholders;
	}
	
	// ---------------------------------------------------
	// Function: getAuthor
	// Get the author name, or if not available the username
	// ---------------------------------------------------
	
	public static function getAuthor($createdby) {
		global $modx;
		
		$user = false;
		if ($createdby > 0) {
			$user = $modx->getUserInfo($createdby);
		} else {
			$user = $modx->getWebUserInfo(abs($createdby));
		}
		if ($user === false) {
			// get admin user name
			$user = $modx->getUserInfo(1);
		}
		return ($user['fullname'] != "") ? $user['fullname'] : $user['username'];
	}
	
	// ---------------------------------------------------
	// Function: customSort
	// Sort resource array if advanced sorting is needed
	// ---------------------------------------------------

	function customSort($data, $fields, $order) {
		// Covert $fields string to array
		// user contributed
		foreach (explode(',', $fields) as $s)
			$sortfields[] = trim($s);

		$code = "";
		for ($c = 0; $c < count($sortfields); $c++)
			$code .= "\$retval = strnatcmp(\$a['$sortfields[$c]'], \$b['$sortfields[$c]']); if(\$retval) return \$retval; ";
		$code .= "return \$retval;";

		$params = ($order == 'ASC') ? '$a,$b' : '$b,$a';
		uasort($data, create_function($params, $code));
		return $data;
	}

	// ---------------------------------------------------
	// Function: userSort
	// Sort the resource array by a user defined function
	// ---------------------------------------------------	
	function userSort($resource,$sort) {
		foreach ($sort['custom'] as $item) {
			usort($resource,$item[1]);
		}
		return $resource;
	}
		
	// ---------------------------------------------------
	// Function: multiSort
	// Sort the resource array by multiple fields
	// Rows->Columns portion by Jon L. -- intel352@gmail.com
	// Link: http://de3.php.net/manual/en/function.array-multisort.php#73498
	// ---------------------------------------------------
	
	function multiSort($resource,$orderBy) {
		$sort_arr = array();
		foreach($resource AS $uniqid => $row){
			foreach($row AS $key=>$value){
				$sort_arr[$key][$uniqid] = $value;
			}
		}
		
		$array_multisort = 'return array_multisort(';
		foreach ($orderBy['parsed'] as $sort) {
			$array_multisort .= '$sort_arr["'.$sort[0].'"], SORT_'.$sort[1].', ';
		}
		$array_multisort .= '$resource);';
		eval($array_multisort);
		return $resource;
	}

	// ---------------------------------------------------
	// Function: determineIDs
	// Get Document IDs for future use
	// ---------------------------------------------------
		
	function determineIDs($IDs, $IDType, $TVs, $orderBy, $depth, $showPublishedOnly, $seeThruUnpub, $hideFolders, $hidePrivate, $showInMenuOnly, $myWhere, $keywords, $dateSource, $limit, $summarize, $filter, $paginate, $randomize) {
		global $modx;
		if (($summarize == 0 && $summarize != "all") || count($IDs) == 0 || ($IDs == false && $IDs != "0")) {
			return array();
		}
		
		// Get starting IDs;
		switch($IDType) {
			case "parents":
				$IDs = explode(",",$IDs);
				$documentIDs = $this->getChildIDs($IDs, $depth);
			break;
			case "documents":
				$documentIDs = explode(",",$IDs);
			break;
		}
		
		if ($this->advSort == false && $hideFolders==0 && $showInMenuOnly==0 && $myWhere == "" && $filter == false && $hidePrivate == 1 && $keywords==0) { 
			$this->prefetch = false;
				$documents = $this->getDocumentsIDs($documentIDs, $showPublishedOnly);
				$documentIDs = array();
				if ($documents) {
					foreach ($documents as $null=>$doc) {
						$documentIDs[] = $doc["id"];
					}
				}
			return $documentIDs;			
		} else {
			$this->prefetch = true; 
		}

		// Create where clause
		$where = array ();
		if ($myWhere != '') {
			$where[] = $myWhere;
		}
		if ($hideFolders) {
			$where[] = 'isfolder = 0';
		}
		if ($showInMenuOnly) {
			$where[] = 'hidemenu = 0';
		}
		$where = implode(" AND ", $where);
		$limit = ($limit == 0) ? "" : $limit;
			// set limit

		$customReset = $this->customReset;
		if ($keywords) {$this->addField("haskeywords","*","db");$this->addField("hasmetatags","*","db");}
		if ($this->debug) {$this->addField("pagetitle","backend","db");}
		if (count($customReset) > 0) {$this->addField("createdon","backend","db");}
		$resource = $this->getDocuments($documentIDs,$this->fields["backend"]["db"],$TVs,$orderBy,$showPublishedOnly,0,$hidePrivate,$where,$limit,$keywords,$randomize,$dateSource);
		if ($resource !== false) {
			$resource = array_values($resource);
				// remove #'s from keys
			$recordCount = count($resource);
				// count number of records

			if (!$seeThruUnpub) {
				$parentList = $this->getParentList();
					// get parent list
			}
			for ($i = 0; $i < $recordCount; $i++) {
				if (!$seeThruUnpub) {
					$published = $parentList[$resource[$i]["parent"]];
					if ($published == "0")
						unset ($resource[$i]);
				}
				if (count($customReset) > 0) {
					foreach ($customReset as $field) {
						if ($resource[$i][$field] === "0") {
							$resource[$i][$field] = $resource[$i]["createdon"];
						}
					}
				}

			}
			if ($this->debug) {
				$dbg_resource = $resource;
			} 
			if ($filter != false) {
				$filterObj = new filter();
				$resource = $filterObj->execute($resource, $filter);
			}
			if (count($resource) < 1) return array();
			if ($this->advSort == true && $randomize==0) {
				$resource = $this->multiSort($resource,$orderBy);
			}
			if (count($orderBy['custom']) > 0) {
				$resource = $this->userSort($resource,$orderBy);
			}
			
			$fields = (array_intersect($this->fields["backend"],$this->fields["display"]));
			$readyFields = array();
			foreach ($fields as $field) {
				$readyFields = array_merge($readyFields,$field);
			}
			$processedIDs = array ();
			$keep = array();
			foreach ($resource as $key => $value) {
				$processedIDs[] = $value['id'];
				$iKey = '#'.$value['id'];
				foreach ($value as $key=>$v) {
					if (in_array($key,$readyFields)) {
						$keep[$iKey][$key] = $v;
					}
					if ($this->getDocVarType($key) == "tv:prefix") {
						if (in_array(substr($key,2),$readyFields)) {
							$keep[$iKey][$key] = $v;
						}
					}
					
				}
			}
			
			$this->prefetch = array("resource"=>$keep,"fields"=>$fields);
			if ($this->debug) {
				$this->prefetch["dbg_resource"] = $dbg_resource;
				$this->prefetch["dbg_IDs_pre"] = $documentIDs;
				$this->prefetch["dbg_IDs_post"] = $processedIDs;
			}
			if (count($processedIDs) > 0) {
				if ($randomize != 0) {shuffle($processedIDs);}
				$this->sortOrder = array_flip($processedIDs);
					// saves the order of the documents for use later
			}

			return $processedIDs;
		} else {
			return array();
		}
	}

	// ---------------------------------------------------
	// Function: weightedRandom
	// Execute a random order sort
	// ---------------------------------------------------

	function weightedRandom($resource,$field,$show) {
		$type = $this->getDocVarType($field);
		if ($type == "unknown") {
			return $resource;
				// handle vad field passed
		}
		$random = new random();
		foreach ($resource as $document) {
			$doc = $document;
			$random->add($doc,abs(intval($document[$field])));
		}
		$resource = $random->select_weighted_unique($show);
		shuffle($resource);
		return $resource;
	}
	
	
	// ---------------------------------------------------
	// Function: getParentList
	// Get a list of all available parents
	// ---------------------------------------------------
		
	function getParentList() {
		global $modx;
		$kids = array();
		foreach ($modx->documentMap as $null => $document) {
			foreach ($document as $parent => $id) {
				$kids[$parent][] = $id;
			}
		}
		$parents = array();
		foreach ($kids as $item => $value) {
			if ($item != 0) {
				$pInfo = $modx->getPageInfo($item,0,"published");
			} else {
				$pInfo["published"] = "1";
			}
			$parents[$item] = $pInfo["published"];
		}
		return $parents;
	}

	// ---------------------------------------------------
	// Function: appendTV
	// Apeend a TV to the documents array
	// ---------------------------------------------------	
		
	function appendTV($tvname="",$docIDs){
		global $modx;
		
		$baspath= MODX_MANAGER_PATH."/includes";
	    include_once $baspath . "/tmplvars.format.inc.php";
	    include_once $baspath . "/tmplvars.commands.inc.php";

		$tb1 = $modx->getFullTableName("site_tmplvar_contentvalues");
		$tb2 = $modx->getFullTableName("site_tmplvars");

		$query = "SELECT stv.name,stc.tmplvarid,stc.contentid,stv.type,stv.display,stv.display_params,stc.value";
		$query .= " FROM ".$tb1." stc LEFT JOIN ".$tb2." stv ON stv.id=stc.tmplvarid ";
		$query .= " WHERE stv.name='".$tvname."' AND stc.contentid IN (".implode($docIDs,",").") ORDER BY stc.contentid ASC;";
		$rs = $modx->db->query($query);
		$tot = $modx->db->getRecordCount($rs);
		$resourceArray = array();
		for($i=0;$i<$tot;$i++)  {
			$row = @$modx->db->getRow($rs);
			$resourceArray["#".$row['contentid']][$row['name']] = getTVDisplayFormat($row['name'], $row['value'], $row['display'], $row['display_params'], $row['type'],$row['contentid']);   
			$resourceArray["#".$row['contentid']]["tv".$row['name']] = $resourceArray["#".$row['contentid']][$row['name']];
		}
		if ($tot != count($docIDs)) {
			$query = "SELECT name,type,display,display_params,default_text";
			$query .= " FROM $tb2";
			$query .= " WHERE name='".$tvname."' LIMIT 1";
			$rs = $modx->db->query($query);
			$row = @$modx->db->getRow($rs);
			if (strtoupper($row['default_text']) == '@INHERIT') {
				foreach ($docIDs as $id) {
					$defaultOutput = getTVDisplayFormat($row['name'], $row['default_text'], $row['display'], $row['display_params'], $row['type'], $id);
					if (!isset($resourceArray["#".$id])) {
						$resourceArray["#$id"][$tvname] = $defaultOutput;
						$resourceArray["#$id"]["tv".$tvname] = $resourceArray["#$id"][$tvname];
					}
				}
			} else {
				$defaultOutput = getTVDisplayFormat($row['name'], $row['default_text'], $row['display'], $row['display_params'], $row['type'],$row['contentid']);
				foreach ($docIDs as $id) {
					if (!isset($resourceArray["#".$id])) {
						$resourceArray["#$id"][$tvname] = $defaultOutput;
						$resourceArray["#$id"]["tv".$tvname] = $resourceArray["#$id"][$tvname];
					}
				}
			}
		}
		return $resourceArray;
	}	
	
	// ---------------------------------------------------
	// Function: appendKeywords
	// Append keywords's to the resource array
	// ---------------------------------------------------
		
	function appendKeywords($resource) {
		$keys = $this->fetchKeywords($resource);
		$resource["keywords"] = "$keys";
		return $resource;
	}

	// ---------------------------------------------------
	// Function: fetchKeywords
	// Helper function to <appendKeywords>
	// ---------------------------------------------------
		
	function fetchKeywords($resource) {
		global $modx;
	  if($resource['haskeywords']==1) {
	    // insert keywords
	    $metas = implode(",",$modx->getKeywords($resource["id"]));
	  }
	  if($resource['hasmetatags']==1){
	    // insert meta tags
	    $tags = $modx->getMETATags($resource["id"]);
	    foreach ($tags as $n=>$col) {
	      $tag = strtolower($col['tag']);
	      $metas.= ",".$col['tagvalue'];
	    }
	  }
	  return $metas;
	}
	
	// ---------------------------------------------------
	// Function: getChildIDs
	// Get the IDs ready to be processed

	// ---------------------------------------------------

	function getChildIDs($IDs, $depth) {
		global $modx;
		$depth = intval($depth);
		$kids = array();
		$docIDs = array();
		//	RedCat
		foreach($IDs as $id) {
			$kids   = $modx->getChildIds($id,$depth);
			$docIDs = array_merge($docIDs,$kids);
		}
		return array_unique($docIDs);
	}

	// ---------------------------------------------------
	// Function: getDocuments
	// Get documents and append TVs + Prefetch Data, and sort
	// ---------------------------------------------------
	
	function getDocuments($ids= array (), $fields, $TVs, $orderBy, $published= 1, $deleted= 0, $public= 1, $where= '', $limit= "",$keywords=0,$randomize=0,$dateSource=false) {
	global $modx;

	if (count($ids) == 0) {
		return false;
	} else {
		sort($ids);
		$limit= ($limit != "") ? "LIMIT $limit" : ""; // LIMIT capabilities - rad14701
		$tblsc= $modx->getFullTableName("site_content");
		$tbldg= $modx->getFullTableName("document_groups");
    $tbltvc = $modx->getFullTableName("site_tmplvar_contentvalues");
		// modify field names to use sc. table reference
		$fields= "sc.".implode(",sc.",$fields);
		if ($randomize != 0) {
			$sort = "RAND()"; 
		} else {
			$sort= $orderBy['sql'];
		}
    
    //Added by Andchir (http://modx-shopkeeper.ru/)
		if(substr($where, 0, 5)=="@SQL:"){
      $where = ($where == "") ? "" : substr(str_replace('@eq','=',$where), 5);
      $left_join_tvc = "LEFT JOIN $tbltvc tvc ON sc.id = tvc.contentid";
    }else{
			$where= ($where == "") ? "" : 'AND sc.' . implode('AND sc.', preg_replace("/^\s/i", "", explode('AND', $where)));
      $left_join_tvc = '';
    }
      
		if ($public) {
			// get document groups for current user
			if ($docgrp= $modx->getUserDocGroups())
			$docgrp= implode(",", $docgrp);
			$access= ($modx->isFrontend() ? "sc.privateweb=0" : "1='" . $_SESSION['mgrRole'] . "' OR sc.privatemgr=0") .
			(!$docgrp ? "" : " OR dg.document_group IN ($docgrp)");
		}
		
		$published = ($published) ? "AND sc.published=1" : ""; 
		
		//$sql = "SELECT DISTINCT $fields FROM $tblsc sc 
    $sql = "SELECT DISTINCT $fields FROM $tblsc sc $left_join_tvc
		LEFT JOIN $tbldg dg on dg.document = sc.id
		WHERE sc.id IN (" . join($ids, ",") . ") $published AND sc.deleted=$deleted $where
		".($public ? 'AND ('.$access.')' : '')." GROUP BY sc.id" .
		($sort ? " ORDER BY $sort" : "") . " $limit ";

		$result= $modx->db->query($sql);
		$resourceArray= array ();
		$cnt = @$modx->db->getRecordCount($result);
		$TVData = array();
		$TVIDs = array();
		if ($cnt) {
			for ($i= 0; $i < $cnt; $i++) {
				$resource = $modx->db->getRow($result);
				if ($modx->config["server_offset_time"] != 0 && $dateSource !== false) {
					$dateValue = (is_int($resource[$dateSource]) !== true) ? $resource[$dateSource] : strtotime($resource[$dateSource]);
					$resource[$dateSource] = $dateValue + $modx->config["server_offset_time"];
				}
				if($keywords) {
					$resource = $this->appendKeywords($resource);
				}
				if ($this->prefetch == true && $this->sortOrder !== false) $resource["ditto_sort"] = $this->sortOrder[$resource["id"]];
					$TVIDs[] = $resource["id"];
					$resourceArray["#".$resource["id"]] = $resource;
					if (count($this->prefetch["resource"]) > 0) {
						$x = "#".$resource["id"];
						$resourceArray[$x] = array_merge($resource,$this->prefetch["resource"][$x]);
							// merge the prefetch array and the normal array
					}
				}

				$TVs = array_unique($TVs);
				if (count($TVs) > 0) {
					foreach($TVs as $tv){
						$TVData = array_merge_recursive($this->appendTV($tv,$TVIDs),$TVData);
					}
				}

				$resourceArray = array_merge_recursive($resourceArray,$TVData);
				if ($this->prefetch == true && $this->sortOrder !== false) {
					$resourceArray = $this->customSort($resourceArray,"ditto_sort","ASC");
				}
		
				return $resourceArray;
			} else {
				return false;
			}
		}
	}
	
	// ---------------------------------------------------
	// Function: getDocumentsLite
	// Get an array of documents
	// ---------------------------------------------------
	
	function getDocumentsIDs($ids= array (), $published= 1) {
		global $modx;
	    if (count($ids) == 0) {
	        return false;
	    } else {
	        $tblsc= $modx->getFullTableName("site_content");
	        $tbldg= $modx->getFullTableName("document_groups");
	        if ($docgrp= $modx->getUserDocGroups())
	            $docgrp= implode(",", $docgrp);
	        $access= ($modx->isFrontend() ? "sc.privateweb=0" : "1='" . $_SESSION['mgrRole'] . "' OR sc.privatemgr=0") .
	         (!$docgrp ? "" : " OR dg.document_group IN ($docgrp)");
			$published = ($published) ? "AND sc.published=1" : "";         
			$sql= "SELECT DISTINCT sc.id FROM $tblsc sc
	                LEFT JOIN $tbldg dg on dg.document = sc.id
	                WHERE (sc.id IN (" . join($ids, ",") . ") $published AND sc.deleted=0)
	                AND ($access)
	                GROUP BY sc.id ";
	        $result= $modx->db->query($sql);
	        $resourceArray= array ();
	        for ($i= 0; $i < @ $modx->db->getRecordCount($result); $i++) {
	            array_push($resourceArray, @ $modx->db->getRow($result));
	        }
	        return $resourceArray;
	    }
	}
	
	// ---------------------------------------------------
	// Function: cleanIDs
	// Clean the IDs of any dangerous characters
	// ---------------------------------------------------
	
	function cleanIDs($IDs) {
		//Define the pattern to search for
		$pattern = array (
			'`(,)+`', //Multiple commas
			'`^(,)`', //Comma on first position
			'`(,)$`' //Comma on last position
		);

		//Define replacement parameters
		$replace = array (
			',',
			'',
			''
		);

		//Clean startID (all chars except commas and numbers are removed)
		$IDs = preg_replace($pattern, $replace, $IDs);

		return $IDs;
	}
	
	// ---------------------------------------------------
	// Function: buildURL
	// Build a URL with regard to Ditto ID
	// ---------------------------------------------------
	
	public static function buildURL($args,$id=false,$dittoIdentifier=false) {
		global $modx, $dittoID;
			$dittoID = ($dittoIdentifier !== false) ? $dittoIdentifier : $dittoID;
			$query = array();
			foreach ($_GET as $param=>$value) {
				if ($param != 'id' && $param != 'q') {
					$clean_param = htmlspecialchars($param, ENT_QUOTES, $modx->config['modx_charset']);
					if(is_array($value)) {
					  //$query[$param] = $value;
					   foreach($value as $key => $val) {
                         $query[$clean_param][htmlspecialchars($key, ENT_QUOTES)] = htmlspecialchars($val, ENT_QUOTES, $modx->config['modx_charset']);
                       }
					}else{
					  $query[$clean_param] = htmlspecialchars($value, ENT_QUOTES, $modx->config['modx_charset']);
					}
				}
			}
			if (!is_array($args)) {
				$args = explode("&",$args);
				foreach ($args as $arg) {
					$arg = explode("=",$arg);
					$query[$dittoID.$arg[0]] = rawurlencode(trim($arg[1]));
				}
			} else {
				foreach ($args as $name=>$value) {
					$query[$dittoID.$name] = rawurlencode(trim($value));
				}
			}
			$queryString = "";
			foreach ($query as $param=>$value) {
				
        //$queryString .= '&'.$param.'='.(is_array($value) ? implode(",",$value) : $value);
        
        if(!is_array($value)){
          $queryString .= '&'.$param.'='.$value;
        }else{
          foreach ($value as $key=>$val){
            $queryString .= '&'.$param.'['.$key.']='.$val;
          }
        }
			}
			$cID = ($id !== false) ? $id : $modx->documentObject['id'];
			$url = $modx->makeURL(trim($cID), '', $queryString);
			return ($modx->config['xhtml_urls']) ? $url : str_replace("&","&amp;",$url);
	}
	
	// ---------------------------------------------------
	// Function: getParam
	// Get a parameter or use the default language value
	// ---------------------------------------------------
	
	function getParam($param,$langString){
		// get a parameter value and if it is not set get the default language string value
		global $modx,$ditto_lang;
		$out = "";
		if ($this->template->fetch($param) != "") {
			return $modx->getChunk($param);
		} else if(!empty($param)) {
			return $param;
		}else{
			return $ditto_lang[$langString];
		}
	}

	// ---------------------------------------------------
	// Function: paginate
	// Paginate the documents
	// ---------------------------------------------------
		
	function paginate($start, $stop, $total, $summarize, $tplPaginateNext, $tplPaginatePrevious, $tplPaginateNextOff, $tplPaginatePreviousOff, $tplPaginatePage, $tplPaginateCurrentPage, $paginateAlwaysShowLinks, $paginateSplitterCharacter, $max_paginate, $max_previous) {
		global $modx, $dittoID,$ditto_lang;

		if ($stop == 0 || $total == 0 || $summarize==0) {
			return false;
		}
		$next = $start + $summarize;
		$rNext =  $this->template->replace(array('url'=>$this->buildURL("start=$next"),'lang:next'=>$ditto_lang['next']),$tplPaginateNext);
		$previous = $start - $summarize;
		$rPrevious =  $this->template->replace(array('url'=>$this->buildURL("start=$previous"),'lang:previous'=>$ditto_lang['prev']),$tplPaginatePrevious);
		$limten = $summarize + $start;
		if ($paginateAlwaysShowLinks == 1) {
			$previousplaceholder = $this->template->replace(array('lang:previous'=>$ditto_lang['prev']),$tplPaginatePreviousOff);
			$nextplaceholder = $this->template->replace(array('lang:next'=>$ditto_lang['next']),$tplPaginateNextOff);
		} else {
			$previousplaceholder = "";
			$nextplaceholder = "";
		}
		$split = "";
		if ($previous > -1 && $next < $total)
			$split = $paginateSplitterCharacter;
		if ($previous > -1)
			$previousplaceholder = $rPrevious;
		if ($next < $total)
			$nextplaceholder = $rNext;
		if ($start < $total)
			$stop = $limten;
		if ($limten > $total) {
			$limiter = $total;
		} else {
			$limiter = $limten;
		}
		$totalpages = ceil($total / $summarize);

		$cur_x = floor($start / $summarize);
		$min_x = $cur_x - $max_previous;

		if ($min_x < 0)  $min_x = 0;

		$max_x = $min_x + $max_paginate - 1;
		if ($max_x > $totalpages - 1) {
			$max_x = $totalpages - 1;
			$min_x = $max_x - $max_paginate + 1;
		}

		for ($x = 0; $x <= $totalpages -1; $x++) {
			$inc = $x * $summarize;
			$display = $x +1;

			if (($x < $min_x) || ($x > $max_x)) continue;

			if ($inc != $start) {
				$pages .= $this->template->replace(array('url'=>$this->buildURL("start=$inc"),'page'=>$display),$tplPaginatePage);
			} else {
				$modx->setPlaceholder($dittoID."currentPage", $display);
				$pages .= $this->template->replace(array('page'=>$display),$tplPaginateCurrentPage);
			}
		}
		$modx->setPlaceholder($dittoID."next", $nextplaceholder);
		$modx->setPlaceholder($dittoID."previous", $previousplaceholder);
		$modx->setPlaceholder($dittoID."pages", $pages);
		$modx->setPlaceholder($dittoID."splitter", $split);
		$modx->setPlaceholder($dittoID."start", $start +1);
		$modx->setPlaceholder($dittoID."urlStart", $start);
		$modx->setPlaceholder($dittoID."stop", $limiter);
		$modx->setPlaceholder($dittoID."total", $total);	
		$modx->setPlaceholder($dittoID."perPage", $summarize);
		$modx->setPlaceholder($dittoID."totalPages", $totalpages);
		$modx->setPlaceholder($dittoID."ditto_pagination_set", true);
	}	
	
	// ---------------------------------------------------
	// Function: noResults
	// Render the noResults output
	// ---------------------------------------------------	
	function noResults($text,$paginate) {
		global $modx, $dittoID;
		$set = $modx->getPlaceholder($dittoID."ditto_pagination_set");
		if ($paginate && $set !== true) {
			$modx->setPlaceholder($dittoID."next", "");
			$modx->setPlaceholder($dittoID."previous", "");
			$modx->setPlaceholder($dittoID."splitter", "");
			$modx->setPlaceholder($dittoID."start", 0);
			$modx->setPlaceholder($dittoID."urlStart", "#start");
			$modx->setPlaceholder($dittoID."stop", 0);
			$modx->setPlaceholder($dittoID."total", 0);
			$modx->setPlaceholder($dittoID."pages", "");
			$modx->setPlaceholder($dittoID."perPage", 0);
			$modx->setPlaceholder($dittoID."totalPages", 0);
			$modx->setPlaceholder($dittoID."currentPage", 0);			
		}
		return $text;
	}
		
	// ---------------------------------------------------
	// Function: relToAbs
	// Convert relative urls to absolute URLs
	// Based on script from http://wintermute.com.au/bits/2005-09/php-relative-absolute-links/
	// ---------------------------------------------------
	function relToAbs($text, $base) {
		return preg_replace('#(href|src)="([^:"]*)(?:")#','$1="'.$base.'$2"',$text);
	}
}
?>