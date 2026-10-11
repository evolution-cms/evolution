<?php

use EvolutionCMS\Support\FileManagerAccess;

test('file manager access requires every restricted ancestor to match', function () {
    $restrictions = [
        'articles' => [10],
        'articles/one' => [11],
        'articles-common/onecommon' => [11],
    ];

    expect(FileManagerAccess::isAccessible('articles', [10], $restrictions))->toBeTrue()
        ->and(FileManagerAccess::isAccessible('articles/one', [10], $restrictions))->toBeFalse()
        ->and(FileManagerAccess::isAccessible('articles/one', [10, 11], $restrictions))->toBeTrue()
        ->and(FileManagerAccess::isAccessible('articles-common/onecommon', [11], $restrictions))->toBeTrue();
});

test('file manager access collects inherited effective groups for display', function () {
    $restrictions = [
        'articles' => [10],
        'articles/one' => [11],
        'articles/one/child' => [11, 12],
    ];

    expect(FileManagerAccess::effectiveGroupIds('articles/one/child', $restrictions))
        ->toBe([10, 11, 12]);
});

test('file manager access prevents modifying top level entries', function () {
    $restrictions = [
        'articles' => [10],
        'articles/one' => [11],
    ];

    expect(FileManagerAccess::canModifyExistingPath('articles', [10], $restrictions))->toBeFalse()
        ->and(FileManagerAccess::canModifyExistingPath('articles/one', [10, 11], $restrictions))->toBeTrue()
        ->and(FileManagerAccess::canModifyExistingPath('articles/one', [10], $restrictions))->toBeFalse();
});

test('a root contains itself and what lies below it, not a sibling sharing its prefix', function () {
    expect(FileManagerAccess::isWithin('/site/assets/alice', '/site/assets/alice'))->toBeTrue()
        ->and(FileManagerAccess::isWithin('/site/assets/alice', '/site/assets/alice/'))->toBeTrue()
        ->and(FileManagerAccess::isWithin('/site/assets/alice', '/site/assets/alice/photos/a.jpg'))->toBeTrue()
        ->and(FileManagerAccess::isWithin('/site/assets/alice/', '/site/assets/alice/photos'))->toBeTrue()
        ->and(FileManagerAccess::isWithin('C:\\site\\alice', 'C:/site/alice/x'))->toBeTrue()
        ->and(FileManagerAccess::isWithin('/site/assets/alice', '/site/assets/alice-private'))->toBeFalse()
        ->and(FileManagerAccess::isWithin('/site/assets/alice', '/site/assets/alicex/a.jpg'))->toBeFalse()
        ->and(FileManagerAccess::isWithin('/site/assets/alice', '/site/assets'))->toBeFalse()
        ->and(FileManagerAccess::isWithin('/site/assets/alice', ''))->toBeFalse()
        ->and(FileManagerAccess::isWithin('', '/site/assets'))->toBeFalse();
});

function fileManagerSandbox(): string
{
    $root = sys_get_temp_dir() . '/evo-fm-' . bin2hex(random_bytes(4));
    foreach (['alice/own', 'alice/private', 'alice-private'] as $dir) {
        mkdir($root . '/' . $dir, 0777, true);
    }
    file_put_contents($root . '/alice/private/secret.txt', 'x');
    file_put_contents($root . '/alice-private/secret.txt', 'x');

    return str_replace('\\', '/', realpath($root));
}

function removeFileManagerSandbox(string $root): void
{
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($root);
}

test('the relative path of a sibling that shares the root prefix is empty, not a path', function () {
    $sandbox = fileManagerSandbox();

    try {
        expect(FileManagerAccess::getRelativePath($sandbox . '/alice', $sandbox . '/alice-private/secret.txt'))->toBe('')
            ->and(FileManagerAccess::getRelativePath($sandbox . '/alice', $sandbox . '/alice/private/secret.txt'))->toBe('private/secret.txt');
    } finally {
        removeFileManagerSandbox($sandbox);
    }
});

test('a requested path is resolved before the ACL sees it', function () {
    $sandbox = fileManagerSandbox();
    $root = $sandbox . '/alice';

    try {
        // the raw path names own/.., whose ancestors carry no restriction; the target is private
        expect(fileManagerResolvePath($root, 'own/../private/secret.txt'))
            ->toBe(['path' => $root . '/private/secret.txt', 'relative' => 'private/secret.txt'])
            ->and(fileManagerResolvePath($root, '/own'))->toBe(['path' => $root . '/own', 'relative' => 'own'])
            ->and(fileManagerResolvePath($root, ''))->toBe(['path' => $root, 'relative' => ''])
            ->and(fileManagerResolvePath($root, '../alice-private/secret.txt'))->toBeNull()
            ->and(fileManagerResolvePath($root, '../../'))->toBeNull()
            ->and(fileManagerResolvePath($root, 'missing'))->toBeNull();
    } finally {
        removeFileManagerSandbox($sandbox);
    }
});

