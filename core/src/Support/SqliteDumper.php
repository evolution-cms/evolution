<?php namespace EvolutionCMS\Support;

use PDO;

class SqliteDumper
{
    /**
     * @var array
     */
    public $_dbtables;
    /**
     * @var bool
     */
    public $_isDroptables;
    /**
     * @var string
     */
    public $dbname;
    /**
     * @var string
     */
    private $snapshootFile;

    /**
     * @param string $dbname
     */
    public function __construct($dbname)
    {
        $this->dbname = $dbname;
        $this->setDroptables(false);
    }

    /**
     * @param bool $state
     */
    public function setDroptables($state)
    {
        $this->_isDroptables = (bool) $state;
    }

    /**
     * @param array $dbtables
     */
    public function setDBtables($dbtables)
    {
        $this->_dbtables = $dbtables;
    }

    /**
     * @param string $file
     */
    public function setSnapshotFile($file)
    {
        $this->snapshootFile = $file;
    }

    /**
     * @return bool
     */
    public function isDroptables()
    {
        return $this->_isDroptables;
    }

    /**
     * @param string $prefix
     * @return array
     */
    public static function listTables($prefix = '')
    {
        $pdo = \DB::connection()->getPdo();
        $like = $prefix . '%';
        $stmt = $pdo->prepare(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' AND name LIKE :prefix ORDER BY name"
        );
        $stmt->execute(['prefix' => $like]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
    }

    /**
     * @param string $name
     * @return string
     */
    private function quoteIdentifier($name)
    {
        $name = str_replace('"', '""', $name);

        return '"' . $name . '"';
    }

    /**
     * @param string $table
     * @param PDO $pdo
     * @return string|false
     */
    private function fetchCreateTable($table, PDO $pdo)
    {
        $stmt = $pdo->prepare("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = :name");
        $stmt->execute(['name' => $table]);

        return $stmt->fetchColumn();
    }

    /**
     * @param mixed $value
     * @param PDO $pdo
     * @return string
     */
    private function quoteValue($value, PDO $pdo)
    {
        if ($value === null) {
            return 'NULL';
        }

        return $pdo->quote((string) $value);
    }

    /**
     * @param string $callBack
     * @return bool
     */
    public function createDump($callBack)
    {
        $modx = evo();
        $config = $modx->getDatabase()->getConfig();
        $pdo = \DB::connection()->getPdo();
        $transactionStarted = false;

        if ($callBack !== 'snapshot') {
            return $this->writeDump($callBack, $modx, $config, $pdo);
        }

        try {
            $pdo->exec('BEGIN IMMEDIATE TRANSACTION');
            $transactionStarted = true;

            $result = $this->writeDump($callBack, $modx, $config, $pdo);

            $pdo->exec('COMMIT');
            $transactionStarted = false;

            return $result;
        } catch (\Throwable $throwable) {
            if ($transactionStarted) {
                $pdo->exec('ROLLBACK');
            }

            throw $throwable;
        }
    }

    private function writeDump($callBack, $modx, array $config, PDO $pdo): bool
    {
        $lf = "\n";
        $tempfile_path = EVO_BASE_PATH . 'assets/backup/temp.php';

        $version = $modx->getVersionData();
        $host = $config['host'] ?? '';

        $output = "--{$lf}";
        $output .= "-- " . addslashes($modx->getPhpCompat()->entities($modx->getConfig('site_name'))) . " Database Dump{$lf}";
        $output .= "-- Evolution CMS Version:{$version['version']}{$lf}";
        $output .= "-- {$lf}";
        $output .= "-- Host: {$host}{$lf}";
        $output .= "-- Generation Time: " . $modx->toDateFormat(time()) . $lf;
        $output .= "-- Server version: " . $modx->getDatabase()->getVersion() . $lf;
        $output .= "-- PHP Version: " . phpversion() . $lf;
        $output .= "-- Database: `{$this->dbname}`{$lf}";
        $output .= "-- Description: " . trim($_REQUEST['backup_title'] ?? '') . "{$lf}";
        $output .= "--";
        file_put_contents($tempfile_path, $output, FILE_APPEND | LOCK_EX);
        $output = '';

        $tables = is_array($this->_dbtables) ? $this->_dbtables : [];
        foreach ($tables as $table) {
            $createSql = $this->fetchCreateTable($table, $pdo);
            if (empty($createSql)) {
                continue;
            }

            $output .= "{$lf}{$lf}-- --------------------------------------------------------{$lf}{$lf}";
            $output .= "--{$lf}-- Table structure for table `{$table}`{$lf}";
            $output .= "--{$lf}{$lf}";
            if ($this->isDroptables()) {
                $output .= "PRAGMA foreign_keys = OFF;{$lf}";
                $output .= "DROP TABLE IF EXISTS " . $this->quoteIdentifier($table) . ";{$lf}";
                $output .= "PRAGMA foreign_keys = ON;{$lf}{$lf}";
            }
            $output .= $createSql . ";{$lf}";
            $output .= $lf;
            file_put_contents($tempfile_path, $output, FILE_APPEND | LOCK_EX);
            $output = '';

            $stmt = $pdo->query('SELECT * FROM ' . $this->quoteIdentifier($table));
            if ($stmt === false) {
                continue;
            }

            $insertQuerySize = 0;
            $hasRows = false;
            while (($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
                $hasRows = true;
                $values = [];
                foreach ($row as $value) {
                    $values[] = $this->quoteValue($value, $pdo);
                }
                $insertdump = '(' . implode(',', $values) . ')';

                if ($insertQuerySize === 0) {
                    $output .= "{$lf}--{$lf}-- Dumping data for table `{$table}`{$lf}--{$lf}";
                    $output .= $lf . 'INSERT INTO ' . $this->quoteIdentifier($table) . ' VALUES';
                } else {
                    $output .= ',';
                }
                $output .= $lf . '  ' . $insertdump;
                $insertQuerySize += strlen($insertdump);

                if ($insertQuerySize > 47299) {
                    $output .= ';' . $lf;
                    $insertQuerySize = 0;
                }

                if (5040000 < strlen($output)) {
                    file_put_contents($tempfile_path, $output, FILE_APPEND | LOCK_EX);
                    $output = '';
                }
            }

            if ($hasRows && $insertQuerySize > 0) {
                $output .= ';' . $lf;
            }
            if ($output !== '') {
                file_put_contents($tempfile_path, $output, FILE_APPEND | LOCK_EX);
                $output = '';
            }
        }

        switch ($callBack) {
            case 'dumpSql':
                dumpSql($tempfile_path);
                break;
            case 'snapshot':
                snapshot($tempfile_path, $this->snapshootFile);
                break;
        }

        return true;
    }
}
