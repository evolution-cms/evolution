<?php namespace EvolutionCMS\Console;

use Illuminate\Console\Command;

/**
 * @see: https://github.com/laravel-zero/foundation/blob/5.6/src/Illuminate/Foundation/Console/ClearCompiledCommand.php
 */
class ClearCacheFullCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'cache:clear-full';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Full cache clear blade + Evolution';
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (file_exists($servicesPath = $this->laravel->getCachedServicesPath())) {
            @unlink($servicesPath);
        }
        if (file_exists($packagesPath = $this->laravel->getCachedPackagesPath())) {
            @unlink($packagesPath);
        }

        evo()->clearCache('full');
        $this->info('Cache clear');
    }
}
