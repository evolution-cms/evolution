<?php namespace EvolutionCMS\Console\Packages;

use Doctrine\DBAL\Exception;
use Illuminate\Console\Command;
use \EvolutionCMS;
use Illuminate\Support\Facades\File;

class ExtrasCommand extends Command
{
    private const DEFAULT_EXTRAS_CATALOG_URL = 'https://evo.im/extras.json';
    private const EXTRAS_REPO_SOURCES = [
        'https://api.github.com/orgs/evolution-cms/repos',
        'https://api.github.com/users/Dmi3yy/repos',
        'https://api.github.com/users/Seiger/repos',
        'https://api.github.com/orgs/evolution-cms-extras/repos',
    ];
    private const PACKAGES_REPO_SOURCES = [
        'https://api.github.com/orgs/evolution-cms-packages/repos',
    ];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'extras {typePackage?} {packageName?} {versionPackage?} {namePackage?} {--list : List available extras} {--json : Output list as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extras';
    /**
     * Path for custom providers
     * @var string
     */
    protected $configDir = EVO_CORE_PATH . 'custom/config/app/providers/';
    /**
     * Custom composer.json
     * @var string
     */
    protected $composer = EVO_CORE_PATH . 'custom/composer.json';
    /**
     * @var string
     */
    public $packagePath = '';

    /**
     * @var mixed|string
     */
    public $load_dir = '';

    /**
     * @var \DocumentParser|string
     */
    public $evo = '';

    /**
     * @var int
     */
    public $typePackage = 0;

    /**
     * @var string
     */
    public $selectPackage = '';

    /**
     * @var array
     */
    public $fullPackage = [];

    /**
     * @var array
     */
    public $tags = [];
    /**
     * @var array
     */
    public $branches = [];

    /**
     * @var string
     */
    protected $namePackage = '';

    /**
     * @var string
     */
    protected $version = '';

    /**
     * @var string
     */
    protected $directory = '';
    /**
     * @var string
     */
    protected $file = 'https://github.com/evolution-cms/evoPackage/archive/master.zip';
    /**
     * @var string
     */
    protected $catalogError = '';

    /**
     * @var array|null
     */
    protected $extrasCatalogPackages = null;

