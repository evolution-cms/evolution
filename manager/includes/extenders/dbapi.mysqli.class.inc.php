<?php

class DBAPI
{
    /**
     * @var mysqli
     */
    public $conn;
    public $config;
    public $lastQuery;
    public $isConnected;
    public $_dbconnectionmethod;

    /**
     * DBAPI constructor.
     *
     * @param string $host
     * @param string $dbase
     * @param string $uid
     * @param string $pwd
     * @param null|string $pre
     * @param string $charset
     * @param string $connection_method
     */
    public function __construct(
        $host = '',
        $dbase = '',
        $uid = '',
        $pwd = '',
        $pre = null,
        $charset = '',
        $connection_method = 'SET CHARACTER SET'
    ) {
        $this->config['host'] = $host ? $host : $GLOBALS['database_server'];
        $this->config['dbase'] = $dbase ? $dbase : $GLOBALS['dbase'];
        $this->config['user'] = $uid ? $uid : $GLOBALS['database_user'];
        $this->config['pass'] = $pwd ? $pwd : $GLOBALS['database_password'];
        $this->config['charset'] = $charset ? $charset : $GLOBALS['database_connection_charset'];
        $this->config['connection_method'] = $this->_dbconnectionmethod = (isset($GLOBALS['database_connection_method']) ? $GLOBALS['database_connection_method'] : $connection_method);
        $this->config['table_prefix'] = ($pre !== null) ? $pre : $GLOBALS['table_prefix'];
    }

    /**
     * @param string $host
     * @param string $dbase
     * @param string $uid
     * @param string $pwd
     * @return mysqli
     */
    public function connect($host = '', $dbase = '', $uid = '', $pwd = '')
    {
        $modx = evolutionCMS();
        $uid = $uid ? $uid : $this->config['user'];
        $pwd = $pwd ? $pwd : $this->config['pass'];
        $host = $host ? $host : $this->config['host'];
        $dbase = $dbase ? $dbase : $this->config['dbase'];
        $dbase = trim($dbase, '`'); // remove the `` chars
        $charset = $this->config['charset'];
        $connection_method = $this->config['connection_method'];
        $tstart = $modx->getMicroTime();
        $safe_count = 0;
        do {
            $host = explode(':', $host, 2);
            $this->conn = new mysqli($host[0], $uid, $pwd, $dbase, isset($host[1]) ? $host[1] : null);
            if ($this->conn->connect_error) {
                $this->conn = null;
                if (isset($modx->config['send_errormail']) && $modx->config['send_errormail'] !== '0') {
                    if ($modx->config['send_errormail'] <= 2) {
                        $logtitle = 'Failed to create the database connection!';
                        $request_uri = $modx->htmlspecialchars($_SERVER['REQUEST_URI']);
                        $ua = $modx->htmlspecialchars($_SERVER['HTTP_USER_AGENT']);
                        $referer = $modx->htmlspecialchars($_SERVER['HTTP_REFERER']);
                        $modx->sendmail(array(
                            'subject' => 'Missing to create the database connection! from ' . $modx->config['site_name'],
                            'body'    => "{$logtitle}\n{$request_uri}\n{$ua}\n{$referer}",
                            'type'    => 'text'
                        ));
                    }
                }
                sleep(1);
                $safe_count++;
            }
        } while (!$this->conn && $safe_count < 3);
        if ($this->conn instanceof mysqli) {
            $this->conn->query("{$connection_method} {$charset}");
            $tend = $modx->getMicroTime();
            $totaltime = $tend - $tstart;
            if ($modx->dumpSQL) {
                $modx->queryCode .= "<fieldset style='text-align:left'><legend>Database connection</legend>" . sprintf("Database connection was created in %2.4f s",
                        $totaltime) . "</fieldset><br />";
            }
            $this->conn->set_charset($this->config['charset']);
            $this->isConnected = true;
            $modx->queryTime += $totaltime;
        } else {
            $modx->messageQuit("Failed to create the database connection!");
            exit;
        }
        return $this->conn;
    }

    /**
     * @return void
     */
    public function disconnect()
    {
        $this->conn->close();
        $this->conn = null;
        $this->isConnected = false;
    }

