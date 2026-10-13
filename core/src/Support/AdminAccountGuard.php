<?php namespace EvolutionCMS\Support;

/**
 * Keeps administrator accounts out of reach of managers who are not administrators.
 *
 * `save_user`, `delete_user` and `save_role` are ordinary permissions that any custom role may
 * hold. Without this guard such a manager could set a new password or e-mail address on an
 * administrator account, or give any account the administrator role, which is a full takeover.
 */
class AdminAccountGuard
{
    public const ADMIN_ROLE = 1;

    /**
     * May a manager with this role change or delete an account with that role?
     *
     * @param int $actorRole Role of the logged-in manager.
     * @param int|null $targetRole Current role of the account, null when it does not exist yet.
     * @return bool
     */
    public static function mayManageAccount(int $actorRole, ?int $targetRole): bool
    {
        return $targetRole !== self::ADMIN_ROLE || $actorRole === self::ADMIN_ROLE;
    }

    /**
     * May a manager with this role give an account the new role?
     *
     * @param int $actorRole
     * @param int $newRole
     * @return bool
     */
    public static function mayAssignRole(int $actorRole, int $newRole): bool
    {
        return $newRole !== self::ADMIN_ROLE || $actorRole === self::ADMIN_ROLE;
    }
}
