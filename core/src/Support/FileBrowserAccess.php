<?php

namespace EvolutionCMS\Support;

/**
 * Access rules for the embedded file browser (manager/media/browser/mcpuk), the picker the
 * editors and image TVs open. It is a different tool from the file manager (a=31): its root is
 * rb_base_dir, optionally narrowed per manager by image_base_upload_dir, never filemanager_path.
 */
final class FileBrowserAccess
{
    private const IMAGE_TYPES = ['images', 'image'];

    /** Folders below rb_base_dir that the browser opens as types (config.php `types`). */
    public const TYPE_FOLDERS = ['files', 'images', 'file', 'image', 'media'];

    /**
     * The manager permission that opens a browser type, besides file_manager which opens all.
     * The browser falls back to its first type (files) for a missing or unknown one, so those
     * need assets_files too.
     */
    public static function permissionFor(?string $type): string
    {
        return in_array($type, self::IMAGE_TYPES, true) ? 'assets_images' : 'assets_files';
    }

    /**
     * Resolve the browser root for a manager.
     *
     * @param string $defaultDir rb_base_dir, [(base_path)] allowed
     * @param string $defaultUrl rb_base_url
     * @param string $customDir image_base_upload_dir: empty, relative to rb_base_dir, or absolute
     * @return array{0: string, 1: string}|null [dir, url], or null when the custom root cannot be
     *                                           used - the caller must then refuse, since falling
     *                                           back to rb_base_dir would open every other
     *                                           manager's folders to one meant to be confined
     */
    public static function resolveUploadRoot(
        string $defaultDir,
        string $defaultUrl,
        string $customDir,
        string $basePath,
        string $baseUrl
    ): ?array {
        $defaultDir = rtrim(str_replace('\\', '/', str_replace('[(base_path)]', $basePath, $defaultDir)), '/');
        $defaultUrl = rtrim(str_replace('\\', '/', $defaultUrl), '/');
        $customDir = trim($customDir);

        if ($customDir === '') {
            return [$defaultDir, $defaultUrl];
        }

        $customDir = str_replace('\\', '/', str_replace('[(base_path)]', $basePath, $customDir));

        // a folder that does not exist yet is not canonicalized, so .. would pass the tests below
        if (in_array('..', explode('/', $customDir), true)) {
            return null;
        }

        if (!preg_match('/^(?:[A-Za-z]:\/|\/)/', $customDir)) {
            $customDir = $defaultDir . '/' . ltrim($customDir, '/');
        }

        $customDir = rtrim($customDir, '/');
        $resolvedDir = rtrim(str_replace('\\', '/', realpath($customDir) ?: $customDir), '/');

        if ($defaultDir !== '' && FileManagerAccess::isWithin($defaultDir, $resolvedDir)) {
            $relativePath = ltrim(substr($resolvedDir, strlen($defaultDir)), '/');

            return [$resolvedDir, $relativePath === '' ? $defaultUrl : $defaultUrl . '/' . $relativePath];
        }

        $siteBasePath = rtrim(str_replace('\\', '/', $basePath), '/');
        $siteBaseUrl = rtrim(str_replace('\\', '/', $baseUrl), '/');
        if ($siteBasePath !== '' && FileManagerAccess::isWithin($siteBasePath, $resolvedDir)) {
            $relativePath = ltrim(substr($resolvedDir, strlen($siteBasePath)), '/');

            return [
                $resolvedDir,
                $relativePath === '' ? ($siteBaseUrl === '' ? '/' : $siteBaseUrl) : $siteBaseUrl . '/' . $relativePath,
            ];
        }

        return null;
    }

