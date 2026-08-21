<?php

use EvolutionCMS\Legacy\PasswordHash;

/**
 * Lets a test pick the algorithm without a booted CMS.
 */
class ConfigurablePasswordHash extends PasswordHash
{
    public string $algorithm = PasswordHash::ALGO_BCRYPT;

    protected function configuredAlgorithm(): string
    {
        return $this->algorithm;
    }
}

test('new hashes are password_hash() output, never phpass or md5', function () {
    $hasher = new ConfigurablePasswordHash();
    $hash = $hasher->HashPassword('correct horse battery staple');

    expect($hash)->not->toStartWith('$P$')
        ->and($hash)->not->toStartWith('$H$')
        ->and(password_get_info($hash)['algo'])->not->toBeNull()
        ->and($hasher->identify($hash))->toBe(PasswordHash::FORMAT_NATIVE)
        ->and($hasher->CheckPassword('correct horse battery staple', $hash))->toBeTrue()
        ->and($hasher->CheckPassword('wrong', $hash))->toBeFalse()
        ->and($hasher->needsRehash($hash))->toBeFalse();
});

test('bcrypt is the default and is stretched beyond the PHP 8.3 default cost', function () {
    $hasher = new ConfigurablePasswordHash();
    $hasher->algorithm = PasswordHash::ALGO_BCRYPT;

    $info = password_get_info($hasher->HashPassword('secret'));

    expect($info['algoName'])->toBe('bcrypt')
        ->and($info['options']['cost'])->toBe(PasswordHash::BCRYPT_COST)
        ->and(PasswordHash::BCRYPT_COST)->toBeGreaterThan(10);
});

test('argon2id is used when selected and supported', function () {
    if (!defined('PASSWORD_ARGON2ID')) {
        expect(true)->toBeTrue(); // libargon2 unavailable on this build

        return;
    }

    $hasher = new ConfigurablePasswordHash();
    $hasher->algorithm = PasswordHash::ALGO_ARGON2ID;

    $hash = $hasher->HashPassword('secret');

    expect(password_get_info($hash)['algoName'])->toBe('argon2id')
        ->and($hasher->CheckPassword('secret', $hash))->toBeTrue()
        ->and($hasher->needsRehash($hash))->toBeFalse();
});

test('pre-3.5 algorithm settings fall back to bcrypt instead of failing', function () {
    // Those names described the verify-only "v1" scheme; password_hash() has no
    // equivalent, and an unknown setting must never stop a password from being hashed.
    foreach (['BLOWFISH_Y', 'BLOWFISH_A', 'SHA512', 'SHA256', 'MD5', 'UNCRYPT', '0', ''] as $legacy) {
        $hasher = new ConfigurablePasswordHash();
        $hasher->algorithm = $legacy;

        $hash = $hasher->HashPassword('secret');

        expect(password_get_info($hash)['algoName'])->toBe('bcrypt')
            ->and($hasher->CheckPassword('secret', $hash))->toBeTrue();
    }
});

test('every hash format the CMS has ever written still verifies', function () {
    $hasher = new ConfigurablePasswordHash();

    $portable = $hasher->HashPasswordPortable('secret');
    $native = $hasher->HashPassword('secret');
    $md5 = md5('secret');

    expect($portable)->toStartWith('$P$')
        ->and($hasher->identify($portable))->toBe(PasswordHash::FORMAT_PORTABLE)
        ->and($hasher->CheckPassword('secret', $portable))->toBeTrue()
        ->and($hasher->CheckPassword('wrong', $portable))->toBeFalse()
        ->and($hasher->identify($md5))->toBe(PasswordHash::FORMAT_MD5)
        ->and($hasher->CheckPassword('secret', $md5))->toBeTrue()
        ->and($hasher->CheckPassword('wrong', $md5))->toBeFalse()
        ->and($hasher->CheckPassword('secret', $native))->toBeTrue();
});

test('a "v1" hash is recognised but left to loginV1, which owns the salt seed', function () {
    $hasher = new ConfigurablePasswordHash();
    $v1 = 'uncrypt>' . md5('whatever') . substr(md5('salt'), 0, 8);

    expect($hasher->identify($v1))->toBe(PasswordHash::FORMAT_V1)
        ->and($hasher->isUsable($v1))->toBeTrue()
        ->and($hasher->needsRehash($v1))->toBeTrue()
        ->and($hasher->CheckPassword('whatever', $v1))->toBeFalse();
});

test('a plaintext password column can never authenticate anybody', function () {
    $hasher = new ConfigurablePasswordHash();

    // The exact failure this guards: password === stored value.
    expect($hasher->CheckPassword('hunter2', 'hunter2'))->toBeFalse()
        ->and($hasher->identify('hunter2'))->toBe(PasswordHash::FORMAT_UNKNOWN)
        ->and($hasher->isUsable('hunter2'))->toBeFalse();
});

test('unusable stored values are reported as such instead of silently failing', function () {
    $hasher = new ConfigurablePasswordHash();

    foreach (['', '*', 'hunter2', 'abcdef', str_repeat('z', 32)] as $broken) {
        expect($hasher->identify($broken))->toBe(PasswordHash::FORMAT_UNKNOWN)
            ->and($hasher->isUsable($broken))->toBeFalse()
            ->and($hasher->CheckPassword($broken, $broken))->toBeFalse()
            // Nothing to upgrade: recovery is the only way out of an unusable row.
            ->and($hasher->needsRehash($broken))->toBeFalse();
    }
});

test('legacy formats are always scheduled for rehash on the next successful login', function () {
    $hasher = new ConfigurablePasswordHash();

    expect($hasher->needsRehash($hasher->HashPasswordPortable('secret')))->toBeTrue()
        ->and($hasher->needsRehash(md5('secret')))->toBeTrue()
        // bcrypt below the configured cost must be restretched too
        ->and($hasher->needsRehash(password_hash('secret', PASSWORD_BCRYPT, ['cost' => 4])))->toBeTrue();
});

test('changing the configured algorithm schedules a rehash of current hashes', function () {
    if (!defined('PASSWORD_ARGON2ID')) {
        expect(true)->toBeTrue();

        return;
    }

    $hasher = new ConfigurablePasswordHash();
    $bcrypt = $hasher->HashPassword('secret');

    $hasher->algorithm = PasswordHash::ALGO_ARGON2ID;

    expect($hasher->needsRehash($bcrypt))->toBeTrue()
        ->and($hasher->CheckPassword('secret', $bcrypt))->toBeTrue();
});

test('hashing never returns a usable-looking value on failure', function () {
    $hasher = new ConfigurablePasswordHash();

    // Over the 4096 byte guard.
    $hash = $hasher->HashPassword(str_repeat('a', 5000));

    expect($hash)->toBe('*')
        ->and($hasher->isUsable($hash))->toBeFalse()
        ->and($hasher->CheckPassword(str_repeat('a', 5000), $hash))->toBeFalse();
});
