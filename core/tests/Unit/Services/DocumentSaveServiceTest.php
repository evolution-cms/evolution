<?php

use EvolutionCMS\Services\DocumentSave\DocumentSaveDenied;
use EvolutionCMS\Services\DocumentSaveService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Facade;
use Tests\Support\DocumentSaveDatabase;

afterEach(fn () => Facade::clearResolvedInstances());

const DS_NOW = 1_700_000_000;

/**
 *  1 folder (group 3, published)
 *   └ 2 page   (group 3, TV 1 = "old", TV 2 = "keep")
 *  4 other folder
 * Template 1 carries TVs 1 and 2; document group 3 is linked to a web user group.
 */
function bootSaveFixture(): Capsule
{
    $capsule = DocumentSaveDatabase::boot();
    foreach ([
        ['id' => 1, 'pagetitle' => 'folder', 'alias' => 'folder', 'isfolder' => 1, 'published' => 1, 'privateweb' => 1],
        ['id' => 2, 'pagetitle' => 'page', 'alias' => 'page', 'parent' => 1, 'template' => 1, 'published' => 1, 'publishedon' => 500, 'publishedby' => 4, 'privateweb' => 1],
        ['id' => 4, 'pagetitle' => 'other', 'alias' => 'other', 'isfolder' => 1],
    ] as $row) {
        Capsule::table('site_content')->insert($row);
    }
    Capsule::table('site_content_closure')->insert([
        ['ancestor' => 1, 'descendant' => 1, 'depth' => 0],
        ['ancestor' => 2, 'descendant' => 2, 'depth' => 0],
        ['ancestor' => 1, 'descendant' => 2, 'depth' => 1],
        ['ancestor' => 4, 'descendant' => 4, 'depth' => 0],
    ]);
    Capsule::table('site_tmplvars')->insert([
        ['id' => 1, 'name' => 'one', 'type' => 'text', 'default_text' => '', 'rank' => 1],
        ['id' => 2, 'name' => 'two', 'type' => 'text', 'default_text' => '', 'rank' => 2],
    ]);
    Capsule::table('site_tmplvar_templates')->insert([['tmplvarid' => 1, 'templateid' => 1], ['tmplvarid' => 2, 'templateid' => 1]]);
    Capsule::table('site_tmplvar_contentvalues')->insert([
        ['tmplvarid' => 1, 'contentid' => 2, 'value' => 'old'],
        ['tmplvarid' => 2, 'contentid' => 2, 'value' => 'keep'],
    ]);
    Capsule::table('document_groups')->insert([
        ['document_group' => 3, 'document' => 1],
        ['document_group' => 3, 'document' => 2],
    ]);
    Capsule::table('membergroup_access')->insert([['membergroup' => 1, 'documentgroup' => 3, 'context' => 1]]);

    return $capsule;
}

function saveForm(array $overrides = []): array
{
    return $overrides + [
        'id' => '', 'mode' => '4', 'pagetitle' => 'New page', 'alias' => '', 'type' => 'document',
        'parent' => '1', 'template' => '1', 'published' => '1', 'pub_date' => '', 'unpub_date' => '',
        'ta' => 'body', 'introtext' => '', 'longtitle' => '', 'description' => '', 'link_attributes' => '',
        'isfolder' => '0', 'richtext' => '1', 'menuindex' => '0', 'searchable' => '1', 'cacheable' => '1',
        'contentType' => 'text/html', 'content_dispo' => '0', 'hide_from_tree' => '0', 'menutitle' => '',
        'hidemenu' => '0', 'alias_visible' => '1', 'syncsite' => '1',
    ];
}

function dbSnapshot(): array
{
    return [
        'level' => Capsule::connection()->transactionLevel(),
        'titles' => Capsule::table('site_content')->orderBy('id')->pluck('pagetitle')->all(),
    ];
}

