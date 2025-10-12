<?php namespace EvolutionCMS\Console\Packages;

use Illuminate\Console\Command;
use \EvolutionCMS;
use Illuminate\Support\Facades\File;

class PackageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'package:discover';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate ServiceProviders for custom packages';
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
     * @var array
     */
    public $require = [];

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

        if (file_exists($this->composer)) {
            $this->parseComposer($this->composer);
        }
        if (count($this->require) > 0) {
            $this->loadRequire();
        }
        unlink(EVO_CORE_PATH.'storage/bootstrap/services.php');
    }

    /**
     * @param string $composer
     */
    public function parseComposer(string $composer)
    {
        $data = json_decode(file_get_contents($composer), true);
        if (isset($data['extra']['laravel']['providers'])) {
            foreach ($data['extra']['laravel']['providers'] as $value) {
                $this->process($value);
            }
        }
        if (isset($data['require'])) {
            foreach ($data['require'] as $key => $value) {
                $composer = EVO_CORE_PATH . 'vendor/' . $key . '/composer.json';
                $this->packagePath = EVO_CORE_PATH . 'vendor/' . $key . '/';
                if (file_exists($composer)) {
                    $this->checkRequired($composer);
                    $this->parseComposerServiceProvider($composer);
                }
            }
        }
        if (isset($data['autoload']['psr-4'])) {
            foreach ($data['autoload']['psr-4'] as $key => $value) {
                $composer = EVO_CORE_PATH . 'custom/' . $value . '/composer.json';
                $this->packagePath = EVO_CORE_PATH . 'custom/' . $value . '/';
                if (file_exists($composer)) {
                    $this->parseComposerServiceProvider($composer);
                }

            }
        }
    }

    /**
     * @param string $composer
     */
    public function parseComposerServiceProvider(string $composer)
    {
        $data = json_decode(file_get_contents($composer), true);
        if (isset($data['extra']['laravel']['providers']) && is_array($data['extra']['laravel']['providers'])) {
            foreach ($data['extra']['laravel']['providers'] as $value) {
                $this->process($value);
            }
        }
        if (isset($data['extra']['laravel']['files'])) {
            foreach ($data['extra']['laravel']['files'] as $copyArray) {
                $this->copyFiles($copyArray);
            }
        }
    }

    /**
     * @param string $value
     */
    protected function process(string $value)
    {
        $arrNamespace = explode('\\', $value);
        $fileName = end($arrNamespace) . '.php';
        $fileContent = "<?php \nreturn " . $value . "::class;";
        if (file_put_contents($this->configDir . $fileName, $fileContent)) {
            $this->getOutput()->write('<info>' . $value . '</info>');
        } else {
            $this->getOutput()->write('<error>Error create config for: ' . $value . '</error>');
        }
        $this->line('');
    }

    /**
     * @param array $copyArray
     */
    protected function copyFiles(array $copyArray)
    {
        File::copyDirectory($this->packagePath . $copyArray['source'], $this->load_dir . $copyArray['destination']);
    }

    protected function checkRequired($composer)
    {
        $composerArray = json_decode(file_get_contents($composer), true);
        if (isset($composerArray['require']) && is_array($composerArray['require'])) {
            foreach ($composerArray['require'] as $key => $item) {
                $this->require[] = $key;
            }
        }
    }

    protected function loadRequire()
    {
        foreach ($this->require as $require) {
            $composer = EVO_CORE_PATH . 'vendor/' . $require . '/composer.json';
            $this->packagePath = EVO_CORE_PATH . 'vendor/' . $require . '/';
            if (file_exists($composer)) {
                $this->parseComposerServiceProvider($composer);
            }
        }
    }
}
