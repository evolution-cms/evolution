<?php

/*
 * A console command has no URL of its own: SCRIPT_NAME is the path it was started by. Taken for a URL it
 * made http://localhostcore/ of "php core/artisan" and http://localhost./ of "php artisan", and with a port
 * in EVO_SITE_HOSTNAMES a site URL Symfony refuses ("Invalid URI."), so that artisan did not start.
 */

/**
 * @param array<string, string> $constants defined before define.inc.php, as custom/define.php does
 * @param array<string, string> $server set by the script itself, as one that stands in for a web request does
 * @return array{base: string, site: string, manager: string}
 */
function consoleUrls(string $cwd, string $script, array $constants = [], array $server = []): array
{
    $repo = dirname(__DIR__, 3);
    $site = str_replace('\\', '/', realpath(sys_get_temp_dir())) . '/evo-console-url-' . bin2hex(random_bytes(6));
    mkdir($site . '/core/includes', 0777, true);
    mkdir($site . '/manager');
    copy($repo . '/core/includes/define.inc.php', $site . '/core/includes/define.inc.php');

    $code = '<?php' . "\n";
    foreach ($server as $key => $value) {
        $code .= '$_SERVER[' . var_export($key, true) . '] = ' . var_export($value, true) . ';' . "\n";
    }
    foreach ($constants + ['IN_INSTALL_MODE' => false, 'IN_MANAGER_MODE' => true, 'EVO_API_MODE' => true] as $name => $value) {
        $code .= 'define(' . var_export($name, true) . ', ' . var_export($value, true) . ');' . "\n";
    }
    foreach (['vendor/autoload.php', 'functions/helper.php', 'functions/preload.php'] as $file) {
        $code .= 'require ' . var_export($repo . '/core/' . $file, true) . ';' . "\n";
    }
    $code .= 'require ' . var_export($site . '/core/includes/define.inc.php', true) . ';' . "\n"
        . 'echo json_encode(["base" => EVO_BASE_URL, "site" => EVO_SITE_URL, "manager" => EVO_MANAGER_URL]);';
    foreach (['core/command.php', 'manager/command.php'] as $file) {
        file_put_contents($site . '/' . $file, $code);
    }

    $before = getcwd();
    try {
        chdir($site . $cwd);
        $output = evoRunPhp(str_replace('{site}', $site, $script), [], $status);
        expect($status)->toBe(0, $output);

        return json_decode(trim($output), true, 512, JSON_THROW_ON_ERROR);
    } finally {
        chdir($before);
        (new \Illuminate\Filesystem\Filesystem())->deleteDirectory($site);
    }
}

dataset('ways to start a command', [
    'from the site root' => ['', 'core/command.php'],
    'from the site root, dotted' => ['', './core/command.php'],
    'from core' => ['/core', 'command.php'],
    'from another folder' => ['/manager', '../core/command.php'],
    'by its full path' => ['', '{site}/core/command.php'],
    'under the manager' => ['', '{site}/manager/command.php'],
]);

test('the path a command is started by is no part of the site URL', function (string $cwd, string $script) {
    expect(consoleUrls($cwd, $script))->toBe([
        'base' => '/',
        'site' => 'http://localhost/',
        'manager' => 'http://localhost/manager/',
    ]);
})->with('ways to start a command');

test('a host list with a port gives the console a valid site URL', function (string $cwd, string $script) {
    $urls = consoleUrls($cwd, $script, ['EVO_SITE_HOSTNAMES' => 'site.test:8348']);

    expect($urls['site'])->toBe('http://site.test:8348/')
        // what artisan builds its request from
        ->and(\Illuminate\Http\Request::create($urls['site'])->getPort())->toBe(8348);
})->with('ways to start a command');

test('a site in a subfolder says so with its site URL or its base URL', function () {
    expect(consoleUrls('', 'core/command.php', ['EVO_SITE_URL' => 'https://site.test:8443/shop/']))->toBe([
        'base' => '/shop/',
        'site' => 'https://site.test:8443/shop/',
        'manager' => 'https://site.test:8443/shop/manager/',
    ])->and(consoleUrls('', 'core/command.php', ['EVO_BASE_URL' => '/shop/', 'EVO_SITE_HOSTNAMES' => 'site.test']))->toBe([
        'base' => '/shop/',
        'site' => 'http://site.test/shop/',
        'manager' => 'http://site.test/shop/manager/',
    ]);
});

test('a script that stands in for a web request keeps the URL it sets', function () {
    $urls = consoleUrls('', 'core/command.php', ['EVO_SITE_HOSTNAMES' => 'site.test'], [
        'SCRIPT_NAME' => '/shop/index.php',
        'PHP_SELF' => '/shop/index.php',
    ]);

    expect($urls['base'])->toBe('/shop/')
        ->and($urls['site'])->toBe('http://site.test/shop/');
});
