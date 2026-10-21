<?php declare(strict_types=1);

namespace EvolutionCMS\Services;

/**
 * Packages a site adds through core/custom/composer.json (merged by
 * wikimedia/composer-merge-plugin).
 *
 * Every update replaces core/composer.lock and core/vendor with the ones the
 * release ships, which know nothing of these packages: until Composer resolves
 * them again they are neither installed nor autoloaded. make:site and both
 * installers restore them from here, under one policy:
 *
 * - They come back at the versions the site ran, together with the packages only
 *   they need: read from composer.lock while it still lists them, else from
 *   custom/composer.installed.json, which every restore writes and no update
 *   package ships. A package the core requires itself keeps the core's version.
 * - Composer runs with --with-dependencies (and --minimal-changes from 2.7.0 on),
 *   never --with-all-dependencies, which would move the core's own packages too.
 * - A dry run comes first. Any change it shows beyond installing those versions
 *   (a custom package at another version, a package of the core moved or removed)
 *   needs the operator's consent; without it nothing is restored.
 * - The Composer of core/vendor never replaces the packages it runs on, however
 *   it was selected (isBundledComposer()).
 *
 * @since 3.5.9
 */
final class CustomComposerPackages
{
    /**
     * Versions of the last restore, relative to the core directory.
     */
    public const RECORD_FILE = 'custom/composer.installed.json';

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
     * @param array<int, string> $packages Composer package names.
     * @return array<string, string>
     */
    public function lockedVersions(array $packages): array
    {
        $lock = $this->lockPackages();
        $versions = [];
        foreach ($packages as $package) {
            $package = strtolower((string) $package);
            if (isset($lock[$package]) && $lock[$package]['version'] !== '') {
                $versions[$package] = $lock[$package]['version'];
            }
        }

        return $versions;
    }

    /**
     * Versions the site runs: the custom packages and the packages only they need.
     *
     * Read before an update replaces the lock (make:site does), or afterwards from the
     * record of the last restore (the installers do). A package missing from both has
     * no entry; pins() then leaves it to Composer, which needs consent.
     *
     * @param array<int, string> $packages Custom package names.
     * @return array<string, string> Package name => version.
     */
    public function previousVersions(array $packages): array
    {
        $lock = $this->lockPackages();
        $core = $this->coreRequirements();
        $record = $this->recordedVersions();

        $versions = [];
        foreach ($packages as $package) {
            $package = strtolower((string) $package);
            if (isset($core[$package])) {
                continue;
            }
            if (isset($lock[$package])) {
                $versions += $this->dependencyClosure($lock, $package, $core);
            } elseif (isset($record[$package])) {
                $versions += [$package => $record[$package]['version']] + $record[$package]['dependencies'];
            }
        }

        return $versions;
    }

    /**
     * What to hand Composer to bring the packages back into this core directory.
     *
     * Packages the lock of this directory already holds, and those the core requires
     * itself, are left out: they come with the release. The custom packages come first,
     * pinned when their version is known; the packages only they need follow, pinned.
     * A custom package the release ships at a version its constraint refuses is left to
     * Composer, which moves it only with consent. That includes a package the core
     * requires itself: the merge plugin runs with "replace", so the custom constraint
     * replaces the core's one.
     *
     * @param array<int, string> $packages Custom package names.
     * @param array<string, string> $previous Versions from previousVersions().
     * @param array<string, string> $constraints Their constraints from constraints(), to check the shipped versions against.
     * @return array<string, string> Package name => version, '' when not known.
     */
    public function pins(array $packages, array $previous, array $constraints = []): array
    {
        $lock = $this->lockPackages();
        $core = $this->coreRequirements();

        $pins = [];
        foreach ($packages as $package) {
            $package = strtolower((string) $package);
            if (!self::isPackageName($package)) {
                continue;
            }
            if (isset($lock[$package])) {
                if (!self::satisfies($lock[$package]['version'], (string) ($constraints[$package] ?? ''))) {
                    $pins[$package] = '';
                }
            } elseif (!isset($core[$package])) {
                $pins[$package] = (string) ($previous[$package] ?? '');
            }
        }
        foreach ($previous as $package => $version) {
            $package = strtolower((string) $package);
            if (!isset($pins[$package]) && self::isPackageName($package) && !isset($lock[$package]) && !isset($core[$package])) {
                $pins[$package] = (string) $version;
            }
        }

        return $pins;
    }

