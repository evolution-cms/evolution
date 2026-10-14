<?php

use EvolutionCMS\Support\BackendCodePermissions;
use EvolutionCMS\Support\TemplateFileGuard;

function codePermissionsManager(array $granted): object
{
    return new class($granted) {
        public function __construct(private array $granted)
        {
        }

        public function hasPermission($permission): bool
        {
            return in_array($permission, $this->granted, true);
        }
    };
}

it('grants code authoring to a holder of any one PHP permission', function (string $permission) {
    expect(BackendCodePermissions::granted(codePermissionsManager([$permission])))->toBeTrue()
        ->and(BackendCodePermissions::grantedBy(fn (string $p) => $p === $permission))->toBeTrue();
})->with(BackendCodePermissions::PERMISSIONS);

it('refuses code authoring to everyone else', function (array $granted) {
    expect(BackendCodePermissions::granted(codePermissionsManager($granted)))->toBeFalse();
})->with([
    'nothing' => [[]],
    'templates and TVs' => [['save_template', 'edit_template', 'new_template']],
    'publisher' => [['save_document', 'save_chunk', 'file_manager', 'exec_module', 'bk_manager', 'settings']],
]);

it('is the list every guard uses', function () {
    expect(TemplateFileGuard::CODE_PERMISSIONS)->toBe(BackendCodePermissions::PERMISSIONS)
        ->and(BackendCodePermissions::PERMISSIONS)->toBe(['save_snippet', 'save_plugin', 'save_module']);
});

it('leaves no hand-picked copy of the code permissions in the sources', function () {
    $root = dirname(__DIR__, 4);
    $offenders = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        $path = str_replace('\\', '/', $file->getPathname());
        if (!str_ends_with($path, '.php')
            || preg_match('~/(core/vendor|core/tests|core/storage|tmp|assets/cache|\.git)/~', $path)
            || str_ends_with($path, 'core/src/Support/BackendCodePermissions.php')) {
            continue;
        }
        $source = (string) file_get_contents($path);
        if (preg_match("~\\[\\s*'save_snippet'\\s*,\\s*'save_plugin'\\s*,\\s*'save_module'\\s*\\]~", $source)) {
            $offenders[] = substr($path, strlen(str_replace('\\', '/', $root)) + 1);
        }
    }

    expect($offenders)->toBe([]);
});

it('asks the shared check wherever a manager stores code bindings or restores SQL', function (string $file) {
    $source = (string) file_get_contents(dirname(__DIR__, 4) . '/' . $file);

    expect($source)->toContain('BackendCodePermissions::')
        ->and($source)->not->toMatch("~(TvBindingGuard::allows(Values)?\\(|mayRestore\\s*=)[^;]*['\"]save_snippet['\"]~");
})->with([
    'TV definitions' => 'manager/processors/save_tmplvars.processor.php',
    'TV values' => 'core/src/Services/DocumentSaveService.php',
    'database restore' => 'manager/actions/bkmanager.static.php',
    'file manager' => 'core/functions/actions/files.php',
]);

it('refuses a database restore before any SQL runs', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/actions/bkmanager.static.php');
    $guard = strpos($source, "in_array(\$mode, ['restore1', 'restore2'], true) && !\$mayRestore");

    expect($guard)->not->toBeFalse()
        ->and($guard)->toBeLessThan(strpos($source, "if (\$mode == 'restore1')"))
        ->and($guard)->toBeLessThan(strpos($source, 'import_sql('));
});
