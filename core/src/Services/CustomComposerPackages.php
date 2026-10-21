<?php declare(strict_types=1);

namespace EvolutionCMS\Services;

/**
 * Packages a site adds through core/custom/composer.json (merged by
 * wikimedia/composer-merge-plugin).
 *
 * Every update replaces core/composer.lock and core/vendor with the ones the
 * release ships, which know nothing of these packages: until Composer resolves
 * them again they are neither installed nor autoloaded. make:site and the CLI
 * installer both restore them from here, with a Composer update scoped to them.
 *
 * @since 3.5.9
 */
final class CustomComposerPackages
{
    public function __construct(private string $corePath)
    {
        $this->corePath = rtrim($corePath, '/\\') . '/';
    }

    /**
     * Package constraints from core/custom/composer.json.
     *
     * Platform requirements such as php/ext-json are left out, because Composer
     * cannot update them as packages.
     *
     * @return array<string, string> Lower-cased package name => constraint.
     */
    public function constraints(): array
    {
        $customComposer = $this->corePath . 'custom/composer.json';
        if (!is_file($customComposer)) {
            return [];
        }

        $composer = json_decode((string) file_get_contents($customComposer), true);
        if (!is_array($composer)) {
            throw new \RuntimeException('Invalid custom composer.json.');
        }

        $requires = $composer['require'] ?? [];
        if (!is_array($requires)) {
            return [];
        }

        $packages = [];
        foreach ($requires as $package => $constraint) {
            $package = trim((string) $package);
            if (self::isPackageName($package)) {
                $packages[strtolower($package)] = trim((string) $constraint);
            }
        }

        return $packages;
    }

    /**
     * Versions core/composer.lock holds for the given packages.
     *
     * Read before the update replaces the lock, so the packages come back at the
     * version the site ran rather than at the newest one their constraint allows.
     *
     * @param array<int, string> $packages Composer package names.
     * @return array<string, string>
     */
    public function lockedVersions(array $packages): array
    {
        $lockFile = $this->corePath . 'composer.lock';
        if (!is_file($lockFile)) {
            return [];
        }

        $lock = json_decode((string) file_get_contents($lockFile), true);
        if (!is_array($lock)) {
            return [];
        }

        $packageMap = array_fill_keys(array_map('strtolower', $packages), true);
        $versions = [];

        foreach (['packages', 'packages-dev'] as $section) {
            foreach (($lock[$section] ?? []) as $package) {
                $name = strtolower((string) ($package['name'] ?? ''));
                if ($name !== '' && isset($packageMap[$name]) && !empty($package['version'])) {
                    $versions[$name] = (string) $package['version'];
                }
            }
        }

        return $versions;
    }

    /**
     * Arguments of a "composer update" scoped to the custom packages.
     *
     * @param array<string, string> $constraints Package constraints from core/custom/composer.json.
     * @param array<string, string> $lockedVersions Versions from the lock file before the update.
     * @return string Quoted package list followed by the update options.
     */
    public function updateArguments(array $constraints, array $lockedVersions = []): string
    {
        $packages = [];
        foreach ($constraints as $package => $constraint) {
            if (!self::isPackageName((string) $package)) {
                continue;
            }

            $package = strtolower((string) $package);
            $version = trim((string) ($lockedVersions[$package] ?? ''));
            $packages[] = $version !== '' ? $package . ':' . $version : $package;
        }

        if ($packages === []) {
            throw new \RuntimeException('Custom Composer packages are missing.');
        }

        return implode(' ', array_map('escapeshellarg', array_values(array_unique($packages))))
            . ' --with-all-dependencies --no-dev --no-interaction --prefer-dist --optimize-autoloader --classmap-authoritative --no-scripts';
    }

    /**
     * Arguments of a "composer update" that brings the custom packages back
     * without moving anything else it does not have to.
     *
     * Unlike updateArguments(), which make:site runs with the versions it locked
     * before the update, there is no earlier lock left here. --with-all-dependencies
     * would then move core's own requirements (illuminate/*, symfony/*) to their
     * newest release; --with-dependencies keeps them, and --minimal-changes keeps
     * shared dependencies where they are unless a custom package needs otherwise.
     *
     * @since 3.5.9
     * @param array<string, string> $constraints Package constraints from core/custom/composer.json.
     * @param bool $minimalChanges Whether the Composer that runs them knows --minimal-changes (2.7.0+).
     * @return string Quoted package list followed by the update options.
     */
    public function restoreArguments(array $constraints, bool $minimalChanges): string
    {
        $packages = [];
        foreach (array_keys($constraints) as $package) {
            if (self::isPackageName((string) $package)) {
                $packages[] = strtolower((string) $package);
            }
        }

        if ($packages === []) {
            throw new \RuntimeException('Custom Composer packages are missing.');
        }

        return implode(' ', array_map('escapeshellarg', array_values(array_unique($packages))))
            . ' --with-dependencies' . ($minimalChanges ? ' --minimal-changes' : '')
            . ' --no-dev --no-interaction --prefer-dist --optimize-autoloader --classmap-authoritative --no-scripts';
    }

    /**
     * Packages the Composer of core/vendor runs on: composer/composer and everything it requires.
     *
     * That Composer loads its classes from the vendor directory it rewrites. When an
     * update removes one of these packages, the next class it needs from it is gone
     * and Composer stops halfway, with the vendor directory half replaced.
     *
     * @since 3.5.9
     * @return list<string>
     */
    public function composerRuntimePackages(): array
    {
        $installed = json_decode((string) @file_get_contents($this->corePath . 'vendor/composer/installed.json'), true);
        $requires = [];
        foreach ((array) ($installed['packages'] ?? $installed ?? []) as $package) {
            if (is_array($package) && isset($package['name'])) {
                $requires[strtolower((string) $package['name'])] = array_keys((array) ($package['require'] ?? []));
            }
        }

        $runtime = [];
        $queue = ['composer/composer'];
        while ($queue !== []) {
            $name = strtolower((string) array_shift($queue));
            if (isset($runtime[$name]) || !isset($requires[$name])) {
                continue;
            }
            $runtime[$name] = true;
            foreach ($requires[$name] as $dependency) {
                if (self::isPackageName((string) $dependency)) {
                    $queue[] = $dependency;
                }
            }
        }

        return array_keys($runtime);
    }

    /**
     * Packages an update would install, update or remove, read from Composer's output.
     *
     * @since 3.5.9
     * @param string $output Output of "composer update --dry-run".
     * @return list<string>
     */
    public static function changedPackages(string $output): array
    {
        preg_match_all(
            '~^\s*-\s*(?:Installing|Upgrading|Downgrading|Updating|Removing)\s+([a-z0-9][a-z0-9_.-]*/[a-z0-9][a-z0-9_.-]*)~mi',
            $output,
            $matches
        );

        return array_values(array_unique(array_map('strtolower', $matches[1])));
    }

    public static function isPackageName(string $package): bool
    {
        return preg_match('~^[a-z0-9][a-z0-9_.-]*/[a-z0-9][a-z0-9_.-]*$~i', $package) === 1;
    }
}
