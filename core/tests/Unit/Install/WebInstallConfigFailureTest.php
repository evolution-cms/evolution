<?php

namespace Tests\Unit\Install;

use PHPUnit\Framework\TestCase;

final class WebInstallConfigFailureTest extends TestCase
{
    public function testWebInstallerStopsBeforeBootstrappingAfterConfigWriteFailure(): void
    {
        $source = file_get_contents(dirname(__DIR__, 4) . '/install/src/controllers/install.php');

        self::assertIsString($source);
        self::assertStringContainsString(
            '$configFileFailed = !writeInstallConfigFile($filename, $configString);',
            $source
        );
        self::assertMatchesRegularExpression(
            '/if \(\$configFileFailed === true\).*?include .*?template\/actions\/install\.php.*?return;/s',
            $source
        );
    }

    public function testGeneratedConfigIsEscapedInTheFailureResponse(): void
    {
        $source = file_get_contents(dirname(__DIR__, 4) . '/install/src/template/actions/install.php');

        self::assertIsString($source);
        self::assertStringContainsString(
            "htmlspecialchars(\$configString, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')",
            $source
        );
    }

    public function testInstallerLocalesRetainTheEnglishFallback(): void
    {
        $source = file_get_contents(dirname(__DIR__, 4) . '/install/src/lang.php');

        self::assertIsString($source);
        self::assertStringContainsString('$fallbackLang = $_lang;', $source);
        self::assertStringContainsString('$_lang += $fallbackLang;', $source);
    }

    public function testEveryInstallerLocaleDefinesTheConfigWriteMessages(): void
    {
        $languageFiles = glob(dirname(__DIR__, 4) . '/install/src/lang/*.inc.php');
        self::assertIsArray($languageFiles);
        self::assertNotEmpty($languageFiles);

        foreach ($languageFiles as $languageFile) {
            $source = file_get_contents($languageFile);
            self::assertIsString($source);
            self::assertStringContainsString(
                "'cant_write_config_file_retry'",
                $source,
                basename($languageFile) . ' must translate the config write failure instructions.'
            );
            self::assertStringContainsString(
                "'checking_if_database_config_writable'",
                $source,
                basename($languageFile) . ' must translate the config writability check.'
            );
            foreach (['0644', 'rw-r--r--', '0755', 'rwxr-xr-x', '0777'] as $permission) {
                self::assertStringContainsString(
                    $permission,
                    $source,
                    basename($languageFile) . " must include the {$permission} permission guidance."
                );
            }
        }
    }

    public function testConfigFailureLayoutHandlesLongTranslatedText(): void
    {
        $template = file_get_contents(dirname(__DIR__, 4) . '/install/src/template/actions/install.php');
        $styles = file_get_contents(dirname(__DIR__, 4) . '/install/style.css');
        $layout = file_get_contents(dirname(__DIR__, 4) . '/install/src/template/install.tpl');

        self::assertIsString($template);
        self::assertIsString($styles);
        self::assertIsString($layout);
        self::assertStringContainsString('class="config-write-failure"', $template);
        self::assertStringContainsString('class="config-write-failure__content"', $template);
        self::assertStringContainsString('overflow-wrap: anywhere;', $styles);
        self::assertStringContainsString('word-break: break-all;', $styles);
        self::assertStringContainsString('max-width: 40rem;', $styles);
        self::assertStringContainsString('direction: ltr;', $styles);
        self::assertStringContainsString('text-align: left;', $styles);
        self::assertStringContainsString('box-sizing: border-box;', $styles);
        self::assertStringContainsString('@media (max-width: 600px)', $styles);
        self::assertStringContainsString('name="viewport"', $layout);
    }

    public function testUpgradeModeBlocksAnUnreadableDatabaseConfig(): void
    {
        $controller = file_get_contents(dirname(__DIR__, 4) . '/install/src/controllers/mode.php');
        $template = file_get_contents(dirname(__DIR__, 4) . '/install/src/template/actions/mode.tpl');

        self::assertIsString($controller);
        self::assertIsString($template);
        self::assertStringContainsString('!is_readable($databaseConfigFile)', $controller);
        self::assertStringContainsString("\$ph['disabledAdvUpg']", $controller);
        self::assertStringContainsString("\$ph['configPermissionError']", $controller);
        self::assertStringContainsString('[+configPermissionError+]', $template);
        self::assertStringContainsString('[+disabledNext+]', $template);
    }

    public function testSummaryCacheWriterDoesNotUseUncheckedStreamHandles(): void
    {
        $source = file_get_contents(dirname(__DIR__, 4) . '/install/src/controllers/summary.php');

        self::assertIsString($source);
        self::assertStringContainsString("\$_lang['cant_write_config_file_retry']", $source);
        self::assertStringContainsString('file_put_contents($path, $data, LOCK_EX)', $source);
        self::assertStringNotContainsString('fwrite($hnd, $data)', $source);
    }
}
