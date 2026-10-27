<?php

use EvolutionCMS\Traits\Models\ManagerActions;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;

it('renders session tokens on every mutating element list link', function (string $view, string $model, int $expectedLinks) {
    $source = file_get_contents(dirname(__DIR__, 4) . '/manager/views/page/resources/elements/' . $view . '.blade.php');
    preg_match_all('/(?:href|data-enable-href|data-disable-href)="({{ \$item->makeUrl\(.*?)"/', $source, $matches);

    $item = new class {
        use ManagerActions;

        public bool $exists = true;
        public array $managerActionsMap;

        public function getKey(): int
        {
            return 42;
        }
    };
    $item->managerActionsMap = (new ReflectionClass($model))->getDefaultProperties()['managerActionsMap'];
    $originalSession = $_SESSION ?? null;
    $_SESSION = ['_token' => str_repeat('a', 40)];
    $compiler = new BladeCompiler(new Filesystem(), sys_get_temp_dir());
    $mutating = 0;

    try {
        // The module name runs it for managers who can execute but cannot edit modules.
        foreach (['actions.edit', 'actions.run'] as $action) {
            foreach ($matches[1] as $fragment) {
                ob_start();
                try {
                    eval('?>' . $compiler->compileString($fragment));
                    $url = html_entity_decode(ob_get_contents(), ENT_QUOTES, 'UTF-8');
                } finally {
                    ob_end_clean();
                }
                parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
                expect($query['id'])->toBe('42');

                if ($query['a'] === (string) $item->managerActionsMap['id']['actions.edit']) {
                    expect($query)->not->toHaveKey('_token');
                    continue;
                }

                expect($query['_token'] ?? null)->toBe($_SESSION['_token']);
                $mutating++;
            }
        }
        expect($mutating)->toBe($expectedLinks);
    } finally {
        $_SESSION = $originalSession;
    }
})->with([
    'templates' => ['template', EvolutionCMS\Models\SiteTemplate::class, 4],
    'chunks' => ['chunk', EvolutionCMS\Models\SiteHtmlsnippet::class, 8],
    'snippets' => ['snippet', EvolutionCMS\Models\SiteSnippet::class, 8],
    'plugins' => ['plugin', EvolutionCMS\Models\SitePlugin::class, 8],
    'TVs' => ['tv', EvolutionCMS\Models\SiteTmplvar::class, 4],
    'modules' => ['module', EvolutionCMS\Models\SiteModule::class, 11],
]);
