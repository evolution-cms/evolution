<?php
/* -----------------------------------------------------------------------------
* Snippet: AjaxSearch
* -----------------------------------------------------------------------------
* @package  AjaxSearchInput
*
* @author       Coroico - www.evo.wangba.fr
* @version      1.9.3
* @date         26/09/2012
*
* Purpose:
*    The AjaxSearchInput class contains all functions and data used to manage Input form
*
*/

class AjaxSearchInput {

    // public variables
    var $asCfg = null;
    var $asCtrl = null;
    var $asUtil = null;
    var $dbg = false;
    var $dbgTpl = false;

    var $inputForm = '';
    var $asfForm = '';

    // private variables
    var $_advSearchType = array(ONEWORD, ALLWORDS, EXACTPHRASE, NOWORDS);

    /*
    * Constructs the ajaxSearchInput object
    *
    * @access public
    */
    function AjaxSearchInput() {
    }
    /*
    * Initializes the class into the proper context
    *
    * @access public
    * @param AjaxSearchConfig &$asCfg configuration context
    * @param AjaxSearchCtrl &$asCtrl controler instance
    * @param AjaxSearchUtil &$asUtil debug instance
    * @param boolean $dbg debug flag
    * @param boolean $dbgTpl debug flag for templates
    */
    function init(&$asCfg, &$asCtrl, &$asUtil) {
        $this->asCfg =& $asCfg;
        $this->asCtrl =& $asCtrl;
        $this->asUtil =& $asUtil;
        $this->dbg = $asUtil->dbg;
        $this->dbgTpl = $asUtil->dbgTpl;
    }
    /*
    * Set up the input form
    *
    * @access public
    * @param string &$msgErr message error
    */
    function display(&$msgErr) {
        $msgErr = '';
        $this->_checkParams();
        $valid = $this->_validSearchString($msgErr);
        $this->_displayInputForm($msgErr);
        $this->_setClearDefaultHeader();

        return $valid;
    }
    /*
    * Check input field params
    */
    function _checkParams() {
        if ($this->asCtrl->forThisAs) {
            if (isset($this->asCfg->cfg['maxWords'])) {
                if ($this->asCfg->cfg['maxWords'] < MIN_WORDS) $this->asCfg->cfg['maxWords'] = MIN_WORDS;
                if ($this->asCfg->cfg['maxWords'] > MAX_WORDS) $this->asCfg->cfg['maxWords'] = MAX_WORDS;
            }
            if (isset($this->asCfg->cfg['minChars'])) {
                if ($this->asCfg->cfg['minChars'] < MIN_CHARS) $this->asCfg->cfg['minChars'] = MIN_CHARS;
                if ($this->asCfg->cfg['minChars'] > MAX_CHARS) $this->asCfg->cfg['minChars'] = MAX_CHARS;
            }
        }
    }
    /*
    * Valid the input search term and the advSearch parameter
    */
    function _validSearchString(&$msgErr) {
        global $modx;
        $msgErr = '';
        $searchString = $this->asCtrl->searchString;
        $advSearch = $this->asCtrl->advSearch;

        $advSearch = (in_array($advSearch, $this->_advSearchType)) ? $advSearch : $this->_advSearchType[0];

        $valid = $this->_checkSearchString($searchString, $advSearch, $msgErr);
        if (!$valid) return false;

        $valid = $this->_stripSearchString($searchString, $this->asCfg->cfg['stripInput'], $advSearch, $msgErr);
        if (!$valid) return false;

        $modx->setPlaceholder("as.searchString", $searchString);

        $this->asCtrl->setSearchString($searchString);
        $this->asCtrl->setAdvSearch($advSearch);
        return true;
    }
    /*
    * Check search string
    */
    function _checkSearchString(&$searchString, $advSearch, &$msgErr) {
        if (!$this->asCtrl->forThisAs) {
            $msgErr = '';
            return false;
        }
        if ($this->dbg) $this->asUtil->dbgRecord($searchString, "checkSearchString - searchString");

        $searchSubmitted = (isset($_POST['search']) || isset($_GET['search']));
        if ($this->dbg) $this->asUtil->dbgRecord($searchSubmitted,"checkSearchString - searchSubmitted");
        $asfSubmitted = (isset($_POST['asf']) || isset($_GET['asf']));
        if ($this->dbg) $this->asUtil->dbgRecord($asfSubmitted,"checkSearchString - asfSubmitted");
        $checkString = true;

        if ($searchSubmitted) {
            if ($searchString == $this->asCfg->lang['as_boxText']) {   // "search here ..."
                if ($this->asCfg->cfg['init'] == 'all') { // display all
                    $checkString = false;
                    $searchString = '';
                }
                else {  // check the empty input field => error message (At least 3 characters)
                    $searchString = '';
                    if ($asfSubmitted) $checkString = false; // no check if a filter is submitted
                }
            }
            else if (($searchString == '') && ($this->asCfg->cfg['init'] == 'all')) {
                $checkString = false;
            }
            else if (($searchString == '') && ($this->asCfg->cfg['init'] == 'none') && $asfSubmitted) {
                $checkString = false;
            }
        }
        else {
            if ($searchString == '') {
                if (($this->asCfg->cfg['init'] == 'none') && (!$asfSubmitted)) {
                    $msgErr = '';
                    return false;
                }
                else {
                    $checkString = false;
               }
            }
        }
        if ($this->dbg) $this->asUtil->dbgRecord($checkString,"checkSearchString - checkString");
        if ($this->dbg) $this->asUtil->dbgRecord($searchString,"checkSearchString - searchString used");

        if ($checkString) {

            $words_array = explode(' ', preg_replace('/\s\s+/', ' ', trim($searchString)));
            $mbStrlen = $this->asCfg->cfg['mbstring'] ? 'mb_strlen' : 'strlen';
            if (($this->asCfg->dbCharset == 'utf8') && ($this->asCfg->cfg['mbstring'])) mb_internal_encoding("UTF-8");


            if (count($words_array) > $this->asCfg->cfg['maxWords']) {
                $msgErr = sprintf($this->asCfg->lang['as_maxWords'], $this->asCfg->cfg['maxWords']);
                return false;
            }

            if ($advSearch == EXACTPHRASE) {

                if ($mbStrlen($searchString) < $this->asCfg->cfg['minChars']) {
                    $msgErr = sprintf($this->asCfg->lang['as_minChars'], $this->asCfg->cfg['minChars']);
                    return false;
                }
                if ($mbStrlen($searchString) > MAX_CHARS) {
                    $msgErr = sprintf($this->asCfg->lang['as_maxChars'], MAX_CHARS);
                    return false;
                }
            } else {
                //oneword, allwords or nowords
                foreach ($words_array as $word) {
                    if ($mbStrlen($word) < $this->asCfg->cfg['minChars']) {
                        $msgErr = sprintf($this->asCfg->lang['as_minChars'], $this->asCfg->cfg['minChars']);
                        return false;
                    }
                    if ($mbStrlen($searchString) > MAX_CHARS) {
                        $msgErr = sprintf($this->asCfg->lang['as_maxChars'], MAX_CHARS);
                        return false;
                    }
                }
            }
        }
        return true;
    }
    /*
    * Strip the searchString with the user StripInput function
    */
    function _stripSearchString(&$searchString, $stripInput, &$advSearch, &$msgErr) {
        $msgErr = '';
        if ($searchString != '') {
            $searchString = preg_replace('/\s\s+/', ' ', trim($searchString));
            if ($stripInput != 'defaultStripInput') {
                if (function_exists($stripInput)) $searchString = $stripInput($searchString, $advSearch);
                else {
                    $msgErr = "<br /><h3>AjaxSearch error: strip input function $stripInput not defined in the configuration file: " . $this->asCfg->cfg['config'] . " !</h3><br />";
                    return false;
                }
            } else $searchString = $this->_defaultStripInput($searchString, $this->asCfg->pgCharset);
            $valid = (($searchString !== '') || ($this->asCfg->cfg['init'] == 'all'));
            return $valid;
        }
        return true;
    }
    /*
    * Default user StripInput function
    */
    function _defaultStripInput($searchString, $pgCharset = 'UTF-8') {
        if ($searchString !== '') {

            $searchString = stripslashes($searchString);

            $searchString = $this->_stripJscripts($searchString);

            $searchString = $this->_stripTags($searchString);

            $searchString = $this->_stripHtml($searchString);

            $searchString = $this->_htmlspecialchars($searchString, ENT_COMPAT, $pgCharset, False);
        }
        return $searchString;
    }
    /*
    * Display the input form
    */
    function _displayInputForm($msgErr) {
        global $modx;
        $varInputForm = array();
        if ($this->asCfg->cfg['showInputForm']) {
            if (!class_exists('AsPhxParser')) include_once AS_PATH . "classes/asPhxParser.class.inc.php";

            $tplInputForm = $this->asCfg->cfg['tplInput'];
            $chkInputForm = new AsPhxParser($tplInputForm);
            if ($this->dbgTpl) {
                $this->asUtil->dbgRecord($chkInputForm->getTemplate($tplInputForm), "tplInputForm template" . $tplInputForm);
            }

            if (isset($this->asCfg->cfg['landingPage']) && !is_bool($this->asCfg->cfg['landingPage'])) {
                $searchAction = "[~" . $this->asCfg->cfg['landingPage'] . "~]";
            } else {
                $searchAction = "[~" . $modx->documentIdentifier . "~]";
            }
            $varInputForm['showInputForm'] = '1';
            $varInputForm['formId'] = ($this->asCfg->cfg['asId'] != '') ? $this->asCfg->cfg['asId'] . '_ajaxSearch_form' : 'ajaxSearch_form';
            $varInputForm['formAction'] = $searchAction;

            if ($this->dbg) $this->asUtil->dbgRecord($this->asCtrl->searchString, "displayInputForm - searchString");

            $varInputForm['inputId'] = ($this->asCfg->cfg['asId'] != '') ? $this->asCfg->cfg['asId'] . '_ajaxSearch_input' : 'ajaxSearch_input';
            // simple input field - Not used if a multiple input list is used
            $varInputForm['inputValue'] = ($this->asCtrl->searchString == '' && $this->asCfg->lang['as_boxText'] != 'init' ) ? $this->asCfg->lang['as_boxText'] : $this->asCtrl->searchString;
            $varInputForm['inputOptions'] = ($this->asCfg->lang['as_boxText']) ? ' onfocus="this.value=(this.value==\'' . $this->asCfg->lang['as_boxText'] . '\')? \'\' : this.value ;"' : '';
            $varInputForm['asfId'] = ($this->asCfg->cfg['asId'] != '') ? $this->asCfg->cfg['asId'] . '_asf_form' : 'asf_form';

            //if (($this->asCtrl->searchString == 'init')) $this->asCtrl->setSearchString('');
            if ($this->dbg) $this->asUtil->dbgRecord($this->asCtrl->searchString, "displayInputForm - searchString");


            // Submit button
            if ($this->asCfg->cfg['liveSearch']) $varInputForm['liveSearch'] = 1;
            else {
                $varInputForm['liveSearch'] = 0;
                $varInputForm['submitId'] = ($this->asCfg->cfg['asId'] != '') ? $this->asCfg->cfg['asId'] . '_ajaxSearch_submit' : 'ajaxSearch_submit';
                $varInputForm['submitText'] = $this->asCfg->lang['as_searchButtonText'];
            }


            $varInputForm['advSearch'] = $this->asCtrl->advSearch;

            $varInputForm['radioId'] = ($this->asCfg->cfg['asId']) ? $this->asCfg->cfg['asId'] . '_ajaxSearch_radio' : 'ajaxSearch_radio';
            $varInputForm['onewordId'] = ($this->asCfg->cfg['asId']) ? $this->asCfg->cfg['asId'] . '_radio_oneword' : 'radio_oneword';
            $varInputForm['oneword'] = ONEWORD;
            $varInputForm['onewordText'] = $this->asCfg->lang[ONEWORD];
            $varInputForm['onewordChecked'] = ($this->asCtrl->advSearch == ONEWORD ? 'checked ="checked"' : '');
            $varInputForm['allwordsId'] = ($this->asCfg->cfg['asId']) ? $this->asCfg->cfg['asId'] . '_radio_allwords' : 'radio_allwords';
            $varInputForm['allwords'] = ALLWORDS;
            $varInputForm['allwordsText'] = $this->asCfg->lang[ALLWORDS];
            $varInputForm['allwordsChecked'] = ($this->asCtrl->advSearch == ALLWORDS ? 'checked ="checked"' : '');
            $varInputForm['exactphraseId'] = ($this->asCfg->cfg['asId']) ? $this->asCfg->cfg['asId'] . '_radio_exactphrase' : 'radio_exactphrase';
            $varInputForm['exactphrase'] = EXACTPHRASE;
            $varInputForm['exactphraseText'] = $this->asCfg->lang[EXACTPHRASE];
            $varInputForm['exactphraseChecked'] = ($this->asCtrl->advSearch == EXACTPHRASE ? 'checked ="checked"' : '');
            $varInputForm['nowordsId'] = ($this->asCfg->cfg['asId']) ? $this->asCfg->cfg['asId'] . '_radio_nowords' : 'radio_nowords';
            $varInputForm['nowords'] = NOWORDS;
            $varInputForm['nowordsText'] = $this->asCfg->lang[NOWORDS];
            $varInputForm['nowordsChecked'] = ($this->asCtrl->advSearch == NOWORDS ? 'checked ="checked"' : '');


            if (!$this->asCtrl->searchString) {
                if ($this->asCfg->cfg['showIntro']) {
                    $varInputForm['showIntro'] = 1;
                    $varInputForm['introMessage'] = $this->asCfg->lang['as_introMessage'];
                } else $varInputForm['showIntro'] = 0;
            }


            if ($this->asCfg->cfg['asId']) {
                $varInputForm['showAsId'] = '1';
                $varInputForm['asName'] = 'asid';
                $varInputForm['asId'] = $this->asCfg->cfg['asId'];
            } else {
                $varInputForm['showAsId'] = '0';
            }

            $chkInputForm->AddVar("as", $varInputForm);
            $this->inputForm = $chkInputForm->Render() . "\n";

            if (isset($this->asCfg->cfg['tplAsf']) && $this->asCfg->cfg['tplAsf']) {
                $tplAsfForm = $this->asCfg->cfg['tplAsf'];
                $chkAsfForm = new AsPhxParser($tplAsfForm);
                if ($this->dbgTpl) {
                    $this->asUtil->dbgRecord($chkAsfForm->getTemplate($tplAsfForm), "tplAsfForm template" . $tplAsfForm);
                    }
                $varAsfForm = array();
                $varAsfForm['asfId'] = ($this->asCfg->cfg['asId'] != '') ? $this->asCfg->cfg['asId'] . '_asf_form' : 'asf_form';
                $chkAsfForm->AddVar("as", $varAsfForm);
                $this->asfForm = $chkAsfForm->Render() . "\n";
            }
        }
    }
    /*
    * setClearDefaultHeader : set the clearDefault header
    */
    function _setClearDefaultHeader() {
        global $modx;

        if ($this->asCfg->cfg['showInputForm'] && $this->asCfg->cfg['clearDefault']) $modx->regClientStartupScript($this->asCfg->cfg['jsClearDefault']);
    }
    /*
    * Manage the double_encode parameter added with version 5.2.3
    */
    function _htmlspecialchars($string, $quote_style = ENT_COMPAT, $charset = 'UTF-8', $double_encode = true) {
        // The double_encode  parameter was added with version 5.2.3
        if (version_compare(PHP_VERSION, '5.2.3', '>=')) $string = htmlspecialchars($string, $quote_style, $charset, $double_encode);
        else {
            if ($double_encode === true) {
                $string = str_replace('&amp;', '&', $string);
            }
            $tf = array('&' => '&amp;', '<' => '&lt;', '>' => '&gt;');
            if ($quote_style & ENT_NOQUOTES) {
                $tf['"'] = '&quot';
            }
            if ($quote_style & ENT_QUOTES) {
                $tf["'"] = '&#039;';
            }
            $searchString = str_replace(array_keys($tf), array_values($tf), $string);
        }
        return $string;
    }
    /*
    *  stripTags : Remove MODx sensitive tags
    */
    function _stripTags($text) {

        $modRegExArray[] = '~\[\[(.*?)\]\]~';
        $modRegExArray[] = '~\[!(.*?)!\]~';
        $modRegExArray[] = '!\[\~(.*?)\~\]!is';
        $modRegExArray[] = '~\[\((.*?)\)\]~';
        $modRegExArray[] = '~{{(.*?)}}~';
        $modRegExArray[] = '~\[\*(.*?)\*\]~';
        $modRegExArray[] = '~\[\+(.*?)\+\]~';

        foreach ($modRegExArray as $mReg) $text = preg_replace($mReg, '', $text);
        return $text;
    }
    /*
    *  stripJscript : Remove jscript
    */
    function _stripJscripts($text) {

        $text = preg_replace("'<script[^>]*>.*?</script>'si", "", $text);
        $text = preg_replace('/{.+?}/', '', $text);
        return $text;
    }
    /*
    *  stripHtml : Remove HTML sensitive tags
    */
    function _stripHtml($text) {
        return strip_tags($text);
    }
}
?>