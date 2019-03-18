<?php

class synccache
{
    public $cachePath;
    public $showReport;
    public $deletedfiles = array();
    /**
     * @var array
     */
    public $aliases = array();
    /**
     * @var array
     */
    public $parents = array();
    /**
     * @var array
     */
    public $aliasVisible = array();
    public $request_time;
    public $cacheRefreshTime;

    /**
     * synccache constructor.
     */
    public function __construct()
    {
        $modx = evolutionCMS();

        $this->request_time = $_SERVER['REQUEST_TIME'] + $modx->config['server_offset_time'];
    }

    /**
     * @param string $path
     */
    public function setCachepath($path)
    {
        $this->cachePath = $path;
    }

    /**
     * @param bool $bool
     */
    public function setReport($bool)
    {
        $this->showReport = $bool;
    }

    /**
     * @param string $s
     * @return string
     */
    public function escapeSingleQuotes($s)
    {
        if ($s === '') {
            return $s;
        }
        $q1 = array("\\", "'");
        $q2 = array("\\\\", "\\'");

        return str_replace($q1, $q2, $s);
    }

    /**
     * @param string $s
     * @return string
     */
    public function escapeDoubleQuotes($s)
    {
        $q1 = array("\\", "\"", "\r", "\n", "\$");
        $q2 = array("\\\\", "\\\"", "\\r", "\\n", "\\$");

        return str_replace($q1, $q2, $s);
    }

    /**
     * @param int|string $id
     * @param string $path
     * @return string
     */
    public function getParents($id, $path = '')
    { // modx:returns child's parent
        $modx = evolutionCMS();
        if (empty($this->aliases)) {
            $f = "id, IF(alias='', id, alias) AS alias, parent, alias_visible";
            $rs = $modx->db->select($f, '[+prefix+]site_content', 'deleted=0');
            while ($row = $modx->db->getRow($rs)) {
                $docid = $row['id'];
                $this->aliases[$docid] = $row['alias'];
                $this->parents[$docid] = $row['parent'];
                $this->aliasVisible[$docid] = $row['alias_visible'];
            }
        }
        if (isset($this->aliases[$id])) {
            if ($this->aliasVisible[$id] == 1) {
                if ($path != '') {
                    $path = $this->aliases[$id] . '/' . $path;
                } else {
                    $path = $this->aliases[$id];
                }
            }

            return $this->getParents($this->parents[$id], $path);
        }

        return $path;
    }

    /**
     * @param null|DocumentParser $modx
     */
    public function emptyCache($modx = null)
    {
        if (!($modx instanceof DocumentParser)) {
            $modx = $GLOBALS['modx'];
        }
        if (!isset($this->cachePath)) {
            $modx->messageQuit("Cache path not set.");
        }

        $files = glob(realpath($this->cachePath) . '/*.pageCache.php');
        $filesincache = count($files);
        $deletedfiles = array();
        while ($file = array_shift($files)) {
            $name = basename($file);
            clearstatcache();
            if (is_file($file)) {
                if (unlink($file)) {
                    $deletedfiles[] = $name;
                }
            }
        }
        $opcache_restrict_api = trim(ini_get('opcache.restrict_api'));
        $opcache_restrict_api = $opcache_restrict_api && mb_stripos(__FILE__, $opcache_restrict_api) !== 0;

        if (!$opcache_restrict_api && function_exists('opcache_get_status')) {
            $opcache = opcache_get_status();
            if (!empty($opcache['opcache_enabled'])) {
                opcache_reset();
            }
        }

        $this->buildCache($modx);

        $this->publishTimeConfig();

        // finished cache stuff.
        if ($this->showReport == true) {
            global $_lang;
            $total = count($deletedfiles);
            echo sprintf($_lang['refresh_cache'], $filesincache, $total);
            if ($total > 0) {
                if (isset($opcache)) {
                    echo '<p>Opcache empty.</p>';
                }
                echo '<p>' . $_lang['cache_files_deleted'] . '</p><ul>';
                foreach ($deletedfiles as $deletedfile) {
                    echo '<li>' . $deletedfile . '</li>';
                }
                echo '</ul>';
            }
        }
    }

