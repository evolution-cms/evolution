<?php

/*
|--------------------------------------------------------------------------
| Code bindings reached through content bindings
|--------------------------------------------------------------------------
|
| ProcessTVCommand() hands a binding's output back to itself. TvBindingGuard
| keeps @EVAL and @SELECT out of TV values for managers who may not write PHP,
| but a value of `@CHUNK name` (or @FILE / @DOCUMENT) pointing at content that
| starts with @EVAL ran it anyway - and the default Editor and Publisher roles
| may write chunks, pages and files. Both copies of ProcessTVCommand() run in
| a fresh process through tests/Fixtures/tv-binding-harness.php.
|
*/

use EvolutionCMS\Support\TvBindingGuard;

$runHarness = static function (string $copy): array {
    $output = evoRunPhp(dirname(__DIR__, 2) . '/Fixtures/tv-binding-harness.php', [$copy], $exitCode, false);
    $decoded = json_decode($output, true);

    expect($exitCode)->toBe(0, $output)
        ->and($decoded)->toBeArray($output);

    return $decoded;
};

$copies = ['functions/tv.php', 'functions/actions/mutate_content.php'];

it('does not run a code binding found in a chunk, file or page', function (string $copy) use ($runHarness) {
    $results = $runHarness($copy)['results'];

    foreach (['chunkEval', 'chunkSelect', 'chunkInheritEval', 'documentEval', 'chunkDocumentEval', 'fileEval'] as $case) {
        expect($results[$case])->toBe('', $case);
    }
    expect($results['chunkEvalLower'])->not->toBe('RAN');
})->with($copies);

it('logs each refused binding for the administrators', function (string $copy) use ($runHarness) {
    $run = $runHarness($copy);
    $sources = array_unique(array_column($run['events'], 'source'));

    expect($sources)->toBe(['TV binding'])
        ->and(array_unique(array_column($run['events'], 'type')))->toBe([2])
        ->and(implode("\n", array_column($run['events'], 'msg')))
        ->toContain('"tv_chunkEval"')
        ->toContain('@SELECT')
        ->toContain('"tv_fileEval"');
})->with($copies);

it('keeps the bindings an administrator writes into the TV working', function (string $copy) use ($runHarness) {
    $results = $runHarness($copy)['results'];

    expect($results['directEval'])->toBe('ok')
        ->and($results['evalReturnsBinding'])->toBe('Hello')
        ->and($results['plainChunk'])->toBe('Hello')
        ->and($results['nestedChunk'])->toBe('Hello')
        ->and($results['plainDocument'])->toBe('Page text');
})->with($copies);

it('tells code bindings and content bindings apart', function () {
    expect(TvBindingGuard::runsCode('EVAL'))->toBeTrue()
        ->and(TvBindingGuard::runsCode(' select '))->toBeTrue()
        ->and(TvBindingGuard::runsCode('CHUNK'))->toBeFalse()
        ->and(TvBindingGuard::runsCode(null))->toBeFalse()
        ->and(TvBindingGuard::nestedMayRunCode('EVAL', true))->toBeTrue()
        ->and(TvBindingGuard::nestedMayRunCode('INHERIT', true))->toBeTrue()
        ->and(TvBindingGuard::nestedMayRunCode('INHERIT', false))->toBeFalse()
        ->and(TvBindingGuard::nestedMayRunCode('CHUNK', true))->toBeFalse()
        ->and(TvBindingGuard::nestedMayRunCode('FILE', true))->toBeFalse()
        ->and(TvBindingGuard::nestedMayRunCode('DOCUMENT', true))->toBeFalse()
        ->and(TvBindingGuard::nestedMayRunCode('DIRECTORY', true))->toBeFalse();
});
