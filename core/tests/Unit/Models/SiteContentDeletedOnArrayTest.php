<?php

use EvolutionCMS\Models\SiteContent;
use Illuminate\Support\Carbon;

function siteContentFromRow(array $row): SiteContent
{
    return (new SiteContent())->newFromBuilder($row);
}

test('toArray keeps deletedon as the stored unix timestamp', function () {
    $never = siteContentFromRow(['id' => 1, 'deletedon' => '0'])->toArray();
    $deleted = siteContentFromRow(['id' => 2, 'deletedon' => '1790625541'])->toArray();
    $missing = siteContentFromRow(['id' => 3, 'deletedon' => null])->toArray();

    expect($never['deletedon'])->toBe(0)
        ->and($deleted['deletedon'])->toBe(1790625541)
        ->and($missing['deletedon'])->toBeNull();
});

test('toArray keeps the column order and still casts the other attributes', function () {
    $array = siteContentFromRow([
        'id' => 1,
        'published' => '1',
        'deletedon' => '0',
        'template' => '4',
        'hidemenu' => '0',
    ])->toArray();

    expect(array_keys($array))->toBe(['id', 'published', 'deletedon', 'template', 'hidemenu'])
        ->and($array['published'])->toBe(1)
        ->and($array['template'])->toBe(4)
        ->and($array['hidemenu'])->toBeFalse();
});

test('reading deletedon as an attribute still returns a date', function () {
    $document = siteContentFromRow(['id' => 1, 'deletedon' => '1790625541']);

    expect($document->deletedon)->toBeInstanceOf(Carbon::class)
        ->and($document->deletedon->getTimestamp())->toBe(1790625541);
});

test('a deletion time set in memory is arrayed as a unix timestamp', function () {
    $document = siteContentFromRow(['id' => 1, 'deletedon' => '0']);
    $document->deletedon = Carbon::createFromTimestamp(1790625541);

    expect($document->toArray()['deletedon'])->toBe(1790625541);
});

test('arraying a document does not load Carbon', function () {
    $exitCode = null;
    $output = evoRunPhp(dirname(__DIR__, 2) . '/Mocks/site_content_to_array_worker.php', [], $exitCode);
    $result = json_decode($output, true, flags: JSON_THROW_ON_ERROR);

    expect($exitCode)->toBe(0)
        ->and($result['deletedon'])->toBe(0)
        ->and($result['carbonLoaded'])->toBeFalse();
});
