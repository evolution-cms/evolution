<?php

use EvolutionCMS\Support\FileManagerAccess;

/*
|--------------------------------------------------------------------------
| A per-user filemanager_path may only narrow the system root
|--------------------------------------------------------------------------
|
| filemanager_path is an ordinary user setting, merged over the site-wide value
| in Core::getUserSettings(). The classic file manager then browses, reads and
| writes under getConfig('filemanager_path') - the merged value - while it keys
| its group ACL on the site-wide root (configGlobal). So a per-user override that
| points outside the system root widens the file manager past the intended root.
| userRootAllowed() is the decision Core uses to drop such an override; it accepts
| only a user root that resolves and lies within the system root.
|
*/

test('a per-user root within the system root is allowed', function () {
    expect(FileManagerAccess::userRootAllowed('/var/www/site/assets', '/var/www/site/assets/alice'))->toBeTrue()
        ->and(FileManagerAccess::userRootAllowed('/var/www/site/assets', '/var/www/site/assets'))->toBeTrue();
});

test('a per-user root outside the system root is refused', function () {
    expect(FileManagerAccess::userRootAllowed('/var/www/site/assets', '/var/www/site'))->toBeFalse()
        ->and(FileManagerAccess::userRootAllowed('/var/www/site/assets', '/etc'))->toBeFalse()
        // the sibling-prefix trap: assets-private is not under assets
        ->and(FileManagerAccess::userRootAllowed('/var/www/site/assets', '/var/www/site/assets-private'))->toBeFalse();
});

test('an unresolved root on either side is refused', function () {
    expect(FileManagerAccess::userRootAllowed(false, '/var/www/site/assets/alice'))->toBeFalse()
        ->and(FileManagerAccess::userRootAllowed('/var/www/site/assets', false))->toBeFalse()
        ->and(FileManagerAccess::userRootAllowed(false, false))->toBeFalse();
});

it('drops a per-user filemanager_path that escapes the system root in getUserSettings', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Core.php');

    $start = strpos($source, 'public function getUserSettings');
    expect($start)->not->toBeFalse();
    $body = substr($source, $start, strpos($source, 'return $usrSettings;', $start) - $start);

    expect($body)->toContain('Support\FileManagerAccess::userRootAllowed(')
        ->and($body)->toContain("configGlobal['filemanager_path']")
        ->and($body)->toContain("\$this->setConfig('filemanager_path', \$systemPath);");
});
