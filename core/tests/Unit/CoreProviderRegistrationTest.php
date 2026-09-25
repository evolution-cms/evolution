<?php

use EvolutionCMS\Core;
use Illuminate\Support\ServiceProvider;

final class CoreProviderRegistrationProbe extends ServiceProvider
{
    public int $registrationCount = 0;

    public function register(): void
    {
        $this->registrationCount++;
    }
}

test('registering the same service provider twice reuses its first instance', function () {
    $core = (new ReflectionClass(Core::class))->newInstanceWithoutConstructor();

    $first = $core->register(CoreProviderRegistrationProbe::class);
    $second = $core->register(CoreProviderRegistrationProbe::class);

    expect($second)->toBe($first)
        ->and($first->registrationCount)->toBe(1)
        ->and($core->getProviders(CoreProviderRegistrationProbe::class))->toHaveCount(1);
});
