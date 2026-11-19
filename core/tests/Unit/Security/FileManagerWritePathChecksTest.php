<?php

/**
 * Runs the classic file manager's textsave(), delete_file() and fileupload() in a clean PHP
 * process against a throwaway site tree, with a stub evolutionCMS() that grants $permissions.
 */
function runFileManagerWrite(string $site, string $function, array $request, array $permissions = [], string $uploadFiles = 'txt,zip'): string
{
    $script = fileManagerWriteHarness($site, $request, $permissions, $uploadFiles)
        . ' echo strip_tags(' . (str_contains($function, '(') ? $function : $function . '()') . ');';

    $tmp = tempnam(sys_get_temp_dir(), 'evo-fm-');
    file_put_contents($tmp, $script);
    try {
        $output = evoRunPhp($tmp);
    } finally {
        unlink($tmp);
    }

    return $output;
}

/** The stubbed file manager, up to the call under test; a null $request keeps what PHP parsed. */
function fileManagerWriteHarness(string $site, ?array $request, array $permissions = [], string $uploadFiles = 'txt,zip'): string
{
    $core = dirname(__DIR__, 3);

    return '<?php'
        . ' define("EVO_BASE_PATH", ' . var_export($site . '/', true) . ');'
        . ' define("EVO_MANAGER_PATH", ' . var_export($site . '/manager/', true) . ');'
        . ' define("EVO_CORE_PATH", ' . var_export($site . '/core/', true) . ');'
        . ' define("EVO_SITE_URL", "http://example.test/");'
        . ' require ' . var_export($core . '/vendor/autoload.php', true) . ';'
        . ' final class FmStubEvo {'
        . '   public array $configGlobal = [];'
        . '   public function __construct(private array $permissions) {}'
        . '   public function getConfig($key, $default = null) {'
        . '     return ["filemanager_path" => EVO_BASE_PATH, "use_udperms" => 0,'
        . '       "upload_files" => ' . var_export($uploadFiles, true) . ', "upload_images" => "png", "upload_media" => ""][$key] ?? $default;'
        . '   }'
        . '   public function hasPermission($permission) { return in_array($permission, $this->permissions, true); }'
        . '   public function invokeEvent(...$args) { return []; }'
        . '   public function logEvent($id, $type, $message, $source) { echo "LOG[$type|$source|$message]"; }'
        . ' }'
        // the file manager itself is only reachable with file_manager
        . ' $GLOBALS["fmStub"] = new FmStubEvo(' . var_export(array_merge(['file_manager'], $permissions), true) . ');'
        . ' function evolutionCMS() { return $GLOBALS["fmStub"]; }'
        . ' function logFileChange($type, $filename) {}'
        // no file groups here: keeps delete_file() away from the database
        . ' function fileManagerAclKey($relativePath) { return null; }'
        . ' $_lang = ["files_access_denied" => "ACCESS_DENIED", "file_saved" => "SAVED", "file_not_saved" => "NOT_SAVED",'
        . '   "deleting_file" => "Deleting %s: ", "file_deleted" => "DELETED", "file_not_deleted" => "NOT_DELETED",'
        . '   "file_folder_deleted" => "FOLDER_DELETED", "file_folder_not_deleted" => "FOLDER_NOT_DELETED",'
        . '   "files_selection_incomplete" => "INCOMPLETE_SELECTION"];'
        . ($request === null ? '' : ' $_POST = ' . var_export($request, true) . ';'
            . ' $_REQUEST = $_POST;'
            . ' $_FILES = ["userfile" => ["name" => [], "tmp_name" => [], "error" => [], "type" => []]];')
        . ' require ' . var_export($core . '/functions/actions/files.php', true) . ';';
}

