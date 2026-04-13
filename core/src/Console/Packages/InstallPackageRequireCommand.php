<?php namespace EvolutionCMS\Console\Packages;


use Composer\Console\Application;
use Illuminate\Console\Command;
use \EvolutionCMS;
use Symfony\Component\Console\Input\ArrayInput;

class InstallPackageRequireCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'package:installrequire {key} {value} {composer_run=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install composer package';

    /**
     * Custom composer.json
     * @var string
     */
    protected $composer = EVO_CORE_PATH . 'custom/composer.json';

    /**
     * @var array
     */
    public $composerArray = [
        'name' => 'evolutioncms/custom',
        'require' => [],
        'autoload' => [
            'psr-4' => []
        ]];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $composerExisted = file_exists($this->composer);
        $originalComposerContents = $composerExisted ? file_get_contents($this->composer) : null;

        $this->checkFile();
        $this->updateArray();
        $this->putComposer();
        if ($this->argument('composer_run') == 1) {
            $exitCode = $this->runComposer();
            if ((int) $exitCode !== 0) {
                $this->restoreComposerState($composerExisted, $originalComposerContents);
            }

            return (int) $exitCode;
        }

        return self::SUCCESS;
    }

    public function checkFile()
    {
        if (file_exists($this->composer)) {
            $composerData = file_get_contents($this->composer);
            $this->composerArray = json_decode($composerData, true);
        }
    }

    public function updateArray()
    {
        $this->composerArray['require'][$this->argument('key')] = $this->argument('value');
    }

    public function putComposer()
    {
        file_put_contents($this->composer, json_encode($this->composerArray, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    public function runComposer()
    {
        putenv('COMPOSER_HOME=' . EVO_CORE_PATH . 'composer');
        $input = new ArrayInput(['command' => 'update']);
        $application = new Application();
        $application->setAutoExit(false);
        $originalCwd = function_exists('getcwd') ? getcwd() : false;

        if (is_string($originalCwd) && $originalCwd !== '') {
            chdir(EVO_CORE_PATH);
        }

        try {
            return (int) $application->run($input);
        } finally {
            if (is_string($originalCwd) && $originalCwd !== '') {
                chdir($originalCwd);
            }
        }

    }

    protected function restoreComposerState($composerExisted, $originalComposerContents)
    {
        if ($composerExisted) {
            file_put_contents($this->composer, (string) $originalComposerContents);
            return;
        }

        if (file_exists($this->composer)) {
            @unlink($this->composer);
        }
    }

}
