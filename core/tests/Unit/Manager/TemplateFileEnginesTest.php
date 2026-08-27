<?php

use EvolutionCMS\Support\TemplateFileEngines;

/**
 * The template form scaffolds a file whose extension decides which engine
 * renders the document - and whose name lands in the web root, so the extension
 * is checked against the registry rather than taken from the request.
 */
function templateFileEngines(?array $renderable = null, array $viewPaths = ['/views/']): TemplateFileEngines
{
    return new TemplateFileEngines(
        [
            'blade.php' => ['label' => 'Blade', 'processor' => null],
            'php' => ['label' => 'PHP', 'processor' => null],
            'latte' => ['label' => 'Latte', 'processor' => 'aLatteX'],
        ],
        $renderable,
        $viewPaths
    );
}

it('offers every declared engine the view factory can render', function () {
    expect(array_keys(templateFileEngines()->all()))
        ->toBe(['blade.php', 'php', 'latte']);
});

it('drops a declaration whose extension nothing can render', function () {
    // A .latte file is only a template once an engine is registered for it;
    // until then, offering it would scaffold a file nothing reads.
    $engines = templateFileEngines(['blade.php', 'php']);

    expect(array_keys($engines->all()))->toBe(['blade.php', 'php'])
        ->and($engines->isRegistered('latte'))->toBeFalse();
});

it('orders engines the way the view factory resolves them', function () {
    // addExtension() prepends, so the last engine registered wins an alias.
    $engines = templateFileEngines(['latte', 'blade.php', 'php']);

    expect(array_key_first($engines->all()))->toBe('latte');
});

it('preselects the engine belonging to the active chunk processor', function () {
    $engines = templateFileEngines();

    expect($engines->defaultExtension('aLatteX'))->toBe('latte')
        ->and($engines->defaultExtension('DLTemplate'))->toBe('blade.php')
        ->and($engines->defaultExtension(''))->toBe('blade.php')
        ->and($engines->defaultExtension(null))->toBe('blade.php');
});

it('does not preselect an engine that belongs to another processor', function () {
    // A plugin registering its engine puts it first in the factory's order, and
    // that must not turn it into the default for everybody else.
    $engines = templateFileEngines(['latte', 'blade.php', 'php']);

    expect(array_key_first($engines->all()))->toBe('latte')
        ->and($engines->defaultExtension('DLTemplate'))->toBe('blade.php')
        ->and($engines->defaultExtension('aLatteX'))->toBe('latte');
});

it('falls back to the first engine when every one is claimed', function () {
    $engines = new TemplateFileEngines(
        ['latte' => ['label' => 'Latte', 'processor' => 'aLatteX']],
        null,
        ['/views/']
    );

    expect($engines->defaultExtension('DLTemplate'))->toBe('latte');
});

it('has nothing to offer when no engine is declared', function () {
    $engines = new TemplateFileEngines([], null, ['/views/']);

    expect($engines->all())->toBe([])
        ->and($engines->defaultExtension('aLatteX'))->toBeNull()
        ->and($engines->filename('alone', 'blade.php'))->toBeNull();
});

it('accepts only registered extensions', function () {
    $engines = templateFileEngines();

    expect($engines->isRegistered('latte'))->toBeTrue()
        ->and($engines->isRegistered('.latte'))->toBeTrue()
        ->and($engines->isRegistered('phtml'))->toBeFalse()
        ->and($engines->isRegistered(null))->toBeFalse();
});

it('refuses to build a filename from an unregistered extension', function (string $extension) {
    expect(templateFileEngines()->filename('alone', $extension))->toBeNull();
})->with([
    'unknown engine' => ['phtml'],
    'traversal' => ['./../index'],
    'trailing dot' => ['latte.php.'],
    'empty' => [''],
]);

it('refuses an alias that is not already a safe filename', function (string $alias) {
    expect(templateFileEngines()->filename($alias, 'latte'))->toBeNull();
})->with([
    'traversal' => ['../alone'],
    'slash' => ['views/alone'],
    'space' => ['al one'],
    'dot' => ['alone.latte'],
    'empty' => [''],
]);

