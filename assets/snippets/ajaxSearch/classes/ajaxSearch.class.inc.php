<?php
/* -----------------------------------------------------------------------------
* Snippet: AjaxSearch
* -----------------------------------------------------------------------------
* @package  AjaxSearch
*
* @author       Coroico - www.evo.wangba.fr
* @version      1.9.3
* @date         26/09/2012
*
* Purpose:
*    The AjaxSearch class contains all functions and data used to manage AjaxSearch
*
*/

define('MIN_CHARS', 2); // minimum number of characters
define('MAX_CHARS', 30); // maximum number of characters
define('MIN_WORDS', 1); // minimum number of words
define('MAX_WORDS', 10); // maximum number of words

define('EXTRACT_MIN', 50); // minimum length of extract
define('EXTRACT_MAX', 800); // maximum length of extract

define('MIXED', 'mixed');
define('UNMIXED', 'unmixed');

define('DEFAULT_SITE', 'defsite');
define('DEFAULT_SUBSITE', 'site_wide');
define('MIXED_SITES', 'all_sites');
define('UNCATEGORIZED', 'uncategorized');
define('UNTAGGED', 'untagged');

define('SITE_CONFIG','siteConfig');
define('SUBSITE_CONFIG','subsiteConfig');
define('CATEG_CONFIG','categConfig');
define('FILTER_CONFIG','filterConfig');

define('PCRE_BACKTRACK_LIMIT', 1600000);

// advanced search parameter values
define('ONEWORD','oneword');
define('ALLWORDS','allwords');
define('EXACTPHRASE','exactphrase');
define('NOWORDS','nowords');

class AjaxSearch {

    /*
    *  Constructs the ajaxSearch object
    *
    *  @access public
    */
    function AjaxSearch() {
    }
    /*
    *  Run ajaxSearch
    *
    *  @access public
    *  @param timestamp $tstart start time
    *  @param array $dcfg default configuration
    *  @param array $cfg current configuration
    *  @return the ajaxSearch output
    */
    function run($tstart, $dcfg, $cfg = null) {
        global $modx;

        include_once AS_PATH . "classes/ajaxSearchConfig.class.inc.php";
        if (!class_exists('AjaxSearchConfig')) return "<h3>error: AjaxSearchConfig classe not found</h3>";
        $asCfg = new AjaxSearchConfig($dcfg,$cfg);
        if (!$asCfg->initConfig($msgErr)) return $msgErr;

        include_once AS_PATH . "classes/ajaxSearchUtil.class.inc.php";
        if (!class_exists('AjaxSearchUtil')) return "<h3>error: AjaxSearchUtil classe not found</h3>";
        $asUtil = new AjaxSearchUtil($asCfg->cfg['debug'],$asCfg->cfg['version'],$tstart,$msgErr);
        if ($msgErr) return $msgErr;

        $dbg = $asUtil->dbg; // first level of debug log
        if(!ini_get('safe_mode')) set_time_limit($asCfg->cfg['timeLimit']);

        include_once AS_PATH . "classes/ajaxSearchCtrl.class.inc.php";
        include_once AS_PATH . "classes/ajaxSearchInput.class.inc.php";
        include_once AS_PATH . "classes/ajaxSearchResults.class.inc.php";
        include_once AS_PATH . "classes/ajaxSearchOutput.class.inc.php";

        if (class_exists('AjaxSearchCtrl') && class_exists('AjaxSearchInput') && class_exists('AjaxSearchResults') && class_exists('AjaxSearchOutput')) {
            if ($asCfg->cfg['asLog']) {
                include_once AS_PATH . "classes/ajaxSearchLog.class.inc.php";
                $asLog = new AjaxSearchLog($asCfg->cfg['asLog']);
            }
            if ($dbg) $asCfg->displayConfig($asUtil);

            $asCtrl = new AjaxSearchCtrl();
            $asInput = new AjaxSearchInput();
            $asResults = new AjaxSearchResults();
            $asOutput = new AjaxSearchOutput();

            $asCtrl->init($asCfg,$asInput,$asResults,$asOutput,$asUtil,$asLog);

            $asUtil->setBacktrackLimit(PCRE_BACKTRACK_LIMIT);

            $output = $asCtrl->run();

            $asUtil->restoreBacktrackLimit();

            $etime = $asUtil->getElapsedTime();
            if ($dbg) $asUtil->dbgRecord($etime, "AjaxSearch - Elapsed Time");
        } else {
            $output = "<h3>error: AjaxSearch classes not found</h3>";
        }
        return $output;
    }
}
//

//
// Below functions could be used in end-user fonctions
/*
*  stripTags : Remove modx sensitive tags
*/
if (!function_exists('stripTags')) {
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
}
/*
*  stripHtml : Remove HTML sensitive tags
*/
if (!function_exists('stripHtml')) {
    function stripHtml($text) {
        return strip_tags($text);
    }
}
/*
*  stripHtmlExceptImage : Remove HTML sensitive tags except image tag
*/
if (!function_exists('stripHtmlExceptImage')) {
    function stripHtmlExceptImage($text) {
        $text = strip_tags($text, '<img>');
        return $text;
    }
}
/*
*  stripJscript : Remove jscript
*/
if (!function_exists('stripJscripts')) {
    function stripJscripts($text) {

        $text = preg_replace("'<script[^>]*>.*?</script>'si", "", $text);
        $text = preg_replace('/{.+?}/', '', $text);
        return $text;
    }
}
/*
*  stripLineBreaking : replace line breaking tags with whitespace
*/
if (!function_exists('stripLineBreaking')) {
    function stripLineBreaking($text) {

        $text = preg_replace("'<(br[^/>]*?/|hr[^/>]*?/|/(div|h[1-6]|li|p|td))>'si", ' ', $text);
        return $text;
    }
}
?>