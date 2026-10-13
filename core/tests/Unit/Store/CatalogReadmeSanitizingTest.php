<?php

use EvolutionCMS\Services\Store\CatalogService;

/**
 * A package README is fetched from a third-party repository and shown in the manager,
 * so raw HTML inside it must never reach the page as script.
 */
test('readme html cannot carry script into the manager', function () {
    $service = new CatalogService();
    $render = new ReflectionMethod($service, 'renderMarkdownHtml');
    $render->setAccessible(true);

    $markdown = "# Title\n\n<script>alert(1)</script>\n\n<a href=\"javascript:alert(2)\" onclick=\"alert(3)\">x</a>\n\n"
        . "<div id=\"config\" onmouseover=\"alert(4)\">ok</div>\n\n<iframe src=\"https://evil.example\"></iframe>\n";

    $html = strtolower((string) $render->invoke($service, $markdown, 'https://github.com/a/b', 'main'));

    expect($html)->toContain('title')
        ->and($html)->not->toContain('<script')
        ->and($html)->not->toContain('javascript:')
        ->and($html)->not->toContain('onclick')
        ->and($html)->not->toContain('onmouseover')
        ->and($html)->not->toContain('<iframe')
        ->and($html)->not->toContain('id="config"');
});
