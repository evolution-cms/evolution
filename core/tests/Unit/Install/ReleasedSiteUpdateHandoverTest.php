<?php

/*
 * make:site of 3.5.7 - 3.5.9 runs the update seeders of the new version in its own process, on the
 * old autoloader, and died there on the first query (3.5 to 3.6). 00_finish_with_new_code.php, the
 * first file it requires, hands the rest of the update to "make:site finish".
 */

/**
 * Runs the update seeders as a released make:site does, on a site whose new artisan answers as given.
 *
 * @param string $methods further methods of that make:site
 */
function releasedUpdate(string $methods, string $artisan, ?int &$exitCode = null): string
{
    $site = sys_get_temp_dir() . '/evo-handover-' . bin2hex(random_bytes(6)) . '/';
    $seeds = $site . 'install/stubs/seeds/update/';
    mkdir($seeds, 0777, true);
    mkdir($site . 'core/factory', 0777, true);
    foreach (glob(dirname(__DIR__, 4) . '/install/stubs/seeds/update/*.php') as $file) {
        // The real seeders need the new autoloader, so loading one at all is the failure.
        str_starts_with(basename($file), '00_')
            ? copy($file, $seeds . basename($file))
            : file_put_contents($seeds . basename($file), '<?php echo "OLD PROCESS SEEDS\n";');
    }
    file_put_contents($site . 'core/artisan', '<?php echo "args: " . implode(" ", array_slice($argv, 1)) . "\n"; ' . $artisan);
    file_put_contents($site . 'core/factory/version.php', "<?php return ['version' => '3.6.0', 'full_appname' => 'Evolution CMS 3.6.0'];");
    $base = var_export(str_replace('\\', '/', $site), true);
    file_put_contents($site . 'run.php', <<<PHP
<?php
namespace EvolutionCMS\Console;
define('EVO_BASE_PATH', $base);
define('EVO_CORE_PATH', EVO_BASE_PATH . 'core/');
class SiteUpdateCommand
{
    public function line(\$line) { echo \$line, "\\n"; }

    public function handle()
    {
        try {
            foreach (glob(EVO_BASE_PATH . 'install/stubs/seeds/update/*.php') as \$filename) {
                require_once \$filename;
            }
            echo "OLD PROCESS CONTINUES\\n";

            return 0;
        } catch (\\Throwable \$e) {
            echo 'ERROR ', \$e->getMessage(), "\\n";

            return 1;
        }
    }

    $methods
}
exit((new SiteUpdateCommand())->handle());
PHP);

    try {
        return evoRunPhp($site . 'run.php', [], $exitCode);
    } finally {
        (new Illuminate\Filesystem\Filesystem())->deleteDirectory($site);
    }
}

const HANDOVER_FINISHES = 'echo "Finish the update with the new code\nApply update seeders\n";';

test('a released make:site hands the rest of the update to the new code and ends', function (string $methods) {
    $output = releasedUpdate($methods, HANDOVER_FINISHES, $exitCode);

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('args: make:site finish --no-ansi')
        ->and($output)->toContain("Finish the update with the new code\nApply update seeders")
        ->and($output)->toContain('Now You use Evolution CMS 3.6.0')
        ->and($output)->not->toContain('OLD PROCESS');
})->with([
    '3.5.7, 3.5.8' => [''],
    '3.5.9' => [<<<'PHP'
        protected function execCoreShellCommand(string $command, ?array &$output = null): int
        {
            exec((DIRECTORY_SEPARATOR === '\\' ? 'cd /d ' : 'cd ') . escapeshellarg(EVO_CORE_PATH) . ' && ' . $command . ' 2>&1', $output, $exitCode);

            return $exitCode;
        }

        protected function describeInstalledVersion(): string
        {
            return 'Evolution CMS 3.6.0 from 3.6.x';
        }
        PHP],
]);

test('a new code that cannot finish is a failure, and the old process runs nothing more', function (string $artisan) {
    $output = releasedUpdate('', $artisan, $exitCode);

    expect($exitCode)->toBe(1)
        ->and($output)->toContain('ERROR The new code could not finish the update')
        ->and($output)->toContain('Run "php artisan make:site finish"')
        ->and($output)->not->toContain('OLD PROCESS')
        ->and($output)->not->toContain('Now You use');
})->with([
    'it fails' => ['echo "Finish the update with the new code\nSQLSTATE[HY000]\n"; exit(1);'],
    'it has no finish' => ['exit(0);'],
]);

test('a make:site that hands over itself, and its "finish", run the seeders', function () {
    $output = releasedUpdate('protected function finishWithNewCode(): bool { return true; }', HANDOVER_FINISHES, $exitCode);

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('OLD PROCESS SEEDS')
        ->and($output)->toContain('OLD PROCESS CONTINUES')
        ->and($output)->not->toContain('args:');
});

test('the installers, which load the folder outside make:site, pass the file by', function () {
    $included = (static fn () => include dirname(__DIR__, 4) . '/install/stubs/seeds/update/00_finish_with_new_code.php')();

    expect($included)->toBeNull();
});

test('the file comes first and matches the make:site it hands over to', function () {
    $files = glob(dirname(__DIR__, 4) . '/install/stubs/seeds/update/*.php');
    $marker = (new ReflectionClassConstant(EvolutionCMS\Console\SiteUpdateCommand::class, 'FINISH_MARKER'))->getValue();

    expect(basename($files[0]))->toBe('00_finish_with_new_code.php')
        ->and((string) file_get_contents($files[0]))->toContain("'" . $marker . "'")
        ->and(method_exists(EvolutionCMS\Console\SiteUpdateCommand::class, 'finishWithNewCode'))->toBeTrue();
});