    /**
     * @param array|string $s
     * @param int $safeCount
     * @return array|string
     */
    public function escape($s, $safeCount = 0)
    {
        $safeCount++;
        if (1000 < $safeCount) {
            exit("Too many loops '{$safeCount}'");
        }
        if ( ! ($this->conn instanceof mysqli)) {
            $this->connect();
        }
        if (is_array($s)) {
            if (count($s) === 0) {
                $s = '';
            } else {
                foreach ($s as $i => $v) {
                    $s[$i] = $this->escape($v, $safeCount);
                }
            }
        } else {
            $s = $this->conn->escape_string($s);
        }

        return $s;
    }

    /**
     * @param string|array|mysqli_result $sql
     * @param bool $watchError
     * @return bool|mysqli_result
     */
    public function query($sql, $watchError = true)
    {
        $modx = evolutionCMS();
        if ( ! ($this->conn instanceof mysqli)) {
            $this->connect();
        }
        $tStart = $modx->getMicroTime();
        if (is_array($sql)) {
            $sql = implode("\n", $sql);
        }
        $this->lastQuery = $sql;
        if (!($result = $this->conn->query($sql))) {
            if (!$watchError) {
                return false;
            }
            switch (mysqli_errno($this->conn)) {
                case 1054:
                case 1060:
                case 1061:
                case 1062:
                case 1091:
                    break;
                default:
                    $modx->messageQuit('Execution of a query to the database failed - ' . $this->getLastError(), $sql);
            }
        } else {
            $tend = $modx->getMicroTime();
            $totalTime = $tend - $tStart;
            $modx->queryTime += $totalTime;
            if ($modx->dumpSQL) {
                $debug = debug_backtrace();
                array_shift($debug);
                $debug_path = array();
                foreach ($debug as $line) {
                    $debug_path[] = $line['function'];
                }
                $debug_path = implode(' > ', array_reverse($debug_path));
                $modx->queryCode .= "<fieldset style='text-align:left'><legend>Query " . ($modx->executedQueries + 1) . " - " . sprintf("%2.2f ms",
                        $totalTime * 1000) . "</legend>";
                $modx->queryCode .= $sql . '<br><br>';
                if ($modx->event->name) {
                    $modx->queryCode .= 'Current Event  => ' . $modx->event->name . '<br>';
                }
                if ($modx->event->activePlugin) {
                    $modx->queryCode .= 'Current Plugin => ' . $modx->event->activePlugin . '<br>';
                }
                if ($modx->currentSnippet) {
                    $modx->queryCode .= 'Current Snippet => ' . $modx->currentSnippet . '<br>';
                }
                if (stripos($sql, 'select') === 0) {
                    $modx->queryCode .= 'Record Count => ' . $this->getRecordCount($result) . '<br>';
                } else {
                    $modx->queryCode .= 'Affected Rows => ' . $this->getAffectedRows() . '<br>';
                }
                $modx->queryCode .= 'Functions Path => ' . $debug_path . '<br>';
                $modx->queryCode .= "</fieldset><br />";
            }
            $modx->executedQueries++;

            return $result;
        }
        return false;
    }

    /**
     * @param string $from
     * @param string $where
     * @param string $orderBy
     * @param string $limit
     * @return bool|mysqli_result
     */
    public function delete($from, $where = '', $orderBy = '', $limit = '')
    {
        $modx = evolutionCMS();
        $out = false;
        if (!$from) {
            $modx->messageQuit("Empty \$from parameters in DBAPI::delete().");
        } else {
            $from = $this->replaceFullTableName($from);
            $where = trim($where);
            $orderBy = trim($orderBy);
            $limit = trim($limit);
            if ($where !== '' && stripos($where, 'WHERE') !== 0) {
                $where = "WHERE {$where}";
            }
            if ($orderBy !== '' && stripos($orderBy, 'ORDER BY') !== 0) {
                $orderBy = "ORDER BY {$orderBy}";
            }
            if ($limit !== '' && stripos($limit, 'LIMIT') !== 0) {
                $limit = "LIMIT {$limit}";
            }

            $out = $this->query("DELETE FROM {$from} {$where} {$orderBy} {$limit}");
        }
        return $out;
    }

