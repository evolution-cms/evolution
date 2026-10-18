<?php

/**
 * Folder copies (both file managers) and directory archives must not hand out what the manager
 * may not reach: file groups they are not in, links, protected folders, names they may not create.
 */

function runFolderCopyFixture(string $site, string $db, array $options): array
{
    $output = evoRunPhp(
        dirname(__DIR__, 2) . '/Fixtures/file-manager-folder-copy.php',
        [$site, base64_encode(json_encode($options + ['db' => $db]))]
    );
    $decoded = json_decode($output, true);
    expect($decoded)->toBeArray($output);

    return $decoded;
}

function folderCopyTree(string $root): array
{
    $entries = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $item) {
        $entries[] = ltrim(str_replace('\\', '/', substr($item->getPathname(), strlen($root))), '/');
    }
    sort($entries);

    return $entries;
}

beforeEach(function () {
    $base = str_replace('\\', '/', sys_get_temp_dir()) . '/evo-fm-copy-' . bin2hex(random_bytes(6));
    foreach ([
        'site/assets/files/team/open/deep',
        'site/assets/files/team/secret',
        'site/assets/files/team/mine',
        'site/assets/plugins/demo',
        'site/manager',
        'site/core',
    ] as $dir) {
        mkdir($base . '/' . $dir, 0777, true);
    }
    $base = str_replace('\\', '/', realpath($base));
    $team = $base . '/site/assets/files/team';
    file_put_contents($team . '/readme.txt', 'readme');
    file_put_contents($team . '/open/a.txt', 'a');
    file_put_contents($team . '/open/deep/b.png', 'b');
    file_put_contents($team . '/secret/salary.txt', 'secret');
    file_put_contents($team . '/mine/note.txt', 'mine');
    file_put_contents($team . '/hidden.txt', 'hidden');
    file_put_contents($team . '/shell.php', '<?php');
    file_put_contents($team . '/notes.md', 'not on the upload list');

    $db = $base . '/groups.sqlite';
    touch($db);
    $pdo = new PDO('sqlite:' . $db);
    $pdo->exec('CREATE TABLE file_groups (id INTEGER PRIMARY KEY AUTOINCREMENT, document_group INTEGER NOT NULL, file VARCHAR(255) NOT NULL)');
    $insert = $pdo->prepare('INSERT INTO file_groups (document_group, file) VALUES (?, ?)');
    foreach ([
        // the folder itself: groups 1 and 3, so the copy has to be held to them as well
        [1, 'assets/files/team'],
        [3, 'assets/files/team'],
        // group 2 only: the manager (group 1) may not reach it, nor anything below
        [2, 'assets/files/team/secret'],
        [1, 'assets/files/team/mine'],
        [2, 'assets/files/team/hidden.txt'],
        // beside the folder, untouched by any copy
        [5, 'assets/files/teamX'],
        // left at the destination by a folder removed outside the CMS
        [7, 'assets/files/team-copy/stale.txt'],
    ] as $row) {
        $insert->execute($row);
    }
    $pdo = null;

    $this->base = $base;
    $this->site = $base . '/site';
    $this->db = $db;
});

afterEach(function () {
    $remove = static function (string $path) use (&$remove): void {
        if (is_link($path) || is_file($path)) {
            @unlink($path);

            return;
        }
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                $remove($path . '/' . $entry);
            }
        }
        @rmdir($path);
    };
    if (isset($this->base) && is_dir($this->base)) {
        $remove($this->base);
    }
});

