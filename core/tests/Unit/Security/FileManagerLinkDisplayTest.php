<?php

/**
 * Links are shown and archived, never followed or created, and their targets are shown only as
 * relative paths inside the manager's root: neither ../../ nor an absolute target reveals where
 * the server keeps anything.
 */

function linkDisplayMake(string $target, string $link): void
{
    if (function_exists('symlink') && @symlink($target, $link)) {
        return;
    }
    if (PHP_OS_FAMILY === 'Windows' && is_dir($target)) {
        try {
            evoRunCommand('mklink /J ' . escapeshellarg(str_replace('/', '\\', $link)) . ' '
                . escapeshellarg(str_replace('/', '\\', $target)) . ' 2>&1', $code);
            if ($code === 0) {
                return;
            }
        } catch (\Exception $e) {
        }
    }
    test()->markTestSkipped('no links can be made here');
}

function linkDisplayRemove(string $path): void
{
    if (fileManagerIsLink($path)) {
        @unlink($path) || @rmdir($path);

        return;
    }
    if (is_file($path)) {
        @unlink($path);

        return;
    }
    foreach (is_dir($path) ? scandir($path) : [] as $entry) {
        if ($entry !== '.' && $entry !== '..') {
            linkDisplayRemove($path . '/' . $entry);
        }
    }
    @rmdir($path);
}

beforeEach(function () {
    $base = sys_get_temp_dir() . '/evo-fm-linkview-' . bin2hex(random_bytes(6));
    foreach (['site/assets/files/app/releases/v1', 'site/assets/files/app/releases/v2', 'site/assets/files/app/hr',
        'site/manager', 'site/core', 'outside/secret'] as $dir) {
        mkdir($base . '/' . $dir, 0777, true);
    }
    $this->base = str_replace('\\', '/', realpath($base));
    $this->site = $this->base . '/site';
    $this->app = $this->site . '/assets/files/app';
    file_put_contents($this->app . '/releases/v2/index.txt', 'v2');
    file_put_contents($this->app . '/hr/pay.txt', 'pay');
    file_put_contents($this->base . '/outside/secret/key.txt', 'key');
});

afterEach(function () {
    if (isset($this->base)) {
        linkDisplayRemove($this->base);
    }
});

it('shows a link target relative to the link, and hides one outside the root', function (string $target, ?string $expected) {
    $root = '/srv/site';

    expect(fileManagerRelativeLinkTarget($root, '/srv/site/app/current', $target))->toBe($expected);
})->with([
    'blue-green release' => ['releases/v2', 'releases/v2'],
    'sibling folder' => ['../shared/uploads', '../shared/uploads'],
    'climbs out of the root' => ['../../../etc/passwd', null],
    'climbs to the root itself' => ['../', '..'],
    // text from an archive: shown for an absolute guess that hits, it would tell where the site lives
    'absolute inside the root' => ['/srv/site/app/releases/v1', null],
    'Windows UNC path' => ['\\\\server\\share\\x', null],
    'absolute outside the root' => ['/etc/passwd', null],
    'absolute beside the root' => ['/srv/site-old/app', null],
    'Windows separators' => ['..\\shared\\x', '../shared/x'],
    'Windows drive outside' => ['C:\\Windows\\System32', null],
    'Windows long-path prefix outside' => ['\\\\?\\C:\\secret', null],
    'above the file system root' => ['../../../../../../..', null],
]);

it('hides a target the viewer may not reach', function () {
    expect(fileManagerRelativeLinkTarget('/srv/site', '/srv/site/app/current', 'hr/pay.txt', static fn () => false))->toBeNull()
        ->and(fileManagerRelativeLinkTarget('/srv/site', '/srv/site/app/current', 'hr/pay.txt', static fn () => true))->toBe('hr/pay.txt');
});

it('reads a real link by where the file system says it leads', function () {
    linkDisplayMake($this->app . '/releases/v2', $this->app . '/current');
    linkDisplayMake($this->base . '/outside/secret', $this->app . '/escape');

    expect(fileManagerIsLink($this->app . '/current'))->toBeTrue()
        ->and(fileManagerRelativeLinkTarget($this->site, $this->app . '/current'))->toBe('releases/v2')
        ->and(fileManagerRelativeLinkTarget($this->site, $this->app . '/escape'))->toBeNull();
});