    /**
     * @param string|array $fields
     * @param string|array $from
     * @param string|array $where
     * @param string $orderBy
     * @param string $limit
     * @return bool|mysqli_result
     */
    public function select($fields = "*", $from = "", $where = "", $orderBy = "", $limit = "")
    {
        $modx = evolutionCMS();

        if (is_array($fields)) {
            $fields = $this->_getFieldsStringFromArray($fields);
        }
        if (is_array($from)) {
            $from = $this->_getFromStringFromArray($from);
        }
        if (is_array($where)) {
            $where = implode(' ', $where);
        }

        if (!$from) {
            $modx->messageQuit("Empty \$from parameters in DBAPI::select().");
            exit;
        }

        $fields = $this->replaceFullTableName($fields);
        $from = $this->replaceFullTableName($from);
        $where = trim($where);
        $orderBy = trim($orderBy);
        $limit = trim($limit);
        if ($where !== '' && stripos($where, 'WHERE') !== 0) {
            $where = "WHERE {$where}";
        }
        if ($orderBy !== '' && stripos($orderBy, 'ORDER') !== 0) {
            $orderBy = "ORDER BY {$orderBy}";
        }
        if ($limit !== '' && stripos($limit, 'LIMIT') !== 0) {
            $limit = "LIMIT {$limit}";
        }

        return $this->query("SELECT {$fields} FROM {$from} {$where} {$orderBy} {$limit}");
    }

    /**
     * @param array|string $fields
     * @param $table
     * @param string $where
     * @return bool|mysqli_result
     */
    public function update($fields, $table, $where = "")
    {
        $modx = evolutionCMS();
        $out = false;
        if (!$table) {
            $modx->messageQuit('Empty '.$table.' parameter in DBAPI::update().');
        } else {
            $table = $this->replaceFullTableName($table);
            if (is_array($fields)) {
                foreach ($fields as $key => $value) {
                    if ($value === null || strtolower($value) === 'null') {
                        $f = 'NULL';
                    } else {
                        $f = "'" . $value . "'";
                    }
                    $fields[$key] = "`{$key}` = " . $f;
                }
                $fields = implode(',', $fields);
            }
            $where = trim($where);
            if ($where !== '' && stripos($where, 'WHERE') !== 0) {
                $where = 'WHERE '.$where;
            }

            return $this->query('UPDATE '.$table.' SET '.$fields.' '.$where);
        }
        return $out;
    }

    /**
     * @param string|array $fields
     * @param string $intotable
     * @param string $fromfields
     * @param string $fromtable
     * @param string $where
     * @param string $limit
     * @return mixed
     */
    public function insert($fields, $intotable, $fromfields = "*", $fromtable = "", $where = "", $limit = "")
    {
        $modx = evolutionCMS();
        $out = false;
        if (!$intotable) {
            $modx->messageQuit("Empty \$intotable parameters in DBAPI::insert().");
        } else {
            $intotable = $this->replaceFullTableName($intotable);
            if (!is_array($fields)) {
                $this->query("INSERT INTO {$intotable} {$fields}");
            } else {
                if (empty($fromtable)) {
                    $fields = "(`" . implode("`, `", array_keys($fields)) . "`) VALUES('" . implode("', '",
                            array_values($fields)) . "')";
                    $this->query("INSERT INTO {$intotable} {$fields}");
                } else {
                    $fromtable = $this->replaceFullTableName($fromtable);
                    $fields = "(" . implode(",", array_keys($fields)) . ")";
                    $where = trim($where);
                    $limit = trim($limit);
                    if ($where !== '' && stripos($where, 'WHERE') !== 0) {
                        $where = "WHERE {$where}";
                    }
                    if ($limit !== '' && stripos($limit, 'LIMIT') !== 0) {
                        $limit = "LIMIT {$limit}";
                    }
                    $this->query("INSERT INTO {$intotable} {$fields} SELECT {$fromfields} FROM {$fromtable} {$where} {$limit}");
                }
            }
            if (($lid = $this->getInsertId()) === false) {
                $modx->messageQuit("Couldn't get last insert key!");
            }

            $out = $lid;
        }
        return $out;
    }

