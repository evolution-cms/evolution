<?php

namespace Tests\Unit\Install;

use Tests\TestCase;

final class PartialInstallConfigWriteStream
{
    public mixed $context;

    private int $writeCount = 0;

    public function stream_open(): bool
    {
        return true;
    }

    public function stream_write(string $data): int
    {
        $this->writeCount++;

        return $this->writeCount === 1 ? min(2, strlen($data)) : 0;
    }

    public function stream_flush(): bool
    {
        return true;
    }

    public function stream_close(): void
    {
    }

    public function url_stat(): false
    {
        return false;
    }
}

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

    public function testConfigWriterReturnsFalseWhenTheParentDirectoryDoesNotExist(): void
    {
        $path = sys_get_temp_dir() . '/evo-missing-' . uniqid('', true) . '/default.php';

        self::assertFalse(hasInstallConfigPermissions($path));
        self::assertFalse(writeInstallConfigFile($path, '<?php return [];'));
        self::assertFileDoesNotExist($path);
    }

    public function testConfigWriterWritesTheCompleteContents(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'evo-config-');
        self::assertIsString($path);
        $contents = "<?php\nreturn ['driver' => 'sqlite'];\n";

        try {
            self::assertTrue(hasInstallConfigPermissions(dirname($path) . '/evo-new-config-' . uniqid() . '.php'));
            self::assertTrue(hasInstallConfigPermissions($path));
            self::assertTrue(writeInstallConfigFile($path, $contents));
            self::assertSame($contents, file_get_contents($path));
            self::assertTrue(is_readable($path));
            self::assertTrue(is_writable($path));
        } finally {
            @chmod($path, 0600);
            @unlink($path);
        }
    }

    public function testConfigWriterRejectsAPartialWrite(): void
    {
        $scheme = 'evopartial' . bin2hex(random_bytes(4));
        self::assertTrue(stream_wrapper_register($scheme, PartialInstallConfigWriteStream::class));

        try {
            self::assertFalse(writeInstallConfigFile($scheme . '://default.php', 'generated config'));
        } finally {
            stream_wrapper_unregister($scheme);
        }
    }

    public function testCliWriteConfigThrowsWhenTheTargetCannotBeOpened(): void
    {
        $missingPath = sys_get_temp_dir() . '/evo-missing-' . uniqid('', true) . '/default.php';
        $installer = new class([], $missingPath) extends \InstallEvo {
            public function __construct(array $arguments, private readonly string $path)
            {
                parent::__construct($arguments);
            }

            protected function configFilePath(): string
            {
                return $this->path;
            }
        };
        $installer->databaseType = 'sqlite';
        $installer->database = 'evolution';
        $installer->tablePrefix = 'evo_';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to write the database configuration file');

        $installer->writeConfig();
    }
}
