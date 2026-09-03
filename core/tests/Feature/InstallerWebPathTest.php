<?php

use EvolutionCMS\Support\InstallerCompletion;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

require_once dirname(__DIR__, 2) . '/src/Support/InstallerCompletion.php';

/**
 * @return array{root: string, lock: string, install: string}
 */
function installerWebFixture(): array
{
    $root = str_replace('\\', '/', sys_get_temp_dir()) . '/evo-installer-web-' . bin2hex(random_bytes(8));
    $install = $root . '/install';
    mkdir($install, 0777, true);
    file_put_contents($install . '/index.php', '<?php // installer fixture');

    return [
        'root' => $root,
        'lock' => $root . '/install.session.php',
        'install' => $install,
    ];
}

/**
 * Run the real standalone processor with a temporary site root.
 *
 * @param array<string, string> $post
 * @param array<string, string> $cookies
 */
function runInstallerRemoval(array $fixture, array $post, array $cookies = [], string $method = 'POST'): Process
{
    $processor = str_replace('\\', '/', dirname(__DIR__, 3))
        . '/manager/processors/remove_installer.processor.php';
    $runner = $fixture['root'] . '/request.php';
    $source = "<?php\n"
        . "define('EVO_BASE_PATH', " . var_export($fixture['root'] . '/', true) . ");\n"
        . "\$_SERVER['REQUEST_METHOD'] = " . var_export($method, true) . ";\n"
        . "\$_SERVER['REMOTE_ADDR'] = '203.0.113.10';\n"
        . "\$_POST = " . var_export($post, true) . ";\n"
        . "\$_COOKIE = " . var_export($cookies, true) . ";\n"
        . "require " . var_export($processor, true) . ";\n";
    file_put_contents($runner, $source);

    $process = new Process([PHP_BINARY, $runner]);
    $process->run();

    return $process;
}

function removeInstallerWebFixture(array $fixture): void
{
    (new Filesystem())->deleteDirectory($fixture['root']);
}

test('fresh web install requires successful completion before its installer can be removed', function () {
    $fixture = installerWebFixture();
    $token = str_repeat('a', 64);

    try {
        file_put_contents(
            $fixture['lock'],
            "<?php\n\$install_session = 'anonymous-session';\n"
            . "\$install_ip = '203.0.113.10';\n"
            . "\$install_timestamp = " . time() . ";\n"
        );

        $beforeCompletion = runInstallerRemoval($fixture, [
            'installer_token' => $token,
            'rminstaller' => '1',
        ]);

        expect($beforeCompletion->isSuccessful())->toBeTrue()
            ->and($beforeCompletion->getOutput())->toContain('Not found.')
            ->and(is_dir($fixture['install']))->toBeTrue();

        expect(InstallerCompletion::writeLock(
            $fixture['lock'],
            'anonymous-session',
            '203.0.113.10',
            time(),
            $token
        ))->toBeTrue();

        $getAttempt = runInstallerRemoval($fixture, [
            'installer_token' => $token,
            'rminstaller' => '1',
        ], [], 'GET');

        expect($getAttempt->isSuccessful())->toBeTrue()
            ->and($getAttempt->getOutput())->toContain('Not found.')
            ->and(is_dir($fixture['install']))->toBeTrue();

        $afterCompletion = runInstallerRemoval($fixture, [
            'installer_token' => $token,
            'rminstaller' => '1',
        ]);

        expect($afterCompletion->isSuccessful())->toBeTrue()
            ->and($afterCompletion->getOutput())->toContain("window.location='../#?a=2'")
            ->and(is_dir($fixture['install']))->toBeFalse()
            ->and(is_file($fixture['lock']))->toBeFalse();
    } finally {
        removeInstallerWebFixture($fixture);
    }
});
