<?php declare(strict_types = 1);

/**
 * Sync "replace" and "conflict" in composer.json with the version from factory/version.php.
 * - replace: evolutioncms/evolution => <version from version.php>
 * - conflict: evolutioncms/evolution => *  (forbid installing a vendor package; root provides it)
 *
 * This ensures:
 *  - Third-party packages may "require" evolutioncms/evolution with a semver range.
 *  - Host never installs/updates the real package; it either matches the provided version or fails fast.
 */

$root = dirname(__DIR__);
$composerFile = $root . '/composer.json';
$versionFile  = $root . '/factory/version.php';

if (!is_file($composerFile)) {
    fwrite(STDERR, "composer.json not found at $composerFile\n");
    exit(1);
}
if (!is_file($versionFile)) {
    fwrite(STDERR, "version.php not found at $versionFile\n");
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
$composer = json_decode($composerRaw, true);
if (!is_array($composer)) {
    fwrite(STDERR, "Failed to parse composer.json\n");
    exit(3);
}

$composer['replace']  = $composer['replace']  ?? [];
$composer['conflict'] = $composer['conflict'] ?? [];

// Provide exact version to satisfy "^x.y.z" in third-party packages, without installing vendor package
$composer['replace']['evolutioncms/evolution']  = $version;

// Forbid installing the vendor package at all (host provides it)
$composer['conflict']['evolutioncms/evolution'] = '*';

$new = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
if ($new !== $composerRaw) {
    file_put_contents($composerFile, $new);
    echo "Synced composer.json: evolutioncms/evolution {$version}\n";
} else {
    echo "Evolution CMS {$version}\n";
}
