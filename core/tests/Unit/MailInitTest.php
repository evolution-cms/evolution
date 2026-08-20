<?php

use EvolutionCMS\Mail;

beforeEach(function () {
    if (!defined('EVO_MANAGER_PATH')) {
        define('EVO_MANAGER_PATH', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'evolution-manager' . DIRECTORY_SEPARATOR);
    }
});

test('init keeps smtp password empty when legacy config returns null', function () {
    $modx = makeMailTestModx([
        'email_method' => 'smtp',
        'smtp_secure' => 'none',
        'smtp_port' => 25,
        'smtp_host' => 'smtp.example.com',
        'smtp_auth' => '0',
        'smtp_autotls' => '1',
        'smtp_username' => 'mailer',
        'smtppw' => null,
        'emailsender' => 'sender@example.com',
        'site_name' => 'Evolution CMS',
        'manager_language' => 'english',
        'modx_charset' => 'UTF-8',
    ]);

    $mail = new Mail();

    $previousHandler = set_error_handler(function (int $severity, string $message, string $file, int $line) {
        if (in_array($severity, [E_DEPRECATED, E_USER_DEPRECATED], true)) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }

        return false;
    });

    try {
        $mail->init($modx);
    } finally {
        restore_error_handler();
    }

    expect($previousHandler)->toBeCallable()
        ->and($mail->Mailer)->toBe('smtp')
        ->and($mail->Password)->toBe('');
});

test('init still decodes stored smtp password values', function () {
    $plainPassword = 'secret-pass';
    $legacyStoredPassword = str_replace('=', '%', base64_encode($plainPassword)) . 'ABCDEFG';

    $modx = makeMailTestModx([
        'email_method' => 'smtp',
        'smtp_secure' => 'none',
        'smtp_port' => 25,
        'smtp_host' => 'smtp.example.com',
        'smtp_auth' => '0',
        'smtp_autotls' => '1',
        'smtp_username' => 'mailer',
        'smtppw' => $legacyStoredPassword,
        'emailsender' => 'sender@example.com',
        'site_name' => 'Evolution CMS',
        'manager_language' => 'english',
        'modx_charset' => 'UTF-8',
    ]);

    $mail = (new Mail())->init($modx);

    expect($mail->Password)->toBe($plainPassword);
});

test('SMTP certificate verification remains enabled by default', function () {
    $mail = (new Mail())->init(makeMailTestModx(mailSenderSettings()));

    expect($mail->SMTPOptions)->toBe([]);
});

test('SMTP certificate verification can be explicitly disabled', function () {
    $mail = (new Mail())->init(makeMailTestModx(mailSenderSettings([
        'smtp_verify_peer' => '0',
    ])));

    expect($mail->SMTPOptions)->toBe([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ]);
});

test('automatic SMTP sender uses an email-form SMTP username for From and envelope sender', function () {
    $mail = (new Mail())->init(makeMailTestModx(mailSenderSettings([
        'email_method' => 'smtp',
        'email_sender_method' => '1',
        'smtp_username' => 'website@example.com',
        'emailsender' => 'support@example.net',
    ])));

    expect($mail->Mailer)->toBe('smtp')
        ->and($mail->Username)->toBe('website@example.com')
        ->and($mail->From)->toBe('website@example.com')
        ->and($mail->Sender)->toBe('website@example.com');
});

test('explicit emailsender mode keeps emailsender as From and envelope sender for SMTP', function () {
    $mail = (new Mail())->init(makeMailTestModx(mailSenderSettings([
        'email_method' => 'smtp',
        'email_sender_method' => '0',
        'smtp_username' => 'website@example.com',
        'emailsender' => 'support@example.net',
    ])));

    expect($mail->From)->toBe('support@example.net')
        ->and($mail->Sender)->toBe('support@example.net');
});

test('automatic SMTP sender falls back safely when the SMTP username is not an email address', function (string $username) {
    $mail = (new Mail())->init(makeMailTestModx(mailSenderSettings([
        'email_method' => 'smtp',
        'email_sender_method' => '1',
        'smtp_username' => $username,
        'emailsender' => 'support@example.net',
    ])));

    expect($mail->Username)->toBe($username)
        ->and($mail->From)->toBe('support@example.net')
        ->and($mail->Sender)->toBe('');
})->with([
    'non-email authentication name' => 'mailer-account',
    'empty authentication name' => '',
    'invalid email-like name' => 'mailer@@example.com',
]);

test('automatic non-SMTP sender retains the legacy mail transport fallback', function () {
    $mail = (new Mail())->init(makeMailTestModx(mailSenderSettings([
        'email_method' => 'mail',
        'email_sender_method' => '1',
        'smtp_username' => 'website@example.com',
        'emailsender' => 'support@example.net',
    ])));

    expect($mail->Mailer)->toBe('mail')
        ->and($mail->From)->toBe('support@example.net')
        ->and($mail->Sender)->toBe('');
});

function mailSenderSettings(array $overrides = []): array
{
    return array_replace([
        'email_method' => 'smtp',
        'email_sender_method' => '1',
        'smtp_secure' => 'none',
        'smtp_port' => 25,
        'smtp_host' => 'smtp.example.com',
        'smtp_auth' => '1',
        'smtp_autotls' => '0',
        'smtp_username' => 'website@example.com',
        'smtppw' => '',
        'emailsender' => 'support@example.net',
        'site_name' => 'Evolution CMS',
        'manager_language' => 'english',
        'evo_charset' => 'UTF-8',
        'modx_charset' => 'UTF-8',
    ], $overrides);
}

function makeMailTestModx(array $settings)
{
    return new class($settings) implements ArrayAccess {
        public array $config = [];
        public bool $debug = false;

        private array $settings;
        private object $configRepository;
        private object $phpCompat;

        public function __construct(array $settings)
        {
            $this->settings = $settings;
            $this->configRepository = new class {
                public function has(string $key): bool
                {
                    return false;
                }

                public function get(string $key)
                {
                    return null;
                }
            };
            $this->phpCompat = new class {
                public function entities($value)
                {
                    return $value;
                }
            };
        }

        public function getConfig(string $key, $default = null)
        {
            return $this->settings[$key] ?? $default;
        }

        public function getPhpCompat(): object
        {
            return $this->phpCompat;
        }

        public function offsetExists(mixed $offset): bool
        {
            return $offset === 'config';
        }

        public function offsetGet(mixed $offset): mixed
        {
            return $offset === 'config' ? $this->configRepository : null;
        }

        public function offsetSet(mixed $offset, mixed $value): void
        {
        }

        public function offsetUnset(mixed $offset): void
        {
        }
    };
}
