<?php

namespace Tests\Unit\Install;

use PHPUnit\Framework\TestCase;

final class InstallerLanguageSelectionTest extends TestCase
{
    /**
     * Runs install/src/lang.php in a clean PHP process with the given request data and
     * returns the languages it settled on.
     */
    private function resolve(array $get = [], array $post = [], string $acceptLanguage = ''): array
    {
        $langFile = dirname(__DIR__, 4) . '/install/src/lang.php';
        $script = '<?php'
            . ' define("MGR_DIR", "manager");'
            . ' $_GET = ' . var_export($get, true) . ';'
            . ' $_POST = ' . var_export($post, true) . ';'
            . ' $_SERVER["HTTP_ACCEPT_LANGUAGE"] = ' . var_export($acceptLanguage, true) . ';'
            . ' require ' . var_export($langFile, true) . ';'
            . ' echo json_encode(["install" => $install_language, "manager" => $manager_language,'
            . ' "hasStrings" => count($_lang) > 0]);';

        $tmp = tempnam(sys_get_temp_dir(), 'evo-lang-');
        file_put_contents($tmp, $script);
        try {
            $output = evoRunPhp($tmp);
        } finally {
            unlink($tmp);
        }

        $result = json_decode((string) $output, true);
        self::assertIsArray($result, 'lang.php failed: ' . $output);

        return $result;
    }

    public function testShippedLanguageIsSelected(): void
    {
        $result = $this->resolve(['language' => 'de', 'managerlanguage' => 'uk']);

        self::assertSame('de', $result['install']);
        self::assertSame('uk', $result['manager']);
        self::assertTrue($result['hasStrings']);
    }

    public function testUnknownLanguageFallsBackInsteadOfFailingTheInclude(): void
    {
        $result = $this->resolve(['language' => 'xx', 'managerlanguage' => 'xx']);

        self::assertSame('en', $result['install']);
        self::assertSame('en', $result['manager']);
        self::assertTrue($result['hasStrings']);
    }

    public function testPathLikeLanguageValuesAreRejected(): void
    {
        $result = $this->resolve(
            ['language' => '../../core/config/app', 'managerlanguage' => '..'],
            ['language' => ['de']],
            '..'
        );

        self::assertSame('en', $result['install']);
        self::assertSame('en', $result['manager']);
    }

    public function testPostTakesPrecedenceOverQueryAndAcceptLanguage(): void
    {
        $result = $this->resolve(['language' => 'fr'], ['language' => 'it'], 'de-DE,de;q=0.9');

        self::assertSame('it', $result['install']);
        self::assertSame('it', $result['manager']);
    }

    public function testAcceptLanguageIsUsedWhenNothingIsRequested(): void
    {
        $result = $this->resolve([], [], 'nl-NL,nl;q=0.9');

        self::assertSame('nl', $result['install']);
    }
}
