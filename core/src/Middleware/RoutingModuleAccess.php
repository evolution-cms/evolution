<?php

namespace EvolutionCMS\Middleware;

use Closure;
use EvolutionCMS\Support\ModuleAccess;
use ManagerTheme;

/**
 * Applies the module execution ACL to the routes of a routing module (Core::registerRoutingModule()).
 *
 * A routing module is served by its own routes under `modules/{id}`, so it never passes through
 * execute_module.processor.php, which is where `exec_module` and the role restriction of a file
 * module are checked. Without this, every manager role reached every routing module, whatever the
 * menu showed. Runs after the `mgr` group, so the user is already signed in here.
 *
 * @since 3.5.8
 */
class RoutingModuleAccess
{
    public function handle($request, Closure $next, string $moduleId = '')
    {
        $evo = evo();
        $module = $evo->modulesFromFile[$moduleId] ?? null;

        if (!static::allows($evo->hasPermission('exec_module'), (int) ($_SESSION['mgrRole'] ?? 0), $module)) {
            return response(ManagerTheme::getLexicon('module_exec_no_privileges'), 403);
        }

        return $next($request);
    }

    /**
     * @param array|null $module Entry from Core::$modulesFromFile, null when not registered.
     */
    public static function allows(bool $canExecModules, int $roleId, ?array $module): bool
    {
        if (!$canExecModules || $module === null) {
            return false;
        }

        return ModuleAccess::canRunFileModule($roleId, $module);
    }
}
