<?php
/**
 *    MODX Document Parser
 *    Function: This class contains the main document parsing functions
 *
 */
if (!defined('E_DEPRECATED')) {
    define('E_DEPRECATED', 8192);
}
if (!defined('E_USER_DEPRECATED')) {
    define('E_USER_DEPRECATED', 16384);
}

class DocumentParser
{
    /**
     * db object
     * @var DBAPI
     * @see /manager/includes/extenders/ex_dbapi.inc.php
     * @example $this->loadExtension('DBAPI')
     */
    public $db;

    /**
     * @var MODxMailer
     * @see /manager/includes/extenders/ex_modxmailer.inc.php
     * @example $this->loadExtension('MODxMailer');
     */
    public $mail;

    /**
     * @var PHPCOMPAT
     * @see /manager/includes/extenders/ex_phpcompat.inc.php
     * @example $this->loadExtension('PHPCOMPAT');
     */
    public $phpcompat;

    /**
     * @var MODIFIERS
     * @see /manager/includes/extenders/ex_modifiers.inc.php
     * @example $this->loadExtension('MODIFIERS');
     */
    public $filter;

    /**
     * @var EXPORT_SITE
     * @see /manager/includes/extenders/ex_export_site.inc.php
     * @example $this->loadExtension('EXPORT_SITE');
     */
    public $export;

    /**
     * @var MakeTable
     * @see /manager/includes/extenders/ex_maketable.inc.php
     * @example $this->loadExtension('makeTable');
     */
    public $table;

    /**
     * @var ManagerAPI
     * @see /manager/includes/extenders/ex_managerapi.inc.php
     * @example $this->loadExtension('ManagerAPI');
     */
    public $manager;

    /**
     * @var PasswordHash
     * @see manager/includes/extenders/ex_phpass.inc.php
     * @example $this->loadExtension('phpass');
     */
    public $phpass;

    /**
     * event object
     * @var SystemEvent
     */

    public $event;
    /**
     * event object
     * @var SystemEvent
     */
    public $Event;

    /**
     * @var array
     */
    public $pluginEvent = array();

    /**
     * @var array
     */
    public $config = array();
    /**
     * @var array
     */
    public $dbConfig = array();
    public $configGlobal = null; // contains backup of settings overwritten by user-settings
    public $rs;
    public $result;
    public $sql;
    public $table_prefix;
    public $debug = false;
    public $documentIdentifier;
    public $documentMethod;
    public $documentGenerated;
    public $documentContent;
    public $documentOutput;
    public $tstart;
    public $mstart;
    public $minParserPasses;
    public $maxParserPasses;
    public $documentObject;
    public $templateObject;
    public $snippetObjects;
    public $stopOnNotice = false;
    public $executedQueries;
    public $queryTime;
    public $currentSnippet;
    public $documentName;
    public $aliases;
    public $visitor;
    public $entrypage;
    public $documentListing;
    /**
     * feed the parser the execution start time
     * @var bool
     */
    public $dumpSnippets = false;
    public $snippetsCode;
    public $snippetsTime = array();
    public $chunkCache;
    public $snippetCache;
    public $contentTypes;
    public $dumpSQL = false;
    public $queryCode;
    public $virtualDir;
    public $placeholders;
    public $sjscripts = array();
    public $jscripts = array();
    public $loadedjscripts = array();
    public $documentMap;
    public $forwards = 3;
    public $error_reporting = 1;
    public $dumpPlugins = false;
    public $pluginsCode;
    public $pluginsTime = array();
    public $pluginCache = array();
    public $aliasListing;
    public $lockedElements = null;
    public $tmpCache = array();
    private $version = array();
    public $extensions = array();
    public $cacheKey = null;
    public $recentUpdate = 0;
    public $useConditional = false;
    protected $systemCacheKey = null;
    public $snipLapCount = 0;
    public $messageQuitCount;
    public $time;
    public $sid;
    private $q;
    public $decoded_request_uri;
    /**
     * @var OldFunctions
     */
    public $old;

    /**
     * Hold the class instance.
     * @var DocumentParser
     */
    private static $instance = null;

    /**
     * Document constructor
     *
     * @return DocumentParser
     */
    public function __construct()
    {
        if ($this->isLoggedIn()) {
            ini_set('display_errors', 1);
        }
        global $database_server;
        if (substr(PHP_OS, 0, 3) === 'WIN' && $database_server === 'localhost') {
            $database_server = '127.0.0.1';
        }
        $this->loadExtension('DBAPI') or die('Could not load DBAPI class.'); // load DBAPI class

        $DLTemplate = MODX_BASE_PATH . 'assets/snippets/DocLister/lib/DLTemplate.class.php';
        if (file_exists($DLTemplate)) {
            include_once $DLTemplate;
        }

        $this->dbConfig = &$this->db->config; // alias for backward compatibility
        // events
        $this->event = new SystemEvent();
        $this->Event = &$this->event; //alias for backward compatibility
        // set track_errors ini variable
        @ ini_set("track_errors", "1"); // enable error tracking in $php_errormsg
        $this->time = $_SERVER['REQUEST_TIME']; // for having global timestamp

        $this->q = self::_getCleanQueryString();
    }

    final public function __clone()
    {
    }
    /**
     * @return DocumentParser
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * @param $method_name
     * @param $arguments
     * @return mixed
     */
    function __call($method_name, $arguments)
    {
        include_once(MODX_MANAGER_PATH . 'includes/extenders/deprecated.functions.inc.php');
        if (method_exists($this->old, $method_name)) {
            $error_type = 1;
        } else {
            $error_type = 3;
        }

        if (!isset($this->config['error_reporting']) || 1 < $this->config['error_reporting']) {
            if ($error_type == 1) {
                $title = 'Call deprecated method';
                $msg = $this->htmlspecialchars("\$modx->{$method_name}() is deprecated function");
            } else {
                $title = 'Call undefined method';
                $msg = $this->htmlspecialchars("\$modx->{$method_name}() is undefined function");
            }
            $info = debug_backtrace();
            $m[] = $msg;
            if (!empty($this->currentSnippet)) {
                $m[] = 'Snippet - ' . $this->currentSnippet;
            } elseif (!empty($this->event->activePlugin)) {
                $m[] = 'Plugin - ' . $this->event->activePlugin;
            }
            $m[] = $this->decoded_request_uri;
            $m[] = str_replace('\\', '/', $info[0]['file']) . '(line:' . $info[0]['line'] . ')';
            $msg = implode('<br />', $m);
            $this->logEvent(0, $error_type, $msg, $title);
        }
        if (method_exists($this->old, $method_name)) {
            return call_user_func_array(array($this->old, $method_name), $arguments);
        }
    }

    /**
     * @param string $connector
     * @return bool
     */
    public function checkSQLconnect($connector = 'db')
    {
        $flag = false;
        if (is_scalar($connector) && !empty($connector) && isset($this->{$connector}) && $this->{$connector} instanceof DBAPI) {
            $flag = (bool)$this->{$connector}->conn;
        }
        return $flag;
    }

    /**
     * Loads an extension from the extenders folder.
     * You can load any extension creating a boot file:
     * MODX_MANAGER_PATH."includes/extenders/ex_{$extname}.inc.php"
     * $extname - extension name in lowercase
     *
     * @param $extname
     * @param bool $reload
     * @return bool
     */
    public function loadExtension($extname, $reload = true)
    {
        $out = false;
        $flag = ($reload || !in_array($extname, $this->extensions));
        if ($this->checkSQLconnect('db') && $flag) {
            $evtOut = $this->invokeEvent('OnBeforeLoadExtension', array('name' => $extname, 'reload' => $reload));
            if (is_array($evtOut) && count($evtOut) > 0) {
                $out = array_pop($evtOut);
            }
        }
        if (!$out && $flag) {
            $extname = trim(str_replace(array('..', '/', '\\'), '', strtolower($extname)));
            $filename = MODX_MANAGER_PATH . "includes/extenders/ex_{$extname}.inc.php";
            $out = is_file($filename) ? include $filename : false;
        }
        if ($out && !in_array($extname, $this->extensions)) {
            $this->extensions[] = $extname;
        }
        return $out;
    }

    /**
     * Returns the current micro time
     *
     * @return float
     */
    public function getMicroTime()
    {
        list ($usec, $sec) = explode(' ', microtime());
        return ((float)$usec + (float)$sec);
    }

    /**
     * Redirect
     *
     * @param string $url
     * @param int $count_attempts
     * @param string $type $type
     * @param string $responseCode
     * @return bool|null
     * @global string $base_url
     * @global string $site_url
     */
    public function sendRedirect($url, $count_attempts = 0, $type = '', $responseCode = '')
    {
        $header = '';
        if (empty ($url)) {
            return false;
        }
        if ($count_attempts == 1) {
            // append the redirect count string to the url
            $currentNumberOfRedirects = isset ($_REQUEST['err']) ? $_REQUEST['err'] : 0;
            if ($currentNumberOfRedirects > 3) {
                $this->messageQuit('Redirection attempt failed - please ensure the document you\'re trying to redirect to exists. <p>Redirection URL: <i>' . $url . '</i></p>');
            } else {
                $currentNumberOfRedirects += 1;
                if (strpos($url, "?") > 0) {
                    $url .= "&err=$currentNumberOfRedirects";
                } else {
                    $url .= "?err=$currentNumberOfRedirects";
                }
            }
        }
        if ($type == 'REDIRECT_REFRESH') {
            $header = 'Refresh: 0;URL=' . $url;
        } elseif ($type == 'REDIRECT_META') {
            $header = '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=' . $url . '" />';
            echo $header;
            exit;
        } elseif ($type == 'REDIRECT_HEADER' || empty ($type)) {
            // check if url has /$base_url
            global $base_url, $site_url;
            if (substr($url, 0, strlen($base_url)) == $base_url) {
                // append $site_url to make it work with Location:
                $url = $site_url . substr($url, strlen($base_url));
            }
            if (strpos($url, "\n") === false) {
                $header = 'Location: ' . $url;
            } else {
                $this->messageQuit('No newline allowed in redirect url.');
            }
        }
        if ($responseCode && (strpos($responseCode, '30') !== false)) {
            header($responseCode);
        }

        if(!empty($header)) {
            header($header);
        }

        exit();
    }

    /**
     * Forward to another page
     *
     * @param int|string $id
     * @param string $responseCode
     */
    public function sendForward($id, $responseCode = '')
    {
        if ($this->forwards > 0) {
            $this->forwards = $this->forwards - 1;
            $this->documentIdentifier = $id;
            $this->documentMethod = 'id';
            if ($responseCode) {
                header($responseCode);
            }
            $this->prepareResponse();
            exit();
        } else {
            $this->messageQuit("Internal Server Error id={$id}");
            header('HTTP/1.0 500 Internal Server Error');
            die('<h1>ERROR: Too many forward attempts!</h1><p>The request could not be completed due to too many unsuccessful forward attempts.</p>');
        }
    }

    /**
     * Redirect to the error page, by calling sendForward(). This is called for example when the page was not found.
     * @param bool $noEvent
     */
    public function sendErrorPage($noEvent = false)
    {
        $this->systemCacheKey = 'notfound';
        if (!$noEvent) {
            // invoke OnPageNotFound event
            $this->invokeEvent('OnPageNotFound');
        }
        $url = $this->config['error_page'] ? $this->config['error_page'] : $this->config['site_start'];

        $this->sendForward($url, 'HTTP/1.0 404 Not Found');
        exit();
    }

    /**
     * @param bool $noEvent
     */
    public function sendUnauthorizedPage($noEvent = false)
    {
        // invoke OnPageUnauthorized event
        $_REQUEST['refurl'] = $this->documentIdentifier;
        $this->systemCacheKey = 'unauth';
        if (!$noEvent) {
            $this->invokeEvent('OnPageUnauthorized');
        }
        if ($this->config['unauthorized_page']) {
            $unauthorizedPage = $this->config['unauthorized_page'];
        } elseif ($this->config['error_page']) {
            $unauthorizedPage = $this->config['error_page'];
        } else {
            $unauthorizedPage = $this->config['site_start'];
        }
        $this->sendForward($unauthorizedPage, 'HTTP/1.1 401 Unauthorized');
        exit();
    }

    /**
     * Get MODX settings including, but not limited to, the system_settings table
     */
    public function getSettings()
    {
        if (!isset($this->config['site_name'])) {
            $this->recoverySiteCache();
        }

        // setup default site id - new installation should generate a unique id for the site.
        if (!isset($this->config['site_id'])) {
            $this->config['site_id'] = "MzGeQ2faT4Dw06+U49x3";
        }

        // store base_url and base_path inside config array
        $this->config['base_url'] = MODX_BASE_URL;
        $this->config['base_path'] = MODX_BASE_PATH;
        $this->config['site_url'] = MODX_SITE_URL;
        $this->config['valid_hostnames'] = MODX_SITE_HOSTNAMES;
        $this->config['site_manager_url'] = MODX_MANAGER_URL;
        $this->config['site_manager_path'] = MODX_MANAGER_PATH;
        $this->error_reporting = $this->config['error_reporting'];
        $this->config['filemanager_path'] = str_replace('[(base_path)]', MODX_BASE_PATH, $this->config['filemanager_path']);
        $this->config['rb_base_dir'] = str_replace('[(base_path)]', MODX_BASE_PATH, $this->config['rb_base_dir']);

        if (!isset($this->config['enable_at_syntax'])) {
            $this->config['enable_at_syntax'] = 1;
        } // @TODO: This line is temporary, should be remove in next version

        // now merge user settings into evo-configuration
        $this->getUserSettings();
    }

    private function recoverySiteCache()
    {
        $site_cache_dir = MODX_BASE_PATH . $this->getCacheFolder();
        $site_cache_path = $site_cache_dir . 'siteCache.idx.php';

        if (is_file($site_cache_path)) {
            include($site_cache_path);
        }
        if (isset($this->config['site_name'])) {
            return;
        }

        include_once(MODX_MANAGER_PATH . 'processors/cache_sync.class.processor.php');
        $cache = new synccache();
        $cache->setCachepath($site_cache_dir);
        $cache->setReport(false);
        $cache->buildCache($this);

        clearstatcache();
        if (is_file($site_cache_path)) {
            include($site_cache_path);
        }
        if (isset($this->config['site_name'])) {
            return;
        }

        $rs = $this->db->select('setting_name, setting_value', '[+prefix+]system_settings');
        while ($row = $this->db->getRow($rs)) {
            $this->config[$row['setting_name']] = $row['setting_value'];
        }

        if (!$this->config['enable_filter']) {
            return;
        }

        $where = "plugincode LIKE '%phx.parser.class.inc.php%OnParseDocument();%' AND disabled != 1";
        $rs = $this->db->select('id', '[+prefix+]site_plugins', $where);
        if ($this->db->getRecordCount($rs)) {
            $this->config['enable_filter'] = '0';
        }
    }

    /**
     * Get user settings and merge into MODX configuration
     * @return array
     */
    public function getUserSettings()
    {
        $tbl_web_user_settings = $this->getFullTableName('web_user_settings');
        $tbl_user_settings = $this->getFullTableName('user_settings');

        // load user setting if user is logged in
        $usrSettings = array();
        if ($id = $this->getLoginUserID()) {
            $usrType = $this->getLoginUserType();
            if (isset ($usrType) && $usrType == 'manager') {
                $usrType = 'mgr';
            }

            if ($usrType == 'mgr' && $this->isBackend()) {
                // invoke the OnBeforeManagerPageInit event, only if in backend
                $this->invokeEvent("OnBeforeManagerPageInit");
            }

            if (isset ($_SESSION[$usrType . 'UsrConfigSet'])) {
                $usrSettings = &$_SESSION[$usrType . 'UsrConfigSet'];
            } else {
                if ($usrType == 'web') {
                    $from = $tbl_web_user_settings;
                    $where = "webuser='{$id}'";
                } else {
                    $from = $tbl_user_settings;
                    $where = "user='{$id}'";
                }

                $which_browser_default = $this->configGlobal['which_browser'] ? $this->configGlobal['which_browser'] : $this->config['which_browser'];

                $result = $this->db->select('setting_name, setting_value', $from, $where);
                while ($row = $this->db->getRow($result)) {
                    if ($row['setting_name'] == 'which_browser' && $row['setting_value'] == 'default') {
                        $row['setting_value'] = $which_browser_default;
                    }
                    $usrSettings[$row['setting_name']] = $row['setting_value'];
                }
                if (isset ($usrType)) {
                    $_SESSION[$usrType . 'UsrConfigSet'] = $usrSettings;
                } // store user settings in session
            }
        }
        if ($this->isFrontend() && $mgrid = $this->getLoginUserID('mgr')) {
            $musrSettings = array();
            if (isset ($_SESSION['mgrUsrConfigSet'])) {
                $musrSettings = &$_SESSION['mgrUsrConfigSet'];
            } else {
                if ($result = $this->db->select('setting_name, setting_value', $tbl_user_settings, "user='{$mgrid}'")) {
                    while ($row = $this->db->getRow($result)) {
                        $musrSettings[$row['setting_name']] = $row['setting_value'];
                    }
                    $_SESSION['mgrUsrConfigSet'] = $musrSettings; // store user settings in session
                }
            }
            if (!empty ($musrSettings)) {
                $usrSettings = array_merge($musrSettings, $usrSettings);
            }
        }
        // save global values before overwriting/merging array
        foreach ($usrSettings as $param => $value) {
            if (isset($this->config[$param])) {
                $this->configGlobal[$param] = $this->config[$param];
            }
        }

        $this->config = array_merge($this->config, $usrSettings);
        $this->config['filemanager_path'] = str_replace('[(base_path)]', MODX_BASE_PATH, $this->config['filemanager_path']);
        $this->config['rb_base_dir'] = str_replace('[(base_path)]', MODX_BASE_PATH, $this->config['rb_base_dir']);

        return $usrSettings;
    }

    /**
     * Returns the document identifier of the current request
     *
     * @param string $method id and alias are allowed
     * @return int
     */
    public function getDocumentIdentifier($method)
    {
        // function to test the query and find the retrieval method
        if ($method === 'alias') {
            return $this->db->escape($_REQUEST['q']);
        }

        $id_ = filter_input(INPUT_GET, 'id');
        if ($id_) {
            if (preg_match('@^[1-9][0-9]*$@', $id_)) {
                return $id_;
            } else {
                $this->sendErrorPage();
            }
        } elseif (strpos($_SERVER['REQUEST_URI'], 'index.php/') !== false) {
            $this->sendErrorPage();
        } else {
            return $this->config['site_start'];
        }
    }

