<?php

use EvolutionCMS\Support\FileManagerAccess;

test('file manager access requires every restricted ancestor to match', function () {
    $restrictions = [
        'articles' => [10],
        'articles/one' => [11],
        'articles-common/onecommon' => [11],
    ];

    expect(FileManagerAccess::isAccessible('articles', [10], $restrictions))->toBeTrue()
        ->and(FileManagerAccess::isAccessible('articles/one', [10], $restrictions))->toBeFalse()
        ->and(FileManagerAccess::isAccessible('articles/one', [10, 11], $restrictions))->toBeTrue()
        ->and(FileManagerAccess::isAccessible('articles-common/onecommon', [11], $restrictions))->toBeTrue();
});

test('file manager access collects inherited effective groups for display', function () {
    $restrictions = [
        'articles' => [10],
        'articles/one' => [11],
        'articles/one/child' => [11, 12],
    ];

    expect(FileManagerAccess::effectiveGroupIds('articles/one/child', $restrictions))
        ->toBe([10, 11, 12]);
});

test('file manager access prevents modifying top level entries', function () {
    $restrictions = [
        'articles' => [10],
        'articles/one' => [11],
    ];

    expect(FileManagerAccess::canModifyExistingPath('articles', [10], $restrictions))->toBeFalse()
        ->and(FileManagerAccess::canModifyExistingPath('articles/one', [10, 11], $restrictions))->toBeTrue()
        ->and(FileManagerAccess::canModifyExistingPath('articles/one', [10], $restrictions))->toBeFalse();
});
