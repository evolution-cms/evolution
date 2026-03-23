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

it('routes new reference saves by selected type instead of the original mode', function () {
    $processorPath = dirname(__DIR__, 4) . '/manager/processors/save_content.processor.php';
    $processor = file_get_contents($processorPath);

    expect($processor)->toContain('$newResourceAction = ($type == "reference") ? "72" : "4";');
    expect($processor)->toContain('if ($type == "reference") {');
    expect($processor)->not->toContain('if ($_POST[\'mode\'] == "72")');
    expect($processor)->not->toContain('if ($_POST[\'mode\'] == "4")');
});
