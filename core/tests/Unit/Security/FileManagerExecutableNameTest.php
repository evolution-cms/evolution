<?php

require_once dirname(__DIR__, 3) . '/functions/actions/files.php';

it('flags names the web server would run or reconfigure', function (string $name) {
    expect(fileManagerIsExecutableName($name))->toBeTrue();
})->with(['shell.php', 'shell.PHP.jpg', 'a/b/x.phtml', '.htaccess', '.user.ini', 'x.phar.png', 'dir\x.php5']);

it('accepts ordinary files', function (string $name) {
    expect(fileManagerIsExecutableName($name))->toBeFalse();
})->with(['photo.jpg', 'notes.txt', 'archive.tar.gz', 'php-logo.png', 'readme']);

it('recognises Windows device names whatever the extension, case or trailing blanks', function () {
    foreach (['CON', 'con.jpg', 'Nul.tar.gz', 'aux .txt', 'COM1.png', 'lpt9', 'CONIN$.txt', 'sub/dir/PRN.gif'] as $name) {
        expect(fileManagerIsReservedDeviceName($name))->toBeTrue($name);
    }
    foreach (['console.jpg', 'com10.jpg', 'com.jpg', 'my.con.jpg', 'null.png', 'photo.jpg', 'lpt.txt'] as $name) {
        expect(fileManagerIsReservedDeviceName($name))->toBeFalse($name);
    }
});

it('refuses a path with a device name in any segment before the file system is asked about it', function () {
    foreach (['CON', 'a/NUL', 'a\COM1.txt/b', '/files/aux/x'] as $path) {
        expect(fileManagerRefuseReservedName($path))->toBeTrue($path);
    }
    foreach (['', 'files/images', 'console/x.jpg'] as $path) {
        expect(fileManagerRefuseReservedName($path))->toBeFalse($path);
    }

    $root = sys_get_temp_dir();
    expect(fileManagerResolvePath($root, 'NUL'))->toBeNull()
        ->and(fileManagerPathContainsLink($root, $root . '/sub/CON'))->toBeTrue()
        ->and(fileManagerIsSafeWriteTarget($root, $root . '/PRN'))->toBeFalse();
});
