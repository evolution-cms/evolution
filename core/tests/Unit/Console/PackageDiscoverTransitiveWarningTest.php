<?php

test('package discovery warns about provider packages kept by transitive requirements', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Console/Packages/PackageCommand.php');

    expect($source)
        ->toContain('$rootRequiredPackages')
        ->toContain('$packageDependents')
        ->toContain('warnAboutTransitivePackage($package, $composerArray)')
        ->toContain('Package \' . $package . \' is still discovered because it is required by \' . $dependents . \'.')
        ->toContain('isset($this->rootRequiredPackages[$package])')
        ->toContain('hasLaravelDiscoveryMetadata');
});
