<?php namespace EvolutionCMS\Console\Packages;

use Doctrine\DBAL\Exception;
use Illuminate\Console\Command;
use \EvolutionCMS;
use Illuminate\Support\Facades\File;

class ExtrasCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'extras {typePackage?} {packageName?} {versionPackage?} {namePackage?}';

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

    /**
     *
     */
    public function workWithExtras()
    {
        $version = $this->getPackages(['https://api.github.com/users/Seiger/repos','https://api.github.com/orgs/evolution-cms-extras/repos']);
        switch ($version) {
            case 'Current and updated';
                $this->version = '*';
                break;
            default:
                $this->version = $version;
                break;
        }
        $url = 'https://raw.githubusercontent.com/' . $this->fullPackage[$this->selectPackage]['full_name'] . '/' . $this->fullPackage[$this->selectPackage]['default_branch'] . '/composer.json';
        $gitInfo = $this->getGithubInfo($url);
        if(!is_array($gitInfo)){
            echo 'The limit that is provided for free use of github has been exceeded. Please try later.';
            exit();
        }
        if (isset($gitInfo['name'])) {
            $this->call("package:installrequire", ['key' => $gitInfo['name'], 'value' => $this->version]);
            $this->runPostInstallSteps($gitInfo['name']);
        } else {
            echo 'No composer.json file';
        }
    }

    /**
     *
     */
    public function workWithPackage()
    {
        $version = $this->getPackages('https://api.github.com/orgs/evolution-cms-packages/repos');
        switch ($version) {
            case 'Current and updated';
                if (count($this->tags) > 2) {
                    $this->version = $this->tags[2];
                } else {
                    $this->version = $this->tags[1];
                }
                break;
            default:
                $this->version = $version;
                break;
        }
        $this->file = 'https://github.com/' . $this->fullPackage[$this->selectPackage]['full_name'] . '/archive/' . $this->version . '.zip';
        $this->installCustomPackage();

    }

    public function getPackages($urlOrArray)
    {
        $packageForChose = [];

        // Convert string to array with single element
        if (is_string($urlOrArray)) {
            $urlOrArray = [$urlOrArray];
        }

        // Check if it's array of URLs or array of repos
        if (isset($urlOrArray[0]) && is_string($urlOrArray[0])) {
            // Array of URLs - get packages from multiple sources
            $fullPackage = [];
            foreach ($urlOrArray as $url) {
                $repos = $this->getGithubInfo($url);
                if (!is_array($repos)) {
                    echo 'The limit that is provided for free use of github has been exceeded. Please try later.';
                    exit();
                }

                // Filter Seiger repos: only those starting with 's' + uppercase letter
                if (strpos($url, 'Seiger') !== false) {
                    $repos = array_filter($repos, function($repo) {
                        return preg_match('/^s[A-Z]/', $repo['name'] ?? '');
                    });
                }

                // Merge repos
                $fullPackage = array_merge($fullPackage, $repos);
            }
        } else {
            // Already processed array of repos
            $fullPackage = $urlOrArray;
        }
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
        $packageArg = $this->argument('packageName');
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
        $getTags[] = 'Current and updated';
        foreach ($tagsInfo as $tag) {
            $getTags[] = $tag['name'];
        }
        $getTags = array_slice($getTags, 0, 4);
        $getTags[] = $this->fullPackage[$this->selectPackage]['default_branch'];
        $this->tags = $getTags;
        $versionArg = $this->argument('versionPackage');
        if (!is_null($versionArg) && in_array($versionArg, $getTags, true)) {
            return $versionArg;
        }
        if (is_null($versionArg)) {
            if (isset($tagsInfo[0]['name']) && is_string($tagsInfo[0]['name'])) {
                return $tagsInfo[0]['name'];
            }
            return $this->fullPackage[$this->selectPackage]['default_branch'];
        }
        return $this->choice('Select version', $getTags);

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

    protected function runPostInstallSteps($packageName)
    {
        if (!is_string($packageName) || $packageName === '') {
            return;
        }
        $providers = $this->getPackageProviders($packageName);
        foreach ($providers as $provider) {
            $this->runArtisanCommand(['vendor:publish', '--provider=' . $provider]);
        }
        $this->runArtisanCommand(['migrate', '--force']);
    }

    protected function getPackageProviders($packageName)
    {
        $composerPath = $this->getPackageComposerPath($packageName);
        if ($composerPath === '' || !file_exists($composerPath)) {
            return [];
        }
        $raw = file_get_contents($composerPath);
        $composer = json_decode($raw, true);
        if (!is_array($composer)) {
            return [];
        }
        $laravelProviders = $composer['extra']['laravel']['providers'] ?? [];
        $evolutionProviders = $composer['extra']['evolution']['providers'] ?? [];
        $providers = array_merge((array) $laravelProviders, (array) $evolutionProviders);
        $providers = array_filter($providers, 'is_string');
        return array_values(array_unique($providers));
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
}
