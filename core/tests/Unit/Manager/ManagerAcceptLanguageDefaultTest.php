<?php

function managerIndexSource(): string
{
    return file_get_contents(dirname(__DIR__, 4) . '/manager/index.php');
}

function managerAcceptLanguageDefaultStatement(): string
{
    preg_match('/^\$_SERVER\[\'HTTP_ACCEPT_LANGUAGE\'\]\s*\?\?=\s*[^;]+;/m', managerIndexSource(), $match);

    return $match[0] ?? '';
}

it('does not answer 404 when the Accept-Language header is missing', function () {
    $source = managerIndexSource();

    expect($source)->not->toMatch('/if\s*\(\s*!\s*isset\(\$_SERVER\[\'HTTP_ACCEPT_LANGUAGE\'\]\)\s*\)\s*\{\s*header\([^)]*404/');
});

it('defaults a missing Accept-Language header to English', function () {
    $statement = managerAcceptLanguageDefaultStatement();
    expect($statement)->not->toBe('');

    $backup = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;
    try {
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        eval($statement);
        expect($_SERVER['HTTP_ACCEPT_LANGUAGE'])->toBe('en');

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-DE,de;q=0.9';
        eval($statement);
        expect($_SERVER['HTTP_ACCEPT_LANGUAGE'])->toBe('de-DE,de;q=0.9');
    } finally {
        if ($backup === null) {
            unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        } else {
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $backup;
        }
    }
});
