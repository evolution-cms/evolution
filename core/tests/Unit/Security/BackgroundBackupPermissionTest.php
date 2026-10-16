<?php

/*
 * The synchronous snapshot in the Backup manager needs bk_manager. The optional sTask worker must
 * need the same: sTask's own endpoint creates and runs a task of any active worker for any manager,
 * and the task later runs without a session. Restores stay synchronous and keep their own guard
 * (BackendCodePermissions, see BackupManagerShellTest).
 */

use EvolutionCMS\Services\BackgroundDatabaseBackup;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Facade;

afterEach(function () {
    Facade::clearResolvedInstances();
});

function bootBackgroundBackupUsers(): void
{
    $capsule = new Capsule();
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    Model::setConnectionResolver($capsule->getDatabaseManager());

    $schema = $capsule->getConnection()->getSchemaBuilder();
    $schema->create('users', function (Blueprint $table) {
        $table->increments('id');
        $table->string('username');
    });
    $schema->create('user_attributes', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('internalKey');
        $table->integer('role')->default(0);
        $table->integer('blocked')->default(0);
        $table->integer('blockeduntil')->default(0);
        $table->integer('blockedafter')->default(0);
    });
    $schema->create('role_permissions', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('role_id');
        $table->string('permission');
    });

    $db = $capsule->getConnection();
    $db->table('users')->insert([
        ['id' => 1, 'username' => 'admin'],
        ['id' => 2, 'username' => 'editor'],
        ['id' => 3, 'username' => 'blocked'],
        ['id' => 4, 'username' => 'suspended'],
        ['id' => 5, 'username' => 'expired'],
        ['id' => 6, 'username' => 'no-attributes'],
    ]);
    foreach ([
        ['internalKey' => 1, 'role' => 1],
        ['internalKey' => 2, 'role' => 2],
        ['internalKey' => 3, 'role' => 1, 'blocked' => 1],
        ['internalKey' => 4, 'role' => 1, 'blockeduntil' => time() + 3600],
        ['internalKey' => 5, 'role' => 1, 'blockedafter' => time() - 3600],
    ] as $attributes) {
        $db->table('user_attributes')->insert($attributes);
    }
    $db->table('role_permissions')->insert([
        ['role_id' => 1, 'permission' => 'bk_manager'],
        ['role_id' => 2, 'permission' => 'stask'],
        ['role_id' => 2, 'permission' => 'save_plugin'],
    ]);
}

it('lets only an active account whose role holds bk_manager start a background backup', function () {
    bootBackgroundBackupUsers();

    expect(BackgroundDatabaseBackup::userMayBackup(1))->toBeTrue()
        ->and(BackgroundDatabaseBackup::userMayBackup(2))->toBeFalse()
        ->and(BackgroundDatabaseBackup::userMayBackup(3))->toBeFalse()
        ->and(BackgroundDatabaseBackup::userMayBackup(4))->toBeFalse()
        ->and(BackgroundDatabaseBackup::userMayBackup(5))->toBeFalse()
        ->and(BackgroundDatabaseBackup::userMayBackup(6))->toBeFalse()
        ->and(BackgroundDatabaseBackup::userMayBackup(99))->toBeFalse()
        ->and(BackgroundDatabaseBackup::userMayBackup(0))->toBeFalse();
});

function runDatabaseBackupWorkerHarness(string $mode, string ...$arguments): array
{
    $output = evoRunPhp(dirname(__DIR__, 2) . '/Fixtures/database-backup-worker-harness.php', [$mode, ...$arguments], $status, false);
    expect($status)->toBe(0, $output);

    return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
}

it('refuses to create a backup task through sTask without bk_manager, and logs it', function () {
    $refused = runDatabaseBackupWorkerHarness('create', '2', '0');
    expect($refused['passed'])->toBeFalse()
        ->and($refused['error'])->toBe('global.error_no_privileges')
        ->and($refused['events'][0] ?? '')->toContain('bk_manager');

    $allowed = runDatabaseBackupWorkerHarness('create', '1', '1');
    expect($allowed['passed'])->toBeTrue()->and($allowed['events'])->toBe([]);
});

