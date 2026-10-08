<?php

require_once dirname(__DIR__, 3) . '/functions/actions/files.php';

it('flags names the web server would run or reconfigure', function (string $name) {
    expect(fileManagerIsExecutableName($name))->toBeTrue();
})->with(['shell.php', 'shell.PHP.jpg', 'a/b/x.phtml', '.htaccess', '.user.ini', 'x.phar.png', 'dir\x.php5']);

it('accepts ordinary files', function (string $name) {
    expect(fileManagerIsExecutableName($name))->toBeFalse();
})->with(['photo.jpg', 'notes.txt', 'archive.tar.gz', 'php-logo.png', 'readme']);
