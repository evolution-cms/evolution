<?php

/*
|--------------------------------------------------------------------------
| Embedded file browser access
|--------------------------------------------------------------------------
|
| browse.php checked a permission only for type=images and type=files, so media, image, file,
| a missing type and an unknown one (both open the files folder) needed none. A manager whose
| image_base_upload_dir could not be resolved was silently given the whole rb_base_dir, and the
| root test was a bare prefix match.
|
| @since 3.5.9
*/

use EvolutionCMS\Support\FileBrowserAccess;

function fileBrowserSite(): string
{
    $site = sys_get_temp_dir() . '/evo-fb-' . bin2hex(random_bytes(4));
    foreach (['assets/images', 'assets/alice/images', 'assets2/images', 'uploads/bob'] as $dir) {
        mkdir($site . '/' . $dir, 0777, true);
    }
    mkdir($site . '-outside', 0777, true);

    return str_replace('\\', '/', realpath($site)) . '/';
}

function removeFileBrowserSite(string $site): void
{
    foreach ([rtrim($site, '/'), rtrim($site, '/') . '-outside'] as $root) {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            rmdir($item->getPathname());
        }
        rmdir($root);
    }
}

describe('FileBrowserAccess::permissionFor()', function () {

    test('image types need assets_images', function () {
        expect(FileBrowserAccess::permissionFor('images'))->toBe('assets_images')
            ->and(FileBrowserAccess::permissionFor('image'))->toBe('assets_images');
    });

    test('every other type, a missing one and an unknown one need assets_files', function (?string $type) {
        expect(FileBrowserAccess::permissionFor($type))->toBe('assets_files');
    })->with(['files', 'file', 'media', null, '', 'flash', 'Images', '../images']);
});

describe('FileBrowserAccess::resolveUploadRoot()', function () {

    test('no personal root means rb_base_dir', function () {
        $site = fileBrowserSite();
        try {
            expect(FileBrowserAccess::resolveUploadRoot('[(base_path)]assets/', 'assets/', '', $site, '/'))
                ->toBe([$site . 'assets', 'assets']);
        } finally {
            removeFileBrowserSite($site);
        }
    });

    test('a relative personal root lies below rb_base_dir, existing or not', function () {
        $site = fileBrowserSite();
        try {
            expect(FileBrowserAccess::resolveUploadRoot('[(base_path)]assets/', 'assets/', 'alice', $site, '/'))
                ->toBe([$site . 'assets/alice', 'assets/alice'])
                ->and(FileBrowserAccess::resolveUploadRoot('[(base_path)]assets/', 'assets/', 'carol/', $site, '/'))
                ->toBe([$site . 'assets/carol', 'assets/carol']);
        } finally {
            removeFileBrowserSite($site);
        }
    });

    test('an absolute personal root inside the site gets a URL relative to the site', function () {
        $site = fileBrowserSite();
        try {
            expect(FileBrowserAccess::resolveUploadRoot('[(base_path)]assets/', 'assets/', '[(base_path)]uploads/bob', $site, '/evo/'))
                ->toBe([$site . 'uploads/bob', '/evo/uploads/bob']);
        } finally {
            removeFileBrowserSite($site);
        }
    });

    test('a sibling sharing the prefix of rb_base_dir is not taken for a folder below it', function () {
        $site = fileBrowserSite();
        try {
            // the prefix test used to answer [.../assets2, 'assets/2']
            expect(FileBrowserAccess::resolveUploadRoot('[(base_path)]assets/', 'assets/', '[(base_path)]assets2', $site, ''))
                ->toBe([$site . 'assets2', '/assets2']);
        } finally {
            removeFileBrowserSite($site);
        }
    });

    test('a personal root that cannot be used refuses instead of falling back to rb_base_dir', function (string $custom) {
        $site = fileBrowserSite();
        try {
            $custom = str_replace('{outside}', rtrim($site, '/') . '-outside', $custom);
            expect(FileBrowserAccess::resolveUploadRoot('[(base_path)]assets/', 'assets/', $custom, $site, '/'))->toBeNull();
        } finally {
            removeFileBrowserSite($site);
        }
    })->with([
        'outside the site' => ['{outside}'],
        'filesystem root' => ['/'],
        'climbing out, relative' => ['../../'],
        'climbing out, missing folder' => ['alice/../../../nowhere'],
        'climbing back in' => ['alice/../images'],
    ]);
});

describe('call sites', function () {

    test('browse.php asks for a permission for every type', function () {
        $source = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/browse.php');

        expect($source)
            ->toContain('FileBrowserAccess::permissionFor(')
            ->toContain("!EvolutionCMS()->hasPermission('file_manager') && !EvolutionCMS()->hasPermission(\$typePermission)")
            ->not->toContain("\$_GET['type'] == 'images'")
            // there is no global $_lang in the browser; the refusal used to be an empty 200
            ->toContain("header('HTTP/1.1 403 Forbidden');")
            ->toContain("sprintf(__('global.files_management_no_permission'), \$role)")
            ->not->toContain('global $_lang;');
        expect(strpos($source, 'permissionFor('))->toBeLessThan(strpos($source, 'new browser('));
    });

    test('an unresolvable root disables the browser before it touches the disk', function () {
        $config = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/config.php');
        $uploader = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/core/uploader.php');

        expect($config)
            ->toContain('FileBrowserAccess::resolveUploadRoot(')
            ->toContain("'disabled' => \$uploadRoot === null,");
        expect($uploader)->toContain("__('global.files_management_no_permission'), __('global.image_base_upload_dir_title')");
        $guard = strpos($uploader, "if (!empty(\$this->config['disabled'])) {");
        expect($guard)->toBeGreaterThan(0)
            ->and($guard)->toBeLessThan(strpos($uploader, '@mkdir($this->config[\'uploadDir\']'));
    });
});
