<?php namespace EvolutionCMS\Salo\Console;

use Illuminate\Console\Command;

class BuildCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'salo:build';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build the Evo Salo with Docker Compose';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        passthru('./core/vendor/bin/salo build');

        $this->info('Salo scaffolding build successfully.');
        return Command::SUCCESS;
    }
}
