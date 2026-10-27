<?php

use EvolutionCMS\Providers\ArtisanServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Console\Signals;
use Illuminate\Container\Container;
use Symfony\Component\Console\Application;

beforeEach(function () {
    $this->resolver = new ReflectionProperty(Signals::class, 'availabilityResolver');
    $this->previousResolver = $this->resolver->getValue();
    $this->resolver->setValue(null, null);

    (new ArtisanServiceProvider(new Container()))->register();
});

afterEach(function () {
    $this->resolver->setValue(null, $this->previousResolver);
});

test('console boot registers signal availability for the current runtime', function () {
    $called = false;

    Signals::whenAvailable(function () use (&$called) {
        $called = true;
    });

    expect($called)->toBe(function_exists('pcntl_signal'));
});

test('commands can trap and release signals after console boot', function () {
    $command = new Command();
    $command->setApplication(new Application());
    $resolved = false;
    $signal = defined('SIGUSR1') ? SIGUSR1 : 10;
    $previousHandler = function_exists('pcntl_signal_get_handler')
        ? pcntl_signal_get_handler($signal)
        : null;

    try {
        $command->trap(function () use (&$resolved, $signal) {
            $resolved = true;

            return $signal;
        }, static function () {});

        expect($resolved)->toBe(function_exists('pcntl_signal'));
    } finally {
        $command->untrap();
    }

    if ($previousHandler !== null) {
        expect(pcntl_signal_get_handler($signal))->toBe($previousHandler);
    }
});
