<?php

require_once dirname(__DIR__, 3) . '/functions/actions/files.php';

$mcpukRoot = dirname(__DIR__, 4) . '/manager/media/browser/mcpuk';
require_once $mcpukRoot . '/lib/helper_path.php';
require_once $mcpukRoot . '/lib/helper_dir.php';
require_once $mcpukRoot . '/lib/helper_file.php';
if (!class_exists('uploader', false)) {
    require_once $mcpukRoot . '/core/uploader.php';
}
if (!class_exists('browser', false)) {
    require_once $mcpukRoot . '/core/browser.php';
}

function fmLinksRemove(string $path): void
{
    if (fileManagerIsLink($path)) {
        @unlink($path) || @rmdir($path);

        return;
    }
    if (is_file($path)) {
        @unlink($path);

        return;
    }
    if (!is_dir($path)) {
        return;
    }
    foreach (scandir($path) as $item) {
        if ($item !== '.' && $item !== '..') {
            fmLinksRemove($path . '/' . $item);
        }
    }
    @rmdir($path);
}

function fmLinksSymlink(string $target, string $link): void
{
    if (!function_exists('symlink') || !@symlink($target, $link)) {
        test()->markTestSkipped('symlinks are not available here');
    }
}

// a directory junction: Windows only, and unlike a symlink it needs no special privilege
function fmLinksJunction(string $target, string $link): void
{
    if (PHP_OS_FAMILY !== 'Windows') {
        test()->markTestSkipped('junctions exist on Windows only');
    }
    try {
        $output = evoRunCommand('mklink /J ' . escapeshellarg(str_replace('/', '\\', $link)) . ' '
            . escapeshellarg(str_replace('/', '\\', $target)) . ' 2>&1', $code);
    } catch (\Exception $e) {
        test()->markTestSkipped('no way to run mklink here');
    }
    if ($code !== 0) {
        test()->markTestSkipped('junctions are not available here: ' . $output);
    }
}

function fmLinksBrowser(string $typeDir): browser
{
    $browser = (new ReflectionClass(browser::class))->newInstanceWithoutConstructor();
    $property = new ReflectionProperty(uploader::class, 'typeDir');
    $property->setAccessible(true);
    $property->setValue($browser, $typeDir);

    return $browser;
}

beforeEach(function () {
    $tmp = sys_get_temp_dir() . '/evo-fm-links-' . bin2hex(random_bytes(6));
    mkdir($tmp . '/root/doomed/inner', 0777, true);
    mkdir($tmp . '/outside', 0777, true);
    $this->tmp = str_replace('\\', '/', realpath($tmp));
    $this->root = $this->tmp . '/root';
    file_put_contents($this->tmp . '/outside/keep.txt', 'outside');
    file_put_contents($this->root . '/doomed/inner/file.txt', 'inside');
    file_put_contents($this->root . '/doomed/.hidden', 'inside');
});

afterEach(function () {
    fmLinksRemove($this->tmp);
});

it('deletes a folder tree without following a nested folder symlink', function () {
    fmLinksSymlink($this->tmp . '/outside', $this->root . '/doomed/inner/link');

    expect(rrmdir($this->root . '/doomed'))->toBeTrue()
        ->and(file_exists($this->root . '/doomed'))->toBeFalse()
        ->and(file_get_contents($this->tmp . '/outside/keep.txt'))->toBe('outside');
});

it('deletes a nested file symlink without touching its target', function () {
    fmLinksSymlink($this->tmp . '/outside/keep.txt', $this->root . '/doomed/keep.txt');

    expect(rrmdir($this->root . '/doomed'))->toBeTrue()
        ->and(file_get_contents($this->tmp . '/outside/keep.txt'))->toBe('outside');
});

it('removes only the link when the folder to delete is itself a symlink', function () {
    fmLinksSymlink($this->tmp . '/outside', $this->root . '/linked');

    expect(rrmdir($this->root . '/linked'))->toBeTrue()
        ->and(is_link($this->root . '/linked'))->toBeFalse()
        ->and(file_get_contents($this->tmp . '/outside/keep.txt'))->toBe('outside');
});

it('creates a new empty file', function () {
    expect(fileManagerCreateFile($this->root, $this->root . '/new.txt'))->toBeTrue()
        ->and(file_get_contents($this->root . '/new.txt'))->toBe('');
});

