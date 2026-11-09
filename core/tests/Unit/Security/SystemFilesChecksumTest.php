<?php

use EvolutionCMS\Support\SystemFilesChecksum;

/*
|--------------------------------------------------------------------------
| The file integrity check survives an update
|--------------------------------------------------------------------------
|
| sys_files_checksum records the md5 of each check_files_onlogin file, and the dashboard reports
| every file that no longer matches. An update replaces index.php and manager/index.php with the
| release's copies, so each update was reported as a modification. SystemFilesChecksum::adopt()
| records a file whose old and new hashes are both release copies, the new one this code's; a
| file anyone edited still is reported. The checks run the real ManagerApi comparison.
|
*/

function sysFilesRoot(): string
{
    return dirname(__DIR__, 4);
}

/** md5 as the release archive ships the file: LF line endings, whatever the checkout uses */
function sysFilesShippedMd5(string $relative): string
{
    return md5(str_replace("\r\n", "\n", (string) file_get_contents(sysFilesRoot() . '/' . $relative)));
}

/** A file as git stores it at $ref, byte for byte (evoRunCommand() joins lines), or null. */
function sysFilesGitShow(string $ref, string $file): ?string
{
    $process = @proc_open(['git', '-C', sysFilesRoot(), 'show', $ref . ':' . $file], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        return null;
    }
    $content = (string) stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return proc_close($process) === 0 ? $content : null;
}

/** @param array<string, string> $files relative name => content */
function sysFilesWrite(string $site, array $files): void
{
    foreach ($files as $name => $content) {
        @mkdir(dirname($site . $name), 0777, true);
        file_put_contents($site . $name, $content);
    }
}

/** The md5 map ManagerApi::getSystemChecksum() stores, written out by hand. */
function sysFilesChecksum(string $site, array $names): string
{
    $sums = [];
    foreach ($names as $name) {
        $sums[$site . $name] = md5_file($site . $name);
    }

    return serialize($sums);
}

/** adopt() and the dashboard's comparison, see Fixtures/system-files-checksum.php */
function sysFilesCheck(string $site, string $list, string $stored, ?array $released = null): array
{
    $output = evoRunPhp(dirname(__DIR__, 2) . '/Fixtures/system-files-checksum.php', [
        $site, base64_encode(json_encode(['list' => $list, 'stored' => $stored, 'released' => $released])),
    ]);
    $decoded = json_decode($output, true);
    expect($decoded)->toBeArray($output);

    return $decoded;
}

const SYS_FILES_LIST = "index.php\n.htaccess\nmanager/index.php";

beforeEach(function () {
    $this->site = str_replace('\\', '/', sys_get_temp_dir()) . '/evo-sysfiles-' . bin2hex(random_bytes(6)) . '/';
    // three copies of index.php and two of manager/index.php, the last of each this code's
    $this->released = [
        'index.php' => [md5('index 1'), md5('index 2'), md5('index 3')],
        'manager/index.php' => [md5('manager 1'), md5('manager 2')],
    ];
    sysFilesWrite($this->site, ['index.php' => 'index 2', 'manager/index.php' => 'manager 1', '.htaccess' => 'rules']);
    $this->stored = sysFilesChecksum($this->site, ['index.php', '.htaccess', 'manager/index.php']);
});

afterEach(function () {
    foreach (['index.php', 'manager/index.php', '.htaccess'] as $name) {
        @unlink($this->site . $name);
    }
    @rmdir($this->site . 'manager');
    @rmdir($this->site);
});

it('records the release copies an update laid over untouched files', function () {
    sysFilesWrite($this->site, ['index.php' => 'index 3', 'manager/index.php' => 'manager 2']);

    $result = sysFilesCheck($this->site, SYS_FILES_LIST, $this->stored, $this->released);

    expect($result['before'])->toBe(['index.php', 'manager/index.php'])
        ->and($result['adopted'])->toBe(sysFilesChecksum($this->site, ['index.php', '.htaccess', 'manager/index.php']))
        ->and($result['after'])->toBe([])
        // what checkSystemChecksum() compares first
        ->and($result['current'])->toBe($result['adopted']);
});

it('still reports a file edited by someone', function (array $files, ?array $baseline, array $reported) {
    if ($baseline !== null) {
        sysFilesWrite($this->site, $baseline);
        $this->stored = sysFilesChecksum($this->site, ['index.php', '.htaccess', 'manager/index.php']);
    }
    sysFilesWrite($this->site, $files);

    expect(sysFilesCheck($this->site, SYS_FILES_LIST, $this->stored, $this->released)['after'])->toBe($reported);
})->with([
    'edited, no update'                      => [['index.php' => 'index 2 + backdoor'], null, ['index.php']],
    'edited after the update'                => [['index.php' => 'index 3 + backdoor'], null, ['index.php']],
    // the baseline was taken of an edited copy, the update then replaced it
    'edited before the update'               => [['index.php' => 'index 3'], ['index.php' => 'custom index'], ['index.php']],
    'replaced by an older release copy'      => [['index.php' => 'index 1'], null, ['index.php']],
    'a file no release ships'                => [['.htaccess' => 'rules + redirect'], null, ['.htaccess']],
    'one replaced by the update, one edited' => [
        ['index.php' => 'index 3', '.htaccess' => 'rules + redirect'], null, ['.htaccess'],
    ],
]);

