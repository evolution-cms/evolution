<?php

// Exercise the real cleanup with a hard timeout in the parent process, even if it loops forever.
require dirname(__DIR__, 2) . '/vendor/autoload.php';

$mode = $argv[1];
$warnings = 0;
set_error_handler(static function () use (&$warnings): bool {
    $warnings++;
    return true;
});

if ($mode !== 'no-baseline') {
    ob_start(null, 0, $mode === 'baseline-locked' ? 0 : PHP_OUTPUT_HANDLER_STDFLAGS);
}
$level = ob_get_level();

if (str_starts_with($mode, 'locked')) {
    ob_start(null, 0, $mode === 'locked-cleanable' ? PHP_OUTPUT_HANDLER_CLEANABLE : 0);
}
echo 'before abort';
ob_start();
echo 'nested output';
ob_start();
echo 'more nested output';

$core = (new ReflectionClass(EvolutionCMS\Core::class))->newInstanceWithoutConstructor();
(new ReflectionMethod($core, 'discardBuffers'))->invoke($core, $level);

fwrite(STDERR, json_encode([
    'level' => ob_get_level(),
    'content' => ob_get_contents() ?: '',
    'warnings' => $warnings,
]));
