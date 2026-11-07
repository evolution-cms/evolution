<?php

use EvolutionCMS\Interfaces\CoreInterface;
use EvolutionCMS\ManagerTheme;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\CallableDispatcher;
use Illuminate\Routing\Contracts\CallableDispatcher as CallableDispatcherContract;
use Illuminate\Routing\Router;
use Symfony\Component\HttpFoundation\Response;

function managerRouteDispatch(Request $request, ?Closure $module = null): Response
{
    $container = new Container();
    $container->instance(Request::class, $request);
    $container->singleton(CallableDispatcherContract::class, fn ($app) => new CallableDispatcher($app));
    $router = new Router(new Dispatcher($container), $container);
    $router->match(['GET', 'POST'], '/', fn () => 'dashboard');
    if ($module !== null) {
        $router->get('modules/{id}', $module);
    }

    $theme = new class(Mockery::mock(CoreInterface::class), 'default') extends ManagerTheme {
        public function __construct($core, $theme)
        {
            $this->core = $core;
            $this->theme = $theme;
        }

        public function dispatch(Router $router, Request $request): Response
        {
            return $this->dispatchRoute($router, $request);
        }
    };

    return $theme->dispatch($router, $request);
}

test('a path no manager route takes answers 404 without quoting the path', function () {
    $response = managerRouteDispatch(Request::create('/<script>alert(1)</script>'));

    expect($response->getStatusCode())->toBe(404)
        ->and($response->getContent())->toBe('Not Found');
});

test('a method the dispatcher does not take answers 405 and names the allowed ones', function () {
    $response = managerRouteDispatch(Request::create('/', 'DELETE'));

    expect($response->getStatusCode())->toBe(405)
        ->and($response->getContent())->toBe('Method Not Allowed')
        ->and($response->headers->get('Allow'))->toBe('GET, HEAD, POST');
});

test('a route that exists is served as before', function () {
    $response = managerRouteDispatch(Request::create('/'));

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toBe('dashboard');
});

test('a fault inside a route still reaches the error handler', function () {
    managerRouteDispatch(
        Request::create('/modules/5'),
        fn () => throw new RuntimeException('module failed')
    );
})->throws(RuntimeException::class, 'module failed');
