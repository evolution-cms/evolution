<?php

/**
 * Runs the classic file manager's fileupload() in a clean PHP process with the given $_FILES entries,
 * against a throwaway site, and returns its report.
 */
function runFileManagerUpload(string $site, array $files, array $post = []): string
{
    $core = dirname(__DIR__, 3);
    $script = '<?php'
        . ' define("EVO_BASE_PATH", ' . var_export($site . '/', true) . ');'
        . ' define("EVO_MANAGER_PATH", ' . var_export($site . '/manager/', true) . ');'
        . ' define("EVO_CORE_PATH", ' . var_export($site . '/core/', true) . ');'
        . ' define("EVO_SITE_URL", "http://example.test/");'
        . ' require ' . var_export($core . '/vendor/autoload.php', true) . ';'
        . ' final class FmUploadStubEvo {'
        . '   public array $configGlobal = [];'
        . '   public function getConfig($key, $default = null) {'
        . '     return ["filemanager_path" => EVO_BASE_PATH, "use_udperms" => 0, "upload_files" => "txt", "upload_images" => "png", "upload_media" => ""][$key] ?? $default;'
        . '   }'
        . '   public function hasPermission($permission) { return $permission === "file_manager"; }'
        . '   public function invokeEvent(...$args) { return []; }'
        . '   public function logEvent($id, $type, $message, $source) {}'
        . ' }'
        . ' $GLOBALS["fmStub"] = new FmUploadStubEvo();'
        . ' function evolutionCMS() { return $GLOBALS["fmStub"]; }'
        . ' function logFileChange($type, $filename) {}'
        . ' function fileManagerAclKey($relativePath) { return null; }'
        . ' $_lang = ["files_access_denied" => "ACCESS_DENIED", "files_file_type" => "Type: ", "files_upload_ok" => "UPLOADED",'
        . '   "files_upload_error" => "ERROR", "files_upload_error0" => "ATTACK?", "files_upload_error1" => "TOO_BIG", "files_upload_error3" => "PARTIAL",'
        . '   "files_upload_summary" => "Uploaded %1\$s of %2\$s files.", "files_upload_not_received" => "%1\$s files did not reach the server (at most %2\$s).",'
        . '   "files_upload_zip_hint" => "Use a ZIP."];'
        . ' $_POST = ' . var_export(['path' => 'assets/files'] + $post, true) . ';'
        . ' $_REQUEST = $_POST;'
        . ' $_FILES = ["userfile" => ' . var_export($files, true) . '];'
        . ' require ' . var_export($core . '/functions/actions/files.php', true) . ';'
        . ' echo fileupload();';

    $tmp = tempnam(sys_get_temp_dir(), 'evo-fm-upload-');
    file_put_contents($tmp, $script);
    try {
        return evoRunPhp($tmp);
    } finally {
        unlink($tmp);
    }
}

function uploadEntries(array $entries): array
{
    $files = ['name' => [], 'type' => [], 'tmp_name' => [], 'error' => [], 'size' => []];
    foreach ($entries as [$name, $error]) {
        $files['name'][] = $name;
        $files['type'][] = $error === UPLOAD_ERR_OK ? 'text/plain' : '';
        $files['tmp_name'][] = '';
        $files['error'][] = $error;
        $files['size'][] = 0;
    }

    return $files;
}

beforeEach(function () {
    $base = str_replace('\\', '/', sys_get_temp_dir()) . '/evo-fm-upload-' . bin2hex(random_bytes(6));
    mkdir($base . '/site/assets/files', 0777, true);
    $this->base = str_replace('\\', '/', realpath($base));
    $this->site = $this->base . '/site';
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

it('reports a file PHP refused instead of skipping it', function () {
    $output = runFileManagerUpload($this->site, uploadEntries([['big.txt', UPLOAD_ERR_INI_SIZE], ['half.txt', UPLOAD_ERR_PARTIAL]]));

    expect($output)->toContain('big.txt')->toContain('TOO_BIG')
        ->toContain('half.txt')->toContain('PARTIAL')
        ->toContain('Uploaded 0 of 2 files.')
        ->toContain('data-uploaded="0" data-total="2"')
        // one block per file, marked, so the page can put the failed ones first
        ->and(substr_count($output, 'class="upload-file upload-failed"'))->toBe(2);
});

it('ignores an empty file field', function () {
    $output = runFileManagerUpload($this->site, uploadEntries([['', UPLOAD_ERR_NO_FILE], ['big.txt', UPLOAD_ERR_INI_SIZE]]));

    expect($output)->toContain('Uploaded 0 of 1 files.')->not->toContain('You must select');
});

it('says how many of the files sent never reached PHP, and what to do about it', function () {
    $output = runFileManagerUpload(
        $this->site,
        uploadEntries([['a.txt', UPLOAD_ERR_INI_SIZE], ['b.txt', UPLOAD_ERR_INI_SIZE]]),
        ['upload_selected' => '30']
    );

    expect($output)->toContain('Uploaded 0 of 30 files.')
        ->toContain('28 files did not reach the server (at most ' . (int) ini_get('max_file_uploads') . ').')
        ->toContain('Use a ZIP.')
        ->toContain('class="upload-dropped"');
});

it('escapes the lexicon text of the summary', function () {
    $summary = (function () {
        $core = dirname(__DIR__, 3);
        $script = '<?php require ' . var_export($core . '/vendor/autoload.php', true) . ';'
            . ' require ' . var_export($core . '/functions/actions/files.php', true) . ';'
            . ' echo fileManagerUploadSummary(["files_upload_summary" => \'<b>%1$s/%2$s</b>\'], 3, 3, 3, 20);';
        $tmp = tempnam(sys_get_temp_dir(), 'evo-fm-summary-');
        file_put_contents($tmp, $script);
        try {
            return evoRunPhp($tmp);
        } finally {
            unlink($tmp);
        }
    })();

    expect($summary)->toContain('&lt;b&gt;3/3&lt;/b&gt;')->toContain('class="success"');
});

it('defines the upload report messages in the English lexicon', function () {
    $_lang = [];
    include dirname(__DIR__, 3) . '/lang/en/global.php';

    foreach (['files_upload_summary', 'files_upload_not_received', 'files_upload_zip_hint', 'files_upload_progress',
                 'files_upload_file_skipped', 'files_upload_stopped'] as $key) {
        expect($_lang)->toHaveKey($key);
    }
});
