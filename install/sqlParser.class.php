<?php

// MySQL Dump Parser
// SNUFFKIN/ Alex 2004

class SqlParser {
	public $host;
	public $dbname;
	public $prefix;
	public $user;
	public $password;
	public $mysqlErrors;
	public $conn;
	public $installFailed;
	public $sitename;
	public $adminname;
	public $adminemail;
	public $adminpass;
	public $managerlanguage;
	public $mode;
	public $fileManagerPath;
	public $imgPath;
	public $imgUrl;
	public $dbMODx;
	public $dbVersion;
    public $connection_charset;
    public $connection_method;
    public $ignoreDuplicateErrors;
    public $autoTemplateLogic;
    public $database_collation;

	public function __construct($host, $user, $password, $db, $prefix='modx_', $adminname, $adminemail, $adminpass, $connection_charset= 'utf8', $managerlanguage='english', $connection_method = 'SET CHARACTER SET', $auto_template_logic = 'parent') {
		$this->host = $host;
		$this->dbname = $db;
		$this->prefix = $prefix;
		$this->user = $user;
		$this->password = $password;
		$this->adminpass = $adminpass;
		$this->adminname = $adminname;
		$this->adminemail = $adminemail;
		$this->connection_charset = $connection_charset;
		$this->connection_method = $connection_method;
		$this->ignoreDuplicateErrors = false;
		$this->managerlanguage = $managerlanguage;
        $this->autoTemplateLogic = $auto_template_logic;
	}

	public function connect() {
        $host = explode(':', $this->host, 2);
		$this->conn = mysqli_connect($host[0], $this->user, $this->password,'', isset($host[1]) ? $host[1] : null);
		mysqli_select_db($this->conn, $this->dbname);
		if (function_exists('mysqli_set_charset')) mysqli_set_charset($this->conn, $this->connection_charset);

		$this->dbVersion = 3.23; // assume version 3.23
		if(function_exists("mysqli_get_server_info")) {
			$ver = mysqli_get_server_info($this->conn);
			$this->dbMODx 	 = version_compare($ver,"4.0.2");
			$this->dbVersion = (float) $ver; // Typecasting (float) instead of floatval() [PHP < 4.2]
		}

        mysqli_query($this->conn,"{$this->connection_method} {$this->connection_charset}");
	}

    public function process($filename) {
	    global $custom_placeholders;

		// check to make sure file exists
		if (!file_exists($filename)) {
			$this->mysqlErrors[] = array("error" => "File '$filename' not found");
			$this->installFailed = true ;
			return false;
		}

		$fh = fopen($filename, 'r');
		$idata = '';

		while (!feof($fh)) {
			$idata .= fread($fh, 1024);
		}

		fclose($fh);
		$idata = str_replace("\r", '', $idata);

		// check if in upgrade mode
		if ($this->mode === 'upd') {
			// remove non-upgradeable parts
			$s = strpos($idata,'non-upgrade-able[[');
			$e = strpos($idata,']]non-upgrade-able') + 17;
			if($s && $e) {
			    $idata = str_replace(substr($idata, $s,$e-$s),' Removed non upgradeable items', $idata);
            }
		}

		// replace {} tags
		$idata = str_replace('{PREFIX}', $this->prefix, $idata);
		$idata = str_replace('{TABLEENCODING}', $this->getTableEncoding(), $idata);
		$idata = str_replace('{ADMIN}', $this->adminname, $idata);
		$idata = str_replace('{ADMINEMAIL}', $this->adminemail, $idata);
		$idata = str_replace('{ADMINPASS}', $this->adminpass, $idata);
		$idata = str_replace('{IMAGEPATH}', $this->imgPath, $idata);
		$idata = str_replace('{IMAGEURL}', $this->imgUrl, $idata);
		$idata = str_replace('{FILEMANAGERPATH}', $this->fileManagerPath, $idata);
		$idata = str_replace('{MANAGERLANGUAGE}', $this->managerlanguage, $idata);
		$idata = str_replace('{AUTOTEMPLATELOGIC}', $this->autoTemplateLogic, $idata);
		/*$idata = str_replace('{VERSION}', $modx_version, $idata);*/

		// Replace custom placeholders
		foreach($custom_placeholders as $key=>$val) {
			if (strpos($idata, '{'.$key.'}') !== false) {
				$idata = str_replace('{'.$key.'}', $val, $idata);
			}
		}

		$sql_array = explode("\n\n", $idata);

		$num = 0;
		foreach($sql_array as $sql_entry) {
			$sql_do = trim($sql_entry, "\r\n; ");

			if (preg_match('/^\#/', $sql_do)) continue;

			// strip out comments and \n for mysql 3.x
			if ($this->dbVersion <4.0) {
				$sql_do = preg_replace("~COMMENT.*[^']?'.*[^']?'~","",$sql_do);
				$sql_do = str_replace('\r', "", $sql_do);
				$sql_do = str_replace('\n', "", $sql_do);
			}


			$num = $num + 1;
			if ($sql_do) mysqli_query($this->conn, $sql_do);
			if(mysqli_error($this->conn)) {
				// Ignore duplicate and drop errors - Raymond
				if ($this->ignoreDuplicateErrors){
					if (mysqli_errno($this->conn) == 1060 || mysqli_errno($this->conn) == 1061 || mysqli_errno($this->conn) == 1062 ||mysqli_errno($this->conn) == 1091) continue;
				}
				// End Ignore duplicate
				$this->mysqlErrors[] = array("error" => mysqli_error($this->conn), "sql" => $sql_do);
				$this->installFailed = true;
			}
		}
	}

	public function getTableEncoding()
    {
        $out = 'DEFAULT CHARSET=' . $this->connection_charset;
        if (!empty($this->database_collation)) {
            $out .= ' COLLATE=' . $this->database_collation;
        }

        return $out;
    }

    public function close() {
		mysqli_close($this->conn);
	}
}
