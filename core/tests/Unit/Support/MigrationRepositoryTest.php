<?php

use EvolutionCMS\Support\MigrationRepository;
use EvolutionCMS\Support\Migrator;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;

/*
 * Up to 3.5.9 the installer logged migrations in migrations_install and artisan in migrations, and
 * both ran the migrations of every package: sGallery's table was created a second time. From 3.5.10
 * both log in migrations; the old installer log is copied once and then left as it is.
 */

const MLOG_GALLERY = '2022_05_15_185152_create_s_gallery_table';
const MLOG_TASKS = '2025_10_15_000000_create_task_tables';
const MLOG_STUB = '2018_06_29_182342_create_site_content_table';

/** A migration that creates a table and cannot run twice, as sGallery's. */
function mlogMigrationFile(string $folder, string $name, string $table): void
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

    $this->root = sys_get_temp_dir() . '/evo-migration-log-' . bin2hex(random_bytes(6));
    $this->gallery = $this->root . '/sgallery';
    $this->tasks = $this->root . '/stask';
    mkdir($this->gallery, 0777, true);
    mkdir($this->tasks, 0777, true);
    mlogMigrationFile($this->gallery, MLOG_GALLERY, 's_galleries');
    mlogMigrationFile($this->tasks, MLOG_TASKS, 's_tasks');
});

afterEach(function () {
    (new Filesystem())->deleteDirectory($this->root);
});

/** The installer log a 3.5.9 site carries: rows and the tables they created. */
function mlogInstallerLog(object $test, array $rows): void
{
    $schema = $test->connection->getSchemaBuilder();
    $schema->create('migrations_install', function ($table) {
        $table->increments('id');
        $table->string('migration');
        $table->integer('batch');
    });
    foreach ($rows as $migration => $batch) {
        $test->connection->table('migrations_install')->insert(['migration' => $migration, 'batch' => $batch]);
    }
}

function mlogRepository(object $test, bool $installer = false): MigrationRepository
{
    $repository = new MigrationRepository($test->resolver, 'migrations', $installer);
    if (!$repository->repositoryExists()) {
        $repository->createRepository();
    }

    return $repository;
}

function mlogMigrator(object $test, bool $installer = false): Migrator
{
    return new Migrator(mlogRepository($test, $installer), $test->resolver, new Filesystem(), new Dispatcher());
}

/** @return array<string, int> */
function mlogLog(object $test, string $table = 'migrations'): array
{
    return $test->connection->table($table)->orderBy('migration')->pluck('batch', 'migration')
        ->map(fn ($batch) => (int) $batch)->all();
}

test('the installer log of an older release is copied into the baseline and left as it was', function () {
    mlogInstallerLog($this, [MLOG_STUB => 1, MLOG_GALLERY => 2]);
    $before = $this->connection->table('migrations_install')->orderBy('id')->get()->all();

    // No migrations table yet: a site the installer alone ever migrated.
    $repository = new MigrationRepository($this->resolver, 'migrations');

    expect($repository->repositoryExists())->toBeTrue()
        ->and(mlogLog($this))->toBe([MLOG_STUB => 0, MLOG_GALLERY => 0])
        ->and($this->connection->table('migrations_install')->orderBy('id')->get()->all())->toEqual($before);
});

test('copying is idempotent and keeps what migrations already holds', function () {
    // sGallery is in both logs (make:site after a web install), the stub twice (the old install seeder).
    mlogInstallerLog($this, [MLOG_STUB => 1, MLOG_GALLERY => 2]);
    $this->connection->table('migrations_install')->insert(['migration' => MLOG_STUB, 'batch' => 1]);
    $this->connection->getSchemaBuilder()->create('migrations', function ($table) {
        $table->increments('id');
        $table->string('migration');
        $table->integer('batch');
    });
    $this->connection->table('migrations')->insert(['migration' => MLOG_GALLERY, 'batch' => 3]);

    $first = new MigrationRepository($this->resolver, 'migrations');

    expect($first->importInstallerLog())->toBe(1)
        ->and((new MigrationRepository($this->resolver, 'migrations'))->importInstallerLog())->toBe(0)
        ->and(mlogLog($this))->toBe([MLOG_STUB => 0, MLOG_GALLERY => 3])
        ->and($this->connection->table('migrations')->count())->toBe(2);
});

test('a package migration only the old installer log holds is not run again', function () {
    mlogInstallerLog($this, [MLOG_GALLERY => 1]);
    $this->connection->getSchemaBuilder()->create('s_galleries', fn ($table) => $table->increments('id'));

    $ran = mlogMigrator($this)->run([$this->gallery, $this->tasks]);

    expect(array_map('basename', $ran))->toBe([MLOG_TASKS . '.php'])
        ->and(mlogLog($this))->toBe([MLOG_GALLERY => 0, MLOG_TASKS => 1]);
});

test('without an old installer log nothing is copied and no table is made', function () {
    $repository = new MigrationRepository($this->resolver, 'migrations');

    expect($repository->importInstallerLog())->toBe(0)
        ->and($repository->repositoryExists())->toBeFalse()
        ->and($this->connection->getSchemaBuilder()->hasTable('migrations'))->toBeFalse();
});

