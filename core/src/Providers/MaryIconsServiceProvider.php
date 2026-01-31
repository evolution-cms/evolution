<?php namespace EvolutionCMS\Providers;

use EvolutionCMS\View\Components\MaryIcon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class MaryIconsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Blade::component('mary-icon', MaryIcon::class);
    }
}
