<?php

use EvolutionCMS\Core;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

/**
 * users 1..5 with one attributes row each, except 5 which has none:
 *  1 clear, 2 blocked, 3 blocked until tomorrow, 4 blocked after yesterday, 5 no attributes row
 */
beforeEach(function () {
    $capsule = new Capsule();
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => 'evo_']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    Model::setConnectionResolver($capsule->getDatabaseManager());

    $schema = $capsule->getConnection()->getSchemaBuilder();
    $schema->create('users', function (Blueprint $table) {
        $table->increments('id');
        $table->string('username')->default('');
    });
    $schema->create('user_attributes', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('internalKey')->default(0);
        $table->integer('blocked')->default(0);
        $table->integer('blockeduntil')->default(0);
        $table->integer('blockedafter')->default(0);
    });
    foreach ([1, 2, 3, 4, 5] as $id) {
        Capsule::table('users')->insert(['id' => $id, 'username' => "user$id"]);
    }
    foreach ([
        ['internalKey' => 1],
        ['internalKey' => 2, 'blocked' => 1],
        ['internalKey' => 3, 'blockeduntil' => time() + 86400],
        ['internalKey' => 4, 'blockedafter' => time() - 86400],
    ] as $row) {
        Capsule::table('user_attributes')->insert($row);
    }

    $this->core = (new ReflectionClass(Core::class))->newInstanceWithoutConstructor();
});

afterEach(function () {
    Capsule::connection()->disconnect();
    \Illuminate\Container\Container::setInstance(null);
});

test('checkAccess admits a clear user and a user without an attributes row', function () {
    expect($this->core->checkAccess(1))->toBeTrue()
        ->and($this->core->checkAccess(5))->toBeTrue();
});

test('checkAccess refuses blocked, temporarily blocked and expired users, and unknown ids', function () {
    expect($this->core->checkAccess(2))->toBeFalse()
        ->and($this->core->checkAccess(3))->toBeFalse()
        ->and($this->core->checkAccess(4))->toBeFalse()
        ->and($this->core->checkAccess(99))->toBeFalse();
});

test('checkAccess is a single query', function () {
    Capsule::connection()->enableQueryLog();
    $this->core->checkAccess(1);

    expect(Capsule::connection()->getQueryLog())->toHaveCount(1);
});
