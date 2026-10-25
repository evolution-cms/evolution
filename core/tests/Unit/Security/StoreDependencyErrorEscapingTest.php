<?php

// The missing dependency names come from the request (?dependencies=a,b) and are listed back on the page.
it('escapes the missing dependency names it lists', function () {
    $html = evoRunPhp(dirname(__DIR__, 2) . '/Mocks/store_dependency_error_worker.php', ['<img src=x onerror=alert(1)>,DocLister']);

    expect($html)
        ->toContain('&lt;img src=x onerror=alert(1)&gt;<br>DocLister')
        ->not->toContain('<img src=x');
});
