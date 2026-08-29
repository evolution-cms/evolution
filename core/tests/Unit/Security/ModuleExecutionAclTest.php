<?php

/**
 * Guards the module execution ACL at the places that actually enforce it.
 *
 * The processor and the model scope are procedural manager code that cannot be
 * exercised without a session and a database, so these assertions pin the
 * structure that the pure rules in ModuleAccess are wired into.
 */

$basePath = dirname(__DIR__, 4);
$read = static fn (string $path): string => (string) file_get_contents($path);

it('refuses to execute a file based module that is not registered', function () use ($basePath, $read) {
    // the id is user input; before this guard an unknown key read an
    // arbitrary path out of an undefined registry entry
    $processor = $read($basePath . '/manager/processors/execute_module.processor.php');

    expect($processor)->toContain('!isset($modx->modulesFromFile[$id])')
        ->and($processor)->toContain('is_file($content[\'file\'])');
});

it('applies the role ACL to file based modules instead of skipping them', function () use ($basePath, $read) {
    $processor = $read($basePath . '/manager/processors/execute_module.processor.php');

    expect($processor)->toContain('ModuleAccess::canRunFileModule($mgrRole, $content)')
        // the old guard skipped every non numeric id outright
        ->and($processor)->not->toContain("if (\$_SESSION['mgrRole'] != 1 && is_numeric(\$id))");
});

it('still checks the database ACL before running a stored module', function () use ($basePath, $read) {
    $processor = $read($basePath . '/manager/processors/execute_module.processor.php');

    expect($processor)->toContain("hasPermission('exec_module')")
        ->and($processor)->toContain('->withoutProtected()');
});

it('reports module execution failures through the lexicon, with the id escaped', function () use ($basePath, $read) {
    $processor = $read($basePath . '/manager/processors/execute_module.processor.php');

    foreach (['module_exec_no_privileges', 'module_exec_not_found', 'module_exec_disabled'] as $key) {
        expect($processor)->toContain('$_lang["' . $key . '"]');
    }

    // webAlertAndQuit() echoes its message into the page unescaped, and the
    // file module branch puts a raw $_GET value in it
    expect($processor)->toContain('sprintf($_lang["module_exec_not_found"], e($id))')
        ->and($processor)->not->toContain('No record found for id')
        ->and($processor)->not->toContain('You do not sufficient privileges');
});

it('carries the module execution messages in every bundled language', function () use ($basePath) {
    $locales = glob($basePath . '/core/lang/*/global.php');
    expect($locales)->not->toBeEmpty();

    foreach ($locales as $file) {
        $_lang = [];
        include $file;

        foreach ([
            'module_exec_no_privileges',
            'module_exec_not_found',
            'module_exec_disabled',
            'role_modules_tab',
            'role_modules_msg',
            'role_modules_admin_msg',
            'role_modules_none',
        ] as $key) {
            $locale = basename(dirname($file));
            expect($_lang)->toHaveKey($key);
            expect(trim((string) $_lang[$key]))->not->toBe('', $locale . ' has an empty ' . $key);
        }

        // the id is substituted with sprintf()
        expect($_lang['module_exec_not_found'])->toContain('%s');
    }
});

it('filters the module menu by role, including file based modules', function () use ($basePath, $read) {
    $frame = $read($basePath . '/core/src/Controllers/Frame.php');

    expect($frame)->toContain('ModuleAccess::canRunFileModule($mgrRole, $module)')
        // the menu used to skip the ACL entirely when use_udperms was off
        ->and($frame)->not->toContain("\$_SESSION['mgrRole'] != 1 && \$this->managerTheme->getCore()->getConfig('use_udperms') === true");
});

it('applies the role axis in the shared module scope so every listing inherits it', function () use ($basePath, $read) {
    $model = $read($basePath . '/core/src/Models/SiteModule.php');

    expect($model)->toContain('function scopeAllowedForRole')
        ->and($model)->toContain('->allowedForRole($roleId)')
        ->and($model)->toContain('site_module_roles')
        // the group ACL branch must stay grouped, or the added role
        // condition would be swallowed by its trailing orWhere
        ->and($model)->toContain('->where(function (Eloquent\Builder $query) {');
});

it('constrains the modules listed inside a category, not just the categories', function () use ($basePath, $read) {
    foreach (['/core/src/Controllers/Modules.php', '/core/src/Controllers/Resources/Modules.php'] as $file) {
        expect($read($basePath . $file))
            ->toContain("Category::with(['modules' => function (\$builder) use (\$roleId) {");
    }

    // the elements tab listed every module regardless of either ACL axis
    expect($read($basePath . '/core/src/Controllers/Resources/Modules.php'))
        ->toContain('->withoutProtected()');
});

it('drops the Modules menu when the user may not run a single module', function () use ($basePath, $read) {
    $frame = $read($basePath . '/core/src/Controllers/Frame.php');

    // the parent node is added before the list is known, so the listing pass
    // is what removes it again - no second query, no extra permission lookup
    expect($frame)->toContain("unset(\$this->sitemenu['modules']);")
        ->and(strpos($frame, '->menuModules()'))
        ->toBeLessThan(strpos($frame, '->menuRunModules()'));
});

it('cleans up role rows wherever module and role rows are removed or copied', function () use ($basePath, $read) {
    $files = [
        '/manager/processors/delete_module.processor.php',
        '/manager/processors/duplicate_module.processor.php',
        '/manager/processors/delete_role.processor.php',
        '/core/src/Services/Store/LegacyDeleteService.php',
        '/core/src/Controllers/UserRoles/UserRole.php',
    ];

    foreach ($files as $file) {
        expect($read($basePath . $file))->toContain('SiteModuleRole');
    }
});

it('ships the role ACL table through every install and update path', function () use ($basePath) {
    $migration = '/2026_08_29_000000_create_site_module_roles_table.php';

    expect(is_file($basePath . '/core/database/migrations' . $migration))->toBeTrue()
        ->and(is_file($basePath . '/install/stubs/migrations' . $migration))->toBeTrue();

    $installers = [
        '/install/src/controllers/install.php',
        '/install/cli-install.php',
        '/core/src/Console/SiteUpdateCommand.php',
    ];

    foreach ($installers as $file) {
        expect((string) file_get_contents($basePath . $file))->toContain('applyDefaultsFor(');
    }
});
