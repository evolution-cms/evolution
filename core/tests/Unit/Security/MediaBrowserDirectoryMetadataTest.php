<?php

$mcpukCore = dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/core';
require_once dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/lib/helper_path.php';
require_once dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/lib/helper_dir.php';
if (!class_exists('uploader', false)) {
    require_once $mcpukCore . '/uploader.php';
}
if (!class_exists('browser', false)) {
    require_once $mcpukCore . '/browser.php';
}

final class MediaBrowserDirectoryMetadataHarness extends browser
{
    public string $aclRootForTest = '';

    protected function getFileGroupsRelPath(string $absPath): string
    {
        return \EvolutionCMS\Support\FileBrowserAccess::getRelativePath($this->aclRootForTest, $absPath);
    }
}

function mediaBrowserDirectoryMetadataHarness(string $typeDir): browser
{
    $browser = (new ReflectionClass(MediaBrowserDirectoryMetadataHarness::class))->newInstanceWithoutConstructor();
    $browser->aclRootForTest = dirname($typeDir, 2);
    foreach ([
        'typeDir' => $typeDir,
        'type' => 'images',
        'config' => ['uploadDir' => dirname($typeDir)],
        'session' => ['dir' => 'images'],
        'dateTimeSmall' => 'Y-m-d H:i:s',
    ] as $name => $value) {
        $property = new ReflectionProperty(uploader::class, $name);
        $property->setAccessible(true);
        $property->setValue($browser, $value);
    }

    return $browser;
}

function mediaBrowserDirectoryMetadataMethod(string $name): ReflectionMethod
{
    $method = new ReflectionMethod(browser::class, $name);
    $method->setAccessible(true);

    return $method;
}

beforeEach(function () {
    $tmp = sys_get_temp_dir() . '/evo-kcf-meta-' . bin2hex(random_bytes(6));
    mkdir($tmp . '/images/team/child', 0777, true);
    mkdir($tmp . '/outside', 0777, true);
    $this->tmp = str_replace('\\', '/', realpath($tmp));
    $this->browser = mediaBrowserDirectoryMetadataHarness($this->tmp . '/images');
    $this->previousSession = $_SESSION ?? null;
    $_SESSION = ['mgrRole' => 1];
});

afterEach(function () {
    foreach ([
        '/images/team/direct.bin',
        '/images/team/child/nested.bin',
        '/outside/secret.bin',
    ] as $file) {
        @unlink($this->tmp . $file);
    }
    foreach ([
        '/images/team/child',
        '/images/team',
        '/images',
        '/outside',
        '',
    ] as $dir) {
        @rmdir($this->tmp . $dir);
    }
    $_SESSION = $this->previousSession;
});

it('includes a folder modification date in directory metadata', function () {
    $folder = $this->tmp . '/images/team';
    $originalMtime = filemtime($folder);
    $originalParentMtime = filemtime(dirname($folder));
    $info = mediaBrowserDirectoryMetadataMethod('getDirInfo')->invoke($this->browser, $folder);

    expect($info['mtime'])->toBe($originalMtime)
        ->and($info['date'])->toBe(date('Y-m-d H:i:s', $originalMtime))
        ->and(filemtime($folder))->toBe($originalMtime)
        ->and(filemtime(dirname($folder)))->toBe($originalParentMtime);
});

it('sums readable files below a folder when its size is requested', function () {
    file_put_contents($this->tmp . '/images/team/direct.bin', 'abc');
    file_put_contents($this->tmp . '/images/team/child/nested.bin', '12345');
    file_put_contents($this->tmp . '/outside/secret.bin', str_repeat('x', 100));

    $size = mediaBrowserDirectoryMetadataMethod('calculateDirectorySize')->invoke(
        $this->browser,
        $this->tmp . '/images/team'
    );

    expect($size)->toBe(8);
});
