<?php

require_once dirname(__DIR__, 3) . '/functions/actions/files.php';

function fmContainmentTempDir(): string
{
    $dir = str_replace('\\', '/', sys_get_temp_dir()) . '/evo-fm-containment-' . bin2hex(random_bytes(6));
    mkdir($dir, 0777, true);

    return str_replace('\\', '/', realpath($dir));
}

function fmContainmentRemove(string $path): void
{
    if (is_link($path) || is_file($path)) {
        @unlink($path);

        return;
    }
    if (!is_dir($path)) {
        return;
    }
    foreach (scandir($path) as $item) {
        if ($item !== '.' && $item !== '..') {
            fmContainmentRemove($path . '/' . $item);
        }
    }
    // a directory symlink on Windows is removed with rmdir
    @rmdir($path);
}

function fmContainmentSymlink(string $target, string $link): void
{
    if (!function_exists('symlink') || !@symlink($target, $link)) {
        test()->markTestSkipped('symlinks are not available here');
    }
}

function fmContainmentZip(string $path, array $entries): void
{
    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($entries as $name => $content) {
        if (substr($name, -1) === '/') {
            $zip->addEmptyDir(rtrim($name, '/'));
        } else {
            $zip->addFromString($name, $content);
        }
    }
    $zip->close();
}

beforeEach(function () {
    $this->tmp = fmContainmentTempDir();
});

afterEach(function () {
    fmContainmentRemove($this->tmp);
});

it('treats only the protected folder and what is inside it as protected', function () {
    $protected = ['/var/www/site/assets/plugins'];

    expect(fileManagerPathIsProtected('/var/www/site/assets/plugins', $protected))->toBeTrue()
        ->and(fileManagerPathIsProtected('/var/www/site/assets/plugins/tinymce/plugin.php', $protected))->toBeTrue()
        ->and(fileManagerPathIsProtected('/var/www/site/assets/plugins-old/plugin.php', $protected))->toBeFalse()
        ->and(fileManagerPathIsProtected('/var/www/site/assets', $protected))->toBeFalse();
});

it('counts a folder that contains a protected folder as touching it', function () {
    $protected = ['/var/www/site/assets/plugins'];

    expect(fileManagerPathTouchesProtected('/var/www/site/assets', $protected))->toBeTrue()
        ->and(fileManagerPathTouchesProtected('/var/www/site/assets/plugins/x', $protected))->toBeTrue()
        ->and(fileManagerPathTouchesProtected('/var/www/site/assets/images', $protected))->toBeFalse();
});

it('accepts write targets below the root and rejects ones that leave it', function () {
    mkdir($this->tmp . '/root/sub', 0777, true);
    $root = $this->tmp . '/root';

    expect(fileManagerIsSafeWriteTarget($root, $root . '/sub/new/file.txt'))->toBeTrue()
        ->and(fileManagerIsSafeWriteTarget($root, $root . '/just-stop..png'))->toBeTrue()
        ->and(fileManagerIsSafeWriteTarget($root, $root))->toBeFalse()
        ->and(fileManagerIsSafeWriteTarget($root, $root . '/../outside.txt'))->toBeFalse()
        ->and(fileManagerIsSafeWriteTarget($root, $this->tmp . '/root-old/file.txt'))->toBeFalse();
});

it('rejects write targets that pass through a symlinked folder', function () {
    mkdir($this->tmp . '/root', 0777, true);
    mkdir($this->tmp . '/outside', 0777, true);
    fmContainmentSymlink($this->tmp . '/outside', $this->tmp . '/root/link');

    expect(fileManagerIsSafeWriteTarget($this->tmp . '/root', $this->tmp . '/root/link/new/file.txt'))->toBeFalse();
});

it('rejects a write target that is itself a symlink', function () {
    mkdir($this->tmp . '/root', 0777, true);
    file_put_contents($this->tmp . '/victim.txt', 'original');
    fmContainmentSymlink($this->tmp . '/victim.txt', $this->tmp . '/root/file.txt');

    expect(fileManagerIsSafeWriteTarget($this->tmp . '/root', $this->tmp . '/root/file.txt'))->toBeFalse();
});