    /**
     * Find the site-side spelling of a configured upload URL, without trusting it as a grant.
     * Callers must still compare its real directory with the configured physical upload root.
     * Remote URLs, traversal and encoded paths cannot establish an ACL identity.
     *
     * @return string|null Absolute site-side path, which may end at a deployment symlink.
     * @since 3.6.0
     */
    public static function localUploadPath(string $url, string $basePath, string $baseUrl, string $siteUrl): ?string
    {
        $parts = parse_url($url);
        $site = parse_url($siteUrl);
        if ($parts === false || $site === false || isset($parts['query']) || isset($parts['fragment'])
            || isset($parts['user']) || isset($parts['pass'])
            || (isset($parts['scheme']) && !in_array(strtolower($parts['scheme']), ['http', 'https'], true))
            || (isset($parts['host']) && (strcasecmp($parts['host'], $site['host'] ?? '') !== 0
                || ($parts['port'] ?? null) !== ($site['port'] ?? null)))) {
            return null;
        }

        $path = $parts['path'] ?? '';
        if (strpbrk($path, "\\%\0:") !== false || in_array('..', explode('/', $path), true)
            || \fileManagerRefuseReservedName($path)) {
            return null;
        }
        $baseUrl = '/' . trim($baseUrl, '/');
        if (str_starts_with($path, '/')) {
            if ($baseUrl !== '/' && !FileManagerAccess::isWithin($baseUrl, $path)) {
                return null;
            }
            $path = $baseUrl === '/' ? ltrim($path, '/') : ltrim(substr($path, strlen($baseUrl)), '/');
        }

        $root = realpath($basePath);
        if ($root === false) {
            return null;
        }
        $segments = array_filter(explode('/', $path), static fn ($part) => $part !== '' && $part !== '.');

        return rtrim(str_replace('\\', '/', $root), '/') . '/' . implode('/', $segments);
    }

    /**
     * The file_groups key of a path in a browser type folder: the type folder's name below the
     * site-wide ACL root, then the path below it by name. Links below the type folder count by
     * their own name when the file managers pass them (fileManagerPassableLinkTarget()); any
     * other link makes the path unusable. The type folder itself is named by its real path when
     * that lies below the ACL root, else by a link to it below the root (a shared folder), else
     * by the site-side spelling of rb_base_url ($localTypeDir) once realpath proves it is the
     * same folder. No name returns null; callers must not interpret that as a public file.
     *
     * @return string|null Key relative to the site-wide ACL root; an empty key is that root itself,
     *                     or a type root the site serves from outside it, where no group applies.
     * @since 3.6.0
     */
    public static function resolveAclPath(string $aclRoot, string $typeDir, string $absolutePath, ?string $localTypeDir = null): ?string
    {
        foreach ([$aclRoot, $typeDir, $absolutePath, $localTypeDir ?? ''] as $path) {
            if (strpos($path, "\0") !== false || \fileManagerRefuseReservedName($path)) {
                return null;
            }
        }
        $root = realpath($aclRoot);
        $typeRoot = realpath($typeDir);
        if ($root === false || $typeRoot === false) {
            return null;
        }
        $root = rtrim(str_replace('\\', '/', $root), '/');
        $typeRoot = rtrim(str_replace('\\', '/', $typeRoot), '/');
        $typeDir = rtrim(str_replace('\\', '/', $typeDir), '/');
        $absolutePath = rtrim(str_replace('\\', '/', $absolutePath), '/');
        $spelling = FileManagerAccess::isWithin($typeDir, $absolutePath) ? $typeDir : $typeRoot;
        if (!FileManagerAccess::isWithin($spelling, $absolutePath)
            || \fileManagerPathContainsLink($spelling, $absolutePath)) {
            return null;
        }
        $parts = [];
        $current = $spelling;
        foreach (explode('/', substr($absolutePath, strlen($spelling))) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                // fileManagerPathContainsLink() let it through, so it stays below the type folder
                array_pop($parts);
                $current = dirname($current);
                continue;
            }
            $segment = \fileManagerStoredName($current, $segment);
            $parts[] = $segment;
            $current .= '/' . $segment;
        }

        if (FileManagerAccess::isWithin($root, $typeRoot)) {
            $key = ltrim(substr($typeRoot, strlen($root)), '/');
        } elseif (($typeName = \fileManagerCanonicalPath($typeDir, $root)) !== false
            && FileManagerAccess::isWithin($root, $typeName)) {
            $key = ltrim(substr($typeName, strlen($root)), '/');
        } elseif ($localTypeDir === null || ($localRoot = realpath($localTypeDir)) === false
            || !FileManagerAccess::isWithin($typeRoot, str_replace('\\', '/', $localRoot))
            || !FileManagerAccess::isWithin(str_replace('\\', '/', $localRoot), $typeRoot)) {
            return null;
        } elseif (($localName = \fileManagerCanonicalPath($localTypeDir, $root)) !== false
            && FileManagerAccess::isWithin($root, $localName)) {
            $key = ltrim(substr($localName, strlen($root)), '/');
        } elseif (!FileManagerAccess::isWithin($root, $localTypeDir)) {
            // the site serves this folder from outside the ACL root (a narrowed filemanager_path),
            // where no file_groups row can name it (FileManagerAccess::aclKey()): nothing applies
            return '';
        } else {
            return null; // below the root, but only through a link the file managers do not pass
        }

