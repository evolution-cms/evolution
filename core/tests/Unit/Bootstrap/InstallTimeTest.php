<?php

/*
 * bootstrap.php looked for "core.install" next to core/ instead of core/.install, so EVO_INSTALL_TIME was always 0.
 */

function installTimeAtBoot(?string $marker, ?string $besideCore = null): int
{
    $site = sys_get_temp_dir() . '/evo-install-time-' . bin2hex(random_bytes(6));
    $core = $site . '/core';
    foreach (['functions', 'vendor', 'includes'] as $dir) {
        mkdir($core . '/' . $dir, 0777, true);
    }

    try {
        copy(dirname(__DIR__, 3) . '/bootstrap.php', $core . '/bootstrap.php');
        file_put_contents($core . '/functions/laravel.php', '<?php');
        file_put_contents($core . '/vendor/autoload.php', '<?php');
        // the first file bootstrap.php loads once the constant is there
        file_put_contents($core . '/includes/custom-define.inc.php', '<?php echo json_encode(EVO_INSTALL_TIME); exit;');
        if ($marker !== null) {
            file_put_contents($core . '/.install', $marker);
        }
        if ($besideCore !== null) {
            file_put_contents($site . '/core.install', $besideCore);
        }

        $output = evoRunPhp($core . '/bootstrap.php', [], $status);
        expect($status)->toBe(0, $output);

        return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    } finally {
        (new \Illuminate\Filesystem\Filesystem())->deleteDirectory($site);
    }
}

test('the install time is what the installer wrote to core/.install', function () {
    expect(installTimeAtBoot('1793613143'))->toBe(1793613143);
});

test('a file next to core/ is not the install marker', function () {
    expect(installTimeAtBoot(null, '1793613143'))->toBe(0);
});

test('a site without a marker, or with one that holds no time, has no install time', function () {
    expect(installTimeAtBoot(null))->toBe(0)
        ->and(installTimeAtBoot('installed'))->toBe(0);
});
