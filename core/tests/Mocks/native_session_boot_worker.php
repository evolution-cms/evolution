<?php
// Loads TracyServiceProvider.php, whose top level starts the native session (EVO_SESSION off), in a fresh process.
// argv: <session save dir> <console:0|1>
[, $dir, $console] = $argv;
// This process is a CLI one either way; the helper (guarded by function_exists) answers what the case asks.
function is_cli(): bool
{
    return $GLOBALS['console'] === '1';
}
define('EVO_SESSION', false);
ini_set('session.save_handler', 'files');
ini_set('session.save_path', $dir);
ini_set('session.use_cookies', '0');
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require dirname(__DIR__, 2) . '/src/Providers/TracyServiceProvider.php';

$status = session_status();
$session = $_SESSION ?? null;
session_write_close();

echo json_encode([
    'active' => $status === PHP_SESSION_ACTIVE,
    'session' => is_array($session) ? 'array' : gettype($session),
    'files' => array_values(array_diff(scandir($dir), ['.', '..'])),
]);