it('copies a folder without what the manager may not reach or create, and keeps its groups', function () {
    $result = runFolderCopyFixture($this->site, $this->db, [
        'action' => 'copyFolder',
        'groups' => [1, 3],
        'source' => 'assets/files/team',
        'target' => 'assets/files/team-copy',
    ]);

    expect($result['result'])->toBeTrue()
        ->and(folderCopyTree($this->site . '/assets/files/team-copy'))->toBe([
            'mine', 'mine/note.txt', 'open', 'open/a.txt', 'open/deep', 'open/deep/b.png', 'readme.txt',
        ])
        ->and($result['skipped'])->toBe([
            'hidden.txt' => 'refused',
            'notes.md' => 'refused',
            'secret' => 'refused',
            'shell.php' => 'refused',
        ])
        // the copy is exactly as closed as the original; the stale row is gone, nothing beside changed
        ->and($result['rows'])->toBe([
            'assets/files/team-copy/mine:1',
            'assets/files/team-copy:1',
            'assets/files/team-copy:3',
            'assets/files/team/hidden.txt:2',
            'assets/files/team/mine:1',
            'assets/files/team/secret:2',
            'assets/files/team:1',
            'assets/files/team:3',
            'assets/files/teamX:5',
        ])
        // the executable name was refused by the rule that logs it
        ->and(implode("\n", $result['log']))->toContain('executable name');
    expect(file_get_contents($this->site . '/assets/files/team/secret/salary.txt'))->toBe('secret');
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

it('copies everything for an administrator, still without executable names they may not create', function () {
    $result = runFolderCopyFixture($this->site, $this->db, [
        'action' => 'copyFolder',
        'role' => 1,
        'source' => 'assets/files/team',
        'target' => 'assets/files/team-copy',
    ]);

    expect($result['result'])->toBeTrue()
        ->and(folderCopyTree($this->site . '/assets/files/team-copy'))->toContain('secret/salary.txt', 'hidden.txt')
        ->and($result['skipped'])->toBe(['notes.md' => 'refused', 'shell.php' => 'refused'])
        // groups follow the copy for whoever makes it, or the copied secret folder would be open
        ->and($result['rows'])->toContain('assets/files/team-copy/secret:2', 'assets/files/team-copy/hidden.txt:2');
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

it('lets a manager who may run code copy executable names that are on the upload list', function () {
    $result = runFolderCopyFixture($this->site, $this->db, [
        'action' => 'copyFolder',
        'groups' => [1],
        'permissions' => ['save_snippet'],
        'uploadFiles' => 'txt,php,md',
        'source' => 'assets/files/team',
        'target' => 'assets/files/team-copy',
    ]);

    expect($result['result'])->toBeTrue()
        ->and(folderCopyTree($this->site . '/assets/files/team-copy'))->toContain('shell.php', 'notes.md')
        ->and($result['skipped'])->toBe(['hidden.txt' => 'refused', 'secret' => 'refused']);
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

it('leaves links and protected folders out of the copy', function () {
    $team = $this->site . '/assets/files/team';
    $linked = @symlink($this->site . '/core', $team . '/core-link');
    $result = runFolderCopyFixture($this->site, $this->db, [
        'action' => 'copyFolder',
        'role' => 1,
        'source' => 'assets',
        'target' => 'assets-copy',
    ]);

    expect($result['result'])->toBeTrue()
        ->and(folderCopyTree($this->site . '/assets-copy'))->not->toContain('plugins', 'plugins/demo')
        ->and($result['skipped'])->toHaveKey('plugins', 'protected folder');
    if ($linked) {
        expect($result['skipped'])->toHaveKey('files/team/core-link', 'link')
            ->and(file_exists($this->site . '/assets-copy/files/team/core-link'))->toBeFalse();
    }
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

it('refuses a destination that exists, is protected or lies inside the folder itself', function (string $target) {
    mkdir($this->site . '/assets/files/taken');
    $result = runFolderCopyFixture($this->site, $this->db, [
        'action' => 'copyFolder',
        'role' => 1,
        'source' => 'assets/files/team',
        'target' => $target,
    ]);

    expect($result['result'])->toBeFalse()
        ->and($result['rows'])->toContain('assets/files/team-copy/stale.txt:7');
})->with([
    'existing folder' => 'assets/files/taken',
    'inside the source' => 'assets/files/team/open/again',
    'protected folder' => 'assets/plugins/team',
])->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

it('leaves out of the classic ZIP download what the manager may not reach', function () {
    $result = runFolderCopyFixture($this->site, $this->db, [
        'action' => 'zip',
        'groups' => [1],
        'source' => 'assets/files/team',
        'zip' => $this->base . '/team.zip',
    ]);

    expect($result['result'])->toBeTrue()
        ->and($result['zipEntries'])->toBe([
            'team', 'team/mine', 'team/mine/note.txt', 'team/notes.md', 'team/open', 'team/open/a.txt',
            'team/open/deep', 'team/open/deep/b.png', 'team/readme.txt', 'team/shell.php',
        ]);
})->skip(!extension_loaded('pdo_sqlite') || !class_exists('ZipArchive'), 'pdo_sqlite and zip are required');

it('leaves a refused folder with everything below it out of the media browser ZIP', function () {
    require_once dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/lib/class_zipFolder.php';
    $team = $this->site . '/assets/files/team';
    $zip = $this->base . '/media.zip';

    new zipFolder($zip, $team, null, static fn ($path) => !str_contains(str_replace('\\', '/', $path), '/team/secret'));

    $archive = new ZipArchive();
    $archive->open($zip);
    $names = [];
    for ($i = 0; $i < $archive->numFiles; $i++) {
        $names[] = rtrim($archive->getNameIndex($i), '/');
    }
    $archive->close();

    expect($names)->toContain('team/open/deep/b.png')
        ->not->toContain('team/secret')
        ->not->toContain('team/secret/salary.txt');
})->skip(!class_exists('ZipArchive'), 'zip is required');

it('checks access, names and groups in both folder copy entry points', function () {
    $root = dirname(__DIR__, 4);
    $classic = (string) file_get_contents($root . '/manager/actions/files.dynamic.php');
    $browser = (string) file_get_contents($root . '/manager/media/browser/mcpuk/core/browser.php');
    $menu = (string) file_get_contents($root . '/manager/media/browser/mcpuk/js/browser/folders.js');
    $copyBlock = substr($classic, strpos($classic, "mode') == 'copyFolder'"), 5000);
    $copyAction = substr($browser, strpos($browser, 'function act_copyDir'), 6000);

    expect($copyBlock)
        ->toContain('$token_check')
        ->toContain('fileManagerIsAccessible($dirTarget[\'relative\'], $userGroups)')
        ->toContain('fileManagerRefuseReservedName($newDirname)')
        ->toContain('fileManagerIsNewWriteTarget($filemanager_path, $newdir)')
        ->toContain('fileManagerCopyFolderAccessFilter($filemanager_path, $dirname)')
        ->toContain('checkExtension($path)')
        ->toContain('FileManagerAccess::carriedRestrictions($sourceKey, $targetKey)')
        ->toContain('FileManagerAccess::copyFolderRestrictions($sourceKey, $targetKey, $carried, $copiedEntries)');
    expect($copyAction)
        ->toContain('$this->isWriteAllowed($parentRel)')
        ->toContain('$this->isPathAccessible($dir)')
        ->toContain('$this->subtreeAccessFilter($dir)')
        ->toContain('$this->validateFilename(basename($path), $type)')
        ->toContain('FileManagerAccess::carriedRestrictions($sourceKey, $targetKey)')
        ->toContain('FileManagerAccess::copyFolderRestrictions($sourceKey, $targetKey, $carried, $copied)')
        ->toContain('$this->isSafeThumbPath(');
    expect($menu)
        ->toContain('kcact:cpdir')
        ->toContain("browser.baseGetData('copyDir')");
    expect($classic)->toContain('function copyFolder(dir)')
        ->toContain('mode=copyFolder')
        ->toContain('&token=<?= $newToken;?>');
});

it('defines the English labels of the folder copy', function () {
    $_lang = [];
    include dirname(__DIR__, 3) . '/lang/en/global.php';

    foreach (['files_copy_folder', 'files_folder_copied', 'files_folder_not_copied', 'files_folder_exists',
        'files_folder_copy_skipped', 'files_dynamic_copy_folder_name'] as $key) {
        expect($_lang)->toHaveKey($key);
    }
});
