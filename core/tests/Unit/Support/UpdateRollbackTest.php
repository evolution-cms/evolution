<?php

use EvolutionCMS\Support\UpdateRollback;

beforeEach(function () {
    $this->root = sys_get_temp_dir() . '/evo-update-rollback-' . bin2hex(random_bytes(6));
    $this->site = $this->root . '/site';
    $this->journal = $this->site . '/core/.update-rollback-test';
    mkdir($this->site . '/core/vendor/acme/lib', 0777, true);
    mkdir($this->site . '/core/src/Old', 0777, true);
    file_put_contents($this->site . '/index.php', 'old front');
    file_put_contents($this->site . '/core/src/Old/Gone.php', 'old class');
    file_put_contents($this->site . '/core/vendor/autoload.php', 'old autoload');
    file_put_contents($this->site . '/core/vendor/acme/lib/Lib.php', 'old lib');
    $this->rollback = new UpdateRollback($this->site, $this->journal);
});

afterEach(function () {
    rollbackTestRemove($this->root);
});

function rollbackTestRemove(string $path): void
{
    if (is_link($path) || is_file($path)) {
        unlink($path);

        return;
    }
    if (!is_dir($path)) {
        return;
    }
    foreach (scandir($path) as $entry) {
        if ($entry !== '.' && $entry !== '..') {
            rollbackTestRemove($path . '/' . $entry);
        }
    }
    rmdir($path);
}

/**
 * Every file and folder below a path with its contents, links as their target.
 *
 * @return array<string, string>
 */
function rollbackTestTree(string $root, string $skip = ''): array
{
    $tree = [];
    $walk = function (string $dir) use (&$walk, &$tree, $root, $skip) {
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            $relative = substr($path, strlen($root) + 1);
            if ($skip !== '' && str_starts_with($relative, $skip)) {
                continue;
            }
            if (is_link($path)) {
                $tree[$relative] = 'link:' . readlink($path);
            } elseif (is_dir($path)) {
                $tree[$relative . '/'] = 'dir';
                $walk($path);
            } else {
                $tree[$relative] = file_get_contents($path);
            }
        }
    };
    $walk($root);
    ksort($tree);

    return $tree;
}

/** Lays a file the way moveFiles() does: through the journal, then over the path. */
function rollbackTestLay(UpdateRollback $rollback, string $path, string $contents): void
{
    $rollback->makeDirectory(dirname($path));
    $rollback->replace($path);
    file_put_contents($path, $contents);
}

test('restore puts replaced files back and removes what the update created', function () {
    $before = rollbackTestTree($this->site);

    rollbackTestLay($this->rollback, $this->site . '/index.php', 'new front');
    rollbackTestLay($this->rollback, $this->site . '/install/stubs/seed.php', 'new installer');
    rollbackTestLay($this->rollback, $this->site . '/core/src/New/Added.php', 'new class');

    expect(file_get_contents($this->site . '/index.php'))->toBe('new front')
        ->and($this->rollback->restore())->toBe([])
        ->and(rollbackTestTree($this->site))->toBe($before)
        ->and(is_dir($this->journal))->toBeFalse();
});

test('a file the update removes comes back, a folder with all it held', function () {
    $before = rollbackTestTree($this->site);

    $this->rollback->remove($this->site . '/core/src/Old');
    $this->rollback->remove($this->site . '/core/src/Missing.php');

    expect(is_dir($this->site . '/core/src/Old'))->toBeFalse()
        ->and($this->rollback->restore())->toBe([])
        ->and(rollbackTestTree($this->site))->toBe($before);
});

test('removing a file the update laid itself leaves the original for the restore', function () {
    $before = rollbackTestTree($this->site);

    rollbackTestLay($this->rollback, $this->site . '/index.php', 'new front');
    rollbackTestLay($this->rollback, $this->site . '/sample.txt', 'new sample');
    $this->rollback->remove($this->site . '/index.php');
    $this->rollback->remove($this->site . '/sample.txt');

    expect(file_exists($this->site . '/index.php'))->toBeFalse()
        ->and(file_exists($this->site . '/sample.txt'))->toBeFalse()
        ->and($this->rollback->restore())->toBe([])
        ->and(rollbackTestTree($this->site))->toBe($before);
});

