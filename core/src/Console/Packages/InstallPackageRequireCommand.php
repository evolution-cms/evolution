<?php namespace EvolutionCMS\Console\Packages;


use Composer\Console\Application;
use EvolutionCMS\Traits\RunsComposerShell;
use ExecWithFallback\ExecWithFallback;
use Illuminate\Console\Command;
use \EvolutionCMS;
use Symfony\Component\Console\Input\ArrayInput;

class InstallPackageRequireCommand extends Command
{
    use RunsComposerShell;

    /**
     * Composer release that introduced `update --minimal-changes`.
     */
    public const MINIMAL_CHANGES_SINCE = '2.7.0';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'package:installrequire {key} {value} {composer_run=1} {--no-dev : Skip installing packages listed in require-dev} {--optimize-autoloader : Optimize Composer autoload files after update} {--keep-dependencies : Leave installed dependencies as they are, so a package from a local artifact repository installs without network access}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install composer package';

    /**
     * Custom composer.json
     * @var string
     */
    protected $composer = EVO_CORE_PATH . 'custom/composer.json';

    /**
     * Lock file Composer rewrites during the update.
     * @var string
     */
    protected $composerLock = EVO_CORE_PATH . 'composer.lock';

    /**
     * Packages touched by updateArray(); scopes the composer update to them.
     * @var array<int,string>
     */
    protected $affectedPackages = [];

    /**
     * Resolved by composerProcessCommand(); false until it has been asked.
     * @var string|null|false
     */
    protected $composerProcessCommand = false;

    /**
     * @var array
     */
    public $composerArray = [
        'name' => 'evolutioncms/custom',
        'require' => [],
        'autoload' => [
            'psr-4' => []
        ]];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $composerExisted = file_exists($this->composer);
        $originalComposerContents = $composerExisted ? file_get_contents($this->composer) : null;
        $originalLockContents = is_file($this->composerLock) ? file_get_contents($this->composerLock) : null;

        $this->checkFile();
        if ($this->updateArray() === false) {
            return self::FAILURE;
        }
        $this->putComposer();
        if ($this->argument('composer_run') == 1) {
            $exitCode = (int) $this->runComposer();
            if ($exitCode === 0 && !$this->applicationBoots()) {
                $this->error('The site no longer boots after the Composer update.');
                $exitCode = self::FAILURE;
            }

            if ($exitCode !== 0) {
                $this->restoreComposerState($composerExisted, $originalComposerContents);
                $this->rollbackVendor($originalLockContents);
            }

            return $exitCode;
        }

