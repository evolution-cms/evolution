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
    protected $signature = 'package:installrequire {key} {value} {composer_run=1} {--no-dev : Skip installing packages listed in require-dev} {--optimize-autoloader : Optimize Composer autoload files after update}';

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
     * Packages touched by updateArray(); scopes the composer update to them.
     * @var array<int,string>
     */
    protected $affectedPackages = [];

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
        if ($this->updateArray() === false) {
            return self::FAILURE;
        }
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
        $this->affectedPackages[] = (string) $this->argument('key');
    }

    public function putComposer()
    {
        file_put_contents($this->composer, json_encode($this->composerArray, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    /**
     * Run Composer update for the modified custom package requirements.
     *
     * Optional flags are exposed for higher-level install flows that should behave like production
     * updates. For example, `extras extras` can avoid dev dependencies and immediately build an
     * optimized autoloader while direct `package:installrequire` calls keep their previous defaults.
     *
     * @return int Composer process exit code.
     */
    public function runComposer()
    {
        putenv('COMPOSER_HOME=' . EVO_CORE_PATH . 'composer');
        $input = new ArrayInput($this->buildComposerArguments());
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

    /**
     * Build the composer update arguments.
     *
     * The update is limited to the changed packages and their dependencies,
     * like `composer require`/`remove` do. A bare `update` would also bump every
     * core dependency, including composer/composer running this very process.
     *
     * @return array<string,mixed>
     */
    public function buildComposerArguments(): array
    {
        $arguments = ['command' => 'update'];

        $packages = array_values(array_unique(array_filter(array_map('trim', $this->affectedPackages))));
        if ($packages !== []) {
            $arguments['packages'] = $packages;
            $arguments['--with-dependencies'] = true;
        }

        if ($this->hasCommandOption('no-dev') && $this->option('no-dev')) {
            $arguments['--no-dev'] = true;
        }
        if ($this->hasCommandOption('optimize-autoloader') && $this->option('optimize-autoloader')) {
            $arguments['--optimize-autoloader'] = true;
        }

        return $arguments;
    }

    protected function hasCommandOption(string $name): bool
    {
        return $this->getDefinition()->hasOption($name);
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
