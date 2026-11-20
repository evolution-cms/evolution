<?php

use EvolutionCMS\Console\MigrateStatusCommand;
use EvolutionCMS\Support\SharedMigrationRepository;
use EvolutionCMS\Support\SharedMigrator;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;

/*
 * The installer logs migrations in migrations_install, artisan in migrations, and both run the
 * migrations of every package. sGallery's table was then created a second time: by make:site
 * after a web install, or by the installer's upgrade mode after make:site.
 */

const SHARED_GALLERY = '2022_05_15_185152_create_s_gallery_table';
const SHARED_TASKS = '2025_10_15_000000_create_task_tables';

/** A migration that creates a table and cannot run twice, as sGallery's. */
function sharedMigrationFile(string $folder, string $name, string $table): void
{
    file_put_contents($folder . '/' . $name . '.php', <<<PHP
<?php
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        Capsule::connection()->getSchemaBuilder()->create('$table', function (Blueprint \$table) {
            \$table->increments('id');
        });
    }

    public function down(): void
    {
        Capsule::connection()->getSchemaBuilder()->drop('$table');
    }
};
PHP);
}

beforeEach(function () {
    $capsule = new Capsule();
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => 'evo_']);
    $capsule->setAsGlobal();
    $this->connection = $capsule->getConnection();
    $this->resolver = new ConnectionResolver(['default' => $this->connection]);
    $this->resolver->setDefaultConnection('default');

    // sGallery, which both sides run, and a package only artisan installed.
    $this->root = sys_get_temp_dir() . '/evo-shared-migrations-' . bin2hex(random_bytes(6));
    $this->gallery = $this->root . '/sgallery';
    $this->tasks = $this->root . '/stask';
    mkdir($this->gallery, 0777, true);
    mkdir($this->tasks, 0777, true);
    sharedMigrationFile($this->gallery, SHARED_GALLERY, 's_galleries');
    sharedMigrationFile($this->tasks, SHARED_TASKS, 's_tasks');
});

afterEach(function () {
    (new Filesystem())->deleteDirectory($this->root);
});

function sharedRepository(object $test, string $table): SharedMigrationRepository
{
    $repository = new SharedMigrationRepository($test->resolver, $table);
    if (!$repository->repositoryExists()) {
        $repository->createRepository();
    }

    return $repository;
}

function sharedMigrator(object $test, string $table): SharedMigrator
{
    return new SharedMigrator(sharedRepository($test, $table), $test->resolver, new Filesystem(), new Dispatcher());
}

function sharedLog(object $test, string $table): array
{
    return $test->connection->table($table)->orderBy('migration')->pluck('migration')->all();
}

test('a migration the installer ran is not run again by artisan', function () {
    sharedMigrator($this, 'migrations_install')->run([$this->gallery]);
    $artisan = sharedMigrator($this, 'migrations');

    expect($artisan->run([$this->gallery]))->toBe([])
        ->and(sharedLog($this, 'migrations'))->toBe([])
        // getRan() keeps to its own log, as Laravel expects of it
        ->and($artisan->getRepository()->getRan())->toBe([])
        ->and($artisan->getRepository()->ranElsewhere())->toBe([SHARED_GALLERY => 1]);
});

test('a migration artisan ran is not run again by the installer upgrade', function () {
    sharedMigrator($this, 'migrations')->run([$this->gallery]);

    expect(sharedMigrator($this, 'migrations_install')->run([$this->gallery]))->toBe([])
        ->and(sharedLog($this, 'migrations_install'))->toBe([]);
});

test('a migration neither side ran runs once, logged by the side that ran it', function () {
    sharedRepository($this, 'migrations_install');

    expect(sharedMigrator($this, 'migrations')->run([$this->gallery]))->toHaveCount(1)
        ->and($this->connection->getSchemaBuilder()->hasTable('s_galleries'))->toBeTrue()
        ->and(sharedLog($this, 'migrations'))->toBe([SHARED_GALLERY])
        ->and(sharedLog($this, 'migrations_install'))->toBe([]);
});

