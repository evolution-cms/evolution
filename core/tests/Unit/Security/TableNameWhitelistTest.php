<?php

/*
|--------------------------------------------------------------------------
| Table identifier validation
|--------------------------------------------------------------------------
|
| a=54 took the table to OPTIMIZE or TRUNCATE straight out of $_REQUEST and put it into raw SQL,
| and the backup manager passed its checkbox list on to pg_dump on a command line. Both now go
| through Database::isValidTableName().
|
| The pattern is the whole guard. Nothing that matches it can leave the identifier it is
| substituted into, which is why existence is not checked as well: an unknown table is a failing
| statement, not an injection, and a catalogue lookup would tie every optimize to schema read
| rights the hosting may not grant.
|
| @since 3.5.8
*/

use EvolutionCMS\Database;
use Illuminate\Database\Capsule\Manager as Capsule;

function tableNameDatabase(string $prefix): Database
{
    $db = new Database();
    $db->addConnection([
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => $prefix,
    ]);
    $db->setAsGlobal();

    return $db;
}

afterEach(function () {
    Capsule::connection()->disconnect();
});

describe('Database::isValidTableName()', function () {

    test('accepts a bare prefixed identifier', function () {
        $db = tableNameDatabase('evo_');

        expect($db->isValidTableName('evo_users'))->toBeTrue()
            ->and($db->isValidTableName('evo_manager_log'))->toBeTrue();
    });

    test('rejects a name outside the prefix', function () {
        expect(tableNameDatabase('evo_')->isValidTableName('other_app_sessions'))->toBeFalse();
    });

    test('rejects everything that could end the identifier', function () {
        $db = tableNameDatabase('evo_');

        // OPTIMIZE TABLE / TRUNCATE take the name unquoted, and pg_dump takes it as a -t argument
        // on a command line, so a break-out needs one of these characters to survive.
        expect($db->isValidTableName('evo_users; DROP TABLE evo_users'))->toBeFalse()
            ->and($db->isValidTableName('evo_users`, (SELECT 1)'))->toBeFalse()
            ->and($db->isValidTableName("evo_users' UNION SELECT 1"))->toBeFalse()
            ->and($db->isValidTableName('evo_users WHERE 1=1'))->toBeFalse()
            ->and($db->isValidTableName('evo_users`id`'))->toBeFalse()
            ->and($db->isValidTableName('evo_users$(id)'))->toBeFalse()
            ->and($db->isValidTableName("evo_users\nDROP"))->toBeFalse()
            ->and($db->isValidTableName('evo_db.evo_users'))->toBeFalse();
    });

    test('rejects anything that is not a non-empty string', function () {
        $db = tableNameDatabase('evo_');

        expect($db->isValidTableName(['evo_users']))->toBeFalse()
            ->and($db->isValidTableName(null))->toBeFalse()
            ->and($db->isValidTableName(''))->toBeFalse();
    });

    test('still refuses metacharacters when no prefix is configured', function () {
        $db = tableNameDatabase('');

        expect($db->isValidTableName('users'))->toBeTrue()
            ->and($db->isValidTableName('users; DROP TABLE users'))->toBeFalse();
    });

    test('does not query the database', function () {
        $db = tableNameDatabase('evo_');
        $connection = $db->getConnection();
        $connection->enableQueryLog();

        $db->isValidTableName('evo_users');

        expect($connection->getQueryLog())->toBe([]);
    });
});

describe('call sites', function () {

    test('a=54 validates before optimizing or truncating', function () {
        $source = file_get_contents(__DIR__ . '/../../../../manager/processors/optimize_table.processor.php');

        expect($source)
            ->toContain("isValidTableName(\$_REQUEST['t'])")
            ->toContain("isValidTableName(\$_REQUEST['u'])")
            // The name still has to bypass the builder's prefixing, so it stays an expression -
            // the validation above is what makes that safe.
            ->toContain("\DB::table(\DB::raw(\$_REQUEST['u']))");
    });

    test('the backup manager drops invalid names from the checkbox list', function () {
        $source = file_get_contents(__DIR__ . '/../../../../manager/actions/bkmanager.static.php');

        expect($source)->toContain('$db->isValidTableName($table)');
    });
});
