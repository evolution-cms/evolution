<?php

use EvolutionCMS\Services\ComposerVersionSynchronizer;

test('composer version synchronizer aligns package metadata and removes legacy self references', function () {
    $directory = sys_get_temp_dir() . '/evo-composer-version-' . bin2hex(random_bytes(6));
    $composerFile = $directory . '/composer.json';
    $versionFile = $directory . '/version.php';

    mkdir($directory, 0777, true);

    try {
        file_put_contents($composerFile, json_encode([
            'name' => 'legacy/evolution',
            'version' => '3.5.7',
            'replace' => [
                'evolution-cms/evolution' => 'self.version',
                'vendor/package' => '*',
            ],
            'conflict' => [
                'evolution-cms/evolution' => '*',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
        file_put_contents($versionFile, "<?php return ['version' => '3.5.8'];\n");

        $result = (new ComposerVersionSynchronizer())->synchronize($composerFile, $versionFile);
        $composer = json_decode((string) file_get_contents($composerFile), true);

        expect($result)->toBe([
            'version' => '3.5.8',
            'changed' => true,
        ]);
        expect($composer['name'])->toBe('evolution-cms/evolution');
        expect($composer['version'])->toBe('3.5.8');
        expect($composer['replace'])->toBe(['vendor/package' => '*']);
        expect($composer)->not->toHaveKey('conflict');

        $unchanged = (new ComposerVersionSynchronizer())->synchronize($composerFile, $versionFile);

        expect($unchanged)->toBe([
            'version' => '3.5.8',
            'changed' => false,
        ]);
    } finally {
        @unlink($composerFile);
        @unlink($versionFile);
        @rmdir($directory);
    }
});