    /**
     * Check for manager or webuser login session since v1.2
     *
     * @param string $context
     * @return bool
     */
    public function isLoggedIn($context = 'mgr')
    {
        if (substr($context, 0, 1) == 'm') {
            $_ = 'mgrValidated';
        } else {
            $_ = 'webValidated';
        }

        if (MODX_CLI || (isset($_SESSION[$_]) && !empty($_SESSION[$_]))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check for manager login session
     *
     * @return boolean
     */
    public function checkSession()
    {
        return $this->isLoggedin();
    }

    /**
     * Checks, if a the result is a preview
     *
     * @return boolean
     */
    public function checkPreview()
    {
        if ($this->isLoggedIn() == true) {
            if (isset ($_REQUEST['z']) && $_REQUEST['z'] == 'manprev') {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * check if site is offline
     *
     * @return boolean
     */
    public function checkSiteStatus()
    {
        if ($this->config['site_status']) {
            return true;
        }  // site online
        elseif ($this->isLoggedin()) {
            return true;
        }  // site offline but launched via the manager
        else {
            return false;
        } // site is offline
    }

    /**
     * Create a 'clean' document identifier with path information, friendly URL suffix and prefix.
     *
     * @param string $qOrig
     * @return string
     */
    public function cleanDocumentIdentifier($qOrig)
    {
        if (!$qOrig) {
            $qOrig = $this->config['site_start'];
        }
        $q = $qOrig;

        $pre = $this->config['friendly_url_prefix'];
        $suf = $this->config['friendly_url_suffix'];
        $pre = preg_quote($pre, '/');
        $suf = preg_quote($suf, '/');
        if ($pre && preg_match('@^' . $pre . '(.*)$@', $q, $_)) {
            $q = $_[1];
        }
        if ($suf && preg_match('@(.*)' . $suf . '$@', $q, $_)) {
            $q = $_[1];
        }

        /* First remove any / before or after */
        $q = trim($q, '/');

        /* Save path if any */
        /* FS#476 and FS#308: only return virtualDir if friendly paths are enabled */
        if ($this->config['use_alias_path'] == 1) {
            $_ = strrpos($q, '/');
            $this->virtualDir = $_ !== false ? substr($q, 0, $_) : '';
            if ($_ !== false) {
                $q = preg_replace('@.*/@', '', $q);
            }
        } else {
            $this->virtualDir = '';
        }

        if (preg_match('@^[1-9][0-9]*$@', $q) && !isset($this->documentListing[$q])) { /* we got an ID returned, check to make sure it's not an alias */
            /* FS#476 and FS#308: check that id is valid in terms of virtualDir structure */
            if ($this->config['use_alias_path'] == 1) {
                if (($this->virtualDir != '' && !isset($this->documentListing[$this->virtualDir . '/' . $q]) || ($this->virtualDir == '' && !isset($this->documentListing[$q]))) && (($this->virtualDir != '' && isset($this->documentListing[$this->virtualDir]) && in_array($q, $this->getChildIds($this->documentListing[$this->virtualDir], 1))) || ($this->virtualDir == '' && in_array($q, $this->getChildIds(0, 1))))) {
                    $this->documentMethod = 'id';
                    return $q;
                } else { /* not a valid id in terms of virtualDir, treat as alias */
                    $this->documentMethod = 'alias';
                    return $q;
                }
            } else {
                $this->documentMethod = 'id';
                return $q;
            }
        } else { /* we didn't get an ID back, so instead we assume it's an alias */
            if ($this->config['friendly_alias_urls'] != 1) {
                $q = $qOrig;
            }
            $this->documentMethod = 'alias';
            return $q;
        }
    }

    /**
     * @return string
     */
    public function getCacheFolder()
    {
        return "assets/cache/";
    }

    /**
     * @param $key
     * @return string
     */
    public function getHashFile($key)
    {
        return $this->getCacheFolder() . "docid_" . $key . ".pageCache.php";
    }

    /**
     * @param $id
     * @return array|mixed|null|string
     */
    public function makePageCacheKey($id){
        $hash = $id;
        $tmp = null;
        $params = array();
        if(!empty($this->systemCacheKey)){
            $hash = $this->systemCacheKey;
        }else {
            if (!empty($_GET)) {
                // Sort GET parameters so that the order of parameters on the HTTP request don't affect the generated cache ID.
                $params = $_GET;
                ksort($params);
                $hash .= '_'.md5(http_build_query($params));
            }
        }
        $evtOut = $this->invokeEvent("OnMakePageCacheKey", array ("hash" => $hash, "id" => $id, 'params' => $params));
        if (is_array($evtOut) && count($evtOut) > 0){
            $tmp = array_pop($evtOut);
        }
        return empty($tmp) ? $hash : $tmp;
    }

    /**
     * @param $id
     * @param bool $loading
     * @return string
     */
    public function checkCache($id, $loading = false)
    {
        return $this->getDocumentObjectFromCache($id, $loading);
    }

    /**
     * Check the cache for a specific document/resource
     *
     * @param int $id
     * @param bool $loading
     * @return string
     */
    public function getDocumentObjectFromCache($id, $loading = false)
    {
        $key = ($this->config['cache_type'] == 2) ? $this->makePageCacheKey($id) : $id;
        if ($loading) {
            $this->cacheKey = $key;
        }

        $cache_path = $this->getHashFile($key);

        if (!is_file($cache_path)) {
            $this->documentGenerated = 1;
            return '';
        }
        $content = file_get_contents($cache_path, false);
        if (substr($content, 0, 5) === '<?php') {
            $content = substr($content, strpos($content, '?>') + 2);
        } // remove php header
        $a = explode('<!--__MODxCacheSpliter__-->', $content, 2);
        if (count($a) == 1) {
            $result = $a[0];
        } // return only document content
        else {
            $docObj = unserialize($a[0]); // rebuild document object
            // check page security
            if ($docObj['privateweb'] && isset ($docObj['__MODxDocGroups__'])) {
                $pass = false;
                $usrGrps = $this->getUserDocGroups();
                $docGrps = explode(',', $docObj['__MODxDocGroups__']);
                // check is user has access to doc groups
                if (is_array($usrGrps)) {
                    foreach ($usrGrps as $k => $v) {
                        if (!in_array($v, $docGrps)) {
                            continue;
                        }
                        $pass = true;
                        break;
                    }
                }
                // diplay error pages if user has no access to cached doc
                if (!$pass) {
                    if ($this->config['unauthorized_page']) {
                        // check if file is not public
                        $rs = $this->db->select('count(id)', '[+prefix+]document_groups', "document='{$id}'", '', '1');
                        $total = $this->db->getValue($rs);
                    } else {
                        $total = 0;
                    }

                    if ($total > 0) {
                        $this->sendUnauthorizedPage();
                    } else {
                        $this->sendErrorPage();
                    }

                    exit; // stop here
                }
            }
            // Grab the Scripts
            if (isset($docObj['__MODxSJScripts__'])) {
                $this->sjscripts = $docObj['__MODxSJScripts__'];
            }
            if (isset($docObj['__MODxJScripts__'])) {
                $this->jscripts = $docObj['__MODxJScripts__'];
            }

            // Remove intermediate variables
            unset($docObj['__MODxDocGroups__'], $docObj['__MODxSJScripts__'], $docObj['__MODxJScripts__']);

            $this->documentObject = $docObj;

            $result = $a[1]; // return document content
        }

        $this->documentGenerated = 0;
        // invoke OnLoadWebPageCache  event
        $this->documentContent = $result;
        $this->invokeEvent('OnLoadWebPageCache');
        return $result;
    }

    /**
     * Final processing and output of the document/resource.
     *
     * - runs uncached snippets
     * - add javascript to <head>
     * - removes unused placeholders
     * - converts URL tags [~...~] to URLs
     *
     * @param boolean $noEvent Default: false
     */
    public function outputContent($noEvent = false)
    {
        $this->documentOutput = $this->documentContent;

        if ($this->documentGenerated == 1 && $this->documentObject['cacheable'] == 1 && $this->documentObject['type'] == 'document' && $this->documentObject['published'] == 1) {
            if (!empty($this->sjscripts)) {
                $this->documentObject['__MODxSJScripts__'] = $this->sjscripts;
            }
            if (!empty($this->jscripts)) {
                $this->documentObject['__MODxJScripts__'] = $this->jscripts;
            }
        }

        // check for non-cached snippet output
        if (strpos($this->documentOutput, '[!') > -1) {
            $this->recentUpdate = $_SERVER['REQUEST_TIME'] + $this->config['server_offset_time'];

            $this->documentOutput = str_replace('[!', '[[', $this->documentOutput);
            $this->documentOutput = str_replace('!]', ']]', $this->documentOutput);

            // Parse document source
            $this->documentOutput = $this->parseDocumentSource($this->documentOutput);
        }

        // Moved from prepareResponse() by sirlancelot
        // Insert Startup jscripts & CSS scripts into template - template must have a <head> tag
        if ($js = $this->getRegisteredClientStartupScripts()) {
            // change to just before closing </head>
            // $this->documentContent = preg_replace("/(<head[^>]*>)/i", "\\1\n".$js, $this->documentContent);
            $this->documentOutput = preg_replace("/(<\/head>)/i", $js . "\n\\1", $this->documentOutput);
        }

        // Insert jscripts & html block into template - template must have a </body> tag
        if ($js = $this->getRegisteredClientScripts()) {
            $this->documentOutput = preg_replace("/(<\/body>)/i", $js . "\n\\1", $this->documentOutput);
        }
        // End fix by sirlancelot

        $this->documentOutput = $this->cleanUpMODXTags($this->documentOutput);

        $this->documentOutput = $this->rewriteUrls($this->documentOutput);

        // send out content-type and content-disposition headers
        if (IN_PARSER_MODE == "true") {
            $type = !empty ($this->documentObject["contentType"]) ? $this->documentObject["contentType"] : "text/html";
            header('Content-Type: ' . $type . '; charset=' . $this->config['modx_charset']);
            //            if (($this->documentIdentifier == $this->config['error_page']) || $redirect_error)
            //                header('HTTP/1.0 404 Not Found');
            if (!$this->checkPreview() && $this->documentObject['content_dispo'] == 1) {
                if ($this->documentObject['alias']) {
                    $name = $this->documentObject['alias'];
                } else {
                    // strip title of special characters
                    $name = $this->documentObject['pagetitle'];
                    $name = strip_tags($name);
                    $name = $this->cleanUpMODXTags($name);
                    $name = strtolower($name);
                    $name = preg_replace('/&.+?;/', '', $name); // kill entities
                    $name = preg_replace('/[^\.%a-z0-9 _-]/', '', $name);
                    $name = preg_replace('/\s+/', '-', $name);
                    $name = preg_replace('|-+|', '-', $name);
                    $name = trim($name, '-');
                }
                $header = 'Content-Disposition: attachment; filename=' . $name;
                header($header);
            }
        }
        $this->setConditional();

        $stats = $this->getTimerStats($this->tstart);

        $out =& $this->documentOutput;
        $out = str_replace("[^q^]", $stats['queries'], $out);
        $out = str_replace("[^qt^]", $stats['queryTime'], $out);
        $out = str_replace("[^p^]", $stats['phpTime'], $out);
        $out = str_replace("[^t^]", $stats['totalTime'], $out);
        $out = str_replace("[^s^]", $stats['source'], $out);
        $out = str_replace("[^m^]", $stats['phpMemory'], $out);
        //$this->documentOutput= $out;

        // invoke OnWebPagePrerender event
        if (!$noEvent) {
            $evtOut = $this->invokeEvent('OnWebPagePrerender', array('documentOutput' => $this->documentOutput));
            if (is_array($evtOut) && count($evtOut) > 0) {
                $this->documentOutput = $evtOut['0'];
            }
        }

        $this->documentOutput = $this->removeSanitizeSeed($this->documentOutput);

        if (strpos($this->documentOutput, '\{') !== false) {
            $this->documentOutput = $this->RecoveryEscapedTags($this->documentOutput);
        } elseif (strpos($this->documentOutput, '\[') !== false) {
            $this->documentOutput = $this->RecoveryEscapedTags($this->documentOutput);
        }

        echo $this->documentOutput;

        if ($this->dumpSQL) {
            echo $this->queryCode;
        }
        if ($this->dumpSnippets) {
            $sc = "";
            $tt = 0;
            foreach ($this->snippetsTime as $s => $v) {
                $t = $v['time'];
                $sname = $v['sname'];
                $sc .= sprintf("%s. %s (%s)<br>", $s, $sname, sprintf("%2.2f ms", $t)); // currentSnippet
                $tt += $t;
            }
            echo "<fieldset><legend><b>Snippets</b> (" . count($this->snippetsTime) . " / " . sprintf("%2.2f ms", $tt) . ")</legend>{$sc}</fieldset><br />";
            echo $this->snippetsCode;
        }
        if ($this->dumpPlugins) {
            $ps = "";
            $tt = 0;
            foreach ($this->pluginsTime as $s => $t) {
                $ps .= "$s (" . sprintf("%2.2f ms", $t * 1000) . ")<br>";
                $tt += $t;
            }
            echo "<fieldset><legend><b>Plugins</b> (" . count($this->pluginsTime) . " / " . sprintf("%2.2f ms", $tt * 1000) . ")</legend>{$ps}</fieldset><br />";
            echo $this->pluginsCode;
        }

        ob_end_flush();
    }

    /**
     * @param $contents
     * @return mixed
     */
    public function RecoveryEscapedTags($contents)
    {
        list($sTags, $rTags) = $this->getTagsForEscape();
        return str_replace($rTags, $sTags, $contents);
    }

    /**
     * @param string $tags
     * @return array[]
     */
    public function getTagsForEscape($tags = '{{,}},[[,]],[!,!],[*,*],[(,)],[+,+],[~,~],[^,^]')
    {
        $srcTags = explode(',', $tags);
        $repTags = array();
        foreach ($srcTags as $tag) {
            $repTags[] = '\\' . $tag[0] . '\\' . $tag[1];
        }
        return array($srcTags, $repTags);
    }

    /**
     * @param $tstart
     * @return array
     */
    public function getTimerStats($tstart)
    {
        $stats = array();

        $stats['totalTime'] = ($this->getMicroTime() - $tstart);
        $stats['queryTime'] = $this->queryTime;
        $stats['phpTime'] = $stats['totalTime'] - $stats['queryTime'];

        $stats['queryTime'] = sprintf("%2.4f s", $stats['queryTime']);
        $stats['totalTime'] = sprintf("%2.4f s", $stats['totalTime']);
        $stats['phpTime'] = sprintf("%2.4f s", $stats['phpTime']);
        $stats['source'] = $this->documentGenerated == 1 ? "database" : "cache";
        $stats['queries'] = isset ($this->executedQueries) ? $this->executedQueries : 0;
        $stats['phpMemory'] = (memory_get_peak_usage(true) / 1024 / 1024) . " mb";

        return $stats;
    }

    public function setConditional()
    {
        if (!empty($_POST) || (defined('MODX_API_MODE') && MODX_API_MODE) || $this->getLoginUserID('mgr') || !$this->useConditional || empty($this->recentUpdate)) {
            return;
        }
        $last_modified = gmdate('D, d M Y H:i:s T', $this->recentUpdate);
        $etag = md5($last_modified);
        $HTTP_IF_MODIFIED_SINCE = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false;
        $HTTP_IF_NONE_MATCH = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] : false;
        header('Pragma: no-cache');

        if ($HTTP_IF_MODIFIED_SINCE == $last_modified || strpos($HTTP_IF_NONE_MATCH, $etag) !== false) {
            header('HTTP/1.1 304 Not Modified');
            header('Content-Length: 0');
            exit;
        } else {
            header("Last-Modified: {$last_modified}");
            header("ETag: '{$etag}'");
        }
    }

    /**
     * Checks the publish state of page
     */
    public function updatePubStatus()
    {
        $cacheRefreshTime = 0;
        $recent_update = 0;
        @include(MODX_BASE_PATH . $this->getCacheFolder() . 'sitePublishing.idx.php');
        $this->recentUpdate = $recent_update;

        $timeNow = $_SERVER['REQUEST_TIME'] + $this->config['server_offset_time'];
        if ($timeNow < $cacheRefreshTime || $cacheRefreshTime == 0) {
            return;
        }

        // now, check for documents that need publishing
        $field = array('published' => 1, 'publishedon' => $timeNow);
        $where = "pub_date <= {$timeNow} AND pub_date!=0 AND published=0";
        $result_pub = $this->db->select( 'id', '[+prefix+]site_content',  $where);
        $this->db->update($field, '[+prefix+]site_content', $where);
        if ($this->db->getRecordCount($result_pub) >= 1) { //Event unPublished doc
            while ($row_pub = $this->db->getRow($result_pub)) {
                $this->invokeEvent("OnDocPublished", array(
                    "docid" => $row_pub['id']
                ));
            }
        }

        // now, check for documents that need un-publishing
        $field = array('published' => 0, 'publishedon' => 0);
        $where = "unpub_date <= {$timeNow} AND unpub_date!=0 AND published=1";
        $result_unpub = $this->db->select( 'id', '[+prefix+]site_content',  $where);
        $this->db->update($field, '[+prefix+]site_content', $where);
        if ($this->db->getRecordCount($result_unpub) >= 1) { //Event unPublished doc
            while ($row_unpub = $this->db->getRow($result_unpub)) {
                $this->invokeEvent("OnDocUnPublished", array(
                    "docid" => $row_unpub['id']
                ));
            }
        }

        $this->recentUpdate = $timeNow;

        // clear the cache
        $this->clearCache('full');
    }

    public function checkPublishStatus()
    {
        $this->updatePubStatus();
    }

    /**
     * Final jobs.
     *
     * - cache page
     */
    public function postProcess()
    {
        // if the current document was generated, cache it!
        $cacheable = ($this->config['enable_cache'] && $this->documentObject['cacheable']) ? 1 : 0;
        if ($cacheable && $this->documentGenerated && $this->documentObject['type'] == 'document' && $this->documentObject['published']) {
            // invoke OnBeforeSaveWebPageCache event
            $this->invokeEvent("OnBeforeSaveWebPageCache");

            if (!empty($this->cacheKey) && is_scalar($this->cacheKey)) {
                // get and store document groups inside document object. Document groups will be used to check security on cache pages
                $where = "document='{$this->documentIdentifier}'";
                $rs = $this->db->select('document_group', '[+prefix+]document_groups', $where);
                $docGroups = $this->db->getColumn('document_group', $rs);

                // Attach Document Groups and Scripts
                if (is_array($docGroups)) {
                    $this->documentObject['__MODxDocGroups__'] = implode(",", $docGroups);
                }

                $docObjSerial = serialize($this->documentObject);
                $cacheContent = $docObjSerial . "<!--__MODxCacheSpliter__-->" . $this->documentContent;
                $page_cache_path = MODX_BASE_PATH . $this->getHashFile($this->cacheKey);
                file_put_contents($page_cache_path, "<?php die('Unauthorized access.'); ?>$cacheContent");
            }
        }

        // Useful for example to external page counters/stats packages
        $this->invokeEvent('OnWebPageComplete');

        // end post processing
    }

    /**
     * @param $content
     * @param string $left
     * @param string $right
     * @return array
     */
    public function getTagsFromContent($content, $left = '[+', $right = '+]')
    {
        $_ = $this->_getTagsFromContent($content, $left, $right);
        if (empty($_)) {
            return array();
        }
        $tags = array();
        foreach ($_ as $v) {
            $tags[0][] = "{$left}{$v}{$right}";
            $tags[1][] = $v;
        }
        return $tags;
    }

    /**
     * @param $content
     * @param string $left
     * @param string $right
     * @return array
     */
    public function _getTagsFromContent($content, $left = '[+', $right = '+]')
    {
        if (strpos($content, $left) === false) {
            return array();
        }
        $spacer = md5('<<<EVO>>>');
        if($left==='{{' && strpos($content,';}}')!==false)  $content = str_replace(';}}', sprintf(';}%s}',   $spacer),$content);
        if($left==='{{' && strpos($content,'{{}}')!==false) $content = str_replace('{{}}',sprintf('{%$1s{}%$1s}',$spacer),$content);
        if($left==='[[' && strpos($content,']]]]')!==false) $content = str_replace(']]]]',sprintf(']]%s]]',  $spacer),$content);
        if($left==='[[' && strpos($content,']]]')!==false)  $content = str_replace(']]]', sprintf(']%s]]',   $spacer),$content);

        $pos['<![CDATA['] = strpos($content, '<![CDATA[');
        $pos[']]>'] = strpos($content, ']]>');

        if ($pos['<![CDATA['] !== false && $pos[']]>'] !== false) {
            $content = substr($content, 0, $pos['<![CDATA[']) . substr($content, $pos[']]>'] + 3);
        }

        $lp = explode($left, $content);
        $piece = array();
        foreach ($lp as $lc => $lv) {
            if ($lc !== 0) {
                $piece[] = $left;
            }
            if (strpos($lv, $right) === false) {
                $piece[] = $lv;
            } else {
                $rp = explode($right, $lv);
                foreach ($rp as $rc => $rv) {
                    if ($rc !== 0) {
                        $piece[] = $right;
                    }
                    $piece[] = $rv;
                }
            }
        }
        $lc = 0;
        $rc = 0;
        $fetch = '';
        $tags = array();
        foreach ($piece as $v) {
            if ($v === $left) {
                if (0 < $lc) {
                    $fetch .= $left;
                }
                $lc++;
            } elseif ($v === $right) {
                if ($lc === 0) {
                    continue;
                }
                $rc++;
                if ($lc === $rc) {
                    // #1200 Enable modifiers in Wayfinder - add nested placeholders to $tags like for $fetch = "phx:input=`[+wf.linktext+]`:test"
                    if ( (isset($this->config['enable_filter']) && $this->config['enable_filter'] == 1) OR class_exists('PHxParser') ) {
                        if (strpos($fetch, $left) !== false) {
                            $nested = $this->_getTagsFromContent($fetch, $left, $right);
                            foreach ($nested as $tag) {
                                if (!in_array($tag, $tags)) {
                                    $tags[] = $tag;
                                }
                            }
                        }
                    }

                    if (!in_array($fetch, $tags)) {  // Avoid double Matches
                        $tags[] = $fetch; // Fetch
                    };
                    $fetch = ''; // and reset
                    $lc = 0;
                    $rc = 0;
                } else {
                    $fetch .= $right;
                }
            } else {
                if (0 < $lc) {
                    $fetch .= $v;
                } else {
                    continue;
                }
            }
        }
        foreach($tags as $i=>$tag) {
            if(strpos($tag,$spacer)!==false) $tags[$i] = str_replace($spacer, '', $tag);
        }
        return $tags;
    }

    /**
     * Merge content fields and TVs
     *
     * @param $content
     * @param bool $ph
     * @return string
     * @internal param string $template
     */
    public function mergeDocumentContent($content, $ph = false)
    {
        if ($this->config['enable_at_syntax']) {
            if (stripos($content, '<@LITERAL>') !== false) {
                $content = $this->escapeLiteralTagsContent($content);
            }
        }
        if (strpos($content, '[*') === false) {
            return $content;
        }
        if (!isset($this->documentIdentifier)) {
            return $content;
        }
        if (!isset($this->documentObject) || empty($this->documentObject)) {
            return $content;
        }

        if (!$ph) {
            $ph = $this->documentObject;
        }

        $matches = $this->getTagsFromContent($content, '[*', '*]');
        if (!$matches) {
            return $content;
        }

        foreach ($matches[1] as $i => $key) {
            if(strpos($key,'[+')!==false) continue; // Allow chunk {{chunk?&param=`xxx`}} with [*tv_name_[+param+]*] as content
            if (substr($key, 0, 1) == '#') {
                $key = substr($key, 1);
            } // remove # for QuickEdit format

            list($key, $modifiers) = $this->splitKeyAndFilter($key);
            if (strpos($key, '@') !== false) {
                list($key, $context) = explode('@', $key, 2);
            } else {
                $context = false;
            }

            // if(!isset($ph[$key]) && !$context) continue; // #1218 TVs/PHs will not be rendered if custom_meta_title is not assigned to template like [*custom_meta_title:ne:then=`[*custom_meta_title*]`:else=`[*pagetitle*]`*]
            if ($context) {
                $value = $this->_contextValue("{$key}@{$context}", $this->documentObject['parent']);
            } else {
                $value = isset($ph[$key]) ? $ph[$key] : '';
            }

            if (is_array($value)) {
                include_once(MODX_MANAGER_PATH . 'includes/tmplvars.format.inc.php');
                include_once(MODX_MANAGER_PATH . 'includes/tmplvars.commands.inc.php');
                $value = getTVDisplayFormat($value[0], $value[1], $value[2], $value[3], $value[4]);
            }

            $s = &$matches[0][$i];
            if ($modifiers !== false) {
                $value = $this->applyFilter($value, $modifiers, $key);
            }

            if (strpos($content, $s) !== false) {
                $content = str_replace($s, $value, $content);
            } elseif($this->debug) {
                $this->addLog('mergeDocumentContent parse error', $_SERVER['REQUEST_URI'] . $s, 2);
            }
        }

        return $content;
    }

    /**
     * @param $key
     * @param bool|int $parent
     * @return bool|mixed|string
     */
    public function _contextValue($key, $parent = false)
    {
        if (preg_match('/@\d+\/u/', $key)) {
            $key = str_replace(array('@', '/u'), array('@u(', ')'), $key);
        }
        list($key, $str) = explode('@', $key, 2);

        if (strpos($str, '(')) {
            list($context, $option) = explode('(', $str, 2);
        } else {
            list($context, $option) = array($str, false);
        }

        if ($option) {
            $option = trim($option, ')(\'"`');
        }

        switch (strtolower($context)) {
            case 'site_start':
                $docid = $this->config['site_start'];
                break;
            case 'parent':
            case 'p':
                $docid = $parent;
                if ($docid == 0) {
                    $docid = $this->config['site_start'];
                }
                break;
            case 'ultimateparent':
            case 'uparent':
            case 'up':
            case 'u':
                if (strpos($str, '(') !== false) {
                    $top = substr($str, strpos($str, '('));
                    $top = trim($top, '()"\'');
                } else {
                    $top = 0;
                }
                $docid = $this->getUltimateParentId($this->documentIdentifier, $top);
                break;
            case 'alias':
                $str = substr($str, strpos($str, '('));
                $str = trim($str, '()"\'');
                $docid = $this->getIdFromAlias($str);
                break;
            case 'prev':
                if (!$option) {
                    $option = 'menuindex,ASC';
                } elseif (strpos($option, ',') === false) {
                    $option .= ',ASC';
                }
                list($by, $dir) = explode(',', $option, 2);
                $children = $this->getActiveChildren($parent, $by, $dir);
                $find = false;
                $prev = false;
                foreach ($children as $row) {
                    if ($row['id'] == $this->documentIdentifier) {
                        $find = true;
                        break;
                    }
                    $prev = $row;
                }
                if ($find) {
                    if (isset($prev[$key])) {
                        return $prev[$key];
                    } else {
                        $docid = $prev['id'];
                    }
                } else {
                    $docid = '';
                }
                break;
            case 'next':
                if (!$option) {
                    $option = 'menuindex,ASC';
                } elseif (strpos($option, ',') === false) {
                    $option .= ',ASC';
                }
                list($by, $dir) = explode(',', $option, 2);
                $children = $this->getActiveChildren($parent, $by, $dir);
                $find = false;
                $next = false;
                foreach ($children as $row) {
                    if ($find) {
                        $next = $row;
                        break;
                    }
                    if ($row['id'] == $this->documentIdentifier) {
                        $find = true;
                    }
                }
                if ($find) {
                    if (isset($next[$key])) {
                        return $next[$key];
                    } else {
                        $docid = $next['id'];
                    }
                } else {
                    $docid = '';
                }
                break;
            default:
                $docid = $str;
        }
        if (preg_match('@^[1-9][0-9]*$@', $docid)) {
            $value = $this->getField($key, $docid);
        } else {
            $value = '';
        }
        return $value;
    }

    /**
     * Merge system settings
     *
     * @param $content
     * @param bool|array $ph
     * @return string
     * @internal param string $template
     */
    public function mergeSettingsContent($content, $ph = false)
    {
        if ($this->config['enable_at_syntax']) {
            if (stripos($content, '<@LITERAL>') !== false) {
                $content = $this->escapeLiteralTagsContent($content);
            }
        }
        if (strpos($content, '[(') === false) {
            return $content;
        }

        if (empty($ph)) {
            $ph = $this->config;
        }

        $matches = $this->getTagsFromContent($content, '[(', ')]');
        if (empty($matches)) {
            return $content;
        }

        foreach ($matches[1] as $i => $key) {
            list($key, $modifiers) = $this->splitKeyAndFilter($key);

            if (isset($ph[$key])) {
                $value = $ph[$key];
            } else {
                continue;
            }

            if ($modifiers !== false) {
                $value = $this->applyFilter($value, $modifiers, $key);
            }
            $s = &$matches[0][$i];
            if (strpos($content, $s) !== false) {
                $content = str_replace($s, $value, $content);
            } elseif($this->debug) {
                $this->addLog('mergeSettingsContent parse error', $_SERVER['REQUEST_URI'] . $s, 2);
            }
        }
        return $content;
    }

    /**
     * Merge chunks
     *
     * @param string $content
     * @param bool|array $ph
     * @return string
     */
    public function mergeChunkContent($content, $ph = false)
    {
        if ($this->config['enable_at_syntax']) {
            if (strpos($content, '{{ ') !== false) {
                $content = str_replace(array('{{ ', ' }}'), array('\{\{ ', ' \}\}'), $content);
            }
            if (stripos($content, '<@LITERAL>') !== false) {
                $content = $this->escapeLiteralTagsContent($content);
            }
        }
        if (strpos($content, '{{') === false) {
            return $content;
        }

        if (empty($ph)) {
            $ph = $this->chunkCache;
        }

        $matches = $this->getTagsFromContent($content, '{{', '}}');
        if (empty($matches)) {
            return $content;
        }

        foreach ($matches[1] as $i => $key) {
            $snip_call = $this->_split_snip_call($key);
            $key = $snip_call['name'];
            $params = $this->getParamsFromString($snip_call['params']);

            list($key, $modifiers) = $this->splitKeyAndFilter($key);

            if (!isset($ph[$key])) {
                $ph[$key] = $this->getChunk($key);
            }
            $value = $ph[$key];

            if (is_null($value)) {
                continue;
            }

            $value = $this->parseText($value, $params); // parse local scope placeholers for ConditionalTags
            $value = $this->mergePlaceholderContent($value, $params);  // parse page global placeholers
            if ($this->config['enable_at_syntax']) {
                $value = $this->mergeConditionalTagsContent($value);
            }
            $value = $this->mergeDocumentContent($value);
            $value = $this->mergeSettingsContent($value);
            $value = $this->mergeChunkContent($value);

            if ($modifiers !== false) {
                $value = $this->applyFilter($value, $modifiers, $key);
            }

            $s = &$matches[0][$i];
            if (strpos($content, $s) !== false) {
                $content = str_replace($s, $value, $content);
            } elseif($this->debug) {
                $this->addLog('mergeChunkContent parse error', $_SERVER['REQUEST_URI'] . $s, 2);
            }
        }
        return $content;
    }

    /**
     * Merge placeholder values
     *
     * @param string $content
     * @param bool|array $ph
     * @return string
     */
    public function mergePlaceholderContent($content, $ph = false)
    {

        if ($this->config['enable_at_syntax']) {
            if (stripos($content, '<@LITERAL>') !== false) {
                $content = $this->escapeLiteralTagsContent($content);
            }
        }
        if (strpos($content, '[+') === false) {
            return $content;
        }

        if (empty($ph)) {
            $ph = $this->placeholders;
        }

        if ($this->config['enable_at_syntax']) {
            $content = $this->mergeConditionalTagsContent($content);
        }

        $content = $this->mergeDocumentContent($content);
        $content = $this->mergeSettingsContent($content);
        $matches = $this->getTagsFromContent($content, '[+', '+]');
        if (empty($matches)) {
            return $content;
        }
        foreach ($matches[1] as $i => $key) {

            list($key, $modifiers) = $this->splitKeyAndFilter($key);

            if (isset($ph[$key])) {
                $value = $ph[$key];
            } elseif ($key === 'phx') {
                $value = '';
            } else {
                continue;
            }

            if ($modifiers !== false) {
                $modifiers = $this->mergePlaceholderContent($modifiers);
                $value = $this->applyFilter($value, $modifiers, $key);
            }
            $s = &$matches[0][$i];
            if (strpos($content, $s) !== false) {
                $content = str_replace($s, $value, $content);
            } elseif($this->debug) {
                $this->addLog('mergePlaceholderContent parse error', $_SERVER['REQUEST_URI'] . $s, 2);
            }
        }
        return $content;
    }

    /**
     * @param $content
     * @param string $iftag
     * @param string $elseiftag
     * @param string $elsetag
     * @param string $endiftag
     * @return mixed|string
     */
    public function mergeConditionalTagsContent($content, $iftag = '<@IF:', $elseiftag = '<@ELSEIF:', $elsetag = '<@ELSE>', $endiftag = '<@ENDIF>')
    {
        if (strpos($content, '@IF') !== false) {
            $content = $this->_prepareCTag($content, $iftag, $elseiftag, $elsetag, $endiftag);
        }

        if (strpos($content, $iftag) === false) {
            return $content;
        }

        $sp = '#' . md5('ConditionalTags' . $_SERVER['REQUEST_TIME']) . '#';
        $content = str_replace(array('<?php', '<?=', '<?', '?>'), array("{$sp}b", "{$sp}p", "{$sp}s", "{$sp}e"), $content);

        $pieces = explode('<@IF:', $content);
        foreach ($pieces as $i => $split) {
            if ($i === 0) {
                $content = $split;
                continue;
            }
            list($cmd, $text) = explode('>', $split, 2);
            $cmd = str_replace("'", "\'", $cmd);
            $content .= "<?php if(\$this->_parseCTagCMD('" . $cmd . "')): ?>";
            $content .= $text;
        }
        $pieces = explode('<@ELSEIF:', $content);
        foreach ($pieces as $i => $split) {
            if ($i === 0) {
                $content = $split;
                continue;
            }
            list($cmd, $text) = explode('>', $split, 2);
            $cmd = str_replace("'", "\'", $cmd);
            $content .= "<?php elseif(\$this->_parseCTagCMD('" . $cmd . "')): ?>";
            $content .= $text;
        }

        $content = str_replace(array('<@ELSE>', '<@ENDIF>'), array('<?php else:?>', '<?php endif;?>'), $content);
        ob_start();
        $content = eval('?>' . $content);
        $content = ob_get_clean();
        $content = str_replace(array("{$sp}b", "{$sp}p", "{$sp}s", "{$sp}e"), array('<?php', '<?=', '<?', '?>'), $content);

        return $content;
    }

    /**
     * @param $content
     * @param string $iftag
     * @param string $elseiftag
     * @param string $elsetag
     * @param string $endiftag
     * @return mixed
     */
    private function _prepareCTag($content, $iftag = '<@IF:', $elseiftag = '<@ELSEIF:', $elsetag = '<@ELSE>', $endiftag = '<@ENDIF>')
    {
        if (strpos($content, '<!--@IF ') !== false) {
            $content = str_replace('<!--@IF ', $iftag, $content);
        } // for jp
        if (strpos($content, '<!--@IF:') !== false) {
            $content = str_replace('<!--@IF:', $iftag, $content);
        }
        if (strpos($content, $iftag) === false) {
            return $content;
        }
        if (strpos($content, '<!--@ELSEIF:') !== false) {
            $content = str_replace('<!--@ELSEIF:', $elseiftag, $content);
        } // for jp
        if (strpos($content, '<!--@ELSE-->') !== false) {
            $content = str_replace('<!--@ELSE-->', $elsetag, $content);
        }  // for jp
        if (strpos($content, '<!--@ENDIF-->') !== false) {
            $content = str_replace('<!--@ENDIF-->', $endiftag, $content);
        }    // for jp
        if (strpos($content, '<@ENDIF-->') !== false) {
            $content = str_replace('<@ENDIF-->', $endiftag, $content);
        }
        $tags = array($iftag, $elseiftag, $elsetag, $endiftag);
        $content = str_ireplace($tags, $tags, $content); // Change to capital letters
        return $content;
    }

    /**
     * @param $cmd
     * @return mixed|string
     */
    private function _parseCTagCMD($cmd)
    {
        $cmd = trim($cmd);
        $reverse = substr($cmd, 0, 1) === '!' ? true : false;
        if ($reverse) {
            $cmd = ltrim($cmd, '!');
        }
        if (strpos($cmd, '[!') !== false) {
            $cmd = str_replace(array('[!', '!]'), array('[[', ']]'), $cmd);
        }
        $safe = 0;
        while ($safe < 20) {
            $bt = md5($cmd);
            if (strpos($cmd, '[*') !== false) {
                $cmd = $this->mergeDocumentContent($cmd);
            }
            if (strpos($cmd, '[(') !== false) {
                $cmd = $this->mergeSettingsContent($cmd);
            }
            if (strpos($cmd, '{{') !== false) {
                $cmd = $this->mergeChunkContent($cmd);
            }
            if (strpos($cmd, '[[') !== false) {
                $cmd = $this->evalSnippets($cmd);
            }
            if (strpos($cmd, '[+') !== false && strpos($cmd, '[[') === false) {
                $cmd = $this->mergePlaceholderContent($cmd);
            }
            if ($bt === md5($cmd)) {
                break;
            }
            $safe++;
        }
        $cmd = ltrim($cmd);
        $cmd = rtrim($cmd, '-');
        $cmd = str_ireplace(array(' and ', ' or '), array('&&', '||'), $cmd);

        if (!preg_match('@^[0-9]*$@', $cmd) && preg_match('@^[0-9<= \-\+\*/\(\)%!&|]*$@', $cmd)) {
            $cmd = eval("return {$cmd};");
        } else {
            $_ = explode(',', '[*,[(,{{,[[,[!,[+');
            foreach ($_ as $left) {
                if (strpos($cmd, $left) !== false) {
                    $cmd = 0;
                    break;
                }
            }
        }
        $cmd = trim($cmd);
        if (!preg_match('@^[0-9]+$@', $cmd)) {
            $cmd = empty($cmd) ? 0 : 1;
        } elseif ($cmd <= 0) {
            $cmd = 0;
        }

        if ($reverse) {
            $cmd = !$cmd;
        }

        return $cmd;
    }

    /**
     * Remove Comment-Tags from output like <!--@- Comment -@-->
     * @param $content
     * @param string $left
     * @param string $right
     * @return mixed
     */
    function ignoreCommentedTagsContent($content, $left = '<!--@-', $right = '-@-->')
    {
        if (strpos($content, $left) === false) {
            return $content;
        }

        $matches = $this->getTagsFromContent($content, $left, $right);
        if (!empty($matches)) {
            foreach ($matches[0] as $i => $v) {
                $addBreakMatches[$i] = $v . "\n";
            }
            $content = str_replace($addBreakMatches, '', $content);
            if (strpos($content, $left) !== false) {
                $content = str_replace($matches[0], '', $content);
            }
        }
        return $content;
    }

    /**
     * @param $content
     * @param string $left
     * @param string $right
     * @return mixed
     */
    public function escapeLiteralTagsContent($content, $left = '<@LITERAL>', $right = '<@ENDLITERAL>')
    {
        if (stripos($content, $left) === false) {
            return $content;
        }

        $matches = $this->getTagsFromContent($content, $left, $right);
        if (empty($matches)) {
            return $content;
        }

        list($sTags, $rTags) = $this->getTagsForEscape();
        foreach ($matches[1] as $i => $v) {
            $v = str_ireplace($sTags, $rTags, $v);
            $s = &$matches[0][$i];
            if (strpos($content, $s) !== false) {
                $content = str_replace($s, $v, $content);
            } elseif($this->debug) {
                $this->addLog('ignoreCommentedTagsContent parse error', $_SERVER['REQUEST_URI'] . $s, 2);
            }
        }
        return $content;
    }

    /**
     * Detect PHP error according to MODX error level
     *
     * @param integer $error PHP error level
     * @return boolean Error detected
     */

    public function detectError($error)
    {
        $detected = false;
        if ($this->config['error_reporting'] == 99 && $error) {
            $detected = true;
        } elseif ($this->config['error_reporting'] == 2 && ($error & ~E_NOTICE)) {
            $detected = true;
        } elseif ($this->config['error_reporting'] == 1 && ($error & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT)) {
            $detected = true;
        }
        return $detected;
    }

    /**
     * Run a plugin
     *
     * @param string $pluginCode Code to run
     * @param array $params
     */
    public function evalPlugin($pluginCode, $params)
    {
        $modx = &$this;
        $modx->event->params = &$params; // store params inside event object
        if (is_array($params)) {
            extract($params, EXTR_SKIP);
        }
        /* if uncomment incorrect work plugin, cant understend where use this code and for what?
        // This code will avoid further execution of plugins in case they cause a fatal-error. clearCache() will delete those locks to allow execution of locked plugins again.
        // Related to https://github.com/modxcms/evolution/issues/1130
        $lock_file_path = MODX_BASE_PATH . 'assets/cache/lock_' . str_replace(' ','-',strtolower($this->event->activePlugin)) . '.pageCache.php';
        if($this->isBackend()) {
            if(is_file($lock_file_path)) {
                $msg = sprintf("Plugin parse error, Temporarily disabled '%s'.", $this->event->activePlugin);
                $this->logEvent(0, 3, $msg, $msg);
                return;
            }
            elseif(stripos($this->event->activePlugin,'ElementsInTree')===false) touch($lock_file_path);
        }*/
        ob_start();
        eval($pluginCode);
        $msg = ob_get_contents();
        ob_end_clean();
        // When reached here, no fatal error occured so the lock should be removed.
        /*if(is_file($lock_file_path)) unlink($lock_file_path);*/

        if ((0 < $this->config['error_reporting']) && $msg && isset($php_errormsg)) {
            $error_info = error_get_last();
            if ($this->detectError($error_info['type'])) {
                $msg = ($msg === false) ? 'ob_get_contents() error' : $msg;
                $this->messageQuit('PHP Parse Error', '', true, $error_info['type'], $error_info['file'], 'Plugin', $error_info['message'], $error_info['line'], $msg);
                if ($this->isBackend()) {
                    $this->event->alert('An error occurred while loading. Please see the event log for more information.<p>' . $msg . '</p>');
                }
            }
        } else {
            echo $msg;
        }
        unset($modx->event->params);
    }

    /**
     * Run a snippet
     *
     * @param $phpcode
     * @param array $params
     * @return string
     * @internal param string $snippet Code to run
     */
    public function evalSnippet($phpcode, $params)
    {
        $modx = &$this;
        /*
        if(isset($params) && is_array($params)) {
            foreach($params as $k=>$v) {
                $v = strtolower($v);
                if($v==='false')    $params[$k] = false;
                elseif($v==='true') $params[$k] = true;
            }
        }*/
        $modx->event->params = &$params; // store params inside event object
        if (is_array($params)) {
            extract($params, EXTR_SKIP);
        }
        ob_start();
        if (strpos($phpcode, ';') !== false) {
            $return = eval($phpcode);
        } else {
            $return = call_user_func_array($phpcode, array($params));
        }
        $echo = ob_get_contents();
        ob_end_clean();
        if ((0 < $this->config['error_reporting']) && isset($php_errormsg)) {
            $error_info = error_get_last();
            if ($this->detectError($error_info['type'])) {
                $echo = ($echo === false) ? 'ob_get_contents() error' : $echo;
                $this->messageQuit('PHP Parse Error', '', true, $error_info['type'], $error_info['file'], 'Snippet', $error_info['message'], $error_info['line'], $echo);
                if ($this->isBackend()) {
                    $this->event->alert('An error occurred while loading. Please see the event log for more information<p>' . $echo . $return . '</p>');
                }
            }
        }
        unset($modx->event->params);
        if (is_array($return) || is_object($return)) {
            return $return;
        } else {
            return $echo . $return;
        }
    }

    /**
     * Run snippets as per the tags in $documentSource and replace the tags with the returned values.
     *
     * @param $content
     * @return string
     * @internal param string $documentSource
     */
    public function evalSnippets($content)
    {
        if (strpos($content, '[[') === false) {
            return $content;
        }

        $matches = $this->getTagsFromContent($content, '[[', ']]');

        if (empty($matches)) {
            return $content;
        }

        $this->snipLapCount++;
        if ($this->dumpSnippets) {
            $this->snippetsCode .= sprintf('<fieldset><legend><b style="color: #821517;">PARSE PASS %s</b></legend><p>The following snippets (if any) were parsed during this pass.</p>', $this->snipLapCount);
        }

        foreach ($matches[1] as $i => $call) {
            $s = &$matches[0][$i];
            if (substr($call, 0, 2) === '$_') {
                if (strpos($content, '_PHX_INTERNAL_') === false) {
                    $value = $this->_getSGVar($call);
                } else {
                    $value = $s;
                }
                if (strpos($content, $s) !== false) {
                    $content = str_replace($s, $value, $content);
                } elseif($this->debug) {
                    $this->addLog('evalSnippetsSGVar parse error', $_SERVER['REQUEST_URI'] . $s, 2);
                }
                continue;
            }
            $value = $this->_get_snip_result($call);
            if (is_null($value)) {
                continue;
            }

            if (strpos($content, $s) !== false) {
                $content = str_replace($s, $value, $content);
            } elseif($this->debug) {
                $this->addLog('evalSnippets parse error', $_SERVER['REQUEST_URI'] . $s, 2);
            }
        }

        if ($this->dumpSnippets) {
            $this->snippetsCode .= '</fieldset><br />';
        }

        return $content;
    }

    /**
     * @param $value
     * @return mixed|string
     */
    public function _getSGVar($value)
    { // Get super globals
        $key = $value;
        $_ = $this->config['enable_filter'];
        $this->config['enable_filter'] = 1;
        list($key, $modifiers) = $this->splitKeyAndFilter($key);
        $this->config['enable_filter'] = $_;
        $key = str_replace(array('(', ')'), array("['", "']"), $key);
        $key = rtrim($key, ';');
        if (strpos($key, '$_SESSION') !== false) {
            $_ = $_SESSION;
            $key = str_replace('$_SESSION', '$_', $key);
            if (isset($_['mgrFormValues'])) {
                unset($_['mgrFormValues']);
            }
            if (isset($_['token'])) {
                unset($_['token']);
            }
        }
        if (strpos($key, '[') !== false) {
            $value = $key ? eval("return {$key};") : '';
        } elseif (0 < eval("return count({$key});")) {
            $value = eval("return print_r({$key},true);");
        } else {
            $value = '';
        }
        if ($modifiers !== false) {
            $value = $this->applyFilter($value, $modifiers, $key);
        }
        return $value;
    }

    /**
     * @param $piece
     * @return null|string
     */
    private function _get_snip_result($piece)
    {
        if (ltrim($piece) !== $piece) {
            return '';
        }

        $eventtime = $this->dumpSnippets ? $this->getMicroTime() : 0;

        $snip_call = $this->_split_snip_call($piece);
        $key = $snip_call['name'];

        list($key, $modifiers) = $this->splitKeyAndFilter($key);
        $snip_call['name'] = $key;
        $snippetObject = $this->_getSnippetObject($key);
        if (is_null($snippetObject['content'])) {
            return null;
        }

        $this->currentSnippet = $snippetObject['name'];

        // current params
        $params = $this->getParamsFromString($snip_call['params']);

        if (!isset($snippetObject['properties'])) {
            $snippetObject['properties'] = '';
        }
        $default_params = $this->parseProperties($snippetObject['properties'], $this->currentSnippet, 'snippet');
        $params = array_merge($default_params, $params);

        $value = $this->evalSnippet($snippetObject['content'], $params);
        $this->currentSnippet = '';
        if ($modifiers !== false) {
            $value = $this->applyFilter($value, $modifiers, $key);
        }

        if ($this->dumpSnippets) {
            $eventtime = $this->getMicroTime() - $eventtime;
            $eventtime = sprintf('%2.2f ms', $eventtime * 1000);
            $code = str_replace("\t", '  ', $this->htmlspecialchars($value));
            $piece = str_replace("\t", '  ', $this->htmlspecialchars($piece));
            $print_r_params = str_replace("\t", '  ', $this->htmlspecialchars('$modx->event->params = ' . print_r($params, true)));
            $this->snippetsCode .= sprintf('<fieldset style="margin:1em;"><legend><b>%s</b>(%s)</legend><pre style="white-space: pre-wrap;background-color:#fff;width:90%%;">[[%s]]</pre><pre style="white-space: pre-wrap;background-color:#fff;width:90%%;">%s</pre><pre style="white-space: pre-wrap;background-color:#fff;width:90%%;">%s</pre></fieldset>', $snippetObject['name'], $eventtime, $piece, $print_r_params, $code);
            $this->snippetsTime[] = array('sname' => $key, 'time' => $eventtime);
        }
        return $value;
    }

    /**
     * @param string $string
     * @return array
     */
    public function getParamsFromString($string = '')
    {
        if (empty($string)) {
            return array();
        }

        if (strpos($string, '&_PHX_INTERNAL_') !== false) {
            $string = str_replace(array('&_PHX_INTERNAL_091_&', '&_PHX_INTERNAL_093_&'), array('[', ']'), $string);
        }

        $_ = $this->documentOutput;
        $this->documentOutput = $string;
        $this->invokeEvent('OnBeforeParseParams');
        $string = $this->documentOutput;
        $this->documentOutput = $_;

        $_tmp = $string;
        $_tmp = ltrim($_tmp, '?&');
        $temp_params = array();
        $key = '';
        $value = null;
        while ($_tmp !== '') {
            $bt = $_tmp;
            $char = substr($_tmp, 0, 1);
            $_tmp = substr($_tmp, 1);

            if ($char === '=') {
                $_tmp = trim($_tmp);
                $delim = substr($_tmp, 0, 1);
                if (in_array($delim, array('"', "'", '`'))) {
                    $null = null;
                    //list(, $value, $_tmp)
                    list($null, $value, $_tmp) = explode($delim, $_tmp, 3);
                    unset($null);

                    if (substr(trim($_tmp), 0, 2) === '//') {
                        $_tmp = strstr(trim($_tmp), "\n");
                    }
                    $i = 0;
                    while ($delim === '`' && substr(trim($_tmp), 0, 1) !== '&' && 1 < substr_count($_tmp, '`')) {
                        list($inner, $outer, $_tmp) = explode('`', $_tmp, 3);
                        $value .= "`{$inner}`{$outer}";
                        $i++;
                        if (200 < $i) {
                            exit('The nest of values are hard to read. Please use three different quotes.');
                        }
                    }
                    if ($i && $delim === '`') {
                        $value = rtrim($value, '`');
                    }
                } elseif (strpos($_tmp, '&') !== false) {
                    list($value, $_tmp) = explode('&', $_tmp, 2);
                    $value = trim($value);
                } else {
                    $value = $_tmp;
                    $_tmp = '';
                }
            } elseif ($char === '&') {
                if (trim($key) !== '') {
                    $value = '1';
                } else {
                    continue;
                }
            } elseif ($_tmp === '') {
                $key .= $char;
                $value = '1';
            } elseif ($key !== '' || trim($char) !== '') {
                $key .= $char;
            }

            if (isset($value) && !is_null($value)) {
                if (strpos($key, 'amp;') !== false) {
                    $key = str_replace('amp;', '', $key);
                }
                $key = trim($key);
                if (strpos($value, '[!') !== false) {
                    $value = str_replace(array('[!', '!]'), array('[[', ']]'), $value);
                }
                $value = $this->mergeDocumentContent($value);
                $value = $this->mergeSettingsContent($value);
                $value = $this->mergeChunkContent($value);
                $value = $this->evalSnippets($value);
                if (substr($value, 0, 6) !== '@CODE:') {
                    $value = $this->mergePlaceholderContent($value);
                }

                $temp_params[][$key] = $value;

                $key = '';
                $value = null;

                $_tmp = ltrim($_tmp, " ,\t");
                if (substr($_tmp, 0, 2) === '//') {
                    $_tmp = strstr($_tmp, "\n");
                }
            }

            if ($_tmp === $bt) {
                $key = trim($key);
                if ($key !== '') {
                    $temp_params[][$key] = '';
                }
                break;
            }
        }

        foreach ($temp_params as $p) {
            $k = key($p);
            if (substr($k, -2) === '[]') {
                $k = substr($k, 0, -2);
                $params[$k][] = current($p);
            } elseif (strpos($k, '[') !== false && substr($k, -1) === ']') {
                list($k, $subk) = explode('[', $k, 2);
                $subk = substr($subk, 0, -1);
                $params[$k][$subk] = current($p);
            } else {
                $params[$k] = current($p);
            }
        }
        return $params;
    }

    /**
     * @param $str
     * @return bool|int
     */
    public function _getSplitPosition($str)
    {
        $closeOpt = false;
        $maybePos = false;
        $inFilter = false;
        $pos = false;
        $total = strlen($str);
        for ($i = 0; $i < $total; $i++) {
            $c = substr($str, $i, 1);
            $cc = substr($str, $i, 2);
            if (!$inFilter) {
                if ($c === ':') {
                    $inFilter = true;
                } elseif ($c === '?') {
                    $pos = $i;
                } elseif ($c === ' ') {
                    $maybePos = $i;
                } elseif ($c === '&' && $maybePos) {
                    $pos = $maybePos;
                } elseif ($c === "\n") {
                    $pos = $i;
                } else {
                    $pos = false;
                }
            } else {
                if ($cc == $closeOpt) {
                    $closeOpt = false;
                } elseif ($c == $closeOpt) {
                    $closeOpt = false;
                } elseif ($closeOpt) {
                    continue;
                } elseif ($cc === "('") {
                    $closeOpt = "')";
                } elseif ($cc === '("') {
                    $closeOpt = '")';
                } elseif ($cc === '(`') {
                    $closeOpt = '`)';
                } elseif ($c === '(') {
                    $closeOpt = ')';
                } elseif ($c === '?') {
                    $pos = $i;
                } elseif ($c === ' ' && strpos($str, '?') === false) {
                    $pos = $i;
                } else {
                    $pos = false;
                }
            }
            if ($pos) {
                break;
            }
        }
        return $pos;
    }

    /**
     * @param $call
     * @return mixed
     */
    private function _split_snip_call($call)
    {
        $spacer = md5('dummy');
        if (strpos($call, ']]>') !== false) {
            $call = str_replace(']]>', "]{$spacer}]>", $call);
        }

        $splitPosition = $this->_getSplitPosition($call);

        if ($splitPosition !== false) {
            $name = substr($call, 0, $splitPosition);
            $params = substr($call, $splitPosition + 1);
        } else {
            $name = $call;
            $params = '';
        }

        $snip['name'] = trim($name);
        if (strpos($params, $spacer) !== false) {
            $params = str_replace("]{$spacer}]>", ']]>', $params);
        }
        $snip['params'] = ltrim($params, "?& \t\n");

        return $snip;
    }

    /**
     * @param $snip_name
     * @return mixed
     */
    private function _getSnippetObject($snip_name)
    {
        if (isset($this->snippetCache[$snip_name])) {
            $snippetObject['name'] = $snip_name;
            $snippetObject['content'] = $this->snippetCache[$snip_name];
            if (isset($this->snippetCache["{$snip_name}Props"])) {
                if (!isset($this->snippetCache["{$snip_name}Props"])) {
                    $this->snippetCache["{$snip_name}Props"] = '';
                }
                $snippetObject['properties'] = $this->snippetCache["{$snip_name}Props"];
            }
        } elseif (substr($snip_name, 0, 1) === '@' && isset($this->pluginEvent[trim($snip_name, '@')])) {
            $snippetObject['name'] = trim($snip_name, '@');
            $snippetObject['content'] = sprintf('$rs=$this->invokeEvent("%s",$params);echo trim(implode("",$rs));', trim($snip_name, '@'));
            $snippetObject['properties'] = '';
        } else {
            $where = sprintf("name='%s' AND disabled=0", $this->db->escape($snip_name));
            $rs = $this->db->select('name,snippet,properties', '[+prefix+]site_snippets', $where);
            $count = $this->db->getRecordCount($rs);
            if (1 < $count) {
                exit('Error $modx->_getSnippetObject()' . $snip_name);
            }
            if ($count) {
                $row = $this->db->getRow($rs);
                $snip_content = $row['snippet'];
                $snip_prop = $row['properties'];
            } else {
                $snip_content = null;
                $snip_prop = '';
            }
            $snippetObject['name'] = $snip_name;
            $snippetObject['content'] = $snip_content;
            $snippetObject['properties'] = $snip_prop;
            $this->snippetCache[$snip_name] = $snip_content;
            $this->snippetCache["{$snip_name}Props"] = $snip_prop;
        }
        return $snippetObject;
    }

    /**
     * @param $text
     * @return mixed
     */
    public function toAlias($text)
    {
        $suff = $this->config['friendly_url_suffix'];
        return str_replace(array('.xml' . $suff, '.rss' . $suff, '.js' . $suff, '.css' . $suff, '.txt' . $suff, '.json' . $suff, '.pdf' . $suff), array('.xml', '.rss', '.js', '.css', '.txt', '.json', '.pdf'), $text);
    }

    /**
     * makeFriendlyURL
     *
     * @desc Create an URL.
     *
     * @param $pre {string} - Friendly URL Prefix. @required
     * @param $suff {string} - Friendly URL Suffix. @required
     * @param $alias {string} - Full document path. @required
     * @param int $isfolder {0; 1}
     * - Is it a folder? Default: 0.
     * @param int $id {integer}
     * - Document id. Default: 0.
     * @return mixed|string {string} - Result URL.
     * - Result URL.
     */
    public function makeFriendlyURL($pre, $suff, $alias, $isfolder = 0, $id = 0)
    {
        if ($id == $this->config['site_start'] && $this->config['seostrict'] === '1') {
            $url = $this->config['base_url'];
        } else {
            $Alias = explode('/', $alias);
            $alias = array_pop($Alias);
            $dir = implode('/', $Alias);
            unset($Alias);

            if ($this->config['make_folders'] === '1' && $isfolder == 1) {
                $suff = '/';
            }

            $url = ($dir != '' ? $dir . '/' : '') . $pre . $alias . $suff;
        }

        $evtOut = $this->invokeEvent('OnMakeDocUrl', array(
            'id' => $id,
            'url' => $url
        ));

        if (is_array($evtOut) && count($evtOut) > 0) {
            $url = array_pop($evtOut);
        }

        return $url;
    }

    /**
     * Convert URL tags [~...~] to URLs
     *
     * @param string $documentSource
     * @return string
     */
    public function rewriteUrls($documentSource)
    {
        // rewrite the urls
        if ($this->config['friendly_urls'] == 1) {
            $aliases = array();
            if (is_array($this->documentListing)) {
                foreach ($this->documentListing as $path => $docid) { // This is big Loop on large site!
                    $aliases[$docid] = $path;
                    $isfolder[$docid] = $this->aliasListing[$docid]['isfolder'];
                }
            }
            if (isset($this->config['full_aliaslisting']) && $this->config['full_aliaslisting'] == 1){
                preg_match_all('!\[\~([0-9]+)\~\]!is', $documentSource, $match);
                $ids = array_unique($match['1']);
                if (is_array($ids)) {
                    foreach ($ids as $id){
                        $documentSource = str_replace('[~'.$id.'~]', $this->makeUrl($id), $documentSource);
                    }
                }
            }
            elseif ($this->config['aliaslistingfolder'] == 1) {
                preg_match_all('!\[\~([0-9]+)\~\]!is', $documentSource, $match);
                $ids = implode(',', array_unique($match['1']));
                if ($ids) {
                    $res = $this->db->select("id,alias,isfolder,parent,alias_visible", $this->getFullTableName('site_content'), "id IN (" . $ids . ") AND isfolder = '0'");
                    while ($row = $this->db->getRow($res)) {
                        if ($this->config['use_alias_path'] == '1' && $row['parent'] != 0) {
                            $parent = $row['parent'];
                            $path = $aliases[$parent];

                            while (isset($this->aliasListing[$parent]) && $this->aliasListing[$parent]['alias_visible'] == 0) {
                                $path = $this->aliasListing[$parent]['path'];
                                $parent = $this->aliasListing[$parent]['parent'];
                            }

                            $aliases[$row['id']] = $path . '/' . $row['alias'];
                        } else {
                            $aliases[$row['id']] = $row['alias'];
                        }
                        $isfolder[$row['id']] = '0';
                    }
                }
            }
            $in = '!\[\~([0-9]+)\~\]!is';
            $isfriendly = ($this->config['friendly_alias_urls'] == 1 ? 1 : 0);
            $pref = $this->config['friendly_url_prefix'];
            $suff = $this->config['friendly_url_suffix'];
            $documentSource = preg_replace_callback($in, function ($m) use ($aliases, $isfolder, $isfriendly, $pref, $suff) {
                global $modx;
                $thealias = $aliases[$m[1]];
                $thefolder = $isfolder[$m[1]];
                if ($isfriendly && isset($thealias)) {
                    //found friendly url
                    $out = ($modx->config['seostrict'] == '1' ? $modx->toAlias($modx->makeFriendlyURL($pref, $suff, $thealias, $thefolder, $m[1])) : $modx->makeFriendlyURL($pref, $suff, $thealias, $thefolder, $m[1]));
                } else {
                    //not found friendly url
                    $out = $modx->makeFriendlyURL($pref, $suff, $m[1]);
                }
                return $out;
            }, $documentSource);

        } else {
            $in = '!\[\~([0-9]+)\~\]!is';
            $out = "index.php?id=" . '\1';
            $documentSource = preg_replace($in, $out, $documentSource);
        }

        return $documentSource;
    }

    public function sendStrictURI()
    {
        $q = $this->q;
        // FIX URLs
        if (empty($this->documentIdentifier) || $this->config['seostrict'] == '0' || $this->config['friendly_urls'] == '0') {
            return;
        }
        if ($this->config['site_status'] == 0) {
            return;
        }

        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
        $len_base_url = strlen($this->config['base_url']);

        $url_path = $q;//LANG

        if (substr($url_path, 0, $len_base_url) === $this->config['base_url']) {
            $url_path = substr($url_path, $len_base_url);
        }

        $strictURL = $this->toAlias($this->makeUrl($this->documentIdentifier));

        if (substr($strictURL, 0, $len_base_url) === $this->config['base_url']) {
            $strictURL = substr($strictURL, $len_base_url);
        }
        $http_host = $_SERVER['HTTP_HOST'];
        $requestedURL = "{$scheme}://{$http_host}" . '/' . $q; //LANG

        $site_url = $this->config['site_url'];
        $url_query_string = explode('?', $_SERVER['REQUEST_URI']);
        // Strip conflicting id/q from query string
        $qstring = !empty($url_query_string[1]) ? preg_replace("#(^|&)(q|id)=[^&]+#", '', $url_query_string[1]) : '';

        if ($this->documentIdentifier == $this->config['site_start']) {
            if ($requestedURL != $this->config['site_url']) {
                // Force redirect of site start
                // $this->sendErrorPage();
                if ($qstring) {
                    $url = "{$site_url}?{$qstring}";
                } else {
                    $url = $site_url;
                }
                if ($this->config['base_url'] != $_SERVER['REQUEST_URI']) {
                    if (empty($_POST)) {
                        if (($this->config['base_url'] . '?' . $qstring) != $_SERVER['REQUEST_URI']) {
                            $this->sendRedirect($url, 0, 'REDIRECT_HEADER', 'HTTP/1.0 301 Moved Permanently');
                            exit(0);
                        }
                    }
                }
            }
        } elseif(preg_match('#/\?q\=' . $strictURL . '#i', $_SERVER['REQUEST_URI']) ||
            ($url_path != $strictURL && $this->documentIdentifier != $this->config['error_page'])
        ) {
            // Force page redirect
            //$strictURL = ltrim($strictURL,'/');
            if (!empty($qstring)) {
                $url = "{$site_url}{$strictURL}?{$qstring}";
            } else {
                $url = "{$site_url}{$strictURL}";
            }
            $this->sendRedirect($url, 0, 'REDIRECT_HEADER', 'HTTP/1.0 301 Moved Permanently');
            exit(0);
        }
        return;
    }

    /**
     * Get all db fields and TVs for a document/resource
     *
     * @param string $method
     * @param mixed $identifier
     * @param bool $isPrepareResponse
     * @return array
     */
    public function getDocumentObject($method, $identifier, $isPrepareResponse = false)
    {

        $cacheKey = md5(print_r(func_get_args(), true));
        if (isset($this->tmpCache[__FUNCTION__][$cacheKey])) {
            return $this->tmpCache[__FUNCTION__][$cacheKey];
        }

        $tblsc = $this->getFullTableName("site_content");
        $tbldg = $this->getFullTableName("document_groups");

        // allow alias to be full path
        if ($method == 'alias') {
            $identifier = $this->cleanDocumentIdentifier($identifier);
            $method = $this->documentMethod;
        }
        if ($method == 'alias' && $this->config['use_alias_path'] && array_key_exists($identifier, $this->documentListing)) {
            $method = 'id';
            $identifier = $this->documentListing[$identifier];
        }

        $out = $this->invokeEvent('OnBeforeLoadDocumentObject', compact('method', 'identifier'));
        if (is_array($out) && is_array($out[0])) {
            $documentObject = $out[0];
        } else {
            // get document groups for current user
            if ($docgrp = $this->getUserDocGroups()) {
                $docgrp = implode(",", $docgrp);
            }
            // get document
            $access = ($this->isFrontend() ? "sc.privateweb=0" : "1='" . $_SESSION['mgrRole'] . "' OR sc.privatemgr=0") . (!$docgrp ? "" : " OR dg.document_group IN ($docgrp)");
            $rs = $this->db->select('sc.*', "{$tblsc} sc
                LEFT JOIN {$tbldg} dg ON dg.document = sc.id", "sc.{$method} = '{$identifier}' AND ({$access})", "", 1);
            if ($this->db->getRecordCount($rs) < 1) {
                $seclimit = 0;
                if ($this->config['unauthorized_page']) {
                    // method may still be alias, while identifier is not full path alias, e.g. id not found above
                    if ($method === 'alias') {
                        $secrs = $this->db->select('count(dg.id)', "{$tbldg} as dg, {$tblsc} as sc", "dg.document = sc.id AND sc.alias = '{$identifier}'", '', 1);
                    } else {
                        $secrs = $this->db->select('count(id)', $tbldg, "document = '{$identifier}'", '', 1);
                    }
                    // check if file is not public
                    $seclimit = $this->db->getValue($secrs);
                }
                if ($seclimit > 0) {
                    // match found but not publicly accessible, send the visitor to the unauthorized_page
                    $this->sendUnauthorizedPage();
                    exit; // stop here
                } else {
                    $this->sendErrorPage();
                    exit;
                }
            }
            # this is now the document :) #
            $documentObject = $this->db->getRow($rs);

            if ($isPrepareResponse === 'prepareResponse') {
                $this->documentObject = &$documentObject;
            }
            $out = $this->invokeEvent('OnLoadDocumentObject', compact('method', 'identifier', 'documentObject'));
            if (is_array($out) && is_array($out[0])) {
                $documentObject = $out[0];
            }
            if ($documentObject['template']) {
                // load TVs and merge with document - Orig by Apodigm - Docvars
                $rs = $this->db->select("tv.*, IF(tvc.value!='',tvc.value,tv.default_text) as value", $this->getFullTableName("site_tmplvars") . " tv
                INNER JOIN " . $this->getFullTableName("site_tmplvar_templates") . " tvtpl ON tvtpl.tmplvarid = tv.id
                LEFT JOIN " . $this->getFullTableName("site_tmplvar_contentvalues") . " tvc ON tvc.tmplvarid=tv.id AND tvc.contentid = '{$documentObject['id']}'", "tvtpl.templateid = '{$documentObject['template']}'");
                $tmplvars = array();
                while ($row = $this->db->getRow($rs)) {
                    $tmplvars[$row['name']] = array(
                        $row['name'],
                        $row['value'],
                        $row['display'],
                        $row['display_params'],
                        $row['type']
                    );
                }
                $documentObject = array_merge($documentObject, $tmplvars);
            }
            $out = $this->invokeEvent('OnAfterLoadDocumentObject', compact('method', 'identifier', 'documentObject'));
            if (is_array($out) && array_key_exists(0, $out) !== false && is_array($out[0])) {
                $documentObject = $out[0];
            }
        }

        $this->tmpCache[__FUNCTION__][$cacheKey] = $documentObject;

        return $documentObject;
    }

    /**
     * Parse a source string.
     *
     * Handles most MODX tags. Exceptions include:
     *   - uncached snippet tags [!...!]
     *   - URL tags [~...~]
     *
     * @param string $source
     * @return string
     */
    public function parseDocumentSource($source)
    {
        // set the number of times we are to parse the document source
        $this->minParserPasses = empty ($this->minParserPasses) ? 2 : $this->minParserPasses;
        $this->maxParserPasses = empty ($this->maxParserPasses) ? 10 : $this->maxParserPasses;
        $passes = $this->minParserPasses;
        for ($i = 0; $i < $passes; $i++) {
            // get source length if this is the final pass
            if ($i == ($passes - 1)) {
                $st = md5($source);
            }
            if ($this->dumpSnippets == 1) {
                $this->snippetsCode .= "<fieldset><legend><b style='color: #821517;'>PARSE PASS " . ($i + 1) . "</b></legend><p>The following snippets (if any) were parsed during this pass.</p>";
            }

            // invoke OnParseDocument event
            $this->documentOutput = $source; // store source code so plugins can
            $this->invokeEvent("OnParseDocument"); // work on it via $modx->documentOutput
            $source = $this->documentOutput;

            if ($this->config['enable_at_syntax']) {
                $source = $this->ignoreCommentedTagsContent($source);
                $source = $this->mergeConditionalTagsContent($source);
            }

            $source = $this->mergeSettingsContent($source);
            $source = $this->mergeDocumentContent($source);
            $source = $this->mergeChunkContent($source);
            $source = $this->evalSnippets($source);
            $source = $this->mergePlaceholderContent($source);

            if ($this->dumpSnippets == 1) {
                $this->snippetsCode .= "</fieldset><br />";
            }
            if ($i == ($passes - 1) && $i < ($this->maxParserPasses - 1)) {
                // check if source content was changed
                if ($st != md5($source)) {
                    $passes++;
                } // if content change then increase passes because
            } // we have not yet reached maxParserPasses
        }
        return $source;
    }

    /**
     * Starts the parsing operations.
     *
     * - connects to the db
     * - gets the settings (including system_settings)
     * - gets the document/resource identifier as in the query string
     * - finally calls prepareResponse()
     */
    public function executeParser()
    {
        if(MODX_CLI) {
            throw new RuntimeException('Call DocumentParser::executeParser on CLI mode');
        }

        $this->registerErrorHandlers();
        $this->db->connect();

        // get the settings
        if (empty ($this->config)) {
            $this->getSettings();
        }

        $this->_IIS_furl_fix(); // IIS friendly url fix

        // check site settings
        if ($this->checkSiteStatus()) {
            // make sure the cache doesn't need updating
            $this->updatePubStatus();

            // find out which document we need to display
            $this->documentMethod = filter_input(INPUT_GET, 'q') ? 'alias' : 'id';
            $this->documentIdentifier = $this->getDocumentIdentifier($this->documentMethod);
        } else {
            header('HTTP/1.0 503 Service Unavailable');
            $this->systemCacheKey = 'unavailable';
            if (!$this->config['site_unavailable_page']) {
                // display offline message
                $this->documentContent = $this->config['site_unavailable_message'];
                $this->outputContent();
                exit; // stop processing here, as the site's offline
            } else {
                // setup offline page document settings
                $this->documentMethod = 'id';
                $this->documentIdentifier = $this->config['site_unavailable_page'];
            }
        }

        if ($this->documentMethod == "alias") {
            $this->documentIdentifier = $this->cleanDocumentIdentifier($this->documentIdentifier);

            // Check use_alias_path and check if $this->virtualDir is set to anything, then parse the path
            if ($this->config['use_alias_path'] == 1) {
                $alias = (strlen($this->virtualDir) > 0 ? $this->virtualDir . '/' : '') . $this->documentIdentifier;
                if (isset($this->documentListing[$alias])) {
                    $this->documentIdentifier = $this->documentListing[$alias];
                } else {
                    //@TODO: check new $alias;
                    if((isset($this->config['full_aliaslisting']) && $this->config['full_aliaslisting'] == 1)){
                        $this->documentIdentifier = $this->getIdFromAlias($alias);
                    }
                    elseif ($this->config['aliaslistingfolder'] == 1) {
                        $tbl_site_content = $this->getFullTableName('site_content');

                        $parentId = empty($this->virtualDir) ? 0 : $this->getIdFromAlias($this->virtualDir);
                        $parentId = ($parentId > 0) ? $parentId : '0';

                        $docAlias = $this->db->escape($this->documentIdentifier);

                        $rs = $this->db->select('id', $tbl_site_content, "deleted=0 and parent='{$parentId}' and alias='{$docAlias}'");
                        if ($this->db->getRecordCount($rs) == 0) {
                            $this->sendErrorPage();
                        }
                        $docId = $this->db->getValue($rs);

                        if (!$docId) {
                            $alias = $this->q;
                            if (!empty($this->config['friendly_url_suffix'])) {
                                $pos = strrpos($alias, $this->config['friendly_url_suffix']);

                                if ($pos !== false) {
                                    $alias = substr($alias, 0, $pos);
                                }
                            }
                            $docId = $this->getIdFromAlias($alias);
                        }

                        if ($docId > 0) {
                            $this->documentIdentifier = $docId;
                        } else {
                            /*
                            $rs  = $this->db->select('id', $tbl_site_content, "deleted=0 and alias='{$docAlias}'");
                            if($this->db->getRecordCount($rs)==0)
                            {
                                $rs  = $this->db->select('id', $tbl_site_content, "deleted=0 and id='{$docAlias}'");
                            }
                            $docId = $this->db->getValue($rs);

                            if ($docId > 0)
                            {
                                $this->documentIdentifier = $docId;

                            }else{
                            */
                            $this->sendErrorPage();
                            //}
                        }
                    } else {
                        $this->sendErrorPage();
                    }
                }
            } else {
                if (isset($this->documentListing[$this->documentIdentifier])) {
                    $this->documentIdentifier = $this->documentListing[$this->documentIdentifier];
                } else {
                    $docAlias = $this->db->escape($this->documentIdentifier);
                    $rs = $this->db->select('id', $this->getFullTableName('site_content'), "deleted=0 and alias='{$docAlias}'");
                    $this->documentIdentifier = (int)$this->db->getValue($rs);
                }
            }
            $this->documentMethod = 'id';
        }

        //$this->_fixURI();
        // invoke OnWebPageInit event
        $this->invokeEvent("OnWebPageInit");
        // invoke OnLogPageView event
        if ($this->config['track_visitors'] == 1) {
            $this->invokeEvent("OnLogPageHit");
        }
        if ($this->config['seostrict'] === '1') {
            $this->sendStrictURI();
        }
        $this->prepareResponse();
    }

    /**
     * @param $path
     * @param null $suffix
     * @return mixed
     */
    public function mb_basename($path, $suffix = null)
    {
        $exp = explode('/', $path);
        return str_replace($suffix, '', end($exp));
    }

    public function _IIS_furl_fix()
    {
        if ($this->config['friendly_urls'] != 1) {
            return;
        }

        if (strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') === false) {
            return;
        }

        $url = $_SERVER['QUERY_STRING'];
        $err = substr($url, 0, 3);
        if ($err !== '404' && $err !== '405') {
            return;
        }

        $k = array_keys($_GET);
        unset ($_GET[$k[0]]);
        unset ($_REQUEST[$k[0]]); // remove 404,405 entry
        $qp = parse_url(str_replace($this->config['site_url'], '', substr($url, 4)));
        $_SERVER['QUERY_STRING'] = $qp['query'];
        if (!empty ($qp['query'])) {
            parse_str($qp['query'], $qv);
            foreach ($qv as $n => $v) {
                $_REQUEST[$n] = $_GET[$n] = $v;
            }
        }
        $_SERVER['PHP_SELF'] = $this->config['base_url'] . $qp['path'];
        $this->q = $qp['path'];
        return $qp['path'];
    }

    /**
     * The next step called at the end of executeParser()
     *
     * - checks cache
     * - checks if document/resource is deleted/unpublished
     * - checks if resource is a weblink and redirects if so
     * - gets template and parses it
     * - ensures that postProcess is called when PHP is finished
     */
    public function prepareResponse()
    {
        // we now know the method and identifier, let's check the cache

        if ($this->config['enable_cache'] == 2 && $this->isLoggedIn()) {
            $this->config['enable_cache'] = 0;
        }

        if ($this->config['enable_cache']) {
            $this->documentContent = $this->getDocumentObjectFromCache($this->documentIdentifier, true);
        } else {
            $this->documentContent = '';
        }

        if ($this->documentContent == '') {
            // get document object from DB
            $this->documentObject = $this->getDocumentObject($this->documentMethod, $this->documentIdentifier, 'prepareResponse');

            // write the documentName to the object
            $this->documentName = &$this->documentObject['pagetitle'];

            // check if we should not hit this document
            if ($this->documentObject['donthit'] == 1) {
                $this->config['track_visitors'] = 0;
            }

            if ($this->documentObject['deleted'] == 1) {
                $this->sendErrorPage();
            } // validation routines
            elseif ($this->documentObject['published'] == 0) {
                $this->_sendErrorForUnpubPage();
            } elseif ($this->documentObject['type'] == 'reference') {
                $this->_sendRedirectForRefPage($this->documentObject['content']);
            }

            // get the template and start parsing!
            if (!$this->documentObject['template']) {
                $templateCode = '[*content*]';
            } // use blank template
            else {
                $templateCode = $this->_getTemplateCodeFromDB($this->documentObject['template']);
            }

            if (substr($templateCode, 0, 8) === '@INCLUDE') {
                $templateCode = $this->atBindInclude($templateCode);
            }


            $this->documentContent = &$templateCode;

            // invoke OnLoadWebDocument event
            $this->invokeEvent('OnLoadWebDocument');

            // Parse document source
            $this->documentContent = $this->parseDocumentSource($templateCode);

            $this->documentGenerated = 1;
        } else {
            $this->documentGenerated = 0;
        }

        if ($this->config['error_page'] == $this->documentIdentifier && $this->config['error_page'] != $this->config['site_start']) {
            header('HTTP/1.0 404 Not Found');
        }

        register_shutdown_function(array(
            &$this,
            'postProcess'
        )); // tell PHP to call postProcess when it shuts down
        $this->outputContent();
        //$this->postProcess();
    }

    public function _sendErrorForUnpubPage()
    {
        // Can't view unpublished pages !$this->checkPreview()
        if (!$this->hasPermission('view_unpublished')) {
            $this->sendErrorPage();
        } else {
            // Inculde the necessary files to check document permissions
            include_once(MODX_MANAGER_PATH . 'processors/user_documents_permissions.class.php');
            $udperms = new udperms();
            $udperms->user = $this->getLoginUserID();
            $udperms->document = $this->documentIdentifier;
            $udperms->role = $_SESSION['mgrRole'];
            // Doesn't have access to this document
            if (!$udperms->checkPermissions()) {
                $this->sendErrorPage();
            }
        }
    }

    /**
     * @param $url
     */
    public function _sendRedirectForRefPage($url)
    {
        // check whether it's a reference
        if (preg_match('@^[1-9][0-9]*$@', $url)) {
            $url = $this->makeUrl($url); // if it's a bare document id
        } elseif (strpos($url, '[~') !== false) {
            $url = $this->rewriteUrls($url); // if it's an internal docid tag, process it
        }
        $this->sendRedirect($url, 0, '', 'HTTP/1.0 301 Moved Permanently');
        exit;
    }

    /**
     * @param $templateID
     * @return mixed
     */
    public function _getTemplateCodeFromDB($templateID)
    {
        $rs = $this->db->select('content', '[+prefix+]site_templates', "id = '{$templateID}'");
        if ($this->db->getRecordCount($rs) == 1) {
            return $this->db->getValue($rs);
        } else {
            $this->messageQuit('Incorrect number of templates returned from database');
        }
    }

    /**
     * Returns an array of all parent record IDs for the id passed.
     *
     * @param int $id Docid to get parents for.
     * @param int $height The maximum number of levels to go up, default 10.
     * @return array
     */
    public function getParentIds($id, $height = 10)
    {
        $parents = array();
        while ($id && $height--) {
            $thisid = $id;
            if ($this->config['aliaslistingfolder'] == 1  || (isset($this->config['full_aliaslisting']) && $this->config['full_aliaslisting'] == 1)) {
                $id = isset($this->aliasListing[$id]['parent']) ? $this->aliasListing[$id]['parent'] : $this->db->getValue("SELECT `parent` FROM " . $this->getFullTableName("site_content") . " WHERE `id` = '{$id}' LIMIT 0,1");
                if (!$id || $id == '0') {
                    break;
                }
            } else {
                $id = $this->aliasListing[$id]['parent'];
                if (!$id) {
                    break;
                }
            }
            $parents[$thisid] = $id;
        }
        return $parents;
    }

    /**
     * @param $id
     * @param int $top
     * @return mixed
     */
    public function getUltimateParentId($id, $top = 0)
    {
        $i = 0;
        while ($id && $i < 20) {
            if ($top == $this->aliasListing[$id]['parent']) {
                break;
            }
            $id = $this->aliasListing[$id]['parent'];
            $i++;
        }
        return $id;
    }

    /**
     * Returns an array of child IDs belonging to the specified parent.
     *
     * @param int $id The parent resource/document to start from
     * @param int $depth How many levels deep to search for children, default: 10
     * @param array $children Optional array of docids to merge with the result.
     * @return array Contains the document Listing (tree) like the sitemap
     */
    public function getChildIds($id, $depth = 10, $children = array())
    {

        $cacheKey = md5(print_r(func_get_args(), true));
        if (isset($this->tmpCache[__FUNCTION__][$cacheKey])) {
            return $this->tmpCache[__FUNCTION__][$cacheKey];
        }

        if ($this->config['aliaslistingfolder'] == 1  || (isset($this->config['full_aliaslisting']) && $this->config['full_aliaslisting'] == 1)) {

            $res = $this->db->select("id,alias,isfolder,parent", $this->getFullTableName('site_content'), "parent IN (" . $id . ") AND deleted = '0'");
            $idx = array();
            while ($row = $this->db->getRow($res)) {
                $pAlias = '';
                if (isset($this->aliasListing[$row['parent']])) {
                    $pAlias .= !empty($this->aliasListing[$row['parent']]['path']) ? $this->aliasListing[$row['parent']]['path'] . '/' : '';
                    $pAlias .= !empty($this->aliasListing[$row['parent']]['alias']) ? $this->aliasListing[$row['parent']]['alias'] . '/' : '';
                };
                $children[$pAlias . $row['alias']] = $row['id'];
                if ($row['isfolder'] == 1) {
                    $idx[] = $row['id'];
                }
            }
            $depth--;
            $idx = implode(',', $idx);
            if (!empty($idx)) {
                if ($depth) {
                    $children = $this->getChildIds($idx, $depth, $children);
                }
            }
            $this->tmpCache[__FUNCTION__][$cacheKey] = $children;
            return $children;

        } else {

            // Initialise a static array to index parents->children
            static $documentMap_cache = array();
            if (!count($documentMap_cache)) {
                foreach ($this->documentMap as $document) {
                    foreach ($document as $p => $c) {
                        $documentMap_cache[$p][] = $c;
                    }
                }
            }

            // Get all the children for this parent node
            if (isset($documentMap_cache[$id])) {
                $depth--;

                foreach ($documentMap_cache[$id] as $childId) {
                    $pkey = (strlen($this->aliasListing[$childId]['path']) ? "{$this->aliasListing[$childId]['path']}/" : '') . $this->aliasListing[$childId]['alias'];
                    if (!strlen($pkey)) {
                        $pkey = "{$childId}";
                    }
                    $children[$pkey] = $childId;

                    if ($depth && isset($documentMap_cache[$childId])) {
                        $children += $this->getChildIds($childId, $depth);
                    }
                }
            }
            $this->tmpCache[__FUNCTION__][$cacheKey] = $children;
            return $children;

        }
    }

    /**
     * Displays a javascript alert message in the web browser and quit
     *
     * @param string $msg Message to show
     * @param string $url URL to redirect to
     */
    public function webAlertAndQuit($msg, $url = '')
    {
        global $modx_manager_charset, $modx_lang_attribute, $modx_textdir, $lastInstallTime;

        if(empty($modx_manager_charset)) {
            $modx_manager_charset = $this->getConfig('modx_charset');
        }

        if(empty($modx_lang_attribute)) {
            $modx_lang_attribute = $this->getConfig('lang_code');
        }

        if(empty($modx_textdir)) {
            $modx_textdir = $this->getConfig('manager_direction');
        }
        $textdir = $modx_textdir === 'rtl' ? 'rtl' : 'ltr';

        switch (true) {
            case (0 === stripos($url, 'javascript:')):
                $fnc = substr($url, 11);
                break;
            case $url === '#':
                $fnc = '';
                break;
            case empty($url):
                $fnc = 'history.back(-1);';
                break;
            default:
                $fnc = "window.location.href='" . addslashes($url) . "';";
        }

        $style = '';
        if (IN_MANAGER_MODE) {
            if (empty($lastInstallTime)) {
                $lastInstallTime = time();
            }

            $path =  'media/style/' . $this->getConfig('manager_theme') . '/';
            $css = file_exists(MODX_MANAGER_PATH .  $path . '/css/styles.min.css') ? '/css/styles.min.css' : 'style.css';
            $style = '<link rel="stylesheet" type="text/css" href="' . MODX_MANAGER_URL . $path . $css . '?v=' . $lastInstallTime . '"/>';
        }

        ob_get_clean();
        echo "<!DOCTYPE html>
            <html lang=\"{$modx_lang_attribute}\" dir=\"{$textdir}\">
                <head>
                <title>MODX :: Alert</title>
                <meta http-equiv=\"Content-Type\" content=\"text/html; charset={$modx_manager_charset};\">
                {$style}
                <script>
                    function __alertQuit() {
                        var el = document.querySelector('p');
                        alert(el.innerHTML);
                        el.remove();
                        {$fnc}
                    }
                    window.setTimeout('__alertQuit();',100);
                </script>
            </head>
            <body>
                <p>{$msg}</p>
            </body>
        </html>";
        exit;
    }

    /**
     * Returns 1 if user has the currect permission
     *
     * @param string $pm Permission name
     * @return int Why not bool?
     */
    public function hasPermission($pm)
    {
        $state = 0;
        $pms = $_SESSION['mgrPermissions'];
        if ($pms) {
            $state = ((bool)$pms[$pm] === true);
        }
        return (int)$state;
    }

    /**
     * Returns true if element is locked
     *
     * @param int $type Types: 1=template, 2=tv, 3=chunk, 4=snippet, 5=plugin, 6=module, 7=resource, 8=role
     * @param int $id Element- / Resource-id
     * @param bool $includeThisUser true = Return also info about actual user
     * @return array lock-details or null
     */
    public function elementIsLocked($type, $id, $includeThisUser = false)
    {
        $id = (int)$id;
        $type = (int)$type;
        if (!$type || !$id) {
            return null;
        }

        // Build lockedElements-Cache at first call
        $this->buildLockedElementsCache();

        if (!$includeThisUser && $this->lockedElements[$type][$id]['sid'] == $this->sid) {
            return null;
        }

        if (isset($this->lockedElements[$type][$id])) {
            return $this->lockedElements[$type][$id];
        } else {
            return null;
        }
    }

    /**
     * Returns Locked Elements as Array
     *
     * @param int $type Types: 0=all, 1=template, 2=tv, 3=chunk, 4=snippet, 5=plugin, 6=module, 7=resource, 8=role
     * @param bool $minimumDetails true =
     * @return array|mixed|null
     */
    public function getLockedElements($type = 0, $minimumDetails = false)
    {
        $this->buildLockedElementsCache();

        if (!$minimumDetails) {
            $lockedElements = $this->lockedElements;
        } else {
            // Minimum details for HTML / Ajax-requests
            $lockedElements = array();
            foreach ($this->lockedElements as $elType => $elements) {
                foreach ($elements as $elId => $el) {
                    $lockedElements[$elType][$elId] = array(
                        'username' => $el['username'],
                        'lasthit_df' => $el['lasthit_df'],
                        'state' => $this->determineLockState($el['internalKey'])
                    );
                }
            }
        }

        if ($type == 0) {
            return $lockedElements;
        }

        $type = (int)$type;
        if (isset($lockedElements[$type])) {
            return $lockedElements[$type];
        } else {
            return array();
        }
    }

    /**
     * Builds the Locked Elements Cache once
     */
    public function buildLockedElementsCache()
    {
        if (is_null($this->lockedElements)) {
            $this->lockedElements = array();
            $this->cleanupExpiredLocks();

            $rs = $this->db->select('sid,internalKey,elementType,elementId,lasthit,username', $this->getFullTableName('active_user_locks') . " ul
                LEFT JOIN {$this->getFullTableName('manager_users')} mu on ul.internalKey = mu.id");
            while ($row = $this->db->getRow($rs)) {
                $this->lockedElements[$row['elementType']][$row['elementId']] = array(
                    'sid' => $row['sid'],
                    'internalKey' => $row['internalKey'],
                    'username' => $row['username'],
                    'elementType' => $row['elementType'],
                    'elementId' => $row['elementId'],
                    'lasthit' => $row['lasthit'],
                    'lasthit_df' => $this->toDateFormat($row['lasthit']),
                    'state' => $this->determineLockState($row['sid'])
                );
            }
        }
    }

    /**
     * Cleans up the active user locks table
     */
    public function cleanupExpiredLocks()
    {
        // Clean-up active_user_sessions first
        $timeout = (int)$this->config['session_timeout'] < 2 ? 120 : $this->config['session_timeout'] * 60; // session.js pings every 10min, updateMail() in mainMenu pings every minute, so 2min is minimum
        $validSessionTimeLimit = $this->time - $timeout;
        $this->db->delete($this->getFullTableName('active_user_sessions'), "lasthit < {$validSessionTimeLimit}");

        // Clean-up active_user_locks
        $rs = $this->db->select('sid,internalKey', $this->getFullTableName('active_user_sessions'));
        $count = $this->db->getRecordCount($rs);
        if ($count) {
            $rs = $this->db->makeArray($rs);
            $userSids = array();
            foreach ($rs as $row) {
                $userSids[] = $row['sid'];
            }
            $userSids = "'" . implode("','", $userSids) . "'";
            $this->db->delete($this->getFullTableName('active_user_locks'), "sid NOT IN({$userSids})");
        } else {
            $this->db->delete($this->getFullTableName('active_user_locks'));
        }

    }

    /**
     * Cleans up the active users table
     */
    public function cleanupMultipleActiveUsers()
    {
        $timeout = 20 * 60; // Delete multiple user-sessions after 20min
        $validSessionTimeLimit = $this->time - $timeout;

        $activeUserSids = array();
        $rs = $this->db->select('sid', $this->getFullTableName('active_user_sessions'));
        $count = $this->db->getRecordCount($rs);
        if ($count) {
            $rs = $this->db->makeArray($rs);
            foreach ($rs as $row) {
                $activeUserSids[] = $row['sid'];
            }
        }

        $rs = $this->db->select("sid,internalKey,lasthit", "{$this->getFullTableName('active_users')}", "", "lasthit DESC");
        if ($this->db->getRecordCount($rs)) {
            $rs = $this->db->makeArray($rs);
            $internalKeyCount = array();
            $deleteSids = '';
            foreach ($rs as $row) {
                if (!isset($internalKeyCount[$row['internalKey']])) {
                    $internalKeyCount[$row['internalKey']] = 0;
                }
                $internalKeyCount[$row['internalKey']]++;

                if ($internalKeyCount[$row['internalKey']] > 1 && !in_array($row['sid'], $activeUserSids) && $row['lasthit'] < $validSessionTimeLimit) {
                    $deleteSids .= $deleteSids == '' ? '' : ' OR ';
                    $deleteSids .= "sid='{$row['sid']}'";
                };

            }
            if ($deleteSids) {
                $this->db->delete($this->getFullTableName('active_users'), $deleteSids);
            }
        }

    }

    /**
     * Determines state of a locked element acc. to user-permissions
     *
     * @param $sid
     * @return int $state States: 0=No display, 1=viewing this element, 2=locked, 3=show unlock-button
     * @internal param int $internalKey : ID of User who locked actual element
     */
    public function determineLockState($sid)
    {
        $state = 0;
        if ($this->hasPermission('display_locks')) {
            if ($sid == $this->sid) {
                $state = 1;
            } else {
                if ($this->hasPermission('remove_locks')) {
                    $state = 3;
                } else {
                    $state = 2;
                }
            }
        }
        return $state;
    }

    /**
     * Locks an element
     *
     * @param int $type Types: 1=template, 2=tv, 3=chunk, 4=snippet, 5=plugin, 6=module, 7=resource, 8=role
     * @param int $id Element- / Resource-id
     * @return bool
     */
    public function lockElement($type, $id)
    {
        $userId = $this->isBackend() && $_SESSION['mgrInternalKey'] ? $_SESSION['mgrInternalKey'] : 0;
        $type = (int)$type;
        $id = (int)$id;
        if (!$type || !$id || !$userId) {
            return false;
        }

        $sql = sprintf('REPLACE INTO %s (internalKey, elementType, elementId, lasthit, sid)
                VALUES (%d, %d, %d, %d, \'%s\')', $this->getFullTableName('active_user_locks'), $userId, $type, $id, $this->time, $this->sid);
        $this->db->query($sql);
    }

    /**
     * Unlocks an element
     *
     * @param int $type Types: 1=template, 2=tv, 3=chunk, 4=snippet, 5=plugin, 6=module, 7=resource, 8=role
     * @param int $id Element- / Resource-id
     * @param bool $includeAllUsers true = Deletes not only own user-locks
     * @return bool
     */
    public function unlockElement($type, $id, $includeAllUsers = false)
    {
        $userId = $this->isBackend() && $_SESSION['mgrInternalKey'] ? $_SESSION['mgrInternalKey'] : 0;
        $type = (int)$type;
        $id = (int)$id;
        if (!$type || !$id) {
            return false;
        }

        if (!$includeAllUsers) {
            $sql = sprintf('DELETE FROM %s WHERE internalKey = %d AND elementType = %d AND elementId = %d;', $this->getFullTableName('active_user_locks'), $userId, $type, $id);
        } else {
            $sql = sprintf('DELETE FROM %s WHERE elementType = %d AND elementId = %d;', $this->getFullTableName('active_user_locks'), $type, $id);
        }
        $this->db->query($sql);
    }

    /**
     * Updates table "active_user_sessions" with userid, lasthit, IP
     */
    public function updateValidatedUserSession()
    {
        if (!$this->sid) {
            return;
        }

        // web users are stored with negative keys
        $userId = $this->getLoginUserType() == 'manager' ? $this->getLoginUserID() : -$this->getLoginUserID();

        // Get user IP
        if ($cip = getenv("HTTP_CLIENT_IP")) {
            $ip = $cip;
        } elseif ($cip = getenv("HTTP_X_FORWARDED_FOR")) {
            $ip = $cip;
        } elseif ($cip = getenv("REMOTE_ADDR")) {
            $ip = $cip;
        } else {
            $ip = "UNKNOWN";
        }
        $_SESSION['ip'] = $ip;

        $sql = sprintf('REPLACE INTO %s (internalKey, lasthit, ip, sid)
            VALUES (%d, %d, \'%s\', \'%s\')', $this->getFullTableName('active_user_sessions'), $userId, $this->time, $ip, $this->sid);
        $this->db->query($sql);
    }

    /**
     * Add an a alert message to the system event log
     *
     * @param int $evtid Event ID
     * @param int $type Types: 1 = information, 2 = warning, 3 = error
     * @param string $msg Message to be logged
     * @param string $source source of the event (module, snippet name, etc.)
     *                       Default: Parser
     */
    public function logEvent($evtid, $type, $msg, $source = 'Parser')
    {
        $msg = $this->db->escape($msg);
        if (strpos($GLOBALS['database_connection_charset'], 'utf8') === 0 && extension_loaded('mbstring')) {
            $esc_source = mb_substr($source, 0, 50, "UTF-8");
        } else {
            $esc_source = substr($source, 0, 50);
        }
        $esc_source = $this->db->escape($esc_source);

        $LoginUserID = $this->getLoginUserID();
        if ($LoginUserID == '') {
            $LoginUserID = 0;
        }

        $usertype = $this->isFrontend() ? 1 : 0;
        $evtid = (int)$evtid;
        $type = (int)$type;

        // Types: 1 = information, 2 = warning, 3 = error
        if ($type < 1) {
            $type = 1;
        } elseif ($type > 3) {
            $type = 3;
        }

        $this->db->insert(array(
            'eventid' => $evtid,
            'type' => $type,
            'createdon' => $_SERVER['REQUEST_TIME'] + $this->config['server_offset_time'],
            'source' => $esc_source,
            'description' => $msg,
            'user' => $LoginUserID,
            'usertype' => $usertype
        ), $this->getFullTableName("event_log"));

        $this->invokeEvent('OnLogEvent', array(
            'eventid' => $evtid,
            'type' => $type,
            'createdon' => $_SERVER['REQUEST_TIME'] + $this->config['server_offset_time'],
            'source' => $esc_source,
            'description' => $msg,
            'user' => $LoginUserID,
            'usertype' => $usertype
        ));

        if (isset($this->config['send_errormail']) && $this->config['send_errormail'] !== '0') {
            if ($this->config['send_errormail'] <= $type) {
                $this->sendmail(array(
                    'subject' => 'Evolution CMS System Error on ' . $this->config['site_name'],
                    'body' => 'Source: ' . $source . ' - The details of the error could be seen in the MODX system events log.',
                    'type' => 'text'
                ));
            }
        }
    }

    /**
     * @param array $params
     * @param string $msg
     * @param array $files
     * @return mixed
     */
    public function sendmail($params = array(), $msg = '', $files = array())
    {
        if (isset($params) && is_string($params)) {
            if (strpos($params, '=') === false) {
                if (strpos($params, '@') !== false) {
                    $p['to'] = $params;
                } else {
                    $p['subject'] = $params;
                }
            } else {
                $params_array = explode(',', $params);
                foreach ($params_array as $k => $v) {
                    $k = trim($k);
                    $v = trim($v);
                    $p[$k] = $v;
                }
            }
        } else {
            $p = $params;
            unset($params);
        }
        if (isset($p['sendto'])) {
            $p['to'] = $p['sendto'];
        }

        if (isset($p['to']) && preg_match('@^[0-9]+$@', $p['to'])) {
            $userinfo = $this->getUserInfo($p['to']);
            $p['to'] = $userinfo['email'];
        }
        if (isset($p['from']) && preg_match('@^[0-9]+$@', $p['from'])) {
            $userinfo = $this->getUserInfo($p['from']);
            $p['from'] = $userinfo['email'];
            $p['fromname'] = $userinfo['username'];
        }
        if ($msg === '' && !isset($p['body'])) {
            $p['body'] = $_SERVER['REQUEST_URI'] . "\n" . $_SERVER['HTTP_USER_AGENT'] . "\n" . $_SERVER['HTTP_REFERER'];
        } elseif (is_string($msg) && 0 < strlen($msg)) {
            $p['body'] = $msg;
        }

        $this->loadExtension('MODxMailer');
        $sendto = (!isset($p['to'])) ? $this->config['emailsender'] : $p['to'];
        $sendto = explode(',', $sendto);
        foreach ($sendto as $address) {
            list($name, $address) = $this->mail->address_split($address);
            $this->mail->AddAddress($address, $name);
        }
        if (isset($p['cc'])) {
            $p['cc'] = explode(',', $p['cc']);
            foreach ($p['cc'] as $address) {
                list($name, $address) = $this->mail->address_split($address);
                $this->mail->AddCC($address, $name);
            }
        }
        if (isset($p['bcc'])) {
            $p['bcc'] = explode(',', $p['bcc']);
            foreach ($p['bcc'] as $address) {
                list($name, $address) = $this->mail->address_split($address);
                $this->mail->AddBCC($address, $name);
            }
        }
        if (isset($p['from']) && strpos($p['from'], '<') !== false && substr($p['from'], -1) === '>') {
            list($p['fromname'], $p['from']) = $this->mail->address_split($p['from']);
        }
        $this->mail->setFrom(
            isset($p['from']) ? $p['from'] : $this->config['emailsender'],
            isset($p['fromname']) ? $p['fromname'] : $this->config['site_name']
        );
        $this->mail->Subject = (!isset($p['subject'])) ? $this->config['emailsubject'] : $p['subject'];
        $this->mail->Body = $p['body'];
        if (isset($p['type']) && $p['type'] == 'text') {
            $this->mail->IsHTML(false);
        }
        if (!is_array($files)) {
            $files = array();
        }
        foreach ($files as $f) {
            if (file_exists(MODX_BASE_PATH . $f) && is_file(MODX_BASE_PATH . $f) && is_readable(MODX_BASE_PATH . $f)) {
                $this->mail->AddAttachment(MODX_BASE_PATH . $f);
            }
        }
        $rs = $this->mail->send();
        return $rs;
    }

    /**
     * @param string $target
     * @param int $limit
     * @param int $trim
     */
    public function rotate_log($target = 'event_log', $limit = 3000, $trim = 100)
    {
        if ($limit < $trim) {
            $trim = $limit;
        }

        $table_name = $this->getFullTableName($target);
        $count = $this->db->getValue($this->db->select('COUNT(id)', $table_name));
        $over = $count - $limit;
        if (0 < $over) {
            $trim = ($over + $trim);
            $this->db->delete($table_name, '', '', $trim);
        }
        $this->db->optimize($table_name);
    }

    /**
     * Returns true if we are currently in the manager backend
     *
     * @return boolean
     */
    public function isBackend()
    {
        return (defined('IN_MANAGER_MODE') && IN_MANAGER_MODE === true);
    }

    /**
     * Returns true if we are currently in the frontend
     *
     * @return boolean
     */
    public function isFrontend()
    {
        return ! $this->isBackend();
    }

    /**
     * Gets all child documents of the specified document, including those which are unpublished or deleted.
     *
     * @param int $id The Document identifier to start with
     * @param string $sort Sort field
     *                     Default: menuindex
     * @param string $dir Sort direction, ASC and DESC is possible
     *                    Default: ASC
     * @param string $fields Default: id, pagetitle, description, parent, alias, menutitle
     * @return array
     */
    public function getAllChildren($id = 0, $sort = 'menuindex', $dir = 'ASC', $fields = 'id, pagetitle, description, parent, alias, menutitle')
    {

        $cacheKey = md5(print_r(func_get_args(), true));
        if (isset($this->tmpCache[__FUNCTION__][$cacheKey])) {
            return $this->tmpCache[__FUNCTION__][$cacheKey];
        }

        $tblsc = $this->getFullTableName("site_content");
        $tbldg = $this->getFullTableName("document_groups");
        // modify field names to use sc. table reference
        $fields = 'sc.' . implode(',sc.', array_filter(array_map('trim', explode(',', $fields))));
        $sort = 'sc.' . implode(',sc.', array_filter(array_map('trim', explode(',', $sort))));
        // get document groups for current user
        if ($docgrp = $this->getUserDocGroups()) {
            $docgrp = implode(",", $docgrp);
        }
        // build query
        $access = ($this->isFrontend() ? "sc.privateweb=0" : "1='" . $_SESSION['mgrRole'] . "' OR sc.privatemgr=0") . (!$docgrp ? "" : " OR dg.document_group IN ($docgrp)");
        $result = $this->db->select("DISTINCT {$fields}", "{$tblsc} sc
                LEFT JOIN {$tbldg} dg on dg.document = sc.id", "sc.parent = '{$id}' AND ({$access}) GROUP BY sc.id", "{$sort} {$dir}");
        $resourceArray = $this->db->makeArray($result);
        $this->tmpCache[__FUNCTION__][$cacheKey] = $resourceArray;
        return $resourceArray;
    }

    /**
     * Gets all active child documents of the specified document, i.e. those which published and not deleted.
     *
     * @param int $id The Document identifier to start with
     * @param string $sort Sort field
     *                     Default: menuindex
     * @param string $dir Sort direction, ASC and DESC is possible
     *                    Default: ASC
     * @param string $fields Default: id, pagetitle, description, parent, alias, menutitle
     * @return array
     */
    public function getActiveChildren($id = 0, $sort = 'menuindex', $dir = 'ASC', $fields = 'id, pagetitle, description, parent, alias, menutitle')
    {
        $cacheKey = md5(print_r(func_get_args(), true));
        if (isset($this->tmpCache[__FUNCTION__][$cacheKey])) {
            return $this->tmpCache[__FUNCTION__][$cacheKey];
        }

        $tblsc = $this->getFullTableName("site_content");
        $tbldg = $this->getFullTableName("document_groups");

        // modify field names to use sc. table reference
        $fields = 'sc.' . implode(',sc.', array_filter(array_map('trim', explode(',', $fields))));
        $sort = 'sc.' . implode(',sc.', array_filter(array_map('trim', explode(',', $sort))));
        // get document groups for current user
        if ($docgrp = $this->getUserDocGroups()) {
            $docgrp = implode(",", $docgrp);
        }
        // build query
        $access = ($this->isFrontend() ? "sc.privateweb=0" : "1='" . $_SESSION['mgrRole'] . "' OR sc.privatemgr=0") . (!$docgrp ? "" : " OR dg.document_group IN ($docgrp)");
        $result = $this->db->select("DISTINCT {$fields}", "{$tblsc} sc
                LEFT JOIN {$tbldg} dg on dg.document = sc.id", "sc.parent = '{$id}' AND sc.published=1 AND sc.deleted=0 AND ({$access}) GROUP BY sc.id", "{$sort} {$dir}");
        $resourceArray = $this->db->makeArray($result);

        $this->tmpCache[__FUNCTION__][$cacheKey] = $resourceArray;

        return $resourceArray;
    }

    /**
     * getDocumentChildren
     * @version 1.1.1 (2014-02-19)
     *
     * @desc Returns the children of the selected document/folder as an associative array.
     *
     * @param $parentid {integer} - The parent document identifier. Default: 0 (site root).
     * @param $published {0; 1; 'all'} - Document publication status. Once the parameter equals 'all', the result will be returned regardless of whether the ducuments are published or they are not. Default: 1.
     * @param $deleted {0; 1; 'all'} - Document removal status. Once the parameter equals 'all', the result will be returned regardless of whether the ducuments are deleted or they are not. Default: 0.
     * @param $fields {comma separated string; '*'} - Comma separated list of document fields to get. Default: '*' (all fields).
     * @param $where {string} - Where condition in SQL style. Should include a leading 'AND '. Default: ''.
     * @param $sort {comma separated string} - Should be a comma-separated list of field names on which to sort. Default: 'menuindex'.
     * @param $dir {'ASC'; 'DESC'} - Sort direction, ASC and DESC is possible. Default: 'ASC'.
     * @param $limit {string} - Should be a valid SQL LIMIT clause without the 'LIMIT ' i.e. just include the numbers as a string. Default: Empty string (no limit).
     *
     * @return {array; false} - Result array, or false.
     */
    public function getDocumentChildren($parentid = 0, $published = 1, $deleted = 0, $fields = '*', $where = '', $sort = 'menuindex', $dir = 'ASC', $limit = '')
    {

        $cacheKey = md5(print_r(func_get_args(), true));
        if (isset($this->tmpCache[__FUNCTION__][$cacheKey])) {
            return $this->tmpCache[__FUNCTION__][$cacheKey];
        }

        $published = ($published !== 'all') ? 'AND sc.published = ' . $published : '';
        $deleted = ($deleted !== 'all') ? 'AND sc.deleted = ' . $deleted : '';

        if ($where != '') {
            $where = 'AND ' . $where;
        }

        // modify field names to use sc. table reference
        $fields = 'sc.' . implode(',sc.', array_filter(array_map('trim', explode(',', $fields))));
        $sort = ($sort == '') ? '' : 'sc.' . implode(',sc.', array_filter(array_map('trim', explode(',', $sort))));

        // get document groups for current user
        if ($docgrp = $this->getUserDocGroups()) {
            $docgrp = implode(',', $docgrp);
        }

        // build query
        $access = ($this->isFrontend() ? 'sc.privateweb=0' : '1="' . $_SESSION['mgrRole'] . '" OR sc.privatemgr=0') . (!$docgrp ? '' : ' OR dg.document_group IN (' . $docgrp . ')');

        $tblsc = $this->getFullTableName('site_content');
        $tbldg = $this->getFullTableName('document_groups');

        $result = $this->db->select("DISTINCT {$fields}", "{$tblsc} sc
                LEFT JOIN {$tbldg} dg on dg.document = sc.id", "sc.parent = '{$parentid}' {$published} {$deleted} {$where} AND ({$access}) GROUP BY sc.id", ($sort ? "{$sort} {$dir}" : ""), $limit);

        $resourceArray = $this->db->makeArray($result);

        $this->tmpCache[__FUNCTION__][$cacheKey] = $resourceArray;

        return $resourceArray;
    }

    /**
     * getDocuments
     * @version 1.1.1 (2013-02-19)
     *
     * @desc Returns required documents (their fields).
     *
     * @param $ids {array; comma separated string} - Documents Ids to get. @required
     * @param $published {0; 1; 'all'} - Documents publication status. Once the parameter equals 'all', the result will be returned regardless of whether the documents are published or they are not. Default: 1.
     * @param $deleted {0; 1; 'all'} - Documents removal status. Once the parameter equals 'all', the result will be returned regardless of whether the documents are deleted or they are not. Default: 0.
     * @param $fields {comma separated string; '*'} - Documents fields to get. Default: '*'.
     * @param $where {string} - SQL WHERE clause. Default: ''.
     * @param $sort {comma separated string} - A comma-separated list of field names to sort by. Default: 'menuindex'.
     * @param $dir {'ASC'; 'DESC'} - Sorting direction. Default: 'ASC'.
     * @param $limit {string} - SQL LIMIT (without 'LIMIT '). An empty string means no limit. Default: ''.
     *
     * @return {array; false} - Result array with documents, or false.
     */
    public function getDocuments($ids = array(), $published = 1, $deleted = 0, $fields = '*', $where = '', $sort = 'menuindex', $dir = 'ASC', $limit = '')
    {

        $cacheKey = md5(print_r(func_get_args(), true));
        if (isset($this->tmpCache[__FUNCTION__][$cacheKey])) {
            return $this->tmpCache[__FUNCTION__][$cacheKey];
        }

        if (is_string($ids)) {
            if (strpos($ids, ',') !== false) {
                $ids = array_filter(array_map('intval', explode(',', $ids)));
            } else {
                $ids = array($ids);
            }
        }
        if (count($ids) == 0) {
            $this->tmpCache[__FUNCTION__][$cacheKey] = false;
            return false;
        } else {
            // modify field names to use sc. table reference
            $fields = 'sc.' . implode(',sc.', array_filter(array_map('trim', explode(',', $fields))));
            $sort = ($sort == '') ? '' : 'sc.' . implode(',sc.', array_filter(array_map('trim', explode(',', $sort))));
            if ($where != '') {
                $where = 'AND ' . $where;
            }

            $published = ($published !== 'all') ? "AND sc.published = '{$published}'" : '';
            $deleted = ($deleted !== 'all') ? "AND sc.deleted = '{$deleted}'" : '';

            // get document groups for current user
            if ($docgrp = $this->getUserDocGroups()) {
                $docgrp = implode(',', $docgrp);
            }

            $access = ($this->isFrontend() ? 'sc.privateweb=0' : '1="' . $_SESSION['mgrRole'] . '" OR sc.privatemgr=0') . (!$docgrp ? '' : ' OR dg.document_group IN (' . $docgrp . ')');

            $tblsc = $this->getFullTableName('site_content');
            $tbldg = $this->getFullTableName('document_groups');

            $result = $this->db->select("DISTINCT {$fields}", "{$tblsc} sc
                    LEFT JOIN {$tbldg} dg on dg.document = sc.id", "(sc.id IN (" . implode(',', $ids) . ") {$published} {$deleted} {$where}) AND ({$access}) GROUP BY sc.id", ($sort ? "{$sort} {$dir}" : ""), $limit);

            $resourceArray = $this->db->makeArray($result);

            $this->tmpCache[__FUNCTION__][$cacheKey] = $resourceArray;

            return $resourceArray;
        }
    }

    /**
     * getDocument
     * @version 1.0.1 (2014-02-19)
     *
     * @desc Returns required fields of a document.
     *
     * @param int $id {integer}
     * - Id of a document which data has to be gained. @required
     * @param string $fields {comma separated string; '*'}
     * - Comma separated list of document fields to get. Default: '*'.
     * @param int $published {0; 1; 'all'}
     * - Document publication status. Once the parameter equals 'all', the result will be returned regardless of whether the documents are published or they are not. Default: false.
     * @param int $deleted {0; 1; 'all'}
     * - Document removal status. Once the parameter equals 'all', the result will be returned regardless of whether the documents are deleted or they are not. Default: 0.
     * @return bool {array; false} - Result array with fields or false.
     * - Result array with fields or false.
     */
    public function getDocument($id = 0, $fields = '*', $published = 1, $deleted = 0)
    {
        if ($id == 0) {
            return false;
        } else {
            $docs = $this->getDocuments(array($id), $published, $deleted, $fields, '', '', '', 1);

            if ($docs != false) {
                return $docs[0];
            } else {
                return false;
            }
        }
    }

    /**
     * @param string $field
     * @param string $docid
     * @return bool|mixed
     */
    public function getField($field = 'content', $docid = '')
    {
        if (empty($docid) && isset($this->documentIdentifier)) {
            $docid = $this->documentIdentifier;
        } elseif (!preg_match('@^[0-9]+$@', $docid)) {
            $docid = $this->getIdFromAlias($docid);
        }

        if (empty($docid)) {
            return false;
        }

        $cacheKey = md5(print_r(func_get_args(), true));
        if (isset($this->tmpCache[__FUNCTION__][$cacheKey])) {
            return $this->tmpCache[__FUNCTION__][$cacheKey];
        }

        $doc = $this->getDocumentObject('id', $docid);
        if (is_array($doc[$field])) {
            $tvs = $this->getTemplateVarOutput($field, $docid, 1);
            $content = $tvs[$field];
        } else {
            $content = $doc[$field];
        }

        $this->tmpCache[__FUNCTION__][$cacheKey] = $content;

        return $content;
    }

    /**
     * Returns the page information as database row, the type of result is
     * defined with the parameter $rowMode
     *
     * @param int $pageid The parent document identifier
     *                    Default: -1 (no result)
     * @param int $active Should we fetch only published and undeleted documents/resources?
     *                     1 = yes, 0 = no
     *                     Default: 1
     * @param string $fields List of fields
     *                       Default: id, pagetitle, description, alias
     * @return boolean|array
     */
    public function getPageInfo($pageid = -1, $active = 1, $fields = 'id, pagetitle, description, alias')
    {

        $cacheKey = md5(print_r(func_get_args(), true));
        if (isset($this->tmpCache[__FUNCTION__][$cacheKey])) {
            return $this->tmpCache[__FUNCTION__][$cacheKey];
        }

        if ($pageid == 0) {
            return false;
        } else {
            $tblsc = $this->getFullTableName("site_content");
            $tbldg = $this->getFullTableName("document_groups");
            $activeSql = $active == 1 ? "AND sc.published=1 AND sc.deleted=0" : "";
            // modify field names to use sc. table reference
            $fields = 'sc.' . implode(',sc.', array_filter(array_map('trim', explode(',', $fields))));
            // get document groups for current user
            if ($docgrp = $this->getUserDocGroups()) {
                $docgrp = implode(",", $docgrp);
            }
            $access = ($this->isFrontend() ? "sc.privateweb=0" : "1='" . $_SESSION['mgrRole'] . "' OR sc.privatemgr=0") . (!$docgrp ? "" : " OR dg.document_group IN ($docgrp)");
            $result = $this->db->select($fields, "{$tblsc} sc LEFT JOIN {$tbldg} dg on dg.document = sc.id", "(sc.id='{$pageid}' {$activeSql}) AND ({$access})", "", 1);
            $pageInfo = $this->db->getRow($result);

            $this->tmpCache[__FUNCTION__][$cacheKey] = $pageInfo;

            return $pageInfo;
        }
    }

    /**
     * Returns the parent document/resource of the given docid
     *
     * @param int $pid The parent docid. If -1, then fetch the current document/resource's parent
     *                 Default: -1
     * @param int $active Should we fetch only published and undeleted documents/resources?
     *                     1 = yes, 0 = no
     *                     Default: 1
     * @param string $fields List of fields
     *                       Default: id, pagetitle, description, alias
     * @return boolean|array
     */
    public function getParent($pid = -1, $active = 1, $fields = 'id, pagetitle, description, alias, parent')
    {
        if ($pid == -1) {
            $pid = $this->documentObject['parent'];
            return ($pid == 0) ? false : $this->getPageInfo($pid, $active, $fields);
        } else if ($pid == 0) {
            return false;
        } else {
            // first get the child document
            $child = $this->getPageInfo($pid, $active, "parent");
            // now return the child's parent
            $pid = ($child['parent']) ? $child['parent'] : 0;
            return ($pid == 0) ? false : $this->getPageInfo($pid, $active, $fields);
        }
    }

    /**
     * Returns the id of the current snippet.
     *
     * @return int
     */
    public function getSnippetId()
    {
        if ($this->currentSnippet) {
            $tbl = $this->getFullTableName("site_snippets");
            $rs = $this->db->select('id', $tbl, "name='" . $this->db->escape($this->currentSnippet) . "'", '', 1);
            if ($snippetId = $this->db->getValue($rs)) {
                return $snippetId;
            }
        }
        return 0;
    }

    /**
     * Returns the name of the current snippet.
     *
     * @return string
     */
    public function getSnippetName()
    {
        return $this->currentSnippet;
    }

    /**
     * Clear the cache of MODX.
     *
     * @param string $type
     * @param bool $report
     * @return bool
     */
    public function clearCache($type = '', $report = false)
    {
        $cache_dir = MODX_BASE_PATH . $this->getCacheFolder();
        if (is_array($type)) {
            foreach ($type as $_) {
                $this->clearCache($_, $report);
            }
        } elseif ($type == 'full') {
            include_once(MODX_MANAGER_PATH . 'processors/cache_sync.class.processor.php');
            $sync = new synccache();
            $sync->setCachepath($cache_dir);
            $sync->setReport($report);
            $sync->emptyCache();
        } elseif (preg_match('@^[1-9][0-9]*$@', $type)) {
            $key = ($this->config['cache_type'] == 2) ? $this->makePageCacheKey($type) : $type;
            $file_name = "docid_" . $key . "_*.pageCache.php";
            $cache_path = $cache_dir . $file_name;
            $files = glob($cache_path);
            $files[] = $cache_dir . "docid_" . $key . ".pageCache.php";
            foreach ($files as $file) {
                if (!is_file($file)) {
                    continue;
                }
                unlink($file);
            }
        } else {
            $files = glob($cache_dir . '*');
            foreach ($files as $file) {
                $name = basename($file);
                if (strpos($name, '.pageCache.php') === false) {
                    continue;
                }
                if (!is_file($file)) {
                    continue;
                }
                unlink($file);
            }
        }
    }

    /**
     * makeUrl
     *
     * @desc Create an URL for the given document identifier. The url prefix and postfix are used, when “friendly_url” is active.
     *
     * @param $id {integer} - The document identifier. @required
     * @param string $alias {string}
     * - The alias name for the document. Default: ''.
     * @param string $args {string}
     * - The paramaters to add to the URL. Default: ''.
     * @param string $scheme {string}
     * - With full as valus, the site url configuration is used. Default: ''.
     * @return mixed|string {string} - Result URL.
     * - Result URL.
     */
    public function makeUrl($id, $alias = '', $args = '', $scheme = '')
    {
        $url = '';
        $virtualDir = isset($this->config['virtual_dir']) ? $this->config['virtual_dir'] : '';
        $f_url_prefix = $this->config['friendly_url_prefix'];
        $f_url_suffix = $this->config['friendly_url_suffix'];

        if (!is_numeric($id)) {
            $this->messageQuit("`{$id}` is not numeric and may not be passed to makeUrl()");
        }

        if ($args !== '') {
            // add ? or & to $args if missing
            $args = ltrim($args, '?&');
            $_ = strpos($f_url_prefix, '?');

            if ($_ === false && $this->config['friendly_urls'] == 1) {
                $args = "?{$args}";
            } else {
                $args = "&{$args}";
            }
        }

        if ($id != $this->config['site_start']) {
            if ($this->config['friendly_urls'] == 1 && $alias == '') {
                $alias = $id;
                $alPath = '';

                if ($this->config['friendly_alias_urls'] == 1) {

                    if ($this->config['aliaslistingfolder'] == 1  || (isset($this->config['full_aliaslisting']) && $this->config['full_aliaslisting'] == 1)) {
                        $al = $this->getAliasListing($id);
                    } else {
                        $al = $this->aliasListing[$id];
                    }

                    if ($al['isfolder'] === 1 && $this->config['make_folders'] === '1') {
                        $f_url_suffix = '/';
                    }

                    $alPath = !empty ($al['path']) ? $al['path'] . '/' : '';

                    if ($al && $al['alias']) {
                        $alias = $al['alias'];
                    }

                }

                $alias = $alPath . $f_url_prefix . $alias . $f_url_suffix;
                $url = "{$alias}{$args}";
            } else {
                $url = "index.php?id={$id}{$args}";
            }
        } else {
            $url = $args;
        }

        $host = $this->config['base_url'];

        // check if scheme argument has been set
        if ($scheme != '') {
            // for backward compatibility - check if the desired scheme is different than the current scheme
            if (is_numeric($scheme) && $scheme != $_SERVER['HTTPS']) {
                $scheme = ($_SERVER['HTTPS'] ? 'http' : 'https');
            }

            //TODO: check to make sure that $site_url incudes the url :port (e.g. :8080)
            $host = $scheme == 'full' ? $this->config['site_url'] : $scheme . '://' . $_SERVER['HTTP_HOST'] . $host;
        }

        //fix strictUrl by Bumkaka
        if ($this->config['seostrict'] == '1') {
            $url = $this->toAlias($url);
        }

        if ($this->config['xhtml_urls']) {
            $url = preg_replace("/&(?!amp;)/", "&amp;", $host . $virtualDir . $url);
        } else {
            $url = $host . $virtualDir . $url;
        }

        $evtOut = $this->invokeEvent('OnMakeDocUrl', array(
            'id' => $id,
            'url' => $url
        ));

        if (is_array($evtOut) && count($evtOut) > 0) {
            $url = array_pop($evtOut);
        }

        return $url;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getAliasListing($id)
    {
        if (isset($this->aliasListing[$id])) {
            $out = $this->aliasListing[$id];
        } else {
            $q = $this->db->query("SELECT id,alias,isfolder,parent,alias_visible FROM " . $this->getFullTableName("site_content") . " WHERE id=" . (int)$id);
            if ($this->db->getRecordCount($q) == '1') {
                $q = $this->db->getRow($q);
                $this->aliasListing[$id] = array(
                    'id' => (int)$q['id'],
                    'alias' => $q['alias'] == '' ? $q['id'] : $q['alias'],
                    'parent' => (int)$q['parent'],
                    'isfolder' => (int)$q['isfolder'],
                    'alias_visible' => (int)$q['alias_visible'],
                );
                if ($this->aliasListing[$id]['parent'] > 0) {
                    //fix alias_path_usage
                    if ($this->config['use_alias_path'] == '1') {
                        //&& $tmp['path'] != '' - fix error slash with epty path
                        $tmp = $this->getAliasListing($this->aliasListing[$id]['parent']);
                        $this->aliasListing[$id]['path'] = $tmp['path'] . ($tmp['alias_visible'] ? (($tmp['parent'] > 0 && $tmp['path'] != '') ? '/' : '') . $tmp['alias'] : '');
                    } else {
                        $this->aliasListing[$id]['path'] = '';
                    }
                }

                $out = $this->aliasListing[$id];
            }
        }
        return $out;
    }

    /**
     * Returns an entry from the config
     *
     * Note: most code accesses the config array directly and we will continue to support this.
     *
     * @param string $name
     * @return bool|string
     */
    public function getConfig($name = '')
    {
        if (!empty ($this->config[$name])) {
            return $this->config[$name];
        } else {
            return false;
        }
    }

    /**
     * Returns the MODX version information as version, branch, release date and full application name.
     *
     * @param null $data
     * @return array
     */

    public function getVersionData($data = null)
    {
        $out = array();
        if (empty($this->version) || !is_array($this->version)) {
            //include for compatibility modx version < 1.0.10
            include MODX_MANAGER_PATH . "includes/version.inc.php";
            $this->version = array();
            $this->version['version'] = isset($modx_version) ? $modx_version : '';
            $this->version['branch'] = isset($modx_branch) ? $modx_branch : '';
            $this->version['release_date'] = isset($modx_release_date) ? $modx_release_date : '';
            $this->version['full_appname'] = isset($modx_full_appname) ? $modx_full_appname : '';
            $this->version['new_version'] = isset($this->config['newversiontext']) ? $this->config['newversiontext'] : '';
        }
        return (!is_null($data) && is_array($this->version) && isset($this->version[$data])) ? $this->version[$data] : $this->version;
    }

    /**
     * Executes a snippet.
     *
     * @param string $snippetName
     * @param array $params Default: Empty array
     * @return string
     */
    public function runSnippet($snippetName, $params = array())
    {
        if (isset ($this->snippetCache[$snippetName])) {
            $snippet = $this->snippetCache[$snippetName];
            $properties = !empty($this->snippetCache[$snippetName . "Props"]) ? $this->snippetCache[$snippetName . "Props"] : '';
        } else { // not in cache so let's check the db
            $sql = "SELECT ss.`name`, ss.`snippet`, ss.`properties`, sm.properties as `sharedproperties` FROM " . $this->getFullTableName("site_snippets") . " as ss LEFT JOIN " . $this->getFullTableName('site_modules') . " as sm on sm.guid=ss.moduleguid WHERE ss.`name`='" . $this->db->escape($snippetName) . "'  AND ss.disabled=0;";
            $result = $this->db->query($sql);
            if ($this->db->getRecordCount($result) == 1) {
                $row = $this->db->getRow($result);
                $snippet = $this->snippetCache[$snippetName] = $row['snippet'];
                $mergedProperties = array_merge($this->parseProperties($row['properties']), $this->parseProperties($row['sharedproperties']));
                $properties = $this->snippetCache[$snippetName . "Props"] = json_encode($mergedProperties);
            } else {
                $snippet = $this->snippetCache[$snippetName] = "return false;";
                $properties = $this->snippetCache[$snippetName . "Props"] = '';
            }
        }
        // load default params/properties
        $parameters = $this->parseProperties($properties, $snippetName, 'snippet');
        $parameters = array_merge($parameters, $params);

        // run snippet
        return $this->evalSnippet($snippet, $parameters);
    }

    /**
     * Returns the chunk content for the given chunk name
     *
     * @param string $chunkName
     * @return boolean|string
     */
    public function getChunk($chunkName)
    {
        $out = null;
        if (empty($chunkName)) {
            // nop
        } elseif ($this->isChunkProcessor('DLTemplate')) {
            $out = DLTemplate::getInstance($this)->getChunk($chunkName);
        } elseif (isset ($this->chunkCache[$chunkName])) {
            $out = $this->chunkCache[$chunkName];
        } elseif (stripos($chunkName, '@FILE') === 0) {
            $out = $this->chunkCache[$chunkName] = $this->atBindFileContent($chunkName);
        } else {
            $where = sprintf("`name`='%s' AND disabled=0", $this->db->escape($chunkName));
            $rs = $this->db->select('snippet', '[+prefix+]site_htmlsnippets', $where);
            if ($this->db->getRecordCount($rs) == 1) {
                $row = $this->db->getRow($rs);
                $out = $this->chunkCache[$chunkName] = $row['snippet'];
            } else {
                $out = $this->chunkCache[$chunkName] = null;
            }
        }
        return $out;
    }

    /**
     * parseText
     * @version 1.0 (2013-10-17)
     *
     * @desc Replaces placeholders in text with required values.
     *
     * @param string $tpl
     * @param array $ph
     * @param string $left
     * @param string $right
     * @param bool $execModifier
     * @return string {string} - Parsed text.
     * - Parsed text.
     * @internal param $chunk {string} - String to parse. - String to parse. @required
     * @internal param $chunkArr {array} - Array of values. Key — placeholder name, value — value. - Array of values. Key — placeholder name, value — value. @required
     * @internal param $prefix {string} - Placeholders prefix. Default: '[+'. - Placeholders prefix. Default: '[+'.
     * @internal param $suffix {string} - Placeholders suffix. Default: '+]'. - Placeholders suffix. Default: '+]'.
     *
     */
    public function parseText($tpl = '', $ph = array(), $left = '[+', $right = '+]', $execModifier = true)
    {
        if (empty($ph) || empty($tpl)) {
            return $tpl;
        }

        if ($this->config['enable_at_syntax']) {
            if (stripos($tpl, '<@LITERAL>') !== false) {
                $tpl = $this->escapeLiteralTagsContent($tpl);
            }
        }

        $matches = $this->getTagsFromContent($tpl, $left, $right);
        if (empty($matches)) {
            return $tpl;
        }

        foreach ($matches[1] as $i => $key) {

            if (strpos($key, ':') !== false && $execModifier) {
                list($key, $modifiers) = $this->splitKeyAndFilter($key);
            } else {
                $modifiers = false;
            }

            //          if(!isset($ph[$key])) continue;
            if (!array_key_exists($key, $ph)) {
                continue;
            } //NULL values must be saved in placeholders, if we got them from database string

            $value = $ph[$key];

            $s = &$matches[0][$i];
            if ($modifiers !== false) {
                if (strpos($modifiers, $left) !== false) {
                    $modifiers = $this->parseText($modifiers, $ph, $left, $right);
                }
                $value = $this->applyFilter($value, $modifiers, $key);
            }
            if (strpos($tpl, $s) !== false) {
                $tpl = str_replace($s, $value, $tpl);
            } elseif($this->debug) {
                $this->addLog('parseText parse error', $_SERVER['REQUEST_URI'] . $s, 2);
            }
        }

        return $tpl;
    }

    /**
     * @param string|object $processor
     * @return bool
     */
    public function isChunkProcessor($processor)
    {
        $value = (string)$this->getConfig('chunk_processor');

        if(is_object($processor)) {
            $processor = get_class($processor);
        }

        return is_scalar($processor) && mb_strtolower($value) === mb_strtolower($processor) && class_exists($processor, false);
    }

    /**
     * parseChunk
     * @version 1.1 (2013-10-17)
     *
     * @desc Replaces placeholders in a chunk with required values.
     *
     * @param $chunkName {string} - Name of chunk to parse. @required
     * @param $chunkArr {array} - Array of values. Key — placeholder name, value — value. @required
     * @param string $prefix {string}
     * - Placeholders prefix. Default: '{'.
     * @param string $suffix {string}
     * - Placeholders suffix. Default: '}'.
     * @return bool|mixed|string {string; false} - Parsed chunk or false if $chunkArr is not array.
     * - Parsed chunk or false if $chunkArr is not array.
     */
    public function parseChunk($chunkName, $chunkArr, $prefix = '{', $suffix = '}')
    {
        //TODO: Wouldn't it be more practical to return the contents of a chunk instead of false?
        if (!is_array($chunkArr)) {
            return false;
        }

        return $prefix === '[+' && $suffix === '+]' && $this->isChunkProcessor('DLTemplate') ?
            DLTemplate::getInstance($this)->parseChunk($chunkName, $chunkArr) :
            $this->parseText($this->getChunk($chunkName), $chunkArr, $prefix, $suffix);
    }

    /**
     * getTpl
     * get template for snippets
     * @param $tpl {string}
     * @return bool|string {string}
     */
    public function getTpl($tpl)
    {
        $command = '';
        $template = $tpl;
        if (preg_match("/^@([^:\s]+)[:\s]+(.+)$/s", trim($tpl), $match)) {
            $command = strtoupper($match[1]);
            $template = $match[2];
        }
        switch ($command) {
            case 'CODE':
                break;
            case 'FILE':
                $template = file_get_contents(MODX_BASE_PATH . $template);
                break;
            case 'CHUNK':
                $template = $this->getChunk($template);
                break;
            case 'DOCUMENT':
                $doc = $this->getDocument($template, 'content', 'all');
                $template = $doc['content'];
                break;
            case 'SELECT':
                $this->db->getValue($this->db->query("SELECT {$template}"));
                break;
            default:
                if (!($template = $this->getChunk($tpl))) {
                    $template = $tpl;
                }
        }
        return $template;
    }

    /**
     * Returns the timestamp in the date format defined in $this->config['datetime_format']
     *
     * @param int $timestamp Default: 0
     * @param string $mode Default: Empty string (adds the time as below). Can also be 'dateOnly' for no time or 'formatOnly' to get the datetime_format string.
     * @return string
     */
    public function toDateFormat($timestamp = 0, $mode = '')
    {
        $timestamp = trim($timestamp);
        if ($mode !== 'formatOnly' && empty($timestamp)) {
            return '-';
        }
        $timestamp = (int)$timestamp;

        switch ($this->config['datetime_format']) {
            case 'YYYY/mm/dd':
                $dateFormat = '%Y/%m/%d';
                break;
            case 'dd-mm-YYYY':
                $dateFormat = '%d-%m-%Y';
                break;
            case 'mm/dd/YYYY':
                $dateFormat = '%m/%d/%Y';
                break;
            /*
            case 'dd-mmm-YYYY':
                $dateFormat = '%e-%b-%Y';
                break;
            */
        }

        if (empty($mode)) {
            $strTime = strftime($dateFormat . " %H:%M:%S", $timestamp);
        } elseif ($mode == 'dateOnly') {
            $strTime = strftime($dateFormat, $timestamp);
        } elseif ($mode == 'formatOnly') {
            $strTime = $dateFormat;
        }
        return $strTime;
    }

    /**
     * Make a timestamp from a string corresponding to the format in $this->config['datetime_format']
     *
     * @param string $str
     * @return string
     */
    public function toTimeStamp($str)
    {
        $str = trim($str);
        if (empty($str)) {
            return '';
        }

        switch ($this->config['datetime_format']) {
            case 'YYYY/mm/dd':
                if (!preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}[0-9 :]*$/', $str)) {
                    return '';
                }
                list ($Y, $m, $d, $H, $M, $S) = sscanf($str, '%4d/%2d/%2d %2d:%2d:%2d');
                break;
            case 'dd-mm-YYYY':
                if (!preg_match('/^[0-9]{2}-[0-9]{2}-[0-9]{4}[0-9 :]*$/', $str)) {
                    return '';
                }
                list ($d, $m, $Y, $H, $M, $S) = sscanf($str, '%2d-%2d-%4d %2d:%2d:%2d');
                break;
            case 'mm/dd/YYYY':
                if (!preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}[0-9 :]*$/', $str)) {
                    return '';
                }
                list ($m, $d, $Y, $H, $M, $S) = sscanf($str, '%2d/%2d/%4d %2d:%2d:%2d');
                break;
            /*
            case 'dd-mmm-YYYY':
                if (!preg_match('/^[0-9]{2}-[0-9a-z]+-[0-9]{4}[0-9 :]*$/i', $str)) {return '';}
                list ($m, $d, $Y, $H, $M, $S) = sscanf($str, '%2d-%3s-%4d %2d:%2d:%2d');
                break;
            */
        }
        if (!$H && !$M && !$S) {
            $H = 0;
            $M = 0;
            $S = 0;
        }
        $timeStamp = mktime($H, $M, $S, $m, $d, $Y);
        $timeStamp = (int)$timeStamp;
        return $timeStamp;
    }

    /**
     * Get the TVs of a document's children. Returns an array where each element represents one child doc.
     *
     * Ignores deleted children. Gets all children - there is no where clause available.
     *
     * @param int $parentid The parent docid
     *                 Default: 0 (site root)
     * @param array $tvidnames . Which TVs to fetch - Can relate to the TV ids in the db (array elements should be numeric only)
     *                                               or the TV names (array elements should be names only)
     *                      Default: Empty array
     * @param int $published Whether published or unpublished documents are in the result
     *                      Default: 1
     * @param string $docsort How to sort the result array (field)
     *                      Default: menuindex
     * @param ASC|string $docsortdir How to sort the result array (direction)
     *                      Default: ASC
     * @param string $tvfields Fields to fetch from site_tmplvars, default '*'
     *                      Default: *
     * @param string $tvsort How to sort each element of the result array i.e. how to sort the TVs (field)
     *                      Default: rank
     * @param string $tvsortdir How to sort each element of the result array i.e. how to sort the TVs (direction)
     *                      Default: ASC
     * @return array|bool
     */
    public function getDocumentChildrenTVars($parentid = 0, $tvidnames = array(), $published = 1, $docsort = "menuindex", $docsortdir = "ASC", $tvfields = "*", $tvsort = "rank", $tvsortdir = "ASC")
    {
        $docs = $this->getDocumentChildren($parentid, $published, 0, '*', '', $docsort, $docsortdir);
        if (!$docs) {
            return false;
        } else {
            $result = array();
            // get user defined template variables
            if ($tvfields) {
                $_ = array_filter(array_map('trim', explode(',', $tvfields)));
                foreach ($_ as $i => $v) {
                    if ($v === 'value') {
                        unset($_[$i]);
                    } else {
                        $_[$i] = 'tv.' . $v;
                    }
                }
                $fields = implode(',', $_);
            } else {
                $fields = "tv.*";
            }

            if ($tvsort != '') {
                $tvsort = 'tv.' . implode(',tv.', array_filter(array_map('trim', explode(',', $tvsort))));
            }
            if ($tvidnames == "*") {
                $query = "tv.id<>0";
            } else {
                $query = (is_numeric($tvidnames[0]) ? "tv.id" : "tv.name") . " IN ('" . implode("','", $tvidnames) . "')";
            }

            $this->getUserDocGroups();

            foreach ($docs as $doc) {

                $docid = $doc['id'];

                $rs = $this->db->select("{$fields}, IF(tvc.value!='',tvc.value,tv.default_text) as value ", "[+prefix+]site_tmplvars tv
                        INNER JOIN [+prefix+]site_tmplvar_templates tvtpl ON tvtpl.tmplvarid = tv.id
                        LEFT JOIN [+prefix+]site_tmplvar_contentvalues tvc ON tvc.tmplvarid=tv.id AND tvc.contentid='{$docid}'", "{$query} AND tvtpl.templateid = '{$doc['template']}'", ($tvsort ? "{$tvsort} {$tvsortdir}" : ""));
                $tvs = $this->db->makeArray($rs);

                // get default/built-in template variables
                ksort($doc);
                foreach ($doc as $key => $value) {
                    if ($tvidnames == '*' || in_array($key, $tvidnames)) {
                        $tvs[] = array('name' => $key, 'value' => $value);
                    }
                }
                if (is_array($tvs) && count($tvs)) {
                    $result[] = $tvs;
                }
            }
            return $result;
        }
    }

    /**
     * getDocumentChildrenTVarOutput
     * @version 1.1 (2014-02-19)
     *
     * @desc Returns an array where each element represents one child doc and contains the result from getTemplateVarOutput().
     *
     * @param int $parentid {integer}
     * - Id of parent document. Default: 0 (site root).
     * @param array $tvidnames {array; '*'}
     * - Which TVs to fetch. In the form expected by getTemplateVarOutput(). Default: array().
     * @param int $published {0; 1; 'all'}
     * - Document publication status. Once the parameter equals 'all', the result will be returned regardless of whether the ducuments are published or they are not. Default: 1.
     * @param string $sortBy {string}
     * - How to sort the result array (field). Default: 'menuindex'.
     * @param string $sortDir {'ASC'; 'DESC'}
     * - How to sort the result array (direction). Default: 'ASC'.
     * @param string $where {string}
     * - SQL WHERE condition (use only document fields, not TV). Default: ''.
     * @param string $resultKey {string; false}
     * - Field, which values are keys into result array. Use the “false”, that result array keys just will be numbered. Default: 'id'.
     * @return array {array; false} - Result array, or false.
     * - Result array, or false.
     */
    public function getDocumentChildrenTVarOutput($parentid = 0, $tvidnames = array(), $published = 1, $sortBy = 'menuindex', $sortDir = 'ASC', $where = '', $resultKey = 'id')
    {
        $docs = $this->getDocumentChildren($parentid, $published, 0, 'id', $where, $sortBy, $sortDir);

        if (!$docs) {
            return false;
        } else {
            $result = array();

            $unsetResultKey = false;

            if ($resultKey !== false) {
                if (is_array($tvidnames)) {
                    if (count($tvidnames) != 0 && !in_array($resultKey, $tvidnames)) {
                        $tvidnames[] = $resultKey;
                        $unsetResultKey = true;
                    }
                } else if ($tvidnames != '*' && $tvidnames != $resultKey) {
                    $tvidnames = array($tvidnames, $resultKey);
                    $unsetResultKey = true;
                }
            }

            for ($i = 0; $i < count($docs); $i++) {
                $tvs = $this->getTemplateVarOutput($tvidnames, $docs[$i]['id'], $published);

                if ($tvs) {
                    if ($resultKey !== false && array_key_exists($resultKey, $tvs)) {
                        $result[$tvs[$resultKey]] = $tvs;

                        if ($unsetResultKey) {
                            unset($result[$tvs[$resultKey]][$resultKey]);
                        }
                    } else {
                        $result[] = $tvs;
                    }
                }
            }

            return $result;
        }
    }

    /**
     * Modified by Raymond for TV - Orig Modified by Apodigm - DocVars
     * Returns a single site_content field or TV record from the db.
     *
     * If a site content field the result is an associative array of 'name' and 'value'.
     *
     * If a TV the result is an array representing a db row including the fields specified in $fields.
     *
     * @param string $idname Can be a TV id or name
     * @param string $fields Fields to fetch from site_tmplvars. Default: *
     * @param string|type $docid Docid. Defaults to empty string which indicates the current document.
     * @param int $published Whether published or unpublished documents are in the result
     *                        Default: 1
     * @return bool
     */
    public function getTemplateVar($idname = "", $fields = "*", $docid = "", $published = 1)
    {
        if ($idname == "") {
            return false;
        } else {
            $result = $this->getTemplateVars(array($idname), $fields, $docid, $published, "", ""); //remove sorting for speed
            return ($result != false) ? $result[0] : false;
        }
    }

    /**
     * getTemplateVars
     * @version 1.0.1 (2014-02-19)
     *
     * @desc Returns an array of site_content field fields and/or TV records from the db.
     * Elements representing a site content field consist of an associative array of 'name' and 'value'.
     * Elements representing a TV consist of an array representing a db row including the fields specified in $fields.
     *
     * @param string|array $idnames {array; '*'} - Which TVs to fetch. Can relate to the TV ids in the db (array elements should be numeric only) or the TV names (array elements should be names only). @required
     * @param string|array $fields {comma separated string; '*'} - Fields names in the TV table of MODx database. Default: '*'
     * @param int|string $docid Id of a document to get. Default: an empty string which indicates the current document.
     * @param int|string $published {0; 1; 'all'} - Document publication status. Once the parameter equals 'all', the result will be returned regardless of whether the ducuments are published or they are not. Default: 1.
     * @param string $sort {comma separated string} - Fields of the TV table to sort by. Default: 'rank'.
     * @param string $dir {'ASC'; 'DESC'} - How to sort the result array (direction). Default: 'ASC'.
     *
     * @return array|bool Result array, or false.
     */
    public function getTemplateVars($idnames = array(), $fields = '*', $docid = '', $published = 1, $sort = 'rank', $dir = 'ASC')
    {
        $cacheKey = md5(print_r(func_get_args(), true));
        if (isset($this->tmpCache[__FUNCTION__][$cacheKey])) {
            return $this->tmpCache[__FUNCTION__][$cacheKey];
        }

        if (($idnames !== '*' && !is_array($idnames)) || empty($idnames) ) {
            return false;
        } else {
            // get document record
            if (empty($docid)) {
                $docid = $this->documentIdentifier;
                $docRow = $this->documentObject;
            } else {
                $docRow = $this->getDocument($docid, '*', $published);

                if (!$docRow) {
                    $this->tmpCache[__FUNCTION__][$cacheKey] = false;
                    return false;
                }
            }

            // get user defined template variables
            if (!empty($fields) && (is_scalar($fields) || \is_array($fields))) {
                if(\is_scalar($fields)) {
                    $fields = explode(',', $fields);
                }
                $fields = array_filter(array_map('trim', $fields), function($value) {
                    return $value !== 'value';
                });
                $fields = 'tv.' . implode(',tv.', $fields);
            } else {
                $fields = 'tv.*';
            }
            $sort = ($sort == '') ? '' : 'tv.' . implode(',tv.', array_filter(array_map('trim', explode(',', $sort))));

            if ($idnames === '*') {
                $query = 'tv.id<>0';
            } else {
                $query = (is_numeric($idnames[0]) ? 'tv.id' : 'tv.name') . " IN ('" . implode("','", $idnames) . "')";
            }

            $rs = $this->db->select(
                "{$fields}, IF(tvc.value != '', tvc.value, tv.default_text) as value",
                $this->getFullTableName('site_tmplvars') . ' tv ' .
                        'INNER JOIN ' . $this->getFullTableName('site_tmplvar_templates') . ' tvtpl ON tvtpl.tmplvarid = tv.id ' .
                        'LEFT JOIN ' . $this->getFullTableName('site_tmplvar_contentvalues') . " tvc ON tvc.tmplvarid = tv.id AND tvc.contentid = '" . $docid . "'",
                $query . " AND tvtpl.templateid = '" . $docRow['template'] . "'",
                ($sort ? ($sort . ' ' . $dir) : '')
            );

            $result = $this->db->makeArray($rs);

            // get default/built-in template variables
            if(is_array($docRow)){
                ksort($docRow);

                foreach ($docRow as $key => $value) {
                    if ($idnames === '*' || in_array($key, $idnames)) {
                        array_push($result, array(
                            'name' => $key,
                            'value' => $value
                        ));
                    }
                }
            }

            $this->tmpCache[__FUNCTION__][$cacheKey] = $result;

            return $result;
        }
    }

    /**
     * getTemplateVarOutput
     * @version 1.0.1 (2014-02-19)
     *
     * @desc Returns an associative array containing TV rendered output values.
     *
     * @param array $idnames {array; '*'}
     * - Which TVs to fetch - Can relate to the TV ids in the db (array elements should be numeric only) or the TV names (array elements should be names only). @required
     * @param string $docid {integer; ''}
     * - Id of a document to get. Default: an empty string which indicates the current document.
     * @param int $published {0; 1; 'all'}
     * - Document publication status. Once the parameter equals 'all', the result will be returned regardless of whether the ducuments are published or they are not. Default: 1.
     * @param string $sep {string}
     * - Separator that is used while concatenating in getTVDisplayFormat(). Default: ''.
     * @return array {array; false} - Result array, or false.
     * - Result array, or false.
     */
    public function getTemplateVarOutput($idnames = array(), $docid = '', $published = 1, $sep = '')
    {
        if (is_array($idnames) && empty($idnames) ) {
            return false;
        } else {
            $output = array();
            $vars = ($idnames == '*' || is_array($idnames)) ? $idnames : array($idnames);

            $docid = (int)$docid > 0 ? (int)$docid : $this->documentIdentifier;
            // remove sort for speed
            $result = $this->getTemplateVars($vars, '*', $docid, $published, '', '');

            if ($result == false) {
                return false;
            } else {
                $baspath = MODX_MANAGER_PATH . 'includes';
                include_once $baspath . '/tmplvars.format.inc.php';
                include_once $baspath . '/tmplvars.commands.inc.php';

                for ($i = 0; $i < count($result); $i++) {
                    $row = $result[$i];

                    if (!isset($row['id']) or !$row['id']) {
                        $output[$row['name']] = $row['value'];
                    } else {
                        $output[$row['name']] = getTVDisplayFormat($row['name'], $row['value'], $row['display'], $row['display_params'], $row['type'], $docid, $sep);
                    }
                }

                return $output;
            }
        }
    }

    /**
     * Returns the full table name based on db settings
     *
     * @param string $tbl Table name
     * @return string Table name with prefix
     */
    public function getFullTableName($tbl)
    {
        return $this->db->config['dbase'] . ".`" . $this->db->config['table_prefix'] . $tbl . "`";
    }

    /**
     * Returns the placeholder value
     *
     * @param string $name Placeholder name
     * @return string Placeholder value
     */
    public function getPlaceholder($name)
    {
        return isset($this->placeholders[$name]) ? $this->placeholders[$name] : null;
    }

    /**
     * Sets a value for a placeholder
     *
     * @param string $name The name of the placeholder
     * @param string $value The value of the placeholder
     */
    public function setPlaceholder($name, $value)
    {
        $this->placeholders[$name] = $value;
    }

    /**
     * Set placeholders en masse via an array or object.
     *
     * @param object|array $subject
     * @param string $prefix
     */
    public function toPlaceholders($subject, $prefix = '')
    {
        if (is_object($subject)) {
            $subject = get_object_vars($subject);
        }
        if (is_array($subject)) {
            foreach ($subject as $key => $value) {
                $this->toPlaceholder($key, $value, $prefix);
            }
        }
    }

    /**
     * For use by toPlaceholders(); For setting an array or object element as placeholder.
     *
     * @param string $key
     * @param object|array $value
     * @param string $prefix
     */
    public function toPlaceholder($key, $value, $prefix = '')
    {
        if (is_array($value) || is_object($value)) {
            $this->toPlaceholders($value, "{$prefix}{$key}.");
        } else {
            $this->setPlaceholder("{$prefix}{$key}", $value);
        }
    }

    /**
     * Returns the manager relative URL/path with respect to the site root.
     *
     * @global string $base_url
     * @return string The complete URL to the manager folder
     */
    public function getManagerPath()
    {
        return MODX_MANAGER_URL;
    }

    /**
     * Returns the cache relative URL/path with respect to the site root.
     *
     * @global string $base_url
     * @return string The complete URL to the cache folder
     */
    public function getCachePath()
    {
        global $base_url;
        $pth = $base_url . $this->getCacheFolder();
        return $pth;
    }

    /**
     * Sends a message to a user's message box.
     *
     * @param string $type Type of the message
     * @param string $to The recipient of the message
     * @param string $from The sender of the message
     * @param string $subject The subject of the message
     * @param string $msg The message body
     * @param int $private Whether it is a private message, or not
     *                     Default : 0
     */
    public function sendAlert($type, $to, $from, $subject, $msg, $private = 0)
    {
        $private = ($private) ? 1 : 0;
        if (!is_numeric($to)) {
            // Query for the To ID
            $rs = $this->db->select('id', $this->getFullTableName("manager_users"), "username='{$to}'");
            $to = $this->db->getValue($rs);
        }
        if (!is_numeric($from)) {
            // Query for the From ID
            $rs = $this->db->select('id', $this->getFullTableName("manager_users"), "username='{$from}'");
            $from = $this->db->getValue($rs);
        }
        // insert a new message into user_messages
        $this->db->insert(array(
            'type' => $type,
            'subject' => $subject,
            'message' => $msg,
            'sender' => $from,
            'recipient' => $to,
            'private' => $private,
            'postdate' => $_SERVER['REQUEST_TIME'] + $this->config['server_offset_time'],
            'messageread' => 0,
        ), $this->getFullTableName('user_messages'));
    }

    /**
     * Returns current user id.
     *
     * @param string $context . Default is an empty string which indicates the method should automatically pick 'web (frontend) or 'mgr' (backend)
     * @return string
     */
    public function getLoginUserID($context = '')
    {
        $out = false;

        if (!empty($context)) {
            if (is_scalar($context) && isset($_SESSION[$context . 'Validated'])) {
                $out = $_SESSION[$context . 'InternalKey'];
            }
        } else {
            switch (true) {
                case ($this->isFrontend() && isset ($_SESSION['webValidated'])): {
                    $out = $_SESSION['webInternalKey'];
                    break;
                }
                case ($this->isBackend() && isset ($_SESSION['mgrValidated'])): {
                    $out = $_SESSION['mgrInternalKey'];
                    break;
                }
            }
        }
        return $out;
    }

    /**
     * Returns current user name
     *
     * @param string $context . Default is an empty string which indicates the method should automatically pick 'web (frontend) or 'mgr' (backend)
     * @return string
     */
    public function getLoginUserName($context = '')
    {
        $out = false;

        if (!empty($context)) {
            if (is_scalar($context) && isset($_SESSION[$context . 'Validated'])) {
                $out = $_SESSION[$context . 'Shortname'];
            }
        } else {
            switch (true) {
                case ($this->isFrontend() && isset ($_SESSION['webValidated'])): {
                    $out = $_SESSION['webShortname'];
                    break;
                }
                case ($this->isBackend() && isset ($_SESSION['mgrValidated'])): {
                    $out = $_SESSION['mgrShortname'];
                    break;
                }
            }
        }
        return $out;
    }

    /**
     * Returns current login user type - web or manager
     *
     * @return string
     */
    public function getLoginUserType()
    {
        if ($this->isFrontend() && isset ($_SESSION['webValidated'])) {
            return 'web';
        } elseif ($this->isBackend() && isset ($_SESSION['mgrValidated'])) {
            return 'manager';
        } else {
            return '';
        }
    }

    /**
     * Returns a user info record for the given manager user
     *
     * @param int $uid
     * @return boolean|string
     */
    public function getUserInfo($uid)
    {
        if (isset($this->tmpCache[__FUNCTION__][$uid])) {
            return $this->tmpCache[__FUNCTION__][$uid];
        }

        $from = '[+prefix+]manager_users mu INNER JOIN [+prefix+]user_attributes mua ON mua.internalkey=mu.id';
        $where = sprintf("mu.id='%s'", $this->db->escape($uid));
        $rs = $this->db->select('mu.username, mu.password, mua.*', $from, $where, '', 1);

        if (!$this->db->getRecordCount($rs)) {
            return $this->tmpCache[__FUNCTION__][$uid] = false;
        }

        $row = $this->db->getRow($rs);
        if (!isset($row['usertype']) || !$row['usertype']) {
            $row['usertype'] = 'manager';
        }

        $this->tmpCache[__FUNCTION__][$uid] = $row;

        return $row;
    }

    /**
     * Returns a record for the web user
     *
     * @param int $uid
     * @return boolean|string
     */
    public function getWebUserInfo($uid)
    {
        $rs = $this->db->select('wu.username, wu.password, wua.*', $this->getFullTableName("web_users") . " wu
                INNER JOIN " . $this->getFullTableName("web_user_attributes") . " wua ON wua.internalkey=wu.id", "wu.id='{$uid}'");
        if ($row = $this->db->getRow($rs)) {
            if (!isset($row['usertype']) or !$row["usertype"]) {
                $row["usertype"] = "web";
            }
            return $row;
        }
    }

    /**
     * Returns an array of document groups that current user is assigned to.
     * This function will first return the web user doc groups when running from
     * frontend otherwise it will return manager user's docgroup.
     *
     * @param boolean $resolveIds Set to true to return the document group names
     *                            Default: false
     * @return string|array
     */
    public function getUserDocGroups($resolveIds = false)
    {
        if ($this->isFrontend() && isset($_SESSION['webDocgroups']) && isset($_SESSION['webValidated'])) {
            $dg = $_SESSION['webDocgroups'];
            $dgn = isset($_SESSION['webDocgrpNames']) ? $_SESSION['webDocgrpNames'] : false;
        } else if ($this->isBackend() && isset($_SESSION['mgrDocgroups']) && isset($_SESSION['mgrValidated'])) {
            $dg = $_SESSION['mgrDocgroups'];
            $dgn = isset($_SESSION['mgrDocgrpNames']) ? $_SESSION['mgrDocgrpNames'] : false;
        } else {
            $dg = '';
        }
        if (!$resolveIds) {
            return $dg;
        } else if (is_array($dgn)) {
            return $dgn;
        } else if (is_array($dg)) {
            // resolve ids to names
            $dgn = array();
            $ds = $this->db->select('name', $this->getFullTableName("documentgroup_names"), "id IN (" . implode(",", $dg) . ")");
            while ($row = $this->db->getRow($ds)) {
                $dgn[] = $row['name'];
            }
            // cache docgroup names to session
            if ($this->isFrontend()) {
                $_SESSION['webDocgrpNames'] = $dgn;
            } else {
                $_SESSION['mgrDocgrpNames'] = $dgn;
            }
            return $dgn;
        }
    }

    /**
     * Change current web user's password
     *
     * @todo Make password length configurable, allow rules for passwords and translation of messages
     * @param string $oldPwd
     * @param string $newPwd
     * @return string|boolean Returns true if successful, oterhwise return error
     *                        message
     */
    public function changeWebUserPassword($oldPwd, $newPwd)
    {
        $rt = false;
        if ($_SESSION["webValidated"] == 1) {
            $tbl = $this->getFullTableName("web_users");
            $ds = $this->db->select('id, username, password', $tbl, "id='" . $this->getLoginUserID() . "'");
            if ($row = $this->db->getRow($ds)) {
                if ($row["password"] == md5($oldPwd)) {
                    if (strlen($newPwd) < 6) {
                        return "Password is too short!";
                    } elseif ($newPwd == "") {
                        return "You didn't specify a password for this user!";
                    } else {
                        $this->db->update(array(
                            'password' => $this->db->escape($newPwd),
                        ), $tbl, "id='" . $this->getLoginUserID() . "'");
                        // invoke OnWebChangePassword event
                        $this->invokeEvent("OnWebChangePassword", array(
                            "userid" => $row["id"],
                            "username" => $row["username"],
                            "userpassword" => $newPwd
                        ));
                        return true;
                    }
                } else {
                    return "Incorrect password.";
                }
            }
        }
        return $rt;
    }

    /**
     * Returns true if the current web user is a member the specified groups
     *
     * @param array $groupNames
     * @return boolean
     */
    public function isMemberOfWebGroup($groupNames = array())
    {
        if (!is_array($groupNames)) {
            return false;
        }
        // check cache
        $grpNames = isset ($_SESSION['webUserGroupNames']) ? $_SESSION['webUserGroupNames'] : false;
        if (!is_array($grpNames)) {
            $rs = $this->db->select('wgn.name', $this->getFullTableName("webgroup_names") . " wgn
                    INNER JOIN " . $this->getFullTableName("web_groups") . " wg ON wg.webgroup=wgn.id AND wg.webuser='" . $this->getLoginUserID() . "'");
            $grpNames = $this->db->getColumn("name", $rs);
            // save to cache
            $_SESSION['webUserGroupNames'] = $grpNames;
        }
        foreach ($groupNames as $k => $v) {
            if (in_array(trim($v), $grpNames)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Registers Client-side CSS scripts - these scripts are loaded at inside
     * the <head> tag
     *
     * @param string $src
     * @param string $media Default: Empty string
     * @return string
     */
    public function regClientCSS($src, $media = '')
    {
        if (empty($src) || isset ($this->loadedjscripts[$src])) {
            return '';
        }
        $nextpos = max(array_merge(array(0), array_keys($this->sjscripts))) + 1;
        $this->loadedjscripts[$src]['startup'] = true;
        $this->loadedjscripts[$src]['version'] = '0';
        $this->loadedjscripts[$src]['pos'] = $nextpos;
        if (strpos(strtolower($src), "<style") !== false || strpos(strtolower($src), "<link") !== false) {
            $this->sjscripts[$nextpos] = $src;
        } else {
            $this->sjscripts[$nextpos] = "\t" . '<link rel="stylesheet" type="text/css" href="' . $src . '" ' . ($media ? 'media="' . $media . '" ' : '') . '/>';
        }
    }

    /**
     * Registers Startup Client-side JavaScript - these scripts are loaded at inside the <head> tag
     *
     * @param string $src
     * @param array $options Default: 'name'=>'', 'version'=>'0', 'plaintext'=>false
     */
    public function regClientStartupScript($src, $options = array('name' => '', 'version' => '0', 'plaintext' => false))
    {
        $this->regClientScript($src, $options, true);
    }

    /**
     * Registers Client-side JavaScript these scripts are loaded at the end of the page unless $startup is true
     *
     * @param string $src
     * @param array $options Default: 'name'=>'', 'version'=>'0', 'plaintext'=>false
     * @param boolean $startup Default: false
     * @return string
     */
    public function regClientScript($src, $options = array('name' => '', 'version' => '0', 'plaintext' => false), $startup = false)
    {
        if (empty($src)) {
            return '';
        } // nothing to register
        if (!is_array($options)) {
            if (is_bool($options))  // backward compatibility with old plaintext parameter
            {
                $options = array('plaintext' => $options);
            } elseif (is_string($options)) // Also allow script name as 2nd param
            {
                $options = array('name' => $options);
            } else {
                $options = array();
            }
        }
        $name = isset($options['name']) ? strtolower($options['name']) : '';
        $version = isset($options['version']) ? $options['version'] : '0';
        $plaintext = isset($options['plaintext']) ? $options['plaintext'] : false;
        $key = !empty($name) ? $name : $src;
        unset($overwritepos); // probably unnecessary--just making sure

        $useThisVer = true;
        if (isset($this->loadedjscripts[$key])) { // a matching script was found
            // if existing script is a startup script, make sure the candidate is also a startup script
            if ($this->loadedjscripts[$key]['startup']) {
                $startup = true;
            }

            if (empty($name)) {
                $useThisVer = false; // if the match was based on identical source code, no need to replace the old one
            } else {
                $useThisVer = version_compare($this->loadedjscripts[$key]['version'], $version, '<');
            }

            if ($useThisVer) {
                if ($startup == true && $this->loadedjscripts[$key]['startup'] == false) {
                    // remove old script from the bottom of the page (new one will be at the top)
                    unset($this->jscripts[$this->loadedjscripts[$key]['pos']]);
                } else {
                    // overwrite the old script (the position may be important for dependent scripts)
                    $overwritepos = $this->loadedjscripts[$key]['pos'];
                }
            } else { // Use the original version
                if ($startup == true && $this->loadedjscripts[$key]['startup'] == false) {
                    // need to move the exisiting script to the head
                    $version = $this->loadedjscripts[$key][$version];
                    $src = $this->jscripts[$this->loadedjscripts[$key]['pos']];
                    unset($this->jscripts[$this->loadedjscripts[$key]['pos']]);
                } else {
                    return ''; // the script is already in the right place
                }
            }
        }

        if ($useThisVer && $plaintext != true && (strpos(strtolower($src), "<script") === false)) {
            $src = "\t" . '<script type="text/javascript" src="' . $src . '"></script>';
        }
        if ($startup) {
            $pos = isset($overwritepos) ? $overwritepos : max(array_merge(array(0), array_keys($this->sjscripts))) + 1;
            $this->sjscripts[$pos] = $src;
        } else {
            $pos = isset($overwritepos) ? $overwritepos : max(array_merge(array(0), array_keys($this->jscripts))) + 1;
            $this->jscripts[$pos] = $src;
        }
        $this->loadedjscripts[$key]['version'] = $version;
        $this->loadedjscripts[$key]['startup'] = $startup;
        $this->loadedjscripts[$key]['pos'] = $pos;
        return '';
    }

    /**
     * Returns all registered JavaScripts
     *
     * @return string
     */
    public function regClientStartupHTMLBlock($html)
    {
        return $this->regClientScript($html, true, true);
    }

    /**
     * Returns all registered startup scripts
     *
     * @return string
     */
    public function regClientHTMLBlock($html)
    {
        return $this->regClientScript($html, true);
    }

    /**
     * Remove unwanted html tags and snippet, settings and tags
     *
     * @param string $html
     * @param string $allowed Default: Empty string
     * @return string
     */
    public function stripTags($html, $allowed = "")
    {
        $t = strip_tags($html, $allowed);
        $t = preg_replace('~\[\*(.*?)\*\]~', "", $t); //tv
        $t = preg_replace('~\[\[(.*?)\]\]~', "", $t); //snippet
        $t = preg_replace('~\[\!(.*?)\!\]~', "", $t); //snippet
        $t = preg_replace('~\[\((.*?)\)\]~', "", $t); //settings
        $t = preg_replace('~\[\+(.*?)\+\]~', "", $t); //placeholders
        $t = preg_replace('~{{(.*?)}}~', "", $t); //chunks
        return $t;
    }

    /**
     * Add an event listener to a plugin - only for use within the current execution cycle
     *
     * @param string $evtName
     * @param string $pluginName
     * @return boolean|int
     */
    public function addEventListener($evtName, $pluginName)
    {
        if (!$evtName || !$pluginName) {
            return false;
        }
        if (!array_key_exists($evtName, $this->pluginEvent)) {
            $this->pluginEvent[$evtName] = array();
        }
        return array_push($this->pluginEvent[$evtName], $pluginName); // return array count
    }

    /**
     * Remove event listener - only for use within the current execution cycle
     *
     * @param string $evtName
     * @return boolean
     */
    public function removeEventListener($evtName)
    {
        if (!$evtName) {
            return false;
        }
        unset ($this->pluginEvent[$evtName]);
    }

    /**
     * Remove all event listeners - only for use within the current execution cycle
     */
    public function removeAllEventListener()
    {
        unset ($this->pluginEvent);
        $this->pluginEvent = array();
    }

    /**
     * Invoke an event.
     *
     * @param string $evtName
     * @param array $extParams Parameters available to plugins. Each array key will be the PHP variable name, and the array value will be the variable value.
     * @return boolean|array
     */
    public function invokeEvent($evtName, $extParams = array())
    {
        if (!$evtName) {
            return false;
        }
        if (!isset ($this->pluginEvent[$evtName])) {
            return false;
        }

        if ($this->event->activePlugin != '') {
            $event = new SystemEvent;
            $event->setPreviousEvent($this->event);
            $this->event = $event;
            $this->Event = &$this->event;
        }

        $results = null;
        foreach ($this->pluginEvent[$evtName] as $pluginName) { // start for loop
            if ($this->dumpPlugins) {
                $eventtime = $this->getMicroTime();
            }
            // reset event object
            $e = &$this->event;
            $e->_resetEventObject();
            $e->name = $evtName;
            $e->activePlugin = $pluginName;

            // get plugin code
            $_ = $this->getPluginCode($pluginName);
            $pluginCode = $_['code'];
            $pluginProperties = $_['props'];

            // load default params/properties
            $parameter = $this->parseProperties($pluginProperties);
            if (!is_array($parameter)) {
                $parameter = array();
            }
            if (!empty($extParams)) {
                $parameter = array_merge($parameter, $extParams);
            }

            // eval plugin
            $this->evalPlugin($pluginCode, $parameter);

            if (class_exists('PHxParser')) {
                $this->config['enable_filter'] = 0;
            }

            if ($this->dumpPlugins) {
                $eventtime = $this->getMicroTime() - $eventtime;
                $this->pluginsCode .= sprintf('<fieldset><legend><b>%s / %s</b> (%2.2f ms)</legend>', $evtName, $pluginName, $eventtime * 1000);
                foreach ($parameter as $k => $v) {
                    $this->pluginsCode .= "{$k} => " . print_r($v, true) . '<br>';
                }
                $this->pluginsCode .= '</fieldset><br />';
                $this->pluginsTime["{$evtName} / {$pluginName}"] += $eventtime;
            }
            if ($this->event->getOutput() != '') {
                $results[] = $this->event->getOutput();
            }
            if ($this->event->_propagate != true) {
                break;
            }
        }

        $event = $this->event->getPreviousEvent();

        if ($event) {
            unset($this->event);
            $this->event = $event;
            $this->Event = &$this->event;
        } else {
            $this->event->activePlugin = '';
        }

        return $results;
    }

    /**
     * Returns plugin-code and properties
     *
     * @param string $pluginName
     * @return array Associative array consisting of 'code' and 'props'
     */
    public function getPluginCode($pluginName)
    {
        $plugin = array();
        if (isset ($this->pluginCache[$pluginName])) {
            $pluginCode = $this->pluginCache[$pluginName];
            $pluginProperties = isset($this->pluginCache[$pluginName . "Props"]) ? $this->pluginCache[$pluginName . "Props"] : '';
        } else {
            $pluginName = $this->db->escape($pluginName);
            $result = $this->db->select('name, plugincode, properties', $this->getFullTableName("site_plugins"), "name='{$pluginName}' AND disabled=0");
            if ($row = $this->db->getRow($result)) {
                $pluginCode = $this->pluginCache[$row['name']] = $row['plugincode'];
                $pluginProperties = $this->pluginCache[$row['name'] . "Props"] = $row['properties'];
            } else {
                $pluginCode = $this->pluginCache[$pluginName] = "return false;";
                $pluginProperties = '';
            }
        }
        $plugin['code'] = $pluginCode;
        $plugin['props'] = $pluginProperties;

        return $plugin;
    }

    /**
     * Parses a resource property string and returns the result as an array
     *
     * @param string $propertyString
     * @param string|null $elementName
     * @param string|null $elementType
     * @return array Associative array in the form property name => property value
     */
    public function parseProperties($propertyString, $elementName = null, $elementType = null)
    {
        $propertyString = trim($propertyString);
        $propertyString = str_replace('{}', '', $propertyString);
        $propertyString = str_replace('} {', ',', $propertyString);
        $property = array();
        if (!empty($propertyString) && $propertyString != '{}') {
            $jsonFormat = $this->isJson($propertyString, true);
            // old format
            if ($jsonFormat === false) {
                $props = explode('&', $propertyString);
                foreach ($props as $prop) {

                    if (empty($prop)) {
                        continue;
                    } elseif (strpos($prop, '=') === false) {
                        $property[trim($prop)] = '';
                        continue;
                    }

                    $_ = explode('=', $prop, 2);
                    $key = trim($_[0]);
                    $p = explode(';', trim($_[1]));
                    switch ($p[1]) {
                        case 'list':
                        case 'list-multi':
                        case 'checkbox':
                        case 'radio':
                            $value = !isset($p[3]) ? '' : $p[3];
                            break;
                        default:
                            $value = !isset($p[2]) ? '' : $p[2];
                    }
                    if (!empty($key)) {
                        $property[$key] = $value;
                    }
                }
                // new json-format
            } else if (!empty($jsonFormat)) {
                foreach ($jsonFormat as $key => $row) {
                    if (!empty($key)) {
                        if (is_array($row)) {
                            if (isset($row[0]['value'])) {
                                $value = $row[0]['value'];
                            }
                        } else {
                            $value = $row;
                        }
                        if (isset($value) && $value !== '') {
                            $property[$key] = $value;
                        }
                    }
                }
            }
        }
        if (!empty($elementName) && !empty($elementType)) {
            $out = $this->invokeEvent('OnParseProperties', array(
                'element' => $elementName,
                'type' => $elementType,
                'args' => $property
            ));
            if (is_array($out)) {
                $out = array_pop($out);
            }
            if (is_array($out)) {
                $property = $out;
            }
        }

        return $property;
    }

    /**
     * Parses docBlock from a file and returns the result as an array
     *
     * @param string $element_dir
     * @param string $filename
     * @param boolean $escapeValues
     * @return array Associative array in the form property name => property value
     */
    public function parseDocBlockFromFile($element_dir, $filename, $escapeValues = false)
    {
        $params = array();
        $fullpath = $element_dir . '/' . $filename;
        if (is_readable($fullpath)) {
            $tpl = @fopen($fullpath, "r");
            if ($tpl) {
                $params['filename'] = $filename;
                $docblock_start_found = false;
                $name_found = false;
                $description_found = false;
                $docblock_end_found = false;
                $arrayParams = array('author', 'documentation', 'reportissues', 'link');

                while (!feof($tpl)) {
                    $line = fgets($tpl);
                    $r = $this->parseDocBlockLine($line, $docblock_start_found, $name_found, $description_found, $docblock_end_found);
                    $docblock_start_found = $r['docblock_start_found'];
                    $name_found = $r['name_found'];
                    $description_found = $r['description_found'];
                    $docblock_end_found = $r['docblock_end_found'];
                    $param = $r['param'];
                    $val = $r['val'];
                    if (!$docblock_end_found) {
                        break;
                    }
                    if (!$docblock_start_found || !$name_found || !$description_found || empty($param)) {
                        continue;
                    }
                    if (!empty($param)) {
                        if (in_array($param, $arrayParams)) {
                            if (!isset($params[$param])) {
                                $params[$param] = array();
                            }
                            $params[$param][] = $escapeValues ? $this->db->escape($val) : $val;
                        } else {
                            $params[$param] = $escapeValues ? $this->db->escape($val) : $val;
                        }
                    }
                }
                @fclose($tpl);
            }
        }
        return $params;
    }

    /**
     * Parses docBlock from string and returns the result as an array
     *
     * @param string $string
     * @param boolean $escapeValues
     * @return array Associative array in the form property name => property value
     */
    public function parseDocBlockFromString($string, $escapeValues = false)
    {
        $params = array();
        if (!empty($string)) {
            $string = str_replace('\r\n', '\n', $string);
            $exp = explode('\n', $string);
            $docblock_start_found = false;
            $name_found = false;
            $description_found = false;
            $docblock_end_found = false;
            $arrayParams = array('author', 'documentation', 'reportissues', 'link');

            foreach ($exp as $line) {
                $r = $this->parseDocBlockLine($line, $docblock_start_found, $name_found, $description_found, $docblock_end_found);
                $docblock_start_found = $r['docblock_start_found'];
                $name_found = $r['name_found'];
                $description_found = $r['description_found'];
                $docblock_end_found = $r['docblock_end_found'];
                $param = $r['param'];
                $val = $r['val'];
                if (!$docblock_start_found) {
                    continue;
                }
                if ($docblock_end_found) {
                    break;
                }
                if (!empty($param)) {
                    if (in_array($param, $arrayParams)) {
                        if (!isset($params[$param])) {
                            $params[$param] = array();
                        }
                        $params[$param][] = $escapeValues ? $this->db->escape($val) : $val;
                    } else {
                        $params[$param] = $escapeValues ? $this->db->escape($val) : $val;
                    }
                }
            }
        }
        return $params;
    }

    /**
     * Parses docBlock of a component´s source-code and returns the result as an array
     * (modified parseDocBlock() from modules/stores/setup.info.php by Bumkaka & Dmi3yy)
     *
     * @param string $line
     * @param boolean $docblock_start_found
     * @param boolean $name_found
     * @param boolean $description_found
     * @param boolean $docblock_end_found
     * @return array Associative array in the form property name => property value
     */
    public function parseDocBlockLine($line, $docblock_start_found, $name_found, $description_found, $docblock_end_found)
    {
        $param = '';
        $val = '';
        $ma = null;
        if (!$docblock_start_found) {
            // find docblock start
            if (strpos($line, '/**') !== false) {
                $docblock_start_found = true;
            }
        } elseif (!$name_found) {
            // find name
            if (preg_match("/^\s+\*\s+(.+)/", $line, $ma)) {
                $param = 'name';
                $val = trim($ma[1]);
                $name_found = !empty($val);
            }
        } elseif (!$description_found) {
            // find description
            if (preg_match("/^\s+\*\s+(.+)/", $line, $ma)) {
                $param = 'description';
                $val = trim($ma[1]);
                $description_found = !empty($val);
            }
        } else {
            if (preg_match("/^\s+\*\s+\@([^\s]+)\s+(.+)/", $line, $ma)) {
                $param = trim($ma[1]);
                $val = trim($ma[2]);
                if (!empty($param) && !empty($val)) {
                    if ($param == 'internal') {
                        $ma = null;
                        if (preg_match("/\@([^\s]+)\s+(.+)/", $val, $ma)) {
                            $param = trim($ma[1]);
                            $val = trim($ma[2]);
                        }
                    }
                }
            } elseif (preg_match("/^\s*\*\/\s*$/", $line)) {
                $docblock_end_found = true;
            }
        }
        return array(
            'docblock_start_found' => $docblock_start_found,
            'name_found' => $name_found,
            'description_found' => $description_found,
            'docblock_end_found' => $docblock_end_found,
            'param' => $param,
            'val' => $val
        );
    }

    /**
     * Renders docBlock-parameters into human readable list
     *
     * @param array $parsed
     * @return string List in HTML-format
     */
    public function convertDocBlockIntoList($parsed)
    {
        global $_lang;

        // Replace special placeholders & make URLs + Emails clickable
        $ph = array('site_url' => MODX_SITE_URL);
        $regexUrl = "/((http|https|ftp|ftps)\:\/\/[^\/]+(\/[^\s]+[^,.?!:;\s])?)/";
        $regexEmail = '#([0-9a-z]([-_.]?[0-9a-z])*@[0-9a-z]([-.]?[0-9a-z])*\\.[a-wyz][a-z](fo|g|l|m|mes|o|op|pa|ro|seum|t|u|v|z)?)#i';
        $emailSubject = isset($parsed['name']) ? '?subject=' . $parsed['name'] : '';
        $emailSubject .= isset($parsed['version']) ? ' v' . $parsed['version'] : '';
        foreach ($parsed as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $key2 => $val2) {
                    $val2 = $this->parseText($val2, $ph);
                    if (preg_match($regexUrl, $val2, $url)) {
                        $val2 = preg_replace($regexUrl, "<a href=\"{$url[0]}\" target=\"_blank\">{$url[0]}</a> ", $val2);
                    }
                    if (preg_match($regexEmail, $val2, $url)) {
                        $val2 = preg_replace($regexEmail, '<a href="mailto:\\1' . $emailSubject . '">\\1</a>', $val2);
                    }
                    $parsed[$key][$key2] = $val2;
                }
            } else {
                $val = $this->parseText($val, $ph);
                if (preg_match($regexUrl, $val, $url)) {
                    $val = preg_replace($regexUrl, "<a href=\"{$url[0]}\" target=\"_blank\">{$url[0]}</a> ", $val);
                }
                if (preg_match($regexEmail, $val, $url)) {
                    $val = preg_replace($regexEmail, '<a href="mailto:\\1' . $emailSubject . '">\\1</a>', $val);
                }
                $parsed[$key] = $val;
            }
        }

        $arrayParams = array(
            'documentation' => $_lang['documentation'],
            'reportissues' => $_lang['report_issues'],
            'link' => $_lang['further_info'],
            'author' => $_lang['author_infos']
        );

        $nl = "\n";
        $list = isset($parsed['logo']) ? '<img src="' . $this->config['base_url'] . ltrim($parsed['logo'], "/") . '" style="float:right;max-width:100px;height:auto;" />' . $nl : '';
        $list .= '<p>' . $nl;
        $list .= isset($parsed['name']) ? '<strong>' . $parsed['name'] . '</strong><br/>' . $nl : '';
        $list .= isset($parsed['description']) ? $parsed['description'] . $nl : '';
        $list .= '</p><br/>' . $nl;
        $list .= isset($parsed['version']) ? '<p><strong>' . $_lang['version'] . ':</strong> ' . $parsed['version'] . '</p>' . $nl : '';
        $list .= isset($parsed['license']) ? '<p><strong>' . $_lang['license'] . ':</strong> ' . $parsed['license'] . '</p>' . $nl : '';
        $list .= isset($parsed['lastupdate']) ? '<p><strong>' . $_lang['last_update'] . ':</strong> ' . $parsed['lastupdate'] . '</p>' . $nl : '';
        $list .= '<br/>' . $nl;
        $first = true;
        foreach ($arrayParams as $param => $label) {
            if (isset($parsed[$param])) {
                if ($first) {
                    $list .= '<p><strong>' . $_lang['references'] . '</strong></p>' . $nl;
                    $list .= '<ul class="docBlockList">' . $nl;
                    $first = false;
                }
                $list .= '    <li><strong>' . $label . '</strong>' . $nl;
                $list .= '        <ul>' . $nl;
                foreach ($parsed[$param] as $val) {
                    $list .= '            <li>' . $val . '</li>' . $nl;
                }
                $list .= '        </ul></li>' . $nl;
            }
        }
        $list .= !$first ? '</ul>' . $nl : '';

        return $list;
    }

    /**
     * @param string $string
     * @return string
     */
    public function removeSanitizeSeed($string = '')
    {
        global $sanitize_seed;

        if (!$string || strpos($string, $sanitize_seed) === false) {
            return $string;
        }

        return str_replace($sanitize_seed, '', $string);
    }

    /**
     * @param string $content
     * @return string
     */
    public function cleanUpMODXTags($content = '')
    {
        if ($this->minParserPasses < 1) {
            return $content;
        }

        $enable_filter = isset($this->config['enable_filter']) ? $this->config['enable_filter'] : 0;
        $this->config['enable_filter'] = 1;
        $_ = array('[* *]', '[( )]', '{{ }}', '[[ ]]', '[+ +]');
        foreach ($_ as $brackets) {
            list($left, $right) = explode(' ', $brackets);
            if (strpos($content, $left) !== false) {
                if ($left === '[*') {
                    $content = $this->mergeDocumentContent($content);
                } elseif ($left === '[(') {
                    $content = $this->mergeSettingsContent($content);
                } elseif ($left === '{{') {
                    $content = $this->mergeChunkContent($content);
                } elseif ($left === '[[') {
                    $content = $this->evalSnippets($content);
                }
            }
        }
        foreach ($_ as $brackets) {
            list($left, $right) = explode(' ', $brackets);
            if (strpos($content, $left) !== false) {
                $matches = $this->getTagsFromContent($content, $left, $right);
                $content = isset($matches[0]) ? str_replace($matches[0], '', $content) : $content;
            }
        }
        $this->config['enable_filter'] = $enable_filter;
        return $content;
    }

    /**
     * @param string $str
     * @param string $allowable_tags
     * @return string
     */
    public function strip_tags($str = '', $allowable_tags = '')
    {
        $str = strip_tags($str, $allowable_tags);
        modx_sanitize_gpc($str);
        return $str;
    }

    /**
     * @param string $name
     * @param string $phpCode
     */
    public function addSnippet($name, $phpCode)
    {
        $this->snippetCache['#' . $name] = $phpCode;
    }

    /**
     * @param string $name
     * @param string $text
     */
    public function addChunk($name, $text)
    {
        $this->chunkCache['#' . $name] = $text;
    }

    /**
     * @param string $phpcode
     * @param string $evalmode
     * @param string $safe_functions
     * @return string|void
     */
    public function safeEval($phpcode = '', $evalmode = '', $safe_functions = '')
    {
        if ($evalmode == '') {
            $evalmode = $this->config['allow_eval'];
        }
        if ($safe_functions == '') {
            $safe_functions = $this->config['safe_functions_at_eval'];
        }

        modx_sanitize_gpc($phpcode);

        switch ($evalmode) {
            case 'with_scan'         :
                $isSafe = $this->isSafeCode($phpcode, $safe_functions);
                break;
            case 'with_scan_at_post' :
                $isSafe = $_POST ? $this->isSafeCode($phpcode, $safe_functions) : true;
                break;
            case 'everytime_eval'    :
                $isSafe = true;
                break; // Should debug only
            case 'dont_eval'         :
            default                  :
                return $phpcode;
        }

        if (!$isSafe) {
            $msg = $phpcode . "\n" . $this->currentSnippet . "\n" . print_r($_SERVER, true);
            $title = sprintf('Unknown eval was executed (%s)', $this->htmlspecialchars(substr(trim($phpcode), 0, 50)));
            $this->messageQuit($title, '', true, '', '', 'Parser', $msg);
            return;
        }

        ob_start();
        $return = eval($phpcode);
        $echo = ob_get_clean();

        if (is_array($return)) {
            return 'array()';
        }

        $output = $echo . $return;
        modx_sanitize_gpc($output);
        return $this->htmlspecialchars($output); // Maybe, all html tags are dangerous
    }

    /**
     * @param string $phpcode
     * @param string $safe_functions
     * @return bool
     */
    public function isSafeCode($phpcode = '', $safe_functions = '')
    { // return true or false
        if ($safe_functions == '') {
            return false;
        }

        $safe = explode(',', $safe_functions);

        $phpcode = rtrim($phpcode, ';') . ';';
        $tokens = token_get_all('<?php ' . $phpcode);
        foreach ($tokens as $i => $token) {
            if (!is_array($token)) {
                continue;
            }
            $tokens[$i]['token_name'] = token_name($token[0]);
        }
        foreach ($tokens as $token) {
            if (!is_array($token)) {
                continue;
            }
            switch ($token['token_name']) {
                case 'T_STRING':
                    if (!in_array($token[1], $safe)) {
                        return false;
                    }
                    break;
                case 'T_VARIABLE':
                    if ($token[1] == '$GLOBALS') {
                        return false;
                    }
                    break;
                case 'T_EVAL':
                    return false;
            }
        }
        return true;
    }

    /**
     * @param string $str
     * @return bool|mixed|string
     */
    public function atBindFileContent($str = '')
    {

        $search_path = array('assets/tvs/', 'assets/chunks/', 'assets/templates/', $this->config['rb_base_url'] . 'files/', '');

        if (stripos($str, '@FILE') !== 0) {
            return $str;
        }
        if (strpos($str, "\n") !== false) {
            $str = substr($str, 0, strpos("\n", $str));
        }

        if ($this->getExtFromFilename($str) === '.php') {
            return 'Could not retrieve PHP file.';
        }

        $str = substr($str, 6);
        $str = trim($str);
        if (strpos($str, '\\') !== false) {
            $str = str_replace('\\', '/', $str);
        }
        $str = ltrim($str, '/');

        $errorMsg = sprintf("Could not retrieve string '%s'.", $str);

        foreach ($search_path as $path) {
            $file_path = MODX_BASE_PATH . $path . $str;
            if (strpos($file_path, MODX_MANAGER_PATH) === 0) {
                return $errorMsg;
            } elseif (is_file($file_path)) {
                break;
            } else {
                $file_path = false;
            }
        }

        if (!$file_path) {
            return $errorMsg;
        }

        $content = (string)file_get_contents($file_path);
        if ($content === false) {
            return $errorMsg;
        }

        return $content;
    }

    /**
     * @param $str
     * @return bool|string
     */
    public function getExtFromFilename($str)
    {
        $str = strtolower(trim($str));
        $pos = strrpos($str, '.');
        if ($pos === false) {
            return false;
        } else {
            return substr($str, $pos);
        }
    }
    /***************************************************************************************/
    /* End of API functions                                       */
    /***************************************************************************************/

    public function registerErrorHandlers()
    {
        $modx = $this;

        set_error_handler(array($modx, 'phpError'), E_ALL);

        register_shutdown_function(function() use($modx) {
            $error = error_get_last();
            if (\is_array($error)) {
                $code = isset($error['type']) ? $error['type'] : 0;
                if ($code>0) {
                    $modx->phpError(
                        $code,
                        isset($error['message']) ? $error['message'] : '',
                        isset($error['file']) ? $error['file'] : '',
                        isset($error['line']) ? $error['line'] : ''
                    );
                }
            }
        });
    }
    /**
     * PHP error handler set by http://www.php.net/manual/en/function.set-error-handler.php
     *
     * Checks the PHP error and calls messageQuit() unless:
     *  - error_reporting() returns 0, or
     *  - the PHP error level is 0, or
     *  - the PHP error level is 8 (E_NOTICE) and stopOnNotice is false
     *
     * @param int $nr The PHP error level as per http://www.php.net/manual/en/errorfunc.constants.php
     * @param string $text Error message
     * @param string $file File where the error was detected
     * @param string $line Line number within $file
     * @return boolean
     */
    public function phpError($nr, $text, $file, $line)
    {
        if (error_reporting() == 0 || $nr == 0) {
            return true;
        }
        if ($this->stopOnNotice == false) {
            switch ($nr) {
                case E_NOTICE:
                    if ($this->error_reporting <= 2) {
                        return true;
                    }
                    $isError = false;
                    $msg = 'PHP Minor Problem (this message show logged in only)';
                    break;
                case E_STRICT:
                case E_DEPRECATED:
                    if ($this->error_reporting <= 1) {
                        return true;
                    }
                    $isError = true;
                    $msg = 'PHP Strict Standards Problem';
                    break;
                default:
                    if ($this->error_reporting === 0) {
                        return true;
                    }
                    $isError = true;
                    $msg = 'PHP Parse Error';
            }
        }
        if (is_readable($file)) {
            $source = file($file);
            $source = $this->htmlspecialchars($source[$line - 1]);
        } else {
            $source = "";
        } //Error $nr in $file at $line: <div><code>$source</code></div>

        $this->messageQuit($msg, '', $isError, $nr, $file, $source, $text, $line);
    }

    /**
     * @param string $msg
     * @param string $query
     * @param bool $is_error
     * @param string $nr
     * @param string $file
     * @param string $source
     * @param string $text
     * @param string $line
     * @param string $output
     * @return bool
     */
    public function messageQuit($msg = 'unspecified error', $query = '', $is_error = true, $nr = '', $file = '', $source = '', $text = '', $line = '', $output = '')
    {
        if (0 < $this->messageQuitCount) {
            return;
        }
        $this->messageQuitCount++;

        if (!class_exists('makeTable')) {
            include_once('extenders/maketable.class.php');
        }
        $MakeTable = new MakeTable();
        $MakeTable->setTableClass('grid');
        $MakeTable->setRowRegularClass('gridItem');
        $MakeTable->setRowAlternateClass('gridAltItem');
        $MakeTable->setColumnWidths(array('100px'));

        $table = array();

        $version = isset ($GLOBALS['modx_version']) ? $GLOBALS['modx_version'] : '';
        $release_date = isset ($GLOBALS['release_date']) ? $GLOBALS['release_date'] : '';
        $request_uri = "http://" . $_SERVER['HTTP_HOST'] . ($_SERVER["SERVER_PORT"] == 80 ? "" : (":" . $_SERVER["SERVER_PORT"])) . $_SERVER['REQUEST_URI'];
        $request_uri = $this->htmlspecialchars($request_uri, ENT_QUOTES, $this->config['modx_charset']);
        $ua = $this->htmlspecialchars($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES, $this->config['modx_charset']);
        $referer = $this->htmlspecialchars($_SERVER['HTTP_REFERER'], ENT_QUOTES, $this->config['modx_charset']);
        if ($is_error) {
            $str = '<h2 style="color:red">&laquo; Evo Parse Error &raquo;</h2>';
            if ($msg != 'PHP Parse Error') {
                $str .= '<h3 style="color:red">' . $msg . '</h3>';
            }
        } else {
            $str = '<h2 style="color:#003399">&laquo; Evo Debug/ stop message &raquo;</h2>';
            $str .= '<h3 style="color:#003399">' . $msg . '</h3>';
        }

        if (!empty ($query)) {
            $str .= '<pre style="font-weight:bold;border:1px solid #ccc;padding:8px;color:#333;background-color:#ffffcd;margin-bottom:15px;">SQL &gt; <span id="sqlHolder">' . $query . '</span></pre>';
        }

        $errortype = array(
            E_ERROR => "ERROR",
            E_WARNING => "WARNING",
            E_PARSE => "PARSING ERROR",
            E_NOTICE => "NOTICE",
            E_CORE_ERROR => "CORE ERROR",
            E_CORE_WARNING => "CORE WARNING",
            E_COMPILE_ERROR => "COMPILE ERROR",
            E_COMPILE_WARNING => "COMPILE WARNING",
            E_USER_ERROR => "USER ERROR",
            E_USER_WARNING => "USER WARNING",
            E_USER_NOTICE => "USER NOTICE",
            E_STRICT => "STRICT NOTICE",
            E_RECOVERABLE_ERROR => "RECOVERABLE ERROR",
            E_DEPRECATED => "DEPRECATED",
            E_USER_DEPRECATED => "USER DEPRECATED"
        );

        if (!empty($nr) || !empty($file)) {
            if ($text != '') {
                $str .= '<pre style="font-weight:bold;border:1px solid #ccc;padding:8px;color:#333;background-color:#ffffcd;margin-bottom:15px;">Error : ' . $text . '</pre>';
            }
            if ($output != '') {
                $str .= '<pre style="font-weight:bold;border:1px solid #ccc;padding:8px;color:#333;background-color:#ffffcd;margin-bottom:15px;">' . $output . '</pre>';
            }
            if ($nr !== '') {
                $table[] = array('ErrorType[num]', $errortype [$nr] . "[" . $nr . "]");
            }
            if ($file) {
                $table[] = array('File', $file);
            }
            if ($line) {
                $table[] = array('Line', $line);
            }

        }

        if ($source != '') {
            $table[] = array("Source", $source);
        }

        if (!empty($this->currentSnippet)) {
            $table[] = array('Current Snippet', $this->currentSnippet);
        }

        if (!empty($this->event->activePlugin)) {
            $table[] = array('Current Plugin', $this->event->activePlugin . '(' . $this->event->name . ')');
        }

        $str .= $MakeTable->create($table, array('Error information', ''));
        $str .= "<br />";

        $table = array();
        $table[] = array('REQUEST_URI', $request_uri);

        if ($this->manager->action) {
            include_once(MODX_MANAGER_PATH . 'includes/actionlist.inc.php');
            global $action_list;
            $actionName = (isset($action_list[$this->manager->action])) ? " - {$action_list[$this->manager->action]}" : '';

            $table[] = array('Manager action', $this->manager->action . $actionName);
        }

        if (preg_match('@^[0-9]+@', $this->documentIdentifier)) {
            $resource = $this->getDocumentObject('id', $this->documentIdentifier);
            $url = $this->makeUrl($this->documentIdentifier, '', '', 'full');
            $table[] = array('Resource', '[' . $this->documentIdentifier . '] <a href="' . $url . '" target="_blank">' . $resource['pagetitle'] . '</a>');
        }
        $table[] = array('Referer', $referer);
        $table[] = array('User Agent', $ua);
        $table[] = array('IP', $_SERVER['REMOTE_ADDR']);
        $table[] = array('Current time', date("Y-m-d H:i:s", $_SERVER['REQUEST_TIME'] + $this->config['server_offset_time']));
        $str .= $MakeTable->create($table, array('Basic info', ''));
        $str .= "<br />";

        $table = array();
        $table[] = array('MySQL', '[^qt^] ([^q^] Requests)');
        $table[] = array('PHP', '[^p^]');
        $table[] = array('Total', '[^t^]');
        $table[] = array('Memory', '[^m^]');
        $str .= $MakeTable->create($table, array('Benchmarks', ''));
        $str .= "<br />";

        $totalTime = ($this->getMicroTime() - $this->tstart);

        $mem = memory_get_peak_usage(true);
        $total_mem = $mem - $this->mstart;
        $total_mem = ($total_mem / 1024 / 1024) . ' mb';

        $queryTime = $this->queryTime;
        $phpTime = $totalTime - $queryTime;
        $queries = isset ($this->executedQueries) ? $this->executedQueries : 0;
        $queryTime = sprintf("%2.4f s", $queryTime);
        $totalTime = sprintf("%2.4f s", $totalTime);
        $phpTime = sprintf("%2.4f s", $phpTime);

        $str = str_replace('[^q^]', $queries, $str);
        $str = str_replace('[^qt^]', $queryTime, $str);
        $str = str_replace('[^p^]', $phpTime, $str);
        $str = str_replace('[^t^]', $totalTime, $str);
        $str = str_replace('[^m^]', $total_mem, $str);

        if (isset($php_errormsg) && !empty($php_errormsg)) {
            $str = "<b>{$php_errormsg}</b><br />\n{$str}";
        }
        $str .= $this->get_backtrace(debug_backtrace());
        // Log error
        if (!empty($this->currentSnippet)) {
            $source = 'Snippet - ' . $this->currentSnippet;
        } elseif (!empty($this->event->activePlugin)) {
            $source = 'Plugin - ' . $this->event->activePlugin;
        } elseif ($source !== '') {
            $source = 'Parser - ' . $source;
        } elseif ($query !== '') {
            $source = 'SQL Query';
        } else {
            $source = 'Parser';
        }
        if ($msg) {
            $source .= ' / ' . $msg;
        }
        if (isset($actionName) && !empty($actionName)) {
            $source .= $actionName;
        }
        switch ($nr) {
            case E_DEPRECATED :
            case E_USER_DEPRECATED :
            case E_STRICT :
            case E_NOTICE :
            case E_USER_NOTICE :
                $error_level = 2;
                break;
            default:
                $error_level = 3;
        }
        $this->logEvent(0, $error_level, $str, $source);

        if ($error_level === 2 && $this->error_reporting !== '99') {
            return true;
        }
        if ($this->error_reporting === '99' && !isset($_SESSION['mgrValidated'])) {
            return true;
        }

        // Set 500 response header
        if ($error_level !== 2) {
            header('HTTP/1.1 500 Internal Server Error');
        }

        // Display error
        ob_get_clean();
        if (isset($_SESSION['mgrValidated'])) {
            echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"><html><head><title>EVO Content Manager ' . $version . ' &raquo; ' . $release_date . '</title>
                 <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                 <link rel="stylesheet" type="text/css" href="' . $this->config['site_manager_url'] . 'media/style/' . $this->config['manager_theme'] . '/style.css" />
                 <style type="text/css">body { padding:10px; } td {font:inherit;}</style>
                 </head><body>
                 ' . $str . '</body></html>';

        } else {
            echo 'Error';
        }
        ob_end_flush();
        exit;
    }

    /**
     * @param $backtrace
     * @return string
     */
    public function get_backtrace($backtrace)
    {
        if (!class_exists('makeTable')) {
            include_once('extenders/maketable.class.php');
        }
        $MakeTable = new MakeTable();
        $MakeTable->setTableClass('grid');
        $MakeTable->setRowRegularClass('gridItem');
        $MakeTable->setRowAlternateClass('gridAltItem');
        $table = array();
        $backtrace = array_reverse($backtrace);
        foreach ($backtrace as $key => $val) {
            $key++;
            if (substr($val['function'], 0, 11) === 'messageQuit') {
                break;
            } elseif (substr($val['function'], 0, 8) === 'phpError') {
                break;
            }
            $path = str_replace('\\', '/', $val['file']);
            if (strpos($path, MODX_BASE_PATH) === 0) {
                $path = substr($path, strlen(MODX_BASE_PATH));
            }
            switch ($val['type']) {
                case '->':
                case '::':
                    $functionName = $val['function'] = $val['class'] . $val['type'] . $val['function'];
                    break;
                default:
                    $functionName = $val['function'];
            }
            $tmp = 1;
            $_ = (!empty($val['args'])) ? count($val['args']) : 0;
            $args = array_pad(array(), $_, '$var');
            $args = implode(", ", $args);
            $modx = &$this;
            $args = preg_replace_callback('/\$var/', function () use ($modx, &$tmp, $val) {
                $arg = $val['args'][$tmp - 1];
                switch (true) {
                    case is_null($arg): {
                        $out = 'NULL';
                        break;
                    }
                    case is_numeric($arg): {
                        $out = $arg;
                        break;
                    }
                    case is_scalar($arg): {
                        $out = strlen($arg) > 20 ? 'string $var' . $tmp : ("'" . $this->htmlspecialchars(str_replace("'", "\\'", $arg)) . "'");
                        break;
                    }
                    case is_bool($arg): {
                        $out = $arg ? 'TRUE' : 'FALSE';
                        break;
                    }
                    case is_array($arg): {
                        $out = 'array $var' . $tmp;
                        break;
                    }
                    case is_object($arg): {
                        $out = get_class($arg) . ' $var' . $tmp;
                        break;
                    }
                    default: {
                        $out = '$var' . $tmp;
                    }
                }
                $tmp++;
                return $out;
            }, $args);
            $line = array(
                "<strong>" . $functionName . "</strong>(" . $args . ")",
                $path . " on line " . $val['line']
            );
            $table[] = array(implode("<br />", $line));
        }
        return $MakeTable->create($table, array('Backtrace'));
    }

    /**
     * @return string
     */
    public function getRegisteredClientScripts()
    {
        return implode("\n", $this->jscripts);
    }

    /**
     * @return string
     */
    public function getRegisteredClientStartupScripts()
    {
        return implode("\n", $this->sjscripts);
    }

    /**
     * Format alias to be URL-safe. Strip invalid characters.
     *
     * @param string $alias Alias to be formatted
     * @return string Safe alias
     */
    public function stripAlias($alias)
    {
        // let add-ons overwrite the default behavior
        $results = $this->invokeEvent('OnStripAlias', array('alias' => $alias));
        if (!empty($results)) {
            // if multiple plugins are registered, only the last one is used
            return end($results);
        } else {
            // default behavior: strip invalid characters and replace spaces with dashes.
            $alias = strip_tags($alias); // strip HTML
            $alias = preg_replace('/[^\.A-Za-z0-9 _-]/', '', $alias); // strip non-alphanumeric characters
            $alias = preg_replace('/\s+/', '-', $alias); // convert white-space to dash
            $alias = preg_replace('/-+/', '-', $alias);  // convert multiple dashes to one
            $alias = trim($alias, '-'); // trim excess
            return $alias;
        }
    }

    /**
     * @param $size
     * @return string
     */
    public function nicesize($size)
    {
        $sizes = array('Tb' => 1099511627776, 'Gb' => 1073741824, 'Mb' => 1048576, 'Kb' => 1024, 'b' => 1);
        $precisions = count($sizes) - 1;
        foreach ($sizes as $unit => $bytes) {
            if ($size >= $bytes) {
                return number_format($size / $bytes, $precisions) . ' ' . $unit;
            }
            $precisions--;
        }
        return '0 b';
    }

    /**
     * @param $parentid
     * @param $alias
     * @return bool
     */
    public function getHiddenIdFromAlias($parentid, $alias)
    {
        $out = false;
        if ($alias !== '') {
            $table = $this->getFullTableName('site_content');
            $query = $this->db->query("SELECT 
                `sc`.`id` AS `hidden_id`,
                `children`.`id` AS `child_id`,
                children.alias AS `child_alias`,
                COUNT(`grandsons`.`id`) AS `grandsons_count`
              FROM " . $table ." AS `sc`
              JOIN " . $table . " AS `children` ON `children`.`parent` = `sc`.`id`
              LEFT JOIN " . $table . " AS `grandsons` ON `grandsons`.`parent` = `children`.`id`
              WHERE `sc`.`parent` = '" . (int)$parentid . "' AND `sc`.`alias_visible` = '0'
              GROUP BY `children`.`id`");

            while ($child = $this->db->getRow($query)) {
                if ($child['child_alias'] == $alias || $child['child_id'] == $alias) {
                    $out = $child['child_id'];
                    break;
                } else if ($child['grandsons_count'] > 0 && ($id = $this->getHiddenIdFromAlias($child['hidden_id'], $alias))) {
                    $out = $id;
                    break;
                }
            }
        }
        return $out;
    }

    /**
     * @param $alias
     * @return bool|int
     */
    public function getIdFromAlias($alias)
    {
        if (isset($this->documentListing[$alias])) {
            return $this->documentListing[$alias];
        }

        $tbl_site_content = $this->getFullTableName('site_content');
        if ($this->config['use_alias_path'] == 1) {
            if ($alias == '.') {
                return 0;
            }

            if (strpos($alias, '/') !== false) {
                $_a = explode('/', $alias);
            } else {
                $_a[] = $alias;
            }
            $id = 0;

            foreach ($_a as $alias) {
                if ($id === false) {
                    break;
                }
                $alias = $this->db->escape($alias);
                $rs = $this->db->select('id', $tbl_site_content, "deleted=0 and parent='{$id}' and alias='{$alias}'");
                if ($this->db->getRecordCount($rs) == 0) {
                    $rs = $this->db->select('id', $tbl_site_content, "deleted=0 and parent='{$id}' and id='{$alias}'");
                }
                $next = $this->db->getValue($rs);
                $id = !$next ? $this->getHiddenIdFromAlias($id, $alias) : $next;
            }
        } else {
            $rs = $this->db->select('id', $tbl_site_content, "deleted=0 and alias='{$alias}'", 'parent, menuindex');
            $id = $this->db->getValue($rs);
            if (!$id) {
                $id = false;
            }
        }
        return $id;
    }

    /**
     * @param string $str
     * @return bool|mixed|string
     */
    public function atBindInclude($str = '')
    {
        if (strpos($str, '@INCLUDE') !== 0) {
            return $str;
        }
        if (strpos($str, "\n") !== false) {
            $str = substr($str, 0, strpos("\n", $str));
        }

        $str = substr($str, 9);
        $str = trim($str);
        $str = str_replace('\\', '/', $str);
        $str = ltrim($str, '/');

        $tpl_dir = 'assets/templates/';

        if (strpos($str, MODX_MANAGER_PATH) === 0) {
            return false;
        } elseif (is_file(MODX_BASE_PATH . $str)) {
            $file_path = MODX_BASE_PATH . $str;
        } elseif (is_file(MODX_BASE_PATH . "{$tpl_dir}{$str}")) {
            $file_path = MODX_BASE_PATH . $tpl_dir . $str;
        } else {
            return false;
        }

        if (!$file_path || !is_file($file_path)) {
            return false;
        }

        ob_start();
        $modx = &$this;
        $result = include($file_path);
        if ($result === 1) {
            $result = '';
        }
        $content = ob_get_clean();
        if (!$content && $result) {
            $content = $result;
        }
        return $content;
    }

    // php compat

    /**
     * @param $str
     * @param int $flags
     * @param string $encode
     * @return mixed
     */
    public function htmlspecialchars($str, $flags = ENT_COMPAT, $encode = '')
    {
        $this->loadExtension('PHPCOMPAT');
        return $this->phpcompat->htmlspecialchars($str, $flags, $encode);
    }

    /**
     * @param $string
     * @param bool $returnData
     * @return bool|mixed
     */
    public function isJson($string, $returnData = false)
    {
        $data = json_decode($string, true);
        return (json_last_error() == JSON_ERROR_NONE) ? ($returnData ? $data : true) : false;
    }

    /**
     * @param $key
     * @return array
     */
    public function splitKeyAndFilter($key)
    {
        if (isset($this->config['enable_filter']) && $this->config['enable_filter'] == 1 && strpos($key, ':') !== false && stripos($key, '@FILE') !== 0) {
            list($key, $modifiers) = explode(':', $key, 2);
        } else {
            $modifiers = false;
        }

        $key = trim($key);
        if ($modifiers !== false) {
            $modifiers = trim($modifiers);
        }

        return array($key, $modifiers);
    }

    /**
     * @param string $value
     * @param bool $modifiers
     * @param string $key
     * @return string
     */
    public function applyFilter($value = '', $modifiers = false, $key = '')
    {
        if ($modifiers === false || $modifiers == 'raw') {
            return $value;
        }
        if ($modifiers !== false) {
            $modifiers = trim($modifiers);
        }

        $this->loadExtension('MODIFIERS');
        return $this->filter->phxFilter($key, $value, $modifiers);
    }

    // End of class.


    /**
     * Get Clean Query String
     *
     * Fixes the issue where passing an array into the q get variable causes errors
     *
     */
    private static function _getCleanQueryString()
    {
        $q = MODX_CLI ? null : (isset($_GET['q']) ? $_GET['q'] : '');

        //Return null if the query doesn't exist
        if (empty($q)) {
            return null;
        }

        //If we have a string, return it
        if (is_string($q)) {
            return $q;
        }

        //If we have an array, return the first element
        if (is_array($q)) {
            return $q[0];
        }
    }

    /**
     * @param string $title
     * @param string $msg
     * @param int $type
     */
    public function addLog($title = 'no title', $msg = '', $type = 1)
    {
        if ($title === '') {
            $title = 'no title';
        }
        if (is_array($msg)) {
            $msg = '<pre>' . print_r($msg, true) . '</pre>';
        } elseif ($msg === '') {
            $msg = $_SERVER['REQUEST_URI'];
        }
        $this->logEvent(0, $type, $msg, $title);
    }

    /**
     * @return DBAPI
     */
    public function getDatabase()
    {
        if ($this->db === null) {
            $this->loadExtension('DBAPI');
        }
        return $this->db;
    }

    /**
     * @return PHPCOMPAT
     */
    public function getPhpCompat()
    {
        if ($this->phpcompat === null) {
            $this->loadExtension('PHPCOMPAT');
        }
        return $this->phpcompat;
    }

    /**
     * @param bool $reload
     * @return PasswordHash
     */
    public function getPasswordHash($reload = false)
    {
        if ($this->phpass === null || $reload === true) {
            $this->loadExtension('phpass');
        }
        return $this->phpass;
    }

    /**
     * @param bool $reload
     * @return MakeTable
     */
    public function getMakeTable($reload = false)
    {
        if ($this->table === null || $reload === true) {
            $this->loadExtension('makeTable');
        }
        return $this->table;
    }

    /**
     * @param bool $reload
     * @return EXPORT_SITE
     */
    public function getExportSite($reload = false)
    {
        if ($this->export === null || $reload === true) {
            $this->loadExtension('makeTable');
        }
        return $this->export;
    }

    /**
     * @return OldFunctions
     */
    public function getDeprecatedCore()
    {
        include_once(MODX_MANAGER_PATH . 'includes/extenders/deprecated.functions.inc.php');
        return $this->old;
    }

    /**
     * @param bool $reload
     * @return ManagerAPI
     */
    public function getManagerApi($reload = false)
    {
        if ($this->manager === null || $reload === true) {
            $this->loadExtension('ManagerAPI');
        }
        return $this->manager;
    }

    /**
     * @param bool $reload
     * @return MODIFIERS
     */
    public function getModifiers($reload = false)
    {
        if ($this->filter === null || $reload === true) {
            $this->loadExtension('MODIFIERS');
        }
        return $this->filter;
    }

    /**
     * @param bool $reload
     * @return MODxMailer
     */
    public function getMail($reload = false)
    {
        if ($this->mail === null || $reload === true) {
            $this->loadExtension('MODxMailer');
        }
        return $this->mail;
    }
}

/**
 * System Event Class
 */
class SystemEvent
{
    public $name = '';
    public $_propagate = true;
    /**
     * @deprecated use setOutput(), getOutput()
     * @var string
     */
    public $_output;
    public $activated = false;
    public $activePlugin = '';
    public $params = array();

    /**
     * Previous event object
     * @var SystemEvent
     */
    private $previousEvent;

    /**
     * @param string $name Name of the event
     */
    public function __construct($name = "")
    {
        $this->_resetEventObject();
        $this->name = $name;
    }

    /**
     * Display a message to the user
     *
     * @global array $SystemAlertMsgQueque
     * @param string $msg The message
     */
    public function alert($msg)
    {
        global $SystemAlertMsgQueque;
        if ($msg == "") {
            return;
        }
        if (is_array($SystemAlertMsgQueque)) {
            $title = '';
            if ($this->name && $this->activePlugin) {
                $title = "<div><b>" . $this->activePlugin . "</b> - <span style='color:maroon;'>" . $this->name . "</span></div>";
            }
            $SystemAlertMsgQueque[] = "$title<div style='margin-left:10px;margin-top:3px;'>$msg</div>";
        }
    }

    /**
     * Output
     *
     * @param string $msg
     * @deprecated see addOutput
     */
    public function output($msg)
    {
        $this->addOutput($msg);
    }

    /**
     * @param mixed $data
     */
    public function addOutput($data)
    {
        if(\is_scalar($data)) {
            $this->_output .= $data;
        }
    }

    /**
     * @param mixed $data
     */
    public function setOutput($data)
    {
        $this->_output = $data;
    }

    /**
     * @return mixed
     */
    public function getOutput()
    {
        return $this->_output;
    }

    /**
     * Stop event propogation
     */
    public function stopPropagation()
    {
        $this->_propagate = false;
    }

    public function _resetEventObject()
    {
        unset ($this->returnedValues);
        $this->name = "";
        $this->setOutput(null);
        $this->_propagate = true;
        $this->activated = false;
    }

    /**
     * @param SystemEvent $event
     */
    public function setPreviousEvent($event)
    {
        $this->previousEvent = $event;
    }

    public function getPreviousEvent()
    {
        return $this->previousEvent;
    }
}
