<?php

namespace Tests\Unit\Install;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 4) . '/install/src/functions.php';

final class InstallerExtensionCheckTest extends TestCase
{
    public function testExtensionsReportedWithMixedCaseAreFound(): void
    {
        // Reflection and SPL are always compiled in, and get_loaded_extensions()
        // lists them as "Reflection" and "SPL", not in lowercase.
        self::assertSame([], missingInstallExtensions(['reflection' => false, 'spl' => true]));
    }

    public function testSimplexmlIsFoundWhenLoaded(): void
    {
        if (!extension_loaded('SimpleXML')) {
            self::markTestSkipped('SimpleXML is not loaded');
        }

        self::assertSame([], missingInstallExtensions(['simplexml' => false]));
    }

    public function testMissingExtensionsKeepTheirMandatoryFlag(): void
    {
        $missing = missingInstallExtensions([
            'json' => true,
            'evo_missing_mandatory' => true,
            'evo_missing_optional' => false,
        ]);

        self::assertSame(['evo_missing_mandatory' => true, 'evo_missing_optional' => false], $missing);
    }
}
