<?php namespace EvolutionCMS\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string compile(string $package, bool $forceRebuild = false)
 */
class Tailwind extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \EvolutionCMS\Services\TailwindService::class;
    }
}