    /**
     * @param string|int $cacheRefreshTime
     */
    public function publishTimeConfig($cacheRefreshTime = '')
    {
        $cacheRefreshTimeFromDB = $this->getCacheRefreshTime();
        if (!preg_match('@^[0-9]+$]@', $cacheRefreshTime) || $cacheRefreshTimeFromDB < $cacheRefreshTime) {
            $cacheRefreshTime = $cacheRefreshTimeFromDB;
        }


        // write the file
        $content = '<?php' . "\n";
        $content .= '$recent_update=\'' . $this->request_time . '\';' . "\n";
        $content .= '$cacheRefreshTime=\'' . $cacheRefreshTime . '\';' . "\n";

        $filename = $this->cachePath . '/sitePublishing.idx.php';
        if (!$handle = fopen($filename, 'w')) {
            exit("Cannot open file ({$filename}");
        }

        $content .= "\n";

        // Write $somecontent to our opened file.
        if (fwrite($handle, $content) === false) {
            exit("Cannot write publishing info file! Make sure the assets/cache directory is writable!");
        }
    }

    /**
     * @return int
     */
    public function getCacheRefreshTime()
    {
        $modx = evolutionCMS();

        // update publish time file
        $timesArr = array();

        $result = $modx->db->select('MIN(pub_date) AS minpub', '[+prefix+]site_content',
            'pub_date>' . $this->request_time);
        if (!$result) {
            echo "Couldn't determine next publish event!";
        }

        $minpub = $modx->db->getValue($result);
        if ($minpub != null) {
            $timesArr[] = $minpub;
        }

        $result = $modx->db->select('MIN(unpub_date) AS minunpub', '[+prefix+]site_content',
            'unpub_date>' . $this->request_time);
        if (!$result) {
            echo "Couldn't determine next unpublish event!";
        }

        $minunpub = $modx->db->getValue($result);
        if ($minunpub != null) {
            $timesArr[] = $minunpub;
        }

        if (isset($this->cacheRefreshTime) && !empty($this->cacheRefreshTime)) {
            $timesArr[] = $this->cacheRefreshTime;
        }

        if (count($timesArr) > 0) {
            $cacheRefreshTime = min($timesArr);
        } else {
            $cacheRefreshTime = 0;
        }

        return $cacheRefreshTime;
    }

