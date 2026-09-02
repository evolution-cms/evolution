<?php

/**
 * Where a template's code is kept and whether the Evolution CMS DocumentParser
 * runs over the result are separate questions - but the second one is not a
 * setting anybody stores. A document rendered from a file is finished output,
 * and that is the core's answer; an engine whose own syntax survives the pass
 * says otherwise from a plugin.
 *
 * This replaced a per-template column. The column institutionalised the legacy
 * pass in the schema - a migration on four update paths, a form control and a
 * lexicon entry in every language - for something one extra already did with
 * public API and nothing else needed at all.
 */

$read = static fn (string $path): string => str_replace(chr(13), '', (string) file_get_contents($path));
$core = static fn (string $file): string => $read(dirname(__DIR__, 3) . '/src/' . $file);
$root = static fn (string $file): string => $read(dirname(__DIR__, 4) . '/' . $file);

test('the decision is a property a plugin can reach', function () use ($core) {
    $source = $core('Core.php');

    expect($source)->toContain('public $runDocumentParser = true;')
        ->and($source)->toContain('$this->runDocumentParser = !$template;');
});

test('the decision is still open when OnLoadWebDocument fires', function () use ($core) {
    // That is the whole hook: the event has to run after the property is set
    // and before anything reads it, or a plugin has nothing to flip.
    $source = $core('Core.php');

    $set = strpos($source, '$this->runDocumentParser = !$template;');
    $event = strpos($source, "\$this->invokeEvent('OnLoadWebDocument');");
    $read = strpos($source, 'if ($this->runDocumentParser) {');
    $output = strpos($source, '$this->outputContent(false, $this->runDocumentParser);');

    expect($set)->toBeLessThan($event)
        ->and($event)->toBeLessThan($read)
        ->and($read)->toBeLessThan($output);
});

test('the whole pipeline follows one decision, not the storage', function () use ($core) {
    // outputContent()'s tail is DocumentParser work too - uncached [!snippets!],
    // [^stats^], cleanUpMODXTags(), rewriteUrls(), escaped tag recovery - so a
    // plugin that turns the pass on gets those in the core's own order rather
    // than re-implementing them.
    $source = $core('Core.php');

    expect($source)->toContain('$this->outputContent(false, $this->runDocumentParser);')
        ->and($source)->not->toContain('$this->outputContent(false, false);');
});

test('a file rendered document is not parsed unless something asks', function () {
    // The core's own answer, and the one the maintainers want: Blade has had
    // its say, and a second dialect does not run over the result.
    $decide = static fn (bool $renderedFromFile): bool => !$renderedFromFile;

    expect($decide(true))->toBeFalse()
        ->and($decide(false))->toBeTrue();
});

test('nothing stores the decision any more', function () use ($core, $root) {
    // No column, no migration, no form control, no lexicon - the pass is a
    // property of the request, not of the template.
    expect($core('Core.php'))->not->toContain('templatedocumentparser')
        ->and($core('TemplateProcessor.php'))->not->toContain('templatedocumentparser')
        ->and($core('Models/SiteTemplate.php'))->not->toContain('templatedocumentparser')
        ->and($core('Controllers/Template.php'))->not->toContain('templatedocumentparser')
        ->and($root('manager/views/page/template.blade.php'))->not->toContain('templatedocumentparser')
        ->and($root('manager/processors/save_template.processor.php'))->not->toContain('templatedocumentparser')
        ->and($root('core/lang/en/global.php'))->not->toContain('template_document_parser')
        ->and(glob(dirname(__DIR__, 3) . '/database/migrations/*templatedocumentparser*'))->toBe([])
        ->and(glob(dirname(__DIR__, 4) . '/install/stubs/migrations/*templatedocumentparser*'))->toBe([]);
});

test('the template row is still read once per request', function () use ($core) {
    // Kept from the same piece of work, and unrelated to the column: where the
    // code lives and which engine was pinned used to be a query each.
    $source = $core('TemplateProcessor.php');

    expect($source)->toContain("first(['id', 'templatesource', 'templatefileextension'])")
        ->and($source)->not->toContain("->value('templatesource')")
        ->and($source)->not->toContain("->value('templatefileextension')");
});
