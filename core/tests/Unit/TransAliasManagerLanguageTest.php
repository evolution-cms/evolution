<?php

namespace Tests\Unit;

use Tests\TestCase;

require_once dirname(__DIR__, 3) . '/assets/plugins/transalias/transalias.class.php';

final class TransAliasManagerLanguageTest extends TestCase
{
    public function testManagerLanguageResolvesKnownTransliterationTables(): void
    {
        $transAlias = new \TransAlias();

        self::assertSame('russian', $transAlias->resolveTableName('utf8lowercase', 'ru'));
        self::assertSame('russian', $transAlias->resolveTableName('utf8lowercase', 'uk'));
        self::assertSame('german', $transAlias->resolveTableName('utf8lowercase', 'de'));
        self::assertSame('dutch', $transAlias->resolveTableName('utf8lowercase', 'nl'));
        self::assertSame('czech', $transAlias->resolveTableName('utf8lowercase', 'cs'));
        self::assertSame('common', $transAlias->resolveTableName('utf8lowercase', 'en'));
        self::assertSame('common', $transAlias->resolveTableName('utf8lowercase', 'fr'));
        self::assertSame('utf8lowercase', $transAlias->resolveTableName('utf8lowercase', 'ja'));
        self::assertSame('german', $transAlias->resolveTableName('german', 'ru'));
    }

    public function testCorePassesManagerLanguageIntoStripAliasPluginEvent(): void
    {
        $coreSource = (string) file_get_contents(dirname(__DIR__, 3) . '/core/src/Core.php');
        $pluginSource = (string) file_get_contents(dirname(__DIR__, 3) . '/assets/plugins/transalias/plugin.transalias.php');

        self::assertStringContainsString("'manager_language' => \$this->getConfig('manager_language')", $coreSource);
        self::assertStringContainsString("\$trans->resolveTableName(\$table_name, \$manager_language ?? null)", $pluginSource);
    }

    public function testAutomaticAliasMessageNoLongerMentionsLegacyTransAliasSetup(): void
    {
        $ukLanguage = (string) file_get_contents(dirname(__DIR__, 3) . '/core/lang/uk/global.php');
        $ruLanguage = (string) file_get_contents(dirname(__DIR__, 3) . '/core/lang/ru/global.php');

        self::assertStringNotContainsString('налаштуйте TransAlias', $ukLanguage);
        self::assertStringNotContainsString('настройте плагин TransAlias', $ruLanguage);
    }
}
