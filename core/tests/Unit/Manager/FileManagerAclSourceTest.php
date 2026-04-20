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

    public function testClassicManagerHelpersExposeAclEnforcementHooks(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 4) . '/core/functions/actions/files.php');

        self::assertStringContainsString('function fileManagerIsAccessible', $source);
        self::assertStringContainsString('function fileManagerCanModifyExistingPath', $source);
        self::assertStringContainsString("if (!fileManagerCanModifyExistingPath(\$fileRel)", $source);
        self::assertStringContainsString("if (!fileManagerIsAccessible(\$dirRel)", $source);
    }
}
