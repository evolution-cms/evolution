<?php

/**
 * Runs EvolutionCMS\Auth\TwoFactor, its built-in methods and its migration on an in-memory database,
 * with the CMS around them replaced. Prints JSON: check name => what it saw.
 */

define('EVO_CORE_PATH', dirname(__DIR__, 2) . '/');
define('EVO_BASE_URL', '/sub/');
define('EVO_SITE_URL', 'http://site.test/sub/');

final class TwoFactorHarnessCms
{
    public array $config = ['failed_login_attempts' => 3, 'blocked_minutes' => 10, 'site_name' => 'Site: one', 'emailsender' => 's@site.test'];
    public array $mails = [];
    public array $logged = [];
    public array $logins = [];
    public bool $mailerWorks = true;
    public ?Closure $listener = null;
    public object $twoFactor;

    public function getConfig($name, $default = null)
    {
        return $this->config[$name] ?? $default;
    }

    public function logEvent($id, $type, $message, $source): void
    {
        $this->logged[] = $message;
    }

    public function invokeEvent($name, $params): void
    {
        if ($name === 'OnTwoFactorMethods' && $this->listener !== null) {
            ($this->listener)($params);
        }
    }

    public function sendmail(array $params): bool
    {
        if ($this->mailerWorks) {
            $this->mails[] = $params;
        }

        return $this->mailerWorks;
    }

    public function make(string $abstract): object
    {
        return $this;
    }

    /** stands for PipelineUserManager: the gate runs inside it as well */
    public function loginByIdWithoutPipeline(array $data): object
    {
        $user = EvolutionCMS\Models\User::query()->find($data['id']);
        $this->twoFactor->gate($user, $data['context'], $data, 'loginById');
        $this->logins[] = $data;

        return $user;
    }
}

function evo()
{
    static $cms;

    return $cms ??= new TwoFactorHarnessCms();
}

function config($key = null, $default = null)
{
    return $GLOBALS['harnessConfig'][$key] ?? $default;
}

function __($key = null, $replace = [])
{
    return $key . ($replace === [] ? '' : json_encode($replace));
}

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use EvolutionCMS\Auth\TwoFactor;
use EvolutionCMS\Auth\TwoFactorMethod;
use EvolutionCMS\Exceptions\ServiceActionException;
use EvolutionCMS\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;

$capsule = new Capsule();
$capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
$capsule->setAsGlobal();
$capsule->bootEloquent();
$container = new Illuminate\Container\Container();
$container->instance('db', $capsule->getDatabaseManager());
$container->bind('db.schema', static fn () => $capsule->getConnection()->getSchemaBuilder());
Facade::setFacadeApplication($container);

$schema = $capsule->getConnection()->getSchemaBuilder();
$schema->create('users', function ($table) {
    $table->increments('id');
    $table->string('username');
    $table->string('cachepwd')->default('');
});
$schema->create('user_attributes', function ($table) {
    $table->increments('id');
    $table->integer('internalKey');
    $table->string('email')->default('');
    $table->integer('blocked')->default(0);
    $table->integer('blockeduntil')->default(0);
    $table->integer('failedlogincount')->default(0);
});
$schema->create('system_settings', function ($table) {
    $table->string('setting_name');
    $table->text('setting_value');
});
$schema->create('system_eventnames', function ($table) {
    $table->increments('id');
    $table->string('name');
    $table->integer('service');
    $table->string('groupname');
});
foreach ([1 => 'admin', 2 => 'editor', 3 => 'nomail'] as $id => $name) {
    DB::table('users')->insert(['id' => $id, 'username' => $name]);
    DB::table('user_attributes')->insert(['internalKey' => $id, 'email' => $id === 3 ? '' : $name . '@site.test']);
}
DB::table('system_eventnames')->insert(['name' => 'OnLoadSettings', 'service' => 1, 'groupname' => '']);

/** A TwoFactor that takes the console for a visitor. */
final class HarnessTwoFactor extends TwoFactor
{
    public bool $console = false;

    protected function isConsole(): bool
    {
        return $this->console;
    }

    protected function redirectToPage(): void
    {
    }
}