beforeEach(function () {
    $base = str_replace('\\', '/', sys_get_temp_dir()) . '/evo-fm-write-' . bin2hex(random_bytes(6));
    foreach (['site/assets/plugins/demo', 'site/assets/images', 'site/manager', 'site/core/config', 'site/temp/backup', 'site-old'] as $dir) {
        mkdir($base . '/' . $dir, 0777, true);
    }
    $base = str_replace('\\', '/', realpath($base));
    file_put_contents($base . '/site/notes..txt', 'original');
    file_put_contents($base . '/site/assets/plugins/demo/plugin.php', 'original');
    file_put_contents($base . '/site/manager/index.php', 'original');
    file_put_contents($base . '/site/temp/backup/dump.sql', 'original');
    file_put_contents($base . '/site/core/config/database.php', 'original');
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

it('refuses to save manager files without a permission that runs code', function () {
    $output = runFileManagerWrite(
        $this->site,
        'textsave',
        ['path' => 'manager/index.php', 'content' => 'changed'],
        ['empty_cache', 'import_static', 'export_static']
    );

    expect($output)->toContain('ACCESS_DENIED')
        ->and(file_get_contents($this->site . '/manager/index.php'))->toBe('original');
});

it('lets a user who can already run PHP save manager files', function (string $permission) {
    $output = runFileManagerWrite(
        $this->site,
        'textsave',
        ['path' => 'manager/index.php', 'content' => 'changed'],
        [$permission]
    );

    expect($output)->toContain('SAVED')
        ->and(file_get_contents($this->site . '/manager/index.php'))->toBe('changed');
})->with(['save_snippet', 'save_plugin', 'save_module']);

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

it('refuses to save core files without a permission that runs code', function () {
    $output = runFileManagerWrite($this->site, 'textsave', [
        'path' => 'core/config/database.php',
        'content' => 'changed',
    ], ['empty_cache', 'import_static', 'save_template']);

    expect($output)->toContain('ACCESS_DENIED')
        ->and(file_get_contents($this->site . '/core/config/database.php'))->toBe('original');
});

it('lets a user who can already run PHP save core files', function (string $permission) {
    $output = runFileManagerWrite($this->site, 'textsave', [
        'path' => 'core/config/database.php',
        'content' => 'changed',
    ], [$permission]);

    expect($output)->toContain('SAVED')
        ->and(file_get_contents($this->site . '/core/config/database.php'))->toBe('changed');
})->with(['save_snippet', 'save_plugin', 'save_module']);

it('keeps database backups from users without bk_manager', function () {
    $output = runFileManagerWrite($this->site, 'textsave', [
        'path' => 'temp/backup/dump.sql',
        'content' => 'changed',
    ], ['save_snippet']);

    expect($output)->toContain('ACCESS_DENIED')
        ->and(file_get_contents($this->site . '/temp/backup/dump.sql'))->toBe('original');
});

it('lets a user with bk_manager reach database backups', function () {
    $output = runFileManagerWrite($this->site, 'textsave', [
        'path' => 'temp/backup/dump.sql',
        'content' => 'changed',
    ], ['bk_manager']);

    expect($output)->toContain('SAVED');
});

it('refuses executable file names without a permission that runs code, whatever the extension list says', function (string $name) {
    $output = runFileManagerWrite($this->site, 'var_export(checkExtension("' . $name . '"), true)', [], ['file_manager'], 'txt,php,phtml,htaccess');

    expect($output)->toEndWith('false')->and($output)->toContain('LOG[2|File manager|');
})->with(['shell.php', 'shell.php.txt', 'x.phtml', '.htaccess']);

it('allows executable file names to a user who can already run PHP, if the extension list has them', function (string $permission) {
    $output = runFileManagerWrite($this->site, 'var_export(checkExtension("shell.php"), true)', [], [$permission], 'txt,php');

    expect($output)->toBe('true');
})->with(['save_snippet', 'save_plugin', 'save_module']);

it('still applies the extension list to a user who can run PHP', function () {
    $output = runFileManagerWrite($this->site, 'var_export(checkExtension("shell.php"), true)', [], ['save_snippet'], 'txt');

    expect($output)->toBe('false');
});

it('refuses to edit an existing executable file without a permission that runs code', function () {
    file_put_contents($this->site . '/assets/images/shell.php.txt', 'original');

    $output = runFileManagerWrite($this->site, 'textsave', [
        'path' => 'assets/images/shell.php.txt',
        'content' => 'changed',
    ], ['empty_cache'], 'txt,php');

    expect($output)->toContain('ACCESS_DENIED')
        ->and(file_get_contents($this->site . '/assets/images/shell.php.txt'))->toBe('original');
});

it('lets a user who can run PHP edit an existing executable file', function () {
    file_put_contents($this->site . '/assets/images/shell.php.txt', 'original');

    $output = runFileManagerWrite($this->site, 'textsave', [
        'path' => 'assets/images/shell.php.txt',
        'content' => 'changed',
    ], ['save_snippet']);

    expect($output)->toContain('SAVED');
});

it('checks the restrictions of an existing file before an upload replaces it', function () {
    $functions = file_get_contents(dirname(__DIR__, 3) . '/functions/actions/files.php');
    $body = substr($functions, strpos($functions, 'function fileupload'));

    $check = strpos($body, 'file_exists($targetFile)');
    expect($check)->not->toBeFalse()
        ->and($check)->toBeLessThan(strpos($body, 'move_uploaded_file'))
        ->and(substr($body, $check - 400, 650))->toContain('fileManagerCanonicalCase($targetFile)')
        ->and(substr($body, $check, 600))->toContain('fileManagerCanModifyExistingPath(')
        // and never over a link, which the move would follow
        ->and(substr($body, $check, 250))->toContain('fileManagerIsSafeWriteTarget($filemanager_path, $targetFile)');
});

it('writes a refused save to the event log for the administrator', function () {
    $output = runFileManagerWrite($this->site, 'textsave', [
        'path' => 'core/config/database.php',
        'content' => 'changed',
    ], ['empty_cache']);

    // a warning from the file manager that names the action and the path
    expect($output)->toContain('LOG[2|File manager|File manager access denied:')
        ->and($output)->toContain('core/config/database.php');
});

it('writes a refused delete to the event log', function () {
    $output = runFileManagerWrite($this->site, 'delete_file', ['path' => 'manager/index.php'], ['empty_cache']);

    expect($output)->toContain('LOG[2|File manager|')->and($output)->toContain('manager/index.php');
});

it('writes a refused executable name to the event log, and says so', function () {
    $output = runFileManagerWrite($this->site, 'var_export(checkExtension("shell.php"), true)', [], ['file_manager'], 'txt,php');

    expect($output)->toContain('LOG[2|File manager|')
        ->and($output)->toContain('shell.php')
        ->and($output)->toContain('executable name')
        ->and($output)->toContain('false');
});

it('does not log what is allowed, nor an ordinary extension that is merely not on the list', function () {
    $allowed = runFileManagerWrite($this->site, 'textsave', [
        'path' => 'notes..txt',
        'content' => 'changed',
    ]);
    $unlisted = runFileManagerWrite($this->site, 'var_export(checkExtension("photo.gif"), true)', [], ['file_manager'], 'txt');

    expect($allowed)->not->toContain('LOG[')
        ->and($unlisted)->not->toContain('LOG[')
        ->and($unlisted)->toContain('false');
});

it('escapes what a client sent before it reaches the event log', function () {
    $output = runFileManagerWrite($this->site, 'fileManagerLogDenied("save", "<img src=x onerror=alert(1)>\\nfake", "")', [], []);

    expect($output)->not->toContain('<img')->and($output)->toContain('LOG[2|File manager|');
});

/** fileManagerDeleteEntries() for the entries of one folder, as the listing's selection posts them. */
function fileManagerDeleteSelection(string $folder, array $names): string
{
    return 'fileManagerDeleteEntries(rtrim(EVO_BASE_PATH, "/"), rtrim(EVO_BASE_PATH . ' . var_export($folder, true) . ', "/"), '
        . var_export($folder, true) . ', ' . var_export($names, true) . ', [], fileManagerProtectedPaths())';
}

it('deletes a selection of files and folders of one folder', function () {
    mkdir($this->site . '/assets/files/batch/sub', 0777, true);
    foreach (['a.txt', 'b.txt', 'c.txt', 'sub/inner.txt'] as $name) {
        file_put_contents($this->site . '/assets/files/batch/' . $name, 'x');
    }

    $output = runFileManagerWrite($this->site, fileManagerDeleteSelection('assets/files/batch', ['a.txt', 'sub', 'c.txt']), []);

    expect($output)->toContain('Deleting assets/files/batch/a.txt: DELETED')
        ->and($output)->toContain('Deleting assets/files/batch/c.txt: DELETED')
        ->and($output)->toContain('sub: FOLDER_DELETED')
        ->and(is_file($this->site . '/assets/files/batch/a.txt'))->toBeFalse()
        ->and(is_file($this->site . '/assets/files/batch/c.txt'))->toBeFalse()
        ->and(is_dir($this->site . '/assets/files/batch/sub'))->toBeFalse()
        ->and(is_file($this->site . '/assets/files/batch/b.txt'))->toBeTrue();
});

it('holds every selected entry to its own delete action, not to the first one', function () {
    $output = runFileManagerWrite($this->site, fileManagerDeleteSelection('', ['notes..txt', 'manager', 'temp']), ['empty_cache']);

    // manager/ needs a permission that runs code, temp/backup inside temp/ needs bk_manager
    expect($output)->toContain('DELETED')
        ->and(substr_count($output, 'ACCESS_DENIED'))->toBe(2)
        ->and(is_file($this->site . '/notes..txt'))->toBeFalse()
        ->and(is_file($this->site . '/manager/index.php'))->toBeTrue()
        ->and(is_file($this->site . '/temp/backup/dump.sql'))->toBeTrue();
});

it('refuses selected names that leave the folder', function () {
    $output = runFileManagerWrite($this->site, fileManagerDeleteSelection('assets', ['../notes..txt', '..', 'plugins/demo/plugin.php', '']), ['save_plugin']);

    expect(substr_count($output, 'Invalid path'))->toBe(4)
        ->and(is_file($this->site . '/notes..txt'))->toBeTrue()
        ->and(is_file($this->site . '/assets/plugins/demo/plugin.php'))->toBeTrue();
});

it('refuses a selection that is not a list of names', function () {
    $output = runFileManagerWrite($this->site, 'fileManagerDeleteEntries(rtrim(EVO_BASE_PATH, "/"), rtrim(EVO_BASE_PATH, "/"), "", "notes..txt", [], [])', []);

    expect($output)->toContain('Invalid path')
        ->and(is_file($this->site . '/notes..txt'))->toBeTrue();
});

it('takes a selection only as one complete list of names', function () {
    require_once dirname(__DIR__, 3) . '/functions/actions/files.php';

    $post = static fn (array $names, $count = null): array => ['names' => json_encode($names), 'count' => (string) ($count ?? count($names))];

    expect(fileManagerSelectionFromRequest($post(['a.txt', 'sub'])))->toBe(['a.txt', 'sub'])
        // a count that does not match is a selection that did not arrive whole
        ->and(fileManagerSelectionFromRequest($post(['a.txt', 'sub'], 3)))->toBeNull()
        ->and(fileManagerSelectionFromRequest(['names' => json_encode(['a.txt'])]))->toBeNull()
        ->and(fileManagerSelectionFromRequest(['count' => '1']))->toBeNull()
        // the shape of one field per name, which PHP can cut short
        ->and(fileManagerSelectionFromRequest(['names' => ['a.txt', 'b.txt'], 'count' => '2']))->toBeNull()
        ->and(fileManagerSelectionFromRequest(['names' => '["a.txt"', 'count' => '1']))->toBeNull()
        ->and(fileManagerSelectionFromRequest(['names' => '{"0":"a.txt"}', 'count' => '1']))->toBeNull()
        ->and(fileManagerSelectionFromRequest(['names' => '[["a.txt"]]', 'count' => '1']))->toBeNull()
        ->and(fileManagerSelectionFromRequest(['names' => '[1]', 'count' => '1']))->toBeNull()
        ->and(fileManagerSelectionFromRequest(['names' => '[]', 'count' => '0']))->toBeNull()
        ->and(fileManagerSelectionFromRequest(['names' => '["a.txt"]', 'count' => '1.0']))->toBeNull()
        ->and(fileManagerSelectionFromRequest($post(array_fill(0, FILE_MANAGER_SELECTION_LIMIT + 1, 'a.txt'))))->toBeNull();
});

it('deletes nothing when PHP drops part of a selection request, and all of one that arrives whole', function () {
    mkdir($this->site . '/assets/files/bulk', 0777, true);
    $names = [];
    for ($i = 0; $i < 30; $i++) {
        $names[] = "file-$i.txt";
        file_put_contents($this->site . "/assets/files/bulk/file-$i.txt", 'x');
    }
    $router = $this->base . '/router.php';
    file_put_contents($router, fileManagerWriteHarness($this->site, null)
        . ' echo strip_tags(fileManagerDeleteSelectionRequest($_POST, rtrim(EVO_BASE_PATH, "/"),'
        . ' EVO_BASE_PATH . "assets/files/bulk", "assets/files/bulk", [], fileManagerProtectedPaths()));');

    $socket = stream_socket_server('tcp://127.0.0.1:0');
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    // a limit of 20 input variables, the default 1000 scaled down
    $server = new Symfony\Component\Process\Process(
        [PHP_BINARY, '-d', 'max_input_vars=20', '-d', 'opcache.enable=0', '-S', $address, $router],
        $this->base
    );
    $server->start();
    for ($attempt = 0; $attempt < 100; $attempt++) {
        $connection = @fsockopen('127.0.0.1', (int) substr(strrchr($address, ':'), 1), $code, $message, 0.1);
        if ($connection !== false) {
            fclose($connection);
            break;
        }
        usleep(50000);
    }
    $post = static function (string $body) use ($address): string {
        return (string) @file_get_contents("http://$address/", false, stream_context_create(['http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $body,
            'ignore_errors' => true,
        ]]));
    };
    $left = fn (): int => count(glob($this->site . '/assets/files/bulk/*.txt'));

    try {
        // one field per name: PHP keeps the first 20 variables, so the names after them never arrive
        $perName = $post(http_build_query(['mode' => 'deletemany', 'count' => '30', 'names' => $names]));
        expect($perName)->toContain('INCOMPLETE_SELECTION')
            ->and($perName)->not->toContain('DELETED')
            ->and($left())->toBe(30);

        // fields ahead of the selection that push its count and names past the limit
        $padding = [];
        for ($i = 0; $i < 25; $i++) {
            $padding["pad$i"] = 'x';
        }
        $pushedOut = $post(http_build_query($padding + ['count' => '30', 'names' => json_encode($names)]));
        expect($pushedOut)->toContain('INCOMPLETE_SELECTION')
            ->and($left())->toBe(30);

        // the listing's request: one field for all 30 names, far below the limit
        $whole = $post(http_build_query(['mode' => 'deletemany', 'count' => '30', 'names' => json_encode($names)]));
        expect(substr_count($whole, ': DELETED'))->toBe(30)
            ->and($whole)->not->toContain('INCOMPLETE_SELECTION')
            ->and($left())->toBe(0);
    } finally {
        $server->stop(0);
    }
});
