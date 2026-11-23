<?php

/* Runtime autoload must not load the separately installed Pest/Symfony toolchain. */

it('keeps test tools outside the CMS runtime autoloader', function () {
    $script = tempnam(sys_get_temp_dir(), 'evo-runtime-autoload-');
    $core = dirname(__DIR__, 2);
    file_put_contents($script, '<?php require ' . var_export($core . '/vendor/autoload.php', true) . ';'
        . ' echo json_encode(['
        . ' "pest" => class_exists(\\Pest\\TestSuite::class),'
        . ' "mockery" => class_exists(\\Mockery::class),'
        . ' "process" => \\Composer\\InstalledVersions::getVersion("symfony/process"),'
        . ' "process_file" => (new ReflectionClass(\\Symfony\\Component\\Process\\Process::class))->getFileName(),'
        . ' ]);');
    try {
        $output = evoRunPhp($script, [], $exitCode);
        expect($exitCode)->toBe(0);
        $runtime = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
        expect($runtime['pest'])->toBeFalse()
            ->and($runtime['mockery'])->toBeFalse()
            ->and($runtime['process'])->toStartWith('7.')
            ->and(str_replace('\\', '/', $runtime['process_file']))
            ->toStartWith(str_replace('\\', '/', $core . '/vendor/symfony/process/'));
    } finally {
        @unlink($script);
    }
});
