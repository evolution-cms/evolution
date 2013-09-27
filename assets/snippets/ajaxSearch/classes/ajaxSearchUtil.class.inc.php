<?php
/* -----------------------------------------------------------------------------
* Snippet: AjaxSearch
* -----------------------------------------------------------------------------
* @package  AjaxSearchUtil
*
* @author       Coroico - www.evo.wangba.fr
* @version      1.9.3
* @date         26/09/2012
*
* Purpose:
*    The AjaxSearchUtil class contains some util methods
*
*/

define('AS_DBGDIR', AS_PATH . 'debug');
define('AS_DBGFILE', 'ajaxSearch_log.txt');

class AjaxSearchUtil {

    //public variables
    var $level = 0;  // debug level
    var $tstart;     // start time
    var $dbg;        // first level of debuging
    var $dbgTpl;     // debuging of templates
    var $dbgRes;     // debuging of results

    //private variables
    var $_dbgFd;
    var $_current_pcre_backtrack;

    function ajaxSearchUtil($level=0, $version, $tstart, &$msgErr) {
        global $modx;
        $this->level = (abs($level) > 0 && abs($level) < 4) ? $level : 0;
        $this->dbg = ($this->level > 0);
        $this->dbgRes = ($this->level > 1);
        $this->dbgTpl = ($this->level > 2);
        $this->tstart = $tstart;

        $msgErr = '';
        $header = 'AjaxSearch ' . $version . ' - Php' . phpversion() . ' - MySql ' . (method_exists($modx->db, 'getVersion') ? $modx->db->getVersion() : mysql_get_server_info());
        if ($this->level > 0 && $level < 4) { // debug trace in a file
            $isWriteable = is_writeable(AS_DBGDIR);
            if ($isWriteable) {
                $dbgFile = AS_DBGDIR . '/' . AS_DBGFILE;
                $this->_dbgFd = fopen($dbgFile, 'w+');
                $this->dbgRecord($header);
                fclose($this->_dbgFd);
                $this->_dbgFd = fopen($dbgFile, 'a+');
            }
            else {
                $msgErr = "<br /><h3>AjaxSearch error: to use the debug mode, " . AS_DBGDIR . " should be a writable directory.";
                $msgErr .= " Change the permissions of this directory.</h3><br />";
            }
        }
    }
    /*
    *  Set Debug log record
    *
    *  @access public
    */
    function dbgRecord() {
        $args = func_get_args();
        if ($this->level > 0) {
            // write trace in a file
            $when = date('[j-M-y h:i:s] ');
            $etime = $this->getElapsedTime();
            $memory = sprintf("%.2fMb",memory_get_usage()/(1024*1024))." > ";
            $nba = count($args);
            $result = $when . " " . $etime . "  " . $memory;
            if ($nba > 1) {
                $result.= $args[1] . " : ";
            }
            if (is_array($args[0])) {
                $result.= print_r($args[0], true) . "\n";
            } else $result.= $args[0] . "\n";
            fwrite($this->_dbgFd, $result);
            return true;
        }
        return;
    }
    /*
    * Returns the elapsed time between the current time and tstart
    *
    * @access public
    * @param timestamp $start starting time
    * @return string Returns the elapsed time
    */
    function getElapsedTime($start=0) {
        list($usec, $sec)= explode(' ', microtime());
        $tend= (float) $usec + (float) $sec;
        if ($start) $eTime= ($tend - $start);
        else $eTime= ($tend - $this->tstart);
        $etime = sprintf("%.4fs",$eTime);
        return $etime;
    }
    /*
    * Change the current PCRE Backtrack limit
    *
    * @access public
    * @param int $backtrackLimit PCRE backtrack limit
    */
    function setBacktrackLimit($backtrackLimit) {
        $this->_current_pcre_backtrack = ini_get('pcre.backtrack_limit');
        if ($dbg) $this->dbgRecord($current_pcre_backtrack, "AjaxSearch - pcre.backtrack_limit");
        ini_set( 'pcre.backtrack_limit', $backtrackLimit);
    }
    /*
    * Restore the initial PCRE Backtrack limit
    *
    * @access public
    */
    function restoreBacktrackLimit() {
        ini_set( 'pcre.backtrack_limit', $this->_current_pcre_backtrack );
    }
}
?>