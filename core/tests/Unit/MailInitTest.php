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
