<?php

use EvolutionCMS\Middleware\Manager;
use EvolutionCMS\Middleware\RoutingModuleAccess;
use EvolutionCMS\Support\ModuleAccess;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

/*
|--------------------------------------------------------------------------
| The login exemption of the manager middleware belongs to the dispatcher
|--------------------------------------------------------------------------
|
| ManagerTheme::getActionId() returns 0 for any `a` that is not a valid action id (`a=0`, `a=x`,
| `a=99999`), and the middleware let action 0 through without a session because 0 is the login
| page. Routing modules (Core::registerRoutingModule()) share the `mgr` group but ignore `a`, so
| `manager/modules/{id}/...?a=x` reached a module's controllers as a guest. Their routes also never
| went through the module ACL of execute_module.processor.php.
|
*/

function managerRequestFor(?string $uri): Request
{
    $request = Request::create('/' . ltrim((string) $uri, '/'));
    if ($uri !== null) {
        $route = new Route(['GET', 'POST'], $uri, fn () => null);
        $request->setRouteResolver(fn () => $route);
    }

    return $request;
}

it('treats only the root route as the action dispatcher', function (?string $uri, bool $dispatcher) {
    expect(Manager::isActionDispatcher(managerRequestFor($uri)))->toBe($dispatcher);
})->with([
    'manager index' => ['/', true],
    'no route resolved' => [null, true],
    'routing module' => ['modules/0123456789abcdef0123456789abcdef/items', false],
    'routing module root' => ['modules/0123456789abcdef0123456789abcdef', false],
    'add-on endpoint' => ['evo-ui/{path?}', false],
]);

it('lets a guest skip the login only for the login action on the dispatcher', function () {
    expect(Manager::skipsLogin(0, true))->toBeTrue()
        // a=x / a=0 on a module route: no exemption
        ->and(Manager::skipsLogin(0, false))->toBeFalse()
        // no `a` at all opens the frameset, which needs a session
        ->and(Manager::skipsLogin(null, true))->toBeFalse()
        ->and(Manager::skipsLogin(8, true))->toBeFalse()
        ->and(Manager::skipsLogin(27, true))->toBeFalse();
});

it('skips the manager access check only for login and logout on the dispatcher', function () {
    expect(Manager::skipsManagerAccess(0, true))->toBeTrue()
        ->and(Manager::skipsManagerAccess(8, true))->toBeTrue()
        ->and(Manager::skipsManagerAccess(0, false))->toBeFalse()
        ->and(Manager::skipsManagerAccess(8, false))->toBeFalse()
        ->and(Manager::skipsManagerAccess(null, true))->toBeFalse()
        ->and(Manager::skipsManagerAccess(27, true))->toBeFalse();
});

it('decides the middleware gates from the route, not from the action alone', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Middleware/Manager.php');

    expect($source)->toContain('static::skipsLogin($action, $dispatcher)')
        ->and($source)->toContain('static::skipsManagerAccess($action, $dispatcher)')
        ->and($source)->not->toContain('if (0 !== $action && ManagerTheme::isAuthManager() === false)');
});

it('applies the module ACL to routing module routes', function () {
    $module = ['id' => 'abc', 'name' => 'Reports', 'file' => '', 'icon' => '', 'properties' => ['routes' => 'x']];
    $restricted = $module;
    $restricted['properties']['roles'] = '2';

    expect(RoutingModuleAccess::allows(true, 3, $module))->toBeTrue()
        ->and(RoutingModuleAccess::allows(false, 3, $module))->toBeFalse()
        ->and(RoutingModuleAccess::allows(false, ModuleAccess::ADMIN_ROLE, $module))->toBeFalse()
        ->and(RoutingModuleAccess::allows(true, 3, null))->toBeFalse()
        ->and(RoutingModuleAccess::allows(true, 3, $restricted))->toBeFalse()
        ->and(RoutingModuleAccess::allows(true, 2, $restricted))->toBeTrue()
        ->and(RoutingModuleAccess::allows(true, ModuleAccess::ADMIN_ROLE, $restricted))->toBeTrue();
});

it('registers routing module routes behind the module ACL middleware', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Core.php');
    $start = strpos($source, 'public function registerRoutingModule(');
    $body = substr($source, $start, 800);

    expect($start)->not->toBeFalse()
        ->and($body)->toContain("Route::middleware(['mgr', Middleware\\RoutingModuleAccess::class . ':' . \$module_id])");
});