test('a log still named migrations_install copies nothing into itself', function () {
    mlogInstallerLog($this, [MLOG_STUB => 1]);

    expect((new MigrationRepository($this->resolver, 'migrations_install'))->importInstallerLog())->toBe(0)
        ->and(mlogLog($this, 'migrations_install'))->toBe([MLOG_STUB => 1]);
});

test('the installer logs in the baseline batch, artisan after it', function () {
    mlogMigrator($this, true)->run([$this->gallery]);
    mlogMigrator($this)->run([$this->tasks]);

    expect(mlogLog($this))->toBe([MLOG_GALLERY => 0, MLOG_TASKS => 1]);
});

test('rollback never undoes the baseline', function () {
    mlogMigrator($this, true)->run([$this->gallery]);
    $schema = $this->connection->getSchemaBuilder();
    $paths = [$this->gallery, $this->tasks];

    expect(mlogMigrator($this)->rollback($paths))->toBe([])
        ->and(mlogMigrator($this)->rollback($paths, ['batch' => 0]))->toBe([])
        ->and(mlogMigrator($this)->rollback($paths, ['step' => 5]))->toBe([]);

    mlogMigrator($this)->run([$this->tasks]);
    $rolledBack = mlogMigrator($this)->rollback($paths, ['step' => 5]);

    expect(array_map('basename', $rolledBack))->toBe([MLOG_TASKS . '.php'])
        ->and($schema->hasTable('s_galleries'))->toBeTrue()
        ->and($schema->hasTable('s_tasks'))->toBeFalse()
        ->and(mlogLog($this))->toBe([MLOG_GALLERY => 0]);
});

test('reset undoes all but the baseline, and the next migrate runs only what it undid', function () {
    mlogInstallerLog($this, [MLOG_GALLERY => 1]);
    $this->connection->getSchemaBuilder()->create('s_galleries', fn ($table) => $table->increments('id'));
    $schema = $this->connection->getSchemaBuilder();
    $paths = [$this->gallery, $this->tasks];
    mlogMigrator($this)->run($paths);

    $reset = mlogMigrator($this)->reset($paths);

    expect(array_map('basename', $reset))->toBe([MLOG_TASKS . '.php'])
        ->and($schema->hasTable('s_galleries'))->toBeTrue()
        ->and($schema->hasTable('s_tasks'))->toBeFalse()
        ->and(mlogMigrator($this)->reset($paths))->toBe([])
        ->and(array_map('basename', mlogMigrator($this)->run($paths)))->toBe([MLOG_TASKS . '.php']);
});

test('the installer and artisan read one log from the configuration', function () {
    $config = require dirname(__DIR__, 3) . '/config/app.php';
    $artisan = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Providers/ArtisanServiceProvider.php');

    expect(require dirname(__DIR__, 3) . '/config/database/migrations.php')->toBe('migrations')
        ->and($config['providers']['Console_Migration'] ?? null)->toBe(\EvolutionCMS\Providers\MigrationServiceProvider::class)
        ->and($artisan)->toContain('use Illuminate\Database\Console\Migrations\StatusCommand as MigrateStatusCommand;');

    $app = new \Illuminate\Container\Container();
    $app->instance('config', new \Illuminate\Config\Repository(['database' => ['migrations' => 'migrations']]));
    $app->instance('db', $this->resolver);
    $app->instance('files', new Filesystem());
    $app->instance('events', new Dispatcher());
    (new \EvolutionCMS\Providers\MigrationServiceProvider($app))->register();

    expect($app->make('migration.repository'))->toBeInstanceOf(MigrationRepository::class)
        ->and($app->make('migration.repository')->getTable())->toBe('migrations')
        ->and($app->make('migrator'))->toBeInstanceOf(Migrator::class);
});

test('the installer upgrade copies the old log before it reads the history', function () {
    $out = evoRunPhp(dirname(__DIR__, 2) . '/Fixtures/install-migration-history.php', [], $exit);

    expect($exit)->toBe(0, $out);
    $result = json_decode(trim($out), true, flags: JSON_THROW_ON_ERROR);

    // The synthetic baseline brings the older stubs; all of it lands in batch 0 of migrations.
    expect($result['added'])->toBe(1)
        ->and($result['migrations'])->toBe([
            '2018_06_29_182342_create_categories_table' => 0,
            '2018_06_29_182342_create_site_content_table' => 0,
            '2025_12_25_000000_initial_schema' => 0,
        ])
        ->and($result['migrations_install'])->toBe([
            '2018_06_29_182342_create_site_content_table' => 1,
            '2025_12_25_000000_initial_schema' => 1,
        ]);
});

test('the install seeders no longer write the old installer log', function () {
    $root = dirname(__DIR__, 4);
    $sources = array_merge(glob($root . '/install/stubs/seeds/*/*.php'), glob($root . '/install/src/controllers/*.php'), [$root . '/install/cli-install.php']);

    $writers = array_values(array_filter($sources, fn ($file) => str_contains((string) file_get_contents($file), 'migrations_install')));

    expect($writers)->toBe([]);
});
