<?php

// Run in a child process: readline must see the real stdin, not a mocked answer.
$root = dirname(__DIR__, 3);
$mode = $argv[1];
if ($mode === 'prefix-cli') {
    define('EVO_CORE_PATH', $argv[2] . '/');
}
require $root . '/install/cli-install.php';

if ($mode === 'prefix-cli') {
    exit(runCliInstall([
        'cli-install.php', '--typeInstall=1', '--databaseType=sqlite', '--database=existing',
        '--tablePrefix=evo_', '--cmsAdmin=admin', '--cmsAdminEmail=admin@example.local',
        '--cmsPassword=example-password', '--language=uk', '--removeInstall=y', '--skipComposer=y',
    ]));
}

$installer = new InstallEvo([]);
try {
    switch ($mode) {
        case 'ask':
            echo 'ANSWER:' . $installer->ask('Database name?', 'fallback');
            break;
        case 'choice':
            echo 'ANSWER:' . $installer->choice('Install or update?', ['Install', 'Update']);
            break;
        case 'prefix-recovery':
            $installer->dbh = new PDO('sqlite::memory:');
            $installer->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $installer->dbh->exec('CREATE TABLE evo_site_content (id INTEGER)');
            $installer->tablePrefix = 'evo_';
            $installer->checkIssetTablePrefix();
            echo 'ANSWER:' . $installer->tablePrefix;
            break;
        default:
            throw new RuntimeException('Unknown test mode');
    }
} catch (RuntimeException $exception) {
    error($exception->getMessage());
    exit(1);
}
