<?php declare(strict_types=1);

/**
 * Sync root composer.json "version" with the version from factory/version.php.
 *
 * Why:
 * - In production installs, the original repository metadata (git tags) may be absent.
 * - Composer needs a reliable root package version to validate constraints from third-party packages.
 * - Root package must NOT "replace" or "conflict" with itself (it *is* evolution-cms/evolution).
 *
 * Behavior:
 * - Reads version from factory/version.php (expects ['version' => 'x.y.z[...']).
 * - Ensures composer.json has "name": "evolution-cms/evolution".
 * - Updates composer.json "version" to match factory/version.php.
 * - Removes legacy self-referential "replace"/"conflict" entries for evolution-cms/evolution.
 * - Prints "Synced ..." when it changed composer.json, otherwise prints "Evolution CMS <version>".
 */

$root = dirname(__DIR__);
$composerFile = $root . '/composer.json';
$versionFile  = $root . '/factory/version.php';

if (!is_file($composerFile)) {
    fwrite(STDERR, "composer.json not found at {$composerFile}\n");
    exit(1);
}
if (!is_file($versionFile)) {
    fwrite(STDERR, "version.php not found at {$versionFile}\n");
    exit(1);
}

/** @var array{version?:string} $info */
$info = include $versionFile;
$version = $info['version'] ?? null;

if (!is_string($version) || !preg_match('/^\d+\.\d+\.\d+(?:[-+][A-Za-z0-9\.-]+)?$/', $version)) {
    fwrite(STDERR, "Invalid or missing version in version.php: " . var_export($version, true) . "\n");
    exit(2);
}

$composerRaw = file_get_contents($composerFile);
if ($composerRaw === false) {
    fwrite(STDERR, "Failed to read composer.json\n");
    exit(3);
}

$composer = json_decode($composerRaw, true);
if (!is_array($composer)) {
    fwrite(STDERR, "Failed to parse composer.json\n");
    exit(3);
}

$changed = false;

// Ensure the root package identity is correct.
if (($composer['name'] ?? null) !== 'evolution-cms/evolution') {
    $composer['name'] = 'evolution-cms/evolution';
    $changed = true;
}

// Ensure Composer sees the correct root version (prevents accidental "upgrade" attempts).
if (($composer['version'] ?? null) !== $version) {
    $composer['version'] = $version;
    $changed = true;
}

/**
 * Legacy cleanup:
 * Old core composer.json used "replace" + "conflict" for evolution-cms/evolution.
 * Now that root "name" is evolution-cms/evolution, those entries are self-referential and invalid.
 */
foreach (['replace', 'conflict'] as $section) {
    if (!isset($composer[$section]) || !is_array($composer[$section])) {
        continue;
    }

    if (array_key_exists('evolution-cms/evolution', $composer[$section])) {
        unset($composer[$section]['evolution-cms/evolution']);
        $changed = true;
    }

    // Remove empty sections to keep composer.json clean.
    if ($composer[$section] === []) {
        unset($composer[$section]);
        $changed = true;
    }
}

// Keep output stable (pretty print, no escaped slashes).
$new = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;

if ($changed && $new !== $composerRaw) {
    file_put_contents($composerFile, $new);
    echo "Synced composer.json: evolution-cms/evolution {$version}\n";
} else {
    echo "Evolution CMS {$version}\n";
}
