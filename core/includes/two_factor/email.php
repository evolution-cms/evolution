<?php

use EvolutionCMS\Auth\TwoFactorMethod;
use EvolutionCMS\Exceptions\ServiceActionException;
use EvolutionCMS\Models\User;

/**
 * A one-time code sent to the address of the account.
 *
 * Loaded on demand by EvolutionCMS\Auth\TwoFactor and anonymous: it takes no autoloader entry.
 */
return new class extends TwoFactorMethod {
    private const LIFETIME = 600;
    private const RESEND_AFTER = 60;

    public function title(): string
    {
        return __('global.two_factor_email');
    }

    public function canResend(): bool
    {
        return true;
    }

    public function begin(User $user, array $state, string $context): array
    {
        $now = time();
        // a mail a minute at most, however often the password is entered
        if (isset($state['hash']) && (int) ($state['sent'] ?? 0) > $now - self::RESEND_AFTER) {
            return $state;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        if (!$this->send($user, $code)) {
            throw new ServiceActionException(__('global.two_factor_email_failed'));
        }
        $salt = bin2hex(random_bytes(16));

        return [
            'salt' => $salt,
            'hash' => hash_hmac('sha256', $code, $salt),
            'sent' => $now,
            'expires' => $now + self::LIFETIME,
        ];
    }

    public function form(User $user, array $state, string $context): string
    {
        return $this->codeField(__('global.two_factor_email_hint', ['email' => $this->maskedAddress($user)]));
    }

    public function verify(User $user, string $response, array $state, string $context): ?array
    {
        if (!isset($state['hash'], $state['salt']) || (int) ($state['expires'] ?? 0) < time()) {
            return null;
        }
        $response = (string) preg_replace('/\s+/', '', $response);

        return hash_equals($state['hash'], hash_hmac('sha256', $response, $state['salt'])) ? [] : null;
    }

    private function address(User $user): string
    {
        $email = trim((string) ($user->attributes->email ?? ''));

        return filter_var($email, FILTER_VALIDATE_EMAIL) === false ? '' : $email;
    }

    private function maskedAddress(User $user): string
    {
        $email = $this->address($user);
        $at = (int) strrpos($email, '@');

        return mb_substr($email, 0, 1) . '***' . substr($email, $at);
    }

    private function send(User $user, string $code): bool
    {
        $email = $this->address($user);
        if ($email === '') {
            return false;
        }

        $site = (string) evo()->getConfig('site_name');
        try {
            return (bool) evo()->sendmail([
                'from' => $site . '<' . evo()->getConfig('emailsender') . '>',
                'to' => $email,
                // the event log keeps subjects, so the code is in the body only
                'subject' => __('global.two_factor_mail_subject', ['site' => $site]),
                'body' => '<p>' . e(__('global.two_factor_mail_intro', ['site' => $site, 'user' => $user->username])) . '</p>'
                    . '<p style="font-size:1.6em;letter-spacing:.2em"><b>' . $code . '</b></p>'
                    . '<p>' . e(__('global.two_factor_mail_outro', ['minutes' => intdiv(self::LIFETIME, 60)])) . '</p>',
            ]);
        } catch (\Throwable $exception) {
            return false;
        }
    }
};