    /**
     * build siteCache file
     * @param DocumentParser $modx
     * @return boolean success
     */
    public function buildCache($modx)
    {
        $content = "<?php\n";

        // SETTINGS & DOCUMENT LISTINGS CACHE

        // get settings
        $rs = $modx->db->select('*', '[+prefix+]system_settings');
        $config = array();
        $content .= '$c=&$this->config;';
        while (list($key, $value) = $modx->db->getRow($rs, 'num')) {
            $content .= '$c[\'' . $modx->db->escape($key) . '\']="' . $this->escapeDoubleQuotes($value) . '";';
            $config[$key] = $value;
        }

        if (isset($config['enable_filter']) && $config['enable_filter']) {
            $where = "plugincode LIKE '%phx.parser.class.inc.php%OnParseDocument();%' AND disabled != 1";
            $count = $modx->db->getRecordCount($modx->db->select('id', '[+prefix+]site_plugins', $where));
            if ($count) {
                $content .= '$this->config[\'enable_filter\']=\'0\';';
            }
        }

        if ($config['aliaslistingfolder'] == 1) {
            $f['id'] = 'c.id';
            $f['alias'] = "IF( c.alias='', c.id, c.alias)";
            $f['parent'] = 'c.parent';
            $f['isfolder'] = 'c.isfolder';
            $f['alias_visible'] = 'c.alias_visible';
            $from = array();
            $from[] = '[+prefix+]site_content c';
            $from[] = 'LEFT JOIN [+prefix+]site_content p ON p.id=c.parent';
            $where = 'c.deleted=0 AND (c.isfolder=1 OR p.alias_visible=0)';
            $rs = $modx->db->select($f, $from, $where, 'c.parent, c.menuindex');
        } else {
            $f = "id, IF(alias='', id, alias) AS alias, parent, isfolder, alias_visible";
            $rs = $modx->db->select($f, '[+prefix+]site_content', 'deleted=0', 'parent, menuindex');
        }

        $use_alias_path = ($config['friendly_urls'] && $config['use_alias_path']) ? 1 : 0;
        $tmpPath = '';
        $content .= '$this->aliasListing=array();';
        $content .= '$a=&$this->aliasListing;';
        $content .= '$d=&$this->documentListing;';
        $content .= '$m=&$this->documentMap;';
        while ($doc = $modx->db->getRow($rs)) {
            $docid = $doc['id'];
            if ($use_alias_path) {
                $tmpPath = $this->getParents($doc['parent']);
                $alias = (strlen($tmpPath) > 0 ? "$tmpPath/" : '') . $doc['alias'];
                $key = $alias;
            } else {
                $key = $doc['alias'];
            }

            $doc['path'] = $tmpPath;
            $content .= '$a[' . $docid . ']=array(\'id\'=>' . $docid . ',\'alias\'=>\'' . $doc['alias'] . '\',\'path\'=>\'' . $doc['path'] . '\',\'parent\'=>' . $doc['parent'] . ',\'isfolder\'=>' . $doc['isfolder'] . ',\'alias_visible\'=>' . $doc['alias_visible'] . ');';
            $content .= '$d[\'' . $key . '\']=' . $docid . ';';
            $content .= '$m[]=array(' . $doc['parent'] . '=>' . $docid . ');';
        }

        // get content types
        $rs = $modx->db->select('id, contentType', '[+prefix+]site_content', "contentType!='text/html'");
        $content .= '$c=&$this->contentTypes;';
        while ($doc = $modx->db->getRow($rs)) {
            $content .= '$c[\'' . $doc['id'] . '\']=\'' . $doc['contentType'] . '\';';
        }

        // WRITE Chunks to cache file
        $rs = $modx->db->select('*', '[+prefix+]site_htmlsnippets');
        $content .= '$c=&$this->chunkCache;';
        while ($doc = $modx->db->getRow($rs)) {
            $content .= '$c[\'' . $modx->db->escape($doc['name']) . '\']=\'' . ($doc['disabled'] ? '' : $this->escapeSingleQuotes($doc['snippet'])) . '\';';
        }

        // WRITE snippets to cache file
        $f = 'ss.*, sm.properties as sharedproperties';
        $from = '[+prefix+]site_snippets ss LEFT JOIN [+prefix+]site_modules sm on sm.guid=ss.moduleguid';
        $rs = $modx->db->select($f, $from);
        $content .= '$s=&$this->snippetCache;';
        while ($row = $modx->db->getRow($rs)) {
            $key = $modx->db->escape($row['name']);
            if ($row['disabled']) {
                $content .= '$s[\'' . $key . '\']=\'return false;\';';
            } else {
                $value = trim($row['snippet']);
                if ($modx->config['minifyphp_incache']) {
                    $value = $this->php_strip_whitespace($value);
                }
                $content .= '$s[\'' . $key . '\']=\'' . $this->escapeSingleQuotes($value) . '\';';
                $properties = $modx->parseProperties($row['properties']);
                $sharedproperties = $modx->parseProperties($row['sharedproperties']);
                $properties = array_merge($sharedproperties, $properties);
                if (0 < count($properties)) {
                    $content .= '$s[\'' . $key . 'Props\']=\'' . $this->escapeSingleQuotes(json_encode($properties)) . '\';';
                }
            }
        }

        // WRITE plugins to cache file
        $f = 'sp.*, sm.properties as sharedproperties';
        $from = array();
        $from[] = '[+prefix+]site_plugins sp';
        $from[] = 'LEFT JOIN [+prefix+]site_modules sm on sm.guid=sp.moduleguid';
        $rs = $modx->db->select($f, $from, 'sp.disabled=0');
        $content .= '$p=&$this->pluginCache;';
        while ($row = $modx->db->getRow($rs)) {
            $key = $modx->db->escape($row['name']);
            $value = trim($row['plugincode']);
            if ($modx->config['minifyphp_incache']) {
                $value = $this->php_strip_whitespace($value);
            }
            $content .= '$p[\'' . $key . '\']=\'' . $this->escapeSingleQuotes($value) . '\';';
            if ($row['properties'] != '' || $row['sharedproperties'] != '') {
                $properties = $modx->parseProperties($row['properties']);
                $sharedproperties = $modx->parseProperties($row['sharedproperties']);
                $properties = array_merge($sharedproperties, $properties);
                if (0 < count($properties)) {
                    $content .= '$p[\'' . $key . 'Props\']=\'' . $this->escapeSingleQuotes(json_encode($properties)) . '\';';
                }
            }
        }

        // WRITE system event triggers
        $f = 'sysevt.name as evtname, event.pluginid, plugin.name as pname';
        $from = array();
        $from[] = '[+prefix+]system_eventnames sysevt';
        $from[] = 'INNER JOIN [+prefix+]site_plugin_events event ON event.evtid=sysevt.id';
        $from[] = 'INNER JOIN [+prefix+]site_plugins plugin ON plugin.id=event.pluginid';
        $rs = $modx->db->select($f, $from, 'plugin.disabled=0', 'sysevt.name, event.priority');
        $content .= '$e=&$this->pluginEvent;';
        $events = array();
        while ($row = $modx->db->getRow($rs)) {
            $evtname = $row['evtname'];
            if (!isset($events[$evtname])) {
                $events[$evtname] = array();
            }
            $events[$evtname][] = $row['pname'];
        }
        foreach ($events as $evtname => $pluginnames) {
            $events[$evtname] = $pluginnames;
            $content .= '$e[\'' . $evtname . '\']=array(\'' . implode('\',\'',
                    $this->escapeSingleQuotes($pluginnames)) . '\');';
        }

        $content .= "\n";

        // close and write the file
        $filename = $this->cachePath . 'siteCache.idx.php';

        // invoke OnBeforeCacheUpdate event
        if ($modx) {
            $modx->invokeEvent('OnBeforeCacheUpdate');
        }

        if (@file_put_contents($filename, $content) === false) {
            exit("Cannot write main MODX cache file! Make sure the assets/cache directory is writable!");
        }

        if (!is_file($this->cachePath . '/.htaccess')) {
            file_put_contents($this->cachePath . '/.htaccess', "order deny,allow\ndeny from all\n");
        }

        // invoke OnCacheUpdate event
        if ($modx) {
            $modx->invokeEvent('OnCacheUpdate');
        }

        return true;
    }