it('does not extract the links of an archive, neither as links nor as files, and reports them', function () {
    $zipPath = $this->base . '/deploy.zip';
    $zip = new ZipArchive();
    $zip->open($zipPath, ZipArchive::CREATE);
    $zip->addFromString('deploy/releases/v3/index.txt', 'v3');
    fileManagerZipAddLink($zip, 'deploy/current', 'releases/v3');
    fileManagerZipAddLink($zip, 'deploy/escape', '../../../../../outside/secret');
    $zip->close();

    $links = [];
    expect(fileManagerExtractZip($zipPath, $this->app, [], 0777, null, $created, $links))->toBeTrue()
        ->and(file_get_contents($this->app . '/deploy/releases/v3/index.txt'))->toBe('v3')
        ->and(file_exists($this->app . '/deploy/current'))->toBeFalse()
        ->and(file_exists($this->app . '/deploy/escape'))->toBeFalse()
        ->and($links)->toBe(['deploy/current' => 'releases/v3', 'deploy/escape' => '../../../../../outside/secret'])
        // what the manager is told: the release, not where the escape would have led
        ->and(fileManagerRelativeLinkTarget($this->site, $this->app . '/deploy/current', $links['deploy/current']))->toBe('releases/v3')
        ->and(fileManagerRelativeLinkTarget($this->site, $this->app . '/deploy/escape', $links['deploy/escape']))->toBeNull();
})->skip(!class_exists('ZipArchive'), 'zip is required');

it('stores a link as a link entry in the classic directory archive, without following it', function () {
    linkDisplayMake($this->app . '/releases/v2', $this->app . '/current');
    linkDisplayMake($this->base . '/outside/secret', $this->app . '/escape');
    $db = $this->base . '/groups.sqlite';
    touch($db);
    $pdo = new PDO('sqlite:' . $db);
    $pdo->exec('CREATE TABLE file_groups (id INTEGER PRIMARY KEY AUTOINCREMENT, document_group INTEGER NOT NULL, file VARCHAR(255) NOT NULL)');
    $pdo = null;

    $output = evoRunPhp(dirname(__DIR__, 2) . '/Fixtures/file-manager-folder-copy.php', [$this->site, base64_encode(json_encode([
        'db' => $db, 'action' => 'zip', 'role' => 1, 'source' => 'assets/files/app', 'zip' => $this->base . '/app.zip',
    ]))]);
    $result = json_decode($output, true);

    expect($result)->toBeArray($output)
        ->and($result['zipLinks'])->toBe(['app/current' => 'releases/v2'])
        ->and($result['zipEntries'])->toContain('app/releases/v2/index.txt')
        ->not->toContain('app/current/index.txt')
        ->not->toContain('app/escape')
        ->not->toContain('app/escape/key.txt');
})->skip(!extension_loaded('pdo_sqlite') || !class_exists('ZipArchive'), 'pdo_sqlite and zip are required');

it('stores a link as a link entry in the media browser archive only through its callback', function () {
    require_once dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/lib/class_zipFolder.php';
    linkDisplayMake($this->app . '/releases/v2', $this->app . '/current');
    $zipPath = $this->base . '/media.zip';

    new zipFolder($zipPath, $this->app, null, null, fn ($link) => fileManagerRelativeLinkTarget($this->app, $link));
    $plain = $this->base . '/plain.zip';
    new zipFolder($plain, $this->app);

    $read = static function (string $file): array {
        $zip = new ZipArchive();
        $zip->open($file);
        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entries[rtrim($zip->getNameIndex($i), '/')] = fileManagerZipEntryIsLink($zip, $i) ? 'link:' . $zip->getFromIndex($i) : 'entry';
        }
        $zip->close();

        return $entries;
    };

    expect($read($zipPath))->toHaveKey('app/current', 'link:releases/v2')
        ->not->toHaveKey('app/current/index.txt')
        ->and($read($plain))->not->toHaveKey('app/current')
        ->not->toHaveKey('app/current/index.txt');
})->skip(!class_exists('ZipArchive'), 'zip is required');

it('lists links in both managers without a URL, an action or a selection', function () {
    $root = dirname(__DIR__, 4);
    $functions = (string) file_get_contents($root . '/core/functions/actions/files.php');
    $browser = (string) file_get_contents($root . '/manager/media/browser/mcpuk/core/browser.php');
    $files = (string) file_get_contents($root . '/manager/media/browser/mcpuk/js/browser/files.js');
    $start = strpos($functions, 'if (fileManagerIsLink($newpath)) {');
    $branch = substr($functions, $start, strpos($functions, 'continue;', $start) - $start);
    $linksHtml = substr($files, strpos($files, 'browser.linksHtml = function'), 1600);

    // removing the link itself is the one action: no drill-down, view, download or web URL
    preg_match_all('/javascript:(\w+)/', $branch, $calls);
    expect($branch)->toContain('fileManagerLinkTargetText($filemanager_path, $newpath)')
        ->toContain('htmlspecialchars($file, ENT_QUOTES')
        ->not->toContain('mode=drill')
        ->not->toContain('viewfile')
        ->not->toContain('download')
        ->not->toContain('href="index.php')
        ->not->toContain('href="../')
        ->and(array_unique($calls[1]))->toBe(['deleteLink']);
    expect($browser)->toContain("'links'       => \$this->getLinks(\$this->session['dir'])")
        ->toContain('fn ($target) => $this->isPathAccessible($target)');
    // no .file class: nothing that selects, opens, returns or deletes files reaches a link
    expect($linksHtml)->toContain('class="file-link"')
        ->not->toMatch('/class="file[ "]/')
        ->toContain('_.htmlData(link.name)')
        ->toContain('_.htmlData(target)');
    foreach (['folders.js', 'init.js', 'toolbar.js'] as $script) {
        expect((string) file_get_contents($root . '/manager/media/browser/mcpuk/js/browser/' . $script))
            ->toContain('browser.links = data.links ? data.links : [];');
    }
});

