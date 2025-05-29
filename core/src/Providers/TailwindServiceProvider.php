<?php namespace EvolutionCMS\Providers;

use Illuminate\Support\ServiceProvider;
use EvolutionCMS\Services\TailwindService;
use EvolutionCMS\Console\TailwindBuildCommand;

class TailwindServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TailwindService::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                TailwindBuildCommand::class,
            ]);
        }
    }
}
