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

        // accesscontrol.php checks to see if the user is logged in. If not, a log in form is shown
        if (0 !== $action && ManagerTheme::isAuthManager() === false) {
            return ManagerTheme::renderLoginPage();
        }

        // Ignore Logout and LogIn action
        if (8 !== $action && 0 !== $action && ManagerTheme::hasManagerAccess() === false) {
            return ManagerTheme::renderAccessPage();
        }

        if (8 !== $action && ManagerTheme::isAuthManager() === true) {
            $this->grantInstaller($request->isSecure());
        }

        return $next($request);
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
