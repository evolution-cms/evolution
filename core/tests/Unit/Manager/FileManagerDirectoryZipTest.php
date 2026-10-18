<?php

it('adds guarded directory zip downloads to the classic file manager', function () {
    // the archive itself is built by fileManagerCreateDirectoryZip() in the helpers
    $source = file_get_contents(dirname(__DIR__, 4) . '/manager/actions/files.dynamic.php')
        . file_get_contents(dirname(__DIR__, 3) . '/functions/actions/files.php');

    expect($source)
        ->toContain("sys_get_temp_dir()")
        ->toContain("sha1(rtrim(str_replace('\\\\', '/', EVO_BASE_PATH)")
        ->toContain("fopen(\$directoryZipPaths['lock'], 'x')")
        ->toContain("fileManagerIsAccessible(\$itemRelPath, \$userGroups, \$fileGroupsMap)")
        ->toContain("fileManagerPathIsProtected(fileManagerCanonicalCase(\$itemPath), \$protectedPaths)")
        ->toContain('fileManagerRelativeLinkTarget($filemanagerPath, $itemPath, null, $mayReveal)')
        ->toContain("fileManagerPathIsProtected(\$startpath, \$protected_path)")
        ->toContain("evo()->getConfig('denyZipDownload')")
        ->toContain("header('Content-Disposition: attachment; filename=\"' . fileManagerDirectoryZipName")
        ->toContain("readfile(\$directoryZipPaths['zip'])")
        ->toContain("mode=downloadzip")
        ->toContain("mode=deletezip")
        ->toContain("\$_lang['files_download_zip']")
        ->toContain("\$_lang['files_delete_zip']");
});

it('defines directory zip labels for every manager language', function () {
    foreach (glob(dirname(__DIR__, 3) . '/lang/*/global.php') as $file) {
        $_lang = [];
        include $file;

        expect($_lang)
            ->toHaveKey('files_download_zip')
            ->toHaveKey('files_delete_zip')
            ->toHaveKey('files_zip_in_progress')
            ->toHaveKey('files_zip_deleted')
            ->toHaveKey('files_zip_failed')
            ->toHaveKey('files_zip_unavailable')
            ->and($_lang['files_download_zip'])
            ->not->toBe('')
            ->and($_lang['files_delete_zip'])
            ->not->toBe('');
    }
});

it('keeps ambiguous upload labels distinct from zip download labels', function () {
    $languages = ['en', 'fr', 'fi', 'sk', 'uk'];

    foreach ($languages as $language) {
        $_lang = [];
        include dirname(__DIR__, 3) . '/lang/' . $language . '/global.php';

        expect(strtolower($_lang['files_uploadfile']))
            ->not->toBe(strtolower($_lang['files_download_zip']));
    }
});
