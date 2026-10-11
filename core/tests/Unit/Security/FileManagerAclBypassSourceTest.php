<?php

function fmAclSource(string $relative): string
{
    return (string) file_get_contents(dirname(__DIR__, 4) . '/' . $relative);
}

it('gives the folder groups only to files an extraction created', function () {
    $dynamic = fmAclSource('manager/actions/files.dynamic.php');

    expect($dynamic)->toContain('foreach ($createdFiles as $fp)')
        ->and($dynamic)->not->toContain('new RecursiveIteratorIterator(new RecursiveDirectoryIterator($startpath');
});

it('applies the folder permissions to the archive being extracted', function () {
    $dynamic = fmAclSource('manager/actions/files.dynamic.php');
    $start = strpos($dynamic, '$zipTarget = fileManagerResolvePath');

    expect(substr($dynamic, $start, 900))->toContain('fileManagerPathIsProtected($zipTarget[\'path\'], $protected_path)');
});

it('needs manage_groups when saving groups would leave the file with none', function () {
    $dynamic = fmAclSource('manager/actions/files.dynamic.php');

    expect($dynamic)->toContain('$remainingGroups = array_merge(array_diff($existingGroupIds, $toDelete), $submittedGroupIds);')
        ->and($dynamic)->toContain('!$canManageAllGroups && !empty($toDelete) && $remainingGroups === []');
});

it('deletes only its own temporary archives when the media browser starts', function () {
    $browser = fmAclSource('manager/media/browser/mcpuk/core/browser.php');

    expect($browser)->toContain("const TEMP_ZIP_PREFIX = 'kcf-download-';")
        ->and($browser)->toContain("preg_match('/^' . self::TEMP_ZIP_PREFIX . '[0-9a-f]{32}\.zip\$/', basename(\$file))")
        ->and(substr_count($browser, '$this->newTempZipPath()'))->toBe(3)
        ->and($browser)->not->toContain('md5(time() . session_id())');
});

it('does not let a media browser folder rename replace an existing destination', function () {
    $browser = fmAclSource('manager/media/browser/mcpuk/core/browser.php');
    $start = strpos($browser, 'function act_renameDir');
    $body = substr($browser, $start, strpos($browser, 'function act_deleteDir') - $start);

    expect(strpos($body, 'file_exists($newPath)'))->toBeLessThan(strpos($body, '@rename($dir, $newPath)'));
});

it('makes a copy as restricted as its original in both file managers', function () {
    $dynamic = fmAclSource('manager/actions/files.dynamic.php');
    $browser = fmAclSource('manager/media/browser/mcpuk/core/browser.php');

    // asked before anything is copied, and refused when no single set of groups can hold the copy
    $ask = strpos($dynamic, 'FileManagerAccess::carriedRestrictions(');
    expect($ask)->not->toBeFalse()
        ->and($ask)->toBeLessThan(strpos($dynamic, 'fileManagerCopyToNewFile($filemanager_path, $filename, $newpath)'))
        ->and($dynamic)->toContain('if ($carried === null) {')
        ->and($dynamic)->toContain('FileManagerAccess::replaceRestrictions(fileManagerAclKey($newRelative), $carried);');

    $ask = strpos($browser, 'FileManagerAccess::carriedRestrictions(', strpos($browser, 'function act_cp_cbd'));
    expect($ask)->toBeLessThan(strpos($browser, '$this->copyExclusive($path, "$dir/$base")'))
        ->and($browser)->not->toContain('copyRestrictions(')
        ->and($browser)->not->toContain('effectiveGroupIdsOf(');
});

it('does not give a replaced upload the groups of its folder', function () {
    $functions = fmAclSource('core/functions/actions/files.php');
    $body = substr($functions, strpos($functions, 'function fileupload'));

    expect($body)->toContain('$replacing = file_exists($targetFile);')
        ->and($body)->toContain('if (!$replacing && !empty($dirGroupIds)) {');
});

it('keeps the groups of the folder a moved file leaves in the media browser', function () {
    $browser = fmAclSource('manager/media/browser/mcpuk/core/browser.php');
    $start = strpos($browser, 'function act_mv_cbd');
    $body = substr($browser, $start, strpos($browser, 'function ', $start + 30) - $start);

    $read = strpos($body, 'FileManagerAccess::carriedRestrictions(');
    expect($read)->not->toBeFalse()
        ->and($read)->toBeLessThan(strpos($body, '@rename($path'))
        ->and($body)->toContain('$carriedGroups === null || !$this->canModifyExisting($path)')
        ->and(strpos($body, '$this->moveFileGroups($oldKey'))->toBeLessThan(strpos($body, 'FileManagerAccess::replaceRestrictions('));
});

