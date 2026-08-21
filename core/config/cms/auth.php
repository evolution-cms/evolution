<?php

return [
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
];
