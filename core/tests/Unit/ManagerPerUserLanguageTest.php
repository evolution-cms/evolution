<?php

namespace Tests\Unit;

use EvolutionCMS\ManagerTheme;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * core/factory/settings.php reads the manager lexicon, so the ManagerTheme singleton is
 * already built - with the system theme and language - by the time Core::getSettings()
 * merges the per-user settings. Without an explicit refresh a user-level manager_language
 * or manager_theme never reaches the manager UI: the whole backend stays on the site-wide
 * value.
 */
final class ManagerPerUserLanguageTest extends TestCase
{
    private static string $coreSource = '';

    public static function setUpBeforeClass(): void
    {
        $rootDir = dirname(__DIR__, 3);
        require_once $rootDir . '/core/vendor/autoload.php';

        self::$coreSource = (string) file_get_contents($rootDir . '/core/src/Core.php');
    }

    public function testFactorySettingsStillNeedTheManagerLexicon(): void
    {
        $factorySettings = (string) file_get_contents(dirname(__DIR__, 2) . '/factory/settings.php');

        // This is what forces ManagerTheme to be resolved before the user settings are known.
        self::assertStringContainsString('ManagerTheme::getLexicon(', $factorySettings);
    }

    public function testManagerThemeCanReloadItsLexicon(): void
    {
        $method = new ReflectionMethod(ManagerTheme::class, 'reloadLang');

        self::assertTrue($method->isPublic());
        self::assertSame(1, $method->getNumberOfRequiredParameters());
    }

    public function testCoreRefreshesTheManagerThemeAfterMergingUserSettings(): void
    {
        $mergePosition = mb_strpos(self::$coreSource, '$this->getUserSettings();');
        $syncPosition = mb_strpos(self::$coreSource, '$this->syncManagerTheme();');

        self::assertIsInt($mergePosition);
        self::assertIsInt($syncPosition);
        self::assertGreaterThan($mergePosition, $syncPosition);
    }

    public function testManagerThemeRefreshOnlyTouchesAnAlreadyBuiltBackendTheme(): void
    {
        $sync = mb_substr(
            self::$coreSource,
            (int) mb_strpos(self::$coreSource, 'protected function syncManagerTheme')
        );
        $sync = mb_substr($sync, 0, (int) mb_strpos($sync, "\n    }"));

        self::assertStringContainsString('$this->isBackend()', $sync);
        self::assertStringContainsString("\$this->resolved('ManagerTheme')", $sync);
        self::assertStringContainsString('reloadLang(', $sync);
        // A different theme cannot be patched in place - the instance has to go.
        self::assertStringContainsString("forgetInstance('ManagerTheme')", $sync);
        self::assertStringContainsString("Facade::clearResolvedInstance('ManagerTheme')", $sync);
    }
}
