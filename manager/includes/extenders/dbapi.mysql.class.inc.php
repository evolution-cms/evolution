<?php

/* Datbase API object of MySQL
 * Written by Raymond Irving June, 2005
 *
 */

class DBAPI {

   var $conn;
   var $config;
   var $lastQuery;
   var $isConnected;

   /**
    * @name:  DBAPI
    *
    */
   function __construct($host='',$dbase='', $uid='',$pwd='',$pre=NULL,$charset='',$connection_method='SET CHARACTER SET') {
      $this->config['host'] = $host ? $host : $GLOBALS['database_server'];
      $this->config['dbase'] = $dbase ? $dbase : $GLOBALS['dbase'];
      $this->config['user'] = $uid ? $uid : $GLOBALS['database_user'];
      $this->config['pass'] = $pwd ? $pwd : $GLOBALS['database_password'];
      $this->config['charset'] = $charset ? $charset : $GLOBALS['database_connection_charset'];
      $this->config['connection_method'] =  $this->_dbconnectionmethod = (isset($GLOBALS['database_connection_method']) ? $GLOBALS['database_connection_method'] : $connection_method);
      $this->config['table_prefix'] = ($pre !== NULL) ? $pre : $GLOBALS['table_prefix'];
      $this->initDataTypes();
   }

   /**
    * @name:  initDataTypes
    * @desc:  called in the constructor to set up arrays containing the types
    *         of database fields that can be used with specific PHP types
    */
   function initDataTypes() {
      $this->dataTypes['numeric'] = array (
         'INT',
         'INTEGER',
         'TINYINT',
         'BOOLEAN',
         'DECIMAL',
         'DEC',
         'NUMERIC',
         'FLOAT',
         'DOUBLE PRECISION',
         'REAL',
         'SMALLINT',
         'MEDIUMINT',
         'BIGINT',
         'BIT'
      );
      $this->dataTypes['string'] = array (
         'CHAR',
         'VARCHAR',
         'BINARY',
         'VARBINARY',
         'TINYBLOB',
         'BLOB',
         'MEDIUMBLOB',
         'LONGBLOB',
         'TINYTEXT',
         'TEXT',
         'MEDIUMTEXT',
         'LONGTEXT',
         'ENUM',
         'SET'
      );
      $this->dataTypes['date'] = array (
         'DATE',
         'DATETIME',
         'TIMESTAMP',
         'TIME',
         'YEAR'
      );
   }

   /**
    * @name:  connect
    *
    */
   function connect($host = '', $dbase = '', $uid = '', $pwd = '', $persist = 0) {
      global $modx;
      $uid = $uid ? $uid : $this->config['user'];
      $pwd = $pwd ? $pwd : $this->config['pass'];
      $host = $host ? $host : $this->config['host'];
      $dbase = $dbase ? $dbase : $this->config['dbase'];
      $charset = $this->config['charset'];
      $connection_method = $this->config['connection_method'];
      $tstart = $modx->getMicroTime();
      $safe_count = 0;
      while(!$this->conn && $safe_count<3)
      {
          if($persist!=0) $this->conn = mysql_pconnect($host, $uid, $pwd);
          else            $this->conn = mysql_connect($host, $uid, $pwd, true);
          
          if(!$this->conn)
          {
            if(isset($modx->config['send_errormail']) && $modx->config['send_errormail'] !== '0')
            {
               if($modx->config['send_errormail'] <= 2)
               {
                  $logtitle    = 'Failed to create the database connection!';
                  $request_uri = $modx->htmlspecialchars($_SERVER['REQUEST_URI']);
                  $ua          = $modx->htmlspecialchars($_SERVER['HTTP_USER_AGENT']);
                  $referer     = $modx->htmlspecialchars($_SERVER['HTTP_REFERER']);

                  $modx->sendmail(array(
					  'subject' => 'Missing to create the database connection! from ' . $modx->config['site_name'],
					  'body' => "{$logtitle}\n{$request_uri}\n{$ua}\n{$referer}",
					  'type' => 'text')
				  );
               }
            }
            sleep(1);
            $safe_count++;
          }
      }
      if (!$this->conn) {
         $modx->messageQuit("Failed to create the database connection!");
         exit;
      } else {
         $dbase = trim($dbase,'`'); // remove the `` chars
         if (!@ mysql_select_db($dbase, $this->conn)) {
            $modx->messageQuit("Failed to select the database '" . $dbase . "'!");
            exit;
         }
         @mysql_query("{$connection_method} {$charset}", $this->conn);
         $tend = $modx->getMicroTime();
         $totaltime = $tend - $tstart;
         if ($modx->dumpSQL) {
            $modx->queryCode .= "<fieldset style='text-align:left'><legend>Database connection</legend>" . sprintf("Database connection was created in %2.4f s", $totaltime) . "</fieldset><br />";
         }
            if (function_exists('mysql_set_charset')) {
                mysql_set_charset($this->config['charset']);
            } else {
                @mysql_query("SET NAMES {$this->config['charset']}", $this->conn);
            }
         $this->isConnected = true;
         // FIXME (Fixed by line below):
         // this->queryTime = this->queryTime + $totaltime;
         $modx->queryTime += $totaltime;
      }
   }

