<?php

use EvolutionCMS\Models\User;
use EvolutionCMS\Services\PasswordRecoveryService;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\Repository;

/*
|--------------------------------------------------------------------------
| The "Forgot your password?" form must not tell which addresses are accounts
|--------------------------------------------------------------------------
|
| It used to answer "Could not find user", "This User is blocked!" or "Email sent", took the
| address from the query string and had no limit at all, so anybody could list the manager
| e-mail addresses of a site (phishing targets) and flood a known one with recovery mails.
|
*/

/** The request flow with the database, the mailer, the cache and the event log replaced. */
class RecordingRecoveryRequests extends PasswordRecoveryService
{
    /** @var array<string, int> e-mail => user id */
    public array $accounts = ['admin@example.com' => 1, 'editor@example.com' => 2];
    public array $lookups = [];
    public array $mails = [];
    public array $logged = [];
    public bool $mailerWorks = true;
    public ?RateLimiter $rateLimiter;
    public array $limits = PasswordRecoveryService::DEFAULT_REQUEST_LIMITS;
    /** @var callable[] work waiting for the response to be sent */
    public array $deferred = [];
    public bool $responseSent = true;
    public bool $canDetach = true;
    public float $clock = 1000.0;
    public array $held = [];

    public function __construct()
    {
        $this->rateLimiter = new RateLimiter(new Repository(new ArrayStore()));
    }

    protected function findRecoverableUser(string $email): ?User
    {
        $this->lookups[] = $email;

        return isset($this->accounts[$email])
            ? (new User())->forceFill(['id' => $this->accounts[$email]])
            : null;
    }

    public function issueToken(User $user): string
    {
        return 'token-' . $user->getKey();
    }

    public function sendRecoveryMail(User $user, $token, $mode = 'hash'): bool
    {
        if (!$this->mailerWorks) {
            throw new RuntimeException('SMTP connect() failed');
        }
        $this->mails[] = $user->getKey();

        return true;
    }

    protected function requestLimits(): array
    {
        return $this->limits;
    }

    protected function limiter(): ?RateLimiter
    {
        return $this->rateLimiter;
    }

    protected function log(int $type, string $message): void
    {
        $this->logged[] = [$type, $message];
    }

    protected function defer(callable $callback): void
    {
        $this->responseSent ? $callback() : $this->deferred[] = $callback;
    }

    protected function detaches(): bool
    {
        return $this->canDetach;
    }

    protected function now(): float
    {
        return $this->clock;
    }

    protected function holdUntil(float $release): void
    {
        $this->held[] = $release;
    }
}

it('answers the same for a known, an unknown and an unusable address', function ($email) {
    $recovery = new RecordingRecoveryRequests();

    expect($recovery->requestByEmail($email, '192.0.2.1'))->toBe(PasswordRecoveryService::REQUEST_ACCEPTED);
})->with([
    'account' => ['admin@example.com'],
    'unknown' => ['nobody@example.com'],
    'empty' => [''],
    'array' => [['admin@example.com']],
    'oversized' => [str_repeat('a', 300) . '@example.com'],
]);

it('mails the account and nobody else', function () {
    $recovery = new RecordingRecoveryRequests();

    $recovery->requestByEmail(' admin@example.com ', '192.0.2.1');
    $recovery->requestByEmail('nobody@example.com', '192.0.2.1');
    $recovery->requestByEmail(['admin@example.com'], '192.0.2.1');
    $recovery->requestByEmail(str_repeat('a', 300) . '@example.com', '192.0.2.1');

    expect($recovery->mails)->toBe([1])
        ->and($recovery->lookups)->toBe(['admin@example.com', 'nobody@example.com']);
});

it('reports a mail that could not be sent to the event log, not to the requester', function () {
    $recovery = new RecordingRecoveryRequests();
    $recovery->mailerWorks = false;

    expect($recovery->requestByEmail('admin@example.com', '192.0.2.1'))->toBe(PasswordRecoveryService::REQUEST_ACCEPTED)
        ->and($recovery->logged)->toHaveCount(1)
        ->and($recovery->logged[0][0])->toBe(3)
        ->and($recovery->logged[0][1])->toContain('user 1')->toContain('SMTP connect() failed')
        // the address asked for is not written down: the log would become the list
        ->not->toContain('admin@example.com');
});

it('issues the token and sends the mail only after the reply', function () {
    $recovery = new RecordingRecoveryRequests();
    $recovery->responseSent = false;

    expect($recovery->requestByEmail('admin@example.com', '192.0.2.1'))->toBe(PasswordRecoveryService::REQUEST_ACCEPTED)
        ->and($recovery->requestByEmail('nobody@example.com', '192.0.2.1'))->toBe(PasswordRecoveryService::REQUEST_ACCEPTED)
        // a mail hand-over takes a second or two, and only an account would have waited for it
        ->and($recovery->mails)->toBe([])
        ->and($recovery->deferred)->toHaveCount(1);

    $recovery->deferred[0]();

    expect($recovery->mails)->toBe([1])
        // the client was let go before the mail, so nothing is left to pad
        ->and($recovery->held)->toBe([]);
});

