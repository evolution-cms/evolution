<?php namespace EvolutionCMS\Console;

use EvolutionCMS\Models\Category;
use EvolutionCMS\Models\SiteModule;
use EvolutionCMS\Models\SiteModuleRole;
use EvolutionCMS\Models\SystemSetting;
use EvolutionCMS\Services\ComposerVersionSynchronizer;
use EvolutionCMS\Services\CustomComposerPackages;
use EvolutionCMS\Services\Store\RemoteTransportService;
use EvolutionCMS\Traits\RunsComposerShell;
use Illuminate\Console\Command;

/**
 * Check and apply a core archive using the site's configured Manager and Composer environment.
 *
 * Preflight runs before the overlay; dependency repair, migrations and cache cleanup
 * then bring the installed files and application state onto the requested version.
 *
 * @see: https://github.com/laravel-zero/foundation/blob/9.x/src/Illuminate/Foundation/Console/ClearCompiledCommand.php
 */
class SiteUpdateCommand extends Command
{
    use RunsComposerShell;

    /**
     * Default GitHub repository used for core updates when no custom repository is configured.
     */
    protected const DEFAULT_UPGRADE_REPOSITORY = 'evolution-cms/evolution';

    /**
     * Commit the downloaded archive was made from, empty when it did not say.
     *
     * @since 3.5.9
     */
    protected string $archiveCommit = '';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'make:site
                            {command_site=update : Update action to run. Keep "update" for normal core updates}
                            {version=null : Optional Release (3.5.8), branch (3.5.x), tag (nightly-3.5.x), commit hash (922ece660) or GitHub URL (https://github.com/owner/evolution/tree/branch). Omit it to stay on the current line}
                            {--repository= : Optional GitHub repository slug for a plain version. Example: middleDuckAi/evolution}
                            {--update-custom-packages : Let Composer move packages of core/custom/composer.json (and what they need) to other versions when the ones the site runs do not fit}';

    /**
     * Every form the version argument takes, shown in the help and after a failed update.
     *
     * @since 3.5.9
     */
    protected const VERSION_FORMATS = <<<'FORMATS'
Version formats:

  (none)                  Stay on the current line: the noted branch or fork, else the
                          newest release, else the major.minor.x branch of a dev build.
                          Never moves a site back to an older release.
  3.5.8                   A release tag, also an older one (the way back from 3.5.x).
  3.5.x                   A branch, updated to its latest commit.
  feature/some-branch     A branch with slashes.
  nightly-3.5.x           Any other tag, such as the nightly build.
  922ece660               A commit hash, 7 to 40 characters.
  https://github.com/<owner>/<repo>/tree/<branch or tag>
  https://github.com/<owner>/<repo>/releases/tag/<tag>
  https://github.com/<owner>/<repo>/commit/<hash>
                          The same in another repository, such as a fork, with the URL
                          GitHub shows in the browser ("https://" and ".git" are optional).
  <ref> --repository=<owner>/<repo>
                          A plain ref of another repository.

A plain version always comes from evolution-cms/evolution (or the UpgradeRepository
setting); --repository or a URL picks another one.
FORMATS;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the site, or switch it to another release, branch, tag or fork (see --help for version formats)';

    /**
     * Additional help shown by the Artisan help command.
     *
     * @var string
     */
    protected $help = <<<'HELP'
Downloads and applies an Evolution CMS core update package from GitHub.

A site switches between a release, the 3.5.x branch (nightly) and a fork in
either direction by passing the version to go to. The branch, tag or fork it
came from is noted in core/factory/version.php, so a later update without a
version stays on it; installing a release removes the note again.

Packages of core/custom/composer.json are kept at the versions the site runs,
migrations and update seeders run, compiled views are dropped. Before any file
is replaced, a Composer dry run checks those packages against the new version;
when they do not fit, or Composer would move other packages, the update stops
unless --update-custom-packages accepts the changes it lists. The command exits
with 1 when an error left the site unchanged (unknown branch, GitHub unreachable
or rate limited, custom packages that do not fit).

Manager files follow EVO_MANAGER_PATH, including a renamed manager directory.
Existing Manager configuration and local access-control files are preserved.
Composer keeps the operator's authentication environment; core/composer/auth.json
remains supported, with a site-local home fallback for headless environments.

HELP;

    /**
     * Worked examples shown at the end of the help.
     *
     * @since 3.5.9
     */
    protected const HELP_EXAMPLES = <<<'EXAMPLES'

Examples:

  php artisan make:site
    Update along the current line, e.g. take the latest 3.5.x every night.

  php artisan make:site update 3.5.x
    From a release (3.5.8) to the development branch.

  php artisan make:site update 3.5.8
    Back from 3.5.x, or from a fork, to the release.

  php artisan make:site update nightly-3.5.x
    The nightly build tag.

  php artisan make:site update https://github.com/middleDuckAi/evolution/tree/add-folder-copying
    A branch of a fork. "make:site" then follows that branch of the fork.

  php artisan make:site update add-folder-copying --repository=middleDuckAi/evolution
    The same without the URL.

  php artisan make:site update 922ece66071acecaea9afb8486791738acc6de5e
    A specific commit.
EXAMPLES;

    /**
     * Create a new site update command instance.
     */
    public function __construct()
    {
        $this->help .= "\n" . self::VERSION_FORMATS . "\n" . self::HELP_EXAMPLES;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            switch ($this->argument('command_site')) {
                case 'pizdato':
                    echo 'Remove MODX REVO and install Evolution CMS' . "\n";
                    return $this->startUpdate() === false ? self::FAILURE : self::SUCCESS;

                case 'update':
                    return $this->startUpdate() === false ? self::FAILURE : self::SUCCESS;
            }
        } catch (\InvalidArgumentException $exception) {
            $this->error($exception->getMessage());
            $this->line(self::VERSION_FORMATS);
            return self::FAILURE;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        return self::FAILURE;
    }

    /**
     * Download, unpack and apply the requested Evolution CMS update package.
     *
     * When no explicit version/ref is provided, resolveTargetRef() keeps the site on
     * its line: the recorded branch, a newer stable tag, or the development branch.
     * After the new core is overlaid, Composer package metadata is synchronized with
     * factory/version.php before dependencies are restored.
     *
     * The version may also be a GitHub URL of another repository, such as a fork:
     * https://github.com/vendor/evolution/tree/branch-or-tag. An explicit plain version
     * always comes from the configured repository, which is how a site switches back.
     *
     * @since 3.5.5 Added support for branch and commit refs in manual update requests.
     * @since 3.5.9 Accepts GitHub URLs and answers false when an error stopped the update.
     * @return bool False when the update failed before the site was changed.
     */
    public function startUpdate()
    {
        $evo = evo();
        $currentVersion = $evo->getVersionData();
        $currentVersion = is_array($currentVersion) ? $currentVersion : [];
        $requestedVersion = $this->normalizeRequestedVersion($this->argument('version'));

        $source = $this->parseGitHubSource($requestedVersion);
        if ($source !== null) {
            $updateRepository = $source['repository'];
            $requestedVersion = $source['ref'];
        } else {
            // Only an update without a version stays in the repository the site came from.
            $updateRepository = $this->resolveUpdateRepository(
                $requestedVersion === 'null' ? (string) ($currentVersion['repository'] ?? '') : ''
            );
        }

        $info = $this->fetchRepositoryMetadata($updateRepository, 'tags');
        if (substr($info, 0, 1) != '[') {
            $this->error('Could not read the tags of ' . $updateRepository . ' from GitHub. Nothing was changed.');

            return false;
        }
        $tags = $this->extractTagNames(json_decode($info, true));
        $targetRef = $this->resolveTargetRef($requestedVersion, $currentVersion, $tags);

        if ($requestedVersion === 'null' && $targetRef !== '' && !$this->isSemanticVersionTag($targetRef)) {
            $this->line('<fg=green>No version given: updating from ' . $this->describeSource($updateRepository, $targetRef) . ', the line this site runs. Pass a release such as 3.5.8 to leave it.</>');
        }

        if ($targetRef !== '') {
            $customPackageNames = array_keys($this->resolveCustomComposerPackageConstraints());
            // Read before the update replaces composer.lock.
            $previousCustomPackageVersions = $customPackageNames === []
                ? []
                : $this->customComposerPackages()->previousVersions($customPackageNames);
            $url = $this->buildArchiveUrl($updateRepository, $targetRef, $tags);
            $this->line('<fg=green>Start download Evolution CMS ' . $this->describeSource($updateRepository, $targetRef) . '</>');
            $file = EVO_BASE_PATH . 'new_version.zip';

            // Nothing below this point may run on a failed package: the site keeps its
            // current files, and composer, migrations and seeders stay untouched.
            if (!$this->downloadUpdateArchive($url, $file)) {
                @unlink($file);
                $this->error('Could not download the update package. Nothing was changed.');
                $this->line('No ' . $targetRef . ' in ' . $updateRepository . '? Check the version against the formats below.');
                $this->line(self::VERSION_FORMATS);

                return false;
            }

            $this->line('<fg=green>Start unpacking Evolution CMS</>');

            $temp_dir = EVO_BASE_PATH . '_temp' . md5(time());
            //run unzip and install

            if (!$this->extractUpdateArchive($file, $temp_dir)) {
                @unlink($file);
                self::rmdirs($temp_dir);
                $this->error('The downloaded update package is not a valid archive. Nothing was changed.');

                return false;
            }

            unlink($file);

            $dir = $this->resolveExtractedRoot($temp_dir);

            if ($dir === '') {
                self::rmdirs($temp_dir);
                $this->error('The update package has an unexpected layout. Nothing was changed.');

                return false;
            }

            $this->configureComposerHome(EVO_CORE_PATH . 'composer');
            $customPackageUpdate = $this->preflightComposer(
                $temp_dir . '/' . $dir . '/core',
                $customPackageNames,
                $previousCustomPackageVersions
            );
            if ($customPackageUpdate === null) {
                self::rmdirs($temp_dir);

                return false;
            }

            self::moveFiles($temp_dir . '/' . $dir, EVO_BASE_PATH, EVO_MANAGER_PATH);
            self::rmdirs($temp_dir);

            // Only a release carries a version number of its own. Any other ref (a
            // branch, the moving nightly-3.5.x tag, a commit) keeps the version file
            // of its archive and records the ref, so the next update stays on it.
            if ($this->isSemanticVersionTag($targetRef)) {
                $releases = $this->fetchRepositoryMetadata($updateRepository, 'releases');
                if (substr($releases, 0, 1) == "[") {
                    $releases = json_decode($releases, true);
                    foreach ($releases as $release) {
                        if ($targetRef == $release["tag_name"]) {
                            $factoryDate = date("M j, Y", strtotime($release["published_at"]));
                            $factoryVersion = self::buildVersionFile([
                                'version' => (string)$release["tag_name"],
                                'release_date' => $factoryDate,
                                'branch' => 'Evolution CMS',
                                'full_appname' => $release["name"] . ' (' . $factoryDate . ')',
                            ]);
                            file_put_contents(EVO_CORE_PATH . "factory/version.php", $factoryVersion);
                            break;
                        }
                    }
                }
            }
            $this->recordInstallSource($targetRef, $updateRepository);

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
            $this->synchronizeComposerVersion();
            $this->installComposerDependencies($customPackageUpdate, $customPackageNames);
            $this->runCoreMigrations();
            $this->runUpdateSeeders();
            $this->clearCompiledViews();
            $this->updateBundledExtrasModule();
            $this->syncSettingsVersion();

            $this->line('<fg=green>Remove Install Directory</>');
            self::rmdirs(EVO_BASE_PATH . 'install');

            $this->line('<fg=yellow;bg=blue>Now You use ' . $this->describeInstalledVersion() . '</>');
        } else {
            $this->line('<fg=yellow;bg=blue>You use almost current version</>');
        }

        return true;
    }

    /**
     * Align Composer package metadata with the overlaid Evolution release.
     *
     * This explicit updater step prevents version mismatch loops without attaching
     * the synchronizer to ordinary Composer install, update, or autoload operations.
     *
     * @since 3.5.8
     * @return void
     */
    protected function synchronizeComposerVersion(): void
    {
        $this->line('<fg=green>Checking Evolution CMS version...</>');

        $result = (new ComposerVersionSynchronizer())->synchronize(
            EVO_CORE_PATH . 'composer.json',
            EVO_CORE_PATH . 'factory/version.php'
        );

        if ($result['changed']) {
            $this->line('<fg=green>Synced composer.json: evolution-cms/evolution ' . $result['version'] . '</>');
        } else {
            $this->line('<fg=green>Evolution CMS ' . $result['version'] . '</>');
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
     * Apply the version update seeders shipped in the install directory.
     *
     * The web installer and install-folder CLI updater run seed('update') to apply
     * data fixes such as permission renames. "php artisan make:site update" replaces
     * files directly and used to skip this step, so manager/CLI updates drifted from
     * the installer. The seeders use only the DB facade and are idempotent, so running
     * them here before the install directory is removed keeps every path consistent.
     *
     * @since 3.5.7
     * @return void
     */
    protected function runUpdateSeeders(): void
    {
        $seedDir = EVO_BASE_PATH . 'install/stubs/seeds/update';
        if (!is_dir($seedDir)) {
            return;
        }

        $this->line('<fg=green>Apply update seeders</>');

        foreach (glob($seedDir . '/*.php') as $filename) {
            require_once $filename;
            $class = 'EvolutionCMS\\Installer\\Update\\' . basename($filename, '.php');
            if (class_exists($class) && is_subclass_of($class, \Illuminate\Database\Seeder::class)) {
                (new $class())->run();
            }
        }
    }

    /**
     * Drop the views compiled by the version that was replaced.
     *
     * The archive keeps file times older than the compiled views. From 3.5.9 on the
     * compiler keys its views on those times (EvolutionCMS\Extensions\BladeCompiler),
     * but 3.5.8 and older key them on the path alone and would serve whatever they
     * find under it. Clearing leaves nothing stale for either, and drops the views
     * the replaced version compiled.
     *
     * @since 3.5.9
     * @return void
     */
    protected function clearCompiledViews(): void
    {
        $path = rtrim($this->compiledViewsPath(), '/\\');
        if ($path === '' || !is_dir($path)) {
            return;
        }

        $this->line('<fg=green>Clear compiled views</>');

        foreach (glob($path . '/*.php') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * @since 3.5.9
     * @return string
     */
    protected function compiledViewsPath(): string
    {
        return (string) evo()['config']['view.compiled'];
    }

    /**
     * Persist the installed version and drop the settings cache.
     *
     * The manager menu and plugins read settings_version, which only a manual
     * settings save used to refresh, so the old version stayed visible after an update.
     *
     * @since 3.5.8
     * @return void
     */
    protected function syncSettingsVersion(): void
    {
        $version = $this->readInstalledVersion();
        if ($version === '') {
            return;
        }

        SystemSetting::query()->updateOrCreate(
            ['setting_name' => 'settings_version'],
            ['setting_value' => $version]
        );

        foreach (['siteCache.idx.php', 'sitePublishing.idx.php'] as $file) {
            $path = $this->bootstrapCachePath() . $file;
            if (is_file($path)) {
                unlink($path);
            }
        }

        $this->line('<fg=green>Settings version set to ' . $version . '</>');
    }

    /**
     * Read the version from the freshly overlaid factory/version.php.
     *
     * @since 3.5.8
     * @return string
     */
    protected function readInstalledVersion(): string
    {
        return trim((string) ($this->readInstalledVersionData()['version'] ?? ''));
    }

    /**
     * Read the whole freshly overlaid factory/version.php.
     *
     * @since 3.5.9
     * @return array<string, mixed> Empty when the file is missing or malformed.
     */
    protected function readInstalledVersionData(): array
    {
        $file = $this->versionFilePath();
        if (!is_file($file)) {
            return [];
        }
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($file, true);
        }

        $version = include $file;

        return is_array($version) ? $version : [];
    }

    /**
     * Note in factory/version.php where a non-release update came from.
     *
     * The archive of a branch carries the version number of the next release, so
     * without this a 3.5.x site could not tell itself apart from that release, and
     * an update without a version moved it back to the last stable tag. A site taken
     * from another repository (a fork) notes that repository too, so it is followed
     * as well, and the commit of the archive lets the manager tell whether the branch
     * has moved since. The note lives in the version file because every way of
     * replacing the code also replaces that file: an overlay of a release drops it.
     *
     * @since 3.5.9
     * @param string $ref Branch, tag or commit the code was taken from.
     * @param string $repository Repository slug the code was taken from.
     * @return void
     */
    protected function recordInstallSource(string $ref, string $repository): void
    {
        $version = $this->readInstalledVersionData();
        if ($version === []) {
            return;
        }

        $recorded = $version;
        unset($recorded['ref'], $recorded['repository'], $recorded['commit']);
        if ($ref !== '' && !$this->isSemanticVersionTag($ref)) {
            $recorded['ref'] = $ref;
            if ($this->archiveCommit !== '') {
                $recorded['commit'] = $this->archiveCommit;
            }
        }
        if ($repository !== '' && strcasecmp($repository, self::DEFAULT_UPGRADE_REPOSITORY) !== 0) {
            $recorded['repository'] = $repository;
        }

        if ($recorded !== $version) {
            file_put_contents($this->versionFilePath(), self::buildVersionFile($recorded));
        }
    }

    /**
     * Name the installed version for the closing message.
     *
     * @since 3.5.9
     * @return string
     */
    protected function describeInstalledVersion(): string
    {
        $version = $this->readInstalledVersionData();
        $name = trim((string) ($version['full_appname'] ?? ''));
        if ($name === '') {
            $name = 'Evolution CMS ' . trim((string) ($version['version'] ?? ''));
        }

        $ref = trim((string) ($version['ref'] ?? ''));
        $repository = trim((string) ($version['repository'] ?? ''));
        if ($ref === '' && $repository === '') {
            return $name;
        }

        return $name . ' from ' . $this->describeSource($repository, $ref);
    }

    /**
     * Name a ref, with its repository when that is not the official one.
     *
     * @since 3.5.9
     * @param string $repository Repository slug.
     * @param string $ref Branch, tag or commit.
     * @return string
     */
    protected function describeSource(string $repository, string $ref): string
    {
        if ($repository === '' || strcasecmp($repository, self::DEFAULT_UPGRADE_REPOSITORY) === 0) {
            return $ref;
        }

        return trim($repository . ' ' . $ref);
    }

    /**
     * @since 3.5.9
     * @return string
     */
    protected function versionFilePath(): string
    {
        return EVO_CORE_PATH . 'factory/version.php';
    }

    /**
     * Directory holding the compiled settings cache.
     *
     * @since 3.5.8
     * @return string
     */
    protected function bootstrapCachePath(): string
    {
        return rtrim(evo()->bootstrapPath(), '/\\') . '/';
    }

    /**
     * Remove placeholder files and root distribution artifacts from the update archive.
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

        foreach ([EVO_BASE_PATH . 'composer.json', EVO_BASE_PATH . 'config.php.example'] as $file) {
            if (is_file($file)) {
                unlink($file);
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
            [EVO_CORE_PATH . 'custom/routes.php.example', EVO_CORE_PATH . 'custom/routes.php'],
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

        $created = SiteModule::query()->create([
            'name' => 'Extras',
            'description' => $description,
            'modulecode' => $moduleCode,
            'properties' => (string) ($params['properties'] ?? ''),
            'guid' => (string) ($params['guid'] ?? ''),
            'enable_sharedparams' => (int) ($params['shareparams'] ?? 0),
            'category' => $categoryId,
        ]);

        // a re-created Extras module keeps the shipped default: admins only
        SiteModuleRole::applyDefaultsFor((int) $created->getKey(), 'Extras');
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
     * Download the update package and store it locally.
     *
     * A transfer that failed or returned nothing must not be mistaken for a package:
     * an empty file opens as a valid archive and would unpack into an empty tree.
     *
     * @since 3.5.8
     * @param string $url Archive URL.
     * @param string $file Absolute path the package is written to.
     * @return bool False when nothing usable was received or stored.
     */
    protected function downloadUpdateArchive(string $url, string $file): bool
    {
        $body = @file_get_contents($url);

        if (!is_string($body) || $body === '') {
            return false;
        }

        return @file_put_contents($file, $body) !== false;
    }

    /**
     * Unpack the update package into a staging directory.
     *
     * @since 3.5.8
     * @param string $file Absolute path to the downloaded package.
     * @param string $tempDir Staging directory the package is unpacked into.
     * @return bool False when the package is not a usable archive.
     */
    protected function extractUpdateArchive(string $file, string $tempDir): bool
    {
        // A zero byte file opens as a valid archive, so it is rejected before
        // ZipArchive sees it and reports the empty archive as deprecated.
        if (!is_file($file) || filesize($file) === 0) {
            return false;
        }

        $zip = new \ZipArchive();

        if ($zip->open($file) !== true) {
            return false;
        }

        if ($zip->numFiles === 0) {
            $zip->close();

            return false;
        }

        try {
            $extracted = $zip->extractTo($tempDir);
        } catch (\Throwable $exception) {
            $extracted = false;
        }

        // GitHub stores the commit an archive was made from as its comment.
        $comment = trim((string) $zip->getArchiveComment());
        $this->archiveCommit = preg_match('/^[0-9a-f]{40}$/', $comment) === 1 ? $comment : '';

        $zip->close();

        return $extracted === true;
    }

    /**
     * Name the directory a release archive unpacked into.
     *
     * A release always contains exactly one top level directory. Anything else means
     * the package is not what the updater expects, or the staging directory was not
     * empty to begin with.
     *
     * @since 3.5.8
     * @param string $tempDir Staging directory holding the unpacked package.
     * @return string Directory name, empty when it cannot be determined.
     */
    protected function resolveExtractedRoot(string $tempDir): string
    {
        if (!is_dir($tempDir)) {
            return '';
        }

        $entries = scandir($tempDir);

        if (!is_array($entries)) {
            return '';
        }

        $directories = [];
        foreach ($entries as $entry) {
            if ($entry !== '.' && $entry !== '..' && is_dir($tempDir . '/' . $entry)) {
                $directories[] = $entry;
            }
        }

        return count($directories) === 1 ? $directories[0] : '';
    }

    /**
     * Render factory/version.php from release metadata.
     *
     * Release name and tag come from the GitHub API, so every value is exported as a
     * PHP literal. Concatenating them into double-quoted strings let a crafted release
     * name break out of the literal and run as code when the file is included.
     *
     * @since 3.5.8
     * @param array $version Version data to persist.
     * @return string
     */
    public static function buildVersionFile(array $version): string
    {
        // Fixed text, never metadata, so a comment can never carry a line break.
        $comments = [
            'version' => 'Current version number',
            'release_date' => 'Date of release',
            'branch' => 'Codebase name',
            'full_appname' => 'Full application name and release date',
            'ref' => 'Branch, tag or commit of a non-release update',
            'repository' => 'Repository of that update, when not the official one',
            'commit' => 'Commit of that update',
        ];

        $body = '';
        foreach ($version as $key => $value) {
            $comment = $comments[$key] ?? '';
            $body .= '    ' . var_export((string)$key, true) . ' => ' . var_export($value, true) . ','
                . ($comment !== '' ? ' // ' . $comment : '') . "\n";
        }

        return '<?php return [' . "\n" . $body . '];' . "\n";
    }

    /**
     * Read repository metadata from the GitHub API over a TLS-verified transport.
     *
     * @since 3.5.8
     * @param string $repository Repository slug, for example evolution-cms/evolution.
     * @param string $resource API resource to read, either tags or releases.
     * @return string Raw response body, empty when the request failed.
     */
    protected function fetchRepositoryMetadata(string $repository, string $resource): string
    {
        return (new RemoteTransportService())
            ->fetchBody('https://api.github.com/repos/' . $repository . '/' . $resource);
    }

    /**
     * Resolve the GitHub repository used as the update source.
     *
     * The --repository option wins, then the repository a site updated without a
     * version came from, then the UpgradeRepository setting.
     *
     * @since 3.5.5
     * @param string $installedRepository Repository noted in factory/version.php (since 3.5.9).
     * @return string Repository slug in the "vendor/repository" format.
     */
    protected function resolveUpdateRepository(string $installedRepository = ''): string
    {
        try {
            $optionRepository = $this->normalizeUpdateRepository((string) $this->option('repository'));
            if ($optionRepository !== '') {
                return $optionRepository;
            }
        } catch (\Throwable $exception) {
            // Unit tests can call protected helpers without a bound console input.
        }

        $installedRepository = $this->normalizeUpdateRepository($installedRepository);
        if ($installedRepository !== '' && $this->isRepositorySlug($installedRepository)) {
            return $installedRepository;
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
     * @since 3.5.9 Takes the update preflightCustomComposerPackages() settled on, and notes the restored versions.
     * @param string $customPackageUpdate Arguments of the restoring "composer update", '' for none.
     * @param array<int, string> $customPackageNames Packages of core/custom/composer.json.
     * @return void
     */
    protected function installComposerDependencies(string $customPackageUpdate = '', array $customPackageNames = []): void
    {
        if ($customPackageUpdate !== '') {
            $this->line('<fg=green>Restore Composer lock with custom packages</>');
            $this->runCoreShellCommand($this->buildCustomComposerUpdateCommand($customPackageUpdate));
        }

        $this->line('<fg=green>Install Composer dependencies</>');
        $this->runCoreShellCommand($this->composerInstallCommand());

        $this->line('<fg=green>Rebuild optimized autoload</>');
        $this->runCoreShellCommand($this->composerDumpAutoloadCommand());

        $this->line('<fg=green>Discover Composer packages</>');
        $this->runCoreShellCommand('php artisan package:discover');

        if ($customPackageNames !== []) {
            $this->customComposerPackages()->recordInstalled($customPackageNames);
        }
    }

    /**
     * Check the Composer steps against the unpacked version, before any site file changes.
     *
     * A Composer dry run in the unpacked core resolves the packages of
     * core/custom/composer.json at the versions the site runs (CustomComposerPackages::plan()),
     * or, with none to restore, the "composer install" that follows (planInstall()): the
     * merged requirements still have to fit the release's lock. When they do not fit, when
     * Composer would move other packages, or when the Composer of core/vendor would replace
     * its own, the update stops here with the site untouched. A site without custom packages
     * is checked only when it runs that Composer. --update-custom-packages accepts the
     * changes the dry run lists.
     *
     * @since 3.5.9
     * @param string $targetCore Core directory of the unpacked update.
     * @param array<int, string> $packages Packages of core/custom/composer.json.
     * @param array<string, string> $previous Versions the site runs (CustomComposerPackages::previousVersions()).
     * @return string|null Arguments of the restoring "composer update", '' when there is nothing to restore, null when refused.
     */
    protected function preflightComposer(string $targetCore, array $packages, array $previous): ?string
    {
        $composer = $this->composerBinaryCommand();
        $bundled = $this->customComposerPackages()->isBundledComposer($composer);
        if ($packages === [] && !$bundled) {
            return '';
        }

        $this->line('<fg=green>Check Composer packages against the new version</>');

        $targetCore = rtrim($targetCore, '/\\') . '/';
        // The staged copy must not reach the site: moveFiles() lays the unpacked tree over it.
        $staged = $targetCore . 'custom/composer.json';
        $shipped = is_file($staged) ? (string) file_get_contents($staged) : null;
        try {
            if ($packages !== [] && !$this->stageCustomComposerJson($targetCore)) {
                $this->error('Could not prepare the custom Composer packages for the check. Nothing was changed.');

                return null;
            }

            return $this->checkComposerPlan($targetCore, $packages, $previous, $composer, $bundled);
        } finally {
            if ($shipped !== null) {
                @file_put_contents($staged, $shipped);
            } elseif (is_file($staged)) {
                @unlink($staged);
            }
        }
    }

    /**
     * Run the dry run preflightComposer() prepared, and report what it found.
     *
     * @since 3.5.9
     * @param string $targetCore Core directory of the unpacked update, with a trailing slash.
     * @param array<int, string> $packages Packages of core/custom/composer.json.
     * @param array<string, string> $previous Versions the site runs.
     * @param string $composer Composer command safe for shell usage.
     * @param bool $bundled Whether that Composer runs from core/vendor.
     * @return string|null Arguments of the restoring "composer update", '' when there is nothing to restore, null when refused.
     */
    protected function checkComposerPlan(string $targetCore, array $packages, array $previous, string $composer, bool $bundled): ?string
    {
        $target = new CustomComposerPackages($targetCore);
        $pins = $packages === [] ? [] : $target->pins($packages, $previous, $target->constraints());
        $run = function (string $command): array {
            $output = [];
            $exitCode = $this->execCoreShellCommand($command, $output);

            return [$exitCode, implode("\n", $output)];
        };
        $plan = $pins === []
            ? $target->planInstall($run, $composer, $bundled)
            : $target->plan(
                $run,
                $composer,
                $pins,
                $packages,
                $this->composerSupportsMinimalChanges($composer),
                $this->allowCustomPackageChanges(),
                $bundled
            );

        if (!$plan['ok']) {
            $this->error('The Composer check stopped the update. Nothing was changed.');
            $this->line(CustomComposerPackages::describeRefusal($plan));
            if ($plan['reason'] === 'changes') {
                $this->line('To accept these changes, run the update again with --update-custom-packages.');
            }

            return null;
        }

        foreach ($plan['changes'] as $change) {
            $this->line('<fg=yellow>Custom Composer package change accepted: ' . $change . '</>');
        }

        return $plan['arguments'];
    }

    /**
     * Put the site's core/custom/composer.json into the unpacked core, its local paths rebased.
     *
     * @since 3.5.9
     * @param string $targetCore Core directory of the unpacked update, with a trailing slash.
     * @return bool
     */
    protected function stageCustomComposerJson(string $targetCore): bool
    {
        $contents = (string) @file_get_contents($this->customComposerJsonPath());
        $json = json_decode($contents, true);
        if (!is_array($json) || (!is_dir($targetCore . 'custom') && !@mkdir($targetCore . 'custom', 0777, true))) {
            return false;
        }

        $rebased = (new CustomComposerPackages(dirname($this->customComposerJsonPath(), 2)))->rebasedCustomComposerJson($json, $targetCore);
        // Written anew only when a path moved: decoding loses the difference between {} and [].
        if ($rebased !== $json) {
            $contents = (string) json_encode($rebased, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return @file_put_contents($targetCore . 'custom/composer.json', $contents) !== false;
    }

    /**
     * The Composer to run; the copy of core/vendor goes through PHP.
     *
     * The update package does not keep the executable bit of vendor/bin/composer, so
     * after the overlay a COMPOSER_BINARY (or PATH entry) leading to it fails with
     * "Permission denied", with the site files already replaced.
     *
     * @since 3.5.9
     * @return string Composer command safe for shell usage.
     */
    protected function composerBinaryCommand(): string
    {
        $command = $this->resolveComposerBinaryCommand() ?? 'composer';
        $script = $this->customComposerPackages()->bundledPhpScript($command);

        return $script === null ? $command : $this->phpBinaryCommand() . ' ' . escapeshellarg($script);
    }

    /**
     * @since 3.5.9
     * @return string
     */
    protected function customComposerJsonPath(): string
    {
        return EVO_CORE_PATH . 'custom/composer.json';
    }

    /**
     * @since 3.5.9
     * @return bool
     */
    protected function allowCustomPackageChanges(): bool
    {
        try {
            return (bool) $this->option('update-custom-packages');
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * Whether a Composer knows "update --minimal-changes".
     *
     * @since 3.5.9
     * @param string $composer Composer command safe for shell usage.
     * @return bool
     */
    protected function composerSupportsMinimalChanges(string $composer): bool
    {
        $version = $this->composerVersion($composer);

        return $version !== null
            && version_compare($version, \EvolutionCMS\Console\Packages\InstallPackageRequireCommand::MINIMAL_CHANGES_SINCE, '>=');
    }

    /**
     * Resolve Composer package constraints from the local custom Composer layer.
     *
     * @since 3.5.7
     * @return array<string, string>
     */
    protected function resolveCustomComposerPackageConstraints(): array
    {
        return $this->customComposerPackages()->constraints();
    }

    /**
     * Build a scoped Composer update command for packages from core/custom/composer.json.
     *
     * @since 3.5.7
     * @since 3.5.9 Takes the arguments CustomComposerPackages::plan() settled on.
     * @param string $arguments Package list and options (CustomComposerPackages::updateArguments()).
     * @return string
     */
    protected function buildCustomComposerUpdateCommand(string $arguments): string
    {
        return $this->composerBinaryCommand() . ' update ' . $arguments;
    }

    protected function isComposerPackageName(string $package): bool
    {
        return CustomComposerPackages::isPackageName($package);
    }

    /**
     * @since 3.5.9
     * @return CustomComposerPackages
     */
    protected function customComposerPackages(): CustomComposerPackages
    {
        return new CustomComposerPackages(defined('EVO_CORE_PATH') ? EVO_CORE_PATH : dirname(__DIR__, 2));
    }

    protected function composerInstallCommand(): string
    {
        return $this->composerBinaryCommand() . ' install --no-dev --no-interaction --prefer-dist --optimize-autoloader --classmap-authoritative --no-scripts';
    }

    protected function composerDumpAutoloadCommand(): string
    {
        return $this->composerBinaryCommand() . ' dump-autoload -o --no-dev --classmap-authoritative --no-scripts';
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
        $exitCode = $this->execCoreShellCommand($command, $output);

        if ($exitCode !== 0) {
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
     * Read a GitHub URL given as the version: another repository and a ref in it.
     *
     * Accepted: https://github.com/vendor/repo/tree/<branch or tag>,
     * .../releases/tag/<tag> and .../commit/<hash>, the forms a browser shows.
     *
     * @since 3.5.9
     * @param string $value Normalized version argument.
     * @return array{repository: string, ref: string}|null Null when the value is no GitHub URL.
     */
    protected function parseGitHubSource(string $value): ?array
    {
        $pattern = '~^(?:https?://)?(?:www\.)?github\.com/([A-Za-z0-9_.-]+)/([A-Za-z0-9_.-]+?)(?:\.git)?'
            . '(?:/(?:tree|releases/tag|commit)/([^?#]+?))?/?(?:[?#].*)?$~i';
        if (preg_match($pattern, $value, $match) !== 1) {
            return null;
        }

        $repository = $match[1] . '/' . $match[2];
        $ref = rawurldecode($match[3] ?? '');
        if ($ref === '') {
            throw new \InvalidArgumentException(
                'Name the branch or tag in the URL, for example https://github.com/' . $repository . '/tree/3.5.x'
            );
        }
        // What git itself refuses in a ref name, so nothing odd reaches the archive URL.
        if (preg_match('#[\x00-\x20\x7f~^:?*\[\\\\]|\.\.|//|@\{|^/|/$|\.lock$#', $ref) === 1) {
            throw new \InvalidArgumentException('Not a valid branch, tag or commit: ' . $ref);
        }

        return ['repository' => $repository, 'ref' => $ref];
    }

    /**
     * @since 3.5.9
     * @param string $repository Candidate "vendor/repository" slug.
     * @return bool
     */
    protected function isRepositorySlug(string $repository): bool
    {
        return preg_match('~^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$~', $repository) === 1;
    }

    /**
     * Decide which ref to download.
     *
     * An explicit ref always wins, so any switch (3.5.8 to 3.5.x and back) is one
     * command away. Without one the site stays on its line: a site updated from a
     * branch or a moving tag follows it again, a release moves to a newer release,
     * and a development build without a note (installed from a git checkout, by
     * Composer, or by an updater older than 3.5.9) follows its "major.minor.x"
     * branch. Nothing here ever moves a site back to an older release on its own.
     *
     * @since 3.5.9
     * @param string $requested Normalized version argument ("null" when none was given).
     * @param array<string, mixed> $currentVersion Data of the installed factory/version.php.
     * @param array<int, string> $tags Tag names of the update repository.
     * @return string Ref to download, empty when the site is already up to date.
     */
    protected function resolveTargetRef(string $requested, array $currentVersion, array $tags): string
    {
        if ($requested !== 'null') {
            return $requested;
        }

        $installedRef = trim((string) ($currentVersion['ref'] ?? ''));
        if ($installedRef !== '' && !$this->isSemanticVersionTag($installedRef) && !$this->isCommitHash($installedRef)) {
            return $installedRef;
        }

        $current = trim((string) ($currentVersion['version'] ?? ''));
        if (!$this->isSemanticVersionTag($current)) {
            return '';
        }

        $stable = $this->latestStableTag($tags, (int) $current);
        if ($stable !== '' && version_compare($current, $stable, '<')) {
            return $stable;
        }

        if ($stable === '' || version_compare($current, $stable, '>')) {
            return $this->developmentBranch($current);
        }

        return '';
    }

    /**
     * Pick the newest stable release of a major version.
     *
     * @since 3.5.9
     * @param array<int, string> $tags Tag names.
     * @param int $major Major version the site runs.
     * @return string Tag name, empty when the major version has no release.
     */
    protected function latestStableTag(array $tags, int $major): string
    {
        $latest = '';
        foreach ($tags as $tag) {
            if (preg_match('/^(\d+)\.\d+\.\d+$/', $tag, $match) !== 1 || (int) $match[1] !== $major) {
                continue;
            }
            if ($latest === '' || version_compare($tag, $latest, '>')) {
                $latest = $tag;
            }
        }

        return $latest;
    }

    /**
     * Name the branch a development build of a version is made from: 3.5.9 is 3.5.x.
     *
     * @since 3.5.9
     * @param string $version Semantic version.
     * @return string
     */
    protected function developmentBranch(string $version): string
    {
        $parts = explode('.', $version);

        return $parts[0] . '.' . $parts[1] . '.x';
    }

    /**
     * Collect tag names from the GitHub tags response.
     *
     * @since 3.5.9
     * @param mixed $tags Decoded response.
     * @return array<int, string>
     */
    protected function extractTagNames($tags): array
    {
        $names = [];
        foreach (is_array($tags) ? $tags : [] as $tag) {
            if (is_array($tag) && isset($tag['name']) && is_string($tag['name'])) {
                $names[] = $tag['name'];
            }
        }

        return $names;
    }

    /**
     * Build a codeload archive URL for a Git tag, branch or commit hash.
     *
     * Semantic versions and the known tags (such as nightly-3.5.x) are treated as tags,
     * hex hashes as commits, and all other refs are resolved as branch names.
     *
     * @since 3.5.5
     * @param string $repository Repository slug in the "vendor/repository" format.
     * @param string $ref Tag, branch or commit hash to download.
     * @param array<int, string> $tags Tag names of the repository (since 3.5.9).
     * @return string Download URL for the requested archive.
     */
    protected function buildArchiveUrl(string $repository, string $ref, array $tags = []): string
    {
        $repository = trim($repository, '/');
        $ref = trim($ref);

        if ($this->isSemanticVersionTag($ref) || in_array($ref, $tags, true)) {
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
     * Keep Composer's normal authentication lookup unless a legacy site auth file is needed.
     *
     * An explicit home or inline authentication belongs to the operator. Without
     * an explicit home, an existing site-local auth.json remains supported unless
     * inline authentication was supplied. Otherwise Composer resolves its own home,
     * including XDG_CONFIG_HOME, instead of losing global auth. A worker without HOME
     * (APPDATA on Windows) still needs the old site-local fallback, even with inline
     * authentication. No credentials are read, copied or printed by the updater.
     *
     * @since 3.6.1
     * @param string $legacyHome Site-local Composer home used by earlier updaters.
     * @return void
     */
    protected function configureComposerHome(string $legacyHome): void
    {
        if (getenv('COMPOSER_HOME') !== false) {
            return;
        }

        $authFile = rtrim($legacyHome, '/\\') . '/auth.json';
        $globalHome = getenv(windows_os() ? 'APPDATA' : 'HOME');
        if ((getenv('COMPOSER_AUTH') === false && is_file($authFile) && is_readable($authFile))
            || $globalHome === false || $globalHome === '') {
            putenv('COMPOSER_HOME=' . $legacyHome);
        }
    }

    /**
     * Overlay an update archive, routing only its top-level manager tree to the active Manager.
     *
     * The optional Manager path preserves the two-argument file-copy contract. Site
     * updates pass EVO_MANAGER_PATH so renamed or externally located Managers receive
     * the new forms and assets without creating a second default login directory.
     * Existing Manager configuration, .htaccess and .user.ini stay operator-owned;
     * unrelated destination files are never deleted. Invalid roots fail before writes.
     *
     * @param string $src Absolute path to the extracted source directory.
     * @param string $dest Absolute path to the destination root.
     * @param string|null $managerPath Existing configured Manager directory, or null for an unchanged archive layout.
     * @return void
     */
    static public function moveFiles($src, $dest, ?string $managerPath = null)
    {
        $path = realpath($src);
        $dest = realpath($dest);
        $manager = $managerPath === null ? null : realpath($managerPath);
        if ($path === false || !is_dir($path) || $dest === false || !is_dir($dest)
            || ($managerPath !== null && ($manager === false || !is_dir($manager)))) {
            throw new \RuntimeException('The update source, site root and configured Manager must be existing directories.');
        }

        $objects = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($objects as $name => $object) {
            $relative = str_replace('\\', '/', substr($name, strlen($path) + 1));
            $target = $dest . '/' . $relative;
            if ($manager !== null && ($relative === 'manager' || str_starts_with($relative, 'manager/'))) {
                $managerRelative = substr($relative, strlen('manager/'));
                $target = $manager . ($managerRelative === '' ? '' : '/' . $managerRelative);
                if (in_array($managerRelative, ['includes/config.inc.php', '.htaccess', '.user.ini'], true)
                    && (file_exists($target) || is_link($target))) {
                    continue;
                }
            }

            if ($object->isDir()) {
                self::mmkDir($target);
                continue;
            }

            if ($object->isFile()) {
                self::mmkDir(dirname($target));
                if (!is_writable(dirname($target)) || !rename((string) $name, $target)) {
                    throw new \RuntimeException('Could not replace an update file: ' . $relative);
                }
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
