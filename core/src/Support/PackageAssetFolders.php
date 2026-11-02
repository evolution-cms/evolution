<?php

namespace EvolutionCMS\Support;

/**
 * The folders Composer packages publish into: assets/plugins/<name>, assets/modules/<name>, assets/snippets/<name>.
 *
 * Their content is rebuilt from core/vendor (vendor:publish, or the package itself while it boots, as the dTui
 * editor and EvoUI do), so it is no part of a site's repository. Each such folder gets a .gitignore that ignores
 * the whole folder, unless it has one already; the folder is created for it, so that a package filling it later
 * still lands in an ignored folder. A site's own plugins are never touched: only publish targets count, and a
 * folder that is a link is left alone.
 *
 * @since 3.6.0
 */
final class PackageAssetFolders
{
    public const TYPES = ['plugins', 'modules', 'snippets'];

    public const GITIGNORE = "# Published by a Composer package (vendor:publish or the package itself): rebuilt from core/vendor.\n*\n";

    /**
     * Package folders among publish targets.
     *
     * @param array<mixed, mixed> $publishes Source => target, as ServiceProvider::pathsToPublish() returns it.
     * @param string $assetsPath The site's assets folder.
     * @return list<string> Absolute folder paths, with forward slashes.
     */
    public static function folders(array $publishes, string $assetsPath): array
    {
        $assets = rtrim(str_replace('\\', '/', $assetsPath), '/') . '/';
        $folders = [];
        foreach ($publishes as $source => $target) {
            if (!is_string($target)) {
                continue;
            }
            $path = str_replace('\\', '/', $target);
            if (!str_starts_with($path, $assets)) {
                continue;
            }
            $parts = explode('/', trim(substr($path, strlen($assets)), '/'));
            if (count($parts) < 2 || !in_array($parts[0], self::TYPES, true) || in_array($parts[1], ['', '.', '..'], true)) {
                continue;
            }
            // assets/plugins/<name> itself is a folder only when a folder is published there, not a file.
            if (count($parts) === 2 && !is_dir(self::sourcePath((string) $source))) {
                continue;
            }
            $folders[$assets . $parts[0] . '/' . $parts[1]] = true;
        }

        return array_keys($folders);
    }

    /**
     * Give every package folder among the publish targets its .gitignore.
     *
     * @param array<mixed, mixed> $publishes Source => target, as ServiceProvider::pathsToPublish() returns it.
     * @param string $assetsPath The site's assets folder.
     * @return list<string> The .gitignore files written.
     */
    public static function ignore(array $publishes, string $assetsPath): array
    {
        $written = [];
        foreach (self::folders($publishes, $assetsPath) as $folder) {
            $file = $folder . '/.gitignore';
            if (is_link($folder) || file_exists($file)) {
                continue;
            }
            if (!is_dir($folder) && !@mkdir($folder, 0775, true) && !is_dir($folder)) {
                continue;
            }
            if (@file_put_contents($file, self::GITIGNORE) !== false) {
                $written[] = $file;
            }
        }

        return $written;
    }

    /**
     * A source path without the "symlink:" marker of VendorPublishCommand.
     */
    private static function sourcePath(string $source): string
    {
        return str_starts_with($source, 'symlink:') ? substr($source, strlen('symlink:')) : $source;
    }
}