    /**
     * PackageCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->evo = evo();
        $this->load_dir = $this->evo->getConfig('rb_base_dir');
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->isListMode()) {
            $this->outputExtrasList();
            return;
        }
        if (!is_dir($this->configDir)) {
            mkdir($this->configDir, 0775, true);
        }
        if (!is_dir($this->configDir)) {
            $this->getOutput()->write('<error>ERROR CREATE CONFIG DIR</error>');
            exit();
        }
        if ($this->argument('typePackage') == 'extras' || $this->argument('typePackage') == 'package') {
            $this->typePackage = $this->argument('typePackage');
        } else {
            $this->typePackage = $this->choice('Select type', ['Extras(install via composer)', 'Package(install in custom/packages)']);
        }
        switch ($this->typePackage) {
            case 'Extras(install via composer)':
            case 'extras':
                $this->workWithExtras();
                break;
            case 'Package(install in custom/packages)':
            case 'package':
                $this->workWithPackage();
                break;
        }
        exit();

    }

    protected function isListMode(): bool
    {
        $type = $this->argument('typePackage');
        if (is_string($type) && strtolower($type) === 'list') {
            return true;
        }
        if ($this->option('list')) {
            return true;
        }
        return (bool) $this->option('json');
    }

    protected function outputExtrasList(): void
    {
        $catalog = $this->loadExtrasCatalog();
        if ($catalog === null) {
            $repos = $this->collectRepos(self::EXTRAS_REPO_SOURCES);
            if ($repos === null) {
                $message = $this->catalogError !== '' ? $this->catalogError : 'Extras catalog unavailable.';
                $message .= ' GitHub fallback failed.';
                $this->renderExtrasListError($message);
                return;
            }
            $packages = $this->buildExtrasListPackagesFromRepos($repos);
        } else {
            $packages = $this->buildExtrasListPackages($catalog['packages'] ?? []);
        }

        if ($this->option('json')) {
            $payload = [
                'ok' => true,
                'type' => 'extras',
                'packages' => $packages,
            ];
            $this->getOutput()->writeln(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            return;
        }

        foreach ($packages as $pkg) {
            $label = $pkg['name'];
            if ($pkg['version'] !== '') {
                $label .= ' (' . $pkg['version'] . ')';
            }
            if ($pkg['description'] !== '') {
                $label .= ' - ' . $pkg['description'];
            }
            $this->line($label);
        }
    }

    protected function renderExtrasListError(string $message): void
    {
        if ($this->option('json')) {
            $payload = [
                'ok' => false,
                'error' => $message,
            ];
            $this->getOutput()->writeln(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            return;
        }
        $this->getOutput()->writeln('<error>' . $message . '</error>');
    }

    protected function collectRepos(array $sources): ?array
    {
        $fullPackage = [];
        foreach ($sources as $url) {
            $repos = $this->getGithubInfo($url);
            if (!is_array($repos) || isset($repos['message'])) {
                return null;
            }

            $repos = $this->filterReposForSource($repos, $url);

            $fullPackage = array_merge($fullPackage, $repos);
        }

        return $fullPackage;
    }

    protected function filterReposForSource(array $repos, string $url): array
    {
        if (strpos($url, '/orgs/evolution-cms/repos') !== false) {
            return $this->filterReposByPrefix($repos, '/^e[A-Z]/');
        }
        if (strpos($url, '/users/Dmi3yy/repos') !== false) {
            return $this->filterReposByPrefix($repos, '/^d[A-Z]/');
        }
        if (strpos($url, '/users/Seiger/repos') !== false) {
            return $this->filterReposByPrefix($repos, '/^s[A-Z]/');
        }
        if (strpos($url, '/orgs/evolution-cms-extras/repos') !== false) {
            return array_slice($repos, 0, 4);
        }
        return $repos;
    }

    protected function filterReposByPrefix(array $repos, string $pattern): array
    {
        $filtered = array_filter($repos, function ($repo) use ($pattern) {
            return preg_match($pattern, $repo['name'] ?? '');
        });
        return array_values($filtered);
    }

    /**
     *
     */
    public function workWithExtras()
    {
        $catalog = $this->loadExtrasCatalog();
        if ($catalog === null) {
            $repos = $this->collectRepos(self::EXTRAS_REPO_SOURCES);
            if ($repos === null) {
                $message = $this->catalogError !== '' ? $this->catalogError : 'Extras catalog unavailable.';
                echo $message . ' GitHub fallback failed.';
                exit();
            }
            $version = $this->getPackages($repos);
            switch ($version) {
                case 'Current and updated';
                    $this->version = '*';
                    break;
                default:
                    $defaultBranch = $this->fullPackage[$this->selectPackage]['default_branch'] ?? '';
                    $this->version = $this->normalizeComposerVersion($version, $this->branches, $defaultBranch);
                    break;
            }
            $url = 'https://raw.githubusercontent.com/' . $this->fullPackage[$this->selectPackage]['full_name'] . '/' . $this->fullPackage[$this->selectPackage]['default_branch'] . '/composer.json';
            $gitInfo = $this->getGithubInfo($url);
            if (!is_array($gitInfo)) {
                echo 'The limit that is provided for free use of github has been exceeded. Please try later.';
                exit();
            }
            if (isset($gitInfo['name'])) {
                if (!$this->installComposerPackage($gitInfo['name'])) {
                    return;
                }
                $this->runPostInstallSteps($gitInfo['name']);
            } else {
                echo 'No composer.json file';
            }
            return;
        }
        $version = $this->getPackagesFromCatalog($catalog['packages'] ?? []);
        switch ($version) {
            case 'Current and updated';
                $this->version = '*';
                break;
            default:
                $defaultBranch = $this->fullPackage[$this->selectPackage]['default_branch'] ?? '';
                $this->version = $this->normalizeComposerVersion($version, $this->branches, $defaultBranch);
                break;
        }
        $composerName = $this->fullPackage[$this->selectPackage]['composer_name'] ?? '';
        if (!is_string($composerName) || $composerName === '') {
            echo 'Composer package name is unavailable in extras catalog.';
            exit();
        }
        if (!$this->installComposerPackage($composerName)) {
            return;
        }
        $this->runPostInstallSteps($composerName);
    }

