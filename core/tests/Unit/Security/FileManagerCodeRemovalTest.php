<?php

/**
 * Whoever may not create code may not delete, rename or move it either: a file or link of an
 * executable name, or a folder holding one, stays where it is for a manager without a code permission.
 */

require_once dirname(__DIR__, 3) . '/functions/actions/files.php';

function codeRemovalTree(): string
{
    $base = str_replace('\\', '/', sys_get_temp_dir()) . '/evo-fm-code-' . bin2hex(random_bytes(6));
    mkdir("$base/clean/deep", 0777, true);
    mkdir("$base/guarded/deep/deeper", 0777, true);
    mkdir("$base/outside", 0777, true);
    file_put_contents("$base/clean/a.txt", 'a');
    file_put_contents("$base/clean/deep/b.png", 'b');
    file_put_contents("$base/guarded/readme.txt", 'r');
    file_put_contents("$base/guarded/deep/deeper/.htaccess", 'Require all denied');
    file_put_contents("$base/outside/shell.php", '<?php');

    return str_replace('\\', '/', realpath($base));
}

function removeCodeRemovalTree(string $dir): void
{
    foreach (scandir($dir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = "$dir/$entry";
        if (fileManagerIsLink($path)) {
            fileManagerRemoveLink($path);
        } elseif (is_dir($path)) {
            removeCodeRemovalTree($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

it('treats spellings Windows turns into a config or script file as executable names', function (string $name) {
    expect(fileManagerIsExecutableName($name))->toBeTrue();
})->with([
    '.htaccess.', '.HTACCESS ', '.htaccess. .', '.htaccess:x.txt', '.htaccess::$DATA',
    '.user.ini.', 'web.config ', 'shell.php.', 'shell.php::$DATA',
]);

it('judges a stream name by the file it belongs to', function (string $name) {
    expect(fileManagerIsExecutableName($name))->toBeFalse();
})->with(['x.txt:.htaccess', 'photo.jpg:shell.php', '.htaccess.txt', 'htaccess']);

it('finds code at or below a path, and nothing in a folder without it', function () {
    $base = codeRemovalTree();
    try {
        expect(fileManagerCodeEntry("$base/clean"))->toBeNull()
            ->and(fileManagerCodeEntry("$base/clean/a.txt"))->toBeNull()
            ->and(fileManagerCodeEntry("$base/guarded"))->toBe('deep/deeper/.htaccess')
            ->and(fileManagerCodeEntry("$base/guarded/deep/deeper/.htaccess"))->toBe('')
            ->and(fileManagerCodeEntry("$base/outside/shell.php"))->toBe('')
            ->and(fileManagerCodeEntry("$base/missing/x.php"))->toBe('');
    } finally {
        removeCodeRemovalTree($base);
    }
});

it('counts a link by its own name and never follows one', function () {
    $base = codeRemovalTree();
    try {
        $link = "$base/clean/to-outside";
        if (!@symlink("$base/outside", $link) && PHP_OS_FAMILY === 'Windows') {
            // without the symlink privilege a junction does the same
            evoRunCommand('mklink /J ' . escapeshellarg(str_replace('/', '\\', $link)) . ' '
                . escapeshellarg(str_replace('/', '\\', "$base/outside")) . ' 2>&1');
        }
        if (!fileManagerIsLink($link)) {
            $this->markTestSkipped('links cannot be created here');
        }
        // the folder it leads to holds shell.php, but the link is not followed
        expect(fileManagerCodeEntry("$base/clean"))->toBeNull()
            ->and(fileManagerCodeEntry("$base/clean/to-outside"))->toBeNull();

        if (@symlink("$base/clean/a.txt", "$base/clean/.htaccess")) {
            // a link named .htaccess is read by the web server like the file
            expect(fileManagerCodeEntry("$base/clean"))->toBe('.htaccess')
                ->and(fileManagerCodeEntry("$base/clean/.htaccess"))->toBe('');
        }
    } finally {
        removeCodeRemovalTree($base);
    }
});

it('asks the code permission before anything is probed, and logs the refusal', function () {
    $source = file_get_contents(dirname(__DIR__, 3) . '/functions/actions/files.php');
    $start = strpos($source, 'function fileManagerCodeRemovalDenied');
    $body = substr($source, $start, strpos($source, "\n    }\n", $start) - $start);

    expect($body)->toContain("fileManagerDenied('executable name'")
        ->and(strpos($body, 'fileManagerMayRunCode()'))->toBeLessThan(strpos($body, 'fileManagerCodeEntry('));
});

it('refuses deleting and renaming code in the classic file manager', function () {
    $root = dirname(__DIR__, 4);
    $page = file_get_contents($root . '/manager/actions/files.dynamic.php');
    $helpers = file_get_contents($root . '/core/functions/actions/files.php');

    foreach (['deletefolder' => '$folder', 'deletelink' => '$linkPath', 'renameFolder' => '$dirname', 'renameFile' => '$filename'] as $mode => $subject) {
        $start = strpos($page, "get_by_key(\$_REQUEST, 'mode') == '$mode'");
        expect($start)->not->toBeFalse($mode);
        $branch = substr($page, $start, 2500);
        $guard = strpos($branch, "fileManagerCodeRemovalDenied($subject, ");
        expect($guard)->not->toBeFalse($mode);
        // before the file system is changed
        foreach (['rrmdir(', 'fileManagerRemoveLink(', 'rename('] as $write) {
            $at = strpos($branch, $write);
            if ($at !== false) {
                expect($guard)->toBeLessThan($at, "$mode / $write");
            }
        }
    }

    $start = strpos($helpers, 'function delete_file()');
    $delete = substr($helpers, $start, 2000);
    expect(strpos($delete, 'fileManagerCodeRemovalDenied($file, $fileRel)'))->toBeLessThan(strpos($delete, 'unlink('))
        // the message names the path below the root, not the server's absolute path
        ->and($delete)->toContain("sprintf(\$_lang['deleting_file'], htmlspecialchars(\$fileRel");
});

it('refuses deleting and renaming code in the media browser', function () {
    $browser = file_get_contents(dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/core/browser.php');

    foreach ([
        'act_renameDir' => ['$this->refuseCodeRemoval($dir)', '@rename($dir'],
        'act_deleteDir' => ['$this->refuseCodeRemoval($dir)', 'dir::prune('],
        'act_rename' => ['$this->refuseCodeRemoval($file)', '@rename($file'],
        'act_delete' => ['$this->refuseCodeRemoval($file)', 'unlink('],
        'act_rm_cbd' => ['$this->refuseCodeRemoval($path)', 'unlink('],
        'act_deleteLink' => ['$this->refuseCodeRemoval($path)', 'fileManagerRemoveLink('],
    ] as $action => [$guard, $write]) {
        $start = strpos($browser, "protected function $action()");
        expect($start)->not->toBeFalse($action);
        $body = substr($browser, $start, strpos($browser, "\n    protected function ", $start + 10) - $start);
        expect($body)->toContain($guard);
        expect(strpos($body, $guard))->toBeLessThan(strpos($body, $write), $action);
    }

    $start = strpos($browser, 'protected function refuseCodeRemoval(');
    $helper = substr($browser, $start, 900);
    expect($helper)->toContain('$this->mayAddExecutableFiles()')
        ->and($helper)->toContain('fileManagerCodeEntry($absPath)')
        ->and($helper)->toContain("\$this->logDenied('executable name'");

    // moving a file away is already held to the name rule of what it writes
    $start = strpos($browser, 'protected function act_mv_cbd()');
    expect(substr($browser, $start, 4000))->toContain('$this->validateFilename($base, $type)');
});