$tf = evo()->twoFactor = new HarnessTwoFactor();
$user = static fn (int $id) => User::query()->find($id);
$site = static function (string $method): void {
    DB::table('system_settings')->delete();
    DB::table('system_settings')->insert(['setting_name' => 'two_factor_method', 'setting_value' => $method]);
};
$unlockAccounts = static fn () => DB::table('user_attributes')->update(['failedlogincount' => 0, 'blockeduntil' => 0]);
/** message of the refusal, '' when the login goes on */
$try = static function (callable $attempt): string {
    try {
        $attempt();

        return '';
    } catch (ServiceActionException $exception) {
        return $exception->getMessage() . '#' . $exception->getCode();
    }
};
$gate = static fn (int $id, array $data = [], string $context = 'mgr') => $try(fn () => $tf->gate($user($id), $context, $data, 'login'));
$complete = static fn (string $code, string $context = 'mgr') => $try(fn () => $tf->complete($context, $code));
$mailCode = static fn () => preg_match('~<b>(\d{6})</b>~', end(evo()->mails)['body'] ?? '', $m) ? $m[1] : '';
$out = [];
// taken once: the run may cross into the next 30 seconds, which the methods allow for
$step = intdiv(time(), 30);

/* ------------------------------------------------- before the migration: logins go on as they did */
$out['no table: gate'] = $gate(1);
TwoFactor::purge(1);

$migration = require EVO_CORE_PATH . 'database/migrations/2026_11_24_000000_create_user_two_factor_table.php';
$migration->up();
$migration->up();
$out['migration: event once'] = DB::table('system_eventnames')->where('name', 'OnTwoFactorMethods')->count();

/* ---------------------------------------------------------------------------------------- policy */
$out['off: gate'] = $gate(1);
$out['options'] = array_keys($tf->options());
$site('totp');
$out['site'] = $tf->effective(1);
$tf->setOverride(1, 'off');
$out['override off'] = [$tf->effective(1), $gate(1)];
$tf->setOverride(1, 'email');
$out['override'] = $tf->effective(1);
$tf->setOverride(1, null);
$tf->setOwnChoice(1, 'email');
$out['own choice under a policy'] = [$tf->effective(1), $tf->mayChoose(1)];
$site('off');
$out['own choice'] = [$tf->effective(1), $tf->mayChoose(1)];
$tf->setOverride(1, 'off');
$out['override beats own choice'] = [$tf->effective(1), $tf->mayChoose(1)];
$tf->setOverride(1, null);
$tf->setOwnChoice(1, null);
$GLOBALS['harnessConfig']['cms.settings.two_factor_method'] = 'off';
$site('totp');
$out['settings file wins'] = $tf->effective(1);
unset($GLOBALS['harnessConfig']['cms.settings.two_factor_method']);
// the configuration of whoever is signed in must not decide about somebody else's login
evo()->config['two_factor_method'] = 'off';
$out['user configuration does not count'] = $tf->effective(1);
unset(evo()->config['two_factor_method']);

/* ------------------------------------------------------------------------- TOTP, set up at login */
$totp = $tf->method('totp');
$out['rfc 6238'] = array_map(fn ($time) => $totp->code('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', intdiv($time, 30)), [59, 1111111109, 1234567890, 20000000000]);
$_SESSION = [];
$out['totp: asked'] = $gate(1, ['rememberme' => '1']);
$pending = $tf->pending('mgr');
$secret = $pending['setup']['secret'];
$out['totp: secret'] = [preg_match('/^[A-Z2-7]{32}$/', $secret), $pending['user'], $pending['remember'], evo()->logins];
$out['totp: uri'] = $totp->uri($user(1), 'ABC');
$out['totp: setup form has a QR code'] = str_contains($totp->setupForm($user(1), $pending['setup'], 'mgr'), '<svg');
$out['totp: no code yet'] = [$complete(''), $tf->isConfirmed(1, 'totp')];
$result = $tf->complete('mgr', $totp->code($secret, $step));
$codes = $result['codes'];
$out['totp: set up'] = [$tf->isConfirmed(1, 'totp'), count($codes), evo()->logins, $tf->pending('mgr')];
$out['codes are stored as hashes'] = !str_contains((string) DB::table('user_two_factor')->where('method', '@recovery')->value('data'), str_replace('-', '', $codes[0]));
evo()->logins = [];

$gate(1);
$out['totp: replay'] = $complete($totp->code($secret, $step));
$next = $totp->code($secret, $step + 1);
$out['totp: next'] = [$complete($next), count(evo()->logins)];
$unlockAccounts();
$out['inline wrong'] = $gate(1, ['two_factor_code' => '000000x']);
$out['inline array'] = $gate(1, ['two_factor_code' => ['1']]);
$out['inline recovery'] = [$gate(1, ['two_factor_code' => $codes[0]]), $tf->recoveryCodesLeft(1)];
$out['recovery once'] = $gate(1, ['two_factor_code' => $codes[0]]);
$out['recovery upper case, no dashes'] = $gate(1, ['two_factor_code' => ' ' . strtoupper(str_replace('-', '', $codes[1]))]);
$out['recovery of somebody else'] = (function () use ($tf, $codes, $gate, $unlockAccounts) {
    $tf->setOverride(2, 'email');
    $answer = $gate(2, ['two_factor_code' => $codes[2]]);
    $unlockAccounts();

    return $answer;
})();

