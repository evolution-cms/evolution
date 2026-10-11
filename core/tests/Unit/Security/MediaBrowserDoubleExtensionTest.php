<?php

$mcpukCore = dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/core';
if (!class_exists('text', false)) {
    require_once dirname($mcpukCore) . '/lib/helper_text.php';
}
if (!class_exists('uploader', false)) {
    require_once $mcpukCore . '/uploader.php';
}

class UploaderWithoutCodePermission extends uploader
{
    public bool $mayRunCode = false;

    protected function mayAddExecutableFiles()
    {
        return $this->mayRunCode;
    }
}

function uploaderForNames(string $types, string $denied): uploader
{
    // the user here cannot store PHP, which is what the extension lists are about
    $uploader = (new ReflectionClass(UploaderWithoutCodePermission::class))->newInstanceWithoutConstructor();
    foreach (['types' => ['images' => $types], 'config' => ['deniedExts' => $denied]] as $name => $value) {
        $property = new ReflectionProperty(uploader::class, $name);
        $property->setAccessible(true);
        $property->setValue($uploader, $value);
    }

    return $uploader;
}

function uploaderAllowsName(uploader $uploader, string $name): bool
{
    $method = new ReflectionMethod(uploader::class, 'validateFilename');
    $method->setAccessible(true);

    return $method->invoke($uploader, $name, 'images');
}

it('rejects executable extensions hidden before the final extension', function (string $name) {
    $uploader = uploaderForNames('jpg png gif', 'exe php phtml');

    expect(uploaderAllowsName($uploader, $name))->toBeFalse();
})->with(['shell.php.jpg', 'shell.PHP.jpg', 'a.b.phtml.png', 'shell.php.', 'dir\\shell.php.gif']);

it('accepts ordinary image names', function (string $name) {
    $uploader = uploaderForNames('jpg png gif', 'exe php phtml');

    expect(uploaderAllowsName($uploader, $name))->toBeTrue();
})->with(['photo.jpg', 'my.holiday.photo.PNG', 'php-logo.gif']);

it('still rejects a denied or unlisted final extension', function (string $name) {
    $uploader = uploaderForNames('jpg png gif', 'exe php phtml');

    expect(uploaderAllowsName($uploader, $name))->toBeFalse();
})->with(['shell.php', 'doc.txt']);

it('refuses executable names the lists do not name unless the user can run code', function (string $name, bool $mayRunCode, bool $expected) {
    $uploader = uploaderForNames('jpg png gif php5 htaccess', 'exe php');
    $uploader->mayRunCode = $mayRunCode;

    expect(uploaderAllowsName($uploader, $name))->toBe($expected);
})->with([
    'php5 without the permission' => ['shell.php5', false, false],
    'phar behind an image name' => ['x.phar.png', false, false],
    'php5 with the permission' => ['shell.php5', true, true],
    'a plain image either way' => ['photo.jpg', false, true],
]);
