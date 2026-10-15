<?php

use EvolutionCMS\Support\UploadLimits;

/*
|--------------------------------------------------------------------------
| Uploads larger than PHP accepts
|--------------------------------------------------------------------------
|
| A POST over post_max_size arrives with $_POST and $_FILES empty, after a startup warning. With
| display_errors on, that warning starts the output, so every later header() and session call in
| manager/index.php warned with a server path, and the empty body then failed the CSRF check with
| a misleading "session token" message. The manager now recognises the discarded body before the
| session starts and answers with a 413 that names both sizes, and the file manager refuses such
| a selection in the browser before sending it.
|
*/

it('reads php.ini size values', function (mixed $value, int $bytes) {
    expect(UploadLimits::toBytes($value))->toBe($bytes);
})->with([
    'megabytes' => ['8M', 8388608],
    'lowercase kilobytes' => ['512k', 524288],
    'gigabytes' => ['1G', 1073741824],
    'plain bytes' => ['1048576', 1048576],
    'zero' => ['0', 0],
    'empty' => ['', 0],
    'negative' => ['-1', 0],
    'garbage' => ['lots', 0],
    'not scalar' => [[8], 0],
]);

it('takes the smallest positive limit for a single file', function () {
    expect(UploadLimits::fileLimit('5000000', 2097152, 8388608))->toBe(2097152)
        ->and(UploadLimits::fileLimit(1000, 2097152, 8388608))->toBe(1000)
        ->and(UploadLimits::fileLimit('', 0, 8388608))->toBe(8388608)
        ->and(UploadLimits::fileLimit(null, 0, 0))->toBe(0);
});

it('recognises a POST body PHP discarded for its size', function () {
    $server = ['REQUEST_METHOD' => 'POST', 'CONTENT_LENGTH' => '24613846'];

    expect(UploadLimits::bodyWasDiscarded($server, [], [], 8388608))->toBeTrue()
        // anything that did arrive means PHP kept the body
        ->and(UploadLimits::bodyWasDiscarded($server, ['a' => '31'], [], 8388608))->toBeFalse()
        ->and(UploadLimits::bodyWasDiscarded($server, [], ['userfile' => []], 8388608))->toBeFalse()
        ->and(UploadLimits::bodyWasDiscarded(['CONTENT_LENGTH' => '24613846'] + $server, [], [], 0))->toBeFalse()
        ->and(UploadLimits::bodyWasDiscarded(['REQUEST_METHOD' => 'POST', 'CONTENT_LENGTH' => '100'], [], [], 8388608))->toBeFalse()
        ->and(UploadLimits::bodyWasDiscarded(['REQUEST_METHOD' => 'GET', 'CONTENT_LENGTH' => '24613846'], [], [], 8388608))->toBeFalse()
        ->and(UploadLimits::bodyWasDiscarded(['REQUEST_METHOD' => 'POST'], [], [], 8388608))->toBeFalse();
});

it('formats sizes in binary units', function () {
    expect(UploadLimits::humanSize(24613846))->toBe('23.5 MB')
        ->and(UploadLimits::humanSize(8388608))->toBe('8 MB')
        ->and(UploadLimits::humanSize(1536))->toBe('1.5 KB')
        ->and(UploadLimits::humanSize(300))->toBe('300 bytes');
});

it('describes the rejected request without exposing any path', function () {
    $_lang = [];
    include dirname(__DIR__, 3) . '/lang/en/global.php';

    $page = UploadLimits::discardedBodyPage($_lang, 24613846, 8388608);

    expect($page)->toContain('it is 23.5 MB, but this server takes at most 8 MB per request')
        ->and($page)->toContain('post_max_size and upload_max_filesize')
        ->and($page)->toContain('history.back()')
        ->and($page)->not->toContain('/var/www')
        ->and($page)->not->toContain(dirname(__DIR__, 4))
        ->and($page)->not->toContain('Warning');
});

it('escapes lexicon text on the rejection page', function () {
    $page = UploadLimits::discardedBodyPage(['upload_request_back' => '<img src=x onerror=alert(1)>'], 10, 5, '"><x');

    expect($page)->not->toContain('<img')
        ->and($page)->not->toContain('"><x');
});

it('picks a shipped manager language from Accept-Language, else English', function () {
    $langDir = dirname(__DIR__, 3) . '/lang';

    expect(UploadLimits::language('de-DE,de;q=0.9,en;q=0.8', $langDir))->toBe('de')
        ->and(UploadLimits::language('xx-YY, uk;q=0.5', $langDir))->toBe('uk')
        ->and(UploadLimits::language('../../etc', $langDir))->toBe('en')
        ->and(UploadLimits::language('', $langDir))->toBe('en');
});

it('defines the messages it uses in the English lexicon', function () {
    $_lang = [];
    include dirname(__DIR__, 3) . '/lang/en/global.php';

    foreach (['upload_request_too_large', 'upload_request_too_large_hint', 'upload_request_back',
                 'upload_file_too_large', 'upload_files_too_large', 'upload_limits_hint'] as $key) {
        expect($_lang)->toHaveKey($key);
    }
});

it('checks the discarded body in the manager before the session and headers start', function () {
    $index = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/index.php');

    $guard = strpos($index, 'UploadLimits::bodyWasDiscarded($_SERVER, $_POST, $_FILES)');
    expect($guard)->toBeInt()
        ->and($guard)->toBeLessThan(strpos($index, "require_once \$config['core'] . '/bootstrap.php';"))
        ->and($guard)->toBeLessThan(strpos($index, "header('Expires:"));
});

it('stops an oversized selection in the file manager before it is sent', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/actions/files.dynamic.php');

    expect($source)->toContain('onsubmit="return fileManagerUploadFits(this);"')
        ->and($source)->toContain('onchange="if (fileManagerUploadFits(document.upload)) document.upload.submit();"')
        ->and($source)->toContain("js_json(\$_lang['upload_file_too_large'])")
        ->and($source)->toContain("js_json(\$_lang['upload_files_too_large'])")
        ->and($source)->not->toContain('onchange="document.upload.submit();"');
});
