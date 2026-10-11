<?php

$mcpukCore = dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/core';
if (!class_exists('uploader', false)) {
    require_once $mcpukCore . '/uploader.php';
}
if (!class_exists('browser', false)) {
    require_once $mcpukCore . '/browser.php';
}

// the file groups root is the site root; here it is set directly instead of read from evo()
final class MediaBrowserContainmentHarness extends browser
{
    public string $aclRootForTest = '';

    protected function getFileGroupsRelPath(string $absPath): string
    {
        return \EvolutionCMS\Support\FileManagerAccess::getRelativePath($this->aclRootForTest, $absPath);
    }
}

function mediaBrowserForTypeDir(string $typeDir): browser
{
    $browser = (new ReflectionClass(MediaBrowserContainmentHarness::class))->newInstanceWithoutConstructor();
    // as on a site: file groups are keyed from the site root, one level above the upload folder
    $browser->aclRootForTest = dirname($typeDir, 2);
    $property = new ReflectionProperty(uploader::class, 'typeDir');
    $property->setAccessible(true);
    $property->setValue($browser, $typeDir);
    // nothing is closed by permission here; fileManagerProtectedPaths() needs a running CMS
    $protected = new ReflectionProperty(browser::class, 'protectedPaths');
    $protected->setAccessible(true);
    $protected->setValue($browser, []);

    return $browser;
}

function mediaBrowserIsInside(browser $browser, string $path): bool
{
    $method = new ReflectionMethod(browser::class, 'isInsideTypeDir');
    $method->setAccessible(true);

    return $method->invoke($browser, $path);
}

beforeEach(function () {
    $tmp = sys_get_temp_dir() . '/evo-kcf-' . bin2hex(random_bytes(6));
    mkdir($tmp . '/images/team', 0777, true);
    mkdir($tmp . '/images-old', 0777, true);
    mkdir($tmp . '/outside', 0777, true);
    file_put_contents($tmp . '/outside/secret.txt', 'secret');
    $this->tmp = str_replace('\\', '/', realpath($tmp));
    $this->browser = mediaBrowserForTypeDir($this->tmp . '/images');
});

afterEach(function () {
    foreach (['images/link', 'images/secret.txt', 'images/dangling', 'images/team-link', 'images/internal-secret.txt'] as $link) {
        if (is_link($this->tmp . '/' . $link)) {
            @unlink($this->tmp . '/' . $link) || @rmdir($this->tmp . '/' . $link);
        }
    }
    @unlink($this->tmp . '/outside/secret.txt');
    @unlink($this->tmp . '/images/team/secret.txt');
    foreach (['images/team', 'images', 'images-old', 'outside', ''] as $dir) {
        @rmdir($this->tmp . '/' . $dir);
    }
});

function mediaBrowserSymlink(string $target, string $link): void
{
    if (!function_exists('symlink') || !@symlink($target, $link)) {
        test()->markTestSkipped('symlinks are not available here');
    }
}

it('accepts folders and new entries inside the type folder', function () {
    expect(mediaBrowserIsInside($this->browser, $this->tmp . '/images'))->toBeTrue()
        ->and(mediaBrowserIsInside($this->browser, $this->tmp . '/images/team'))->toBeTrue()
        ->and(mediaBrowserIsInside($this->browser, $this->tmp . '/images/team/new.png'))->toBeTrue()
        ->and(mediaBrowserIsInside($this->browser, $this->tmp . '/images/team/just-stop..png'))->toBeTrue();
});

it('rejects a sibling folder that only shares the name prefix', function () {
    expect(mediaBrowserIsInside($this->browser, $this->tmp . '/images-old'))->toBeFalse()
        ->and(mediaBrowserIsInside($this->browser, $this->tmp . '/images/../outside/secret.txt'))->toBeFalse();
});

it('rejects a symlinked folder that points outside the type folder', function () {
    mediaBrowserSymlink($this->tmp . '/outside', $this->tmp . '/images/link');

    expect(mediaBrowserIsInside($this->browser, $this->tmp . '/images/link'))->toBeFalse()
        ->and(mediaBrowserIsInside($this->browser, $this->tmp . '/images/link/secret.txt'))->toBeFalse()
        ->and(mediaBrowserIsInside($this->browser, $this->tmp . '/images/link/new.png'))->toBeFalse();
});

