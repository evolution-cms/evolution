<?php

use EvolutionCMS\Models\SiteContent;
use EvolutionCMS\Support\MoveDocumentTargetGuard;

test('move document target guard blocks missing or deleted parents', function () {
    $deletedParent = new SiteContent();
    $deletedParent->deleted = 1;

    $activeParent = new SiteContent();
    $activeParent->deleted = 0;

    expect(MoveDocumentTargetGuard::blocksParent(null))->toBeTrue()
        ->and(MoveDocumentTargetGuard::blocksParent($deletedParent))->toBeTrue()
        ->and(MoveDocumentTargetGuard::blocksParent($activeParent))->toBeFalse();
});
