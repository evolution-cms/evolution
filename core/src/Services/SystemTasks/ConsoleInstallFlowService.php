<?php namespace EvolutionCMS\Services\SystemTasks;

use EvolutionCMS\Models\SystemCliTask;
use EvolutionCMS\Services\Store\CatalogService;
use Symfony\Component\Process\Process;

class ConsoleInstallFlowService
{
    protected CatalogService $catalogService;

    public function __construct(?CatalogService $catalogService = null)
    {
        $this->catalogService = $catalogService ?: new CatalogService();
    }

    public function execute(SystemCliTask $task, ?callable $report = null)
    {
        $snapshot = is_array($task->payload_json) ? $task->payload_json : [];
        $composerName = trim((string) ($snapshot['composer_name'] ?? $task->target));
        $resolvedVersion = trim((string) ($snapshot['resolved_version'] ?? $task->requested_version));
        $composerVersion = trim((string) ($snapshot['composer_version'] ?? $resolvedVersion));

        if ($composerName === '' || $resolvedVersion === '' || $composerVersion === '') {
            throw new \RuntimeException('Console install snapshot is incomplete.');
        }

        $this->report($report, 'preflight', 10, 'Validated console install snapshot.');

        $this->runArtisanCommand(
            'package:installrequire',
            [
                'key' => $composerName,
                'value' => $composerVersion,
                'composer_run' => 1,
            ],
            'install_require',
            30,
            'Updating composer requirements for ' . $composerName . '.',
            $report
        );

        $this->runArtisanCommand(
            'package:discover',
            [],
            'discover',
            50,
            'Refreshing package discovery metadata.',
            $report
        );

        $providers = $this->getPublishProviders($composerName);
        if ($providers !== []) {
            foreach ($providers as $index => $provider) {
                $stepProgress = 55 + (int) floor((($index + 1) / max(count($providers), 1)) * 20);
                $this->runArtisanCommand(
                    'vendor:publish',
                    ['--provider' => $provider],
                    'publish',
                    $stepProgress,
                    'Publishing assets for ' . $provider . '.',
                    $report
                );
            }
        } else {
            $this->report($report, 'publish', 70, 'No publish providers were detected for this package.');
        }

        $this->runArtisanCommand(
            'migrate',
            ['--force' => true],
            'migrate',
            85,
            'Running database migrations.',
            $report
        );

        $this->report($report, 'finalize', 95, 'Console install flow completed.');

        return [
            'message' => 'Console package installed successfully.',
            'result' => [
                'task_type' => 'console_install',
                'composer_name' => $composerName,
                'resolved_version' => $resolvedVersion,
                'composer_version' => $composerVersion,
                'published_providers' => $providers,
            ],
        ];
    }

    protected function runArtisanCommand($command, array $arguments, $step, $progress, $message, ?callable $report = null)
    {
        $this->report($report, $step, $progress, $message);

        $process = new Process($this->buildArtisanProcessArguments($command, $arguments), EVO_CORE_PATH, null, null, null);
        $process->setTimeout(null);
        $process->run();

        $exitCode = $process->getExitCode();
        $output = trim($process->getOutput() . "\n" . $process->getErrorOutput());

        if ($output !== '') {
            $this->report($report, $step, $progress, $this->summarizeOutput($output), 'info', [
                'command' => $command,
            ]);
        }

        if ((int) $exitCode !== 0) {
            $reason = $this->summarizeOutput($output);
            if ($reason !== '') {
                throw new \RuntimeException($command . ' failed with exit code ' . (int) $exitCode . '. ' . $reason);
            }

            throw new \RuntimeException($command . ' failed with exit code ' . (int) $exitCode . '.');
        }
    }

    protected function buildArtisanProcessArguments($command, array $arguments)
    {
        $parts = [PHP_BINARY, EVO_CORE_PATH . 'artisan', $command];

        foreach ($arguments as $key => $value) {
            if (is_string($key) && str_starts_with($key, '--')) {
                if ($value === true || $value === 1 || $value === '1') {
                    $parts[] = $key;
                    continue;
                }

                if ($value === false || $value === null || $value === '') {
                    continue;
                }

                $parts[] = $key . '=' . (string) $value;
                continue;
            }

            $parts[] = (string) $value;
        }

        return $parts;
    }

    protected function getPublishProviders($packageName)
    {
        $composer = $this->getPackageComposer($packageName);
        if (!$composer) {
            return [];
        }

        $providers = array_merge(
            $this->extractProviders($composer),
            $this->getDependencyProviders($composer, [$packageName => true])
        );

        $providers = array_filter($providers, 'is_string');
        return array_values(array_unique($providers));
    }