        return trim($key . '/' . implode('/', $parts), '/');
    }

    /**
     * Diagnose an upload root without opening it to an unrecognised ACL namespace.
     * External shared roots need a proven site-side identity for every existing browser type.
     * This read-only check never creates folders or replaces the browser's operation guards.
     *
     * @since 3.6.0
     */
    public static function isUsableRoot(string $uploadDir, string $uploadUrl, string $aclRoot, string $basePath, string $baseUrl, string $siteUrl): bool
    {
        $uploadDir = str_replace('[(base_path)]', $basePath, $uploadDir);
        $aclRoot = str_replace('[(base_path)]', $basePath, $aclRoot);
        if (strpos($uploadDir . $aclRoot, "\0") !== false
            || \fileManagerRefuseReservedName($uploadDir) || \fileManagerRefuseReservedName($aclRoot)) {
            return false;
        }
        if (!is_dir($uploadDir) || !is_readable($uploadDir) || !is_dir($aclRoot) || !is_readable($aclRoot)) {
            return false;
        }
        if (self::resolveAclPath($aclRoot, $uploadDir, $uploadDir) !== null) {
            return true;
        }

        $found = false;
        foreach (self::TYPE_FOLDERS as $type) {
            $typeDir = rtrim($uploadDir, '/\\') . '/' . $type;
            if (!is_dir($typeDir)) {
                continue;
            }
            $found = true;
            $local = self::localUploadPath(rtrim($uploadUrl, '/') . '/' . $type, $basePath, $baseUrl, $siteUrl);
            if (self::resolveAclPath($aclRoot, $typeDir, $typeDir, $local) === null) {
                return false;
            }
        }

        return $found;
    }

    /**
     * Type folders a deployment keeps in shared storage, by the site-side link the file manager may
     * pass: [link => real folder], both absolute with forward slashes. A link qualifies when it is
     * the site-side spelling of a configured type folder (rb_base_url below the site root), lies
     * below the ACL root with no other link on the way, and resolves exactly to that type folder
     * (rb_base_dir/<type>) outside the ACL root. Its key is the browser's (resolveAclPath()), so
     * both file managers read and write the same file_groups rows. Links above the ACL root (a
     * hosting public_html, a release switch) belong to the root and do not count.
     *
     * @return array<string, string>
     * @since 3.6.0
     */
    public static function sharedTypeRoots(string $aclRoot, string $uploadDir, string $uploadUrl, string $basePath, string $baseUrl, string $siteUrl): array
    {
        $uploadDir = rtrim(str_replace('\\', '/', str_replace('[(base_path)]', $basePath, $uploadDir)), '/');
        $aclRoot = str_replace('[(base_path)]', $basePath, $aclRoot);
        if ($uploadDir === '' || strpos($uploadDir . $aclRoot, "\0") !== false
            || \fileManagerRefuseReservedName($uploadDir) || \fileManagerRefuseReservedName($aclRoot)) {
            return [];
        }
        $root = realpath($aclRoot);
        if ($root === false) {
            return [];
        }
        $root = rtrim(str_replace('\\', '/', $root), '/');

        $links = [];
        foreach (self::TYPE_FOLDERS as $type) {
            $typeDir = $uploadDir . '/' . $type;
            $real = realpath($typeDir);
            if ($real === false || !is_dir($real)) {
                continue;
            }
            $real = rtrim(str_replace('\\', '/', $real), '/');
            if (FileManagerAccess::isWithin($root, $real)) {
                continue; // reachable without a link: nothing to pass
            }
            $local = self::localUploadPath(rtrim($uploadUrl, '/') . '/' . $type, $basePath, $baseUrl, $siteUrl);
            $key = $local === null ? null : self::resolveAclPath($root, $typeDir, $typeDir, $local);
            if ($key === null || $key === '') {
                continue;
            }
            $link = $root . '/' . $key;
            if (!\fileManagerIsLink($link)) {
                continue;
            }
            // only the type folder itself may be the link
            $parent = $root;
            foreach (explode('/', dirname($key)) as $segment) {
                if ($segment === '.' || $segment === '') {
                    continue;
                }
                $parent .= '/' . $segment;
                if (\fileManagerIsLink($parent)) {
                    continue 2;
                }
            }
            $links[$link] = $real;
        }

        return $links;
    }
}
