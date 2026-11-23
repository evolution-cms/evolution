<?php

declare(strict_types=1);

// Composer pre-autoload entry point: do not depend on the loader being rebuilt.
require_once dirname(__DIR__) . '/src/Services/IlluminateCompatibility.php';

try {
    $changed = (new EvolutionCMS\Services\IlluminateCompatibility())->apply(dirname(__DIR__));
    foreach ($changed as $file) {
        echo "Applied PHP 8.6 compatibility: {$file}\n";
    }
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
