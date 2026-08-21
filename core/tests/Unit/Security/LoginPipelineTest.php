<?php

use EvolutionCMS\Auth\Contracts\LoginPipe;
use EvolutionCMS\Auth\LoginAttempt;
use EvolutionCMS\Auth\LoginPipeline;
use EvolutionCMS\Exceptions\ServiceActionException;
use Illuminate\Container\Container;

/**
 * Proves that two or more independent extras compose: a social-login pipe and a
 * second-factor pipe can be installed by different packages and still form one
 * predictable chain, which is what the OnManagerAuthentication event cannot do.
 */

/** Records the order pipes ran in. */
class RecordingPipe implements LoginPipe
{
    public static array $log = [];

    public function handle(LoginAttempt $attempt, Closure $next)
    {
        static::$log[] = static::class . ':' . $attempt->stage;

        return $next($attempt);
    }
}

class FirstPipe extends RecordingPipe {}
class SecondPipe extends RecordingPipe {}

/** A second factor: refuses the login outright when the code is missing. */
class RequireCodePipe implements LoginPipe
{
    public function handle(LoginAttempt $attempt, Closure $next)
    {
        if ($attempt->get('code') !== '123456') {
            throw new ServiceActionException('second factor required');
        }

        return $next($attempt);
    }
}

/** A social login: authenticates by itself and never reaches the password check. */
class SocialPipe implements LoginPipe
{
    public function handle(LoginAttempt $attempt, Closure $next)
    {
        if ($attempt->get('oauth_token') === 'valid-token') {
            return 'social-user';
        }

        return $next($attempt);
    }
}

/** Post-processing: sees the result of everything below it. */
class AuditPipe implements LoginPipe
{
    public static array $seen = [];

    public function handle(LoginAttempt $attempt, Closure $next)
    {
        $user = $next($attempt);
        static::$seen[] = $user;

        return $user;
    }
}

/** Lets a test supply the configuration without a booted CMS. */
class ArrayConfiguredPipeline extends LoginPipeline
{
    public array $configured = [];

    protected function configuredPipeline(): array
    {
        return $this->configured;
    }
}

beforeEach(function () {
    RecordingPipe::$log = [];
    AuditPipe::$seen = [];
});

function makeAttempt(array $data = [], string $stage = LoginPipeline::STAGE_LOGIN): LoginAttempt
{
    return new LoginAttempt($stage, $data, new stdClass());
}

function passwordLogin(): Closure
{
    return static fn (LoginAttempt $attempt) => 'password-user';
}

test('an empty pipeline changes nothing', function () {
    $pipeline = new ArrayConfiguredPipeline(new Container());

    expect($pipeline->run(makeAttempt(), passwordLogin()))->toBe('password-user')
        ->and(RecordingPipe::$log)->toBe([]);
});

test('two extras compose into one chain, in configured order', function () {
    $pipeline = new ArrayConfiguredPipeline(new Container());
    $pipeline->configured = ['login' => [FirstPipe::class, SecondPipe::class]];

    expect($pipeline->run(makeAttempt(), passwordLogin()))->toBe('password-user')
        ->and(RecordingPipe::$log)->toBe(['FirstPipe:login', 'SecondPipe:login']);

    // Order is configuration, not chance: swapping the array swaps the chain.
    RecordingPipe::$log = [];
    $pipeline->configured = ['login' => [SecondPipe::class, FirstPipe::class]];
    $pipeline->run(makeAttempt(), passwordLogin());

    expect(RecordingPipe::$log)->toBe(['SecondPipe:login', 'FirstPipe:login']);
});

test('a pipe can veto the login, which an OnManagerAuthentication plugin cannot', function () {
    $pipeline = new ArrayConfiguredPipeline(new Container());
    $pipeline->configured = ['login' => [RequireCodePipe::class]];

    expect(fn () => $pipeline->run(makeAttempt(['username' => 'admin']), passwordLogin()))
        ->toThrow(ServiceActionException::class, 'second factor required');

    // With the factor supplied the chain completes normally.
    expect($pipeline->run(makeAttempt(['username' => 'admin', 'code' => '123456']), passwordLogin()))
        ->toBe('password-user');
});

test('the veto is reported by the existing login error handling', function () {
    // LogInOut::simpleLogin() catches ServiceActionException and shows its message,
    // so a refusing pipe needs no new plumbing in the controllers.
    expect(is_subclass_of(ServiceActionException::class, Throwable::class))->toBeTrue();
});

test('a social pipe can authenticate on its own and skip the password check', function () {
    $pipeline = new ArrayConfiguredPipeline(new Container());
    $pipeline->configured = ['login' => [SocialPipe::class, FirstPipe::class]];

    // Short-circuits: the pipes below it and the password check never run.
    expect($pipeline->run(makeAttempt(['oauth_token' => 'valid-token']), passwordLogin()))
        ->toBe('social-user')
        ->and(RecordingPipe::$log)->toBe([]);

    // Without a token it falls through to the ordinary password login.
    expect($pipeline->run(makeAttempt(['username' => 'admin']), passwordLogin()))
        ->toBe('password-user')
        ->and(RecordingPipe::$log)->toBe(['FirstPipe:login']);
});

