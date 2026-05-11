<?php

it('passes web alert content to the native alert as plain text', function () {
    $core = file_get_contents(dirname(__DIR__, 3) . '/src/Core.php');

    expect($core)
        ->toContain("alert(el.textContent || el.innerText || '')")
        ->not->toContain('alert(el.innerHTML)');
});
