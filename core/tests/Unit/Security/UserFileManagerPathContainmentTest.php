<?php

use EvolutionCMS\Support\FileManagerAccess;

/*
|--------------------------------------------------------------------------
| A per-user filemanager_path may only narrow the system root
|--------------------------------------------------------------------------
|
| filemanager_path is an ordinary user setting, merged over the site-wide value
| in Core::getUserSettings(). The classic file manager then browses, reads and
| writes under getConfig('filemanager_path') - the merged value - while it keys
| its group ACL on the site-wide root (configGlobal). So a per-user override that
| points outside the system root widens the file manager past the intended root.
| userRootAllowed() is the decision Core uses to drop such an override; it accepts
| only a user root that resolves and lies within the system root.
|
*/

test('a per-user root within the system root is allowed', function () {
    expect(FileManagerAccess::userRootAllowed('/var/www/site/assets', '/var/www/site/assets/alice'))->toBeTrue()
        ->and(FileManagerAccess::userRootAllowed('/var/www/site/assets', '/var/www/site/assets'))->toBeTrue();
});

test('a per-user root outside the system root is refused', function () {
    expect(FileManagerAccess::userRootAllowed('/var/www/site/assets', '/var/www/site'))->toBeFalse()
        ->and(FileManagerAccess::userRootAllowed('/var/www/site/assets', '/etc'))->toBeFalse()
        // the sibling-prefix trap: assets-private is not under assets
        ->and(FileManagerAccess::userRootAllowed('/var/www/site/assets', '/var/www/site/assets-private'))->toBeFalse();
});

test('an unresolved root on either side is refused', function () {
    expect(FileManagerAccess::userRootAllowed(false, '/var/www/site/assets/alice'))->toBeFalse()
        ->and(FileManagerAccess::userRootAllowed('/var/www/site/assets', false))->toBeFalse()
        ->and(FileManagerAccess::userRootAllowed(false, false))->toBeFalse();
});

it('drops a per-user filemanager_path that escapes the system root in getUserSettings', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Core.php');

    $start = strpos($source, 'public function getUserSettings');
    expect($start)->not->toBeFalse();
    $body = substr($source, $start, strpos($source, 'return $usrSettings;', $start) - $start);

    expect($body)->toContain('Support\FileManagerAccess::userRootAllowed(')
        ->and($body)->toContain("configGlobal['filemanager_path']")
        ->and($body)->toContain("\$this->setConfig('filemanager_path', \$systemPath);");
});

/*
| The value the manager actually uses. Core::getUserSettings() judges the override by realpath(),
| but the file managers name paths by fileManagerCanonicalPath(), which refuses spellings
| realpath() accepts (a ".." back over the root, a link that gives a folder a second name). Both
| file managers used to replace such a refusal with the site folder, wider than a narrowed
| site-wide root. fileManagerRoot() validates the override by the consumer's own rule and falls
| back only to the site-wide root, or to nothing when that root does not resolve.
*/

function userRootSite(): string
{
    $site = str_replace('\\', '/', sys_get_temp_dir()) . '/evo-user-root-' . bin2hex(random_bytes(6));
    foreach (['assets/files/sub', 'assets/files/team', 'outside'] as $dir) {
        mkdir($site . '/' . $dir, 0777, true);
    }

    return rtrim(str_replace('\\', '/', realpath($site)), '/');
}

function userRootRemove(string $path): void
{
    if (is_link($path) || is_file($path)) {
        @unlink($path) || @rmdir($path);
        return;
    }
    if (!is_dir($path)) {
        return;
    }
    foreach (array_diff(scandir($path), ['.', '..']) as $entry) {
        userRootRemove($path . '/' . $entry);
    }
    @rmdir($path);
}

function userRootFor(string $site, string $system, ?string $own): array
{
    $options = ['system' => $system] + ($own === null ? [] : ['own' => $own]);
    $output = evoRunPhp(dirname(__DIR__, 2) . '/Fixtures/file-manager-user-root.php', [$site, base64_encode(json_encode($options))]);
    $decoded = json_decode($output, true);
    expect($decoded)->toBeArray($output);

    return $decoded;
}

beforeEach(function () {
    $this->site = userRootSite();
    $this->system = $this->site . '/assets/files';
});

afterEach(function () {
    userRootRemove($this->site);
});

it('keeps the file manager root within a narrowed site-wide root, whatever the manager sets', function (?string $own, string $expected) {
    $own = $own === null ? null : str_replace('{site}', $this->site, $own);
    $result = userRootFor($this->site, '[(base_path)]assets/files/', $own);

    expect($result['root'])->toBe($this->site . '/' . $expected)
        ->and(FileManagerAccess::isWithin($this->system, $result['root']))->toBeTrue();
})->with([
    'no own root'                                  => [null, 'assets/files'],
    'a subfolder'                                  => ['{site}/assets/files/sub', 'assets/files/sub'],
    'the site folder'                              => ['{site}/', 'assets/files'],
    'a folder outside the site'                    => ['{site}/outside', 'assets/files'],
    'a sibling sharing the prefix'                 => ['{site}/assets/files-private', 'assets/files'],
    // realpath() lands on assets/files/sub, so Core keeps it; the file manager refuses the
    // spelling, and the root used to become the whole site
    'contained by realpath, refused by the rule'   => ['{site}/assets/files/sub/../../files/sub', 'assets/files'],
]);

it('does not widen to the site folder through a link that names a folder a second time', function () {
    if (!@symlink($this->system . '/team', $this->system . '/alias')) {
        $this->markTestSkipped('symlinks are not available');
    }

    $result = userRootFor($this->site, '[(base_path)]assets/files/', $this->system . '/alias');

    // Core accepted it: realpath() of the link is assets/files/team
    expect($result['config'])->toBe($this->system . '/alias')
        // the file manager does not pass such a link, and stays on the site-wide root
        ->and($result['root'])->toBe($this->system);
});

it('refuses rather than falling back to the site folder when the site-wide root does not resolve', function () {
    $result = userRootFor($this->site, '[(base_path)]missing/', $this->site . '/assets/files/sub');

    expect($result['root'])->toBeNull();
});

it('takes the root of both file managers from fileManagerRoot(), never the site folder', function () {
    $root = dirname(__DIR__, 4);
    $dynamic = (string) file_get_contents($root . '/manager/actions/files.dynamic.php');
    $browser = (string) file_get_contents($root . '/manager/media/browser/mcpuk/core/browser.php');
    $functions = (string) file_get_contents($root . '/core/functions/actions/files.php');
    $aclKey = substr($functions, strpos($functions, 'function fileManagerAclKey'), 800);

    expect($dynamic)->toContain('$filemanager_path = fileManagerRoot();')
        ->and($dynamic)->toContain('if ($filemanager_path === null) {')
        ->and($dynamic)->not->toContain("getConfig('filemanager_path')) ?: realpath(EVO_BASE_PATH)")
        ->and($aclKey)->toContain('fileManagerRoot()')
        ->and($aclKey)->not->toContain('realpath(EVO_BASE_PATH)')
        ->and(substr_count($browser, '$root = fileManagerRoot();'))->toBe(2)
        ->and($browser)->not->toContain("realpath(\$this->modx->getConfig('filemanager_path')) ?: realpath(EVO_BASE_PATH)");
});
