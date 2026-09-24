<?php namespace EvolutionCMS\Tracy;

use Closure;
use Illuminate\Database\Connection;
use Tracy\Debugger;
use WeakMap;

/** Request-local PDO timing, preserving lazy read/write connections. */
class ConnectionTiming
{
    private WeakMap $connections;
    private WeakMap $wrappers;
    private float $totalMs = 0.0;

    public function __construct()
    {
        $this->connections = new WeakMap();
        $this->wrappers = new WeakMap();
        $this->publish();
    }

    /** Attach to new connections and refresh resolver wrappers after reconnects. */
    public function attach(Connection $connection): void
    {
        if (!isset($this->connections[$connection])) {
            $this->connections[$connection] = (object) ['total' => 0.0, 'beforeQuery' => null];
            $connection->beforeExecuting(function ($sql, $bindings, Connection $connection): void {
                // Illuminate also does this before its query timer; keep it outside our baseline.
                $connection->reconnectIfMissingConnection();
                $this->wrapResolvers($connection);
                $state = $this->connections[$connection];
                $state->beforeQuery = $state->total;
            });
        }
        $this->wrapResolvers($connection);
    }

    private function wrapResolvers(Connection $connection): void
    {
        foreach ([['getRawPdo', 'setPdo'], ['getRawReadPdo', 'setReadPdo']] as [$get, $set]) {
            $resolver = $connection->$get();
            if (!$resolver instanceof Closure || isset($this->wrappers[$resolver])) {
                continue;
            }
            $state = $this->connections[$connection];
            $wrapper = function () use ($resolver, $state) {
                $start = hrtime(true);
                try {
                    return $resolver();
                } finally {
                    $ms = (hrtime(true) - $start) / 1e6;
                    $state->total += $ms;
                    $this->totalMs += $ms;
                    $this->publish();
                }
            };
            $this->wrappers[$wrapper] = true;
            $connection->$set($wrapper);
        }
    }

    /** Remove only connection setup measured within this query's execution window. */
    public function queryMilliseconds(Connection $connection, float $milliseconds): float
    {
        $state = $this->connections[$connection] ?? null;
        if ($state === null || $state->beforeQuery === null) {
            return $milliseconds;
        }
        $connectionMs = $state->total - $state->beforeQuery;
        $state->beforeQuery = null;
        return max(0.0, $milliseconds - $connectionMs);
    }

    private function publish(): void
    {
        $panel = Debugger::getBar()->getPanel('Tracy:info');
        if ($panel instanceof \Tracy\DefaultBarPanel) {
            $panel->data = array_replace((array) $panel->data, [
                'PDO connection time' => number_format($this->totalMs, 2, '.', '') . ' ms',
            ]);
        }
    }
}
