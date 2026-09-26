<?php

/*
|--------------------------------------------------------------------------
| Committed classmap
|--------------------------------------------------------------------------
|
| core/vendor is committed and its autoloader is classmap-authoritative, so a class under
| core/src that is missing from the committed vendor/composer/autoload_*.php does not exist on a
| site: it fails with "class not found" there, while a test run - after composer install has
| regenerated vendor/, or through the PSR-4 fallback - finds it. So the committed files are read
| from git, not from disk.
|
*/

function srcClassNames(): array
{
    $root = dirname(__DIR__, 2);
    $names = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src', FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        $source = (string) file_get_contents($file->getPathname());
        if (!preg_match('/^\s*(?:(?:final|abstract|readonly)\s+)*(?:class|interface|trait|enum)\s+\w+/m', $source)) {
            continue;
        }
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root . '/src/')));
        $names[] = 'EvolutionCMS\\' . str_replace('/', '\\', substr($relative, 0, -4));
    }
    sort($names);

    return $names;
}

test('every class under core/src is in the committed classmap', function (string $map) {
    $committed = shell_exec('git -C ' . escapeshellarg(dirname(__DIR__, 2)) . ' show HEAD:core/vendor/composer/' . $map . ' 2>&1');
    if (!is_string($committed) || !str_contains($committed, 'EvolutionCMS\\\\')) {
        $this->markTestSkipped('no git checkout to read the committed classmap from');
    }

    preg_match_all("/^\s*'(EvolutionCMS\\\\\\\\[^']+)' =>/m", $committed, $matches);
    $classmap = array_flip(array_map('stripslashes', $matches[1]));

    $missing = array_values(array_filter(srcClassNames(), static fn ($class) => !isset($classmap[$class])));

    expect($missing)->toBe([]);
})->with(['autoload_classmap.php', 'autoload_static.php']);