it('refuses to run a queued backup task started by an account without bk_manager', function () {
    foreach (['2', '0', '99'] as $userId) {
        $refused = runDatabaseBackupWorkerHarness('run', $userId);
        expect($refused['passed'])->toBeFalse()
            ->and($refused['error'])->toBe('global.error_no_privileges')
            ->and($refused['events'][0] ?? '')->toContain('refused');
    }

    $allowed = runDatabaseBackupWorkerHarness('run', '1');
    expect($allowed['passed'])->toBeTrue()->and($allowed['events'])->toBe([]);
});

it('runs a scheduled backup as the manager who allowed the schedule', function () {
    $scheduled = runDatabaseBackupWorkerHarness('schedule', '1');

    expect($scheduled['passed'])->toBeTrue()
        ->and($scheduled['started_by'])->toBe(1)
        ->and($scheduled['scheduled_flag'])->toBeTrue()
        ->and($scheduled['events'])->toBe([]);
});

it('refuses a queued scheduled task once its schedule is no longer allowed by the account it runs as', function () {
    $revoked = runDatabaseBackupWorkerHarness('run', '1', '1', '0');
    expect($revoked['passed'])->toBeFalse()
        ->and($revoked['error'])->toBe('global.error_no_privileges')
        ->and($revoked['events'][0] ?? '')->toContain('no longer allowed');

    $stillAllowed = runDatabaseBackupWorkerHarness('run', '1', '1', '1');
    expect($stillAllowed['passed'])->toBeTrue()->and($stillAllowed['events'])->toBe([]);

    // A manual task is not tied to the schedule.
    $manual = runDatabaseBackupWorkerHarness('run', '1', '0', '0');
    expect($manual['passed'])->toBeTrue();
});

it('refuses a scheduled backup nobody with bk_manager allowed, logging it once over many ticks', function (string $authorizedBy, string $reason) {
    $refused = runDatabaseBackupWorkerHarness('schedule', $authorizedBy);

    expect($refused['passed'])->toBeFalse()
        ->and($refused['error'])->toBe('global.error_no_privileges')
        ->and($refused['events'])->toHaveCount(1)
        ->and($refused['events'][0])->toContain($reason);
})->with([
    'never allowed' => ['0', 'no manager'],
    'allowed by an account without bk_manager' => ['2', 'user 2'],
    'allowed by an account blocked since' => ['3', 'user 3'],
]);

it('records the saving manager as the one who allows the schedule only when they hold bk_manager', function (array $arguments, int $expected, ?string $logged) {
    $saved = runDatabaseBackupWorkerHarness('authorize', ...$arguments);

    expect($saved['passed'])->toBeTrue($saved['error'] ?? '')
        ->and($saved['authorized_by'])->toBe($expected);
    if ($logged === null) {
        expect($saved['events'])->toBe([]);
    } else {
        expect($saved['events'])->toHaveCount(1)->and($saved['events'][0])->toContain($logged);
    }
})->with([
    // session user, has bk_manager, schedule on, stored value, posted value
    'bk_manager holder turns the schedule on' => [['1', '1', '1', '0', '0'], 1, 'now allowed by user 1'],
    'manager without bk_manager re-saves an allowed schedule' => [['2', '0', '1', '1', '1'], 0, 'without the bk_manager permission'],
    'a posted value is never trusted' => [['2', '0', '1', '0', '1'], 0, null],
    'bk_manager holder turns the schedule off' => [['1', '1', '0', '1', '1'], 0, 'schedule off'],
    'a save without a manager session keeps the stored value' => [['0', '0', '1', '1', '5'], 1, null],
    'a save without a manager session cannot grant' => [['0', '0', '1', '0', '1'], 0, null],
    'other workers are left alone' => [['2', '0', '1', '0', '1', 'OtherWorker'], 1, null],
]);
