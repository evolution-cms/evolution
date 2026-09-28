<?php

/**
 * Runs the classic file manager's textsave(), delete_file() and fileupload() in a clean PHP
 * process against a throwaway site tree, with a stub evolutionCMS() that grants $permissions.
 */
function runFileManagerWrite(string $site, string $function, array $request, array $permissions = []): string
{
    $core = dirname(__DIR__, 3);
    $script = '<?php'
        . ' define("EVO_BASE_PATH", ' . var_export($site . '/', true) . ');'
        . ' define("EVO_MANAGER_PATH", ' . var_export($site . '/manager/', true) . ');'
        . ' define("EVO_SITE_URL", "http://example.test/");'
        . ' require ' . var_export($core . '/vendor/autoload.php', true) . ';'
        . ' final class FmStubEvo {'
        . '   public array $configGlobal = [];'
        . '   public function __construct(private array $permissions) {}'
        . '   public function getConfig($key, $default = null) {'
        . '     return ["filemanager_path" => EVO_BASE_PATH, "use_udperms" => 0,'
        . '       "upload_files" => "txt,zip", "upload_images" => "png", "upload_media" => ""][$key] ?? $default;'
        . '   }'
        . '   public function hasPermission($permission) { return in_array($permission, $this->permissions, true); }'
        . '   public function invokeEvent(...$args) { return []; }'
        . ' }'
        // the file manager itself is only reachable with file_manager
        . ' $GLOBALS["fmStub"] = new FmStubEvo(' . var_export(array_merge(['file_manager'], $permissions), true) . ');'
        . ' function evolutionCMS() { return $GLOBALS["fmStub"]; }'
        . ' function logFileChange($type, $filename) {}'
        // no file groups here: keeps delete_file() away from the database
        . ' function fileManagerAclKey($relativePath) { return null; }'
        . ' $_lang = ["files_access_denied" => "ACCESS_DENIED", "file_saved" => "SAVED", "file_not_saved" => "NOT_SAVED",'
        . '   "deleting_file" => "Deleting %s: ", "file_deleted" => "DELETED", "file_not_deleted" => "NOT_DELETED"];'
        . ' $_POST = ' . var_export($request, true) . ';'
        . ' $_REQUEST = $_POST;'
        . ' $_FILES = ["userfile" => ["name" => [], "tmp_name" => [], "error" => [], "type" => []]];'
        . ' require ' . var_export($core . '/functions/actions/files.php', true) . ';'
        . ' echo strip_tags(' . $function . '());';

    $tmp = tempnam(sys_get_temp_dir(), 'evo-fm-');
    file_put_contents($tmp, $script);
    try {
        $output = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tmp) . ' 2>&1');
    } finally {
        unlink($tmp);
    }

    return (string) $output;
}

beforeEach(function () {
    $base = str_replace('\\', '/', sys_get_temp_dir()) . '/evo-fm-write-' . bin2hex(random_bytes(6));
    foreach (['site/assets/plugins/demo', 'site/assets/images', 'site/manager', 'site-old'] as $dir) {
        mkdir($base . '/' . $dir, 0777, true);
    }
    $base = str_replace('\\', '/', realpath($base));
    file_put_contents($base . '/site/notes..txt', 'original');
    file_put_contents($base . '/site/assets/plugins/demo/plugin.php', 'original');
    file_put_contents($base . '/site/manager/index.php', 'original');
    file_put_contents($base . '/site-old/secret.txt', 'original');
    $this->base = $base;
    $this->site = $base . '/site';
});

afterEach(function () {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->base, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($this->base);
});

it('saves an ordinary file whose name has a double dot before the extension', function () {
    $output = runFileManagerWrite($this->site, 'textsave', ['path' => 'notes..txt', 'content' => 'changed']);

    expect($output)->toContain('SAVED')->not->toContain('NOT_SAVED')
        ->and(file_get_contents($this->site . '/notes..txt'))->toBe('changed');
});

it('refuses to save into a sibling folder that shares the root name prefix', function () {
    $output = runFileManagerWrite($this->site, 'textsave', ['path' => '../site-old/secret.txt', 'content' => 'changed']);

    expect($output)->toContain('Invalid path')
        ->and(file_get_contents($this->base . '/site-old/secret.txt'))->toBe('original');
});

it('refuses to save a plugin file without save_plugin', function () {
    $output = runFileManagerWrite($this->site, 'textsave', ['path' => 'assets/plugins/demo/plugin.php', 'content' => 'changed']);

    expect($output)->toContain('ACCESS_DENIED')
        ->and(file_get_contents($this->site . '/assets/plugins/demo/plugin.php'))->toBe('original');
});

it('saves a plugin file for a user with save_plugin', function () {
    $output = runFileManagerWrite(
        $this->site,
        'textsave',
        ['path' => 'assets/plugins/demo/plugin.php', 'content' => 'changed'],
        ['save_plugin']
    );

    expect($output)->toContain('SAVED')
        ->and(file_get_contents($this->site . '/assets/plugins/demo/plugin.php'))->toBe('changed');
});

it('refuses to save manager files whatever the permissions', function () {
    $output = runFileManagerWrite(
        $this->site,
        'textsave',
        ['path' => 'manager/index.php', 'content' => 'changed'],
        ['save_plugin', 'save_snippet', 'save_template', 'save_module', 'empty_cache', 'import_static', 'export_static']
    );

    expect($output)->toContain('ACCESS_DENIED')
        ->and(file_get_contents($this->site . '/manager/index.php'))->toBe('original');
});

it('refuses to delete a plugin file without save_plugin', function () {
    $output = runFileManagerWrite($this->site, 'delete_file', ['path' => 'assets/plugins/demo/plugin.php']);

    expect($output)->toContain('ACCESS_DENIED')
        ->and(is_file($this->site . '/assets/plugins/demo/plugin.php'))->toBeTrue();
});

it('refuses to delete from a sibling folder that shares the root name prefix', function () {
    $output = runFileManagerWrite($this->site, 'delete_file', ['path' => '../site-old/secret.txt']);

    expect($output)->toContain('Invalid path')
        ->and(is_file($this->base . '/site-old/secret.txt'))->toBeTrue();
});

it('deletes an ordinary file whose name has a double dot before the extension', function () {
    $output = runFileManagerWrite($this->site, 'delete_file', ['path' => 'notes..txt']);

    expect($output)->toContain('DELETED')->not->toContain('NOT_DELETED')
        ->and(is_file($this->site . '/notes..txt'))->toBeFalse();
});

it('refuses uploads into a protected folder, with a readable message', function () {
    $output = runFileManagerWrite($this->site, 'fileupload', ['path' => 'assets/plugins/demo']);

    expect($output)->toContain('ACCESS_DENIED');
});

it('refuses uploads into a sibling folder that shares the root name prefix', function () {
    $output = runFileManagerWrite($this->site, 'fileupload', ['path' => '../site-old']);

    expect($output)->toContain('Invalid path');
});

it('accepts uploads into an ordinary folder', function () {
    $output = runFileManagerWrite($this->site, 'fileupload', ['path' => 'assets/images']);

    expect($output)->not->toContain('ACCESS_DENIED')->not->toContain('Invalid path');
});
