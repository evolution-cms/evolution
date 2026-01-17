<?php namespace EvolutionCMS\Salo\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'salo:install
      {--runtime= : The PHP runtime (name of folder with Dockerfile, php.ini etc. for deploying Evolution app)}
      {--with= : The services that should be included in the installation}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Evo Salo\'s default Docker Compose file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->option('with')) {
            $services = $this->option('with') == 'none' ? [] : explode(',', $this->option('with'));
        } elseif ($this->option('no-interaction')) {
            $services = ['mysql', 'redis', 'selenium', 'mailhog'];
        } else {
            $services = $this->gatherServicesWithSymfonyMenu();
        }

        if ($this->buildDockerCompose($services) && $this->replaceEnvVariables($services)) {
            $this->info('Salo scaffolding installed successfully.');
            return Command::SUCCESS;
        }
        return Command::FAILURE;
    }

    /**
     * Gather the desired Salo services using a Symfony menu.
     *
     * @return array
     */
    protected function gatherServicesWithSymfonyMenu()
    {
        return $this->choice('Which services would you like to install?', [
            'mysql',
            'mariadb',
            'redis',
            'memcached',
            'meilisearch',
            'minio',
            'mailhog',
            'selenium',
        ], 0, null, true);
    }

    /**
     * Build the Docker Compose file.
     *
     * @param array $services
     * @return int|false
     */
    protected function buildDockerCompose(array $services)
    {
        $depends = collect($services)
            ->filter(function ($service) {
                return in_array($service, ['mysql', 'pgsql', 'mariadb', 'redis', 'meilisearch', 'minio', 'selenium']);
            })->map(function ($service) {
                return "            - {$service}";
            })->whenNotEmpty(function ($collection) {
                return $collection->prepend('depends_on:');
            })->implode("\n");

        $stubs = rtrim(collect($services)->map(function ($service) {
            return file_get_contents(__DIR__ . "/../../stubs/{$service}.stub");
        })->implode(''));

        $defaultDB = collect($services)
            ->filter(function ($service) {
                return in_array($service, ['mysql', 'pgsql', 'mariadb']);
            })->map(function ($service) {
                return "{$service}";
            })->first();

        $volumes = collect($services)
            ->filter(function ($service) {
                return in_array($service, ['mysql', 'pgsql', 'mariadb', 'redis', 'meilisearch', 'minio']);
            })->map(function ($service) {
                return "    salo{$service}:\n        driver: local";
            })->whenNotEmpty(function ($collection) {
                return $collection->prepend('volumes:');
            })->implode("\n");

        $evoRuntime = $this->option('runtime') ?: '8.3';
        $evoImage = $this->evoImage($evoRuntime);
        $evoPorts = $this->evoPorts($evoRuntime);

        $dockerCompose = file_get_contents(__DIR__ . '/../../stubs/docker-compose.stub');

        $dockerCompose = str_replace('{{depends}}', empty($depends) ? '' : '        ' . $depends, $dockerCompose);
        $dockerCompose = str_replace('{{defaultDB}}', $defaultDB, $dockerCompose);
        $dockerCompose = str_replace('{{services}}', $stubs, $dockerCompose);
        $dockerCompose = str_replace('{{volumes}}', $volumes, $dockerCompose);
        $dockerCompose = str_replace('{{evoRuntime}}', $evoRuntime, $dockerCompose);
        $dockerCompose = str_replace('{{evoImage}}', $evoImage, $dockerCompose);
        $dockerCompose = str_replace('{{evoPorts}}', $evoPorts, $dockerCompose);

        // Remove empty lines...
        $dockerCompose = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $dockerCompose);

        return file_put_contents($this->laravel->publicPath('docker-compose.yml'), $dockerCompose);
    }

    /**
     * Replace the Host environment variables in the app's .env file.
     *
     * @param array $services
     * @return int|false
     */
    protected function replaceEnvVariables(array $services)
    {
        if (file_exists(evo()->basePath('custom/.env'))) {
            $environment = file_get_contents(evo()->basePath('custom/.env'));
        } elseif (file_exists(evo()->basePath('custom/.env.docker.example'))) {
            $environment = file_get_contents(evo()->basePath('custom/.env.docker.example'));
        } else {
            $this->error('Either custom/.env or custom/.env.docker.example should exist');
            return Command::FAILURE;
        }

        if (in_array('pgsql', $services)) {
            $environment = str_replace('DB_CONNECTION=mysql', "DB_CONNECTION=pgsql", $environment);
            $environment = str_replace('DB_HOST=127.0.0.1', "DB_HOST=pgsql", $environment);
            $environment = str_replace('DB_PORT=3306', "DB_PORT=5432", $environment);
        } elseif (in_array('mariadb', $services)) {
            $environment = str_replace('DB_HOST=127.0.0.1', "DB_HOST=mariadb", $environment);
        } else {
            $environment = str_replace('DB_HOST=127.0.0.1', "DB_HOST=mysql", $environment);
        }

        $environment = str_replace('DB_USERNAME=root', "DB_USERNAME=salo", $environment);
        $environment = preg_replace("/DB_PASSWORD=(.*)/", "DB_PASSWORD=password", $environment);

        $environment = str_replace('MEMCACHED_HOST=127.0.0.1', 'MEMCACHED_HOST=memcached', $environment);
        $environment = str_replace('REDIS_HOST=127.0.0.1', 'REDIS_HOST=redis', $environment);

        if (in_array('meilisearch', $services)) {
            $environment .= "\nSCOUT_DRIVER=meilisearch";
            $environment .= "\nMEILISEARCH_HOST=http://meilisearch:7700\n";
        }

        return file_put_contents(evo()->publicPath('.env'), $environment);
    }

    private function evoImage($evoRuntime)
    {
        $dockerfileContent = file_get_contents(__DIR__ . '/../../runtimes/' . $evoRuntime . '/Dockerfile');
        preg_match_all('/^\s*FROM\s+([^\s#]+)(?:\s+AS\s+\S+)?/mi', $dockerfileContent, $m);
        return $m[1] ? end($m[1]) : null;
    }

    private function evoPorts($evoRuntime)
    {
        $portsFile = __DIR__ . '/../../runtimes/' . $evoRuntime . '/ports';
        $portsLines = is_readable($portsFile) ? file($portsFile) : ['${APP_PORT:-80}:80'];
        $result = "";
        foreach ($portsLines as $portsLine) {
            $portsLine = trim($portsLine);
            if (empty($portsLine)) {
                continue;
            }
            $result .= "            - '$portsLine'" . PHP_EOL;
        }
        return $result;
    }
}
