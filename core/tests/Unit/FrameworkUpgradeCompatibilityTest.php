<?php

use EvolutionCMS\Extensions\Router;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Container\Container;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Request;

test('the Evo router preserves explicit routes alongside one parser fallback', function () {
    $container = new Container();
    (new Illuminate\Routing\RoutingServiceProvider($container))->register();
    $router = new Router(new Dispatcher($container), $container);
    $router->get('health/{name}', fn (string $name) => ['name' => $name]);
    $router->fallbackToParser();
    $router->fallbackToParser();

    $response = $router->dispatch(Request::create('/health/evo', 'GET'));

    expect($response->getStatusCode())->toBe(200)
        ->and(json_decode($response->getContent(), true))->toBe(['name' => 'evo'])
        ->and($router->getRoutes()->count())->toBe(2);
});

test('native SQLite schema changes preserve data without DBAL', function () {
    $connection = new SQLiteConnection(new PDO('sqlite::memory:'), ':memory:', 'compat_');
    $schema = $connection->getSchemaBuilder();
    $schema->create('resources', function (Blueprint $table) {
        $table->increments('id');
        $table->string('title');
        $table->string('obsolete')->nullable();
    });
    $connection->table('resources')->insert(['title' => 'Existing document']);
    $schema->table('resources', fn (Blueprint $table) => $table->renameColumn('title', 'pagetitle'));
    $schema->table('resources', fn (Blueprint $table) => $table->dropColumn('obsolete'));

    expect($schema->getColumnListing('resources'))->toBe(['id', 'pagetitle'])
        ->and($connection->table('resources')->value('pagetitle'))->toBe('Existing document');
});

test('the Illuminate HTTP client sends JSON through Guzzle and resolves feed links', function () {
    $handler = new MockHandler([new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}')]);
    $request = (new Factory())->withHeaders(['X-Evo-Version' => '3.6']);
    $response = $request->setHandler($handler)->post('https://example.test/api', ['resource' => 42]);
    $sent = $handler->getLastRequest();

    expect($response->successful())->toBeTrue()
        ->and($response->json('ok'))->toBeTrue()
        ->and($sent->getMethod())->toBe('POST')
        ->and($sent->getHeaderLine('X-Evo-Version'))->toBe('3.6')
        ->and(json_decode((string) $sent->getBody(), true))->toBe(['resource' => 42])
        ->and((string) UriResolver::resolve(new Uri('https://example.test/news/feed.xml'), new Uri('../story')))
        ->toBe('https://example.test/story');
});
