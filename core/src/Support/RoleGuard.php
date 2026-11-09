<?php namespace EvolutionCMS\Support;

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
     * The permission keys a manager may actually store on a role: the requested keys it also holds,
     * plus the always-on baseline keys. An administrator keeps everything requested.
     *
     * @param int      $actorRole
     * @param string[] $actorPermissions
     * @param string[] $requestedKeys
     * @param string[] $requiredKeys  always-on (disabled) permissions, stored for every role
     * @return string[]
     */
    public static function grantablePermissions(int $actorRole, array $actorPermissions, array $requestedKeys, array $requiredKeys): array
    {
        $allowed = $actorRole === self::ADMIN_ROLE
            ? $requestedKeys
            : array_intersect($requestedKeys, $actorPermissions);

        return array_values(array_unique(array_merge($allowed, $requiredKeys)));
    }
}
