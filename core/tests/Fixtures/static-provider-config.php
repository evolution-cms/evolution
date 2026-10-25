<?php

use EvolutionCMS\AbstractLaravel;
use EvolutionCMS\Bootstrap\EnvCacheLoader;
use EvolutionCMS\Core;
use Illuminate\Filesystem\Filesystem;

// Real application construction with test-owned paths and no parser/database.
$root = sys_get_temp_dir().'/evo-static-config-'.bin2hex(random_bytes(5));
define('EVO_CORE_PATH', $root.'/core/');
define('EVO_BASE_PATH', $root.'/');
require dirname(__DIR__, 2).'/vendor/autoload.php';

/**
 * Run the actual configuration constructor while observing its final values.
 *
 * @since 3.7.0
 */
class StaticDeclarationRuntime extends Core
{
    /**
     * Exercise AbstractLaravel's cold and warm configuration paths directly.
     *
     * @since 3.7.0
     */
    public function __construct()
    {
        (new ReflectionMethod(AbstractLaravel::class, '__construct'))->invoke($this);
    }

    /**
     * Keep the fixture provider-free after configuration replay.
     *
     * @return void
     * @since 3.7.0
     */
    public function registerConfiguredProviders() {}

    /**
     * Resolve bootstrap paths only inside the fixture directory.
     *
     * @param string $path Relative bootstrap path.
     * @return string Temporary bootstrap path.
     * @since 3.7.0
     */
    public function bootstrapPath($path = '')
    {
        return EVO_CORE_PATH.'storage/bootstrap/'.ltrim($path, '/');
    }
}

$files = new Filesystem;
$check = static function (bool $value, string $message): void {
    if (!$value) throw new RuntimeException($message);
};
try {
    foreach (['config/app/providers', 'config/app/aliases', 'custom/config/app/providers', 'storage/cache', 'storage/bootstrap'] as $dir) {
        $files->ensureDirectoryExists(EVO_CORE_PATH.$dir);
    }
    $files->put(EVO_CORE_PATH.'.install', 'installed');
    $files->put(EVO_CORE_PATH.'config/app.php', '<?php return ["providers" => [], "aliases" => [], "env" => "testing"];');
    $files->put(EVO_CORE_PATH.'config/app/providers/Literal.php', '<?php return \\Fixture\\OriginalProvider::class;');
    $files->put(EVO_CORE_PATH.'custom/config/app/providers/Literal.php', '<?php return \\Fixture\\OverrideProvider::class;');
    $files->put(EVO_CORE_PATH.'config/app/aliases/FixtureAlias.php', '<?php return "Fixture\\\\Alias";');
    $dynamic = EVO_CORE_PATH.'config/app/providers/Dynamic.php';
    $files->put($dynamic, '<?php $GLOBALS["config_read_count"]++; return $GLOBALS["config_runtime_provider"];');
    $GLOBALS['config_read_count'] = 0;
    $GLOBALS['config_runtime_provider'] = 'FirstRuntimeProvider';
    EnvCacheLoader::load($root);
    $cold = new StaticDeclarationRuntime;
    $check($cold['config']->get('app.providers.Literal') === 'Fixture\\OverrideProvider', 'Custom override precedence changed');
    $check($cold['config']->get('app.providers.Dynamic') === 'FirstRuntimeProvider', 'Cold dynamic provider changed');
    $entries = EnvCacheLoader::configuration()['dynamic_files'];
    $literal = array_values(array_filter($entries, static fn ($entry) => $entry['key'] === 'app.providers.Literal'));
    $check(count($literal) === 2 && isset($literal[0]['static_value'], $literal[1]['static_value']), 'Static declarations not cached in source order');
    // A warm cache must replay literal values, without requiring these files again.
    foreach ($literal as $entry) $files->put($entry['path'], '<?php throw new RuntimeException("Literal replay performed I/O");');
    $GLOBALS['config_runtime_provider'] = 'SecondRuntimeProvider';
    $warm = new StaticDeclarationRuntime;
    $check($warm['config']->get('app.providers.Literal') === 'Fixture\\OverrideProvider', 'Warm override precedence changed');
    $check($warm['config']->get('app.providers.Dynamic') === 'SecondRuntimeProvider', 'Dynamic declaration stopped being dynamic');
    $check($GLOBALS['config_read_count'] === 2, 'Dynamic file must run on every bootstrap');
    $check($warm['config']->get('app.aliases.FixtureAlias') === 'Fixture\\Alias', 'Literal alias changed');
    $files->put(EVO_CORE_PATH.'config/app/providers/Literal.php', '<?php return "ChangedRoot";');
    $files->put(EVO_CORE_PATH.'custom/config/app/providers/Literal.php', '<?php return "ChangedOverride";');
    EnvCacheLoader::invalidate($root);
    EnvCacheLoader::load($root);
    $fresh = new StaticDeclarationRuntime;
    $check($fresh['config']->get('app.providers.Literal') === 'ChangedOverride', 'Cache invalidation did not restore declaration reads');
    echo "PASS static replay, dynamic configuration, overrides and invalidation\n";
} finally {
    $files->deleteDirectory($root);
}
