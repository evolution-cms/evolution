<?php

namespace EvolutionCMS\Middleware;

use Closure, ManagerTheme;
use EvolutionCMS\Support\InstallerCompletion;

class Manager
{
    public function handle($request, Closure $next)
    {
        // Update last action in table active_users
        global $action;
        $action = ManagerTheme::getActionId();
        $dispatcher = static::isActionDispatcher($request);

        // accesscontrol.php checks to see if the user is logged in. If not, a log in form is shown
        if (!static::skipsLogin($action, $dispatcher) && ManagerTheme::isAuthManager() === false) {
            return ManagerTheme::renderLoginPage();
        }

        // Ignore Logout and LogIn action
        if (!static::skipsManagerAccess($action, $dispatcher) && ManagerTheme::hasManagerAccess() === false) {
            return ManagerTheme::renderAccessPage();
        }

        if (8 !== $action && ManagerTheme::isAuthManager() === true) {
            $this->grantInstaller($request->isSecure());
        }

        return $next($request);
    }

    /**
     * Whether the request goes to the manager's own action dispatcher (`manager/index.php`, route
     * `/`), the only route where `a` selects a page.
     *
     * Every other route in the `mgr` group (routing modules under `modules/{id}`, add-on endpoints)
     * ignores `a`, so the login exemption must not follow it there: getActionId() returns 0 for any
     * value that is not a valid action id, and `modules/{id}/...?a=x` used to reach a module as a
     * guest.
     */
    public static function isActionDispatcher($request): bool
    {
        $route = method_exists($request, 'route') ? $request->route() : null;
        if ($route === null) {
            // no route resolved: nothing but the dispatcher can be served
            return true;
        }

        return trim((string) $route->uri(), '/') === '';
    }

    /**
     * The login action (0) is the only page a guest may open, and only on the dispatcher.
     */
    public static function skipsLogin(?int $action, bool $dispatcher): bool
    {
        return $dispatcher && $action === 0;
    }

    /**
     * Login (0) and logout (8) are open to a signed-in user without manager access, on the dispatcher.
     */
    public static function skipsManagerAccess(?int $action, bool $dispatcher): bool
    {
        return $dispatcher && ($action === 0 || $action === 8);
    }

    /**
     * An administrator working in the Manager may run the installer (an upgrade) in the same
     * browser: the session grants it through a cookie scoped to the installer, and the installer
     * never asks the Manager.
     */
    protected function grantInstaller(bool $secure): void
    {
        if ((int) ($_SESSION['mgrRole'] ?? 0) !== 1 || !is_dir(EVO_BASE_PATH . 'install')) {
            return;
        }

        $held = $_COOKIE[InstallerCompletion::GRANT_COOKIE] ?? null;
        $held = is_string($held) ? $held : null;
        $secret = InstallerCompletion::grantToAdministrator(
            EVO_BASE_PATH . 'install.session.php',
            (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            $held,
            time(),
            (int) ini_get('session.gc_maxlifetime')
        );

        if ($secret === null || headers_sent()) {
            return;
        }

        // Set on every grant or renewal, so a browser that lost one of the two copies gets it back
        foreach (InstallerCompletion::grantCookiePaths(EVO_BASE_URL, MGR_DIR) as $path) {
            setcookie(InstallerCompletion::GRANT_COOKIE, $secret, [
                'path' => $path,
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
    }
}
