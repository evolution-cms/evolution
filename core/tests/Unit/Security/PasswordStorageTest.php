<?php

/**
 * Guards the one invariant that matters for the password column: whatever reaches it
 * came out of the hasher. Not a style check — every regression here is a database full
 * of readable or md5 passwords.
 */

$root = dirname(__DIR__, 3);

$scanRoots = [
    $root . '/src',
    $root . '/functions',
    $root . '/database',
    $root . '/vendor/evolutioncms-services/user-manager/src',
];

$phpFiles = static function (array $dirs): array {
    $files = [];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = str_replace('\\', '/', $file->getPathname());
            }
        }
    }
    sort($files);

    return $files;
};

$hashesInline = static function (string $line): bool {
    return str_contains($line, 'HashPassword(') || str_contains($line, 'password_hash(');
};

test('every write to a user password attribute stores a hash', function () use ($phpFiles, $scanRoots, $hashesInline) {
    $unhashed = [];

    foreach ($phpFiles($scanRoots) as $file) {
        $source = (string) file_get_contents($file);
        $lines = explode("\n", $source);

        foreach ($lines as $number => $line) {
            // Eloquent attribute write: $user->password = ...
            if (preg_match('/\$\w+(?:->\w+)*->password\s*=\s*([^=].*?);/', $line, $matches) !== 1) {
                continue;
            }

            $value = trim($matches[1]);

            if ($hashesInline($value)) {
                continue;
            }

            // Assigned from a local variable — follow it back to its own assignment.
            if (preg_match('/^(?:\(string\)\s*)?(\$\w+)$/', $value, $variable) === 1) {
                $pattern = '/' . preg_quote($variable[1], '/') . '\s*=\s*[^=].*(?:HashPassword\(|password_hash\()/';
                if (preg_match($pattern, $source) === 1) {
                    continue;
                }
            }

            $unhashed[] = basename($file) . ':' . ($number + 1) . ' — ' . trim($line);
        }
    }

    expect($unhashed)->toBe([]);
});

test('no source stores md5 as a password', function () use ($phpFiles, $scanRoots) {
    $offenders = [];

    foreach ($phpFiles($scanRoots) as $file) {
        foreach (explode("\n", (string) file_get_contents($file)) as $number => $line) {
            if (preg_match('/(?:->password|\[.password.\]|.password.\s*=>)\s*=?>?\s*(?:\(string\)\s*)?md5\(/', $line) === 1) {
                $offenders[] = basename($file) . ':' . ($number + 1) . ' — ' . trim($line);
            }
        }
    }

    expect($offenders)->toBe([]);
});

test('the admin seeder hashes the initial password', function () use ($root) {
    $seeder = (string) file_get_contents($root . '/database/seeders/AdminUserTableSeeder.php');

    expect($seeder)->toContain("'password' => evolutionCMS()->getPasswordHash()->HashPassword(\$password),")
        ->and($seeder)->not->toContain('md5($password)');
});

test('updateNewHash refuses to persist a failed hash over a working one', function () use ($root) {
    $processors = (string) file_get_contents($root . '/functions/processors.php');

    $guard = strpos($processors, "\$hash === '*'");
    $write = strpos($processors, "\$field['password'] = \$hash;");

    expect($guard)->not->toBeFalse()
        ->and($write)->not->toBeFalse()
        ->and($guard)->toBeLessThan($write);
});

test('changeWebUserPassword verifies against the stored format and stores a hash', function () use ($root) {
    $core = (string) file_get_contents($root . '/src/Core.php');

    $start = strpos($core, 'public function changeWebUserPassword');
    expect($start)->not->toBeFalse();

    $body = substr($core, $start, 2600);

    expect($body)->toContain('$this->getPasswordHash()->HashPassword($newPwd)')
        ->and($body)->toContain('$this->getManagerApi()->getHashType(')
        // The old implementation compared the stored value against md5($oldPwd) whatever
        // its real format was, and then wrote $newPwd verbatim.
        ->and($body)->not->toContain("'password' => \$newPwd")
        ->and($body)->not->toContain("!== md5(\$oldPwd)")
        // md5 may still appear — but only as one branch, chosen by the stored format.
        ->and($body)->toContain("if (\$hashType === 'md5') {");
});

test('the generic credential comparison cannot be satisfied by a password attribute', function () use ($root) {
    $auth = (string) file_get_contents($root . '/src/Services/AuthServices.php');

    // Auth::attempt() unsets any checked field whose model attribute equals the given
    // value. Applied to the password that is a plaintext-row backdoor.
    expect($auth)->toContain("if (\$key !== 'password') {")
        ->and($auth)->toContain('login($this->user->username, $value, $this->user->password)');
});
