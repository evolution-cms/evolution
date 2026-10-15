<?php

/*
| DataSetPager (the paging of DataGrid: the manager event log, TV datagrid widgets, the :datagrid
| output modifier) rebuilt the current query string from $_GET by pasting every key and value raw
| into a single-quoted href. A link such as ?a=114&x=' onmouseover=alert(1) ' ran script for the
| logged-in manager who opened it (the GPC filter only defuses "<script" and CMS tags).
*/

function renderDataSetPager(array $get): string
{
    return evoRunPhp(dirname(__DIR__, 2) . '/Fixtures/dataset-pager-harness.php', [base64_encode((string) json_encode($get))]);
}

it('keeps request keys and values inside the page links', function () {
    $html = renderDataSetPager([
        'a' => '114',
        'x' => "' onmouseover=alert(1) '",
        "y' onfocus=alert(2) z" => '1',
    ]);

    expect($html)->toContain("href='")
        ->and($html)->not->toContain('onmouseover=alert')
        ->and($html)->not->toContain("' onfocus")
        ->and($html)->not->toMatch("/href='[^']*'\\s+on/")
        ->and($html)->toContain('x=%27%20onmouseover%3Dalert%281%29%20%27');
});

it('keeps the other parameters and replaces only its own page number', function () {
    $html = renderDataSetPager(['a' => '114', 'dpgngrid' => '2', 'filter' => ['level' => '3']]);

    expect($html)->toContain("href='/manager/index.php?a=114&amp;filter%5Blevel%5D=3&amp;dpgngrid=2'")
        ->and($html)->toContain("href='/manager/index.php?a=114&amp;filter%5Blevel%5D=3&amp;dpgngrid=3'")
        ->and(substr_count($html, 'dpgngrid='))->toBe(2);
});
