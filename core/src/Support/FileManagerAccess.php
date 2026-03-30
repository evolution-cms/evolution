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

    public static function getRelativePath(string $fileManagerRoot, string $absolutePath): string
    {
        $root = rtrim(str_replace('\\', '/', realpath($fileManagerRoot) ?: $fileManagerRoot), '/');
        $path = rtrim(str_replace('\\', '/', realpath($absolutePath) ?: $absolutePath), '/');

        if ($root === '' || strpos($path, $root) !== 0) {
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
