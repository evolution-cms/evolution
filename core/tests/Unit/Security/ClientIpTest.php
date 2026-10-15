<?php

use EvolutionCMS\Support\ClientIp;

/*
|--------------------------------------------------------------------------
| Client-IP and X-Forwarded-For are request headers, not addresses
|--------------------------------------------------------------------------
|
| updateValidatedUserSession() stored the raw header as the session IP, and the dashboard's online
| users widget printed it unescaped inside a template the CMS parser runs over, so any manager
| could plant markup or [[snippet]] calls that ran in an administrator's session.
|
*/

it('keeps a well-formed forwarded address', function (array $server, string $ip) {
    expect(ClientIp::fromServer($server))->toBe($ip);
})->with([
    'client ip' => [['HTTP_CLIENT_IP' => '203.0.113.7', 'REMOTE_ADDR' => '10.0.0.1'], '203.0.113.7'],
    'forwarded for' => [['HTTP_X_FORWARDED_FOR' => '198.51.100.2', 'REMOTE_ADDR' => '10.0.0.1'], '198.51.100.2'],
    'forwarded chain' => [['HTTP_X_FORWARDED_FOR' => '198.51.100.2, 10.0.0.5', 'REMOTE_ADDR' => '10.0.0.1'], '198.51.100.2'],
    'ipv6' => [['HTTP_X_FORWARDED_FOR' => '2001:db8::1', 'REMOTE_ADDR' => '10.0.0.1'], '2001:db8::1'],
    'remote only' => [['REMOTE_ADDR' => '192.0.2.10'], '192.0.2.10'],
]);

it('falls back past a header that is not an address', function (string $header) {
    expect(ClientIp::fromServer(['HTTP_CLIENT_IP' => $header, 'REMOTE_ADDR' => '192.0.2.10']))->toBe('192.0.2.10')
        ->and(ClientIp::fromServer(['HTTP_X_FORWARDED_FOR' => $header, 'REMOTE_ADDR' => '192.0.2.10']))->toBe('192.0.2.10');
})->with([
    'markup' => ['<script src=//x.yz></script>'],
    'event handler' => ['<svg onload=alert(1)>'],
    'snippet call' => ['[[Snippet? &a=`1`]]'],
    'setting tag' => ['[(site_name)]'],
    'sql' => ["1.2.3.4' OR '1'='1"],
    'empty first entry' => [', 198.51.100.2'],
]);

it('returns UNKNOWN when no source is an address', function () {
    expect(ClientIp::fromServer([]))->toBe(ClientIp::UNKNOWN)
        ->and(ClientIp::fromServer(['REMOTE_ADDR' => '<b>']))->toBe(ClientIp::UNKNOWN)
        ->and(ClientIp::fromServer(['HTTP_CLIENT_IP' => ['1.2.3.4']]))->toBe(ClientIp::UNKNOWN);
});

it('consults only the headers it is given', function () {
    $server = ['HTTP_CLIENT_IP' => '203.0.113.7', 'HTTP_X_FORWARDED_FOR' => '198.51.100.2', 'REMOTE_ADDR' => '192.0.2.10'];

    expect(ClientIp::fromServer($server, ['HTTP_X_FORWARDED_FOR']))->toBe('198.51.100.2')
        ->and(ClientIp::fromServer($server, []))->toBe('192.0.2.10');
});

it('records session and manager log addresses through the validator', function () {
    $root = dirname(__DIR__, 3);
    $core = (string) file_get_contents($root . '/src/Core.php');
    $log = (string) file_get_contents($root . '/src/Legacy/LogHandler.php');

    expect($core)->toContain("\$_SESSION['ip'] = Support\\ClientIp::fromServer(\$_SERVER);")
        ->and($core)->not->toContain("\$_SESSION['ip'] = getenv('HTTP_CLIENT_IP');")
        ->and($log)->toContain("ClientIp::fromServer(\$_SERVER, ['HTTP_X_FORWARDED_FOR'])")
        ->and($log)->not->toContain("return \$_SERVER['HTTP_X_FORWARDED_FOR'];");
});
