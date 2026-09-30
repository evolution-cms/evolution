<?php

use EvolutionCMS\Services\Store\ComposerArtifactService;
use EvolutionCMS\Services\Store\ModuleActionService;

/**
 * Artifact service whose inspect() result is scripted and whose store() only records.
 */
final class ScriptedComposerArtifactService extends ComposerArtifactService
{
    public ?array $package = null;
    public array $stored = [];
    public string $storedPath = '';

    public function inspect(string $zipPath, string $fileName = ''): ?array
    {
        return $this->package;
    }

    public function store(string $zipPath, array $package): string
    {
        $this->stored[] = $package['name'];
        $this->storedPath = tempnam(sys_get_temp_dir(), 'evo-stored-');

        return $this->storedPath;
    }
}

/**
 * Store stand-in that records the artifact install task it is asked to queue.
 */
function makeArtifactUploadStore(array $taskResponse): object
{
    return new class($taskResponse) {
        public array $lang = [];
        public array $taskCalls = [];

        public function __construct(private array $taskResponse)
        {
        }

        public function isSuperAdmin()
        {
            return false;
        }

        public function getRequesterSnapshot()
        {
            return ['permissions' => ['exec_module' => 1]];
        }

        public function systemTaskService()
        {
            $store = $this;

            return new class($store) {
                public function __construct(private object $store)
                {
                }

                public function createArtifactInstallTask($name, $version, $archiveName = '', array $requester = [], $isSuperAdmin = false)
                {
                    $this->store->taskCalls[] = [$name, $version, $archiveName];

                    return $this->store->taskResponse();
                }
            };
        }

        public function taskResponse(): array
        {
            return $this->taskResponse;
        }
    };
}

function queueArtifactUpload(object $store, ScriptedComposerArtifactService $artifacts, string $fileName = 'hello-evo-1.0.0.zip')
{
    $method = new ReflectionMethod(ModuleActionService::class, 'queueComposerArtifactFile');
    $method->setAccessible(true);

    return $method->invoke(new ModuleActionService(), $store, $artifacts, '/tmp/upload.zip', $fileName);
}

function artifactPackage(array $overrides = []): array
{
    return array_merge([
        'name' => 'evodemo/hello-evo',
        'version' => '1.0.0',
        'entry' => 'hello-evo/composer.json',
        'composer' => ['name' => 'evodemo/hello-evo'],
        'invalid' => false,
    ], $overrides);
}

test('an uploaded composer package is stored and queued as a console install', function () {
    $store = makeArtifactUploadStore(['ok' => true, 'task' => ['id' => 42]]);
    $artifacts = new ScriptedComposerArtifactService();
    $artifacts->package = artifactPackage();

    $body = queueArtifactUpload($store, $artifacts);

    expect($body)->toContain('evodemo/hello-evo 1.0.0')
        ->and($body)->toContain('#42')
        ->and($artifacts->stored)->toBe(['evodemo/hello-evo'])
        ->and($store->taskCalls)->toBe([['evodemo/hello-evo', '1.0.0', 'hello-evo-1.0.0.zip']])
        ->and(is_file($artifacts->storedPath))->toBeTrue();

    @unlink($artifacts->storedPath);
});

test('a legacy upload is left to the legacy installer', function () {
    $store = makeArtifactUploadStore(['ok' => true, 'task' => ['id' => 1]]);
    $artifacts = new ScriptedComposerArtifactService();

    expect(queueArtifactUpload($store, $artifacts))->toBeNull()
        ->and($store->taskCalls)->toBe([]);
});

test('an invalid composer package is refused instead of being copied into the web root', function () {
    $store = makeArtifactUploadStore(['ok' => true, 'task' => ['id' => 1]]);
    $artifacts = new ScriptedComposerArtifactService();
    $artifacts->package = artifactPackage(['name' => '', 'version' => '', 'composer' => [], 'invalid' => true]);

    $body = queueArtifactUpload($store, $artifacts);

    expect($body)->toContain('hello-evo/composer.json')
        ->and($body)->toContain('Nothing was installed')
        ->and($artifacts->stored)->toBe([])
        ->and($store->taskCalls)->toBe([]);
});

test('a composer package without a version asks for one and queues nothing', function () {
    $store = makeArtifactUploadStore(['ok' => true, 'task' => ['id' => 1]]);
    $artifacts = new ScriptedComposerArtifactService();
    $artifacts->package = artifactPackage(['version' => '']);

    $body = queueArtifactUpload($store, $artifacts, 'hello-evo.zip');

    expect($body)->toContain('hello-evo-1.0.0.zip')
        ->and($artifacts->stored)->toBe([])
        ->and($store->taskCalls)->toBe([]);
});

test('a refused task removes the stored archive again', function () {
    $store = makeArtifactUploadStore(['ok' => false, 'error_code' => 'ACL_DENIED', 'message' => 'Denied <b>here</b>']);
    $artifacts = new ScriptedComposerArtifactService();
    $artifacts->package = artifactPackage();

    $body = queueArtifactUpload($store, $artifacts);

    expect($body)->toContain('could not be queued')
        ->and($body)->toContain('Denied &lt;b&gt;here&lt;/b&gt;')
        ->and(is_file($artifacts->storedPath))->toBeFalse();
});

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
