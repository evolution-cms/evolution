<?php

namespace Tests\Unit\PinnedControllerFixtures {
    class BaseController {}

    class HomeController extends BaseController
    {
        public static int $constructed = 0;
        public static int $mainCalls = 0;

        public function __construct() { ++self::$constructed; }
        public function main() { ++self::$mainCalls; }
    }
}

namespace {
    use EvolutionCMS\Core;
    use EvolutionCMS\Models\SiteTemplate;
    use EvolutionCMS\TemplateProcessor;
    use Illuminate\Config\Repository;
    use Tests\Unit\PinnedControllerFixtures\HomeController;

    // Run in a separate process: it defines CMS constants and the $evo global.
    define('EVO_CLASS', Core::class);
    define('IN_MANAGER_MODE', false);
    require dirname(__DIR__, 2) . '/vendor/autoload.php';

    $assertSame = static function ($expected, $actual, string $message): void {
        if ($expected !== $actual) {
            throw new RuntimeException($message . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
        }
    };

    $directory = sys_get_temp_dir() . '/evo-controller-' . bin2hex(random_bytes(6));
    mkdir($directory);
    $path = $directory . '/home.blade.php';
    file_put_contents($path, 'pinned');
    $app = (new ReflectionClass(Core::class))->newInstanceWithoutConstructor();
    $GLOBALS['evo'] = $app;
    $app->instance('config', new Repository([
        'cms' => ['settings' => ['ControllerNamespace' => substr(HomeController::class, 0, -strlen('HomeController'))]],
        'view' => [
            'paths' => [$directory],
            'template_engines' => ['blade.php' => ['label' => 'Blade', 'processor' => 'blade']],
        ],
    ]));
    $views = new class {
        public array $found = [];
        public array $calls = [];
        public function exists($name) { $this->calls[] = $name; return in_array($name, $this->found, true); }
        public function getExtensions() { return ['blade.php' => 'blade']; }
    };
    $app->instance('view', $views);
    $app->documentObject = ['id' => 7, 'template' => 2, 'templatealias' => 'home', 'content' => 'page'];
    $app->documentContent = 'template';

    try {
        foreach (['blade.php', ''] as $extension) {
            HomeController::$constructed = 0;
            HomeController::$mainCalls = 0;
            // Pinned files must work without resolving the alias through the view finder.
            $views->found = $extension === '' ? ['home'] : [];
            $views->calls = [];
            $row = new SiteTemplate();
            $row->setRawAttributes(['id' => 2, 'templatesource' => 'file', 'templatefileextension' => $extension]);
            $processor = new TemplateProcessor($app);
            (new ReflectionProperty($processor, 'templateRows'))->setValue($processor, [2 => $row]);

            $assertSame('home', $processor->getBladeDocumentContent(), "[$extension] document content");
            $assertSame(1, HomeController::$constructed, "[$extension] controller constructed");
            $assertSame(1, HomeController::$mainCalls, "[$extension] controller main calls");
            $assertSame($extension === '' ? '' : $path, $processor->getDocumentViewPath(), "[$extension] view path");
            $assertSame($extension === ''
                ? ['tpl-2_doc-7', 'doc-7', 'tpl-2', 'home']
                : ['tpl-2_doc-7', 'doc-7', 'tpl-2'], $views->calls, "[$extension] view probes");
        }

        $views->found = ['doc-7'];
        $row->templatefileextension = 'blade.php';
        $processor = new TemplateProcessor($app);
        (new ReflectionProperty($processor, 'templateRows'))->setValue($processor, [2 => $row]);
        $assertSame('doc-7', $processor->getBladeDocumentContent(), 'doc-7 override content');
        $assertSame('', $processor->getDocumentViewPath(), 'doc-7 override view path');
        $assertSame(1, HomeController::$constructed, 'doc-7 override controller constructed');
        echo "Pinned and automatic template files run the controller once.\n";
    } finally {
        unlink($path);
        rmdir($directory);
        unset($GLOBALS['evo']);
    }
}