/* ------------------------------------------------------------------------------ nothing to slip by */
$out['payload cannot pass'] = $gate(1, ['passed' => 1, 'trusted' => 1, 'two_factor' => 'verified', 'context' => 'web']);
$out['trusted'] = [$tf->withoutChallenge(fn () => $gate(1)), str_contains($gate(1), '#2')];
$out['trusted after an exception'] = [$try(fn () => $tf->withoutChallenge(fn () => throw new ServiceActionException('x'))), str_contains($gate(1), '#2')];
$tf->console = true;
$out['console'] = $gate(1);
$tf->console = false;
$_SESSION = [];
$out['nothing waits'] = $complete($codes[3]);
$gate(1);
$out['the other context'] = $complete($codes[3], 'web');
$_SESSION[TwoFactor::SESSION]['pending']['mgr']['at'] = time() - 901;
$out['expired'] = [$complete($codes[3]), $tf->recoveryCodesLeft(1)];
$gate(1);
$tf->setOverride(1, 'email');
$out['method switched meanwhile'] = $complete($codes[3]);
$tf->setOverride(1, null);
// two browsers start the first set-up, one finishes: the other must not replace it
$_SESSION = [];
$tf->reset($user(1));
$gate(1);
$first = $_SESSION;
$_SESSION = [];
$gate(1);
$second = $tf->pending('mgr')['setup']['secret'];
$tf->complete('mgr', $totp->code($second, $step));
$_SESSION = $first;
$out['late set-up'] = [$complete($totp->code($first[TwoFactor::SESSION]['pending']['mgr']['setup']['secret'], $step)), $tf->state($user(1), 'totp')['secret'] === $second];

/* ------------------------------------------------------------------------------------------- lock */
$unlockAccounts();
$_SESSION = [];
$gate(1);
$codes = $tf->regenerateRecoveryCodes(1);
$out['lock'] = [$complete('111111'), $complete('111111'), $complete('111111'), $complete($codes[0]), $tf->recoveryCodesLeft(1)];
DB::table('user_attributes')->update(['blockeduntil' => time() - 1]);
$out['lock over'] = [$complete('111111'), DB::table('user_attributes')->where('internalKey', 1)->value('failedlogincount'), $complete($codes[0])];
DB::table('user_attributes')->where('internalKey', 1)->update(['blocked' => 1]);
$out['blocked account'] = $gate(1, ['two_factor_code' => $codes[1]]);
DB::table('user_attributes')->update(['blocked' => 0]);

/* ------------------------------------------------------------------------------------------ e-mail */
$site('email');
$_SESSION = [];
evo()->mails = [];
$out['email: asked'] = [$gate(2), count(evo()->mails), end(evo()->mails)['to']];
$gate(2);
$tf->resend('mgr');
$out['email: one mail a minute'] = count(evo()->mails);
$out['email: code not in the subject'] = !str_contains(end(evo()->mails)['subject'], $mailCode());
$out['email: code not stored'] = !str_contains((string) DB::table('user_two_factor')->where(['user_id' => 2, 'method' => 'email'])->value('data'), $mailCode());
$out['email: masked'] = str_contains($tf->method('email')->form($user(2), [], 'mgr'), 'e***@site.test');
$out['email: accepted'] = [$complete($mailCode()), $tf->isConfirmed(2, 'email')];
$gate(2);
$state = $tf->state($user(2), 'email');
DB::table('user_two_factor')->where(['user_id' => 2, 'method' => 'email'])->update(['data' => json_encode(['expires' => time() - 1] + $state)]);
$out['email: expired'] = $complete($mailCode());
evo()->mailerWorks = false;
$_SESSION = [];
$out['email: no address, no way in'] = [$gate(3), $tf->pending('mgr')];
$unlockAccounts();
$recovery = $tf->regenerateRecoveryCodes(3);
$out['email: no address, recovery code'] = [$gate(3), $complete($recovery[0])];
evo()->mailerWorks = true;

/* ---------------------------------------------------------------------- a client without a session */
unset($_SESSION);
DB::table('user_two_factor')->where('user_id', 2)->where('method', '<>', '@policy')->delete();
$out['stateless: e-mail'] = [$gate(2), $gate(2, ['two_factor_code' => $mailCode()]), isset($_SESSION)];
$tf->setOverride(3, 'totp');
$out['stateless: set-up needs a browser'] = $gate(3);
$tf->setOverride(3, null);
$_SESSION = [];

