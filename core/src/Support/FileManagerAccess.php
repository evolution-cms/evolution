<?php

namespace EvolutionCMS\Support;

use EvolutionCMS\Models\FileGroup;

final class FileManagerAccess
{
    public static function normalizeRelativePath(?string $path): string
    {
        $path = str_replace('\\', '/', (string) $path);

        return trim($path, '/');
    }

    /**
     * Whether $path is $root itself or lies below it. A bare prefix test is not enough: with
     * the root at assets/alice it would also admit assets/alice-private.
     */
    public static function isWithin(string $root, string $path): bool
    {
        $root = rtrim(str_replace('\\', '/', $root), '/');
        $path = rtrim(str_replace('\\', '/', $path), '/');

        return $root !== '' && ($path === $root || strncmp($path, $root . '/', strlen($root) + 1) === 0);
    }

    /**
     * The root file groups are stored against: the site-wide filemanager_path. A manager's own
     * filemanager_path only narrows what the file manager shows; keying the ACL by it would
     * give the same file a different key per manager, and a key nobody restricted.
     */
    public static function aclRoot(): string
    {
        $evo = evo();
        $root = $evo->configGlobal['filemanager_path'] ?? $evo->getConfig('filemanager_path');
        $root = str_replace('[(base_path)]', EVO_BASE_PATH, (string) $root);

        return rtrim(str_replace('\\', '/', realpath($root) ?: realpath(EVO_BASE_PATH)), '/');
    }

    /**
     * The file_groups key of $relativePath, given relative to a manager's own root.
     *
     * @return string|null null when the path lies outside the ACL root, where no group applies
     */
    public static function aclKey(string $aclRoot, string $userRoot, ?string $relativePath): ?string
    {
        $aclRoot = rtrim(str_replace('\\', '/', $aclRoot), '/');
        $userRoot = rtrim(str_replace('\\', '/', $userRoot), '/');
        $relativePath = self::normalizeRelativePath($relativePath);

        if (self::isWithin($aclRoot, $userRoot)) {
            $prefix = self::normalizeRelativePath(substr($userRoot, strlen($aclRoot)));

            return trim($prefix . '/' . $relativePath, '/');
        }

        if (self::isWithin($userRoot, $aclRoot)) {
            // the manager sees more than the ACL root: only what lies inside it has a key
            $aclPrefix = self::normalizeRelativePath(substr($aclRoot, strlen($userRoot)));
            if ($relativePath === $aclPrefix) {
                return '';
            }

            return strncmp($relativePath, $aclPrefix . '/', strlen($aclPrefix) + 1) === 0
                ? substr($relativePath, strlen($aclPrefix) + 1)
                : null;
        }

        return null;
    }

    public static function getRelativePath(string $fileManagerRoot, string $absolutePath): string
    {
        $root = rtrim(str_replace('\\', '/', realpath($fileManagerRoot) ?: $fileManagerRoot), '/');
        $path = rtrim(str_replace('\\', '/', realpath($absolutePath) ?: $absolutePath), '/');

        if (!self::isWithin($root, $path)) {
            return '';
        }

        return self::normalizeRelativePath(substr($path, strlen($root)));
    }

    public static function ancestorPaths(?string $relativePath): array
    {
        $relativePath = self::normalizeRelativePath($relativePath);

        if ($relativePath === '') {
            return [];
        }

        $paths = [];
        $current = '';

        foreach (explode('/', $relativePath) as $segment) {
            $current = $current !== '' ? $current . '/' . $segment : $segment;
            $paths[] = $current;
        }

        return $paths;
    }

    public static function loadRestrictions(array $relativePaths): array
    {
        $expandedPaths = [];

        foreach ($relativePaths as $relativePath) {
            foreach (self::ancestorPaths($relativePath) as $ancestorPath) {
                $expandedPaths[$ancestorPath] = true;
            }
        }

        if (empty($expandedPaths)) {
            return [];
        }

        return FileGroup::query()
            ->whereIn('file', array_keys($expandedPaths))
            ->get()
            ->groupBy('file')
            ->map(static fn ($rows) => $rows->pluck('document_group')->map(static fn ($groupId) => (int) $groupId)->unique()->values()->all())
            ->all();
    }

