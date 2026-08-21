<?php

use EvolutionCMS\Exceptions\PasswordRecoveryRequiredException;
use EvolutionCMS\Exceptions\ServiceActionException;
use EvolutionCMS\Services\PasswordRecoveryService;

$root = dirname(__DIR__, 3);

/**
 * Lets a test set the `pwd_repair_minutes` setting without a booted CMS.
 */
class ConfigurableRecoveryService extends PasswordRecoveryService
{
    public $minutes = null;

    protected function configuredMinutes()
    {
        return $this->minutes;
    }
}

test('an unusable stored password starts recovery instead of failing the login forever', function () use ($root) {
    $processors = (string) file_get_contents($root . '/functions/processors.php');

    $guard = strpos($processors, 'if (!$hasher->isUsable($dbasePassword)) {');
    $recovery = strpos($processors, 'startPasswordRecovery($username);');
    $check = strpos($processors, 'if (!$hasher->CheckPassword($givenPassword, $dbasePassword)) {');

    // The unusable-format branch has to come first: CheckPassword() would answer
    // "false" for such a row, which reads as a wrong password and never recovers.
    expect($guard)->not->toBeFalse()
        ->and($recovery)->not->toBeFalse()
        ->and($check)->not->toBeFalse()
        ->and($guard)->toBeLessThan($check)
        ->and($recovery)->toBeLessThan($check);
});

test('a successful login upgrades a hash that no longer matches the settings', function () use ($root) {
    $processors = (string) file_get_contents($root . '/functions/processors.php');

    expect($processors)->toContain('if ($hasher->needsRehash($dbasePassword)) {')
        ->and($processors)->toContain('updateNewHash($username, $givenPassword);');
});

test('the recovery exception is reported by the existing login error handling', function () {
    // LogInOut::simpleLogin() and the user-manager services only catch
    // ServiceActionException; anything else would surface as a fatal error.
    expect(is_subclass_of(PasswordRecoveryRequiredException::class, ServiceActionException::class))->toBeTrue();
});

test('the recovery service exposes the whole token lifecycle', function () {
    expect(method_exists(PasswordRecoveryService::class, 'issueToken'))->toBeTrue()
        ->and(method_exists(PasswordRecoveryService::class, 'findByToken'))->toBeTrue()
        ->and(method_exists(PasswordRecoveryService::class, 'clearToken'))->toBeTrue()
        ->and(method_exists(PasswordRecoveryService::class, 'ttlSeconds'))->toBeTrue()
        ->and(method_exists(PasswordRecoveryService::class, 'startAutomaticRecovery'))->toBeTrue();
});

test('the link lifetime comes from the system settings, and 0 means never expires', function () {
    $service = new ConfigurableRecoveryService();

    $service->minutes = 60;
    expect($service->ttlSeconds())->toBe(3600);

    $service->minutes = 15;
    expect($service->ttlSeconds())->toBe(900);

    // The whole point of the setting: unlimited lifetime on request.
    $service->minutes = 0;
    expect($service->ttlSeconds())->toBe(0);

    $service->minutes = '0';
    expect($service->ttlSeconds())->toBe(0);
});

test('an unusable lifetime setting falls back to the default rather than to unlimited', function () {
    $service = new ConfigurableRecoveryService();

    // Never let a typo or an unreadable config silently produce eternal links.
    foreach ([null, '', 'soon', -5, [], false] as $broken) {
        $service->minutes = $broken;

        expect($service->ttlSeconds())->toBe(PasswordRecoveryService::DEFAULT_TTL_MINUTES * 60);
    }
});

test('the default lifetime is finite and short', function () {
    expect(PasswordRecoveryService::DEFAULT_TTL_MINUTES)->toBeGreaterThan(0)
        ->and(PasswordRecoveryService::DEFAULT_TTL_MINUTES)->toBeLessThan(60 * 24);
});

test('the lifetime setting ships with a factory default and a settings field', function () use ($root) {
    $factory = (string) file_get_contents($root . '/factory/settings.php');
    $view = (string) file_get_contents(dirname($root) . '/manager/views/page/system_settings/security.blade.php');

    expect($factory)->toContain("'pwd_repair_minutes' =>")
        ->and($view)->toContain("'name' => 'pwd_repair_minutes',")
        ->and($view)->toContain("__('global.pwd_repair_minutes_message')");
});

test('repeated login attempts on a broken row do not send repeated mails', function () use ($root) {
    $service = (string) file_get_contents($root . '/src/Services/PasswordRecoveryService.php');

    $start = strpos($service, 'public function startAutomaticRecovery');
    expect($start)->not->toBeFalse();

    $body = substr($service, $start, 900);

    expect($body)->toContain('if ($this->hasValidToken($user)) {')
        ->and($body)->toContain('return false;');
});

