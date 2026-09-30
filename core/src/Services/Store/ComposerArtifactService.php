<?php namespace EvolutionCMS\Services\Store;

use ZipArchive;

/**
 * Install Composer packages from uploaded archives, without Packagist.
 *
 * An uploaded archive that holds a Composer package is kept in core/custom/artifacts,
 * which custom/composer.json declares as an "artifact" repository. The merge plugin
 * prepends that repository, so Composer takes the package from the archive and never
 * asks Packagist for it; with its dependencies already installed the whole install
 * works offline.
 *
 * @since 3.5.9
 */
class ComposerArtifactService
{
    /**
     * Artifact directory, relative to the core directory Composer runs in.
     */
    public const REPOSITORY_URL = 'custom/artifacts';

    protected string $corePath;

    public function __construct(?string $corePath = null)
    {
        $this->corePath = rtrim($corePath ?? EVO_CORE_PATH, '/\\') . '/';
    }

    /**
     * Read the Composer package an archive holds.
     *
     * Composer finds composer.json at the root of an archive or inside its only top-level
     * directory, as in a GitHub download; this looks in the same places. A legacy Extras
     * package carries an install/ directory next to it and is left to the legacy installer.
     *
     * An archive shaped like a Composer package whose composer.json cannot be used is still
     * reported, with "invalid" set: handed to the legacy installer instead, its files would
     * be copied into the web root.
     *
     * @param string $zipPath Archive to inspect.
     * @param string $fileName Name the archive was uploaded under; may carry the version.
     * @return array{name: string, version: string, entry: string, composer: array, invalid: bool}|null
     *         Null when the archive holds no Composer package; version is '' when unknown.
     */
    public function inspect(string $zipPath, string $fileName = ''): ?array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return null;
        }

        try {
            $entry = $this->findComposerJson($zip);
            if ($entry === null) {
                return null;
            }

            $prefix = substr($entry, 0, -strlen('composer.json'));
            if ($this->hasEntryUnder($zip, $prefix . 'install/')) {
                return null;
            }

            $composer = json_decode((string) $zip->getFromName($entry), true);
        } finally {
            $zip->close();
        }

        if (!is_array($composer) || !self::isPackageName((string) ($composer['name'] ?? ''))) {
            return [
                'name' => '',
                'version' => '',
                'entry' => $entry,
                'composer' => [],
                'invalid' => true,
            ];
        }

        $version = trim((string) ($composer['version'] ?? ''));
        if ($version === '') {
            $version = (string) self::versionFromFileName($fileName);
        }

        return [
            'name' => (string) $composer['name'],
            'version' => $version,
            'entry' => $entry,
            'composer' => $composer,
            'invalid' => false,
        ];
    }

    /**
     * Keep an archive in the artifact repository and make Composer look there.
     *
     * Composer's artifact repository reads the version from the composer.json inside the
     * archive and rejects a package without one, so a version known only from the file name
     * is written into that composer.json.
     *
     * @param string $zipPath Uploaded archive.
     * @param array $package Result of inspect() with a non-empty version.
     * @return string Path of the stored archive.
     */
    public function store(string $zipPath, array $package): string
    {
        $directory = $this->corePath . self::REPOSITORY_URL;
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create ' . $directory . '.');
        }

        $target = $directory . '/' . self::archiveFileName($package['name'], $package['version']);
        if (!copy($zipPath, $target)) {
            throw new \RuntimeException('Unable to copy the archive to ' . $target . '.');
        }

        if (trim((string) ($package['composer']['version'] ?? '')) === '') {
            $composer = $package['composer'];
            $composer['version'] = $package['version'];

            $zip = new ZipArchive();
            if ($zip->open($target) !== true) {
                @unlink($target);
                throw new \RuntimeException('Unable to open ' . $target . '.');
            }
            $zip->addFromString($package['entry'], (string) json_encode($composer, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            $zip->close();
        }

        $this->registerRepository();

        return $target;
    }

    /**
     * Declare the artifact repository in custom/composer.json once.
     *
     * @return void
     */
    public function registerRepository(): void
    {
        $composerFile = $this->corePath . 'custom/composer.json';
        $composer = is_file($composerFile) ? json_decode((string) file_get_contents($composerFile), true) : null;
        if (!is_array($composer)) {
            $composer = [
                'name' => 'evolutioncms/custom',
                'require' => [],
                'autoload' => [
                    'psr-4' => [],
                ],
            ];
        }

        $repositories = isset($composer['repositories']) && is_array($composer['repositories']) ? $composer['repositories'] : [];
        foreach ($repositories as $repository) {
            if (is_array($repository)
                && ($repository['type'] ?? '') === 'artifact'
                && trim((string) ($repository['url'] ?? ''), '/') === self::REPOSITORY_URL
            ) {
                return;
            }
        }

        $repositories[] = ['type' => 'artifact', 'url' => self::REPOSITORY_URL];
        $composer['repositories'] = $repositories;

        file_put_contents($composerFile, json_encode($composer, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    /**
     * File name of a stored archive: one file per package version.
     */
    public static function archiveFileName(string $name, string $version): string
    {
        $safeVersion = preg_replace('~[^A-Za-z0-9._-]+~', '_', $version);

        return str_replace('/', '-', strtolower($name)) . '-' . $safeVersion . '.zip';
    }

    /**
     * Version carried by an archive name such as "sgallery-1.5.2.zip" or "sgallery-v1.5.2.zip".
     */
    public static function versionFromFileName(string $fileName): ?string
    {
        $pattern = '~(?:^|[-_ ])v?(\d+\.\d+(?:\.\d+){0,2}(?:-(?:alpha|beta|rc|patch)\.?\d*)?)\.zip$~i';
        if (preg_match($pattern, basename($fileName), $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    /**
     * Whether a string is a valid Composer package name.
     */
    public static function isPackageName(string $name): bool
    {
        return preg_match('~^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$~', $name) === 1;
    }

    private function findComposerJson(ZipArchive $zip): ?string
    {
        if ($zip->locateName('composer.json') !== false) {
            return 'composer.json';
        }

        $topLevel = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            $slash = strpos($name, '/');
            $topLevel[$slash === false ? $name : substr($name, 0, $slash + 1)] = true;
        }

        if (count($topLevel) !== 1) {
            return null;
        }

        $directory = (string) array_key_first($topLevel);
        if (!str_ends_with($directory, '/')) {
            return null;
        }

        return $zip->locateName($directory . 'composer.json') !== false ? $directory . 'composer.json' : null;
    }

    private function hasEntryUnder(ZipArchive $zip, string $directory): bool
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (str_starts_with((string) $zip->getNameIndex($i), $directory)) {
                return true;
            }
        }

        return false;
    }
}
