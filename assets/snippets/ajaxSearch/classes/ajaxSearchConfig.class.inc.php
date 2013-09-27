<?php
/* -----------------------------------------------------------------------------
* Snippet: AjaxSearch
* -----------------------------------------------------------------------------
* @package  AjaxSearchConfig
*
* @author       Coroico - www.evo.wangba.fr
* @version      1.9.3
* @date         26/09/2012
*
* Purpose:
*    The AjaxSearchConfig class contains all functions and data used to manage configuration context
*
*/

class AjaxSearchConfig {

    // public variables
    var $pgCharset;
    var $dbCharset;
    var $isAjax;
    var $cfg = array();
    var $dcfg = array();
    var $ucfg;
    var $bcfg = array();
    var $scfg = array();
    var $lang;

    // private variables
    // Conversion code name between html page character encoding and Mysql character encoding
    // Some others conversions should be added if needed. Otherwise Page charset = Database charset
    var $_pageCharset = array('utf8' => 'UTF-8', 'latin1' => 'ISO-8859-1', 'latin2' => 'ISO-8859-2', 'cp1251' => 'windows-1251');

    function AjaxSearchConfig($dcfg, $cfg) {
        global $modx;
        $this->dbCharset = $modx->db->config['charset'];
        $this->pcreModifier = ($this->dbCharset == "utf8") ? 'iu' : 'i';
        $this->dcfg = $dcfg;
        $this->cfg = $cfg;
    }
    /*
    * Init the configuration
    */
    function initConfig(&$msgErr) {
        $msgErr = '';
        if (!isset($_POST['ucfg'])) {

            $this->isAjax = false;

            $this->ucfg = $this->getUserConfig();

            $this->bcfg = array_merge($this->dcfg, (array)$this->ucfg);

            $this->scfg[DEFAULT_SITE][DEFAULT_SUBSITE] = array();
        } else {

            $this->isAjax = true;

            $this->ucfg = $this->parseUserConfig(strip_tags($_POST['ucfg']));

            $this->bcfg = array_merge($this->dcfg, (array)$this->ucfg);

            $this->cfg = $this->bcfg;

            $this->scfg[DEFAULT_SITE][DEFAULT_SUBSITE] = array();
        }

        $this->_loadLang();

        $valid = $this->_setCharset($msgErr);
        return $valid;
    }
    /*
    * Load the language file
    */
    function _loadLang() {
        $_lang = array();

        $language = 'english';
        include AS_PATH . "lang/{$language}.inc.php";

        if (($this->cfg['language'] != '') && ($this->cfg['language'] != $language)) {
            if (file_exists(AS_PATH . "lang/{$this->cfg['language']}.inc.php")) include AS_PATH . "lang/" . $this->cfg['language'] . ".inc.php";
        }
        $this->lang = $_lang;
    }
    /*
    * Display config arrays
    */
    function displayConfig(& $asUtil) {
        if ($asUtil->dbg) {
            if ($this->cfg['config']) $asUtil->dbgRecord($this->readConfigFile($this->cfg['config']), __FUNCTION__ . ' - ' . $this->cfg['config']);
            $asUtil->dbgRecord($this->cfg, __FUNCTION__ . ' - Config before parameter checking');
        }
    }
    /*
    * Set the Page charset
    */
    function _setCharset(&$msgErr) {
        $valid = false;
        $msgErr = '';

        $this->pgCharset = array_key_exists($this->dbCharset, $this->_pageCharset) ? $this->_pageCharset[$this->dbCharset] : $this->dbCharset;

        if (isset($this->dbCharset) && isset($this->_pageCharset[$this->dbCharset])) {

            if ($this->dbCharset == 'utf8' && !extension_loaded('mbstring')) {
                $msgErr = "AjaxSearch error: php_mbstring extension required";
            } else {
                if ($this->dbCharset == 'utf8' && $this->cfg['mbstring']) mb_internal_encoding("UTF-8");
                $this->pgCharset = $this->_pageCharset[$this->dbCharset];
                $valid = true;
            }
        } elseif (!isset($this->dbCharset)) {
            $msgErr = "AjaxSearch error: database_connection_charset not set. Check your MODx config file";
        } elseif (!strlen($this->dbCharset)) {
            $msgErr = "AjaxSearch error: database_connection_charset is null. Check your MODx config file";
        } else {
            // if you get this message, simply update the $pageCharset array in search.class.inc.php file
            // with the appropriate mapping between Mysql Charset and Html charset
            // eg: 'latin2' => 'ISO-8859-2'
            $msgErr = "AjaxSearch error: unknown database_connection_charset = {$this->dbCharset}<br />Add the appropriate Html charset mapping in the ajaxSearchConfig.class.inc.php file";
        }
        return $valid;
    }
    /*
    * Save the current configuration
    */
    function saveConfig($site, $subsite) {
        if (!isset($this->scfg[$site][$subsite])) $this->scfg[$site][$subsite] = array();
        foreach ($this->cfg as $key => $value) {
            if (!isset($this->bcfg[$key]) || ($this->bcfg[$key] != $value)) $this->scfg[$site][$subsite][$key] = $value;
        }
    }
    /*
    * Restore a named configuration
    */
    function restoreConfig($site, $subsite) {
        if (isset($this->scfg[$site][$subsite])) $this->cfg = array_merge($this->bcfg, $this->scfg[$site][$subsite]);
        else $this->cfg = array_merge($this->bcfg, $this->scfg[DEFAULT_SITE][DEFAULT_SUBSITE]);
    }
    /*
    * Choose the appropriate configuration for displaying results
    */
    function chooseConfig($site, $subsite, $display) {
        $s = ($display != MIXED) ? $site : DEFAULT_SITE;
        $ss = ($display != MIXED) ? $subsite : DEFAULT_SUBSITE;
        $this->restoreConfig($s, $ss);
    }
    /*
    * Create a config by merging site and category config
    */
    function addConfigFromCateg($site, $categ, $ctg) {
        if (($site) && ($categ) && (!isset($this->scfg[$site][$categ]))) {
            if (isset($this->scfg[$site][$DEFAULT_SUBSITE])) $s = $this->scfg[$site][$DEFAULT_SUBSITE];
            else $s = array();
            $this->scfg[$site][$categ] = array_merge((array)$s, (array)$ctg);
        }
    }
    /*
    * Get the non default configuration (advSearch and subSearch excepted)
    */
    function getUserConfig() {
        $ucfg = array();
        foreach ($this->cfg as $key => $value) {
            if ($key != 'subSearch' && $value != $this->dcfg[$key]) $ucfg[$key] = $this->cfg[$key];
        }
        return $ucfg;
    }
    /*
    * Parse the non default configuration from string
    */
    function parseUserConfig($strUcfg) {
        $ucfg = array();
        $pattern = '/&([^=]*)=`([^`]*)`/';
        preg_match_all($pattern, $strUcfg, $out);
        foreach ($out[1] as $key => $values) $ucfg[$out[1][$key]] = $out[2][$key];
        return $ucfg;
    }
    /*
    * Set the AjaxSearch snippet call
    */
    function setAsCall($ucfg) {
        $tpl = "&%s=`%s` ";
        $asCall = '';
        foreach ($ucfg as $key => $value) $asCall.= sprintf($tpl, $key, $value);
        return $asCall;
    }
    /*
    *  Read config file
    */
    function readConfigFile($config) {
        global $modx;
        $configFile = (substr($config, 0, 6) != "@FILE:") ? AS_PATH . "configs/$config.config.php" : $modx->config['base_path'] . trim(substr($config, 6, strlen($config)-6));
        $fh = fopen($configFile, 'r');
        $output = fread($fh, filesize($configFile));
        fclose($fh);
        return "\n" . $output;
    }
}
?>