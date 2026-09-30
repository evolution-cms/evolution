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
                if ($action === 'install_file') {
                    $artifactBody = $this->queueComposerArtifactInstall($store, $files);
                    if ($artifactBody !== null) {
                        return [
                            'handled' => true,
                            'body' => $artifactBody,
                            'terminate' => true,
                        ];
                    }
                }

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

    /**
     * Queue the install of an uploaded archive that holds a Composer package.
     *
     * Such a package has no install/ directory for the legacy installer to run; it is
     * kept in the local artifact repository and installed by the scheduler, exactly as
     * a package picked from the catalog.
     *
     * @since 3.5.9
     * @return string|null Response text, or null when the upload is a legacy package.
     */
    protected function queueComposerArtifactInstall($store, array $files)
    {
        $upload = $files['install_file'] ?? null;
        $tmpName = is_array($upload) ? (string) ($upload['tmp_name'] ?? '') : '';
        $fileName = is_array($upload) ? (string) ($upload['name'] ?? '') : '';
        if ($tmpName === '' || !is_uploaded_file($tmpName) || strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) !== 'zip') {
            return null;
        }

        return $this->queueComposerArtifactFile($store, new ComposerArtifactService(), $tmpName, $fileName);
    }

    /**
     * @since 3.5.9
     * @return string|null Response text, or null when the archive holds no Composer package.
     */
    protected function queueComposerArtifactFile($store, ComposerArtifactService $artifacts, string $zipPath, string $fileName)
    {
        $package = $artifacts->inspect($zipPath, $fileName);
        if ($package === null) {
            return null;
        }

        $lang = is_array($store->lang ?? null) ? $store->lang : [];
        if ($package['invalid']) {
            return e(sprintf(
                $lang['install_file_artifact_invalid'] ?? 'The archive looks like a Composer package, but %1$s is not valid JSON with a package "name". Nothing was installed.',
                $package['entry']
            ));
        }

        if ($package['version'] === '') {
            return e(sprintf(
                $lang['install_file_artifact_no_version'] ?? 'The archive holds Composer package %1$s but no version. Add "version" to its composer.json or put the version in the file name, e.g. %2$s-1.0.0.zip.',
                $package['name'],
                basename($package['name'])
            ));
        }

        try {
            $archive = $artifacts->store($zipPath, $package);
        } catch (\Throwable $exception) {
            return e(sprintf(
                $lang['install_file_artifact_failed'] ?? 'Composer package %1$s could not be queued: %2$s',
                $package['name'],
                $exception->getMessage()
            ));
        }

        $response = $store->systemTaskService()->createArtifactInstallTask(
            $package['name'],
            $package['version'],
            basename($fileName),
            $store->getRequesterSnapshot(),
            $store->isSuperAdmin()
        );

        if (empty($response['ok'])) {
            @unlink($archive);

            return e(sprintf(
                $lang['install_file_artifact_failed'] ?? 'Composer package %1$s could not be queued: %2$s',
                $package['name'],
                (string) ($response['message'] ?? '')
            ));
        }

        return e(sprintf(
            $lang['install_file_artifact_queued'] ?? 'Composer package %1$s %2$s is queued for installation as system task #%3$s. The scheduler installs it from the uploaded archive.',
            $package['name'],
            $package['version'],
            (string) ($response['task']['id'] ?? '')
        ));
    }

    protected function hasSystemTaskViewAccess($store, array $requesterSnapshot = [])
    {
        return $store->isSuperAdmin()
            || !empty($requesterSnapshot['permissions']['system_tasks.view']);
    }
}
