<?php

use EvolutionCMS\Services\DocumentSave\DocumentSaveResult;
use EvolutionCMS\Support\DocumentSave\SaveResponse;

$edited = new DocumentSaveResult(12, 'edit', 'document', 3, 'Page', 'page', 1_700_000_000);
$created = new DocumentSaveResult(40, 'new', 'reference', 3, 'Link', 'link');

test('after an edit the listing sort survives the redirect, after a create it does not', function () use ($edited, $created) {
    expect(SaveResponse::redirectUrl($edited, '2', false, '&dir=ASC&sort=pagetitle&page=2', 'http://s/'))
        ->toBe('index.php?a=27&id=12&r=1&stay=2&dir=ASC&sort=pagetitle&page=2')
        ->and(SaveResponse::redirectUrl($created, '2', false, '&dir=ASC', 'http://s/'))
        ->toBe('index.php?a=27&id=40&r=1&stay=2');
});

test('close goes to the resource overview and "add another" to the editor of the same type', function () use ($edited, $created) {
    expect(SaveResponse::redirectUrl($edited, '', false, '', 'http://s/'))->toBe('index.php?a=3&id=12&r=1')
        ->and(SaveResponse::redirectUrl($edited, '1', false, '', 'http://s/'))->toBe('index.php?a=4&pid=3&r=1&stay=1')
        // a weblink opens the weblink form, whatever the original mode was
        ->and(SaveResponse::redirectUrl($created, '1', false, '', 'http://s/'))->toBe('index.php?a=72&pid=3&r=1&stay=1');
});

test('the preview flow only exists for an existing resource', function () use ($edited, $created) {
    expect(SaveResponse::redirectUrl($edited, '2', true, '&dir=ASC', 'http://s/'))->toBe('http://s/index.php?id=12&z=manprev')
        ->and(SaveResponse::redirectUrl($created, '2', true, '', 'http://s/'))->toBe('index.php?a=27&id=40&r=1&stay=2');
});

test('the JSON answer carries what the editor writes back plus where the form flow would go', function () use ($edited) {
    expect(SaveResponse::payload($edited, 'index.php?a=27&id=12', 'tok'))->toBe([
        'success' => true,
        'id' => 12,
        'mode' => 'edit',
        'type' => 'document',
        'parent' => 3,
        'pagetitle' => 'Page',
        'alias' => 'page',
        'editedon' => 1_700_000_000,
        'redirect' => 'index.php?a=27&id=12',
        'token' => 'tok',
    ]);
});

test('the save processor answers JSON to the in-place save and redirects everyone else', function () {
    $root = dirname(__DIR__, 5);
    $processor = file_get_contents($root . '/manager/processors/save_content.processor.php');
    $editor = file_get_contents($root . '/manager/actions/mutate_content.dynamic.php');

    expect($processor)->toContain('$ajax = is_ajax();')
        ->and($processor)->toContain("\$respondJson(200, \\EvolutionCMS\\Support\\DocumentSave\\SaveResponse::payload(\$saved, \$redirectUrl, csrf_token()));")
        // plugin output during the save is buffered away from the JSON
        ->and(substr_count($processor, 'ob_end_clean();'))->toBe(2)
        ->and($processor)->toContain("if (\$ajax) {\n    ob_start();\n}")
        ->and($processor)->toContain("\$respondJson(422, ['success' => false, 'message' => \$denied->getMessage()]);")
        ->and($processor)->toContain("\$respondJson(403, ['success' => false, 'message' => __(\"global.error_no_privileges\")]);")
        ->and($processor)->toContain('SaveResponse::redirectUrl($saved, $stay, $refreshPreview, $add_path, EVO_SITE_URL)')
        // the in-place save posts to index.php?a=5 with the XHR marker; the form post stays the fallback
        ->and($editor)->toContain("revision(MGR_DIR . '/media/script/document-save-helper.js')")
        ->and($editor)->toContain('ajaxSaveHelper.usesAjax(document.mutate)')
        ->and($editor)->toContain("xhr.open('POST', ajaxSaveHelper.requestUrl(form.a.value), true);")
        ->and($editor)->toContain("xhr.setRequestHeader('X-REQUESTED-WITH', 'XMLHttpRequest');")
        ->and($editor)->toContain('xhr.send(ajaxSaveHelper.requestBody(new FormData(form), tokenMeta));')
        // a save that cannot be confirmed is never replayed
        ->and($editor)->not->toContain('classicSave')
        ->and($editor)->toContain("js_json(\$_lang['resource_save_unconfirmed'])")
        ->and($editor)->toContain("button.classList.add('saved');")
        ->and($editor)->toContain('parent.evo.tree.restoreTree()');
});