        return self::SUCCESS;
    }

    public function checkFile()
    {
        if (file_exists($this->composer)) {
            $composerData = file_get_contents($this->composer);
            $this->composerArray = json_decode($composerData, true);
        }
    }

    public function updateArray()
    {
        $this->composerArray['require'][$this->argument('key')] = $this->argument('value');
        $this->affectedPackages[] = (string) $this->argument('key');
    }

    public function putComposer()
    {
        file_put_contents($this->composer, json_encode($this->composerArray, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    /**
     * Run Composer update for the modified custom package requirements.
     *
     * Composer runs as a separate process whenever PHP may start one. Run inside this process,
     * it replaces the vendor files this very process is loading classes from, and a class loaded
     * lazily halfway through the update (symfony/finder, say) is already gone: every remaining
     * operation fails and the site is left without a working vendor directory. The in-process
     * run is kept only for hosts that disable exec(), proc_open(), popen() and passthru() alike.
     *
     * @return int Composer process exit code.
     */
    public function runComposer()
    {
        putenv('COMPOSER_HOME=' . EVO_CORE_PATH . 'composer');

        $composerCommand = $this->composerProcessCommand();
        if ($composerCommand === null) {
            $this->warn('No way to start a process is enabled; running Composer inside the current process.');

            return $this->runComposerInProcess($this->buildComposerArguments(
                $this->supportsMinimalChanges(\Composer\Composer::VERSION)
            ));
        }

        $arguments = $this->buildComposerArguments(
            $this->supportsMinimalChanges($this->composerVersion($composerCommand))
        );

        return $this->runComposerProcess($composerCommand, $arguments);
    }

    /**
     * Run Composer with the given arguments in the current process.
     *
     * @since 3.5.9
     * @param array<string,mixed> $arguments
     * @return int Composer exit code.
     */
    protected function runComposerInProcess(array $arguments): int
    {
        $input = new ArrayInput($arguments);
        $application = new Application();
        $application->setAutoExit(false);
        $originalCwd = function_exists('getcwd') ? getcwd() : false;

        if (is_string($originalCwd) && $originalCwd !== '') {
            chdir(EVO_CORE_PATH);
        }

        try {
            return (int) $application->run($input);
        } finally {
            if (is_string($originalCwd) && $originalCwd !== '') {
                chdir($originalCwd);
            }
        }
    }

    /**
     * Run Composer with the given arguments as a separate process.
     *
     * @since 3.5.9
     * @param string $composerCommand Composer command safe for shell usage.
     * @param array<string,mixed> $arguments
     * @return int Composer exit code.
     */
    protected function runComposerProcess(string $composerCommand, array $arguments): int
    {
        $output = [];
        $exitCode = $this->execCoreShellCommand(
            $composerCommand . ' ' . $this->buildComposerShellArguments($arguments),
            $output
        );

        foreach ($output as $line) {
            $this->line((string) $line);
        }

        return $exitCode;
    }

    /**
     * Pick the Composer to start as a separate process.
     *
     * A standalone Composer comes first: it does not load a single file from the vendor
     * directory it rewrites. The vendor copy is the last resort; as a separate process it
     * still leaves this command alive to roll back when an update breaks Composer itself.
     *
     * @since 3.5.9
     * @return string|null Composer command safe for shell usage, or null when no process can be started.
     */
    protected function composerProcessCommand(): ?string
    {
        if ($this->composerProcessCommand !== false) {
            return $this->composerProcessCommand;
        }

        $this->composerProcessCommand = null;
        if (!ExecWithFallback::anyAvailable()) {
            return null;
        }

        $this->composerProcessCommand = $this->resolveComposerBinaryCommand();
        if ($this->composerProcessCommand !== null) {
            return $this->composerProcessCommand;
        }

        $vendorComposer = EVO_CORE_PATH . 'vendor/bin/composer';
        if (is_file($vendorComposer)) {
            $this->warn('Standalone Composer not found; using the copy from the vendor directory.');
            $this->composerProcessCommand = $this->phpBinaryCommand() . ' ' . escapeshellarg($vendorComposer);
        }

        return $this->composerProcessCommand;
    }

    /**
     * Build the composer update arguments.
     *
     * The update is limited to the changed packages and their dependencies,
     * like `composer require`/`remove` do. A bare `update` would also bump every
     * core dependency, including composer/composer running this very process.
     *
     * @param bool $minimalChanges Whether the Composer that runs them knows `--minimal-changes`.
     * @return array<string,mixed>
     */
    public function buildComposerArguments(bool $minimalChanges = false): array
    {
        $arguments = ['command' => 'update'];

        $packages = array_values(array_unique(array_filter(array_map('trim', $this->affectedPackages))));
        if ($packages !== []) {
            $arguments['packages'] = $packages;
            if (!$this->commandOptionEnabled('keep-dependencies')) {
                $arguments['--with-dependencies'] = true;
            }
            // Dependencies shared with the rest of the site move only as far as the
            // changed package needs them to, not to their newest release.
            if ($minimalChanges) {
                $arguments['--minimal-changes'] = true;
            }
        }

        if ($this->commandOptionEnabled('no-dev')) {
            $arguments['--no-dev'] = true;
        }
        if ($this->commandOptionEnabled('optimize-autoloader')) {
            $arguments['--optimize-autoloader'] = true;
        }

        return $arguments;
    }

    /**
     * Turn the arguments of buildComposerArguments() into a shell command line.
     *
     * @since 3.5.9
     * @param array<string,mixed> $arguments
     * @return string
     */
    public function buildComposerShellArguments(array $arguments): string
    {
        $parts = [escapeshellarg((string) ($arguments['command'] ?? 'update'))];

        foreach ((array) ($arguments['packages'] ?? []) as $package) {
            $parts[] = escapeshellarg((string) $package);
        }

        foreach ($arguments as $key => $value) {
            if (is_string($key) && str_starts_with($key, '--') && $value === true) {
                $parts[] = $key;
            }
        }

        $parts[] = '--no-interaction';
        $parts[] = '--no-ansi';

        return implode(' ', $parts);
    }

    /**
     * Whether a Composer version knows `update --minimal-changes`.
     *
     * @since 3.5.9
     * @param string|null $version Composer version, null when unknown.
     * @return bool
     */
    public function supportsMinimalChanges(?string $version): bool
    {
        return $version !== null && version_compare($version, self::MINIMAL_CHANGES_SINCE, '>=');
    }

    /**
     * Check that the site still boots after an update, in a fresh process.
     *
     * This process keeps the classes it loaded before the update, so only a new
     * process can tell whether the rewritten vendor directory still works. Without
     * a way to start one there is nothing to check with, and the update stands.
     *
     * @since 3.5.9
     * @return bool
     */
    protected function applicationBoots(): bool
    {
        if (!ExecWithFallback::anyAvailable()) {
            return true;
        }

        $output = [];
        $exitCode = $this->execCoreShellCommand($this->phpBinaryCommand() . ' artisan --version --no-ansi', $output);
        if ($exitCode !== 0) {
            foreach (array_slice($output, -8) as $line) {
                $this->line((string) $line);
            }
        }

        return $exitCode === 0;
    }

    /**
     * Put vendor back the way the lock file described it before the update.
     *
     * Restoring custom/composer.json alone is not enough: Composer has already written
     * the new lock file and replaced part of vendor. `composer install` from the old lock
     * file downgrades what was updated and reinstalls what a failed step left half removed.
     *
     * @since 3.5.9
     * @param string|null $originalLockContents Lock file contents before the update, null when there was none.
     * @return void
     */
    protected function rollbackVendor(?string $originalLockContents): void
    {
        if ($originalLockContents === null) {
            return;
        }

        file_put_contents($this->composerLock, $originalLockContents);
        $this->warn('Composer update failed; restoring the previous dependencies.');

        $arguments = ['command' => 'install'];
        if ($this->commandOptionEnabled('no-dev')) {
            $arguments['--no-dev'] = true;
        }

        $composerCommand = $this->composerProcessCommand();
        try {
            $exitCode = $composerCommand === null
                ? $this->runComposerInProcess($arguments)
                : $this->runComposerProcess($composerCommand, $arguments);
        } catch (\Throwable $exception) {
            $exitCode = self::FAILURE;
            $this->line($exception->getMessage());
        }

        if ($exitCode !== 0) {
            $this->error('Restoring the previous dependencies failed. Run "composer install" in ' . EVO_CORE_PATH . ' manually.');
        }
    }

    protected function commandOptionEnabled(string $name): bool
    {
        return $this->hasCommandOption($name) && (bool) $this->option($name);
    }

    protected function hasCommandOption(string $name): bool
    {
        return $this->getDefinition()->hasOption($name);
    }

    protected function restoreComposerState($composerExisted, $originalComposerContents)
    {
        if ($composerExisted) {
            file_put_contents($this->composer, (string) $originalComposerContents);
            return;
        }

        if (file_exists($this->composer)) {
            @unlink($this->composer);
        }
    }
}
