<?php namespace EvolutionCMS\Salo\Console;

use Illuminate\Console\Command;

class DownCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'salo:down';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Down the Evo Salo Docker Compose services';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        passthru('./core/vendor/bin/salo down');

        $this->info('Salo scaffolding down successfully.');
        return Command::SUCCESS;
    }
}
