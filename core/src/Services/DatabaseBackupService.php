<?php namespace EvolutionCMS\Services;

use EvolutionCMS\Support\MysqlDumper;
use EvolutionCMS\Support\SqliteDumper;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    /**
     * Apache deny rule for the directories dumps land in. `Order deny,allow` on its own is 2.2
     * syntax: on 2.4 without mod_access_compat it is a 500, which leaves the directory served
     * rather than denied, so both forms are written and each is guarded by its module.
     */
    public const DENY_HTACCESS = "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
        . "<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n";

    protected string $basePath;

    public function __construct(?string $basePath = null)
    {
        if ($basePath === null || trim((string) $basePath) === '') {
            $basePath = defined('EVO_BASE_PATH') ? EVO_BASE_PATH : dirname(__DIR__, 2) . '/';
        }

        $this->basePath = rtrim((string) $basePath, '/\\') . '/';
    }

    public function createSnapshot($description = '')
    {
        $modx = evo();
        $database = (string) $modx->getDatabase()->getConfig('database');
        $driver = (string) $modx->getDatabase()->getConfig('driver');
        $snapshotPath = $this->resolveSnapshotPath();
        $filePath = $this->buildSnapshotFilePath($snapshotPath);

        $this->prepareSnapshotPath($snapshotPath);
        $this->prepareTempPath();
        $this->removeTempFile();

        $hadBackupTitle = array_key_exists('backup_title', $_REQUEST);
        $previousBackupTitle = $hadBackupTitle ? $_REQUEST['backup_title'] : null;
        $_REQUEST['backup_title'] = trim((string) $description);

        try {
            $dumpFinished = $this->createDriverSnapshot($driver, $database, $filePath);
        } finally {
            if ($hadBackupTitle) {
                $_REQUEST['backup_title'] = $previousBackupTitle;
            } else {
                unset($_REQUEST['backup_title']);
            }
        }

        if (!$dumpFinished || !is_file($filePath) || filesize($filePath) <= 0) {
            throw new \RuntimeException('Unable to create database backup before site update.');
        }

        $this->rotateSnapshots($snapshotPath);
        $version = $modx->getVersionData();

        return [
            'path' => $filePath,
            'filename' => basename($filePath),
            'database' => $database,
            'driver' => $driver,
            'version' => isset($version['version']) ? (string) $version['version'] : '',
            'description' => trim((string) $description),
            'size' => filesize($filePath),
        ];
    }

    protected function resolveSnapshotPath()
    {
        $modx = evo();
        $snapshotPath = (string) $modx->getConfig('snapshot_path');

        if ($snapshotPath === '') {
            $snapshotPath = is_dir($this->basePath . 'temp/backup/')
                ? $this->basePath . 'temp/backup/'
                : $this->basePath . 'assets/backup/';
            $modx->setConfig('snapshot_path', $snapshotPath);
        }

        return rtrim($snapshotPath, '/\\') . '/';
    }

    protected function prepareSnapshotPath($snapshotPath)
    {
        $path = rtrim((string) $snapshotPath, '/\\');
        if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            throw new \RuntimeException('Unable to create database backup directory.');
        }

        @chmod($path, 0777);

        $htaccess = $path . '/.htaccess';
        if (!is_file($htaccess)) {
            file_put_contents($htaccess, self::DENY_HTACCESS);
        }

        if (!is_writable($path)) {
            throw new \RuntimeException('Database backup directory is not writable.');
        }
    }

    protected function prepareTempPath()
    {
        $tempPath = $this->basePath . 'assets/backup/';
        if (!is_dir($tempPath) && !mkdir($tempPath, 0777, true) && !is_dir($tempPath)) {
            throw new \RuntimeException('Unable to create database backup temp directory.');
        }

        @chmod($tempPath, 0777);
    }

    protected function removeTempFile()
    {
        $tempFile = $this->basePath . 'assets/backup/temp.php';
        if (is_file($tempFile)) {
            unlink($tempFile);
        }
    }

    protected function buildSnapshotFilePath($snapshotPath)
    {
        $baseName = date('Y-m-d_H-i-s');
        $filePath = rtrim((string) $snapshotPath, '/\\') . '/' . $baseName . '.sql';
        $index = 1;

        while (is_file($filePath)) {
            $filePath = rtrim((string) $snapshotPath, '/\\') . '/' . $baseName . '_' . $index . '.sql';
            $index++;
        }

        return $filePath;
    }

    protected function createDriverSnapshot($driver, $database, $filePath)
    {
        switch ((string) $driver) {
            case 'pgsql':
                return $this->createPostgresSnapshot($database, $filePath);

            case 'sqlite':
            case 'sqlite3':
                $prefix = (string) evo()->getDatabase()->getConfig('prefix');
                $tables = SqliteDumper::listTables($prefix);
                $dumper = new SqliteDumper($database);
                $dumper->setDBtables($tables);
                $dumper->setSnapshotFile($filePath);
                $dumper->setDroptables(true);

                return (bool) $dumper->createDump('snapshot');

            default:
                $modx = evo();
                $prefix = $modx->getDatabase()->escape((string) $modx->getDatabase()->getConfig('prefix'));
                $sql = "SHOW TABLE STATUS FROM `{$database}` LIKE '{$prefix}%'";
                $result = $modx->getDatabase()->query($sql);
                $tables = $modx->getDatabase()->getColumn('Name', $result);
                if (!is_array($tables)) {
                    $tables = [];
                }
                $dumper = new MysqlDumper($database);
                $dumper->setDBtables($tables);
                $dumper->setSnapshotFile($filePath);
                $dumper->setDroptables(true);

                return (bool) $dumper->createDump('snapshot');
        }
    }

    protected function createPostgresSnapshot($database, $filePath)
    {
        $config = $this->databaseConfig();
        $host = isset($config['host']) ? (string) $config['host'] : '';
        $tempFilePath = $this->buildTempSnapshotFilePath((string) $filePath);

        file_put_contents($tempFilePath, $this->buildSqlHeader('--', (string) $database, $host));

        if (!$this->runPostgresDump(['--clean', '--inserts', '--no-owner', '--no-privileges'], $tempFilePath)) {
            if (is_file($tempFilePath)) {
                unlink($tempFilePath);
            }

            return false;
        }

        if (is_file((string) $filePath)) {
            unlink((string) $filePath);
        }

        return rename($tempFilePath, (string) $filePath);
    }

    /**
     * Dumps the given tables to a file under the snapshot directory and returns its path, or null
     * when the dump failed. The caller owns the file from there on.
     *
     * @since 3.5.8
     * @param array $tables
     * @param bool $dropTables
     * @return string|null
     */
    public function dumpPostgresTables(array $tables, $dropTables = true)
    {
        $config = $this->databaseConfig();
        $database = isset($config['database']) ? (string) $config['database'] : '';
        $host = isset($config['host']) ? (string) $config['host'] : '';
        $snapshotPath = $this->resolveSnapshotPath();

        $this->prepareSnapshotPath($snapshotPath);

        // Under the snapshot directory rather than the old assets/backup/temp.php: that path sat
        // in the web root with nothing denying it, so a full dump was readable by anyone who
        // guessed the name. Here the directory carries a deny rule and the name is not guessable.
        $tempFilePath = $this->buildTempSnapshotFilePath($snapshotPath . 'download.sql');

        file_put_contents($tempFilePath, $this->buildSqlHeader('--', $database, $host));

        $arguments = ['--inserts', '--no-owner', '--no-privileges'];

        if ($dropTables) {
            $arguments[] = '--clean';
        }

        foreach ($tables as $table) {
            $arguments[] = '--table';
            $arguments[] = (string) $table;
        }

        if (!$this->runPostgresDump($arguments, $tempFilePath)) {
            if (is_file($tempFilePath)) {
                unlink($tempFilePath);
            }

            return null;
        }

        return $tempFilePath;
    }

    /**
     * Replays a SQL file into the database.
     *
     * @since 3.5.8
     * @param string $path
     * @return bool
     */
    public function restorePostgresFile($path)
    {
        $path = (string) $path;

        if (!is_file($path)) {
            return false;
        }

        $process = $this->buildPostgresProcess('psql', ['--file', $path]);

        try {
            $process->run();
        } catch (\Throwable $exception) {
            return false;
        }

        return $process->isSuccessful();
    }

    /**
     * Streams a pg_dump run onto the end of the given file.
     *
     * @param array $arguments
     * @param string $tempFilePath
     * @return bool
     */
    protected function runPostgresDump(array $arguments, $tempFilePath)
    {
        $handle = fopen($tempFilePath, 'ab');

        if ($handle === false) {
            return false;
        }

        $process = $this->buildPostgresProcess('pg_dump', $arguments);

        try {
            // Streamed rather than buffered: a dump is as large as the
            // database, and getOutput() would hold all of it in memory. The
            // shell redirect this replaces streamed too, so buffering here
            // would be a regression on exactly the databases worth backing up.
            $process->run(static function ($type, $buffer) use ($handle) {
                if ($type === Process::OUT) {
                    fwrite($handle, $buffer);
                }
            });
        } catch (\Throwable $exception) {
            fclose($handle);

            return false;
        }

        fclose($handle);
        clearstatcache(true, $tempFilePath);

        return $process->isSuccessful() && is_file($tempFilePath) && filesize($tempFilePath) > 0;
    }

    /**
     * Builds a PostgreSQL client invocation.
     *
     * No shell is involved here, and that is the point. The previous form was
     * `PGPASSWORD=... pg_dump ... >> file`, and a leading VAR=value assignment
     * is POSIX shell syntax that cmd.exe rejects outright with "'PGPASSWORD'
     * is not recognized", so this backup could never succeed on Windows.
     * Passing the password as an environment entry and the arguments as a list
     * works the same way on every platform, keeps the password out of the
     * process list, and has the side benefit that nothing has to be quoted for
     * a shell - an argument holding a semicolon stays one argument.
     *
     * @param string $binary
     * @param array $arguments
     * @return Process
     */
    /**
     * The connection settings the pg client is invoked with.
     *
     * @return array
     */
    protected function databaseConfig()
    {
        return (array) evo()->getDatabase()->getConfig();
    }

    protected function buildPostgresProcess($binary, array $arguments)
    {
        $config = $this->databaseConfig();
        $password = isset($config['password']) ? (string) $config['password'] : '';
        $host = isset($config['host']) ? (string) $config['host'] : '';
        $username = isset($config['username']) ? (string) $config['username'] : '';
        $database = isset($config['database']) ? (string) $config['database'] : '';

        $process = new Process(
            array_merge(
                [
                    (string) $binary,
                    '--host', $host,
                    '--username', $username,
                    '--dbname', $database,
                ],
                array_values($arguments)
            ),
            null,
            ['PGPASSWORD' => $password]
        );
        $process->setTimeout(null);

        return $process;
    }

    protected function buildTempSnapshotFilePath($filePath)
    {
        return rtrim(dirname((string) $filePath), '/\\')
            . '/.' . basename((string) $filePath) . '.' . getmypid() . '.tmp';
    }

    protected function buildSqlHeader($commentPrefix, $database, $host)
    {
        $modx = evo();
        $line = "\n";
        $version = $modx->getVersionData();
        $prefix = (string) $commentPrefix;

        return $prefix . $line
            . $prefix . ' ' . addslashes($modx->getPhpCompat()->entities($modx->getConfig('site_name'))) . ' Database Dump' . $line
            . $prefix . ' Evolution CMS Version:' . (isset($version['version']) ? $version['version'] : '') . $line
            . $prefix . ' ' . $line
            . $prefix . ' Host: ' . $host . $line
            . $prefix . ' Generation Time: ' . $modx->toDateFormat(time()) . $line
            . $prefix . ' Server version: ' . $modx->getDatabase()->getVersion() . $line
            . $prefix . ' PHP Version: ' . phpversion() . $line
            . $prefix . ' Database: `' . $database . '`' . $line
            . $prefix . ' Description: ' . trim($_REQUEST['backup_title'] ?? '') . $line
            . $prefix . $line;
    }

    protected function rotateSnapshots($snapshotPath)
    {
        $pattern = rtrim((string) $snapshotPath, '/\\') . '/*.sql';
        $files = glob($pattern, GLOB_NOCHECK);
        if (!is_array($files) || (isset($files[0]) && $files[0] === $pattern)) {
            return;
        }

        usort($files, function ($left, $right) {
            return filemtime($right) <=> filemtime($left);
        });

        foreach (array_slice($files, 10, 40) as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
