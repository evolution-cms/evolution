<?php
/* -----------------------------------------------------------------------------
* Snippet: AjaxSearch
* -----------------------------------------------------------------------------
* @package  AjaxSearchOutput
*
* @author       Coroico - www.evo.wangba.fr
* @version      1.9.3
* @date         26/09/2012
*
* Purpose:
*    The AjaxSearchOutput class contains all functions and data used to display output
*
*/

define('HIGHLIGHT_CLASS', 'ajaxSearch_highlight');

define('PREFIX_AJAX_RESULT_CLASS', 'AS_ajax_result');

define('PREFIX_RESULT_CLASS', 'ajaxSearch_result');

define('INTROFAILURE_CLASS', 'AS_ajax_resultsIntroFailure');

define('RESULTS_DIV', '<div id="%sajaxSearch_output" class="init"></div>');

define('AJAXSEARCH_JSDIR','js/ajaxSearch');

define('ASPHX','||-AJAXSEARCH-||');

define('NB_MORE_RESULTS',10);

class AjaxSearchOutput {

    // public variables
    var $asCfg = null;
    var $asCtrl = null;
    var $asInput = null;
    var $asResults = null;
    var $asUtil = null;
    var $asLog = null;

    var $dbg = false;
    var $dbgTpl = false;
    var $dbgRes = false;
    var $log = false;

    var $output;
    // class variables
    var $asClass = array();
    // chunkie variables
    var $chkResults;
    var $varResults = array();
    var $chkGrpResult;
    var $varGrpResult = array();
    var $tplGrpRes;
    var $chkResult;
    var $varResult = array();
    var $tplRes;
    var $scMain = array();
    var $scJoined = array();
    var $scTvs = array();
    var $logIds = array();

    // private variables
    var $_needsConvert;

