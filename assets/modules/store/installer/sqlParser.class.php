<?php
/**
 * @deprecated
 * @since 3.5.0
 *
 * There are no valid calls of this class in the CMS
 *
 * @todo [remove@3.7] Remove in Evolution CMS 3.7
 */
// MySQL Dump Parser
// SNUFFKIN/ Alex 2004
error_reporting(E_ALL & ~E_NOTICE);
class SqlParser {
    var $host, $dbname, $prefix, $user, $password, $mysqlErrors, $ignoreDuplicateErrors, $autoTemplateLogic;
    var $conn, $installFailed, $sitename, $adminname, $adminemail, $adminpass, $managerlanguage;
    var $mode, $fileManagerPath, $imgPath, $imgUrl;
    var $connection_charset;
    public $database_collation;

    public function __construct() {
        $adminname = $_SESSION['mgrShortname'];
        $adminemail = $_SESSION['mgrEmail'];
        $imgPath = EVO_BASE_PATH . 'assets/images/';
        $imgUrl = EVO_SITE_URL . 'assets/images/';
        $fileManagerPath = EVO_BASE_PATH . MGR_DIR . '/';
        $connection_charset= 'utf8';
        $managerlanguage='en';
        $auto_template_logic = 'sibling';
        $this->adminname = $adminname;
        $this->adminemail = $adminemail;
        $this->imgPath = $imgPath;
        $this->imgUrl = $imgUrl;
        $this->fileManagerPath = $fileManagerPath;
        $this->connection_charset = $connection_charset;
        $this->ignoreDuplicateErrors = false;
        $this->managerlanguage = $managerlanguage;
        $this->autoTemplateLogic = $auto_template_logic;
    }

    function process($filename) {
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
        $idata = str_replace('{PREFIX}', evo()->getDatabase()->getConfig('prefix'), $idata);
        $idata = str_replace('{TABLEENCODING}', $this->getTableEncoding(), $idata);
        $idata = str_replace('{ADMIN}', $this->adminname, $idata);
        $idata = str_replace('{ADMINEMAIL}', $this->adminemail, $idata);
        $idata = str_replace('{IMAGEPATH}', $this->imgPath, $idata);
        $idata = str_replace('{IMAGEURL}', $this->imgUrl, $idata);
        $idata = str_replace('{FILEMANAGERPATH}', $this->fileManagerPath, $idata);
        $idata = str_replace('{MANAGERLANGUAGE}', $this->managerlanguage, $idata);
        $idata = str_replace('{AUTOTEMPLATELOGIC}', $this->autoTemplateLogic, $idata);
        if ($this->adminpass && !empty($this->adminpass)) {
            $idata = str_replace('{ADMINPASS}', $this->adminpass, $idata);
        }

        $sql_array = explode("\n\n", $idata);

        $num = 0;
        foreach ($sql_array as $sql_entry) {
            $sql_do = trim($sql_entry, "\r\n; ");

            if (preg_match('/^\#/', $sql_do)) {
                continue;
            }

            // strip out comments and \n for mysql 3.x
            if (floatval(evo()->getDatabase()->getVersion()) < 4.0) {
                $sql_do = preg_replace("~COMMENT.*[^']?'.*[^']?'~","",$sql_do);
                $sql_do = str_replace('\r', "", $sql_do);
                $sql_do = str_replace('\n', "", $sql_do);
            }

            $num = $num + 1;
            if ($sql_do) {
                evo()->getDatabase()->query($sql_do, false);
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
}