it('extracts ordinary entries, including names with a double dot before the extension', function () {
    mkdir($this->tmp . '/dest', 0777, true);
    fmContainmentZip($this->tmp . '/a.zip', [
        'just-stop..png' => 'png',
        'folder/' => '',
        'folder/nested/note.txt' => 'note',
    ]);

    expect(fileManagerExtractZip($this->tmp . '/a.zip', $this->tmp . '/dest'))->toBeTrue()
        ->and(file_get_contents($this->tmp . '/dest/just-stop..png'))->toBe('png')
        ->and(is_dir($this->tmp . '/dest/folder'))->toBeTrue()
        ->and(file_get_contents($this->tmp . '/dest/folder/nested/note.txt'))->toBe('note');
});

it('decides each zip entry by its path segments, not by the characters in its name', function (string $entry, ?string $expected) {
    mkdir($this->tmp . '/dest', 0777, true);
    fmContainmentZip($this->tmp . '/a.zip', [$entry => 'data']);

    fileManagerExtractZip($this->tmp . '/a.zip', $this->tmp . '/dest');

    $written = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->tmp, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $item) {
        $path = substr(str_replace('\\', '/', $item->getPathname()), strlen($this->tmp) + 1);
        if ($path !== 'a.zip') {
            $written[] = $path;
        }
    }

    expect($written)->toBe($expected === null ? [] : [$expected]);
})->with([
    'double dot before the extension' => ['just-stop..png', 'dest/just-stop..png'],
    'double dot inside a folder' => ['photos/just-stop..png', 'dest/photos/just-stop..png'],
    'name starting with two dots' => ['..env.txt', 'dest/..env.txt'],
    'folder with a double dot' => ['v1..2/readme.txt', 'dest/v1..2/readme.txt'],
    'parent segment' => ['../escaped.txt', null],
    'nested parent segment' => ['a/../../escaped.txt', null],
    'backslash parent segment' => ['..\\escaped.txt', null],
    'absolute path' => ['/escaped.txt', null],
    'drive letter' => ['C:/escaped.txt', null],
    'current folder prefix' => ['./here.txt', 'dest/here.txt'],
    'doubled slash' => ['photos//pic.png', 'dest/photos/pic.png'],
]);

it('extracts normally when the site root is reached through a symlink, like public_html', function () {
    mkdir($this->tmp . '/srv/evo/assets/files', 0777, true);
    fmContainmentSymlink($this->tmp . '/srv/evo', $this->tmp . '/public_html');
    fmContainmentZip($this->tmp . '/a.zip', ['just-stop..png' => 'png', 'photos/pic.png' => 'png']);

    expect(fileManagerExtractZip($this->tmp . '/a.zip', $this->tmp . '/public_html/assets/files'))->toBeTrue()
        ->and(file_get_contents($this->tmp . '/srv/evo/assets/files/just-stop..png'))->toBe('png')
        ->and(file_get_contents($this->tmp . '/srv/evo/assets/files/photos/pic.png'))->toBe('png');
});

it('matches protected folders when the site root is reached through a symlink', function () {
    mkdir($this->tmp . '/srv/evo/assets/plugins', 0777, true);
    fmContainmentSymlink($this->tmp . '/srv/evo', $this->tmp . '/public_html');
    // both sides are canonical in the file manager: protected paths and $startpath go through realpath()
    $protected = [str_replace('\\', '/', realpath($this->tmp . '/public_html/assets/plugins'))];
    $startpath = str_replace('\\', '/', realpath($this->tmp . '/public_html/assets/plugins'));
    fmContainmentZip($this->tmp . '/a.zip', ['plugins/evil.php' => 'x', 'ok.txt' => 'ok']);

    fileManagerExtractZip($this->tmp . '/a.zip', $this->tmp . '/public_html/assets', $protected);

    expect(fileManagerPathIsProtected($startpath, $protected))->toBeTrue()
        ->and(file_exists($this->tmp . '/srv/evo/assets/plugins/evil.php'))->toBeFalse()
        ->and(file_get_contents($this->tmp . '/srv/evo/assets/ok.txt'))->toBe('ok');
});

