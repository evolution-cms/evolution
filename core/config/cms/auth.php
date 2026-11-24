<?php return [
    /*
    |--------------------------------------------------------------------------
    | Login pipeline
    |--------------------------------------------------------------------------
    |
    | Ordered classes run around each way into a session. Empty by default: the
    | pipeline changes nothing until a site or an extra adds pipes to it.
    |
    | Keys are the entry points — 'login', 'loginById', 'hashLogin' — plus '*',
    | which runs for all of them. A second factor belongs under '*': listed only
    | under 'login' it is bypassed by a password recovery link or remember-me.
    | A flat list is treated as '*'.
    |
    | Override per key from core/custom/config/cms/auth/pipeline.php.
    |
    |     '*' => [\EvolutionCMS\Auth\Pipes\EnsureNotThrottled::class],
    |
    */
    'pipeline' => [],

    /*
    |--------------------------------------------------------------------------
    | Login rate limiting
    |--------------------------------------------------------------------------
    |
    | Used by the EnsureNotThrottled pipe: how many attempts per username and IP
    | are allowed, and for how many seconds the counter is kept.
    |
    */
    'throttle' => [
        'attempts' => 5,
        'decay' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Password recovery rate limiting
    |--------------------------------------------------------------------------
    |
    | The "Forgot your password?" form of the manager login screen. 'attempts'
    | requests per client address are answered within 'decay' seconds, whatever
    | address they ask for; at most 'mails' recovery mails go to one account
    | within 'mail_decay' seconds, wherever the requests come from.
    |
    | The mail is sent after the reply. A server that cannot release the client
    | before that (Apache mod_php; PHP-FPM, FrankenPHP and LiteSpeed can) keeps
    | the connection of every answered request open for 'hold' seconds, so its
    | length does not show whether a mail was sent. Raise it if your mail server
    | takes longer to accept a message.
    |
    */
    'recovery' => [
        'attempts' => 5,
        'decay' => 900,
        'mails' => 3,
        'mail_decay' => 3600,
        'hold' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-factor authentication
    |--------------------------------------------------------------------------
    |
    | Which method applies is a system setting (Security) and can be overridden
    | per user. A site locked out by a broken method is opened again with
    | 'two_factor_method' => 'off' in core/custom/config/cms/settings.php and
    | a cleared cache (php core/artisan cache:clear-full).
    |
    | 'key': any long random text; with it, the stored data (secrets of the
    | authenticator apps) is encrypted. Keep it outside the database and never
    | lose it: without it nobody can use what they have set up.
    |
    | 'redirect': a website login that needs the second factor takes a browser
    | to the page that asks for it. false leaves that to the login form, which
    | gets a ServiceActionException with the code TwoFactor::PENDING.
    |
    | Override per key, e.g. core/custom/config/cms/auth/two_factor/key.php.
    |
    */
    'two_factor' => [
        'key' => null,
        'redirect' => true,
    ],
];
