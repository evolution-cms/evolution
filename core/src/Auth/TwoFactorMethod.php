<?php namespace EvolutionCMS\Auth;

use EvolutionCMS\Models\User;

/**
 * A second sign-in step: TOTP, a code by e-mail, or whatever a plugin adds.
 *
 * Register one on the OnTwoFactorMethods event; it then appears in the system settings, in the
 * user form and on the user's own page:
 *
 *     $twoFactor->extend('sms', new class extends \EvolutionCMS\Auth\TwoFactorMethod { ... });
 *
 * A method that sends a code needs title(), begin() and verify() only.
 *
 * `$state` is the method's own JSON-serializable data about the user. What begin(), finishSetup()
 * and verify() return replaces it; verify() results are stored by compare-and-swap, so a code whose
 * use changes the state cannot be used twice, not even by two requests at once. Attempts are
 * counted and the account is locked by TwoFactor: a method only says whether an answer is right.
 *
 * @since 3.6.0
 */
abstract class TwoFactorMethod
{
    /** Name in the lists of methods, plain text. */
    abstract public function title(): string;

    /**
     * Check the answer of an enrolled user.
     *
     * @param string $response what the form posted as `two_factor_code`
     * @return array|null the new state, null when the answer is wrong
     */
    abstract public function verify(User $user, string $response, array $state, string $context): ?array;

    /** Does the user have to set something up (scan a QR code, register a device) before verify() can work? */
    public function needsSetup(User $user, array $state, string $context): bool
    {
        return false;
    }

    /** Data of a set-up in progress; kept in the session until finishSetup() accepts an answer. */
    public function beginSetup(User $user, string $context): array
    {
        return [];
    }

    /** HTML inside the set-up form. The form posts its answer as `two_factor_code`. */
    public function setupForm(User $user, array $setup, string $context): string
    {
        return $this->codeField();
    }

    /**
     * Check the first answer, which proves that the set-up works.
     *
     * @return array|null the state to store, null when the answer is wrong
     */
    public function finishSetup(User $user, string $response, array $setup, string $context): ?array
    {
        return $this->verify($user, $response, $setup, $context);
    }

    /**
     * Start a challenge: send the code, create a nonce.
     *
     * @return array the state with whatever verify() will need
     * @throws \EvolutionCMS\Exceptions\ServiceActionException with a message for the user when it cannot be started
     */
    public function begin(User $user, array $state, string $context): array
    {
        return $state;
    }

    /** May the user ask for another code, that is, for another begin()? */
    public function canResend(): bool
    {
        return false;
    }

    /** HTML inside the challenge form. The form posts its answer as `two_factor_code`. */
    public function form(User $user, array $state, string $context): string
    {
        return $this->codeField();
    }

    /** The state of a user whose set-up an administrator or the user takes back is deleted; undo the rest here. */
    public function forget(User $user, array $state): void
    {
    }

    protected function codeField(string $hint = ''): string
    {
        return ($hint === '' ? '' : '<p>' . e($hint) . '</p>')
            . '<label for="two_factor_code">' . e(__('global.two_factor_code')) . '</label>'
            . '<input type="text" class="form-control" name="two_factor_code" id="two_factor_code" inputmode="numeric"'
            . ' autocomplete="one-time-code" autocapitalize="off" spellcheck="false" required autofocus>';
    }
}
