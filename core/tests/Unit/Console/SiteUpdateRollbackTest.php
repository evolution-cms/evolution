<?php

use EvolutionCMS\Console\SiteUpdateCommand;
use EvolutionCMS\Support\UpdateRollback;

/*
 * make:site lays the new files over the site before Composer rebuilds core/vendor. A failure in
 * between used to leave the new code on the old vendor: a site that neither served pages nor ran
 * artisan to finish the update. Everything up to package:discover now goes through a journal.
 */

beforeEach(function () {
    $this->root = sys_get_temp_dir() . '/evo-update-flow-' . bin2hex(random_bytes(6));
    $this->site = $this->root . '/site';
    $this->archive = $this->root . '/unpacked';
    foreach (['/core/vendor/composer', '/core/src', '/manager'] as $folder) {
        mkdir($this->site . $folder, 0777, true);
    }
    file_put_contents($this->site . '/index.php', 'old front');
    file_put_contents($this->site . '/manager/index.php', 'old manager');
    file_put_contents($this->site . '/core/src/Core.php', 'old core');
    file_put_contents($this->site . '/core/composer.lock', 'old lock');
    file_put_contents($this->site . '/core/vendor/autoload.php', 'old autoload');
    file_put_contents($this->site . '/core/vendor/composer/autoload_classmap.php', 'old classmap');

    foreach (['/core/src/Support', '/manager', '/install'] as $folder) {
        mkdir($this->archive . $folder, 0777, true);
    }
    file_put_contents($this->archive . '/index.php', 'new front');
    file_put_contents($this->archive . '/manager/index.php', 'new manager');
    file_put_contents($this->archive . '/core/src/Core.php', 'new core');
    file_put_contents($this->archive . '/core/src/Support/Added.php', 'new class');
    file_put_contents($this->archive . '/core/composer.lock', 'new lock');
    file_put_contents($this->archive . '/install/index.php', 'new installer');
});

afterEach(function () {
    SiteUpdateCommand::rmdirs($this->root);
});

function updateFlowInvoke(SiteUpdateCommand $command, string $method, array $args = [])
{
    return (new ReflectionMethod($command, $method))->invokeArgs($command, $args);
}

/**
 * Every file and folder below a path with its contents.
 *
 * @return array<string, string>
 */
function updateFlowTree(string $root): array
{
    $tree = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $path => $entry) {
        $relative = str_replace('\\', '/', substr($path, strlen($root) + 1));
        $tree[$relative] = $entry->isDir() ? 'dir' : (string) file_get_contents($path);
    }
    ksort($tree);

    return $tree;
}

/** A make:site whose Composer calls answer from a list and write into the test's vendor. */
function rollbackFlowCommand(string $site, array $failing): SiteUpdateCommand
{
    return new class($site, $failing) extends SiteUpdateCommand {
        public array $commands = [];
        public array $lines = [];

        public function __construct(private string $site, private array $failing)
        {
            parent::__construct();
        }

        protected function composerBinaryCommand(): string
        {
            return 'composer';
        }

        protected function execCoreShellCommand(string $command, ?array &$output = null): int
        {
            $this->commands[] = $command;
            foreach ($this->failing as $fragment) {
                if (str_contains($command, $fragment)) {
                    // Composer had started on vendor before it stopped.
                    file_put_contents($this->site . '/core/vendor/composer/autoload_classmap.php', 'half written');
                    $output = ['In HttpDownloader.php line 330:', 'The "https://repo.packagist.org/packages.json" file could not be downloaded'];

                    return 1;
                }
            }
            $output = [];

            return 0;
        }

        public function protect(UpdateRollback $rollback): void
        {
            $rollback->snapshotDirectory($this->site . '/core/vendor');
        }

        public function line($string, $style = null, $verbosity = null)
        {
            $this->lines[] = (string) $string;
        }

        public function error($string, $verbosity = null)
        {
            $this->lines[] = 'ERROR ' . $string;
        }
    };
}

function rollbackFlowSteps(SiteUpdateCommand $command, UpdateRollback $rollback, string $archive, string $site): callable
{
    return function () use ($command, $rollback, $archive, $site) {
        $command->protect($rollback);
        SiteUpdateCommand::moveFiles($archive, $site, $site . '/manager', $rollback);
        updateFlowInvoke($command, 'installComposerDependencies');
    };
}

test('a Composer failure after the overlay puts every file back', function () {
    $before = updateFlowTree($this->site);
    $rollback = new UpdateRollback($this->site, $this->site . '/core/.update-rollback-test');
    $command = rollbackFlowCommand($this->site, ['install --no-dev']);

    $applied = updateFlowInvoke($command, 'withRollback', [$rollback, rollbackFlowSteps($command, $rollback, $this->archive, $this->site)]);

    expect($applied)->toBeFalse()
        ->and(updateFlowTree($this->site))->toBe($before)
        ->and(implode("\n", $command->lines))->toContain('ERROR The update failed: Command failed: composer install')
        ->and(implode("\n", $command->lines))->toContain('ERROR The site was put back as it was. Nothing was changed.');
});

