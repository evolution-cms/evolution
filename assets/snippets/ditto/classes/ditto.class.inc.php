<?php

/*
 * Title: Main Class
 * Purpose:
 *      The Ditto class contains all functions relating to Ditto's
 *      functionality and any supporting functions they need
*/

class ditto {
    var $template,$resource,$format,$debug,$advSort,$sqlOrderBy,$customReset,$fields,$constantFields,$prefetch,$sortOrder,$customPlaceholdersMap;

    function __construct($dittoID,$format,$language,$debug) {
        $this->format = $format;
        $GLOBALS['ditto_lang']  = $language;
        $this->prefetch         = false;
        $this->advSort          = false;
        $this->sqlOrderBy       = array();
        $this->customReset      = array();
        $this->constantFields[] = array('db','tv');
        $this->constantFields['db'] = explode(',', 'id,type,contentType,pagetitle,longtitle,description,alias,link_attributes,published,pub_date,unpub_date,parent,isfolder,introtext,content,richtext,template,menuindex,searchable,cacheable,createdby,createdon,editedby,editedon,deleted,deletedon,deletedby,publishedon,publishedby,menutitle,donthit,privateweb,privatemgr,content_dispo,hidemenu');
        $this->constantFields['tv'] = $this->getTVList();
        $GLOBALS['ditto_constantFields'] = $this->constantFields;
        $this->fields = array('display'=>array(),'backend'=>array('tv'=>array(),'db'=>array('id', 'published')));
        $this->sortOrder = false;
        $this->customPlaceholdersMap = array();
        $this->template = new template();
        
        if (!is_null($debug)) $this->debug = new debug($debug);
    }

    // ---------------------------------------------------
    // Function: getTVList
    // Get a list of all available TVs
    // ---------------------------------------------------
        
    function getTVList() {
        global $modx;
        // TODO: make it so that it only pulls those that apply to the current template
        $tvs = $modx->db->select('name', '[+prefix+]site_tmplvars');
        $dbfields = $modx->db->getColumn('name', $tvs);
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
        if ($type == 'tv:prefix') {
            $type = 'tv';
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
            if (strpos($fields,$delimiter)!==false) $fields = explode($delimiter,$fields);
            else                                    $fields = array($fields);
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
            if ($callback !== false) call_user_func_array($callback, array($name));
        }
        return true;
    }
    
    // ---------------------------------------------------
    // Function: removeField
    // Remove a field to the internal field detection system
    // ---------------------------------------------------
    
    function removeField($name,$location,$type) {
        $key = array_search ($name, $this->fields[$location][$type]);
        if ($key !== false) unset($this->fields[$location][$type][$key]);
    }
    
    // ---------------------------------------------------
    // Function: setDisplayFields
    // Move the detected fields into the Ditto fields array
    // ---------------------------------------------------
    
    function setDisplayFields($fields,$hiddenFields) {
        $this->fields['display'] = $fields;
        if ($hiddenFields) $this->addFields($hiddenFields,'display');
    }

    // ---------------------------------------------------
    // Function: getDocVarType
    // Determine if the provided field is a tv, a database field, or something else
    // ---------------------------------------------------
    
