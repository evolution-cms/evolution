<?php

use EvolutionCMS\Models\ActiveUser;
use EvolutionCMS\Models\ActiveUserSession;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

beforeEach(function () {
    $capsule = new Capsule();
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    Model::setConnectionResolver($capsule->getDatabaseManager());

    $schema = $capsule->getConnection()->getSchemaBuilder();
    $schema->create('active_user_sessions', function (Blueprint $table) {
        $table->string('sid', 128)->primary();
        $table->integer('internalKey')->default(0);
        $table->integer('lasthit')->default(0);
        $table->string('ip')->default('');
    });
    $schema->create('active_users', function (Blueprint $table) {
        $table->string('sid', 128)->primary();
        $table->integer('internalKey')->default(0);
        $table->string('username', 50)->default('');
        $table->integer('lasthit')->default(0);
        $table->string('action', 10)->default('');
        $table->integer('id')->nullable();
    });
});

afterEach(function () {
    Capsule::connection()->disconnect();
});

function onSettings(int $userId, string $username, string $sid, int $lasthit, ?int $sessionHit): void
{
    ActiveUser::query()->insert(['sid' => $sid, 'internalKey' => $userId, 'username' => $username, 'lasthit' => $lasthit, 'action' => '17', 'id' => null]);
    if ($sessionHit !== null) {
        ActiveUserSession::query()->insert(['sid' => $sid, 'internalKey' => $userId, 'lasthit' => $sessionHit, 'ip' => '127.0.0.1']);
    }
}

test('a manager who left the settings open no longer holds them once the session is gone', function () {
    $now = 1_800_000_000;
    $since = ActiveUserSession::validSince($now, 20);
    // left 2 h ago, session already cleaned up
    onSettings(2, 'gone', 'sid-gone', $now - 7200, null);
    // last hit long ago, session row not cleaned yet but past the timeout
    onSettings(3, 'expired', 'sid-expired', $now - 7200, $now - 21 * 60);

    expect(ActiveUser::locked(17, null, 1, $since)->first())->toBeNull();
});

test('a signed-in manager on the settings page still holds them', function () {
    $now = 1_800_000_000;
    // opened the settings 30 min ago and kept the manager open: keep-alive refreshed the session
    onSettings(2, 'editor', 'sid-editor', $now - 1800, $now - 60);

    expect(ActiveUser::locked(17, null, 1, ActiveUserSession::validSince($now, 20))->first()?->username)->toBe('editor')
        ->and(ActiveUser::locked(17, null, 2, ActiveUserSession::validSince($now, 20))->first())->toBeNull();
});

test('the session window is session_timeout minutes and never below two', function () {
    expect(ActiveUserSession::validSince(10_000, 20))->toBe(10_000 - 1200)
        ->and(ActiveUserSession::validSince(10_000, 0))->toBe(10_000 - 120)
        ->and(ActiveUserSession::validSince(10_000, 1))->toBe(10_000 - 120);
});

test('the alert page waits for load, so a manager frame replaces alert() before it fires', function () {
    $source = file_get_contents(dirname(__DIR__, 3) . '/src/Core.php');

    expect($source)->toContain("window.addEventListener('load', function () { window.setTimeout(__alertQuit, 100); });")
        ->and($source)->not->toContain("window.setTimeout('__alertQuit();',100);");
});

test('the dashboard does not send a new install to settings another manager holds', function () {
    $view = file_get_contents(dirname(__DIR__, 4) . '/manager/views/page/2.blade.php');

    expect($view)->toMatch('/settings_version.*\n.*!\\\\EvolutionCMS\\\\Models\\\\ActiveUser::locked\(17\)->exists\(\)/');
});
