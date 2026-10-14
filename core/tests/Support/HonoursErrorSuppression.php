<?php

namespace Tests\Support;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;

/**
 * Keeps errors raised under the @ operator out of the results, as PHP keeps them off the screen.
 *
 * PHPUnit marks such an error as suppressed and leaves it out of its own results, but Pest's
 * printer (Collision) counts every PHP warning and deprecation regardless. Code probes paths with
 * @lstat(), @unlink(), @fopen(), @symlink() ... on purpose - most of all on Windows, where
 * symlink() needs a privilege and junctions are probed - and each probe showed up as a warning.
 *
 * The handler is stacked on PHPUnit's before setUp() and removed after tearDown(), so it also
 * covers a file's own beforeEach / afterEach (Pest runs global hooks first, which would leave the
 * cleanup of a file unprotected). Anything not silenced with @ still reaches PHPUnit.
 */
trait HonoursErrorSuppression
{
    #[Before(priority: 1000)]
    public function stackSuppressionAwareErrorHandler(): void
    {
        $insuppressible = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;
        $previous = null;
        $previous = set_error_handler(static function (int $errno, string $errstr, string $errfile = '', int $errline = 0) use (&$previous, $insuppressible) {
            if ((error_reporting() & ~$insuppressible) === 0) {
                return true;
            }

            return is_callable($previous) ? $previous($errno, $errstr, $errfile, $errline) : false;
        });
    }

    #[After(priority: -1000)]
    public function removeSuppressionAwareErrorHandler(): void
    {
        restore_error_handler();
    }
}
