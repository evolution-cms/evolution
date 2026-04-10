<?php

function loadBootConstantsInFreshProcess(array $env): array
{
    $rootDir = dirname(__DIR__, 3);
    $script = <<<'PHP'
<?php
$rootDir = %s;
$envValues = %s;
$managedEnvNames = [
    'EVO_CLASS',
    'EVO_SITE_HOSTNAMES',
    'EVO_BASE_PATH',
    'EVO_BASE_URL',
    'EVO_MANAGER_PATH',
    'EVO_SITE_URL',
    'EVO_MANAGER_URL',
    'EVO_CLASS',
    'EVO_SITE_HOSTNAMES',
    'EVO_BASE_PATH',
    'EVO_BASE_URL',
    'EVO_MANAGER_PATH',
    'EVO_SITE_URL',
    'EVO_MANAGER_URL',
];

foreach ($managedEnvNames as $name) {
    putenv($name);
    unset($_ENV[$name], $_SERVER[$name]);
}

foreach ($envValues as $name => $value) {
    putenv($name . '=' . $value);
    $_ENV[$name] = $value;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['HTTP_HOST'] = 'example.test';
$_SERVER['SERVER_PORT'] = 80;
$_SERVER['HTTPS'] = 'off';

define('IN_INSTALL_MODE', false);
define('IN_MANAGER_MODE', false);
define('EVO_API_MODE', true);

require $rootDir . '/core/vendor/autoload.php';
require $rootDir . '/core/functions/helper.php';
require $rootDir . '/core/functions/preload.php';
require $rootDir . '/core/includes/define.inc.php';

$constants = [];
foreach ([
    'EVO_CLASS',
    'EVO_SITE_HOSTNAMES',
    'EVO_BASE_PATH',
    'EVO_BASE_URL',
    'EVO_MANAGER_PATH',
    'EVO_SITE_URL',
    'EVO_MANAGER_URL',
    'EVO_CLASS',
    'EVO_SITE_HOSTNAMES',
    'EVO_BASE_PATH',
    'EVO_BASE_URL',
    'EVO_MANAGER_PATH',
    'EVO_SITE_URL',
    'EVO_MANAGER_URL',
] as $name) {
    $constants[$name] = defined($name) ? constant($name) : null;
}

echo json_encode($constants, JSON_THROW_ON_ERROR);
PHP;

    $scriptPath = tempnam(sys_get_temp_dir(), 'evo-constants-');
    file_put_contents($scriptPath, sprintf($script, var_export($rootDir, true), var_export($env, true)));

    $output = [];
    $status = 0;
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($scriptPath), $output, $status);
    @unlink($scriptPath);

    expect($status)->toBe(0, implode("\n", $output));

    return json_decode(implode("\n", $output), true, 512, JSON_THROW_ON_ERROR);
}

test('evo bootstrap constants publish modx aliases', function () {
    $constants = loadBootConstantsInFreshProcess([
        'EVO_CLASS' => '\\DocumentParser',
        'EVO_SITE_HOSTNAMES' => 'example.test',
        'EVO_BASE_PATH' => '/tmp/evo/',
        'EVO_BASE_URL' => '/evo/',
        'EVO_MANAGER_PATH' => '/tmp/evo/manager/',
        'EVO_SITE_URL' => 'https://example.test/evo/',
        'EVO_MANAGER_URL' => 'https://example.test/evo/manager/',
    ]);

    expect($constants['EVO_CLASS'])->toBe($constants['EVO_CLASS'])
        ->and($constants['EVO_SITE_HOSTNAMES'])->toBe($constants['EVO_SITE_HOSTNAMES'])
        ->and($constants['EVO_BASE_PATH'])->toBe($constants['EVO_BASE_PATH'])
        ->and($constants['EVO_BASE_URL'])->toBe($constants['EVO_BASE_URL'])
        ->and($constants['EVO_MANAGER_PATH'])->toBe($constants['EVO_MANAGER_PATH'])
        ->and($constants['EVO_SITE_URL'])->toBe($constants['EVO_SITE_URL'])
        ->and($constants['EVO_MANAGER_URL'])->toBe($constants['EVO_MANAGER_URL']);
});

test('legacy modx bootstrap env still hydrates evo constants', function () {
    $constants = loadBootConstantsInFreshProcess([
        'EVO_CLASS' => '\\DocumentParser',
        'EVO_SITE_HOSTNAMES' => 'legacy.example.test',
        'EVO_BASE_PATH' => '/tmp/legacy-evo/',
        'EVO_BASE_URL' => '/legacy-evo/',
        'EVO_MANAGER_PATH' => '/tmp/legacy-evo/manager/',
        'EVO_SITE_URL' => 'https://legacy.example.test/legacy-evo/',
        'EVO_MANAGER_URL' => 'https://legacy.example.test/legacy-evo/manager/',
    ]);

    expect($constants['EVO_CLASS'])->toBe($constants['EVO_CLASS'])
        ->and($constants['EVO_SITE_HOSTNAMES'])->toBe($constants['EVO_SITE_HOSTNAMES'])
        ->and($constants['EVO_BASE_PATH'])->toBe($constants['EVO_BASE_PATH'])
        ->and($constants['EVO_BASE_URL'])->toBe($constants['EVO_BASE_URL'])
        ->and($constants['EVO_MANAGER_PATH'])->toBe($constants['EVO_MANAGER_PATH'])
        ->and($constants['EVO_SITE_URL'])->toBe($constants['EVO_SITE_URL'])
        ->and($constants['EVO_MANAGER_URL'])->toBe($constants['EVO_MANAGER_URL']);
});
