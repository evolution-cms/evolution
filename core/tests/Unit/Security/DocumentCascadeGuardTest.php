<?php

use EvolutionCMS\Support\DocumentCascadeGuard;

/*
|--------------------------------------------------------------------------
| Branch actions must check every document they touch, not only the entry id
|--------------------------------------------------------------------------
|
| deniedIds() is the pure decision behind the delete, undelete, menuindex-sort
| and drag-and-drop guards. Given each document's privatemgr flag it returns the
| ids the actor may not reach. These cases pin that an administrator and an
| install with permissions off reach everything, while a non-admin is refused the
| private documents whose group it is not a member of - including descendants that
| a single check on the parent would have missed.
|
*/

test('an administrator reaches every document in the cascade', function () {
    $documents = [10 => 1, 11 => 1, 12 => 0];

    expect(DocumentCascadeGuard::deniedIds($documents, 1, 1, []))->toBe([]);
});

test('with permissions off every document is reachable', function () {
    $documents = [10 => 1, 11 => 1];

    expect(DocumentCascadeGuard::deniedIds($documents, 2, 0, []))->toBe([]);
});

test('a non-admin is refused private documents outside its groups', function () {
    // 10 public, 11 private but a member, 12 private and not a member
    $documents = [10 => 0, 11 => 1, 12 => 1];

    expect(DocumentCascadeGuard::deniedIds($documents, 2, 1, [11]))
        ->toBe([12]);
});

test('a restricted descendant is caught even when the parent is public', function () {
    // parent 10 public and reachable, child 11 private to a group the actor lacks
    $documents = [10 => 0, 11 => 1];

    expect(DocumentCascadeGuard::deniedIds($documents, 2, 1, []))
        ->toBe([11]);
});

test('public documents alone leave nothing denied', function () {
    $documents = [10 => 0, 11 => 0, 12 => 0];

    expect(DocumentCascadeGuard::deniedIds($documents, 2, 1, []))->toBe([]);
});
