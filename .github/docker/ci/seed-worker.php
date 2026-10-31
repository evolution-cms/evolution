<?php

if (getenv('EVO_WORKER_MODE') !== '1' || getenv('EVO_WORKER_DEBUG') !== '1') {
    throw new RuntimeException('This fixture is only for a disposable worker test container.');
}
define('EVO_API_MODE', true);
$root = $argv[1] ?? '/var/www/html';
require $root . '/index.php';
evo();
copy(__DIR__ . '/worker-routes.php', EVO_CORE_PATH . 'custom/routes.php');
$snippet = EvolutionCMS\Models\SiteSnippet::firstOrNew(['name' => 'WorkerProbe']);
$snippet->snippet = 'return "worker-demo:" . evo()->documentIdentifier;';
$snippet->save();
$page = EvolutionCMS\Models\SiteContent::firstOrNew(['alias' => 'worker-demo']);
$page->fill(['pagetitle' => 'Worker demo', 'published' => 1, 'template' => 0,
    'alias_visible' => 1, 'cacheable' => 0, 'content' => '<h1>[[WorkerProbe]]</h1>']);
$page->save();
$errorPage = EvolutionCMS\Models\SiteContent::firstOrNew(['alias' => 'worker-error']);
$errorPage->fill(['pagetitle' => 'Worker error page', 'published' => 1, 'template' => 0,
    'cacheable' => 0, 'content' => '<h1>worker-error</h1>']);
$errorPage->save();
foreach (['seostrict' => '0', 'error_page' => (string) $errorPage->id,
    'worker_transaction' => 'original', 'worker_setting' => 'original'] as $name => $value) {
    Illuminate\Support\Facades\DB::table('system_settings')->updateOrInsert(
        ['setting_name' => $name], ['setting_value' => $value]
    );
}
evo()->clearCache('full');
echo json_encode(['page' => $page->id]) . PHP_EOL;
