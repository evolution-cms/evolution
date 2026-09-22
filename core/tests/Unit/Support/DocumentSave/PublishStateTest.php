<?php

use EvolutionCMS\Support\DocumentSave\PublishState;

const PS_NOW = 1_700_000_000;

test('a publish date decides the flag relative to now', function () {
    expect(PublishState::fromDates(0, PS_NOW - 10, 0, PS_NOW))->toBe(1)
        ->and(PublishState::fromDates(1, PS_NOW + 10, 0, PS_NOW))->toBe(0)
        ->and(PublishState::fromDates(1, 0, PS_NOW - 10, PS_NOW))->toBe(0)
        ->and(PublishState::fromDates(1, 0, PS_NOW + 10, PS_NOW))->toBe(1)
        ->and(PublishState::fromDates(1, 0, 0, PS_NOW))->toBe(1);
});

test('a new document without publish_document is always unpublished with no dates', function () {
    expect(PublishState::forNew(1, PS_NOW - 5, PS_NOW + 5, PS_NOW, 7, false))
        ->toBe(['published' => 0, 'pub_date' => 0, 'unpub_date' => 0, 'publishedon' => 0, 'publishedby' => 0]);
});

test('a new published document records the publisher and uses the publish date when set', function () {
    expect(PublishState::forNew(1, 0, 0, PS_NOW, 7, true))
        ->toBe(['published' => 1, 'pub_date' => 0, 'unpub_date' => 0, 'publishedon' => PS_NOW, 'publishedby' => 7])
        ->and(PublishState::forNew(0, PS_NOW - 100, 0, PS_NOW, 7, true))
        ->toBe(['published' => 1, 'pub_date' => PS_NOW - 100, 'unpub_date' => 0, 'publishedon' => PS_NOW - 100, 'publishedby' => 7]);
});

test('editing without publish_document keeps the stored state and dates', function () {
    $existing = ['published' => 1, 'pub_date' => 11, 'unpub_date' => 22, 'publishedon' => 33, 'publishedby' => 4];

    // the legacy processor wrote the literal strings 'pub_date' / 'unpub_date' here
    expect(PublishState::forEdit(0, 0, 0, PS_NOW, 7, false, $existing))
        ->toBe(['published' => 1, 'pub_date' => 11, 'unpub_date' => 22, 'publishedon' => 33, 'publishedby' => 4]);
});

test('editing tracks the publish transition', function () {
    $unpublished = ['published' => 0, 'pub_date' => 0, 'unpub_date' => 0, 'publishedon' => 0, 'publishedby' => 0];
    $published = ['published' => 1, 'pub_date' => 0, 'unpub_date' => 0, 'publishedon' => 500, 'publishedby' => 4];

    expect(PublishState::forEdit(1, 0, 0, PS_NOW, 7, true, $unpublished)['publishedon'])->toBe(PS_NOW)
        ->and(PublishState::forEdit(1, 0, 0, PS_NOW, 7, true, $unpublished)['publishedby'])->toBe(7)
        ->and(PublishState::forEdit(0, 0, 0, PS_NOW, 7, true, $published))->toMatchArray(['publishedon' => 0, 'publishedby' => 0])
        ->and(PublishState::forEdit(1, 0, 0, PS_NOW, 7, true, $published))->toMatchArray(['publishedon' => 500, 'publishedby' => 4])
        ->and(PublishState::forEdit(1, PS_NOW - 50, 0, PS_NOW, 7, true, $published))->toMatchArray(['publishedon' => PS_NOW - 50, 'publishedby' => 7]);
});
