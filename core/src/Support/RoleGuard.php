<?php namespace EvolutionCMS\Support;

use EvolutionCMS\Models\Permissions;
use EvolutionCMS\Models\RolePermissions;

/**
 * Keeps a manager from granting, through a role, more than it holds itself.
 *
 * Saving a role (a=35/36), deleting a role and assigning a role to a user are reachable by the
 * ordinary role / user rights, so without this guard a non-administrator could give a role (its
 * own, a new one, or a user) a permission it does not have, or tamper with a stronger role,
 * including the administrator role. The administrator (role 1) bypasses every check.
 */
class RoleGuard
{
    public const ADMIN_ROLE = 1;

    /**
     * May a manager create, edit, delete or assign a role that currently holds $targetPermissions?
     *
     * A non-administrator may never touch the administrator role, and may not act on a role that
     * already holds a permission the actor itself lacks (a role at least as strong as the actor).
     *
     * @param int      $actorRole        the acting manager's role
     * @param string[] $actorPermissions permission keys the actor holds
     * @param int      $targetRole       the role being managed or assigned (0 for a new role)
     * @param string[] $targetPermissions the target role's current permission keys
     * @return bool
     */
    public static function mayManageRole(int $actorRole, array $actorPermissions, int $targetRole, array $targetPermissions): bool
    {
        if ($actorRole === self::ADMIN_ROLE) {
            return true;
        }
        if ($targetRole === self::ADMIN_ROLE) {
            return false;
        }

        return array_diff($targetPermissions, $actorPermissions) === [];
    }

    /**
     * Grants that nothing authorizes any more: role_actionok, the lang key the earlier default
     * seeders granted every role in place of action_ok, and action_ok itself, which nothing checks.
     * Sites installed with those seeders still carry them; they count on neither side.
     */
    public const OBSOLETE = ['role_actionok', 'action_ok'];

    /**
     * What a role authorizes at runtime: the session of its managers holds every stored grant
     * (StoreContextService::buildManagerPermissions()), whether or not a permissions row defines
     * it, so a plugin's own key counts as much as a core one.
     *
     * @return string[]
     */
    public static function grantsOf(int $roleId): array
    {
        $grants = RolePermissions::query()->where('role_id', $roleId)->pluck('permission')->all();

        return array_values(array_unique(array_filter($grants, static fn ($key) => is_string($key) && $key !== '')));
    }

    /**
     * The grants of a role that set its reach, for mayManageRole(): every grant that authorizes at
     * runtime, always-on permissions included, less only the obsolete keys.
     *
     * @return string[]
     */
    public static function reachOf(int $roleId): array
    {
        return array_values(array_diff(self::grantsOf($roleId), self::OBSOLETE));
    }

    /**
     * May the acting manager give a user $roleId? Assigning needs save_role and a role within the
     * actor's reach, as EditOrNewUser asks before it changes a user's role.
     *
     * @param callable(string): mixed $hasPermission the actor's Core::hasPermission()
     */
    public static function mayAssignRole(int $actorRole, callable $hasPermission, int $roleId): bool
    {
        return (bool) $hasPermission('save_role')
            && self::mayManageRole($actorRole, self::heldBy($hasPermission), $roleId, self::reachOf($roleId));
    }

    /**
     * The keys the acting manager holds, asked of its runtime check for every key a role can carry:
     * each defined permission and each key any role is granted, custom ones included.
     *
     * @param callable(string): mixed $hasPermission the actor's Core::hasPermission()
     * @return string[]
     */
    public static function heldBy(callable $hasPermission): array
    {
        $keys = array_merge(
            Permissions::query()->pluck('key')->all(),
            RolePermissions::query()->distinct()->pluck('permission')->all()
        );
        $keys = array_unique(array_filter($keys, static fn ($key) => is_string($key) && $key !== ''));

        return array_values(array_filter($keys, static fn ($key) => (bool) $hasPermission($key)));
    }

    /**
     * The permission keys a manager may actually store on a role: the requested and the always-on
     * keys, each only if the manager holds it. A non-administrator never stores a grant it lacks,
     * a baseline one included; an administrator keeps everything requested plus the baseline.
     *
     * @param int      $actorRole
     * @param string[] $actorPermissions
     * @param string[] $requestedKeys
     * @param string[] $requiredKeys  always-on (disabled) permissions, stored for every role
     * @return string[]
     */
    public static function grantablePermissions(int $actorRole, array $actorPermissions, array $requestedKeys, array $requiredKeys): array
    {
        $keys = array_merge($requestedKeys, $requiredKeys);
        $allowed = $actorRole === self::ADMIN_ROLE ? $keys : array_intersect($keys, $actorPermissions);

        return array_values(array_unique($allowed));
    }
}
