<?php

/*
 * Renders the default DataSetPager links for a request whose query string is given as JSON, against
 * a stub evo() in the manager (so the links start from PHP_SELF), and prints the pager HTML.
 *
 * Usage: php dataset-pager-harness.php <base64 of the JSON of $_GET>
 * (base64: on Windows escapeshellarg() turns the quotes of raw JSON into spaces)
 */

ini_set('display_errors', 'stderr');

define('IN_MANAGER_MODE', true);
define('IN_INSTALL_MODE', false);
define('EVO_API_MODE', true);

final class DataSetPagerHarnessCore
{
    public function isFrontend()
    {
        return false;
    }
}

function evo()
{
    static $core;

    return $core ??= new DataSetPagerHarnessCore();
}

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$_GET = json_decode((string) base64_decode($argv[1] ?? ''), true) ?: [];
$_SERVER['PHP_SELF'] = '/manager/index.php';

$pager = new \EvolutionCMS\Support\DataSetPager('grid', range(1, 25), 10, 1);
$pager->render();

echo $pager->getRenderedPager();
