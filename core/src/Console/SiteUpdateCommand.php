<?php namespace EvolutionCMS\Console;

use EvolutionCMS\Models\Category;
use EvolutionCMS\Models\SiteModule;
use Illuminate\Console\Command;

/**
 * @see: https://github.com/laravel-zero/foundation/blob/9.x/src/Illuminate/Foundation/Console/ClearCompiledCommand.php
 */
class SiteUpdateCommand extends Command
{
    /**
     * Default GitHub repository used for core updates when no custom repository is configured.
     */
    protected const DEFAULT_UPGRADE_REPOSITORY = 'evolution-cms/evolution';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'make:site
                            {command_site=update : Update action to run. Keep "update" for normal core updates}
                            {version=null : Optional tag, branch or commit hash. Examples: 3.5.4, 3.5.x, 922ece660}
                            {--repository= : Optional GitHub repository slug. Example: middleDuckAi/evolution}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update site';

    /**
     * Additional help shown by the Artisan help command.
     *
     * @var string
     */
    protected $help = <<<'HELP'
Downloads and applies an Evolution CMS core update package from GitHub.

If no version is provided, the command installs the latest stable tag
available for the current major version.

You can also request a specific ref manually:

  php artisan make:site
    Update to the latest stable tag for the current major version.

  php artisan make:site update 3.5.4
    Update to a specific release tag.

  php artisan make:site update 3.5.x
    Update to the current HEAD of the 3.5.x branch.

  php artisan make:site update 3.5.x --repository=middleDuckAi/evolution
    Update from a branch in a custom repository, useful for testing a fork.

  php artisan make:site update 922ece66071acecaea9afb8486791738acc6de5e
    Update to a specific commit.
HELP;

