<?php

use EvolutionCMS\Auth\PipelineUserManager;
use EvolutionCMS\Auth\TwoFactor;
use EvolutionCMS\Auth\TwoFactorMethod;
use EvolutionCMS\Controllers\Users\TwoFactor as TwoFactorController;

/*
|--------------------------------------------------------------------------
| Two-factor authentication
|--------------------------------------------------------------------------
|
| The service runs in a process of its own against an in-memory database
| (Fixtures/two-factor-harness.php); these tests read what it saw.
|
*/

/** @return array<string, mixed> */
function twoFactorRun(): array
{
    static $result = null;

    if ($result === null) {
        $output = evoRunPhp(dirname(__DIR__, 2) . '/Fixtures/two-factor-harness.php', [], $status, false);
        expect($status)->toBe(0, $output);
        $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    }

    return $result;
}

function twoFactorSource(string $path): string
{
    return (string) file_get_contents(dirname(__DIR__, 4) . '/' . $path);
}

const TWO_FACTOR_ASKED = 'global.two_factor_required#2';
const TWO_FACTOR_WRONG = 'global.two_factor_wrong_code#0';
const TWO_FACTOR_EXPIRED = 'global.two_factor_expired#0';
const TWO_FACTOR_BLOCKED = 'global.login_processor_blocked2#0';

test('the gate and the policy behave as documented', function (string $check, mixed $expected) {
    expect(twoFactorRun()[$check])->toBe($expected);
})->with(static function () {
    $checks = [
        // an update puts the code in place before its migrations
        'no table: gate' => '',
        'migration: event once' => 1,
        'off: gate' => '',
        'options' => ['off', 'email', 'totp'],
        'site' => 'totp',
        'override off' => ['off', ''],
        'override' => 'email',
        'own choice under a policy' => ['totp', false],
        'own choice' => ['email', true],
        'override beats own choice' => ['off', false],
        'settings file wins' => 'off',
        'user configuration does not count' => 'totp',
        'rfc 6238' => ['287082', '081804', '005924', '353130'],
        'totp: asked' => TWO_FACTOR_ASKED,
        'totp: secret' => [1, 1, 1, []],
        'totp: uri' => 'otpauth://totp/Site%20%20one:admin?secret=ABC&issuer=Site%20%20one&algorithm=SHA1&digits=6&period=30',
        'totp: setup form has a QR code' => true,
        'totp: no code yet' => [TWO_FACTOR_WRONG, false],
        'totp: set up' => [true, 10, [['id' => 1, 'rememberme' => 1, 'context' => 'mgr']], null],
        'codes are stored as hashes' => true,
        'totp: replay' => TWO_FACTOR_WRONG,
        'totp: next' => ['', 1],
        'inline wrong' => TWO_FACTOR_WRONG,
        'inline array' => TWO_FACTOR_ASKED,
        'inline recovery' => ['', 9],
        'recovery once' => TWO_FACTOR_WRONG,
        'recovery upper case, no dashes' => '',
        'recovery of somebody else' => TWO_FACTOR_WRONG,
        'payload cannot pass' => TWO_FACTOR_ASKED,
        'trusted' => ['', true],
        'trusted after an exception' => ['x#0', true],
        'console' => '',
        'nothing waits' => TWO_FACTOR_EXPIRED,
        'the other context' => TWO_FACTOR_EXPIRED,
        'expired' => [TWO_FACTOR_EXPIRED, 8],
        'method switched meanwhile' => TWO_FACTOR_EXPIRED,
        'late set-up' => [TWO_FACTOR_EXPIRED, true],
        // the third wrong answer locks, and then a right one does not help either
        'lock' => [TWO_FACTOR_WRONG, TWO_FACTOR_WRONG, TWO_FACTOR_WRONG, TWO_FACTOR_BLOCKED, 10],
        'lock over' => [TWO_FACTOR_WRONG, 1, ''],
        'blocked account' => TWO_FACTOR_BLOCKED,
        'email: asked' => [TWO_FACTOR_ASKED, 1, 'editor@site.test'],
        'email: one mail a minute' => 1,
        'email: code not in the subject' => true,
        'email: code not stored' => true,
        'email: masked' => true,
        'email: accepted' => ['', true],
        'email: expired' => TWO_FACTOR_WRONG,
        'email: no address, no way in' => ['global.two_factor_email_failed#0', null],
        'email: no address, recovery code' => [TWO_FACTOR_ASKED, ''],
        'stateless: e-mail' => [TWO_FACTOR_ASKED, '', false],
        'stateless: set-up needs a browser' => 'global.two_factor_setup_required#2',
        'plugin: in the list' => 'PIN',
        'plugin: asked' => [TWO_FACTOR_ASKED, TWO_FACTOR_WRONG, '', true],
        'plugin: gone' => ['email', true],
        'plugin: bad keys' => array_fill(0, 7, 'refused'),
        'own: open while nothing is set up' => false,
        "own: not somebody else's" => null,
        'own: set up' => ['totp', 10, false],
        'own: locked in another session' => [true, null],
        'own: unlock' => [TWO_FACTOR_WRONG, '', false],
        'own: recovery code proves nothing' => [TWO_FACTOR_WRONG, false],
        'reset' => ['off', 0, false],
        'encrypted' => [true, false, 'totp'],
        'key lost fails closed' => 'global.two_factor_key_lost#0',
        'return' => ['/sub/shop?page=2', '/sub/', '/sub/', '/sub/auth?x=1', '/sub/'],
        'purge' => 0,
    ];

    foreach ($checks as $check => $expected) {
        yield $check => [$check, $expected];
    }
});

