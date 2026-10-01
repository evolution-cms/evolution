<?php

/*
|--------------------------------------------------------------------------
| The connection the installer writes
|--------------------------------------------------------------------------
|
| On MySQL and MariaDB a query is one round trip (client-side prepares) and the
| connection does not issue a separate `use` for the database its DSN names.
| Other drivers keep PDO's defaults.
|
*/

/** The installed default connection for a database type, with every other placeholder empty. */
function installedConnection(string $databaseType): array
{
    $template = (string) file_get_contents(dirname(__DIR__, 4) . '/install/stubs/files/config/database/connections/default.tpl');
    $php = preg_replace('/\[\+\w+\+\]/', '', str_replace('[+database_type+]', $databaseType, $template));
    $file = tempnam(sys_get_temp_dir(), 'evo_connection_');
    file_put_contents($file, $php);
    try {
        return require $file;
    } finally {
        unlink($file);
    }
}

it('connects to MySQL and MariaDB with client-side prepares and without a use statement', function (string $type) {
    $connection = installedConnection($type);

    expect($connection['driver'])->toBe($type)
        ->and($connection['use_db_after_connecting'])->toBeFalse()
        ->and($connection['options'][PDO::ATTR_EMULATE_PREPARES])->toBeTrue()
        ->and($connection['options'][PDO::ATTR_STRINGIFY_FETCHES])->toBeTrue();
})->with(['mysql', 'mariadb']);

it('leaves the prepares of other drivers to PDO', function (string $type) {
    $connection = installedConnection($type);

    expect($connection['options'])->not->toHaveKey(PDO::ATTR_EMULATE_PREPARES)
        ->and($connection['options'][PDO::ATTR_STRINGIFY_FETCHES])->toBeTrue();
})->with(['pgsql', 'sqlite']);
