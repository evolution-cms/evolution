<?php

use EvolutionCMS\Core;
use EvolutionCMS\Parser;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

/*
|--------------------------------------------------------------------------
| The chunk parser builds its Blade view on first use
|--------------------------------------------------------------------------
|
| Most pages never render a Blade chunk, and building the copy of the view
| factory for every request resolved the whole view stack.
|
*/

beforeEach(function () {
    defined('EVO_BASE_PATH') || define('EVO_BASE_PATH', sys_get_temp_dir() . '/evo-base/');
    resetParserInstance();

    $this->core = (new ReflectionClass(Core::class))->newInstanceWithoutConstructor();
    $this->viewsBuilt = 0;
    $this->core->singleton('view', function () {
        $this->viewsBuilt++;

        return new Factory(new EngineResolver(), new FileViewFinder(new Filesystem(), [sys_get_temp_dir()]), new Dispatcher());
    });
});

afterEach(fn () => resetParserInstance());

/**
 * The finder's paths with forward slashes and no trailing slash: it resolves
 * existing directories with realpath(), which uses backslashes on Windows.
 */
function viewPaths(Factory $view): array
{
    return array_map('normalizedViewPath', $view->getFinder()->getPaths());
}

function normalizedViewPath(string $path): string
{
    return rtrim(str_replace('\\', '/', $path), '/');
}

function resetParserInstance(): void
{
    (new ReflectionProperty(Parser::class, 'instance'))->setValue(null, null);
}

test('the parser does not build its view until it is read', function () {
    $parser = Parser::getInstance($this->core);
    $parser->setTemplatePath('views/');

    expect($this->viewsBuilt)->toBe(0);
});

test('checking whether the view is set does not build it', function () {
    $parser = Parser::getInstance($this->core);

    expect(isset($parser->blade))->toBeTrue()
        ->and($this->viewsBuilt)->toBe(0)
        ->and($parser->blade ?? null)->toBeInstanceOf(Factory::class)
        ->and($this->viewsBuilt)->toBe(1);
});

test('an assigned view replaces the built one', function () {
    $parser = Parser::getInstance($this->core);
    $view = new Factory(new EngineResolver(), new FileViewFinder(new Filesystem(), [sys_get_temp_dir()]), new Dispatcher());

    $parser->blade = $view;

    expect($parser->blade)->toBe($view)
        ->and($this->viewsBuilt)->toBe(0);

    $parser->blade = null;

    expect(isset($parser->blade))->toBeFalse()
        ->and($parser->blade)->toBeNull()
        ->and($this->viewsBuilt)->toBe(0);
});

test('the view is a copy of the factory, built once, with the template path given before', function () {
    $parser = Parser::getInstance($this->core);
    $parser->setTemplatePath('views/');

    $blade = $parser->blade;

    expect($blade)->toBeInstanceOf(Factory::class)
        ->and($blade)->not->toBe($this->core['view'])
        ->and($parser->blade)->toBe($blade)
        ->and($this->viewsBuilt)->toBe(1)
        ->and(viewPaths($blade))->toBe([normalizedViewPath(EVO_BASE_PATH . 'views')]);
});

test('a template path set after the view is built still reaches it', function () {
    $parser = Parser::getInstance($this->core);
    $blade = $parser->blade;

    $parser->setTemplatePath('assets/chunks/');

    expect($parser->blade)->toBe($blade)
        ->and(viewPaths($blade))->toBe([normalizedViewPath(EVO_BASE_PATH . 'assets/chunks')]);
});
