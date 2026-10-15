<?php

/*
 * Renders a DataGrid over an Eloquent query (an in-memory SQLite table) of two rows whose name holds
 * markup, the way the module dependency list does (a "template:" column type over name,type), and
 * prints the HTML. With "escape" as the first argument the grid lists name in escapeFields.
 *
 * Usage: php data-grid-harness.php [escape]
 */

ini_set('display_errors', 'stderr');

define('IN_MANAGER_MODE', true);
define('IN_INSTALL_MODE', false);
define('EVO_API_MODE', true);

final class DataGridHarnessCore
{
    public function isFrontend()
    {
        return false;
    }

    public function getConfig($name = '', $default = null)
    {
        return $default;
    }
}

function evo()
{
    static $core;

    return $core ??= new DataGridHarnessCore();
}

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$capsule = new \Illuminate\Database\Capsule\Manager();
$capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => 'evo_']);
$capsule->setAsGlobal();
$capsule->bootEloquent();
$capsule->getConnection()->getSchemaBuilder()->create('site_module_depobj', function ($t) {
    $t->increments('id');
    $t->string('name');
    $t->integer('type');
});
$capsule->getConnection()->table('site_module_depobj')->insert([
    ['id' => 7, 'name' => '<img src=x onerror=alert(1)>', 'type' => 20],
    ['id' => 8, 'name' => 'Plain & simple', 'type' => 10],
]);

$rows = \EvolutionCMS\Models\SiteModuleDepobj::query()->select('id', 'name', 'type')->orderBy('id');
$grd = new \EvolutionCMS\Support\DataGrid('', $rows, 0);
$grd->prepareResult = ['type' => [10 => 'Chunk', 20 => 'Document']];
$grd->columns = 'Name ,Type';
$grd->colTypes = "template:<input type='checkbox' name='depid[]' value='[+id+]'> [+value+]";
$grd->fields = 'name,type';
if (($argv[1] ?? '') === 'escape') {
    $grd->escapeFields = ['name'];
}

echo $grd->render();