it('never asks the file groups about link text from an archive, nor says broken of a hidden target', function () {
    $functions = (string) file_get_contents(dirname(__DIR__, 3) . '/functions/actions/files.php');
    $start = strpos($functions, 'function fileManagerLinkTargetText');
    $body = substr($functions, $start, strpos($functions, "if(!function_exists('fileManagerLinkList'))") - $start);

    // a restricted guess would answer differently from an open one
    expect($body)->toContain('$mayReveal = $target !== null ? null :')
        ->and($body)->toContain("if (\$relative === null) {\n            return \$_lang['files_link_hidden'];")
        ->and($body)->toContain("\$relative . ' (' . \$_lang['files_link_broken'] . ')'");
});

it('removes only the link itself, judged by its own path, in both managers', function () {
    $root = dirname(__DIR__, 4);
    $classic = (string) file_get_contents($root . '/manager/actions/files.dynamic.php');
    $browser = (string) file_get_contents($root . '/manager/media/browser/mcpuk/core/browser.php');
    $functions = (string) file_get_contents($root . '/core/functions/actions/files.php');
    $files = (string) file_get_contents($root . '/manager/media/browser/mcpuk/js/browser/files.js');
    $mode = substr($classic, strpos($classic, "mode') == 'deletelink'"), 2200);
    $act = substr($browser, strpos($browser, 'function act_deleteLink'), 2600);
    $canModify = substr($browser, strpos($browser, 'function canModifyLink'), 1400);

    expect($mode)->toContain('$token_check')
        ->toContain("strpbrk(\$linkName, '/\\\\') !== false")
        ->toContain('fileManagerRefuseReservedName($linkName) || !fileManagerIsLink($linkPath)')
        ->toContain('fileManagerCanModifyExistingPath($linkRelative, $userGroups)')
        ->toContain('fileManagerPathIsProtected($linkPath, $protected_path)')
        ->toContain('!fileManagerIsLink($linkPath) || !fileManagerRemoveLink($linkPath)')
        ->not->toContain('forgetRestrictions')
        ->not->toContain('rrmdir(');
    // the reserved name is refused before the first probe of the file system
    expect(strpos($mode, 'fileManagerRefuseReservedName($linkName)'))->toBeLessThan(strpos($mode, 'fileManagerIsLink($linkPath)'));
    expect($act)->toContain("\$this->config['access']['files']['delete']")
        ->toContain("\$this->isWriteAllowed(\$this->post['dir'])")
        ->toContain('fileManagerRefuseReservedName($name)')
        ->toContain('!$this->isLinkVisible($path) || !$this->canModifyLink($path)')
        ->toContain('!fileManagerIsLink($path) || !fileManagerRemoveLink($path)')
        ->not->toContain('forgetFileGroups')
        ->not->toContain('prune(');
    // the link's own path: canModifyExisting() would resolve it and judge the target
    expect($canModify)->toContain('fileManagerCanonicalCase($parent)')
        ->toContain('fileManagerPathIsProtected($lexical, $this->protectedPaths)')
        ->toContain('fileManagerCanModifyExistingPath(substr($lexical, strlen($root) + 1))')
        ->not->toContain('realpath($absPath)');
    expect($functions)->toContain("'<a href=\"javascript:deleteLink(\\'' . urlencode(\$file) . '\\');\">");
    expect($files)->toContain("url: browser.baseGetData('deleteLink')")
        ->toContain("\$('#files .file-link').rightClick(");
});

it('refuses a device name as cleaned, before the media browser probes it', function () {
    $browser = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/core/browser.php');

    foreach ([
        'function act_newDir' => ['fileManagerRefuseReservedName($newDir)', 'file_exists("$dir/$newDir")'],
        'function act_renameDir' => ['fileManagerRefuseReservedName($newName)', '$this->isTaken($newPath)'],
        'function act_rename()' => ['fileManagerRefuseReservedName($newName)', '$this->isTaken($newName)'],
        'function act_copyDir' => ['fileManagerRefuseReservedName($newName)', '$this->isTaken($newPath)'],
    ] as $function => [$refusal, $probe]) {
        $body = substr($browser, strpos($browser, $function), 4000);
        expect(strpos($body, $refusal))->not->toBeFalse($function)
            ->and(strpos($body, $refusal))->toBeLessThan(strpos($body, $probe));
    }
});

it('defines the English labels of links', function () {
    $_lang = [];
    include dirname(__DIR__, 3) . '/lang/en/global.php';

    foreach (['files_link', 'files_link_broken', 'files_link_hidden', 'file_unzip_links_skipped', 'files_link_delete',
        'files_link_confirm_delete', 'files_link_deleted', 'files_link_not_deleted', 'files_folder_copy_links'] as $key) {
        expect($_lang)->toHaveKey($key);
    }
});
