<?php
// Renders the Store "install these packages first" page for argv[1] with no installed elements,
// so every requested dependency is reported missing. Prints the HTML.
namespace EvolutionCMS\Models {
    class StoreWorkerEmptyQuery
    {
        public function whereIn($key, $values) { return $this; }
        public function pluck($key) { return []; }
    }
    class SiteSnippet { public static function query() { return new StoreWorkerEmptyQuery(); } }
    class SitePlugin extends SiteSnippet {}
    class SiteModule extends SiteSnippet {}
}

namespace {
    define('EVO_SITE_URL', 'https://example.invalid/');
    function evo() { return (object) ['config' => ['manager_theme_mode' => 0]]; }

    require dirname(__DIR__, 2) . '/src/Services/Store/PackageInstallFlowService.php';

    $class = new ReflectionClass(\EvolutionCMS\Services\Store\PackageInstallFlowService::class);
    $method = $class->getMethod('buildDependencyErrorHtml');
    echo $method->invoke($class->newInstanceWithoutConstructor(), ['dependencies' => $argv[1], 'name' => 'pkg']);
}
