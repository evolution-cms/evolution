<?php

use EvolutionCMS\Models\FileGroup;

test('file groups migration owns the file_groups table definition', function () {
    $dedicatedMigration = (string) file_get_contents(dirname(__DIR__, 2) . '/database/migrations/2026_03_29_000000_create_file_groups_table.php');
    $stubMigration = (string) file_get_contents(dirname(__DIR__, 3) . '/install/stubs/migrations/2026_03_29_000000_create_file_groups_table.php');
    $initialSchemaMigration = (string) file_get_contents(dirname(__DIR__, 2) . '/database/migrations/2025_12_25_000000_initial_schema.php');

    expect($dedicatedMigration)->toContain("Schema::create('file_groups'")
        ->and($dedicatedMigration)->toContain("Schema::hasTable('file_groups')")
        ->and($dedicatedMigration)->toContain("Schema::dropIfExists('file_groups')")
        ->and($dedicatedMigration)->toContain("\$table->unique(['document_group', 'file']")
        ->and($stubMigration)->toContain("Schema::hasTable('file_groups')")
        ->and($stubMigration)->toContain("Schema::dropIfExists('file_groups')")
        ->and($initialSchemaMigration)->not->toContain("createTableIfMissing('file_groups'");
});

test('file group model remains a simple non timestamped acl pivot', function () {
    $model = new FileGroup();

    expect($model->timestamps)->toBeFalse()
        ->and($model->getFillable())->toBe(['document_group', 'file'])
        ->and($model->getCasts())->toHaveKey('document_group', 'int');
});

test('document group model exposes file group relation', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 2) . '/src/Models/DocumentgroupName.php');

    expect($source)->toContain('public function fileGroups(): Eloquent\Relations\HasMany')
        ->and($source)->toContain("return \$this->hasMany(FileGroup::class, 'document_group');");
});
