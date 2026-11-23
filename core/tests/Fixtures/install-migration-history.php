<?php
/*
 * bootstrapInstallMigrationHistory() on a site a 3.5.9 installer left: its log is migrations_install,
 * with the synthetic baseline row. Prints the two logs as JSON. Run by MigrationRepositoryTest.
 */

use EvolutionCMS\Support\MigrationRepository;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Facade;

$root = dirname(__DIR__, 3);
require $root . '/core/vendor/autoload.php';

$database = tempnam(sys_get_temp_dir(), 'evo-install-history-');
$stubs = sys_get_temp_dir() . '/evo-install-stubs-' . bin2hex(random_bytes(6));
mkdir($stubs);
register_shutdown_function(static function () use ($database, $stubs) {
    @unlink($database);
    array_map('unlink', glob($stubs . '/*.php'));
    @rmdir($stubs);
});

$capsule = new Capsule();
$capsule->addConnection(['driver' => 'sqlite', 'database' => $database, 'prefix' => 'evo_']);
$capsule->setAsGlobal();
$connection = $capsule->getConnection();

$app = new Illuminate\Container\Container();
$app->instance('db', $capsule->getDatabaseManager());
$app->instance('db.schema', $connection->getSchemaBuilder());
$app->instance('migration.repository', new MigrationRepository($capsule->getDatabaseManager(), 'migrations'));
Facade::setFacadeApplication($app);

// evo(), and app() through it, answer this container.
define('EVO_CLASS', Illuminate\Container\Container::class);
define('IN_MANAGER_MODE', true);
define('IN_INSTALL_MODE', true);
define('EVO_API_MODE', true);
$GLOBALS['evo'] = $app;

require $root . '/install/src/functions.php';

$schema = $connection->getSchemaBuilder();
$schema->create('active_user_locks', fn ($table) => $table->increments('id'));
$schema->create('migrations_install', function ($table) {
    $table->increments('id');
    $table->string('migration');
    $table->integer('batch');
});
$connection->table('migrations_install')->insert([
    ['migration' => '2018_06_29_182342_create_site_content_table', 'batch' => 1],
    ['migration' => '2025_12_25_000000_initial_schema', 'batch' => 1],
]);
foreach (['2018_06_29_182342_create_categories_table', '2018_06_29_182342_create_site_content_table', '2026_01_17_000000_fix_columns'] as $stub) {
    file_put_contents($stubs . '/' . $stub . '.php', '<?php');
}

$added = bootstrapInstallMigrationHistory($stubs);

$log = static fn (string $table) => $connection->table($table)->orderBy('migration')->pluck('batch', 'migration')
    ->map(fn ($batch) => (int) $batch)->all();

echo json_encode(['added' => $added, 'migrations' => $log('migrations'), 'migrations_install' => $log('migrations_install')]);
