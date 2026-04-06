<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Tests\Unit\Controllers\SearchTestHarness;

beforeEach(function () {
    $this->capsule = new Capsule();
    $this->capsule->addConnection([
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);
    $this->capsule->setAsGlobal();
    $this->capsule->bootEloquent();

    $this->capsule->schema()->create('search_test_items', function (Blueprint $table) {
        $table->increments('id');
        $table->string('title');
        $table->string('description')->nullable();
    });

    $this->capsule->table('search_test_items')->insert([
        ['title' => 'Evolution CMS Install Success', 'description' => 'Welcome screen'],
        ['title' => 'Another Page', 'description' => 'CMS helper text'],
        ['title' => 'Plain page', 'description' => 'No match here'],
    ]);

    $this->controller = (new ReflectionClass(SearchTestHarness::class))->newInstanceWithoutConstructor();
});

afterEach(function () {
    Capsule::connection()->disconnect();
    // Reset the global IoC container so it doesn't leak a plain Container
    // (created by Container::getInstance() ??= new static during schema ops)
    // into subsequent tests that rely on Core::getInstance().
    \Illuminate\Container\Container::setInstance(null);
});

test('sqlite text searches remain case-insensitive for manager search queries', function () {
    $query = Capsule::table('search_test_items')->select('id', 'title', 'description')
        ->where(function ($nested) {
            $this->controller->exposeApplyContainsConditionGroup($nested, ['title', 'description'], 'evolution');
        });

    $titles = $query->pluck('title')->all();

    expect($titles)->toBe(['Evolution CMS Install Success']);
});

test('sqlite case-insensitive search also matches secondary text columns', function () {
    $query = Capsule::table('search_test_items')->select('id', 'title', 'description')
        ->where(function ($nested) {
            $this->controller->exposeApplyContainsConditionGroup($nested, ['title', 'description'], 'cms');
        });

    $titles = $query->pluck('title')->all();

    expect($titles)->toContain('Evolution CMS Install Success')
        ->and($titles)->toContain('Another Page')
        ->and($titles)->not()->toContain('Plain page');
});