it('keeps the media browser out of the folders the file manager keeps closed', function () {
    $browser = fmAclSource('manager/media/browser/mcpuk/core/browser.php');

    expect($browser)->toContain('!fileManagerPathContainsLink($this->typeDir, $absPath)')
        ->and($browser)->toContain('&& $this->isInsideTypeRoot($absPath)')
        ->and($browser)->toContain('&& !$this->isInProtectedFolder($absPath);')
        ->and($browser)->toContain('$this->protectedPaths = fileManagerProtectedPaths();')
        ->and($browser)->toContain('fileManagerPathIsProtected(fileManagerCanonicalCase($absPath), $this->protectedPaths)');
});

it('resolves the classic manager folder and upload directory through the link-safe resolver', function () {
    $dynamic = fmAclSource('manager/actions/files.dynamic.php');
    $functions = fmAclSource('core/functions/actions/files.php');
    $upload = substr($functions, strpos($functions, 'function fileupload'));

    expect($dynamic)->toContain('$resolvedCurrentPath = fileManagerResolvePath($filemanager_path, $requested_path);')
        ->and($upload)->toContain('$resolvedStartPath = fileManagerResolvePath($filemanager_path, $requested_path);')
        ->and($functions)->toContain('function fileManagerPathContainsLink($root, $path)');

    foreach (['function textsave', 'function delete_file'] as $function) {
        $body = substr($functions, strpos($functions, $function), 1800);
        expect($body)->toContain('fileManagerPathContainsLink($filemanager_path, $requested)');
    }
});

it('applies the executable name rule of the file manager to every media browser name', function () {
    $uploader = fmAclSource('manager/media/browser/mcpuk/core/uploader.php');
    $browser = fmAclSource('manager/media/browser/mcpuk/core/browser.php');
    $start = strpos($uploader, 'function validateFilename');

    expect(substr($uploader, $start, 600))->toContain('fileManagerIsExecutableName($name) && !$this->mayAddExecutableFiles()')
        ->and($uploader)->toContain('return fileManagerMayRunCode();');

    // and to the name an upload ends up with, not only the one the client sent
    $final = strpos($browser, 'validateFilename(basename($target), $this->type)');
    expect($final)->not->toBeFalse()
        ->and($final)->toBeLessThan(strpos($browser, '@move_uploaded_file'));
});

it('holds the media browser to the top-level rule of the file manager when it changes an existing entry', function () {
    $browser = fmAclSource('manager/media/browser/mcpuk/core/browser.php');

    expect($browser)->toContain('fileManagerCanModifyExistingPath($relative)');
    foreach (['act_rename', 'act_delete', 'act_rm_cbd', 'act_renameDir', 'act_deleteDir', 'act_mv_cbd'] as $act) {
        $start = strpos($browser, 'function ' . $act . '(');
        $body = substr($browser, $start, strpos($browser, "\n    }\n", $start) - $start);
        expect($body)->toContain('canModifyExisting(');
    }
});

it('keeps the groups of the target when a link to it is deleted in the media browser', function () {
    $browser = fmAclSource('manager/media/browser/mcpuk/core/browser.php');

    foreach (['act_delete', 'act_rm_cbd', 'act_deleteDir'] as $act) {
        $start = strpos($browser, 'function ' . $act . '(');
        $body = substr($browser, $start, strpos($browser, "\n    }\n", $start) - $start);
        expect($body)->toContain('$wasLink = fileManagerIsLink(');
        expect(strpos($body, '$wasLink = '))->toBeLessThan(strpos($body, '$this->forgetFileGroups('));
    }
});

it('logs every refusal of the classic file manager, not only the ones that return a message', function () {
    $dynamic = fmAclSource('manager/actions/files.dynamic.php');

    // no refusal is printed without being logged
    expect($dynamic)->not->toContain('<b>\' . $_lang[\'files_access_denied\']')
        ->and(substr_count($dynamic, 'fileManagerLogDenied();'))->toBeGreaterThanOrEqual(3)
        ->and($dynamic)->toContain("fileManagerLogDenied(null, null, 'protected folder');")
        ->and(substr_count($dynamic, 'echo fileManagerDenied();'))->toBeGreaterThanOrEqual(13);
});

it('logs the refusals of the media browser with the folder and file asked for', function () {
    $browser = fmAclSource('manager/media/browser/mcpuk/core/browser.php');
    $uploader = fmAclSource('manager/media/browser/mcpuk/core/uploader.php');

    // each place that refuses writing to a folder logs first
    expect(preg_match_all('/\$this->logDenied\(\'folder permissions\'\);\s+(?:\$this->errorMsg|\$error\[\]|\$response\[\'message\'\])/', $browser))->toBeGreaterThanOrEqual(14)
        ->and($uploader)->toContain('fileManagerLogDenied(\'media browser: \' . ($this->action ?? \'browse\'), $path, $reason);')
        ->and($uploader)->toContain('$this->logDenied(\'executable name\'');
});

