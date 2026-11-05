<?php

/*
|--------------------------------------------------------------------------
| Installer identifier validation
|--------------------------------------------------------------------------
|
| The installer splices the database name, collation and table prefix into CREATE DATABASE,
| SELECT ... FROM {prefix}site_content and the generated connection config. Its validators
| compared preg_match() with false, which only happens on a regex error, so a mismatch passed
| straight through and every one of them accepted anything.
|
| @since 3.5.8
*/

require_once dirname(__DIR__, 4) . '/install/src/functions.php';

function installerRoot(string $path): string
{
    return (string) file_get_contents(dirname(__DIR__, 4) . '/' . $path);
}

describe('validateDbName()', function () {

    test('accepts ordinary database names', function () {
        expect(validateDbName('evo'))->toBe('evo')
            ->and(validateDbName('my_site-2'))->toBe('my_site-2')
            ->and(validateDbName('1site'))->toBe('1site')
            ->and(validateDbName('app$db'))->toBe('app$db')
            ->and(validateDbName(' `evo` '))->toBe('evo');
    });

    test('rejects names that could leave their quotes', function (string $name) {
        expect(fn () => validateDbName($name))->toThrow(InvalidArgumentException::class);
    })->with([
        'backtick' => ['evo`; DROP DATABASE mysql; -- '],
        'double quote' => ['evo" WITH OWNER postgres'],
        'single quote' => ["evo' OR 1=1"],
        'space' => ['evo site'],
        'semicolon' => ['evo;'],
        'newline' => ["evo\nDROP"],
        'path' => ['../evo'],
        'empty' => [''],
    ]);

    test('rejects names of 64 characters or more', function () {
        expect(fn () => validateDbName(str_repeat('a', 64)))->toThrow(InvalidArgumentException::class);
    });

    test('lets a SQLite file sit in a folder below core/database', function () {
        expect(validateDbName('sqlite/evolution', 'sqlite'))->toBe('sqlite/evolution')
            ->and(validateDbName('a/b/evolution', 'sqlite'))->toBe('a/b/evolution')
            ->and(validateDbName('evolution', 'sqlite'))->toBe('evolution');
    });

    test('a folder stays a SQLite thing and never climbs out of core/database', function (string $name, string $type) {
        expect(fn () => validateDbName($name, $type))->toThrow(InvalidArgumentException::class);
    })->with([
        'mysql' => ['sqlite/evolution', 'mysql'],
        'pgsql' => ['sqlite/evolution', 'pgsql'],
        'no type' => ['sqlite/evolution', ''],
        'parent' => ['../evolution', 'sqlite'],
        'parent inside' => ['a/../../evolution', 'sqlite'],
        'absolute' => ['/etc/evolution', 'sqlite'],
        'trailing slash' => ['evolution/', 'sqlite'],
        'backslash' => ['a\\evolution', 'sqlite'],
    ]);
});

describe('validateDbHost()', function () {

    test('accepts addresses, hostnames and Docker service names', function (string $host) {
        expect(validateDbHost($host, 'mysql'))->toBe($host);
    })->with([
        'hostname' => ['db.example.com'],
        'hostname with a port' => ['db.example.com:3307'],
        'address' => ['10.0.0.5'],
        'service name' => ['project_service-db'],
        'service name with a port' => ['project_service-db:3306'],
    ]);

    test('rejects what is no host', function (string $host) {
        expect(fn () => validateDbHost($host, 'mysql'))->toThrow(InvalidArgumentException::class);
    })->with([
        'space' => ['db host'],
        'quote' => ["db'host"],
        'slash' => ['db/host'],
        'dollar' => ['db$host'],
        'label ending in a dash' => ['db-.example.com'],
        'label ending in an underscore' => ['db_.example.com'],
    ]);
});

describe('validateDbCollation()', function () {

    test('accepts MySQL, PostgreSQL and SQLite collations', function (string $collation) {
        expect(validateDbCollation($collation))->toBe($collation);
    })->with([
        'utf8mb4_unicode_ci', 'utf8mb4_0900_ai_ci', 'en_US.UTF-8', 'en_US.utf8', 'C', 'C.UTF-8',
        'en-US-x-icu', 'sr_RS@latin', 'utf8', '',
    ]);

    test('rejects collations carrying SQL', function (string $collation) {
        expect(fn () => validateDbCollation($collation))->toThrow(InvalidArgumentException::class);
    })->with([
        'unquoted clause' => ['utf8mb4_general_ci; DROP DATABASE mysql'],
        'quote break' => ["en_US.utf8' TEMPLATE template1 --"],
        'comment' => ['utf8mb4_bin/**/'],
        'leading digit' => ['1collation'],
    ]);
});

describe('validateTablePrefix()', function () {

    test('accepts the prefixes the installer generates or allows', function () {
        expect(validateTablePrefix('evo_'))->toBe('evo_')
            ->and(validateTablePrefix('a1b2_'))->toBe('a1b2_')
            ->and(validateTablePrefix(''))->toBe('')
            ->and(validateTablePrefix(null))->toBe('');
    });

    test('rejects prefixes that would change the statement or the config file', function (string $prefix) {
        expect(fn () => validateTablePrefix($prefix))->toThrow(InvalidArgumentException::class);
    })->with([
        'statement' => ['x; DROP TABLE users; --'],
        'subquery' => ['(SELECT 1)'],
        'quote' => ["evo_'"],
        'php' => ['evo_\'.phpinfo().\''],
        'too long' => [str_repeat('a', 45)],
    ]);
});

describe('call sites', function () {

    test('every controller tells validateDbName() the database type', function (string $file) {
        expect(installerRoot($file))
            ->toMatch('/validateDbName\(\$_POST\[\'database_name\'\], \$(driver|database_type)\)/');
    })->with([
        'install/src/controllers/connection.php',
        'install/src/controllers/connection/collation.php',
        'install/src/controllers/connection/databasetest.php',
        'install/src/controllers/install.php',
        'install/src/controllers/options.php',
        'install/src/controllers/summary.php',
    ]);

    test('the database test validates the collation before it reaches CREATE DATABASE', function () {
        expect(installerRoot('install/src/controllers/connection/databasetest.php'))
            ->toContain("\$database_collation = validateDbCollation(\$_POST['database_collation'] ?? '');")
            ->not->toContain("\$database_collation = \$_POST['database_collation'];");
    });

    test('the database test reports a rejected value instead of dying on it', function () {
        // The validators never threw before, so nothing here caught them.
        expect(installerRoot('install/src/controllers/connection/databasetest.php'))
            ->toMatch('/try \{\s+\$driver = validateDbType.*?\} catch \(InvalidArgumentException \$e\) \{\s+exit\(\$output \. \'<span id="database_fail">\'/s');
    });

    test('the installer validates the prefix it writes into the connection config', function () {
        expect(installerRoot('install/src/controllers/install.php'))
            ->toContain("addslashes(validateTablePrefix(\$_POST['tableprefix'] ?? ''))");
    });

    test('the legacy store processor casts ids and escapes event names', function () {
        $source = installerRoot('assets/modules/store/installer/instprocessor.php');

        expect($source)
            ->toContain('$id = (int) $row["id"];')
            ->toContain("\$templateId = (int) \$tRow['id'];")
            ->toContain('$prev_id = (int) $prev_id;')
            ->toContain("evo()->getDatabase()->escape(array_map('trim', \$events))")
            ->not->toContain("WHERE id='{\$row['id']}'")
            ->not->toContain('DELETE FROM $dbase.');
    });
});
