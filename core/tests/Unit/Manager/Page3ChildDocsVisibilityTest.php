<?php

namespace Tests\Unit\Manager;

use Tests\TestCase;

final class Page3ChildDocsVisibilityTest extends TestCase
{
    public function testNonAdminChildVisibilityFiltersStayOnTheChildQuery(): void
    {
        $view = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/views/page/3.blade.php');

        self::assertStringContainsString("\$childs = \$childs->where(function (\$q) {", $view);
        self::assertStringContainsString("\$childs = \$childs->where('site_content.privatemgr', 0);", $view);
        self::assertStringNotContainsString("\$childs = \$resources->where(function (\$q) {", $view);
        self::assertStringNotContainsString("\$childs = \$resources->where('site_content.privatemgr', 0);", $view);
    }
}