    /**
     * Create a new site update command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        try {
            switch ($this->argument('command_site')) {
                case 'pizdato':
                    echo 'Remove MODX REVO and install Evolution CMS' . "\n";
                    $this->startUpdate();
                    return self::SUCCESS;

                case 'update':
                    $this->startUpdate();
                    return self::SUCCESS;
            }
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        return self::FAILURE;
    }

    /**
     * Download, unpack and apply the requested Evolution CMS update package.
     *
     * When no explicit version/ref is provided, the command falls back to the latest
     * stable tag for the current major version reported by GitHub.
     *
     * @since 3.5.5 Added support for branch and commit refs in manual update requests.
     * @return void
     */
    public function startUpdate()
    {
        $evo = evo();
        $updateRepository = $this->resolveUpdateRepository();
        $ch = curl_init();
        $url = 'https://api.github.com/repos/' . $updateRepository . '/tags';
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: updateNotify widget']);
        $info = curl_exec($ch);
        curl_close($ch);
        if (substr($info, 0, 1) != '[') {
            return;
        }
        $currentVersion = $evo->getVersionData();
        $arrayVersion = explode('.', $currentVersion['version']);
        $currentMajorVersion = array_shift($arrayVersion);

        $info = json_decode($info, true);
        foreach ($info as $key => $val) {
            $arrayVersion = explode('.', $val['name']);
            if ($currentMajorVersion == array_shift($arrayVersion)) {
                $git['version'] = $val['name'];
                if (strpos($val['name'], 'alpha')) {
                    $git['alpha'] = $val['name'];
                    continue;
                } elseif (strpos($val['name'], 'beta')) {
                    $git['beta'] = $val['name'];
                    continue;
                } else {
                    $git['stable'] = $val['name'];
                    break;
                }
            }
        }
        $git['version'] = $this->normalizeRequestedVersion($this->argument('version'));

        if ($git['version'] == 'null') {
            if (isset($git['stable'])) {
                if (version_compare($currentVersion['version'], $git['stable'], '!=')) {
                    $git['version'] = $git['stable'];
                }
            }
        }
        if ($git['version'] != '') {
            $customPackageConstraints = $this->resolveCustomComposerPackageConstraints();
            $lockedCustomPackageVersions = $this->resolveLockedComposerPackageVersions(array_keys($customPackageConstraints));
            $url = $this->buildArchiveUrl($updateRepository, $git['version']);
            $this->line('<fg=green>Start download Evolution CMS</>');
            $url = file_get_contents($url);
            $file = EVO_BASE_PATH . 'new_version.zip';

            file_put_contents($file, $url);
            $this->line('<fg=green>Start unpacking Evolution CMS</>');

            $temp_dir = EVO_BASE_PATH . '_temp' . md5(time());
            //run unzip and install

            $zip = new \ZipArchive;
            $res = $zip->open($file);
            $zip->extractTo($temp_dir);
            $zip->close();
            unlink($file);

            if ($handle = opendir($temp_dir)) {
                while (false !== ($name = readdir($handle))) {
                    if ($name != '.' && $name != '..') $dir = $name;
                }
                closedir($handle);
            }

            self::moveFiles($temp_dir . '/' . $dir, EVO_BASE_PATH);
            self::rmdirs($temp_dir);

            $ch = curl_init();
            $url = 'https://api.github.com/repos/' . $updateRepository . '/releases';
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_REFERER, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["User-Agent: updateNotify widget"]);
            $releases = curl_exec($ch);
            curl_close($ch);

            $factoryName = $currentVersion['full_appname'];
            if (substr($releases, 0, 1) == "[") {
                $releases = json_decode($releases, true);
                foreach ($releases as $release) {
                    if ($git['version'] == $release["tag_name"]) {
                        $factoryDate = date("M j, Y", strtotime($release["published_at"]));
                        $factoryName = $release["name"] . ' (' . $factoryDate . ')';
                        $factoryVersion = '<?php return [' . "\n";
                        $factoryVersion .= "\t" . '"version" => "' . $release["tag_name"] . '", // Current version number' . "\n";
                        $factoryVersion .= "\t" . '"release_date" => "' . $factoryDate . '", // Date of release' . "\n";
                        $factoryVersion .= "\t" . '"branch" => "Evolution CMS", // Codebase name' . "\n";
                        $factoryVersion .= "\t" . '"full_appname" => "' . $factoryName . '", // Date of release' . "\n";
                        $factoryVersion .= '];';
                        file_put_contents(EVO_CORE_PATH . "factory/version.php", $factoryVersion);
                        break;
                    }
                }
            }

            $delete_file = EVO_BASE_PATH . 'install/stubs/file_for_delete.txt';
            if (file_exists($delete_file)) {
                $files = explode("\n", file_get_contents($delete_file));
                foreach ($files as $file) {
                    $file = str_replace('{core}', EVO_CORE_PATH, $file);
                    if (file_exists($file)) {
                        if (is_dir($file)) {
                            self::rmdirs($file);
                        } else {
                            unlink($file);
                        }
                    }
                }
            }
            $this->cleanupUpdatePlaceholderFiles();
            putenv('COMPOSER_HOME=' . EVO_CORE_PATH . 'composer');
            $this->installComposerDependencies($customPackageConstraints, $lockedCustomPackageVersions);
            $this->runCoreMigrations();
            $this->updateBundledExtrasModule();

            $this->line('<fg=green>Remove Install Directory</>');
            self::rmdirs(EVO_BASE_PATH . 'install');

            $this->line('<fg=yellow;bg=blue>Now You use ' . $factoryName . '</>');
        } else {
            $this->line('<fg=yellow;bg=blue>You use almost current version</>');
        }
    }

    /**
     * Run database migrations shipped with the updated core.
     *
     * @since 3.5.7
     * @return void
     */
    protected function runCoreMigrations(): void
    {
        $this->line('<fg=green>Run Core Migrations</>');
        $this->runCoreShellCommand('php artisan migrate --force');
    }

    /**
     * Remove placeholder files from the update archive when real local files exist.
     *
     * @since 3.5.7
     * @return void
     */
    protected function cleanupUpdatePlaceholderFiles(): void
    {
        foreach ($this->updatePlaceholderFilePairs() as [$placeholder, $localFile]) {
            if (is_file($placeholder) && is_file($localFile)) {
                unlink($placeholder);
            }
        }
    }

    /**
     * Placeholder files shipped by the core archive and their local replacements.
     *
     * @since 3.5.7
     * @return array<int, array{0: string, 1: string}>
     */
    protected function updatePlaceholderFilePairs(): array
    {
        return [
            [EVO_BASE_PATH . 'ht.access', EVO_BASE_PATH . '.htaccess'],
            [EVO_BASE_PATH . 'sample-robots.txt', EVO_BASE_PATH . 'robots.txt'],
            [EVO_CORE_PATH . 'custom/.env.docker.example', EVO_CORE_PATH . 'custom/.env.docker'],
            [EVO_CORE_PATH . 'custom/composer.json.example', EVO_CORE_PATH . 'custom/composer.json'],
            [
                EVO_CORE_PATH . 'custom/config/cms/settings/ControllerNamespace.php.example',
                EVO_CORE_PATH . 'custom/config/cms/settings/ControllerNamespace.php',
            ],
        ];
    }

    /**
     * Update the bundled Extras module during CLI core updates.
     *
     * The web and install-folder CLI updater run the installer asset update flow,
     * but "php artisan make:site update" replaces files directly and used to skip
     * the bundled module refresh. Keeping this step here ensures Extras is updated
     * before the installer directory is removed.
     *
     * @since 3.5.7
     * @return void
     */
    protected function updateBundledExtrasModule(): void
    {
        $moduleFile = EVO_BASE_PATH . 'install/assets/modules/store.tpl';
        if (!is_file($moduleFile) || !is_readable($moduleFile)) {
            return;
        }

        $params = $this->parseInstallerDocblock($moduleFile);
        if (($params['name'] ?? '') !== 'Extras') {
            return;
        }

        $moduleCode = $this->readInstallerModuleCode($moduleFile);
        if ($moduleCode === '') {
            return;
        }

        $categoryId = $this->getOrCreateCategoryId((string) ($params['modx_category'] ?? ''));
        $description = trim((string) ($params['description'] ?? ''));
        if (!empty($params['version'])) {
            $description = '<strong>' . $params['version'] . '</strong> ' . $description;
        }

        $module = SiteModule::query()->where('name', 'Extras')->first();

        if ($module) {
            $module->modulecode = $moduleCode;
            $module->description = $description;
            $module->category = $categoryId;
            $module->guid = (string) ($params['guid'] ?? $module->guid);
            $module->enable_sharedparams = (int) ($params['shareparams'] ?? $module->enable_sharedparams);
            if (!empty($params['properties'])) {
                $module->properties = (string) $params['properties'];
            }
            $module->save();

            return;
        }

        SiteModule::query()->create([
            'name' => 'Extras',
            'description' => $description,
            'modulecode' => $moduleCode,
            'properties' => (string) ($params['properties'] ?? ''),
            'guid' => (string) ($params['guid'] ?? ''),
            'enable_sharedparams' => (int) ($params['shareparams'] ?? 0),
            'category' => $categoryId,
        ]);
    }

    /**
     * Parse installer asset docblock metadata.
     *
     * @since 3.5.7
     * @param string $file Absolute path to an installer asset file.
     * @return array<string, string>
     */
    protected function parseInstallerDocblock(string $file): array
    {
        $params = [];
        $handle = @fopen($file, 'r');
        if (!$handle) {
            return $params;
        }

        $docblockStartFound = false;
        $nameFound = false;
        $descriptionFound = false;

        while (!feof($handle)) {
            $line = (string) fgets($handle);

            if (!$docblockStartFound) {
                if (strpos($line, '/**') !== false) {
                    $docblockStartFound = true;
                }
                continue;
            }

            if (!$nameFound) {
                if (preg_match("/^\s+\*\s+(.+)/", $line, $matches)) {
                    $params['name'] = trim($matches[1]);
                    $nameFound = $params['name'] !== '';
                }
                continue;
            }

            if (!$descriptionFound) {
                if (preg_match("/^\s+\*\s+(.+)/", $line, $matches)) {
                    $params['description'] = trim($matches[1]);
                    $descriptionFound = $params['description'] !== '';
                }
                continue;
            }

            if (preg_match("/^\s+\*\s+\@([^\s]+)\s+(.+)/", $line, $matches)) {
                $param = trim($matches[1]);
                $value = trim($matches[2]);

                if ($param === 'internal' && preg_match("/\@([^\s]+)\s+(.+)/", $value, $internalMatches)) {
                    $param = trim($internalMatches[1]);
                    $value = trim($internalMatches[2]);
                }

                if ($param !== '' && $value !== '') {
                    $params[$param] = $value;
                }
            } elseif (preg_match("/^\s*\*\/\s*$/", $line)) {
                break;
            }
        }

        fclose($handle);

        return $params;
    }

    /**
     * Read module PHP code from an installer module template.
     *
     * @since 3.5.7
     * @param string $file Absolute path to the module template.
     * @return string
     */
    protected function readInstallerModuleCode(string $file): string
    {
        $content = (string) file_get_contents($file);
        $parts = preg_split("/(\/\/)?\s*\<\?php/", $content, 2);

        return trim((string) end($parts));
    }

    /**
     * Return an existing category id or create the category when missing.
     *
     * @since 3.5.7
     * @param string $categoryName Category name from installer metadata.
     * @return int
     */
    protected function getOrCreateCategoryId(string $categoryName): int
    {
        $categoryName = trim($categoryName);
        if ($categoryName === '') {
            return 0;
        }

        return (int) Category::query()->firstOrCreate(['category' => $categoryName])->getKey();
    }

    /**
     * Resolve the GitHub repository used as the update source.
     *
     * @since 3.5.5
     * @return string Repository slug in the "vendor/repository" format.
     */
    protected function resolveUpdateRepository(): string
    {
        try {
            $optionRepository = $this->normalizeUpdateRepository((string) $this->option('repository'));
            if ($optionRepository !== '') {
                return $optionRepository;
            }
        } catch (\Throwable $exception) {
            // Unit tests can call protected helpers without a bound console input.
        }

        $updateRepository = (string) evo()->getConfig('UpgradeRepository');

        $updateRepository = $this->normalizeUpdateRepository($updateRepository);

        return $updateRepository !== '' ? $updateRepository : self::DEFAULT_UPGRADE_REPOSITORY;
    }

    /**
     * Install Composer dependencies after core files are replaced.
     *
     * Core dependencies are restored from the shipped lock file. Custom Composer
     * packages need a scoped lock refresh first because the updated core lock does
     * not contain packages merged from core/custom/composer.json.
     *
     * @since 3.5.7
     * @param array<string, string> $customPackageConstraints Package constraints from core/custom/composer.json.
     * @param array<string, string> $lockedCustomPackageVersions Package versions from the pre-update composer.lock.
     * @return void
     */
    protected function installComposerDependencies(array $customPackageConstraints = [], array $lockedCustomPackageVersions = []): void
    {
        if ($customPackageConstraints !== []) {
            $this->line('<fg=green>Restore Composer lock with custom packages</>');
            $this->runCoreShellCommand($this->buildCustomComposerUpdateCommand(
                $customPackageConstraints,
                $lockedCustomPackageVersions
            ));
        }

        $this->line('<fg=green>Install Composer dependencies</>');
        $this->runCoreShellCommand($this->composerInstallCommand());

        $this->line('<fg=green>Rebuild optimized autoload</>');
        $this->runCoreShellCommand($this->composerDumpAutoloadCommand());

        $this->line('<fg=green>Discover Composer packages</>');
        $this->runCoreShellCommand('php artisan package:discover');
    }

    /**
     * Resolve Composer package constraints from the local custom Composer layer.
     *
     * Platform requirements such as php/ext-json are intentionally ignored because
     * Composer cannot update them as packages.
     *
     * @since 3.5.7
     * @return array<string, string>
     */
    protected function resolveCustomComposerPackageConstraints(): array
    {
        $customComposer = EVO_CORE_PATH . 'custom/composer.json';
        if (!is_file($customComposer)) {
            return [];
        }

        $composer = json_decode((string) file_get_contents($customComposer), true);
        if (!is_array($composer)) {
            throw new \RuntimeException('Invalid custom composer.json.');
        }

        $requires = $composer['require'] ?? [];
        if (!is_array($requires)) {
            return [];
        }

        $packages = [];
        foreach ($requires as $package => $constraint) {
            $package = trim((string) $package);
            if ($this->isComposerPackageName($package)) {
                $packages[strtolower($package)] = trim((string) $constraint);
            }
        }

        return $packages;
    }

    /**
     * Resolve currently locked versions for custom packages before core files are replaced.
     *
     * @since 3.5.7
     * @param array<int, string> $packages Composer package names.
     * @return array<string, string>
     */
    protected function resolveLockedComposerPackageVersions(array $packages): array
    {
        $lockFile = EVO_CORE_PATH . 'composer.lock';
        if (!is_file($lockFile)) {
            return [];
        }

        $lock = json_decode((string) file_get_contents($lockFile), true);
        if (!is_array($lock)) {
            return [];
        }

        $packageMap = array_fill_keys(array_map('strtolower', $packages), true);
        $versions = [];

        foreach (['packages', 'packages-dev'] as $section) {
            foreach (($lock[$section] ?? []) as $package) {
                $name = strtolower((string) ($package['name'] ?? ''));
                if ($name !== '' && isset($packageMap[$name]) && !empty($package['version'])) {
                    $versions[$name] = (string) $package['version'];
                }
            }
        }

        return $versions;
    }

    /**
     * Build a scoped Composer update command for packages from core/custom/composer.json.
     *
     * @since 3.5.7
     * @param array<string, string> $customPackageConstraints Package constraints from core/custom/composer.json.
     * @param array<string, string> $lockedCustomPackageVersions Package versions from the pre-update composer.lock.
     * @return string
     */
    protected function buildCustomComposerUpdateCommand(array $customPackageConstraints, array $lockedCustomPackageVersions = []): string
    {
        $packages = [];
        foreach ($customPackageConstraints as $package => $constraint) {
            if (!$this->isComposerPackageName($package)) {
                continue;
            }

            $package = strtolower($package);
            $version = trim((string) ($lockedCustomPackageVersions[$package] ?? ''));
            $packages[] = $version !== '' ? $package . ':' . $version : $package;
        }

        if ($packages === []) {
            throw new \RuntimeException('Custom Composer packages are missing.');
        }

        return $this->composerBinaryCommand() . ' update '
            . implode(' ', array_map('escapeshellarg', array_values(array_unique($packages))))
            . ' --with-all-dependencies --no-dev --no-interaction --prefer-dist --optimize-autoloader --classmap-authoritative --no-scripts';
    }

    protected function isComposerPackageName(string $package): bool
    {
        return preg_match('~^[a-z0-9][a-z0-9_.-]*/[a-z0-9][a-z0-9_.-]*$~i', $package) === 1;
    }

    protected function composerInstallCommand(): string
    {
        return $this->composerBinaryCommand() . ' install --no-dev --no-interaction --prefer-dist --optimize-autoloader --classmap-authoritative --no-scripts';
    }

    protected function composerDumpAutoloadCommand(): string
    {
        return $this->composerBinaryCommand() . ' dump-autoload -o --no-dev --classmap-authoritative --no-scripts';
    }

    /**
     * Resolve a Composer executable command for shell calls.
     *
     * Some shared-hosting environments expose Composer only as a shell alias such
     * as ~/.composer/composer. PHP executes update commands through /bin/sh, where
     * interactive bash aliases are not available, so we need a real executable path.
     *
     * @since 3.5.7
     * @return string Composer command safe for shell usage.
     */
    protected function composerBinaryCommand(): string
    {
        foreach (['COMPOSER_BINARY', 'COMPOSER_BIN'] as $envName) {
            $configured = trim((string) getenv($envName));
            if ($configured !== '') {
                return escapeshellarg($configured);
            }
        }

        if ($this->shellCommandExists('composer')) {
            return 'composer';
        }

        foreach ($this->composerBinaryCandidates() as $candidate) {
            if (is_file($candidate) && is_executable($candidate)) {
                return escapeshellarg($candidate);
            }
        }

        return 'composer';
    }

    /**
     * Build fallback Composer executable candidates.
     *
     * @since 3.5.7
     * @return array<int, string>
     */
    protected function composerBinaryCandidates(): array
    {
        $candidates = [
            '/usr/local/bin/composer',
            '/usr/bin/composer',
        ];

        foreach ($this->homeDirectories() as $home) {
            $candidates[] = $home . '/.composer/composer';
        }

        return array_values(array_unique($candidates));
    }

    /**
     * Resolve possible home directories without relying on shell "~" expansion.
     *
     * @since 3.5.7
     * @return array<int, string>
     */
    protected function homeDirectories(): array
    {
        $homes = [];

        foreach (['HOME', 'USERPROFILE'] as $envName) {
            $home = trim((string) getenv($envName));
            if ($home !== '') {
                $homes[] = rtrim(str_replace('\\', '/', $home), '/');
            }
        }

        if (function_exists('posix_getpwuid') && function_exists('posix_getuid')) {
            $user = posix_getpwuid(posix_getuid());
            if (is_array($user) && !empty($user['dir'])) {
                $homes[] = rtrim(str_replace('\\', '/', (string) $user['dir']), '/');
            }
        }

        return array_values(array_unique(array_filter($homes)));
    }

    /**
     * Check whether a command is available to the non-interactive shell.
     *
     * @since 3.5.7
     * @param string $command Command name.
     * @return bool
     */
    protected function shellCommandExists(string $command): bool
    {
        $output = [];
        $exitCode = 1;

        exec('command -v ' . escapeshellarg($command) . ' >/dev/null 2>&1', $output, $exitCode);

        return (int) $exitCode === 0;
    }

    protected function normalizeUpdateRepository(string $repository): string
    {
        return trim($repository, " \t\n\r\0\x0B/");
    }

    /**
     * Run a shell command from the core directory.
     *
     * Core updates may be started from the manager, cron, or a shell with a different
     * current working directory, so Composer and installer calls must not rely on cwd.
     *
     * @since 3.5.7
     * @param string $command Command to execute from EVO_CORE_PATH.
     * @return void
     */
    protected function runCoreShellCommand(string $command): void
    {
        $fullCommand = 'cd ' . escapeshellarg(EVO_CORE_PATH) . ' && ' . $command . ' 2>&1';
        exec($fullCommand, $output, $exitCode);

        if ((int) $exitCode !== 0) {
            $message = 'Command failed: ' . $command;
            if (!empty($output)) {
                $message .= '. ' . implode("\n", array_slice($output, -8));
            }

            throw new \RuntimeException($message);
        }
    }

    /**
     * Normalize the optional version/ref argument passed to the command.
     *
     * Empty values are converted to the legacy "null" marker expected by the existing logic.
     *
     * @since 3.5.5
     * @param mixed $version Raw version argument from Artisan input.
     * @return string Normalized ref name or the "null" placeholder.
     */
    protected function normalizeRequestedVersion($version): string
    {
        if ($version === null) {
            return 'null';
        }

        $version = trim((string) $version);

        return $version === '' ? 'null' : $version;
    }

    /**
     * Build a codeload archive URL for a Git tag, branch or commit hash.
     *
     * Semantic versions are treated as tags, hex hashes as commits, and all other refs
     * are resolved as branch names.
     *
     * @since 3.5.5
     * @param string $repository Repository slug in the "vendor/repository" format.
     * @param string $ref Tag, branch or commit hash to download.
     * @return string Download URL for the requested archive.
     */
    protected function buildArchiveUrl(string $repository, string $ref): string
    {
        $repository = trim($repository, '/');
        $ref = trim($ref);

        if ($this->isSemanticVersionTag($ref)) {
            return sprintf('https://codeload.github.com/%s/zip/refs/tags/%s', $repository, rawurlencode($ref));
        }

        if ($this->isCommitHash($ref)) {
            return sprintf('https://codeload.github.com/%s/zip/%s', $repository, rawurlencode($ref));
        }

        return sprintf(
            'https://codeload.github.com/%s/zip/refs/heads/%s',
            $repository,
            str_replace('%2F', '/', rawurlencode($ref))
        );
    }

    /**
     * Determine whether the given ref looks like a semantic-version tag.
     *
     * @since 3.5.5
     * @param string $ref Candidate ref name.
     * @return bool True when the ref matches the expected release-tag pattern.
     */
    protected function isSemanticVersionTag(string $ref): bool
    {
        return preg_match('/^\d+\.\d+\.\d+(?:[-+][A-Za-z0-9\.-]+)?$/', $ref) === 1;
    }

    /**
     * Determine whether the given ref looks like a Git commit hash.
     *
     * @since 3.5.5
     * @param string $ref Candidate ref name.
     * @return bool True when the ref is a 7-40 character hexadecimal hash.
     */
    protected function isCommitHash(string $ref): bool
    {
        return preg_match('/^[0-9a-f]{7,40}$/i', $ref) === 1;
    }

    /**
     * Recursively move files from the extracted update archive into the site root.
     *
     * Destination directories are created on demand. Existing writable files are replaced.
     *
     * @param string $src Absolute path to the extracted source directory.
     * @param string $dest Absolute path to the destination root.
     * @return void
     */
    static public function moveFiles($src, $dest)
    {
        $path = realpath($src);
        $dest = realpath($dest);
        $objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($objects as $name => $object) {
            $startsAt = substr(dirname($name), strlen($path));
            self::mmkDir($dest . $startsAt);
            if ($object->isDir()) {
                self::mmkDir($dest . substr($name, strlen($path)));
            }

            if (is_writable($dest . $startsAt) && $object->isFile()) {
                rename((string)$name, $dest . $startsAt . '/' . basename($name));
            }
        }
    }

    /**
     * Recursively delete a directory and all of its contents.
     *
     * @param string $dir Absolute path to the directory being removed.
     * @return void
     */
    static public function rmdirs($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object) && !is_link($dir . "/" . $object))
                        self::rmdirs($dir . "/" . $object);
                    else
                        unlink($dir . "/" . $object);
                }
            }
            rmdir($dir);
        }
    }

    /**
     * Create a directory when it does not already exist.
     *
     * @param string $folder Absolute path to the directory.
     * @param int $perm Octal permissions passed to mkdir().
     * @return void
     */
    static public function mmkDir($folder, $perm = 0777)
    {
        if (!is_dir($folder)) {
            mkdir($folder, $perm);
        }
    }
}
