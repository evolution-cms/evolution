<?php declare(strict_types=1);

use EvolutionCMS\Services\ComposerVersionSynchronizer;

/**
 * Explicit Composer metadata synchronization entry point.
 *
 * Ordinary Composer install/update commands do not invoke this script. It remains
 * available through "composer sync-replace" for release and maintenance workflows.
 */

$root = dirname(__DIR__);

require_once $root . '/src/Services/ComposerVersionSynchronizer.php';

try {
    $result = (new ComposerVersionSynchronizer())->synchronize(
        $root . '/composer.json',
        $root . '/factory/version.php'
    );

    if ($result['changed']) {
        echo "Synced composer.json: evolution-cms/evolution {$result['version']}\n";
    } else {
        echo "Evolution CMS {$result['version']}\n";
    }
} catch (\Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(max(1, (int) $exception->getCode()));
}
