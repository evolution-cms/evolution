<?php declare(strict_types=1);

namespace EvolutionCMS\Support;

/**
 * Decides whether a manager role may run a module.
 *
 * Two independent axes guard a module: the long standing user-group ACL
 * (site_module_access) and, since 3.5.7, a per-role ACL (site_module_roles).
 * A module with no role rows stays available to every role, so existing
 * installs behave exactly as before until somebody restricts something.
 *
 * The rules live here as pure functions so both the manager menu and the
 * execution processor answer the question the same way, and so the answer
 * can be tested without a database or a session.
 */
class ModuleAccess
{
    /**
     * The administrator role bypasses both ACL axes.
     */
    public const ADMIN_ROLE = 1;

    /**
     * Role restrictions applied to bundled modules on a fresh install and
     * to existing sites by the migration that creates the table.
     *
     * @var array<string, int[]>
     */
    public const DEFAULT_RESTRICTIONS = [
        'Extras' => [self::ADMIN_ROLE],
    ];

    /**
     * @param int $roleId Role of the user asking to run the module.
     * @param array $allowedRoleIds Roles the module is restricted to; empty means unrestricted.
     * @return bool
     */
    public static function isAllowedForRole(int $roleId, array $allowedRoleIds): bool
    {
        if ($roleId === self::ADMIN_ROLE) {
            return true;
        }

        $allowed = self::normalizeRoleIds($allowedRoleIds);
        if ($allowed === []) {
            return true;
        }

        return in_array($roleId, $allowed, true);
    }

    /**
     * @param array $roleIds
     * @return int[] Positive, unique, re-indexed role ids.
     */
    public static function normalizeRoleIds(array $roleIds): array
    {
        $normalized = [];
        foreach ($roleIds as $roleId) {
            if (is_array($roleId) || is_object($roleId) || !is_numeric($roleId)) {
                continue;
            }
            $roleId = (int) $roleId;
            if ($roleId > 0 && !in_array($roleId, $normalized, true)) {
                $normalized[] = $roleId;
            }
        }

        return $normalized;
    }

    /**
     * Roles a file-based module (registerModule()) is restricted to.
     *
     * File modules have no database row, so they declare their restriction
     * at registration time via $params['roles']. Nothing declared means
     * unrestricted, which is how every file module behaved before.
     *
     * @param array $module Entry from Core::$modulesFromFile.
     * @return int[]
     */
    public static function fileModuleAllowedRoles(array $module): array
    {
        $properties = $module['properties'] ?? [];
        if (!is_array($properties) || !isset($properties['roles'])) {
            return [];
        }

        $roles = $properties['roles'];
        if (is_string($roles)) {
            $roles = explode(',', $roles);
        }

        return is_array($roles) ? self::normalizeRoleIds($roles) : [];
    }

    /**
     * @param int $roleId
     * @param array $module Entry from Core::$modulesFromFile.
     * @return bool
     */
    public static function canRunFileModule(int $roleId, array $module): bool
    {
        return self::isAllowedForRole($roleId, self::fileModuleAllowedRoles($module));
    }

    /**
     * Default role restriction for a bundled module, by module name.
     *
     * @param string $moduleName
     * @return int[]
     */
    public static function defaultRolesFor(string $moduleName): array
    {
        return self::DEFAULT_RESTRICTIONS[$moduleName] ?? [];
    }
}
