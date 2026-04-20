<?php namespace EvolutionCMS\Services\SystemTasks;

use Carbon\Carbon;
use EvolutionCMS\Models\SystemSchedulerHealth;

class SchedulerHealthService
{
    public const SINGLETON_ID = 1;
    public const HEALTHY_THRESHOLD_SECONDS = 90;
    public const DEGRADED_THRESHOLD_SECONDS = 180;

    public function recordHeartbeat($host = '', $mode = '')
    {
        $record = $this->getRecord();
        $now = Carbon::now();

        $record->fill([
            'last_heartbeat_at' => $now,
            'last_heartbeat_host' => trim((string) $host),
            'last_heartbeat_mode' => trim((string) $mode),
            'updated_at' => $now,
        ]);

        $record->save();

        return $record->fresh();
    }

    public function getStatusPayload()
    {
        $record = $this->getRecord();
        $lastHeartbeatAt = $record->last_heartbeat_at;
        $ageSeconds = $lastHeartbeatAt instanceof Carbon ? $lastHeartbeatAt->diffInSeconds(Carbon::now()) : null;

        return [
            'status' => $this->deriveStatus($ageSeconds),
            'last_heartbeat_at' => $lastHeartbeatAt ? $lastHeartbeatAt->toAtomString() : null,
            'last_heartbeat_host' => (string) $record->last_heartbeat_host,
            'last_heartbeat_mode' => (string) $record->last_heartbeat_mode,
            'age_seconds' => $ageSeconds,
            'grace_seconds' => self::DEGRADED_THRESHOLD_SECONDS,
        ];
    }

    public function getRecord()
    {
        return SystemSchedulerHealth::query()->firstOrCreate(
            ['id' => self::SINGLETON_ID],
            ['updated_at' => Carbon::now()]
        );
    }

    public function deriveStatus($ageSeconds)
    {
        if ($ageSeconds === null) {
            return 'unhealthy';
        }

        if ($ageSeconds <= self::HEALTHY_THRESHOLD_SECONDS) {
            return 'healthy';
        }

        if ($ageSeconds <= self::DEGRADED_THRESHOLD_SECONDS) {
            return 'degraded';
        }

        return 'unhealthy';
    }
}
