<?php namespace EvolutionCMS\Services\Store;

class ModuleActionService
{
    public function handle($store, $action, array $request = [], array $files = [], array $get = [], array $post = [])
    {
        switch ((string) $action) {
            case 'saveuser':
                $_SESSION['STORE_USER'] = $post['res'] ?? '';
                return ['handled' => true];

            case 'exituser':
                $_SESSION['STORE_USER'] = '';
                return ['handled' => true];

            case 'install2_step':
                $_GET['action'] = 'install';
                require $store->getModulePath() . '/installer/index.php';
                return ['handled' => true];

            case 'install':
            case 'install_file':
                $response = $store->packageInstallFlowService()->handleLegacyInstall($action, $request, $files, $get, $post);
                return [
                    'handled' => true,
                    'content_type' => $response['content_type'] ?? null,
                    'body' => $response['body'] ?? '',
                    'terminate' => true,
                ];

            case 'console_catalog':
                $consoleCatalog = $store->getConsoleCatalog();
                return $this->json([
                    'ok' => true,
                    'items' => $consoleCatalog,
                    'count' => count($consoleCatalog),
                ]);

            case 'console_readme':
                return $this->json($store->getConsoleReadmePayload(
                    isset($get['repo']) ? (string) $get['repo'] : '',
                    isset($get['branch']) ? (string) $get['branch'] : '',
                    isset($get['source_url']) ? (string) $get['source_url'] : ''
                ));

            case 'legacy_delete_preview':
                return $this->json($store->buildLegacyDeletePreview(
                    isset($request['cid']) ? (int) $request['cid'] : 0,
                    isset($request['file']) ? (string) $request['file'] : '',
                    isset($request['name']) ? (string) $request['name'] : '',
                    isset($request['version']) ? (string) $request['version'] : ''
                ));

            case 'legacy_delete_run':
                return $this->json($store->runLegacyDelete(
                    isset($request['token']) ? (string) $request['token'] : '',
                    isset($request['selection']) && is_array($request['selection']) ? $request['selection'] : []
                ));

            case 'refresh_installed_state':
                $legacyInstalled = $store->getLegacyInstalledState();
                return $this->json([
                    'ok' => true,
                    'installed_state' => [
                        'legacy_by_type' => $legacyInstalled['by_type'],
                        'legacy_items' => $legacyInstalled['items'],
                        'console_by_composer' => $store->getConsoleInstalledState(),
                    ],
                ]);

            case 'system_task_scheduler_status':
                $requesterSnapshot = $store->getRequesterSnapshot();
                if (!$this->hasSystemTaskViewAccess($store, $requesterSnapshot)) {
                    return $this->json([
                        'ok' => false,
                        'error_code' => 'ACL_DENIED',
                        'message' => 'You do not have access to system task health status.',
                    ]);
                }
                return $this->json($store->schedulerHealthService()->getStatusPayload());

            case 'system_task_worker_status':
                $requesterSnapshot = $store->getRequesterSnapshot();
                if (!$this->hasSystemTaskViewAccess($store, $requesterSnapshot)) {
                    return $this->json([
                        'ok' => false,
                        'error_code' => 'ACL_DENIED',
                        'message' => 'You do not have access to system task health status.',
                    ]);
                }
                return $this->json($store->workerHealthService()->getStatusPayload($store->schedulerHealthService()));

            case 'system_task_health':
                $requesterSnapshot = $store->getRequesterSnapshot();
                if (!$this->hasSystemTaskViewAccess($store, $requesterSnapshot)) {
                    return $this->json([
                        'ok' => false,
                        'error_code' => 'ACL_DENIED',
                        'message' => 'You do not have access to system task health status.',
                    ]);
                }
                return $this->json([
                    'ok' => true,
                    'scheduler' => $store->schedulerHealthService()->getStatusPayload(),
                    'worker' => $store->workerHealthService()->getStatusPayload($store->schedulerHealthService()),
                ]);

            case 'system_task_create':
                return $this->json($store->systemTaskService()->createTaskFromStoreRequest(
                    isset($request['type']) ? (string) $request['type'] : '',
                    $request,
                    $store->getRequesterSnapshot(),
                    $store->isSuperAdmin()
                ));

            case 'system_task_status':
                return $this->json($store->systemTaskService()->getTaskStatusPayload(
                    isset($request['task_id']) ? (int) $request['task_id'] : 0,
                    isset($request['task_uuid']) ? (string) $request['task_uuid'] : '',
                    $store->getRequesterSnapshot(),
                    $store->isSuperAdmin()
                ));

            case 'system_task_result':
                return $this->json($store->systemTaskService()->getTaskResultPayload(
                    isset($request['task_id']) ? (int) $request['task_id'] : 0,
                    isset($request['task_uuid']) ? (string) $request['task_uuid'] : '',
                    $store->getRequesterSnapshot(),
                    $store->isSuperAdmin()
                ));

            case 'system_task_cancel':
                return $this->json($store->systemTaskService()->cancelQueuedTaskPayload(
                    isset($request['task_id']) ? (int) $request['task_id'] : 0,
                    isset($request['task_uuid']) ? (string) $request['task_uuid'] : '',
                    $store->getRequesterSnapshot(),
                    $store->isSuperAdmin()
                ));

            case 'refresh_manager_permissions':
                return $this->json($store->refreshCurrentManagerPermissions());
        }

        return ['handled' => false];
    }

    protected function json(array $payload)
    {
        return [
            'handled' => true,
            'content_type' => 'application/json; charset=UTF-8',
            'body' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'terminate' => true,
        ];
    }

    protected function hasSystemTaskViewAccess($store, array $requesterSnapshot = [])
    {
        return $store->isSuperAdmin()
            || !empty($requesterSnapshot['permissions']['system_tasks.view']);
    }
}
