<?php

it('syncs the manager form when switching a resource to link', function () {
    $formPath = dirname(__DIR__, 4) . '/manager/actions/mutate_content.dynamic.php';
    $form = file_get_contents($formPath);

    expect($form)->toContain('syncReferenceContent()');
    expect($form)->toContain('syncResourceTypePanels(this.value);');
    expect($form)->toContain('id="resource-type-reference-fields"');
    expect($form)->toContain('id="resource-type-document-fields"');
    expect($form)->toContain('id="weblink-content"');
    expect($form)->toContain("getSelectedResourceType() === 'reference'");
});

it('hints a URL or a document id in the weblink field by the link selection mode', function () {
    $form = file_get_contents(dirname(__DIR__, 4) . '/manager/actions/mutate_content.dynamic.php');

    expect($form)->toContain('placeholder="https://evo.im" data-placeholder-url="https://evo.im" data-placeholder-id="123"');
    expect($form)->toContain("linkField.placeholder = linkField.getAttribute(b ? 'data-placeholder-id' : 'data-placeholder-url');");
    // An empty field shows the hint instead of a prefilled scheme that breaks a typed id.
    expect($form)->not->toContain(": 'http://'");
});

it('lays out the weblink field inline with its link icon and insert button', function () {
    $styleDir = dirname(__DIR__, 4) . '/manager/media/style/default/css/';
    $main = file_get_contents($styleDir . 'main.css');
    $custom = file_get_contents($styleDir . 'custom.css');

    // The icon overlays the start of the field, so the field leaves room for it.
    expect($main)->toContain('form#mutate #llock {');
    expect($main)->toContain('form#mutate #weblink-content.inputBox { padding-left: 2rem }');
    // The insert button sits on the field's right edge, like the image TV button.
    expect($custom)->toContain('#weblink-content.inputBox + input[type=button],');
});

it('routes new reference saves by selected type instead of the original mode', function () {
    $processorPath = dirname(__DIR__, 4) . '/manager/processors/save_content.processor.php';
    $processor = file_get_contents($processorPath);

    $response = file_get_contents(dirname(__DIR__, 4) . '/core/src/Support/DocumentSave/SaveResponse.php');

    expect($processor)->toContain('$newResourceAction = ($type == "reference") ? "72" : "4";');
    expect($response)->toContain("\$newAction = \$saved->type === 'reference' ? '72' : '4';");
    expect($processor)->not->toContain('if ($_POST[\'mode\'] == "72")');
    expect($processor)->not->toContain('if ($_POST[\'mode\'] == "4")');
});

it('previews a site-relative weblink or a document id on the site, not under the manager', function () {
    $form = file_get_contents(dirname(__DIR__, 4) . '/manager/actions/mutate_content.dynamic.php');

    expect($form)->toContain("previewUrl = <?= js_json(EVO_SITE_URL . 'index.php?id=') ?> + rawUrl;");
    expect($form)->toContain('? rawUrl : <?= js_json(EVO_SITE_URL) ?> + rawUrl;');
});
