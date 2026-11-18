<?php

test('updater manager modal reloads after successful live update', function () {
    $root = dirname(__DIR__, 3);
    $source = (string) file_get_contents($root . '/assets/plugins/updater/plugin.updater.php');
    $enLang = (string) file_get_contents($root . '/assets/plugins/updater/lang/en.php');
    $ukLang = (string) file_get_contents($root . '/assets/plugins/updater/lang/uk.php');

    expect($source)
        ->toContain('reloadOnClose')
        // Reload the whole manager so the top menu shows the new version, and
        // rebuild session permissions before that reload.
        ->toContain('target = window.top;')
        ->toContain('target.location.reload();')
        ->toContain("request('refresh_session')")
        ->toContain("case 'refresh_session':")
        ->toContain("['create', 'cancel', 'refresh_session']")
        ->toContain('updater_live_update_close_reload')
        ->toContain('updater_live_update_response_changed')
        ->toContain('normalized.substring(firstJsonChar, lastJsonChar + 1)')
        ->toContain('function renderRecoverablePollError(error, retrying)')
        ->toContain('updaterBuildBackupWarningHtml($_lang)')
        ->toContain('$primaryUpdateButtonHtml' . "\n" . '                        . $managerUpdateButtonHtml')
        ->not->toContain('activeTask.force_close_button');

    expect($enLang)
        ->toContain('updater_live_update_close_reload')
        ->toContain('updater_live_update_completed')
        ->toContain('updater_live_update_response_changed')
        ->toContain("updater_backup_action_label'] = 'create a backup'")
        ->toContain("updater_live_update_button'] = 'Manual update (WEB)'");

    expect($ukLang)
        ->toContain('updater_live_update_close_reload')
        ->toContain('updater_live_update_completed')
        ->toContain('updater_live_update_response_changed')
        ->toContain("updater_backup_action_label'] = 'зробити резервну копію'")
        ->toContain("updater_live_update_button'] = 'Самостійне оновлення (WEB)'");
});

test('the live update window outlasts the manager it replaces', function () {
    $node = (new Symfony\Component\Process\ExecutableFinder())->find('node');
    if ($node === null) {
        $this->markTestSkipped('Node.js is not installed.');
    }

    $test = dirname(__DIR__, 3) . '/assets/plugins/updater/tests/live-update.test.js';
    $output = evoRunCommand(escapeshellarg($node) . ' --test ' . escapeshellarg($test) . ' 2>&1', $exitCode);

    expect($exitCode)->toBe(0, $output);
});

test('a dashboard rendered during the update hands the running task to the window instead of a new update', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/assets/plugins/updater/plugin.updater.php');
    $state = substr($source, strpos($source, 'function updaterBuildSystemTaskUiState()'), 2600);

    expect($state)->toContain("->getActiveTaskPayload('site_update', \$requesterSnapshot, \$isSuperAdmin);")
        // the scheduler can look stale while its worker runs the update, so the task alone opens the window
        ->and($source)->toContain("if (!empty(\$systemTaskUiState['can_queue_site_update']) || \$activeUpdateTask !== null) {")
        ->and($source)->toContain("'activeTask' => \$activeUpdateTask,")
        ->and($source)->toContain("updaterLang(\$_lang, 'updater_live_update_show_progress', 'Show update progress')");
});
