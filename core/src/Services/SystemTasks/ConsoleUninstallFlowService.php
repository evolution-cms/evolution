<?php namespace EvolutionCMS\Services\SystemTasks;

use EvolutionCMS\Models\SystemCliTask;
use Symfony\Component\Process\Process;

class ConsoleUninstallFlowService
{
    protected string $corePath;
    protected string $providersDir;
    protected string $aliasesDir;
    protected string $servicesCache;
    protected string $legacyAliasesCache;

    public function __construct()
    {
        $corePath = defined('EVO_CORE_PATH')
            ? EVO_CORE_PATH
            : dirname(__DIR__, 2) . '/';

        $this->corePath = $corePath;
        $this->providersDir = $corePath . 'custom/config/app/providers/';
        $this->aliasesDir = $corePath . 'custom/config/app/aliases/';
        $this->servicesCache = $corePath . 'storage/bootstrap/services.php';
        $this->legacyAliasesCache = $corePath . 'includes/aliases.inc.php';
    }

    public function execute(SystemCliTask $task, ?callable $report = null)
    {
        $snapshot = is_array($task->payload_json) ? $task->payload_json : [];
        $composerName = trim((string) ($snapshot['composer_name'] ?? $task->target));
        $resolvedVersion = trim((string) ($snapshot['resolved_version'] ?? $task->requested_version));

        if ($composerName === '') {
            throw new \RuntimeException('Console uninstall snapshot is incomplete.');
        }

        $discoveryArtifacts = $this->captureDiscoveryArtifacts($composerName, $snapshot);

        $this->report($report, 'preflight', 10, 'Validated console uninstall snapshot.');
        $this->report($report, 'cleanup_discovery', 20, 'Preparing package discovery files for uninstall.');

        $this->purgeInvalidDiscoveryArtifacts($report);
        $this->removeDiscoveryArtifacts($discoveryArtifacts);

        try {
            $this->runArtisanCommand(
                'package:removerequire',
                [
                    'key' => $composerName,
                    'composer_run' => 1,
                ],
                'remove_require',
                55,
                'Removing composer requirement for ' . $composerName . '.',
                $report
            );

            $this->runArtisanCommand(
                'package:discover',
                [],
                'discover',
                80,
                'Refreshing package discovery metadata after uninstall.',
                $report
            );
        } catch (\Throwable $exception) {
            $this->restoreDiscoveryArtifacts($discoveryArtifacts);
            throw $exception;
        }

        $this->report($report, 'finalize', 95, 'Console uninstall flow completed.');

        return [
            'message' => 'Console package removed from composer successfully.',
            'result' => [
                'task_type' => 'console_uninstall',
                'composer_name' => $composerName,
                'resolved_version' => $resolvedVersion,
                'artifacts_cleaned' => false,
            ],
        ];
    }

    protected function runArtisanCommand($command, array $arguments, $step, $progress, $message, ?callable $report = null)
    {
        $this->report($report, $step, $progress, $message);

        $process = new Process($this->buildArtisanProcessArguments($command, $arguments), $this->corePath, null, null, null);
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
        $parts = [PHP_BINARY, $this->corePath . 'artisan', $command];

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

        if ($lines === []) {
            return '';
        }

        return implode(' ', array_slice($lines, 0, 3));
    }

    protected function purgeInvalidDiscoveryArtifacts(?callable $report = null)
    {
        $stalePaths = [];

        foreach (glob($this->providersDir . '*.php') ?: [] as $path) {
            $providerClass = $this->readPhpReturnString($path);
            if ($providerClass !== '' && !class_exists($providerClass)) {
                $stalePaths[] = $path;
            }
        }

        foreach (glob($this->aliasesDir . '*.php') ?: [] as $path) {
            $aliasClass = $this->readPhpReturnString($path);
            if ($aliasClass !== '' && !class_exists($aliasClass)) {
                $stalePaths[] = $path;
            }
        }

        $stalePaths[] = $this->servicesCache;
        $stalePaths[] = $this->legacyAliasesCache;
        $stalePaths = array_values(array_unique(array_filter($stalePaths, 'is_string')));

        foreach ($stalePaths as $path) {
            if ($path !== '' && file_exists($path)) {
                @unlink($path);
                $this->report($report, 'cleanup_discovery', 20, 'Removed stale discovery artifact: ' . basename($path) . '.', 'info');
            }
        }
    }

