<?php

use Carbon\Carbon;
use EvolutionCMS\Models\SystemWorkerHealth;
use EvolutionCMS\Services\SystemTasks\SchedulerHealthService;
use EvolutionCMS\Services\SystemTasks\WorkerHealthService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;

beforeAll(function () {
    $capsule = new Capsule();
    $capsule->addConnection([
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    Model::setConnectionResolver($capsule->getDatabaseManager());
});

test('scheduler health thresholds derive healthy degraded and unhealthy states', function () {
    $service = new SchedulerHealthService();

    expect($service->deriveStatus(null))->toBe('unhealthy')
        ->and($service->deriveStatus(0))->toBe('healthy')
        ->and($service->deriveStatus(SchedulerHealthService::HEALTHY_THRESHOLD_SECONDS))->toBe('healthy')
        ->and($service->deriveStatus(SchedulerHealthService::HEALTHY_THRESHOLD_SECONDS + 1))->toBe('degraded')
        ->and($service->deriveStatus(SchedulerHealthService::DEGRADED_THRESHOLD_SECONDS))->toBe('degraded')
        ->and($service->deriveStatus(SchedulerHealthService::DEGRADED_THRESHOLD_SECONDS + 1))->toBe('unhealthy');
});

test('worker health is unknown when scheduler is not healthy', function () {
    $service = new WorkerHealthService();
    $record = new SystemWorkerHealth();
    $record->setRawAttributes([
        'last_worker_run_at' => Carbon::now()->subSeconds(30)->toDateTimeString(),
        'last_worker_success_at' => Carbon::now()->subSeconds(30)->toDateTimeString(),
    ], true);

    expect($service->deriveStatus($record, 'degraded', 30))->toBe('unknown')
        ->and($service->deriveStatus($record, 'unhealthy', 30))->toBe('unknown');
});

test('worker health is healthy when last run is fresh and no newer failure exists', function () {
    $service = new WorkerHealthService();
    $record = new SystemWorkerHealth();
    $record->setRawAttributes([
        'last_worker_run_at' => Carbon::now()->subSeconds(30)->toDateTimeString(),
        'last_worker_success_at' => Carbon::now()->subSeconds(30)->toDateTimeString(),
    ], true);

    expect($service->deriveStatus($record, 'healthy', 30))->toBe('healthy');
});

test('worker health degrades when a newer failure exists even with a recent run', function () {
    $service = new WorkerHealthService();
    $record = new SystemWorkerHealth();
    $record->setRawAttributes([
        'last_worker_run_at' => Carbon::now()->subSeconds(30)->toDateTimeString(),
        'last_worker_success_at' => Carbon::now()->subSeconds(120)->toDateTimeString(),
        'last_worker_failed_at' => Carbon::now()->subSeconds(20)->toDateTimeString(),
        'last_worker_error_code' => 'TASK_EXECUTION_FAILED',
    ], true);

    expect($service->deriveStatus($record, 'healthy', 30))->toBe('degraded');
});

test('worker health becomes unhealthy when the worker run is stale', function () {
    $service = new WorkerHealthService();
    $record = new SystemWorkerHealth();
    $record->setRawAttributes([
        'last_worker_run_at' => Carbon::now()->subSeconds(WorkerHealthService::DEGRADED_THRESHOLD_SECONDS + 10)->toDateTimeString(),
        'last_worker_success_at' => Carbon::now()->subSeconds(WorkerHealthService::DEGRADED_THRESHOLD_SECONDS + 10)->toDateTimeString(),
    ], true);

    expect($service->deriveStatus($record, 'healthy', WorkerHealthService::DEGRADED_THRESHOLD_SECONDS + 10))
        ->toBe('unhealthy');
});
