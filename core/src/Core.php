<?php namespace EvolutionCMS;

use AgelxNash\Modx\Evo\Database\Exceptions\InvalidFieldException;
use AgelxNash\Modx\Evo\Database\Exceptions\TableNotDefinedException;
use AgelxNash\Modx\Evo\Database\Exceptions\UnknownFetchTypeException;
use EvolutionCMS\Models\ActiveUser;
use EvolutionCMS\Models\ActiveUserLock;
use EvolutionCMS\Models\ActiveUserSession;
use EvolutionCMS\Models\DocumentGroup;
use EvolutionCMS\Models\EventLog;
use EvolutionCMS\Models\MembergroupName;
use EvolutionCMS\Models\SiteContent;
use EvolutionCMS\Models\SitePlugin;
use EvolutionCMS\Models\SiteTemplate;
use EvolutionCMS\Models\SiteTmplvar;
use EvolutionCMS\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Pipeline;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PHPMailer\PHPMailer\Exception;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use TemplateProcessor;
use UrlProcessor;
use HelperProcessor;

/**
 * @see: https://github.com/laravel/framework/blob/5.6/src/Illuminate/Foundation/Bootstrap/LoadConfiguration.php
 * @property Mail $mail
 *      $this->loadExtension('MODxMailer');
 * @property Database $db
 *      $this->loadExtension('DBAPI')
 * @property Legacy\PhpCompat $phpcompat
 *      $this->loadExtension('PHPCOMPAT');
 * @property Legacy\Modifiers $filter
 *      $this->loadExtension('MODIFIERS');
 * @property Support\MakeTable $table
 *      $this->loadExtension('makeTable');
 * @property Legacy\ManagerApi $manager
 *      $this->loadExtension('ManagerAPI');
 * @property Legacy\PasswordHash $phpass
 *      $this->loadExtension('phpass');
 * @property Parser $tpl
 */
class Core extends AbstractLaravel implements Interfaces\CoreInterface
{
    use Traits\Settings {
        getSettings as loadConfig;
    }
    use Traits\Path, Traits\Helpers;

    /**
     * event object
     * @var Event
     */

    public $event;
    /**
     * event object
     * @var Event
     * @deprecated
     */
    public $Event;

    /**
     * @var array
     */
    public $pluginEvent = array();

    /**
     * @var array
     */
    public $configGlobal = []; // contains backup of settings overwritten by user-settings
    public $rs;
    public $result;
    public $sql;
    public $debug = false;
    public $documentIdentifier = 0;
    public $documentMethod;
    public $documentGenerated;
    public $documentContent;
    public $documentOutput;
    public $tstart = 0;
    public $mstart = 0;
    public $minParserPasses = 2;
    public $maxParserPasses = 10;
    public $maxSourcePasses = 10;
    public $documentObject = [];
    public $templateObject;
    public $snippetObjects;
    public $stopOnNotice = false;
    public $executedQueries = 0;
    public $queryTime = 0;
    public $currentSnippet;
    public $documentName;
    public $aliases;
    public $visitor;
    public $entrypage;
    /**
     * @deprecated use UrlProcessor::getFacadeRoot()->documentListing
     */
    public $documentListing = [];
    /**
     * feed the parser the execution start time
     * @var bool
     */
    public $dumpSnippets = false;
    public $snippetsCode;
    public $snippetsTime = [];
    public $chunkCache = [];
    public $snippetCache = [];
    public $modulesFromFile = [];
    public $contentTypes;
    public $dumpSQL = false;
    public $queryCode;
    /**
     * @deprecated use UrlProcessor::getFacadeRoot()->virtualDir
     */
    public $virtualDir;
    public $placeholders = [];
    public $sjscripts = [];
    public $jscripts = [];
    public $loadedjscripts = [];
    public $documentMap = [];
    public $forwards = 3;
    public $error_reporting = 1;
    public $dumpPlugins = false;
    public $pluginsCode;
    public $pluginsTime = [];
    public $pluginCache = [];
    /**
     * @deprecated use UrlProcessor::getFacadeRoot()->aliasListing
     */
    public $aliasListing = [];
    public $lockedElements = null;
    public $tmpCache = [];
    private $version = [];
    public $extensions = [];
    public $cacheKey = '';
    public $recentUpdate = 0;
    public $useConditional = false;
    protected $systemCacheKey = '';
    public $snipLapCount = 0;
    public $messageQuitCount;
    public $time;
    public $sid;
    private $q;
    public $decoded_request_uri;
    /**
     * @var Legacy\DeprecatedCore
     * @deprecated use ->getDeprecatedCore()
     */
    public $old;

    /**
     * @deprecated
     * @var array|false
     */
    public $_TVnames = false;

    /** @var UrlProcessor|null */
    public $urlProcessor;

    /**
     * @deprecated Needs for the newChunkie class by Thomas Jakobi
     * @var array
     */
    public $chunkieCache = [];

    /**
     * @deprecated Needs for the modxRTEbridge class
     * @var array
     */
    public $modxRTEbridge = [];

    /**
     * @var array
     */
    private $dataForView = [];

    /**
     * @var string
     */
    private $context = '';

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->tstart = get_by_key($_SERVER, 'REQUEST_TIME_FLOAT', 0);
        $this->instance('path', $this->path());
        $this->instance('path.base', $this->basePath());
        $this->instance('path.lang', $this->langPath());
        $this->instance('path.config', $this->configPath());
        $this->instance('path.public', $this->publicPath());
        $this->instance('path.storage', $this->storagePath());
        $this->instance('path.database', $this->databasePath());
        $this->instance('path.resources', $this->resourcePath());
        $this->instance('path.bootstrap', $this->bootstrapPath());

        /**
         * Laravel: $this->config instance of the Illuminate\Config\Repository
         * EvolutionCMS: $this->config is array
         * Before loading the provider we merge settings!!!
         * @TODO: This is dirty code. Any ideas?
         */
        $this->saveConfig = $this->config;
        $this->booting(function () {
            $this->config = $this->configCompatibility();
        });
        parent::__construct();

