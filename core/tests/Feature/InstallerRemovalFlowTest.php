<?php

/*
| "Remove the install folder" on the last page of the installer, driven the way a browser drives
| it: a real site, the real forms, a real server. InstallerWebPathTest runs the processor against a
| lock it writes itself, so it cannot tell whether the installer ever stored the token.
|
| These flows run real migrations, and DB_* wins over the connection of a site, so nothing of the
| installation the tests run from may reach them. The copy leaves out its configuration, the site
| gets one of its own, and every test asks the site what it sees before the first write. All of it
| runs next to deliberately conflicting settings that lead to a database of their own, which must
| stay as it was.
*/

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\Process\Process;

const INSTALLER_FLOW_ADMIN = 'admin';
const INSTALLER_FLOW_PASSWORD = 'Passw0rd123';

/** Paths of the tree a site must not get: local configuration, or what would make it look installed. */
function installerFlowSkipped(string $path, bool $folder): bool
{
    if ($folder) {
        return preg_match('~^(\.git|\.github|tmp|node_modules|core/tests|core/storage/[^/]+|assets/cache)$~', $path) === 1;
    }

    return preg_match('~(^|/)\.env(\.|$)~', $path) === 1
        || preg_match('~^core/custom/.*(?<!\.example)(?<!\.sample)$~', $path) === 1
        || preg_match(
            '~^(core/\.install|core/config/database/connections/.*|core/database/[^/]+\.sqlite.*'
            . '|manager/includes/config\.inc\.php|install\.session\.php)$~',
            $path
        ) === 1;
}

