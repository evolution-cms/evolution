<?php

namespace EvolutionCMS\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Illuminate\Support\Facades\Route;

final class LivewireManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerFoundationShims();
    }

    public function boot(): void
    {
        if (!class_exists(Livewire::class)) {
            return;
        }

        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/manager/livewire/update', $handle)
                ->middleware('mgr')
                ->name('manager.livewire.update');
        });

        Livewire::setScriptRoute(function ($handle) {
            return Route::get('/manager/livewire/livewire.js', $handle)
                ->middleware('mgr');
        });

        if (method_exists(Livewire::class, 'setPersistentMiddleware')) {
            Livewire::setPersistentMiddleware(config('app.middleware.mgr', []));
        }
    }

    private function registerFoundationShims(): void
    {
        $this->aliasIfMissing(
            'Illuminate\\Foundation\\Http\\Middleware\\TrimStrings',
            \EvolutionCMS\Livewire\Foundation\Http\Middleware\TrimStrings::class
        );

        $this->aliasIfMissing(
            'Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull',
            \EvolutionCMS\Livewire\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class
        );

        $this->aliasIfMissing(
            'Illuminate\\Foundation\\Http\\Events\\RequestHandled',
            \EvolutionCMS\Livewire\Foundation\Http\Events\RequestHandled::class
        );

        $this->aliasIfMissing(
            'Illuminate\\Foundation\\Auth\\Access\\AuthorizesRequests',
            \EvolutionCMS\Livewire\Foundation\Auth\Access\AuthorizesRequests::class
        );
    }

    private function aliasIfMissing(string $alias, string $target): void
    {
        if (class_exists($alias)) {
            return;
        }

        if (class_exists($target)) {
            class_alias($target, $alias);
        }
    }
}