test('a folder rewritten in place returns to its copy', function () {
    $before = rollbackTestTree($this->site);
    $this->rollback->snapshotDirectory($this->site . '/core/vendor');

    // What a Composer run that stops halfway leaves behind.
    file_put_contents($this->site . '/core/vendor/autoload.php', 'new autoload');
    unlink($this->site . '/core/vendor/acme/lib/Lib.php');
    mkdir($this->site . '/core/vendor/acme/next');
    file_put_contents($this->site . '/core/vendor/acme/next/Next.php', 'new package');

    expect($this->rollback->restore())->toBe([])
        ->and(rollbackTestTree($this->site))->toBe($before);
});

test('links in a copied folder stay links', function () {
    mkdir($this->site . '/core/custom/packages/local', 0777, true);
    file_put_contents($this->site . '/core/custom/packages/local/Local.php', 'local package');
    if (!@symlink('../../custom/packages/local', $this->site . '/core/vendor/acme/local')) {
        $this->markTestSkipped('This file system does not let the test create a symlink.');
    }
    $before = rollbackTestTree($this->site);
    $this->rollback->snapshotDirectory($this->site . '/core/vendor');

    unlink($this->site . '/core/vendor/acme/local');
    file_put_contents($this->site . '/core/vendor/acme/local', 'a file now');

    expect($this->rollback->restore())->toBe([])
        ->and(rollbackTestTree($this->site))->toBe($before)
        ->and(file_get_contents($this->site . '/core/custom/packages/local/Local.php'))->toBe('local package');
});

test('a folder that did not exist before is removed again', function () {
    $this->rollback->snapshotDirectory($this->site . '/core/storage/bootstrap');
    mkdir($this->site . '/core/storage/bootstrap', 0777, true);
    file_put_contents($this->site . '/core/storage/bootstrap/packages.php', 'new manifest');

    expect($this->rollback->restore())->toBe([])
        ->and(is_dir($this->site . '/core/storage/bootstrap'))->toBeFalse();
});

test('a file written in place gets its contents back, or goes when it was not there', function () {
    file_put_contents($this->site . '/core/composer.installed.json', '{"old":1}');
    $this->rollback->snapshotFile($this->site . '/core/composer.installed.json');
    $this->rollback->snapshotFile($this->site . '/core/absent.json');

    file_put_contents($this->site . '/core/composer.installed.json', '{"new":1}');
    file_put_contents($this->site . '/core/absent.json', '{"new":1}');

    expect($this->rollback->restore())->toBe([])
        ->and(file_get_contents($this->site . '/core/composer.installed.json'))->toBe('{"old":1}')
        ->and(file_exists($this->site . '/core/absent.json'))->toBeFalse();
});

test('a file outside the site root is kept and restored too', function () {
    mkdir($this->root . '/elsewhere');
    file_put_contents($this->root . '/elsewhere/config.php', 'old config');

    rollbackTestLay($this->rollback, $this->root . '/elsewhere/config.php', 'new config');

    expect($this->rollback->restore())->toBe([])
        ->and(file_get_contents($this->root . '/elsewhere/config.php'))->toBe('old config');
});

test('discard keeps the new files and deletes the journal', function () {
    $this->rollback->snapshotDirectory($this->site . '/core/vendor');
    rollbackTestLay($this->rollback, $this->site . '/index.php', 'new front');

    $this->rollback->discard();

    expect(file_get_contents($this->site . '/index.php'))->toBe('new front')
        ->and(is_dir($this->journal))->toBeFalse()
        ->and($this->rollback->restore())->toBe([])
        ->and(file_get_contents($this->site . '/index.php'))->toBe('new front');
});

test('a restore that cannot finish says where and keeps the journal', function () {
    rollbackTestLay($this->rollback, $this->site . '/core/src/Old/Gone.php', 'new class');
    // Something put a file where the folder of a kept file was.
    rollbackTestRemove($this->site . '/core/src/Old');
    file_put_contents($this->site . '/core/src/Old', 'in the way');

    $failed = $this->rollback->restore();

    expect($failed)->toBe([$this->site . '/core/src/Old/Gone.php'])
        ->and(file_get_contents($this->journal . '/files/core/src/Old/Gone.php'))->toBe('old class');
});

test('the journal mirrors the layout of the site', function () {
    rollbackTestLay($this->rollback, $this->site . '/core/src/Old/Gone.php', 'new class');

    expect(file_get_contents($this->journal . '/files/core/src/Old/Gone.php'))->toBe('old class')
        ->and($this->rollback->journalPath())->toBe($this->journal);
});