it('refuses to create a file over an existing one', function () {
    expect(fileManagerCreateFile($this->root, $this->root . '/doomed/inner/file.txt'))->toBeFalse()
        ->and(file_get_contents($this->root . '/doomed/inner/file.txt'))->toBe('inside');
});

it('refuses to create a file through a symlink, dangling or not', function () {
    fmLinksSymlink($this->tmp . '/outside/keep.txt', $this->root . '/keep.txt');
    fmLinksSymlink($this->tmp . '/outside/shell.php', $this->root . '/shell.php');

    expect(fileManagerCreateFile($this->root, $this->root . '/keep.txt'))->toBeFalse()
        ->and(fileManagerCreateFile($this->root, $this->root . '/shell.php', '<?php'))->toBeFalse()
        ->and(file_get_contents($this->tmp . '/outside/keep.txt'))->toBe('outside')
        ->and(file_exists($this->tmp . '/outside/shell.php'))->toBeFalse();
});

it('refuses to create a file outside the root', function () {
    expect(fileManagerCreateFile($this->root, $this->tmp . '/outside/new.txt'))->toBeFalse()
        ->and(file_exists($this->tmp . '/outside/new.txt'))->toBeFalse();
});

it('duplicates a file under a new name', function () {
    $source = $this->root . '/doomed/inner/file.txt';

    expect(fileManagerCopyToNewFile($this->root, $source, $this->root . '/doomed/inner/copy.txt'))->toBeTrue()
        ->and(file_get_contents($this->root . '/doomed/inner/copy.txt'))->toBe('inside');
});

it('refuses to duplicate over an existing file or through a symlink', function () {
    $source = $this->root . '/doomed/inner/file.txt';
    file_put_contents($this->root . '/existing.txt', 'existing');
    fmLinksSymlink($this->tmp . '/outside/keep.txt', $this->root . '/keep.txt');
    fmLinksSymlink($this->tmp . '/outside/shell.php', $this->root . '/shell.php');

    expect(fileManagerCopyToNewFile($this->root, $source, $this->root . '/existing.txt'))->toBeFalse()
        ->and(fileManagerCopyToNewFile($this->root, $source, $this->root . '/keep.txt'))->toBeFalse()
        ->and(fileManagerCopyToNewFile($this->root, $source, $this->root . '/shell.php'))->toBeFalse()
        ->and(file_get_contents($this->root . '/existing.txt'))->toBe('existing')
        ->and(file_get_contents($this->tmp . '/outside/keep.txt'))->toBe('outside')
        ->and(file_exists($this->tmp . '/outside/shell.php'))->toBeFalse();
});

it('treats an existing name or a symlinked folder name as no place for a new folder', function () {
    fmLinksSymlink($this->tmp . '/outside', $this->root . '/linked');

    expect(fileManagerIsNewWriteTarget($this->root, $this->root . '/fresh'))->toBeTrue()
        ->and(fileManagerIsNewWriteTarget($this->root, $this->root . '/doomed'))->toBeFalse()
        ->and(fileManagerIsNewWriteTarget($this->root, $this->root . '/linked'))->toBeFalse()
        ->and(fileManagerIsNewWriteTarget($this->root, $this->root . '/'))->toBeFalse()
        ->and(fileManagerIsNewWriteTarget($this->root, $this->root . '/..'))->toBeFalse();
});

it('routes the classic manager create and duplicate actions through the new-target helpers', function () {
    $source = file_get_contents(dirname(__DIR__, 4) . '/manager/actions/files.dynamic.php');

    expect($source)->toContain('fileManagerIsNewWriteTarget($filemanager_path, $newdir)')
        ->and($source)->toContain('fileManagerCreateFile($filemanager_path, $startpath . \'/\' . $filename)')
        ->and($source)->toContain('fileManagerCopyToNewFile($filemanager_path, $filename, $newpath)')
        ->and($source)->not->toContain('mkdirs($newdir')
        ->and($source)->not->toContain('file_put_contents($startpath')
        ->and($source)->not->toContain('copy($filename, $newpath)');
});

it('prunes a media folder without following a nested folder symlink', function () {
    fmLinksSymlink($this->tmp . '/outside', $this->root . '/doomed/inner/link');

    expect(dir::prune($this->root . '/doomed', false))->toBeTrue()
        ->and(file_exists($this->root . '/doomed'))->toBeFalse()
        ->and(file_get_contents($this->tmp . '/outside/keep.txt'))->toBe('outside');
});