test('restricted descendants the user cannot reach are reported, reachable ones are not', function () {
    $restrictions = [
        'shared' => [10],
        'shared/team' => [10],
        'shared/team/hr' => [12],
        'shared/other' => [11],
        'shared-archive/x' => [12],
    ];

    expect(FileManagerAccess::inaccessibleDescendants('shared', [10], $restrictions))->toBe(['shared/team/hr', 'shared/other'])
        ->and(FileManagerAccess::inaccessibleDescendants('shared', [10, 11, 12], $restrictions))->toBe([])
        ->and(FileManagerAccess::inaccessibleDescendants('shared/team', [10], $restrictions))->toBe(['shared/team/hr'])
        // the folder itself and a prefix-sharing sibling are not descendants
        ->and(FileManagerAccess::inaccessibleDescendants('shared/team/hr', [10], $restrictions))->toBe([])
        ->and(FileManagerAccess::inaccessibleDescendants('', [10, 11], $restrictions))->toBe(['shared/team/hr', 'shared-archive/x']);
});

test('subtree restrictions cover ancestors and every level below, and nothing beside', function () {
    $capsule = new Illuminate\Database\Capsule\Manager();
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    $capsule->schema()->create('file_groups', function ($table) {
        $table->increments('id');
        $table->integer('document_group');
        $table->string('file');
    });
    Illuminate\Database\Capsule\Manager::table('file_groups')->insert([
        ['document_group' => 10, 'file' => 'shared'],
        ['document_group' => 12, 'file' => 'shared/team/hr/pay.pdf'],
        ['document_group' => 11, 'file' => 'shared/team/hr/pay.pdf'],
        ['document_group' => 13, 'file' => 'shared_x/y'],
        ['document_group' => 14, 'file' => 'sharedx/y'],
        ['document_group' => 16, 'file' => 'sharedax/y'],
        ['document_group' => 15, 'file' => 'other'],
    ]);

    try {
        $restrictions = FileManagerAccess::loadSubtreeRestrictions('shared/team');

        ksort($restrictions);
        expect($restrictions)->toBe([
            'shared' => [10],
            'shared/team/hr/pay.pdf' => [12, 11],
        ])
            // nothing to cover below the root means every row
            // the _ in shared_x is a LIKE wildcard that also matches sharedax
            ->and(FileManagerAccess::loadSubtreeRestrictions('shared_x'))->toBe(['shared_x/y' => [13]])
            ->and(array_keys(FileManagerAccess::loadSubtreeRestrictions('')))->toHaveCount(6);
    } finally {
        $capsule->getConnection()->disconnect();
    }
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('a path below a personal root is keyed by the site-wide root', function () {
    // the admin restricts assets/images/hr; alice's file manager starts at assets
    expect(FileManagerAccess::aclKey('/site', '/site/assets', 'images/hr/ceo.png'))->toBe('assets/images/hr/ceo.png')
        ->and(FileManagerAccess::aclKey('/site', '/site/assets/', ''))->toBe('assets')
        ->and(FileManagerAccess::aclKey('/site', '/site', 'assets/x'))->toBe('assets/x')
        ->and(FileManagerAccess::aclKey('/site/', '/site', ''))->toBe('')
        ->and(FileManagerAccess::aclKey('C:\\site', 'C:/site/assets', 'a'))->toBe('assets/a');
});

test('a personal root above the site-wide one keys only what lies inside it', function () {
    expect(FileManagerAccess::aclKey('/site/public', '/site', 'public/images/a.png'))->toBe('images/a.png')
        ->and(FileManagerAccess::aclKey('/site/public', '/site', 'public'))->toBe('')
        ->and(FileManagerAccess::aclKey('/site/public', '/site', 'public-old/a.png'))->toBeNull()
        ->and(FileManagerAccess::aclKey('/site/public', '/site', 'logs/a.log'))->toBeNull();
});

test('a personal root beside the site-wide one has no keys', function () {
    expect(FileManagerAccess::aclKey('/site/public', '/site/public-old', 'a.png'))->toBeNull()
        ->and(FileManagerAccess::aclKey('/site/public', '/elsewhere', 'a.png'))->toBeNull();
});

function fileGroupsTable(array $rows): Illuminate\Database\Capsule\Manager
{
    $capsule = new Illuminate\Database\Capsule\Manager();
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    $capsule->schema()->create('file_groups', function ($table) {
        $table->increments('id');
        $table->integer('document_group');
        $table->string('file');
    });
    Illuminate\Database\Capsule\Manager::table('file_groups')->insert($rows);

    return $capsule;
}

function fileGroupRows(): array
{
    return Illuminate\Database\Capsule\Manager::table('file_groups')->orderBy('file')->pluck('file')->all();
}

test('groups follow a renamed folder, its descendants included, and nothing beside it', function () {
    $capsule = fileGroupsTable([
        ['document_group' => 1, 'file' => 'files/team_a'],
        ['document_group' => 1, 'file' => 'files/team_a/hr/pay.pdf'],
        ['document_group' => 2, 'file' => 'files/teamXa/other.txt'],
        ['document_group' => 2, 'file' => 'files/team_ab'],
    ]);

    try {
        FileManagerAccess::moveRestrictions('files/team_a', 'files/team_b');

        // _ is a LIKE wildcard: teamXa and team_ab must stay where they are
        expect(fileGroupRows())->toBe(['files/teamXa/other.txt', 'files/team_ab', 'files/team_b', 'files/team_b/hr/pay.pdf']);

        FileManagerAccess::moveRestrictions('files/team_b', 'files/team_b');
        FileManagerAccess::moveRestrictions('', 'files/x');
        expect(fileGroupRows())->toHaveCount(4);
    } finally {
        $capsule->getConnection()->disconnect();
    }
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('groups of a removed entry and everything below it are dropped, and nothing beside it', function () {
    $capsule = fileGroupsTable([
        ['document_group' => 1, 'file' => 'files/team_a'],
        ['document_group' => 1, 'file' => 'files/team_a/hr/pay.pdf'],
        ['document_group' => 2, 'file' => 'files/teamXa/other.txt'],
        ['document_group' => 2, 'file' => 'files/team_ab'],
    ]);

    try {
        FileManagerAccess::forgetRestrictions('files/team_a');
        expect(fileGroupRows())->toBe(['files/teamXa/other.txt', 'files/team_ab']);

        // the ACL root is never dropped wholesale
        FileManagerAccess::forgetRestrictions('');
        FileManagerAccess::forgetRestrictions(null);
        expect(fileGroupRows())->toHaveCount(2);
    } finally {
        $capsule->getConnection()->disconnect();
    }
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

it('compares paths case-insensitively only where the file system is', function () {
    $within = FileManagerAccess::isWithin('/site/assets/plugins', '/site/ASSETS/Plugins/demo/note.txt');

    expect($within)->toBe(PHP_OS_FAMILY === 'Windows');
});

test('a copy is held to every restriction of the original at once, or refused', function () {
    $capsule = fileGroupsTable([
        ['document_group' => 1, 'file' => 'files/hr'],
        ['document_group' => 2, 'file' => 'files/hr'],
        ['document_group' => 2, 'file' => 'files/hr/pay.pdf'],
        ['document_group' => 3, 'file' => 'files/hr/pay.pdf'],
        ['document_group' => 5, 'file' => 'files/a'],
        ['document_group' => 6, 'file' => 'files/a/x.txt'],
        ['document_group' => 7, 'file' => 'files/solo/own.txt'],
    ]);

    try {
        // folder [1,2] and file [2,3] mean "in 1 or 2, and in 2 or 3": only group 2 satisfies both
        expect(FileManagerAccess::carriedRestrictions('files/hr/pay.pdf', 'files/public/pay.pdf'))->toBe([2]);

        // folder [5] and file [6] share nothing: one set of groups cannot hold the copy to both
        expect(FileManagerAccess::carriedRestrictions('files/a/x.txt', 'files/public/x.txt'))->toBeNull();

        // a copy beside the original is already held to its folders: only the file's own groups remain
        expect(FileManagerAccess::carriedRestrictions('files/hr/pay.pdf', 'files/hr/copy.pdf'))->toBe([2, 3]);
        expect(FileManagerAccess::carriedRestrictions('files/a/x.txt', 'files/a/copy.txt'))->toBe([6]);

        expect(FileManagerAccess::carriedRestrictions('files/solo/own.txt', 'files/public/own.txt'))->toBe([7])
            ->and(FileManagerAccess::carriedRestrictions('files/open/a.txt', 'files/public/a.txt'))->toBe([])
            ->and(FileManagerAccess::carriedRestrictions('', 'files/public/a.txt'))->toBe([])
            ->and(FileManagerAccess::carriedRestrictions('files/hr/pay.pdf', ''))->toBeNull()
            ->and(FileManagerAccess::carriedRestrictions('files/hr/pay.pdf', 'files/hr/pay.pdf'))->toBe([]);
    } finally {
        $capsule->getConnection()->disconnect();
    }
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('direct restrictions can be added without duplicates and replaced by a narrower set', function () {
    $capsule = fileGroupsTable([
        ['document_group' => 1, 'file' => 'files/x.txt'],
        ['document_group' => 2, 'file' => 'files/x.txt'],
        ['document_group' => 9, 'file' => 'files/other.txt'],
    ]);

    try {
        FileManagerAccess::addRestrictions('files/x.txt', [2, 3]);
        $groupsOf = static fn (string $file) => Illuminate\Database\Capsule\Manager::table('file_groups')
            ->where('file', $file)->orderBy('document_group')->pluck('document_group')->map('intval')->all();
        expect($groupsOf('files/x.txt'))->toBe([1, 2, 3]);

        FileManagerAccess::replaceRestrictions('files/x.txt', [2]);
        expect($groupsOf('files/x.txt'))->toBe([2])
            ->and($groupsOf('files/other.txt'))->toBe([9]);

        FileManagerAccess::addRestrictions('', [1]);
        FileManagerAccess::addRestrictions('files/y.txt', []);
        FileManagerAccess::replaceRestrictions('', [1]);
        expect(fileGroupRows())->toHaveCount(2);
    } finally {
        $capsule->getConnection()->disconnect();
    }
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('a rename or a copy drops stale rows left at its destination', function () {
    $capsule = fileGroupsTable([
        ['document_group' => 1, 'file' => 'files/a.txt'],
        ['document_group' => 9, 'file' => 'files/b.txt'],
        ['document_group' => 8, 'file' => 'files/c.txt'],
        ['document_group' => 9, 'file' => 'files/dir/old.txt'],
        ['document_group' => 1, 'file' => 'files/src'],
    ]);

    try {
        $groupsOf = static fn (string $file) => Illuminate\Database\Capsule\Manager::table('file_groups')
            ->where('file', $file)->orderBy('document_group')->pluck('document_group')->map('intval')->all();

        // group 9 is a leftover of a file removed outside the CMS
        FileManagerAccess::moveRestrictions('files/a.txt', 'files/b.txt');
        expect($groupsOf('files/b.txt'))->toBe([1])->and($groupsOf('files/a.txt'))->toBe([]);

        // a copy replaces them with what it carries, and an unrestricted copy leaves none
        FileManagerAccess::replaceRestrictions('files/c.txt', [2]);
        expect($groupsOf('files/c.txt'))->toBe([2]);
        FileManagerAccess::replaceRestrictions('files/c.txt', []);
        expect($groupsOf('files/c.txt'))->toBe([]);

        // stale rows below a folder destination go too
        FileManagerAccess::moveRestrictions('files/src', 'files/dir');
        expect($groupsOf('files/dir/old.txt'))->toBe([])->and($groupsOf('files/dir'))->toBe([1]);
    } finally {
        $capsule->getConnection()->disconnect();
    }
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('a copy and a rename see only the rows of their own path, not those of a sibling that differs by case', function () {
    if (PHP_OS_FAMILY === 'Windows') {
        $this->markTestSkipped('paths that differ by case are one path here');
    }
    $capsule = fileGroupsTable([
        ['document_group' => 1, 'file' => 'files/Report.txt'],
        ['document_group' => 9, 'file' => 'files/REPORT.txt'],
    ]);

    try {
        $groupsOf = static fn (string $file) => Illuminate\Database\Capsule\Manager::table('file_groups')
            ->where('file', $file)->orderBy('document_group')->pluck('document_group')->map('intval')->all();

        FileManagerAccess::addRestrictions('files/report.txt', [1]);
        expect($groupsOf('files/report.txt'))->toBe([1]);

        // the stale rows of the exact destination go; those of its sibling stay
        FileManagerAccess::moveRestrictions('files/Report.txt', 'files/REPORT.txt');
        expect($groupsOf('files/REPORT.txt'))->toBe([1])->and($groupsOf('files/report.txt'))->toBe([1]);

        FileManagerAccess::replaceRestrictions('files/report.txt', []);
        expect($groupsOf('files/REPORT.txt'))->toBe([1]);
    } finally {
        $capsule->getConnection()->disconnect();
    }
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');
