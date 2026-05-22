<?php namespace EvolutionCMS\Support;

use EvolutionCMS\Interfaces\MysqlDumperInterface;

/**
 * @package  MySQLdumper
 * @version  1.0
 * @author   Dennis Mozes <opensource@mosix.nl>
 * @url        http://www.mosix.nl/mysqldumper
 * @since    PHP 4.0
 * @copyright Dennis Mozes
 * @license GNU/LGPL License: http://www.gnu.org/copyleft/lgpl.html
 *
 * Modified by Raymond and Seiger for use with this module
 *
 **/
class MysqlDumper implements MysqlDumperInterface
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
    public $database_server;

    /**
     * Field with snapshoot filename
     * @var string
     */
    private $snapshootFile;


    /**
     * Mysqldumper constructor.
     * @param string $dbname
     */
    public function __construct($dbname)
    {
        // Don't drop tables by default.
        $this->dbname = $dbname;
        $this->setDroptables(false);
    }

    /**
     * If set to true, it will generate 'DROP TABLE IF EXISTS'-statements for each table.
     *
     * @param bool $state
     */
    public function setDroptables($state)
    {
        $this->_isDroptables = $state;
    }

    /**
     * @param array $dbtables
     */
    public function setDBtables($dbtables)
    {
        $this->_dbtables = $dbtables;
    }

    /**
     * @param string $callBack
     * @return bool
     */
    public function createDump($callBack)
    {
        $modx = evo();
        $createtable = [];
        $dataBaseConfig = $modx->db->getConfig();
        $pdo = \DB::connection()->getPdo();
        $transactionStarted = false;

        if ($callBack !== 'snapshot') {
            return $this->writeDump($callBack, $modx, $createtable, $dataBaseConfig);
        }

        try {
            $pdo->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');
            $transactionStarted = true;

            $result = $this->writeDump($callBack, $modx, $createtable, $dataBaseConfig);

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

    private function writeDump($callBack, $modx, array $createtable, array $dataBaseConfig): bool
    {
        $databaseName = $dataBaseConfig['database'];
        $sql =  'SELECT table_name AS "table", round(((data_length + index_length) / 1024 / 1024)) "size" FROM information_schema.TABLES WHERE table_schema = "'.$databaseName.'"';
        $tableSizes = array_column($modx->db->makeArray($modx->db->query($sql)), 'size', 'table');
        $tables = $this->sortTablesByDependencies($this->_dbtables);
        // Set line feed
        $lf = "\n";
        $tempfile_path = EVO_BASE_PATH . 'assets/backup/temp.php';

        foreach ($tables as $tblval) {
            $result = $modx->getDatabase()->query("SHOW CREATE TABLE `{$tblval}`");
            $createtable[$tblval] = $this->result2Array(1, $result);
        }

        $version = $modx->getVersionData();

        // Set header
        $output = "#{$lf}";
        $output .= "# " . addslashes($modx->getPhpCompat()->entities($modx->getConfig('site_name'))) . " Database Dump{$lf}";
        $output .= "# Evolution CMS Version:{$version['version']}{$lf}";
        $output .= "# {$lf}";
        $output .= "# Host: {$this->database_server}{$lf}";
        $output .= "# Generation Time: " . $modx->toDateFormat(time()) . $lf;
        $output .= "# Server version: " . $modx->getDatabase()->getVersion() . $lf;
        $output .= "# PHP Version: " . phpversion() . $lf;
        $output .= "# Database: `{$this->dbname}`{$lf}";
        $output .= "# Description: " . trim($_REQUEST['backup_title'] ?? '') . "{$lf}";
        $output .= "#";
        file_put_contents($tempfile_path, $output, FILE_APPEND | LOCK_EX);
        $output = '';

        // Generate dumptext for the tables.
        if (isset($this->_dbtables) && count($this->_dbtables)) {
            $this->_dbtables = implode(',', $this->_dbtables);
        } else {
            unset($this->_dbtables);
        }



        foreach ($tables as $tblval) {
            // check for selected table
            if (isset($this->_dbtables)) {
                if (strstr(",{$this->_dbtables},", ",{$tblval},") === false) {
                    continue;
                }
            }
            if ($callBack === 'snapshot') {
                if (!preg_match('@^' . $modx->getDatabase()->getConfig('prefix') . '@', $tblval)) {
                    continue;
                }
            }
            $output .= "{$lf}{$lf}# --------------------------------------------------------{$lf}{$lf}";
            $output .= "#{$lf}# Table structure for table `{$tblval}`{$lf}";
            $output .= "#{$lf}{$lf}";
            // Generate DROP TABLE statement when client wants it to.
            if ($this->isDroptables()) {
                $output .= "SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;{$lf}";
                $output .= "DROP TABLE IF EXISTS `{$tblval}`;{$lf}";
                $output .= "SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;{$lf}{$lf}";
            }
            $output .= "{$createtable[$tblval][0]};{$lf}";
            $output .= $lf;

            $rowCount = $modx->getDatabase()->getValue($modx->getDatabase()->select("COUNT(*)",$tblval));
            if(!empty($rowCount)){
                $output .= "#{$lf}# Dumping data for table `{$tblval}`{$lf}#{$lf}";
            }
            file_put_contents($tempfile_path, $output, FILE_APPEND | LOCK_EX);
            $output = '';

            if(empty($rowCount)){
                continue;
            }

            $tableSize = $tableSizes[$tblval];

            $rowByOneQuery = $this->calculateRowBatchSize((int) $rowCount, (int) $tableSize);

            $total = intval(($rowCount - 1) / $rowByOneQuery) + 1;

            $insertQuerySize = 0;

            for ($page = 1; $page <= $total; $page++) {
                $start = $page * $rowByOneQuery - $rowByOneQuery;
                $result = $modx->getDatabase()->select('*', $tblval, '', '', "$start, $rowByOneQuery");



                while ($arr = $modx->getDatabase()->getRow($result)) {
                    //формируем блок значений
                    $insertdump = "(";
                    if (!is_array($arr)) $arr = [];

                    foreach ($arr as $key => $value) {
                        if (is_null($value)) {
                            $value = 'NULL';
                        } else {
                            $value = addslashes($value);
                            $value = str_replace([
                                "\r\n",
                                "\r",
                                "\n"
                            ], '\\n', $value);
                            $value = "'{$value}'";
                        }
                        $insertdump .= $value . ',';
                    }
                    $insertdump = rtrim($insertdump, ',') . ")";

                    //если еще небыло значен
                    if($insertQuerySize === 0){
                        $output .= $lf."INSERT INTO `{$tblval}` VALUES";
                    }
                    else{
                        $output .= ",";
                    }
                    $output .= $lf."  ".$insertdump;
                    $insertQuerySize+=strlen($insertdump);

                    //если записали больше 30 строк з запрос ставим ; и сбрасивыем счетчик
                    if($insertQuerySize>47299){
                        $output .= ";".$lf;
                        $insertQuerySize = 0;
                    }
                    //если большая строрки пишем в файл чтоб не перегрузить память

                    if (5040000 < strlen($output)) {
                        file_put_contents($tempfile_path, $output, FILE_APPEND | LOCK_EX);
                        $output = '';
                    }
                }
            }
            //если данные есть, и записано больше 0 строк данных ставим ; в конце
            if(!empty($output) && $insertQuerySize >0){
                $output .= ";".$lf;
            }

            //пишем блок в файл
            file_put_contents($tempfile_path, $output, FILE_APPEND | LOCK_EX);
            $output = '';
        }

        switch ($callBack) {
            case 'dumpSql':
                dumpSql($tempfile_path);
                break;
            case 'snapshot':
                snapshot($tempfile_path,$this->snapshootFile);
                break;
        }

        return true;
    }

    /**
     * @param int $numinarray
     * @param \PDOStatement $resource
     * @return array
     */
    public function result2Array($numinarray, $resource)
    {
        $modx = evo();
        $array = [];
        while ($row = $modx->getDatabase()->getRow($resource, 'num')) {
            $array[] = $row[$numinarray];
        }

        return $array;
    }

    /**
     * @return bool
     */
    public function isDroptables()
    {
        return $this->_isDroptables;
    }

    /**
     * @param string $key
     * @param \PDOStatement $resource
     * @return array
     */
    public function loadObjectList($key, $resource)
    {
        $modx = evo();
        $array = [];
        while ($row = $modx->getDatabase()->getRow($resource, 'object')) {
            if ($key) {
                $array[$row->$key] = $row;
            } else {
                $array[] = $row;
            }
        }

        return $array;
    }

    /**
     * @param \stdClass $obj
     * @return array|null
     */
    public function object2Array($obj)
    {
        $array = null;
        if (is_object($obj)) {
            $array = [];
            foreach (get_object_vars($obj) as $key => $value) {
                if (is_object($value)) {
                    $array[$key] = $this->object2Array($value);
                } else {
                    $array[$key] = $value;
                }
            }
        }

        return $array;
    }

    public function setSnapshotFile($file){
        $this->snapshootFile = $file;
    }

    /**
     * Sorts the tables based on their foreign key dependencies.
     *
     * This method sorts the given list of tables in a way that ensures tables with no dependencies (or primary tables)
     * are processed first, followed by tables that depend on other tables through foreign keys.
     * It uses a topological sorting approach to ensure that tables with dependencies are created in the correct order.
     * The method relies on the **getForeignKeyDependencies()** method to retrieve the dependencies of each table.
     *
     * @param array $dbtables An array of table names to be sorted based on their dependencies.
     *
     * @return array The sorted array of table names, with dependent tables placed after the ones they rely on.
     */
    public function sortTablesByDependencies($dbtables)
    {
        $sorted = [];
        $visited = [];
        $dependencies = $this->getForeignKeyDependencies($dbtables);

        foreach ($dbtables as $table) {
            $this->visitTable($table, $dependencies, $visited, $sorted);
        }

        return $sorted;
    }

    /**
     * Retrieves all foreign key dependencies for the given tables.
     * Returns an associative array with tables and their dependencies.
     */
    public function getForeignKeyDependencies($tables)
    {
        $dependencies = [];

        foreach ($tables as $table) {
            $sql = "SHOW CREATE TABLE `$table`";
            $result = evo()->db->query($sql);
            $createTableQuery = evo()->db->getRow($result)['Create Table'];
            preg_match_all('/FOREIGN KEY \(`([^`]+)`\) REFERENCES `([^`]+)` \(`([^`]+)`\)/', $createTableQuery, $matches);

            if (!empty($matches[2])) {
                foreach ($matches[2] as $index => $referencedTable) {
                    $dependencies[$table][] = $referencedTable;
                }
            }
        }

        return $dependencies;
    }

    /**
     * Checks the table and its dependencies, visits all the tables it depends on, and adds them to the sorted list.
     *
     * This method recursively visits tables, checking their dependencies, and adds them to the **$sorted** array.
     * If a table has already been visited, it is skipped.
     * It works based on the principle of topological sorting to ensure the correct order of table creation
     * during the dump process, taking foreign keys into account.
     *
     * @param string $table The name of the table to check.
     * @param array $dependencies An associative array containing tables and their dependencies.
     * @param array $visited An array containing already visited tables to avoid circular references.
     * @param array $sorted An array where tables are added after visiting their dependencies.
     */
    private function visitTable($table, $dependencies, &$visited, &$sorted)
    {
        if (isset($visited[$table])) {
            return;
        }

        $visited[$table] = true;

        if (isset($dependencies[$table])) {
            foreach ($dependencies[$table] as $dependentTable) {
                $this->visitTable($dependentTable, $dependencies, $visited, $sorted);
            }
        }

        $sorted[] = $table;
    }

    private function calculateRowBatchSize(int $rowCount, int $tableSize): int
    {
        $parts = (int) round($tableSize / 5);
        $parts = max(1, $parts);

        return max(1, (int) ceil($rowCount / $parts));
    }
}
