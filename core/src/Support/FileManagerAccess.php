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

        if ($root === '') {
            return false;
        }
        if (self::caseInsensitiveFileSystem()) {
            // ASSETS/PLUGINS names the same folder as assets/plugins on Windows
            return strcasecmp($path, $root) === 0 || strncasecmp($path, $root . '/', strlen($root) + 1) === 0;
        }

        return $path === $root || strncmp($path, $root . '/', strlen($root) + 1) === 0;
    }

    private static function caseInsensitiveFileSystem(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
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

        // the destination was free, so a row still stored there is a leftover of a file removed
        // outside the CMS; kept, it would let its group in beside the groups that moved along.
        // (A change of letter case alone is the same entry only where the file system ignores case;
        // elsewhere report.txt and REPORT.txt are two files and the destination is a different one.)
        if (!(self::caseInsensitiveFileSystem() && strcasecmp($oldKey, $newKey) === 0)) {
            self::forgetRestrictions($newKey);
        }

        foreach (self::subtreeRows($oldKey) as $row) {
            $file = self::normalizeRelativePath($row->file);
            $row->update(['file' => $newKey . substr($file, strlen($oldKey))]);
        }
    }

    /**
     * The direct restriction a copy or a moved file at $targetKey needs to stay as closed as it is
     * at $sourceKey. Access is an AND over the restricted folders on the way (and the file itself),
     * each of which is an OR over its groups - one set of groups on one key cannot say that. What
     * the folders above $targetKey enforce anyway is left out; of the rest, the groups that satisfy
     * every set at once are kept. That never opens the file to anyone the original was closed to.
     *
     * @return int[]|null the groups ([] when nothing needs carrying), or null when no single set
     *                    expresses it (the sets share no group): the caller must refuse
     */
    public static function carriedRestrictions(?string $sourceKey, ?string $targetKey): ?array
    {
        $sourceKey = self::normalizeRelativePath($sourceKey);
        $targetKey = self::normalizeRelativePath($targetKey);
        if ($sourceKey === '' || $sourceKey === $targetKey) {
            return [];
        }

        $restrictions = self::loadRestrictions([$sourceKey]);
        $enforcedAnyway = array_flip(array_slice(self::ancestorPaths($targetKey), 0, -1));

        $sets = [];
        foreach (self::ancestorPaths($sourceKey) as $path) {
            $groups = array_values(array_unique(array_map('intval', $restrictions[$path] ?? [])));
            if ($groups === [] || isset($enforcedAnyway[$path])) {
                continue;
            }
            sort($groups);
            $sets[implode(',', $groups)] = $groups;
        }

        if ($sets === []) {
            return [];
        }

        // An empty target key is the ACL root itself or a path outside it. There is no
        // per-entry row that can carry a source restriction to either destination.
        if ($targetKey === '') {
            return null;
        }

        $common = array_values(array_intersect(...array_values($sets)));

        return $common === [] ? null : $common;
    }

    /**
     * Add groups to $key as direct restrictions, keeping the ones it has. For an entry that
     * already exists; a copy lands on a free path and takes replaceRestrictions(), which also
     * drops what stale rows would add.
     *
     * @param int[] $groups
     */
    public static function addRestrictions(?string $key, array $groups): void
    {
        $key = self::normalizeRelativePath($key);
        if ($key === '' || $groups === []) {
            return;
        }

        // whatever the table's keys do about duplicates, a group is stored once per entry
        $missing = array_diff(array_map('intval', $groups), self::storedGroupsOf($key));
        if ($missing === []) {
            return;
        }

        FileGroup::query()->insertOrIgnore(array_map(
            static fn ($groupId) => ['document_group' => $groupId, 'file' => $key],
            array_values(array_unique($missing))
        ));
    }

    /**
     * The rows stored for exactly $key. The database may compare names without regard to case
     * (the column takes the collation of the database), which would hand back the rows of a
     * sibling that differs by letter case alone.
     */
    private static function exactRows(string $key)
    {
        return FileGroup::query()->where('file', $key)->get()
            ->filter(static fn ($row) => self::normalizeRelativePath($row->file) === $key);
    }

    /** @return int[] */
    private static function storedGroupsOf(string $key): array
    {
        return self::exactRows($key)->map(static fn ($row) => (int) $row->document_group)->all();
    }

    /**
     * Make the direct restriction of $key exactly $groups: a moved file brings its own groups
     * along, and they may be wider than what it has to be held to now.
     *
     * @param int[] $groups
     */
    public static function replaceRestrictions(?string $key, array $groups): void
    {
        $key = self::normalizeRelativePath($key);
        if ($key === '') {
            return;
        }

        $groups = array_map('intval', $groups);
        $stale = self::exactRows($key)
            ->filter(static fn ($row) => !in_array((int) $row->document_group, $groups, true))
            ->map(static fn ($row) => $row->getKey())->all();
        if ($stale !== []) {
            FileGroup::query()->whereIn('id', $stale)->delete();
        }
        self::addRestrictions($key, $groups);
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