    protected function getDependencyProviders(array $composer, array $visited = [])
    {
        $requires = $composer['require'] ?? [];
        if (!is_array($requires) || $requires === []) {
            return [];
        }

        $providers = [];
        $catalogPackages = $this->getConsoleCatalogPackageNames();
        $useCatalog = $catalogPackages !== [];

        foreach (array_keys($requires) as $dependency) {
            if (!is_string($dependency)) {
                continue;
            }

            $dependency = trim($dependency);
            if (!$this->isComposerDependencyName($dependency) || isset($visited[$dependency])) {
                continue;
            }

            $visited[$dependency] = true;
            $depComposer = $this->getPackageComposer($dependency);
            if (!$depComposer) {
                continue;
            }

            if ($useCatalog) {
                if (!isset($catalogPackages[strtolower($dependency)])) {
                    continue;
                }
            } elseif (!$this->isEvoPackageType($depComposer['type'] ?? null)) {
                continue;
            }

            $providers = array_merge($providers, $this->extractProviders($depComposer));
            $providers = array_merge($providers, $this->getDependencyProviders($depComposer, $visited));
        }

        return array_values(array_unique(array_filter($providers, 'is_string')));
    }

    protected function getConsoleCatalogPackageNames()
    {
        $packages = [];
        foreach ($this->catalogService->getConsoleCatalog() as $item) {
            if (!is_array($item)) {
                continue;
            }

            $composerName = trim((string) ($item['composer_name'] ?? ''));
            if ($composerName !== '') {
                $packages[strtolower($composerName)] = true;
            }
        }

        return $packages;
    }

    protected function getPackageComposer($packageName)
    {
        $composerPath = $this->getPackageComposerPath($packageName);
        if ($composerPath === '' || !file_exists($composerPath)) {
            return null;
        }

        $raw = file_get_contents($composerPath);
        $composer = json_decode($raw, true);

        return is_array($composer) ? $composer : null;
    }

    protected function getPackageComposerPath($packageName)
    {
        if (class_exists('\\Composer\\InstalledVersions')) {
            try {
                $path = \Composer\InstalledVersions::getInstallPath($packageName);
                if (is_string($path) && $path !== '') {
                    return rtrim($path, '/\\') . '/composer.json';
                }
            } catch (\Throwable $exception) {
                // fall back to vendor path
            }
        }

        return EVO_CORE_PATH . 'vendor/' . $packageName . '/composer.json';
    }

    protected function extractProviders(array $composer)
    {
        $laravelProviders = $composer['extra']['laravel']['providers'] ?? [];
        $evolutionProviders = $composer['extra']['evolution']['providers'] ?? [];
        $providers = array_merge((array) $laravelProviders, (array) $evolutionProviders);
        $providers = array_filter($providers, 'is_string');

        return array_values(array_unique($providers));
    }

    protected function isEvoPackageType($type)
    {
        if (!is_string($type) || $type === '') {
            return false;
        }

        return str_starts_with($type, 'evolutioncms-') || str_starts_with($type, 'evolution-cms-');
    }

    protected function isComposerDependencyName(string $name): bool
    {
        if ($name === '' || $name === 'php' || $name === 'composer-plugin-api') {
            return false;
        }
        if (str_starts_with($name, 'ext-') || str_starts_with($name, 'lib-')) {
            return false;
        }

        return true;
    }

    protected function summarizeOutput($output)
    {
        $lines = preg_split('/\r\n|\r|\n/', trim((string) $output));
        $lines = array_values(array_filter(array_map(function ($line) {
            $line = trim((string) $line);
            $line = preg_replace('~^Evolution CMS \d+\.\d+\.\d+(?:\.\d+)?\s*~', '', $line);
            return trim((string) $line);
        }, $lines), function ($line) {
            if ($line === '') {
                return false;
            }

            if (preg_match('~^Evolution CMS \d+\.\d+\.\d+(\.\d+)?$~', $line)) {
                return false;
            }

            if (preg_match('~^[A-Za-z0-9_\\\\]+(?:\s+[A-Za-z0-9_\\\\]+)+$~', $line)) {
                return false;
            }

            if (substr_count($line, '\\') >= 2 && strpos($line, ' ') === false) {
                return false;
            }

            return true;
        }));

        $output = implode(' ', array_slice($lines, 0, 3));
        if ($output === '') {
            return '';
        }

        if (mb_strlen($output) > 400) {
            return mb_substr($output, 0, 397) . '...';
        }

        return $output;
    }

    protected function report(?callable $report = null, $step = '', $progress = 0, $message = '', $level = 'info', array $context = [])
    {
        if ($report) {
            $report((string) $step, (int) $progress, (string) $message, (string) $level, $context);
        }
    }
}
