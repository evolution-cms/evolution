<?php namespace EvolutionCMS\Console\Presets;

use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class ApplyCommand extends Command
{
    protected $signature = 'preset:apply {--path= : Target Evo install path} {--source= : Preset source path} {--from= : Git repo URL or local path to preset} {--ref= : Git branch/tag (optional)} {--keep : Keep cloned preset directory} {--preset= : Preset name (auto-detect when omitted)} {--delete : Delete files not present in source} {--dry-run : Show actions without changing files} {--force : Run preset seeders without prompt} {--no-composer : Skip composer dump-autoload}';

    protected $description = 'Apply preset project-layer to an Evolution CMS install.';

    public function handle(): int
    {
        $sourceRoot = $this->option('source') ?: getcwd();
        $targetRoot = $this->option('path') ?: (defined('EVO_BASE_PATH') ? EVO_BASE_PATH : getcwd());
        $from = (string) $this->option('from');
        $ref = (string) $this->option('ref');
        $keep = (bool) $this->option('keep');
        $delete = (bool) $this->option('delete');
        $dryRun = (bool) $this->option('dry-run');
        $preset = $this->option('preset') ?: $this->env('EVO_PRESET_NAME', '');
        $skipComposer = (bool) $this->option('no-composer');
        $runSeed = $this->shouldRunSeed((bool) $this->option('force'));

        $sourceRoot = rtrim($this->normalizePath($sourceRoot), '/');
        $targetRoot = rtrim($this->normalizePath($targetRoot), '/');

        $tempPresetPath = '';
        if ($from !== '') {
            $sourceRoot = $this->resolvePresetSource($from, $ref, $keep);
            if ($sourceRoot === '') {
                return 1;
            }
            if (!$keep && is_dir($sourceRoot)) {
                $tempPresetPath = $sourceRoot;
            }
        }

        if (!is_dir($targetRoot . '/core') || !is_file($targetRoot . '/core/artisan')) {
            $this->error("Evo install not found at {$targetRoot} (missing core/artisan)." );
            return 1;
        }

        if ($preset === '') {
            $preset = $this->detectPresetName($sourceRoot);
            if ($preset === '') {
                $this->error('Preset name not found. Pass --preset or set EVO_PRESET_NAME.');
                return 1;
            }
        }

        $allowlist = [
            'core/custom',
            'views',
            'themes/' . $preset,
        ];

        try {
            $this->info("Applying preset from {$sourceRoot} to {$targetRoot}");

            foreach ($allowlist as $rel) {
                $source = $sourceRoot . '/' . $rel;
                if (!is_dir($source)) {
                    continue;
                }
                $target = $targetRoot . '/' . $rel;
                $this->syncDirectory($source, $target, $this->excludesFor($rel), $delete, $dryRun);
            }

            $this->writeControllerNamespace($targetRoot, $preset, $dryRun);

            if ($dryRun) {
                $this->info('Dry-run complete. No changes were applied.');
                return 0;
            }

            if (!$skipComposer) {
                $this->runComposerDumpAutoload($targetRoot);
            }

            $this->ensureServicesCacheFile($targetRoot);
            $this->call('package:discover');
            if ($runSeed) {
                $this->runPresetSeeder($preset, (bool) $this->option('force'), $targetRoot);
            }
            $this->call('cache:clear-full');

            $this->info('Preset applied.');
            return 0;
        } finally {
            if ($tempPresetPath !== '') {
                $this->removeDirectory($tempPresetPath);
            }
        }
    }

    private function env(string $key, string $default): string
    {
        $value = getenv($key);
        return $value === false || $value === '' ? $default : $value;
    }

    private function normalizePath(string $path): string
    {
        $real = realpath($path);
        if ($real !== false) {
            return str_replace('\\', '/', $real);
        }
        return str_replace('\\', '/', $path);
    }

    private function writeControllerNamespace(string $targetRoot, string $preset, bool $dryRun): void
    {
        $namespace = 'EvolutionCMS\\' . $this->studly($preset) . '\\Controllers\\';
        $path = rtrim($targetRoot, '/') . '/core/custom/config/cms/settings';
        $file = $path . '/ControllerNamespace.php';

        $this->line("Set ControllerNamespace: {$namespace}");

        if ($dryRun) {
            return;
        }

        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }

        $content = "<?php return '" . addslashes($namespace) . "';" . PHP_EOL;
        file_put_contents($file, $content);
    }

    private function studly(string $value): string
    {
        $value = preg_replace('/[^a-zA-Z0-9]+/', ' ', $value);
        $value = ucwords(strtolower(trim($value)));
        return str_replace(' ', '', $value);
    }

    private function detectPresetName(string $sourceRoot): string
    {
        $theme = $this->detectSingleDir($sourceRoot . '/themes');
        if ($theme !== '') {
            return $theme;
        }

        $package = $this->detectSingleDir($sourceRoot . '/core/custom/packages');
        if ($package !== '') {
            return $package;
        }

        return '';
    }

    private function detectSingleDir(string $path): string
    {
        if (!is_dir($path)) {
            return '';
        }

        $dirs = array_values(array_filter(scandir($path), function ($item) use ($path) {
            if ($item === '.' || $item === '..') {
                return false;
            }
            return is_dir($path . '/' . $item);
        }));

        return count($dirs) === 1 ? $dirs[0] : '';
    }

    private function resolvePresetSource(string $from, string $ref, bool $keep): string
    {
        if (is_dir($from)) {
            return rtrim($this->normalizePath($from), '/');
        }

        $git = $this->findBinary('git');
        if ($git === '') {
            $this->error('Git not found. Install git or pass a local path via --from.');
            return '';
        }

        $tmpBase = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
        $hash = substr(sha1($from . '|' . $ref . '|' . microtime(true)), 0, 10);
        $dest = $tmpBase . DIRECTORY_SEPARATOR . 'evo-preset-' . $hash;

        $this->line("Cloning preset from {$from}");

        $cmd = [$git, 'clone', '--depth', '1'];
        if ($ref !== '') {
            $cmd[] = '--branch';
            $cmd[] = $ref;
        }
        $cmd[] = $from;
        $cmd[] = $dest;

        $process = new Process($cmd, $tmpBase, null, null, null);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error('Failed to clone preset repository.');
            return '';
        }

        if ($keep) {
            $this->line("Preset cloned to {$dest}");
        }

        return $dest;
    }

    private function findBinary(string $name): string
    {
        $finder = new ExecutableFinder();
        return $finder->find($name) ?: '';
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($path);
    }

    private function runComposerDumpAutoload(string $targetRoot): void
    {
        $corePath = rtrim($targetRoot, '/') . '/core';
        $composer = $this->env('EVO_COMPOSER', '');

        if ($composer === '') {
            $finder = new ExecutableFinder();
            $composer = $finder->find('composer') ?: '';
        }

        if ($composer === '') {
            $this->warn('Composer not found. Skipping dump-autoload.');
            return;
        }

        $this->line('Running: composer dump-autoload -o');
        $process = new Process([$composer, 'dump-autoload', '-o'], $corePath, null, null, null);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->warn('composer dump-autoload failed.');
        }
    }

    private function runPresetSeeder(string $preset, bool $force, string $targetRoot): void
    {
        $class = 'EvolutionCMS\\' . $this->studly($preset) . '\\Seeders\\HomeTemplateSeeder';
        $seederPath = rtrim($targetRoot, '/') . '/core/custom/packages/' . $preset . '/src/Seeders/HomeTemplateSeeder.php';
        if (!is_file($seederPath)) {
            $this->warn("Preset seeder not found: {$class}");
            return;
        }

        $this->line("Running preset seeder: {$class}");
        $php = PHP_BINARY ?: $this->findBinary('php');
        if ($php === '') {
            $this->warn('PHP binary not found. Skipping preset seeder.');
            return;
        }

        $artisan = rtrim($targetRoot, '/') . '/core/artisan';
        if (!is_file($artisan)) {
            $this->warn('Artisan not found. Skipping preset seeder.');
            return;
        }

        $corePath = rtrim($targetRoot, '/') . '/core';
        $cmd = [$php, $artisan, 'db:seed', '--class=' . $class, '--force'];

        $process = new Process($cmd, $corePath, null, null, null);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->warn('Preset seeder failed.');
        }
    }

    private function shouldRunSeed(bool $force): bool
    {
        if ($force) {
            return true;
        }

        while (true) {
            $answer = strtolower(trim((string) $this->ask('Run preset seeders now? [Y/n]', 'y')));
            if ($answer === '' || $answer === 'y' || $answer === 'yes') {
                return true;
            }
            if ($answer === 'n' || $answer === 'no') {
                return false;
            }

            $this->warn('Please answer y or n.');
        }
    }

    private function ensureServicesCacheFile(string $targetRoot): void
    {
        $path = rtrim($targetRoot, '/') . '/core/storage/bootstrap';
        $file = $path . '/services.php';

        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }

        if (!file_exists($file)) {
            file_put_contents($file, "<?php return [];" . PHP_EOL);
        }
    }


    /**
     * @return string[]
     */
    private function excludesFor(string $rel): array
    {
        $common = ['.env', '.DS_Store'];

        if (str_starts_with($rel, 'core/custom')) {
            return array_merge($common, [
                'cache',
                'storage',
                'logs',
                'config/app/providers',
                'config/app/aliases',
            ]);
        }

        if (str_starts_with($rel, 'assets')) {
            return array_merge($common, [
                'cache',
                'temp',
                'backup',
                'export',
                'import',
            ]);
        }

        return $common;
    }

    /**
     * @param string[] $excludes
     */
    private function syncDirectory(string $source, string $target, array $excludes, bool $delete, bool $dryRun): void
    {
        $this->line("Sync: {$source} -> {$target}");

        if (!is_dir($target) && !$dryRun) {
            mkdir($target, 0775, true);
        }

        $sourceIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($sourceIterator as $item) {
            /** @var SplFileInfo $item */
            $relative = ltrim(str_replace($source, '', $item->getPathname()), '/');
            $relative = str_replace('\\', '/', $relative);
            if ($this->shouldExclude($relative, $excludes)) {
                continue;
            }

            $destPath = $target . '/' . $relative;
            if ($item->isDir()) {
                if (!is_dir($destPath) && !$dryRun) {
                    mkdir($destPath, 0775, true);
                }
                continue;
            }

            if ($dryRun) {
                $this->line("  copy {$relative}");
                continue;
            }

            if (!is_dir(dirname($destPath))) {
                mkdir(dirname($destPath), 0775, true);
            }
            copy($item->getPathname(), $destPath);
        }

        if (!$delete) {
            return;
        }

        $targetIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($targetIterator as $item) {
            /** @var SplFileInfo $item */
            $relative = ltrim(str_replace($target, '', $item->getPathname()), '/');
            $relative = str_replace('\\', '/', $relative);
            if ($this->shouldExclude($relative, $excludes)) {
                continue;
            }

            $sourcePath = $source . '/' . $relative;
            if ($item->isDir()) {
                if (!is_dir($sourcePath) && !$dryRun) {
                    @rmdir($item->getPathname());
                }
                continue;
            }

            if (!is_file($sourcePath) && !$dryRun) {
                @unlink($item->getPathname());
            }
        }
    }

    /**
     * @param string[] $excludes
     */
    private function shouldExclude(string $relative, array $excludes): bool
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        foreach ($excludes as $exclude) {
            $exclude = trim(str_replace('\\', '/', $exclude), '/');
            if ($relative === $exclude || str_starts_with($relative, $exclude . '/')) {
                return true;
            }
        }
        return false;
    }
}