    /**
     * @param string $source
     * @return string
     *
     * @see http://php.net/manual/en/tokenizer.examples.php
     */
    public function php_strip_whitespace($source)
    {

        $source = trim($source);
        if (substr($source, 0, 5) !== '<?php') {
            $source = '<?php ' . $source;
        }

        $tokens = token_get_all($source);
        $_ = '';
        $prev_token = 0;
        $chars = explode(' ', '( ) ; , = { } ? :');
        foreach ($tokens as $i => $token) {
            if (is_string($token)) {
                if (in_array($token, array('=', ':'))) {
                    $_ = trim($_);
                } elseif (in_array($token, array('(', '{')) && in_array($prev_token, array(T_IF, T_ELSE, T_ELSEIF))) {
                    $_ = trim($_);
                }
                $_ .= $token;
                if ($prev_token == T_END_HEREDOC) {
                    $_ .= "\n";
                }
                continue;
            }

            list($type, $text) = $token;

            switch ($type) {
                case T_COMMENT    :
                case T_DOC_COMMENT:
                    break;
                case T_WHITESPACE :
                    if ($prev_token != T_END_HEREDOC) {
                        $_ = trim($_);
                    }
                    $lastChar = substr($_, -1);
                    if (!in_array($lastChar, $chars)) {// ,320,327,288,284,289
                        if (!in_array($prev_token,
                            array(T_FOREACH, T_WHILE, T_FOR, T_BOOLEAN_AND, T_BOOLEAN_OR, T_DOUBLE_ARROW))) {
                            $_ .= ' ';
                        }
                    }
                    break;
                case T_IS_EQUAL :
                case T_IS_IDENTICAL :
                case T_IS_NOT_EQUAL :
                case T_DOUBLE_ARROW :
                case T_BOOLEAN_AND :
                case T_BOOLEAN_OR :
                case T_START_HEREDOC :
                    if ($prev_token != T_START_HEREDOC) {
                        $_ = trim($_);
                    }
                    $prev_token = $type;
                    $_ .= $text;
                    break;
                default:
                    $prev_token = $type;
                    $_ .= $text;
            }
        }
        $source = preg_replace(array('@^<\?php@i', '|\s+|', '|<!--|', '|-->|', '|-->\s+<!--|'),
            array('', ' ', "\n" . '<!--', '-->' . "\n", '-->' . "\n" . '<!--'), $_);
        $source = trim($source);

        return $source;
    }
}
