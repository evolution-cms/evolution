<?php

/*
| DataGrid prints every value into its cells and colTypes templates as-is. The module dependency
| lists (mutate_module, mutate_module_resources) and the resource selector show element names,
| page titles and descriptions through it, so an editor's page title such as <img onerror=...> ran
| in the browser of whoever manages module dependencies - a user who holds save_module. The grid
| now takes an opt-in escapeFields list; callers that pass their own markup are unchanged.
*/

function renderHarnessDataGrid(bool $escape): string
{
    return evoRunPhp(dirname(__DIR__, 2) . '/Fixtures/data-grid-harness.php', $escape ? ['escape'] : []);
}

it('escapes the listed fields in cells and templates', function () {
    $html = renderHarnessDataGrid(true);

    expect($html)->not->toContain('<img')
        ->and($html)->toContain("value='7'> &lt;img src=x onerror=alert(1)&gt;")
        ->and($html)->toContain('Plain &amp; simple')
        ->and($html)->toContain('Document');
});

it('leaves other grids printing their values as before', function () {
    expect(renderHarnessDataGrid(false))->toContain('<img src=x onerror=alert(1)>');
});

it('escapes stored names in the module dependency lists and the resource selector', function (string $file, string $expected) {
    expect((string) file_get_contents(dirname(__DIR__, 4) . '/' . $file))->toContain($expected);
})->with([
    'module' => ['manager/actions/mutate_module.dynamic.php', "\$grd->escapeFields = ['name'];"],
    'module resources' => ['manager/actions/mutate_module_resources.dynamic.php', "\$grd->escapeFields = ['name'];"],
    'resource selector' => ['manager/actions/resource_selector.static.php', "\$grd->escapeFields = ['name', 'description'];"],
]);
