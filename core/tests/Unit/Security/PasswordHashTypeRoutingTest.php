<?php

use EvolutionCMS\Legacy\ManagerApi;

/**
 * getHashType() decides which verifier a stored password is handed to. A value outside
 * the md5|v1|phpass set means "no verifier at all", i.e. a locked-out account, so the
 * routing is pinned down here.
 */
test('each stored format is routed to the verifier that understands it', function () {
    $api = new ManagerApi();

    expect($api->getHashType(md5('secret')))->toBe('md5')
        ->and($api->getHashType('uncrypt>' . md5('x') . '12345678'))->toBe('v1')
        ->and($api->getHashType('blowfish_y>' . md5('x') . '12345678'))->toBe('v1')
        ->and($api->getHashType('$P$Bp.7VsURZ7.kdfjhsdkfjhsdkfjhsd'))->toBe('phpass')
        ->and($api->getHashType('$2y$12$abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUV'))->toBe('phpass')
        ->and($api->getHashType('$argon2id$v=19$m=65536,t=4,p=1$c29tZXNhbHQ$hash'))->toBe('phpass');
});

test('unusable stored values are routed to login(), which starts recovery', function () {
    $api = new ManagerApi();

    // Not md5 and not v1 — so they reach login(), where PasswordHash::isUsable()
    // detects them and password recovery is started instead of a wrong-password reply.
    expect($api->getHashType(''))->toBe('phpass')
        ->and($api->getHashType('hunter2'))->toBe('phpass')
        ->and($api->getHashType('*'))->toBe('phpass');
});

test('a 32 character password is not mistaken for an md5 hash', function () {
    $api = new ManagerApi();

    // Exactly 32 characters but not hexadecimal: this is what a plaintext row written
    // by the old changeWebUserPassword() looked like. Treating it as md5 would compare
    // it against md5(input) forever; it must go down the recovery path instead.
    $plaintext = str_repeat('zq', 16);

    expect(strlen($plaintext))->toBe(32)
        ->and($api->getHashType($plaintext))->toBe('phpass');
});

test('the new algorithms are offered only when PHP can produce them', function () {
    $api = new ManagerApi();

    expect($api->checkHashAlgorithm('BCRYPT'))->toBeTrue()
        ->and($api->checkHashAlgorithm('ARGON2ID'))->toBe(defined('PASSWORD_ARGON2ID'))
        ->and($api->checkHashAlgorithm('ARGON2I'))->toBe(defined('PASSWORD_ARGON2I'))
        ->and($api->checkHashAlgorithm(''))->toBeFalse();
});
