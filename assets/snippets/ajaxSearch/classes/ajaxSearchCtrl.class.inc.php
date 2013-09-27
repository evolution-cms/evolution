<?php
/* -----------------------------------------------------------------------------
* Snippet: AjaxSearch
* -----------------------------------------------------------------------------
* @package  AjaxSearchCtrl
*
* @author       Coroico - www.evo.wangba.fr
* @version      1.9.3
* @date         26/09/2012
*
* Purpose:
*    The AjaxSearchCtrl class contains the logic and synchronisation between model and views
*
*/

class AjaxSearchCtrl {

    // public variables
    var $asCfg = null;
    var $asInput = null;
    var $asResults = null;
    var $asOutput = null;
    var $asUtil = null;
    var $asLog = null;
    var $dbg = false;
    var $dbgTpl = false;
    var $dbgRes = false;
    var $log = false;

    var $forThisAs;
    var $searchString;
    var $advSearch;
    var $subSearch;
    var $pagination;
    var $offset;
    var $asf;
    var $output;
    var $fClause;
    var $fParams = array();

    function AjaxSearchCtrl() {
    }
    function init(&$asCfg, &$asInput, &$asResults, &$asOutput, &$asUtil, &$asLog){
        // initialize the controler instance
        $this->asCfg =& $asCfg;
        $this->asInput =& $asInput;
        $this->asResults =& $asResults;
        $this->asOutput =& $asOutput;
        $this->asUtil =& $asUtil;
        $this->dbg = $asUtil->dbg;
        $this->dbgTpl = $asUtil->dbgTpl;
        $this->dbgRes = $asUtil->dbgRes;
        $this->asLog =& $asLog;
        $asLog_array = explode(':', $asCfg->cfg['asLog']);
        $this->log = ($asLog_array[0]) ? true : false;


        $this->asInput->init($asCfg, $this, $asUtil);
        $this->asResults->init($asCfg, $this, $asOutput, $asUtil);
        $this->asOutput->init($asCfg, $this, $asInput, $asResults, $asUtil, $asLog, $this->log);
    }
    /*
    * run : run the search
    */
    function run() {
        $this->setforThisAs();
        $this->getEvents();     // get $_POST and _GET variables
        $valid = $this->asInput->display($msg);
        if ($valid) {
            $valid2 = $this->asResults->getSearchResults($msg);
            if (!$valid2) return $msg;
        }
        $this->asOutput->setAjaxSearchHeader();
        if (!$this->pagination) $output = $this->asOutput->display($valid, $msg);
        else $output = $this->asOutput->paginate($valid, $msg);
        return $output;
    }
    /*
    * setforThisAs : Check if this instance is concerned
    */
    function setforThisAs() {
        if ($this->asCfg->isAjax) $this->forThisAs = true;
        else {
            $id = '';
            if (isset($_POST['asid']) || isset($_GET['asid'])) {

                if (isset($_POST['asid'])) $id = strip_tags($_POST['asid']);
                else $id = strip_tags(urldecode($_GET['asid']));
            }
            $this->forThisAs = ($this->asCfg->cfg['asId'] != $id) ? false : true;
        }
    }
    function getEvents() {

        $this->getSearchString();
        $this->subSearch = $this->asCfg->cfg['subSearch'];
        if (isset($_POST['subsearch']) || isset($_GET['subsearch'])) {

            if (isset($_POST['subsearch'])) {
                $ssc = isset($_POST['ssc']) ? ':' : ',';
                if (is_array($_POST['subsearch'])) {
                    foreach($_POST['subsearch'] as $key => $value) $_POST['subsearch'][$key] = strip_tags($value);
                    $this->subSearch = implode($ssc,$_POST['subsearch']);
                }
                else $this->subSearch = strip_tags($_POST['subsearch']);
            }
            else {
                $ssc = isset($_GET['ssc']) ? ':' : ',';
                if (is_array($_GET['subsearch'])) {
                    foreach($_GET['subsearch'] as $key => $value) $_GET['subsearch'][$key] = strip_tags($value);
                    $this->subSearch = implode($ssc,$_GET['subsearch']);
                }
                else $this->subSearch = strip_tags($_GET['subsearch']);
            }
        }
        if ($this->dbg) $this->asUtil->dbgRecord($this->subSearch , "getEvents - subsearch");


        $asfConfig = 'asfConfig';
        if ((isset($_POST['asf']) || isset($_GET['asf'])) &&  function_exists($asfConfig)) {
            $this->asf = isset($_POST['asf']) ? strip_tags($_POST['asf']) : strip_tags(urldecode($_GET['asf']));
            $this->fClause = $asfConfig($this->asf, $this->fParams);
            if ($this->dbg) $this->asUtil->dbgRecord($this->fParams , "getEvents - fParams");
            if ($this->dbg) $this->asUtil->dbgRecord($this->fClause , "getEvents - fClause");
        }
        else $this->asf  = '';
        if ($this->dbg) $this->asUtil->dbgRecord($this->asf , "getEvents - asf");

        $this->offset = (isset($_GET['aso'])) ? strip_tags(urldecode($_GET['aso'])) : '0,0';
        if ($this->dbg) $this->asUtil->dbgRecord($this->offset , "getEvents - offset");

        $this->pagination = (isset($_POST['pgn'])) ? strip_tags($_POST['pgn']) : '';
        if ($this->dbg) $this->asUtil->dbgRecord($this->pagination , "getEvents - pgn");
    }
    function getSearchString() {
        $this->searchString = '';
        $this->advSearch = $this->asCfg->cfg['advSearch'];

        if ($this->forThisAs) {
            if (!$this->asCfg->isAjax) {

                if (isset($_POST['search']) || (isset($_GET['search']) && (!$this->asCfg->cfg['ajaxSearch']))) {
                    if (isset($_POST['search'])) {
                        if (is_array($_POST['search'])) {
                            foreach($_POST['search'] as $key => $value) $_POST['search'][$key] = strip_tags($value);
                            $this->searchString = implode(' ', $_POST['search']);
                        }
                        else $this->searchString = strip_tags($_POST['search']);
                    } else {
                        $this->searchString = strip_tags(urldecode($_GET['search']));
                    }
                    if (isset($_POST['advsearch'])) $this->advSearch = strip_tags($_POST['advsearch']);
                    else if (isset($_GET['advsearch'])) $this->advSearch = strip_tags(urldecode($_GET['advsearch']));
                }
            }
            else {
                if (isset($_POST['search'])) {
                    if (is_array($_POST['search'])) {
                        foreach($_POST['search'] as $key => $value) $_POST['search'][$key] = strip_tags($value);
                        $this->searchString = implode(' ', $_POST['search']);
                    }
                    else $this->searchString = strip_tags($_POST['search']);

                    if (($this->asCfg->pgCharset != 'UTF-8') && (ini_get('mbstring.encoding_translation') == '' || strtolower(ini_get('mbstring.http_input')) == 'pass')) {
                        $this->searchString = mb_convert_encoding($this->searchString, $this->asCfg->pgCharset, "UTF-8");
                        $this->asOutput->setNeedsConvert(true);
                    } else {
                        $this->asOutput->setNeedsConvert(false);
                    }
                    if (isset($_POST['advsearch'])) $this->advSearch = strip_tags($_POST['advsearch']);
               }
            }
        }
        if ($this->dbg) $this->asUtil->dbgRecord($this->searchString, "getSearchString - searchString");
        if ($this->dbg) $this->asUtil->dbgRecord($this->advSearch, "getSearchString - advSearch");
    }
    /*
    *  getSearchWords : depending advSearch, get the search words
    */
    function getSearchWords($search, $advSearch) {
        $searchList = array();
        if (($advSearch == NOWORDS) || (!$search)) return $searchList;
        if ($advSearch == EXACTPHRASE) $searchList[] = $search;
        else $searchList = explode(' ', $search);
        return $searchList;
    }
    function setSearchString($searchString) {
        $this->searchString = $searchString;
    }
    function setAdvSearch($advSearch) {
        $this->advSearch = $advSearch;
    }
    function setSubSearch($subSearch) {
        $this->subSearch = $subSearch;
    }
}
?>