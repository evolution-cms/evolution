<?php

use EvolutionCMS\Bootstrap\EnvCacheLoader;

test('only side-effect-free literal provider and alias declarations enter the cache', function (string $source, bool $expected) {
    $path = tempnam(sys_get_temp_dir(), 'evo-declaration-');
    file_put_contents($path, $source);
    try {
        expect(EnvCacheLoader::isStaticClassDeclaration('app.providers.Probe', $path))->toBe($expected)
            ->and(EnvCacheLoader::isStaticClassDeclaration('app.aliases.Probe', $path))->toBe($expected)
            ->and(EnvCacheLoader::isStaticClassDeclaration('session.driver', $path))->toBeFalse();
    } finally {
        unlink($path);
    }
})->with([
    ['<?php return \\Fixture\\Provider::class;', true],
    ['<?php /* comment */ return "Fixture\\\\Provider";', true],
    ['<?php return \\stdClass::class; ?>', true],
    ['<?php use Fixture\\Provider; return Provider::class;', false],
    ['<?php return getenv("PROVIDER");', false],
    ['<?php echo "effect"; return "Provider";', false],
    ['<?php return "{$runtime}";', false],
    ['<?php return ["Provider"];', false],
    ['<?php return "Provider"; ?>output', false],
]);

test('warm bootstrap preserves dynamic declarations and custom override order', function () {
    $output = evoRunPhp(dirname(__DIR__, 2).'/Fixtures/static-provider-config.php', [], $status);
    expect($status)->toBe(0, $output)->and($output)->toContain('PASS static replay');
});