it('skips entries that climb out of the destination', function () {
    mkdir($this->tmp . '/dest', 0777, true);
    fmContainmentZip($this->tmp . '/a.zip', [
        '../escaped.txt' => 'x',
        'folder/../../escaped2.txt' => 'x',
        'ok.txt' => 'ok',
    ]);

    fileManagerExtractZip($this->tmp . '/a.zip', $this->tmp . '/dest');

    expect(file_exists($this->tmp . '/escaped.txt'))->toBeFalse()
        ->and(file_exists($this->tmp . '/escaped2.txt'))->toBeFalse()
        ->and(file_get_contents($this->tmp . '/dest/ok.txt'))->toBe('ok');
});

it('skips entries that land in a protected folder below the destination', function () {
    mkdir($this->tmp . '/dest/plugins', 0777, true);
    fmContainmentZip($this->tmp . '/a.zip', [
        'plugins/evil.php' => '<?php echo 1;',
        'plugins/' => '',
        'images/ok.png' => 'png',
    ]);

    fileManagerExtractZip($this->tmp . '/a.zip', $this->tmp . '/dest', [$this->tmp . '/dest/plugins']);

    expect(file_exists($this->tmp . '/dest/plugins/evil.php'))->toBeFalse()
        ->and(file_get_contents($this->tmp . '/dest/images/ok.png'))->toBe('png');
});

it('does not follow a symlinked folder or file while extracting', function () {
    mkdir($this->tmp . '/dest', 0777, true);
    mkdir($this->tmp . '/outside', 0777, true);
    file_put_contents($this->tmp . '/victim.txt', 'original');
    fmContainmentSymlink($this->tmp . '/outside', $this->tmp . '/dest/link');
    fmContainmentSymlink($this->tmp . '/victim.txt', $this->tmp . '/dest/file.txt');
    fmContainmentZip($this->tmp . '/a.zip', [
        'link/new/escaped.txt' => 'x',
        'file.txt' => 'overwritten',
    ]);

    fileManagerExtractZip($this->tmp . '/a.zip', $this->tmp . '/dest');

    expect(file_exists($this->tmp . '/outside/new/escaped.txt'))->toBeFalse()
        ->and(file_get_contents($this->tmp . '/victim.txt'))->toBe('original');
});

it('checks protected folders in every file manager write before anything runs', function () {
    $functions = file_get_contents(dirname(__DIR__, 3) . '/functions/actions/files.php');
    $dynamic = file_get_contents(dirname(__DIR__, 4) . '/manager/actions/files.dynamic.php');

    foreach (['function fileupload', 'function textsave', 'function delete_file'] as $function) {
        $body = substr($functions, strpos($functions, $function), 2500);
        expect($body)->toContain('fileManagerProtectedPaths()');
    }

    $gate = strpos($dynamic, 'if (fileManagerPathIsProtected($startpath, $protected_path)) {');
    expect($gate)->not->toBeFalse()
        ->and($gate)->toBeLessThan(strpos($dynamic, 'echo textsave();'))
        ->and($gate)->toBeLessThan(strpos($dynamic, '$information = fileupload();'))
        ->and($dynamic)->toContain('$protected_path = fileManagerProtectedPaths();')
        ->and($dynamic)->toContain('fileManagerPathTouchesProtected($folder, $protected_path)')
        ->and($dynamic)->toContain('fileManagerPathTouchesProtected($dirname, $protected_path)')
        ->and($dynamic)->toContain('fileManagerExtractZip($zipTarget[\'path\'], $startpath, $protected_path)')
        ->and($dynamic)->not->toContain('function safe_unzip');
});
