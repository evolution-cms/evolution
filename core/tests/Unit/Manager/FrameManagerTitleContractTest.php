<?php

namespace Tests\Unit\Manager;

use Tests\TestCase;

class FrameManagerTitleContractTest extends TestCase
{
    public function test_frame_title_uses_raw_manager_title_contract(): void
    {
        $view = file_get_contents(dirname(__DIR__, 4) . '/manager/views/frame/1.blade.php');

        $this->assertIsString($view);
        $this->assertStringContainsString(
            "\$managerTitle = evo()->getConfig('site_name') . ' - (Evolution CMS Manager)';",
            $view
        );
        $this->assertStringContainsString('<title>{!! $managerTitle !!}</title>', $view);
        $this->assertStringContainsString(
            'manager_title: {!! json_encode($managerTitle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},',
            $view
        );
        $this->assertStringNotContainsString(
            "<title>{{evo()->getConfig('site_name')}} - (Evolution CMS Manager)</title>",
            $view
        );
    }
}
