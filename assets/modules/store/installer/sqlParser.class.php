<?php
// MySQL Dump Parser
// SNUFFKIN/ Alex 2004
error_reporting(E_ALL & ~E_NOTICE);
class SqlParser {
	var $host, $dbname, $prefix, $user, $password, $mysqlErrors;
	var $conn, $installFailed, $sitename, $adminname, $adminemail, $adminpass, $managerlanguage;
	var $mode, $fileManagerPath, $imgPath, $imgUrl;
    var $connection_charset, $connection_method;
    public $database_collation;

	public function __construct() {
		$adminname='';
		$adminemail='';
		$adminpass='';
		$connection_charset= 'utf8';
		$managerlanguage='english';
		$connection_method = 'SET CHARACTER SET';
		$auto_template_logic = 'parent';
		$this->adminname = $adminname;
		$this->adminemail = $adminemail;
		$this->connection_charset = $connection_charset;
		$this->connection_method = $connection_method;
		$this->ignoreDuplicateErrors = false;
		$this->managerlanguage = $managerlanguage;
        $this->autoTemplateLogic = $auto_template_logic;
	}

	function process($filename) {
	    global $modx_version,$modx;

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

		if ($this->mode=="upd") {
			$s = strpos($idata,"non-upgrade-able[[");
			$e = strpos($idata,"]]non-upgrade-able")+17;
			if($s && $e) $idata = str_replace(substr($idata,$s,$e-$s)," Removed non upgradeable items",$idata);
		}

		// replace {} tags
		$idata = str_replace('{PREFIX}', $modx->db->config['table_prefix'], $idata);
        $idata = str_replace('{TABLEENCODING}', $this->getTableEncoding(), $idata);
		$idata = str_replace('{ADMIN}', $this->adminname, $idata);
		$idata = str_replace('{ADMINEMAIL}', $this->adminemail, $idata);
		$idata = str_replace('{ADMINPASS}', $this->adminpass, $idata);
		$idata = str_replace('{IMAGEPATH}', $this->imagePath, $idata);
		$idata = str_replace('{IMAGEURL}', $this->imageUrl, $idata);
		$idata = str_replace('{FILEMANAGERPATH}', $this->fileManagerPath, $idata);
		$idata = str_replace('{MANAGERLANGUAGE}', $this->managerlanguage, $idata);
		$idata = str_replace('{AUTOTEMPLATELOGIC}', $this->autoTemplateLogic, $idata);
		//$idata = str_replace('{VERSION}', $modx_version, $idata);

		$sql_array = explode("\n\n", $idata);

		$num = 0;
		foreach($sql_array as $sql_entry) {
			$sql_do = trim($sql_entry, "\r\n; ");

			if (preg_match('/^\#/', $sql_do)) continue;

			// strip out comments and \n for mysql 3.x
			if ( floatval( $modx->db->getVersion() ) < 4.0 ) {
				$sql_do = preg_replace("~COMMENT.*[^']?'.*[^']?'~","",$sql_do);
				$sql_do = str_replace('\r', "", $sql_do);
				$sql_do = str_replace('\n', "", $sql_do);
			}


			$num = $num + 1;
			if ($sql_do) $modx->db->query($sql_do, false);
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
}