it('rejects a symlinked file and a dangling symlink', function () {
    mediaBrowserSymlink($this->tmp . '/outside/secret.txt', $this->tmp . '/images/secret.txt');
    mediaBrowserSymlink($this->tmp . '/outside/missing.txt', $this->tmp . '/images/dangling');

    expect(mediaBrowserIsInside($this->browser, $this->tmp . '/images/secret.txt'))->toBeFalse()
        ->and(mediaBrowserIsInside($this->browser, $this->tmp . '/images/dangling'))->toBeFalse();
});

it('rejects internal symlinks so their ACL path cannot be replaced by the target path', function () {
    file_put_contents($this->tmp . '/images/team/secret.txt', 'secret');
    mediaBrowserSymlink($this->tmp . '/images/team', $this->tmp . '/images/team-link');
    mediaBrowserSymlink($this->tmp . '/images/team/secret.txt', $this->tmp . '/images/internal-secret.txt');

    expect(mediaBrowserIsInside($this->browser, $this->tmp . '/images/team-link'))->toBeFalse()
        ->and(mediaBrowserIsInside($this->browser, $this->tmp . '/images/team-link/secret.txt'))->toBeFalse()
        ->and(mediaBrowserIsInside($this->browser, $this->tmp . '/images/internal-secret.txt'))->toBeFalse();
});

it('works when the site root is reached through a symlink, like public_html', function () {
    mediaBrowserSymlink($this->tmp, $this->tmp . '-public_html');
    try {
        $browser = mediaBrowserForTypeDir($this->tmp . '-public_html/images');

        expect(mediaBrowserIsInside($browser, $this->tmp . '-public_html/images/team'))->toBeTrue()
            ->and(mediaBrowserIsInside($browser, $this->tmp . '/images/team'))->toBeTrue()
            ->and(mediaBrowserIsInside($browser, $this->tmp . '-public_html/images/team/new.png'))->toBeTrue()
            ->and(mediaBrowserIsInside($browser, $this->tmp . '-public_html/outside/secret.txt'))->toBeFalse();
    } finally {
        @unlink($this->tmp . '-public_html') || @rmdir($this->tmp . '-public_html');
    }
});

it('works when the type folder itself is a symlink to a shared folder', function () {
    mkdir($this->tmp . '/shared/images/team', 0777, true);
    mediaBrowserSymlink($this->tmp . '/shared/images', $this->tmp . '/images-shared');
    try {
        $browser = mediaBrowserForTypeDir($this->tmp . '/images-shared');

        expect(mediaBrowserIsInside($browser, $this->tmp . '/images-shared/team'))->toBeTrue()
            ->and(mediaBrowserIsInside($browser, $this->tmp . '/images-shared/team/new.png'))->toBeTrue()
            ->and(mediaBrowserIsInside($browser, $this->tmp . '/images/team'))->toBeFalse();
    } finally {
        @unlink($this->tmp . '/images-shared') || @rmdir($this->tmp . '/images-shared');
        @rmdir($this->tmp . '/shared/images/team');
        @rmdir($this->tmp . '/shared/images');
        @rmdir($this->tmp . '/shared');
    }
});

it('reports an ordinary folder below the type folder as writable', function () {
    require_once dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/lib/helper_path.php';
    require_once dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/lib/helper_dir.php';
    foreach (['config' => ['uploadDir' => $this->tmp], 'session' => ['dir' => 'images']] as $name => $value) {
        $property = new ReflectionProperty(uploader::class, $name);
        $property->setAccessible(true);
        $property->setValue($this->browser, $value);
    }
    $method = new ReflectionMethod(browser::class, 'getDirInfo');
    $method->setAccessible(true);
    $previousSession = $_SESSION ?? null;
    // an administrator skips the file groups, leaving only the path checks
    $_SESSION = ['mgrRole' => 1];
    try {
        $team = $method->invoke($this->browser, $this->tmp . '/images/team');
        $root = $method->invoke($this->browser, $this->tmp . '/images');
    } finally {
        $_SESSION = $previousSession;
    }

    expect($team['writable'])->toBeTrue()
        ->and($team['removable'])->toBeTrue()
        ->and($root['removable'])->toBeFalse();
});

it('routes every folder and entry check through the containment check', function () {
    $source = file_get_contents(dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/core/browser.php');

    foreach (['postDir', 'getDir', 'isPathAccessible', 'filterAccessiblePaths', 'isWriteAllowed', 'subtreeAccessFilter'] as $method) {
        $start = strpos($source, 'protected function ' . $method . '(');
        expect($start)->not->toBeFalse();
        expect(substr($source, $start, 700))->toContain('isInsideTypeDir');
    }
});
