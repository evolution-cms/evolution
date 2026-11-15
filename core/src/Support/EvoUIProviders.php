<?php namespace EvolutionCMS\Support;

use EvoUI\EvoUIServiceProvider;
use Livewire\LivewireServiceProvider;

/**
 * Keep the bundled EvoUI provider in charge of its Livewire runtime.
 *
 * @since 3.6.0
 */
final class EvoUIProviders
{
    public const CORE_KEY = 'Evolution_EvoUI';

    /**
     * Whether a separate declaration would duplicate EvoUI or its Livewire runtime.
     *
     * An installation without EvoUI can still register Livewire on its own.
     *
     * @param array<string, mixed> $configured The application's provider declarations.
     * @since 3.6.0
     */
    public static function handles(string $provider, array $configured): bool
    {
        return match ($provider) {
            EvoUIServiceProvider::class => ($configured[self::CORE_KEY] ?? null) === EvoUIServiceProvider::class,
            LivewireServiceProvider::class => in_array(EvoUIServiceProvider::class, $configured, true)
                && class_exists(EvoUIServiceProvider::class),
            default => false,
        };
    }
}
