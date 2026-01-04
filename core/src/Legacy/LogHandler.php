<?php namespace EvolutionCMS\Legacy;

use EvolutionCMS\Models\ManagerLog;

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
    public $entry = [];
    protected static $actions = [];


    /**
     * @param string $actionId
     * @param string $itemid
     * @return string
     */
    public static function getAction($actionId, $itemid = '')
    {
        if (empty(self::$actions)) {
            self::$actions = include EVO_CORE_PATH . 'factory/actionlist.php';
        }

        $text = get_by_key(self::$actions, $actionId, 'Idle (unknown)');

        if ($actionId == 112) {
            if (isset($_SESSION['itemaction']) && trim($_SESSION['itemaction'])) {
                $text = $_SESSION['itemaction'];
            } else {
                $text .= ($itemid != 0 ? ' ' . $itemid : '');
            }
        }

        return sprintf($text, $itemid);
    }
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
        $this->entry['msg'] = $msg; // writes testmessage to the object
        $this->entry['action'] = empty($action) ? evo()->getManagerApi()->action : $action; // writes the action to the object

        // User Credentials
        $this->entry['internalKey'] = $internalKey == "" ? evo()->getLoginUserID() : $internalKey;
        $this->entry['username'] = $username == "" ? evo()->getLoginUserName() : $username;

        $this->entry['itemId'] = (empty($itemid) && isset($_REQUEST['id'])) ? (int)$_REQUEST['id'] : $itemid; // writes the id to the object
        if ($this->entry['itemId'] == 0) {
            if (isset($_REQUEST['i']) && (int)$_REQUEST['i'] > 0) {
                $this->entry['itemId'] = (int)$_REQUEST['i'];
            } else {
                $this->entry['itemId'] = "-";
            }
        } // to stop items having id 0

        $this->entry['itemName'] = ($itemname == "" && isset($_SESSION['itemname'])) ? $_SESSION['itemname'] : $itemname; // writes the id to the object
        if ($this->entry['itemName'] == "") {
            $this->entry['itemName'] = "-";
        } // to stop item name being empty

        $this->writeToLog();
    }

    /**
     * function to write to the log collects all required info, and writes it to the logging table
     *
     * @return void
     */
    public function writeToLog()
    {
        if ($this->entry['internalKey'] == "") {
            evo()->webAlertAndQuit("Logging error: internalKey not set.");
        }
        if (empty($this->entry['action'])) {
            evo()->webAlertAndQuit("Logging error: action not set.");
        }
        if ($this->entry['msg'] == "") {
            $this->entry['msg'] = self::getAction($this->entry['action'], $this->entry['itemName']);
            if ($this->entry['msg'] == "") {
                evo()->webAlertAndQuit("Logging error: couldn't find message to write to log.");
            }
        }

        $fields['timestamp'] = time();
        $fields['internalKey'] = $this->entry['internalKey'];
        $fields['username'] = $this->entry['username'];
        $fields['action'] = $this->entry['action'];
        $fields['itemid'] = $this->entry['itemId'];
        $fields['itemname'] = $this->entry['itemName'];
        $fields['message'] = $this->entry['msg'];
        $fields['ip'] = $this->getUserIP();
        $fields['useragent'] = $_SERVER['HTTP_USER_AGENT'];

        $insert_id = ManagerLog::query()->create($fields)->getKey();
        if (!$insert_id) {
            evo()->getService('ExceptionHandler')
                ->messageQuit(
                    "Logging error: couldn't save log to table! Error code: " . evo()->getDatabase()->getLastError()
                );
        } else {
            $limit = (isset(evo()->config['manager_log_limit'])) ? (int)evo()->getConfig('manager_log_limit') : 3000;
            $trim = (isset(evo()->config['manager_log_trim'])) ? (int)evo()->getConfig('manager_log_trim') : 100;
            if (($insert_id % $trim) === 0) {
                evo()->rotate_log('manager_log', $limit, $trim);
            }
        }
    }

    private function getUserIP()
    {
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') > 0) {
                $addr = explode(",",$_SERVER['HTTP_X_FORWARDED_FOR']);
                return trim($addr[0]);
            } else {
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
}
