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
}
