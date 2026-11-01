<?php

use EvolutionCMS\Providers\ManagerThemeServiceProvider;
use EvoUI\EvoUI;
use Illuminate\Container\Container;

function managerThemeComponents(EvoUI $evoUi): array
{
    return (new ReflectionProperty(EvoUI::class, 'components'))->getValue($evoUi);
}

test('booting the manager theme provider does not resolve EvoUI', function () {
    $app = new Container();
    $app->singleton(EvoUI::class);

    (new ManagerThemeServiceProvider($app))->boot();

    expect($app->resolved(EvoUI::class))->toBeFalse();
    expect(managerThemeComponents($app->make(EvoUI::class)))
        ->toHaveKey('evo-core.system-info', EvolutionCMS\Livewire\SystemInfo::class);
});

test('an EvoUI resolved before the manager theme provider boots still gets the system info component', function () {
    $app = new Container();
    $app->singleton(EvoUI::class);
    $evoUi = $app->make(EvoUI::class);

    (new ManagerThemeServiceProvider($app))->boot();

    expect(managerThemeComponents($evoUi))->toHaveKey('evo-core.system-info');
});
