<?php
// Reads the legacy session payload for an id from a save path in a fresh process
// (session_save_path() refuses changes once output was sent). argv: <save path> <hex id>
[, $savePath, $hexId] = $argv;
require_once dirname(__DIR__, 2) . '/functions/session_proxy.php';

session_save_path($savePath);
$method = new ReflectionMethod(EvoSessionProxy::class, 'readLegacySessionPayload');
echo json_encode($method->invoke(null, hex2bin($hexId)));