    /**
     * Restrictions on $relativePath, its ancestors and everything below it: what a recursive
     * delete or a directory archive has to respect, where loadRestrictions() only covers the
     * paths it is given.
     */
    public static function loadSubtreeRestrictions(?string $relativePath): array
    {
        $relativePath = self::normalizeRelativePath($relativePath);
        $restrictions = self::loadRestrictions([$relativePath]);

        $query = FileGroup::query();
        if ($relativePath !== '') {
            // LIKE reads _ and % in the path as wildcards; the prefix test below drops what they let in
            $query->where('file', 'like', $relativePath . '/%');
        }

        foreach ($query->get() as $row) {
            $file = self::normalizeRelativePath($row->file);
            if (!self::isBelow($relativePath, $file)) {
                continue;
            }
            $restrictions[$file][] = (int) $row->document_group;
        }

        return array_map(static fn ($groups) => array_values(array_unique($groups)), $restrictions);
    }

    /**
     * Carry the groups of $oldKey and everything below it over to $newKey after a rename or
     * move. Whoever moved it, the rows have to follow: a row left under the old path protects
     * nothing, and the entry at the new path is open to everyone.
     */
    public static function moveRestrictions(?string $oldKey, ?string $newKey): void
    {
        $oldKey = self::normalizeRelativePath($oldKey);
        $newKey = self::normalizeRelativePath($newKey);
        if ($oldKey === '' || $newKey === '' || $oldKey === $newKey) {
            return;
        }

        foreach (self::subtreeRows($oldKey) as $row) {
            $file = self::normalizeRelativePath($row->file);
            $row->update(['file' => $newKey . substr($file, strlen($oldKey))]);
        }
    }

    /**
     * Drop the groups of $key and everything below it once the entry is gone.
     */
    public static function forgetRestrictions(?string $key): void
    {
        $key = self::normalizeRelativePath($key);
        if ($key === '') {
            return;
        }

        $ids = array_map(static fn ($row) => $row->getKey(), self::subtreeRows($key));
        if ($ids !== []) {
            FileGroup::query()->whereIn('id', $ids)->delete();
        }
    }

    /**
     * @return FileGroup[] rows of $key and below; LIKE alone would also match a sibling through _ or %
     */
    private static function subtreeRows(string $key): array
    {
        return FileGroup::query()
            ->where('file', $key)
            ->orWhere('file', 'like', $key . '/%')
            ->get()
            ->filter(static function ($row) use ($key) {
                $file = self::normalizeRelativePath($row->file);

                return $file === $key || self::isBelow($key, $file);
            })
            ->all();
    }

    /**
     * Restricted paths strictly below $relativePath that $userGroups may not reach.
     *
     * @return string[]
     */
    public static function inaccessibleDescendants(?string $relativePath, array $userGroups, array $restrictions): array
    {
        $relativePath = self::normalizeRelativePath($relativePath);
        $blocked = [];

        foreach (array_keys($restrictions) as $path) {
            $path = self::normalizeRelativePath((string) $path);
            if (self::isBelow($relativePath, $path) && !self::isAccessible($path, $userGroups, $restrictions)) {
                $blocked[] = $path;
            }
        }

        return $blocked;
    }

    private static function isBelow(string $relativePath, string $path): bool
    {
        if ($relativePath === '') {
            return $path !== '';
        }

        return strncmp($path, $relativePath . '/', strlen($relativePath) + 1) === 0;
    }

    public static function isAccessible(?string $relativePath, array $userGroups, array $restrictions = []): bool
    {
        $userGroups = array_values(array_unique(array_map('intval', $userGroups)));

        foreach (self::ancestorPaths($relativePath) as $ancestorPath) {
            $requiredGroups = array_values(array_unique(array_map('intval', $restrictions[$ancestorPath] ?? [])));

            if ($requiredGroups === []) {
                continue;
            }

            if ($userGroups === [] || array_intersect($requiredGroups, $userGroups) === []) {
                return false;
            }
        }

        return true;
    }

    public static function effectiveGroupIds(?string $relativePath, array $restrictions = []): array
    {
        $effectiveGroups = [];

        foreach (self::ancestorPaths($relativePath) as $ancestorPath) {
            foreach ($restrictions[$ancestorPath] ?? [] as $groupId) {
                $effectiveGroups[(int) $groupId] = true;
            }
        }

        return array_keys($effectiveGroups);
    }

    public static function isTopLevelPath(?string $relativePath): bool
    {
        $relativePath = self::normalizeRelativePath($relativePath);

        return $relativePath !== '' && strpos($relativePath, '/') === false;
    }

    public static function canModifyExistingPath(?string $relativePath, array $userGroups, array $restrictions = []): bool
    {
        $relativePath = self::normalizeRelativePath($relativePath);

        return $relativePath !== ''
            && !self::isTopLevelPath($relativePath)
            && self::isAccessible($relativePath, $userGroups, $restrictions);
    }
}
