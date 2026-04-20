<?php

namespace Tests\Unit\Manager;

use Tests\TestCase;

class WebAccessPermissionsGroupIdsVisibilityTest extends TestCase
{
    private function getTemplate(): string
    {
        $path = __DIR__ . '/../../../../manager/views/page/web_access_permissions.blade.php';

        $contents = file_get_contents($path);
        $this->assertNotFalse($contents, 'Failed to read manager web access permissions template.');

        return $contents;
    }

    public function test_group_summary_labels_include_group_ids(): void
    {
        $template = $this->getTemplate();

        $this->assertStringContainsString('$usersInGroupLabel }} {{ $userGroup->name }} ({{ $userGroup->getKey() }}):', $template);
        $this->assertStringContainsString('$resourcesInGroupLabel }} {{ $documentGroup->name }} ({{ $documentGroup->getKey() }}):', $template);
    }

    public function test_group_link_views_include_group_ids(): void
    {
        $template = $this->getTemplate();

        $this->assertStringContainsString('{{ $userGroup->name }} ({{ $userGroup->getKey() }})</option>', $template);
        $this->assertStringContainsString('{{ $documentGroup->name }} ({{ $documentGroup->getKey() }})</option>', $template);
        $this->assertStringContainsString('<b>{{ $userGroup->name }} ({{ $userGroup->getKey() }})</b>', $template);
        $this->assertStringContainsString('{{ $documentGroup->name }} ({{ $documentGroup->getKey() }}) ({{ $documentGroup->pivot->context ? \'web\' : \'mgr\' }})', $template);
    }

    public function test_ukrainian_and_russian_labels_use_entity_names(): void
    {
        $uk = file_get_contents(__DIR__ . '/../../../../core/lang/uk/global.php');
        $ru = file_get_contents(__DIR__ . '/../../../../core/lang/ru/global.php');

        $this->assertNotFalse($uk, 'Failed to read the Ukrainian lexicon file.');
        $this->assertNotFalse($ru, 'Failed to read the Russian lexicon file.');

        $this->assertStringContainsString('$_lang["access_permissions_users_in_group"] = \'Користувачі в групі:\';', $uk);
        $this->assertStringContainsString('$_lang["access_permissions_resources_in_group"] = \'<b>Ресурси в групі:</b> \';', $uk);
        $this->assertStringContainsString('$_lang["access_permissions_users_in_group"] = \'Пользователи в группе:\';', $ru);
        $this->assertStringContainsString('$_lang["access_permissions_resources_in_group"] = \'<b>Ресурсы в группе:</b> \';', $ru);
    }
}
