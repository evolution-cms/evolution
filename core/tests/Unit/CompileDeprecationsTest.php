<?php

/**
 * Every PHP file the project ships (not its dependencies), relative to the repository root.
 *
 * @return string[]
 */
function projectPhpFiles(string $rootDir): array
{
    $skipped = ['vendor', 'node_modules', '.git', 'storage'];
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($rootDir, FilesystemIterator::SKIP_DOTS),
            function (SplFileInfo $entry, string $path) use ($skipped, $rootDir) {
                if ($entry->isDir()) {
                    return !in_array($entry->getFilename(), $skipped, true)
                        && !$entry->isLink()
                        && str_replace('\\', '/', $path) !== str_replace('\\', '/', $rootDir) . '/tmp';
                }

                return $entry->getExtension() === 'php';
            }
        )
    );
    foreach ($iterator as $file) {
        $files[] = $file->getPathname();
    }
    sort($files);

    return $files;
}

/**
 * Deprecations PHP reports while compiling (implicitly nullable parameters, optional parameters
 * before required ones, "case x;") show on every request that loads the file, so none may ship.
 */
test('project files compile without deprecations on this PHP version', function () {
    $rootDir = dirname(__DIR__, 3);
    $files = projectPhpFiles($rootDir);
    expect(count($files))->toBeGreaterThan(500);

    $prefix = escapeshellarg(PHP_BINARY) . ' -d error_reporting=-1 -d display_errors=stdout -d log_errors=0 -l';
    // Windows limits a command line to 8191 characters, so lint in batches; php -l takes many files since 8.3.
    $limit = PHP_OS_FAMILY === 'Windows' ? 7000 : 100000;
    $reports = [];
    $command = $prefix;
    foreach ($files as $index => $file) {
        $command .= ' ' . escapeshellarg($file);
        $last = $index === count($files) - 1;
        if ($last || strlen($command) > $limit) {
            $output = evoRunCommand($command . ' 2>&1');
            foreach (explode("\n", $output) as $line) {
                $line = trim($line);
                if ($line !== '' && !str_starts_with($line, 'No syntax errors detected')) {
                    $reports[] = str_replace($rootDir, '', $line);
                }
            }
            $command = $prefix;
        }
    }

    expect($reports)->toBe([]);
});
