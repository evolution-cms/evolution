<?php

use EvolutionCMS\Middleware\VerifyCsrfToken;

/*
|--------------------------------------------------------------------------
| Every Manager entry point has to carry the token
|--------------------------------------------------------------------------
|
| Making the middleware fail closed is only half of the fix: a form or link that does not emit
| a token now breaks instead of silently passing. These tests scan the shipped Manager sources
| so a newly added form or destructive link cannot regress either half.
|
*/

/**
 * Absolute path to the repository root.
 */
function evoRoot(): string
{
    // tests/Unit/Security -> tests -> core -> repository root
    return str_replace('\\', '/', dirname(__DIR__, 4));
}

/**
 * Collects Manager template sources, skipping bundled third-party assets.
 */
function managerTemplateFiles(): array
{
    $found = [];

    foreach (['manager', 'views'] as $dir) {
        $base = evoRoot() . '/' . $dir;
        if (!is_dir($base)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base));

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $path = str_replace('\\', '/', $file->getPathname());

            // KCFinder ships its own forms and its own request handling.
            if (str_contains($path, '/media/browser/') || str_contains($path, '/vendor/')) {
                continue;
            }

            if (preg_match('/\.(php|phtml|tpl)$/', $path)) {
                $found[] = $path;
            }
        }
    }

    sort($found);

    return $found;
}

/**
 * The login screens are rendered before a Manager session exists, so they hold no token and
 * the middleware deliberately exempts them.
 */
function isLoginTemplate(string $path): bool
{
    return str_contains($path, 'login.tpl') || str_contains($path, 'manager.lockout.tpl');
}

it('emits a CSRF field in every state-changing manager form', function () {
    $missing = [];

    foreach (managerTemplateFiles() as $path) {
        if (isLoginTemplate($path)) {
            continue;
        }

        $lines = explode("\n", (string)file_get_contents($path));

        foreach ($lines as $index => $line) {
            if (!preg_match('/<form\b.{0,400}/i', $line, $match)) {
                continue;
            }

            if (!preg_match('/method\s*=\s*["\']?post/i', $match[0])) {
                continue;
            }

            // The field is emitted directly inside the opening tag.
            $window = implode("\n", array_slice($lines, $index, 4));

            if (!str_contains(strtolower($window), 'csrf')) {
                $relative = str_replace(evoRoot() . '/', '', $path);
                $missing[] = $relative . ':' . ($index + 1);
            }
        }
    }

    expect($missing)->toBe([]);
});

it('appends a token to every link that triggers a state-changing GET action', function () {
    $reflection = new ReflectionClass(VerifyCsrfToken::class);
    $guarded = $reflection->getConstant('MUTATING_GET_ACTIONS');

    expect($guarded)->not->toBeEmpty();

    $pattern = '/index\.php\?[^"\'\s]*\ba=(' . implode('|', $guarded) . ')\b/';
    $untokenised = [];

    $files = managerTemplateFiles();
    $files[] = evoRoot() . '/core/src/Controllers/Frame.php';

    foreach ($files as $path) {
        $lines = explode("\n", (string)file_get_contents($path));

        foreach ($lines as $index => $line) {
            if (!preg_match_all($pattern, $line, $matches, PREG_SET_ORDER)) {
                continue;
            }

            // A link is tokenised when `_token` is emitted on the same statement, whether as a
            // literal, a Blade echo or a PHP concatenation.
            if (str_contains($line, '_token')) {
                continue;
            }

            $relative = str_replace(evoRoot() . '/', '', $path);
            $untokenised[] = $relative . ':' . ($index + 1) . ' (a=' . $matches[0][1] . ')';
        }
    }

    expect($untokenised)->toBe([]);
});

