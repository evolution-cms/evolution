<?php

/*
| "Remove the install folder" on the last page of the installer, driven the way a browser drives
| it: a real site, the real forms, a real server. InstallerWebPathTest runs the processor against a
| lock it writes itself, so it cannot tell whether the installer ever stored the token.
*/

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\Process\Process;

const INSTALLER_FLOW_ADMIN = 'admin';
const INSTALLER_FLOW_PASSWORD = 'Passw0rd123';

/** Paths of the repository a site does not need, or that would make it look installed already. */
function installerFlowSkipped(string $path): bool
{
    return preg_match(
        '~^(\.git|\.github|tmp|node_modules|core/tests|core/storage/(logs|cache|sessions)|core/\.install'
        . '|core/config/database/connections/default\.php|core/database/[^/]+\.sqlite|install\.session\.php)(/|$)~',
        $path
    ) === 1;
}

function installerFlowCopy(string $from, string $to, string $relative = ''): void
{
    is_dir($to) || mkdir($to, 0777, true);
    foreach (scandir($from) as $name) {
        $path = ltrim($relative . '/' . $name, '/');
        if ($name === '.' || $name === '..' || installerFlowSkipped($path)) {
            continue;
        }
        if (is_dir("$from/$name") && !is_link("$from/$name")) {
            installerFlowCopy("$from/$name", "$to/$name", $path);
        } else {
            copy("$from/$name", "$to/$name");
        }
    }
}

function installerFlowDelete(string $path): void
{
    if (is_link($path) || is_file($path)) {
        unlink($path);
    } elseif (is_dir($path)) {
        foreach (array_diff(scandir($path), ['.', '..']) as $name) {
            installerFlowDelete("$path/$name");
        }
        rmdir($path);
    }
}

/**
 * The site of this file: copied and served once, because a tree with its vendor takes seconds.
 *
 * @return array{root: string, url: string, server: Process}
 */
function installerFlowSite(bool $stop = false): ?array
{
    static $site = null;
    if ($stop) {
        if ($site !== null) {
            $site['server']->stop(1);
            installerFlowDelete($site['root']);
        }
        return $site = null;
    }
    if ($site !== null) {
        return $site;
    }

    $root = str_replace('\\', '/', sys_get_temp_dir()) . '/evo-installer-flow-' . bin2hex(random_bytes(8));
    installerFlowCopy(dirname(__DIR__, 3), $root);
    foreach (['logs', 'cache', 'sessions'] as $folder) {
        mkdir("$root/core/storage/$folder", 0777, true);
    }

    $socket = stream_socket_server('tcp://127.0.0.1:0');
    $address = stream_socket_get_name($socket, false);
    fclose($socket);

    // The settings of the test run must not reach the site: DB_* would win over its own connection.
    $environment = array_map(static fn () => false, array_filter(
        getenv(),
        static fn (string $name) => preg_match('/^(DB_|EVO_|APP_)/', $name) === 1,
        ARRAY_FILTER_USE_KEY
    ));
    $server = new Process([PHP_BINARY, '-d', 'opcache.enable=0', '-S', $address, '-t', $root], $root, $environment, null, null);
    $server->start();

    $url = "http://$address";
    for ($attempt = 0; $attempt < 100; $attempt++) {
        $connection = @fsockopen('127.0.0.1', (int) substr(strrchr($address, ':'), 1), $code, $message, 0.1);
        if ($connection !== false) {
            fclose($connection);
            break;
        }
        usleep(50000);
    }

    return $site = ['root' => $root, 'url' => $url, 'server' => $server];
}

/** Back to a tree that was never installed, with the installer of the repository. */
function installerFlowReset(): array
{
    $site = installerFlowSite();
    $root = $site['root'];
    foreach ([
        'core/.install', 'core/config/database/connections/default.php', 'core/database/flow.sqlite',
        'install.session.php', 'install', 'core/storage/bootstrap', 'assets/cache/installProc.inc.php',
    ] as $path) {
        installerFlowDelete("$root/$path");
    }
    mkdir("$root/core/storage/bootstrap", 0777, true);
    installerFlowCopy(dirname(__DIR__, 3) . '/install', "$root/install");

    return $site;
}