test('a package that no longer boots after Composer puts every file back too', function () {
    $before = updateFlowTree($this->site);
    $rollback = new UpdateRollback($this->site, $this->site . '/core/.update-rollback-test');
    $command = rollbackFlowCommand($this->site, ['package:discover']);

    $applied = updateFlowInvoke($command, 'withRollback', [$rollback, rollbackFlowSteps($command, $rollback, $this->archive, $this->site)]);

    expect($applied)->toBeFalse()
        ->and($command->commands[array_key_last($command->commands)])->toBe('php artisan package:discover')
        ->and(updateFlowTree($this->site))->toBe($before);
});

test('an update that goes through keeps the new files and drops the journal', function () {
    $rollback = new UpdateRollback($this->site, $this->site . '/core/.update-rollback-test');
    $command = rollbackFlowCommand($this->site, []);

    $applied = updateFlowInvoke($command, 'withRollback', [$rollback, rollbackFlowSteps($command, $rollback, $this->archive, $this->site)]);

    expect($applied)->toBeTrue()
        ->and(file_get_contents($this->site . '/core/src/Core.php'))->toBe('new core')
        ->and(file_get_contents($this->site . '/install/index.php'))->toBe('new installer')
        ->and(is_dir($this->site . '/core/.update-rollback-test'))->toBeFalse();
});

test('placeholders the update removes come back with the rest', function () {
    file_put_contents($this->site . '/ht.access', 'shipped rules');
    file_put_contents($this->site . '/.htaccess', 'site rules');
    file_put_contents($this->site . '/composer.json', 'repository root');
    $before = updateFlowTree($this->site);
    $rollback = new UpdateRollback($this->site, $this->site . '/core/.update-rollback-test');
    $command = new class($this->site) extends SiteUpdateCommand {
        public function __construct(private string $site)
        {
            parent::__construct();
        }

        protected function updatePlaceholderFilePairs(): array
        {
            return [[$this->site . '/ht.access', $this->site . '/.htaccess']];
        }

        protected function updateDistributionFiles(): array
        {
            return [$this->site . '/composer.json'];
        }

        public function line($string, $style = null, $verbosity = null)
        {
        }

        public function error($string, $verbosity = null)
        {
        }
    };

    $applied = updateFlowInvoke($command, 'withRollback', [$rollback, function () use ($command, $rollback) {
        updateFlowInvoke($command, 'cleanupUpdatePlaceholderFiles', [$rollback]);
        expect(file_exists($this->site . '/ht.access'))->toBeFalse()
            ->and(file_exists($this->site . '/composer.json'))->toBeFalse();
        throw new RuntimeException('Composer stopped');
    }]);

    expect($applied)->toBeFalse()
        ->and(updateFlowTree($this->site))->toBe($before);
});

test('make:site runs every file-changing step inside the rollback and the migrations after it', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Console/SiteUpdateCommand.php');
    $guarded = substr($source, strpos($source, '$applied = $this->withRollback('));
    $guarded = substr($guarded, 0, strpos($guarded, 'if (!$applied)'));

    expect($guarded)->toContain('$this->protectUpdateState($rollback);')
        ->and($guarded)->toContain("self::moveFiles(\$temp_dir . '/' . \$dir, EVO_BASE_PATH, EVO_MANAGER_PATH, \$rollback);")
        ->and($guarded)->toContain('$this->applyNewFiles($targetRef, $updateRepository, $rollback);')
        ->and($guarded)->toContain('$this->installComposerDependencies($customPackageUpdate, $customPackageNames);')
        ->and(strpos($source, '$applied = $this->withRollback('))->toBeLessThan(strpos($source, '$this->runCoreMigrations();'))
        ->and(substr_count($source, '$this->installComposerDependencies('))->toBe(1);
});

test('make:site keeps a copy of vendor, the bootstrap cache and the version notes', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Console/SiteUpdateCommand.php');
    $protect = substr($source, strpos($source, 'protected function protectUpdateState('));
    $protect = substr($protect, 0, strpos($protect, "\n    }\n"));

    expect($protect)->toContain("snapshotDirectory(EVO_CORE_PATH . 'vendor')")
        ->and($protect)->toContain('snapshotDirectory(rtrim($this->bootstrapCachePath()')
        ->and($protect)->toContain('snapshotFile($this->versionFilePath())')
        ->and($protect)->toContain('snapshotFile(EVO_CORE_PATH . CustomComposerPackages::RECORD_FILE)');
});