it('leaves the checksum alone when there is nothing to adopt', function (string $stored) {
    sysFilesWrite($this->site, ['index.php' => 'index 3']);

    expect(SystemFilesChecksum::adopt(SYS_FILES_LIST, $stored, $this->site, $this->released))->toBeNull();
})->with([
    'no checksum yet'      => [''],
    'not a checksum'       => ['O:8:"stdClass":0:{}'],
    'files not in the map' => [serialize(['/elsewhere/index.php' => md5('index 2')])],
]);

it('lists every shipped file of the default check list, this code\'s copy last', function () {
    $defaults = (string) file_get_contents(sysFilesRoot() . '/core/factory/settings.php');
    preg_match("/'check_files_onlogin' => \"([^\"]+)\"/", $defaults, $match);
    // .htaccess and the database config are the site's own, no release writes them
    $shipped = array_values(array_filter(
        array_map(fn ($file) => ltrim(trim($file), '/'), explode('\n', $match[1])),
        fn ($file) => is_file(sysFilesRoot() . '/' . $file) && !str_starts_with($file, '.') && !str_starts_with($file, 'core/config/')
    ));

    expect($shipped)->toBe(['index.php', 'manager/index.php'])
        ->and(array_keys(SystemFilesChecksum::RELEASED))->toBe($shipped);
    foreach ($shipped as $file) {
        $copies = SystemFilesChecksum::RELEASED[$file];
        expect(end($copies))->toBe(
            sysFilesShippedMd5($file),
            "{$file} changed: append " . sysFilesShippedMd5($file) . " to SystemFilesChecksum::RELEASED['{$file}']"
        )
            ->and(array_values(array_unique($copies)))->toBe($copies)
            ->and(array_values(preg_grep('/^[0-9a-f]{32}$/', $copies)))->toBe($copies);
    }
});

it('knows the copies of every 3.x release, so an update from any of them is quiet', function () {
    $root = escapeshellarg(sysFilesRoot());
    $tags = array_filter(explode("\n", (string) evoRunCommand("git -C {$root} tag --merged HEAD --list \"3.*\" 2>&1", $exitCode)));
    if ($exitCode !== 0 || !in_array('3.5.9', $tags, true)) {
        $this->markTestSkipped('no git checkout with the release tags');
    }

    $unknown = [];
    foreach ($tags as $tag) {
        foreach (array_keys(SystemFilesChecksum::RELEASED) as $file) {
            $copy = sysFilesGitShow($tag, $file);
            if ($copy !== null && !in_array(md5($copy), SystemFilesChecksum::RELEASED[$file], true)) {
                $unknown[] = $tag . ':' . $file;
            }
        }
    }

    expect($unknown)->toBe([]);
});

it('stays quiet over a real update from 3.5.9 to this code', function () {
    $files = [];
    foreach (array_keys(SystemFilesChecksum::RELEASED) as $file) {
        $files[$file] = sysFilesGitShow('3.5.9', $file);
        if ($files[$file] === null) {
            $this->markTestSkipped('no git checkout with the 3.5.9 tag');
        }
    }
    sysFilesWrite($this->site, $files);
    $list = "index.php\n.htaccess\nmanager/index.php\n/core/config/database/connections/default.php";
    $stored = sysFilesChecksum($this->site, ['index.php', '.htaccess', 'manager/index.php']);

    // the update lays this code's copies over the site
    foreach (array_keys($files) as $file) {
        file_put_contents($this->site . $file, str_replace("\r\n", "\n", (string) file_get_contents(sysFilesRoot() . '/' . $file)));
    }
    $result = sysFilesCheck($this->site, $list, $stored);

    expect($result['before'])->not->toBe([])
        ->and($result['adopted'])->not->toBeNull()
        ->and($result['after'])->toBe([]);
});

it('adopts on the dashboard check, before the files are compared', function () {
    $source = (string) file_get_contents(sysFilesRoot() . '/manager/includes/config_check.inc.php');
    $adopt = strpos($source, 'SystemFilesChecksum::adopt(');

    expect($adopt)->toBeInt()
        ->and($adopt)->toBeLessThan(strpos($source, 'checkSystemChecksum()'))
        ->and($source)->toContain('setSystemChecksum($adoptedSysfilesChecksum)')
        // the reset link is a state change by GET
        ->and($source)->toContain('b=resetSysfilesChecksum&_token=\' . csrf_token()');
});
