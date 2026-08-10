<?php

test('manager hash login rejects empty hashes before user lookup', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Controllers/Users/LogInOut.php');

    $emptyHashGuard = strpos($source, "\$hash === ''");
    $hashLogin = strpos($source, '\\UserManager::hashLogin($_GET)');

    expect($source)
        ->toContain("\$hash = trim((string)(\$_GET['hash'] ?? ''));")
        ->toContain("jsAlert(\\Lang::get('global.login_processor_unknown_user'))")
        ->toContain("\$_GET['hash'] = \$hash;")
        ->and($emptyHashGuard)->not->toBeFalse()
        ->and($hashLogin)->not->toBeFalse()
        ->and($emptyHashGuard)->toBeLessThan($hashLogin);
});