   /**
    * @name:  disconnect
    *
    */
   function disconnect() {
      @ mysql_close($this->conn);
      $this->conn = null;
      $this->isConnected = false;
   }

   function escape($s, $safecount=0) {
      
      $safecount++;
      if(1000<$safecount) exit("Too many loops '{$safecount}'");
      
      if (empty ($this->conn) || !is_resource($this->conn)) {
         $this->connect();
       }
       
      if(is_array($s)) {
          if(count($s) === 0) $s = '';
          else {
              foreach($s as $i=>$v) {
                  $s[$i] = $this->escape($v,$safecount);
              }
          }
      }
      else $s = mysql_real_escape_string($s, $this->conn);
          return $s;
   }

   /**
    * @name:  query
    * @desc:  Mainly for internal use.
    * Developers should use select, update, insert, delete where possible
    */
   function query($sql,$watchError=true) {
      global $modx;
      if (empty ($this->conn) || !is_resource($this->conn)) {
         $this->connect();
      }
      $tstart = $modx->getMicroTime();
      if(is_array($sql)) $sql = join("\n", $sql);
      $this->lastQuery = $sql;
      if (!$result = @ mysql_query($sql, $this->conn)) {
         if(!$watchError) return;
            switch(mysql_errno()) {
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
         $totaltime = $tend - $tstart;
         $modx->queryTime = $modx->queryTime + $totaltime;
         if ($modx->dumpSQL) {
            $debug = debug_backtrace();
            array_shift($debug);	
            $debug_path = array();
            foreach ($debug as $line) $debug_path[] = $line['function'];
            $debug_path = implode(' > ',array_reverse($debug_path));
            $modx->queryCode .= "<fieldset style='text-align:left'><legend>Query " . ($modx->executedQueries + 1) . " - " . sprintf("%2.2f ms", $totaltime*1000) . "</legend>";
            $modx->queryCode .= $sql . '<br><br>';
            if ($modx->event->name) $modx->queryCode .= 'Current Event  => ' . $modx->event->name . '<br>';
            if ($modx->event->activePlugin) $modx->queryCode .= 'Current Plugin => ' . $modx->event->activePlugin . '<br>';
            if ($modx->currentSnippet) $modx->queryCode .= 'Current Snippet => ' . $modx->currentSnippet . '<br>';
            if (stripos($sql, 'select')===0) $modx->queryCode .= 'Record Count => ' . $this->getRecordCount($result) . '<br>';
            else $modx->queryCode .= 'Affected Rows => ' . $this->getAffectedRows() . '<br>';
            $modx->queryCode .= 'Functions Path => ' . $debug_path . '<br>';
            $modx->queryCode .= "</fieldset><br />";
         }
         $modx->executedQueries = $modx->executedQueries + 1;
         return $result;
      }
   }

   /**
    * @name:  delete
    *
    */
   function delete($from, $where='', $orderby='', $limit = '') {
      global $modx;
      if (!$from)
         $modx->messageQuit("Empty \$from parameters in DBAPI::delete().");
      else {
         $from = $this->replaceFullTableName($from);
         $where   = trim($where);
         $orderby = trim($orderby);
         $limit   = trim($limit);
         if($where!==''   && stripos($where,   'WHERE')!==0)    $where   = "WHERE {$where}";
         if($orderby!=='' && stripos($orderby, 'ORDER BY')!==0) $orderby = "ORDER BY {$orderby}";
         if($limit!==''   && stripos($limit,   'LIMIT')!==0)    $limit   = "LIMIT {$limit}";
         return $this->query("DELETE FROM {$from} {$where} {$orderby} {$limit}");
      }
   }

   /**
    * @name:  select
    *
    */
   function select($fields = "*", $from = "", $where = "", $orderby = "", $limit = "") {
      global $modx;
      
      if(is_array($fields)) $fields = $this->_getFieldsStringFromArray($fields);
      if(is_array($from))   $from   = $this->_getFromStringFromArray($from);
      if(is_array($where))  $where  = join(' ', $where);
      
      if (!$from) {
         $modx->messageQuit("Empty \$from parameters in DBAPI::select().");
         exit;
      }
      
      $fields = $this->replaceFullTableName($fields);
      $from = $this->replaceFullTableName($from);
      $where   = trim($where);
      $orderby = trim($orderby);
      $limit   = trim($limit);
      if($where!==''   && stripos($where,'WHERE')!==0)   $where   = "WHERE {$where}";
      if($orderby!=='' && stripos($orderby,'ORDER')!==0) $orderby = "ORDER BY {$orderby}";
      if($limit!==''   && stripos($limit,'LIMIT')!==0)   $limit   = "LIMIT {$limit}";
      return $this->query("SELECT {$fields} FROM {$from} {$where} {$orderby} {$limit}");
   }

   /**
    * @name:  update
    *
    */
   function update($fields, $table, $where = "") {
      global $modx;
      if (!$table)
         $modx->messageQuit("Empty \$table parameter in DBAPI::update().");
      else {
         $table = $this->replaceFullTableName($table);
         if (is_array($fields)) {
			 foreach ($fields as $key => $value) {
				 if(is_null($value) || strtolower($value) === 'null'){
					 $flds = 'NULL';
				 }else{
					 $flds = "'" . $value . "'";
				 }
				 $fields[$key] = "`{$key}` = ".$flds;
			 }
            $fields = implode(",", $fields);
         }
         $where = trim($where);
         if($where!=='' && stripos($where, 'WHERE')!==0) $where = "WHERE {$where}";
         return $this->query("UPDATE {$table} SET {$fields} {$where}");
      }
   }

   /**
    * @name:  insert
    * @desc:  returns either last id inserted or the result from the query
    */
   function insert($fields, $intotable, $fromfields = "*", $fromtable = "", $where = "", $limit = "") {
      global $modx;
      if (!$intotable)
         $modx->messageQuit("Empty \$intotable parameters in DBAPI::insert().");
      else {
         $intotable = $this->replaceFullTableName($intotable);
         if (!is_array($fields)) {
            $this->query("INSERT INTO {$intotable} {$fields}");
         } else {
            if (empty($fromtable)) {
               $fields = "(`".implode("`, `", array_keys($fields))."`) VALUES('".implode("', '", array_values($fields))."')";
               $rt = $this->query("INSERT INTO {$intotable} {$fields}");
            } else {
               if (version_compare($this->getVersion(),"4.0.14")>=0) {
                  $fromtable = $this->replaceFullTableName($fromtable);
                  $fields = "(".implode(",", array_keys($fields)).")";
                  $where = trim($where);
                  $limit = trim($limit);
                  if($where!=='' && stripos($where, 'WHERE')!==0) $where = "WHERE {$where}";
                  if($limit!=='' && stripos($limit, 'LIMIT')!==0) $limit = "LIMIT {$limit}";
                  $rt = $this->query("INSERT INTO {$intotable} {$fields} SELECT {$fromfields} FROM {$fromtable} {$where} {$limit}");
               } else {
                  $ds = $this->select($fromfields, $fromtable, $where, '', $limit);
                  while ($row = $this->getRow($ds)) {
                     $fields = "(".implode(",", array_keys($fields)).") VALUES('".implode("', '", $row)."')";
                     $rt = $this->query("INSERT INTO {$intotable} {$fields}");
                  }
               }
            }
         }
         if (($lid = $this->getInsertId())===false) $modx->messageQuit("Couldn't get last insert key!");
         return $lid;
      }
   }
   
    function save($fields, $table, $where='') {
        
        if($where === '')                                                  $mode = 'insert';
        elseif($this->getRecordCount($this->select('*',$table,$where))==0) $mode = 'insert';
        else                                                               $mode = 'update';
        
        if($mode==='insert') return $this->insert($fields, $table);
        else                 return $this->update($fields, $table, $where);
    }
    
   /**
    * @name:  isResult
    *
    */
   function isResult($rs) {
      return is_resource($rs);
   }

   /**
    * @name:  freeResult
    *
    */
   function freeResult($rs) {
      mysql_free_result($rs);
   }
   
   /**
    * @name:  numFields
    *
    */
   function numFields($rs) {
      return mysql_num_fields($rs);
   }
   
   /**
    * @name:  fieldName
    *
    */
   function fieldName($rs,$col=0) {
      return mysql_field_name($rs,$col);
   }
   
    /**
    * @name:  selectDb
    *
    */
   function selectDb($name) {
      mysql_select_db($name);
   }
   

   /**
    * @name:  getInsertId
    *
    */
   function getInsertId($conn=NULL) {
      if( !is_resource($conn)) $conn =& $this->conn;
      return mysql_insert_id($conn);
   }

   /**
    * @name:  getAffectedRows
    *
    */
   function getAffectedRows($conn=NULL) {
      if (!is_resource($conn)) $conn =& $this->conn;
      return mysql_affected_rows($conn);
   }

   /**
    * @name:  getLastError
    *
    */
   function getLastError($conn=NULL) {
      if (!is_resource($conn)) $conn =& $this->conn;
      return mysql_error($conn);
   }

   /**
    * @name:  getRecordCount
    *
    */
   function getRecordCount($ds) {
      return (is_resource($ds)) ? mysql_num_rows($ds) : 0;
   }

   /**
    * @name:  getRow
    * @desc:  returns an array of column values
    * @param: $dsq - dataset
    *
    */
   function getRow($ds, $mode = 'assoc') {
      if (is_resource($ds)) {
         if ($mode == 'assoc') {
            return mysql_fetch_assoc($ds);
         }
         elseif ($mode == 'num') {
            return mysql_fetch_row($ds);
         }
		 elseif ($mode == 'object') {
            return mysql_fetch_object($ds);
         }
         elseif ($mode == 'both') {
            return mysql_fetch_array($ds, MYSQL_BOTH);
         } else {
            global $modx;
            $modx->messageQuit("Unknown get type ($mode) specified for fetchRow - must be empty, 'assoc', 'num' or 'both'.");
         }
      }
   }

   /**
    * @name:  getColumn
    * @desc:  returns an array of the values found on colun $name
    * @param: $dsq - dataset or query string
    */
   function getColumn($name, $dsq) {
      if (!is_resource($dsq))
         $dsq = $this->query($dsq);
      if ($dsq) {
         $col = array ();
         while ($row = $this->getRow($dsq)) {
            $col[] = $row[$name];
         }
         return $col;
      }
   }

   /**
    * @name:  getColumnNames
    * @desc:  returns an array containing the column $name
    * @param: $dsq - dataset or query string
    */
   function getColumnNames($dsq) {
      if (!is_resource($dsq))
         $dsq = $this->query($dsq);
      if ($dsq) {
         $names = array ();
         $limit = mysql_num_fields($dsq);
         for ($i = 0; $i < $limit; $i++) {
            $names[] = mysql_field_name($dsq, $i);
         }
         return $names;
      }
   }

   /**
    * @name:  getValue
    * @desc:  returns the value from the first column in the set
    * @param: $dsq - dataset or query string
    */
   function getValue($dsq) {
      if (!is_resource($dsq))
         $dsq = $this->query($dsq);
      if ($dsq) {
         $r = $this->getRow($dsq, "num");
         return $r[0];
      }
   }

   /**
    * @name:  getTableMetaData
    * @desc:  returns an array of MySQL structure detail for each column of a
    *         table
    * @param: $table: the full name of the database table
    */
   function getTableMetaData($table) {
      $metadata = false;
      if (!empty ($table)) {
         $sql = "SHOW FIELDS FROM $table";
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
    * @name:  prepareDate
    * @desc:  prepares a date in the proper format for specific database types
    *         given a UNIX timestamp
    * @param: $timestamp: a UNIX timestamp
    * @param: $fieldType: the type of field to format the date for
    *         (in MySQL, you have DATE, TIME, YEAR, and DATETIME)
    */
   function prepareDate($timestamp, $fieldType = 'DATETIME') {
      $date = '';
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
   * @name:  makeArray
   * @desc:  turns a recordset into a multidimensional array
   * @return: an array of row arrays from recordset, or empty array
   *          if the recordset was empty, returns false if no recordset
   *          was passed
   * @param: $rs Recordset to be packaged into an array
   */
	function makeArray($rs='',$index=false){
		if (!$rs) return false;
		$rsArray = array();
		$iterator = 0;
		while ($row = $this->getRow($rs)) {
			$returnIndex = $index !== false && isset($row[$index]) ? $row[$index] : $iterator;
			$rsArray[$returnIndex] = $row;
			$iterator++;
		}
		return $rsArray;
	}
   
   /**
    * @name	getVersion
    * @desc	returns a string containing the database server version
    *
    * @return string
    */
   function getVersion() {
       return mysql_get_server_info();
   }
   
   /**
    * @name replaceFullTableName
    * @desc  Get full table name. Append table name and table prefix.
    * 
    * @param string $str
    * @return string 
    */
   function replaceFullTableName($str,$force=null) {
       
       $str = trim($str);
       $dbase  = trim($this->config['dbase'],'`');
       $prefix = $this->config['table_prefix'];
       if(!empty($force))
       {
           $result = "`{$dbase}`.`{$prefix}{$str}`";
       }
       elseif(strpos($str,'[+prefix+]')!==false)
       {
           $result = preg_replace('@\[\+prefix\+\]([0-9a-zA-Z_]+)@', "`{$dbase}`.`{$prefix}$1`", $str);
       }
       else $result = $str;
       
       return $result;
   }
   
   function optimize($table_name)
   {
       $rs = $this->query("OPTIMIZE TABLE {$table_name}");
       if($rs) $rs = $this->query("ALTER TABLE {$table_name}");
       return $rs;
   }
   
   function truncate($table_name)
   {
       $rs = $this->query("TRUNCATE {$table_name}");
       return $rs;
   }

  function dataSeek($result, $row_number) {
    return mysql_data_seek($result, $row_number);
  }
  
    function _getFieldsStringFromArray($fields=array()) {
        
        if(empty($fields)) return '*';
        
        $_ = array();
        foreach($fields as $k=>$v) {
            if($k!==$v) $_[] = "{$v} as {$k}";
            else        $_[] = $v;
        }
        return join(',', $_);
    }
    
    function _getFromStringFromArray($tables=array()) {
        $_ = array();
        foreach($tables as $k=>$v) {
            $_[] = $v;
        }
        return join(' ', $_);
    }
}

