<?php

use EvolutionCMS\Services\Store\ModuleActionService;

function decodeModuleActionJsonResponse(array $response): array
{
    expect($response['handled'] ?? false)->toBeTrue()
        ->and($response['content_type'] ?? '')->toContain('application/json');

    $decoded = json_decode($response['body'] ?? '', true);
    expect(is_array($decoded))->toBeTrue();

    return $decoded;
}

test('scheduler status endpoint denies access without system task view permission', function () {
    $service = new ModuleActionService();
    $store = new class {
        public function isSuperAdmin()
        {
            return false;
        }

        public function getRequesterSnapshot()
        {
            return [
                'permissions' => [
                    'system_tasks.view' => 0,
                ],
            ];
        }
    };

    $response = $service->handle($store, 'system_task_scheduler_status');
    $payload = decodeModuleActionJsonResponse($response);

    expect($payload['ok'])->toBeFalse()
        ->and($payload['error_code'])->toBe('ACL_DENIED');
});

test('worker status endpoint returns payload for users with system task view permission', function () {
    $service = new ModuleActionService();
    $store = new class {
        public function isSuperAdmin()
        {
            return false;
        }

        public function getRequesterSnapshot()
        {
            return [
                'permissions' => [
                    'system_tasks.view' => 1,
                ],
            ];
        }

        public function schedulerHealthService()
        {
            return new class {
                public function getStatusPayload()
                {
                    return [
                        'status' => 'healthy',
                        'last_heartbeat_at' => '2026-04-12T10:00:00+00:00',
                    ];
                }
            };
        }

        public function workerHealthService()
        {
            return new class {
                public function getStatusPayload($schedulerHealthService)
                {
                    expect($schedulerHealthService)->not->toBeNull();

                    return [
                        'status' => 'healthy',
                        'last_worker_run_at' => '2026-04-12T10:00:10+00:00',
                    ];
                }
            };
        }
    };

    $response = $service->handle($store, 'system_task_worker_status');
    $payload = decodeModuleActionJsonResponse($response);

    expect($payload['status'])->toBe('healthy')
        ->and($payload['last_worker_run_at'])->toBe('2026-04-12T10:00:10+00:00');
});

test('combined system task health endpoint returns scheduler and worker payloads', function () {
    $service = new ModuleActionService();
    $store = new class {
        public function isSuperAdmin()
        {
            return false;
        }

        public function getRequesterSnapshot()
        {
            return [
                'permissions' => [
                    'system_tasks.view' => 1,
                ],
            ];
        }

        public function schedulerHealthService()
        {
            return new class {
                public function getStatusPayload()
                {
                    return [
                        'status' => 'healthy',
                        'last_heartbeat_at' => '2026-04-12T10:00:00+00:00',
                    ];
                }
            };
        }

        public function workerHealthService()
        {
            return new class {
                public function getStatusPayload($schedulerHealthService)
                {
                    expect($schedulerHealthService)->not->toBeNull();

                    return [
                        'status' => 'degraded',
                        'last_worker_run_at' => '2026-04-12T10:00:10+00:00',
                    ];
                }
            };
        }
    };

    $response = $service->handle($store, 'system_task_health');
    $payload = decodeModuleActionJsonResponse($response);

    expect($payload['ok'])->toBeTrue()
        ->and($payload['scheduler']['status'])->toBe('healthy')
        ->and($payload['worker']['status'])->toBe('degraded');
});

test('system task status endpoint forwards requester snapshot and super admin context', function () {
    $service = new ModuleActionService();
    $store = new class {
        public array $received = [];

        public function isSuperAdmin()
        {
            return true;
        }

        public function getRequesterSnapshot()
        {
            return [
                'user_id' => 7,
                'permissions' => [
                    'system_tasks.view' => 1,
                ],
            ];
        }

        public function systemTaskService()
        {
            return new class($this) {
                protected $store;

                public function __construct($store)
                {
                    $this->store = $store;
                }

                public function getTaskStatusPayload($id = 0, $uuid = '', array $requesterSnapshot = [], $isSuperAdmin = false)
                {
                    $this->store->received = [
                        'id' => $id,
                        'uuid' => $uuid,
                        'requesterSnapshot' => $requesterSnapshot,
                        'isSuperAdmin' => $isSuperAdmin,
                    ];

                    return [
                        'ok' => true,
                        'task' => [
                            'id' => $id,
                            'uuid' => $uuid,
                            'status' => 'queued',
                        ],
                    ];
                }
            };
        }
    };

    $response = $service->handle($store, 'system_task_status', [
        'task_id' => 42,
        'task_uuid' => 'abc-123',
    ]);
    $payload = decodeModuleActionJsonResponse($response);

    expect($payload['ok'])->toBeTrue()
        ->and($payload['task']['id'])->toBe(42)
        ->and($store->received)->toBe([
            'id' => 42,
            'uuid' => 'abc-123',
            'requesterSnapshot' => [
                'user_id' => 7,
                'permissions' => [
                    'system_tasks.view' => 1,
                ],
            ],
            'isSuperAdmin' => true,
        ]);
});