function installerFlowBrowser(array $site): Client
{
    return new Client([
        'base_uri' => $site['url'],
        'cookies' => new CookieJar(),
        'http_errors' => false,
        'timeout' => 300,
        'headers' => ['Accept-Language' => 'en', 'Referer' => $site['url'] . '/install/'],
    ]);
}

/**
 * The fields form#install_form submits as it stands, and where it posts them.
 *
 * @return array{action: string, fields: array<string, mixed>}
 */
function installerFlowForm(string $html): array
{
    $document = new DOMDocument();
    @$document->loadHTML($html);
    $form = $document->getElementById('install_form');
    expect($form)->toBeInstanceOf(DOMElement::class, 'no installer form in: ' . substr(strip_tags($html), 0, 500));

    $fields = [];
    foreach ((new DOMXPath($document))->query('.//input|.//select|.//textarea', $form) as $field) {
        $name = $field->getAttribute('name');
        $type = strtolower($field->getAttribute('type'));
        if ($name === '' || $field->hasAttribute('disabled')
            || (in_array($type, ['checkbox', 'radio'], true) && !$field->hasAttribute('checked'))) {
            continue;
        }
        if ($field->nodeName === 'select') {
            $value = '';
            foreach ($field->getElementsByTagName('option') as $index => $option) {
                if ($index === 0 || $option->hasAttribute('selected')) {
                    $value = $option->getAttribute('value');
                }
            }
        } else {
            $value = $field->nodeName === 'textarea' ? $field->textContent : $field->getAttribute('value');
        }
        if (str_ends_with($name, '[]')) {
            $fields[substr($name, 0, -2)][] = $value;
        } else {
            $fields[$name] = $value;
        }
    }

    return ['action' => $form->getAttribute('action'), 'fields' => $fields];
}

function installerFlowPost(Client $browser, string $path, array $fields): string
{
    $response = $browser->post($path, ['form_params' => $fields]);
    expect($response->getStatusCode())->toBe(200, "$path answered " . $response->getStatusCode());

    return (string) $response->getBody();
}

/** Submits the forms from $page on, each with $choices, up to the page of results. */
function installerFlowFinish(Client $browser, string $page, array $choices): string
{
    for ($step = 0; $step < 8 && !str_contains($page, 'remove_installer.processor.php'); $step++) {
        $form = installerFlowForm($page);
        $page = installerFlowPost(
            $browser,
            '/install/' . $form['action'],
            array_replace($form['fields'], $choices)
        );
    }
    expect($page)->toContain('remove_installer.processor.php')->not->toContain('class="notok"');

    return $page;
}

/** @return string the page of results */
function installerFlowInstall(Client $browser): string
{
    $page = (string) $browser->get('/install/index.php')->getBody();
    $form = installerFlowForm($page);
    $page = installerFlowPost($browser, '/install/' . $form['action'], ['language' => 'en'] + $form['fields']);
    $form = installerFlowForm($page);
    $page = installerFlowPost($browser, '/install/' . $form['action'], ['installmode' => '0'] + $form['fields']);

    $connection = [
        'database_type' => 'sqlite', 'method' => 'sqlite', 'host' => '', 'uid' => '', 'pwd' => '',
        'database_name' => 'flow', 'tableprefix' => 'evo_', 'installMode' => '0',
    ];
    $collations = installerFlowPost($browser, '/install/index.php?s=1&action=connection/collation', $connection);
    preg_match('/<option value="([^"]+)"/', $collations, $collation);

    return installerFlowFinish($browser, $page, [
        'database_type' => 'sqlite', 'databasehost' => '', 'databaseloginname' => '', 'databaseloginpassword' => '',
        'database_name' => 'flow', 'tableprefix' => 'evo_', 'database_collation' => $collation[1] ?? '',
        'cmsadmin' => INSTALLER_FLOW_ADMIN, 'cmsadminemail' => 'admin@example.com',
        'cmspassword' => INSTALLER_FLOW_PASSWORD, 'cmspasswordconfirm' => INSTALLER_FLOW_PASSWORD,
        'managerlanguage' => 'en', 'language' => 'en', 'installmode' => '0', 'chkagree' => '1', 'installdata' => '0',
    ]);
}