    /**
     * @param $fields
     * @param $table
     * @param string $where
     * @return bool|mixed|mysqli_result
     */
    public function save($fields, $table, $where = '')
    { // This is similar to "replace into table".

        if ($where === '') {
            $mode = 'insert';
        } elseif ($this->getRecordCount($this->select('*', $table, $where)) === 0) {
            $mode = 'insert';
        } else {
            $mode = 'update';
        }

        return ($mode === 'insert') ? $this->insert($fields, $table) : $this->update($fields, $table, $where);
    }

    /**
     * @param mixed $rs
     * @return bool
     */
    public function isResult($rs)
    {
        return $rs instanceof mysqli_result;
    }

    /**
     * @param mysqli_result $rs
     */
    public function freeResult($rs)
    {
        $rs->free_result();
    }

    /**
     * @param mysqli_result $rs
     * @return mixed
     */
    public function numFields($rs)
    {
        return $rs->field_count;
    }

    /**
     * @param mysqli_result $rs
     * @param int $col
     * @return string|null
     */
    public function fieldName($rs, $col = 0)
    {
        $field = $rs->fetch_field_direct($col);

        return isset($field->name) ? $field->name : null;
    }

    /**
     * @param $name
     */
    public function selectDb($name)
    {
        $this->conn->select_db($name);
    }


    /**
     * @param null|mysqli $conn
     * @return mixed
     */
    public function getInsertId($conn = null)
    {
        if (! ($conn instanceof mysqli)) {
            $conn =& $this->conn;
        }

        return $conn->insert_id;
    }

    /**
     * @param null|mysqli $conn
     * @return int
     */
    public function getAffectedRows($conn = null)
    {
        if (! ($conn instanceof mysqli)) {
            $conn =& $this->conn;
        }

        return $conn->affected_rows;
    }

    /**
     * @param null|mysqli $conn
     * @return string
     */
    public function getLastError($conn = null)
    {
        if (! ($conn instanceof mysqli)) {
            $conn =& $this->conn;
        }

        return $conn->error;
    }

    /**
     * @param mysqli_result $ds
     * @return int
     */
    public function getRecordCount($ds)
    {
        return ($ds instanceof mysqli_result) ? $ds->num_rows : 0;
    }

    /**
     * @param mysqli_result $ds
     * @param string $mode
     * @return array|bool|mixed|object|stdClass
     */
    public function getRow($ds, $mode = 'assoc')
    {
        $out = false;
        if ($ds instanceof mysqli_result) {
            switch($mode){
                case 'assoc':
                    $out = $ds->fetch_assoc();
                    break;
                case 'num':
                    $out = $ds->fetch_row();
                    break;
                case 'object':
                    $out = $ds->fetch_object();
                    break;
                case 'both':
                    $out = $ds->fetch_array(MYSQLI_BOTH);
                    break;
                default:
                    $modx = evolutionCMS();
                    $modx->messageQuit("Unknown get type ($mode) specified for fetchRow - must be empty, 'assoc', 'num' or 'both'.");

            }
        }
        return $out;
    }

    /**
     * @param $name
     * @param mysqli_result|string $dsq
     * @return array
     */
    public function getColumn($name, $dsq)
    {
        $col = array();
        if ( ! ($dsq instanceof mysqli_result)) {
            $dsq = $this->query($dsq);
        }
        if ($dsq) {
            while ($row = $this->getRow($dsq)) {
                $col[] = $row[$name];
            }
        }

        return $col;
    }

    /**
     * @param mysqli_result|string $dsq
     * @return array
     */
    public function getColumnNames($dsq)
    {
        $names = array();
        if ( ! ($dsq instanceof mysqli_result)) {
            $dsq = $this->query($dsq);
        }
        if ($dsq) {
            $limit = $this->numFields($dsq);
            for ($i = 0; $i < $limit; $i++) {
                $names[] = $this->fieldName($dsq, $i);
            }
        }

        return $names;
    }