function installerFlowCopy(string $from, string $to, string $relative = ''): void
{
    is_dir($to) || mkdir($to, 0777, true);
    foreach (scandir($from) as $name) {
        $path = ltrim($relative . '/' . $name, '/');
        $folder = is_dir("$from/$name") && !is_link("$from/$name");
        if ($name === '.' || $name === '..' || installerFlowSkipped($path, $folder)) {
            continue;
        }
        if ($folder) {
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

/** A configured installation in miniature: every way a site takes settings, all leading to one database. */
function installerFlowLocalConfiguration(string $root, string $database): void
{
    $connection = "<?php return ['driver' => 'sqlite', 'database' => " . var_export($database, true) . ", 'prefix' => 'evo_'];\n";
    $environment = "DB_TYPE=sqlite\nDB_DATABASE=$database\nDB_PREFIX=evo_\n";
    foreach ([
        '.env' => $environment,
        '.env.local' => $environment,
        'core/custom/.env' => $environment,
        'core/custom/define.php' => "<?php putenv('DB_DATABASE=$database');\n",
        'core/custom/config/database/connections/default.php' => $connection,
        'core/config/database/connections/default.php' => $connection,
        'core/config/database/connections/local.php' => $connection,
        'core/storage/cache/env.php' => "<?php return ['DB_DATABASE' => " . var_export($database, true) . "];\n",
        'manager/includes/config.inc.php' => "<?php\n",
    ] as $path => $content) {
        is_dir(dirname("$root/$path")) || mkdir(dirname("$root/$path"), 0777, true);
        file_put_contents("$root/$path", $content);
    }
}

/** Only what a process needs to run: the settings of the test run stay out of the site. */
function installerFlowEnvironment(): array
{
    $kept = ['PATH', 'HOME', 'TMP', 'TEMP', 'TMPDIR', 'SYSTEMROOT', 'COMPOSER_HOME'];
    $environment = [];
    foreach (array_keys($_ENV + getenv()) as $name) {
        if (!in_array(strtoupper($name), $kept, true)) {
            $environment[$name] = false;
        }
    }

    return $environment;
}

/**
 * The site of this file: copied and served once, because a tree with its vendor takes seconds.
 *
 * @return array{root: string, url: string, server: Process, original: string, originalHash: string}
 */
function installerFlowSite(bool $stop = false): ?array
{
    static $site = null;
    if ($stop) {
        if ($site !== null) {
            $site['server']->stop(1);
            installerFlowDelete(dirname($site['root']));
            installerFlowDelete(dirname($site['original']));
        }
        return $site = null;
    }
    if ($site !== null) {
        return $site;
    }

    $unique = str_replace('\\', '/', sys_get_temp_dir()) . '/evo-installer-flow-' . bin2hex(random_bytes(8));
    $root = "$unique/site";

    $original = "$unique-original/original.sqlite";
    mkdir(dirname($original), 0777, true);
    (new PDO("sqlite:$original"))->exec('CREATE TABLE evo_site_content (id INTEGER PRIMARY KEY, pagetitle TEXT)');
    $local = dirname($original) . '/tree';
    installerFlowLocalConfiguration($local, $original);

    installerFlowCopy(dirname(__DIR__, 3), $root);
    installerFlowCopy($local, $root);
    foreach (['logs', 'cache', 'sessions', 'bootstrap', 'blade'] as $folder) {
        is_dir("$root/core/storage/$folder") || mkdir("$root/core/storage/$folder", 0777, true);
    }
    is_dir("$root/assets/cache") || mkdir("$root/assets/cache", 0777, true);

    file_put_contents("$root/core/custom/.env", "DB_TYPE=sqlite\nDB_DATABASE=$root/core/database/flow.sqlite\nDB_PREFIX=evo_\n");
    file_put_contents("$root/flow-probe.php", <<<'PHP'
<?php
// What this site resolves its connection from, with the loader the site and the installer share.
require __DIR__ . '/core/functions/laravel.php';
require __DIR__ . '/core/vendor/autoload.php';
require __DIR__ . '/core/includes/custom-define.inc.php';
$values = [];
foreach (['DB_CONNECTION', 'DB_TYPE', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_SOCKET'] as $name) {
    $values[$name] = env($name);
}
$files = glob(__DIR__ . '/core/custom/define.php') ?: [];
foreach (['core/custom/config', 'core/config/database/connections'] as $folder) {
    if (is_dir(__DIR__ . "/$folder")) {
        $entries = new RecursiveDirectoryIterator(__DIR__ . "/$folder", FilesystemIterator::SKIP_DOTS);
        foreach (new RecursiveIteratorIterator($entries) as $entry) {
            if ($entry->getExtension() === 'php') {
                $files[] = $entry->getPathname();
            }
        }
    }
}
header('Content-Type: application/json');
echo json_encode(['env' => $values, 'files' => $files]);
PHP);

    $socket = stream_socket_server('tcp://127.0.0.1:0');
    $address = stream_socket_get_name($socket, false);
    fclose($socket);

    // A conflict in the environment too: it has to go the way of the rest.
    $_ENV['DB_DATABASE'] = $original;
    $server = new Process(
        [PHP_BINARY, '-d', 'opcache.enable=0', '-S', $address, '-t', $root],
        $root,
        installerFlowEnvironment(),
        null,
        null
    );
    $server->start();
    unset($_ENV['DB_DATABASE']);

    for ($attempt = 0; $attempt < 100; $attempt++) {
        $connection = @fsockopen('127.0.0.1', (int) substr(strrchr($address, ':'), 1), $code, $message, 0.1);
        if ($connection !== false) {
            fclose($connection);
            break;
        }
        usleep(50000);
    }

    return $site = [
        'root' => $root,
        'url' => "http://$address",
        'server' => $server,
        'original' => $original,
        'originalHash' => hash_file('sha256', $original),
    ];
}

/**
 * Stops a test before its first write unless the site sees only the database of the fixture.
 * The connection file the installer writes is the one file allowed to appear.
 */
function installerFlowAssertIsolated(array $site): void
{
    $root = $site['root'];
    $seen = json_decode((string) file_get_contents($site['url'] . '/flow-probe.php'), true);
    $expected = [
        'DB_CONNECTION' => null, 'DB_TYPE' => 'sqlite', 'DB_HOST' => null,
        'DB_DATABASE' => "$root/core/database/flow.sqlite", 'DB_USERNAME' => null, 'DB_SOCKET' => null,
    ];
    if (!is_array($seen) || $seen['env'] !== $expected) {
        throw new RuntimeException('The site sees another connection: ' . json_encode($seen));
    }
    $written = "$root/core/config/database/connections/default.php";
    $foreign = array_values(array_filter($seen['files'], static fn (string $file) => str_replace('\\', '/', $file) !== $written));
    if ($foreign !== []) {
        throw new RuntimeException('Local configuration reached the site: ' . json_encode($foreign));
    }
}

/** Back to a tree that was never installed, with the installer of the repository. */
function installerFlowReset(): array
{
    $site = installerFlowSite();
    $root = $site['root'];
    foreach ([
        'core/.install', 'core/config/database/connections/default.php', 'core/database/flow.sqlite',
        'install.session.php', 'install', 'core/storage/bootstrap', 'core/storage/cache/env.php',
        'assets/cache/installProc.inc.php',
    ] as $path) {
        installerFlowDelete("$root/$path");
    }
    mkdir("$root/core/storage/bootstrap", 0777, true);
    installerFlowCopy(dirname(__DIR__, 3) . '/install', "$root/install", 'install');
    installerFlowAssertIsolated($site);

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

/** The fixture took the writes, and the database of the conflicting settings took none. */
function installerFlowExpectOwnDatabase(array $site): void
{
    installerFlowAssertIsolated($site);
    $database = new PDO('sqlite:' . $site['root'] . '/core/database/flow.sqlite');
    expect((int) $database->query('SELECT COUNT(*) FROM evo_site_content')->fetchColumn())->toBeGreaterThan(0)
        ->and(hash_file('sha256', $site['original']))->toBe($site['originalHash']);
}

afterEach(function () {
    $site = installerFlowSite();
    expect(hash_file('sha256', $site['original']))->toBe($site['originalHash']);
});

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

    installerFlowExpectOwnDatabase($site);
    expect(file_get_contents($site['root'] . '/install.session.php'))->toContain('$install_token');

    expect(installerFlowClose($browser, $page, true))->toContain("window.location='../#?a=2'")
        ->and(is_dir($site['root'] . '/install'))->toBeFalse()
        ->and(is_file($site['root'] . '/install.session.php'))->toBeFalse()
        ->and((string) installerFlowBrowser($site)->get('/install/index.php')->getBody())->not->toContain('install_form');
})->with($scenarios);

test('the box left unticked keeps the folder, shut to everyone else', function (Closure $scenario) {
    $site = installerFlowReset();
    [$browser, $page] = $scenario($site);

    installerFlowExpectOwnDatabase($site);
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

test('local configuration stays out of the copy', function () {
    $root = installerFlowSite()['root'];

    expect(is_file("$root/.env"))->toBeFalse()
        ->and(is_file("$root/.env.local"))->toBeFalse()
        ->and(is_file("$root/core/custom/define.php"))->toBeFalse()
        ->and(is_file("$root/core/custom/config/database/connections/default.php"))->toBeFalse()
        ->and(is_file("$root/core/config/database/connections/local.php"))->toBeFalse()
        ->and(is_file("$root/manager/includes/config.inc.php"))->toBeFalse()
        ->and(file_get_contents("$root/core/custom/.env"))->toContain("$root/core/database/flow.sqlite");
});

test('a setting that reaches the site stops the test before it writes', function (string $path, string $content) {
    $site = installerFlowReset();
    $file = $site['root'] . '/' . $path;
    $kept = is_file($file) ? file_get_contents($file) : null;
    is_dir(dirname($file)) || mkdir(dirname($file), 0777, true);
    file_put_contents($file, str_replace('ORIGINAL', $site['original'], $content));
    installerFlowDelete($site['root'] . '/core/storage/cache/env.php');

    try {
        expect(fn () => installerFlowAssertIsolated($site))->toThrow(RuntimeException::class);
    } finally {
        $kept === null ? unlink($file) : file_put_contents($file, $kept);
        installerFlowDelete($site['root'] . '/core/storage/cache/env.php');
    }
    installerFlowAssertIsolated($site);
})->with([
    'the .env of the site' => ['core/custom/.env', "DB_TYPE=sqlite\nDB_DATABASE=ORIGINAL\n"],
    'a server in .env' => ['core/custom/.env', "DB_TYPE=mysql\nDB_HOST=db.example\n"],
    'custom/define.php' => ['core/custom/define.php', "<?php\n"],
    'custom/config' => ['core/custom/config/database/connections/default.php', "<?php return [];\n"],
    'a connection file' => ['core/config/database/connections/local.php', "<?php return [];\n"],
]);
