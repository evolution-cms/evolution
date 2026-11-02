<?php

// Changes a setting from a CLI process, as the manager of another server, a deploy or cron would.
if (getenv('EVO_WORKER_MODE') !== '1' || getenv('EVO_WORKER_DEBUG') !== '1') {
    throw new RuntimeException('This fixture is only for a disposable worker test container.');
}
define('EVO_API_MODE', true);
require ($argv[1] ?? '/var/www/html') . '/index.php';
evo();
Illuminate\Support\Facades\DB::table('system_settings')
    ->where('setting_name', 'worker_setting')->update(['setting_value' => $argv[2]]);
evo()->clearCache('full');
