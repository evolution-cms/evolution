<?php
/*
 * artisan migrate on a site a 3.5.9 installer left (its log migrations_install), with the core migration
 * that drops that log. Argument "fresh": a site without it; "uncopied": a log whose copy did not happen.
 * Prints the logs as JSON. Run by DropInstallerLogMigrationTest.
 */

use EvolutionCMS\Support\MigrationRepository;
use EvolutionCMS\Support\Migrator;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;

$root = dirname(__DIR__, 3);
require $root . '/core/vendor/autoload.php';

$database = tempnam(sys_get_temp_dir(), 'evo-drop-installer-log-');
register_shutdown_function(static fn () => @unlink($database));

$capsule = new Capsule();
$capsule->addConnection(['driver' => 'sqlite', 'database' => $database, 'prefix' => 'evo_']);
$capsule->setAsGlobal();
$connection = $capsule->getConnection();
$manager = $capsule->getDatabaseManager();

$repository = ($argv[1] ?? '') === 'uncopied'
    ? new class($manager, 'migrations') extends MigrationRepository {
        public function importInstallerLog(): int
        {
            return 0;
        }
    }
    : new MigrationRepository($manager, 'migrations');
$app = new Illuminate\Container\Container();
$app->instance('db', $manager);
$app->instance('db.schema', $connection->getSchemaBuilder());
$app->instance('migration.repository', $repository);
Facade::setFacadeApplication($app);

// evo(), and app() through it, answer this container.
define('EVO_CLASS', Illuminate\Container\Container::class);
define('IN_MANAGER_MODE', false);
define('IN_INSTALL_MODE', false);
define('EVO_API_MODE', true);
$GLOBALS['evo'] = $app;

$schema = $connection->getSchemaBuilder();
if (($argv[1] ?? '') !== 'fresh') {
    $schema->create('migrations_install', function ($table) {
        $table->increments('id');
        $table->string('migration');
        $table->integer('batch');
    });
    $connection->table('migrations_install')->insert([
        ['migration' => '2018_06_29_182342_create_site_content_table', 'batch' => 1],
        ['migration' => '2022_05_15_185152_create_s_gallery_table', 'batch' => 2],
    ]);
}

$migration = $root . '/core/database/migrations/2026_11_23_000000_drop_migrations_install_table.php';
$migrator = new Migrator($repository, $manager, new Filesystem(), new Dispatcher());
if (!$repository->repositoryExists()) {
    $repository->createRepository();
}
$error = null;
try {
    $migrator->run([$migration]);
} catch (RuntimeException $exception) {
    $error = $exception->getMessage();
}

echo json_encode([
    'error' => $error,
    'migrations' => $connection->table('migrations')->orderBy('migration')->pluck('batch', 'migration')->map(fn ($batch) => (int) $batch)->all(),
    'installer_log' => $schema->hasTable('migrations_install'),
]);
