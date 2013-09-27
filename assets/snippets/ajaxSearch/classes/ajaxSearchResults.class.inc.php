<?php
/* -----------------------------------------------------------------------------
* Snippet: AjaxSearch
* -----------------------------------------------------------------------------
* @package  AjaxSearchResults
*
* @author       Coroico - www.evo.wangba.fr
* @version      1.9.3
* @date         26/09/2012
*
* Purpose:
*    The AjaxSearchResults class contains all functions and data used to manage Results
*
*/

define('GROUP_CONCAT_LENGTH', 4096); // maximum length of the group concat

class AjaxSearchResults {

    // public variables
    var $asCfg = null;
    var $asCtrl = null;
    var $asOutput = null;
    var $asUtil = null;
    var $asLog = null;

    var $dbg = false;
    var $dbgRes = false;
    var $log;

    var $groupResults = array();
    var $extractNb;
    var $withExtract;

    // private variables
    var $_siteList;
    var $_subsiteList;

    var $_groupMixedResults = array();
    var $_extractFields = array();
    var $_asRequest;

    var $_array_key, $_filtertype, $_filterValue;

    var $_idType;
    var $_pardoc;
    var $_depth;

    /*
    *  Constructs the ajaxSearchResults object
    *
    *  @access public
    */
    function AjaxSearchResults() {
    }
    /*
    *  Initializes the class into the proper context
    *
    *  @access public
    *  @param AjaxSearchConfig &$asCfg configuration context
    *  @param AjaxSearchCtrl &$asCtrl controler instance
    *  @param AjaxSearchOutput &$asOutput ouput instance
    *  @param AjaxSearchUtil &$asUtil debug instance
    *  @param boolean $dbg debug flag
    *  @param boolean $dbgRes debug flag for results
    */
    function init(&$asCfg, &$asCtrl, &$asOutput, &$asUtil){
        $this->asCfg =& $asCfg;
        $this->asCtrl =& $asCtrl;
        $this->asOutput =& $asOutput;
        $this->asUtil =& $asUtil;
        $this->dbg = $asUtil->dbg;
        $this->dbgRes = $asUtil->dbgRes;
    }
    /*
    *  Get search results
    *
    *  @access public
    *  @param string &$msgErr message error
    *  @return boolean true if ok
    */
    function getSearchResults(&$msgErr) {
        global $modx;
        $results = array();
        include_once AS_PATH . "classes/ajaxSearchRequest.class.inc.php";
        if (class_exists('AjaxSearchRequest')) {
            $this->_asRequest = new AjaxSearchRequest($this->asUtil,$this->asCfg->pgCharset);
        }
        if (!$this->_getSiteList($msgErr)) return false;
        foreach ($this->_siteList as $site) {
            if (!$this->_getSubsiteList($site,$msgErr)) return false;
            foreach ($this->_subsiteList as $subsite) {
                if (!$this->_getSubsiteParams($site, $subsite,$msgErr)) return false;
                if (!$this->_checkParams($msgErr)) return false;
                $this->asCfg->saveConfig($site, $subsite);
                if ($this->asCfg->cfg['showResults']) {
                    $this->asOutput->initClassVariables();
                    $bsf = $this->_doBeforeSearchFilter();
                    $results = $this->_asRequest->doSearch($this->asCtrl->searchString, $this->asCtrl->advSearch, $this->asCfg->cfg, $bsf, $this->asCtrl->fClause);
                    $results = $this->_doFilter($results, $this->asCtrl->searchString, $this->asCtrl->advSearch);
                    $this->_setSearchResults($site, $subsite, $results);
                }
            }
        }
        $this->asCfg->restoreConfig(DEFAULT_SITE, DEFAULT_SUBSITE);
        $this->_sortMixedResults();
        if ($this->dbgRes) $this->asUtil->dbgRecord($this->asCfg->scfg, "AjaxSearch - scfg");
        if ($this->dbgRes) $this->asUtil->dbgRecord($this->groupResults, "AjaxSearch - group results");
        if ($this->dbgRes) $this->asUtil->dbgRecord($this->_groupMixedResults, "AjaxSearch - group mixed results");

        return true;
    }
    /*
    *  Get the list of sites from snippet call
    */
    function _getSiteList(&$msgErr) {
        $siteList = array();
        if ($this->asCtrl->forThisAs) {
            if ($this->asCfg->cfg['sites']) $siteList = explode(',', $this->asCfg->cfg['sites']);
            else $siteList[0] = DEFAULT_SITE;
        }
        if ($this->dbgRes) $this->asUtil->dbgRecord($siteList, "getSiteList - siteList");
        $this->_siteList = $siteList;
        return true;
    }
    /*
    *  Get the list of subsites from subsearch parameter
    */
    function _getSubsiteList($site, &$msgErr) {
        $subsiteList = array();
        if ($this->asCtrl->forThisAs) {
            if ($this->asCtrl->subSearch) $subsiteList = explode(',', $this->asCtrl->subSearch);
            else $subsiteList[0] = DEFAULT_SUBSITE;
        }
        if ($this->dbgRes) $this->asUtil->dbgRecord($subsiteList, "getSubsiteList - subsiteList");
        $this->_subsiteList = $subsiteList;
        return true;
    }
    /*
    *  Get the parameters for each subsite
    */
    function _getSubsiteParams($site, $subsite, &$msgErr) {
        $msgErr = '';

        if ($site != DEFAULT_SITE) {
            $siteConfigFunction = SITE_CONFIG;
            if (!function_exists($siteConfigFunction)) {
                $msgErr = '<br /><h3>AjaxSearch error: search function ' . $siteConfigFunction .  ' not defined in the configuration file: ' . $this->asCfg->cfg['config'] . ' !</h3><br />';
                return false;
            }
            else {
                $sitecfg = $siteConfigFunction($site);
                if (!count($sitecfg)) {
                    $msgErr = '<br /><h3>AjaxSearch error: Site ' .$site .  ' not defined in the configuration file: ' . $this->asCfg->cfg['config'] . ' !</h3><br />';
                    return false;
                }
            }
        }

        if ($subsite != DEFAULT_SUBSITE) {
            $subsiteConfigFunction = SUBSITE_CONFIG;
            if (!function_exists($subsiteConfigFunction)) {
                $msgErr = '<br /><h3>AjaxSearch error: search function ' . $subsiteConfigFunction .  ' not defined in the configuration file: ' . $this->asCfg->cfg['config'] . ' !</h3><br />';
                return false;
            }
            else {
                $subsitecfg = $subsiteConfigFunction($site,$subsite);
                if (!count($subsitecfg)) {
                    $msgErr = '<br /><h3>AjaxSearch error: Subsite ' .$subsite .  ' of ' . $site . 'not defined in the configuration file: ' . $this->asCfg->cfg['config'] . ' !</h3><br />';
                    return false;
                }
            }
        }
        $this->asCfg->cfg = array_merge($this->asCfg->bcfg, (array)$sitecfg, (array)$subsitecfg);
        return true;
    }
    /*
    * Check or not search params
    */
    function _checkParams(&$msgErr) {
        global $modx;

        $msgErr = '';
        if ($this->asCtrl->forThisAs) {
            if (isset($this->asCfg->cfg['extractLength'])) {
                if ($this->asCfg->cfg['extractLength'] == 0) $this->asCfg->cfg['extract'] = 0;
                if ($this->asCfg->cfg['extractLength'] < EXTRACT_MIN) $this->asCfg->cfg['extractLength'] = EXTRACT_MIN;
                if ($this->asCfg->cfg['extractLength'] > EXTRACT_MAX) $this->asCfg->cfg['extractLength'] = EXTRACT_MAX;
            }
            if (isset($this->asCfg->cfg['extract'])) {
                $extr = explode(':', $this->asCfg->cfg['extract']);
                if (($extr[0] == '') || (!is_numeric($extr[0]))) $extr[0] = 0;
                if (($extr[1] == '') || (is_numeric($extr[1]))) $extr[1] = 'content';
                $this->asCfg->cfg['extract'] = $extr[0] . ":" . $extr[1];
            }
            if (isset($this->asCfg->cfg['opacity'])) {
                if ($this->asCfg->cfg['opacity'] < 0.) $this->asCfg->cfg['opacity'] = 0.;
                if ($this->asCfg->cfg['opacity'] > 1.) $this->asCfg->cfg['opacity'] = 1.;
            }

            // check that the tables where to do the search exist
            if (isset($this->asCfg->cfg['whereSearch'])) {
                $tables_array = explode('|', $this->asCfg->cfg['whereSearch']);
                foreach ($tables_array as $table) {
                    $fields_array = explode(':', $table);
                    $tbcode = $fields_array[0];
                    if (($tbcode != 'content') && ($tbcode != 'tv') && ($tbcode != 'jot') && ($tbcode != 'maxigallery') && !function_exists($tbcode)) {
                        $msgErr = "<br /><h3>AjaxSearch error: table $tbcode not defined in the configuration file: " . $this->asCfg->cfg['config'] . " !</h3><br />";
                        return false;
                    }
                }
            }

            // check the list of tvs enabled with "withTvs"
            if ((isset($this->asCfg->cfg['withTvs'])) &&  ($this->asCfg->cfg['withTvs'])){
                $tv_array = explode(':', $this->asCfg->cfg['withTvs']);
                $tvSign = $tv_array[0];
                if (($tvSign != '+') && ($tvSign != '-')) {
                    $tvList = $tvSign;
                    $tvSign = '+';
                }
                else {
                    if (isset($tv_array[1])) $tvList = $tv_array[1];
                    else $tvList = '';
                }
                if (!$this->_validListTvs($tvList, $msgErr)) return False;
                $this->asCfg->cfg['withTvs'] = ($tvList) ? $tvSign . ':' . $tvList : $tvSign;
            }

            // check the list of tvs enabled with "phxTvs" - filter the tv already enabled by withTvs
            if ((isset($this->asCfg->cfg['withTvs'])) &&  ($this->asCfg->cfg['phxTvs'])){
                unset($tv_array);
                $tv_array = explode(':', $this->asCfg->cfg['phxTvs']);
                $tvSign = $tv_array[0];
                if (($tvSign != '+') && ($tvSign != '-')) {
                    $tvList = $tvSign;
                    $tvSign = '+';
                }
                else {
                    if (isset($tv_array[1])) $tvList = $tv_array[1];
                    else $tvList = '';
                }
                if (!$this->_validListTvs($tvList, $msgErr)) return False;
                $this->asCfg->cfg['phxTvs'] = ($tvList) ? $tvSign . ':' . $tvList : $tvSign;
            }

            if (isset($this->asCfg->cfg['hideMenu'])) {
                $this->asCfg->cfg['hideMenu'] = (($this->asCfg->cfg['hideMenu'] < 0)  || ($this->asCfg->cfg['hideMenu'] > 2)) ?  2 : $this->asCfg->cfg['hideMenu'];
            }

            if (isset($this->asCfg->cfg['hideLink'])) {
                $this->asCfg->cfg['hideLink'] = (($this->asCfg->cfg['hideLink'] < 0)  || ($this->asCfg->cfg['hideLink'] > 1)) ? 1 : $this->asCfg->cfg['hideLink'];
            }

            $this->_idType = ($this->asCfg->cfg['documents'] != "") ? "documents" : "parents";
            $this->_pardoc = ($this->_idType == "parents") ? $this->asCfg->cfg['parents'] : $this->asCfg->cfg['documents'];
            $this->_depth = $this->asCfg->cfg['depth'];

            $this->asCfg->cfg['docgrp'] = '';
            if ($docgrp = $modx->getUserDocGroups()) $this->asCfg->cfg['docgrp'] = implode(",", $docgrp);

            if (isset($this->asCfg->cfg['filter'])) {
            }

        } else {
            $this->asCfg->cfg['showResults'] = false;
        }
        return true;
    }
    /*
    *  Set up search results
    */
    function _setSearchResults($site, $subsite, $rs) {
        global $modx;
        $nbrs = count($rs);
        if (!$nbrs) return false;
        $categConfigFunction = CATEG_CONFIG;
        $this->_initExtractVariables();
        $display = $this->asCfg->cfg['display'];
        $select = $this->_asRequest->asSelect;

        if (($display == MIXED)) {
            $this->asCfg->chooseConfig(DEFAULT_SITE, $DEFAULT_SUBSITE, $display);
            if (!isset($this->_groupMixedResults['length'])) {
                $this->_groupMixedResults = $this->_setHeaderGroupResults(MIXED_SITES, $subsite, $display, 'N/A', $select, $nbrs);
            } else $this->_groupMixedResults['length']+= $nbrs;

            $order_array = explode(',', $this->asCfg->cfg['order']);
            $order = $order_array[0];
            for($i=0;$i<$nbrs;$i++){
                $rs[$i]['order'] = $rs[$i][$order];
                $this->_groupMixedResults['results'][] = $rs[$i];
            }
            if ($this->dbgRes) $this->asUtil->dbgRecord($this->_groupMixedResults[$ig], "AjaxSearch - group mixed results");

        }
        else {

            if ($this->asCfg->cfg['category']) {


                $categ = '---';
                $cfunc = function_exists($categConfigFunction);
                $ic = 0;
                for ($i = 0;$i < $nbrs;$i++) {
                    $newCateg = trim($rs[$i]['category']);
                    if ($newCateg != $categ) {
                        $display = UNMIXED;
                        $cfg = NULL;
                        if ($cfunc){
                            $cfg = $categConfigFunction($site,$newCateg);
                            if (isset($cfg['display'])) $display = $cfg['display'];
                        }
                        if ($ic>0) $ctg[$ic-1]['end'] = $i-1;
                        $ctg[] = array('categ' => $newCateg, 'start' => $i, 'end' => 0, 'display' => $display, 'cfg' => $cfg);
                        $ic++;
                    }
                    $categ = $newCateg;
                }

                if ($ic>0) $ctg[$ic-1]['end'] = $i-1;
                $nbc = count($ctg);

                $ig0 = count($this->groupResults);

                for ($i = 0;$i < $nbc;$i++) {
                    $categ = $ctg[$i]['categ'];
                    $categ = ($categ) ? $categ : UNCATEGORIZED;
                    $display = $ctg[$i]['display'];
                    $start = $ctg[$i]['start'];
                    $nbrsg = $ctg[$i]['end'] - $ctg[$i]['start'] + 1;
                    $cfg = $ctg[$i]['cfg'];

                    if ($display == UNMIXED) {
                        $ig = count($this->groupResults);
                        $this->asCfg->addConfigFromCateg($site, $categ, $cfg);
                        $this->asCfg->chooseConfig($site, $categ, $display);
                        $ucfg = $this->asCfg->setAsCall($this->asCfg->getUserConfig());
                        $this->groupResults[$ig] = $this->_setHeaderGroupResults($site, $categ, $display, $ucfg, $select, $nbrsg);
                        $grpresults = array_slice($rs,$start,$nbrsg);
                        $this->groupResults[$ig]['results'] = $this->_sortResultsByRank($this->asCtrl->searchString, $this->asCtrl->advSearch, $grpresults, $nbrsg);
                        $this->nbGroups = $ig + 1;
                        $this->asCfg->restoreConfig($site, DEFAULT_SUBSITE);

                        if ($this->dbgRes) $this->asUtil->dbgRecord($this->groupResults[$ig], "AjaxSearch - group results");

                    }
                    else {
                        if (!isset($this->_groupMixedResults['length'])) {
                            $this->_groupMixedResults = $this->_setHeaderGroupResults(NO_NAME, $subsite, $display, 'N/A', 'N/A', $nbrsg);
                        } else $this->_groupMixedResults['length']+= $nbrsg;
                        $order_array = explode(',', $this->asCfg->cfg['order']);
                        $order = $order_array[0];
                        for($j=0;$j<$nbrsg;$j++) {
                            $grpresults[$j]['order'] = $grpresults[$j][$order];
                            $this->_groupMixedResults['results'][] = $rs[$j];
                        }

                        if ($this->dbgRes) $this->asUtil->dbgRecord($this->groupResults[$ig], "AjaxSearch - group results");
                    }
                }
            }
            else {
                $ig = count($this->groupResults);
                $ucfg = $this->asCfg->setAsCall($this->asCfg->getUserConfig());
                $this->groupResults[$ig] = $this->_setHeaderGroupResults($site, $subsite, $display, $ucfg, $select, $nbrs);
                $row = array();
                if ($this->dbgRes) $this->asUtil->dbgRecord($rs, "AjaxSearch - rs");
                $rs = $this->_sortResultsByRank($this->asCtrl->searchString, $this->asCtrl->advSearch, $rs, $nbrs);
                $this->groupResults[$ig]['results'] = $rs;
                $this->nbGroups = $ig + 1;
            }
            unset($ctg);
            unset($rs);
        }
        $this->nbResults+= $nbrs;
    }
    /*
    *  Initialize the Extract variables
    */
    function _initExtractVariables() {
        list($nbExtr,$lstFlds) = explode(':', $this->asCfg->cfg['extract']);
        $this->extractNb = $nbExtr;
        $this->_extractFields = explode(',', $lstFlds);
        $this->withExtract+= $this->extractNb;
    }
    /*
    * Set the header of group of results
    */
    function _setHeaderGroupResults($site, $subsite, $display, $ucfg, $select, $length) {
        $headerGroupResults = array();
        $headerGroupResults['site'] = $site;
        $headerGroupResults['subsite'] = $subsite;
        $headerGroupResults['display'] = $display;
        $headerGroupResults['offset'] = 0;
        $headerGroupResults['ucfg'] = $ucfg;
        $headerGroupResults['select'] = $select;
        $headerGroupResults['length'] = $length;
        $headerGroupResults['found'] = '';
        return $headerGroupResults;
    }
    /*
    * Sort results by rank value
    */
    function _sortResultsByRank($searchString, $advSearch, $results, $nbrs) {
        $rkFields = array();
        if ($this->asCfg->cfg['rank']) {
            $searchString = strtolower($searchString);

            $rkParam = explode(',', $this->asCfg->cfg['rank']);
            foreach ($rkParam as $rk) {
                $rankParam = explode(':', $rk);
                $name = $rankParam[0];
                $weight = (isset($rankParam[1]) ? $rankParam[1] : 1);
                $rkFields[] = array('name' => $name, 'weight' => $weight);
            }

            for ($i = 0;$i < $nbrs;$i++) {
                $results[$i]['rank'] = 0;
                foreach ($rkFields as $rf) {
                    $results[$i]['rank']+= $this->_getRank($searchString, $advSearch, $results[$i][$rf['name']], $rf['weight']);
                }
            }
            if ($nbrs >1) {

                $i = 0;
                foreach ($results as $key => $row) {
                    $category[$key] = $row['category'];
                    $rank[$key] = $row['rank'];
                    $ascOrder[$key] = $i++;
                }
                array_multisort($category, SORT_ASC, $rank, SORT_DESC, $ascOrder, SORT_ASC, $results);
            }
        }
        return $results;
    }
    /*
    * Get the rank value
    */
    function _getRank($searchString, $advSearch, $field, $weight) {
    $search = array();
        $rank = 0;
        if ($searchString && ($advSearch != NOWORDS)) {
            switch ($advSearch) {
                case EXACTPHRASE:
                    $search[0] = $searchString;
                break;
                case ALLWORDS:
                case ONEWORD:
                    $search = explode(" ", $searchString);
            }
            $field = $this->cleanText($field, $this->asCfg->cfg['stripOutput']);
            if (($this->asCfg->dbCharset == 'utf8') && ($this->asCfg->cfg['mbstring'])) {
                $field = mb_strtolower($field);
                foreach ($search as $srch) $rank+= mb_substr_count($field, $srch);
            } else {
                $field = strtolower($field);
                foreach ($search as $srch) $rank+= substr_count($field, $srch);
            }
            $rank = $rank * $weight;
        }
        return $rank;
    }
    /*
    * Sort noName results by order
    */
    function _sortMixedResults() {
        if (isset($this->_groupMixedResults['results'])) {
            foreach ($this->_groupMixedResults['results'] as $key => $row) {
                $order[$key] = $row['order'];
            }
            array_multisort($order, SORT_ASC, $this->_groupMixedResults['results']);
            $this->groupResults[] = $this->_groupMixedResults;
            $this->nbGroups++;
            if ($this->dbgRes) $this->asUtil->dbgRecord($this->_groupMixedResults['results'], "AjaxSearch - sorted noName results");
        }
    }
    /*
    *  Check the validity of a value separated list of TVs name
    */
    function _validListTvs($listTvs, &$msgErr) {
        global $modx;
        if ($listTvs) {
            $tvs = explode(',', $listTvs);
            $tblName = $modx->getFullTableName('site_tmplvars');
            foreach ($tvs as $tv) {
                $tplRS = $modx->db->select('id', $tblName, 'name="' . $tv . '"');
                if (!$modx->db->getRecordCount($tplRS)) {
                    $msgErr = "<br /><h3>AjaxSearch error: tv $tv not defined - Check your withTvs parameter !</h3><br />";
                    return false;
                }
            }
        }
        return true;
    }
    /*
    * Returns extracts with highlighted searchterms
    */
    function _getExtract($text, $searchString, $advSearch, $highlightClass, &$nbExtr) {
        $finalExtract = '';
        if (($text !== '') && ($searchString !== '') && ($this->extractNb > 0) && ($advSearch !== NOWORDS)) {
            $extracts = array();
            if (($this->asCfg->dbCharset == 'utf8') && ($this->asCfg->cfg['mbstring'])) {
                $text = $this->_html_entity_decode($text, ENT_QUOTES, 'UTF-8');
                $mbStrpos = 'mb_strpos';
                $mbStrlen = 'mb_strlen';
                $mbStrtolower = 'mb_strtolower';
                $mbSubstr = 'mb_substr';
                $mbStrrpos = 'mb_strrpos';
                mb_internal_encoding('UTF-8');
            } else {

                $text = html_entity_decode($text, ENT_QUOTES);
                $mbStrpos = 'strpos';
                $mbStrlen = 'strlen';
                $mbStrtolower = 'strtolower';
                $mbSubstr = 'substr';
                $mbStrrpos = 'strrpos';
            }
            $rank = 0;
            // $lookAhead = '(?![^<]+>)';
            $pcreModifier = $this->asCfg->pcreModifier;
            $textLength = $mbStrlen($text);
            $extractLength = $this->asCfg->cfg['extractLength'];
            $extractLength2 = $extractLength / 2;
            $searchList = $this->asCtrl->getSearchWords($searchString, $advSearch);
            foreach ($searchList as $searchTerm) {
                $rank++;
                $wordLength = $mbStrlen($searchTerm);
                $wordLength2 = $wordLength / 2;
                // $pattern = '/' . preg_quote($searchTerm, '/') . $lookAhead . '/' . $pcreModifier;
                if ($advSearch == EXACTPHRASE) $pattern = '/\b' . preg_quote($searchTerm, '/') . '\b/' . $pcreModifier;
                else $pattern = '/' . preg_quote($searchTerm, '/') . '/' . $pcreModifier;
                $matches = array();
                $nbr = preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

                for($i=0;$i<$nbr && $i<$this->extractNb;$i++) {
                    $wordLeft = $mbStrlen(substr($text,0,$matches[0][$i][1]));
                    $wordRight = $wordLeft + $wordLength - 1;
                    $left = intval($wordLeft - $extractLength2 + $wordLength2);
                    $right = $left + $extractLength - 1;
                    if ($left < 0) $left = 0;
                    if ($right > $textLength) $right = $textLength;
                    $extracts[] = array('word' => $searchTerm,
                                        'wordLeft' => $wordLeft,
                                        'wordRight' => $wordRight,
                                        'rank' => $rank,
                                        'left' => $left,
                                        'right' => $right,
                                        'etcLeft' => $this->asCfg->cfg['extractEllips'],
                                        'etcRight' => $this->asCfg->cfg['extractEllips']
                                        );
                }
            }

            $nbExtr = count($extracts);
            if ($nbExtr > 1) {
                for ($i = 0;$i < $nbExtr;$i++) {
                    $lft[$i] = $extracts[$i]['left'];
                    $rght[$i] = $extracts[$i]['right'];
                }
                array_multisort($lft, SORT_ASC, $rght, SORT_ASC, $extracts);

                for ($i = 0;$i < $nbExtr;$i++) {

                    $begin = $mbSubstr($text, 0, $extracts[$i]['left']);
                    if ($begin != '') $extracts[$i]['left'] = (int)$mbStrrpos($begin, ' ');

                    $end = $mbSubstr($text, $extracts[$i]['right'] + 1, $textLength - $extracts[$i]['right']);
                    if ($end != '') $dr = (int)$mbStrpos($end, ' ');
                    if (is_int($dr)) $extracts[$i]['right']+= $dr + 1;
                }

                if ($extracts[0]['left'] == 0) $extracts[0]['etcLeft'] = '';
                for ($i = 1;$i < $nbExtr;$i++) {

                    if ($extracts[$i]['left'] < $extracts[$i - 1]['wordRight']) {
                        $extracts[$i - 1]['right'] = $extracts[$i - 1]['wordRight'];
                        $extracts[$i]['left'] = $extracts[$i - 1]['right'] + 1;
                        $extracts[$i - 1]['etcRight'] = $extracts[$i]['etcLeft'] = '';
                    } else if ($extracts[$i]['left'] < $extracts[$i - 1]['right']) {
                        $extracts[$i - 1]['right'] = $extracts[$i]['left'];
                        $extracts[$i - 1]['etcRight'] = $extracts[$i]['etcLeft'] = '';
                    }
                }
            }

            for ($i = 0;$i < $nbExtr;$i++) {
                $separation = ($extracts[$i]['etcRight'] != '') ? $this->asCfg->cfg['extractSeparator'] : '';
                $extract = $mbSubstr($text, $extracts[$i]['left'], $extracts[$i]['right'] - $extracts[$i]['left'] + 1);

                if ($this->asCfg->cfg['highlightResult']) {
                    $rank = $extracts[$i]['rank'];
                    $searchTerm = $searchList[$rank - 1];
                    if ($advSearch == EXACTPHRASE) $pattern = '/\b' . preg_quote($searchTerm, '/') . '\b/' . $pcreModifier;
                    else $pattern = '/' . preg_quote($searchTerm, '/') . '/' . $pcreModifier;
                    $subject = '<span class="' . $highlightClass . ' ' . $highlightClass . $rank . '">\0</span>';
                    $extract = preg_replace($pattern, $subject, $extract);
                }
                $finalExtract.= $extracts[$i]['etcLeft'] . $extract . $extracts[$i]['etcRight'] . $separation;
            }
            $finalExtract = $mbSubstr($finalExtract, 0, $mbStrlen($finalExtract) - $mbStrlen($this->asCfg->cfg['extractSeparator']));
        }
        else if ((($text !== '') && ($searchString !== '') && ($this->extractNb > 0) && ($advSearch == NOWORDS)) ||
                   (($text !== '') && ($searchString == '') && ($this->extractNb > 0))) {

            if (($this->asCfg->dbCharset == 'utf8') && ($this->asCfg->cfg['mbstring'])) {
                $mbSubstr = 'mb_substr';
                $mbStrrpos = 'mb_strrpos';
                mb_internal_encoding('UTF-8');
            } else {
                $mbSubstr = 'substr';
                $mbStrrpos = 'strrpos';
            }
            $introLength = $this->asCfg->cfg['extractLength'];
            $intro = $mbSubstr($text,0,$introLength);

            $right = (int) $mbStrrpos($intro, ' ');
            $intro = $mbSubstr($intro,0,$right);
            if ($intro) $intro .= ' ' . $this->asCfg->cfg['extractEllips'];
            $finalExtract = $intro;
        }

        return $finalExtract;
    }
    /*
    *  Get the extract result from each row
    *
    *  @access public
    *  @param row $row mysql row
    *  @return string extract
    */
    function getExtractRow($row) {
        $text = '';
        $nbExtr = 0;
        if ($this->extractNb) {

            foreach ($this->_extractFields as $f) if ($row[$f]) $text.= $row[$f] . ' ';

            $text = $this->cleanText($text, $this->asCfg->cfg['stripOutput']);

            $highlightClass = $this->asOutput->getHClass();
            $text = $this->_getExtract($text, $this->asCtrl->searchString, $this->asCtrl->advSearch, $highlightClass, $nbExtr);
        }
        return $text;
    }
    /*
    * Strip function to clean outputted results
    */
    function cleanText($text, $stripOutput) {
        global $modx;
        if (($stripOutput) && function_exists($stripOutput)) $text = $stripOutput($text);
        else $text = $this->defaultStripOutput($text);

        return $text;
    }
    /*
    *  Return the sign and the list of Ids used for the search (parents & documents)
    */
    function _doBeforeSearchFilter() {
        global $modx;
        $beforeFilter = array();

        list($fsign,$listIds) = explode(':',$this->_pardoc);
        if (($fsign != 'in') && ($fsign != 'not in')) {
            $listIds = $fsign;
            $fsign = 'in';
        }
        $beforeFilter['oper'] = ($fsign == 'in') ? 'in' : 'not in';
        if ($listIds != '') $listIds = $this->_cleanIds($listIds);
        if (strlen($listIds)) {
            switch ($this->_idType) {
                case "parents":
                    $arrayIds = explode(",", $listIds);
                    $listIds = implode(',', $this->_getChildIds($arrayIds, $this->_depth));
                break;
                case "documents":
                break;
            }
        }
        $beforeFilter['listIds'] = $listIds;
        return $beforeFilter;
    }
    /*
    *  Filter the search results
    */
    function _doFilter($results, $searchString, $advSearch) {

        $globalDelimiter = '|';
        $localDelimiter = ',';

        $results = $this->_doFilterTags($results, $searchString, $advSearch);

        $filter = $this->asCfg->cfg['filter'];
        if ($filter) {

            $searchString_array = array();
            if ($advSearch == EXACTPHRASE) $searchString_array[] = $searchString;
            else $searchString_array = explode(' ', $searchString);
            $nbs = count($searchString_array);
            $filter_array = explode('|', $filter);
            $nbf = count($filter_array);
            for ($i = 0;$i < $nbf;$i++) {
                if (preg_match('/#/', $filter_array[$i])) {
                    $terms_array = explode(',', $filter_array[$i]);
                    if ($searchString == EXACTPHRASE) $filter_array[$i] = preg_replace('/#/i', $searchString, $filter_array[$i]);
                    else {
                        $filter_array[$i] = preg_replace('/#/i', $searchString_array[0], $filter_array[$i]);
                        for ($j = 1;$j < $nbs;$j++) {
                            $filter_array[] = $terms_array[0] . ',' . $searchString_array[$j] . ',' . $terms_array[2];
                        }
                    }
                }
            }
            $filter = implode('|', $filter_array);

            $parsedFilters = array();
            $filters = explode($globalDelimiter, $filter);
            if ($filter && count($filters) > 0) {
                foreach ($filters AS $filter) {
                    if (!empty($filter)) {
                        $filterArray = explode($localDelimiter, $filter);
                        $this->_array_key = $filterArray[0];
                        if (substr($filterArray[1], 0, 5) != "@EVAL") {
                            $this->_filterValue = $filterArray[1];
                        } else {
                            $this->_filterValue = eval(substr($filterArray[1], 5));
                        }
                        $this->_filtertype = (isset($filterArray[2])) ? $filterArray[2] : 1;
                        $results = array_filter($results, array($this, "_basicFilter"));
                    }
                }
            }
            $results = array_values($results);
        }
        return $results;
    }
    /*
    *  Do basic comparison filtering
    */
    function _basicFilter($value) {
        $unset = 1;
        switch ($this->_filtertype) {
            case "!=":
            case 1:
                if (!isset($value[$this->_array_key]) || $value[$this->_array_key] != $this->_filterValue) $unset = 0;
                break;
            case "==":
            case 2:
                if ($value[$this->_array_key] == $this->_filterValue) $unset = 0;
                break;
            case "<":
            case 3:
                if ($value[$this->_array_key] < $this->_filterValue) $unset = 0;
                break;
            case ">":
            case 4:
                if ($value[$this->_array_key] > $this->_filterValue) $unset = 0;
                break;
            case "<=":
            case 5:
                if (!($value[$this->_array_key] < $this->_filterValue)) $unset = 0;
                break;
            case ">=":
            case 6:
                if (!($value[$this->_array_key] > $this->_filterValue)) $unset = 0;
                break;
            case "not like":
            case 7: // does not contain the text of the criterion (like)
                if (strpos($value[$this->_array_key], $this->_filterValue) === FALSE) $unset = 0;
                break;
            case "like":
            case 8: // does contain the text of the criterion (not like)
                if (strpos($value[$this->_array_key], $this->_filterValue) !== FALSE) $unset = 0;
                break;
            case 9: // case insenstive version of #7 - exclude records that do not contain the text of the criterion
                if (strpos(strtolower($value[$this->_array_key]), strtolower($this->_filterValue)) === FALSE) $unset = 0;
                break;
            case 10: // case insenstive version of #8 - exclude records that do contain the text of the criterion
                if (strpos(strtolower($value[$this->_array_key]), strtolower($this->_filterValue)) !== FALSE) $unset = 0;
                break;
            case "in":
            case 11: // in list
                $filter_list = explode(':',$this->_filterValue);
                if (in_array($value[$this->_array_key] , $filter_list)) $unset = 0;
                break;
            case "not in":
            case 12: // not in list
                $filter_list = explode(':',$this->_filterValue);
                if (!in_array($value[$this->_array_key] , $filter_list)) $unset = 0;
                break;
            case "custom":
            case 13: // custom
                $custom_list = explode(':',$this->_filterValue);
                $custom = array_shift($custom_list);
                if (function_exists($custom)) {
                    if ($custom($value[$this->_array_key], $custom_list)) $unset = 0;
                }
                break;
            }
            return $unset;
    }
    /*
    *  Get the Ids ready to be processed
    */
    function _getChildIds($Ids, $depth) {
        global $modx;
        $depth = intval($depth);
        $kids = array();
        $docIds = array();
        if ($depth == 0 && $Ids[0] == 0 && count($Ids) == 1) {
            foreach ($modx->documentMap as $null => $document) {
                foreach ($document as $parent => $id) {
                    $kids[] = $id;
                }
            }
            return $kids;
        } else if ($depth == 0) {
            $depth = 10000;


        }
        foreach ($modx->documentMap as $null => $document) {
            foreach ($document as $parent => $id) {
                $kids[$parent][] = $id;
            }
        }
        foreach ($Ids AS $seed) {
            if (!empty($kids[intval($seed) ])) {
                $docIds = array_merge($docIds, $kids[intval($seed) ]);
                unset($kids[intval($seed) ]);
            }
        }
        $depth--;
        while ($depth != 0) {
            $valid = $docIds;
            foreach ($docIds as $child => $id) {
                if (!empty($kids[intval($id) ])) {
                    $docIds = array_merge($docIds, $kids[intval($id) ]);
                    unset($kids[intval($id) ]);
                }
            }
            $depth--;
            if ($valid == $docIds) $depth = 0;
        }
        return array_unique($docIds);
    }
    /*
    *  Clean Ids list of unwanted characters
    */
    function _cleanIds($Ids) {

        $pattern = array('`(,)+`',
        '`^(,)`',
        '`(,)$`'
        );
        $replace = array(',', '', '');
        $Ids = preg_replace($pattern, $replace, $Ids);
        return $Ids;
    }
    /*
    *  Filter the search results when the search terms are found inside HTML or MODx tags
    */
    function _doFilterTags($results, $searchString, $advSearch) {
        $filteredResults = array();
        $nbr = count($results);
        for($i=0;$i<$nbr;$i++) {
            if ($advSearch === NOWORDS) $found = true;
            else {
                $text = implode(' ',$results[$i]);
                $text = $this->defaultStripOutput($text);
                $found = true;
                if ($searchString !== '') {
                    if (($this->asCfg->dbCharset == 'utf8') && ($this->asCfg->cfg['mbstring'])) {
                        $text = $this->_html_entity_decode($text, ENT_QUOTES, 'UTF-8');
                    }
                    else {
                        $text = html_entity_decode($text, ENT_QUOTES);
                    }

                    $searchList = $this->asCtrl->getSearchWords($searchString, $advSearch);
                    $pcreModifier = $this->asCfg->pcreModifier;
                    foreach ($searchList as $searchTerm) {
                        if ($advSearch == EXACTPHRASE) $pattern = '/\b' . preg_quote($searchTerm, '/') . '\b/' . $pcreModifier;
                        else $pattern = '/' . preg_quote($searchTerm, '/') . '/' . $pcreModifier;
                        $matches = array();
                        $found = preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE);
                        if ($found) break;
                    }
                }
            }
            if ($found) $filteredResults[] = $results[$i];
        }
        return $filteredResults;
    }
    /*
    * Get the array of categories found
    */
    function getResultsCateg() {
        $resCategName = array();
        $resCategNb = array();
        for ($i = 0;$i < $this->nbGroups;$i++) {
            $resCategName[$i] = "'" . $this->groupResults[$i]['subsite'] . "'";
            $resCategNb[$i] = $this->groupResults[$i]['length'];
        }
        return array("name" => $resCategName, "nb" => $resCategNb);
    }
    /*
    * Get the array of tags found
    */
    function getResultsTag() {
        $tags = array();
        $resResTag = array();
        $resTagName = array();
        $resTagNb = array();
        $indTag = array();

        for ($i = 0;$i < $this->nbGroups;$i++) {
            $categ = $this->groupResults[$i]['subsite'];
            $nbr = $this->groupResults[$i]['length'];
            $results = $this->groupResults[$i]['results'];
            for ($j = 0;$j < $nbr; $j++) {
                $tags_array = explode(',',$results[$j]['tags']);
                foreach($tags_array as $tagv) {
                    $tv = ($tagv) ? (string) (trim($tagv)) : UNTAGGED;
                    $tags[$tv][]= $i . ',' . $j;
                    $resResTag[$i][$j][] = $tv;
                }
            }
        }
        $itag = 0;
        foreach($tags as $key => $value) {
            $resTagName[] = "'" . $key . "'";
            $resTagNb[] = count($tags[$key]);
            $indTag[$key] = $itag;
            $itag++;
        }
        for ($i = 0;$i < $this->nbGroups;$i++) {
            $nbr = $this->groupResults[$i]['length'];
            for ($j = 0;$j < $nbr; $j++) {
                $nbt = count($resResTag[$i][$j]);
                for ($t = 0;$t < $nbt; $t++) {
                    $resResTag[$i][$j][$t] = $indTag[$resResTag[$i][$j][$t]];
                }
            }
        }
        return array("name" => $resTagName, "nb" => $resTagNb, "restag" => $resResTag);
    }
    /*
    * Default ouput strip function
    */
    function defaultStripOutput($text) {
        global $modx;

        if ($text !== '') {
            // $text = $modx->parseDocumentSource($text); // parse document

            $text = $this->stripLineBreaking($text);

            $text = $modx->stripTags($text);

            $text = $this->stripJscripts($text);

            $text = $this->stripHtml($text);
        }
        return $text;
    }
    /*
    *  stripLineBreaking : replace line breaking tags with whitespace
    */
    function stripLineBreaking($text) {

        $text = preg_replace("'<(br[^/>]*?/|hr[^/>]*?/|/(div|h[1-6]|li|p|td))>'si", ' ', $text);
        return $text;
    }
    /*
    *  stripTags : Remove MODx sensitive tags
    */
    function stripTags($text) {

        $modRegExArray[] = '~\[\[(.*?)\]\]~s';
        $modRegExArray[] = '~\[\!(.*?)\!\]~s';
        $modRegExArray[] = '#\[\~(.*?)\~\]#s';
        $modRegExArray[] = '~\[\((.*?)\)\]~s';
        $modRegExArray[] = '~{{(.*?)}}~s';
        $modRegExArray[] = '~\[\*(.*?)\*\]~s';
        $modRegExArray[] = '~\[\+(.*?)\+\]~s';

        foreach ($modRegExArray as $mReg) $text = preg_replace($mReg, '', $text);
        return $text;
    }
    /*
    *  stripJscript : Remove jscript
    */
    function stripJscripts($text) {

        $text = preg_replace("'<script[^>]*>.*?</script>'si", "", $text);
        $text = preg_replace('/{.+?}/', '', $text);
        return $text;
    }
    /*
    *  stripHtml : Remove HTML sensitive tags
    */
    function stripHtml($text) {
        return strip_tags($text);
    }
    /*
    *  stripHtmlExceptImage : Remove HTML sensitive tags except image tag
    */
    function stripHtmlExceptImage($text) {
        $text = strip_tags($text, '<img>');
        return $text;
    }
    function getSearchContext() {
        // return the search context
        $searchContext['main'] = $this->_asRequest->scMain;
        $searchContext['joined'] = $this->_asRequest->scJoined;
        $searchContext['tvs'] = $this->_asRequest->scTvs;
        $searchContext['category'] = $this->_asRequest->scCategory;
        $searchContext['tags'] = $this->_asRequest->scTags;
        return $searchContext;
    }
    function getWithContent() {
        // return the withContent boolean value
        return $this->_asRequest->withContent;
    }
    function _html_entity_decode($text, $quote_style = ENT_COMPAT, $charset) {

        if (version_compare(PHP_VERSION, '5.0.0', '>=')) $text = html_entity_decode($text, ENT_QUOTES, $charset);
        else $text = $this->_html_entity_decode_php4($text);
        return $text;
    }

    // Author      : Nicola Asuni
    // License     : GNU LGPL (http://www.gnu.org/copyleft/lesser.html)
    //
    // Description : This is a PHP4 function that redefine the
    //               standard html_entity_decode function to support
    //               UTF-8 encoding.

    /**
     * Reverse function for htmlentities.
     * Convert entities in UTF-8.
     */
    function _html_entity_decode_php4($text_to_convert) {
        $htmlentities_table = array (
            "&Aacute;" => "".chr(195).chr(129)."",
            "&aacute;" => "".chr(195).chr(161)."",
            "&Acirc;" => "".chr(195).chr(130)."",
            "&acirc;" => "".chr(195).chr(162)."",
            "&acute;" => "".chr(194).chr(180)."",
            "&AElig;" => "".chr(195).chr(134)."",
            "&aelig;" => "".chr(195).chr(166)."",
            "&Agrave;" => "".chr(195).chr(128)."",
            "&agrave;" => "".chr(195).chr(160)."",
            "&alefsym;" => "".chr(226).chr(132).chr(181)."",
            "&Alpha;" => "".chr(206).chr(145)."",
            "&alpha;" => "".chr(206).chr(177)."",
            "&amp;" => "".chr(38)."",
            "&and;" => "".chr(226).chr(136).chr(167)."",
            "&ang;" => "".chr(226).chr(136).chr(160)."",
            "&Aring;" => "".chr(195).chr(133)."",
            "&aring;" => "".chr(195).chr(165)."",
            "&asymp;" => "".chr(226).chr(137).chr(136)."",
            "&Atilde;" => "".chr(195).chr(131)."",
            "&atilde;" => "".chr(195).chr(163)."",
            "&Auml;" => "".chr(195).chr(132)."",
            "&auml;" => "".chr(195).chr(164)."",
            "&bdquo;" => "".chr(226).chr(128).chr(158)."",
            "&Beta;" => "".chr(206).chr(146)."",
            "&beta;" => "".chr(206).chr(178)."",
            "&brvbar;" => "".chr(194).chr(166)."",
            "&bull;" => "".chr(226).chr(128).chr(162)."",
            "&cap;" => "".chr(226).chr(136).chr(169)."",
            "&Ccedil;" => "".chr(195).chr(135)."",
            "&ccedil;" => "".chr(195).chr(167)."",
            "&cedil;" => "".chr(194).chr(184)."",
            "&cent;" => "".chr(194).chr(162)."",
            "&Chi;" => "".chr(206).chr(167)."",
            "&chi;" => "".chr(207).chr(135)."",
            "&circ;" => "".chr(203).chr(134)."",
            "&clubs;" => "".chr(226).chr(153).chr(163)."",
            "&cong;" => "".chr(226).chr(137).chr(133)."",
            "&copy;" => "".chr(194).chr(169)."",
            "&crarr;" => "".chr(226).chr(134).chr(181)."",
            "&cup;" => "".chr(226).chr(136).chr(170)."",
            "&curren;" => "".chr(194).chr(164)."",
            "&dagger;" => "".chr(226).chr(128).chr(160)."",
            "&Dagger;" => "".chr(226).chr(128).chr(161)."",
            "&darr;" => "".chr(226).chr(134).chr(147)."",
            "&dArr;" => "".chr(226).chr(135).chr(147)."",
            "&deg;" => "".chr(194).chr(176)."",
            "&Delta;" => "".chr(206).chr(148)."",
            "&delta;" => "".chr(206).chr(180)."",
            "&diams;" => "".chr(226).chr(153).chr(166)."",
            "&divide;" => "".chr(195).chr(183)."",
            "&Eacute;" => "".chr(195).chr(137)."",
            "&eacute;" => "".chr(195).chr(169)."",
            "&Ecirc;" => "".chr(195).chr(138)."",
            "&ecirc;" => "".chr(195).chr(170)."",
            "&Egrave;" => "".chr(195).chr(136)."",
            "&egrave;" => "".chr(195).chr(168)."",
            "&empty;" => "".chr(226).chr(136).chr(133)."",
            "&emsp;" => "".chr(226).chr(128).chr(131)."",
            "&ensp;" => "".chr(226).chr(128).chr(130)."",
            "&Epsilon;" => "".chr(206).chr(149)."",
            "&epsilon;" => "".chr(206).chr(181)."",
            "&equiv;" => "".chr(226).chr(137).chr(161)."",
            "&Eta;" => "".chr(206).chr(151)."",
            "&eta;" => "".chr(206).chr(183)."",
            "&ETH;" => "".chr(195).chr(144)."",
            "&eth;" => "".chr(195).chr(176)."",
            "&Euml;" => "".chr(195).chr(139)."",
            "&euml;" => "".chr(195).chr(171)."",
            "&euro;" => "".chr(226).chr(130).chr(172)."",
            "&exist;" => "".chr(226).chr(136).chr(131)."",
            "&fnof;" => "".chr(198).chr(146)."",
            "&forall;" => "".chr(226).chr(136).chr(128)."",
            "&frac12;" => "".chr(194).chr(189)."",
            "&frac14;" => "".chr(194).chr(188)."",
            "&frac34;" => "".chr(194).chr(190)."",
            "&frasl;" => "".chr(226).chr(129).chr(132)."",
            "&Gamma;" => "".chr(206).chr(147)."",
            "&gamma;" => "".chr(206).chr(179)."",
            "&ge;" => "".chr(226).chr(137).chr(165)."",
            "&harr;" => "".chr(226).chr(134).chr(148)."",
            "&hArr;" => "".chr(226).chr(135).chr(148)."",
            "&hearts;" => "".chr(226).chr(153).chr(165)."",
            "&hellip;" => "".chr(226).chr(128).chr(166)."",
            "&Iacute;" => "".chr(195).chr(141)."",
            "&iacute;" => "".chr(195).chr(173)."",
            "&Icirc;" => "".chr(195).chr(142)."",
            "&icirc;" => "".chr(195).chr(174)."",
            "&iexcl;" => "".chr(194).chr(161)."",
            "&Igrave;" => "".chr(195).chr(140)."",
            "&igrave;" => "".chr(195).chr(172)."",
            "&image;" => "".chr(226).chr(132).chr(145)."",
            "&infin;" => "".chr(226).chr(136).chr(158)."",
            "&int;" => "".chr(226).chr(136).chr(171)."",
            "&Iota;" => "".chr(206).chr(153)."",
            "&iota;" => "".chr(206).chr(185)."",
            "&iquest;" => "".chr(194).chr(191)."",
            "&isin;" => "".chr(226).chr(136).chr(136)."",
            "&Iuml;" => "".chr(195).chr(143)."",
            "&iuml;" => "".chr(195).chr(175)."",
            "&Kappa;" => "".chr(206).chr(154)."",
            "&kappa;" => "".chr(206).chr(186)."",
            "&Lambda;" => "".chr(206).chr(155)."",
            "&lambda;" => "".chr(206).chr(187)."",
            "&lang;" => "".chr(226).chr(140).chr(169)."",
            "&laquo;" => "".chr(194).chr(171)."",
            "&larr;" => "".chr(226).chr(134).chr(144)."",
            "&lArr;" => "".chr(226).chr(135).chr(144)."",
            "&lceil;" => "".chr(226).chr(140).chr(136)."",
            "&ldquo;" => "".chr(226).chr(128).chr(156)."",
            "&le;" => "".chr(226).chr(137).chr(164)."",
            "&lfloor;" => "".chr(226).chr(140).chr(138)."",
            "&lowast;" => "".chr(226).chr(136).chr(151)."",
            "&loz;" => "".chr(226).chr(151).chr(138)."",
            "&lrm;" => "".chr(226).chr(128).chr(142)."",
            "&lsaquo;" => "".chr(226).chr(128).chr(185)."",
            "&lsquo;" => "".chr(226).chr(128).chr(152)."",
            "&macr;" => "".chr(194).chr(175)."",
            "&mdash;" => "".chr(226).chr(128).chr(148)."",
            "&micro;" => "".chr(194).chr(181)."",
            "&middot;" => "".chr(194).chr(183)."",
            "&minus;" => "".chr(226).chr(136).chr(146)."",
            "&Mu;" => "".chr(206).chr(156)."",
            "&mu;" => "".chr(206).chr(188)."",
            "&nabla;" => "".chr(226).chr(136).chr(135)."",
            "&nbsp;" => "".chr(194).chr(160)."",
            "&ndash;" => "".chr(226).chr(128).chr(147)."",
            "&ne;" => "".chr(226).chr(137).chr(160)."",
            "&ni;" => "".chr(226).chr(136).chr(139)."",
            "&not;" => "".chr(194).chr(172)."",
            "&notin;" => "".chr(226).chr(136).chr(137)."",
            "&nsub;" => "".chr(226).chr(138).chr(132)."",
            "&Ntilde;" => "".chr(195).chr(145)."",
            "&ntilde;" => "".chr(195).chr(177)."",
            "&Nu;" => "".chr(206).chr(157)."",
            "&nu;" => "".chr(206).chr(189)."",
            "&Oacute;" => "".chr(195).chr(147)."",
            "&oacute;" => "".chr(195).chr(179)."",
            "&Ocirc;" => "".chr(195).chr(148)."",
            "&ocirc;" => "".chr(195).chr(180)."",
            "&OElig;" => "".chr(197).chr(146)."",
            "&oelig;" => "".chr(197).chr(147)."",
            "&Ograve;" => "".chr(195).chr(146)."",
            "&ograve;" => "".chr(195).chr(178)."",
            "&oline;" => "".chr(226).chr(128).chr(190)."",
            "&Omega;" => "".chr(206).chr(169)."",
            "&omega;" => "".chr(207).chr(137)."",
            "&Omicron;" => "".chr(206).chr(159)."",
            "&omicron;" => "".chr(206).chr(191)."",
            "&oplus;" => "".chr(226).chr(138).chr(149)."",
            "&or;" => "".chr(226).chr(136).chr(168)."",
            "&ordf;" => "".chr(194).chr(170)."",
            "&ordm;" => "".chr(194).chr(186)."",
            "&Oslash;" => "".chr(195).chr(152)."",
            "&oslash;" => "".chr(195).chr(184)."",
            "&Otilde;" => "".chr(195).chr(149)."",
            "&otilde;" => "".chr(195).chr(181)."",
            "&otimes;" => "".chr(226).chr(138).chr(151)."",
            "&Ouml;" => "".chr(195).chr(150)."",
            "&ouml;" => "".chr(195).chr(182)."",
            "&para;" => "".chr(194).chr(182)."",
            "&part;" => "".chr(226).chr(136).chr(130)."",
            "&permil;" => "".chr(226).chr(128).chr(176)."",
            "&perp;" => "".chr(226).chr(138).chr(165)."",
            "&Phi;" => "".chr(206).chr(166)."",
            "&phi;" => "".chr(207).chr(134)."",
            "&Pi;" => "".chr(206).chr(160)."",
            "&pi;" => "".chr(207).chr(128)."",
            "&piv;" => "".chr(207).chr(150)."",
            "&plusmn;" => "".chr(194).chr(177)."",
            "&pound;" => "".chr(194).chr(163)."",
            "&prime;" => "".chr(226).chr(128).chr(178)."",
            "&Prime;" => "".chr(226).chr(128).chr(179)."",
            "&prod;" => "".chr(226).chr(136).chr(143)."",
            "&prop;" => "".chr(226).chr(136).chr(157)."",
            "&Psi;" => "".chr(206).chr(168)."",
            "&psi;" => "".chr(207).chr(136)."",
            "&radic;" => "".chr(226).chr(136).chr(154)."",
            "&rang;" => "".chr(226).chr(140).chr(170)."",
            "&raquo;" => "".chr(194).chr(187)."",
            "&rarr;" => "".chr(226).chr(134).chr(146)."",
            "&rArr;" => "".chr(226).chr(135).chr(146)."",
            "&rceil;" => "".chr(226).chr(140).chr(137)."",
            "&rdquo;" => "".chr(226).chr(128).chr(157)."",
            "&real;" => "".chr(226).chr(132).chr(156)."",
            "&reg;" => "".chr(194).chr(174)."",
            "&rfloor;" => "".chr(226).chr(140).chr(139)."",
            "&Rho;" => "".chr(206).chr(161)."",
            "&rho;" => "".chr(207).chr(129)."",
            "&rlm;" => "".chr(226).chr(128).chr(143)."",
            "&rsaquo;" => "".chr(226).chr(128).chr(186)."",
            "&rsquo;" => "".chr(226).chr(128).chr(153)."",
            "&sbquo;" => "".chr(226).chr(128).chr(154)."",
            "&Scaron;" => "".chr(197).chr(160)."",
            "&scaron;" => "".chr(197).chr(161)."",
            "&sdot;" => "".chr(226).chr(139).chr(133)."",
            "&sect;" => "".chr(194).chr(167)."",
            "&shy;" => "".chr(194).chr(173)."",
            "&Sigma;" => "".chr(206).chr(163)."",
            "&sigma;" => "".chr(207).chr(131)."",
            "&sigmaf;" => "".chr(207).chr(130)."",
            "&sim;" => "".chr(226).chr(136).chr(188)."",
            "&spades;" => "".chr(226).chr(153).chr(160)."",
            "&sub;" => "".chr(226).chr(138).chr(130)."",
            "&sube;" => "".chr(226).chr(138).chr(134)."",
            "&sum;" => "".chr(226).chr(136).chr(145)."",
            "&sup1;" => "".chr(194).chr(185)."",
            "&sup2;" => "".chr(194).chr(178)."",
            "&sup3;" => "".chr(194).chr(179)."",
            "&sup;" => "".chr(226).chr(138).chr(131)."",
            "&supe;" => "".chr(226).chr(138).chr(135)."",
            "&szlig;" => "".chr(195).chr(159)."",
            "&Tau;" => "".chr(206).chr(164)."",
            "&tau;" => "".chr(207).chr(132)."",
            "&there4;" => "".chr(226).chr(136).chr(180)."",
            "&Theta;" => "".chr(206).chr(152)."",
            "&theta;" => "".chr(206).chr(184)."",
            "&thetasym;" => "".chr(207).chr(145)."",
            "&thinsp;" => "".chr(226).chr(128).chr(137)."",
            "&THORN;" => "".chr(195).chr(158)."",
            "&thorn;" => "".chr(195).chr(190)."",
            "&tilde;" => "".chr(203).chr(156)."",
            "&times;" => "".chr(195).chr(151)."",
            "&trade;" => "".chr(226).chr(132).chr(162)."",
            "&Uacute;" => "".chr(195).chr(154)."",
            "&uacute;" => "".chr(195).chr(186)."",
            "&uarr;" => "".chr(226).chr(134).chr(145)."",
            "&uArr;" => "".chr(226).chr(135).chr(145)."",
            "&Ucirc;" => "".chr(195).chr(155)."",
            "&ucirc;" => "".chr(195).chr(187)."",
            "&Ugrave;" => "".chr(195).chr(153)."",
            "&ugrave;" => "".chr(195).chr(185)."",
            "&uml;" => "".chr(194).chr(168)."",
            "&upsih;" => "".chr(207).chr(146)."",
            "&Upsilon;" => "".chr(206).chr(165)."",
            "&upsilon;" => "".chr(207).chr(133)."",
            "&Uuml;" => "".chr(195).chr(156)."",
            "&uuml;" => "".chr(195).chr(188)."",
            "&weierp;" => "".chr(226).chr(132).chr(152)."",
            "&Xi;" => "".chr(206).chr(158)."",
            "&xi;" => "".chr(206).chr(190)."",
            "&Yacute;" => "".chr(195).chr(157)."",
            "&yacute;" => "".chr(195).chr(189)."",
            "&yen;" => "".chr(194).chr(165)."",
            "&yuml;" => "".chr(195).chr(191)."",
            "&Yuml;" => "".chr(197).chr(184)."",
            "&Zeta;" => "".chr(206).chr(150)."",
            "&zeta;" => "".chr(206).chr(182)."",
            "&zwj;" => "".chr(226).chr(128).chr(141)."",
            "&zwnj;" => "".chr(226).chr(128).chr(140)."",
            "&gt;" => ">",
            "&lt;" => "<"
        );
        $return_text = strtr($text_to_convert, $htmlentities_table);
        $return_text = preg_replace('~&#x([0-9a-f]+);~ei', 'code_to_utf8(hexdec("\\1"))', $return_text);
        $return_text = preg_replace('~&#([0-9]+);~e', 'code_to_utf8(\\1)', $return_text);
        return $return_text;
    }
}

/**
* Returns the UTF-8 string corresponding to unicode value.
* @param $num unicode value to convert.
* @return string converted
*/
function code_to_utf8($num) {
    if ($num <= 0x7F) {
        return chr($num);
    } elseif ($num <= 0x7FF) {
        return chr(($num >> 0x06) + 0xC0).chr(($num & 0x3F) + 128);
    } elseif ($num <= 0xFFFF) {
        return chr(($num >> 0x0C) + 0xE0).chr((($num >> 0x06) & 0x3F) + 0x80).chr(($num & 0x3F) + 0x80);
    } elseif ($num <= 0x1FFFFF) {
        return chr(($num >> 0x12) + 0xF0).chr((($num >> 0x0C) & 0x3F) + 0x80).chr((($num >> 0x06) & 0x3F) + 0x80).chr(($num & 0x3F) + 0x80);
    }
    return ' '; // default value
}

?>