    /**
     * @param mysqli_result|string $dsq
     * @return bool|string|int
     */
    public function getValue($dsq)
    {
        $out = false;
        if ( ! ($dsq instanceof mysqli_result)) {
            $dsq = $this->query($dsq);
        }
        if ($dsq) {
            $r = $this->getRow($dsq, 'num');
            $out = is_array($r) && array_key_exists(0, $r) ? $r[0] : false;
        }

        return $out;
    }

    /**
     * @param string $table
     * @return array
     */
    public function getTableMetaData($table)
    {
        $metadata = array();
        if (!empty($table) && is_scalar($table)) {
            $sql = 'SHOW FIELDS FROM '.$table;
            if ($ds = $this->query($sql)) {
                while ($row = $this->getRow($ds)) {
                    $fieldName = $row['Field'];
                    $metadata[$fieldName] = $row;
                }
            }
        }

        return $metadata;
    }

    /**
     * @param int $timestamp
     * @param string $fieldType
     * @return false|string
     */
    public function prepareDate($timestamp, $fieldType = 'DATETIME')
    {
        $date = false;
        if (!$timestamp === false && $timestamp > 0) {
            switch ($fieldType) {
                case 'DATE' :
                    $date = date('Y-m-d', $timestamp);
                    break;
                case 'TIME' :
                    $date = date('H:i:s', $timestamp);
                    break;
                case 'YEAR' :
                    $date = date('Y', $timestamp);
                    break;
                default :
                    $date = date('Y-m-d H:i:s', $timestamp);
                    break;
            }
        }

        return $date;
    }

    /**
     * @param string|mysqli_result $rs
     * @param bool $index
     * @return array
     */
    public function makeArray($rs = '', $index = false)
    {
        $rsArray = array();
        if (!$rs) {
            return $rsArray;
        }
        $iterator = 0;
        while ($row = $this->getRow($rs)) {
            $returnIndex = $index !== false && isset($row[$index]) ? $row[$index] : $iterator;
            $rsArray[$returnIndex] = $row;
            $iterator++;
        }

        return $rsArray;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->conn->server_info;
    }

    /**
     * @param string $tableName
     * @param bool $force
     * @return string
     */
    public function replaceFullTableName($tableName, $force = false)
    {
        $tableName = trim($tableName);
        $dbase = trim($this->config['dbase'], '`');
        $prefix = $this->config['table_prefix'];
        if ((bool)$force === true) {
            $result = "`{$dbase}`.`{$prefix}{$tableName}`";
        } elseif (strpos($tableName, '[+prefix+]') !== false) {
            $result = preg_replace('@\[\+prefix\+\]([0-9a-zA-Z_]+)@', "`{$dbase}`.`{$prefix}$1`", $tableName);
        } else {
            $result = $tableName;
        }

        return $result;
    }

    /**
     * @param string $table_name
     * @return bool|mysqli_result
     */
    public function optimize($table_name)
    {
        $rs = $this->query('OPTIMIZE TABLE '.$table_name);
        if ($rs) {
            $rs = $this->query('ALTER TABLE '.$table_name);
        }

        return $rs;
    }

    /**
     * @param string $table_name
     * @return bool|mysqli_result
     */
    public function truncate($table_name)
    {
        return $this->query('TRUNCATE '.$table_name);
    }

    /**
     * @param mysqli_result $result
     * @param int $row_number
     * @return bool
     */
    public function dataSeek($result, $row_number)
    {
        return $result->data_seek($row_number);
    }

    /**
     * @param array $fields
     * @return string
     */
    public function _getFieldsStringFromArray($fields = array())
    {

        if (empty($fields)) {
            return '*';
        }

        $_ = array();
        foreach ($fields as $k => $v) {
            if ($k !== $v) {
                $_[] = $v.' as '.$k;
            } else {
                $_[] = $v;
            }
        }

        return implode(',', $_);
    }

    /**
     * @param array $tables
     * @return string
     */
    public function _getFromStringFromArray($tables = array())
    {
        $_ = array();
        foreach ($tables as $k => $v) {
            $_[] = $v;
        }

        return implode(' ', $_);
    }
}
