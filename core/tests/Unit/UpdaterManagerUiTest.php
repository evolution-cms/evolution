<?php

test('updater manager modal reloads after successful live update', function () {
    $root = dirname(__DIR__, 3);
    $source = (string) file_get_contents($root . '/assets/plugins/updater/plugin.updater.php');
    $enLang = (string) file_get_contents($root . '/assets/plugins/updater/lang/en.php');
    $ukLang = (string) file_get_contents($root . '/assets/plugins/updater/lang/uk.php');

    expect($source)
        ->toContain('reloadOnClose')
        ->toContain('window.location.reload();')
        ->toContain('updater_live_update_close_reload')
        ->toContain('updater_live_update_response_changed')
        ->toContain('normalized.substring(firstJsonChar, lastJsonChar + 1)')
        ->toContain('function renderRecoverablePollError(error)')
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
