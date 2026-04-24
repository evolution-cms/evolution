<?php
/*
@TODO:
— auto backup system files
— rollback option for updater
*/
if (!defined('EVO_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}
if (empty($_SESSION['mgrInternalKey'])) {
    return;
}
// get manager role
$internalKey = $modx->getLoginUserID();
$sid = $modx->sid;
$role = isset($_SESSION['mgrRole']) ? $_SESSION['mgrRole'] : '';
$user = isset($_SESSION['mgrShortname']) ? $_SESSION['mgrShortname'] : '';
$wdgVisibility = isset($wdgVisibility) ? $wdgVisibility : '';
$ThisRole = isset($ThisRole) ? $ThisRole : '';
$ThisUser = isset($ThisUser) ? $ThisUser : '';
$version = isset($version) ? $version : 'evolution-cms/evolution';
$type = isset($type) ? $type : 'tags';
$branch = isset($branch) ? trim((string)$branch) : 'develop';
if ($branch === '') {
    $branch = 'develop';
}
$showButton = isset($showButton) ? $showButton : 'AdminOnly';
$supportLink = isset($supportLink) ? trim((string)$supportLink) : '';
if ($supportLink === '') {
    $supportLink = 'https://evo.im/support.html';
}
$result = '';

if (!function_exists('updaterParseSemver')) {
    function updaterParseSemver($versionString)
    {
        $match = [];
        if (preg_match('/(\d+)\.(\d+)\.(\d+)/', (string)$versionString, $match)) {
            return [(int)$match[1], (int)$match[2], (int)$match[3]];
        }

        $numbers = [];
        preg_match_all('/\d+/', (string)$versionString, $numbers);
        $parts = isset($numbers[0]) ? $numbers[0] : [];

        return [
            isset($parts[0]) ? (int)$parts[0] : 0,
            isset($parts[1]) ? (int)$parts[1] : 0,
            isset($parts[2]) ? (int)$parts[2] : 0,
        ];
    }
}

if (!function_exists('updaterGetSeverity')) {
    function updaterGetSeverity($currentVersion, $latestVersion)
    {
        $current = updaterParseSemver($currentVersion);
        $latest = updaterParseSemver($latestVersion);

        if ($latest[0] > $current[0]) {
            return 'critical';
        }
        if ($latest[1] > $current[1]) {
            return 'warning';
        }
        if ($latest[2] > $current[2]) {
            return 'info';
        }

        return 'info';
    }
}

if (!function_exists('updaterBuildHideKey')) {
    function updaterBuildHideKey($latestVersionRaw, $userId)
    {
        $versionPart = preg_replace('/[^A-Za-z0-9]+/', '_', strtolower((string)$latestVersionRaw));
        $versionPart = trim((string)$versionPart, '_');
        if ($versionPart === '') {
            $versionPart = 'version';
        }

        return '_hide_updater_notice_until_' . $versionPart . '_u_' . (int)$userId;
    }
}

if (!function_exists('updaterCanShowUpdateActions')) {
    function updaterCanShowUpdateActions($showButton, $role, $errors = 0)
    {
        return ((int)$role === 1)
            && ((string)$showButton !== 'hide')
            && ((int)$errors <= 0);
    }
}

if (!function_exists('updaterBuildReleaseUrls')) {
    function updaterBuildReleaseUrls($repository, $latestVersionRaw)
    {
        $repo = trim((string)$repository);
        $latest = trim((string)$latestVersionRaw);
        $base = 'https://github.com/' . $repo . '/releases';
        $urls = [];

        if ($latest !== '') {
            $urls[] = $base . '/tag/' . rawurlencode($latest);
            if (strpos($latest, 'v') !== 0) {
                $urls[] = $base . '/tag/' . rawurlencode('v' . $latest);
            }
        }
        $urls[] = $base;

        return array_values(array_unique($urls));
    }
}

if (!function_exists('updaterBuildBranchUrls')) {
    function updaterBuildBranchUrls($repository, $branchRef)
    {
        $repo = trim((string)$repository);
        $ref = trim((string)$branchRef);
        $base = 'https://github.com/' . $repo;

        if ($ref === '') {
            return [$base];
        }

        if (preg_match('/^[0-9a-f]{7,40}$/i', $ref)) {
            return [$base . '/commit/' . rawurlencode($ref), $base];
        }

        return [
            $base . '/tree/' . str_replace('%2F', '/', rawurlencode($ref)),
            $base . '/branches',
        ];
    }
}

if (!function_exists('updaterFetchReleasePublishedAt')) {
    function updaterFetchReleasePublishedAt($repository, $versionRaw)
    {
        $repo = trim((string)$repository);
        $version = trim((string)$versionRaw);

        if ($repo === '' || $version === '') {
            return '';
        }

        $tags = [$version];
        if (strpos($version, 'v') === 0) {
            $withoutPrefix = substr($version, 1);
            if ($withoutPrefix !== '') {
                $tags[] = $withoutPrefix;
            }
        } else {
            $tags[] = 'v' . $version;
        }

        foreach (array_unique($tags) as $tag) {
            $url = 'https://api.github.com/repos/' . $repo . '/releases/tags/' . rawurlencode($tag);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_REFERER, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: updateNotify widget']);
            $response = curl_exec($ch);
            curl_close($ch);

            if (!is_string($response) || $response === '' || strpos(ltrim($response), '{') !== 0) {
                continue;
            }

            $release = json_decode($response, true);
            if (isset($release['published_at']) && $release['published_at'] !== '') {
                return (string)$release['published_at'];
            }
        }

        return '';
    }
}

if (!function_exists('updaterFormatReleaseDate')) {
    function updaterFormatReleaseDate($dateValue)
    {
        $raw = trim((string)$dateValue);
        if ($raw === '') {
            return '';
        }

        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            return $raw;
        }

        return date('d.m.Y', $timestamp);
    }
}

if (!function_exists('updaterLang')) {
    function updaterLang(array $lang, $key, $fallback = '')
    {
        return isset($lang[$key]) && $lang[$key] !== '' ? (string)$lang[$key] : (string)$fallback;
    }
}

if (!function_exists('updaterEnsureSystemTaskToken')) {
    function updaterEnsureSystemTaskToken()
    {
        if (empty($_SESSION['updater_system_task_token'])) {
            try {
                $_SESSION['updater_system_task_token'] = bin2hex(random_bytes(16));
            } catch (Exception $exception) {
                $_SESSION['updater_system_task_token'] = md5(uniqid('updater-system-task-', true));
            }
        }

        return (string)$_SESSION['updater_system_task_token'];
    }
}

if (!function_exists('updaterJsonResponse')) {
    function updaterJsonResponse(array $payload)
    {
        while (ob_get_level() > 0) {
            if (!@ob_end_clean()) {
                break;
            }
        }

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }

        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('updaterBuildSystemTaskUiState')) {
    function updaterBuildSystemTaskUiState()
    {
        $state = [
            'ok' => false,
            'can_view' => false,
            'can_queue_site_update' => false,
            'scheduler' => null,
            'worker' => null,
            'message' => '',
        ];

        try {
            if (!class_exists('\EvolutionCMS\Services\Store\StoreContextService')) {
                $state['message'] = 'System task services are not available.';
                return $state;
            }

            $context = new \EvolutionCMS\Services\Store\StoreContextService();
            $requesterSnapshot = $context->buildRequesterSnapshot();
            $isSuperAdmin = $context->isSuperAdmin();
            $permissions = isset($requesterSnapshot['permissions']) && is_array($requesterSnapshot['permissions'])
                ? $requesterSnapshot['permissions']
                : [];

            $schedulerService = new \EvolutionCMS\Services\SystemTasks\SchedulerHealthService();
            $workerService = new \EvolutionCMS\Services\SystemTasks\WorkerHealthService();
            $scheduler = $schedulerService->getStatusPayload();
            $worker = $workerService->getStatusPayload($schedulerService);

            $schedulerStatus = (string)($scheduler['status'] ?? 'unhealthy');
            $workerStatus = (string)($worker['status'] ?? 'unknown');

            $state['ok'] = true;
            $state['can_view'] = $isSuperAdmin || !empty($permissions['system_tasks.view']);
            $state['scheduler'] = $scheduler;
            $state['worker'] = $worker;
            $state['can_queue_site_update'] = $isSuperAdmin
                && !empty($permissions['exec_module'])
                && !empty($permissions['system_tasks.site_update'])
                && $schedulerStatus === 'healthy'
                && $workerStatus !== 'unhealthy';

            return $state;
        } catch (Throwable $exception) {
            $state['message'] = $exception->getMessage();
            return $state;
        }
    }
}

if (!function_exists('updaterHandleSystemTaskRequest')) {
    function updaterHandleSystemTaskRequest()
    {
        if (empty($_SESSION['mgrInternalKey'])) {
            updaterJsonResponse([
                'ok' => false,
                'error_code' => 'MANAGER_SESSION_REQUIRED',
                'message' => 'Manager session is required.',
            ]);
        }

        if ((int)($_SESSION['mgrRole'] ?? 0) !== 1) {
            updaterJsonResponse([
                'ok' => false,
                'error_code' => 'ACL_DENIED',
                'message' => 'Only administrators can run system updates from the manager.',
            ]);
        }

        $action = isset($_REQUEST['updater_task_action']) ? trim((string)$_REQUEST['updater_task_action']) : '';
        if ($action === '') {
            updaterJsonResponse([
                'ok' => false,
                'error_code' => 'ACTION_REQUIRED',
                'message' => 'System task action is required.',
            ]);
        }

        $requiresToken = in_array($action, ['create', 'cancel'], true);
        if ($requiresToken) {
            $token = isset($_REQUEST['updater_task_token']) ? (string)$_REQUEST['updater_task_token'] : '';
            if ($token === '' || !hash_equals(updaterEnsureSystemTaskToken(), $token)) {
                updaterJsonResponse([
                    'ok' => false,
                    'error_code' => 'TOKEN_INVALID',
                    'message' => 'System task token is invalid.',
                ]);
            }
        }

        try {
            $context = new \EvolutionCMS\Services\Store\StoreContextService();
            $requesterSnapshot = $context->buildRequesterSnapshot();
            $isSuperAdmin = $context->isSuperAdmin();
            $permissions = isset($requesterSnapshot['permissions']) && is_array($requesterSnapshot['permissions'])
                ? $requesterSnapshot['permissions']
                : [];
            $canView = $isSuperAdmin || !empty($permissions['system_tasks.view']);

            if (!$canView) {
                updaterJsonResponse([
                    'ok' => false,
                    'error_code' => 'ACL_DENIED',
                    'message' => 'You do not have access to system task status.',
                ]);
            }

            $schedulerService = new \EvolutionCMS\Services\SystemTasks\SchedulerHealthService();
            $workerService = new \EvolutionCMS\Services\SystemTasks\WorkerHealthService();
            $taskService = new \EvolutionCMS\Services\SystemTasks\SystemTaskService();

            switch ($action) {
                case 'health':
                    updaterJsonResponse([
                        'ok' => true,
                        'scheduler' => $schedulerService->getStatusPayload(),
                        'worker' => $workerService->getStatusPayload($schedulerService),
                    ]);

                case 'create':
                    updaterJsonResponse($taskService->createTaskFromStoreRequest(
                        'site_update',
                        [
                            'target_ref' => isset($_REQUEST['target_ref']) ? (string)$_REQUEST['target_ref'] : '',
                            'update_repository' => isset($_REQUEST['update_repository']) ? (string)$_REQUEST['update_repository'] : '',
                            'backup_database' => isset($_REQUEST['backup_database']) ? (string)$_REQUEST['backup_database'] : '1',
                        ],
                        $requesterSnapshot,
                        $isSuperAdmin
                    ));

                case 'status':
                    updaterJsonResponse($taskService->getTaskStatusPayload(
                        isset($_REQUEST['task_id']) ? (int)$_REQUEST['task_id'] : 0,
                        isset($_REQUEST['task_uuid']) ? (string)$_REQUEST['task_uuid'] : '',
                        $requesterSnapshot,
                        $isSuperAdmin
                    ));

                case 'result':
                    updaterJsonResponse($taskService->getTaskResultPayload(
                        isset($_REQUEST['task_id']) ? (int)$_REQUEST['task_id'] : 0,
                        isset($_REQUEST['task_uuid']) ? (string)$_REQUEST['task_uuid'] : '',
                        $requesterSnapshot,
                        $isSuperAdmin
                    ));

                case 'cancel':
                    updaterJsonResponse($taskService->cancelQueuedTaskPayload(
                        isset($_REQUEST['task_id']) ? (int)$_REQUEST['task_id'] : 0,
                        isset($_REQUEST['task_uuid']) ? (string)$_REQUEST['task_uuid'] : '',
                        $requesterSnapshot,
                        $isSuperAdmin
                    ));
            }
        } catch (Throwable $exception) {
            updaterJsonResponse([
                'ok' => false,
                'error_code' => 'SYSTEM_TASK_REQUEST_FAILED',
                'message' => $exception->getMessage(),
            ]);
        }

        updaterJsonResponse([
            'ok' => false,
            'error_code' => 'ACTION_NOT_ALLOWED',
            'message' => 'System task action is not allowed.',
        ]);
    }
}

if (!function_exists('updaterBuildSystemTaskScript')) {
    function updaterBuildSystemTaskScript(array $config, array $labels)
    {
        $payloadJson = json_encode(
            ['config' => $config, 'labels' => $labels],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );
        $payloadJson = htmlspecialchars($payloadJson ?: '{}', ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $script = <<<'HTML'
<script>
(function () {
    var payloadNode = document.getElementById('updater-system-task-payload');
    var payload = {};
    var timer = null;
    var activeTask = null;
    var lastTaskResult = null;
    var reloadOnClose = false;

    try {
        payload = payloadNode ? JSON.parse(payloadNode.textContent || '{}') : {};
    } catch (error) {
        payload = {};
    }

    var config = payload.config || {};
    var labels = payload.labels || {};

    function t(key, fallback) {
        return labels[key] || fallback || key;
    }

    function esc(value) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };

        return String(value === undefined || value === null ? '' : value).replace(/[&<>"']/g, function (ch) {
            return map[ch] || ch;
        });
    }

    function qs(data) {
        var params = [];

        for (var key in data) {
            if (Object.prototype.hasOwnProperty.call(data, key)) {
                params.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key] === undefined || data[key] === null ? '' : data[key]));
            }
        }

        return params.join('&');
    }

    function request(action, data) {
        data = data || {};
        data.updater_system_task = 1;
        data.updater_task_action = action;

        if (config.token) {
            data.updater_task_token = config.token;
        }

        return fetch(config.endpoint || 'index.php?a=2&updater_system_task=1', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'Accept': 'application/json'
            },
            body: qs(data)
        }).then(function (response) {
            return response.text().then(function (text) {
                var normalized = String(text || '').replace(/^\s+|\s+$/g, '');

                if (normalized === '') {
                    throw new Error(t('invalid_response', 'Manager returned an empty update response.'));
                }

                try {
                    return JSON.parse(normalized);
                } catch (error) {
                    var firstJsonChar = normalized.indexOf('{');
                    var lastJsonChar = normalized.lastIndexOf('}');

                    if (firstJsonChar !== -1 && lastJsonChar > firstJsonChar) {
                        try {
                            return JSON.parse(normalized.substring(firstJsonChar, lastJsonChar + 1));
                        } catch (innerError) {
                            // Fall through to the normalized manager error below.
                        }
                    }

                    throw new Error(t('invalid_response', 'Manager returned an invalid update response.'));
                }
            });
        });
    }

    function close() {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }

        var modal = document.getElementById('updater-system-task-modal');

        if (modal) {
            modal.parentNode.removeChild(modal);
        }

        if (reloadOnClose) {
            window.location.reload();
        }
    }

    function shell() {
        var modal = document.getElementById('updater-system-task-modal');

        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'updater-system-task-modal';
            modal.innerHTML = '<div class="updater-system-task-backdrop"></div><div class="updater-system-task-dialog" role="dialog" aria-modal="true"><button type="button" class="updater-system-task-close" data-role="close">&times;</button><div data-role="content"></div></div>';
            document.body.appendChild(modal);
            modal.addEventListener('click', function (event) {
                if (event.target && event.target.getAttribute('data-role') === 'close') {
                    close();
                }
            });
        }

        return modal.querySelector('[data-role=content]');
    }

    function style() {
        if (document.getElementById('updater-system-task-style')) {
            return;
        }

        var css = [
            '#updater-system-task-modal{position:fixed;inset:0;z-index:2147483000;display:flex;align-items:center;justify-content:center;padding:24px}',
            '.updater-system-task-backdrop{position:absolute;inset:0;background:rgba(0,0,0,.62)}',
            '.updater-system-task-dialog{position:relative;width:min(720px,calc(100vw - 32px));max-height:calc(100vh - 48px);overflow:auto;background:#20242b;color:#f2f4f8;border:1px solid rgba(255,255,255,.12);border-radius:14px;box-shadow:0 24px 70px rgba(0,0,0,.45);padding:24px}',
            '.updater-system-task-close{position:absolute;top:10px;right:12px;border:0;background:transparent;color:#b9c1ce;font-size:26px;line-height:1;cursor:pointer}',
            '.updater-system-task-title{margin:0 34px 12px 0;font-size:22px;font-weight:700}',
            '.updater-system-task-text{margin:0 0 12px 0;color:#c9d0dc;line-height:1.45}',
            '.updater-system-task-warning{margin:12px 0;padding:12px 14px;border-radius:10px;background:rgba(255,193,7,.14);color:#ffd36a;border:1px solid rgba(255,193,7,.28)}',
            '.updater-system-task-warning.is-danger{background:rgba(220,53,69,.16);color:#ff7b8a;border-color:rgba(220,53,69,.36)}',
            '.updater-system-task-warning.is-success{background:rgba(40,167,69,.16);color:#8fe1a4;border-color:rgba(40,167,69,.36)}',
            '.updater-system-task-check{display:flex;gap:8px;align-items:flex-start;margin:12px 0;padding:10px 12px;border-radius:10px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);color:#e6ebf3}',
            '.updater-system-task-check input{margin-top:3px}',
            '.updater-system-task-meta{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;margin:14px 0}',
            '.updater-system-task-meta div{padding:10px 12px;border-radius:10px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08)}',
            '.updater-system-task-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:16px}',
            '.updater-system-task-progress{height:10px;background:rgba(255,255,255,.1);border-radius:999px;overflow:hidden;margin:14px 0}',
            '.updater-system-task-progress span{display:block;height:100%;width:0;background:#28a745;transition:width .25s}',
            '.updater-system-task-logs{margin-top:14px;max-height:220px;overflow:auto;border:1px solid rgba(255,255,255,.08);border-radius:10px;background:rgba(0,0,0,.18);padding:10px}',
            '.updater-system-task-log{padding:5px 0;color:#ccd4df;border-bottom:1px solid rgba(255,255,255,.06)}',
            '.updater-system-task-log:last-child{border-bottom:0}',
            '.updater-system-task-log.is-error{color:#ff9d9d}',
            '.updater-system-task-log.is-warning{color:#ffd36a}',
            '.updater-system-task-log.is-success{color:#8fe1a4}'
        ].join('');
        var tag = document.createElement('style');

        tag.id = 'updater-system-task-style';
        tag.textContent = css;
        document.head.appendChild(tag);
    }

    function setContent(html) {
        style();
        shell().innerHTML = html;
    }

    function renderConfirm() {
        setContent(
            '<h2 class="updater-system-task-title">' + esc(t('title', 'System update')) + '</h2>'
            + '<p class="updater-system-task-text">' + esc(t('intro', 'Scheduler is available. The update can be queued and monitored from the manager.')) + '</p>'
            + '<label class="updater-system-task-check"><input type="checkbox" data-role="backup-database" checked> <span>' + esc(t('backup_checkbox', 'Create database backup before updating')) + (config.currentVersion ? ' <small>(' + esc(t('current_version', 'current version')) + ': ' + esc(config.currentVersion) + ')</small>' : '') + '</span></label>'
            + '<div class="updater-system-task-meta">'
            + '<div><small>' + esc(t('current', 'Current version')) + '</small><br><strong>' + esc(config.currentVersion || '') + '</strong></div>'
            + '<div><small>' + esc(t('target', 'Target version')) + '</small><br><strong>' + esc(config.targetRef) + '</strong></div>'
            + '<div><small>' + esc(t('repository', 'Repository')) + '</small><br><strong>' + esc(config.repository) + '</strong></div>'
            + '<div><small>' + esc(t('health', 'Scheduler / worker')) + '</small><br><strong>' + esc(config.schedulerStatus) + ' / ' + esc(config.workerStatus) + '</strong></div>'
            + '</div>'
            + '<div class="updater-system-task-actions">'
            + '<button type="button" class="btn btn-success" data-role="confirm">' + esc(t('confirm', 'Start update')) + '</button>'
            + '<button type="button" class="btn btn-secondary" data-role="close">' + esc(t('cancel', 'Cancel')) + '</button>'
            + '</div>'
        );

        var content = shell();
        var confirm = content.querySelector('[data-role=confirm]');

        if (confirm) {
            confirm.addEventListener('click', queue);
        }
    }

    function renderTask(task, result) {
        task = task || {};
        result = result || {};

        var progress = parseInt(task.progress || 0, 10);
        var logs = result.logs || task.logs || [];
        var isFinished = task.status === 'succeeded' || task.status === 'failed';
        var isSucceeded = task.status === 'succeeded';

        if (isNaN(progress)) {
            progress = 0;
        }

        if (isFinished) {
            lastTaskResult = result;
        }

        if (isSucceeded) {
            reloadOnClose = true;
        }

        var html = '<h2 class="updater-system-task-title">' + esc(t('title', 'System update')) + '</h2>'
            + '<p class="updater-system-task-text">' + esc(task.message || t('queued', 'Update task queued. Waiting for worker...')) + '</p>'
            + '<div class="updater-system-task-meta">'
            + '<div><small>' + esc(t('status', 'Status')) + '</small><br><strong>' + esc(task.status || '') + '</strong></div>'
            + '<div><small>' + esc(t('step', 'Step')) + '</small><br><strong>' + esc(task.step || '') + '</strong></div>'
            + '<div><small>' + esc(t('progress', 'Progress')) + '</small><br><strong>' + progress + '%</strong></div>'
            + '</div>'
            + '<div class="updater-system-task-progress"><span style="width:' + Math.max(0, Math.min(100, progress)) + '%"></span></div>';

        if (isSucceeded) {
            html += '<div class="updater-system-task-warning is-success"><strong>' + esc(t('completed', 'Update completed. Close this window to reload the manager and verify the new version.')) + '</strong></div>';
        }

        if (logs.length) {
            html += '<div class="updater-system-task-logs">';
            logs.forEach(function (log) {
                var cls = 'updater-system-task-log';

                if (log.level === 'error') {
                    cls += ' is-error';
                } else if (log.level === 'warning') {
                    cls += ' is-warning';
                } else if (log.step === 'completed' || task.status === 'succeeded') {
                    cls += ' is-success';
                }

                html += '<div class="' + cls + '">' + esc(log.message || '') + '</div>';
            });
            html += '</div>';
        }

        if (isFinished) {
            html += '<div class="updater-system-task-actions"><button type="button" class="btn btn-primary" data-role="close">' + esc(isSucceeded ? t('close_reload', 'Close and reload') : t('close', 'Close')) + '</button></div>';
        }

        setContent(html);
    }

    function renderRecoverablePollError(error) {
        var logs = (lastTaskResult && lastTaskResult.logs) || (activeTask && activeTask.logs) || [];
        var html = '<h2 class="updater-system-task-title">' + esc(t('title', 'System update')) + '</h2>'
            + '<p class="updater-system-task-text">' + esc(t('response_changed', 'The manager response changed while the update was running. Close this window to reload the manager and read the final state.')) + '</p>';

        reloadOnClose = true;

        if (logs.length) {
            html += '<div class="updater-system-task-logs">';
            logs.forEach(function (log) {
                var cls = 'updater-system-task-log';

                if (log.level === 'error') {
                    cls += ' is-error';
                } else if (log.level === 'warning') {
                    cls += ' is-warning';
                }

                html += '<div class="' + cls + '">' + esc(log.message || '') + '</div>';
            });
            html += '</div>';
        }

        html += '<div class="updater-system-task-actions"><button type="button" class="btn btn-primary" data-role="close">' + esc(t('close_reload', 'Close and reload')) + '</button></div>';

        setContent(html);
    }

    function queue() {
        var content = shell();
        var backupCheckbox = content.querySelector('[data-role=backup-database]');
        var backupDatabase = !backupCheckbox || backupCheckbox.checked;

        setContent(
            '<h2 class="updater-system-task-title">' + esc(t('title', 'System update')) + '</h2>'
            + '<p class="updater-system-task-text">' + esc(t('queueing', 'Queueing update...')) + '</p>'
        );

        request('create', {target_ref: config.targetRef, update_repository: config.repository, backup_database: backupDatabase ? '1' : '0'}).then(function (response) {
            if (!response || !response.ok) {
                throw new Error((response && response.message) || t('failed', 'Unable to start update.'));
            }

            activeTask = response.task;
            renderTask(activeTask, response);
            poll();
        }).catch(function (error) {
            setContent(
                '<h2 class="updater-system-task-title">' + esc(t('title', 'System update')) + '</h2>'
                + '<div class="updater-system-task-warning">' + esc(error.message || t('failed', 'Unable to start update.')) + '</div>'
                + '<div class="updater-system-task-actions"><button type="button" class="btn btn-primary" data-role="close">' + esc(t('close', 'Close')) + '</button></div>'
            );
        });
    }

    function poll() {
        if (!activeTask || !activeTask.id) {
            return;
        }

        request('result', {task_id: activeTask.id}).then(function (response) {
            if (!response || !response.ok) {
                throw new Error((response && response.message) || t('failed', 'Unable to read update status.'));
            }

            activeTask = response.task;
            lastTaskResult = response.result || response;
            renderTask(activeTask, lastTaskResult);

            if (activeTask.status === 'succeeded' || activeTask.status === 'failed') {
                return;
            }

            timer = setTimeout(poll, 2200);
        }).catch(function (error) {
            try {
                renderRecoverablePollError(error);
            } catch (fallbackError) {
                setContent(
                    '<h2 class="updater-system-task-title">' + esc(t('title', 'System update')) + '</h2>'
                    + '<div class="updater-system-task-warning">' + esc(fallbackError.message || t('failed', 'Unable to read update status.')) + '</div>'
                    + '<div class="updater-system-task-actions"><button type="button" class="btn btn-primary" data-role="close">' + esc(t('close', 'Close')) + '</button></div>'
                );
            }
        });
    }

    window.EvoUpdaterSystemTask = {
        openConfirm: function () {
            renderConfirm();
            return false;
        },
        close: close
    };
})();
</script>
HTML;

        return '<script type="application/json" id="updater-system-task-payload">' . $payloadJson . '</script>' . $script;
    }
}

