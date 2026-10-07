<?php

use EvolutionCMS\Bootstrap\EnvCacheLoader;
use EvolutionCMS\Console\Packages\PackageCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

// Run in a separate process so CMS path constants point only at this fixture.
$root = sys_get_temp_dir() . '/evo-discover-cache-' . bin2hex(random_bytes(6));
define('EVO_BASE_PATH', $root . '/');
define('EVO_CORE_PATH', $root . '/core/');
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$files = new Filesystem();
$check = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

try {
    $files->ensureDirectoryExists(EVO_CORE_PATH . 'custom');
    $files->ensureDirectoryExists(EVO_CORE_PATH . 'storage/bootstrap');
    $files->put(EVO_CORE_PATH . '.install', 'installed');
    $files->put(EVO_CORE_PATH . 'custom/composer.json', json_encode([
        'extra' => ['laravel' => [
            'providers' => ['Fixture\\NewServiceProvider'],
            'aliases' => ['FixtureAlias' => 'stdClass'],
        ]],
    ]));
    EnvCacheLoader::load($root);

    $command = new class extends PackageCommand {
        public function __construct()
        {
            $this->output = new OutputStyle(new ArrayInput([]), new BufferedOutput());
        }
    };

    // Both a first discovery and a repeat must invalidate old snapshots.
    for ($run = 0; $run < 2; $run++) {
        EnvCacheLoader::cacheConfiguration(['cached' => true], []);
        $files->put(EVO_CORE_PATH . 'storage/bootstrap/services.php', '<?php return [];');
        $check(is_file(EVO_CORE_PATH . 'storage/cache/env.php'), 'Fixture cache exists');
        $check(EnvCacheLoader::configuration() !== null, 'Fixture runtime cache exists');
        $command->handle();
        $check(is_file(EVO_CORE_PATH . 'custom/config/app/providers/NewServiceProvider.php'), 'Provider generated');
        $check(is_file(EVO_CORE_PATH . 'custom/config/app/aliases/FixtureAlias.php'), 'Alias generated');
        $check(!is_file(EVO_CORE_PATH . 'storage/bootstrap/services.php'), 'Services cache removed');
        $check(!is_file(EVO_CORE_PATH . 'storage/cache/env.php'), 'Bootstrap disk cache removed');
        $check(EnvCacheLoader::configuration() === null, 'Bootstrap runtime cache removed');
    }
    echo "Discovery invalidates disk and runtime bootstrap caches.\n";
} finally {
    $files->deleteDirectory($root);
}