/* ----------------------------------------------------------------------------------------- plugins */
evo()->listener = static function (array $params): void {
    $params['twoFactor']->extend('pin', new class extends TwoFactorMethod {
        public function title(): string
        {
            return 'PIN';
        }

        public function verify(User $user, string $response, array $state, string $context): ?array
        {
            return $response === '42' ? $state : null;
        }
    });
};
$out['plugin: in the list'] = $tf->options()['pin'] ?? null;
$tf->setOverride(2, 'pin');
$_SESSION = [];
$out['plugin: asked'] = [$gate(2), $complete('41'), $complete('42'), $tf->isConfirmed(2, 'pin')];
$fresh = evo()->twoFactor = new HarnessTwoFactor();
evo()->listener = null;
$out['plugin: gone'] = [$fresh->effective(2), count(evo()->logged) > 0];
evo()->twoFactor = $tf;
$out['plugin: bad keys'] = array_map(static function ($key) use ($tf, $totp) {
    try {
        $tf->extend($key, $totp);

        return 'accepted';
    } catch (InvalidArgumentException $exception) {
        return 'refused';
    }
}, ['off', '@policy', '@recovery', '', 'A', "x\n", 'a b']);

/* ---------------------------------------------------------------------------------------- own page */
$site('off');
$_SESSION = [];
$tf->reset($user(1));
$out['own: open while nothing is set up'] = $tf->isLocked($user(1), 'mgr');
$tf->beginOwn($user(1), 'totp', 'mgr');
$own = $tf->ownPending($user(1), 'mgr');
$out['own: not somebody else\'s'] = $tf->ownPending($user(2), 'mgr');
$tf->beginOwn($user(1), 'totp', 'mgr');
$own = $tf->ownPending($user(1), 'mgr');
$ownCodes = $tf->completeOwn($user(1), $totp->code($own['setup']['secret'], $step), 'mgr');
$out['own: set up'] = [$tf->effective(1), count($ownCodes), $tf->isLocked($user(1), 'mgr')];
$_SESSION = [];
$tf->beginOwn($user(1), 'email', 'mgr');
$out['own: locked in another session'] = [$tf->isLocked($user(1), 'mgr'), $tf->ownPending($user(1), 'mgr')];
$tf->beginOwn($user(1), null, 'mgr');
$out['own: unlock'] = [$try(fn () => $tf->completeOwn($user(1), '1', 'mgr')), $try(fn () => $tf->completeOwn($user(1), $ownCodes[0], 'mgr')), $tf->isLocked($user(1), 'mgr')];
$tf->beginOwn($user(1), 'email', 'mgr');
$out['own: recovery code proves nothing'] = [$try(fn () => $tf->completeOwn($user(1), $ownCodes[1], 'mgr')), $tf->isConfirmed(1, 'email')];
$tf->reset($user(1));
$out['reset'] = [$tf->effective(1), $tf->recoveryCodesLeft(1), $tf->isConfirmed(1, 'totp')];

/* ------------------------------------------------------------------------------------- encryption */
$GLOBALS['harnessConfig']['cms.auth.two_factor.key'] = 'long random text';
$tf->setOverride(3, 'totp');
$raw = (string) DB::table('user_two_factor')->where(['user_id' => 3, 'method' => '@policy'])->value('data');
$out['encrypted'] = [str_starts_with($raw, 'enc:'), str_contains($raw, 'totp'), $tf->override(3)];
$GLOBALS['harnessConfig']['cms.auth.two_factor.key'] = 'another';
$out['key lost fails closed'] = $try(fn () => $tf->effective(3));
unset($GLOBALS['harnessConfig']['cms.auth.two_factor.key']);

/* ----------------------------------------------------------------------------------- return address */
$return = static function (string $method, string $uri, string $referer) use ($tf, $gate, $unlockAccounts) {
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['HTTP_REFERER'] = $referer;
    $_SESSION = [];
    $tf->setOverride(2, 'totp');
    $gate(2, [], 'web');

    return $tf->pending('web')['return'] ?? null;
};
$out['return'] = [
    $return('POST', '/sub/login', 'http://site.test/sub/shop?page=2'),
    $return('POST', '/sub/login', 'http://evil.test/x'),
    $return('POST', '/sub/login', ''),
    $return('GET', '/sub/auth?x=1', 'http://evil.test/x'),
    $return('GET', '//evil.test/x', ''),
];

$tf->setOverride(2, 'off');
User::query()->find(1);
TwoFactor::purge(2);
$out['purge'] = DB::table('user_two_factor')->where('user_id', 2)->count();

echo json_encode($out, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