    function getDocVarType($field) {
        global $ditto_constantFields;
        
        $tvFields = $ditto_constantFields['tv'];
        $dbFields = $ditto_constantFields['db'];
        
        if(in_array($field, $tvFields))               return 'tv';
        elseif(in_array(substr($field,2), $tvFields)) return 'tv:prefix'; // TODO: Remove TV Prefix support
        elseif(in_array($field, $dbFields))           return 'db';
        else                                          return 'unknown';
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
            $inputs = array_filter(array_map('trim', explode(',', $orderBy['unparsed'])));
            foreach ($inputs as $input) {
                if(strpos($input,' ')===false) $input .= ' ASC';
                list($sortBy,$sortDir) = explode(' ',$input);
                $sortBy = $this->checkAdvSort($sortBy,$sortDir);
                $this->addField($sortBy,'backend');
                $orderBy['parsed'][] = array($sortBy,strtoupper($sortDir));
            }
        }
        if(!isset($orderBy['parsed'][0])) $orderBy['parsed'][0] = array('id','ASC');
        $orderBy['sql'] = join(', ',$this->sqlOrderBy);
        unset($orderBy['unparsed']);
        return $orderBy;
    }
    
    // ---------------------------------------------------
    // Function: checkAdvSort
    // Check the advSortString
    // ---------------------------------------------------
    function checkAdvSort($sortBy,$sortDir='asc') {
        $advSort = array ('pub_date','unpub_date','editedon','deletedon','publishedon');
        $type = $this->getDocVarType($sortBy);
        switch($type) {
            case 'tv:prefix':
                $sortBy = substr($sortBy, 2);
                $this->advSort = true;
            break;
            case 'tv':
                $this->advSort = true;
            break;
            case 'db':
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
        $parsedFilters = array('basic'=>array(),'custom'=>array());
        $filters = explode($globalDelimiter, $filter);
        if (!empty($filters)) {
            foreach ($filters AS $filter) {
                if (!empty($filter)) {
                    $filterArray = explode($localDelimiter, $filter);
                    $source = $filterArray[0];
                    $this->addField($source,'backend');
                    $value = $filterArray[1];
                    $mode = (isset ($filterArray[2])) ? $filterArray[2] : 1;
                    $parsedFilters['basic'][] = array('source'=>$source,'value'=>$value,'mode'=>$mode);
                }
            }
        }
        if ($cFilters) {
            foreach ($cFilters as $name=>$value) {
                if (!empty($name) && !empty($value)) {
                    $parsedFilters['custom'][$name] = $value[1];
                    $this->addFields($value[0],'backend');
                }
            }    // TODO: Replace addField with addFields with callback
        }
        if($pFilters) {
            foreach ($pFilters as $filter) {
                foreach ($filter as $name=>$value) {
                    $parsedFilters['basic'][] = $value;
                    $this->addFields($value['source'],'backend');
                }
            }    // TODO: Replace addField with addFields with callback
        }
        return $parsedFilters;
    }

    // ---------------------------------------------------
    // Function: render
    // Render the document output
    // ---------------------------------------------------
    
    function render($doc, $template, $removeChunk,$dateSource,$dateFormat,$customPlaceholders=array(),$phx=1,$x=0,$stop=1) {
        global $modx,$ditto_lang;

        if (!is_array($doc)) return $ditto_lang['resource_array_error'];
        
        $ph = $doc;
        $contentVars = array();
        $exFields =& $this->fields['display']['custom'];
        
        foreach ($doc as $name=>$value) {
            $contentVars["[*{$name}*]"] = $value;
        }

        if (in_array('author',$exFields))          $ph['author'] = $this->getAuthor($doc['createdby']);
        if (in_array('title',$exFields))           $ph['title']  = $doc['pagetitle'];
        if (in_array('ditto_iteration',$exFields)) $ph['ditto_iteration'] = $x;
        
        //Added by Andchir
        $r_start = isset($_GET['start']) ? $_GET['start'] : 0;
        $ph['ditto_index'] = $r_start+$x+1;
        
        //Added by Dmi3yy placeholder ditto_class
        $class = array();
        if($x % 2 == 0)                              $class[] = 'even';
        else                                         $class[] = 'odd';
        
        if ($x==0)                                   $class[] = 'first';
        if ($x==($stop -1))                          $class[] = 'last';
        if ($doc['id'] == $modx->documentIdentifier) $class[] = 'current';
        $ph['ditto_class'] = join(' ', $class);

        // set url placeholder
        if (in_array('url',$exFields)) {
            if($doc['id']==$modx->config['site_start']) $ph['url'] = $modx->config['site_url'];
            else                                        $ph['url'] = $modx->makeURL($doc['id'],'','','full');
        }

        if (in_array('date',$exFields)) {
            $timestamp = ($doc[$dateSource] != '0') ? $doc[$dateSource] : $doc['createdon'];
            if (is_array($timestamp)) {
                if(!$this->isNum($timestamp[1])) $timestamp[1] = strtotime($timestamp[1]);
                $timestamp = $timestamp[1] + $timestamp[0];
            }
            $ph['date'] = strftime($dateFormat,$timestamp);
        }
        
        if (in_array('content',$this->fields['display']['db']) && $this->format != 'html') {
            $ph['content'] = $this->relToAbs($doc['content'], $modx->config['site_url']);
        }
         
        if (in_array('introtext',$this->fields['display']['db']) && $this->format != 'html') {
            $ph['introtext'] = $this->relToAbs($doc['introtext'], $modx->config['site_url']);
        }
        
        // set custom placeholder
        foreach ($customPlaceholders as $name=>$value) {
            if ($name != '*') {
                $ph[$name] = call_user_func($value[1],$doc);
                unset($customPlaceholders[$name]);
            }
        }
        
        foreach ($customPlaceholders as $name=>$value) {
            $ph = call_user_func($value,$ph);
        }
        
        if ($phx) $output = $this->parseModifiers($template,$ph,$contentVars);
        else {
             $output = $this->template->replace($ph,$template);
            $output = $this->template->replace($contentVars,$output);
        }
        
        if ($removeChunk) {
            foreach ($removeChunk as $chunk) {
                $output = str_replace('{{'.$chunk.'}}','',$output);
                $output = str_replace($modx->getChunk($chunk),'',$output);
                // remove chunk that is not wanted
            }
        }
        return $output;
    }
    
    function parseModifiers($tpl,$ph,$contentVars){
        global $modx;
        if ($modx->config['enable_filter']) {
            $content = $this->parseDocumentSource($tpl,$ph);
        }
        else {
            foreach($ph as $key=>$content) {
                $ph[$key] = str_replace( array_keys($contentVars), array_values($contentVars), $content );
            }
            $phx = new prePHx($tpl);
            $phx->setPlaceholders($ph);
            $content = $phx->output();
        }
        return $content;
    }
    
    function parseDocumentSource($content='',$ph)
    {
        global $modx;
        
        if(strpos($content,'[')===false && strpos($content,'{')===false) return $content;
        
        $loopLimit = @ $modx->maxParserPasses ?: 10;
        $bt='';
        $i=0;
        while($bt!==$content)
        {
            $bt = $content;
            if(strpos($content,'[+')!==false) $content = $modx->parseText($content,$ph);
            if(strpos($content,'[+')!==false) continue;

            if(strpos($content,'[*')!==false && $modx->documentIdentifier)
                                              $content = $modx->mergeDocumentContent($content);
            if(strpos($content,'[+')!==false && $content!==$bt) continue;
            if(strpos($content,'[(')!==false) $content = $modx->mergeSettingsContent($content);
            if(strpos($content,'[+')!==false && $content!==$bt) continue;
            if(strpos($content,'{{')!==false) $content = $modx->mergeChunkContent($content);
            if(strpos($content,'[+')!==false && $content!==$bt) continue;
            if(strpos($content,'[!')!==false) $content = str_replace(array('[!','!]'),array('[[',']]'),$content);
            if(strpos($content,'[[')!==false) $content = $modx->evalSnippets($content);

            if($content===$bt)  break;
            if($loopLimit < $i) break;
            $i++;
        }
        return $content;
    }

    // ---------------------------------------------------
    // Function: parseFields
    // Find the fields that are contained in the custom placeholders or those that are needed in other functions
    // ---------------------------------------------------
    
    function parseFields($placeholders,$seeThruUnpub,$dateSource,$randomize) {
        $this->parseCustomPlaceholders($placeholders);
        $this->parseDBFields($seeThruUnpub);
        if ($randomize != 0) {
            $this->addField($randomize,'backend');
        }
        $this->addField('id','display','db');
        $this->addField('pagetitle','display','db');
        $this->addField('parent','display','db');
        $checkOptions = array('createdon','pub_date','unpub_date','editedon','deletedon','publishedon');
        if (in_array($dateSource,$checkOptions)) {
            $this->addField($dateSource,'display');
        }
        if (in_array('date',$this->fields['display']['custom'])) {
            $this->addField($dateSource,'display');
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
            $this->addField($name,'display','custom');
            $this->removeField($name,'display','unknown');
            $source = $value[0];
    
            if(is_array($source)) {
                if(strpos($source[0],',')!==false){
                    $fields = array_filter(array_map('trim', explode(',', $source[0])));
                    foreach ($fields as $field) {
                        $this->addField($field,$source[1]);
                        $this->customPlaceholdersMap[$name] = $field;
                    }
                } else {
                    $this->addField($source[0],$source[1]);
                    $this->customPlaceholdersMap[$name] = $source[0];
                }    // TODO: Replace addField with addFields with callback
            } elseif(is_array($value)) {
                $fields = array_filter(array_map('trim', explode(',', $source)));
                foreach ($fields as $field) {
                    $this->addField($field,'display');
                    $this->customPlaceholdersMap[$name] = $field;
                }
            }
        }
    }
    
    // ---------------------------------------------------
    // Function: parseDBFields
    // Parse out the fields required for each state
    // ---------------------------------------------------
    
    function parseDBFields($seeThruUnpub) {
        if (!$seeThruUnpub) {
            $this->addField('parent','backend','db');
        }
        
        if (in_array('author',$this->fields['display']['custom'])) {
            $this->fields['display']['db'][] = 'createdby';
        }
        
        if (count($this->fields['display']['tv']) >= 0) {
            $this->addField('published','display','db');
        }
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
        return ($user['fullname'] != '') ? $user['fullname'] : $user['username'];
    }
    
    // ---------------------------------------------------
    // Function: customSort
    // Sort resource array if advanced sorting is needed
    // ---------------------------------------------------

    function customSort($data, $fields, $order) {
        // Covert $fields string to array
        // user contributed
        $sortfields = array_filter(array_map('trim', explode(',', $fields)));

        $code = '';
        foreach($sortfields as $field) {
            $code .= sprintf('$retval = strnatcmp($a["%s"], $b["%s"]); if($retval) return $retval; ',$field,$field);
        }
        $code .= 'return $retval;';

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
        foreach($resource as $uniqid => $row){
            foreach($row as $key=>$value){
                $sort_arr[$key][$uniqid] = $value;
            }
        }
        
        $_ ='';
        foreach ($orderBy['parsed'] as $sort) {
            $_ .= sprintf('$sort_arr["%s"], SORT_%s, ', $sort[0], $sort[1]);
        }
        $array_multisort = sprintf('return array_multisort( %s $resource);', $_);
        eval($array_multisort);
        return $resource;
    }

    // ---------------------------------------------------
    // Function: determineIDs
    // Get Document IDs for future use
    // ---------------------------------------------------
        
    function determineIDs($IDs, $IDType, $TVs, $orderBy, $depth, $showPublishedOnly, $seeThruUnpub, $hideFolders, $hidePrivate, $showInMenuOnly, $myWhere, $dateSource, $limit, $summarize, $filter, $paginate, $randomize) {
        global $modx;
        if (($summarize == 0 && $summarize != 'all') || $IDs=='') {
            return array();
        }
        
        // Get starting IDs;
        switch($IDType) {
            case 'parents':
                $IDs = explode(',',$IDs);
                $documentIDs = $this->getChildIDs($IDs, $depth);
            break;
            case 'documents':
                if(!preg_match('@^[0-9, ]*$@',$IDs)) exit(sprintf('Illegal value of &amp;documents: %s', $IDs));
                $documentIDs = explode(',',$IDs);
            break;
        }
        
        if ($this->advSort == false && $hideFolders==0 && $showInMenuOnly==0 && $myWhere == '' && $filter == false && $hidePrivate == 1) { 
            $this->prefetch = false;
                $documents = $this->getDocumentsIDs($documentIDs, $showPublishedOnly);
                $documentIDs = array();
                if ($documents) {
                    foreach ($documents as $null=>$doc) {
                        $documentIDs[] = $doc['id'];
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
        $where = join(' AND ', $where);
        $limit = ($limit == 0) ? '' : $limit;
            // set limit

        $customReset = $this->customReset;
        if ($this->debug) {$this->addField('pagetitle','backend','db');}
       
        if (count($customReset) > 0) {$this->addField('createdon','backend','db');}
        $resource = $this->getDocuments($documentIDs,$this->fields['backend']['db'],$TVs,$orderBy,$showPublishedOnly,0,$hidePrivate,$where,$limit,$randomize,$dateSource);

// EPO - End of change (then see line 692 - if ($limit) array_slice($resource, 0, $limit);    )

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
                    $published = $parentList[$resource[$i]['parent']];
                    if ($published == '0')
                        unset ($resource[$i]);
                }
                if (count($customReset) > 0) {
                    foreach ($customReset as $field) {
                        if ($resource[$i][$field] === '0') {
                            $resource[$i][$field] = $resource[$i]['createdon'];
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
            
            //intersel - see above in order to limit the array to $limit.
                    if ($limit) $resource=array_slice($resource, 0, $limit);
      
            $fields = (array_intersect($this->fields['backend'],$this->fields['display']));
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
                    if ($this->getDocVarType($key) == 'tv:prefix') {
                        if (in_array(substr($key,2),$readyFields)) {
                            $keep[$iKey][$key] = $v;
                        }
                    }
                }
            }
            
            $this->prefetch = array('resource'=>$keep,'fields'=>$fields);
            if ($this->debug) {
                $this->prefetch['dbg_resource'] = $dbg_resource;
                $this->prefetch['dbg_IDs_pre'] = $documentIDs;
                $this->prefetch['dbg_IDs_post'] = $processedIDs;
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
        if ($type == 'unknown') {
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
                $pInfo = $modx->getPageInfo($item,0,'published');
            } else {
                $pInfo['published'] = '1';
            }
            $parents[$item] = $pInfo['published'];
        }
        return $parents;
    }

    // ---------------------------------------------------
    // Function: appendTV
    // Apeend a TV to the documents array
    // ---------------------------------------------------
        
    function appendTV($tvname='',$docIDs){
        global $modx;
        
        $baspath= MODX_MANAGER_PATH.'/includes';
        include_once $baspath . '/tmplvars.format.inc.php';
        include_once $baspath . '/tmplvars.commands.inc.php';

        $fields = 'stv.name,stc.tmplvarid,stc.contentid,stv.type,stv.display,stv.display_params,stc.value';
        $from[] = '[+prefix+]site_tmplvar_contentvalues AS stc';
        $from[] = 'LEFT JOIN [+prefix+]site_tmplvars AS stv ON stv.id=stc.tmplvarid';
        $where = sprintf("stv.name='%s' AND stc.contentid IN (%s)", $modx->db->escape($tvname), join($docIDs,','));
        $rs= $modx->db->select($fields, $from, $where, 'stc.contentid ASC');

        $docs = array();
        while ($row = $modx->db->getRow($rs)) {
            $docs["#".$row['contentid']][$row['name']] = getTVDisplayFormat($row['name'], $row['value'], $row['display'], $row['display_params'], $row['type'],$row['contentid']);
            $docs["#".$row['contentid']]["tv".$row['name']] = $docs["#".$row['contentid']][$row['name']];
        }
        if (count($docs) != count($docIDs)) {
            $rs = $modx->db->select("name,type,display,display_params,default_text", '[+prefix+]site_tmplvars', "name='{$tvname}'", '', 1);
            $row = $modx->db->getRow($rs);
            if (strtoupper($row['default_text']) == '@INHERIT') {
                foreach ($docIDs as $id) {
                    $defaultOutput = getTVDisplayFormat($row['name'], $row['default_text'], $row['display'], $row['display_params'], $row['type'], $id);
                    if (!isset($resourceArray["#".$id])) {
                        $docs["#$id"][$tvname] = $defaultOutput;
                        $docs["#$id"]["tv".$tvname] = $docs["#$id"][$tvname];
                    }
                }
            } else {
                $row['contentid'] = isset($row['contentid']) ? $row['contentid'] : '';
                $defaultOutput = getTVDisplayFormat($row['name'], $row['default_text'], $row['display'], $row['display_params'], $row['type'],$row['contentid']);
                foreach ($docIDs as $id) {
                    if (!isset($docs["#".$id])) {
                        $docs["#$id"][$tvname] = $defaultOutput;
                        $docs["#$id"]["tv".$tvname] = $docs["#$id"][$tvname];
                    }
                }
            }
        }
        return $docs;
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
        //    RedCat
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
    
    function getDocuments($ids= array (), $fields, $TVs, $orderBy, $published= 1, $deleted= 0, $pubOnly= 1, $where= '', $limit='',$randomize=0,$dateSource=false) {
        global $modx;
        
        if (count($ids) == 0) return false;
        sort($ids);
        // modify field names to use sc. table reference
        $fields= 'sc.'.join(',sc.',$fields);
        
        if ($randomize != 0) $sort = 'RAND()';
        else                 $sort= $orderBy['sql'];
        
        //Added by Andchir (http://modx-shopkeeper.ru/)
        if(substr($where, 0, 5)=='@SQL:'){
            if($where) $where = substr(str_replace('@eq','=',$where), 5);
            $left_join_tvc = 'LEFT JOIN [+prefx+]site_tmplvar_contentvalues AS tvc ON sc.id = tvc.contentid';
        } else {
            if($where) $where = 'AND sc.' . join(' AND sc.', array_filter(array_map('trim', explode('AND', $where))));
            $left_join_tvc = '';
        }
        
        if ($pubOnly) {
            if($modx->isFrontend())         $access = 'sc.privateweb=0';
            elseif($_SESSION['mgrRole']!=1) $access = 'sc.privatemgr=0';
            else                            $access = '';
            
            if($access) {
                $docgrp=$modx->getUserDocGroups();
                if($docgrp) {
                    $access .= sprintf(' OR dg.document_group IN (%s)', join(',', $docgrp));
                    $access = "({$access})";
                }
            }
        }
        
        $published = ($published) ? 'AND sc.published=1' : '';
        
        $from = array();
        $from[] = "[+prefix+]site_content sc {$left_join_tvc}";
        $from[] = 'LEFT JOIN [+prefix+]document_groups dg on dg.document=sc.id';
        $sqlWhere = array();
        $sqlWhere[] = sprintf('sc.id IN (%s)', join(',', $ids));
        $sqlWhere[] = $published;
        $sqlWhere[] = "AND sc.deleted={$deleted}";
        $sqlWhere[] = $where;
        if($pubOnly && $access) $sqlWhere[] = "AND {$access}";
        $sqlWhere[] = 'GROUP BY sc.id';
        $rs= $modx->db->select("DISTINCT {$fields}",$from,$sqlWhere,$sort,$limit);
        if(!$modx->db->getRecordCount($rs)) return false;
        
        $docs   = array();
        $TVData = array();
        $TVIDs  = array();
        while ($doc = $modx->db->getRow($rs)) {
            if ($dateSource !== false) {
                if(!$this->isNum($doc[$dateSource])) $doc[$dateSource] = strtotime($doc[$dateSource]);
                if($modx->config['server_offset_time'] != 0)
                    $doc[$dateSource] += $modx->config['server_offset_time'];
            }
            
            if ($this->prefetch && $this->sortOrder!==false) $doc['ditto_sort'] = $this->sortOrder[$doc['id']];
            
            $k = '#'.$doc['id'];
            if (!empty($this->prefetch['resource'])) {
                $docs[$k] = array_merge($doc,$this->prefetch['resource'][$k]);
                // merge the prefetch array and the normal array
            }
            else $docs[$k] = $doc;
            
            $TVIDs[] = $doc['id'];
        }

        $TVs = array_unique($TVs);
        if (!empty($TVs)) {
            foreach($TVs as $tv){
                $TVData = array_merge_recursive($this->appendTV($tv,$TVIDs),$TVData);
            }
        }

        $docs = array_merge_recursive($docs,$TVData);
        if ($this->prefetch == true && $this->sortOrder !== false)
            $docs = $this->customSort($docs,'ditto_sort','ASC');

        return $docs;
    }
    
    // ---------------------------------------------------
    // Function: getDocumentsLite
    // Get an array of documents
    // ---------------------------------------------------
    
    function getDocumentsIDs($ids= array (), $published= 1) {
        global $modx;
        if (empty($ids)) {
            return false;
        } else {
            $ids = join(',',$ids);
            $from[] = '[+prefix+]site_content sc';
            $from[] = 'LEFT JOIN [+prefix+]document_groups dg on dg.document=sc.id';
            $published = $published ? 'AND sc.published=1' : '';
            if($modx->isFrontend())         $access = 'sc.privateweb=0';
            elseif($_SESSION['mgrRole']!=1) $access = 'sc.privatemgr=0';
            else                            $access = '';
            
            if($access) {
                $docgrp=$modx->getUserDocGroups();
                if($docgrp) {
                    $access .= sprintf(' OR dg.document_group IN (%s)', join(',', $docgrp));
                    $access = "({$access})";
                }
                $access = "AND {$access}";
            }
            
            if($published) $where = sprintf('(sc.id IN (%s) AND sc.published=1 AND sc.deleted=0) %s GROUP BY sc.id', $ids, $access);
            else           $where = sprintf('(sc.id IN (%s) AND sc.deleted=0)                    %s GROUP BY sc.id', $ids, $access);
            
            $rs= $modx->db->select('DISTINCT sc.id', $from, $where);
            $docs = $modx->db->makeArray($rs);
            return $docs;
        }
    }
    
    // ---------------------------------------------------
    // Function: cleanIDs
    // Clean the IDs of any dangerous characters
    // ---------------------------------------------------
    
    function cleanIDs($IDs) {
        //Clean startID (all chars except commas and numbers are removed)
        $IDs = trim($IDs,',');
        $IDs = preg_replace('/,+/', ',', $IDs);
        $IDs = str_replace(' ','',$IDs);

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
                $args = explode('&',$args);
                foreach ($args as $arg) {
                    $arg = explode('=',$arg);
                    $query[$dittoID.$arg[0]] = rawurlencode(trim($arg[1]));
                }
            } else {
                foreach ($args as $name=>$value) {
                    $query[$dittoID.$name] = rawurlencode(trim($value));
                }
            }
            $queryString = '';
            foreach ($query as $param=>$value) {

                //$queryString .= '&'.$param.'='.(is_array($value) ? join(',',$value) : $value);
                if (!is_array($value)) {
                    if (!($modx->config['seostrict']=='1' && $param == $dittoID.'start' && !$value)) $queryString .= '&'.$param.'='.$value;
                }
                else {
                    foreach ($value as $key=>$val) {
                        $queryString .= '&'.$param.'['.$key.']='.$val;
                    }
                }
            }
            $cID = ($id !== false) ? $id : $modx->documentObject['id'];
            $url = $modx->makeURL(trim($cID), '', $queryString);
            return ($modx->config['xhtml_urls']) ? $url : str_replace('&','&amp;',$url);
    }
    
    // ---------------------------------------------------
    // Function: getParam
    // Get a parameter or use the default language value
    // ---------------------------------------------------
    
    function getParam($param,$langString){
        // get a parameter value and if it is not set get the default language string value
        global $modx,$ditto_lang;
        $output = '';
        if (substr($param,0,1)==='@') {
            $output = $this->template->fetch($param);
        } elseif(!empty($param)) {
            $output = $modx->getChunk($param);
        } else {
            $output = $ditto_lang[$langString];
        }
        if(trim($output)==='') $output = $param;
        return $output;
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
            $previousplaceholder = '';
            $nextplaceholder = '';
        }
        $split = '';
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

        $modx->setPlaceholder('dittoID', $dittoID);
        $pages = '';
        for ($x = 0; $x <= $totalpages -1; $x++) {
            $inc = $x * $summarize;
            $display = $x +1;

            if (($x < $min_x) || ($x > $max_x)) continue;

            if ($inc != $start) {
                $pages .= $this->template->replace(array('url'=>$this->buildURL("start=$inc"),'page'=>$display),$tplPaginatePage);
            } else {
                $modx->setPlaceholder($dittoID.'currentPage', $display);
                $pages .= $this->template->replace(array('page'=>$display),$tplPaginateCurrentPage);
            }
        }
        if ($totalpages>1){
            $modx->setPlaceholder($dittoID.'next', $nextplaceholder);
            $modx->setPlaceholder($dittoID.'previous', $previousplaceholder);
            $modx->setPlaceholder($dittoID.'pages', $pages);
        }elseif($paginateAlwaysShowLinks == 1){
            $modx->setPlaceholder($dittoID.'next', $nextplaceholder);
            $modx->setPlaceholder($dittoID.'previous', $previousplaceholder);
            $modx->setPlaceholder($dittoID.'pages', $pages);
        }    
        $modx->setPlaceholder($dittoID.'splitter', $split);
        $modx->setPlaceholder($dittoID.'start', $start +1);
        $modx->setPlaceholder($dittoID.'urlStart', $start);
        $modx->setPlaceholder($dittoID.'stop', $limiter);
        $modx->setPlaceholder($dittoID.'total', $total);
        $modx->setPlaceholder($dittoID.'perPage', $summarize);
        $modx->setPlaceholder($dittoID.'totalPages', $totalpages);
        $modx->setPlaceholder($dittoID.'ditto_pagination_set', true);
    }
    
    // ---------------------------------------------------
    // Function: noResults
    // Render the noResults output
    // ---------------------------------------------------
    function noResults($text,$paginate) {
        global $modx, $dittoID;
        $set = $modx->getPlaceholder($dittoID.'ditto_pagination_set');
        if ($paginate && $set !== true) {
            $modx->setPlaceholder('dittoID', $dittoID);
            $modx->setPlaceholder($dittoID.'next', '');
            $modx->setPlaceholder($dittoID.'previous', '');
            $modx->setPlaceholder($dittoID.'splitter', '');
            $modx->setPlaceholder($dittoID.'start', 0);
            $modx->setPlaceholder($dittoID.'urlStart', '#start');
            $modx->setPlaceholder($dittoID.'stop', 0);
            $modx->setPlaceholder($dittoID.'total', 0);
            $modx->setPlaceholder($dittoID.'pages', '');
            $modx->setPlaceholder($dittoID.'perPage', 0);
            $modx->setPlaceholder($dittoID.'totalPages', 0);
            $modx->setPlaceholder($dittoID.'currentPage', 0);
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
    
    function isNum($str='') {
        return preg_match('@^[1-9][0-9]*$@',$str);
    }
}
