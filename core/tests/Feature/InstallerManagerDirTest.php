<?php

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * A site tree around the real install/index.php and core/includes/custom-define.inc.php. The
 * installer stops at its first core class, which the fixture replaces with a probe that prints
 * the MGR_DIR it resolved.
 *
 * @param array<string, string> $files path => contents
 */
function installerManagerDirFixture(array $files): string
{
    $repo = str_replace('\\', '/', dirname(__DIR__, 3));
    $root = str_replace('\\', '/', sys_get_temp_dir()) . '/evo-installer-mgr-' . bin2hex(random_bytes(8));

    $files += [
        'install/index.php' => (string) file_get_contents($repo . '/install/index.php'),
        'core/includes/custom-define.inc.php' => (string) file_get_contents($repo . '/core/includes/custom-define.inc.php'),
        'core/functions/laravel.php' => '<?php require_once ' . var_export($repo . '/core/functions/laravel.php', true) . ';',
        'core/vendor/autoload.php' => '<?php return require_once ' . var_export($repo . '/core/vendor/autoload.php', true) . ';',
        'core/src/Support/InstallerCompletion.php' => "<?php echo 'MGR_DIR=' . MGR_DIR; exit;",
    ];

    foreach ($files as $path => $contents) {
        if (!is_dir(dirname($root . '/' . $path))) {
            mkdir(dirname($root . '/' . $path), 0777, true);
        }
        file_put_contents($root . '/' . $path, $contents);
    }

    return $root;
}

function runInstallerStartup(string $root, array $env = []): string
{
    $process = new Process([PHP_BINARY, 'index.php'], $root . '/install', $env + ['MGR_DIR' => false]);
    $process->run();

    return trim($process->getOutput());
}

test('the installer finds a renamed manager the way the site does', function (array $files, array $env, string $expected) {
    $root = installerManagerDirFixture($files);

    try {
        expect(runInstallerStartup($root, $env))->toContain($expected);
    } finally {
        (new Filesystem())->deleteDirectory($root);
    }
})->with([
    'custom/define.php wins over a stale legacy cache' => [
        [
            'core/custom/define.php' => "<?php define('MGR_DIR', 'admin');",
            'assets/cache/siteManager.php' => "<?php define('MGR_DIR', 'manager');",
            'admin/includes/version.inc.php' => '<?php',
            'manager/includes/version.inc.php' => '<?php',
        ],
        [],
        'MGR_DIR=admin',
    ],
    'the environment' => [
        ['admin/includes/version.inc.php' => '<?php'],
        ['MGR_DIR' => 'admin'],
        'MGR_DIR=admin',
    ],
    'the legacy cache, when nothing else sets it' => [
        [
            'assets/cache/siteManager.php' => "<?php define('MGR_DIR', 'admin');",
            'admin/includes/version.inc.php' => '<?php',
        ],
        [],
        'MGR_DIR=admin',
    ],
    'the default folder' => [
        ['manager/includes/version.inc.php' => '<?php'],
        [],
        'MGR_DIR=manager',
    ],
    'a renamed manager nobody configured' => [
        ['admin/includes/version.inc.php' => '<?php'],
        [],
        'set MGR_DIR in core/custom/define.php',
    ],
]);
