<?php

namespace EvolutionCMS\Support;

use EvolutionCMS\Legacy\Permissions;
use EvolutionCMS\Models\SiteContent;

/**
 * Per-document access checks for actions that touch a whole branch at once.
 *
 * Deleting, undeleting, reordering (menuindex sort) and dragging a document read one
 * id from the request but write to its descendants or to a posted list of ids as well.
 * The single permission check on the entry id is then not enough: a branch can hold a
 * document made private to a group the acting manager is not in, and the cascade would
 * reach it anyway. These helpers tell which of a set of ids the manager may not reach,
 * reusing the same rule the resource tree and the single-document check already apply
 * ({@see ResourceParentGuard::documentIsAccessible()}).
 */
class DocumentCascadeGuard
{
    /**
     * The ids the actor may not reach, given each document's privatemgr flag.
     *
     * Pure: no database or session access. $documents maps document id => privatemgr.
     * An administrator, or an install with use_udperms off, reaches everything.
     *
     * @param array<int|string, int|string|null> $documents document id => privatemgr
     * @param int|string|null $role manager role of the actor
     * @param mixed $usePermissions the use_udperms setting
     * @param int[] $accessibleDocumentIds ids of the private documents the actor belongs to
     * @return int[]
     */
    public static function deniedIds(
        array $documents,
        $role,
        $usePermissions,
        array $accessibleDocumentIds
    ): array {
        if (ResourceParentGuard::grantsWithoutLookup($role, $usePermissions)) {
            return [];
        }

        $denied = [];
        foreach ($documents as $id => $privatemgr) {
            $accessible = ResourceParentGuard::documentIsAccessible(
                $role,
                $usePermissions,
                $privatemgr,
                $id,
                $accessibleDocumentIds
            );
            if (!$accessible) {
                $denied[] = (int) $id;
            }
        }

        return $denied;
    }

    /**
     * The ids among $ids the current manager session may not reach.
     *
     * Trashed documents are kept in the lookup, so restoring and moving one stays possible.
     * An id with no document row is treated as unreachable rather than silently allowed.
     *
     * @param array<int|string> $ids
     * @return int[]
     */
    public static function deniedForSession(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($ids, 'is_numeric'))));
        if ($ids === []) {
            return [];
        }

        $role = $_SESSION['mgrRole'] ?? 0;
        $usePermissions = evo()->getConfig('use_udperms');
        if (ResourceParentGuard::grantsWithoutLookup($role, $usePermissions)) {
            return [];
        }

        $documents = SiteContent::withTrashed()
            ->whereIn('id', $ids)
            ->pluck('privatemgr', 'id')
            ->all();

        // A posted id with no row cannot be reached either; mark it private.
        foreach ($ids as $id) {
            if (!array_key_exists($id, $documents)) {
                $documents[$id] = 1;
            }
        }

        return self::deniedIds($documents, $role, $usePermissions, Permissions::getAccessibleDocumentIds());
    }
}
