<?php

use EvolutionCMS\Services\CustomComposerPackages;
use EvolutionCMS\Services\IlluminateCompatibility;
use Illuminate\Filesystem\Filesystem;

// The runtime fixture may have a classmap made before the helper was added.
require_once dirname(__DIR__, 2) . '/src/Services/IlluminateCompatibility.php';

beforeEach(function () {
    $this->core = sys_get_temp_dir() . '/evo-illuminate-backport-' . bin2hex(random_bytes(8));
    mkdir($this->core . '/vendor/illuminate/support', 0777, true);
    mkdir($this->core . '/vendor/illuminate/session', 0777, true);
    $this->strings = <<<'PHP'
<?php namespace Illuminate\Support;
class Str {
    public static function studly($value) { return mb_split('\s+', $value); }
}
PHP;
    $this->handler = <<<'PHP'
<?php namespace Illuminate\Session;
class FileSessionHandler implements \SessionHandlerInterface {
    public function read($id) { return ''; }
}
PHP;
    file_put_contents($this->core . '/vendor/illuminate/support/Str.php', $this->strings);
    file_put_contents($this->core . '/vendor/illuminate/session/FileSessionHandler.php', $this->handler);
});

afterEach(function () {
    (new Filesystem())->deleteDirectory($this->core);
});

it('keeps Laravel 12 Unicode, separators, initials and title results without diagnostics', function () {
    $fixture = dirname(__DIR__) . '/Fixtures/illuminate-php86.php';
    $output = evoRunPhp($fixture, ['strings'], $exitCode);
    $expected = json_decode(file_get_contents(dirname($fixture) . '/illuminate-strings-laravel12.json'), true, flags: JSON_THROW_ON_ERROR);
    expect($exitCode)->toBe(0, $output)
        ->and(json_decode($output, true, flags: JSON_THROW_ON_ERROR))->toBe($expected);
});

it('loads all session stores and preserves IDs, payloads and deletion without diagnostics', function () {
    $output = evoRunPhp(dirname(__DIR__) . '/Fixtures/illuminate-php86.php', ['sessions'], $exitCode);
    expect($exitCode)->toBe(0, $output);
    $results = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
    foreach (['file', 'array', 'cache', 'database', 'cookie', 'null'] as $name) {
        expect($results[$name])->toBe([
            'idsValid' => true,
            'before' => $name === 'null',
            'after' => true,
            'payload' => $name === 'null' ? '' : 'preserved payload',
            'afterDestroy' => $name === 'null',
        ]);
    }
});

it('preserves closure resolution, removable scopes and object-aware memoization', function () {
    $output = evoRunPhp(dirname(__DIR__) . '/Fixtures/illuminate-php86.php', ['objects'], $exitCode);
    expect($exitCode)->toBe(0, $output)
        ->and(json_decode($output, true, flags: JSON_THROW_ON_ERROR))
        ->toBe(['factories' => true, 'singleton' => true, 'once' => true, 'scopes' => true]);
});

it('reapplies a dependency reinstall and leaves already-patched files untouched', function () {
    $patches = new IlluminateCompatibility();
    expect($patches->apply($this->core))->toHaveCount(2);
    $path = $this->core . '/vendor/illuminate/support/Str.php';
    touch($path, 1234567890);
    expect($patches->apply($this->core))->toBe([])
        ->and(filemtime($path))->toBe(1234567890);
    file_put_contents($path, $this->strings);
    expect($patches->apply($this->core))->toBe(['illuminate/support/Str.php']);
});

it('validates the whole plan before changing dependencies with unknown source', function () {
    file_put_contents($this->core . '/vendor/illuminate/session/FileSessionHandler.php', '<?php class SomethingElse {}');
    expect(fn () => (new IlluminateCompatibility())->apply($this->core))->toThrow(RuntimeException::class, 'Unknown Illuminate session handler')
        ->and(file_get_contents($this->core . '/vendor/illuminate/support/Str.php'))->toBe($this->strings);
});

it('rejects an unknown mbregex expression before any dependency is changed', function () {
    file_put_contents($this->core . '/vendor/illuminate/support/Str.php', str_replace("'\\s+'", "'different'", $this->strings));
    expect(fn () => (new IlluminateCompatibility())->apply($this->core))->toThrow(RuntimeException::class, 'Unknown Illuminate Str mb_split')
        ->and(file_get_contents($this->core . '/vendor/illuminate/session/FileSessionHandler.php'))->toBe($this->handler);
});

it('does not patch a dependency linked outside vendor', function () {
    $external = $this->core . '/external.php';
    $path = $this->core . '/vendor/illuminate/support/Str.php';
    file_put_contents($external, $this->strings);
    unlink($path);
    if (!@symlink($external, $path)) {
        test()->markTestSkipped('Symlinks are unavailable.');
    }
    expect(fn () => (new IlluminateCompatibility())->apply($this->core))->toThrow(RuntimeException::class, 'outside vendor')
        ->and(file_get_contents($external))->toBe($this->strings);
});

it('reapplies backports during managed Composer restores that disable scripts', function () {
    mkdir($this->core . '/src/Services', 0777, true);
    copy(dirname(__DIR__, 2) . '/src/Services/IlluminateCompatibility.php', $this->core . '/src/Services/IlluminateCompatibility.php');
    (new CustomComposerPackages($this->core))->patchRuntimeDependencies();
    expect((new IlluminateCompatibility())->apply($this->core))->toBe([]);
});

it('runs the actual Composer pre-autoload hook after replacing dependency sources', function () {
    mkdir($this->core . '/src/Services', 0777, true);
    mkdir($this->core . '/functions');
    copy(dirname(__DIR__, 2) . '/src/Services/IlluminateCompatibility.php', $this->core . '/src/Services/IlluminateCompatibility.php');
    copy(dirname(__DIR__, 2) . '/functions/patch-illuminate.php', $this->core . '/functions/patch-illuminate.php');
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2) . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    file_put_contents($this->core . '/composer.json', json_encode([
        'name' => 'evolutioncms/test-compatibility',
        'scripts' => ['pre-autoload-dump' => $manifest['scripts']['pre-autoload-dump']],
    ], JSON_THROW_ON_ERROR));
    $composer = dirname(__DIR__, 2) . '/vendor/bin/composer';
    for ($run = 0; $run < 2; $run++) {
        file_put_contents($this->core . '/vendor/illuminate/support/Str.php', $this->strings);
        file_put_contents($this->core . '/vendor/illuminate/session/FileSessionHandler.php', $this->handler);
        $output = evoRunCommand(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($composer)
            . ' --working-dir=' . escapeshellarg($this->core) . ' dump-autoload --no-plugins --no-interaction 2>&1', $exitCode);
        expect($exitCode)->toBe(0, $output)
            ->and((new IlluminateCompatibility())->apply($this->core))->toBe([]);
    }
});