it('prunes only the link when the media folder is itself a symlink', function () {
    fmLinksSymlink($this->tmp . '/outside', $this->root . '/linked');

    expect(dir::prune($this->root . '/linked'))->toBeTrue()
        ->and(is_link($this->root . '/linked'))->toBeFalse()
        ->and(file_get_contents($this->tmp . '/outside/keep.txt'))->toBe('outside');
});

it('does not pick a dangling symlink as a free upload name', function () {
    fmLinksSymlink($this->tmp . '/outside/shell.php', $this->root . '/upload.png');

    expect(basename(file::getInexistantFilename('upload.png', $this->root)))->toBe('upload(1).png');
});

it('accepts only a new name inside the type folder as an upload target', function () {
    fmLinksSymlink($this->tmp . '/outside/shell.php', $this->root . '/dangling.png');
    fmLinksSymlink($this->tmp . '/outside', $this->root . '/linked');
    $browser = fmLinksBrowser($this->root);
    $method = new ReflectionMethod(browser::class, 'isNewUploadTarget');
    $method->setAccessible(true);

    expect($method->invoke($browser, $this->root . '/new.png'))->toBeTrue()
        ->and($method->invoke($browser, $this->root . '/dangling.png'))->toBeFalse()
        ->and($method->invoke($browser, $this->root . '/linked/new.png'))->toBeFalse()
        ->and($method->invoke($browser, $this->root . '/doomed/inner/file.txt'))->toBeFalse()
        ->and($method->invoke($browser, $this->tmp . '/outside/new.png'))->toBeFalse();
});

