<?php

use EvolutionCMS\Support\TemplateFileGuard;
use EvolutionCMS\TemplateProcessor;

/**
 * A manager with only save_template may edit database templates, but writing a
 * template to a *file* is authoring code an engine executes, and must need a
 * code permission. See the "File manager access rules" section of AGENTS.md and
 * manager/processors/save_template.processor.php.
 */

/** A stand-in for the CMS instance exposing just hasPermission(). */
function fakeManager(array $granted): object
{
    return new class($granted) {
        /** @param string[] $granted */
        public function __construct(private array $granted) {}

        public function hasPermission($permission): bool
        {
            return in_array($permission, $this->granted, true);
        }
    };
}

it('treats a file-backed template as a filesystem write', function (string $source) {
    expect(TemplateFileGuard::writesFile($source, false))->toBeTrue();
})->with([TemplateProcessor::SOURCE_FILE]);

it('treats scaffolding a file as a filesystem write even for a database template', function () {
    expect(TemplateFileGuard::writesFile(TemplateProcessor::SOURCE_DATABASE, true))->toBeTrue()
        ->and(TemplateFileGuard::writesFile('', true))->toBeTrue();
});

it('leaves a database template that writes no file alone', function (?string $source) {
    expect(TemplateFileGuard::writesFile($source, false))->toBeFalse();
})->with([TemplateProcessor::SOURCE_DATABASE, '', null]);

it('refuses the file write to a manager who may not run code', function () {
    expect(TemplateFileGuard::authorMayRunCode(fakeManager(['save_template'])))->toBeFalse()
        ->and(TemplateFileGuard::authorMayRunCode(fakeManager(['save_template', 'save_chunk', 'file_manager'])))->toBeFalse()
        ->and(TemplateFileGuard::authorMayRunCode(fakeManager([])))->toBeFalse();
});

it('allows the file write to a manager who may run code', function (string $permission) {
    expect(TemplateFileGuard::authorMayRunCode(fakeManager([$permission])))->toBeTrue();
})->with(TemplateFileGuard::CODE_PERMISSIONS);

it('mirrors the file manager code permissions exactly', function () {
    expect(TemplateFileGuard::CODE_PERMISSIONS)
        ->toBe(['save_snippet', 'save_plugin', 'save_module']);
});

it('blocks a save_template-only publisher from planting an executable template file', function (string $extension) {
    // The exact shape save_template.processor.php evaluates before any write.
    $source = TemplateProcessor::SOURCE_FILE; // templatesource=file
    $wantsFile = false;
    $publisher = fakeManager(['save_template', 'save_chunk', 'file_manager', 'exec_module']);

    $wouldWrite = TemplateFileGuard::writesFile($source, $wantsFile);
    $mayRunCode = TemplateFileGuard::authorMayRunCode($publisher);

    // writes a file, but not allowed to -> the processor quits with no_privileges
    expect($wouldWrite && !$mayRunCode)->toBeTrue();
})->with(['php', 'blade.php', 'latte', 'twig']);

it('closes the view paths in the file manager to a manager who may not run code', function () {
    $functions = (string) file_get_contents(dirname(__DIR__, 3) . '/functions/actions/files.php');
    $body = substr($functions, strpos($functions, 'function fileManagerProtectedPaths'), 2500);
    $gate = strpos($body, 'if (!fileManagerMayRunCode()) {');

    // The media browser reads the same list (isInProtectedFolder()), so both file managers are covered.
    expect($gate)->not->toBeFalse()
        ->and(strpos($body, 'fileManagerTemplateViewPaths()'))->toBeGreaterThan($gate);
});

it('takes the view paths from the configuration and falls back to the shipped views folder', function () {
    $shipped = (defined('EVO_BASE_PATH') ? EVO_BASE_PATH : dirname(__DIR__, 4) . '/') . 'views';

    expect(fileManagerTemplateViewPaths(['/srv/site/views/', '/srv/site/themes/views']))
        ->toBe(['/srv/site/views/', '/srv/site/themes/views'])
        ->and(fileManagerTemplateViewPaths([]))->toBe([$shipped])
        ->and(fileManagerTemplateViewPaths(['', '  ']))->toBe([$shipped]);
});
