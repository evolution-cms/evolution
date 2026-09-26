<?php

namespace Tests\Unit\Manager;

use Tests\TestCase;

final class FileManagerAclSourceTest extends TestCase
{
    public function testClassicManagerSourceEnforcesAclOnGroupSaveAndView(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/actions/files.dynamic.php');

        self::assertStringContainsString("!fileManagerIsAccessible(\$groupsTargetPath, \$userGroups)", $source);
        self::assertStringContainsString("\$chkAllFiles && !\$canManageAllGroups", $source);
        self::assertStringContainsString("if (!empty(\$permissions) && \$canManageAllGroups)", $source);
        self::assertStringContainsString("\$canEditPathAcl = \$canManageAllGroups || empty(array_diff(\$directGroupIds, \$userGroups));", $source);
    }

    public function testKcfinderSourceDoesNotBypassWriteAclForFileManagerPermission(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/core/browser.php');

        self::assertStringNotContainsString("\$this->modx->hasPermission('file_manager')", $source);
        self::assertStringContainsString("FileManagerAccess::isAccessible(\$relPath, \$userGroups, \$rows)", $source);
        self::assertStringContainsString("!\\EvolutionCMS\\Support\\FileManagerAccess::isTopLevelPath", $source);
        self::assertStringContainsString("'files'       => \$this->getEntries(\$this->session['dir'])", $source);
    }

    public function testClassicManagerChecksTheResolvedPathOfEveryNamedEntry(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/actions/files.dynamic.php');

        // a bare prefix test admits a sibling such as alice-private next to the root alice
        self::assertDoesNotMatchRegularExpression('/strpos\(\$\w+, \$filemanager_path\)/', $source);
        self::assertStringContainsString('FileManagerAccess::isWithin($filemanager_path, $startpath)', $source);

        // the raw request is not what the ACL is keyed by: own/../private names private
        self::assertStringNotContainsString('fileManagerCanModifyExistingPath($requested_', $source);
        self::assertStringNotContainsString('$fileGroupsMap) || !is_writable', $source);
        foreach (['$folderTarget', '$fileTarget', '$dirTarget'] as $target) {
            self::assertStringContainsString("fileManagerCanModifyExistingPath({$target}['relative'], \$userGroups)", $source);
        }
        self::assertStringContainsString("fileManagerIsAccessible(\$viewTarget['relative'], \$userGroups)", $source);
        self::assertStringContainsString("\$groupsTargetPath = \$groupsTarget['relative'];", $source);

        self::assertStringContainsString("fileManagerHasInaccessibleDescendants(\$folderTarget['relative'])", $source);
        self::assertStringContainsString("fileManagerIsAccessible(\$zipTarget['relative'], \$userGroups)", $source);
        self::assertStringContainsString('$subtreeGroupsMap = fileManagerSubtreeRestrictionMap($relative_path);', $source);
        self::assertStringContainsString("'allDocGroups', 'userGroups')), EXTR_OVERWRITE);", $source);
    }

    public function testKcfinderChecksFileGroupsOnEveryReadAndChangeOfANamedEntry(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/core/browser.php');

        $acts = [
            'act_thumb' => 'isPathAccessible("{$this->typeDir}/{$this->get[\'dir\']}/$file")',
            'act_download' => '!$this->isPathAccessible($file)',
            'act_rename' => '!$this->isPathAccessible($file)',
            'act_delete' => '!$this->isPathAccessible($file)',
            'act_deleteDir' => '$this->hasInaccessibleDescendants($dir)',
            'act_cp_cbd' => '!$this->isPathAccessible($path)',
            'act_mv_cbd' => '!$this->isPathAccessible($path)',
            'act_rm_cbd' => '!$this->isPathAccessible($path)',
            'act_downloadDir' => 'new zipFolder($file, $dir, null, $this->subtreeAccessFilter($dir));',
            'act_downloadSelected' => '!$this->isPathAccessible($file)',
            'act_downloadClipboard' => '!$this->isPathAccessible($file)',
        ];
        foreach ($acts as $act => $check) {
            self::assertSame(1, preg_match('/function ' . $act . '\(\)\n    \{\n(.*?)\n    \}\n/s', $source, $body), "$act not found");
            self::assertStringContainsString($check, $body[1], "$act does not check file groups");
        }
    }

    public function testFileGroupsAreKeyedBySiteRootAndFollowEveryRenameAndDelete(): void
    {
        $classic = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/actions/files.dynamic.php');
        $helpers = (string) file_get_contents(dirname(__DIR__, 4) . '/core/functions/actions/files.php');
        $browser = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/core/browser.php');

        // a personal filemanager_path must not change which rows a path hits
        self::assertStringContainsString('FileManagerAccess::aclKey($roots[0], $roots[1], $relativePath)', $helpers);
        self::assertStringContainsString("\$groupsKey = \$groupsTarget === null ? null : fileManagerAclKey(\$groupsTargetPath);", $classic);
        self::assertStringContainsString("FileGroup::query()->where('file', \$groupsKey)->get();", $classic);
        self::assertStringNotContainsString("where('file', \$groupsTargetPath)", $classic);
        self::assertStringContainsString('\EvolutionCMS\Support\FileManagerAccess::aclRoot(),', $browser);
        self::assertStringNotContainsString("getConfig('filemanager_path', EVO_BASE_PATH)", $browser);

        // rows follow the entry whoever renames or deletes it, not only group managers
        self::assertSame(2, substr_count($classic, 'FileManagerAccess::moveRestrictions('));
        self::assertStringContainsString("FileManagerAccess::forgetRestrictions(fileManagerAclKey(\$folderTarget['relative']));", $classic);
        self::assertStringContainsString('FileManagerAccess::forgetRestrictions(fileManagerAclKey($fileRel));', $helpers);
        self::assertStringNotContainsString("orWhere('file', 'like'", $classic);

        $acts = [
            'act_renameDir' => '$this->moveFileGroups($oldKey,',
            'act_rename' => '$this->moveFileGroups($oldKey, $newName);',
            'act_mv_cbd' => '$this->moveFileGroups($oldKey, "$dir/$base");',
            'act_delete' => '$this->forgetFileGroups($oldKey);',
            'act_rm_cbd' => '$this->forgetFileGroups($oldKey);',
            'act_deleteDir' => '$this->forgetFileGroups($oldKey);',
        ];
        foreach ($acts as $act => $call) {
            self::assertSame(1, preg_match('/function ' . $act . '\(\)\n    \{\n(.*?)\n    \}\n/s', $browser, $body), "$act not found");
            self::assertStringContainsString($call, $body[1], "$act leaves file groups behind");
        }
    }

    public function testClassicManagerHelpersExposeAclEnforcementHooks(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 4) . '/core/functions/actions/files.php');

        self::assertStringContainsString('function fileManagerIsAccessible', $source);
        self::assertStringContainsString('function fileManagerCanModifyExistingPath', $source);
        self::assertStringContainsString("if (!fileManagerCanModifyExistingPath(\$fileRel)", $source);
        self::assertStringContainsString("if (!fileManagerIsAccessible(\$dirRel)", $source);
    }
}