it('copies and moves in the media browser only to a destination nothing occupies, a dangling link included', function () {
    $browser = fmAclSource('manager/media/browser/mcpuk/core/browser.php');

    expect($browser)->not->toContain('@copy(')
        ->and($browser)->not->toContain('file_exists("$dir/$base")')
        ->and(substr_count($browser, '$this->isTaken("$dir/$base")'))->toBe(2)
        ->and($browser)->toContain("'xb'");
});

it('does not list links of the classic file manager nor serve a thumbnail through one', function () {
    $functions = fmAclSource('core/functions/actions/files.php');
    $browser = fmAclSource('manager/media/browser/mcpuk/core/browser.php');
    $ls = substr($functions, strpos($functions, '// first, get info'), 700);
    $thumb = substr($browser, strpos($browser, 'function act_thumb'), 1500);

    expect($ls)->toContain('if (fileManagerIsLink($newpath)) {')
        ->and(strpos($ls, 'fileManagerIsLink($newpath)'))->toBeLessThan(strpos($ls, 'fileManagerIsAccessible('))
        ->and($thumb)->toContain('!$this->isSafeThumbPath($file)')
        ->and(strpos($thumb, 'isSafeThumbPath($file)'))->toBeLessThan(strpos($thumb, 'is_file($file)'));
});

it('never writes, renames or empties the thumbnail cache through a link', function () {
    $browser = fmAclSource('manager/media/browser/mcpuk/core/browser.php');
    $uploader = fmAclSource('manager/media/browser/mcpuk/core/uploader.php');

    expect($uploader)->toContain('!fileManagerPathContainsLink($this->config')
        ->and(substr($uploader, strpos($uploader, 'function makeThumb'), 500))->toContain('isSafeThumbPath(')
        ->and(substr_count($browser, 'isSafeThumbPath('))->toBeGreaterThanOrEqual(9)
        ->and($browser)->toContain('is_dir($thumbDir) && $this->isSafeThumbPath($thumbDir)');
});

it('does not let stale group rows at a copy or rename destination widen the entry', function () {
    $access = fmAclSource('core/src/Support/FileManagerAccess.php');
    $browser = fmAclSource('manager/media/browser/mcpuk/core/browser.php');
    $move = substr($access, strpos($access, 'function moveRestrictions'), 900);

    expect(strpos($move, 'self::forgetRestrictions($newKey)'))->toBeLessThan(strpos($move, 'subtreeRows($oldKey)'))
        ->and($browser)->not->toContain('FileManagerAccess::addRestrictions(')
        ->and($browser)->not->toContain('file_exists($newName)')
        ->and($browser)->not->toContain('file_exists($newPath)');
});

it('starts the media browser only with a thumbnail cache that no link leads out of', function () {
    $browser = fmAclSource('manager/media/browser/mcpuk/core/browser.php');
    $init = strpos($browser, "fileManagerPathContainsLink(\$this->config['uploadDir'], \"\$thumbsDir/{\$this->type}\")");

    expect($init)->not->toBeFalse()
        ->and($init)->toBeLessThan(strpos($browser, '!is_dir($thumbsDir)'))
        ->and($browser)->toContain('if (!$this->isSafeThumbPath($file) || fileManagerPathContainsLink($this->typeDir, $original))');
});

it('refuses Windows device names as file names in both file managers', function () {
    $functions = fmAclSource('core/functions/actions/files.php');
    $uploader = fmAclSource('manager/media/browser/mcpuk/core/uploader.php');

    expect(substr($functions, strpos($functions, 'function checkExtension'), 400))->toContain('fileManagerIsReservedDeviceName($path)')
        ->and(substr($uploader, strpos($uploader, 'function validateFilename'), 300))->toContain('fileManagerIsReservedDeviceName($name)');
});

it('refuses device names in folder names and in every media browser request name', function () {
    $dynamic = fmAclSource('manager/actions/files.dynamic.php');
    $browser = fmAclSource('manager/media/browser/mcpuk/core/browser.php');
    $uploader = fmAclSource('manager/media/browser/mcpuk/core/uploader.php');

    expect($dynamic)->toContain('elseif (fileManagerRefuseReservedName($newDirname))')
        ->and(strpos($browser, '$this->refuseReservedRequestNames();'))->toBeLessThan(strpos($browser, 'if ($this->config[\'disabled\'])'))
        ->and(substr($uploader, strpos($uploader, 'function checkInputDir'), 500))->toContain('fileManagerRefuseReservedName($dir)');
});
