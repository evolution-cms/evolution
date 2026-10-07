<?php

namespace Tests\Unit;

use EvolutionCMS\Core;
use EvolutionCMS\Models\SiteTemplate;
use EvolutionCMS\TemplateProcessor;
use Illuminate\Config\Repository;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
#[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
final class TemplatePinnedControllerTest extends TestCase
{
    /** Selected files and legacy alias lookup must execute the same controller once. */
    public function testPinnedAndAutomaticFilesRunTheControllerOnce(): void
    {
        $directory = sys_get_temp_dir() . '/evo-controller-' . bin2hex(random_bytes(6));
        mkdir($directory);
        $path = $directory . '/home.blade.php';
        file_put_contents($path, 'pinned');
        define('EVO_CLASS', Core::class);
        define('IN_MANAGER_MODE', false);
        $app = (new \ReflectionClass(Core::class))->newInstanceWithoutConstructor();
        $GLOBALS['evo'] = $app;
        $app->instance('config', new Repository([
            'cms' => ['settings' => ['ControllerNamespace' => __NAMESPACE__ . '\\PinnedControllerFixtures\\']],
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
                PinnedControllerFixtures\HomeController::$constructed = 0;
                PinnedControllerFixtures\HomeController::$mainCalls = 0;
                // Pinned files must work without resolving the alias through the view finder.
                $views->found = $extension === '' ? ['home'] : [];
                $views->calls = [];
                $row = new SiteTemplate();
                $row->setRawAttributes(['id' => 2, 'templatesource' => 'file', 'templatefileextension' => $extension]);
                $processor = new TemplateProcessor($app);
                (new \ReflectionProperty($processor, 'templateRows'))->setValue($processor, [2 => $row]);

                self::assertSame('home', $processor->getBladeDocumentContent());
                self::assertSame(1, PinnedControllerFixtures\HomeController::$constructed);
                self::assertSame(1, PinnedControllerFixtures\HomeController::$mainCalls);
                self::assertSame($extension === '' ? '' : $path, $processor->getDocumentViewPath());
                self::assertSame($extension === ''
                    ? ['tpl-2_doc-7', 'doc-7', 'tpl-2', 'home']
                    : ['tpl-2_doc-7', 'doc-7', 'tpl-2'], $views->calls);
            }

            $views->found = ['doc-7'];
            $row->templatefileextension = 'blade.php';
            $processor = new TemplateProcessor($app);
            (new \ReflectionProperty($processor, 'templateRows'))->setValue($processor, [2 => $row]);
            self::assertSame('doc-7', $processor->getBladeDocumentContent());
            self::assertSame('', $processor->getDocumentViewPath());
            self::assertSame(1, PinnedControllerFixtures\HomeController::$constructed);
        } finally {
            unlink($path);
            rmdir($directory);
            unset($GLOBALS['evo']);
        }
    }
}

namespace Tests\Unit\PinnedControllerFixtures;

class BaseController {}

class HomeController extends BaseController
{
    public static int $constructed = 0;
    public static int $mainCalls = 0;

    public function __construct() { ++self::$constructed; }
    public function main() { ++self::$mainCalls; }
}