it('holds every answered connection equally long where the server cannot let go of the client', function () {
    $recovery = new RecordingRecoveryRequests();
    $recovery->canDetach = false;
    $recovery->limits = ['attempts' => 3] + $recovery->limits;

    $recovery->requestByEmail('admin@example.com', '192.0.2.1');
    $recovery->requestByEmail('nobody@example.com', '192.0.2.1');
    $recovery->requestByEmail('', '192.0.2.1');

    // measured from the request, not from the end of the mail: the mail fits inside it
    expect($recovery->held)->toBe([1003.0, 1003.0, 1003.0])
        ->and($recovery->mails)->toBe([1]);

    // a refusal says nothing about the address, and must stay cheap
    expect($recovery->requestByEmail('admin@example.com', '192.0.2.1'))->toBe(PasswordRecoveryService::REQUEST_THROTTLED)
        ->and($recovery->held)->toHaveCount(3);
});

it('refuses an address that keeps asking, whatever it asks for', function () {
    $recovery = new RecordingRecoveryRequests();
    $asked = ['a@example.com', 'b@example.com', 'c@example.com', 'd@example.com', 'e@example.com'];

    foreach ($asked as $email) {
        expect($recovery->requestByEmail($email, '192.0.2.1'))->toBe(PasswordRecoveryService::REQUEST_ACCEPTED);
    }

    // the sixth is refused before anything is looked up, an existing account included
    expect($recovery->requestByEmail('admin@example.com', '192.0.2.1'))->toBe(PasswordRecoveryService::REQUEST_THROTTLED)
        ->and($recovery->requestByEmail('nobody@example.com', '192.0.2.1'))->toBe(PasswordRecoveryService::REQUEST_THROTTLED)
        ->and($recovery->lookups)->toBe($asked)
        ->and($recovery->mails)->toBe([])
        // one warning per window, not one per request
        ->and($recovery->logged)->toHaveCount(1)
        ->and($recovery->logged[0][0])->toBe(2)
        ->and($recovery->logged[0][1])->toContain('192.0.2.1')
        // and nobody else is held up by it
        ->and($recovery->requestByEmail('admin@example.com', '192.0.2.2'))->toBe(PasswordRecoveryService::REQUEST_ACCEPTED)
        ->and($recovery->mails)->toBe([1]);
});

it('does not flood one mailbox from many addresses, and does not say so', function () {
    $recovery = new RecordingRecoveryRequests();

    foreach (range(1, 8) as $host) {
        expect($recovery->requestByEmail('admin@example.com', '192.0.2.' . $host))
            ->toBe(PasswordRecoveryService::REQUEST_ACCEPTED);
    }

    expect($recovery->mails)->toBe([1, 1, 1])
        ->and($recovery->logged)->toBe([]);

    // another account is not affected
    $recovery->requestByEmail('editor@example.com', '192.0.2.9');
    expect($recovery->mails)->toBe([1, 1, 1, 2]);
});

it('still recovers when no cache is available', function () {
    $recovery = new RecordingRecoveryRequests();
    $recovery->rateLimiter = null;

    foreach (range(1, 7) as $attempt) {
        expect($recovery->requestByEmail('admin@example.com', '192.0.2.1'))->toBe(PasswordRecoveryService::REQUEST_ACCEPTED);
    }

    expect($recovery->mails)->toHaveCount(7);
});

it('falls back to the default limits for values that are not positive numbers', function () {
    $limits = new class extends PasswordRecoveryService {
        public array $configured = [];

        public function read(): array
        {
            return $this->requestLimits();
        }

        protected function configuredLimits(): array
        {
            return $this->configured;
        }
    };

    expect($limits->read())->toBe(PasswordRecoveryService::DEFAULT_REQUEST_LIMITS);

    $limits->configured = ['attempts' => 0, 'decay' => 'soon', 'mails' => 2, 'mail_decay' => -1];

    expect($limits->read())->toBe(['attempts' => 5, 'decay' => 900, 'mails' => 2, 'mail_decay' => 3600, 'hold' => 3]);
});

test('the shipped limits are the documented defaults', function () {
    $config = include dirname(__DIR__, 3) . '/config/cms/auth.php';

    expect($config['recovery'])->toBe(PasswordRecoveryService::DEFAULT_REQUEST_LIMITS);
});

test('the login screen takes the address by POST and prints one answer', function () {
    $root = dirname(__DIR__, 4);
    $theme = (string) file_get_contents($root . '/core/src/ManagerTheme.php');
    $start = strpos($theme, 'public function repairPassword');
    $body = substr($theme, (int) $start, (int) strpos($theme, 'public function sendRepairMail') - (int) $start);
    $form = (string) file_get_contents($root . '/manager/media/style/default/repair_form.tpl');

    expect($start)->not->toBeFalse()
        ->and($body)->toContain("\$_SERVER['REQUEST_METHOD'] ?? '') === 'POST'")
        ->and($body)->toContain('->requestByEmail(')
        // a forwarded header is the client's to choose, and would reset the limit at will
        ->and($body)->toContain("\$_SERVER['REMOTE_ADDR']")
        ->and($body)->not->toContain('$_GET[\'email\']')
        ->and($body)->not->toContain('$_REQUEST')
        ->and($body)->not->toContain('could_not_find_user')
        ->and($body)->not->toContain('user_is_blocked')
        ->and($body)->not->toContain('error_sending_email')
        ->and($form)->toContain("form.method = 'post'")
        ->and($form)->toContain("input.name = 'repair_email'")
        ->and($form)->not->toContain('email=')
        // Enter in the field used to submit the login form around it ("Field username is required")
        ->and($form)->toContain("event.key === 'Enter'");
});
