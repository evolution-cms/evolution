<?php

use EvolutionCMS\Support\DocumentPrivacy;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Facade;
use Tests\Support\DocumentSaveDatabase;

afterEach(fn () => Facade::clearResolvedInstances());

/**
 * Group 3 is linked to a web user group, group 4 to a manager user group, group 5 to nothing.
 * Document 10 is in 3, 11 in 4, 12 in 5, 13 in 3 and 4, 14 has no group but stale flags.
 */
function bootPrivacyFixture(): Capsule
{
    $capsule = DocumentSaveDatabase::boot();
    foreach ([
        ['id' => 10, 'pagetitle' => 'web'],
        ['id' => 11, 'pagetitle' => 'mgr'],
        ['id' => 12, 'pagetitle' => 'unlinked group'],
        ['id' => 13, 'pagetitle' => 'both', 'deleted' => 1],
        ['id' => 14, 'pagetitle' => 'stale', 'privateweb' => 1, 'privatemgr' => 1],
    ] as $row) {
        Capsule::table('site_content')->insert($row);
    }
    Capsule::table('document_groups')->insert([
        ['document_group' => 3, 'document' => 10],
        ['document_group' => 4, 'document' => 11],
        ['document_group' => 5, 'document' => 12],
        ['document_group' => 3, 'document' => 13],
        ['document_group' => 4, 'document' => 13],
    ]);
    Capsule::table('membergroup_access')->insert([
        ['membergroup' => 1, 'documentgroup' => 3, 'context' => DocumentPrivacy::WEB],
        ['membergroup' => 2, 'documentgroup' => 4, 'context' => DocumentPrivacy::MANAGER],
    ]);

    return $capsule;
}

function privacyOf(int $id): array
{
    $row = Capsule::table('site_content')->where('id', $id)->first(['privateweb', 'privatemgr']);

    return [(int) $row->privateweb, (int) $row->privatemgr];
}

test('refresh sets both flags of one document from the linked user groups', function () {
    $capsule = bootPrivacyFixture();
    $capsule->getConnection()->enableQueryLog();

    foreach ([10, 11, 12, 13, 14] as $id) {
        DocumentPrivacy::refresh($id);
    }

    expect($capsule->getConnection()->getQueryLog())->toHaveCount(10)
        ->and(privacyOf(10))->toBe([1, 0])
        ->and(privacyOf(11))->toBe([0, 1])
        ->and(privacyOf(12))->toBe([0, 0])
        // trashed documents are refreshed too
        ->and(privacyOf(13))->toBe([1, 1])
        ->and(privacyOf(14))->toBe([0, 0]);
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('refresh with a context leaves the other flag alone, as the legacy include did', function () {
    bootPrivacyFixture();

    DocumentPrivacy::refresh(14, DocumentPrivacy::WEB);
    expect(privacyOf(14))->toBe([0, 1]);

    DocumentPrivacy::refresh(14, DocumentPrivacy::MANAGER);
    expect(privacyOf(14))->toBe([0, 0]);
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('refreshAll recomputes one flag for the whole site and leaves the other alone', function () {
    bootPrivacyFixture();

    DocumentPrivacy::refreshAll(DocumentPrivacy::WEB);

    expect(privacyOf(10))->toBe([1, 0])
        ->and(privacyOf(13))->toBe([1, 0])
        ->and(privacyOf(14))->toBe([0, 1]);

    DocumentPrivacy::refreshAll(DocumentPrivacy::MANAGER);

    expect(privacyOf(11))->toBe([0, 1])
        ->and(privacyOf(13))->toBe([1, 1])
        ->and(privacyOf(14))->toBe([0, 0]);
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('the legacy include delegates to DocumentPrivacy', function () {
    $source = file_get_contents(dirname(__DIR__, 4) . '/manager/includes/secure_web_documents.inc.php');

    expect($source)->toContain('DocumentPrivacy::refresh((int) $docid, $context)')
        ->and($source)->toContain('DocumentPrivacy::refreshAll($context)')
        ->and($source)->toContain('@deprecated since 3.5.9');
});
