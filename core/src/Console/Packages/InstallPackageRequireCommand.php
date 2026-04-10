<?php namespace EvolutionCMS\Console\Packages;


use Composer\Console\Application;
use Illuminate\Console\Command;
use \EvolutionCMS;
use Symfony\Component\Console\Input\ArrayInput;

class InstallPackageRequireCommand extends Command
{
    protected const EXTRAS_CATALOG_URL = 'https://evo.im/extras.json';

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
        'repositories' => [],
        'autoload' => [
            'psr-4' => []
        ]];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->checkFile();
        $this->updateArray();
        $this->putComposer();
        if ($this->argument('composer_run') == 1) {
            $this->runComposer();
        }
    }

    public function checkFile()
    {
        if (file_exists($this->composer)) {
            $composerData = file_get_contents($this->composer);
            $decoded = json_decode($composerData, true);
            if (is_array($decoded)) {
                $this->composerArray = $decoded;
            }
        }

        if (!isset($this->composerArray['require']) || !is_array($this->composerArray['require'])) {
            $this->composerArray['require'] = [];
        }

        if (!isset($this->composerArray['repositories']) || !is_array($this->composerArray['repositories'])) {
            $this->composerArray['repositories'] = [];
        }

        if (!isset($this->composerArray['autoload']) || !is_array($this->composerArray['autoload'])) {
            $this->composerArray['autoload'] = ['psr-4' => []];
        }

        if (!isset($this->composerArray['autoload']['psr-4']) || !is_array($this->composerArray['autoload']['psr-4'])) {
            $this->composerArray['autoload']['psr-4'] = [];
        }
    }

    public function updateArray()
    {
        $packageName = trim((string) $this->argument('key'));
        $this->composerArray['require'][$packageName] = $this->argument('value');
        $this->appendExtrasRepositoriesForPackage($packageName);
    }

    public function putComposer()
    {
        $composerDir = dirname($this->composer);
        if (!is_dir($composerDir)) {
            @mkdir($composerDir, 0775, true);
        }
        file_put_contents($this->composer, json_encode($this->composerArray, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    public function runComposer()
    {
        putenv('COMPOSER_HOME=' . EVO_CORE_PATH . 'composer');
        $input = new ArrayInput(['command' => 'update']);
        $application = new Application();
        $application->setAutoExit(false);
        $application->run($input);

    }

    protected function appendExtrasRepositoriesForPackage(string $packageName): void
    {
        if ($packageName === '') {
            return;
        }

        $catalogPackages = $this->loadExtrasCatalogPackages();
        if ($catalogPackages === []) {
            return;
        }

        $visited = [];
        $queue = [$packageName];

        while ($queue !== []) {
            $current = strtolower((string) array_shift($queue));
            if ($current === '' || isset($visited[$current]) || !isset($catalogPackages[$current])) {
                continue;
            }

            $visited[$current] = true;
            $package = $catalogPackages[$current];
            $repoUrl = $this->getGithubRepoUrl($package);

            if ($repoUrl !== '') {
                $this->ensureComposerRepository($repoUrl);
            }

            foreach ($this->getExtrasDependencies($package, $catalogPackages) as $dependency) {
                if (!isset($visited[$dependency])) {
                    $queue[] = $dependency;
                }
            }
        }
    }

    protected function loadExtrasCatalogPackages(): array
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'Evolution CMS Store Installer',
            ],
            'https' => [
                'timeout' => 15,
                'user_agent' => 'Evolution CMS Store Installer',
            ],
        ]);

        $raw = @file_get_contents(static::EXTRAS_CATALOG_URL, false, $context);
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !isset($decoded['packages']) || !is_array($decoded['packages'])) {
            return [];
        }

        $packages = [];
        foreach ($decoded['packages'] as $package) {
            if (!is_array($package)) {
                continue;
            }

            $composerName = strtolower(trim((string) ($package['composer_name'] ?? '')));
            $fullName = trim((string) ($package['full_name'] ?? ''));
            if ($composerName === '' || $fullName === '' || strpos($fullName, '/') === false) {
                continue;
            }

            $packages[$composerName] = $package;
        }

        return $packages;
    }

    protected function getGithubRepoUrl(array $package): string
    {
        $fullName = trim((string) ($package['full_name'] ?? ''));
        if ($fullName === '' || strpos($fullName, '/') === false) {
            return '';
        }

        return 'https://github.com/' . $fullName;
    }

    protected function getExtrasDependencies(array $package, array $catalogPackages): array
    {
        $fullName = trim((string) ($package['full_name'] ?? ''));
        if ($fullName === '' || strpos($fullName, '/') === false) {
            return [];
        }

        $defaultBranch = trim((string) ($package['default_branch'] ?? 'main'));
        $composerUrl = sprintf(
            'https://raw.githubusercontent.com/%s/%s/composer.json',
            $fullName,
            rawurlencode($defaultBranch !== '' ? $defaultBranch : 'main')
        );

        $context = stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'Evolution CMS Store Installer',
            ],
            'https' => [
                'timeout' => 15,
                'user_agent' => 'Evolution CMS Store Installer',
            ],
        ]);

        $raw = @file_get_contents($composerUrl, false, $context);
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $composer = json_decode($raw, true);
        if (!is_array($composer) || !isset($composer['require']) || !is_array($composer['require'])) {
            return [];
        }

        $dependencies = [];
        foreach (array_keys($composer['require']) as $dependencyName) {
            $dependencyName = strtolower(trim((string) $dependencyName));
            if ($dependencyName === '' || !isset($catalogPackages[$dependencyName])) {
                continue;
            }

            $dependencies[] = $dependencyName;
        }

        return array_values(array_unique($dependencies));
    }

    protected function ensureComposerRepository(string $repoUrl): void
    {
        $repoUrl = trim($repoUrl);
        if ($repoUrl === '') {
            return;
        }

        foreach ($this->composerArray['repositories'] as $repository) {
            if (!is_array($repository)) {
                continue;
            }

            if (($repository['type'] ?? '') === 'vcs' && trim((string) ($repository['url'] ?? '')) === $repoUrl) {
                return;
            }
        }

        $this->composerArray['repositories'][] = [
            'type' => 'vcs',
            'url' => $repoUrl,
        ];
    }

}
