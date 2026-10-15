<?php

/*
|--------------------------------------------------------------------------
| Request sanitizing of nested arrays
|--------------------------------------------------------------------------
|
| modx_sanitize_gpc() neutralises parser tags in $_GET, $_POST, $_COOKIE and
| $_REQUEST before the front end parses anything. It recursed into a copy of
| each nested array, so `?a[b]=[[Snippet]]` kept its tags while `?a=[[Snippet]]`
| lost them.
|
*/

if (!defined('EVO_SANITIZE_SEED')) {
    define('EVO_SANITIZE_SEED', 'gpc-nested-test-seed');
}

it('neutralises parser tags at every nesting level', function () {
    $values = [
        'flat' => '[[Snippet]]',
        'nested' => [
            'a' => '[[Snippet]]',
            'b' => ['c' => '[!Snippet!]', 'd' => ['e' => '{{chunk}}']],
        ],
        'list' => ['[*pagetitle*]', '[(site_name)]'],
    ];

    modx_sanitize_gpc($values);

    array_walk_recursive($values, function ($value) {
        foreach (['[[', ']]', '[!', '!]', '[*', '*]', '[(', ')]', '{{', '}}'] as $tag) {
            expect($value)->not->toContain($tag);
        }
    });

    expect(removeSanitizeSeed($values['nested']['b']['d']['e']))->toBe('{{chunk}}')
        ->and(removeSanitizeSeed($values['list'][0]))->toBe('[*pagetitle*]');
});

it('keeps plain nested values untouched', function () {
    $values = ['form' => ['name' => 'Jane', 'tags' => ['red', 'blue']]];

    modx_sanitize_gpc($values);

    expect($values)->toBe(['form' => ['name' => 'Jane', 'tags' => ['red', 'blue']]]);
});

it('still stops on arrays nested too deep', function () {
    $source = str_replace("\r", '', (string) file_get_contents(dirname(__DIR__, 3) . '/functions/preload.php'));

    expect($source)->toContain('modx_sanitize_gpc($values[$key], $depth);')
        ->and($source)->toContain("exit('GPC Array nested too deep!');");
});