    /**
     *
     */
    public function workWithPackage()
    {
        $repos = $this->collectRepos(self::PACKAGES_REPO_SOURCES);
        if ($repos === null) {
            echo 'The limit that is provided for free use of github has been exceeded. Please try later.';
            exit();
        }
        $version = $this->getPackages($repos);
        switch ($version) {
            case 'Current and updated';
                if (!empty($this->tags)) {
                    $this->version = $this->tags[0];
                } else {
                    $this->version = $this->fullPackage[$this->selectPackage]['default_branch'] ?? '';
                }
                break;
            default:
                $this->version = $version;
                break;
        }
        $this->file = 'https://github.com/' . $this->fullPackage[$this->selectPackage]['full_name'] . '/archive/' . $this->version . '.zip';
        $this->installCustomPackage();

    }

    public function getPackages(array $fullPackage)
    {
        $packageForChose = [];
        foreach ($fullPackage as $package) {
            $name = $package['name'] ?? '';
            if ($name === '') {
                continue;
            }
            $latestRelease = $this->getLatestReleaseTag($package);
            $description = trim((string) ($package['description'] ?? ''));
            if ($latestRelease !== '') {
                $label = '<fg=blue>' . $latestRelease . '</>';
                if ($description !== '') {
                    $label .= ' - ' . $description;
                }
            } else {
                $label = $description !== '' ? $description : '';
            }
            if ($label === '') {
                $label = $name;
            }
            $packageForChose[$name] = $label;
            $this->fullPackage[$name] = $package;
        }
        [$packageArg, $versionArg] = $this->parsePackageArguments();
        if (!is_null($packageArg) && array_key_exists($packageArg, $packageForChose)) {
            $this->selectPackage = $packageArg;
        } else {
            $this->selectPackage = $this->choice('Select package', $packageForChose);
        }
        $tagsUrl = $this->fullPackage[$this->selectPackage]['tags_url'];

        $tagsInfo = $this->getGithubInfo($tagsUrl);
        if(!is_array($tagsInfo)){
            echo 'The limit that is provided for free use of github has been exceeded. Please try later.';
            exit();
        }
        $tags = [];
        foreach ($tagsInfo as $tag) {
            if (!is_array($tag)) {
                continue;
            }
            $name = $tag['name'] ?? '';
            if (is_string($name) && $name !== '') {
                $tags[] = $name;
            }
        }
        $tags = array_values(array_unique($tags));

        $branches = [];
        $branchesUrl = $this->fullPackage[$this->selectPackage]['branches_url'] ?? '';
        if (is_string($branchesUrl) && $branchesUrl !== '') {
            $branchesUrl = str_replace('{/branch}', '', $branchesUrl);
            $branchesInfo = $this->getGithubInfo($branchesUrl);
            if (is_array($branchesInfo)) {
                foreach ($branchesInfo as $branch) {
                    if (!is_array($branch)) {
                        continue;
                    }
                    $name = $branch['name'] ?? '';
                    if (is_string($name) && $name !== '') {
                        $branches[] = $name;
                    }
                }
            }
        }
        $defaultBranch = $this->fullPackage[$this->selectPackage]['default_branch'] ?? '';
        if (is_string($defaultBranch) && $defaultBranch !== '' && !in_array($defaultBranch, $branches, true)) {
            $branches[] = $defaultBranch;
        }
        $branches = array_values(array_unique($branches));

        $this->tags = $tags;
        $this->branches = $branches;

        $versionChoices = ['Current and updated' => 'Current and updated'];
        foreach ($tags as $tag) {
            $versionChoices[$tag] = $tag . ' (tag)';
        }
        foreach ($branches as $branch) {
            $versionChoices[$branch] = $branch . ' (branch)';
        }
        if (!is_null($versionArg)) {
            if (is_string($versionArg)) {
                $versionArg = trim($versionArg);
                if ($versionArg !== '') {
                    return $versionArg;
                }
            } else {
                return $versionArg;
            }
        }
        return $this->choice('Select version', $versionChoices);

    }

