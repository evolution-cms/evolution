<?php

/*
|--------------------------------------------------------------------------
| The web installer repairs a stale core/vendor before the new core boots
|--------------------------------------------------------------------------
|
| The manager's updater lays a source archive over the site and keeps its
| core/vendor. With a classmap-authoritative autoloader the new core's classes
| are not found, and a package the new lock moved stays at its old version, so
| any step that boots the new application fails before a repair could run.
| install/index.php therefore asks installerDependenciesStale() first and runs
| the Composer repair in place of the step, loading nothing of the new core but
| its two Composer helpers, then resumes in a fresh request.
|
*/

$root = dirname(__DIR__, 4);

/** A core directory with a vendor/composer state and a core/src, all small. */
function staleVendorCore(array $locked, array $installed, bool $authoritative, array $classmap, array $srcClasses): string
{
    $core = sys_get_temp_dir() . '/evo-stale-vendor-' . getmypid() . '-' . uniqid() . '/';
    mkdir($core . 'vendor/composer', 0777, true);
    file_put_contents($core . 'vendor/autoload.php', "<?php\n");
    file_put_contents($core . 'composer.lock', json_encode(['packages' => $locked]));
    file_put_contents($core . 'vendor/composer/installed.json', json_encode(['packages' => $installed]));
    file_put_contents($core . 'vendor/composer/autoload_real.php', '<?php ' . ($authoritative ? '$loader->setClassMapAuthoritative(true);' : '') . "\n");
    file_put_contents($core . 'vendor/composer/autoload_classmap.php', '<?php return ' . var_export(array_fill_keys($classmap, 'x'), true) . ';');
    foreach ($srcClasses as $relative) {
        $file = $core . 'src/' . $relative . '.php';
        @mkdir(dirname($file), 0777, true);
        file_put_contents($file, "<?php\nnamespace EvolutionCMS;\n\nfinal class " . basename($relative) . "\n{\n}\n");
    }
    @mkdir($core . 'src/functions', 0777, true);
    file_put_contents($core . 'src/functions/helpers.php', "<?php\nfunction evo_helper() {}\n");

    return $core;
}

/** installerDependenciesStale() of a core directory, in its own process. */
function staleVendorCheck(string $root, string $core): ?string
{
    $probe = tempnam(sys_get_temp_dir(), 'evo_stale_vendor_');
    file_put_contents($probe, '<?php'
        . ' require ' . var_export($root . '/core/vendor/autoload.php', true) . ';'
        . ' require ' . var_export($root . '/install/src/functions.php', true) . ';'
        . ' echo json_encode(installerDependenciesStale(' . var_export($core, true) . '));');
    $out = trim(evoRunPhp($probe));
    unlink($probe);

    return json_decode($out, true, flags: JSON_THROW_ON_ERROR);
}

$sgallery = static fn (string $version): array => ['name' => 'seiger/sgallery', 'version' => $version];

test('a vendor that matches the lock and lists every class boots the new core', function () use ($root, $sgallery) {
    $core = staleVendorCore([$sgallery('v1.8.1')], [$sgallery('v1.8.1')], true,
        ['EvolutionCMS\\Core', 'EvolutionCMS\\Support\\OnDemandProviders'], ['Core', 'Support/OnDemandProviders']);

    try {
        expect(staleVendorCheck($root, $core))->toBeNull();
    } finally {
        (new Illuminate\Filesystem\Filesystem())->deleteDirectory($core);
    }
});

test('an authoritative classmap without a class of the new core is stale', function () use ($root, $sgallery) {
    $core = staleVendorCore([$sgallery('v1.8.1')], [$sgallery('v1.8.1')], true,
        ['EvolutionCMS\\Core'], ['Core', 'Support/OnDemandProviders']);

    try {
        expect(staleVendorCheck($root, $core))->toBe('The classmap of core/vendor does not list EvolutionCMS\\Support\\OnDemandProviders.');
    } finally {
        (new Illuminate\Filesystem\Filesystem())->deleteDirectory($core);
    }
});

test('a classmap that is not authoritative finds new classes itself', function () use ($root, $sgallery) {
    $core = staleVendorCore([$sgallery('v1.8.1')], [$sgallery('v1.8.1')], false,
        ['EvolutionCMS\\Core'], ['Core', 'Support/OnDemandProviders']);

    try {
        expect(staleVendorCheck($root, $core))->toBeNull();
    } finally {
        (new Illuminate\Filesystem\Filesystem())->deleteDirectory($core);
    }
});

