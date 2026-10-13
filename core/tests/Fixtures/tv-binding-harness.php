<?php

/*
 * Runs ProcessTVCommand() from one of its two copies against a stub evo() and prints the results
 * as JSON. Both copies are guarded by function_exists() and composer autoloads
 * functions/actions/mutate_content.php, so only a fresh process that loads the chosen copy
 * before the autoloader can exercise core/functions/tv.php.
 *
 * Usage: php tv-binding-harness.php <functions/tv.php|functions/actions/mutate_content.php>
 */

// keep diagnostics off stdout, which carries the JSON
ini_set('display_errors', 'stderr');

define('IN_MANAGER_MODE', false);
define('IN_INSTALL_MODE', false);
define('EVO_API_MODE', true);

$core = dirname(__DIR__, 2) . '/';
define('EVO_BASE_PATH', dirname($core) . '/');
define('EVO_CORE_PATH', $core);

final class TvBindingHarnessDatabase
{
    /** @var array<int, array{sql: string, bindings: array}> */
    public array $queries = [];

    public string $driver = 'mysql';

    public function getConfig($key)
    {
        return ['database' => 'evo', 'prefix' => 'evo_', 'driver' => $this->driver][$key] ?? null;
    }

    public function query($sql, $watchError = true, array $bindings = [])
    {
        $this->queries[] = ['sql' => $sql, 'bindings' => $bindings];

        return 'result';
    }
}

final class TvBindingHarnessCore
{
    public array $config = ['enable_bindings' => 1];
    public $documentIdentifier = 1;
    public $documentObject = [];
    public array $chunks = [];
    public array $documents = [];
    public array $files = [];
    public array $placeholders = [];
    public array $events = [];
    public TvBindingHarnessDatabase $db;

    public function __construct()
    {
        $this->db = new TvBindingHarnessDatabase();
    }

    public function getConfig($name = '', $default = null)
    {
        return $this->config[$name] ?? $default;
    }

    public function getChunk($name)
    {
        return $this->chunks[$name] ?? '';
    }

    public function getDocument($id)
    {
        return isset($this->documents[$id]) ? ['content' => $this->documents[$id]] : false;
    }

    public function atBindFileContent($binding)
    {
        return $this->files[trim(substr($binding, 5))] ?? '';
    }

    public function getPageInfo($id)
    {
        return ['id' => $id, 'parent' => 0, 'published' => 1];
    }

    public function getDatabase()
    {
        return $this->db;
    }

    public function setPlaceholder($name, $value)
    {
        $this->placeholders[$name] = $value;
    }

    public function mergePlaceholderContent($content)
    {
        foreach ($this->placeholders as $name => $value) {
            $content = str_replace('[+' . $name . '+]', $value, $content);
        }

        return $content;
    }

    public function getTagsFromContent($content, $left = '[+', $right = '+]')
    {
        preg_match_all('/' . preg_quote($left, '/') . '(.*?)' . preg_quote($right, '/') . '/s', $content, $matches);

        return $matches;
    }

    public function logEvent($evtid, $type, $msg, $source = 'Parser')
    {
        $this->events[] = ['type' => $type, 'source' => $source, 'msg' => $msg];
    }
}

function evo()
{
    return $GLOBALS['tvBindingHarness'];
}

function evolutionCMS()
{
    return evo();
}

$copy = $argv[1] ?? 'functions/tv.php';
require $core . $copy;
require $core . 'vendor/autoload.php';

$modx = $GLOBALS['tvBindingHarness'] = new TvBindingHarnessCore();
$modx->chunks = [
    'plain' => 'Hello',
    'nested' => '@CHUNK plain',
    'eval' => "@EVAL return 'RAN';",
    'evalLower' => "  @eval return 'RAN';",
    'select' => '@SELECT 1',
    'inherit' => "@INHERIT @EVAL return 'RAN';",
    'viaDocument' => '@DOCUMENT 7',
];
$modx->documents = [5 => "@EVAL return 'RAN';", 6 => 'Page text', 7 => "@EVAL return 'RAN';"];
$modx->files = ['assets/files/a.txt' => "@EVAL return 'RAN';"];

$results = [];
$run = static function (string $label, string $value) use (&$results) {
    $out = ProcessTVCommand($value, 'tv_' . $label, 1);
    $results[$label] = is_string($out) ? $out : get_debug_type($out);
};

$run('directEval', "@EVAL return 'ok';");
$run('evalReturnsBinding', "@EVAL return '@CHUNK plain';");
$run('plainChunk', '@CHUNK plain');
$run('nestedChunk', '@CHUNK nested');
$run('plainDocument', '@DOCUMENT 6');
$run('chunkEval', '@CHUNK eval');
$run('chunkEvalLower', '@CHUNK evalLower');
$run('chunkSelect', '@CHUNK select');
$run('chunkInheritEval', '@CHUNK inherit');
$run('documentEval', '@DOCUMENT 5');
$run('chunkDocumentEval', '@CHUNK viaDocument');
$run('fileEval', '@FILE assets/files/a.txt');

echo json_encode([
    'results' => $results,
    'events' => $modx->events,
    'queries' => $modx->db->queries,
]);
