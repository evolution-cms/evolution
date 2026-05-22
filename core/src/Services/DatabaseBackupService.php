<?php namespace EvolutionCMS\Services;

use EvolutionCMS\Support\MysqlDumper;
use EvolutionCMS\Support\SqliteDumper;

class DatabaseBackupService
{
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
            file_put_contents($htaccess, "order deny,allow\ndeny from all\n");
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
        $config = evo()->getDatabase()->getConfig();
        $password = isset($config['password']) ? (string) $config['password'] : '';
        $host = isset($config['host']) ? (string) $config['host'] : '';
        $username = isset($config['username']) ? (string) $config['username'] : '';
        $tempFilePath = $this->buildTempSnapshotFilePath((string) $filePath);

        file_put_contents($tempFilePath, $this->buildSqlHeader('--', (string) $database, $host));

        $command = 'PGPASSWORD=' . escapeshellarg($password)
            . ' pg_dump --host ' . escapeshellarg($host)
            . ' --username ' . escapeshellarg($username)
            . ' --dbname ' . escapeshellarg((string) $database)
            . ' --clean --inserts --no-owner --no-privileges >> ' . escapeshellarg((string) $tempFilePath);

        exec($command, $output, $exitCode);

        if ((int) $exitCode !== 0 || !is_file($tempFilePath) || filesize($tempFilePath) <= 0) {
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