test('a QR code is drawn as an independent decoder read it', function () {
    // zxing read codes of all 40 versions when this was written; the hash is of one of them
    $qr = require dirname(__DIR__, 3) . '/includes/two_factor/qr.php';

    expect(sha1($qr('otpauth://totp/Demo:admin?secret=JBSWY3DPEHPK3PXP&issuer=Demo')))->toBe('4db7a1bccc4c95206a4001cf8cb6c6963c733edc')
        ->and(fn () => $qr(str_repeat('x', 2332)))->toThrow(LengthException::class);
});

test('every way into a session passes the gate', function () {
    $source = twoFactorSource('core/src/Auth/PipelineUserManager.php');

    // each login class calls the gate before the package writes the session
    expect(preg_match_all('/public function authProcess\(\)\s*\{\s*PipelineUserManager::secondFactor\(/', $source))->toBe(2)
        ->and(substr_count($source, 'parent::authProcess();'))->toBe(2)
        ->and($source)->not->toContain('parent::login')
        ->and($source)->not->toContain('parent::hashLogin');

    foreach (['login', 'loginById', 'hashLogin', 'loginByIdWithoutPipeline'] as $method) {
        expect((new ReflectionMethod(PipelineUserManager::class, $method))->class)->toBe(PipelineUserManager::class);
    }
    // an anonymous class of the package's UserHashLogin cannot even be declared
    expect($source)->not->toContain('extends UserHashLogin');
});

test('the second step keeps the login template around its form', function (string $path) {
    $page = TwoFactorController::replaceLoginForm(twoFactorSource($path), '<b>$1 second \\0 step</b>');

    expect($page)->toContain('<b>$1 second \\0 step</b>')
        ->and($page)->toContain('id="logo"')
        ->and($page)->toContain('</body>')
        ->and($page)->not->toContain('name="password"')
        ->and($page)->not->toContain('document.loginfrm');
})->with(['manager/media/style/default/login.tpl', 'manager/media/style/common/login.tpl']);

test('a login template without the form gets the stand-alone page', function () {
    expect(TwoFactorController::replaceLoginForm('<form id="other"></form>', 'x'))->toBeNull()
        ->and(TwoFactorController::replaceLoginForm("<form class='c' name='loginfrm'>a</form>", 'x'))->toBe('<div class="">x</div>');
});

test('the setting cannot be turned on before whoever saves it has passed the method', function () {
    $source = twoFactorSource('manager/processors/save_settings.processor.php');

    expect($source)->toContain('$secondFactor->isConfirmed((int) $modx->getLoginUserID(\'mgr\'), $v)')
        ->and($source)->toContain('instanceof \\EvolutionCMS\\Auth\\PipelineUserManager')
        ->and(EvolutionCMS\Support\SecuritySettings::isGated(TwoFactor::SETTING))->toBeTrue();
});

test('the per-user fields are no user settings and need security_settings', function () {
    $source = twoFactorSource('core/src/Controllers/Users/EditOrNewUser.php');
    $taken = strpos($source, "array_diff_key(\$userData, \$secondFactor)");

    expect($taken)->toBeLessThan(strpos($source, '\UserManager::saveSettings($userData)'))
        ->and(substr($source, $taken, 200))->toContain("hasPermission('security_settings')");
});

test('two-factor authentication adds three classes to the autoloader', function () {
    $root = dirname(__DIR__, 4);
    $methods = ['email', 'totp'];
    foreach ($methods as $method) {
        expect(twoFactorSource('core/includes/two_factor/' . $method . '.php'))->toContain('return new class extends TwoFactorMethod');
    }

    expect(twoFactorSource('core/database/migrations/2026_11_24_000000_create_user_two_factor_table.php'))->toContain('return new class')
        ->and(glob($root . '/core/src/Auth/TwoFactor*.php'))->toHaveCount(2)
        ->and((new ReflectionClass(TwoFactorMethod::class))->isAbstract())->toBeTrue();
});

test('a frontend request does not load the two-factor classes', function () {
    // the query parameter is spelled out there for that reason
    expect(TwoFactor::QUERY)->toBe('evo_two_factor')
        ->and(twoFactorSource('core/src/Core.php'))->toContain("isset(\$_GET['evo_two_factor'])")
        ->and(twoFactorSource('core/src/Core.php'))->not->toContain('TwoFactor::')
        ->and(twoFactorSource('core/src/Providers/RoutingServiceProvider.php'))->toContain("isset(\$_GET['evo_two_factor'])");
});

test('both event seeders carry the event', function () {
    foreach (['core/database/seeders', 'install/stubs/seeds/install'] as $dir) {
        expect(twoFactorSource($dir . '/SystemEventnamesTableSeeder.php'))->toContain("'OnTwoFactorMethods'");
    }
});
