<?php

namespace EvolutionCMS\Support\DocumentSave;

use EvolutionCMS\Models\DocumentGroup;

/**
 * Applies the resource-group checkboxes of the editor to the document_groups table.
 * @since 3.5.9
 */
final class DocumentGroupSync
{
    /**
     * Group ids of the posted "group,link" pairs.
     *
     * @return int[]
     */
    public static function postedGroups(array $pairs): array
    {
        $groups = [];
        foreach ($pairs as $pair) {
            if (is_scalar($pair)) {
                $groups[] = (int) explode(',', (string) $pair)[0];
            }
        }

        return array_values(array_unique($groups));
    }

    /**
     * A user without one of their own groups left in the posted set would lock themselves out.
     *
     * @param int[] $posted
     * @param int[] $userGroups
     */
    public static function locksOut(array $posted, array $userGroups): bool
    {
        return $posted !== [] && array_intersect($posted, $userGroups) === [];
    }

    /**
     * @param int[] $groupsOfParent
     * @param int[] $userGroups
     */
    public static function forNewDocument(int $documentId, array $pairs, array $groupsOfParent, array $userGroups, bool $manageGroups, bool $manageDocPerms): void
    {
        if (!$manageGroups && !$manageDocPerms) {
            self::insert($documentId, $groupsOfParent); // inherit from the parent
            return;
        }

        $groups = [];
        foreach (self::postedGroups($pairs) as $group) {
            if ($manageGroups || in_array($group, $userGroups)) {
                $groups[] = $group;
            }
        }
        // the parent groups the user cannot manage stay attached
        if ($manageDocPerms) {
            foreach ($groupsOfParent as $group) {
                if (!in_array($group, $userGroups)) {
                    $groups[] = $group;
                }
            }
        }
        // nothing of their own picked: keep every group of the parent
        if (!$manageGroups && array_intersect($groups, $userGroups) === []) {
            $groups = array_merge($groups, $groupsOfParent);
        }

        self::insert($documentId, $groups);
    }

    /**
     * Returns false when the change would leave the user without access to the document.
     *
     * @param int[] $userGroups
     */
    public static function forExistingDocument(int $documentId, array $pairs, array $userGroups, bool $manageGroups, bool $makePublic): bool
    {
        $canTouch = fn (int $group): bool => $manageGroups || in_array($group, $userGroups);

        $wanted = [];
        foreach ($pairs as $pair) {
            if (!is_scalar($pair)) {
                continue;
            }
            [$group, $link] = array_pad(explode(',', (string) $pair, 2), 2, 'new');
            if ($canTouch((int) $group)) {
                $wanted[(int) $group] = $link;
            }
        }

        $current = [];
        $untouchable = [];
        foreach (DocumentGroup::query()->where('document', $documentId)->get(['id', 'document_group']) as $row) {
            if ($canTouch($row->document_group)) {
                $current[$row->document_group] = $row->id;
            } else {
                $untouchable[] = $row->document_group;
            }
        }

        $insert = [];
        foreach ($wanted as $group => $link) {
            if (isset($current[$group])) {
                unset($current[$group]);
            } elseif ($link === 'new') {
                $insert[] = $group;
            }
        }

        if (!$manageGroups && $userGroups !== []) {
            $remaining = array_merge($untouchable, array_diff(array_keys($wanted), array_keys($current)), $insert);
            if (array_intersect($userGroups, $remaining) === []) {
                return false;
            }
        }

        self::insert($documentId, $insert);
        if ($current) {
            DocumentGroup::query()->whereIn('id', array_values($current))->delete();
        }
        if ($makePublic) {
            DocumentGroup::query()->where('document', $documentId)->delete();
        }

        return true;
    }

    /**
     * @param int[] $groups
     */
    private static function insert(int $documentId, array $groups): void
    {
        $rows = [];
        foreach (array_unique($groups) as $group) {
            $rows[] = ['document_group' => (int) $group, 'document' => $documentId];
        }
        if ($rows) {
            DocumentGroup::query()->insert($rows);
        }
    }
}