it('builds the filename for a safe alias', function () {
    $engines = templateFileEngines();

    expect($engines->filename('alone', 'latte'))->toBe('alone.latte')
        ->and($engines->filename('alone', '.blade.php'))->toBe('alone.blade.php')
        ->and($engines->filename('my_page-2', 'php'))->toBe('my_page-2.php');
});

it('reports the files already on disk, first one wins', function () {
    $dir = sys_get_temp_dir() . '/evo-template-files-' . bin2hex(random_bytes(4));
    mkdir($dir);

    try {
        file_put_contents($dir . '/alone.blade.php', '');
        file_put_contents($dir . '/alone.latte', '');

        $engines = templateFileEngines(['latte', 'blade.php', 'php'], [$dir]);
        $existing = $engines->existing('alone');

        expect(array_keys($existing))->toBe(['latte', 'blade.php'])
            ->and($existing['latte'])->toBe($dir . '/alone.latte')
            ->and($engines->existing('nothinghere'))->toBe([]);
    } finally {
        array_map('unlink', glob($dir . '/*') ?: []);
        rmdir($dir);
    }
});

/**
 * Which file renders is the template's own decision, recorded when it was
 * saved. Without that the answer comes from the view factory's extension order,
 * which the last plugin to boot gets to set for the whole site.
 */
it('renders the engine the template pinned, whatever registered first', function () {
    $dir = sys_get_temp_dir() . '/evo-template-pin-' . bin2hex(random_bytes(4));
    mkdir($dir);

    try {
        foreach (['alone.latte', 'alone.blade.php', 'alone.php'] as $file) {
            file_put_contents($dir . '/' . $file, '');
        }

        // latte first: what the factory would pick on its own.
        $engines = templateFileEngines(['latte', 'blade.php', 'php'], [$dir]);

        expect($engines->winner('alone', 'blade.php'))->toBe('blade.php')
            ->and($engines->winner('alone', 'php'))->toBe('php')
            ->and($engines->winner('alone', 'latte'))->toBe('latte')
            ->and($engines->pathFor('alone', 'blade.php'))->toBe($dir . '/alone.blade.php');
    } finally {
        array_map('unlink', glob($dir . '/*') ?: []);
        rmdir($dir);
    }
});

it('falls back to the factory order when nothing is pinned', function () {
    $dir = sys_get_temp_dir() . '/evo-template-pin-' . bin2hex(random_bytes(4));
    mkdir($dir);

    try {
        file_put_contents($dir . '/alone.latte', '');
        file_put_contents($dir . '/alone.blade.php', '');

        $engines = templateFileEngines(['latte', 'blade.php', 'php'], [$dir]);

        expect($engines->winner('alone', ''))->toBe('latte')
            ->and($engines->winner('alone', null))->toBe('latte');
    } finally {
        array_map('unlink', glob($dir . '/*') ?: []);
        rmdir($dir);
    }
});

it('falls back when the pinned file has been deleted', function () {
    $dir = sys_get_temp_dir() . '/evo-template-pin-' . bin2hex(random_bytes(4));
    mkdir($dir);

    try {
        file_put_contents($dir . '/alone.latte', '');

        $engines = templateFileEngines(['latte', 'blade.php', 'php'], [$dir]);

        // Pinned to Blade, but somebody removed the Blade file.
        expect($engines->pathFor('alone', 'blade.php'))->toBeNull()
            ->and($engines->winner('alone', 'blade.php'))->toBe('latte');
    } finally {
        array_map('unlink', glob($dir . '/*') ?: []);
        rmdir($dir);
    }
});

it('reports no winner when the alias has no file at all', function () {
    $dir = sys_get_temp_dir() . '/evo-template-pin-' . bin2hex(random_bytes(4));
    mkdir($dir);

    try {
        $engines = templateFileEngines(null, [$dir]);

        expect($engines->winner('alone', 'blade.php'))->toBe('')
            ->and($engines->pathFor('alone', 'blade.php'))->toBeNull()
            ->and($engines->pathFor('../evil', 'blade.php'))->toBeNull()
            ->and($engines->pathFor('alone', 'phtml'))->toBeNull();
    } finally {
        rmdir($dir);
    }
});
