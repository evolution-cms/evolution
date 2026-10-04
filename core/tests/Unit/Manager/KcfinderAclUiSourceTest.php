<?php

namespace Tests\Unit\Manager;

use Tests\TestCase;

final class KcfinderAclUiSourceTest extends TestCase
{
    public function testFolderTreeScriptKeepsRenamedSubtreePathsInSync(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/js/browser/folders.js');

        self::assertStringContainsString('browser.updateDirSubtreePaths = function(dir, oldPath, newPath)', $source);
        self::assertStringContainsString("browser.dir.indexOf(oldPath + '/') === 0", $source);
        self::assertStringContainsString("item.dir.indexOf(oldPath + '/') === 0", $source);
        self::assertStringContainsString("this.label(\"folders\") + ', ' + files + ' ' + this.label(\"files\")", $source);
    }

    public function testFilePaneScriptSupportsFolderTilesAndFolderContextMenu(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/js/browser/files.js');

        self::assertStringContainsString("if ($(this).data('isDir'))", $source);
        self::assertStringContainsString('browser.menuFolder = function(file, e)', $source);
        self::assertStringContainsString("browser.menuDir(dir, e);", $source);
        self::assertStringContainsString("var icon = file.isDir ? 'folder' :", $source);
        self::assertStringContainsString("themes/' + browser.theme + '/img/files/big/folder.png", $source);
    }

    public function testOrderingAndSettingsScriptsProtectAclUiBehavior(): void
    {
        $miscSource = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/js/browser/misc.js');
        $settingsSource = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/js/browser/settings.js');

        self::assertStringContainsString('if (!!a.isDir != !!b.isDir)', $miscSource);
        self::assertStringContainsString('if (el) el.checked = checked;', $settingsSource);
        self::assertStringContainsString('if (orderEl) orderEl.checked = true;', $settingsSource);
        self::assertStringContainsString('if (viewEl) viewEl.checked = true;', $settingsSource);
    }

    public function testUploaderScriptUsesFilePaneDropzoneAndSkipsFolderTiles(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/js/browser/uploader.js');

        self::assertStringContainsString('browser.initFilesDropGuards();', $source);
        self::assertStringContainsString('browser.initFilesDropzone();', $source);
        self::assertStringContainsString("browser.bindDropListeners(document, '_filesDropGuardHandlers'", $source);
        self::assertStringContainsString("browser.bindDropListeners(filesPane, '_filesDropzoneHandlers'", $source);
        self::assertStringContainsString('browser.prepareFiles(this.files);', $source);
        self::assertStringContainsString('browser.preventExternalFileDropDefault = function(evt)', $source);
        self::assertStringContainsString('browser.extractDroppedFiles = function(evt)', $source);
        self::assertStringContainsString('dataTransfer.files', $source);
        self::assertStringContainsString("if (typeof types.contains === 'function')", $source);
        self::assertStringContainsString("browser.isFilesDropzoneElement = function(target)", $source);
        self::assertStringContainsString("browser.isFilesDropTarget = function(target)", $source);
        self::assertStringContainsString("$(target).closest('.file', '#files')", $source);
        self::assertStringContainsString("!file.length || !file.data('isDir')", $source);
        self::assertStringContainsString('!browser.allowedExts.test(ext) || browser.deniedExts.test(ext)', $source);
        self::assertStringContainsString('browser.maxFileSize <= file.size', $source);
        self::assertStringNotContainsString('FileAPI', $source);
        self::assertStringContainsString('new FormData()', $source);
        self::assertStringContainsString('new XMLHttpRequest()', $source);
        self::assertStringContainsString("data.append('file', blob, file.name);", $source);
        self::assertStringContainsString("data.append('dir', browser.dir);", $source);
        self::assertStringContainsString("browser.baseGetData('upload')", $source);
        self::assertStringContainsString('window.createImageBitmap', $source);
        self::assertStringContainsString('canvas.toBlob', $source);
    }

    public function testUploaderLoadsWithoutTheLegacyFlashFileApiBundle(): void
    {
        $root = dirname(__DIR__, 4);
        $template = (string) file_get_contents($root . '/manager/media/browser/mcpuk/tpl/tpl_javascript.php');

        self::assertStringNotContainsString('js/FileAPI/', $template);
        self::assertFileDoesNotExist($root . '/manager/media/browser/mcpuk/js/FileAPI/FileAPI.flash.swf');
        self::assertFileDoesNotExist($root . '/manager/media/browser/mcpuk/js/FileAPI/FileAPI.flash.image.swf');
        self::assertFileDoesNotExist($root . '/manager/media/browser/mcpuk/js/FileAPI/FileAPI.flash.camera.swf');
    }

    public function testFolderTileAssetsExistForBothThemes(): void
    {
        self::assertFileExists(dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/themes/evo/img/files/big/folder.png');
        self::assertFileExists(dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/themes/evo/img/files/small/folder.png');
        self::assertFileExists(dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/themes/oxygen/img/files/big/folder.png');
        self::assertFileExists(dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/themes/oxygen/img/files/small/folder.png');
    }
}
