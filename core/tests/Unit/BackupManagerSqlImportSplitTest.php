<?php

require_once __DIR__ . '/../../functions/actions/bkmanager.php';

test('backup manager import keeps semicolons inside sqlite string literals', function () {
    $sql = <<<'SQL'
-- Dumping data for table `evo_site_tmplvars`
INSERT INTO "evo_site_tmplvars" VALUES
  ('3','checkbox','tags','tags','','0','1','0','@EVAL return ''||''.EvolutionCMS\Models\SiteContent::whereIn(''parent'',[2,3])->where(''isfolder'',0)->get()->map(function ($product) {
    return "{$product->pagetitle} ({$product->id})=={$product->id}";
})->implode(''||'');','0',NULL,NULL,'0','1776962019','1776971909',NULL);

CREATE TABLE "after_restore" ("id" integer not null primary key autoincrement);
SQL;

    $statements = import_sql_split_statements($sql);

    expect($statements)->toHaveCount(2)
        ->and($statements[0])->toContain('@EVAL return')
        ->and($statements[0])->toContain('return "{$product->pagetitle} ({$product->id})=={$product->id}";')
        ->and($statements[1])->toContain('CREATE TABLE "after_restore"');
});

test('backup manager import ignores semicolons inside quoted values and comments', function () {
    $sql = <<<'SQL'
INSERT INTO "sample" VALUES ('single; quote', "double; quote");
-- comment with ; delimiter
/* block ; comment */
INSERT INTO `sample` VALUES (`identifier; value`, 'done');
SQL;

    $statements = import_sql_split_statements($sql);

    expect($statements)->toHaveCount(2)
        ->and($statements[0])->toBe('INSERT INTO "sample" VALUES (\'single; quote\', "double; quote")')
        ->and($statements[1])->toContain('INSERT INTO `sample` VALUES (`identifier; value`, \'done\')');
});

test('backup manager import keeps serialized sqlite settings values together', function () {
    $sql = <<<'SQL'
INSERT INTO "evo_system_settings" VALUES
  (1,'sys_files_checksum','a:4:{s:36:"D:/OSPanel/domains/site.loc/index.php";s:32:"checksum;with;semicolons";s:44:"D:/OSPanel/domains/site.loc/core/vendor.php";s:14:"quoted \"value\"";}',NULL);
CREATE TABLE "after_restore" ("id" integer not null primary key autoincrement);
SQL;

    $statements = import_sql_split_statements($sql);

    expect($statements)->toHaveCount(2)
        ->and($statements[0])->toContain('sys_files_checksum')
        ->and($statements[0])->toContain('checksum;with;semicolons')
        ->and($statements[1])->toContain('CREATE TABLE "after_restore"');
});

test('backup manager executes sqlite restore dumps as one script', function () {
    $source = file_get_contents(__DIR__ . '/../../functions/actions/bkmanager.php');

    expect($source)->toContain('in_array($driver, [\'sqlite\', \'sqlite3\'], true)')
        ->and($source)->toContain('\\DB::connection()->getPdo()->exec($source)')
        ->and($source)->toContain('import_sql(file_get_contents($path), $result_code)');
});
