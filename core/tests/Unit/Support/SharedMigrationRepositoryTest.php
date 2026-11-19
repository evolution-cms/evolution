<?php

use EvolutionCMS\Support\SharedMigrationRepository;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;

/*
 * The installer logs migrations in migrations_install, artisan in migrations, and both run the
 * migrations of every package. sGallery's table was then created a second time: by make:site
 * after a web install, or by the installer's upgrade mode after make:site.
 */

beforeEach(function () {
    $capsule = new Capsule();
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => 'evo_']);
    $this->connection = $capsule->getConnection();
    $this->resolver = new ConnectionResolver(['default' => $this->connection]);
    $this->resolver->setDefaultConnection('default');

    $this->folder = sys_get_temp_dir() . '/evo-shared-migrations-' . bin2hex(random_bytes(6));
    mkdir($this->folder);
    // A package migration that cannot run twice, as sGallery's create migration.
    file_put_contents($this->folder . '/2022_05_15_185152_create_s_gallery_table.php', <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        $this->schema()->create('s_galleries', function (Blueprint $table) {
            $table->increments('id');
        });
    }

    private function schema()
    {
        return \Illuminate\Database\Capsule\Manager::connection()->getSchemaBuilder();
    }
};
PHP);
    $capsule->setAsGlobal();
});

afterEach(function () {
    (new Filesystem())->deleteDirectory($this->folder);
});

function sharedRepository(object $test, string $table): SharedMigrationRepository
{
    $repository = new SharedMigrationRepository($test->resolver, $table);
    if (!$repository->repositoryExists()) {
        $repository->createRepository();
    }

    return $repository;
}

function sharedMigrator(object $test, SharedMigrationRepository $repository): Migrator
{
    return new Migrator($repository, $test->resolver, new Filesystem(), new Dispatcher());
}

test('a migration the installer ran counts for artisan', function () {
    $installer = sharedRepository($this, 'migrations_install');
    sharedMigrator($this, $installer)->run([$this->folder]);
    $artisan = sharedRepository($this, 'migrations');

    $ran = sharedMigrator($this, $artisan)->run([$this->folder]);

    expect($ran)->toBe([])
        ->and($artisan->getRan())->toBe(['2022_05_15_185152_create_s_gallery_table'])
        // artisan logs nothing of its own, so its rollback leaves the installer's migration alone
        ->and($artisan->getLast())->toBe([]);
});

test('a migration artisan ran counts for the installer upgrade', function () {
    $artisan = sharedRepository($this, 'migrations');
    sharedMigrator($this, $artisan)->run([$this->folder]);
    $installer = sharedRepository($this, 'migrations_install');

    expect(sharedMigrator($this, $installer)->run([$this->folder]))->toBe([])
        ->and($this->connection->table('migrations_install')->count())->toBe(0);
});

test('a migration neither side ran still runs once, logged by the side that ran it', function () {
    sharedRepository($this, 'migrations_install');
    $artisan = sharedRepository($this, 'migrations');

    $ran = sharedMigrator($this, $artisan)->run([$this->folder]);

    expect($ran)->toHaveCount(1)
        ->and($this->connection->getSchemaBuilder()->hasTable('s_galleries'))->toBeTrue()
        ->and($this->connection->table('migrations')->pluck('migration')->all())->toBe(['2022_05_15_185152_create_s_gallery_table'])
        ->and($this->connection->table('migrations_install')->count())->toBe(0);
});

test('without the other table only its own log counts', function () {
    $artisan = sharedRepository($this, 'migrations');
    $artisan->log('2026_01_01_000000_own', 1);

    expect($artisan->getRan())->toBe(['2026_01_01_000000_own'])
        ->and($artisan->counterpartTable())->toBe('migrations_install');
});

test('a log of another name has no counterpart', function () {
    $repository = sharedRepository($this, 'package_migrations');
    sharedRepository($this, 'migrations')->log('2026_01_01_000000_elsewhere', 1);

    expect($repository->counterpartTable())->toBeNull()
        ->and($repository->getRan())->toBe([]);
});

test('the application registers the shared log for both sides', function () {
    $config = require dirname(__DIR__, 3) . '/config/app.php';

    expect($config['providers']['Console_Migration'] ?? null)->toBe(\EvolutionCMS\Providers\MigrationServiceProvider::class);

    $app = new \Illuminate\Container\Container();
    $app->instance('config', new \Illuminate\Config\Repository(['database' => ['migrations' => 'migrations_install']]));
    $app->instance('db', $this->resolver);
    (new \EvolutionCMS\Providers\MigrationServiceProvider($app))->register();

    expect($app->make('migration.repository'))->toBeInstanceOf(SharedMigrationRepository::class)
        ->and($app->make('migration.repository')->counterpartTable())->toBe('migrations');
});
