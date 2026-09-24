<?php

namespace Tests\Unit;

use EvolutionCMS\ManagerTheme;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Factory defaults use Laravel's translator. The backend still builds ManagerTheme
 * before per-user settings are merged, then refreshes it for a user's theme or language.
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

    public function testFactorySettingsUseTheSelectedLocaleWithoutResolvingTheTheme(): void
    {
        $factorySettings = (string) file_get_contents(dirname(__DIR__, 2) . '/factory/settings.php');
        $settingsTrait = (string) file_get_contents(dirname(__DIR__, 2) . '/src/Traits/Settings.php');

        self::assertStringNotContainsString('ManagerTheme::getLexicon(', $factorySettings);
        self::assertStringContainsString("__('global.emailsubject_default', [], \$factoryLocale)", $factorySettings);
        self::assertStringContainsString('if ($this->isBackend())', $settingsTrait);
        self::assertStringContainsString("\$this['ManagerTheme'];", $settingsTrait);
    }

    public function testFactoryTranslationsMatchTheOldLexiconAcrossBundledLanguages(): void
    {
        $langPath = dirname(__DIR__, 2) . '/lang';
        $translator = new Translator(new FileLoader(new Filesystem(), $langPath), 'en');
        $translator->setFallback('en');
        $keys = [
            'rss_url_releases_default', 'rss_url_extras_default', 'captcha_words_default',
            'emailsubject_default', 'system_email_signup', 'system_email_websignup',
            'system_email_webreminder', 'siteunavailable_message_default',
        ];

        foreach (glob($langPath . '/*/global.php') as $file) {
            $locale = basename(dirname($file));
            $legacy = (static function (string $english, string $localized): array {
                $_lang = [];
                include $english;
                if ($localized !== $english) {
                    include $localized;
                }
                return $_lang;
            })($langPath . '/en/global.php', $file);

            foreach ($keys as $key) {
                self::assertSame($legacy[$key] ?? '', $translator->get('global.' . $key, [], $locale), "$locale: $key");
            }
        }
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