if (isset($_REQUEST['updater_system_task'])) {
    updaterHandleSystemTaskRequest();
}

if ($role != 1 && $wdgVisibility == 'AdminOnly') {

} else if ($role == 1 && $wdgVisibility == 'AdminExcluded') {

} else if ($role != $ThisRole && $wdgVisibility == 'ThisRoleOnly') {

} else if ($user != $ThisUser && $wdgVisibility == 'ThisUserOnly') {

} else {

    //lang
    $_lang = [];
    $plugin_path = EVO_BASE_PATH . "assets/plugins/updater/";
    include($plugin_path . 'lang/en.php');
    if (file_exists($plugin_path . 'lang/' . $modx->config['manager_language'] . '.php')) {
        include($plugin_path . 'lang/' . $modx->config['manager_language'] . '.php');
    }

    $e = &$modx->Event;
    if ($e->name == 'OnSiteRefresh') {
        array_map("unlink", glob(EVO_BASE_PATH . 'assets/cache/updater/*.json'));
    }

    if ($e->name == 'OnManagerWelcomeHome') {
        $errorsMessage = '';
        $errors = 0;
        if (!extension_loaded('curl')) {
            $errorsMessage .= '-' . $_lang['error_curl'] . '<br>';
            $errors += 1;
            $curlNotReady = true;
        }
        if (!extension_loaded('zip')) {
            $errorsMessage .= '-' . $_lang['error_zip'] . '<br>';
            $errors += 1;
        }
        if (!extension_loaded('openssl')) {
            $errorsMessage .= '-' . $_lang['error_openssl'] . '<br>';
            $errors += 1;
        }
        if (!is_writable(EVO_BASE_PATH . 'assets/')) {
            $errorsMessage .= '-' . $_lang['error_overwrite'] . '<br>';
            $errors += 1;
        }

        // Avoid "Fatal error: Call to undefined function curl_init()"
        if (isset($curlNotReady)) {
            $output = '<div class="card-body">
                <small style="color:red;font-size:10px">' . $errorsMessage . '</small></div>';

            $widgets['updater'] = [
                'menuindex' => '1',
                'id' => 'updater',
                'cols' => 'col-sm-12',
                'icon' => 'fa-exclamation-triangle',
                'title' => $_lang['system_update'],
                'body' => $output
            ];
            $e->output(serialize($widgets));
            return;
        }

        if (!isset($_SESSION['updatelink'])) {
            $_SESSION['updatelink'] = md5(time());
        }

        // if a GitHub commit feed
        if ($type === 'commits') {

            $branchPath = 'https://github.com/' . $version . '/' . $type . '/' . $branch;
            $url = $branchPath . '.atom';

            // create Feed
            $updateButton = '';
            $rss = fetchCacheableRss($url, null, function (SimpleXMLElement $item) {
                return $item->getName() === 'entry' ? $item : null;
            });
            if (empty($rss)) {
                $errorsMessage .= '-' . $_lang['error_failedtogetfeed'] . ':' . $url . '<br>';
                $errors += 1;
            }
            $updateButton .= '<div class="table-responsive" style="max-height:200px;"><table class="table data">';
            $updateButton .= '<thead><tr><th>' . $_lang['table_commitdate'] . '</th><th>' . $_lang['table_titleauthor'] . '</th><th></th></tr></thead><tbody>';

            $items = array_slice($rss, 0, $commitCount);
            /** @var SimpleXMLElement $item */
            foreach ($items as $item) {
                $commitid = $item->id->__toString();
                $commit = substr($commitid, strpos($commitid, "Commit/") + 7);
                $href = $item->link['href'];
                $title = $item->title->__toString();
                $pubdate = $item->updated->__toString();
                $pubdate = $modx->toDateFormat(strtotime($pubdate));
                $author = $item->author->name->__toString();
                $updateButton .= '<tr><td><b>' . $pubdate . '</b></td><td><a href="' . $href . '" target="_blank">' . $title . '</a> (' . $author . ')</td>';
                if (!updaterCanShowUpdateActions($showButton, $role, $errors)) {
                    $updateButton .= '<td></td></tr>';
                } else {
                    $updateButton .= '<td><a onclick="return confirm(\'' . $_lang['are_you_sure_update'] . '\')" target="_parent" title="sha: '
                        . $commit . '" class="btn btn-sm btn-danger" href="' . EVO_SITE_URL . $_SESSION['updatelink']
                        . '&sha=' . $commit . '">' . $_lang['updateButtonCommit_txt'] . '</a></td></tr>';
                }
            }

            $updateButton .= '</tbody></table></div>';

            $output = '<div class="card-body">GitHub commits for <strong>(<a target="_blank" href="' . $branchPath . '">' . $branchPath . '</a>)</strong><br>
            <small style="color:red;font-size:10px"> ' . $_lang['bkp_before_msg'] . '</small><br>
            <small style="color:red;font-size:10px">' . $errorsMessage . '</small>
                    </div>' . $updateButton;
            // Add widget to end as is always displayed for commits
            $widgets['updater'] = [
                'menuindex' => '1000',
                'id' => 'updater',
                'cols' => 'col-sm-12',
                'icon' => 'fa-exclamation-triangle',
                'title' => $_lang['system_update'],
                'body' => $output
            ];
            $e->output(serialize($widgets));
        } else {
            // Create directory 'assets/cache/updater'
            if (!file_exists(EVO_BASE_PATH . 'assets/cache/updater')) {
                mkdir(EVO_BASE_PATH . 'assets/cache/updater', intval($modx->config['new_folder_permissions'], 8), true);
            }

            $output = '';

            $currentVersion = $modx->getVersionData();
            $arrayVersion = explode('.', $currentVersion['version']);
            $currentMajorVersion = array_shift($arrayVersion);
            $isBranchMode = $type === 'branch';
            $git = [];

            if ($isBranchMode) {
                $git = [
                    'version' => $branch,
                    'published_at' => '',
                    'branch_ref' => true,
                ];
            } else {
                $cacheFile = EVO_BASE_PATH . 'assets/cache/updater/check_' . date("d") . '.json';

                if (!file_exists($cacheFile)) {
                    $ch = curl_init();
                    $url = 'https://api.github.com/repos/' . $version . '/' . $type;
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_HEADER, false);
                    //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_REFERER, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: updateNotify widget']);
                    $info = curl_exec($ch);
                    curl_close($ch);
                    if (substr($info, 0, 1) != '[') {
                        return;
                    }
                    $info = json_decode($info, true);

                    foreach ($info as $key => $val) {
                        $candidateVersion = '';
                        if (isset($val['name']) && $val['name'] !== '') {
                            $candidateVersion = $val['name'];
                        } elseif (isset($val['tag_name']) && $val['tag_name'] !== '') {
                            $candidateVersion = $val['tag_name'];
                        }

                        if ($candidateVersion === '') {
                            continue;
                        }

                        $arrayVersion = explode('.', $candidateVersion);
                        if ($currentMajorVersion == array_shift($arrayVersion)) {

                            $git['version'] = $candidateVersion;
                            if (isset($val['published_at']) && $val['published_at'] !== '') {
                                $git['published_at'] = $val['published_at'];
                            }

                            if (strpos($candidateVersion, 'alpha')) {
                                $git['alpha'] = $candidateVersion;
                                continue;
                            } elseif (strpos($candidateVersion, 'beta')) {
                                $git['beta'] = $candidateVersion;
                                continue;
                            } else {
                                $git['stable'] = $candidateVersion;
                                break;
                            }
                        }
                    }

                    file_put_contents($cacheFile, json_encode($git));
                } else {
                    $git = file_get_contents($cacheFile);
                    $git = json_decode($git, true);
                }

                if ($stableOnly == 'true') {
                    if (isset($git['stable'])) {
                        if (version_compare($git['version'], $git['stable'], '!=')) {
                            $git['version'] = $git['stable'];
                        }
                    }
                }
                if (isset($git['version']) && (!isset($git['published_at']) || $git['published_at'] === '')) {
                    $fallbackPublishedAt = updaterFetchReleasePublishedAt($version, $git['version']);
                    if ($fallbackPublishedAt !== '') {
                        $git['published_at'] = $fallbackPublishedAt;
                        file_put_contents($cacheFile, json_encode($git));
                    }
                }
            }

            if (isset($git['version'])) {
                $_SESSION['updateversion'] = $git['version'];
            } else {
                $git['version'] = $currentVersion['version'];
            }
            $shouldShowUpdate = $isBranchMode
                ? $git['version'] != ''
                : (version_compare($git['version'], $currentVersion['version'], '>') && $git['version'] != '');
            if ($shouldShowUpdate) {
                $currentVersionString = (string)$currentVersion['version'];
                $latestVersionRaw = (string)$git['version'];
                $hideKey = updaterBuildHideKey($latestVersionRaw, $internalKey);
                $hideUntil = (int)$modx->getConfig($hideKey);

                if ($hideUntil <= time()) {
                    $severity = updaterGetSeverity($currentVersionString, $latestVersionRaw);
                    $severityAlertClass = 'alert-info';

                    if ($isBranchMode) {
                        $severityAlertClass = 'alert-warning';
                    } elseif ($severity === 'critical') {
                        $severityAlertClass = 'alert-danger';
                    } elseif ($severity === 'warning') {
                        $severityAlertClass = 'alert-warning';
                    }

                    $releaseUrls = $isBranchMode
                        ? updaterBuildBranchUrls($version, $latestVersionRaw)
                        : updaterBuildReleaseUrls($version, $latestVersionRaw);
                    $releaseUrl = reset($releaseUrls);
                    $releaseFallbackUrl = end($releaseUrls);
                    $safeReleaseUrl = htmlspecialchars((string)$releaseUrl, ENT_QUOTES, 'UTF-8');
                    $safeFallbackUrl = htmlspecialchars((string)$releaseFallbackUrl, ENT_QUOTES, 'UTF-8');
                    $primaryReleaseLabel = $isBranchMode
                        ? updaterLang($_lang, 'updater_action_branch', 'View branch/ref')
                        : $_lang['updater_action_release'];
                    $secondaryReleaseLabel = $isBranchMode
                        ? updaterLang($_lang, 'updater_action_branch_all', 'All branches')
                        : $_lang['updater_action_release_all'];

                    $currentReleaseDate = updaterFormatReleaseDate(isset($currentVersion['release_date']) ? (string)$currentVersion['release_date'] : '');
                    $latestReleaseDate = updaterFormatReleaseDate(isset($git['published_at']) ? (string)$git['published_at'] : '');

                    $currentWithDate = $currentVersionString;
                    if ($currentReleaseDate !== '') {
                        $currentWithDate .= ' (' . $currentReleaseDate . ')';
                    }

                    $latestWithDate = $isBranchMode
                        ? updaterLang($_lang, 'updater_branch_target_label', 'Branch/ref') . ': ' . $latestVersionRaw
                        : $latestVersionRaw;
                    if (!$isBranchMode && $latestReleaseDate !== '') {
                        $latestWithDate .= ' (' . $latestReleaseDate . ')';
                    }

                    $safeCurrentWithDate = htmlspecialchars($currentWithDate, ENT_QUOTES, 'UTF-8');
                    $safeLatestWithDate = htmlspecialchars($latestWithDate, ENT_QUOTES, 'UTF-8');


                    $supportUrl = $supportLink;
                    $safeSupportUrl = htmlspecialchars($supportUrl, ENT_QUOTES, 'UTF-8');

                    $hideUntilValue = strtotime('tomorrow');
                    if ($hideUntilValue === false) {
                        $hideUntilValue = time() + 86400;
                    }
                    $csrfToken = isset($_SESSION['_token']) ? (string)$_SESSION['_token'] : '';
                    $hideAction = 'return window.updaterHideForDay('
                        . json_encode($hideKey) . ','
                        . json_encode($csrfToken) . ','
                        . (int)$hideUntilValue . ', this);';
                    $safeHideAction = htmlspecialchars($hideAction, ENT_QUOTES, 'UTF-8');
                    $hideTodayHtml = '<div style="margin-top:8px;font-size:12px;">'
                        . '<a href="#" onclick="' . $safeHideAction . '" style="color:#6c757d;text-decoration:underline;">'
                        . htmlspecialchars($_lang['updater_action_hide_today'], ENT_QUOTES, 'UTF-8')
                        . '</a>'
                        . '</div>';

                    $supportButtonHtml = '<a class="btn btn-sm btn-warning" href="' . $safeSupportUrl . '" target="_blank" rel="noopener noreferrer">'
                        . '<i class="fa fa-envelope"></i> ' . htmlspecialchars($_lang['updater_action_support'], ENT_QUOTES, 'UTF-8') . '</a>';

                    $releaseButtonsHtml = '<div style="margin-left:auto;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">'
                        . '<a class="btn btn-xs btn-primary" href="' . $safeReleaseUrl . '" target="_blank" rel="noopener noreferrer">'
                        . '<i class="fa fa-external-link"></i> ' . htmlspecialchars($primaryReleaseLabel, ENT_QUOTES, 'UTF-8')
                        . '</a>'
                        . '<a href="' . $safeFallbackUrl . '" target="_blank" rel="noopener noreferrer" style="font-size:12px;text-decoration:underline;color:#0d6efd;">'
                        . '<i class="fa fa-list"></i> ' . htmlspecialchars($secondaryReleaseLabel, ENT_QUOTES, 'UTF-8')
                        . '</a>'
                        . '</div>';

                    $cliUpdateCommand = $_lang['updater_cli_command'];
                    if ($isBranchMode) {
                        $cliUpdateCommand .= ' ' . escapeshellarg($latestVersionRaw);
                        $cliUpdateCommand .= ' --repository=' . escapeshellarg($version);
                    }
                    $cliCommand = 'cd core && ' . $cliUpdateCommand;
                    $safeCliCommand = htmlspecialchars($cliCommand, ENT_QUOTES, 'UTF-8');
                    $branchModeNoticeHtml = '';
                    if ($isBranchMode) {
                        $branchModeNoticeHtml = '<p style="margin:0 0 8px 0;color:#856404;font-weight:600;"><i class="fa fa-code-fork"></i> '
                            . htmlspecialchars(updaterLang($_lang, 'updater_branch_mode_notice', 'Development branch/ref update mode is enabled. Use it only for testing or controlled maintenance.'), ENT_QUOTES, 'UTF-8')
                            . '</p>';
                    }
                    $canShowUpdateActions = updaterCanShowUpdateActions($showButton, $role, $errors);
                    $liveUpdateScript = '';
                    $managerUpdateButtonHtml = '';
                    $primaryUpdateButtonHtml = '';
                    $cliPanelHtml = '';

                    if ($canShowUpdateActions) {
                        $primaryUpdateButtonHtml = '<a class="btn btn-sm btn-success" href="#" onclick="var panel=document.getElementById(\'updater-cli-panel\');if(panel){panel.style.display=(panel.style.display===\'block\'?\'none\':\'block\');}return false;">'
                            . '<i class="fa fa-terminal"></i> ' . htmlspecialchars(updaterLang($_lang, 'updater_cli_summary', 'Manual update (CLI)'), ENT_QUOTES, 'UTF-8')
                            . '</a>';
                        $cliPanelHtml = '<div id="updater-cli-panel" style="display:none;margin-top:12px;padding:8px;border:1px dashed #bdbdbd;border-radius:6px;">'
                            . '<div style="margin-bottom:6px;">' . htmlspecialchars(updaterLang($_lang, 'updater_cli_intro', 'If you are updating manually, run this command in terminal:'), ENT_QUOTES, 'UTF-8') . '</div>'
                            . '<code style="display:block;padding:8px;background:rgba(127,127,127,0.12);border:1px solid rgba(127,127,127,0.35);border-radius:4px;color:inherit;">' . $safeCliCommand . '</code>'
                            . '</div>';

                        $systemTaskUiState = updaterBuildSystemTaskUiState();
                        if (!empty($systemTaskUiState['can_queue_site_update'])) {
                            $systemTaskToken = updaterEnsureSystemTaskToken();
                            $schedulerStatus = isset($systemTaskUiState['scheduler']['status']) ? (string)$systemTaskUiState['scheduler']['status'] : 'unknown';
                            $workerStatus = isset($systemTaskUiState['worker']['status']) ? (string)$systemTaskUiState['worker']['status'] : 'unknown';
                            $liveUpdateScript = updaterBuildSystemTaskScript([
                                'endpoint' => 'index.php?a=2&updater_system_task=1',
                                'token' => $systemTaskToken,
                                'repository' => $version,
                                'targetRef' => $latestVersionRaw,
                                'currentVersion' => $currentVersionString,
                                'schedulerStatus' => $schedulerStatus,
                                'workerStatus' => $workerStatus,
                            ], [
                                'title' => updaterLang($_lang, 'updater_live_update_title', 'System update'),
                                'intro' => updaterLang($_lang, 'updater_live_update_intro', 'Scheduler is available. The update can be queued and monitored from the manager.'),
                                'backup' => updaterLang($_lang, 'updater_notice_backup_warning', 'Do not forget to create a backup before updating.'),
                                'backup_checkbox' => updaterLang($_lang, 'updater_live_update_backup_checkbox', 'Create database backup before updating'),
                                'current' => updaterLang($_lang, 'updater_live_update_current', 'Current version'),
                                'current_version' => updaterLang($_lang, 'updater_live_update_current_version', 'current version'),
                                'target' => $isBranchMode
                                    ? updaterLang($_lang, 'updater_live_update_branch_target', 'Target branch/ref')
                                    : updaterLang($_lang, 'updater_live_update_target', 'Target version'),
                                'repository' => updaterLang($_lang, 'updater_live_update_repository', 'Repository'),
                                'health' => updaterLang($_lang, 'updater_live_update_health', 'Scheduler / worker'),
                                'confirm' => updaterLang($_lang, 'updater_live_update_confirm', 'Start update'),
                                'cancel' => updaterLang($_lang, 'updater_live_update_cancel', 'Cancel'),
                                'queueing' => updaterLang($_lang, 'updater_live_update_queueing', 'Queueing update...'),
                                'queued' => updaterLang($_lang, 'updater_live_update_queued', 'Update task queued. Waiting for worker...'),
                                'status' => updaterLang($_lang, 'updater_live_update_status', 'Status'),
                                'step' => updaterLang($_lang, 'updater_live_update_step', 'Step'),
                                'progress' => updaterLang($_lang, 'updater_live_update_progress', 'Progress'),
                                'close' => updaterLang($_lang, 'updater_live_update_close', 'Close'),
                                'close_reload' => updaterLang($_lang, 'updater_live_update_close_reload', 'Close and reload'),
                                'completed' => updaterLang($_lang, 'updater_live_update_completed', 'Update completed. Close this window to reload the manager and verify the new version.'),
                                'response_changed' => updaterLang($_lang, 'updater_live_update_response_changed', 'The manager response changed while the update was running. Close this window to reload the manager and read the final state.'),
                                'failed' => updaterLang($_lang, 'updater_live_update_failed', 'Unable to start update.'),
                                'invalid_response' => updaterLang($_lang, 'updater_live_update_invalid_response', 'Manager returned an invalid update response.'),
                            ]);
                            $managerUpdateButtonHtml = '<button type="button" class="btn btn-sm btn-primary" onclick="return window.EvoUpdaterSystemTask && window.EvoUpdaterSystemTask.openConfirm ? window.EvoUpdaterSystemTask.openConfirm() : false;">'
                                . '<i class="fa fa-refresh"></i> ' . htmlspecialchars(updaterLang($_lang, 'updater_live_update_button', 'Update in manager'), ENT_QUOTES, 'UTF-8')
                                . '</button>';
                        }
                    }

                    $output = '<div class="card-body" data-updater-hide-root="1">'
                        . '<div class="alert ' . $severityAlertClass . '" role="alert" style="margin-bottom:12px;">'
                        . '<div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">'
                        . '<div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">'
                        . '<strong style="color:#dc3545;">' . $safeCurrentWithDate . '</strong>'
                        . '<i class="fa fa-arrow-right" aria-hidden="true"></i>'
                        . '<strong style="color:#28a745;">' . $safeLatestWithDate . '</strong>'
                        . '</div>'
                        . $releaseButtonsHtml
                        . '</div>'
                        . '</div>'

                        . '<div style="margin:0 0 12px 0;">'
                        . $branchModeNoticeHtml
                        . '<p style="margin:0 0 8px 0;"><i class="fa fa-check-circle"></i> '
                        . htmlspecialchars($_lang['updater_notice_text_1'], ENT_QUOTES, 'UTF-8') . '</p>'
                        . '<p style="margin:0 0 8px 0;"><i class="fa fa-database"></i> '
                        . htmlspecialchars($_lang['updater_notice_text_2'], ENT_QUOTES, 'UTF-8')
                        . '<span style="display:block;margin-top:4px;color:#dc3545;font-weight:600;">'
                        . htmlspecialchars($_lang['updater_notice_backup_warning'], ENT_QUOTES, 'UTF-8')
                        . '</span></p>'
                        . '<p style="margin:0;"><i class="fa fa-user"></i> '
                        . htmlspecialchars($_lang['updater_notice_text_3'], ENT_QUOTES, 'UTF-8') . '</p>'
                        . '</div>'

                        . '<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">'
                        . $managerUpdateButtonHtml
                        . $primaryUpdateButtonHtml
                        . $supportButtonHtml
                        . '<span data-updater-action-slot="update"></span>'
                        . '</div>'
                        . $cliPanelHtml
                        . ($errorsMessage !== '' ? '<small style="color:red;font-size:10px;display:block;">' . $errorsMessage . '</small>' : '')
                        . $hideTodayHtml
                        . '<script>(function(){if(window.updaterHideForDay){return;}window.updaterHideForDay=function(hideKey,token,untilTs,trigger){var xhr=new XMLHttpRequest();xhr.open("POST","index.php?a=118",true);xhr.setRequestHeader("Content-Type","application/x-www-form-urlencoded; charset=UTF-8");xhr.onload=function(){if(xhr.readyState!==4){return;}var root=null;if(trigger&&trigger.closest){root=trigger.closest("[data-updater-hide-root]");}if(!root){root=document.getElementById("updater");}if(root){root.style.display="none";}};var payload="action=setsetting&key="+encodeURIComponent(hideKey)+"&value="+encodeURIComponent(String(untilTs));if(token){payload+="&_token="+encodeURIComponent(token);}xhr.send(payload);return false;};})();</script>'
                        . $liveUpdateScript
                        . '</div>';

                    $widgets['updater'] = [
                        'menuindex' => '1',
                        'id' => 'updater',
                        'cols' => 'col-sm-12',
                        'icon' => 'fa-exclamation-triangle',
                        'title' => $_lang['updater_widget_title'],
                        'body' => $output
                    ];

                    $e->output(serialize($widgets));
                }
            }
        }
    }
    if (isset($_GET['q']) && $_GET['q'] === $_SESSION['updatelink']) {
        if (empty($_SESSION['mgrInternalKey']) || empty($_SESSION['updatelink'])) {
            return;
        }
        if ((int)$role !== 1) {
            return;
        }
        unset($_SESSION['updatelink']);
                $currentVersion = $modx->getVersionData();
                $commit = isset($_GET['sha']) ? $_GET['sha'] : '';
                if ($_SESSION['updateversion'] != $currentVersion['version'] || (isset($commit) && $type == 'commits')) {
                    file_put_contents(EVO_BASE_PATH . 'update.php', '<?php
function downloadFile($url, $path)
{
    $newfname = $path;
    $file = null;
    $newf = null;
    try {
        if (ini_get("allow_url_fopen")) {
            $file = fopen($url, "rb");
            if ($file) {
                $newf = fopen($newfname, "wb");
                if ($newf) {
                    while (!feof($file)) {
                        fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
                    }
                }
            }
        } elseif (function_exists("curl_version")) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            $content = curl_exec($ch);
            curl_close($ch);
            file_put_contents($newfname, $content);
        }
    } catch (Exception $e) {
        $this->errors[] = array("ERROR:Download", $e->getMessage());
        return false;
    }
    if ($file) {
        fclose($file);
    }
    if ($newf) {
        fclose($newf);
    }
    return true;
}

function removeFolder($path)
{
    $dir = realpath($path);
    if (!is_dir($dir)) {
        return;
    }
    $it = new RecursiveDirectoryIterator($dir);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        set_time_limit(30);
        if ($file->getFilename() === "." || $file->getFilename() === "..") {
            continue;
        }
        if ($file->isDir()) {
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }
    rmdir($dir);
}

function copyFolder($src, $dest)
{
    $path = realpath($src);
    $dest = realpath($dest);
    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($objects as $name => $object) {
        set_time_limit(30);
        $startsAt = substr(dirname($name), strlen($path));
        mmkDir($dest . $startsAt);
        if ($object->isDir()) {
            mmkDir($dest . substr($name, strlen($path)));
        }
        if (is_writable($dest . $startsAt) and $object->isFile()) {
            copy((string)$name, $dest . $startsAt . DIRECTORY_SEPARATOR . basename($name));
        }
    }
}

function mmkDir($folder, $perm = 0777)
{
    if (!is_dir($folder)) {
        mkdir($folder, $perm);
    }
}

$version = "' . $version . '";

downloadFile("https://github.com/" . $version . "/archive/" . $_GET["version"] . ".zip", "evo.zip");
$zip = new ZipArchive;
$res = $zip->open(__DIR__ . "/evo.zip");
$zip->extractTo(__DIR__ . "/temp");
$zip->close();

if ($handle = opendir(__DIR__ . "/temp")) {
    while (false !== ($name = readdir($handle))) {
        if ($name != "." && $name != "..") {
            $dir = $name;
        }
    }
    closedir($handle);
}
removeFolder(__DIR__ . "/temp/" . $dir . "/install/assets/chunks");
removeFolder(__DIR__ . "/temp/" . $dir . "/install/assets/tvs");
removeFolder(__DIR__ . "/temp/" . $dir . "/install/assets/templates");
unlink(__DIR__ . "/temp/" . $dir . "/ng.inx");
unlink(__DIR__ . "/temp/" . $dir . "/ht.access");
unlink(__DIR__ . "/temp/" . $dir . "/README.md");
unlink(__DIR__ . "/temp/" . $dir . "/sample-robots.txt");
unlink(__DIR__ . "/temp/" . $dir . "/composer.json");

if (is_file(__DIR__ . "/assets/cache/siteManager.php")) {
    unlink(__DIR__ . "/temp/" . $dir . "/assets/cache/siteManager.php");
    include_once(__DIR__ . "/assets/cache/siteManager.php");
    if (!defined("MGR_DIR")) {
        define("MGR_DIR", "manager");
    }
    if (MGR_DIR != "manager") {
        mmkDir(__DIR__ . "/temp/" . $dir . "/" . MGR_DIR);
        copyFolder(__DIR__ . "/temp/" . $dir . "/manager", __DIR__ . "/temp/" . $dir . "/" . MGR_DIR);
        removeFolder(__DIR__ . "/temp/" . $dir . "/manager");
    }
}
copyFolder(__DIR__ . "/temp/" . $dir, __DIR__ . "/");
removeFolder(__DIR__ . "/temp");
unlink(__DIR__ . "/evo.zip");
$ch = curl_init();
$url = "https://api.github.com/repos/' . $version . '/releases";
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_REFERER, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("User-Agent: updateNotify widget"));
$releases = curl_exec($ch);
curl_close($ch);
if (substr($releases, 0, 1) == "[") {
    $releases = json_decode($releases, true);
    foreach ($releases as $release) {
        if ($_GET["version"] == $release["tag_name"]) {
            $factoryDate = date("M j, Y", strtotime($release["published_at"]));
            $factoryVersion = \'<?php return [\'."\n";
            $factoryVersion .= "\t".\'"version" => "\'.$release["tag_name"].\'", // Current version number\'."\n";
            $factoryVersion .= "\t".\'"release_date" => "\'.$factoryDate.\'", // Date of release\'."\n";
            $factoryVersion .= "\t".\'"branch" => "Evolution CMS", // Codebase name\'."\n";
            $factoryVersion .= "\t".\'"full_appname" => "\'.$release["name"].\' (\'.$factoryDate.\')", // Date of release\'."\n";
            $factoryVersion .= \'];\';
            file_put_contents(__DIR__ . "/core/factory/version.php", $factoryVersion);
            break;
        }
    }
}
unlink(__DIR__ . "/update.php");
header("Location: ' . constant('EVO_SITE_URL') . 'install/index.php?action=mode");');
                    if ($result === false) {
                        echo 'Update failed: cannot write to ' . EVO_BASE_PATH . 'update.php';
                    } else {
                        if ($type == 'commits') {
                            $versionGet = $commit;
                            $versionText = $version . '/' . $type . '/' . $branch . '/' . $commit;
                        } else {
                            $versionGet = $_SESSION['updateversion'];
                            $versionText = $_SESSION['updateversion'];
                        }
                        echo '<html><head></head><body><h2>Evolution Updater</h2>
                          <p>Downloading version: <strong>' . $versionText . '</strong>.</p>
                          <p>You will be redirected to the update wizard shortly.</p>
                          <p>Please wait...</p>
                          <script>window.location = "' . EVO_SITE_URL . 'update.php?version=' . $versionGet . '";</script>
                          </body></html>';
                    }
                }
                die();
    }
}