test('a reset undoes only its own log, and the next migrate runs only what it undid', function () {
    sharedMigrator($this, 'migrations_install')->run([$this->gallery]);
    sharedMigrator($this, 'migrations')->run([$this->gallery, $this->tasks]);
    $schema = $this->connection->getSchemaBuilder();

    sharedMigrator($this, 'migrations')->reset([$this->gallery, $this->tasks]);

    // The installer's migration and its table stay; artisan's own is undone.
    expect($schema->hasTable('s_galleries'))->toBeTrue()
        ->and($schema->hasTable('s_tasks'))->toBeFalse()
        ->and(sharedLog($this, 'migrations'))->toBe([])
        ->and(sharedLog($this, 'migrations_install'))->toBe([SHARED_GALLERY]);

    $ran = sharedMigrator($this, 'migrations')->run([$this->gallery, $this->tasks]);

    expect(array_map('basename', $ran))->toBe([SHARED_TASKS . '.php'])
        ->and($schema->hasTable('s_tasks'))->toBeTrue()
        ->and(sharedLog($this, 'migrations'))->toBe([SHARED_TASKS]);
});

test('migrate:status shows a migration of the other log as run, with that log', function () {
    sharedMigrator($this, 'migrations_install')->run([$this->gallery]);
    $migrator = sharedMigrator($this, 'migrations');
    $command = new class($migrator, [$this->gallery, $this->tasks]) extends MigrateStatusCommand {
        public function __construct($migrator, private array $folders)
        {
            parent::__construct($migrator);
        }

        protected function getAllMigrationFiles()
        {
            return $this->migrator->getMigrationFiles($this->folders);
        }

        public function statusFor(): array
        {
            $repository = $this->migrator->getRepository();

            return $this->getStatusFor($repository->getRan(), $repository->getMigrationBatches())->all();
        }
    };

    $status = array_values(array_map(fn (array $row) => [$row[0], strip_tags($row[1])], $command->statusFor()));

    expect($status)->toBe([
        [SHARED_GALLERY, '[migrations_install 1] Ran'],
        [SHARED_TASKS, 'Pending'],
    ]);
});

test('without the other table only its own log counts', function () {
    $artisan = sharedRepository($this, 'migrations');
    $artisan->log('2026_01_01_000000_own', 1);

    expect($artisan->ranElsewhere())->toBe([])
        ->and($artisan->counterpartTable())->toBe('migrations_install');
});

test('a log of another name has no counterpart', function () {
    $repository = sharedRepository($this, 'package_migrations');
    sharedRepository($this, 'migrations')->log('2026_01_01_000000_elsewhere', 1);

    expect($repository->counterpartTable())->toBeNull()
        ->and($repository->ranElsewhere())->toBe([]);
});

test('the application registers the shared log, the migrator and the status command', function () {
    $config = require dirname(__DIR__, 3) . '/config/app.php';
    $artisan = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Providers/ArtisanServiceProvider.php');

    expect($config['providers']['Console_Migration'] ?? null)->toBe(\EvolutionCMS\Providers\MigrationServiceProvider::class)
        ->and($artisan)->toContain('use EvolutionCMS\Console\MigrateStatusCommand;');

    $app = new \Illuminate\Container\Container();
    $app->instance('config', new \Illuminate\Config\Repository(['database' => ['migrations' => 'migrations_install']]));
    $app->instance('db', $this->resolver);
    $app->instance('files', new Filesystem());
    $app->instance('events', new Dispatcher());
    (new \EvolutionCMS\Providers\MigrationServiceProvider($app))->register();

    expect($app->make('migration.repository'))->toBeInstanceOf(SharedMigrationRepository::class)
        ->and($app->make('migration.repository')->counterpartTable())->toBe('migrations')
        ->and($app->make('migrator'))->toBeInstanceOf(SharedMigrator::class);
});
