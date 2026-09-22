<?php

use EvolutionCMS\Support\DocumentSave\DocumentGroupSync;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Facade;
use Tests\Support\DocumentSaveDatabase;

afterEach(fn () => Facade::clearResolvedInstances());

function groupsOf(int $document): array
{
    return Capsule::table('document_groups')->where('document', $document)->orderBy('document_group')->pluck('document_group')->map(fn ($g) => (int) $g)->all();
}

test('posted pairs reduce to unique group ids', function () {
    expect(DocumentGroupSync::postedGroups(['3,new', '5,17', '3,new', ['bad']]))->toBe([3, 5]);
});

test('a user locks themselves out only when none of the chosen groups is theirs', function () {
    expect(DocumentGroupSync::locksOut([], [1]))->toBeFalse()
        ->and(DocumentGroupSync::locksOut([3, 5], [5]))->toBeFalse()
        ->and(DocumentGroupSync::locksOut([3, 5], [8]))->toBeTrue()
        ->and(DocumentGroupSync::locksOut([3], []))->toBeTrue();
});

test('without group permissions a new document inherits the groups of its parent', function () {
    DocumentSaveDatabase::boot();

    DocumentGroupSync::forNewDocument(50, ['3,new'], [7, 8], [3], false, false);

    expect(groupsOf(50))->toBe([7, 8]);
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('manage_groups attaches exactly the posted groups', function () {
    DocumentSaveDatabase::boot();

    DocumentGroupSync::forNewDocument(50, ['3,new', '5,new', '5,new'], [7], [], true, false);

    expect(groupsOf(50))->toBe([3, 5]);
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('manage_document_permissions keeps the parent groups the user cannot manage', function () {
    DocumentSaveDatabase::boot();

    // 9 is not the user's group and is dropped; 7 is a parent group outside the user's reach and stays
    DocumentGroupSync::forNewDocument(50, ['3,new', '9,new'], [7, 3], [3], false, true);

    expect(groupsOf(50))->toBe([3, 7]);
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('a user who picks none of their own groups keeps all groups of the parent', function () {
    DocumentSaveDatabase::boot();

    DocumentGroupSync::forNewDocument(50, [], [7, 3], [3], false, true);

    expect(groupsOf(50))->toBe([3, 7]);
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('editing inserts the new pairs and deletes the unchecked ones', function () {
    DocumentSaveDatabase::boot();
    Capsule::table('document_groups')->insert([
        ['id' => 1, 'document_group' => 3, 'document' => 50],
        ['id' => 2, 'document_group' => 4, 'document' => 50],
        ['id' => 3, 'document_group' => 4, 'document' => 51],
    ]);

    $kept = DocumentGroupSync::forExistingDocument(50, ['3,1', '5,new'], [], true, false);

    expect($kept)->toBeTrue()
        ->and(groupsOf(50))->toBe([3, 5])
        ->and(groupsOf(51))->toBe([4]);
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('a user without manage_groups cannot touch groups outside their own', function () {
    DocumentSaveDatabase::boot();
    Capsule::table('document_groups')->insert([
        ['id' => 1, 'document_group' => 3, 'document' => 50],
        ['id' => 2, 'document_group' => 8, 'document' => 50],
    ]);

    // tries to drop 8 (not theirs) and add 9 (not theirs), adds 5 (theirs)
    $kept = DocumentGroupSync::forExistingDocument(50, ['3,1', '5,new', '9,new'], [3, 5], false, false);

    expect($kept)->toBeTrue()
        ->and(groupsOf(50))->toBe([3, 5, 8]);
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('removing the last own group is refused before anything is written', function () {
    DocumentSaveDatabase::boot();
    Capsule::table('document_groups')->insert([
        ['id' => 1, 'document_group' => 3, 'document' => 50],
        ['id' => 2, 'document_group' => 8, 'document' => 50],
    ]);

    $kept = DocumentGroupSync::forExistingDocument(50, [], [3], false, false);

    expect($kept)->toBeFalse()
        ->and(groupsOf(50))->toBe([3, 8]);
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('making the document public removes every group', function () {
    DocumentSaveDatabase::boot();
    Capsule::table('document_groups')->insert([
        ['id' => 1, 'document_group' => 3, 'document' => 50],
        ['id' => 2, 'document_group' => 8, 'document' => 50],
    ]);

    expect(DocumentGroupSync::forExistingDocument(50, [], [], true, true))->toBeTrue()
        ->and(groupsOf(50))->toBe([]);
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');
