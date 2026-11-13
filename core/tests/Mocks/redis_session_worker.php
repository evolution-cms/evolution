<?php

/*
| One request on the legacy PHP session in Redis (EVO_SESSION off, SESSION_STORAGE=redis), without an
| execution limit, as a web request run without one would be.
|
|   php redis_session_worker.php <host:port> <session id> <work dir> [hold seconds]
|
| With a hold it prints "locked" once the session is open, writes holder=first and keeps the lock that long.
| It ends by printing how long opening the session took, the handler it ended on and the session data.
*/

[, $server, $id, $dir] = $argv;
$hold = (float)($argv[4] ?? 0);
[$host, $port] = explode(':', $server) + [1 => '6379'];

// startCMSSession() returns at once on CLI
function is_cli()
{
    return false;
}

$_SERVER['SERVER_PORT'] = 80;
$_SERVER['REQUEST_TIME'] = time();
define('SESSION_STORAGE', 'redis');
define('EVO_SESSION', false);
define('HTTPS_PORT', 443);
define('SESSION_COOKIE_NAME', 'evotest');
define('EVO_BASE_URL', '/');
putenv('REDIS_HOST=' . $host);
putenv('REDIS_PORT=' . $port);
putenv('REDIS_SESSION_DATABASE=15');
putenv('REDIS_TIMEOUT=2');

ini_set('max_execution_time', '0');
ini_set('session.save_handler', 'files');
ini_set('session.save_path', $dir);
ini_set('log_errors', '1');
ini_set('error_log', $dir . DIRECTORY_SEPARATOR . 'error.log');

require dirname(__DIR__, 2) . '/vendor/autoload.php';

session_id($id);
$start = microtime(true);
startCMSSession();
$opened = microtime(true) - $start;

if ($hold > 0) {
    $_SESSION['holder'] = 'first';
    echo "locked\n";
    flush();
    usleep((int)($hold * 1000000));
}

echo json_encode([
    'opened' => round($opened, 2),
    'handler' => ini_get('session.save_handler'),
    'status' => session_status(),
    'session' => $_SESSION,
    'lock' => [
        'expire' => ini_get('redis.session.lock_expire'),
        'retries' => ini_get('redis.session.lock_retries'),
        'wait' => ini_get('redis.session.lock_wait_time'),
    ],
]), "\n";