    protected function getPackagesFromCatalog(array $packages)
    {
        $packageForChose = [];
        $this->fullPackage = [];
        foreach ($packages as $package) {
            if (!is_array($package)) {
                continue;
            }
            $name = $package['name'] ?? '';
            if (!is_string($name) || $name === '') {
                continue;
            }
            $latestRelease = $package['latest_release'] ?? '';
            $description = trim((string) ($package['description'] ?? ''));
            if (!is_string($latestRelease)) {
                $latestRelease = '';
            }
            if ($latestRelease !== '') {
                $label = '<fg=blue>' . $latestRelease . '</>';
                if ($description !== '') {
                    $label .= ' - ' . $description;
                }
            } else {
                $label = $description !== '' ? $description : '';
            }
            if ($label === '') {
                $label = $name;
            }
            $packageForChose[$name] = $label;
            $this->fullPackage[$name] = $package;
        }
        [$packageArg, $versionArg] = $this->parsePackageArguments();
        if (!is_null($packageArg) && array_key_exists($packageArg, $packageForChose)) {
            $this->selectPackage = $packageArg;
        } else {
            $this->selectPackage = $this->choice('Select package', $packageForChose);
        }

        $tags = $this->fullPackage[$this->selectPackage]['tags'] ?? [];
        $branches = $this->fullPackage[$this->selectPackage]['branches'] ?? [];

        if (!is_array($tags)) {
            $tags = [];
        }
        if (!is_array($branches)) {
            $branches = [];
        }
        $tags = array_values(array_unique(array_filter($tags, 'is_string')));
        $branches = array_values(array_unique(array_filter($branches, 'is_string')));

        $defaultBranch = $this->fullPackage[$this->selectPackage]['default_branch'] ?? '';
        if (is_string($defaultBranch) && $defaultBranch !== '' && !in_array($defaultBranch, $branches, true)) {
            $branches[] = $defaultBranch;
        }

        $this->tags = $tags;
        $this->branches = $branches;

        $versionChoices = ['Current and updated' => 'Current and updated'];
        foreach ($tags as $tag) {
            $versionChoices[$tag] = $tag . ' (tag)';
        }
        foreach ($branches as $branch) {
            $versionChoices[$branch] = $branch . ' (branch)';
        }
        if (!is_null($versionArg)) {
            if (is_string($versionArg)) {
                $versionArg = trim($versionArg);
                if ($versionArg !== '') {
                    return $versionArg;
                }
            } else {
                return $versionArg;
            }
        }
        return $this->choice('Select version', $versionChoices);
    }

    protected function buildExtrasListPackages(array $packages): array
    {
        $list = [];
        foreach ($packages as $package) {
            if (!is_array($package)) {
                continue;
            }
            $name = $package['name'] ?? '';
            if (!is_string($name) || $name === '') {
                continue;
            }
            $description = trim((string) ($package['description'] ?? ''));
            $latestRelease = $package['latest_release'] ?? '';
            if (!is_string($latestRelease)) {
                $latestRelease = '';
            }
            $tags = $package['tags'] ?? [];
            if (!is_array($tags)) {
                $tags = [];
            }
            $versions = array_values(array_unique(array_filter($tags, 'is_string')));
            if (count($versions) > 6) {
                $versions = array_slice($versions, 0, 6);
            }
            $defaultBranch = $package['default_branch'] ?? '';
            $defaultMode = $latestRelease !== '' ? 'latest-release' : 'default-branch';
            $list[] = [
                'name' => $name,
                'version' => $latestRelease,
                'versions' => $versions,
                'description' => $description,
                'defaultInstallMode' => $defaultMode,
                'defaultBranch' => is_string($defaultBranch) ? $defaultBranch : '',
            ];
        }
        return $list;
    }

    protected function buildExtrasListPackagesFromRepos(array $repos): array
    {
        $packages = [];
        foreach ($repos as $package) {
            $name = $package['name'] ?? '';
            if (!is_string($name) || $name === '') {
                continue;
            }
            $description = trim((string) ($package['description'] ?? ''));
            $version = $this->getLatestReleaseTag($package);
            $versions = $this->getPackageVersions($package);
            $defaultBranch = $package['default_branch'] ?? '';
            if (!is_string($version)) {
                $version = '';
            }
            $defaultMode = $version !== '' ? 'latest-release' : 'default-branch';
            $packages[] = [
                'name' => $name,
                'version' => $version,
                'versions' => $versions,
                'description' => $description,
                'defaultInstallMode' => $defaultMode,
                'defaultBranch' => is_string($defaultBranch) ? $defaultBranch : '',
            ];
        }
        return $packages;
    }

