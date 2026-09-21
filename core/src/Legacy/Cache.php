<?php namespace EvolutionCMS\Legacy;

use EvolutionCMS\Interfaces;
use EvolutionCMS\Models;

/**
 * @class: synccache
 */
class Cache
{
    public $cachePath;
    public $showReport;
    public $deletedfiles = [];
    /**
     * @var array
     */
    public $aliases = [];
    /**
     * @var array
     */
    public $parents = [];
    /**
     * @var array
     */
    public $aliasVisible = [];
    public $request_time;
    public $cacheRefreshTime;

    /**
     * synccache constructor.
     */
    public function __construct()
    {
        $this->request_time = $_SERVER['REQUEST_TIME'] + evo()->getConfig('server_offset_time');
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
        $q1 = ["\\", "'"];
        $q2 = ["\\\\", "\\'"];

        return str_replace($q1, $q2, $s);
    }

    /**
     * @param string $s
     * @return string
     */
    public function escapeDoubleQuotes($s)
    {
        $q1 = ["\\", "\"", "\r", "\n", "\$"];
        $q2 = ["\\\\", "\\\"", "\\r", "\\n", "\\$"];
        return str_replace($q1, $q2, ($s ?? ''));
    }

    /**
     * @param int|string $id
     * @param string $path
     * @return string
     */
    public function getParents($id, $path = '')
    { // modx:returns child's parent
        if (empty($this->aliases)) {
            $parents = Models\SiteContent::select('id', 'alias', 'parent', 'alias_visible')->where('deleted', 0)->get();
            foreach ($parents->toArray() as $row) {
                if ($row['alias'] == '') $row['alias'] = $row['id'];
                $this->aliases[$row['id']] = $row['alias'];
                $this->parents[$row['id']] = $row['parent'];
                $this->aliasVisible[$row['id']] = $row['alias_visible'];
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
     * @param null|DocumentParser $evo
     */
    public function emptyCache($evo = null)
    {
        $evo = $this->resolveCore($evo);
        \Illuminate\Support\Facades\Cache::flush();
        Models\UserSetting::query()->whereIn('setting_name', ['password', 'password_confirmation', 'clearPassword'])->delete();
        $filesincache = count(glob(realpath($this->cachePath) . '/*.pageCache.php') ?: []);
        $deletedfiles = $this->clearPageCache();

        if ($this->opcacheApiAllowed() && function_exists('opcache_get_status')) {
            $opcache = opcache_get_status();
            if (!empty($opcache['opcache_enabled'])) {
                opcache_reset();
            }
        }

        $this->buildCache($evo);

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
     * Document save/publish/move: drops page caches and rebuilds the site cache
     * without resetting opcache or touching compiled views.
     * @param null|Interfaces\CoreInterface $evo
     * @since 3.5.8
     */
    /**
     * @param bool $deferRebuild rebuild siteCache.idx.php after the response instead of inside it; the purge
     *                           itself stays synchronous so no stale page is served meanwhile
     */
    public function refreshDocumentCache($evo = null, bool $deferRebuild = false)
    {
        $evo = $this->resolveCore($evo);
        \Illuminate\Support\Facades\Cache::flush();
        $this->clearPageCache();
        $rebuild = function () use ($evo) {
            $this->buildCache($evo);
            $this->publishTimeConfig();
        };
        $deferRebuild ? $evo->deferAfterResponse($rebuild) : $rebuild();
    }

    /**
     * @return string[] deleted file names
     */
    public function clearPageCache(): array
    {
        $deleted = [];
        foreach (glob(realpath($this->cachePath) . '/*.pageCache.php') ?: [] as $file) {
            clearstatcache(false, $file);
            if (is_file($file) && @unlink($file)) {
                $deleted[] = basename($file);
            }
        }

        return $deleted;
    }

    /**
     * @param mixed $evo
     * @return Interfaces\CoreInterface
     */
    protected function resolveCore($evo)
    {
        if (!($evo instanceof Interfaces\CoreInterface)) {
            $evo = $GLOBALS['evo'];
        }
        if (!isset($this->cachePath)) {
            $evo->getService('ExceptionHandler')->messageQuit("Cache path not set.");
        }

        return $evo;
    }

    protected function opcacheApiAllowed(): bool
    {
        $restrict = trim((string) ini_get('opcache.restrict_api'));

        return !($restrict && mb_stripos(__FILE__, $restrict) !== 0);
    }

    /**
     * Writes through a temp file + rename so concurrent readers never include a partial file,
     * then drops the stale opcache entry (needed with opcache.validate_timestamps=0).
     */
    protected function writeCacheFile(string $filename, string $content): bool
    {
        $tmp = $filename . '.' . uniqid('', true) . '.tmp';
        if (@file_put_contents($tmp, $content) === false) {
            return false;
        }
        if (!@rename($tmp, $filename)) {
            @unlink($tmp);
            if (@file_put_contents($filename, $content) === false) {
                return false;
            }
        }
        if ($this->opcacheApiAllowed() && function_exists('opcache_invalidate')) {
            @opcache_invalidate($filename, true);
        }

        return true;
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

        $content .= "\n";

        $filename = evo()->getSitePublishingFilePath();
        if (!$this->writeCacheFile($filename, $content)) {
            exit("Cannot write publishing info file! Make sure the {$filename} and its directory is writable!");
        }
    }

    /**
     * @return int
     */
    public function getCacheRefreshTime()
    {
        // update publish time file
        $timesArr = [];

        $minpub = Models\SiteContent::query()
            ->where('pub_date', '>', $this->request_time)->min('pub_date');

        if ($minpub != null) {
            $timesArr[] = $minpub;
        }

        $minpub = Models\SiteContent::query()
            ->where('unpub_date', '>', $this->request_time)->min('unpub_date');

        if ($minpub != null) {
            $timesArr[] = $minpub;
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
     * @param Interfaces\CoreInterface $evo
     * @return boolean success
     */
    public function buildCache($evo)
    {
        // serialise concurrent rebuilds so the last writer always reflects the latest DB state
        $lock = @fopen($evo->getSiteCacheFilePath() . '.lock', 'c');
        if ($lock) {
            flock($lock, LOCK_EX);
        }
        try {
            return $this->writeSiteCache($evo);
        } finally {
            if ($lock) {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
    }

    /**
     * @param Interfaces\CoreInterface $evo
     * @return boolean success
     */
    protected function writeSiteCache($evo)
    {
        $content = "<?php\n";

        // SETTINGS & DOCUMENT LISTINGS CACHE

        // get settings
        $systemSettings = Models\SystemSetting::all();
        $config = [];
        $content .= '$c=&$this->config;';
        foreach ($systemSettings as $systemSetting) {
            $content .= '$c[\'' . $systemSetting->setting_name . '\']="' . $this->escapeDoubleQuotes($systemSetting->setting_value) . '";';
            $config[$systemSetting->setting_name] = $systemSetting->setting_value;
        }

        if (isset($config['enable_filter']) && $config['enable_filter'] == 1) {
            if (Models\SitePlugin::activePhx()->count()) {
                $content .= '$this->config[\'enable_filter\']=\'0\';';
            }
        }
        if (!isset($config['full_aliaslisting']) || $config['full_aliaslisting'] != 1) {
            $resources = Models\SiteContent::query()->select('site_content.id', 'site_content.alias', 'site_content.parent', 'site_content.isfolder', 'site_content.alias_visible')
                ->where('site_content.deleted', 0)
                ->orderBy('site_content.parent', 'ASC')
                ->orderBy('site_content.menuindex', 'ASC');

            if (isset($config['aliaslistingfolder']) && $config['aliaslistingfolder'] == 1) {
                $resources = $resources->where(function ($query) {
                    $query->where('site_content.isfolder', 1)
                        ->orWhere('site_content.alias_visible', 1);
                });

            }

            $use_alias_path = ($config['friendly_urls'] && $config['use_alias_path']) ? 1 : 0;
            $tmpPath = '';
            $content .= '$this->aliasListing=array();';
            $content .= '$a=&$this->aliasListing;';
            $content .= '$d=&$this->documentListing;';
            $content .= '$m=&$this->documentMap;';
            foreach ($resources->get()->toArray() as $doc) {
                if ($doc['alias'] == '') $doc['alias'] = $doc['id'];
                if ($use_alias_path) {
                    $tmpPath = $this->getParents($doc['parent']);
                    $alias = (strlen($tmpPath) > 0 ? "$tmpPath/" : '') . $doc['alias'];
                    $key = $alias;
                } else {
                    $key = $doc['alias'];
                }

                $doc['path'] = $tmpPath;
                $content .= '$a[' . $doc['id'] . ']=array(\'id\'=>' . $doc['id'] . ',\'alias\'=>\'' . $doc['alias'] . '\',\'path\'=>\'' . $doc['path'] . '\',\'parent\'=>' . $doc['parent'] . ',\'isfolder\'=>' . $doc['isfolder'] . ',\'alias_visible\'=>' . $doc['alias_visible'] . ');';
                $content .= '$d[\'' . $key . '\']=' . $doc['id'] . ';';
                $content .= '$m[]=array(' . $doc['parent'] . '=>' . $doc['id'] . ');';
            }

            // get content types
            $contentTypes = Models\SiteContent::query()
                ->select('id', 'contentType')
                ->where('contentType', '!=', 'text/html')->get();

            $content .= '$c=&$this->contentTypes;';
            foreach ($contentTypes->toArray() as $doc) {
                $content .= '$c[\'' . $doc['id'] . '\']=\'' . $doc['contentType'] . '\';';
            }
        }
        if (!isset($config['disable_chunk_cache']) || $config['disable_chunk_cache'] != 1) {
            // WRITE Chunks to cache file
            $chunks = Models\SiteHtmlsnippet::all();
            $chunkFiles = \EvolutionCMS\Support\ChunkFileStore::make();
            $content .= '$c=&$this->chunkCache;';
            foreach ($chunks->toArray() as $doc) {
                // What the front end reads: getBaseChunk() never runs for a
                // cached chunk, so files are resolved here or not at all.
                //
                // @deprecated since 3.5.8 the $doc['snippet'] half
                // @todo [remove@3.7] Remove in Evolution CMS 3.7
                $value = $doc['disabled']
                    ? ''
                    : (string) $chunkFiles->resolve((string) $doc['name'], $doc['snippet']);
                $content .= '$c[\'' . $doc['name'] . '\']=\'' . $this->escapeSingleQuotes($value) . '\';';
            }
        }

        if (!isset($config['disable_snippet_cache']) || $config['disable_snippet_cache'] != 1) {
            // WRITE snippets to cache file
            $snippets = Models\SiteSnippet::query()->select('site_snippets.*', 'site_modules.properties as sharedproperties')
                ->leftJoin('site_modules', 'site_snippets.moduleguid', '=', 'site_modules.guid')->get();
            $content .= '$s=&$this->snippetCache;';
            foreach ($snippets->toArray() as $row) {
                if ($row['disabled']) {
                    $content .= '$s[\'' . $row['name'] . '\']=\'return false;\';';
                } else {
                    $value = trim($row['snippet']);
                    if ($evo->getConfig('minifyphp_incache')) {
                        $value = $this->php_strip_whitespace($value);
                    }
                    $content .= '$s[\'' . $row['name'] . '\']=\'' . $this->escapeSingleQuotes($value) . '\';';
                    $properties = $evo->parseProperties($row['properties']);
                    $sharedproperties = $evo->parseProperties($row['sharedproperties']);
                    $properties = array_merge($sharedproperties, $properties);
                    if (0 < count($properties)) {
                        $content .= '$s[\'' . $row['name'] . 'Props\']=\'' . $this->escapeSingleQuotes(json_encode($properties)) . '\';';
                    }
                }
            }
        }

        if (!isset($config['disable_plugins_cache']) || $config['disable_plugins_cache'] != 1) {
            // WRITE plugins to cache file
            $plugins = Models\SitePlugin::query()->select('site_plugins.*', 'site_modules.properties as sharedproperties')
                ->leftJoin('site_modules', 'site_plugins.moduleguid', '=', 'site_modules.guid')
                ->where('site_plugins.disabled', 0)->get();
            $content .= '$p=&$this->pluginCache;';
            foreach ($plugins->toArray() as $row) {
                $value = trim($row['plugincode']);
                if ($evo->getConfig('minifyphp_incache')) {
                    $value = $this->php_strip_whitespace($value);
                }
                $content .= '$p[\'' . $row['name'] . '\']=\'' . $this->escapeSingleQuotes($value) . '\';';
                if ($row['properties'] != '' || $row['sharedproperties'] != '') {
                    $properties = $evo->parseProperties($row['properties']);
                    $sharedproperties = $evo->parseProperties($row['sharedproperties']);
                    $properties = array_merge($sharedproperties, $properties);
                    if (0 < count($properties)) {
                        $content .= '$p[\'' . $row['name'] . 'Props\']=\'' . $this->escapeSingleQuotes(json_encode($properties)) . '\';';
                    }
                }
            }
        }

        // WRITE system event triggers
        $systemEvents = Models\SystemEventname::query()->select('system_eventnames.name as evtname', 'site_plugin_events.pluginid', 'site_plugins.name as pname')
            ->leftJoin('site_plugin_events', 'system_eventnames.id', '=', 'site_plugin_events.evtid')
            ->leftJoin('site_plugins', 'site_plugin_events.pluginid', '=', 'site_plugins.id')
            ->where('site_plugins.disabled', 0)
            ->orderBy('system_eventnames.name', 'ASC')
            ->orderBy('site_plugin_events.priority', 'ASC')->get();
        $content .= '$e=&$this->pluginEvent;';
        $events = [];
        foreach ($systemEvents->toArray() as $row) {
            if (!isset($events[$row['evtname']])) {
                $events[$row['evtname']] = [];
            }
            $events[$row['evtname']][] = $row['pname'];
        }
        foreach ($events as $evtname => $pluginnames) {
            $events[$evtname] = $pluginnames;
            $content .= '$e[\'' . $evtname . '\']=array(\'' . implode('\',\'',
                    $this->escapeSingleQuotes($pluginnames)) . '\');';
        }

        $content .= "\n";

        // close and write the file
        $filename = $evo->getSiteCacheFilePath();

        // invoke OnBeforeCacheUpdate event
        $evo->invokeEvent('OnBeforeCacheUpdate');

        if (!$this->writeCacheFile($filename, $content)) {
            exit("Cannot write $filename! Make sure file or its directory is writable!");
        }

        if (!is_file($this->cachePath . '/.htaccess')) {
            file_put_contents($this->cachePath . '/.htaccess', "order deny,allow\ndeny from all\n");
        }

        // invoke OnCacheUpdate event
        $evo->invokeEvent('OnCacheUpdate');

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
                if (in_array($token, ['=', ':'])) {
                    $_ = trim($_);
                } elseif (in_array($token, ['(', '{']) && in_array($prev_token, [T_IF, T_ELSE, T_ELSEIF])) {
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
                            [T_FOREACH, T_WHILE, T_FOR, T_BOOLEAN_AND, T_BOOLEAN_OR, T_DOUBLE_ARROW])) {
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
        $source = preg_replace(['@^<\?php@i', '|\s+|', '|<!--|', '|-->|', '|-->\s+<!--|'],
            ['', ' ', "\n" . '<!--', '-->' . "\n", '-->' . "\n" . '<!--'], $_);
        $source = trim($source);

        return $source;
    }
}
