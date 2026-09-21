<?php

namespace EvolutionCMS\Support;

use EvolutionCMS\Legacy\Permissions;
use EvolutionCMS\Models\SiteContent;

class MoveDocumentTargetGuard
{
    public static function blocksParent(?SiteContent $parentDocument): bool
    {
        return $parentDocument === null || (int)$parentDocument->deleted === 1;
    }

    /**
     * True when the target is the document itself or one of its descendants (a move would create a cycle).
     * Walks the parent column, not the alias listing or closure table, so a stale cache cannot let a cycle through.
     * @since 3.5.8
     */
    public static function isInsideItself(int $documentId, int $parentId): bool
    {
        $seen = [];
        while ($parentId > 0 && !isset($seen[$parentId])) {
            if ($parentId === $documentId) {
                return true;
            }
            $seen[$parentId] = true;
            $parentId = (int)(SiteContent::withTrashed()->where('id', $parentId)->value('parent') ?? 0);
        }

        return false;
    }

    /**
     * True when document permissions (use_udperms) deny the current manager user the target folder.
     * @since 3.5.8
     */
    public static function deniedForUser(int $parentId): bool
    {
        if (!evo()->getConfig('use_udperms')) {
            return false;
        }
        $udperms = new Permissions();
        $udperms->user = evo()->getLoginUserID('mgr');
        $udperms->document = $parentId;
        $udperms->role = $_SESSION['mgrRole'] ?? 0;

        return !$udperms->checkPermissions();
    }
}