        $this->initialize();

    }

    public function initialize()
    {

        if ($this->isLoggedIn()) {
            ini_set('display_errors', 1);
        }
        // events
        $this->event = new Event();
        $this->Event = &$this->event; //alias for backward compatibility
        $this->time = $_SERVER['REQUEST_TIME']; // for having global timestamp

        $this->getService('ExceptionHandler');
        $this->checkAuth();
        $this->getSettings();
        $this->q = UrlProcessor::cleanQueryString(is_cli() ? '' : get_by_key($_GET, 'q', ''));
    }

    final public function __clone()
    {
    }

    /**
     * @return self
     * @throws \Exception
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * @see: https://stackoverflow.com/a/13186679/2323306
     * @param string $name
     * @return mixed|null
     */
    public function __get($name)
    {
        if ($this->hasEvolutionProperty($name)) {
            if ($this->getConfig('error_reporting') > 99) {
                trigger_error(
                    'Property EvolutionCMS\Core::$' . $name . ' is deprecated and should no longer be used. ',
                    E_USER_DEPRECATED
                );
            }
            return $this->getEvolutionProperty($name);
        }

        return parent::__get($name);
    }

    /**
     * @param $method_name
     * @param $arguments
     * @return mixed
     */
    function __call($method_name, $arguments)
    {
        $old = $this->getDeprecatedCore();
        if (method_exists($old, $method_name)) {
            if ($this->getConfig('error_reporting') > 99) {
                trigger_error(
                    'The EvolutionCMS\Core::' . $method_name . '() method is deprecated and should no longer be used. ',
                    E_USER_DEPRECATED
                );
            }

            return call_user_func_array([$old, $method_name], $arguments);
        }

        trigger_error(
            'The EvolutionCMS\Core::' . $method_name . '() method is undefined',
            E_USER_ERROR
        );
    }

    /**
     * @param string $connector
     * @return bool
     */
    public function checkSQLconnect($connector = 'db')
    {
        $flag = false;
        if (is_scalar($connector) && !empty($connector) && isset($this->{$connector}) && $this->{$connector} instanceof Interfaces\DatabaseInterface) {
            $flag = (bool)$this->{$connector}->conn;
        }

        return $flag;
    }


    /**
     *
     */
    public function checkAuth($context = '')
    {
        if (empty($context)) {
            $context = $this->getContext();
        }
        if (evo()->getLoginUserID($context) !== false) {
            $result = $this->checkAccess(evo()->getLoginUserID($context));
            if ($result === false) {
                \UserManager::logout();
                if (IN_MANAGER_MODE) {
                    evo()->sendRedirect('/'.MGR_DIR);
                }
            }
        }

    }

    /**
     * @param $userId
     * @return bool
     */
    public function checkAccess($userId): bool
    {
        if (empty($context)) {
            $context = $this->getContext();
        }
        $user = User::query()->find(evo()->getLoginUserID($context));
        if (is_null($user)) {
            return false;
        }
        if ($user->attributes->blocked != 0) {
            return false;
        }
        if ($user->attributes->blockeduntil > time()) {
            return false;
        }
        if ($user->attributes->blockedafter < time() && $user->attributes->blockedafter > 0) {
            return false;
        }
        return true;
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
     * @deprecated use getService
     */
    public function loadExtension($extname, $reload = true)
    {
        if ($this->isEvolutionProperty($extname)) {
            return $this->getEvolutionProperty($extname);
        }

        $out = false;
        $found = false;
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
        [$usec, $sec] = explode(' ', microtime());

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
                $this->getService('ExceptionHandler')->messageQuit(
                    'Redirection attempt failed - please ensure the document you\'re trying to redirect to exists.' .
                    '<p>Redirection URL: <i>' . $url . '</i></p>'
                );
            } else {
                $currentNumberOfRedirects += 1;
                if (Str::contains($url, '?')) {
                    $url .= "&err=$currentNumberOfRedirects";
                } else {
                    $url .= "?err=$currentNumberOfRedirects";
                }
            }
        }
        if ($type === 'REDIRECT_REFRESH') {
            $header = 'Refresh: 0;URL=' . $url;
        } elseif ($type === 'REDIRECT_META') {
            $header = '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=' . $url . '" />';
            echo $header;
            exit;
        } elseif ($type === 'REDIRECT_HEADER' || empty ($type)) {
            // check if url has /$base_url
            if (substr($url, 0, strlen(MODX_BASE_URL)) == MODX_BASE_URL) {
                // append $site_url to make it work with Location:
                $url = MODX_SITE_URL . substr($url, strlen(MODX_BASE_URL));
            }
            if (!Str::contains($url, "\n")) {
                $header = 'Location: ' . $url;
            } else {
                $this->getService('ExceptionHandler')->messageQuit('No newline allowed in redirect url.');
            }
        }
        if ($responseCode && (Str::contains($responseCode, '30'))) {
            header($responseCode);
        }

        if (!empty($header)) {
            header($header);
        }

        exit(0);
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
        }

        $this->getService('ExceptionHandler')->messageQuit("Internal Server Error id={$id}");
        header('HTTP/1.0 500 Internal Server Error');
        die('<h1>ERROR: Too many forward attempts!</h1><p>The request could not be completed due to too many unsuccessful forward attempts.</p>');
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
        $url = UrlProcessor::getNotFoundPageId();

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

        $this->sendForward(UrlProcessor::getUnAuthorizedPageId(), 'HTTP/1.1 401 Unauthorized');
        exit();
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
            return $_REQUEST['q'];
        }

        $id_ = filter_input(INPUT_GET, 'id');
        if ($id_) {
            if (preg_match('@^[1-9]\d*$@', $id_)) {
                return $id_;
            }

            $this->sendErrorPage();
        } elseif (Str::contains($_SERVER['REQUEST_URI'], 'index.php/')) {
            $this->sendErrorPage();
        } else {
            return $this->getConfig('site_start');
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
        $_ = 'mgrValidated';

        return is_cli() || (isset($_SESSION[$_]) && !empty($_SESSION[$_]));
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
        return ($this->isLoggedIn() == true) && isset ($_REQUEST['z']) && $_REQUEST['z'] === 'manprev';
    }

    /**
     * check if site is offline
     *
     * @return boolean
     */
    public function checkSiteStatus()
    {
        if ($this->getConfig('site_status')) {
            return true;
        }

        if ($this->isLoggedin()) {
            return true;
        }  // site online
        // site offline but launched via the manager

        return false; // site is offline
    }

    /**
     * @deprecated use UrlProcessor::cleanDocumentIdentifier()
     */
    public function cleanDocumentIdentifier($qOrig): string
    {
        return UrlProcessor::cleanDocumentIdentifier($qOrig, $this->documentMethod);
    }

    /**
     * @param $id
     * @return array|mixed|null|string
     */
    public function makePageCacheKey($id)
    {
        $hash = $id;
        $tmp = null;
        $params = array();
        if (!empty($this->systemCacheKey)) {
            $hash = $this->systemCacheKey;
        } else {
            if (!empty($_GET)) {
                // Sort GET parameters so that the order of parameters on the HTTP request don't affect the generated cache ID.
                $params = $_GET;
                ksort($params);
                $hash .= '_' . md5(http_build_query($params));
            }
        }
        $evtOut = $this->invokeEvent("OnMakePageCacheKey", array("hash" => $hash, "id" => $id, 'params' => $params));
        if (is_array($evtOut) && count($evtOut) > 0) {
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
        $key = ($this->getConfig('cache_type') == 2) ? $this->makePageCacheKey($id) : $id;
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
            if ((!isset($_SESSION['mgrRole']) || $_SESSION['mgrRole'] != 1)) {
                if ($docObj['privatemgr'] && isset ($docObj['__MODxDocGroups__'])) {
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
                        if ($this->getConfig('unauthorized_page')) {
                            // check if file is not public
                            $documentGroups = DocumentGroup::where('document', $id);
                            $total = $documentGroups->count();
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

        if ($this->documentGenerated == 1
            && $this->documentObject['cacheable'] == 1
            && $this->documentObject['type'] === 'document'
            && $this->documentObject['published'] == 1
        ) {
            if ($this->sjscripts) {
                $this->documentObject['__MODxSJScripts__'] = $this->sjscripts;
            }
            if ($this->jscripts) {
                $this->documentObject['__MODxJScripts__'] = $this->jscripts;
            }
        }

        // check for non-cached snippet output
        if (Str::contains($this->documentOutput, '[!')) {
            $this->recentUpdate = $_SERVER['REQUEST_TIME'] + $this->getConfig('server_offset_time', 0);

            $this->documentOutput = str_replace('[!', '[[', $this->documentOutput);
            $this->documentOutput = str_replace('!]', ']]', $this->documentOutput);
            $this->minParserPasses = 2;
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
            $type = !empty ($this->contentTypes[$this->documentIdentifier]) ? $this->contentTypes[$this->documentIdentifier] : "text/html";
            header('Content-Type: ' . $type . '; charset=' . $this->getConfig('modx_charset'));
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
        if (Str::contains($out, '[^')) {
            $out = str_replace(
                array('[^q^]', '[^qt^]', '[^p^]', '[^t^]', '[^s^]', '[^m^]')
                , array($stats['queries'], $stats['queryTime'], $stats['phpTime'], $stats['totalTime'], $stats['source'], $stats['phpMemory'])
                , $out
            );
        }
        //$this->documentOutput= $out;

        // invoke OnWebPagePrerender event
        if (!$noEvent) {
            $evtOut = $this->invokeEvent(
                'OnWebPagePrerender'
                , array('documentOutput' => &$this->documentOutput)
            );
        }

        $this->documentOutput = removeSanitizeSeed($this->documentOutput);

        if (Str::contains($this->documentOutput, '\{')) {
            $this->documentOutput = $this->RecoveryEscapedTags($this->documentOutput);
        } elseif (Str::contains($this->documentOutput, '\[')) {
            $this->documentOutput = $this->RecoveryEscapedTags($this->documentOutput);
        }

        echo $this->documentOutput;

        if ($this->dumpSQL) {
            echo $this->queryCode;
        }
        if ($this->dumpSnippets) {
            $sc = '';
            $tt = 0;
            foreach ($this->snippetsTime as $s => $v) {
                $t = $v['time'];
                $sname = $v['sname'];
                $sc .= sprintf(
                    '%s. %s (%2.2f ms)<br>'
                    , $s
                    , $sname
                    , $t
                ); // currentSnippet
                $tt += $t;
            }
            echo sprintf(
                '<fieldset><legend><b>Snippets</b> (%s / %2.2f ms)</legend>%s</fieldset><br />'
                , count($this->snippetsTime)
                , $tt
                , $sc
            );
            echo $this->snippetsCode;
        }
        if ($this->dumpPlugins) {
            $ps = '';
            $tt = 0;
            foreach ($this->pluginsTime as $s => $t) {
                $ps .= sprintf(
                    '%s (%2.2f ms)<br>'
                    , $s
                    , $t * 1000
                );
                $tt += $t;
            }
            echo sprintf(
                '<fieldset><legend><b>Plugins</b> (%s / %2.2f ms)</legend>%s</fieldset><br />'
                , count($this->pluginsTime)
                , $tt * 1000
                , $ps
            );
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
        [$sTags, $rTags] = $this->getTagsForEscape();

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

        $stats['queryTime'] = sprintf('%2.4f s', $stats['queryTime']);
        $stats['totalTime'] = sprintf('%2.4f s', $stats['totalTime']);
        $stats['phpTime'] = sprintf('%2.4f s', $stats['phpTime']);
        $stats['source'] = $this->documentGenerated == 1 ? 'database' : 'cache';
        $stats['queries'] = isset ($this->executedQueries) ? $this->executedQueries : 0;
        $stats['phpMemory'] = (memory_get_peak_usage(true) / 1024 / 1024) . ' mb';

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

        if ($HTTP_IF_MODIFIED_SINCE == $last_modified || Str::contains($HTTP_IF_NONE_MATCH, $etag)) {
            header('HTTP/1.1 304 Not Modified');
            header('Content-Length: 0');
            exit;
        }

        header('Last-Modified: ' . $last_modified);
        header(sprintf("ETag: '%s'", $etag));
    }

    /**
     * Checks the publish state of page
     */
    public function updatePubStatus()
    {
        $cacheRefreshTime = 0;
        $recent_update = 0;
        if (file_exists($this->getSitePublishingFilePath())) {
            @include($this->getSitePublishingFilePath());
        }
        $this->recentUpdate = $recent_update;

        $timeNow = $_SERVER['REQUEST_TIME'] + $this->getConfig('server_offset_time');
        if ($timeNow < $cacheRefreshTime || $cacheRefreshTime == 0) {
            return;
        }

        // now, check for documents that need publishing
        $field = array('published' => 1, 'publishedon' => $timeNow);
        $where = "pub_date <= {$timeNow} AND pub_date!=0 AND published=0";
        $result_pub = \EvolutionCMS\Models\SiteContent::select('id')->whereRaw($where)->get();
        \EvolutionCMS\Models\SiteContent::whereRaw($where)->update($field);

        if ($result_pub->count() >= 1) { //Event unPublished doc
            foreach ($result_pub as $row_pub) {
                $this->invokeEvent("OnDocUnPublished", array(
                    "docid" => $row_pub->id
                ));
            }
        }

        // now, check for documents that need un-publishing
        $field = array('published' => 0, 'publishedon' => 0);
        $where = "unpub_date <= {$timeNow} AND unpub_date!=0 AND published=1";
//
        $result_unpub = \EvolutionCMS\Models\SiteContent::select('id')->whereRaw($where)->get();

        \EvolutionCMS\Models\SiteContent::whereRaw($where)->update($field);

        if ($result_unpub->count() >= 1) { //Event unPublished doc
            foreach ($result_unpub as $row_unpub) {
                $this->invokeEvent("OnDocUnPublished", array(
                    "docid" => $row_unpub->id
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
        $cacheable = ($this->getConfig('enable_cache') && $this->documentObject['cacheable']) ? 1 : 0;
        if ($cacheable && $this->documentGenerated && $this->documentObject['type'] == 'document' && $this->documentObject['published']) {
            // invoke OnBeforeSaveWebPageCache event
            $this->invokeEvent("OnBeforeSaveWebPageCache");

            if (!empty($this->cacheKey) && is_scalar($this->cacheKey)) {
                // get and store document groups inside document object. Document groups will be used to check security on cache pages
                $docGroups = DocumentGroup::where('document', $this->documentIdentifier)->pluck('document_group')->toArray();
                // Attach Document Groups and Scripts
                if (is_array($docGroups)) {
                    $this->documentObject['__MODxDocGroups__'] = implode(",", $docGroups);
                }

                $docObjSerial = serialize($this->documentObject);
                $cacheContent = $docObjSerial . "<!--__MODxCacheSpliter__-->" . $this->documentContent;
                $page_cache_path = $this->getHashFile($this->cacheKey);
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
        if (!Str::contains($content, $left)) {
            return array();
        }
        $spacer = md5('<<<EVO>>>');
        if ($left === '{{' && Str::contains($content, ';}}')) {
            $content = str_replace(';}}', sprintf(';}%s}', $spacer), $content);
        }
        if ($left === '{{' && Str::contains($content, '{{}}')) {
            $content = str_replace('{{}}', sprintf('{%$1s{}%$1s}', $spacer), $content);
        }
        if ($left === '[[' && Str::contains($content, ']]]]')) {
            $content = str_replace(']]]]', sprintf(']]%s]]', $spacer), $content);
        }
        if ($left === '[[' && Str::contains($content, ']]]')) {
            $content = str_replace(']]]', sprintf(']%s]]', $spacer), $content);
        }

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
            if (!Str::contains($lv, $right)) {
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
                    if ($this->config['enable_filter'] == 1 or class_exists('PHxParser')) {
                        if (Str::contains($fetch, $left)) {
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
        foreach ($tags as $i => $tag) {
            if (Str::contains($tag, $spacer)) {
                $tags[$i] = str_replace($spacer, '', $tag);
            }
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
        if ($this->getConfig('enable_at_syntax')) {
            if (stripos($content, '<@LITERAL>') !== false) {
                $content = $this->escapeLiteralTagsContent($content);
            }
        }
        if (!Str::contains($content, '[*')) {
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
            if (Str::contains($key, '[+')) {
                continue;
            } // Allow chunk {{chunk?&param=`xxx`}} with [*tv_name_[+param+]*] as content
            if (strpos($key, '#') === 0) {
                $key = substr($key, 1);
            } // remove # for QuickEdit format

            [$key, $modifiers] = $this->splitKeyAndFilter($key);
            if (Str::contains($key, '@')) {
                [$key, $context] = explode('@', $key, 2);
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
                $value = getTVDisplayFormat($value[0], $value[1], $value[2], $value[3], $value[4]);
            }

            $s = &$matches[0][$i];
            if ($modifiers !== false) {
                $value = $this->applyFilter($value, $modifiers, $key);
            }

            if (Str::contains($content, $s)) {
                $content = str_replace($s, $value, $content);
            } elseif ($this->debug) {
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
        [$key, $str] = explode('@', $key, 2);

        if (Str::contains($str, '(')) {
            [$context, $option] = explode('(', $str, 2);
        } else {
            [$context, $option] = array($str, false);
        }

        if ($option) {
            $option = trim($option, ')(\'"`');
        }

        switch (strtolower($context)) {
            case 'site_start':
                $docid = $this->getConfig('site_start');
                break;
            case 'parent':
            case 'p':
                $docid = $parent;
                if ($docid == 0) {
                    $docid = $this->getConfig('site_start');
                }
                break;
            case 'ultimateparent':
            case 'uparent':
            case 'up':
            case 'u':
                if (Str::contains($str, '(')) {
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
                $docid = UrlProcessor::getIdFromAlias($str);
                break;
            case 'prev':
                if (!$option) {
                    $option = 'menuindex,ASC';
                } elseif (!Str::contains($option, ',')) {
                    $option .= ',ASC';
                }
                [$by, $dir] = explode(',', $option, 2);
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
                    }
                    $docid = $prev['id'];
                } else {
                    $docid = '';
                }
                break;
            case 'next':
                if (!$option) {
                    $option = 'menuindex,ASC';
                } elseif (!Str::contains($option, ',')) {
                    $option .= ',ASC';
                }
                [$by, $dir] = explode(',', $option, 2);
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
                    }
                    $docid = $next['id'];
                } else {
                    $docid = '';
                }
                break;
            default:
                $docid = $str;
        }
        if (preg_match('@^[1-9]\d*$@', $docid)) {
            unset($this->systemCacheKey);
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
        if ($this->getConfig('enable_at_syntax')) {
            if (stripos($content, '<@LITERAL>') !== false) {
                $content = $this->escapeLiteralTagsContent($content);
            }
        }
        if (!Str::contains($content, '[(')) {
            return $content;
        }

        if (empty($ph)) {

            $ph = array_merge(
                $this->allConfig(),
                [
                    'base_url' => MODX_BASE_URL,
                    'base_path' => MODX_BASE_PATH,
                    'site_url' => MODX_SITE_URL,
                    'valid_hostnames' => MODX_SITE_HOSTNAMES,
                    'site_manager_url' => MODX_MANAGER_URL,
                    'site_manager_path' => MODX_MANAGER_PATH
                ]
            );
        }

        $matches = $this->getTagsFromContent($content, '[(', ')]');
        if (empty($matches)) {
            return $content;
        }

        foreach ($matches[1] as $i => $key) {
            [$key, $modifiers] = $this->splitKeyAndFilter($key);

            if (isset($ph[$key])) {
                $value = $ph[$key];
            } else {
                continue;
            }

            if ($modifiers !== false) {
                $value = $this->applyFilter($value, $modifiers, $key);
            }
            $s = &$matches[0][$i];
            if (Str::contains($content, $s)) {
                $content = str_replace($s, $value, $content);
            } elseif ($this->debug) {
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
        if ($this->getConfig('enable_at_syntax')) {
            if (Str::contains($content, '{{ ')) {
                $content = str_replace(array('{{ ', ' }}'), array('\{\{ ', ' \}\}'), $content);
            }
            if (stripos($content, '<@LITERAL>') !== false) {
                $content = $this->escapeLiteralTagsContent($content);
            }
        }
        if (!Str::contains($content, '{{')) {
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

            [$key, $modifiers] = $this->splitKeyAndFilter($key);

            if (!isset($ph[$key])) {
                $ph[$key] = $this->getChunk($key);
            }
            $value = $ph[$key];

            if ($value === null && !stripos('[',$key)) {
                continue;
            }

            $value = $this->parseText($value, $params); // parse local scope placeholers for ConditionalTags
            $value = $this->mergePlaceholderContent($value, $params);  // parse page global placeholers
            if ($this->getConfig('enable_at_syntax')) {
                $value = $this->mergeConditionalTagsContent($value);
            }
            $value = $this->mergeDocumentContent($value);
            $value = $this->mergeSettingsContent($value);
            $value = $this->mergeChunkContent($value);

            if ($modifiers !== false) {
                $value = $this->applyFilter($value, $modifiers, $key);
            }

            $s = &$matches[0][$i];
            if (Str::contains($content, $s)) {
                $content = str_replace($s, $value, $content);
            } elseif ($this->debug) {
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

        if ($this->getConfig('enable_at_syntax')) {
            if (stripos($content, '<@LITERAL>') !== false) {
                $content = $this->escapeLiteralTagsContent($content);
            }
        }
        if (!Str::contains($content, '[+')) {
            return $content;
        }

        if (empty($ph)) {
            $ph = $this->placeholders;
        }

        if ($this->getConfig('enable_at_syntax')) {
            $content = $this->mergeConditionalTagsContent($content);
        }

        $content = $this->mergeDocumentContent($content);
        $content = $this->mergeSettingsContent($content);
        $matches = $this->getTagsFromContent($content, '[+', '+]');
        if (empty($matches)) {
            return $content;
        }
        foreach ($matches[1] as $i => $key) {

            [$key, $modifiers] = $this->splitKeyAndFilter($key);

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
            if (Str::contains($content, $s)) {
                $content = str_replace($s, $value, $content);
            } elseif ($this->debug) {
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
    public function mergeConditionalTagsContent(
        $content,
        $iftag = '<@IF:',
        $elseiftag = '<@ELSEIF:',
        $elsetag = '<@ELSE>',
        $endiftag = '<@ENDIF>'
    )
    {
        if (Str::contains($content, '@IF')) {
            $content = $this->_prepareCTag($content, $iftag, $elseiftag, $elsetag, $endiftag);
        }

        if (!Str::contains($content, $iftag)) {
            return $content;
        }

        $sp = '#' . md5('ConditionalTags' . $_SERVER['REQUEST_TIME']) . '#';
        $content = str_replace(array('<?php', '<?=', '<?', '?>'), array("{$sp}b", "{$sp}p", "{$sp}s", "{$sp}e"),
            $content);

        $pieces = explode('<@IF:', $content);
        foreach ($pieces as $i => $split) {
            if ($i === 0) {
                $content = $split;
                continue;
            }
            [$cmd, $text] = explode('>', $split, 2);
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
            [$cmd, $text] = explode('>', $split, 2);
            $cmd = str_replace("'", "\'", $cmd);
            $content .= "<?php elseif(\$this->_parseCTagCMD('" . $cmd . "')): ?>";
            $content .= $text;
        }

        $content = str_replace(array('<@ELSE>', '<@ENDIF>'), array('<?php else:?>', '<?php endif;?>'), $content);
        ob_start();
        eval('?>' . $content);
        $content = ob_get_clean();
        $content = str_replace(array("{$sp}b", "{$sp}p", "{$sp}s", "{$sp}e"), array('<?php', '<?=', '<?', '?>'),
            $content);

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
    private function _prepareCTag(
        $content,
        $iftag = '<@IF:',
        $elseiftag = '<@ELSEIF:',
        $elsetag = '<@ELSE>',
        $endiftag = '<@ENDIF>'
    )
    {
        if (Str::contains($content, '<!--@IF ')) {
            $content = str_replace('<!--@IF ', $iftag, $content);
        } // for jp
        if (Str::contains($content, '<!--@IF:')) {
            $content = str_replace('<!--@IF:', $iftag, $content);
        }
        if (!Str::contains($content, $iftag)) {
            return $content;
        }
        if (Str::contains($content, '<!--@ELSEIF:')) {
            $content = str_replace('<!--@ELSEIF:', $elseiftag, $content);
        } // for jp
        if (Str::contains($content, '<!--@ELSE-->')) {
            $content = str_replace('<!--@ELSE-->', $elsetag, $content);
        }  // for jp
        if (Str::contains($content, '<!--@ENDIF-->')) {
            $content = str_replace('<!--@ENDIF-->', $endiftag, $content);
        }    // for jp
        if (Str::contains($content, '<@ENDIF-->')) {
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
        $reverse = strpos($cmd, '!') === 0 ? true : false;
        if ($reverse) {
            $cmd = ltrim($cmd, '!');
        }
        if (Str::contains($cmd, '[!')) {
            $cmd = str_replace(array('[!', '!]'), array('[[', ']]'), $cmd);
        }
        $safe = 0;
        while ($safe < 20) {
            $bt = md5($cmd);
            if (Str::contains($cmd, '[*')) {
                $cmd = $this->mergeDocumentContent($cmd);
            }
            if (Str::contains($cmd, '[(')) {
                $cmd = $this->mergeSettingsContent($cmd);
            }
            if (Str::contains($cmd, '{{')) {
                $cmd = $this->mergeChunkContent($cmd);
            }
            if (Str::contains($cmd, '[[')) {
                $cmd = $this->evalSnippets($cmd);
            }
            if (Str::contains($cmd, '[+') && !Str::contains($cmd, '[[')) {
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

        if (!preg_match('@^\d*$@', $cmd) && preg_match('@^[0-9<= \-\+\*/\(\)%!&|]*$@', $cmd)) {
            $cmd = eval("return {$cmd};");
        } else {
            $_ = explode(',', '[*,[(,{{,[[,[!,[+');
            foreach ($_ as $left) {
                if (Str::contains($cmd, $left)) {
                    $cmd = 0;
                    break;
                }
            }
        }
        $cmd = trim($cmd);
        if (!preg_match('@^\d+$@', $cmd)) {
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
        if (!Str::contains($content, $left)) {
            return $content;
        }

        $matches = $this->getTagsFromContent($content, $left, $right);
        if (!empty($matches)) {
            foreach ($matches[0] as $i => $v) {
                $addBreakMatches[$i] = $v . "\n";
            }
            $content = str_replace($addBreakMatches, '', $content);
            if (Str::contains($content, $left)) {
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

        [$sTags, $rTags] = $this->getTagsForEscape();
        foreach ($matches[1] as $i => $v) {
            $v = str_ireplace($sTags, $rTags, $v);
            $s = &$matches[0][$i];
            if (Str::contains($content, $s)) {
                $content = str_replace($s, $v, $content);
            } elseif ($this->debug) {
                $this->addLog('ignoreCommentedTagsContent parse error', $_SERVER['REQUEST_URI'] . $s, 2);
            }
        }

        return $content;
    }

    /**
     * Detect PHP error according to Evolution CMS error level
     *
     * @param integer $error PHP error level
     * @return boolean Error detected
     */

    public function detectError($error)
    {
        $detected = false;
        if ($this->getConfig('error_reporting') === 199 && $error) {
            $detected = true;
        } elseif ($this->getConfig('error_reporting') === 99 && ($error & ~E_USER_DEPRECATED)) {
            $detected = true;
        } elseif ($this->getConfig('error_reporting') === 2 && ($error & ~E_NOTICE & ~E_USER_DEPRECATED)) {
            $detected = true;
        } elseif ($this->getConfig('error_reporting') === 1 && ($error & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT)) {
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
        if (!is_object($modx->event)) {
            $modx->event = new \stdClass();
        }
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
        $msg = ob_get_clean();
        // When reached here, no fatal error occured so the lock should be removed.
        /*if(is_file($lock_file_path)) unlink($lock_file_path);*/
        $error_info = error_get_last();

        if ((0 < $this->getConfig('error_reporting')) && $msg && $error_info !== null && $this->detectError($error_info['type'])) {
            $msg = ($msg === false) ? 'ob_get_contents() error' : $msg;
            $this->getService('ExceptionHandler')->messageQuit(
                'PHP Parse Error',
                '',
                true,
                $error_info['type'],
                $error_info['file'],
                'Plugin',
                $error_info['message'],
                $error_info['line'],
                $msg
            );
            if ($this->isBackend()) {
                $this->event->alert(
                    sprintf(
                        'An error occurred while loading. Please see the event log for more information.<p>%s</p>'
                        , $msg
                    )
                );
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
        if (!is_object($modx->event)) {
            $modx->event = new \stdClass();
        }
        $modx->event->params = &$params; // store params inside event object
        if (is_array($params)) {
            extract($params, EXTR_SKIP);
        }
        ob_start();
        if (is_scalar($phpcode) && Str::contains($phpcode, ';')) {
            if (substr($phpcode, 0, 5) === '<?php') {
                $phpcode = substr($phpcode, 5);
            }
            $return = eval($phpcode);
        } elseif (!empty($phpcode) && !is_bool($phpcode)) {
            $return = call_user_func_array($phpcode, array($params));
        } else {
            $return = '';
        }
        $echo = ob_get_clean();
        $error_info = error_get_last();
        if ((0 < $this->getConfig('error_reporting')) && $error_info !== null && $this->detectError($error_info['type'])) {
            $echo = ($echo === false) ? 'ob_get_contents() error' : $echo;
            $this->getService('ExceptionHandler')->messageQuit(
                'PHP Parse Error',
                '',
                true,
                $error_info['type'],
                $error_info['file'],
                'Snippet',
                $error_info['message'],
                $error_info['line'],
                $echo
            );
            if ($this->isBackend()) {
                $this->event->alert(
                    'An error occurred while loading. Please see the event log for more information' .
                    '<p>' . $echo . $return . '</p>'
                );
            }
        }
        unset($modx->event->params);
        if (is_array($return) || is_object($return)) {
            return $return;
        }

        return $echo . $return;
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
        if (!Str::contains($content, '[[')) {
            return $content;
        }

        $matches = $this->getTagsFromContent($content, '[[', ']]');

        if (empty($matches)) {
            return $content;
        }

        $this->snipLapCount++;
        if ($this->dumpSnippets) {
            $this->snippetsCode .= sprintf('<fieldset><legend><b style="color: #821517;">PARSE PASS %s</b></legend><p>The following snippets (if any) were parsed during this pass.</p>',
                $this->snipLapCount);
        }

        foreach ($matches[1] as $i => $call) {
            $s = &$matches[0][$i];
            if (substr($call, 0, 2) === '$_') {
                if (!Str::contains($content, '_PHX_INTERNAL_')) {
                    $value = $this->_getSGVar($call);
                } else {
                    $value = $s;
                }
                if (Str::contains($content, $s)) {
                    $content = str_replace($s, $value, $content);
                } elseif ($this->debug) {
                    $this->addLog('evalSnippetsSGVar parse error', $_SERVER['REQUEST_URI'] . $s, 2);
                }
                continue;
            }
            $value = $this->_get_snip_result($call);

            if (Str::contains($content, $s)) {
                $content = str_replace($s, $value, $content);
            } elseif ($this->debug) {
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
        $_ = $this->getConfig('enable_filter');
        $this->setConfig('enable_filter', 1);
        [$key, $modifiers] = $this->splitKeyAndFilter($key);
        $this->setConfig('enable_filter', $_);
        $key = str_replace(array('(', ')'), array("['", "']"), $key);
        $key = rtrim($key, ';');
        if (Str::contains($key, '$_SESSION')) {
            $_ = $_SESSION;
            $key = str_replace('$_SESSION', '$_', $key);
            if (isset($_['mgrFormValues'])) {
                unset($_['mgrFormValues']);
            }
            if (isset($_['token'])) {
                unset($_['token']);
            }
        }
        if (Str::contains($key, '[')) {
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

        [$key, $modifiers] = $this->splitKeyAndFilter($key);
        $snip_call['name'] = $key;
        $snippetObject = $this->getSnippetObject($key);
        if ($snippetObject['content'] === null) {
            return null;
        }

        $this->currentSnippet = $snippetObject['name'];

        // current params
        $params = $this->getParamsFromString($snip_call['params']);

        if (!isset($snippetObject['properties'])) {
            $snippetObject['properties'] = array();
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
            $code = str_replace("\t", '  ', $this->getPhpCompat()->htmlspecialchars($value));
            $piece = str_replace("\t", '  ', $this->getPhpCompat()->htmlspecialchars($piece));
            $print_r_params = str_replace("\t", '  ',
                $this->getPhpCompat()->htmlspecialchars('$modx->event->params = ' . print_r($params, true)));
            $this->snippetsCode .= sprintf(
                '<fieldset style="margin:1em;"><legend><b>%s</b>(%s)</legend><pre style="white-space: pre-wrap;background-color:#fff;width:90%%;">[[%s]]</pre><pre style="white-space: pre-wrap;background-color:#fff;width:90%%;">%s</pre><pre style="white-space: pre-wrap;background-color:#fff;width:90%%;">%s</pre></fieldset>'
                , $snippetObject['name']
                , $eventtime
                , $piece
                , $print_r_params
                , $code
            );
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

        if (Str::contains($string, '&_PHX_INTERNAL_')) {
            $string = str_replace(
                array('&_PHX_INTERNAL_091_&', '&_PHX_INTERNAL_093_&')
                , array('[', ']')
                , $string
            );
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
                    [$null, $value, $_tmp] = explode($delim, $_tmp, 3);
                    unset($null);

                    if (strpos(trim($_tmp), '//') === 0) {
                        $_tmp = strstr(trim($_tmp), "\n");
                    }
                    $i = 0;
                    while ($delim === '`' && substr(trim($_tmp), 0, 1) !== '&' && 1 < substr_count($_tmp, '`')) {
                        [$inner, $outer, $_tmp] = explode('`', $_tmp, 3);
                        $value .= "`{$inner}`{$outer}";
                        $i++;
                        if (100 < $i) {
                            exit('The nest of values are hard to read. Please use three different quotes.');
                        }
                    }
                    if ($i && $delim === '`') {
                        $value = rtrim($value, '`');
                    }
                } elseif (Str::contains($_tmp, '&')) {
                    [$value, $_tmp] = explode('&', $_tmp, 2);
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

            if (isset($value) && $value !== null) {
                if (Str::contains($key, 'amp;')) {
                    $key = str_replace('amp;', '', $key);
                }
                $key = trim($key);
                if (Str::contains($value, '[!')) {
                    $value = str_replace(array('[!', '!]'), array('[[', ']]'), $value);
                }
                $value = $this->mergeDocumentContent($value);
                $value = $this->mergeSettingsContent($value);
                $value = $this->mergeChunkContent($value);
                $value = $this->evalSnippets($value);
                if (strpos($value, '@CODE:') !== 0) {
                    $value = $this->mergePlaceholderContent($value);
                }

                $temp_params[][$key] = $value;

                $key = '';
                $value = null;

                $_tmp = ltrim($_tmp, " ,\t");
                if (strpos($_tmp, '//') === 0) {
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
            } elseif (Str::contains($k, '[') && substr($k, -1) === ']') {
                [$k, $subk] = explode('[', $k, 2);
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
                } elseif ($c === ' ' && !Str::contains($str, '?')) {
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
        if (Str::contains($call, ']]>')) {
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
        if (Str::contains($params, $spacer)) {
            $params = str_replace("]{$spacer}]>", ']]>', $params);
        }
        $snip['params'] = ltrim($params, "?& \t\n");

        return $snip;
    }

    /**
     * @param $snip_name
     * @return mixed
     */
    public function getSnippetObject($snip_name)
    {
        if (array_key_exists($snip_name, $this->snippetCache)) {
            $snippetObject['name'] = $snip_name;
            $snippetObject['content'] = $this->snippetCache[$snip_name];
            if (isset($this->snippetCache[$snip_name . 'Props'])) {
                if (!isset($this->snippetCache[$snip_name . 'Props'])) {
                    $this->snippetCache[$snip_name . 'Props'] = '';
                }
                $snippetObject['properties'] = $this->snippetCache["{$snip_name}Props"];
            }
        } elseif (strpos($snip_name, '@') === 0 && isset($this->pluginEvent[substr($snip_name, 1)])) {
            $snippetObject['name'] = substr($snip_name, 1);
            $snippetObject['content'] = sprintf(
                '$rs=$this->invokeEvent("%s",$params);echo trim(implode("",$rs));'
                , $snippetObject['name']
            );
            $snippetObject['properties'] = '';
        } else {
            $snippetObject = $this->getSnippetFromDatabase($snip_name);

            $this->snippetCache[$snip_name] = $snippetObject['content'];
            $this->snippetCache["{$snip_name}Props"] = $snippetObject['properties'];
        }

        return $snippetObject;
    }

    public function getSnippetFromDatabase($snip_name): array
    {
        $snippetObject = [];

        /** @var \Illuminate\Database\Eloquent\Collection $snippetModelCollection */
        $snippetModelCollection = Models\SiteSnippet::where('name', '=', $snip_name)
            ->where('disabled', '=', 0)
            ->get();
        if ($snippetModelCollection->count() > 1) {
            exit('Error $modx->getSnippetObject()' . $snip_name);
        }

        if ($snippetModelCollection->count() === 1) {
            /** @var Models\SiteSnippet $snippetModel */
            $snippetModel = $snippetModelCollection->first();
            $snip_content = $snippetModel->snippet;
            $snip_prop = $snippetModel->properties;

            $snip_prop = array_merge(
                $this->parseProperties($snip_prop),
                $this->parseProperties(optional($snippetModel->activeModule)->properties ?? [])
            );
            $snip_prop = empty($snip_prop) ? '{}' : json_encode($snip_prop);

        } else {
            $snip_content = null;
            $snip_prop = '';
        }
        $snippetObject['name'] = $snip_name;
        $snippetObject['content'] = $snip_content;
        $snippetObject['properties'] = $snip_prop;

        return $snippetObject;
    }

    /**
     * @deprecated use UrlProcessor::toAlias()
     */
    public function toAlias($text)
    {
        return UrlProcessor::toAlias($text);
    }

    /**
     * @deprecated use UrlProcessor::makeFriendlyURL()
     */
    public function makeFriendlyURL($pre, $suff, $alias, $isfolder = 0, $id = 0)
    {
        return UrlProcessor::makeFriendlyURL($pre, $suff, $alias, (bool)$isfolder, (int)$id);
    }

    /**
     * @deprecated use UrlProcessor::rewriteUrls()
     */
    public function rewriteUrls($documentSource)
    {
        return UrlProcessor::rewriteUrls($documentSource);
    }

    public function sendStrictURI()
    {
        $url = UrlProcessor::strictURI((string)$this->q, (int)$this->documentIdentifier);
        if ($url !== null) {
            $this->sendRedirect($url, 0, 'REDIRECT_HEADER', 'HTTP/1.0 301 Moved Permanently');
        }
    }

    /**
     * Get all db fields and TVs for a document/resource
     *
     * @param string $method
     * @param mixed $identifier
     * @param bool $isPrepareResponse
     * @return array
     * @throws \AgelxNash\Modx\Evo\Database\Exceptions\Exception
     * @throws InvalidFieldException
     * @throws TableNotDefinedException
     */
    public function getDocumentObject($method, $identifier, $isPrepareResponse = false)
    {
        $documentObject = false;
        $cacheKey = $this->makePageCacheKey($identifier);

        // allow alias to be full path
        if ($method === 'alias') {
            $identifier = $this->cleanDocumentIdentifier($identifier);
            $method = $this->documentMethod;
        }
        if ($method === 'alias' && $this->getConfig('use_alias_path') && array_key_exists($identifier,
                UrlProcessor::getFacadeRoot()->documentListing)) {
            $method = 'id';
            $identifier = UrlProcessor::getFacadeRoot()->documentListing[$identifier];
        }

        $out = $this->invokeEvent(
            'OnBeforeLoadDocumentObject'
            , compact('method', 'identifier')
        );

        if (is_array($out) && is_array($out[0])) {
            $documentObject = $out[0];
        } else {
            $cachedData = Cache::get($cacheKey);
            if (!is_null($cachedData)) {
                $documentObject = $cachedData;
            }
        }

        if ($documentObject === false) {
            $documentObject = SiteContent::query()
                ->where('site_content.' . $method, $identifier)->first();


            if (is_null($documentObject)) {
                $this->sendErrorPage();
                exit;
            }

            # this is now the document :) #
            $documentObject = $documentObject->toArray();

            if ($isPrepareResponse === 'prepareResponse') {
                $this->documentObject = &$documentObject;
            }

            $out = $this->invokeEvent(
                'OnLoadDocumentObject'
                , compact('method', 'identifier', 'documentObject')
            );

            if (is_array($out) && is_array($out[0])) {
                $documentObject = $out[0];
            }

            if ($documentObject['template']) {
                // load TVs and merge with document - Orig by Apodigm - Docvars
                $tvs = SiteTmplvar::query()->select('site_tmplvars.*', 'site_tmplvar_contentvalues.value')
                    ->join('site_tmplvar_templates', 'site_tmplvar_templates.tmplvarid', '=', 'site_tmplvars.id')
                    ->leftJoin('site_tmplvar_contentvalues', function ($join) use ($documentObject) {
                        $join->on('site_tmplvar_contentvalues.tmplvarid', '=', 'site_tmplvars.id');
                        $join->on('site_tmplvar_contentvalues.contentid', '=', \DB::raw((int)$documentObject['id']));
                    })->where('site_tmplvar_templates.templateid', $documentObject['template'])->get();

                $tmplvars = array();
                foreach ($tvs as $tv) {
                    $row = $tv->toArray();
                    if ($row['value'] == '') $row['value'] = $row['default_text'];
                    $tmplvars[$row['name']] = array(
                        $row['name'],
                        $row['value'],
                        $row['display'],
                        $row['display_params'],
                        $row['type']
                    );
                }
                $documentObject = array_merge($documentObject, $tmplvars);

                $documentObject['templatealias'] = SiteTemplate::select('templatealias')->where('id', $documentObject['template'])->first()->templatealias;
            }
            $out = $this->invokeEvent(
                'OnAfterLoadDocumentObject'
                , compact('method', 'identifier', 'documentObject')
            );

            if (is_array($out) && array_key_exists(0, $out) !== false && is_array($out[0])) {
                $documentObject = $out[0];
            }
        }
        if ($documentObject['privatemgr'] == 1 && (!isset($_SESSION['mgrRole']) || $_SESSION['mgrRole'] != 1)) {
            $checkRole = false;
            if (!isset($documentObject['__user_groups'])) {
                if ($method !== 'alias') {
                    $documentObject['__user_groups'] = \EvolutionCMS\Models\DocumentGroup::where('document', $identifier)->pluck('document_group')->toArray();
                } else {
                    $documentObject['__user_groups'] = \EvolutionCMS\Models\DocumentGroup::query()->select('document_groups.document_group')
                        ->join('site_content', function ($join) use ($identifier) {
                            $join->on('document_groups.document', '=', 'site_content.id');
                            $join->on('site_content.alias', '=', $identifier);
                        })->pluck('document_group')->toArray();
                }
            }
            $docgrp = $this->getUserDocGroups();

            if (is_array($docgrp)) {
                foreach ($docgrp as $group) {
                    if (in_array($group, $documentObject['__user_groups'])) {
                        $checkRole = true;
                        break;
                    }
                }
            }
            // method may still be alias, while identifier is not full path alias, e.g. id not found above
            if ($this->getConfig('unauthorized_page') && $checkRole === false) {
                // match found but not publicly accessible, send the visitor to the unauthorized_page
                $this->sendUnauthorizedPage();
                exit; // stop here
            }
            if ($checkRole === false) {
                $this->sendErrorPage();
                exit;
            }
        }

        Cache::forever($cacheKey, $documentObject);

        return $documentObject;
    }

    public function makeDocumentObject($id, $values = true)
    {
        if (\is_array($this->documentObject) && $id === $this->documentObject['id']) {
            $documentObject = $this->documentObject;
            if ($values === true) {
                foreach ($documentObject as $key => $value) {
                    if (\is_array($value)) {
                        $documentObject[$key] = $value[1] ?? '';
                    }
                }
            }
            return $documentObject;
        }

        $documentObject = \EvolutionCMS\Models\SiteContent::findOrFail((int)$id)->toArray();
        if ($documentObject === null) {
            return array();
        }

        $rs = \DB::table('site_tmplvars as tv')
            ->select('tv.*', 'tvc.value', 'tv.default_text')
            ->join('site_tmplvar_templates as tvtpl', 'tvtpl.tmplvarid', '=', 'tv.id')
            ->leftJoin('site_tmplvar_contentvalues as tvc', function ($join) use ($documentObject) {
                $join->on('tvc.tmplvarid', '=', 'tv.id');
                $join->on('tvc.contentid', '=', \DB::raw((int)$documentObject['id']));
            })->where('tvtpl.templateid', (int)$documentObject['template'])->get();

        $tmplvars = array();
        foreach ($rs as $row) {

            if ($row->value == '') {
                $row->value = $row->default_text;
            }
            $tmplvars[$row->name] = array(
                $row->name,
                $row->value,
                $row->display,
                $row->display_params,
                $row->type
            );
        }
        $documentObject = array_merge($documentObject, $tmplvars);
        if ($values === true) {
            foreach ($documentObject as $key => $value) {
                if (\is_array($value)) {
                    $documentObject[$key] = $value[1] ?? '';
                }
            }
        }
        return $documentObject;
    }

    /**
     * Parse a source string.
     *
     * Handles most Evolution CMS tags. Exceptions include:
     *   - uncached snippet tags [!...!]
     *   - URL tags [~...~]
     *
     * @param string $source
     * @return string
     */
    public function parseDocumentSource($source)
    {
        // set the number of times we are to parse the document source
        $this->minParserPasses = !$this->minParserPasses ? 2 : $this->minParserPasses;
        $passes = $this->minParserPasses;
        for ($i = 0; $i < $passes; $i++) {
            // get source length if this is the final pass
            if ($i == ($passes - 1)) {
                $st = md5($source);
            }
            if ($this->dumpSnippets == 1) {
                $this->snippetsCode .= sprintf(
                    "<fieldset><legend><b style='color: #821517;'>PARSE PASS %d</b></legend><p>The following snippets (if any) were parsed during this pass.</p>"
                    , $i + 1);
            }

            // invoke OnParseDocument event
            $this->documentOutput = $source; // store source code so plugins can
            $this->invokeEvent('OnParseDocument'); // work on it via $modx->documentOutput
            $source = $this->documentOutput;

            if ($this->getConfig('enable_at_syntax')) {
                $source = $this->ignoreCommentedTagsContent($source);
                $source = $this->mergeConditionalTagsContent($source);
            }

            $source = $this->mergeSettingsContent($source);
            $source = $this->mergeDocumentContent($source);
            $source = $this->mergeChunkContent($source);
            $source = $this->evalSnippets($source);
            $source = $this->mergePlaceholderContent($source);

            if ($this->dumpSnippets == 1) {
                $this->snippetsCode .= '</fieldset><br />';
            }
            if ($i == ($passes - 1) && $i < ($this->maxSourcePasses - 1)) {
                // check if source content was changed
                if ($st != md5($source)) {
                    $passes++;
                } // if content change then increase passes because
            } // we have not yet reached maxParserPasses
        }

        return $source;
    }

    public function processRoutes()
    {
        $request = Request::createFromGlobals();
        $this->instance(Request::class, $request);
        $this->alias(Request::class, 'request');

        $middleware = array_merge(
            config('app.middleware', []),
            config('middleware.global', [])
        );

        try {
            $response = (new Pipeline($this))
                ->send($request)
                ->through($middleware)
                ->then(function ($request) {
                    return $this->router->dispatch($request);
                });
        } catch (NotFoundHttpException | MethodNotAllowedException $exception) {
            $this->executeParser();
            exit;
        }

        $response->send();
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
        if (is_cli()) {
            throw new \RuntimeException('Call DocumentParser::executeParser on CLI mode');
        }
        //error_reporting(0);

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
                $this->documentContent = $this->getConfig('site_unavailable_message');
                $this->outputContent();
                exit; // stop processing here, as the site's offline
            }

            // setup offline page document settings
            $this->documentMethod = 'id';
            $this->documentIdentifier = $this->getConfig('site_unavailable_page');
        }

        if ($this->documentMethod !== 'alias') {
            $DLTemplate = \DLTemplate::getInstance(EvolutionCMS());
            $DLTemplate->setTemplatePath('views/');
            //$this->_fixURI();
            // invoke OnWebPageInit event
            $this->invokeEvent("OnWebPageInit");
            // invoke OnLogPageView event
            if ($this->getConfig('track_visitors') == 1) {
                $this->invokeEvent("OnLogPageHit");
            }
            if ($this->getConfig('seostrict') == '1') {
                $this->sendStrictURI();
            }
            $this->prepareResponse();
            return;
        }

        $this->documentIdentifier = $this->cleanDocumentIdentifier($this->documentIdentifier);

        // Check use_alias_path and check if $this->virtualDir is set to anything, then parse the path
        if ($this->getConfig('use_alias_path') == 1) {

            $virtualDir = UrlProcessor::getFacadeRoot()->virtualDir;
            $alias = sprintf(
                '%s%s'
                , $virtualDir != '' ? $virtualDir . '/' : ''
                , $this->documentIdentifier
            );
            if (isset(UrlProcessor::getFacadeRoot()->documentListing[$alias])) {
                $this->documentIdentifier = UrlProcessor::getFacadeRoot()->documentListing[$alias];
            } else {
                if ($this->getConfig('aliaslistingfolder') == 1 || $this->getConfig('full_aliaslisting') == 1) {
                    $parent = $virtualDir ? UrlProcessor::getIdFromAlias($virtualDir) : 0;
                    $doc = SiteContent::select('id')
                        ->where('deleted', 0)
                        ->where('parent', $parent)
                        ->where('alias', $this->documentIdentifier)->first();
                    if (is_null($doc)) {
                        $this->sendErrorPage();
                    }
                    $this->documentIdentifier = $doc->getKey();
                } else {
                    $this->sendErrorPage();
                }
            }
        } else {
            if (isset(UrlProcessor::getFacadeRoot()
                    ->documentListing[$this->documentIdentifier])) {
                $this->documentIdentifier = UrlProcessor::getFacadeRoot()
                    ->documentListing[$this->documentIdentifier];
            } else {
                $doc = SiteContent::select('id')
                    ->where('deleted', 0)
                    ->where('alias', $this->documentIdentifier)->first();
                if (is_null($doc)) {
                    $this->sendErrorPage();
                }
                $this->documentIdentifier = $doc->getKey();
            }
        }
        $this->documentMethod = 'id';
        $DLTemplate = \DLTemplate::getInstance(EvolutionCMS());
        $DLTemplate->setTemplatePath('views/');

        //$this->_fixURI();
        // invoke OnWebPageInit event
        $this->invokeEvent('OnWebPageInit');
        // invoke OnLogPageView event
        if ($this->getConfig('track_visitors') == 1) {
            $this->invokeEvent('OnLogPageHit');
        }
        if ($this->getConfig('seostrict') == '1') {
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
        if ($this->getConfig('friendly_urls') != 1) {
            return;
        }

        if (!Str::contains($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS')) {
            return;
        }

        $url = $_SERVER['QUERY_STRING'];
        $err = substr($url, 0, 3);
        if ($err !== '404' && $err !== '405') {
            return;
        }

        $k = array_keys($_GET);
        unset($_GET[$k[0]], $_REQUEST[$k[0]]);
        // remove 404,405 entry
        $qp = parse_url(str_replace(MODX_SITE_URL, '', substr($url, 4)));
        $_SERVER['QUERY_STRING'] = $qp['query'];
        if ($qp['query']) {
            parse_str($qp['query'], $qv);
            foreach ($qv as $n => $v) {
                $_REQUEST[$n] = $_GET[$n] = $v;
            }
        }
        $_SERVER['PHP_SELF'] = MODX_BASE_URL . $qp['path'];
        $this->q = $qp['path'];
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

        if ($this->getConfig('enable_cache') == 2 && $this->isLoggedIn()) {
            $this->setConfig('enable_cache', 0);
        }

        if ($this->getConfig('enable_cache')) {
            $this->documentContent = $this->getDocumentObjectFromCache($this->documentIdentifier, true);
        } else {
            $this->documentContent = '';
        }

        if ($this->documentContent == '') {

            // get document object from DB
            $this->documentObject = $this->getDocumentObject(
                $this->documentMethod
                , $this->documentIdentifier
                , 'prepareResponse'
            );

            // write the documentName to the object
            $this->documentName = &$this->documentObject['pagetitle'];

            // check if we should not hit this document
            if ($this->documentObject['hide_from_tree'] == 1) {
                $this->setConfig('track_visitors', 0);
            }

            if ($this->documentObject['deleted'] == 1) {
                $this->sendErrorPage();
            } // validation routines
            elseif ($this->documentObject['published'] == 0) {
                $this->_sendErrorForUnpubPage();
            } elseif ($this->documentObject['type'] === 'reference') {
                $this->_sendRedirectForRefPage($this->documentObject['content']);
            }

            $template = TemplateProcessor::getBladeDocumentContent();

            if ($template) {
                $this->documentObject['cacheable'] = 0;
                /** @var \Illuminate\View\View $tpl */

                if (isset($this->documentObject['id'])) {
                    $data = [
                        'modx' => $this,
                        'documentObject' => $this->makeDocumentObject($this->documentObject['id'])
                    ];
                } else {
                    $data = [
                        'modx' => $this,
                        'documentObject' => [],
                        'siteContentObject' => []
                    ];
                }

                $this['view']->share($data);

                if ($this->isChunkProcessor('DLTemplate')) {
                    app('DLTemplate')->blade->share($data);
                }

                $tpl = $this['view']->make($template, $this->dataForView);
                $templateCode = $tpl->render();
            } else {
                // get the template and start parsing!
                if (!$this->documentObject['template']) {
                    $templateCode = '[*content*]';
                } // use blank template
                else {
                    $templateCode = TemplateProcessor::getTemplateCodeFromDB($this->documentObject['template']);
                }

                if (strpos($templateCode, '@INCLUDE') === 0) {
                    $templateCode = $this->atBindInclude($templateCode);
                }
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

        if ($this->getConfig('error_page') == $this->documentIdentifier) {
            if ($this->getConfig('error_page') != $this->getConfig('site_start')) {
                header('HTTP/1.0 404 Not Found');
            }
        }

        register_shutdown_function(array(
            &$this,
            'postProcess'
        )); // tell PHP to call postProcess when it shuts down
        $this->outputContent();
        $this->postProcess();
    }

    public function _sendErrorForUnpubPage()
    {
        // Can't view unpublished pages !$this->checkPreview()
        if (!$this->hasPermission('view_unpublished')) {
            $this->sendErrorPage();
            return;
        }

        $udperms = new Legacy\Permissions();
        $udperms->user = $this->getLoginUserID();
        $udperms->document = $this->documentIdentifier;
        $udperms->role = $_SESSION['mgrRole'];
        // Doesn't have access to this document
        if (!$udperms->checkPermissions()) {
            $this->sendErrorPage();
        }
    }

    /**
     * @param $url
     */
    public function _sendRedirectForRefPage($url)
    {
        // check whether it's a reference
        if (preg_match('@^[1-9]\d*$@', $url)) {
            $url = UrlProcessor::makeUrl($url); // if it's a bare document id
        } elseif (Str::contains($url, '[~')) {
            $url = UrlProcessor::rewriteUrls($url); // if it's an internal docid tag, process it
        }
        $this->sendRedirect($url, 0, '', 'HTTP/1.0 302 Moved Temporarily');
        exit;
    }

    /**
     * @deprecated use TemplateProcessor::getTemplateCodeFromDB()
     */
    public function _getTemplateCodeFromDB($templateID)
    {
        return TemplateProcessor::getTemplateCodeFromDB($templateID);
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
            $aliasListing = get_by_key(
                UrlProcessor::getFacadeRoot()->aliasListing
                , $id
                , []
                , 'is_array'
            );
            $tmp = get_by_key($aliasListing, 'parent');
            $cerrent_id = $id;
            if ($this->getConfig('aliaslistingfolder')) {
                $id = $tmp ?? (int)Models\SiteContent::findOrNew($id)->parent;
            } else {
                $id = $tmp;
            }

            if ((int)$id === 0) {
                break;
            }
            $parents[$cerrent_id] = (int)$id;
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
            if ($top == UrlProcessor::getFacadeRoot()->aliasListing[$id]['parent']) {
                break;
            }
            $id = UrlProcessor::getFacadeRoot()->aliasListing[$id]['parent'];
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
     * @throws \AgelxNash\Modx\Evo\Database\Exceptions\Exception
     * @throws InvalidFieldException
     * @throws TableNotDefinedException
     */
    public function getChildIds($id, $depth = 10, $children = array())
    {
        static $cached = array();

        $cacheKey = md5(print_r(func_get_args(), true));
        if (isset($cached[$cacheKey])) {
            return $cached[$cacheKey];
        }
        $cached[$cacheKey] = array();

        if ($this->getConfig('aliaslistingfolder') == 1) {

            $res = \EvolutionCMS\Models\SiteContent::destroy()
                ->selectRaw("id,alias,isfolder,parent")
                ->where([
                        ['parent', 'IN', $id],
                        ['deleted', '=', '0']
                    ]
                )->get()->toArray();


            $idx = array();
            foreach ($res as $row) {
                $pAlias = '';
                if (isset(UrlProcessor::getFacadeRoot()->aliasListing[$row['parent']])) {
                    if (UrlProcessor::getFacadeRoot()->aliasListing[$row['parent']]['path']) {
                        $pAlias .= UrlProcessor::getFacadeRoot()->aliasListing[$row['parent']]['path'] . '/';
                    }
                    if (UrlProcessor::getFacadeRoot()->aliasListing[$row['parent']]['alias']) {
                        $pAlias .= UrlProcessor::getFacadeRoot()->aliasListing[$row['parent']]['alias'] . '/';
                    }
                };
                $children[$pAlias . $row['alias']] = $row['id'];
                if ($row['isfolder']) {
                    $idx[] = $row['id'];
                }
            }
            $depth--;
            $idx = implode(',', $idx);
            if ($idx && $depth) {
                $children = $this->getChildIds($idx, $depth, $children);
            }
            $cached[$cacheKey] = $children;

            return $children;

        }

        // Initialise a static array to index parents->children
        static $documentMap_cache = array();
        if (!$documentMap_cache) {
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
                if (strlen(UrlProcessor::getFacadeRoot()->aliasListing[$childId]['path'])) {
                    $pkey = sprintf(
                        "{UrlProcessor::getFacadeRoot()->aliasListing[%s]['path']}/%s"
                        , $childId
                        , UrlProcessor::getFacadeRoot()->aliasListing[$childId]['alias']
                    );
                } else {
                    $pkey = UrlProcessor::getFacadeRoot()->aliasListing[$childId]['alias'];
                }
                if ($pkey == '') {
                    $pkey = (string)$childId;
                }
                $children[$pkey] = $childId;

                if ($depth && isset($documentMap_cache[$childId])) {
                    $children += $this->getChildIds($childId, $depth);
                }
            }
        }

        $cached[$cacheKey] = $children;

        return $children;
    }

    /**
     * Displays a javascript alert message in the web browser and quit
     *
     * @param string $msg Message to show
     * @param string $url URL to redirect to
     */
    public function webAlertAndQuit($msg, $url = '')
    {
        $manager_charset = Arr::get($GLOBALS, 'modx_manager_charset', $this->getConfig('modx_charset'));
        $lang_attribute = Arr::get($GLOBALS, 'modx_lang_attribute', $this->getConfig('lang_code'));

        if (Arr::get($GLOBALS, 'modx_textdir', $this->getConfig('manager_direction')) === 'rtl') {
            $textdir = 'rtl';
        } else {
            $textdir = 'ltr';
        }

        if (stripos($url, 'javascript:') === 0) {
            $fnc = substr($url, 11);
        } elseif ($url === '#') {
            $fnc = '';
        } elseif (!$url) {
            $fnc = 'history.back(-1);';
        } else {
            $fnc = "window.location.href='" . addslashes($url) . "';";
        }

        $style = '';
        if (IN_MANAGER_MODE) {
            $path = sprintf('media/style/%s/', $this->getConfig('manager_theme'));
            if (is_file(MODX_MANAGER_PATH . $path . '/css/styles.min.css')) {
                $file_name = '/css/styles.min.css';
            } else {
                $file_name = 'style.css';
            }
            $style = sprintf(
                '<link rel="stylesheet" type="text/css" href="%s%s%s?v=%s"/>'
                , MODX_MANAGER_URL
                , $path
                , $file_name
                , Arr::get($GLOBALS, 'lastInstallTime', time())
            );
        }

        ob_get_clean();
        echo '<!DOCTYPE html>
            <html lang="' . $lang_attribute . '" dir="' . $textdir . '">
                <head>
                <title>Evolution CMS :: Alert</title>
                <meta http-equiv="Content-Type" content="text/html; charset=' . $manager_charset . ';">
                ' . $style . "
                <script>
                    function __alertQuit() {
                        var el = document.querySelector('p');
                        alert(el.innerHTML);
                        el.remove();
                        " . $fnc . "
                    }
                    window.setTimeout('__alertQuit();',100);
                </script>
            </head>
            <body>
                <p>" . $msg . '</p>
            </body>
        </html>';
        exit;
    }

    /**
     * Returns 1 if user has the currect permission
     *
     * @param string $pm Permission name
     * @return int Why not bool?
     */
    public function hasPermission($pm, $context = '')
    {
        if (empty($context)) {
            $context = $this->getContext();
        }
        $state = 0;
        $pms = get_by_key($_SESSION, $context . 'Permissions', [], 'is_array');
        if ($pms) {
            $state = (isset($pms[$pm]) && (bool)$pms[$pm] === true);
        }

        return (int)$state;
    }

    /**
     * @param array $permissions
     * @return bool
     */
    public function hasAnyPermissions(array $permissions, $context = '')
    {
        if (empty($context)) {
            $context = $this->getContext();
        }
        foreach ($permissions as $p) {
            if ($this->hasPermission($p, $context)) {
                return true;
            }
        }

        return false;
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
        if (!$includeThisUser && get_by_key($this->lockedElements, $type . '.' . $id . '.sid') == $this->sid) {
            return null;
        }

        if (isset($this->lockedElements[$type][$id])) {
            return $this->lockedElements[$type][$id];
        }

        return null;
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
        }

        return array();
    }

    /**
     * Builds the Locked Elements Cache once
     */
    public function buildLockedElementsCache()
    {
        if ($this->lockedElements === null) {
            $this->lockedElements = array();
            $this->cleanupExpiredLocks();
            $rs = ActiveUserLock::query()
                ->select('sid', 'internalKey', 'elementType', 'elementId', 'lasthit', 'username')
                ->leftJoin('users', 'active_user_locks.internalKey', '=', 'users.id')
                ->get();
            foreach ($rs->toArray() as $row) {
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
        if ((int)$this->getConfig('session_timeout') < 2) {
            $timeout = 120;
        } else {
            $timeout = $this->getConfig('session_timeout') * 60;
        }
        // session.js pings every 10min, updateMail() in mainMenu pings every minute, so 2min is minimum
        $validSessionTimeLimit = $this->time - $timeout;
        ActiveUserSession::where('lasthit', '<', (int)$validSessionTimeLimit)->delete();

        // Clean-up active_user_locks
        $activeUsers = ActiveUserSession::select('sid', 'internalKey')->get();

        if ($activeUsers->count() > 0) {
            $rs = $activeUsers->toArray();
            $userSids = array();
            foreach ($rs as $row) {
                $userSids[] = $row['sid'];
            }
            ActiveUserSession::whereNotIn('sid', $userSids)->delete();
        } else {
            ActiveUserLock::query()->truncate();
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
        $activeUserSids = ActiveUserSession::all();
        if ($activeUserSids->count() > 0) {
            $activeUserSids = $activeUserSids->pluck('sid');
        } else {
            $activeUserSids = [];
        }

        $activeUsers = ActiveUser::query()->orderBy('lasthit', 'DESC')->get();

        if ($activeUsers->count() > 0) {
            $rs = $activeUsers->toArray();
            $internalKeyCount = array();
            $deleteSids = [];
            foreach ($rs as $row) {
                if (!isset($internalKeyCount[$row['internalKey']])) {
                    $internalKeyCount[$row['internalKey']] = 0;
                }
                $internalKeyCount[$row['internalKey']]++;

                if ($internalKeyCount[$row['internalKey']] > 1
                    && !in_array($row['sid'], $activeUserSids)
                    && $row['lasthit'] < $validSessionTimeLimit) {
                    $deleteSids[] = $row['sid'];
                };
            }

            if (count($deleteSids) > 0) {
                ActiveUser::query()->whereIn('sid', $deleteSids)->delete();
            }
            return;
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
        if (!$this->hasPermission('display_locks')) {
            return 0;
        }

        if ($sid == $this->sid) {
            return 1;
        }

        if ($this->hasPermission('remove_locks')) {
            return 3;
        }

        return 2;
    }

    /**
     * Locks an element
     *
     * @param int $type Types: 1=template, 2=tv, 3=chunk, 4=snippet, 5=plugin, 6=module, 7=resource, 8=role
     * @param int $id Element- / Resource-id
     * @return bool
     * @throws \AgelxNash\Modx\Evo\Database\Exceptions\Exception
     * @throws TableNotDefinedException
     */
    public function lockElement($type, $id)
    {
        $userId = $this->isBackend() && $_SESSION['mgrInternalKey'] ? $_SESSION['mgrInternalKey'] : 0;
        $type = (int)$type;
        $id = (int)$id;
        if (!$type || !$id || !$userId) {
            return false;
        }
        return ActiveUserLock::query()->updateOrCreate(['elementId' => $id],
            ['internalKey' => $userId, 'elementType' => $type, 'lasthit' => $this->time, 'sid' => $this->sid]);

    }

    /**
     * Unlocks an element
     *
     * @param int $type Types: 1=template, 2=tv, 3=chunk, 4=snippet, 5=plugin, 6=module, 7=resource, 8=role
     * @param int $id Element- / Resource-id
     * @param bool $includeAllUsers true = Deletes not only own user-locks
     * @return bool
     * @throws \AgelxNash\Modx\Evo\Database\Exceptions\Exception
     * @throws TableNotDefinedException
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
            return ActiveUserLock::where('internalKey', $userId)
                ->where('elementType', $type)
                ->where('elementId', $id)
                ->delete();
        }

        return \EvolutionCMS\Models\ActiveUserLock::where(['elementType' => $type, 'elementId' => $id])->delete();
    }

    /**
     * Updates table "active_user_sessions" with userid, lasthit, IP
     */
    public function updateValidatedUserSession()
    {
        if (!$this->sid) {
            return;
        }

        // Get user IP
        if (getenv('HTTP_CLIENT_IP')) {
            $_SESSION['ip'] = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $_SESSION['ip'] = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR')) {
            $_SESSION['ip'] = getenv('REMOTE_ADDR');
        } else {
            $_SESSION['ip'] = 'UNKNOWN';
        }

        // web users are stored with negative keys
        $userId = $this->getLoginUserType() == 'manager' ? $this->getLoginUserID() : -$this->getLoginUserID();
        if ($userId != false) {
            Models\ActiveUserSession::where('internalKey', $userId)->delete();
            Models\ActiveUserSession::where('sid', $this->sid)->delete();
            Models\ActiveUserSession::updateOrCreate([
                'internalKey' => $userId,
                'sid' => $this->sid,
            ], [
                'lasthit' => $this->time,
                'ip' => $_SESSION['ip'],
            ]);
        }
    }

    /**
     * Add an a alert message to the system event log
     *
     * @param int $evtid Event ID
     * @param int $type Types: 1 = information, 2 = warning, 3 = error
     * @param string $msg Message to be logged
     * @param string $source source of the event (module, snippet name, etc.)
     *                       Default: Parser
     * @throws \AgelxNash\Modx\Evo\Database\Exceptions\Exception
     * @throws \AgelxNash\Modx\Evo\Database\Exceptions\GetDataException
     * @throws InvalidFieldException
     * @throws TableNotDefinedException
     * @throws \AgelxNash\Modx\Evo\Database\Exceptions\TooManyLoopsException
     * @throws Exception
     */
    public function logEvent($evtid, $type, $msg, $source = 'Parser')
    {
        if (!$this->getDatabase()->getConnection()->getDatabaseName()) {
            return;
        }
        if (strpos($this['config']->get('database.connections.default.charset'), 'utf8') === 0 && extension_loaded('mbstring')) {
            $esc_source = mb_substr($source, 0, 50, 'UTF-8');
        } else {
            $esc_source = substr($source, 0, 50);
        }

        $LoginUserID = $this->getLoginUserID();
        if ($LoginUserID == '') {
            $LoginUserID = 0;
        }

        $evtid = (int)$evtid;
        $type = (int)$type;

        // Types: 1 = information, 2 = warning, 3 = error
        if ($type < 1) {
            $type = 1;
        } elseif ($type > 3) {
            $type = 3;
        }

        EventLog::insert(array(
            'eventid' => (int)$evtid,
            'type' => $type,
            'createdon' => $_SERVER['REQUEST_TIME'] + $this->getConfig('server_offset_time'),
            'source' => $esc_source,
            'description' => $msg,
            'user' => $LoginUserID,
            'usertype' => $this->isFrontend() ? 1 : 0
        ));

        $this->invokeEvent('OnLogEvent', array(
            'eventid' => (int)$evtid,
            'type' => $type,
            'createdon' => $_SERVER['REQUEST_TIME'] + $this->getConfig('server_offset_time'),
            'source' => $esc_source,
            'description' => $msg,
            'user' => $LoginUserID,
            'usertype' => $this->isFrontend() ? 1 : 0
        ));

        if ($this->getConfig('send_errormail', '0') != '0') {
            if ($this->getConfig('send_errormail') <= $type) {
                $this->sendmail(array(
                    'subject' => 'Evolution CMS System Error on ' . $this->getConfig('site_name'),
                    'body' => 'Source: ' . $source . ' - The details of the error could be seen in the Evolution CMS system events log.',
                    'type' => 'text'
                ));
            }
        }
    }

    /**
     * @param string|array $params
     * @param string $msg
     * @param array $files
     * @return bool
     * @throws Exception
     */
    public function sendmail($params = array(), $msg = '', $files = array())
    {
        if (\is_scalar($params)) {
            if (!Str::contains($params, '=')) {
                if (Str::contains($params, '@')) {
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
        }
        if (isset($p['sendto'])) {
            $p['to'] = $p['sendto'];
        }

        if (isset($p['to']) && preg_match('@^\d+$@', $p['to'])) {
            $userinfo = $this->getUserInfo($p['to']);
            $p['to'] = $userinfo['email'];
        }
        if (isset($p['from']) && preg_match('@^\d+$@', $p['from'])) {
            $userinfo = $this->getUserInfo($p['from']);
            $p['from'] = $userinfo['email'];
            $p['fromname'] = $userinfo['username'];
        }
        if ($msg === '' && !isset($p['body'])) {
            $p['body'] = $_SERVER['REQUEST_URI'] . "\n" . $_SERVER['HTTP_USER_AGENT'] . "\n" . $_SERVER['HTTP_REFERER'];
        } elseif (is_string($msg) && 0 < strlen($msg)) {
            $p['body'] = $msg;
        }

        $sendto = !isset($p['to']) ? $this->getConfig('emailsender') : $p['to'];
        $sendto = explode(',', $sendto);
        $mail = $this->getMail();
        foreach ($sendto as $address) {
            [$name, $address] = $mail->address_split($address);
            $mail->AddAddress($address, $name);
        }
        if (isset($p['cc'])) {
            $p['cc'] = explode(',', $p['cc']);
            foreach ($p['cc'] as $address) {
                [$name, $address] = $mail->address_split($address);
                $mail->AddCC($address, $name);
            }
        }
        if (isset($p['bcc'])) {
            $p['bcc'] = explode(',', $p['bcc']);
            foreach ($p['bcc'] as $address) {
                [$name, $address] = $mail->address_split($address);
                $mail->AddBCC($address, $name);
            }
        }
        if (isset($p['from']) && Str::contains($p['from'], '<') && substr($p['from'], -1) === '>') {
            [$p['fromname'], $p['from']] = $mail->address_split($p['from']);
        }
        $mail->setFrom(
            isset($p['from']) ? $p['from'] : $this->getConfig('emailsender'),
            isset($p['fromname']) ? $p['fromname'] : $this->getConfig('site_name')
        );
        $mail->Subject = (!isset($p['subject'])) ? $this->getConfig('emailsubject') : $p['subject'];
        $mail->Body = $p['body'];
        if (isset($p['type']) && $p['type'] === 'text') {
            $mail->IsHTML(false);
        }
        if (!is_array($files)) {
            $files = array();
        }
        foreach ($files as $f) {
            if (file_exists(MODX_BASE_PATH . $f) && is_file(MODX_BASE_PATH . $f) && is_readable(MODX_BASE_PATH . $f)) {
                $mail->AddAttachment(MODX_BASE_PATH . $f);
            }
        }

        return $mail->send();
    }

    /**
     * @param string $target
     * @param int $limit
     * @param int $trim
     * @throws InvalidFieldException
     * @throws TableNotDefinedException
     */
    public function rotate_log($target = 'event_log', $limit = 3000, $trim = 100)
    {
        if ($limit < $trim) {
            $trim = $limit;
        }

        $count = \DB::table($target)->count();
        $over = $count - $limit;
        if (0 < $over) {
            $trim = ($over + $trim);
            \DB::table($target)->take($trim)->delete();
        }
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
        return !$this->isBackend();
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
     * @throws InvalidFieldException
     * @throws TableNotDefinedException
     * @throws UnknownFetchTypeException
     */
    public function getAllChildren($id = 0, $sort = 'menuindex', $dir = 'ASC', $fields = 'id, pagetitle, description, parent, alias, menutitle')
    {
        $cacheKey = md5(print_r(func_get_args(), true));
        if (isset($this->tmpCache[__FUNCTION__][$cacheKey])) {
            return $this->tmpCache[__FUNCTION__][$cacheKey];
        }

        // modify field names to use sc. table reference
        $fields = 'site_content.' . implode(',site_content.', array_filter(array_map('trim', explode(',', $fields))));
        $sort = 'site_content.' . implode(',site_content.', array_filter(array_map('trim', explode(',', $sort))));
        // get document groups for current user
        $docgrp = $this->getUserDocGroups();
        $content = SiteContent::query()
            ->select(explode(',', $fields))
            ->where('site_content.parent', $id)
            ->groupBy('site_content.id')
            ->orderBy($sort, $dir);
        if ($this->isFrontend()) {
            $content = $content->where('site_content.privateweb', 0);
        } else {
            if ($_SESSION['mgrRole'] != 1 && is_array($docgrp) && count($docgrp) > 0) {
                $content->where(function ($query) use ($docgrp) {
                    $query->where('site_content.privatemgr', 0)
                        ->orWhereIn('document_groups.document_group', $docgrp);
                });
            }elseif($_SESSION['mgrRole'] != 1) {
                $content = $content->where('site_content.privatemgr', 0);
            }
        }
        // build query
        $resourceArray = $content->get()->toArray();
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
     * @throws InvalidFieldException
     * @throws TableNotDefinedException
     * @throws UnknownFetchTypeException
     */
    public function getActiveChildren($id = 0, $sort = 'menuindex', $dir = 'ASC', $fields = 'id, pagetitle, description, parent, alias, menutitle')
    {
        $cacheKey = md5(print_r(func_get_args(), true));
        if (isset($this->tmpCache[__FUNCTION__][$cacheKey])) {
            return $this->tmpCache[__FUNCTION__][$cacheKey];
        }

        // modify field names to use sc. table reference
        $fields = 'site_content.' . implode(',site_content.', array_filter(array_map('trim', explode(',', $fields))));
        $sort = 'site_content.' . implode(',site_content.', array_filter(array_map('trim', explode(',', $sort))));
        // get document groups for current user
        $docgrp = $this->getUserDocGroups();
        $content = SiteContent::query()
            ->select(explode(',', $fields))
            ->where('site_content.parent', $id)
            ->active()
            ->groupBy('site_content.id')
            ->orderBy($sort, $dir);
        if ($this->isFrontend()) {
            $content = $content->where('site_content.privateweb', 0);
        } else {
            if ($_SESSION['mgrRole'] != 1 && is_array($docgrp) && count($docgrp) > 0) {
                $content->where(function ($query) use ($docgrp) {
                    $query->where('site_content.privatemgr', 0)
                        ->orWhereIn('document_groups.document_group', $docgrp);
                });
            }elseif($_SESSION['mgrRole'] != 1) {
                $content = $content->where('site_content.privatemgr', 0);
            }
        }
        // build query
        $resourceArray = $content->get()->toArray();
        $this->tmpCache[__FUNCTION__][$cacheKey] = $resourceArray;
        return $resourceArray;
    }

    /**
     * getDocumentChildren
     * @param int $parentid {integer}
     * - The parent document identifier. Default: 0 (site root).
     * @param int $published {0; 1; 'all'}
     * - Document publication status. Once the parameter equals 'all', the result will be returned regardless of whether the ducuments are published or they are not. Default: 1.
     * @param int $deleted {0; 1; 'all'}
     * - Document removal status. Once the parameter equals 'all', the result will be returned regardless of whether the ducuments are deleted or they are not. Default: 0.
     * @param string $fields {comma separated string; '*'}
     * - Comma separated list of document fields to get. Default: '*' (all fields).
     * @param string $where {string}
     * - Where condition in SQL style. Should include a leading 'AND '. Default: ''.
     * @param string $sort {comma separated string}
     * - Should be a comma-separated list of field names on which to sort. Default: 'menuindex'.
     * @param string $dir {'ASC'; 'DESC'}
     * - Sort direction, ASC and DESC is possible. Default: 'ASC'.
     * @param string $limit {string}
     * - Should be a valid SQL LIMIT clause without the 'LIMIT ' i.e. just include the numbers as a string. Default: Empty string (no limit).
     *
     * @return array|mixed {array; false} - Result array, or false. - Result array, or false.
     * @throws InvalidFieldException
     * @throws TableNotDefinedException
     * @throws UnknownFetchTypeException
     * @version 1.1.1 (2014-02-19)
     *
     * @desc Returns the children of the selected document/folder as an associative array.
     *
     */
    public function getDocumentChildren(
        $parentid = 0,
        $published = 1,
        $deleted = 0,
        $fields = '*',
        $where = '',
        $sort = 'menuindex',
        $dir = 'ASC',
        $limit = ''
    )
    {
        $cacheKey = md5(print_r(func_get_args(), true));
        if (isset($this->tmpCache[__FUNCTION__][$cacheKey])) {
            return $this->tmpCache[__FUNCTION__][$cacheKey];
        }

        $documentChildren = SiteContent::query()->where('site_content.parent', $parentid);
        if ($published !== 'all') {
            $documentChildren = $documentChildren->where('site_content.published', $published);
        }
        if ($deleted !== 'all') {
            $documentChildren = $documentChildren->where('site_content.deleted', $deleted);
        }

        if (is_string($where) && $where != '') {
            $documentChildren = $documentChildren->whereRaw($where);
        } elseif (is_array($where)) {
            $documentChildren = $documentChildren->where($where);
        }
        if (!is_array($fields)) {
            $documentChildren = $documentChildren->select(explode(',', $fields));
        }
        // modify field names to use sc. table reference
        if ($sort != '') {
            $sort = explode(',', $sort);
            foreach ($sort as $item)
                $documentChildren = $documentChildren->orderBy($item, $dir);
        }

        // get document groups for current user
        $docgrp = $this->getUserDocGroups();

        // build query

        if ($this->isFrontend()) {
            if (!$docgrp) {
                $documentChildren = $documentChildren->where('privatemgr', 0);
            } else {
                $documentChildren = $documentChildren->where(function ($query) use ($docgrp) {
                    $query->where('privatemgr', 0)
                        ->orWhereIn('document_groups.document_group', $docgrp);
                });
            }
        } else {
            if ($_SESSION['mgrRole'] != 1) {
                if (!$docgrp) {
                    $documentChildren = $documentChildren->where('privatemgr', 0);
                } else {
                    $documentChildren = $documentChildren->where(function ($query) use ($docgrp) {
                        $query->where('privatemgr', 0)
                            ->orWhereIn('document_groups.document_group', $docgrp);
                    });
                }
            }
        }

        if (is_numeric($limit)) {
            $documentChildren = $documentChildren->take($limit);
        }
        $resourceArray = $documentChildren->get()->toArray();

        $this->tmpCache[__FUNCTION__][$cacheKey] = $resourceArray;

        return $resourceArray;
    }

    /**
     * getDocuments
     * @param array $ids {array; comma separated string}
     * - Documents Ids to get. @required
     * @param int $published {0; 1; 'all'}
     * - Documents publication status. Once the parameter equals 'all', the result will be returned regardless of whether the documents are published or they are not. Default: 1.
     * @param int $deleted {0; 1; 'all'}
     * - Documents removal status. Once the parameter equals 'all', the result will be returned regardless of whether the documents are deleted or they are not. Default: 0.
     * @param string $fields {comma separated string; '*'}
     * - Documents fields to get. Default: '*'.
     * @param string $where {string}
     * - SQL WHERE clause. Default: ''.
     * @param string $sort {comma separated string}
     * - A comma-separated list of field names to sort by. Default: 'menuindex'.
     * @param string $dir {'ASC'; 'DESC'}
     * - Sorting direction. Default: 'ASC'.
     * @param string $limit {string}
     * - SQL LIMIT (without 'LIMIT '). An empty string means no limit. Default: ''.
     *
     * @return array|bool|mixed {array; false} - Result array with documents, or false. - Result array with documents, or false.
     * @throws InvalidFieldException
     * @throws TableNotDefinedException
     * @throws UnknownFetchTypeException
     * @version 1.1.1 (2013-02-19)
     *
     * @desc Returns required documents (their fields).
     *
     */
    public function getDocuments(
        $ids = array(),
        $published = 1,
        $deleted = 0,
        $fields = '*',
        $where = '',
        $sort = 'menuindex',
        $dir = 'ASC',
        $limit = ''
    )
    {

        $cacheKey = md5(print_r(func_get_args(), true));
        if (isset($this->tmpCache[__FUNCTION__][$cacheKey])) {
            return $this->tmpCache[__FUNCTION__][$cacheKey];
        }

        $documentChildren = SiteContent::query()->whereIn('site_content.id', $ids);
        if ($published !== 'all') {
            $documentChildren = $documentChildren->where('site_content.published', $published);
        }
        if ($deleted !== 'all') {
            $documentChildren = $documentChildren->where('site_content.deleted', $deleted);
        }

        if (is_string($where) && $where != '') {
            $documentChildren = $documentChildren->whereRaw($where);
        } elseif (is_array($where)) {
            $documentChildren = $documentChildren->where($where);
        }
        if (!is_array($fields)) {
            $arr = explode(',', $fields);
            $new_arr = [];
            foreach ($arr as $item) {
                if (stristr($item, '.') === false) {
                    $new_arr[] = 'site_content.' . $item;
                }else
                    $new_arr[] = $item;

            }
            $documentChildren = $documentChildren->select($new_arr);
        }
        // modify field names to use sc. table reference
        if ($sort != '') {
            $sort = explode(',', $sort);
            foreach ($sort as $item)
                $documentChildren = $documentChildren->orderBy($item, $dir);
        }

        // get document groups for current user
        $docgrp = $this->getUserDocGroups();

        // build query

        if ($this->isFrontend()) {
            if (!$docgrp) {
                $documentChildren = $documentChildren->where('privatemgr', 0);
            } else {
                $documentChildren = $documentChildren->leftJoin('document_groups', 'site_content.id', '=', 'document_groups.document');
                $documentChildren = $documentChildren->where(function ($query) use ($docgrp) {
                    $query->where('privatemgr', 0)
                        ->orWhereIn('document_groups.document_group', $docgrp);
                });
            }
        } else {
            if (isset($_SESSION['mgrRole']) && $_SESSION['mgrRole'] != 1) {
                if (!$docgrp) {
                    $documentChildren = $documentChildren->where('privatemgr', 0);
                } else {
                    $documentChildren = $documentChildren->leftJoin('document_groups', 'site_content.id', '=', 'document_groups.document');
                    $documentChildren = $documentChildren->where(function ($query) use ($docgrp) {
                        $query->where('privatemgr', 0)
                            ->orWhereIn('document_groups.document_group', $docgrp);
                    });
                }
            }
        }

        if (is_numeric($limit)) {
            $documentChildren = $documentChildren->take($limit);
        }
        $resourceArray = $documentChildren->get()->toArray();

        $this->tmpCache[__FUNCTION__][$cacheKey] = $resourceArray;

        return $resourceArray;
    }

    /**
     * getDocument
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
     * @throws InvalidFieldException
     * @throws TableNotDefinedException
     * @throws UnknownFetchTypeException
     * @version 1.0.1 (2014-02-19)
     *
     * @desc Returns required fields of a document.
     *
     */
    public function getDocument($id = 0, $fields = '*', $published = 1, $deleted = 0)
    {
        if ($id == 0) {
            return false;
        }

        $docs = $this->getDocuments(array($id), $published, $deleted, $fields, '', '', '', 1);

        if ($docs != false) {
            return $docs[0];
        }

        return false;
    }

    /**
     * @param string $field
     * @param string $docid
     * @return bool|mixed
     * @throws InvalidFieldException
     * @throws TableNotDefinedException
     * @throws \AgelxNash\Modx\Evo\Database\Exceptions\Exception
     */
    public function getField($field = 'content', $docid = '')
    {
        if (empty($docid) && isset($this->documentIdentifier)) {
            $docid = $this->documentIdentifier;
        } elseif (!preg_match('@^\d+$@', $docid)) {
            $docid = UrlProcessor::getIdFromAlias($docid);
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
     * @throws InvalidFieldException
     * @throws TableNotDefinedException
     * @throws \AgelxNash\Modx\Evo\Database\Exceptions\Exception
     */
    public function getPageInfo($pageid = -1, $active = 1, $fields = 'site_content.id, site_content.pagetitle, site_content.description, site_content.alias')
    {

        $cacheKey = md5(print_r(func_get_args(), true));
        if (isset($this->tmpCache[__FUNCTION__][$cacheKey])) {
            return $this->tmpCache[__FUNCTION__][$cacheKey];
        }

        if ($pageid == 0) {
            return false;
        }

        // get document groups for current user
        if ($docgrp = $this->getUserDocGroups()) {
            $docgrp = implode(",", $docgrp);
        }
        $fields = array_filter(array_map('trim', explode(',', $fields)));
        foreach ($fields as $key => $value) {
            if (stristr($value, '.') === false) {
                $fields[$key] = 'site_content.' . $value;
            }
        }
        $pageInfo = SiteContent::query()->select($fields)
            ->leftJoin('document_groups', 'document_groups.document', '=', 'site_content.id')
            ->where('site_content.id', $pageid);
        if ($active == 1) {
            $pageInfo = $pageInfo->where('site_content.published', 1)->where('site_content.deleted', 0);
        }
        if ($docgrp = $this->getUserDocGroups() && $_SESSION['mgrRole'] != 1) {
            if ($this->isFrontend()) {
                $pageInfo = $pageInfo->where('site_content.privatemgr', 0);
            } else {
                $pageInfo = $pageInfo->where(function ($query) use ($docgrp) {
                    $query->where('site_content.privatemgr', '=', 0)
                        ->orWhereIn('document_groups.document_group', $docgrp);
                });
            }
        }
        $pageInfo = $pageInfo->first();
        if (!is_null($pageInfo)) {
            $pageInfo = $pageInfo->toArray();
        }


        $this->tmpCache[__FUNCTION__][$cacheKey] = $pageInfo;

        return $pageInfo;
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
     * @throws InvalidFieldException
     * @throws TableNotDefinedException
     * @throws \AgelxNash\Modx\Evo\Database\Exceptions\Exception
     */
    public function getParent($pid = -1, $active = 1, $fields = 'id, pagetitle, description, alias, parent')
    {
        if ($pid == -1) {
            $pid = $this->documentObject['parent'];

            return ($pid == 0) ? false : $this->getPageInfo($pid, $active, $fields);
        }

        if ($pid == 0) {
            return false;
        }

        // first get the child document
        $child = $this->getPageInfo($pid, $active, "parent");
        // now return the child's parent
        $pid = ($child['parent']) ? $child['parent'] : 0;

        return ($pid == 0) ? false : $this->getPageInfo($pid, $active, $fields);
    }

    /**
     * Returns the id of the current snippet.
     *
     * @return int
     * @throws InvalidFieldException
     * @throws TableNotDefinedException
     * @throws \AgelxNash\Modx\Evo\Database\Exceptions\Exception
     */
    public function getSnippetId()
    {
        if ($this->currentSnippet) {
            $snippetId = \EvolutionCMS\Models\SiteSnippet::select('id')->where('name', $this->currentSnippet)->first()->id;
            if ($snippetId) {
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
     * @return void
     */
    public function clearCache($type = '', $report = false)
    {
        $cache_dir = $this->bootstrapPath();

        /*$this['command.view.clear']->handle();*/
        $path = $this['config']['view.compiled'];
        if ($path) {
            foreach ($this['files']->glob("{$path}/*") as $view) {
                $this['files']->delete($view);
            }
        }

        if (is_array($type)) {
            foreach ($type as $_) {
                $this->clearCache($_, $report);
            }
        } elseif ($type === 'full') {
            $sync = new Legacy\Cache();
            $sync->setCachepath($cache_dir);
            $sync->setReport($report);
            $sync->emptyCache();
        } elseif (preg_match('@^[1-9]\d*$@', $type)) {
            $key = ($this->getConfig('cache_type') == 2) ? $this->makePageCacheKey($type) : $type;
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
     * @deprecated use UrlProcessor::makeUrl()
     */
    public function makeUrl($id, $alias = '', $args = '', $scheme = '')
    {
        return UrlProcessor::makeUrl((int)$id, $alias, $args, $scheme);
    }

    /**
     * @deprecated use UrlProcessor::getAliasListing()
     */
    public function getAliasListing($id)
    {
        return UrlProcessor::getAliasListing($id);
    }

    /**
     * Returns the Evolution CMS version information as version, branch, release date and full application name.
     *
     * @param null $data
     * @return string|array
     */

    public function getVersionData($data = null)
    {
        if (empty($this->version) || !is_array($this->version)) {
            //include for compatibility modx version < 1.0.10
            $version = include EVO_CORE_PATH . 'factory/version.php';
            $this->version = $version;
            $this->version['new_version'] = $this->getConfig('newversiontext', '');
        }
        return ($data !== null && \is_array($this->version) && isset($this->version[$data])) ?
            $this->version[$data] : $this->version;
    }

    /**
     * Executes a snippet.
     *
     * @param string $snippetName
     * @param array $params Default: Empty array
     * @param int $cacheTime
     * @param string $cacheKey
     * @return string
     */
    public function runSnippet($snippetName, $params = array(), $cacheTime = false, $cacheKey = false)
    {
        if (
            is_numeric($cacheTime)
            && $this->getConfig('enable_cache')
        ) {
            $arrPlaceholderCheck = $this->placeholders;
            if (!is_string($cacheKey)) {
                $getParams = $_GET;
                ksort($getParams);
                ksort($params);
                $cacheKey = md5(json_encode($getParams) . $snippetName . json_encode($params));
            }
            $return = Cache::get($cacheKey);
            if (!is_null($return)) {
                $arrPlaceholderFromSnippet = Cache::get($cacheKey . '_placeholders');
                $this->toPlaceholders($arrPlaceholderFromSnippet);
                return $return;
            }
        }
        if (array_key_exists($snippetName, $this->snippetCache)) {
            $snippet = $this->snippetCache[$snippetName];
            $properties = !empty($this->snippetCache[$snippetName . "Props"]) ? $this->snippetCache[$snippetName . "Props"] : '';
        } else { // not in cache so let's check the db
            $snippetObject = $this->getSnippetFromDatabase($snippetName);
            if ($snippetObject['content'] === null) {
                $snippet = $this->snippetCache[$snippetName] = "return false;";
            } else {
                $snippet = $this->snippetCache[$snippetName] = $snippetObject['content'];
            }
            $properties = $this->snippetCache[$snippetName . "Props"] = $snippetObject['properties'];
        }
        // load default params/properties
        $parameters = $this->parseProperties($properties, $snippetName, 'snippet');
        $parameters = array_merge($parameters, $params);

        // run snippet
        $result = $this->evalSnippet($snippet, $parameters);
        if (
            is_numeric($cacheTime)
            && $this->getConfig('enable_cache')
        ) {
            if ($cacheTime != 0) {
                Cache::put($cacheKey, $result, $cacheTime);
            } else {
                Cache::forever($cacheKey, $result);
            }

            if (!empty($this->placeholders)) {
                $arrPlaceholderCheckAfterSnippet = $this->placeholders;
                $arrPlaceholderFromSnippet = array_diff($arrPlaceholderCheckAfterSnippet, $arrPlaceholderCheck);

                if ($cacheTime != 0) {
                    Cache::put($cacheKey . '_placeholders', $arrPlaceholderFromSnippet, $cacheTime);
                } else {
                    Cache::forever($cacheKey . '_placeholders', $arrPlaceholderFromSnippet);
                }
            }
        }
        return $result;
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
            $out = app('DLTemplate')->getChunk($chunkName);
        } elseif (isset ($this->chunkCache[$chunkName])) {
            $out = $this->chunkCache[$chunkName];
        } elseif (stripos($chunkName, '@FILE') === 0) {
            $out = $this->chunkCache[$chunkName] = $this->atBindFileContent($chunkName);
        } else {
            $out = app('DLTemplate')->getBaseChunk($chunkName);
        }
        return $out;
    }

    /**
     * @param string|object $processor
     * @return bool
     */
    public function isChunkProcessor($processor)
    {
        $value = (string)$this->getConfig('chunk_processor');
        if (is_object($processor)) {
            $processor = get_class($processor);
        }
        return is_scalar($processor) && mb_strtolower($value) === mb_strtolower($processor) && class_exists($processor, false);
    }

    /**
     * parseText
     * @param string $tpl
     * @param array $ph
     * @param string $left
     * @param string $right
     * @param bool $execModifier
     * @return string {string} - Parsed text.
     * - Parsed text.
     * @version 1.0 (2013-10-17)
     *
     * @desc Replaces placeholders in text with required values.
     *
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

        if ($this->getConfig('enable_at_syntax')) {
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
                [$key, $modifiers] = $this->splitKeyAndFilter($key);
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
            } elseif ($this->debug) {
                $this->addLog('parseText parse error', $_SERVER['REQUEST_URI'] . $s, 2);
            }
        }

        return $tpl;
    }

    /**
     * parseChunk
     * @param $chunkName {string} - Name of chunk to parse. @required
     * @param $chunkArr {array} - Array of values. Key — placeholder name, value — value. @required
     * @param string $prefix {string}
     * - Placeholders prefix. Default: '{'.
     * @param string $suffix {string}
     * - Placeholders suffix. Default: '}'.
     * @return bool|mixed|string {string; false} - Parsed chunk or false if $chunkArr is not array.
     * - Parsed chunk or false if $chunkArr is not array.
     * @version 1.1 (2013-10-17)
     *
     * @desc Replaces placeholders in a chunk with required values.
     *
     */
    public function parseChunk($chunkName, $chunkArr, $prefix = '{', $suffix = '}')
    {
        //TODO: Wouldn't it be more practical to return the contents of a chunk instead of false?
        if (!is_array($chunkArr)) {
            return false;
        }

        return $prefix === '[+' && $suffix === '+]' && $this->isChunkProcessor('DLTemplate') ?
            app('DLTemplate')->parseChunk($chunkName, $chunkArr) :
            $this->parseText($this->getChunk($chunkName), $chunkArr, $prefix, $suffix);
    }

    /**
     * getTpl
     * get template for snippets
     * @param $tpl {string}
     * @return bool|string {string}
     * @throws InvalidFieldException
     * @throws TableNotDefinedException
     * @throws UnknownFetchTypeException
     * @throws \AgelxNash\Modx\Evo\Database\Exceptions\Exception
     */
    public function getTpl($tpl)
    {
        $template = $tpl;
        $command = '';
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
                $this->getDatabase()->getValue($this->getDatabase()->query("SELECT {$template}"));
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

        switch ($this->getConfig('datetime_format')) {
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
        } elseif ($mode === 'dateOnly') {
            $strTime = strftime($dateFormat, $timestamp);
        } elseif ($mode === 'formatOnly') {
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

        switch ($this->getConfig('datetime_format')) {
            case 'YYYY/mm/dd':
                if (!preg_match('/^\d{4}\/\d{2}\/\d{2}[\d :]*$/', $str)) {
                    return '';
                }
                [$Y, $m, $d, $H, $M, $S] = sscanf($str, '%4d/%2d/%2d %2d:%2d:%2d');
                break;
            case 'dd-mm-YYYY':
                if (!preg_match('/^\d{2}-\d{2}-\d{4}[\d :]*$/', $str)) {
                    return '';
                }
                [$d, $m, $Y, $H, $M, $S] = sscanf($str, '%2d-%2d-%4d %2d:%2d:%2d');
                break;
            case 'mm/dd/YYYY':
                if (!preg_match('/^\d{2}\/\d{2}\/\d{4}[\d :]*$/', $str)) {
                    return '';
                }
                [$m, $d, $Y, $H, $M, $S] = sscanf($str, '%2d/%2d/%4d %2d:%2d:%2d');
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
     * @throws InvalidFieldException
     * @throws TableNotDefinedException
     * @throws UnknownFetchTypeException
     */
    public function getDocumentChildrenTVars($parentid = 0, $tvidnames = array(), $published = 1, $docsort = "menuindex", $docsortdir = "ASC", $tvfields = "*", $tvsort = "rank", $tvsortdir = "ASC")
    {
        $docs = $this->getDocumentChildren($parentid, $published, 0, '*', '', $docsort, $docsortdir);
        if (!$docs) {
            return false;
        }

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
        if ($tvidnames === "*") {
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

    /**
     * getDocumentChildrenTVarOutput
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
     * @return array|bool
     * - Result array, or false.
     * @throws InvalidFieldException
     * @throws TableNotDefinedException
     * @throws UnknownFetchTypeException
     * @version 1.1 (2014-02-19)
     *
     * @desc Returns an array where each element represents one child doc and contains the result from getTemplateVarOutput().
     */
    public function getDocumentChildrenTVarOutput($parentid = 0, $tvidnames = array(), $published = 1, $sortBy = 'menuindex', $sortDir = 'ASC', $where = '', $resultKey = 'id')
    {
        $docs = $this->getDocumentChildren($parentid, $published, 0, 'id', $where, $sortBy, $sortDir);

        if (!$docs) {
            return false;
        }

        $result = array();

        $unsetResultKey = false;

        if ($resultKey !== false) {
            if (is_array($tvidnames)) {
                if (count($tvidnames) != 0 && !in_array($resultKey, $tvidnames)) {
                    $tvidnames[] = $resultKey;
                    $unsetResultKey = true;
                }
            } else if ($tvidnames !== '*' && $tvidnames != $resultKey) {
                $tvidnames = array($tvidnames, $resultKey);
                $unsetResultKey = true;
            }
        }

        foreach ($docs as $iValue) {
            $tvs = $this->getTemplateVarOutput($tvidnames, $iValue['id'], $published);

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
        }

        $result = $this->getTemplateVars(array($idname), $fields, $docid, $published, "", ""); //remove sorting for speed
        return ($result != false) ? $result[0] : false;
    }

    /**
     * getTemplateVars
     * @param string|array $idnames {array; '*'} - Which TVs to fetch. Can relate to the TV ids in the db (array elements should be numeric only) or the TV names (array elements should be names only). @required
     * @param string|array $fields {comma separated string; '*'} - Fields names in the TV table of MODx database. Default: '*'
     * @param int|string $docid {integer; ''} - Id of a document to get. Default: an empty string which indicates the current document.
     * @param int|string $published {0; 1; 'all'} - Document publication status. Once the parameter equals 'all', the result will be returned regardless of whether the ducuments are published or they are not. Default: 1.
     * @param string $sort {comma separated string} - Fields of the TV table to sort by. Default: 'rank'.
     * @param string $dir {'ASC'; 'DESC'} - How to sort the result array (direction). Default: 'ASC'.
     *
     * @return array|bool Result array, or false.
     * @throws InvalidFieldException
     * @throws TableNotDefinedException
     * @throws UnknownFetchTypeException
     * @version 1.0.1 (2014-02-19)
     *
     * @desc Returns an array of site_content field fields and/or TV records from the db.
     * Elements representing a site content field consist of an associative array of 'name' and 'value'.
     * Elements representing a TV consist of an array representing a db row including the fields specified in $fields.
     *
     */
    public function getTemplateVars($idnames = array(), $fields = '*', $docid = '', $published = 1, $sort = 'rank', $dir = 'ASC')
    {
        static $cached = array();
        $cacheKey = md5(print_r(func_get_args(), true));
        if (isset($cached[$cacheKey])) {
            return $cached[$cacheKey];
        }
        $cached[$cacheKey] = false;

        if (($idnames !== '*' && !is_array($idnames)) || empty($idnames)) {
            return false;
        }

        // get document record
        if (empty($docid)) {
            $docid = $this->documentIdentifier;
            $docRow = $this->documentObject;
        } else {
            $docRow = $this->getDocument($docid, '*', $published);

            if (!$docRow) {
                $cached[$cacheKey] = false;
                return false;
            }
        }
        $table = $this->getDatabase()->getFullTableName('site_tmplvars');
        // get user defined template variables
        if (!empty($fields) && (is_scalar($fields) || \is_array($fields))) {
            if (\is_scalar($fields)) {
                $fields = explode(',', $fields);
            }
            $fields = array_filter(array_map('trim', $fields), function ($value) {
                return $value !== 'value';
            });
        } else {
            $fields = ['*'];
        }
        $sort = ($sort == '') ? '' : $table . '.' . implode(',' . $table . '.', array_filter(array_map('trim', explode(',', $sort))));

        if ($idnames === '*') {
            $query = '' . $table . '.id<>0';
        } else {
            $query = (is_numeric($idnames[0]) ? '' . $table . '.id' : '' . $table . '.name') . " IN ('" . implode("','", $idnames) . "')";
        }

        $rs = SiteTmplvar::query()
            ->select($fields)
            ->selectRaw(" IF(" . $this->getDatabase()->getConfig('prefix') . "site_tmplvar_contentvalues.value != '', " . $this->getDatabase()->getConfig('prefix') . "site_tmplvar_contentvalues.value, " . $this->getDatabase()->getConfig('prefix') . "site_tmplvars.default_text) as value")
            ->join('site_tmplvar_templates', 'site_tmplvar_templates.tmplvarid', '=', 'site_tmplvars.id')
            ->leftJoin('site_tmplvar_contentvalues', function ($join) use ($docid) {
                $join->on('site_tmplvar_contentvalues.tmplvarid', '=', 'site_tmplvars.id');
                $join->on('site_tmplvar_contentvalues.contentid', '=', \DB::raw($docid));
            })
            ->whereRaw($query . " AND " . $this->getDatabase()->getConfig('prefix') . "site_tmplvar_templates.templateid = '" . $docRow['template'] . "'");
        if ($sort != '') {
            $rs = $rs->orderByRaw($sort);
        }
        $rs = $rs->get();

        $result = $rs->toArray();

        // get default/built-in template variables
        if (is_array($docRow)) {
            ksort($docRow);

            foreach ($docRow as $name => $value) {
                if ($idnames === '*' || \in_array($name, $idnames)) {
                    $result[] = compact('name', 'value');
                }
            }
        }

        $cached[$cacheKey] = $result;

        return $result;
    }

    /**
     * getTemplateVarOutput
     * @param array $idnames {array; '*'}
     * - Which TVs to fetch - Can relate to the TV ids in the db (array elements should be numeric only) or the TV names (array elements should be names only). @required
     * @param string $docid {integer; ''}
     * - Id of a document to get. Default: an empty string which indicates the current document.
     * @param int $published {0; 1; 'all'}
     * - Document publication status. Once the parameter equals 'all', the result will be returned regardless of whether the ducuments are published or they are not. Default: 1.
     * @param string $sep {string}
     * - Separator that is used while concatenating in getTVDisplayFormat(). Default: ''.
     * @return array|bool - Result array, or false.
     * - Result array, or false.
     * @throws InvalidFieldException
     * @throws TableNotDefinedException
     * @throws UnknownFetchTypeException
     * @version 1.0.1 (2014-02-19)
     *
     * @desc Returns an associative array containing TV rendered output values.
     */
    public function getTemplateVarOutput($idnames = array(), $docid = '', $published = 1, $sep = '')
    {
        if (is_array($idnames) && empty($idnames)) {
            return false;
        }

        $output = array();
        $vars = ($idnames === '*' || is_array($idnames)) ? $idnames : array($idnames);

        if ((int)$docid > 0) {
            $docid = (int)$docid;
        } else {
            $docid = $this->documentIdentifier;
        }
        // remove sort for speed
        $result = $this->getTemplateVars($vars, '*', $docid, $published, '', '');

        if ($result == false) {
            return false;
        }

        foreach ($result as $iValue) {
            $row = $iValue;

            if (!isset($row['id']) or !$row['id']) {
                $output[$row['name']] = $row['value'];
            } else {
                $output[$row['name']] = getTVDisplayFormat($row['name'], $row['value'], $row['display'], $row['display_params'], $row['type'], $docid, $sep);
            }
        }

        return $output;
    }

    /**
     * Returns the full table name based on db settings
     *
     * @param string $tbl Table name
     * @return string Table name with prefix
     * @deprecated use ->getDatabase()->getFullTableName()
     */
    public function getFullTableName($tbl)
    {
        return $this->getDatabase()->getFullTableName($tbl);
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
     * Returns current user id.
     *
     * @param string $context . Default is an empty string which indicates the method should automatically pick 'web (frontend) or 'mgr' (backend)
     * @return bool|int
     */
    public function getLoginUserID($context = '')
    {
        $out = false;
        if (empty($context)) {
            $context = $this->getContext();
        }
        if (isset ($_SESSION[$context . 'Validated'])) {
            $out = $_SESSION[$context . 'InternalKey'];
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
        if (empty($context)) {
            $context = $this->getContext();
        }
        if (isset ($_SESSION[$context . 'Validated'])) {
            $out = $_SESSION[$context . 'Shortname'];
        }
        return $out;
    }

    /**
     * Returns current login user type - web or manager
     *
     * @return string
     */
    public function getLoginUserType($context = '')
    {
        if (empty($context)) {
            $context = $this->getContext();
        }
        if ($context == 'mgr' && isset ($_SESSION['mgrValidated'])) {
            return 'manager';
        } elseif ($context == 'web' && isset($_SESSION['webValidated'])) {
            return 'web';
        }

        return '';
    }

    /**
     * @return string
     */
    public function getContext(): string
    {
        if (empty($this->context)) {
            $out = $this->isFrontend() ? 'web' : 'mgr';
        } else {
            $out = $this->context;
        }

        return $out;
    }

    /**
     * @param $context
     */
    public function setContext($context)
    {
        if (is_scalar($context)) {
            $this->context = $context;
        }
    }

    /**
     * Returns a user info record for the given manager user
     *
     * @param int $uid
     * @return boolean|string
     * @throws InvalidFieldException
     * @throws TableNotDefinedException
     * @throws \AgelxNash\Modx\Evo\Database\Exceptions\Exception
     */
    public function getUserInfo($uid)
    {
        if (isset($this->tmpCache[__FUNCTION__][$uid])) {
            return $this->tmpCache[__FUNCTION__][$uid];
        }

        $row = User::select('users.username', 'users.password', 'user_attributes.*')
            ->join('user_attributes', 'users.id', '=', 'user_attributes.internalKey')
            ->where('users.id', $uid)->first();


        if (is_null($row)) {
            return $this->tmpCache[__FUNCTION__][$uid] = false;
        }

        $row = $row->toArray();

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
     * @throws InvalidFieldException
     * @throws TableNotDefinedException
     * @throws \AgelxNash\Modx\Evo\Database\Exceptions\Exception
     */
    public function getWebUserInfo($uid)
    {
        return $this->getUserInfo($uid);
    }

    /**
     * Returns an array of document groups that current user is assigned to.
     * This function will first return the web user doc groups when running from
     * frontend otherwise it will return manager user's docgroup.
     *
     * @param boolean $resolveIds Set to true to return the document group names
     *                            Default: false
     * @return string|array
     * @throws InvalidFieldException
     * @throws TableNotDefinedException
     * @throws \AgelxNash\Modx\Evo\Database\Exceptions\Exception
     */
    public function getUserDocGroups($resolveIds = false)
    {
        $context = $this->getContext();
        if (isset($_SESSION[$context . 'Docgroups']) && isset($_SESSION[$context . 'Validated'])) {
            $dg = $_SESSION[$context . 'Docgroups'];
            $dgn = isset($_SESSION[$context . 'DocgrpNames']) ? $_SESSION[$context . 'DocgrpNames'] : false;
        } else {
            $dg = '';
        }
        if (!$resolveIds) {
            return $dg;
        }

        if (is_array($dgn)) {
            return $dgn;
        }

        if (is_array($dg)) {
            // resolve ids to names
            $dgn = array();
            $ds = \EvolutionCMS\Models\DocumentgroupName::select('name')
                ->whereIn('id', $dg)
                ->get();

            foreach ($ds as $row) {
                $dgn[] = $row->name;
            }
            // cache docgroup names to session
            $_SESSION[$context . 'DocgrpNames'] = $dgn;

            return $dgn;
        }
    }

    /**
     * Change current web user's password
     *
     * @param string $oldPwd
     * @param string $newPwd
     * @return string|boolean Returns true if successful, oterhwise return error
     *                        message
     * @throws InvalidFieldException
     * @throws TableNotDefinedException
     * @throws \AgelxNash\Modx\Evo\Database\Exceptions\Exception
     * @todo Make password length configurable, allow rules for passwords and translation of messages
     */
    public function changeWebUserPassword($oldPwd, $newPwd)
    {
        if ($_SESSION['webValidated'] != 1) {
            return false;
        }
        $ds = \EvolutionCMS\Models\User::selectRaw('id, username, password')
            ->where('id', (int)$this->getLoginUserID())
            ->first();

        $row = $ds->toArray();
        if (!$row) {
            return false;
        }

        if ($row['password'] !== md5($oldPwd)) {
            return 'Incorrect password.';
        }
        if (strlen($newPwd) < 6) {
            return 'Password is too short!';
        }

        if ($newPwd == '') {
            return "You didn't specify a password for this user!";
        }

        \EvolutionCMS\Models\User::where('id', (int)$this->getLoginUserID())
            ->update(array(
                'password' => $newPwd
            ));

        // invoke OnWebChangePassword event
        $this->invokeEvent('OnWebChangePassword', array(
            'userid' => $row['id'],
            'username' => $row['username'],
            'userpassword' => $newPwd
        ));
        return true;
    }

    /**
     * Returns true if the current web user is a member the specified groups
     *
     * @param array $groupNames
     * @return boolean
     * @throws InvalidFieldException
     * @throws TableNotDefinedException
     */
    public function isMemberOfWebGroup($groupNames = array())
    {
        if (!is_array($groupNames)) {
            return false;
        }

        $grpNames = isset ($_SESSION['mgrUserGroupNames']) ? $_SESSION['mgrUserGroupNames'] : false;
        if (!is_array($grpNames)) {
            $grpNames = MembergroupName::query()
                ->join('member_groups', 'membergroup_names.id', '=', 'member_groups.user_group')
                ->where('member_groups.member', $this->getLoginUserID())
                ->pluck('membergroup_names.name')->toArray();

            // save to cache
            $_SESSION['webUserGroupNames'] = $grpNames;
        }
        foreach ($groupNames as $k => $v) {
            if (in_array(trim($v), $grpNames, true)) {
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
     * @return void
     */
    public function regClientCSS($src, $media = '')
    {
        if (empty($src) || isset ($this->loadedjscripts[$src])) {
            return;
        }
        $nextpos = max(array_merge(array(0), array_keys($this->sjscripts))) + 1;
        $this->loadedjscripts[$src]['startup'] = true;
        $this->loadedjscripts[$src]['version'] = '0';
        $this->loadedjscripts[$src]['pos'] = $nextpos;
        if (stripos($src, '<style') !== false || stripos($src, '<link') !== false) {
            $this->sjscripts[$nextpos] = $src;
        } else {
            $this->sjscripts[$nextpos] = sprintf(
                "\t" . '<link rel="stylesheet" type="text/css" href="%s" %s/>'
                , $src
                , $media ? sprintf('media="%s" ', $media) : ''
            );
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
     * @return void
     */
    public function regClientScript($src, $options = array('name' => '', 'version' => '0', 'plaintext' => false), $startup = false)
    {
        if (empty($src)) {
            return;
        } // nothing to register
        if (!is_array($options)) {
            if (\is_bool($options)) {
                // backward compatibility with old plaintext parameter
                $options = array('plaintext' => $options);
            } elseif (\is_string($options)) {
                // Also allow script name as 2nd param
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
                if ($startup != true || $this->loadedjscripts[$key]['startup'] != false) {
                    return; // the script is already in the right place
                }

                // need to move the exisiting script to the head
                $version = $this->loadedjscripts[$key][$version];
                $src = $this->jscripts[$this->loadedjscripts[$key]['pos']];
                unset($this->jscripts[$this->loadedjscripts[$key]['pos']]);
            }
        }

        if ($useThisVer && $plaintext != true && (stripos($src, "<script") === false)) {
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
    }

    /**
     * Returns all registered JavaScripts
     *
     * @return void
     */
    public function regClientStartupHTMLBlock($html)
    {
        $this->regClientScript($html, true, true);
    }

    /**
     * Returns all registered startup scripts
     *
     * @return void
     */
    public function regClientHTMLBlock($html)
    {
        $this->regClientScript($html, true);
    }

    /**
     * Remove unwanted html tags and snippet, settings and tags
     *
     * @param string $html
     * @param string $allowed Default: Empty string
     * @return string
     */
    public function stripTags($html, $allowed = '')
    {
        $t = strip_tags($html, $allowed);
        $t = preg_replace('~\[\*(.*?)\*\]~', '', $t); //tv
        $t = preg_replace('~\[\[(.*?)\]\]~', '', $t); //snippet
        $t = preg_replace('~\[\!(.*?)\!\]~', '', $t); //snippet
        $t = preg_replace('~\[\((.*?)\)\]~', '', $t); //settings
        $t = preg_replace('~\[\+(.*?)\+\]~', '', $t); //placeholders
        $t = preg_replace('~{{(.*?)}}~', '', $t); //chunks
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
     * @return void
     */
    public function removeEventListener($evtName)
    {
        if (!$evtName) {
            return;
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

    protected function restoreEvent()
    {
        $event = $this->event->getPreviousEvent();
        if ($event) {
            unset($this->event);
            $this->event = $event;
            $this->Event = &$this->event;
        } else {
            $this->event->activePlugin = '';
        }

        return $event;
    }

    protected function storeEvent()
    {
        if ($this->event->activePlugin !== '') {
            $event = new Event;
            $event->setPreviousEvent($this->event);
            $this->event = $event;
            $this->Event = &$this->event;
        } else {
            $event = $this->event;
        }

        return $event;
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
        $results = null;

        if (!$evtName) {
            return false;
        }

        $out = $this['events']->dispatch('evolution.' . $evtName, [$extParams]);
        if ($out === false) {
            return false;
        }

        if (\is_array($out)) {
            foreach ($out as $result) {
                if ($result !== null) {
                    $results[] = $result;
                }
            }
        }

        if (!isset ($this->pluginEvent[$evtName])) {
            return $results ?? false;
        }

        $this->storeEvent();

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
                $this->setConfig('enable_filter', 0);
            }

            if ($this->dumpPlugins) {
                $eventtime = $this->getMicroTime() - $eventtime;
                $this->pluginsCode .= sprintf(
                    '<fieldset><legend><b>%s / %s</b> (%2.2f ms)</legend>'
                    , $evtName
                    , $pluginName
                    , $eventtime * 1000
                );
                foreach ($parameter as $k => $v) {
                    $this->pluginsCode .= sprintf(
                        '%d => %s<br>'
                        , $k
                        , print_r($v, true)
                    );
                }
                $this->pluginsCode .= '</fieldset><br />';
                $this->pluginsTime[sprintf('%s / %s', $evtName, $pluginName)] += $eventtime;
            }
            if ($this->event->getOutput() != '') {
                $results[] = $this->event->getOutput();
            }
            if ($this->event->_propagate != true) {
                break;
            }
        }

        $this->restoreEvent();
        return $results;
    }

    /**
     * Returns plugin-code and properties
     *
     * @param string $pluginName
     * @return array Associative array consisting of 'code' and 'props'
     * @throws InvalidFieldException
     * @throws TableNotDefinedException
     * @throws \AgelxNash\Modx\Evo\Database\Exceptions\Exception
     */
    public function getPluginCode($pluginName)
    {
        $plugin = array();
        if (isset ($this->pluginCache[$pluginName])) {
            $pluginCode = $this->pluginCache[$pluginName];
            if (isset($this->pluginCache[$pluginName . 'Props'])) {
                $pluginProperties = $this->pluginCache[$pluginName . 'Props'];
            } else {
                $pluginProperties = '';
            }
        } else {

            $plugin = SitePlugin::select('name', 'plugincode', 'properties')
                ->where('name', $pluginName)->where('disabled', 0)->first();

            if (!is_null($plugin)) {
                $pluginCode = $this->pluginCache[$plugin->name] = $plugin->plugincode;
                $pluginProperties = $this->pluginCache[$plugin->name . 'Props'] = $plugin->properties;
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
     * @param string|array $propertyString
     * @param string|null $elementName
     * @param string|null $elementType
     * @return array Associative array in the form property name => property value
     */
    public function parseProperties($propertyString, $elementName = null, $elementType = null)
    {
        $property = array();

        if (\is_scalar($propertyString)) {
            $propertyString = trim($propertyString);
            $propertyString = str_replace(
                array('{}', '} {')
                , array('', ',')
                , $propertyString
            );
            if (!empty($propertyString) && $propertyString !== '{}') {
                $jsonFormat = data_is_json($propertyString, true);
                // old format
                if ($jsonFormat === false) {
                    $props = explode('&', $propertyString);
                    foreach ($props as $prop) {

                        if (empty($prop)) {
                            continue;
                        }

                        if (strpos($prop, '=') === false) {
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
        } elseif (\is_array($propertyString)) {
            $property = $propertyString;
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
     * @deprecated
     */
    public function parseDocBlockFromFile($element_dir, $filename, $escapeValues = false)
    {
        $data = $this->get('DocBlock')->parseFromFile($element_dir, $filename);
        return $data;
    }

    /**
     * @deprecated
     */
    public function parseDocBlockFromString($string, $escapeValues = false)
    {
        $data = $this->get('DocBlock')->parseFromString($string);
        return $data;
    }

    /**
     * @deprecated
     */
    public function parseDocBlockLine($line, $docblock_start_found, $name_found, $description_found, $docblock_end_found)
    {
        return $this->get('DocBlock')->parseLine($line, $docblock_start_found, $name_found, $description_found, $docblock_end_found);
    }

    /**
     * @deprecated
     */
    public function convertDocBlockIntoList($parsed)
    {
        return $this->get('DocBlock')->convertIntoList($parsed);
    }

    /**
     * @param string $string
     * @return string
     * @deprecated
     */
    public function removeSanitizeSeed($string = '')
    {
        return removeSanitizeSeed($string);
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

        $enable_filter = $this->getConfig('enable_filter');
        $this->setConfig('enable_filter', 1);
        $_ = array('[* *]', '[( )]', '{{ }}', '[[ ]]', '[+ +]');
        foreach ($_ as $brackets) {
            [$left, $right] = explode(' ', $brackets);
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
            [$left, $right] = explode(' ', $brackets);
            if (strpos($content, $left) !== false) {
                $matches = $this->getTagsFromContent($content, $left, $right);
                $content = isset($matches[0]) ? str_replace($matches[0], '', $content) : $content;
            }
        }
        $this->setConfig('enable_filter', $enable_filter);
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
     * {@inheritdoc}
     */
    public function addSnippet($name, $phpCode, $namespace = '#', array $defaultParams = array())
    {
        $this->snippetCache[$namespace . $name] = $phpCode;
        $this->snippetCache[$namespace . $name . 'Props'] = $defaultParams;
    }

    /**
     * {@inheritdoc}
     */
    public function addChunk($name, $text, $namespace = '#')
    {
        $this->chunkCache[$namespace . $name] = $text;
    }

    /**
     * {@inheritdoc}
     */
    public function findElements($type, $scanPath, array $ext)
    {
        $out = array();

        if (!is_dir($scanPath) || empty($ext)) {
            return $out;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($scanPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            /**
             * @var \SplFileInfo $item
             */
            if ($item->isFile() && $item->isReadable() && \in_array($item->getExtension(), $ext)) {
                $name = $item->getBasename('.' . $item->getExtension());
                $path = ltrim(str_replace(
                    array(rtrim($scanPath, '//'), '/'),
                    array('', '\\'),
                    $item->getPath() . '/'
                ), '\\');

                if (!empty($path)) {
                    $name = $path . $name;
                }
                switch ($type) {
                    case 'chunk':
                        $out[$name] = file_get_contents($item->getRealPath());
                        break;
                    case 'snippet':
                        $out[$name] = "return require '" . $item->getRealPath() . "';";
                        break;
                    default:
                        throw new \Exception;
                }
            }
        }

        return $out;
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
            $evalmode = $this->getConfig('allow_eval');
        }
        if ($safe_functions == '') {
            $safe_functions = $this->getConfig('safe_functions_at_eval');
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
            $title = sprintf(
                'Unknown eval was executed (%s)'
                , $this->getPhpCompat()->htmlspecialchars(substr(trim($phpcode), 0, 50))
            );
            $this->getService('ExceptionHandler')
                ->messageQuit($title, '', true, '', '', 'Parser', $msg);
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
        return $this->getPhpCompat()->htmlspecialchars($output); // Maybe, all html tags are dangerous
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
                    if ($token[1] === '$GLOBALS') {
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

        $search_path = array('assets/tvs/', 'assets/chunks/', 'assets/templates/', $this->getConfig('rb_base_url') . 'files/', '');
        foreach ($search_path as $path) {
            $file_path = MODX_BASE_PATH . $path . $str;
            if (strpos($file_path, MODX_MANAGER_PATH) === 0) {
                return $errorMsg;
            }

            if (is_file($file_path)) {
                break;
            }

            $file_path = false;
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
        }

        return substr($str, $pos);
    }

    /**
     * Get Evolution CMS settings including, but not limited to, the system_settings table
     */
    public function getSettings()
    {

        /**
         * Restore original settings
         * And hack again at the getSettings() method
         * @TODO: This is dirty code. Any ideas?
         */
        $this->config = $this->saveConfig;
        $this->saveConfig = [];

        if (empty($this->config)) {
            $this->recoverySiteCache();
        }

        /**
         * @TODO: This is dirty code. Any ideas?
         */
        $this->config = $this->configCompatibility();

        $this->loadConfig();

        // now merge user settings into evo-configuration
        $this->getUserSettings();
    }

    /**
     * Get user settings and merge into Evolution CMS configuration
     * @return array
     */
    public function getUserSettings()
    {

        $this->getDatabase();
        //if (!$this->getDatabase()->getDriver()->isConnected()) {
        //   $this->getDatabase()->connect();
        //}
        // load user setting if user is logged in
        $usrSettings = array();
        if ($id = $this->getLoginUserID()) {
            $usrType = $this->getLoginUserType();
            if (isset ($usrType) && $usrType === 'manager') {
                $usrType = 'mgr';
            }

            if ($usrType === 'mgr' && $this->isBackend()) {
                // invoke the OnBeforeManagerPageInit event, only if in backend
                $this->invokeEvent("OnBeforeManagerPageInit");
            }

            if ($usrType === 'web') {
                $usrSettings = Models\UserSetting::where('user', '=', $id)->get()
                    ->pluck('setting_value', 'setting_name')
                    ->toArray();
            } else {
                $usrSettings = Models\UserSetting::where('user', '=', $id)->get()
                    ->pluck('setting_value', 'setting_name')
                    ->toArray();
            }

            $which_browser_default = get_by_key(
                $this->configGlobal,
                'which_browser',
                $this->getConfig('which_browser')
            );

            if (get_by_key($usrSettings, 'which_browser') === 'default') {
                $usrSettings['which_browser'] = $which_browser_default;
            }

            if (isset ($usrType)) {
                $_SESSION[$usrType . 'UsrConfigSet'] = $usrSettings;
            } // store user settings in session
        }
        if ($this->isFrontend() && $mgrid = $this->getLoginUserID('mgr')) {
            $musrSettings = Models\UserSetting::where('user', '=', $mgrid)->get()
                ->pluck('setting_value', 'setting_name')
                ->toArray();

            $_SESSION['mgrUsrConfigSet'] = $musrSettings; // store user settings in session
            if (!empty ($musrSettings)) {
                $usrSettings = array_merge($musrSettings, $usrSettings);
            }
        }
        // save global values before overwriting/merging array
        foreach ($usrSettings as $param => $value) {
            if ($this->getConfig($param) !== null) {
                $this->configGlobal[$param] = $this->getConfig($param);
            }
        }

        $this->config = array_merge($this->config, $usrSettings);
        $this->setConfig(
            'filemanager_path',
            str_replace('[(base_path)]', MODX_BASE_PATH, $this->getConfig('filemanager_path'))
        );
        $this->setConfig(
            'rb_base_dir',
            str_replace('[(base_path)]', MODX_BASE_PATH, $this->getConfig('rb_base_dir'))
        );

        return $usrSettings;
    }

    private function recoverySiteCache()
    {
        $siteCacheDir = $this->bootstrapPath();
        $siteCachePath = $this->getSiteCacheFilePath();

        if (is_file($siteCachePath)) {
            include $siteCachePath;
        }

        if (!empty($this->config)) {
            return;
        }

        $cache = new Legacy\Cache();
        $cache->setCachepath($siteCacheDir);
        $cache->setReport(false);

        if (IN_INSTALL_MODE === false)
            $cache->buildCache($this);

        clearstatcache();
        if (is_file($siteCachePath)) {
            include $siteCachePath;
        }
        if (!empty($this->config)) {
            return;
        }
        if (IN_INSTALL_MODE === false)
            $this->config = Models\SystemSetting::all()
                ->pluck('setting_value', 'setting_name')
                ->toArray();

        if ($this->getConfig('enable_filter') === null) {
            return;
        }
        if (IN_INSTALL_MODE === false)
            if (Models\SitePlugin::activePhx()->count() === 0) {
                $this->setConfig('enable_filter', '0');
            }
    }


    /***************************************************************************************/
    /* End of API functions                                       */
    /***************************************************************************************/

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
     * @deprecated
     */
    public function phpError($nr, $text, $file, $line)
    {
        $this->getService('ExceptionHandler')->phpError($nr, $text, $file, $line);
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
     * @deprecated
     */
    public function messageQuit($msg = 'unspecified error', $query = '', $is_error = true, $nr = '', $file = '', $source = '', $text = '', $line = '', $output = '')
    {
        return $this->getService('ExceptionHandler')->messageQuit($msg, $query, $is_error, $nr, $file, $source, $text, $line, $output);
    }

    /**
     * @param $backtrace
     * @return string
     * @deprecated
     */
    public function get_backtrace($backtrace)
    {
        return $this->getService('ExceptionHandler')->getBacktrace($backtrace);
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
        }

        // default behavior: strip invalid characters and replace spaces with dashes.
        $alias = strip_tags($alias); // strip HTML
        $alias = preg_replace('/[^\.A-Za-z0-9 _-]/', '', $alias); // strip non-alphanumeric characters
        $alias = preg_replace('/\s+/', '-', $alias); // convert white-space to dash
        $alias = preg_replace('/-+/', '-', $alias);  // convert multiple dashes to one
        $alias = trim($alias, '-'); // trim excess
        return $alias;
    }

    /**
     * @param $size
     * @return string
     * @deprecated
     */
    public function nicesize($size)
    {
        return nicesize($size);
    }

    /**
     * @deprecated use UrlProcessor::getHiddenIdFromAlias()
     */
    public function getHiddenIdFromAlias($parentid, $alias)
    {
        return UrlProcessor::getHiddenIdFromAlias($parentid, $alias);
    }

    /**
     * @deprecated use UrlProcessor::getIdFromAlias()
     */
    public function getIdFromAlias($alias)
    {
        return UrlProcessor::getIdFromAlias($alias);
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
        }

        if (is_file(MODX_BASE_PATH . $str)) {
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
        return $this->getPhpCompat()->htmlspecialchars($str, $flags, $encode);
    }

    /**
     * @param $string
     * @param bool $returnData
     * @return bool|mixed
     * @deprecated
     */
    public function isJson($string, $returnData = false)
    {
        return data_is_json($string, $returnData);
    }

    /**
     * @param $key
     * @return array
     */
    public function splitKeyAndFilter($key)
    {
        if ($this->getConfig('enable_filter') && strpos($key, ':') !== false && stripos($key, '@FILE') !== 0) {
            [$key, $modifiers] = explode(':', $key, 2);
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
        if ($modifiers === false || $modifiers === 'raw') {
            return $value;
        }
        if ($modifiers !== false) {
            $modifiers = trim($modifiers);
        }

        return $this->getModifiers()->phxFilter($key, $value, $modifiers);
    }

    // End of class.

    /**
     * @param string $title
     * @param string $msg
     * @param int $type
     * @throws Exception
     * @throws InvalidFieldException
     * @throws TableNotDefinedException
     * @throws \AgelxNash\Modx\Evo\Database\Exceptions\Exception
     * @throws \AgelxNash\Modx\Evo\Database\Exceptions\GetDataException
     * @throws \AgelxNash\Modx\Evo\Database\Exceptions\TooManyLoopsException
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
     * @param array $data
     */
    public function addDataToView($data = [])
    {
        $this->dataForView = array_merge($this->dataForView, $data);
    }

    public function getDataForView()
    {
        return $this->dataForView;
    }

    /**
     * @param string $name
     * @param string $file
     * @param string $icon
     * @param array $params
     */
    public function registerModule($name, $file, $icon = 'fa fa-cube', $params = [])
    {
        $this->modulesFromFile[md5($name)] = ['id' => md5($name), 'name' => $name, 'file' => $file, 'icon' => $icon, 'properties' => $params];
    }
}
