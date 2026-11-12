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
];
