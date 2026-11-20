<?php

/*
 * EVO_BASE_PATH is the folder above core/. It used to be cut at the first MGR_DIR found in that
 * path, a leftover from when define.inc.php lived under manager/: a manager renamed to "admin"
 * in /home/admin/evolution gave /home/, and one named "evo" in /home/user/evolution /home/user/.
 */

function basePathInFreshProcess(string $site, string $managerDir, string $scriptName): array
{
    $repo = dirname(__DIR__, 3);
    mkdir($site . '/core/includes', 0777, true);
    copy($repo . '/core/includes/define.inc.php', $site . '/core/includes/define.inc.php');

    $code = '<?php' . "\n"
        . 'function is_cli() { return false; }' . "\n"
        . '$_SERVER = ' . var_export([
            'SCRIPT_NAME' => $scriptName,
            'PHP_SELF' => $scriptName,
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.test',
            'SERVER_PORT' => '80',
        ], true) . ';' . "\n"
        . 'define("IN_INSTALL_MODE", false);' . "\n"
        . 'define("IN_MANAGER_MODE", false);' . "\n"
        . 'define("EVO_API_MODE", true);' . "\n"
        . 'define("MGR_DIR", ' . var_export($managerDir, true) . ');' . "\n"
        . 'require ' . var_export($repo . '/core/vendor/autoload.php', true) . ';' . "\n"
        . 'require ' . var_export($repo . '/core/functions/helper.php', true) . ';' . "\n"
        . 'require ' . var_export($repo . '/core/functions/preload.php', true) . ';' . "\n"
        . 'require ' . var_export($site . '/core/includes/define.inc.php', true) . ';' . "\n"
        . 'echo json_encode([EVO_BASE_PATH, EVO_MANAGER_PATH, EVO_BASE_URL, EVO_MANAGER_URL]);';

    $script = tempnam(sys_get_temp_dir(), 'evo-base-path-') . '.php';
    file_put_contents($script, $code);
    $output = evoRunPhp($script, [], $status);
    @unlink($script);

    expect($status)->toBe(0, $output);

    return json_decode(trim($output), true);
}

test('the base path is the folder above core, whatever the manager is called', function (string $parent, string $managerDir) {
    $root = str_replace('\\', '/', realpath(sys_get_temp_dir())) . '/evo-base-' . bin2hex(random_bytes(6));
    $site = $root . '/' . $parent . '/evolution';

    try {
        [$base, $manager, $baseUrl, $managerUrl] = basePathInFreshProcess($site, $managerDir, '/' . $managerDir . '/index.php');

        expect($base)->toBe($site . '/')
            ->and($manager)->toBe($site . '/' . $managerDir . '/')
            // The URL side is cut at MGR_DIR on purpose: a request into the manager names it.
            ->and($baseUrl)->toBe('/')
            ->and($managerUrl)->toBe('http://example.test/' . $managerDir . '/');
    } finally {
        (new \Illuminate\Filesystem\Filesystem())->deleteDirectory($root);
    }
})->with([
    'manager named like the account' => ['admin', 'admin'],
    'manager named like part of the folder' => ['user', 'evo'],
    'the default manager in a manager-named home' => ['manager', 'manager'],
    'an ordinary path' => ['user', 'manager'],
]);