it('guards every processor that mutates state straight from the query string', function () {
    // A processor that reads $_GET or $_REQUEST and writes is reachable from an <img> tag, so
    // its action id has to appear in the middleware's GET list.
    $guarded = (new ReflectionClass(VerifyCsrfToken::class))->getConstant('MUTATING_GET_ACTIONS');

    $mutatingProcessors = [
        'delete_content' => 6,
        'delete_template' => 21,
        'delete_snippet' => 25,
        'optimize_table' => 54,
        'empty_table' => 55,
        'publish_content' => 61,
        'unpublish_content' => 62,
        'undelete_content' => 63,
        'remove_content' => 64,
        'remove_locks' => 67,
        'delete_htmlsnippet' => 80,
        'duplicate_content' => 94,
        'duplicate_template' => 96,
        'duplicate_htmlsnippet' => 97,
        'duplicate_snippet' => 98,
        'delete_plugin' => 104,
        'duplicate_plugin' => 105,
        'delete_module' => 110,
        'duplicate_module' => 111,
        'execute_module' => 112,
        'delete_eventlog' => 116,
        'purge_plugin' => 119,
        'delete_tmplvars' => 303,
        'duplicate_tmplvars' => 304,
        'delete_category' => 501,
    ];

    $unguarded = [];

    foreach ($mutatingProcessors as $name => $action) {
        $path = evoRoot() . '/manager/processors/' . $name . '.processor.php';

        expect(is_file($path))->toBeTrue();

        if (!in_array($action, $guarded, true)) {
            $unguarded[] = $name . ' (a=' . $action . ')';
        }
    }

    expect($unguarded)->toBe([]);
});

it('guards the page controllers that mutate state from the request', function () {
    // These are easy to miss when auditing manager/processors/, because a page controller has
    // no processor file at all - the action id maps straight to a class in ManagerTheme.
    $guarded = (new ReflectionClass(VerifyCsrfToken::class))->getConstant('MUTATING_GET_ACTIONS');

    $mutatingControllers = [
        8 => 'Users/LogInOut',    // logout destroys the manager session
        26 => 'RefreshSite',      // publishes/unpublishes pending documents, clears the cache
        52 => 'MoveDocument',     // reparents a document from $_REQUEST
        90 => 'Users/DeleteUser', // deletes a manager user from $_GET
    ];

    $unguarded = [];

    foreach ($mutatingControllers as $action => $class) {
        expect(is_file(evoRoot() . '/core/src/Controllers/' . $class . '.php'))->toBeTrue();

        if (!in_array($action, $guarded, true)) {
            $unguarded[] = $class . ' (a=' . $action . ')';
        }
    }

    // web_access_groups is a processor, but it is reached only through a controller-style
    // ?operation= switch rather than a save/delete action, so it is checked here.
    $accessGroups = (string)file_get_contents(evoRoot() . '/manager/processors/web_access_groups.processor.php');
    expect($accessGroups)->toContain("\$_REQUEST['operation']");

    if (!in_array(92, $guarded, true)) {
        $unguarded[] = 'web_access_groups (a=92)';
    }

    expect($unguarded)->toBe([]);
});

it('guards the page actions that delete when a query parameter is present', function () {
    // Role, permission and category pages are also plain edit screens, so their action ids
    // cannot go into MUTATING_GET_ACTIONS; the triggering parameter is guarded instead.
    $parameters = (new ReflectionClass(VerifyCsrfToken::class))->getConstant('MUTATING_GET_PARAMETERS');

    $deleteBranches = [
        'core/src/Controllers/UserRoles/UserRole.php' => [[35, 36, 38], 'action', "\$_GET['action'] == 'delete'"],
        'core/src/Controllers/UserRoles/Permission.php' => [[135], 'action', "\$_GET['action'] == 'delete'"],
        'core/src/Controllers/UserRoles/PermissionsGroups.php' => [[136], 'action', "\$_GET['action'] == 'delete'"],
        'core/src/Controllers/SystemInfo.php' => [[53], 'opcache_reset', "request()->boolean('opcache_reset')"],
        'manager/actions/mutate_module_resources.dynamic.php' => [[113], 'op', "switch (\$_REQUEST['op'])"],
        'manager/actions/category_mgr/inc/request_trigger.inc.php' => [
            [120, 121],
            'module_categories_manager',
            "\$_GET[\$cm->get('request_key')]['delete']",
        ],
    ];

    $unguarded = [];

    foreach ($deleteBranches as $relative => [$actions, $parameter, $trigger]) {
        expect((string)file_get_contents(evoRoot() . '/' . $relative))->toContain($trigger);

        foreach ($actions as $action) {
            if (($parameters[$action] ?? null) !== $parameter) {
                $unguarded[] = $relative . ' (a=' . $action . ')';
            }
        }
    }

    $categories = (string)file_get_contents(evoRoot() . '/manager/actions/mutate_categories.dynamic.php');
    expect($categories)->toContain("'request_key'      => 'module_categories_manager'");

    expect($unguarded)->toBe([]);
});

