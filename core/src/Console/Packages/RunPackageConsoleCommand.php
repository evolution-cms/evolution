<?php namespace EvolutionCMS\Console\Packages;

use Illuminate\Console\Command;
use \EvolutionCMS;
use Illuminate\Support\Facades\File;

class RunPackageConsoleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'package:runconsoles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run console command from custom packages';

    /**
     * Custom composer.json
     * @var string
     */
    protected $composer = EVO_CORE_PATH . 'custom/composer.json';

    /**
     * The path to the package directory
     *
     * @var string
     */
    protected $packagePath;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (file_exists($this->composer)) {
            $this->parseComposer($this->composer);
        }
    }

    /**
     * @param string $composer
     */
    public function parseComposer(string $composer)
    {
        $data = json_decode(file_get_contents($composer), true);
        if (isset($data['extra']['laravel']['commands'])) {
            foreach ($data['extra']['laravel']['commands'] as $value) {
                $this->process($value);
            }
        }
        if (isset($data['require'])) {
            foreach ($data['require'] as $key => $value) {
                $composer = EVO_CORE_PATH . 'vendor/' . $key . '/composer.json';
                $this->packagePath = EVO_CORE_PATH . 'vendor/' . $key . '/';
                if (file_exists($composer)) {
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
        if (isset($data['extra']['laravel']['commands'])) {
            foreach ($data['extra']['laravel']['commands'] as $value) {
                $this->process($value);
            }
        }
    }

    /**
     * @param $comands
     */
    protected function process($comands)
    {
        foreach ($comands as $comand=>$params)
        $this->call($comand, $params);
    }

}
