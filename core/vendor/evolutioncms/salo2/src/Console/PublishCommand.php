<?php namespace EvolutionCMS\Salo\Console;

use Illuminate\Console\Command;

class PublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'salo:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish the Evo Salo Docker files';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->call('vendor:publish', ['--tag' => 'salo']);

        return file_put_contents(
            $this->laravel->basePath('docker-compose.yml'),
            str_replace(
                [
                    './vendor/laravel/salo/runtimes/8.2',
                ],
                [
                    './docker/8.2',
                ],
                file_get_contents($this->laravel->basePath('docker-compose.yml'))
            )
        ) ? Command::SUCCESS : Command::FAILURE;
    }
}
