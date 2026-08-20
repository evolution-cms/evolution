<?php

use EvolutionCMS\Models\EventLog;

it('formats stored event timestamps in the IANA site timezone without a legacy offset', function () {
    $timestamp = (new DateTimeImmutable(
        '2026-07-26 21:52:00',
        new DateTimeZone('UTC')
    ))->getTimestamp();

    expect(EventLog::formatStoredTimestamp($timestamp, 'UTC', 'Y-m-d H:i:s'))
        ->toBe('2026-07-26 21:52:00')
        ->and(EventLog::formatStoredTimestamp($timestamp, 'Europe/Kyiv', 'Y-m-d H:i:s'))
        ->toBe('2026-07-27 00:52:00')
        ->and(EventLog::formatStoredTimestamp(
            new DateTimeImmutable('@' . $timestamp),
            'Europe/Kyiv',
            'Y-m-d H:i:s'
        ))
        ->toBe('2026-07-27 00:52:00')
        ->and(EventLog::formatStoredTimestamp(
            '2026-07-26 21:52:00',
            'Europe/Kyiv',
            'Y-m-d H:i:s'
        ))
        ->toBe('-')
        ->not->toContain('1970-01-01');
});

it('escapes authenticated usernames and leaves system fallback to the localized view', function () {
    $managerEvent = new EventLog();
    $managerEvent->setRawAttributes(['list_username' => 'admin']);
    $unsafeEvent = new EventLog();
    $unsafeEvent->setRawAttributes(['list_username' => '<admin>']);
    $systemEvent = new EventLog();
    $systemEvent->setRawAttributes(['list_username' => null]);

    expect($managerEvent->list_username)
        ->toBe('admin')
        ->and($unsafeEvent->list_username)
        ->toBe('&lt;admin&gt;')
        ->and($systemEvent->list_username)
        ->toBe('');
});

it('uses the same timezone presenter and universal user join in list and details', function () {
    $root = dirname(__DIR__, 4);
    $list = file_get_contents($root . '/manager/views/page/eventlog.blade.php');
    $details = file_get_contents($root . '/manager/views/page/eventlog_details.blade.php');

    expect($list)
        ->toContain("'event_log.createdon as list_created_at'")
        ->toContain("'users.username as list_username'")
        ->toContain("->leftJoin('users', 'users.id', '=', 'event_log.user')")
        ->toContain("ManagerTheme::getLexicon('eventlog_system_user')")
        ->toContain('"type,list_source,list_created_at,eventid,list_username"')
        ->not->toContain("'event_log.usertype', '=', \\DB::raw(1)")
        ->not->toContain('||date:')
        ->and($details)
        ->toContain('EventLog::formatStoredTimestamp(')
        ->toContain("getRawOriginal('createdon')")
        ->toContain("getConfig('site_timezone')")
        ->toContain("ManagerTheme::getLexicon('eventlog_system_user')");
});

it('records manager or web actors and uses System only when neither is authenticated', function () {
    $core = file_get_contents(dirname(__DIR__, 3) . '/src/Core.php');

    $managerLookup = strpos($core, "\$this->getLoginUserID('mgr')");
    $webLookup = strpos($core, "\$this->getLoginUserID('web')");
    $systemFallback = strpos($core, '$LoginUserID = 0;', $webLookup);

    expect($managerLookup)
        ->not->toBeFalse()
        ->and($webLookup)
        ->not->toBeFalse()
        ->and($systemFallback)
        ->not->toBeFalse()
        ->and($managerLookup)
        ->toBeLessThan($webLookup)
        ->and($webLookup)
        ->toBeLessThan($systemFallback)
        ->and($core)
        ->toContain('$userType = EventLog::USER_MGR;')
        ->toContain('$userType = EventLog::USER_WEB;')
        ->toContain('// Prefer a verified manager, then a verified web user; only unauthenticated work stays actorless.');
});

it('provides a localized system actor label in every manager locale', function () {
    $langRoot = dirname(__DIR__, 3) . '/lang';

    foreach (glob($langRoot . '/*/global.php') as $file) {
        $_lang = [];
        include $file;

        expect($_lang)
            ->toHaveKey('eventlog_system_user');
    }

    $_lang = [];
    include $langRoot . '/en/global.php';
    expect($_lang['eventlog_system_user'])->toBe('System');

    $_lang = [];
    include $langRoot . '/uk/global.php';
    expect($_lang['eventlog_system_user'])->toBe('Система');
});