    /**
     * Whether every custom package is installed in core/vendor.
     *
     * @param array<int, string> $packages Custom package names.
     * @return bool
     */
    public function installed(array $packages): bool
    {
        $installed = [];
        foreach ($this->installedPackages() as $package) {
            $installed[strtolower((string) ($package['name'] ?? ''))] = true;
        }

        foreach ($packages as $package) {
            if (!isset($installed[strtolower((string) $package)])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Arguments of the "composer update" that restores the packages.
     *
     * @param array<string, string> $pins Package name => version ('' leaves the version to Composer).
     * @param bool $minimalChanges Whether the Composer that runs them knows --minimal-changes (2.7.0+).
     * @return string Quoted package list followed by the update options.
     */
    public function updateArguments(array $pins, bool $minimalChanges): string
    {
        $packages = [];
        foreach ($pins as $package => $version) {
            if (!self::isPackageName((string) $package)) {
                continue;
            }

            $package = strtolower((string) $package);
            $version = trim((string) $version);
            $packages[$package] = $version !== '' ? $package . ':' . $version : $package;
        }

        if ($packages === []) {
            throw new \RuntimeException('Custom Composer packages are missing.');
        }

        return implode(' ', array_map('escapeshellarg', array_values($packages)))
            . ' --with-dependencies' . ($minimalChanges ? ' --minimal-changes' : '')
            . ' --no-dev --no-interaction --prefer-dist --optimize-autoloader --classmap-authoritative --no-scripts';
    }

    /**
     * Decide how to restore the packages, from Composer dry runs against this core directory.
     *
     * The dry run with the pinned versions comes first. When Composer cannot resolve
     * it, a run that leaves the versions to Composer shows what would have to change.
     * The answer is ok only when nothing changes beyond installing the pinned versions,
     * or the operator allowed it, and the Composer of core/vendor would not replace the
     * packages it runs on.
     *
     * @param callable $run Runs a shell command: fn(string $command): array{0: int, 1: string}.
     * @param string $composer Composer command safe for shell usage.
     * @param array<string, string> $pins From pins().
     * @param array<int, string> $packages Custom package names.
     * @param bool $minimalChanges Whether that Composer knows --minimal-changes.
     * @param bool $allowChanges Whether the operator allowed other versions.
     * @param bool $bundled Whether that Composer runs from core/vendor (isBundledComposer()).
     * @return array{ok: bool, reason: string, arguments: string, changes: list<string>, runtime: list<string>, pinsFit: bool, output: string}
     *         reason is '' when ok, else 'unresolvable', 'changes' or 'runtime'.
     */
    public function plan(callable $run, string $composer, array $pins, array $packages, bool $minimalChanges, bool $allowChanges, bool $bundled): array
    {
        $plan = ['ok' => false, 'reason' => '', 'arguments' => $this->updateArguments($pins, $minimalChanges), 'changes' => [], 'runtime' => [], 'pinsFit' => true, 'output' => ''];
        $dryRun = fn (string $arguments): array => $run($composer . ' update ' . $arguments
            . ' --working-dir=' . escapeshellarg(rtrim($this->corePath, '/\\')) . ' --dry-run 2>&1');

        [$exitCode, $output] = $dryRun($plan['arguments']);
        if ((int) $exitCode !== 0) {
            $plan['pinsFit'] = false;
            // Every custom package, also one the release ships at a version its constraint
            // refuses: Composer may only move it with consent, and the dry run says to what.
            $customOnly = [];
            foreach ($packages as $package) {
                $package = strtolower((string) $package);
                if (self::isPackageName($package)) {
                    $customOnly[$package] = '';
                }
            }
            if ($customOnly === [] || $customOnly === $pins) {
                $plan['reason'] = 'unresolvable';
                $plan['output'] = self::outputTail((string) $output);

                return $plan;
            }

            $plan['arguments'] = $this->updateArguments($customOnly, $minimalChanges);
            [$exitCode, $output] = $dryRun($plan['arguments']);
            if ((int) $exitCode !== 0) {
                $plan['reason'] = 'unresolvable';
                $plan['output'] = self::outputTail((string) $output);

                return $plan;
            }
        }

        $plan['output'] = self::outputTail((string) $output);
        // What the restore changes is judged on the lock; any vendor operation can hit the bundled Composer.
        $plan['changes'] = self::unexpectedChanges(self::operations((string) $output, true), $pins, $packages);
        if ($plan['changes'] !== [] && !$allowChanges) {
            $plan['reason'] = 'changes';

            return $plan;
        }

        if ($bundled) {
            $plan['runtime'] = array_values(array_intersect(array_keys(self::operations((string) $output)), $this->composerRuntimePackages()));
            if ($plan['runtime'] !== []) {
                $plan['reason'] = 'runtime';

                return $plan;
            }
        }

        $plan['ok'] = true;

        return $plan;
    }

    /**
     * Check the "composer install" of this core directory, for an update with nothing to restore.
     *
     * The install still has to accept the merged requirements: a custom constraint on a
     * package the release ships (and, through the merge plugin's "replace", one the core
     * requires itself) makes it fail when the release's lock refuses it. It also brings
     * core/vendor in line with the lock, which an update package may not be, so the
     * Composer of core/vendor would replace its own packages here too.
     *
     * @param callable $run Runs a shell command: fn(string $command): array{0: int, 1: string}.
     * @param string $composer Composer command safe for shell usage.
     * @param bool $bundled Whether that Composer runs from core/vendor (isBundledComposer()).
     * @return array{ok: bool, reason: string, arguments: string, changes: list<string>, runtime: list<string>, pinsFit: bool, output: string}
     *         Shaped like plan(); arguments is always ''.
     */
    public function planInstall(callable $run, string $composer, bool $bundled): array
    {
        $plan = ['ok' => false, 'reason' => '', 'arguments' => '', 'changes' => [], 'runtime' => [], 'pinsFit' => true, 'output' => ''];

        [$exitCode, $output] = $run($composer . ' install --no-dev --no-interaction --prefer-dist --no-scripts'
            . ' --working-dir=' . escapeshellarg(rtrim($this->corePath, '/\\')) . ' --dry-run 2>&1');
        $plan['output'] = self::outputTail((string) $output);
        if ((int) $exitCode !== 0) {
            $plan['reason'] = 'unresolvable';

            return $plan;
        }

        if ($bundled) {
            $plan['runtime'] = array_values(array_intersect(array_keys(self::operations((string) $output)), $this->composerRuntimePackages()));
            if ($plan['runtime'] !== []) {
                $plan['reason'] = 'runtime';

                return $plan;
            }
        }

        $plan['ok'] = true;

        return $plan;
    }

    /**
     * Explain in a few lines why plan() or planInstall() refused.
     *
     * @param array{reason: string, changes: list<string>, runtime: list<string>, pinsFit: bool, output: string} $plan
     * @return string
     */
    public static function describeRefusal(array $plan): string
    {
        switch ($plan['reason']) {
            case 'changes':
                return ($plan['pinsFit']
                        ? 'Restoring the custom Composer packages would change installed versions:'
                        : 'The custom Composer packages at the versions the site ran do not fit this version of Evolution CMS. Restoring them would change:')
                    . "\n  - " . implode("\n  - ", $plan['changes']);
            case 'runtime':
                return 'Composer would replace packages the Composer of core/vendor runs on ('
                    . implode(', ', $plan['runtime']) . '), which would stop it halfway. Run it with a standalone Composer.';
            default:
                return "Composer could not resolve this version of Evolution CMS together with core/custom/composer.json:\n" . $plan['output'];
        }
    }

    /**
     * core/custom/composer.json as it has to read from another core directory, such as an unpacked update.
     *
     * The merge plugin resolves the path repositories and the further files of that one
     * ("include" and "require") against its own folder (it prefixes "custom/" to them), and Composer resolves
     * an artifact repository against its working directory. A copy in another core
     * directory would look for them there, where they are not: rebased, they still lead
     * to the site's own folders.
     *
     * @param array<string, mixed> $json Decoded core/custom/composer.json of this core directory.
     * @param string $otherCore The core directory the copy is for.
     * @return array<string, mixed>
     */
    public function rebasedCustomComposerJson(array $json, string $otherCore): array
    {
        $liveCustom = realpath($this->corePath . 'custom');
        $otherCustom = realpath(rtrim($otherCore, '/\\') . '/custom');
        $liveCore = realpath($this->corePath);
        if ($liveCustom === false || $otherCustom === false || $liveCore === false) {
            return $json;
        }

        $isRelative = static fn (string $path): bool => $path !== '' && preg_match('~^(?:[a-z]:)?[/\\\\]|^[a-z][a-z0-9+.-]*://~i', $path) !== 1;

        if (isset($json['repositories']) && is_array($json['repositories'])) {
            foreach ($json['repositories'] as $key => $repository) {
                if (!is_array($repository) || !is_string($repository['url'] ?? null) || !$isRelative($repository['url'])) {
                    continue;
                }
                if (($repository['type'] ?? '') === 'path') {
                    $rebased = self::relativePath($otherCustom, $liveCustom . '/' . $repository['url']);
                    if ($rebased !== null) {
                        $json['repositories'][$key]['url'] = $rebased;
                    }
                } elseif (($repository['type'] ?? '') === 'artifact') {
                    $json['repositories'][$key]['url'] = str_replace('\\', '/', $liveCore) . '/' . $repository['url'];
                }
            }
        }

        // Files to include ("include", optional) and files that must exist ("require"),
        // a list or a single path.
        $rebase = static function ($path) use ($isRelative, $otherCustom, $liveCustom) {
            if (!is_string($path) || !$isRelative($path)) {
                return $path;
            }

            return self::relativePath($otherCustom, $liveCustom . '/' . $path) ?? $path;
        };
        foreach (['include', 'require'] as $key) {
            $paths = $json['extra']['merge-plugin'][$key] ?? null;
            if (is_array($paths)) {
                $json['extra']['merge-plugin'][$key] = array_map($rebase, $paths);
            } elseif (is_string($paths)) {
                $json['extra']['merge-plugin'][$key] = $rebase($paths);
            }
        }

        return $json;
    }

    /**
     * Note the versions the packages were restored at, for the next update.
     *
     * Kept in custom/composer.installed.json, beside the custom composer.json: an
     * update package never ships it, so it is still there when the installer runs
     * after the files of another version were laid over the site.
     *
     * @param array<int, string> $packages Custom package names.
     * @return void
     */
    public function recordInstalled(array $packages): void
    {
        $lock = $this->lockPackages();
        $core = $this->coreRequirements();

        $record = [];
        foreach ($packages as $package) {
            $package = strtolower((string) $package);
            if (isset($core[$package]) || !isset($lock[$package])) {
                continue;
            }
            $closure = $this->dependencyClosure($lock, $package, $core);
            $version = (string) $closure[$package];
            unset($closure[$package]);
            ksort($closure);
            $record[$package] = ['version' => $version, 'dependencies' => $closure];
        }
        ksort($record);

        $file = $this->corePath . self::RECORD_FILE;
        $json = json_encode(['packages' => $record], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT) . "\n";
        if (!is_file($file) || file_get_contents($file) !== $json) {
            @file_put_contents($file, $json);
        }
    }

    /**
     * Versions noted by the last restore.
     *
     * @return array<string, array{version: string, dependencies: array<string, string>}>
     */
    public function recordedVersions(): array
    {
        $file = $this->corePath . self::RECORD_FILE;
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

        $record = [];
        foreach ((array) ($data['packages'] ?? []) as $package => $entry) {
            $package = strtolower((string) $package);
            if (!self::isPackageName($package) || !is_array($entry) || trim((string) ($entry['version'] ?? '')) === '') {
                continue;
            }

            $dependencies = [];
            foreach ((array) ($entry['dependencies'] ?? []) as $name => $version) {
                if (self::isPackageName((string) $name) && trim((string) $version) !== '') {
                    $dependencies[strtolower((string) $name)] = trim((string) $version);
                }
            }
            $record[$package] = ['version' => trim((string) $entry['version']), 'dependencies' => $dependencies];
        }

        return $record;
    }

    /**
     * Packages the Composer of core/vendor runs on: composer/composer and everything it requires.
     *
     * That Composer loads its classes from the vendor directory it rewrites. When an
     * update removes one of these packages, the next class it needs from it is gone
     * and Composer stops halfway, with the vendor directory half replaced.
     *
     * @return list<string>
     */
    public function composerRuntimePackages(): array
    {
        $requires = [];
        foreach ($this->installedPackages() as $package) {
            if (isset($package['name'])) {
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
     * Whether a Composer command runs the copy of Composer in core/vendor.
     *
     * Asked of the command itself, however it was chosen (COMPOSER_BINARY, the PATH,
     * a fallback): the executable is looked up on the PATH, links are resolved, and
     * a short wrapper script that starts the vendor copy counts as that copy.
     *
     * @param string $command Composer command safe for shell usage, e.g. "'php' '/site/core/vendor/bin/composer'".
     * @return bool
     */
    public function isBundledComposer(string $command): bool
    {
        $words = self::shellWords($command);
        if ($words === []) {
            return false;
        }

        $executable = $words[0];
        if (isset($words[1]) && preg_match('~^php[0-9.]*(?:\.exe)?$~i', basename(str_replace('\\', '/', $executable))) === 1) {
            $executable = $words[1];
        }

        $path = $this->resolveExecutable($executable);
        $vendor = realpath($this->corePath . 'vendor');
        if ($path === null || $vendor === false) {
            return false;
        }

        if (self::isWithin($path, $vendor)) {
            return true;
        }

        // A wrapper such as "exec php /site/core/vendor/bin/composer "$@"".
        if ((int) @filesize($path) > 0 && (int) @filesize($path) <= 8192) {
            $script = self::normalizePath((string) @file_get_contents($path));
            foreach (array_unique([self::normalizePath($vendor), self::normalizePath($this->corePath . 'vendor')]) as $vendorPath) {
                if (str_contains($script, $vendorPath . '/bin/composer') || str_contains($script, $vendorPath . '/composer/composer/bin/composer')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The PHP script of core/vendor a command starts by itself, such as vendor/bin/composer.
     *
     * An update package does not keep the executable bit, so once make:site laid the
     * new files over the site such a command fails with "Permission denied"; run
     * through PHP, the script needs none.
     *
     * @param string $command Composer command safe for shell usage.
     * @return string|null Absolute path of the script, null when the command is anything else.
     */
    public function bundledPhpScript(string $command): ?string
    {
        $words = self::shellWords($command);
        $vendor = realpath($this->corePath . 'vendor');
        $path = count($words) === 1 ? $this->resolveExecutable($words[0]) : null;
        if ($path === null || $vendor === false || !self::isWithin($path, $vendor)) {
            return null;
        }

        $head = (string) @file_get_contents($path, false, null, 0, 64);

        return preg_match('~^(?:#![^\n]*\bphp\b|<\?php)~', $head) === 1 ? $path : null;
    }

    /**
     * Packages an update would install, update or remove, read from Composer's output.
     *
     * @param string $output Output of "composer update --dry-run".
     * @return list<string>
     */
    public static function changedPackages(string $output): array
    {
        return array_keys(self::operations($output));
    }

    /**
     * Operations Composer reports, by package.
     *
     * An update reports two lists. "Lock file operations" is what the update changes;
     * "Package operations" brings core/vendor in line with the new lock, so it also
     * holds every difference the vendor directory already had from the lock (an update
     * package may ship one), which "composer install" applies anyway.
     *
     * @param string $output Output of "composer update" (dry run or not).
     * @param bool $lockOnly Only the lock file operations; the whole output when Composer printed no such list.
     * @return array<string, array{op: string, from: string, to: string}> op is install, change or remove.
     */
    public static function operations(string $output, bool $lockOnly = false): array
    {
        if ($lockOnly) {
            if (preg_match('~^Lock file operations:.*?(?=^(?:Installing dependencies from lock file|Package operations:|Writing lock file)|\z)~ms', $output, $lock) === 1) {
                $output = $lock[0];
            } elseif (stripos($output, 'Nothing to modify in lock file') !== false) {
                return [];
            }
        }

        preg_match_all(
            '~^\s*-\s*(Installing|Locking|Upgrading|Downgrading|Updating|Removing)\s+([a-z0-9][a-z0-9_.-]*/[a-z0-9][a-z0-9_.-]*)(?:\s*\(([^)]*)\))?~mi',
            $output,
            $matches,
            PREG_SET_ORDER
        );

        $operations = [];
        foreach ($matches as $match) {
            $name = strtolower($match[2]);
            $versions = array_map('trim', explode('=>', (string) ($match[3] ?? '')));
            $verb = strtolower($match[1]);
            if ($verb === 'installing' || $verb === 'locking') {
                $operations[$name] ??= ['op' => 'install', 'from' => '', 'to' => $versions[0]];
            } elseif ($verb === 'removing') {
                $operations[$name] = ['op' => 'remove', 'from' => $versions[0], 'to' => ''];
            } else {
                $operations[$name] = ['op' => 'change', 'from' => $versions[0], 'to' => (string) ($versions[1] ?? '')];
            }
        }

        return $operations;
    }

    /**
     * Operations beyond installing the packages at the versions the site ran.
     *
     * @param array<string, array{op: string, from: string, to: string}> $operations From operations().
     * @param array<string, string> $pins From pins().
     * @param array<int, string> $packages Custom package names.
     * @return list<string> One line per change, e.g. "illuminate/contracts v12.70.0 => v12.70.1".
     */
    public static function unexpectedChanges(array $operations, array $pins, array $packages): array
    {
        $custom = array_fill_keys(array_map('strtolower', $packages), true);
        $sameVersion = static fn (string $a, string $b): bool => strtolower(ltrim($a, 'vV')) === strtolower(ltrim($b, 'vV'));

        $changes = [];
        foreach ($operations as $name => $operation) {
            $pinned = $pins[$name] ?? null;
            if ($operation['op'] === 'change') {
                $changes[] = $name . ' ' . $operation['from'] . ' => ' . $operation['to'];
            } elseif ($operation['op'] === 'remove') {
                $changes[] = $name . ' ' . $operation['from'] . ' would be removed';
            } elseif ($pinned !== null && $pinned !== '' && !$sameVersion($pinned, $operation['to'])) {
                $changes[] = $name . ' ' . $pinned . ' => ' . $operation['to'];
            } elseif ($pinned === '' && isset($custom[$name])) {
                $changes[] = $name . ' would be installed at ' . $operation['to'] . ' (the version the site ran is not known)';
            }
        }

        return $changes;
    }

    public static function isPackageName(string $package): bool
    {
        return preg_match('~^[a-z0-9][a-z0-9_.-]*/[a-z0-9][a-z0-9_.-]*$~i', $package) === 1;
    }

    /**
     * Whether a version meets a constraint; true when either is missing or cannot be read.
     */
    private static function satisfies(string $version, string $constraint): bool
    {
        if ($version === '' || trim($constraint) === '' || !class_exists(\Composer\Semver\Semver::class)) {
            return true;
        }

        try {
            return \Composer\Semver\Semver::satisfies($version, $constraint);
        } catch (\Throwable $exception) {
            return true;
        }
    }

    /**
     * Packages of core/composer.lock with their versions and requirements.
     *
     * @return array<string, array{version: string, require: list<string>}>
     */
    private function lockPackages(): array
    {
        $lockFile = $this->corePath . 'composer.lock';
        $lock = is_file($lockFile) ? json_decode((string) file_get_contents($lockFile), true) : null;
        if (!is_array($lock)) {
            return [];
        }

        $packages = [];
        foreach (['packages', 'packages-dev'] as $section) {
            foreach ((array) ($lock[$section] ?? []) as $package) {
                $name = strtolower((string) ($package['name'] ?? ''));
                if ($name !== '') {
                    $packages[$name] = [
                        'version' => (string) ($package['version'] ?? ''),
                        'require' => array_map('strtolower', array_keys((array) ($package['require'] ?? []))),
                    ];
                }
            }
        }

        return $packages;
    }

    /**
     * Packages core/composer.json requires itself; they keep the core's version.
     *
     * @return array<string, true>
     */
    private function coreRequirements(): array
    {
        $composer = json_decode((string) @file_get_contents($this->corePath . 'composer.json'), true);
        $requirements = [];
        foreach ((array) ($composer['require'] ?? []) as $package => $constraint) {
            if (self::isPackageName((string) $package)) {
                $requirements[strtolower((string) $package)] = true;
            }
        }

        return $requirements;
    }

    /**
     * A package and what it requires in the lock, without the core's own requirements.
     *
     * @param array<string, array{version: string, require: list<string>}> $lock
     * @param string $root Package name.
     * @param array<string, true> $core
     * @return array<string, string> Package name => version.
     */
    private function dependencyClosure(array $lock, string $root, array $core): array
    {
        $versions = [];
        $queue = [$root];
        while ($queue !== []) {
            $name = array_shift($queue);
            if (isset($versions[$name]) || !isset($lock[$name]) || ($name !== $root && isset($core[$name]))) {
                continue;
            }
            $versions[$name] = $lock[$name]['version'];
            foreach ($lock[$name]['require'] as $dependency) {
                if (self::isPackageName($dependency)) {
                    $queue[] = $dependency;
                }
            }
        }

        return $versions;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function installedPackages(): array
    {
        $installed = json_decode((string) @file_get_contents($this->corePath . 'vendor/composer/installed.json'), true);

        return array_values(array_filter((array) ($installed['packages'] ?? $installed ?? []), 'is_array'));
    }

    /**
     * The file a command name or path runs, links resolved.
     *
     * @param string $executable Command name ("composer") or path, relative to the core directory or not.
     * @return string|null
     */
    private function resolveExecutable(string $executable): ?string
    {
        if ($executable === '') {
            return null;
        }

        if (str_contains($executable, '/') || str_contains($executable, '\\')) {
            // make:site runs its commands from the core directory, the installers from the site root.
            $isAbsolute = preg_match('~^(?:[a-z]:)?[/\\\\]~i', $executable) === 1;
            $candidates = $isAbsolute
                ? [$executable]
                : [$this->corePath . $executable, dirname($this->corePath) . '/' . $executable, $executable];
            foreach ($candidates as $candidate) {
                $path = realpath($candidate);
                if ($path !== false && is_file($path)) {
                    return $path;
                }
            }

            return null;
        }

        $extensions = [''];
        if (PHP_OS_FAMILY === 'Windows') {
            $extensions = array_merge($extensions, array_map('strtolower', explode(';', (string) (getenv('PATHEXT') ?: '.COM;.EXE;.BAT;.CMD'))));
        }
        foreach (explode(PATH_SEPARATOR, (string) getenv('PATH')) as $directory) {
            if (trim($directory) === '') {
                continue;
            }
            foreach ($extensions as $extension) {
                $path = realpath(rtrim($directory, '/\\') . '/' . $executable . $extension);
                if ($path !== false && is_file($path)) {
                    return $path;
                }
            }
        }

        return null;
    }

    /**
     * Split a command into its words the way the shell does for escapeshellarg() output.
     *
     * @return list<string>
     */
    private static function shellWords(string $command): array
    {
        $words = [];
        $word = '';
        $started = false;
        $quote = '';
        $escapes = PHP_OS_FAMILY !== 'Windows';
        $length = strlen($command);

        for ($i = 0; $i < $length; $i++) {
            $char = $command[$i];
            if ($quote !== '') {
                if ($char === $quote) {
                    $quote = '';
                } else {
                    $word .= $char;
                }
                continue;
            }
            if ($char === "'" || $char === '"') {
                $quote = $char;
                $started = true;
            } elseif ($char === '\\' && $escapes && $i + 1 < $length) {
                $word .= $command[++$i];
                $started = true;
            } elseif (ctype_space($char)) {
                if ($started) {
                    $words[] = $word;
                    $word = '';
                    $started = false;
                }
            } else {
                $word .= $char;
                $started = true;
            }
        }
        if ($started) {
            $words[] = $word;
        }

        return $words;
    }

    private static function normalizePath(string $path): string
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');

        return PHP_OS_FAMILY === 'Windows' ? strtolower($path) : $path;
    }

    private static function isWithin(string $path, string $directory): bool
    {
        return str_starts_with(self::normalizePath($path) . '/', self::normalizePath($directory) . '/');
    }

    /**
     * A path relative to a directory, for a target that may hold a glob.
     *
     * @return string|null Null when there is no relative way (another Windows drive).
     */
    private static function relativePath(string $fromDirectory, string $target): ?string
    {
        $split = static fn (string $path): array => array_values(array_filter(explode('/', str_replace('\\', '/', $path)), static fn ($part) => $part !== '' && $part !== '.'));
        $from = $split($fromDirectory);
        $to = [];
        foreach ($split($target) as $part) {
            if ($part === '..' && $to !== [] && end($to) !== '..') {
                array_pop($to);
            } else {
                $to[] = $part;
            }
        }

        $same = static fn (string $a, string $b): bool => PHP_OS_FAMILY === 'Windows' ? strcasecmp($a, $b) === 0 : $a === $b;
        if (PHP_OS_FAMILY === 'Windows' && ($from === [] || $to === [] || !$same($from[0], $to[0]))) {
            return null;
        }

        $common = 0;
        while (isset($from[$common], $to[$common]) && $same($from[$common], $to[$common])) {
            $common++;
        }

        return implode('/', array_merge(array_fill(0, count($from) - $common, '..'), array_slice($to, $common))) ?: '.';
    }

    private static function outputTail(string $output): string
    {
        return implode("\n", array_slice(preg_split('/\r\n|\r|\n/', trim($output)) ?: [], -8));
    }
}