    public function getGithubInfo($url)
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => $this->getGithubHeaders(),
            ]
        ];

        $context = stream_context_create($opts);
        $result = @file_get_contents($url, false, $context);
        try {
            return json_decode($result, true);
        } catch (\Exception $exception) {
            return [];
        }
    }

    protected function getLatestReleaseTag(array $package)
    {
        $releasesUrl = $package['releases_url'] ?? '';
        if ($releasesUrl === '') {
            return '';
        }
        $releasesUrl = str_replace('{/id}', '/latest', $releasesUrl);
        $releaseInfo = $this->getGithubInfo($releasesUrl);
        if (!is_array($releaseInfo) || isset($releaseInfo['message'])) {
            return '';
        }
        $tag = $releaseInfo['tag_name'] ?? $releaseInfo['name'] ?? '';
        return is_string($tag) ? trim($tag) : '';
    }

    protected function getPackageVersions(array $package): array
    {
        $tagsUrl = $package['tags_url'] ?? '';
        if (!is_string($tagsUrl) || $tagsUrl === '') {
            return [];
        }
        $tagsInfo = $this->getGithubInfo($tagsUrl);
        if (!is_array($tagsInfo) || isset($tagsInfo['message'])) {
            return [];
        }
        $versions = [];
        foreach ($tagsInfo as $tag) {
            $name = $tag['name'] ?? '';
            if (!is_string($name)) {
                continue;
            }
            $name = trim($name);
            if ($name === '') {
                continue;
            }
            $versions[] = $name;
        }
        if (count($versions) > 6) {
            $versions = array_slice($versions, 0, 6);
        }
        return array_values(array_unique($versions));
    }

    protected function runPostInstallSteps($packageName)
    {
        if (!is_string($packageName) || $packageName === '') {
            return;
        }
        $providers = $this->getPackageProviders($packageName);
        $dependencyProviders = $this->getDependencyProviders($packageName);
        $allProviders = array_values(array_unique(array_merge($providers, $dependencyProviders)));
        foreach ($allProviders as $provider) {
            $this->runArtisanCommand(['vendor:publish', '--provider=' . $provider]);
        }
        $this->runArtisanCommand(['migrate', '--force']);
    }

    /**
     * Install or update the selected composer package before running publish steps.
     *
     * The extras command must not continue to `vendor:publish` or migrations when Composer fails,
     * otherwise it can publish assets from the previously installed package version. This is most
     * visible during branch upgrades where the selected version may require dependency changes.
     *
     * @param string $composerName Composer package name selected from the catalog or GitHub repo.
     * @return bool True when Composer finished successfully and post-install steps may continue.
     */
    protected function installComposerPackage(string $composerName): bool
    {
        $exitCode = (int) $this->call('package:installrequire', [
            'key' => $composerName,
            'value' => $this->version,
            '--no-dev' => true,
            '--optimize-autoloader' => true,
        ]);

        if ($exitCode === 0) {
            return true;
        }

        $this->error('Composer update failed for ' . $composerName . '. Publishing assets and migrations were skipped.');

        return false;
    }

    protected function getPackageProviders($packageName)
    {
        $composer = $this->getPackageComposer($packageName);
        if (!$composer) {
            return [];
        }
        return $this->extractProviders($composer);
    }

    protected function getDependencyProviders($packageName)
    {
        $composer = $this->getPackageComposer($packageName);
        if (!$composer) {
            return [];
        }

        $requires = $composer['require'] ?? [];
        if (!is_array($requires) || $requires === []) {
            return [];
        }

        $providers = [];
        $visited = [$packageName => true];
        $queue = array_keys($requires);
        $catalogPackages = $this->getExtrasCatalogPackages();
        $useCatalog = $catalogPackages !== [];
        while ($queue !== []) {
            $dependency = array_pop($queue);
            if (!is_string($dependency)) {
                continue;
            }
            $dependency = trim($dependency);
            if (!$this->isComposerDependencyName($dependency)) {
                continue;
            }
            if (isset($visited[$dependency])) {
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

            $depRequires = $depComposer['require'] ?? [];
            if (is_array($depRequires) && $depRequires !== []) {
                foreach (array_keys($depRequires) as $childDependency) {
                    if (is_string($childDependency)) {
                        $queue[] = $childDependency;
                    }
                }
            }
        }

        $providers = array_filter($providers, 'is_string');
        return array_values(array_unique($providers));
    }

    protected function getPackageComposer($packageName)
    {
        $composerPath = $this->getPackageComposerPath($packageName);
        if ($composerPath === '' || !file_exists($composerPath)) {
            return null;
        }
        $raw = file_get_contents($composerPath);
        $composer = json_decode($raw, true);
        if (!is_array($composer)) {
            return null;
        }
        return $composer;
    }

    protected function extractProviders(array $composer): array
    {
        $laravelProviders = $composer['extra']['laravel']['providers'] ?? [];
        $evolutionProviders = $composer['extra']['evolution']['providers'] ?? [];
        $providers = array_merge((array) $laravelProviders, (array) $evolutionProviders);
        $providers = array_filter($providers, 'is_string');
        return array_values(array_unique($providers));
    }

    protected function isEvoPackageType($type): bool
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

    protected function isExtrasCatalogPackage(string $packageName): bool
    {
        if ($packageName === '') {
            return false;
        }
        $packages = $this->getExtrasCatalogPackages();
        return isset($packages[strtolower($packageName)]);
    }

    protected function getExtrasCatalogPackages(): array
    {
        if ($this->extrasCatalogPackages !== null) {
            return $this->extrasCatalogPackages;
        }

        $catalog = $this->loadExtrasCatalog();
        if ($catalog === null) {
            $this->extrasCatalogPackages = [];
            return $this->extrasCatalogPackages;
        }

        $packages = [];
        foreach ($catalog['packages'] ?? [] as $pkg) {
            if (!is_array($pkg)) {
                continue;
            }
            $composerName = $pkg['composer_name'] ?? '';
            if (is_string($composerName) && $composerName !== '') {
                $packages[strtolower($composerName)] = true;
                continue;
            }
            $fullName = $pkg['full_name'] ?? '';
            if (is_string($fullName) && $fullName !== '') {
                $packages[strtolower($fullName)] = true;
            }
        }

        $this->extrasCatalogPackages = $packages;
        return $this->extrasCatalogPackages;
    }

    protected function getPackageComposerPath($packageName)
    {
        if (class_exists('\\Composer\\InstalledVersions')) {
            try {
                $path = \Composer\InstalledVersions::getInstallPath($packageName);
                if (is_string($path) && $path !== '') {
                    return rtrim($path, '/') . '/composer.json';
                }
            } catch (\Throwable $exception) {
                // fall back to vendor path
            }
        }
        return EVO_CORE_PATH . 'vendor/' . $packageName . '/composer.json';
    }

    protected function runArtisanCommand(array $args)
    {
        $artisan = rtrim(EVO_CORE_PATH, '/\\') . '/artisan';
        if (!file_exists($artisan)) {
            return;
        }
        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($artisan);
        foreach ($args as $arg) {
            $command .= ' ' . escapeshellarg($arg);
        }
        passthru($command);
    }

    protected function normalizeComposerVersion($version, array $branches = [], $defaultBranch = '')
    {
        if (!is_string($version)) {
            return $version;
        }
        $version = trim($version);
        if ($version === '') {
            return $version;
        }
        if (strpos($version, '|') !== false) {
            $parts = explode('|', $version);
            $normalized = [];
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part === '') {
                    continue;
                }
                $normalized[] = $this->normalizeSingleVersion($part, $branches, $defaultBranch);
            }
            return implode('|', $normalized);
        }
        return $this->normalizeSingleVersion($version, $branches, $defaultBranch);
    }

    protected function normalizeSingleVersion($version, array $branches = [], $defaultBranch = '')
    {
        if ($version === '' || strpos($version, 'dev-') === 0 || $version === '*') {
            return $version;
        }
        if ($this->isVersionLikeBranch($version)) {
            return $version . '-dev';
        }
        if ($defaultBranch !== '' && $version === $defaultBranch) {
            return 'dev-' . $version;
        }
        if (in_array($version, $branches, true)) {
            return 'dev-' . $version;
        }
        return $version;
    }

    /**
     * Detect Composer version-like branch names such as `1.x`, `2.x`, or `3.5.x`.
     *
     * Composer normalizes numeric branch aliases in the opposite direction from regular branch
     * names: `main` becomes `dev-main`, but `2.x` must be requested as `2.x-dev`.
     *
     * @param string $version Version or branch value selected by the operator.
     * @return bool True when the selected branch must use Composer's `*.x-dev` constraint form.
     */
    protected function isVersionLikeBranch(string $version): bool
    {
        return preg_match('/^\d+(?:\.\d+)*\.x$/', $version) === 1;
    }

    protected function parsePackageArguments()
    {
        $packageArg = $this->argument('packageName');
        $versionArg = $this->argument('versionPackage');

        if (is_string($packageArg)) {
            $packageArg = trim($packageArg);
        }
        if (is_string($versionArg)) {
            $versionArg = trim($versionArg);
        }
        if (is_string($packageArg) && $versionArg === null) {
            $atPos = strrpos($packageArg, '@');
            if ($atPos !== false) {
                $maybePackage = substr($packageArg, 0, $atPos);
                $maybeVersion = substr($packageArg, $atPos + 1);
                if ($maybePackage !== '' && $maybeVersion !== '') {
                    $packageArg = $maybePackage;
                    $versionArg = $maybeVersion;
                }
            }
        }

        return [$packageArg, $versionArg];
    }

    public function getGithubFile($url)
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => $this->getGithubHeaders(),
            ]
        ];

        $context = stream_context_create($opts);
        return file_get_contents($url, false, $context);
    }

    protected function getGithubHeaders()
    {
        $headers = ['User-Agent: PHP'];
        $token = getenv('GITHUB_PAT');
        if ($token === false && function_exists('env')) {
            $token = env('GITHUB_PAT');
        }
        $token = is_string($token) ? trim($token) : '';
        if ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        return $headers;
    }

    public function installCustomPackage()
    {
        $this->enterName();
        $data = $this->getGithubFile($this->file);
        file_put_contents($this->directory . '/temp.zip', $data);
        $zip = new \ZipArchive;
        if ($zip->open($this->directory . '/temp.zip') == true) {
            $zip->extractTo($this->directory . '/');
            $zip->close();
            File::copyDirectory($this->directory . '/' . $this->selectPackage . '-' . $this->version . '/', $this->directory . '/');
            File::deleteDirectory($this->directory . '/' . $this->selectPackage . '-' . $this->version . '/');
            unlink($this->directory . '/temp.zip');
            $serviceprovider = file_get_contents($this->directory . '/src/ExampleServiceProvider.php');
            $serviceprovider = str_replace('example', $this->namePackage, $serviceprovider);
            $serviceprovider = str_replace('Example', ucfirst($this->namePackage), $serviceprovider);
            file_put_contents($this->directory . '/src/' . ucfirst($this->namePackage) . 'ServiceProvider.php', $serviceprovider);
            //update composer part
            $composer = file_get_contents($this->directory . '/src/composer.json');
            $composer = str_replace('example', $this->namePackage, $composer);
            $composer = str_replace('Example', ucfirst($this->namePackage), $composer);
            file_put_contents($this->directory . '/src/composer.json', $composer);

            unlink($this->directory . '/src/ExampleServiceProvider.php');
            $dirForComposer = 'packages/' . $this->namePackage . '/src/';
            $namespaceForComposer = 'EvolutionCMS\\' . ucfirst($this->namePackage) . '\\';
            $this->call('package:installautoload', ['key' => $namespaceForComposer, 'value' => $dirForComposer]);
            if (file_exists($this->directory . 'install.md'))
                echo file_get_contents($this->directory . 'install.md');
        }
    }

    public function enterName($message = '')
    {
        if (!is_null($this->argument('namePackage'))) {
            $this->namePackage =  $this->argument('namePackage');
        } else {
            $this->namePackage = $this->ask($message . 'Enter u package name: ');
        }
        $this->namePackage = strtolower($this->namePackage);
        $this->checkPath();
    }

    public function checkPath()
    {
        $this->directory = EVO_CORE_PATH . 'custom/packages/' . $this->namePackage;
        if (!File::isDirectory($this->directory)) {
            File::makeDirectory($this->directory, 0755, true);
        } else {
            $this->enterName('This package name already used. Please enter other name.' . "\n");
        }
    }

    protected function loadExtrasCatalog(): ?array
    {
        $this->catalogError = '';
        $path = $this->getEnvValue('EXTRAS_CATALOG_PATH');
        if ($path !== '' && file_exists($path)) {
            $raw = @file_get_contents($path);
            $catalog = $this->decodeExtrasCatalog($raw);
            if ($catalog !== null) {
                return $catalog;
            }
            $this->catalogError = 'Invalid extras catalog at EXTRAS_CATALOG_PATH.';
            return null;
        }

        $cacheDir = EVO_CORE_PATH . 'custom/cache';
        $cacheDir = rtrim($cacheDir, '/\\');
        if (!File::isDirectory($cacheDir)) {
            File::makeDirectory($cacheDir, 0755, true);
        }
        $cachePath = $cacheDir . '/extras.catalog.json';
        $metaPath = $cacheDir . '/extras.catalog.meta.json';

        $url = $this->getEnvValue('EXTRAS_CATALOG_URL');
        if ($url === '') {
            $url = self::DEFAULT_EXTRAS_CATALOG_URL;
        }
        if ($url !== '') {
            $catalog = $this->fetchCatalogFromUrl($url, $cachePath, $metaPath);
            if ($catalog !== null) {
                return $catalog;
            }
        }

        if (file_exists($cachePath)) {
            $raw = @file_get_contents($cachePath);
            $catalog = $this->decodeExtrasCatalog($raw);
            if ($catalog !== null) {
                return $catalog;
            }
        }

        if ($this->catalogError === '') {
            $this->catalogError = 'Extras catalog unavailable.';
        }
        return null;
    }

    protected function fetchCatalogFromUrl(string $url, string $cachePath, string $metaPath): ?array
    {
        $headers = ['User-Agent: EvolutionCMS'];
        $meta = $this->readJsonFile($metaPath);
        if (!empty($meta['etag'])) {
            $headers[] = 'If-None-Match: ' . $meta['etag'];
        }
        if (!empty($meta['last_modified'])) {
            $headers[] = 'If-Modified-Since: ' . $meta['last_modified'];
        }
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);

        $result = @file_get_contents($url, false, $context);
        if ($result === false && empty($http_response_header)) {
            $this->catalogError = 'Unable to download extras catalog.';
            return null;
        }
        $headersOut = $http_response_header ?? [];
        $status = $this->parseStatusCode($headersOut);

        if ($status === 304) {
            if (file_exists($cachePath)) {
                $raw = @file_get_contents($cachePath);
                $catalog = $this->decodeExtrasCatalog($raw);
                if ($catalog !== null) {
                    return $catalog;
                }
            }
            $this->catalogError = 'Extras catalog cache is missing or invalid.';
            return null;
        }

        if ($status < 200 || $status >= 300) {
            $this->catalogError = 'Unable to download extras catalog.';
            return null;
        }

        $catalog = $this->decodeExtrasCatalog($result);
        if ($catalog === null) {
            $this->catalogError = 'Invalid extras catalog JSON.';
            return null;
        }

        File::put($cachePath, $result);
        $meta = [
            'url' => $url,
            'etag' => $this->extractHeaderValue($headersOut, 'ETag'),
            'last_modified' => $this->extractHeaderValue($headersOut, 'Last-Modified'),
            'fetched_at' => date('c'),
        ];
        File::put($metaPath, json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $catalog;
    }

    protected function decodeExtrasCatalog($raw): ?array
    {
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return null;
        }
        if (($data['type'] ?? '') !== 'extras') {
            return null;
        }
        if (!isset($data['packages']) || !is_array($data['packages'])) {
            return null;
        }
        return $data;
    }

    protected function readJsonFile(string $path): array
    {
        if (!file_exists($path)) {
            return [];
        }
        $raw = file_get_contents($path);
        $data = json_decode((string) $raw, true);
        return is_array($data) ? $data : [];
    }

    protected function parseStatusCode(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('#^HTTP/\\S+\\s+(\\d{3})#', $header, $matches)) {
                return (int) $matches[1];
            }
        }
        return 0;
    }

    protected function extractHeaderValue(array $headers, string $name): string
    {
        $needle = strtolower($name);
        foreach ($headers as $header) {
            $pos = strpos($header, ':');
            if ($pos === false) {
                continue;
            }
            $headerName = strtolower(trim(substr($header, 0, $pos)));
            if ($headerName === $needle) {
                return trim(substr($header, $pos + 1));
            }
        }
        return '';
    }

    protected function getEnvValue(string $key): string
    {
        $value = getenv($key);
        if ($value === false && function_exists('env')) {
            $value = env($key);
        }
        return is_string($value) ? trim($value) : '';
    }
}
