<?php

use EvolutionCMS\Core;

class EvalErrorAttributionCore extends Core
{
    public array $quits = [];

    public function getConfig($name = '', $default = null)
    {
        return $name === 'error_reporting' ? 1 : $default;
    }

    public function getService($name)
    {
        $core = $this;

        return new class ($core) {
            public function __construct(private EvalErrorAttributionCore $core) {}

            public function messageQuit(...$arguments): void
            {
                $this->core->quits[] = $arguments[6];
            }
        };
    }

    public function isBackend()
    {
        return false;
    }
}

/** Runs $callback under PHP's own error handling, which is what fills error_get_last(). */
function withNativeErrorHandling(callable $callback): void
{
    set_error_handler(static fn () => false);
    try {
        $callback();
    } finally {
        restore_error_handler();
    }
}

function evalErrorAttributionCore(): EvalErrorAttributionCore
{
    $core = (new ReflectionClass(EvalErrorAttributionCore::class))->newInstanceWithoutConstructor();
    $core->event = new EvolutionCMS\Event();

    return $core;
}

test('a warning raised before a snippet is not reported as the snippet error', function () {
    $core = evalErrorAttributionCore();
    withNativeErrorHandling(function () use ($core, &$output) {
        // e.g. a provider re-running class_alias() in a persistent worker, or a suppressed @fileperms()
        @trigger_error('raised before the snippet', E_USER_WARNING);
        expect(error_get_last()['message'] ?? null)->toBe('raised before the snippet');
        $output = $core->evalSnippet('return "snippet output";', []);
    });

    expect($output)->toBe('snippet output')->and($core->quits)->toBe([]);
});

test('a warning raised before a plugin is not reported as the plugin error', function () {
    $core = evalErrorAttributionCore();
    ob_start();
    withNativeErrorHandling(function () use ($core) {
        @trigger_error('raised before the plugin', E_USER_WARNING);
        $core->evalPlugin('echo "plugin output";', []);
    });

    expect(ob_get_clean())->toBe('plugin output')->and($core->quits)->toBe([]);
});

test('a warning raised by the snippet itself is still reported', function () {
    $core = evalErrorAttributionCore();
    withNativeErrorHandling(fn () => $core->evalSnippet('@trigger_error("snippet warning", E_USER_WARNING); return "x";', []));

    expect($core->quits)->toBe(['snippet warning']);
});