it('checks the upload target before every write attempt', function () {
    $source = file_get_contents(dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/core/browser.php');
    $start = strpos($source, 'protected function moveUploadFile(');
    $body = substr($source, $start, strpos($source, 'protected function isNewUploadTarget(') - $start);

    expect(substr_count($body, '$this->isNewUploadTarget($target)'))->toBe(3);
});

it('tells links from plain entries, including dangling ones', function () {
    fmLinksSymlink($this->tmp . '/outside', $this->root . '/linked');
    fmLinksSymlink($this->tmp . '/outside/missing.txt', $this->root . '/dangling.txt');

    expect(fileManagerIsLink($this->root . '/doomed'))->toBeFalse()
        ->and(fileManagerIsLink($this->root . '/doomed/inner/file.txt'))->toBeFalse()
        ->and(fileManagerIsLink($this->root . '/not-there.txt'))->toBeFalse()
        ->and(fileManagerIsLink($this->root . '/linked'))->toBeTrue()
        ->and(fileManagerIsLink($this->root . '/dangling.txt'))->toBeTrue()
        ->and(dir::isLink($this->root . '/doomed'))->toBeFalse()
        ->and(dir::isLink($this->root . '/linked'))->toBeTrue()
        ->and(dir::isLink($this->root . '/dangling.txt'))->toBeTrue();
});

it('sees a Windows junction as a link, although is_link() does not', function () {
    fmLinksJunction($this->tmp . '/outside', $this->root . '/junction');

    expect(fileManagerIsLink($this->root . '/junction'))->toBeTrue()
        ->and(dir::isLink($this->root . '/junction'))->toBeTrue()
        ->and(fileManagerIsSafeWriteTarget($this->root, $this->root . '/junction/new.txt'))->toBeFalse()
        ->and(fileManagerCreateFile($this->root, $this->root . '/junction/new.txt'))->toBeFalse()
        ->and(file_exists($this->tmp . '/outside/new.txt'))->toBeFalse();
});

it('deletes a folder tree without following a nested junction', function () {
    fmLinksJunction($this->tmp . '/outside', $this->root . '/doomed/inner/junction');

    expect(rrmdir($this->root . '/doomed'))->toBeTrue()
        ->and(file_exists($this->root . '/doomed'))->toBeFalse()
        ->and(file_get_contents($this->tmp . '/outside/keep.txt'))->toBe('outside');
});

it('deletes a folder tree holding a dangling junction', function () {
    mkdir($this->tmp . '/gone');
    fmLinksJunction($this->tmp . '/gone', $this->root . '/doomed/inner/junction');
    rmdir($this->tmp . '/gone');

    expect(rrmdir($this->root . '/doomed'))->toBeTrue()
        ->and(file_exists($this->root . '/doomed'))->toBeFalse();
});

it('prunes a media folder without following a nested junction', function () {
    fmLinksJunction($this->tmp . '/outside', $this->root . '/doomed/inner/junction');

    expect(dir::prune($this->root . '/doomed', false))->toBeTrue()
        ->and(file_exists($this->root . '/doomed'))->toBeFalse()
        ->and(file_get_contents($this->tmp . '/outside/keep.txt'))->toBe('outside');
});

it('deletes a folder holding a symlink checked out as a plain file', function () {
    // git with core.symlinks=false (the Windows default) writes the link target as the file content
    file_put_contents($this->root . '/doomed/inner/link', '../../../outside');

    expect(rrmdir($this->root . '/doomed'))->toBeTrue()
        ->and(dir::prune($this->root, false))->toBeTrue()
        ->and(file_get_contents($this->tmp . '/outside/keep.txt'))->toBe('outside');
});

it('allows renaming to a new name only', function () {
    $source = $this->root . '/doomed/inner/file.txt';
    file_put_contents($this->root . '/doomed/inner/other.txt', 'other');
    fmLinksSymlink($this->tmp . '/outside/keep.txt', $this->root . '/doomed/inner/keep.txt');

    expect(fileManagerCanRenameTo($this->root, $source, $this->root . '/doomed/inner/renamed.txt'))->toBeTrue()
        ->and(fileManagerCanRenameTo($this->root, $source, $this->root . '/doomed/inner/other.txt'))->toBeFalse()
        ->and(fileManagerCanRenameTo($this->root, $source, $this->root . '/doomed/inner/keep.txt'))->toBeFalse()
        ->and(fileManagerCanRenameTo($this->root, $source, $this->tmp . '/outside/renamed.txt'))->toBeFalse();
});

it('allows a rename that only changes the case of the name', function () {
    $source = $this->root . '/doomed/inner/file.txt';

    // on Windows the new name finds the file itself; elsewhere it is simply free
    expect(fileManagerCanRenameTo($this->root, $source, $this->root . '/doomed/inner/File.txt'))->toBeTrue()
        ->and(fileManagerCanRenameTo($this->root, $source, $source))->toBeTrue();
});

it('refuses a case-only rename onto a different file on a case-sensitive file system', function () {
    file_put_contents($this->root . '/doomed/inner/File.txt', 'other');
    if (file_get_contents($this->root . '/doomed/inner/file.txt') !== 'inside') {
        test()->markTestSkipped('the file system here ignores case');
    }

    expect(fileManagerCanRenameTo($this->root, $this->root . '/doomed/inner/file.txt', $this->root . '/doomed/inner/File.txt'))
        ->toBeFalse();
});

it('routes the classic manager file rename through the rename check', function () {
    $source = file_get_contents(dirname(__DIR__, 4) . '/manager/actions/files.dynamic.php');

    expect($source)->toContain('fileManagerCanRenameTo($filemanager_path, $filename, $path . \'/\' . $newFilename)');
});

it('allows renaming a folder to a new name only', function () {
    $source = $this->root . '/doomed/inner';
    mkdir($this->root . '/doomed/empty');
    fmLinksSymlink($this->tmp . '/outside', $this->root . '/doomed/linked');

    expect(fileManagerCanRenameTo($this->root, $source, $this->root . '/doomed/renamed'))->toBeTrue()
        ->and(fileManagerCanRenameTo($this->root, $source, $this->root . '/doomed/Inner'))->toBeTrue()
        ->and(fileManagerCanRenameTo($this->root, $source, $this->root . '/doomed/empty'))->toBeFalse()
        ->and(fileManagerCanRenameTo($this->root, $source, $this->root . '/doomed/linked'))->toBeFalse()
        ->and(fileManagerCanRenameTo($this->root, $source, $this->root . '/doomed/'))->toBeFalse();
});

it('refuses renaming a folder onto a junction', function () {
    fmLinksJunction($this->tmp . '/outside', $this->root . '/doomed/junction');

    expect(fileManagerCanRenameTo($this->root, $this->root . '/doomed/inner', $this->root . '/doomed/junction'))->toBeFalse();
});

it('routes the classic manager folder rename through the rename check', function () {
    $source = file_get_contents(dirname(__DIR__, 4) . '/manager/actions/files.dynamic.php');

    expect($source)->toContain('fileManagerCanRenameTo($filemanager_path, $dirname, dirname($dirname) . \'/\' . $newDirname)');
});
