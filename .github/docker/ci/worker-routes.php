<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/__worker/state', static function () {
    $_SESSION['worker_counter'] = ($_SESSION['worker_counter'] ?? 0) + 1;
    return response()->json([
        'counter' => $_SESSION['worker_counter'],
        'user' => app('AuthServices')->id(),
        'placeholder' => evo()->getPlaceholder('worker_marker'),
        'scripts' => evo()->jscripts,
        'core_config' => evo()->getConfig('worker_marker'),
        'config' => config('worker_marker'),
        'global' => $GLOBALS['worker_marker'] ?? null,
        'environment' => $_ENV['worker_marker'] ?? null,
        'locale' => app('translator')->getLocale(),
        'precision' => ini_get('precision'),
        'redirected' => $_SESSION['worker_redirected'] ?? false,
        'transaction' => DB::table('system_settings')->where('setting_name', 'worker_transaction')->value('setting_value'),
        'shutdowns' => (int) @file_get_contents('/tmp/evo-worker-shutdowns'),
        'native_cleanup' => is_file('/tmp/evo-worker-native-cleanup'),
        'memory' => memory_get_usage(true),
        'setting' => evo()->getConfig('worker_setting'),
    ]);
});
Route::get('/__worker/mutate', static function () {
    evo()->setPlaceholder('worker_marker', 'dirty');
    evo()->jscripts[] = 'dirty';
    evo()->setConfig('worker_marker', 'dirty');
    app('config')->set('worker_marker', 'dirty');
    app('translator')->setLocale('fr');
    $GLOBALS['worker_marker'] = 'dirty';
    $_ENV['worker_marker'] = 'dirty';
    ini_set('precision', '9');
    date_default_timezone_set('Pacific/Honolulu');
    return 'mutated';
});
Route::get('/__worker/login', static function () {
    $_SESSION['webValidated'] = true;
    $_SESSION['webInternalKey'] = 1;
    return 'logged in';
});
Route::get('/__worker/redirect', static function () {
    $_SESSION['worker_redirected'] = true;
    evo()->sendRedirect('/__worker/state');
});
Route::get('/__worker/forward', static function () {
    $id = EvolutionCMS\Models\SiteContent::where('alias', 'worker-demo')->value('id');
    evo()->sendForward($id);
});
Route::get('/__worker/shutdown', static function () {
    evo_register_shutdown(static function () {
        $count = (int) @file_get_contents('/tmp/evo-worker-shutdowns');
        file_put_contents('/tmp/evo-worker-shutdowns', (string) ($count + 1));
    });
    evo_exit('shutdown queued');
});
Route::get('/__worker/transaction', static function () {
    DB::beginTransaction();
    DB::table('system_settings')->where('setting_name', 'worker_transaction')->update(['setting_value' => 'uncommitted']);
    return 'transaction left open';
});
Route::get('/__worker/update-setting', static function () {
    $value = evo()->getConfig('worker_setting') === 'updated' ? 'original' : 'updated';
    DB::table('system_settings')->where('setting_name', 'worker_setting')->update(['setting_value' => $value]);
    evo()->clearCache('full');
    return $value;
});
Route::post('/__worker/post', static fn () => response()->json(['value' => request()->input('value')]));
Route::get('/__worker/failure', static function () {
    throw new RuntimeException('worker-test-private-detail');
});
Route::get('/__worker/native-exit', static function () {
    $_SESSION['worker_redirected'] = true;
    evo_register_shutdown(static fn () => file_put_contents('/tmp/evo-worker-native-cleanup', 'done'));
    exit('legacy response');
});
