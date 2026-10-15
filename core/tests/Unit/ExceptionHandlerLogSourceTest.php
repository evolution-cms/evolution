<?php

use EvolutionCMS\ExceptionHandler;

it('turns the HTML message of messageQuit() into a plain log source', function (string $html, string $plain) {
    expect(ExceptionHandler::plainLogSource($html))->toBe($plain);
})->with([
    'markup' => ['External or invalid redirect not allowed: <i>/x?a=1&amp;b=2</i>', 'External or invalid redirect not allowed: /x?a=1&b=2'],
    'command line' => ['exit 2>1', 'exit 2>1'],
    'comparison' => ['a < b and c > d', 'a < b and c > d'],
    'plain' => ['PHP Parse Error', 'PHP Parse Error'],
]);