/**
 * An update of an installed site that still has its install folder, by an administrator's browser.
 *
 * @return array{0: Client, 1: string} the browser and the page of results
 */
function installerFlowUpdate(array $site): array
{
    installerFlowInstall(installerFlowBrowser($site));
    // The session that installed the site is over, as it is by the time anyone updates it.
    installerFlowDelete($site['root'] . '/install.session.php');

    $browser = installerFlowBrowser($site);
    $browser->get('/manager/');
    $browser->post('/manager/index.php?a=0', [
        'form_params' => ['username' => INSTALLER_FLOW_ADMIN, 'password' => INSTALLER_FLOW_PASSWORD],
        'headers' => ['Referer' => $site['url'] . '/manager/'],
    ]);
    $browser->get('/manager/index.php?a=2');
    expect($browser->getConfig('cookies')->getCookieByName('evo_installer_grant'))->not->toBeNull();

    $page = (string) $browser->get('/install/index.php?action=mode')->getBody();

    return [$browser, installerFlowFinish($browser, $page, [
        'installmode' => '1', 'chkagree' => '1', 'installdata' => '0', 'language' => 'en',
    ])];
}

/** What the last page posts: its token, and the box if it is ticked. */
function installerFlowClose(Client $browser, string $page, bool $ticked): string
{
    $form = installerFlowForm($page);
    expect($form['action'])->toEndWith('/processors/remove_installer.processor.php');
    $fields = $form['fields'];
    expect($fields['rminstaller'] ?? null)->toBe('1', 'the box must start ticked');
    if (!$ticked) {
        unset($fields['rminstaller']);
    }

    return (string) $browser->post('/install/' . $form['action'], ['form_params' => $fields])->getBody();
}

afterAll(function () {
    installerFlowSite(true);
});

$scenarios = [
    'a new site' => [static fn (array $site) => [$browser = installerFlowBrowser($site), installerFlowInstall($browser)]],
    'an update' => [static fn (array $site) => installerFlowUpdate($site)],
];

test('the ticked box removes the install folder', function (Closure $scenario) {
    $site = installerFlowReset();
    [$browser, $page] = $scenario($site);

    expect(file_get_contents($site['root'] . '/install.session.php'))->toContain('$install_token');

    expect(installerFlowClose($browser, $page, true))->toContain("window.location='../#?a=2'")
        ->and(is_dir($site['root'] . '/install'))->toBeFalse()
        ->and(is_file($site['root'] . '/install.session.php'))->toBeFalse()
        ->and((string) installerFlowBrowser($site)->get('/install/index.php')->getBody())->not->toContain('install_form');
})->with($scenarios);

test('the box left unticked keeps the folder, shut to everyone else', function (Closure $scenario) {
    $site = installerFlowReset();
    [$browser, $page] = $scenario($site);

    expect(installerFlowClose($browser, $page, false))->toContain("window.location='../#?a=2'")
        ->and(is_file($site['root'] . '/install/index.php'))->toBeTrue()
        ->and(is_file($site['root'] . '/install.session.php'))->toBeTrue()
        ->and(installerFlowBrowser($site)->get('/install/index.php')->getStatusCode())->toBe(404);
})->with($scenarios);

test('the token works once and not for another browser', function () {
    $site = installerFlowReset();
    $browser = installerFlowBrowser($site);
    $page = installerFlowInstall($browser);

    $form = installerFlowForm($page);
    $forged = ['installer_token' => str_repeat('a', 64), 'rminstaller' => '1'];
    expect((string) installerFlowBrowser($site)->post('/install/' . $form['action'], ['form_params' => $forged])->getBody())
        ->toContain('Not found.')
        ->and(is_dir($site['root'] . '/install'))->toBeTrue();

    installerFlowClose($browser, $page, true);
    expect(is_dir($site['root'] . '/install'))->toBeFalse();
});
