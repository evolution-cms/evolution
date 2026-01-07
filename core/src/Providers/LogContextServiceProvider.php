<?php namespace EvolutionCMS\Providers;

use Illuminate\Contracts\Log\ContextLogProcessor as ContextLogProcessorContract;
use Illuminate\Log\Context\ContextLogProcessor;
use Illuminate\Support\ServiceProvider;

class LogContextServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (interface_exists(ContextLogProcessorContract::class) && class_exists(ContextLogProcessor::class)) {
            $this->app->bind(ContextLogProcessorContract::class, fn () => new ContextLogProcessor);
        }
    }
}
