<?php

/*
|--------------------------------------------------------------------------
| Backup manager: no shell, no dump in the web root
|--------------------------------------------------------------------------
|
| The PostgreSQL branches of the backup manager built psql and pg_dump command lines by
| concatenation and ran them through exec(). The snapshot name from the restore form and the
| table names from the checkbox list went in unquoted, so anyone holding bk_manager could append
| a command of their own - a step up from that permission's intended reach, which is arbitrary
| SQL, not arbitrary shell. The password rode along in argv, where the process list shows it.
|
| Everything now goes through DatabaseBackupService, which invokes the client with an argument
| list and the password in the environment: no shell parses either, so nothing needs quoting.
|
| @since 3.5.8
*/

use EvolutionCMS\Services\DatabaseBackupService;

/**
 * Exposes the process builder and stands in for the connection settings, so the invocation can
 * be inspected without a bootstrapped application.
 */
class InspectableBackupService extends DatabaseBackupService
{
    public $config = [
        'host' => 'db.example.test',
        'username' => 'evo',
        'database' => 'evo_site',
        'password' => 'sup3r-s3cret',
    ];

    protected function databaseConfig()
    {
        return $this->config;
    }

    public function inspectProcess($binary, array $arguments)
    {
        return $this->buildPostgresProcess($binary, $arguments);
    }
}

describe('pg client invocation', function () {

    test('the password travels in the environment, not on the command line', function () {
        $process = (new InspectableBackupService())->inspectProcess('pg_dump', ['--clean']);

        expect($process->getEnv())->toBe(['PGPASSWORD' => 'sup3r-s3cret'])
            // argv is world readable through ps and /proc/<pid>/cmdline.
            ->and($process->getCommandLine())->not->toContain('sup3r-s3cret');
    });

    test('an argument carrying shell metacharacters stays a single argument', function () {
        $table = 'evo_users; rm -rf /';
        $line = (new InspectableBackupService())->inspectProcess('pg_dump', ['--table', $table])
            ->getCommandLine();

        // Both escaping styles Symfony uses - sh and cmd.exe - wrap the whole value in quotes,
        // so the semicolon never reaches a command parser.
        expect(str_contains($line, '"' . $table . '"') || str_contains($line, "'" . $table . "'"))
            ->toBeTrue();
    });

    test('the connection settings are passed as separate arguments', function () {
        $line = (new InspectableBackupService())->inspectProcess('psql', ['--file', '/tmp/x.sql'])
            ->getCommandLine();

        expect($line)
            ->toContain('db.example.test')
            ->toContain('evo_site')
            // The old form put credentials into a postgresql://user:password@host URI.
            ->not->toContain('postgresql://');
    });
});

describe('deny rule for dump directories', function () {

    test('covers both Apache generations', function () {
        expect(DatabaseBackupService::DENY_HTACCESS)
            ->toContain('mod_authz_core')
            ->toContain('Require all denied')
            // Order/Deny alone is 2.2 syntax: on 2.4 without mod_access_compat it is a 500,
            // which serves the directory instead of denying it.
            ->toContain('Deny from all');
    });
});

describe('backup manager call sites', function () {

    test('no shell is spawned any more', function () {
        $source = file_get_contents(__DIR__ . '/../../../../manager/actions/bkmanager.static.php');

        expect($source)
            ->not->toContain('exec($dump_request')
            ->not->toContain('PGPASSWORD=')
            ->toContain('->restorePostgresFile(')
            ->toContain('->dumpPostgresTables(');
    });

    test('no dump is written under the web root', function () {
        $source = file_get_contents(__DIR__ . '/../../../../manager/actions/bkmanager.static.php');

        // assets/backup/temp.php is served by Apache: the default ht.access excludes assets/
        // from every rule it has, so a dump left there is one guessed URL away.
        expect($source)->not->toContain("EVO_BASE_PATH . 'assets/backup/temp.php'");
    });

    test('the snapshot to restore has to resolve inside the snapshot directory', function () {
        $source = file_get_contents(__DIR__ . '/../../../../manager/actions/bkmanager.static.php');

        // basename() alone is not enough - the name is also read back as SQL, so the resolved
        // path is compared against the directory it must sit in.
        expect($source)
            ->toContain('$filename = basename(')
            ->toContain("preg_match('/^[A-Za-z0-9_.-]+\\.sql$/', \$filename)")
            ->toContain('strncmp($path, $snapshotDir')
            ->not->toContain("EvolutionCMS()->getConfig('snapshot_path') . \$_POST['filename']");
    });
});
