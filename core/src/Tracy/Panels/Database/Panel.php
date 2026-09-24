<?php namespace EvolutionCMS\Tracy\Panels\Database;

use PDO;
use Exception;
use Illuminate\Database\Events\QueryExecuted;
use EvolutionCMS\Tracy\Panels\AbstractPanel;
use EvolutionCMS\Support\Formatter\SqlFormatter;

/**
 * @see: https://github.com/recca0120/laravel-tracy
 */
class Panel extends AbstractPanel
{
    /**
     * $queries.
     *
     * @var array
     */
    protected $queries = [];

    /**
     * $totalTime.
     *
     * @var float
     */
    protected $totalTime = 0.0;

    /**
     * $counter.
     *
     * @var int
     */
    protected $counter = 0;

    /**
     * logQuery.
     *
     * @param string $sql
     * @param array $bindings
     * @param int $time
     * @param string $name
     * @param PDO $pdo
     * @param string $driver
     * @return $this
     */
    public function logQuery($sql, $bindings = [], $time = 0, $name = null, ?PDO $pdo = null, $driver = 'mysql')
    {
        $this->counter++;
        $this->totalTime += $time;
        $source = static::findSource();
        $editorLink = static::editorLink($source);
        $this->queries[] = [
            'sql' => $sql,
            'bindings' => $bindings,
            'time' => $time,
            'name' => $name,
            'pdo' => $pdo,
            'driver' => $driver,
            'source' => $source,
            'editorLink' => $editorLink,
            'hightlight' => null,
        ];

        return $this;
    }

    /**
     * subscribe.
     */
    protected function subscribe()
    {
        $events = $this->evolution['events'];
        $events->listen(QueryExecuted::class, function ($event) {
            $time = $event->time;
            if ($this->evolution->bound(\EvolutionCMS\Tracy\ConnectionTiming::class)) {
                $time = $this->evolution[\EvolutionCMS\Tracy\ConnectionTiming::class]
                    ->queryMilliseconds($event->connection, (float) $time);
            }
            // Inspect existing handles only; logging must not open a write connection.
            $pdo = $event->connection->getRawPdo();
            if (!$pdo instanceof PDO) {
                $pdo = $event->connection->getRawReadPdo();
            }
            $this->logQuery(
                $event->sql,
                $event->bindings,
                $time,
                $event->connectionName,
                $pdo instanceof PDO ? $pdo : null
            );
        });
    }

    /**
     * getAttributes.
     *
     * @return array
     */
    protected function getAttributes()
    {
        $queries = [];
        foreach ($this->queries as $query) {
            $sql = $query['sql'];
            $bindings = $query['bindings'];
            $pdo = $query['pdo'];
            $driver = $query['driver'];
            $version = 0;


            $explains = [];
            $hints = [];
            if ($pdo instanceof PDO) {
                $hightlight = SqlFormatter::prepare($sql, $bindings, $pdo);

                $driver = $this->getDatabaseDriver($pdo);
                if ($driver === 'mysql') {
                    $version = $this->getDatabaseVersion($pdo);
                    $explains = SqlFormatter::explain($pdo, $sql, $bindings);
                    $hints = SqlFormatter::performQueryAnalysis($sql, $version, $driver);
                }
            } else {
                $hightlight = SqlFormatter::highlight($sql);
            }

            $queries[] = array_merge($query, compact('hightlight', 'explains', 'hints', 'driver', 'version'));
        }

        return [
            'counter' => $this->counter,
            'totalTime' => $this->totalTime,
            'queries' => $queries,
        ];
    }

    /**
     * getDatabaseDriver.
     *
     * @param \PDO $pdo
     * @return string
     */
    protected function getDatabaseDriver(PDO $pdo)
    {
        try {
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        } catch (Exception $e) {
            $driver = null;
        }

        return $driver;
    }

    /**
     * getDatabaseVersion.
     *
     * @param \PDO $pdo
     * @return string
     */
    protected function getDatabaseVersion(PDO $pdo)
    {
        try {
            $version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        } catch (Exception $e) {
            $version = 0;
        }

        return $version;
    }
}
