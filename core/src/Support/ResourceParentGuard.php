<?php

namespace EvolutionCMS\Support;

/**
 * Decisions behind "may this manager user put a resource here?".
 *
 * Kept free of database and session access so both the permission check and the
 * resource tree can share exactly the same rules.
 */
class ResourceParentGuard
{
    /**
     * Access can be granted without looking at any document group.
     *
     * @param int|string|null $role manager role of the user
     * @param mixed $usePermissions the use_udperms setting
     * @return bool
     */
    public static function grantsWithoutLookup($role, $usePermissions): bool
    {
        return (int)$role === 1 || !$usePermissions;
    }

    /**
     * Whether resources may be placed in the site root.
     *
     * @param mixed $allowRootSetting the udperms_allowroot setting
     * @return bool
     */
    public static function allowsRoot($allowRootSetting): bool
    {
        return (int)$allowRootSetting === 1;
    }

    /**
     * Duplicating a document whose parent is the root produces a copy in the root,
     * which is denied whenever the root itself is denied.
     *
     * @param mixed $allowRootSetting
     * @param int|string|null $sourceParent parent of the document being duplicated
     * @return bool
     */
    public static function duplicateBlockedAtRoot($allowRootSetting, $sourceParent): bool
    {
        return !static::allowsRoot($allowRootSetting) && (int)$sourceParent === 0;
    }

    /**
     * Whether a document is reachable for a user, given the private documents that
     * user is a member of.
     *
     * @param int|string|null $role
     * @param mixed $usePermissions
     * @param int|string|null $privatemgr
     * @param int|string|null $documentId
     * @param array $accessibleDocumentIds
     * @return bool
     */
    public static function documentIsAccessible(
        $role,
        $usePermissions,
        $privatemgr,
        $documentId,
        array $accessibleDocumentIds
    ): bool {
        if (static::grantsWithoutLookup($role, $usePermissions)) {
            return true;
        }

        return (int)$privatemgr === 0 || in_array((int)$documentId, $accessibleDocumentIds, true);
    }

    /**
     * Whether a resource tree node may receive a new child. Trashed documents are
     * excluded so nothing is created inside the recycle bin.
     *
     * @param mixed $hasAccess
     * @param int|string|null $deleted
     * @return bool
     */
    public static function nodeAcceptsChild($hasAccess, $deleted): bool
    {
        return (bool)$hasAccess && (int)$deleted === 0;
    }

    /**
     * Where a new resource should be created when the caller did not name a parent:
     * the root when it is available, otherwise the first document the user can reach.
     * Falls back to the root so the form still opens and the save processor is the one
     * reporting the missing permission.
     *
     * @param bool $rootAllowed
     * @param int|string|null $firstAccessibleDocument
     * @return int
     */
    public static function pickDefaultParent(bool $rootAllowed, $firstAccessibleDocument): int
    {
        if ($rootAllowed) {
            return 0;
        }

        return $firstAccessibleDocument === null ? 0 : (int)$firstAccessibleDocument;
    }
}
