<?php

use EvolutionCMS\ManagerTheme;

/*
 * A manager session from before the last install or update ends. The check runs since EVO_INSTALL_TIME
 * is read at all (tests/Unit/Bootstrap/InstallTimeTest.php).
 */

const MGR_SESSION_CREATED_KEY = 'evo.session.created.time';

function managerSessionCheck(int $installTime): bool
{
    $theme = new class($installTime) extends ManagerTheme {
        public function __construct(private int $installTime)
        {
        }

        protected function getInstallTime(): int
        {
            return $this->installTime;
        }

        protected function getInstallProcFile(): string
        {
            return __DIR__ . '/no-such-file.inc.php';
        }
    };

    return $theme->isAuthManager();
}

function signedInSince(int $createdAt): array
{
    return ['mgrValidated' => 1, 'usertype' => 'manager', 'mgrRole' => 1, MGR_SESSION_CREATED_KEY => $createdAt];
}

beforeEach(function () {
    $this->globals = [$_SESSION ?? null, $_GET, $_SERVER['REQUEST_METHOD'] ?? null, $_COOKIE];
    $_GET = [];
    $_COOKIE = [];
    $_SERVER['REQUEST_METHOD'] = 'GET';
});

afterEach(function () {
    [$_SESSION, $_GET, $method, $_COOKIE] = $this->globals;
    unset($_SERVER['REQUEST_METHOD']);
    if ($method !== null) {
        $_SERVER['REQUEST_METHOD'] = $method;
    }
});

test('a session from before the install ends, without a cookie of the native session', function () {
    $_SESSION = signedInSince(1000);

    expect(managerSessionCheck(2000))->toBeFalse()
        ->and($_SESSION)->toBe([])
        ->and($_GET['installGoingOn'])->toBe(2);
});

test('a session from after the install, or of a site with no install time, stays', function (int $installTime) {
    $_SESSION = $session = signedInSince(2000);

    expect(managerSessionCheck($installTime))->toBeTrue()
        ->and($_SESSION)->toBe($session)
        ->and($_GET)->toBe([]);
})->with(['same second' => [2000], 'earlier' => [1999], 'unknown' => [0]]);

test('a form posted from an old session is not lost', function () {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SESSION = $session = signedInSince(1000);

    expect(managerSessionCheck(2000))->toBeTrue()
        ->and($_SESSION)->toBe($session);
});

test('a login in a guest session from before the install lasts', function (string $method, int $installTime) {
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SESSION = [MGR_SESSION_CREATED_KEY => 1000, 'veriword' => 'kept'];

    expect(managerSessionCheck($installTime))->toBeFalse()
        ->and($_SESSION['veriword'])->toBe('kept');

    // the login keeps what the guest's session held
    $_SESSION = ['mgrValidated' => 1, 'usertype' => 'manager'] + $_SESSION;
    $_SERVER['REQUEST_METHOD'] = 'GET';

    expect(managerSessionCheck($installTime))->toBeTrue();
})->with([
    'login page' => ['GET', 2000],
    'login form' => ['POST', 2000],
    'install clock ahead' => ['POST', time() + 3600],
]);

test('a guest session from after the install keeps its time', function () {
    $_SESSION = [MGR_SESSION_CREATED_KEY => 3000];

    expect(managerSessionCheck(2000))->toBeFalse()
        ->and($_SESSION)->toBe([MGR_SESSION_CREATED_KEY => 3000]);
});