test('an expired recovery token is rejected and cleared before it can log anybody in', function () use ($root) {
    $controller = (string) file_get_contents($root . '/src/Controllers/Users/LogInOut.php');

    $lookup = strpos($controller, '$user = $recovery->findByToken($hash);');
    $reject = strpos($controller, "jsAlert(\\Lang::get('global.login_processor_hash_expired'));");
    $login = strpos($controller, '\\UserManager::hashLogin($_GET)');

    expect($lookup)->not->toBeFalse()
        ->and($reject)->not->toBeFalse()
        ->and($login)->not->toBeFalse()
        ->and($lookup)->toBeLessThan($login)
        ->and($reject)->toBeLessThan($login);
});

test('a token issued with an unlimited lifetime stores no deadline and never expires', function () use ($root) {
    $service = (string) file_get_contents($root . '/src/Services/PasswordRecoveryService.php');

    $issue = strpos($service, 'public function issueToken');
    $expired = strpos($service, 'protected function isExpired');

    expect($issue)->not->toBeFalse()
        ->and($expired)->not->toBeFalse()
        // 0 seconds of lifetime is stored as "no deadline"...
        ->and(substr($service, $issue, 900))
        ->toContain('$ttl > 0 ? date(\'Y-m-d H:i:s\', time() + $ttl) : null')
        // ...and "no deadline" is read back as "still valid".
        ->and(substr($service, $expired, 700))->toContain('if (empty($validTo)) {')
        ->and(substr($service, $expired, 700))->toContain('return false;');
});

test('the migration clears pre-existing tokens so an empty deadline is unambiguous', function () use ($root) {
    // Old tokens carry no deadline and would otherwise become eternal the moment
    // "no deadline" started to mean "never expires".
    $migration = (string) file_get_contents(
        $root . '/database/migrations/2026_08_20_000000_add_cachepwd_expiry_to_users.php'
    );

    expect($migration)->toContain("DB::table('users')->where('cachepwd', '<>', '')->update([")
        ->and($migration)->toContain("'cachepwd' => ''");
});

test('the recovery mail states the actual lifetime of the link it carries', function () use ($root) {
    $service = (string) file_get_contents($root . '/src/Services/PasswordRecoveryService.php');

    $start = strpos($service, 'protected function validityNotice');
    expect($start)->not->toBeFalse();

    $body = substr($service, $start, 700);

    // Read from the row, not from the current setting: changing the setting later
    // must not make an already delivered mail lie about its own link.
    expect($body)->toContain('$user->cachepwd_valid_to')
        ->and($body)->toContain("forgot_password_email_valid_unlimited")
        ->and($body)->toContain("forgot_password_email_valid_until")
        ->and($service)->toContain('$this->validityNotice($user)');
});

test('the users table carries the token expiry on every install path', function () use ($root) {
    $migration = $root . '/database/migrations/2026_08_20_000000_add_cachepwd_expiry_to_users.php';
    $mirrored = dirname($root) . '/install/stubs/migrations/2026_08_20_000000_add_cachepwd_expiry_to_users.php';
    $baseline = (string) file_get_contents($root . '/database/migrations/2025_12_25_000000_initial_schema.php');

    expect(is_file($migration))->toBeTrue()
        ->and(is_file($mirrored))->toBeTrue()
        ->and(file_get_contents($migration))->toBe(file_get_contents($mirrored))
        // DATETIME, not TIMESTAMP: a long but finite lifetime must not hit the 2038 limit.
        ->and($baseline)->toContain("dateTime('cachepwd_valid_to')")
        ->and(file_get_contents($migration))->toContain("dateTime('cachepwd_valid_to')");
});

test('the forgot-password form survives an unknown e-mail address', function () use ($root) {
    $theme = (string) file_get_contents($root . '/src/ManagerTheme.php');

    $start = strpos($theme, 'public function repairPassword');
    expect($start)->not->toBeFalse();

    $body = substr($theme, $start, 1600);

    // The previous version dereferenced $user right after finding it was null.
    expect($body)->toContain('if (is_null($attributes) || is_null($user)) {')
        ->and($body)->toContain("\\Lang::get('global.could_not_find_user')");
});

test('the recovery messages are translated in every shipped language', function () use ($root) {
    // A missing key would show the raw lexicon name to the user on the login screen.
    $missing = [];

    foreach ((array) glob($root . '/lang/*/global.php') as $file) {
        $_lang = [];
        include $file;

        $language = basename(dirname($file));

        $keys = [
            'login_processor_password_recovery',
            'login_processor_hash_expired',
            // The lifetime setting and what it does — including that 0 means unlimited.
            'pwd_repair_minutes_title',
            'pwd_repair_minutes_message',
            // What the recovery mail tells its recipient about the link's validity.
            'forgot_password_email_valid_until',
            'forgot_password_email_valid_unlimited',
        ];

        foreach ($keys as $key) {
            if (empty($_lang[$key])) {
                $missing[] = $language . '.' . $key;
            }
        }
    }

    expect($missing)->toBe([])
        // Guards the glob itself: an empty sweep would pass vacuously.
        ->and(count((array) glob($root . '/lang/*/global.php')))->toBeGreaterThan(20);
});
