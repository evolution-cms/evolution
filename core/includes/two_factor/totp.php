<?php

use EvolutionCMS\Auth\TwoFactorMethod;
use EvolutionCMS\Models\User;

/**
 * One-time passwords of an authenticator app (RFC 6238: SHA-1, 6 digits, 30 seconds).
 *
 * Loaded on demand by EvolutionCMS\Auth\TwoFactor and anonymous: it takes no autoloader entry.
 */
return new class extends TwoFactorMethod {
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const PERIOD = 30;

    public function title(): string
    {
        return __('global.two_factor_totp');
    }

    public function needsSetup(User $user, array $state, string $context): bool
    {
        return empty($state['secret']);
    }

    public function beginSetup(User $user, string $context): array
    {
        $secret = '';
        foreach (str_split(random_bytes(32)) as $byte) {
            $secret .= self::ALPHABET[ord($byte) & 31];
        }

        return ['secret' => $secret];
    }

    public function setupForm(User $user, array $setup, string $context): string
    {
        $qr = require __DIR__ . '/qr.php';

        return '<p>' . e(__('global.two_factor_totp_scan')) . '</p>'
            . '<div class="evo-2fa__qr">' . $qr($this->uri($user, $setup['secret'])) . '</div>'
            . '<p>' . e(__('global.two_factor_totp_manual')) . ' <code class="evo-2fa__secret">'
            . e(implode(' ', str_split($setup['secret'], 4))) . '</code></p>'
            . $this->codeField(__('global.two_factor_totp_enter'));
    }

    public function form(User $user, array $state, string $context): string
    {
        return $this->codeField(__('global.two_factor_totp_hint'));
    }

    public function verify(User $user, string $response, array $state, string $context): ?array
    {
        $response = (string) preg_replace('/\s+/', '', $response);
        if (empty($state['secret']) || !preg_match('/^\d{6}$/D', $response)) {
            return null;
        }

        $current = intdiv(time(), self::PERIOD);
        // one step either way for clocks that differ; a step that was accepted never is again
        for ($step = max($current - 1, (int) ($state['step'] ?? 0) + 1); $step <= $current + 1; $step++) {
            if (hash_equals($this->code($state['secret'], $step), $response)) {
                return ['secret' => $state['secret'], 'step' => $step];
            }
        }

        return null;
    }

    public function code(string $secret, int $step): string
    {
        $bits = '';
        foreach (str_split($secret) as $char) {
            $bits .= str_pad(decbin((int) strpos(self::ALPHABET, $char)), 5, '0', STR_PAD_LEFT);
        }
        $key = '';
        foreach (str_split(substr($bits, 0, intdiv(strlen($bits), 8) * 8), 8) as $byte) {
            $key .= chr((int) bindec($byte));
        }

        $hash = hash_hmac('sha1', pack('J', $step), $key, true);
        $value = unpack('N', substr($hash, ord($hash[19]) & 15, 4))[1] & 0x7fffffff;

        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    public function uri(User $user, string $secret): string
    {
        // a colon separates the issuer from the account in the label
        $issuer = trim(str_replace(':', ' ', mb_substr((string) evo()->getConfig('site_name'), 0, 40)));
        $issuer = rawurlencode($issuer === '' ? 'Evolution CMS' : $issuer);
        $account = rawurlencode(str_replace(':', ' ', (string) $user->username));

        return "otpauth://totp/{$issuer}:{$account}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=" . self::PERIOD;
    }
};
