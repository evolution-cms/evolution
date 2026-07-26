<?php declare(strict_types=1);

namespace EvolutionCMS\Services;

/**
 * Keeps the Evolution CMS Composer package identity aligned with factory metadata.
 *
 * The site updater calls this service after replacing core files and before running
 * Composer. The standalone sync script also uses it for explicit maintenance runs.
 *
 * @since 3.5.8
 */
final class ComposerVersionSynchronizer
{
    private const PACKAGE_NAME = 'evolution-cms/evolution';

    /**
     * Synchronize composer.json with the version declared in factory/version.php.
     *
     * The method updates the root package name and version, removes legacy
     * self-references from replace/conflict, and preserves unrelated entries.
     *
     * @since 3.5.8
     * @param string $composerFile Absolute path to the core composer.json file.
     * @param string $versionFile Absolute path to the factory version metadata file.
     * @return array{version:string, changed:bool} Applied version and whether composer.json was rewritten.
     */
    public function synchronize(string $composerFile, string $versionFile): array
    {
        if (!is_file($composerFile)) {
            throw new \RuntimeException("composer.json not found at {$composerFile}", 1);
        }
        if (!is_file($versionFile)) {
            throw new \RuntimeException("version.php not found at {$versionFile}", 1);
        }

        /** @var array{version?:string} $info */
        $info = include $versionFile;
        $version = $info['version'] ?? null;

        if (!is_string($version) || !preg_match('/^\d+\.\d+\.\d+(?:[-+][A-Za-z0-9\.-]+)?$/', $version)) {
            throw new \RuntimeException(
                'Invalid or missing version in version.php: ' . var_export($version, true),
                2
            );
        }

        $composerRaw = file_get_contents($composerFile);
        if ($composerRaw === false) {
            throw new \RuntimeException('Failed to read composer.json', 3);
        }

        $composer = json_decode($composerRaw, true);
        if (!is_array($composer)) {
            throw new \RuntimeException('Failed to parse composer.json', 3);
        }

        $changed = false;

        if (($composer['name'] ?? null) !== self::PACKAGE_NAME) {
            $composer['name'] = self::PACKAGE_NAME;
            $changed = true;
        }

        if (($composer['version'] ?? null) !== $version) {
            $composer['version'] = $version;
            $changed = true;
        }

        foreach (['replace', 'conflict'] as $section) {
            if (!isset($composer[$section]) || !is_array($composer[$section])) {
                continue;
            }

            if (array_key_exists(self::PACKAGE_NAME, $composer[$section])) {
                unset($composer[$section][self::PACKAGE_NAME]);
                $changed = true;
            }

            if ($composer[$section] === []) {
                unset($composer[$section]);
                $changed = true;
            }
        }

        $newComposer = json_encode(
            $composer,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        if ($newComposer === false) {
            throw new \RuntimeException('Failed to encode composer.json', 3);
        }
        $newComposer .= PHP_EOL;

        $rewritten = $changed && $newComposer !== $composerRaw;
        if ($rewritten && file_put_contents($composerFile, $newComposer) === false) {
            throw new \RuntimeException('Failed to write composer.json', 3);
        }

        return [
            'version' => $version,
            'changed' => $rewritten,
        ];
    }
}
