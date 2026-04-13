<?php namespace EvolutionCMS\Services\Store;

class StoreContextService
{
    public function loadLanguage(string $modulePath, string $managerLanguage): array
    {
        $languageFile = rtrim($modulePath, '/\\') . '/lang/' . $managerLanguage . '.php';
        $fallbackFile = rtrim($modulePath, '/\\') . '/lang/en.php';

        if (file_exists($languageFile)) {
            include $languageFile;
        } else {
            include $fallbackFile;
        }

        return isset($_Lang) && is_array($_Lang) ? $_Lang : [];
    }

    public function getLanguageCode(string $managerLanguage): string
    {
        return substr($managerLanguage, 0, 2);
    }

    public function isSuperAdmin(): bool
    {
        return isset($_SESSION['mgrRole']) && (int) $_SESSION['mgrRole'] === 1;
    }

    public function buildRequesterSnapshot(): array
    {
        $modx = \EvolutionCMS();
        $sessionId = function_exists('session_id') ? (string) session_id() : '';

        return [
            'user_id' => isset($_SESSION['mgrInternalKey']) ? (int) $_SESSION['mgrInternalKey'] : 0,
            'username' => isset($_SESSION['mgrShortname']) ? (string) $_SESSION['mgrShortname'] : '',
            'email' => isset($_SESSION['mgrEmail']) ? (string) $_SESSION['mgrEmail'] : '',
            'role' => isset($_SESSION['mgrRole']) ? (int) $_SESSION['mgrRole'] : 0,
            'is_super_admin' => $this->isSuperAdmin(),
            'permissions' => $this->getSystemTaskPermissions($modx),
            'requested_at' => date(DATE_ATOM),
            'ip' => isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '',
            'session_hash' => $sessionId !== '' ? sha1($sessionId) : '',
        ];
    }

    public function getSystemTaskUiFlags(): array
    {
        $permissions = $this->getSystemTaskPermissions(\EvolutionCMS());

        return [
            'can_view' => !empty($permissions['system_tasks.view']) ? 1 : 0,
            'can_manage_packages' => !empty($permissions['system_tasks.manage_packages']) ? 1 : 0,
            'can_site_update' => !empty($permissions['system_tasks.site_update']) ? 1 : 0,
        ];
    }

    protected function getSystemTaskPermissions($modx): array
    {
        $canManagePackages = $modx->hasPermission('system_tasks.manage_packages') ? 1 : 0;
        $canSiteUpdate = $modx->hasPermission('system_tasks.site_update') ? 1 : 0;
        $canView = $modx->hasPermission('system_tasks.view') ? 1 : 0;

        if ($canManagePackages || $canSiteUpdate) {
            $canView = 1;
        }

        return [
            'exec_module' => $modx->hasPermission('exec_module') ? 1 : 0,
            'system_tasks.view' => $canView,
            'system_tasks.manage_packages' => $canManagePackages,
            'system_tasks.site_update' => $canSiteUpdate,
        ];
    }
}