    protected function readPhpReturnString($path)
    {
        if (!is_string($path) || $path === '' || !file_exists($path)) {
            return '';
        }

        try {
            $value = include $path;
            return is_string($value) ? trim($value) : '';
        } catch (\Throwable $exception) {
            return '';
        }
    }

    protected function report(?callable $report, $step, $progress, $message, $level = 'info', array $context = [])
    {
        if ($report !== null) {
            $report((string) $step, (int) $progress, (string) $message, (string) $level, $context);
        }
    }

    protected function captureDiscoveryArtifacts($packageName, array $snapshot = [])
    {
        $composer = $this->getPackageComposer($packageName);
        $artifacts = [];

        foreach ($this->resolveProviderConfigFiles($composer) as $path) {
            $artifacts[$path] = file_exists($path) ? file_get_contents($path) : null;
        }

        foreach ($this->resolveAliasConfigFiles($composer) as $path) {
            $artifacts[$path] = file_exists($path) ? file_get_contents($path) : null;
        }

        foreach (($snapshot['cleanup_files'] ?? []) as $path) {
            if (!is_string($path) || trim($path) === '') {
                continue;
            }
            $artifacts[$path] = file_exists($path) ? file_get_contents($path) : null;
        }

        $artifacts[$this->servicesCache] = file_exists($this->servicesCache)
            ? file_get_contents($this->servicesCache)
            : null;

        return $artifacts;
    }

    protected function removeDiscoveryArtifacts(array $artifacts)
    {
        foreach (array_keys($artifacts) as $path) {
            if ($path !== '' && file_exists($path)) {
                @unlink($path);
            }
        }
    }

    protected function restoreDiscoveryArtifacts(array $artifacts)
    {
        foreach ($artifacts as $path => $contents) {
            if ($path === '') {
                continue;
            }

            if ($contents === null) {
                if (file_exists($path)) {
                    @unlink($path);
                }
                continue;
            }

            $directory = dirname($path);
            if (!is_dir($directory)) {
                @mkdir($directory, 0775, true);
            }

            file_put_contents($path, $contents);
        }
    }

    protected function resolveProviderConfigFiles(?array $composer)
    {
        if (!$composer || !isset($composer['extra']['laravel']['providers']) || !is_array($composer['extra']['laravel']['providers'])) {
            return [];
        }

        $priorities = $composer['extra']['laravel']['priority'] ?? [];
        $files = [];

        foreach ($composer['extra']['laravel']['providers'] as $provider) {
            if (!is_string($provider) || trim($provider) === '') {
                continue;
            }

            $className = basename(str_replace('\\', '/', trim($provider)));
            $priority = isset($priorities[$provider]) ? (int) $priorities[$provider] : 0;

            if ($priority > 0 && $priority < 1000) {
                $files[] = $this->providersDir . str_pad((string) $priority, 3, '0', STR_PAD_LEFT) . '_' . $className . '.php';
            }

            $files[] = $this->providersDir . $className . '.php';

            foreach (glob($this->providersDir . '*_' . $className . '.php') ?: [] as $matched) {
                $files[] = $matched;
            }
        }

        return array_values(array_unique(array_filter($files, 'is_string')));
    }

    protected function resolveAliasConfigFiles(?array $composer)
    {
        if (!$composer || !isset($composer['extra']['laravel']['aliases']) || !is_array($composer['extra']['laravel']['aliases'])) {
            return [];
        }

        $files = [];
        foreach (array_keys($composer['extra']['laravel']['aliases']) as $alias) {
            if (!is_string($alias) || trim($alias) === '') {
                continue;
            }

            $fileName = preg_replace('~[^A-Za-z0-9_]+~', '', trim($alias)) . '.php';
            if ($fileName === '.php') {
                continue;
            }

            $files[] = $this->aliasesDir . $fileName;
        }

        return array_values(array_unique($files));
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

        return $this->corePath . 'vendor/' . $packageName . '/composer.json';
    }
}