test('a new document is created with its tvs, inherited groups, folder flag and privacy in one go', function () {
    bootSaveFixture();
    $events = [];
    $service = new DocumentSaveService(DocumentSaveDatabase::context(
        config: ['use_udperms' => 1, 'friendly_urls' => 1, 'automatic_alias' => 1],
        permissions: ['publish_document'],
        role: 2,
        userGroups: [3],
        events: $events,
        snapshot: 'dbSnapshot',
    ));
    Capsule::table('site_content')->where('id', 4)->update(['isfolder' => 0]);

    $result = $service->save(saveForm(['parent' => '4', 'tv1' => 'first', 'tv2' => '', 'docgroups' => []]));

    $row = Capsule::table('site_content')->find($result->id);
    expect($result->isNew())->toBeTrue()
        ->and($result->alias)->toBe('new-page')
        ->and($row->pagetitle)->toBe('New page')
        ->and((int) $row->published)->toBe(1)
        ->and((int) $row->publishedby)->toBe(7)
        ->and((int) $row->createdby)->toBe(7)
        // createdon is not fillable; the creating hook of the model writes it, for the legacy processor too
        ->and((int) $row->createdon)->toBeGreaterThan(0)
        ->and($result->editedon)->toBe((int) $row->editedon)
        ->and($result->editedon)->toBeGreaterThan(0)
        ->and(Capsule::table('site_tmplvar_contentvalues')->where('contentid', $result->id)->pluck('value', 'tmplvarid')->all())->toBe([1 => 'first'])
        ->and(Capsule::table('site_content')->where('id', 4)->value('isfolder'))->toBe(1)
        ->and(Capsule::table('site_content_closure')->where('descendant', $result->id)->count())->toBe(2);

    // events: before fires with no row and outside a transaction, after fires once the row is committed
    expect(array_column($events, 0))->toBe(['OnBeforeDocFormSave', 'OnDocFormSave'])
        ->and($events[0][1])->toBe(['mode' => 'new', 'id' => ''])
        ->and($events[0][2])->toBe(['level' => 0, 'titles' => ['folder', 'page', 'other']])
        ->and($events[1][1])->toBe(['mode' => 'new', 'id' => $result->id])
        ->and($events[1][2])->toBe(['level' => 0, 'titles' => ['folder', 'page', 'other', 'New page']]);
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('OnBeforeDocFormSave still announces the id docid_incrmnt_method promises', function () {
    bootSaveFixture();
    $events = [];
    $service = new DocumentSaveService(DocumentSaveDatabase::context(config: ['docid_incrmnt_method' => 2], events: $events));
    $service->save(saveForm());

    $gaps = [];
    $service = new DocumentSaveService(DocumentSaveDatabase::context(config: ['docid_incrmnt_method' => 1], events: $gaps));
    $service->save(saveForm());

    // max + 1 with ids 1, 2, 4 is 5; the first gap is 3; the row itself is auto increment either way
    expect($events[0][1])->toBe(['mode' => 'new', 'id' => 5])
        ->and($gaps[0][1])->toBe(['mode' => 'new', 'id' => 3])
        ->and($events[1][1]['id'])->toBe(5)
        ->and($gaps[1][1]['id'])->toBe(6);
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('a new document under a private parent inherits its groups and becomes private', function () {
    bootSaveFixture();
    $service = new DocumentSaveService(DocumentSaveDatabase::context(config: ['use_udperms' => 1], permissions: ['publish_document'], role: 2, userGroups: [3]));

    $result = $service->save(saveForm(['parent' => '1']));

    expect(Capsule::table('document_groups')->where('document', $result->id)->pluck('document_group')->all())->toBe([3])
        ->and((int) Capsule::table('site_content')->where('id', $result->id)->value('privateweb'))->toBe(1);
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('without publish_document a new document is stored unpublished', function () {
    bootSaveFixture();
    $service = new DocumentSaveService(DocumentSaveDatabase::context(role: 2));

    $result = $service->save(saveForm(['published' => '1', 'pub_date' => '2020-01-01']));

    expect(Capsule::table('site_content')->find($result->id))->toMatchObject(['published' => 0, 'pub_date' => 0, 'publishedon' => 0]);
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('editing writes the tv diff, moves the document and fixes both folder flags', function () {
    $capsule = bootSaveFixture();
    $events = [];
    $service = new DocumentSaveService(DocumentSaveDatabase::context(
        config: ['use_udperms' => 1],
        permissions: ['publish_document', 'manage_groups'],
        events: $events,
        snapshot: 'dbSnapshot',
    ));

    $capsule->getConnection()->enableQueryLog();
    $result = $service->save(saveForm([
        'id' => '2', 'mode' => '27', 'pagetitle' => 'moved', 'alias' => 'moved', 'parent' => '4',
        'tv1' => 'new', 'tv2' => 'keep', 'docgroups' => ['3,1'],
    ]));
    $queries = count($capsule->getConnection()->getQueryLog());

    $row = Capsule::table('site_content')->find(2);
    expect($result->isNew())->toBeFalse()
        ->and($row->pagetitle)->toBe('moved')
        ->and((int) $row->parent)->toBe(4)
        ->and((int) $row->publishedon)->toBe(500)
        ->and(Capsule::table('site_tmplvar_contentvalues')->where('contentid', 2)->pluck('value', 'tmplvarid')->all())->toBe([1 => 'new', 2 => 'keep'])
        ->and(Capsule::table('document_groups')->where('document', 2)->pluck('document_group')->all())->toBe([3])
        // the old parent lost its last child, the new one gained one
        ->and((int) Capsule::table('site_content')->where('id', 1)->value('isfolder'))->toBe(0)
        ->and((int) Capsule::table('site_content')->where('id', 4)->value('isfolder'))->toBe(1)
        ->and(Capsule::table('site_content_closure')->where('descendant', 2)->where('ancestor', 4)->exists())->toBeTrue()
        ->and(array_column($events, 0))->toBe(['OnBeforeDocFormSave', 'OnDocFormSave'])
        ->and($events[1][1])->toBe(['mode' => 'upd', 'id' => 2])
        ->and($events[1][2]['level'])->toBe(0)
        // the legacy processor needed ~30 queries for this form
        ->and($queries)->toBeLessThanOrEqual(20);
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('an unchanged edit does not touch the tv table', function () {
    $capsule = bootSaveFixture();
    $service = new DocumentSaveService(DocumentSaveDatabase::context(permissions: ['publish_document']));

    $capsule->getConnection()->enableQueryLog();
    $service->save(saveForm(['id' => '2', 'mode' => '27', 'pagetitle' => 'page', 'alias' => 'page', 'tv1' => 'old', 'tv2' => 'keep']));

    $tvWrites = array_filter($capsule->getConnection()->getQueryLog(), fn ($q) => preg_match('/^(insert|update|delete)\b.*site_tmplvar_contentvalues/', $q['query']));
    expect($tvWrites)->toBe([]);
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('removing the last own group rolls the whole save back and fires no after event', function () {
    bootSaveFixture();
    $events = [];
    $service = new DocumentSaveService(DocumentSaveDatabase::context(
        config: ['use_udperms' => 1],
        permissions: ['publish_document', 'manage_document_permissions'],
        role: 2,
        userGroups: [3],
        events: $events,
    ));

    // chkalldocs: the group list is empty, which passes the early check but leaves the user with nothing
    $save = fn () => $service->save(saveForm(['id' => '2', 'mode' => '27', 'pagetitle' => 'changed', 'alias' => 'page', 'chkalldocs' => 'on', 'tv1' => 'changed']));

    expect($save)->toThrow(DocumentSaveDenied::class, 'resource_permissions_error')
        ->and(Capsule::table('site_content')->where('id', 2)->value('pagetitle'))->toBe('page')
        ->and(Capsule::table('site_tmplvar_contentvalues')->where('contentid', 2)->where('tmplvarid', 1)->value('value'))->toBe('old')
        ->and(Capsule::table('document_groups')->where('document', 2)->count())->toBe(1)
        ->and(array_column($events, 0))->toBe(['OnBeforeDocFormSave'])
        ->and(Capsule::connection()->transactionLevel())->toBe(0);
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('a duplicate alias is refused with the id of the other document', function () {
    bootSaveFixture();
    $service = new DocumentSaveService(DocumentSaveDatabase::context(config: ['friendly_urls' => 1]));

    try {
        $service->save(saveForm(['alias' => 'other']));
        $this->fail('expected a denial');
    } catch (DocumentSaveDenied $denied) {
        expect($denied->getMessage())->toBe('duplicate 4 other')
            ->and($denied->restoreForm)->toBeTrue();
    }
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('an automatic alias gets a counter while it collides', function () {
    bootSaveFixture();
    $service = new DocumentSaveService(DocumentSaveDatabase::context(config: ['friendly_urls' => 1, 'automatic_alias' => 1]));

    expect($service->save(saveForm(['pagetitle' => 'Other']))->alias)->toBe('other1')
        ->and($service->save(saveForm(['pagetitle' => 'Other']))->alias)->toBe('other2');
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('the parent permission is checked for new documents and on a move only', function () {
    bootSaveFixture();
    $asked = [];
    $context = DocumentSaveDatabase::context(
        config: ['use_udperms' => 1],
        permissions: ['publish_document'],
        canCreateIn: function (int $parent) use (&$asked) {
            $asked[] = $parent;
            return $parent !== 4;
        },
    );
    $service = new DocumentSaveService($context);

    // same parent: not asked
    $service->save(saveForm(['id' => '2', 'mode' => '27', 'alias' => 'page', 'parent' => '1']));
    expect($asked)->toBe([]);

    $save = fn () => $service->save(saveForm(['id' => '2', 'mode' => '27', 'alias' => 'page', 'parent' => '4']));
    expect($save)->toThrow(DocumentSaveDenied::class, 'access_permission_parent_denied')
        ->and($asked)->toBe([4])
        ->and((int) Capsule::table('site_content')->where('id', 2)->value('parent'))->toBe(1);
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('a non administrator cannot post a group list without one of their own groups', function () {
    bootSaveFixture();
    $service = new DocumentSaveService(DocumentSaveDatabase::context(config: ['use_udperms' => 1], role: 2, userGroups: [3]));

    $save = fn () => $service->save(saveForm(['docgroups' => ['9,new']]));

    expect($save)->toThrow(DocumentSaveDenied::class, 'resource_permissions_error')
        ->and(Capsule::table('site_content')->count())->toBe(3);
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('structural guards refuse a missing document, a self parent and a descendant parent', function () {
    bootSaveFixture();
    $service = new DocumentSaveService(DocumentSaveDatabase::context(config: ['site_start' => 1], permissions: ['publish_document']));

    $denied = function (array $form) use ($service): DocumentSaveDenied {
        try {
            $service->save(saveForm($form));
        } catch (DocumentSaveDenied $e) {
            return $e;
        }
        $this->fail('expected a denial');
    };

    expect($denied(['id' => '99', 'mode' => '27'])->getMessage())->toBe('error_no_results')
        ->and($denied(['id' => '99', 'mode' => '27'])->restoreForm)->toBeFalse()
        ->and($denied(['id' => '2', 'mode' => '27', 'parent' => '2'])->getMessage())->toContain('own parent')
        ->and($denied(['id' => '1', 'mode' => '27', 'parent' => '2'])->getMessage())->toContain('descendant')
        ->and($denied(['id' => '1', 'mode' => '27', 'parent' => '0', 'published' => '0'])->getMessage())->toContain('cannot be unpublished')
        ->and($denied(['id' => '1', 'mode' => '27', 'parent' => '0', 'unpub_date' => '2030-01-01'])->getMessage())->toContain('unpublish dates');
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('the save processor delegates to the service and keeps the redirect and cache logic', function () {
    $source = file_get_contents(dirname(__DIR__, 4) . '/manager/processors/save_content.processor.php');

    expect($source)->toContain('DocumentSaveService::forManager()->save($_POST)')
        ->and($source)->toContain('catch (\EvolutionCMS\Services\DocumentSave\DocumentSaveDenied $denied)')
        ->and($source)->toContain("hasPermission('save_document')")
        ->and($source)->toContain("clearCache('document')")
        ->and($source)->not->toContain('invokeEvent')
        ->and($source)->not->toContain('SiteTmplvarContentvalue');
});