it('appends a token to the OPcache reset link', function () {
    // The link is built in the controller, so the template scan below does not see it.
    $source = (string)file_get_contents(evoRoot() . '/core/src/Controllers/SystemInfo.php');

    preg_match_all('/^.*opcache_reset=1.*$/m', $source, $links);

    expect($links[0])->not->toBeEmpty();

    foreach ($links[0] as $link) {
        expect($link)->toContain('_token=');
    }
});

it('appends a token to the logout links built outside the templates', function () {
    // The template scan covers the Blade links; these are assembled in PHP and in the theme script.
    $theme = (string)file_get_contents(evoRoot() . '/core/src/ManagerTheme.php');
    $script = (string)file_get_contents(evoRoot() . '/manager/media/style/default/js/evo.js');

    expect($theme)->toContain("'logouturl' => EVO_MANAGER_URL . 'index.php?a=8' . (isset(\$_SESSION) ? '&_token=' . rawurlencode(csrf_token()) : '')")
        ->and($script)->toContain("'?a=8&_token=' + encodeURIComponent(evo.tabsCsrfToken())")
        ->and($script)->not->toMatch("/\?a=8';/");
});

it('appends a token to every role, permission and category delete link', function () {
    $patterns = [
        '/\ba=(35|36|38|135|136)&action=delete/',
        "/makeUrl\('actions\.delete'\) }}&action=delete/",
        "/request_key'\); \?>\[delete\]=/",
    ];

    $found = 0;
    $untokenised = [];

    foreach (managerTemplateFiles() as $path) {
        $lines = explode("\n", (string)file_get_contents($path));

        foreach ($lines as $index => $line) {
            foreach ($patterns as $pattern) {
                if (!preg_match($pattern, $line)) {
                    continue;
                }

                $found++;

                if (!str_contains($line, '_token')) {
                    $untokenised[] = str_replace(evoRoot() . '/', '', $path) . ':' . ($index + 1);
                }
            }
        }
    }

    // user_role, permission, permissions_groups, element_permission and the category editor.
    expect($found)->toBeGreaterThanOrEqual(5)
        ->and($untokenised)->toBe([]);
});

it('guards the disable toggle branch of the element save processors', function () {
    // Each save_* processor acts on ?disabled= before it ever looks at the POST body.
    $toggles = [
        'save_snippet' => 24,
        'save_htmlsnippet' => 79,
        'save_plugin' => 103,
        'save_module' => 109,
    ];

    $guarded = (new ReflectionClass(VerifyCsrfToken::class))->getConstant('MUTATING_GET_ACTIONS');
    $unguarded = [];

    foreach ($toggles as $name => $action) {
        $source = (string)file_get_contents(evoRoot() . '/manager/processors/' . $name . '.processor.php');

        expect($source)->toContain("isset(\$_GET['disabled'])");

        if (!in_array($action, $guarded, true)) {
            $unguarded[] = $name . ' (a=' . $action . ')';
        }
    }

    expect($unguarded)->toBe([]);
});

/*
|--------------------------------------------------------------------------
| The token must not leak into saved data
|--------------------------------------------------------------------------
|
| Several legacy processors walk the whole POST body instead of reading named keys, so adding
| a token field to their forms would have written `_token` into the database.
|
*/

it('does not persist the token as a system setting', function () {
    // save_settings writes every POST key it does not recognise into system_settings.
    $source = (string)file_get_contents(evoRoot() . '/manager/processors/save_settings.processor.php');

    expect($source)->toMatch("/case '_token':\s*\n(\s*\/\/.*\n)?\s*\\\$k = '';/");
});

it('skips the token in the sort processors that walk the whole POST body', function () {
    $walkers = [
        '/manager/actions/mutate_template_tv_rank.dynamic.php',
        '/core/src/Controllers/TmplvarRank.php',
    ];

    foreach ($walkers as $relative) {
        $source = (string)file_get_contents(evoRoot() . $relative);

        expect($source)
            ->toContain('foreach ($_POST as $listName => $listValue)')
            ->toContain("\$listName == '_token'");
    }
});

it('keeps the session cookie off cross-site posts as defence in depth', function () {
    // The file cannot simply be required here: it calls storage_path(), which needs a booted
    // application, so the declaration is read from the source instead.
    $source = (string)file_get_contents(evoRoot() . '/core/config/session.php');

    expect($source)->toMatch("/'same_site'\s*=>\s*'lax'/");
});

it('exposes the token to scripts that post without an enclosing form', function () {
    $header = (string)file_get_contents(evoRoot() . '/manager/views/partials/header.blade.php');

    expect($header)->toContain('name="csrf-token"');
});