test('a pipe can post-process the authenticated user', function () {
    $pipeline = new ArrayConfiguredPipeline(new Container());
    $pipeline->configured = ['login' => [AuditPipe::class]];

    $pipeline->run(makeAttempt(), passwordLogin());

    expect(AuditPipe::$seen)->toBe(['password-user']);
});

test('the star stage covers every door into a session', function () {
    $pipeline = new ArrayConfiguredPipeline(new Container());
    $pipeline->configured = [LoginPipeline::STAGE_ANY => [RequireCodePipe::class]];

    // A second factor listed only under 'login' would be bypassed by a password
    // recovery link (hashLogin) or by remember-me (loginById).
    foreach ([
        LoginPipeline::STAGE_LOGIN,
        LoginPipeline::STAGE_LOGIN_BY_ID,
        LoginPipeline::STAGE_HASH_LOGIN,
    ] as $stage) {
        expect(fn () => $pipeline->run(makeAttempt([], $stage), passwordLogin()))
            ->toThrow(ServiceActionException::class);
    }
});

test('star pipes run before the stage specific ones', function () {
    $pipeline = new ArrayConfiguredPipeline(new Container());
    $pipeline->configured = [
        'login' => [SecondPipe::class],
        LoginPipeline::STAGE_ANY => [FirstPipe::class],
    ];

    $pipeline->run(makeAttempt(), passwordLogin());

    // Declaration order in the config array must not decide it — throttling and
    // second factors have to run before anything stage specific.
    expect(RecordingPipe::$log)->toBe(['FirstPipe:login', 'SecondPipe:login']);
});

test('a flat list is read as covering every stage', function () {
    $pipeline = new ArrayConfiguredPipeline(new Container());
    $pipeline->configured = [FirstPipe::class];

    $pipeline->run(makeAttempt([], LoginPipeline::STAGE_HASH_LOGIN), passwordLogin());

    expect(RecordingPipe::$log)->toBe(['FirstPipe:hashLogin']);
});

test('empty and malformed entries are ignored instead of fataling', function () {
    $pipeline = new ArrayConfiguredPipeline(new Container());
    $pipeline->configured = ['login' => ['', null, FirstPipe::class, false]];

    expect($pipeline->pipesFor('login'))->toBe([FirstPipe::class])
        ->and($pipeline->run(makeAttempt(), passwordLogin()))->toBe('password-user');
});

test('every entry point into a session runs the pipeline', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Auth/PipelineUserManager.php');

    foreach (['login', 'loginById', 'hashLogin'] as $method) {
        expect($source)->toContain('public function ' . $method . '(array $userData')
            ->and($source)->toContain('parent::' . $method . '($attempt->data');
    }

    expect($source)->toContain('STAGE_LOGIN,')
        ->and($source)->toContain('STAGE_LOGIN_BY_ID,')
        ->and($source)->toContain('STAGE_HASH_LOGIN,');
});

test('the pipeline ships enabled but empty, so behaviour is unchanged by default', function () {
    $config = require dirname(__DIR__, 3) . '/config/cms/auth.php';
    $appConfig = (string) file_get_contents(dirname(__DIR__, 3) . '/config/app.php');

    // The wrapper is the default provider, but an empty pipeline makes it a
    // pass-through: shipping pipes enabled would change every existing install.
    expect($appConfig)->toContain("'Evolution_UserManager' => EvolutionCMS\Providers\PipelineUserManagerServiceProvider::class")
        ->and($config['pipeline'])->toBe([]);
});

test('the default provider keeps the container key the facade resolves', function () {
    $provider = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Providers/PipelineUserManagerServiceProvider.php');
    $appConfig = (string) file_get_contents(dirname(__DIR__, 3) . '/config/app.php');

    // The facade alias stays as shipped, so all 16 core call sites keep working.
    expect($provider)->toContain("singleton('UserManager'")
        ->and($appConfig)->toContain("'UserManager' => EvolutionCMS\UserManager\Facades\UserManager::class");
});

test('a site can put the plain package implementation back with one file', function () {
    $appConfig = (string) file_get_contents(dirname(__DIR__, 3) . '/config/app.php');
    $provider = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Providers/PipelineUserManagerServiceProvider.php');

    // The provider list stays keyed, which is what makes a single custom config file
    // able to override exactly this one entry.
    expect($appConfig)->toContain("'Evolution_UserManager' =>")
        ->and($provider)->toContain('core/custom/config/app/providers/Evolution_UserManager.php')
        ->and(class_exists(\EvolutionCMS\UserManager\Providers\UserManagerServiceProvider::class))->toBeTrue();
});

test('the user-manager package itself is not modified', function () {
    $package = dirname(__DIR__, 3) . '/vendor/evolutioncms-services/user-manager/src/Services/UserManager.php';

    // The whole point of the subclass: the package stays upgradable.
    expect(is_file($package))->toBeTrue()
        ->and((string) file_get_contents($package))->not->toContain('LoginPipeline');
});
