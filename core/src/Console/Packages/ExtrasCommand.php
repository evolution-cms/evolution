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
        $version = $this->getPackages('https://api.github.com/orgs/evolution-cms-extras/repos');
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

    public function getPackages($url)
    {
        $packageForChose = [];
        $fullPackage = $this->getGithubInfo($url);
        if(!is_array($fullPackage)){
            echo 'The limit that is provided for free use of github has been exceeded. Please try later.';
            exit();
        }
        foreach ($fullPackage as $key => $package) {
            $packageForChose[$key] = $package['name'];
            $this->fullPackage[$package['name']] = $package;
        }
        if (!is_null($this->argument('packageName')) && in_array($this->argument('packageName'), $packageForChose)) {
            $this->selectPackage = $this->argument('packageName');
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
        if (!is_null($this->argument('versionPackage')) && in_array($this->argument('versionPackage'), $getTags)) {
            return $this->argument('versionPackage');
        } else {
            return $this->choice('Select version', $getTags);
        }

    }

    public function getGithubInfo($url)
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: PHP'
                ]
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

    public function getGithubFile($url)
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: PHP'
                ]
            ]
        ];

        $context = stream_context_create($opts);
        return file_get_contents($url, false, $context);
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
