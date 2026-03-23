<?php

namespace Tests\Unit\Install;

use Tests\TestCase;

require_once dirname(__DIR__, 4) . '/install/cli-install.php';

final class CliInstallTest extends TestCase
{
    private string $configPath;
    private bool $configExisted = false;
    private string $originalConfig = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->configPath = dirname(__DIR__, 4) . '/core/config/database/connections/default.php';
        $this->configExisted = file_exists($this->configPath);

        if ($this->configExisted) {
            $this->originalConfig = (string) file_get_contents($this->configPath);
            @chmod($this->configPath, 0600);
        }
    }

    protected function tearDown(): void
    {
        if ($this->configExisted) {
            @chmod($this->configPath, 0600);
            file_put_contents($this->configPath, $this->originalConfig);
        } elseif (file_exists($this->configPath)) {
            @chmod($this->configPath, 0600);
            unlink($this->configPath);
        }

        parent::tearDown();
    }

    public function testWriteConfigUsesTheProvidedDatabaseName(): void
    {
        $installer = new \InstallEvo([]);
        $installer->databaseServer = 'host.mysql.tools';
        $installer->databaseType = 'mysql';
        $installer->database = 'db_name';
        $installer->databaseUser = 'db_user';
        $installer->databasePassword = 'password';
        $installer->tablePrefix = 'evo_';
        $installer->database_charset = 'utf8mb4';
        $installer->database_collation = 'utf8mb4_unicode_520_ci';
        $installer->dbh = new class {
            public function getAttribute($attribute): string
            {
                return '8.0.36';
            }
        };

        $installer->writeConfig();

        $config = (string) file_get_contents($this->configPath);

        self::assertStringContainsString("'database' => env('DB_DATABASE', 'db_name')", $config);
        self::assertStringNotContainsString('[+database_name+]', $config);
        self::assertStringContainsString("'username' => env('DB_USERNAME', 'db_user')", $config);
    }
}
