<?php

/**
 * The dashboard's file integrity check over a throwaway site, in a clean process (ManagerApi
 * reads EVO_BASE_PATH): SystemFilesChecksum::adopt(), then the real ManagerApi comparison.
 *
 * argv: site root (with a trailing slash), base64 JSON {list, stored, released?}
 * Prints JSON: {adopted: new checksum or null, current: getSystemChecksum(),
 *               before: files reported with the stored checksum, after: with the adopted one}
 */

[, $site, $payload] = $argv;
$options = json_decode(base64_decode($payload), true);

define('EVO_BASE_PATH', $site);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$api = (new ReflectionClass(EvolutionCMS\Legacy\ManagerApi::class))->newInstanceWithoutConstructor();
$adopted = EvolutionCMS\Support\SystemFilesChecksum::adopt(
    $options['list'],
    $options['stored'],
    EVO_BASE_PATH,
    $options['released'] ?? null
);

echo json_encode([
    'adopted' => $adopted,
    'current' => $api->getSystemChecksum($options['list']),
    'before' => $api->getModifiedSystemFilesList($options['list'], $options['stored']),
    'after' => $api->getModifiedSystemFilesList($options['list'], $adopted ?? $options['stored']),
]);
