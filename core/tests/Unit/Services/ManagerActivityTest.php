<?php

use EvolutionCMS\Services\ManagerActivity;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Facade;

function bootActivityDatabase(): Capsule
{
    $capsule = new Capsule();
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    $container = $capsule->getContainer();
    $container->instance('db', $capsule->getDatabaseManager());
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($container);
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
    $schema->create('active_user_locks', function (Blueprint $table) {
        $table->increments('id');
        $table->string('sid', 128)->default('');
        $table->integer('internalKey')->default(0);
        $table->integer('elementType')->default(0);
        $table->integer('elementId')->default(0);
        $table->integer('lasthit')->default(0);
    });

    return $capsule;
}

beforeEach(function () {
    ManagerActivity::reset();
    $this->capsule = bootActivityDatabase();
});

afterEach(function () {
    ManagerActivity::instance()->flush();
    ManagerActivity::reset();
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication(null);
    Capsule::connection()->disconnect();
    \Illuminate\Container\Container::setInstance(null);
});

test('nothing is written until flush', function () {
    $activity = ManagerActivity::instance();
    $activity->touchSession(7, 'sid-a', 1000, '10.0.0.1');
    $activity->setAction(7, 'sid-a', 'admin', 1000, 27, 42);
    $activity->lock(7, 42, 7, 'sid-a', 1000);

    expect($activity->hasPending())->toBeTrue()
        ->and(Capsule::table('active_user_sessions')->count())->toBe(0)
        ->and(Capsule::table('active_users')->count())->toBe(0)
        ->and(Capsule::table('active_user_locks')->count())->toBe(0);

    $activity->flush();

    expect($activity->hasPending())->toBeFalse()
        ->and(Capsule::table('active_user_sessions')->get()->map(fn ($r) => (array) $r)->all())
            ->toBe([['sid' => 'sid-a', 'internalKey' => 7, 'lasthit' => 1000, 'ip' => '10.0.0.1']])
        ->and((array) Capsule::table('active_users')->first())
            ->toBe(['sid' => 'sid-a', 'internalKey' => 7, 'username' => 'admin', 'lasthit' => 1000, 'action' => '27', 'id' => 42])
        ->and(Capsule::table('active_user_locks')->where('elementId', 42)->value('internalKey'))->toBe(7);
});

test('a session replaces the stale rows of the same user and of the same sid', function () {
    Capsule::table('active_user_sessions')->insert([
        ['sid' => 'old-of-user', 'internalKey' => 7, 'lasthit' => 1, 'ip' => ''],
        ['sid' => 'sid-a', 'internalKey' => 3, 'lasthit' => 1, 'ip' => ''],
        ['sid' => 'someone-else', 'internalKey' => 9, 'lasthit' => 1, 'ip' => ''],
    ]);

    ManagerActivity::instance()->touchSession(7, 'sid-a', 2000, '10.0.0.2');
    ManagerActivity::instance()->flush();

    expect(Capsule::table('active_user_sessions')->orderBy('sid')->pluck('internalKey', 'sid')->all())
        ->toBe(['sid-a' => 7, 'someone-else' => 9]);
});

test('an action keeps one active_users row per user and refreshes an existing lock in place', function () {
    Capsule::table('active_users')->insert(['sid' => 'old', 'internalKey' => 7, 'username' => 'admin', 'lasthit' => 1, 'action' => '3', 'id' => null]);
    Capsule::table('active_user_locks')->insert(['sid' => 'old', 'internalKey' => 7, 'elementType' => 7, 'elementId' => 42, 'lasthit' => 1]);

    $activity = ManagerActivity::instance();
    $activity->setAction(7, 'new', 'admin', 5000, 27, 42);
    $activity->lock(7, 42, 7, 'new', 5000);
    $activity->flush();

    expect(Capsule::table('active_users')->pluck('sid')->all())->toBe(['new'])
        ->and(Capsule::table('active_user_locks')->count())->toBe(1)
        ->and((array) Capsule::table('active_user_locks')->select('sid', 'lasthit')->first())->toBe(['sid' => 'new', 'lasthit' => 5000]);
});

test('flush runs queued jobs once, in order, and is a no-op afterwards', function () {
    $order = [];
    $activity = ManagerActivity::instance();
    $activity->defer(function () use (&$order) { $order[] = 'first'; });
    $activity->defer(function () use (&$order) { $order[] = 'second'; });

    $activity->flush();
    $activity->flush();

    expect($order)->toBe(['first', 'second']);

    $activity->defer(function () use (&$order) { $order[] = 'third'; });
    $activity->flush();

    expect($order)->toBe(['first', 'second', 'third']);
});

test('a failing job rolls the whole batch back and surfaces the error when attached', function () {
    $activity = ManagerActivity::instance();
    $activity->touchSession(7, 'sid-a', 1000, '10.0.0.1');
    $activity->defer(function () { throw new RuntimeException('boom'); });

    expect(fn () => $activity->flush())->toThrow(RuntimeException::class, 'boom')
        ->and(Capsule::table('active_user_sessions')->count())->toBe(0)
        ->and($activity->hasPending())->toBeFalse();
});

test('the manager hands its bookkeeping to the service and flushes after the response is sent', function () {
    $core = file_get_contents(dirname(__DIR__, 3) . '/src/Core.php');
    $theme = file_get_contents(dirname(__DIR__, 3) . '/src/ManagerTheme.php');

    expect($core)->toContain('ManagerActivity::instance()')
        ->and($core)->not->toContain("ActiveUserSession::where('sid', \$this->sid)->delete()")
        ->and($theme)->toContain('$response->send();')
        ->and(strpos($theme, 'ManagerActivity::instance()->flush('))->toBeGreaterThan(strpos($theme, '$response->send();'))
        ->and($theme)->not->toContain('->forceDelete()');
});
