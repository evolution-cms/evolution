<?php

namespace EvolutionCMS\Support;

/**
 * The system settings that live on the manager's "Security" tab.
 *
 * These control the access-control system itself (use_udperms, udperms_allowroot),
 * server-side code execution (allow_eval, enable_bindings, safe_functions_at_eval)
 * and the login/anti-automation hardening. Changing any of them can switch off a
 * protection for the whole site, so they are gated behind the dedicated
 * `security_settings` permission on top of the general `settings` right: a manager
 * with `settings` but not `security_settings` keeps every other system setting but
 * may not touch these.
 *
 * This list is the single source of truth. The settings form hides the tab without
 * the permission, and save_settings skips these keys so a crafted POST cannot set
 * them either; a test cross-checks it against the keys the Security tab renders.
 */
final class SecuritySettings
{
    /** @var string[] */
    public const GATED = [
        'use_udperms',
        'udperms_allowroot',
        'allow_eval',
        'safe_functions_at_eval',
        'enable_bindings',
        'check_files_onlogin',
        'validate_referer',
        'error_reporting',
        'send_errormail',
        'use_captcha',
        'captcha_words',
        'captcha_words_default',
        'reload_captcha_words',
        'failed_login_attempts',
        'blocked_minutes',
        'pwd_hash_algo',
        'pwd_repair_minutes',
    ];

    /**
     * Whether $key is a security setting that needs the `security_settings` permission. Letter
     * case does not count: the database matches setting names case-insensitively.
     */
    public static function isGated(string $key): bool
    {
        return in_array(strtolower($key), self::GATED, true);
    }
}