test('a package at another version than the lock, or missing, is stale', function () use ($root, $sgallery) {
    $moved = staleVendorCore([$sgallery('v1.8.1')], [$sgallery('v1.7.0')], true, [], []);
    $missing = staleVendorCore([$sgallery('v1.8.1')], [], true, [], []);
    $branch = staleVendorCore(
        [['name' => 'acme/tool', 'version' => 'dev-main', 'source' => ['reference' => 'b2']]],
        [['name' => 'acme/tool', 'version' => 'dev-main', 'source' => ['reference' => 'a1']]],
        true, [], []
    );

    try {
        expect(staleVendorCheck($root, $moved))->toBe('core/vendor has seiger/sgallery v1.7.0, core/composer.lock wants v1.8.1.')
            ->and(staleVendorCheck($root, $missing))->toBe('core/vendor has seiger/sgallery (not installed), core/composer.lock wants v1.8.1.')
            ->and(staleVendorCheck($root, $branch))->toBe('core/vendor has acme/tool dev-main@a1, core/composer.lock wants dev-main@b2.');
    } finally {
        foreach ([$moved, $missing, $branch] as $core) {
            (new Illuminate\Filesystem\Filesystem())->deleteDirectory($core);
        }
    }
});

test('a site without core/vendor has to install it', function () use ($root) {
    $core = sys_get_temp_dir() . '/evo-stale-vendor-' . getmypid() . '-' . uniqid() . '/';
    mkdir($core, 0777, true);

    try {
        expect(staleVendorCheck($root, $core))->toBe('core/vendor is missing.');
    } finally {
        (new Illuminate\Filesystem\Filesystem())->deleteDirectory($core);
    }
});

test('the repair runs on an autoloader that cannot load any class of the new core', function () use ($root) {
    $core = sys_get_temp_dir() . '/evo-stale-repair-' . getmypid() . '-' . uniqid() . '/';
    mkdir($core . 'custom', 0777, true);
    file_put_contents($core . 'custom/composer.json', json_encode(['require' => ['php' => '^8.3']]));
    $probe = tempnam(sys_get_temp_dir(), 'evo_stale_repair_');
    // The vendor autoloader, then a loader in front of it that refuses the whole EvolutionCMS namespace:
    // what a stale authoritative classmap amounts to for classes the new core added or reshaped.
    file_put_contents($probe, '<?php'
        . ' define("EVO_CORE_PATH", ' . var_export($root . '/core/', true) . ');'
        . ' require ' . var_export($root . '/core/vendor/autoload.php', true) . ';'
        . ' spl_autoload_register(static function (string $class): void {'
        . '   if (str_starts_with($class, "EvolutionCMS\\\\")) { throw new RuntimeException("autoloaded $class"); }'
        . ' }, true, true);'
        . ' require ' . var_export($root . '/install/src/functions.php', true) . ';'
        . ' $commands = [];'
        . ' $run = static function (string $command) use (&$commands): array { $commands[] = $command; return [0, ""]; };'
        . ' $repair = installerRepairDependencies($run, ["command" => "composer", "standalone" => true]);'
        . ' echo json_encode(["repair" => $repair, "commands" => $commands]);');

    try {
        $out = json_decode(trim(evoRunPhp($probe)), true, flags: JSON_THROW_ON_ERROR);

        expect($out['repair']['customPackages']['restored'])->toBeTrue()
            ->and($out['repair']['composerDependencies']['installed'])->toBeTrue()
            ->and($out['commands'])->toHaveCount(2)
            ->and($out['commands'][0])->toStartWith('composer ' . EvolutionCMS\Services\CustomComposerPackages::INSTALL_ARGUMENTS)
            ->and($out['commands'][1])->toStartWith('composer ' . EvolutionCMS\Services\CustomComposerPackages::DUMP_AUTOLOAD_ARGUMENTS);
    } finally {
        unlink($probe);
        (new Illuminate\Filesystem\Filesystem())->deleteDirectory($core);
    }
});

test('every installer page asks about core/vendor before its step can boot the new core', function () use ($root) {
    $index = (string) file_get_contents($root . '/install/index.php');
    $controller = (string) file_get_contents($root . '/install/src/controllers/dependencies.php');
    $page = substr($index, strpos($index, "if (empty(\$_GET['s'])) {"));

    expect($page)->toContain('$vendorStale = installerDependenciesStale(EVO_CORE_PATH);')
        ->and(strpos($page, '$vendorStale = installerDependenciesStale(EVO_CORE_PATH);'))->toBeLessThan(strpos($page, 'require $controller;'))
        // Admission first: an installer shut to this browser runs nothing.
        ->and(strpos($index, 'if ($decision === InstallerCompletion::DENY) {'))->toBeLessThan(strpos($index, 'installerDependenciesStale('))
        ->and($controller)->toContain('$repair = installerRepairDependencies();')
        // A fresh request after the repair: this one still runs the previous autoloader.
        ->and($controller)->toContain("header('Location: index.php?action=' . rawurlencode(\$resume), true, 303);")
        ->and($controller)->toContain('installerDependenciesStale(EVO_CORE_PATH)');
});
