<?php namespace EvolutionCMS\Providers;

use Illuminate\Contracts\Log\ContextLogProcessor as ContextLogProcessorContract;
use Illuminate\Log\Context\ContextLogProcessor;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\ServiceProvider;
use Monolog\LogRecord;

/**
 * Class LogContextServiceProvider
 *
 * Registers the Context Log Processor for Illuminate logging when available.
 *
 * This provider conditionally binds the ContextLogProcessor to the
 * ContextLogProcessor contract to support structured logging with context
 * propagation (introduced in Laravel 12+).
 *
 * The implementation is defensive to ensure compatibility with EvolutionCMS
 * installations where illuminate/queue (and thus SerializesModels) may not be
 * installed. In such cases, the provider safely exits without registering
 * the processor to avoid fatal errors.
 *
 * @package EvolutionCMS\Providers
 */
class LogContextServiceProvider extends ServiceProvider
{
    /**
     * Register services in the container.
     *
     * Binds the ContextLogProcessorContract to the default
     * ContextLogProcessor implementation if all required interfaces,
     * classes, and traits are available in the runtime environment.
     *
     * @return void
     */
    public function register(): void
    {
        if (!interface_exists(ContextLogProcessorContract::class)) {
            return;
        }

        // Illuminate\Log\Context\Repository uses Illuminate\Queue\SerializesModels (Laravel 12+).
        // EvolutionCMS core may run without illuminate/queue installed, so fall back to a no-op processor.
        if (!trait_exists(SerializesModels::class) || !class_exists(ContextLogProcessor::class)) {
            $this->app->bind(
                ContextLogProcessorContract::class,
                static fn () => new class implements ContextLogProcessorContract {
                    public function __invoke(LogRecord $record): LogRecord
                    {
                        return $record;
                    }
                }
            );

            return;
        }

        if (class_exists(ContextLogProcessor::class)) {
            $this->app->bind(
                ContextLogProcessorContract::class,
                fn () => new ContextLogProcessor
            );
        }
    }
}
