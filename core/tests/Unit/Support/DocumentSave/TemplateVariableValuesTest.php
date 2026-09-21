<?php

use EvolutionCMS\Support\DocumentSave\TemplateVariableValues;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Facade;
use Tests\Support\DocumentSaveDatabase;

afterEach(fn () => Facade::clearResolvedInstances());

/**
 * Template 1 has TVs 1..3; TV 3 is restricted to document group 9. Document 100 stores TVs 1 and 2.
 */
function bootTvFixture(): Capsule
{
    $capsule = DocumentSaveDatabase::boot();
    Capsule::table('site_tmplvars')->insert([
        ['id' => 1, 'name' => 'one', 'type' => 'text', 'default_text' => '', 'rank' => 2],
        ['id' => 2, 'name' => 'two', 'type' => 'text', 'default_text' => 'd2', 'rank' => 1],
        ['id' => 3, 'name' => 'three', 'type' => 'text', 'default_text' => '', 'rank' => 3],
        ['id' => 4, 'name' => 'other-template', 'type' => 'text', 'default_text' => '', 'rank' => 0],
    ]);
    Capsule::table('site_tmplvar_templates')->insert([
        ['tmplvarid' => 1, 'templateid' => 1], ['tmplvarid' => 2, 'templateid' => 1],
        ['tmplvarid' => 3, 'templateid' => 1], ['tmplvarid' => 4, 'templateid' => 2],
    ]);
    Capsule::table('site_tmplvar_access')->insert([['tmplvarid' => 3, 'documentgroup' => 9]]);
    Capsule::table('site_tmplvar_contentvalues')->insert([
        ['id' => 10, 'tmplvarid' => 1, 'contentid' => 100, 'value' => 'v1'],
        ['id' => 11, 'tmplvarid' => 2, 'contentid' => 100, 'value' => 'v2'],
        ['id' => 12, 'tmplvarid' => 1, 'contentid' => 200, 'value' => 'other doc'],
    ]);

    return $capsule;
}

test('an administrator gets every tv of the template, ordered by rank, with the stored value', function () {
    bootTvFixture();

    $rows = TemplateVariableValues::forTemplate(1, 100, false, []);

    expect(array_column($rows, 'id'))->toBe([2, 1, 3])
        ->and($rows[0])->toMatchArray(['value_id' => 11, 'value' => 'v2', 'default_text' => 'd2'])
        ->and($rows[2])->toMatchArray(['value_id' => null, 'value' => null]);
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('a restricted tv is hidden from a user whose document is not in its group', function () {
    bootTvFixture();

    expect(array_column(TemplateVariableValues::forTemplate(1, 100, true, [5]), 'id'))->toBe([2, 1]);

    Capsule::table('document_groups')->insert(['document_group' => 5, 'document' => 100]);
    Capsule::table('site_tmplvar_contentvalues')->insert(['tmplvarid' => 3, 'contentid' => 100, 'value' => 'v3']);

    expect(array_column(TemplateVariableValues::forTemplate(1, 100, true, [5]), 'id'))->toBe([2, 1, 3]);
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('sync writes only the differences and leaves other documents alone', function () {
    $capsule = bootTvFixture();
    $tvs = TemplateVariableValues::forTemplate(1, 100, false, []);

    $capsule->getConnection()->enableQueryLog();
    // TV 1 unchanged, TV 2 removed, TV 3 added
    TemplateVariableValues::sync(100, $tvs, [1 => 'v1', 2 => null, 3 => 'new']);

    $log = array_column($capsule->getConnection()->getQueryLog(), 'query');
    expect($log)->toHaveCount(2)
        ->and($log[0])->toStartWith('insert into')
        ->and($log[1])->toStartWith('delete from')
        ->and(Capsule::table('site_tmplvar_contentvalues')->where('contentid', 100)->orderBy('tmplvarid')->pluck('value', 'tmplvarid')->all())
        ->toBe([1 => 'v1', 3 => 'new'])
        ->and(Capsule::table('site_tmplvar_contentvalues')->where('id', 12)->value('value'))->toBe('other doc');
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('a changed value is updated in place and an unchanged form costs no query', function () {
    $capsule = bootTvFixture();
    $tvs = TemplateVariableValues::forTemplate(1, 100, false, []);

    $capsule->getConnection()->enableQueryLog();
    TemplateVariableValues::sync(100, $tvs, [1 => 'v1', 2 => 'v2', 3 => null]);
    expect($capsule->getConnection()->getQueryLog())->toBe([]);

    TemplateVariableValues::sync(100, $tvs, [1 => 'changed', 2 => 'v2', 3 => null]);
    expect($capsule->getConnection()->getQueryLog())->toHaveCount(1)
        ->and(Capsule::table('site_tmplvar_contentvalues')->where('id', 10)->value('value'))->toBe('changed');
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');
