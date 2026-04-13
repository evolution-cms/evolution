<?php namespace EvolutionCMS\Services\SystemTasks;

use Carbon\Carbon;
use EvolutionCMS\Models\SystemWorkerHealth;

class WorkerHealthService
{
    public const SINGLETON_ID = 1;
    public const HEALTHY_THRESHOLD_SECONDS = 120;
    public const DEGRADED_THRESHOLD_SECONDS = 300;

    public function markRun($host = '', $pid = null)
    {
        return $this->saveState([
            'last_worker_run_at' => Carbon::now(),
            'last_worker_host' => trim((string) $host),
            'last_worker_pid' => $pid,
        ]);
    }

    public function markPick($host = '', $pid = null)
    {
        return $this->saveState([
            'last_worker_pick_at' => Carbon::now(),
            'last_worker_host' => trim((string) $host),
            'last_worker_pid' => $pid,
        ]);
    }

    public function markSuccess($host = '', $pid = null)
    {
        return $this->saveState([
            'last_worker_success_at' => Carbon::now(),
            'last_worker_error_code' => '',
            'last_worker_host' => trim((string) $host),
            'last_worker_pid' => $pid,
        ]);
    }

    public function markFailure($errorCode, $host = '', $pid = null)
    {
        return $this->saveState([
            'last_worker_failed_at' => Carbon::now(),
            'last_worker_error_code' => trim((string) $errorCode),
            'last_worker_host' => trim((string) $host),
            'last_worker_pid' => $pid,
        ]);
    }

    public function getStatusPayload(?SchedulerHealthService $schedulerHealthService = null)
    {
        $schedulerHealthService = $schedulerHealthService ?: new SchedulerHealthService();
        $record = $this->getRecord();
        $schedulerStatus = $schedulerHealthService->getStatusPayload();
        $lastWorkerRunAt = $record->last_worker_run_at;
        $ageSeconds = $lastWorkerRunAt instanceof Carbon ? $lastWorkerRunAt->diffInSeconds(Carbon::now()) : null;

        return [
            'status' => $this->deriveStatus($record, (string) ($schedulerStatus['status'] ?? 'unhealthy'), $ageSeconds),
            'last_worker_run_at' => $lastWorkerRunAt ? $lastWorkerRunAt->toAtomString() : null,
            'last_worker_pick_at' => $record->last_worker_pick_at ? $record->last_worker_pick_at->toAtomString() : null,
            'last_worker_success_at' => $record->last_worker_success_at ? $record->last_worker_success_at->toAtomString() : null,
            'last_worker_failed_at' => $record->last_worker_failed_at ? $record->last_worker_failed_at->toAtomString() : null,
            'last_worker_error_code' => (string) $record->last_worker_error_code,
            'last_worker_host' => (string) $record->last_worker_host,
            'last_worker_pid' => $record->last_worker_pid ? (int) $record->last_worker_pid : null,
            'age_seconds' => $ageSeconds,
            'scheduler_status' => (string) ($schedulerStatus['status'] ?? 'unhealthy'),
        ];
    }

    public function getRecord()
    {
        return SystemWorkerHealth::query()->firstOrCreate(
            ['id' => self::SINGLETON_ID],
            ['updated_at' => Carbon::now()]
        );
    }

    public function deriveStatus(SystemWorkerHealth $record, $schedulerStatus, $ageSeconds)
    {
        if ($schedulerStatus !== 'healthy') {
            return 'unknown';
        }

        $lastSuccess = $record->last_worker_success_at;
        $lastFailure = $record->last_worker_failed_at;
        $hasNewerFailure = $lastFailure instanceof Carbon && (!$lastSuccess instanceof Carbon || $lastFailure->greaterThan($lastSuccess));

        if ($ageSeconds !== null && $ageSeconds <= self::HEALTHY_THRESHOLD_SECONDS && !$hasNewerFailure) {
            return 'healthy';
        }

        if (
            ($ageSeconds !== null && $ageSeconds <= self::DEGRADED_THRESHOLD_SECONDS)
            || $hasNewerFailure
        ) {
            return 'degraded';
        }

        return 'unhealthy';
    }

    protected function saveState(array $attributes)
    {
        $record = $this->getRecord();
        $attributes['updated_at'] = Carbon::now();
        $record->fill($attributes);
        $record->save();

        return $record->fresh();
    }
}
