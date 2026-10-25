<?php

/*
| The legacy session cookie is appended to "sess_" to find the old session file. PHP collapses
| "sess_/../" without the folder existing, so an unchecked cookie could read any file and import
| it into the visitor's session.
*/

function legacySessionRead(string $savePath, string $sessionId): ?string
{
    // Hex keeps a null byte intact on the command line.
    $out = evoRunPhp(dirname(__DIR__, 2) . '/Mocks/legacy_session_read_worker.php', [$savePath, bin2hex($sessionId)]);

    return json_decode($out, true, flags: JSON_THROW_ON_ERROR);
}

beforeEach(function () {
    $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'evo-legacy-session-' . bin2hex(random_bytes(4));
    $this->store = $this->root . DIRECTORY_SEPARATOR . 'store';
    mkdir($this->store, 0777, true);
    file_put_contents($this->root . DIRECTORY_SEPARATOR . 'planted.txt', 'mgrValidated|b:1;');
    file_put_contents($this->store . DIRECTORY_SEPARATOR . 'sess_abc123,DEF-456', 'legacy|s:2:"ok";');
});

afterEach(function () {
    @unlink($this->root . DIRECTORY_SEPARATOR . 'planted.txt');
    @unlink($this->store . DIRECTORY_SEPARATOR . 'sess_abc123,DEF-456');
    @rmdir($this->store);
    @rmdir($this->root);
});

it('reads a legacy session file named by a well-formed id', function () {
    expect(legacySessionRead($this->store, 'abc123,DEF-456'))->toBe('legacy|s:2:"ok";');
});

it('refuses ids that would leave the session directory', function (string $id) {
    expect(legacySessionRead($this->store, $id))->toBeNull();
})->with([
    'slash traversal' => ['/../../planted.txt'],
    'backslash traversal' => ['\\..\\..\\planted.txt'],
    'dot segment' => ['..'],
    'null byte' => ["abc\0"],
    'too long' => [str_repeat('a', 257)],
]);
