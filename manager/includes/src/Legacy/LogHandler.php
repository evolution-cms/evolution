<?php namespace EvolutionCMS\Legacy;

/**
 * logger class.
 *
 * Usage:
 *
 * include_once "log.class.inc.php"; // include_once the class
 * $log = new logHandler;  // create the object
 * $log->initAndWriteLog($msg); // write $msg to log, and populate all other fields as best as possible
 * $log->initAndWriteLog($msg, $internalKey, $username, $action, $id, $itemname); // write $msg and other data to log
 */
class LogHandler
{
    /**
     * Single variable for a log entry
     *
     * @var array
     */
    public $entry = array();

    /**
     * @param string $msg
     * @param string $internalKey
     * @param string $username
     * @param string $action
     * @param string $itemid
     * @param string $itemname
     *
     * @return void
     */
    public function initAndWriteLog(
        $msg = "",
        $internalKey = "",
        $username = "",
        $action = "",
        $itemid = "",
        $itemname = ""
    ) {
        $modx = evolutionCMS();
        $this->entry['msg'] = $msg; // writes testmessage to the object
        $this->entry['action'] = empty($action) ? $modx->getManagerApi()->action : $action;    // writes the action to the object

        // User Credentials
        $this->entry['internalKey'] = $internalKey == "" ? $modx->getLoginUserID() : $internalKey;
        $this->entry['username'] = $username == "" ? $modx->getLoginUserName() : $username;

        $this->entry['itemId'] = (empty($itemid) && isset($_REQUEST['id'])) ? (int)$_REQUEST['id'] : $itemid;  // writes the id to the object
        if ($this->entry['itemId'] == 0) {
            $this->entry['itemId'] = "-";
        } // to stop items having id 0

        $this->entry['itemName'] = ($itemname == "" && isset($_SESSION['itemname'])) ? $_SESSION['itemname'] : $itemname; // writes the id to the object
        if ($this->entry['itemName'] == "") {
            $this->entry['itemName'] = "-";
        } // to stop item name being empty

        $this->writeToLog();

        return;
    }

    /**
     * function to write to the log collects all required info, and writes it to the logging table
     *
     * @return void
     */
    public function writeToLog()
    {
        $modx = evolutionCMS();
        $tbl_manager_log = $modx->getDatabase()->getFullTableName('manager_log');

        if ($this->entry['internalKey'] == "") {
            $modx->webAlertAndQuit("Logging error: internalKey not set.");
        }
        if (empty($this->entry['action'])) {
            $modx->webAlertAndQuit("Logging error: action not set.");
        }
        if ($this->entry['msg'] == "") {
            include_once "actionlist.inc.php";
            $this->entry['msg'] = getAction($this->entry['action'], $this->entry['itemId']);
            if ($this->entry['msg'] == "") {
                $modx->webAlertAndQuit("Logging error: couldn't find message to write to log.");
            }
        }

        $fields['timestamp'] = time();
        $fields['internalKey'] = $modx->getDatabase()->escape($this->entry['internalKey']);
        $fields['username'] = $modx->getDatabase()->escape($this->entry['username']);
        $fields['action'] = $this->entry['action'];
        $fields['itemid'] = $this->entry['itemId'];
        $fields['itemname'] = $modx->getDatabase()->escape($this->entry['itemName']);
        $fields['message'] = $modx->getDatabase()->escape($this->entry['msg']);
        $fields['ip'] = $this->getUserIP();
        $fields['useragent'] = $_SERVER['HTTP_USER_AGENT'];

        $insert_id = $modx->getDatabase()->insert($fields, $tbl_manager_log);
        if (!$insert_id) {
            $modx->messageQuit("Logging error: couldn't save log to table! Error code: " . $modx->getDatabase()->getLastError());
        } else {
            $limit = (isset($modx->config['manager_log_limit'])) ? (int)$modx->config['manager_log_limit'] : 3000;
            $trim = (isset($modx->config['manager_log_trim'])) ? (int)$modx->config['manager_log_trim'] : 100;
            if (($insert_id % $trim) === 0) {
                $modx->rotate_log('manager_log', $limit, $trim);
            }
        }
    }

    private function getUserIP() {
        if( array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ) {
            if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',')>0) {
                $addr = explode(",",$_SERVER['HTTP_X_FORWARDED_FOR']);
                return trim($addr[0]);
            } else {
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
        }
        else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
}