    function AjaxSearchOutput() {
    }
    function init(&$asCfg, &$asCtrl, &$asInput, &$asResults, &$asUtil, &$asLog, $log) {
        // initialize the output instance
        $this->asCfg =& $asCfg;
        $this->asCtrl =& $asCtrl;
        $this->asInput =& $asInput;
        $this->asResults =& $asResults;
        $this->asUtil =& $asUtil;
        $this->dbg = $asUtil->dbg;
        $this->dbgTpl = $asUtil->dbgTpl;
        $this->dbgRes = $asUtil->dbgRes;
        $this->asLog =& $asLog;
        $this->log = $log;
    }
    /*
    * display : display final output
    */
    function display($validSearch, &$msgErr) {
        global $modx;

        $this->_checkParams();
        if ($this->asCfg->isAjax) {
            $jsonPairs = array();
            $output = $this->_displayResults($validSearch, $msgErr, $nbResultsInfos);
            $output .= $this->_displayComment();
            $jsonPairs[] = $this->_getJsonPair('res',$output);
            $jsonPairs[] = $this->_getJsonPair('resnb',$nbResultsInfos);
            if ($validSearch) $this->_updateAsf($jsonPairs);
            $output = $this->_getJson($jsonPairs);
        }
        else {
            $outputInputForm = $this->asInput->inputForm;   //
            $outputAsfForm = $this->asInput->asfForm;
            if ($this->asCfg->cfg['ajaxSearch']) {
                $outputResults = $this->_initDisplayResults();
            }
            else {
                $outputResults = $this->_displayResults($validSearch, $msgErr, $nbResultsInfos); // non ajax results output
                $outputResults .= $this->_displayComment();
            }
            if (!$this->asCfg->cfg['output']) {
                $output = $outputInputForm;
                $output .= $outputAsfForm;
                $output .= $outputResults;
            }
            else {
                $output = '';
                $modx->setPlaceholder("as.inputForm", $outputInputForm);
                $modx->setPlaceholder("as.asfForm", $outputAsfForm);
                $modx->setPlaceholder("as.results", $outputResults);
            }
        }
        return $output;
    }
    /*
    * checkParams : Check output field params
    */
    function _checkParams() {
        if (($this->asCfg->cfg['pagingType'] < 0) || ($this->asCfg->cfg['pagingType'] > 2)) $this->asCfg->cfg['pagingType'] = 1;
    }
    /*
    * Init display results
    */
    function _initDisplayResults(){
        $prefix = ($this->asCfg->cfg['asId']) ? $this->asCfg->cfg['asId'] . "_" : '';
        $outputResults = sprintf(RESULTS_DIV, $prefix);
        return $outputResults;
    }
    /*
    * displayResults : display results
    */
    function _displayResults($validSearch, &$msgErr, &$nbResultsInfos) {
        $this->_initCommonChunks();
        $outputResults = '';
        $nbFoundResults = 0;
        $nbDisplayedResults = 0;
        $logIds = array();
        $asCall = $this->_getAsCall($this->asCfg->setAsCall($this->asCfg->getUserConfig()));
        $select = $this->asResults->_asRequest->asSelect;
        if ($this->asCfg->cfg['showResults']) {
            if ($validSearch) {
                if (!$this->asCfg->isAjax) $this->_setOffset();
                $listGrpResults = '';
                $ig = 0;
                if ($this->asResults->nbResults > 0) {
                    $this->_getSearchContext();
                    $nbDisplayedResults = 0;
                    for ($ig = 0;$ig < $this->asResults->nbGroups;$ig++) {

                       $found = '';
                        $site = $this->asResults->groupResults[$ig]['site'];
                        $subsite = $this->asResults->groupResults[$ig]['subsite'];
                        $display = $this->asResults->groupResults[$ig]['display'];
                        $offset = intval($this->asResults->groupResults[$ig]['offset']);
                        $nbrs = intval($this->asResults->groupResults[$ig]['length']);
                        $nbFoundResults += $nbrs;
                        // nb results displayed
                        if ($this->asCfg->isAjax) $nbMax = ($this->asCfg->cfg['ajaxMax'] > 0) ? $this->asCfg->cfg['ajaxMax'] : $nbrs;
                        else $nbMax = ($this->asCfg->cfg['grabMax'] > 0) ? $this->asCfg->cfg['grabMax'] : $nbrs;

                        $searchResults = array_slice($this->asResults->groupResults[$ig]['results'], $offset, $nbMax);
                        $nbDisplayedResults += count($searchResults);

                        $this->asCfg->chooseConfig($site, $subsite, $display);
                        $this->_initDisplayVariables();

                        $listGrpResults .= $this->_displayGrpResult($ig, $site, $subsite, $display, $nbrs, $searchResults, $offset, $nbMax);

                        $lid = $this->_setSuccessfullSearches($ig);
                        if ($lid) $logIds[] = $lid;
                    }

                    $this->asCfg->restoreConfig(DEFAULT_SITE, DEFAULT_SUBSITE);

                    if ($nbFoundResults) {

                        if ($this->asCtrl->searchString && ($this->asCtrl->advSearch != NOWORDS)) {
                            $resultsFoundText = ($nbFoundResults > 1) ? $this->asCfg->lang['as_resultsFoundTextMultiple'] : $this->asCfg->lang['as_resultsFoundTextSingle'];
                            if ($this->asResults->withExtract) {
                                $searchList = $this->asCtrl->getSearchWords($this->asCtrl->searchString, $this->asCtrl->advSearch);
                                $hits = 1;
                                $searchwords = '';
                                foreach ($searchList as $words) {
                                    $searchwords.= '<span class="ajaxSearch_highlight ajaxSearch_highlight' . $hits . '">' . $words . '</span>&nbsp;';
                                    $hits++;
                                }
                                $searchwords = substr($searchwords, 0, strlen($searchwords) - 6);
                                $this->varResults['resultsFoundText'] = sprintf($resultsFoundText, $nbFoundResults, $searchwords);
                            } else {
                                $this->varResults['resultsFoundText'] = sprintf($resultsFoundText, $nbFoundResults, $this->asCtrl->searchString);
                            }
                        }
                        else {
                            $resultsFoundText = ($nbFoundResults > 1) ? $this->asCfg->lang['as_resultsTextMultiple'] : $this->asCfg->lang['as_resultsTextSingle'];
                            $this->varResults['resultsFoundText'] = sprintf($resultsFoundText, $nbFoundResults);
                        }

                        $resultsDisplayedText = $this->asCfg->lang['as_resultsDisplayed'];
                        $this->varResults['resultsDisplayedText'] = sprintf($resultsDisplayedText, $nbDisplayedResults);


                        $this->varResults['noResults'] = 0;
                        $this->varResults['listGrpResults'] = ASPHX;

                        if ($this->asCfg->isAjax) $this->_setMoreResultsLink($this->asResults->nbResults);
                    }
                    else {
                        $this->varResults['noResults'] = 1;
                        $this->varResults['noResultClass'] = INTROFAILURE_CLASS;
                        $this->varResults['noResultText'] = $this->asCfg->lang['as_resultsIntroFailure'];
                        $this->_setFailedSearches($asCall,$select);
                    }
                } else {
                    $this->varResults['noResults'] = 1;
                    $this->varResults['noResultClass'] = INTROFAILURE_CLASS;
                    $this->varResults['noResultText'] = $this->asCfg->lang['as_resultsIntroFailure'];
                    $this->_setFailedSearches($asCall,$select);
                }
            }
            else {
                $this->varResults['showCmt'] = 0;
                $this->varResults['noResults'] = 1;
                $this->varResults['noResultClass'] = 'AS_ajax_resultsIntroFailure';
                $this->varResults['noResultText'] = $msgErr;
            }
            $this->chkResults->AddVar("as", $this->varResults);
            $outputResults = $this->chkResults->Render();
            $outputResults = str_replace(ASPHX, $listGrpResults, $outputResults);
            unset($this->varResults);
            unset($this->chkResults);

            // UTF-8 conversion is required if mysql character set is different of 'utf8'
            if ($this->_needsConvert) $outputResults = mb_convert_encoding($outputResults,"UTF-8",$this->asCfg->pgCharset);

            $this->logIds = $logIds;
        }

        $nbResultsInfos = $nbFoundResults . ',' . $nbDisplayedResults;
        return $outputResults;
    }
    /*
    * Display a group of results
    */
    function _displayGrpResult($ig, $site, $subsite, $display, $nbrs, $searchResults, $offset, $nbMax) {
        $found = array();
        $this->chkGrpResult = new AsPhxParser($this->tplGrpRes);
        $this->varGrpResult = array();

        $this->varGrpResult['headerGrpResult'] = $this->_displayHeaderGrpResult($site, $subsite, $display, $nbrs, $searchResults, $offset, $nbMax);

        $this->varGrpResult['grpResultsDef'] = 1;
        $prefix = ($this->asCfg->cfg['asId']) ? $this->asCfg->cfg['asId'] . "_" : '';
        $this->varGrpResult['grpResultId'] = $prefix . 'grpResult_' . $this->_getCleanCssId($subsite);

        $listResults = $this->_displayListResults($site, $subsite, $display, $nbrs, $searchResults, $found, $offset);
        $this->varGrpResult['listResults'] = ASPHX;

        $this->varGrpResult['footerGrpResult'] = $this->_displayFooterGrpResult($ig, $nbrs,  $offset, $nbMax);

        $this->asResults->groupResults[$ig]['found'] = implode(' ',$found);

        $this->chkGrpResult->AddVar("as", $this->varGrpResult);
        $grpResult = $this->chkGrpResult->Render();
        $grpResult = str_replace(ASPHX, $listResults, $grpResult);
        unset($this->varGrpResult);
        unset($this->chkGrpResult);

        return $grpResult;
    }
    /*
    * Display the header of a group of results
    */
    function _displayHeaderGrpResult($site, $subsite, $display, $nbrs, $searchResults, $offset, $nbMax) {
        $varHeader = array();

        $varHeader['grpResultsDef'] = 0;
        $label = (isset($this->asCfg->scfg[$site][$subsite]['grpLabel'])) ? $this->asCfg->scfg[$site][$subsite]['grpLabel'] : $subsite;
        $varHeader['grpResultNameShow'] = ($label != DEFAULT_SUBSITE) ? 1 : 0;
        $varHeader['grpResultName'] = $label;
        $grpResultsDisplayedText = $this->asCfg->lang['as_grpResultsDisplayedText'];
        $grpResultNb = $nbrs;
        $grpResultStart = $offset + 1;
        $grpResultEnd = ($offset + $nbMax > $nbrs) ? $nbrs : $offset + $nbMax;
        $varHeader['grpResultsDisplayedText'] = sprintf($grpResultsDisplayedText, $grpResultStart, $grpResultEnd, $grpResultNb);

        $this->chkGrpResult->AddVar("as", $varHeader);
        $header = $this->chkGrpResult->Render();
        $this->chkGrpResult->CleanVars();
        return $header;
    }
    /*
    * Display the list of results
    */
    function _displayListResults($site, $subsite, $display, $nbrs, $searchResults, & $found, $offset) {
        $nb = count($searchResults);
        $listResults = '';

        for ($i = 0;$i < $nb;$i++) {
            $this->varResult = array();

            $found[] = $this->_setResultDisplayed($searchResults[$i]);

            $this->_setResultLink($searchResults[$i]);

            $this->_setResultExtract($searchResults[$i]);

            $this->_setResultBreadcrumbs($searchResults[$i]);

            $this->_setResultNumber($offset + $i + 1);


            $this->chkResult->AddVar("as", $this->varResult);
            $listResults .= $this->chkResult->Render();
            unset($this->varResult);
            $this->chkResult->CleanVars();
        }
        return $listResults;
    }
    /*
    * Display the footer of group results (paging)
    */
    function _displayFooterGrpResult($ig, $nbrs, $offset, $nbMax) {
        global $modx;
        $footer = '';
        $showPagingAlways = (int)$this->asCfg->cfg['showPagingAlways'];
        $pagingType = $this->asCfg->cfg['pagingType'];
        if ($nbMax > 0) {
            $numResultPages = ceil($nbrs / $nbMax);
            $maxOffset = ($numResultPages - 1) * $nbMax;
            $offset = ($offset > $maxOffset) ? $maxOffset : $offset;
            $offset = ($offset < 0) ? 0 : $offset;
            if (($pagingType == 0) && (!$this->asCfg->isAjax)) {

                $tplPaging = $this->asCfg->cfg['tplPaging0'];
                if ($tplPaging == '') $tplPaging = "@FILE:" . AS_SPATH . 'templates/paging0.tpl.html';
                $chkPaging = new AsPhxParser($tplPaging);
                if ($this->dbgTpl) $this->asUtil->dbgRecord($chkPaging->getTemplate($tplPaging), "tplPaging template " . $tplPaging);
                $resultPagingText = (($nbrs > $nbMax) || $showPagingAlways) ? $this->asCfg->lang['as_paginationTextMultiplePages'] : $this->asCfg->lang['as_paginationTextSinglePage'];
                $resultPageLinkNumber = 1;
                $resultPageLinks = '';
                $url = $this->_getParamsUrl();
                $otherOffset = $this->_getOtherOffset($ig);
                for ($nrp = 0;$nrp < $nbrs && (($nbrs > $nbMax) || $showPagingAlways);$nrp+= $nbMax) {
                    $varLink = array();
                    if ($offset == ($resultPageLinkNumber - 1) * $nbMax) {
                        $varLink['tpl'] = 'pagingLinksCurrent';
                    } else {
                        $varLink['tpl'] = 'pagingLinks';
                        $ofst = (string)$ig . ',' . (string)$nrp;
                        $asOffset = ($otherOffset) ? $otherOffset . ',' . $ofst : $ofst;
                        $asOffset = '&aso=' . $asOffset;
                        $paramsUrl = $url . $asOffset;
                        $varLink['pagingLink'] = $modx->makeUrl($modx->documentIdentifier, '', $paramsUrl);
                    }
                    $varLink['pagingSeparator'] = ($nrp + $nbMax < $nbrs) ? $this->asCfg->cfg['pageLinkSeparator'] : '';
                    $varLink['pagingText'] = $resultPageLinkNumber;
                    $resultPageLinkNumber++;

                    $chkPaging->AddVar("as", $varLink);
                    $resultPageLinks.= $chkPaging->Render();
                    unset($varLink);
                    $chkPaging->CleanVars();
                }
                $varPaging = array();
                $varPaging['tpl'] = 'paging';
                $varPaging['pagingText'] = $resultPagingText;
                $varPaging['pagingLinks'] = $resultPageLinks;

                $chkPaging->AddVar("as", $varPaging);
                $footer = $chkPaging->Render();
                unset($varPaging);
                $chkPaging->CleanVars();
            }
            else if (($pagingType == 1) && (($nbrs >= $nbMax) || showPagingAlways)) {

                $tplPaging = $this->asCfg->cfg['tplPaging1'];
                if ($tplPaging == '') $tplPaging = "@FILE:" . AS_SPATH . 'templates/paging1.tpl.html';
                if (!$this->asCfg->isAjax) {
                    $url = $this->_getParamsUrl();
                    $otherOffset = $this->_getOtherOffset($ig);
                }

                $chkPaging = new AsPhxParser($tplPaging);
                if ($this->dbgTpl) $this->asUtil->dbgRecord($chkPaging->getTemplate($tplPaging), "tplPaging template " . $tplPaging);
                $varPaging1 = array();
                if ($offset - $nbMax >= 0) {
                    $varPaging1['showPrev'] = 1;
                    $prevOffset = $offset - $nbMax;
                    $prefix = ($this->asCfg->cfg['asId']) ? $this->asCfg->cfg['asId'] . "_" : '';
                    $varPaging1['prev_grpResultId'] = $prefix . 'prev_' . $this->_getCleanCssId($this->asResults->groupResults[$ig]['subsite']);
                    if ($this->asCfg->isAjax) $varPaging1['pagingPrev'] = 'javascript:void(0);';
                    else {
                        $ofst = (string)$ig . ',' . (string)$prevOffset;
                        $asOffset = ($otherOffset) ? $otherOffset . ',' . $ofst : $ofst;
                        $asOffset = '&aso=' . $asOffset;
                        $paramsUrl = $url . $asOffset;
                        $varPaging1['pagingPrev'] = $modx->makeUrl($modx->documentIdentifier, '', $paramsUrl);
                        }
                }
                else $varPaging1['showPrev'] = 0;
                $varPaging1['pagingStart'] = $offset+1;
                $varPaging1['pagingEnd'] = ($offset + $nbMax > $nbrs) ? $nbrs : $offset + $nbMax;
                $varPaging1['pagingNb'] = $nbrs;

                if ($offset + $nbMax < $nbrs) {
                    $varPaging1['showNext'] = 1;
                    $nextOffset = $offset + $nbMax;
                     $prefix = ($this->asCfg->cfg['asId']) ? $this->asCfg->cfg['asId'] . "_" : '';
                    $varPaging1['next_grpResultId'] = $prefix . 'next_' . $this->_getCleanCssId($this->asResults->groupResults[$ig]['subsite']);
                    if ($this->asCfg->isAjax) $varPaging1['pagingNext'] = 'javascript:void(0);';
                    else {
                        $nrp = $nrp + 1;
                        $ofst = (string)$ig . ',' . (string)$nextOffset;
                        $asOffset = ($otherOffset) ? $otherOffset . ',' . $ofst : $ofst;
                        $asOffset = '&aso=' . $asOffset;
                        $paramsUrl = $url . $asOffset;
                        $varPaging1['pagingNext'] = $modx->makeUrl($modx->documentIdentifier, '', $paramsUrl);
                        }
                }
                else $varPaging1['showNext'] = 0;

                $chkPaging->AddVar("as", $varPaging1);
                $footer = $chkPaging->Render();
                unset($varPaging1);
                $chkPaging->CleanVars();
            }
            elseif (($pagingType == 2) && ($nbrs >= $nbMax) && ($this->asCfg->isAjax)) {

                $tplPaging = $this->asCfg->cfg['tplPaging2'];
                if ($tplPaging == '') $tplPaging = "@FILE:" . AS_SPATH . 'templates/paging2.tpl.html';

                $chkPaging = new AsPhxParser($tplPaging);
                if ($this->dbgTpl) $this->asUtil->dbgRecord($chkPaging->getTemplate($tplPaging), "tplPaging template " . $tplPaging);
                $varPaging2 = array();
                $varPaging2['pagingStart'] = $offset+1;
                $varPaging2['pagingEnd'] = ($offset + $nbMax > $nbrs) ? $nbrs : $offset + $nbMax;
                $varPaging2['pagingNb'] = $nbrs;

                if ($offset + $nbMax < $nbrs) {
                    $varPaging2['showMore'] = 1;
                    $nextOffset = $offset + $nbMax;
                    $prefix = ($this->asCfg->cfg['asId']) ? $this->asCfg->cfg['asId'] . "_" : '';
                    $varPaging2['more_grpResultId'] = $prefix . 'more_' . $this->_getCleanCssId($this->asResults->groupResults[$ig]['subsite']);
                    $varPaging2['pagingMore'] = 'javascript:void(0);';
                    $paging2Text = $this->asCfg->lang['as_paging2Text'];
                    $varPaging2['pagingText'] = sprintf($paging2Text, NB_MORE_RESULTS);
                }
                else $varPaging2['showMore'] = 0;

                $chkPaging->AddVar("as", $varPaging2);
                $footer = $chkPaging->Render();
                unset($varPaging2);
                $chkPaging->CleanVars();
            }
        }
        return $footer;
    }
    /*
    * Set the more results link
    */
    function _setMoreResultsLink($nbrs) {
        global $modx;
        $ajaxMax = $this->asCfg->cfg['ajaxMax'];

        if (($this->asCfg->cfg['moreResultsPage'] || $this->asCfg->cfg['showMoreResults']) && ($ajaxMax < $nbrs)) {
            $this->varResults['moreResults'] = 1;
            $this->varResults['moreClass'] = 'AS_ajax_more';
            $url = $this->_getParamsUrl();
            $this->varResults['moreLink'] = $modx->makeUrl($this->asCfg->cfg['moreResultsPage'], '', $url);
            $this->varResults['moreTitle'] = $this->asCfg->lang['as_moreResultsTitle'];
            $this->varResults['moreText'] = $this->asCfg->lang['as_moreResultsText'];
        }
    }
    /*
    * Get a clean css Id
    */
    function _getCleanCssId($name) {
        $name = preg_replace('/\s+\|\|\s+/','_',trim($name));
        $name = preg_replace('/\s+/','_',$name);
        return $name;
    }
    /*
    * Get the parameters to set up an URL
    */
    function _getParamsUrl() {
        global $modx;
        $firstarg = $modx->config['friendly_urls'] ? '?' : '&';
        $url = '';

        if ($this->asCfg->cfg['asId']) $url = $firstarg . 'asid=' . urlencode($this->asCfg->cfg['asId']);

        if ($this->asCtrl->searchString) {
            if ($url) $url .= '&search=' . urlencode($this->asCtrl->searchString) . '&amp;advsearch=' . urlencode($this->asCtrl->advSearch);
            else $url = $firstarg . 'search=' . urlencode($this->asCtrl->searchString) . '&amp;advsearch=' . urlencode($this->asCtrl->advSearch);
        }
        if ($this->asCtrl->subSearch) {
            if (is_array($this->asCtrl->subSearch)) {
                foreach($this->asCtrl->subSearch as $k => $v) {
                    if ($url) $url .=  '&amp;subsearch=' . urlencode($v);
                    else $url = $firstarg . 'subsearch=' . urlencode($v);
                }
            }
            else {
                if ($url) $url .=  '&amp;subsearch=' . urlencode($this->asCtrl->subSearch);
                else $url = $firstarg . 'subsearch=' . urlencode($this->asCtrl->subSearch);
            }
        }
        if ($this->asCtrl->asf) {
            if ($url) $url .=  '&amp;asf=' . urlencode($this->asCtrl->asf);
            else $url = $firstarg . 'asf=' . urlencode($this->asCtrl->asf);
            foreach($this->asCtrl->fParams as $key =>$value) {
                if (is_array($value)) {
                    foreach($value as $k => $v) $url .= '&amp;' . $key . '[]=' . urlencode($v);
                }
                else $url .= '&amp;' . $key . '=' . urlencode($value);
            }
        }
        return $url;
    }
    /*
    * Initialize common chunks variables
    */
    function _initCommonChunks() {
        global $modx;

        if (!class_exists('AsPhxParser')) include_once AS_PATH . "classes/asPhxParser.class.inc.php";
        if (!$this->asCfg->isAjax) {

            $tplResults = $this->asCfg->cfg['tplResults'];
            if ($tplResults == '') $tplResults = "@FILE:" . AS_SPATH . 'templates/results.tpl.html';
        } else {

            $tplResults = $this->asCfg->cfg['tplAjaxResults'];
            // if @FILE binding was passed in via ajax processor, verify the path is safe
            if(stristr($tplResults, '@FILE:') !== false) {
                $path = substr($tplResults, 6);
                $frombase = $modx->config['base_path'] . $path;
                $dirname = dirname($frombase);
                $as_expected_dirname = $modx->config['base_path'] . AS_SPATH . 'templates';
                if(strpos($dirname, $as_expected_dirname) === false) {
                    $path = str_replace('..', '', $path);
                    $path = str_replace('\\', '/', $path);
                    if(substr($path, 0, 1) == '/') $path = substr($path, 1);
                    $tplResults = '@FILE:templates/' . $path;
                }
                if(!file_exists($as_expected_dirname . '/' . $path)) {
                    $tplResults = '';
                }
            }
            if ($tplResults == '') $tplResults = "@FILE:" . AS_SPATH . 'templates/ajaxResults.tpl.html';
        }

        $this->chkResults = new AsPhxParser($tplResults);
        if ($this->dbgTpl) {
            $this->asUtil->dbgRecord($this->chkResults->getTemplate($tplResults), "tplResults template" . $tplResults);
        }
    }
    /*
    * Initialize chunks variables - config dependent
    */
    function _initChunks() {
        if (!$this->asCfg->isAjax) {

            $tplGrpResult = $this->asCfg->cfg['tplGrpResult'];
            if ($tplGrpResult == '') $tplGrpResult = "@FILE:" . AS_SPATH . 'templates/grpResult.tpl.html';

            $tplResult = $this->asCfg->cfg['tplResult'];
            if ($tplResult == '') $tplResult = "@FILE:" . AS_SPATH . 'templates/result.tpl.html';
        } else {

            $tplGrpResult = $this->asCfg->cfg['tplAjaxGrpResult'];
            if ($tplGrpResult == '') $tplGrpResult = "@FILE:" . AS_SPATH . 'templates/ajaxGrpResult.tpl.html';

            $tplResult = $this->asCfg->cfg['tplAjaxResult'];
            if ($tplResult == '') $tplResult = "@FILE:" . AS_SPATH . 'templates/ajaxResult.tpl.html';
        }
        $this->chkGrpResult = new AsPhxParser($tplGrpResult);
        $this->tplGrpRes = "@CODE:" . $this->chkGrpResult->template;
        $this->chkResult = new AsPhxParser($tplResult);
        $this->tplRes = "@CODE:" . $this->chkResult->template;
        if ($this->dbgTpl) {
            $this->asUtil->dbgRecord($this->chkGrpResult->getTemplate($tplGrpResult), "tplGrpResult template" . $tplGrpResult);
            if ($this->chkResults) $this->asUtil->dbgRecord($this->chkResults->getTemplate($tplResult), "tplResult template " . $tplResult);
        }
    }
    /*
    * Set the offset of groups (used only for non-ajax mode)
    */
    function _setOffset() {
        $offset_array = explode(',', $this->asCtrl->offset);
        $io = count($offset_array);
        for ($i = 0;$i < $io;$i = $i + 2) {
            $ig = intval($offset_array[$i]);
            $ig = (($ig >= 0) && ($ig < $this->asResults->nbGroups)) ? $ig : 0;
            $val = intval($offset_array[$i + 1]);
            $val = (($val > 0) && ($val < $this->asResults->groupResults[$ig]['length'])) ? $val : 0;
            $this->asResults->groupResults[$ig]['offset'] = $val;
        }
    }
    /*
    * Get the search context
    */
    function _getSearchContext() {
        $searchContext = $this->asResults->getSearchContext();
        $this->scMain = $searchContext['main'];
        $this->scJoined = $searchContext['joined'];
        $this->scTvs = $searchContext['tvs'];
    }
    /*
    * Initialize variables used for the display - config context dependent
    */
    function _initDisplayVariables() {
        $this->_initChunks();
        $this->_initBreadcrumbs();
    }
    /*
    * Set log infos into DB for failed searches
    */
    function _setFailedSearches($asCall = '', $select = '') {
        global $modx;
        $logid = '';
        if ($this->log >= 1 ) {
            $logInfo = array();
            $logInfo['searchString'] = $this->asCtrl->searchString;
            $logInfo['nbResults'] = 0;
            $logInfo['results'] = '';
            $logInfo['asCall'] = $asCall;
            $logInfo['asSelect'] = $modx->db->escape($select);
            $logid = $this->asLog->setLogRecord($logInfo);
        }
        return $logid;
    }
    /*
    * Set log infos into DB for successfull searches
    */
    function _setSuccessfullSearches($ig) {
        global $modx;
        $logid = '';
        if ($this->log == 2) {
            $logInfo = array();
            $logInfo['searchString'] = $this->asCtrl->searchString;
            $logInfo['nbResults'] = $this->asResults->groupResults[$ig]['length'];
            $logInfo['results'] = $this->asResults->groupResults[$ig]['found'];
            $logInfo['asCall'] = $this->_getAsCall($this->asResults->groupResults[$ig]['ucfg']);
            $logInfo['asSelect'] = $modx->db->escape($this->asResults->groupResults[$ig]['select']);
            $logid = $this->asLog->setLogRecord($logInfo);
        }
        return $logid;
    }
    /*
    * Get the AjaxSearch snippet call
    */
    function _getAsCall($ucfg) {
        $call_array = explode(' ', $ucfg);
        $tpl = "&%s=`%s`";
        if ($this->asCtrl->advSearch != ONEWORD) $call_array[] = sprintf($tpl, 'advSearch', $this->asCtrl->advSearch);
        $asCall = "[!AjaxSearch";
        if (count($call_array)) {
            $asCall.= "? ";
            $asCall.= implode(' ', $call_array);
        }
        $asCall.= "!]";
        return $asCall;
    }
    /*
    *  Set all the displayed fields as PHx
    */
    function _setResultDisplayed($row) {

        $id = $this->scMain['id'];
        $this->varResult[$id] = $row[$id];

        if (isset($this->scMain['date'])) {
            foreach ($this->scMain['date'] as $field) $this->_setPhxField($field, $row, 'date');
        }

        foreach ($this->scMain['displayed'] as $field) $this->_setPhxField($field, $row, 'string');

        if (isset($this->scTvs['names'])) foreach ($this->scTvs['names'] as $field) {
            if (isset($row[$field])) $this->_setPhxField($field, $row, 'string');
        }

        if ($this->scMain['append']) foreach ($this->scMain['append'] as $field) {
            if (isset($row[$field])) $this->_setPhxField($field, $row, 'string');
        }

        if (isset($this->scJoined)) foreach ($this->scJoined as $joined) {
            $f = $joined['tb_alias'] . '_' . $id;
            $this->_setPhxField($f, $row, 'string');
        }

        if (isset($this->scJoined)) foreach ($this->scJoined as $joined) {
            foreach ($joined['displayed'] as $field) {
                $f = $joined['tb_alias'] . '_' . $field;
                $this->_setPhxField($f, $row, 'string');
            }
        }

        if ($this->asCfg->cfg['rank']) $this->_setPhxField('rank', $row, 'int');
        return $row[$id];
    }
    /*
    *  Set a field as PHx
    */
    function _setPhxField($field, $row, $type = 'string') {
        $showField = $field . "Show";
        $classField = $field . "Class";
        $contentField = $row[$field];
        if ($contentField != '') {
            $this->varResult[$showField] = 1;
            $this->varResult[$classField] = $this->asClass['prefix'] . ucfirst($field);
            if ($type == 'string') {
                $this->varResult[$field] = $this->asResults->cleanText($contentField, $this->asCfg->cfg['stripOutput']);
            } elseif ($type == 'date') {
                $this->varResult[$field] = date($this->asCfg->cfg['formatDate'], $contentField);
            } else {
                $this->varResult[$field] = $contentField;
            }
        } else {
            $this->varResult[$showField] = 0;
        }
    }
    /*
    *  Set the ResultLink PHx
    */
    function _setResultLink($row) {
        global $modx;
        $id = $this->scMain['id'];
        if (!$row[$id]) {
            return;
        }
        $hClass = $this->asClass['highlight'];
        if ($this->asCfg->cfg['highlightResult'] && $hClass) {
            $resultLink = $modx->makeUrl($row[$id], '', 'searched=' . urlencode($this->asCtrl->searchString) . '&amp;advsearch=' . urlencode($this->asCtrl->advSearch) . '&amp;highlight=' . urlencode($hClass));
        } else {
            $resultLink = $modx->makeUrl($row[$id]);
        }
        $this->varResult['resultClass'] = $this->asClass['prefix'];
        $this->varResult['resultLinkClass'] = $this->asClass['prefix'] . 'Link';
        $this->varResult['resultLink'] = $resultLink;
    }
    /*
    *  Set the ResultExtract PHx
    */
    function _setResultExtract($row) {
        if ($this->asResults->extractNb) {
            $this->varResult['extractShow'] = 1;
            $this->varResult['extractClass'] = $this->asClass['prefix'] . 'Extract';
            $this->varResult['extract'] = $this->asResults->getExtractRow($row);
        } else {
            $this->varResult['extractShow'] = 0;
        }
    }
    /*
    *  Set the ResultBreadcrumbs PHx
    */
    function _setResultBreadcrumbs($row) {
        global $modx;
        if ($this->asCfg->cfg['breadcrumbs']) {
            if ($this->asCfg->cfg['bCrumbsInfo']['type'] == 'function') {

                $bc = $this->asCfg->cfg['bCrumbsInfo']['name']($this->scMain, $row, $this->asCfg->cfg['bCrumbsInfo']['params']);
            } elseif ($this->asResults->getWithContent()) {


                $current_id = $modx->documentObject['id'];
                $current_parent = $modx->documentObject['parent'];
                $current_pagetitle = $modx->documentObject['pagetitle'];
                $current_longtitle = $modx->documentObject['longtitle'];
                $current_menutitle = $modx->documentObject['menutitle'];
                $current_description = $modx->documentObject['description'];

                $id = $this->scMain['id'];
                $modx->documentObject['id'] = $row[$id];
                $parentIds = $modx->getParentIds($row[$id], 1);
                $pid = array_pop($parentIds);
                $modx->documentObject['parent'] = $pid;
                $modx->documentObject['pagetitle'] = $row['pagetitle'];
                $modx->documentObject['longtitle'] = $row['longtitle'];
                $modx->documentObject['menutitle'] = $row['menutitle'];
                $modx->documentObject['description'] = $row['description'];

                $bc = $modx->runSnippet($this->asCfg->cfg['bCrumbsInfo']['name'], $this->asCfg->cfg['bCrumbsInfo']['params']);

                $modx->documentObject['id'] = $current_id;
                $modx->documentObject['parent'] = $current_parent;
                $modx->documentObject['pagetitle'] = $current_pagetitle;
                $modx->documentObject['longtitle'] = $current_longtitle;
                $modx->documentObject['menutitle'] = $current_menutitle;
                $modx->documentObject['description'] = $current_description;
            }

            $this->varResult['breadcrumbsShow'] = 1;
            $this->varResult['breadcrumbsClass'] = $this->asClass['prefix'] . 'Breadcrumbs';
            $this->varResult['breadcrumbs'] = $bc;
        } else {
            $this->varResult['breadcrumbsShow'] = 0;
        }
    }
    /*
    *  Set number of result as PHx
    */
    function _setResultNumber($no) {
        $this->varResult['resultNumber'] = $no;
    }
    /*
    * Initialize the breadcrumbs variables
    */
    function _initBreadcrumbs() {
        if ($this->asCfg->cfg['breadcrumbs']) {
            $bc = explode(',', $this->asCfg->cfg['breadcrumbs']);
            if (function_exists($bc[0])) {
                $this->asCfg->cfg['bCrumbsInfo']['type'] = 'function';
            } elseif ($this->_snippet_exists($bc[0])) {
                $this->asCfg->cfg['bCrumbsInfo']['type'] = 'snippet';
            } else {
                $this->asCfg->cfg['breadcrumbs'] = false;
            }
            if ($this->asCfg->cfg['breadcrumbs']) {
                $this->asCfg->cfg['bCrumbsInfo']['name'] = array_shift($bc);
                $this->asCfg->cfg['bCrumbsInfo']['params'] = array();
                foreach ($bc as $prm) {
                    $param = explode(':', $prm);
                    $this->asCfg->cfg['bCrumbsInfo']['params'][$param[0]] = (isset($param[1]) ? $param[1] : 0);
                }
            }
        }
    }
    /*
    * Check the existing of a snippet
    */
    function _snippet_exists($snippetName) {
        global $modx;
        $tbl = $modx->getFullTableName('site_snippets');
        $select = "SELECT * FROM " . $tbl . " WHERE " . $tbl . ".name='" . $modx->db->escape($snippetName) . "';";
        $rs = $modx->db->query($select);
        return $modx->db->getRecordCount($rs);
    }
    /*
    * Get offset of other groups
    */
    function _getOtherOffset($ig) {
        $otherOffset = array();
        for ($i = 0;$i < $this->asResults->nbGroups;$i++) {
            if (($i != $ig) && ($this->asResults->groupResults[$i]['offset'] != 0)) {
                $otherOffset[] = (string)$i . ',' . (string)$this->asResults->groupResults[$i]['offset'];
            }
        }
        $output = implode(',', $otherOffset);
        return $output;
    }
    /*
    * Set the needsConvert flag value
    */
    function setNeedsConvert($flag) {
        $this->_needsConvert = $flag;
    }
    /*
    *  initClassVariables : initialize the required Class values
    */
    function initClassVariables() {

        if ($this->asCfg->cfg['ajaxSearch']) $this->asClass['prefix'] = PREFIX_AJAX_RESULT_CLASS;
        else $this->asClass['prefix'] = PREFIX_RESULT_CLASS;

        $this->asClass['highlight'] = $this->_getHighlightClass($this->asCtrl->searchString, $this->asCtrl->advSearch);
    }
    /*
    *  Depending the search words, set up the highlight classes
    */
    function _getHighlightClass($search, $advSearch) {
        $hClass = '';
        $searchList = $this->asCtrl->getSearchWords($search, $advSearch);
        if (count($searchList)) {
            $hClass = HIGHLIGHT_CLASS;
            $count = 1;
            foreach ($searchList as $searchTerm) {
                $hClass.= ' ' . HIGHLIGHT_CLASS . $count;
                $count++;
            }
        }
        return $hClass;
    }
    function getHClass() {
        return HIGHLIGHT_CLASS;
    }
    /*
    * Set the ajax header with the appropriate variables
    */
    function setAjaxSearchHeader() {
        global $modx;
        $typeAs = $this->asCfg->cfg['ajaxSearch'];
        if ($typeAs) {

            if ($this->asCfg->cfg['jscript'] == 'jquery') {
                if ($this->asCfg->cfg['addJscript']) $modx->regClientStartupScript($this->asCfg->cfg['jsJquery']);
                $jsInclude = AS_SPATH . AJAXSEARCH_JSDIR . $typeAs . '/ajaxSearch-jquery.js';
            } elseif ($this->asCfg->cfg['jscript'] == 'mootools2') {
                if ($this->asCfg->cfg['addJscript']) $modx->regClientStartupScript($this->asCfg->cfg['jsMooTools2']);
                $jsInclude = AS_SPATH . AJAXSEARCH_JSDIR . $typeAs . '/ajaxSearch-mootools2.js';
            } else {
                if ($this->asCfg->cfg['addJscript']) $modx->regClientStartupScript($this->asCfg->cfg['jsMooTools']);
                $jsInclude = AS_SPATH . AJAXSEARCH_JSDIR . $typeAs . '/ajaxSearch.js';
            }
            $modx->regClientStartupScript($jsInclude);

            $json = '{"vsn":"' . AS_VERSION . '"';
            $json.= ',"adv":"' . $this->asCtrl->advSearch . '"';
            $json.= ',"sub":"' . $this->asCtrl->subSearch . '"';
            $json.= ',"bxt":"' . addslashes($this->asCfg->lang['as_boxText']) . '"';
            $json.= ',"cfg":"' . addslashes($this->asCfg->setAsCall($this->asCfg->ucfg)) . '"}';
            $line = (!$this->asCfg->cfg['asId']) ? "asvar=new Array();asvar[0]='{$json}';" : "asvar[asvar.length]='{$json}';";
            $jsVars = <<<EOD
<!-- start AjaxSearch header -->
<script type="text/javascript">
//<![CDATA[
{$line}
//]]>
</script>
<!-- end AjaxSearch header -->
EOD;
            $modx->regClientStartupScript($jsVars);
        }
    }
    /*
    *  Display Comment form
    */
    function _displayComment() {
        $outputComment = '';
        if ($this->asLog->logcmt && count($this->logIds)) {
            $chkCmt = new AsPhxParser($this->asCfg->cfg['tplComment']);
            if ($this->dbgTpl) $this->asUtil->dbgRecord($chkCmt->getTemplate($this->asCfg->cfg['tplComment']), "tplComment template " . $this->asCfg->cfg['tplComment']);
            $varCmt = array();
            $varCmt['hiddenFieldIntro'] = $this->asCfg->lang['as_cmtHiddenFieldIntro'];
            $varCmt['hiddenField'] = 'ajaxSearch_cmtHField';
            $varCmt['logid'] = array_pop($this->logIds);
            $varCmt['cmtIntroMessage'] = $this->asCfg->lang['as_cmtIntroMessage'];
            $varCmt['cmtSubmitText'] = $this->asCfg->lang['as_cmtSubmitText'];
            $varCmt['cmtResetText'] = $this->asCfg->lang['as_cmtResetText'];
            $varCmt['cmtThksMessage'] = $this->asCfg->lang['as_cmtThksMessage'];

            $chkCmt->AddVar("as", $varCmt);
            $outputComment = $chkCmt->Render();
        }
        return $outputComment;
    }
    /*
    * paginate : display the previous / next page of results
    */
    function paginate($validSearch, &$msgErr) {
        $ouputResults = null;
        if ($validSearch) {
            list($ig, $currentOffset, $sens) = explode(',',$this->asCtrl->pagination);
            $this->_getSearchContext();
            $site = $this->asResults->groupResults[$ig]['site'];
            $subsite = $this->asResults->groupResults[$ig]['subsite'];
            $display = $this->asResults->groupResults[$ig]['display'];
            $nbrs = intval($this->asResults->groupResults[$ig]['length']);
            $ajaxMax = ($this->asCfg->cfg['ajaxMax'] > 0) ? $this->asCfg->cfg['ajaxMax'] : $nbrs;
            $pagingType = $this->asCfg->cfg['pagingType'];

            // nb results displayed
            if ($pagingType == 1) {
                if ($sens == 1) {
                    $offset = $currentOffset + $ajaxMax;
                    $nbRes = ($offset + $ajaxMax > $nbrs) ? $nbrs - $offset : $ajaxMax;
                }
                else {
                    $offset = $currentOffset - $ajaxMax;
                    $offset = ($offset >= 0) ? $offset : 0;
                    $nbRes = $ajaxMax;
                }
            }
            elseif ($pagingType == 2) {
                if ($sens > 0) {
                    if ($currentOffset == 0) $currentNbRes = $ajaxMax;
                    else $currentNbRes = NB_MORE_RESULTS;
                    $offset = $currentOffset + $currentNbRes;
                    $nbRes = ($offset + NB_MORE_RESULTS > $nbrs) ? $nbrs - $offset : NB_MORE_RESULTS;
                }
                else {
                    if ($currentOffset == $ajaxMax) $previousNbRes = $ajaxMax;
                    else $previousNbRes = NB_MORE_RESULTS;
                    $offset = $currentOffset - $previousNbRes;
                    $offset = ($offset >= 0) ? $offset : 0;
                    $nbRes = $previousNbRes;
                }
            }

            $searchResults = array_slice($this->asResults->groupResults[$ig]['results'], $offset, $nbRes);

            $this->asCfg->chooseConfig($site, $subsite, $display);
            $this->_initDisplayVariables();

            $jsonPairs = array();
            if ($pagingType == 1) {  // Prev / Next links
                $grpResult = $this->_displayGrpResult($ig, $site, $subsite, $display, $nbrs, $searchResults, $offset, $nbRes);
                $jsonPairs[] = $this->_getJsonPair('res',$grpResult);
            }
            elseif ($pagingType == 2) {
                $found = array();
                $this->chkGrpResult = new AsPhxParser($this->tplGrpRes);

                $moreOffset = 0;
                $moreNbMax = $offset + $nbRes;
                $header = $this->_displayHeaderGrpResult($site, $subsite, $display, $nbrs, $searchResults, $moreOffset, $moreNbMax);
                $listResults = $this->_displayListResults($site, $subsite, $display, $nbrs, $searchResults, $found, $offset);
                $footer = $this->_displayFooterGrpResult($ig, $nbrs, $moreOffset, $moreNbMax);

                $this->asResults->groupResults[$ig]['found'] = implode(' ',$found);
                $jsonPairs[] = $this->_getJsonPair('hdr',$header);
                $jsonPairs[] = $this->_getJsonPair('res',$listResults);
                $jsonPairs[] = $this->_getJsonPair('ftr',$footer);
            }
            $pgn = (string)$ig . ',' . (string)$offset;
            $jsonPairs[] = $this->_getJsonPair('pgn',$pgn);

            $this->_updateAsfPaginate($ig, $jsonPairs);

            $outputResults = $this->_getJson($jsonPairs);

            $this->asCfg->restoreConfig(DEFAULT_SITE, DEFAULT_SUBSITE);
        }
        return $outputResults;
    }
    /*
    * Send back categories & tags
    */
    function _updateAsfPaginate($ig, & $jsonPairs) {

        $resultsCateg = array();
        $resultsCateg = $this->asResults->getResultsCateg();

        $ctgnm = array();
        $ctgnm[] = $resultsCateg['name'][$ig];
        if ($this->dbgRes) $this->asUtil->dbgRecord($resultsCateg, "getResultsCateg");
        $sctgnm = $this->_getJsonArray($ctgnm);
        $jsonPairs[] = $this->_getJsonPair('ctgnm',$sctgnm);

        return;
    }
    /*
    * Send back categories
    */
    function _updateAsf( & $jsonPairs) {

        $resultsCateg = array();
        $resultsCateg = $this->asResults->getResultsCateg();
        if ($this->dbgRes) $this->asUtil->dbgRecord($resultsCateg, "getResultsCateg");

        $ctgnm = $this->_getJsonArray($resultsCateg['name']);
        $jsonPairs[] = $this->_getJsonPair('ctgnm',$ctgnm);
        $ctgnb = $this->_getJsonArray($resultsCateg['nb']);
        $jsonPairs[] = $this->_getJsonPair('ctgnb',$ctgnb);

        return;
    }
    /*
    * Prepare json array of String
    * ["art","music","geography"]
    */
    function _getJsonArray($array) {
        $nbr = count($array);
        if ($nbr) $jsonArray = '[' . implode(',',$array) . ']';
        else $jsonArray = '';
        return $jsonArray;
    }
    /*
    * Prepare json pair key : value
    */
    function _getJsonPair($key,$value) {
        $value = addslashes($value);
        $value = str_replace(array("\r\n", "\r", "\n"), ' ', $value);
        $jsonPair = '"' . $key . '":"' . $value . '"';
        return $jsonPair;
    }
    /*
    * Prepare json string
    */
    function _getJson($pairs) {
        $json = '{' . implode(',',$pairs) . '}';
        return $json;
    }
}
?>