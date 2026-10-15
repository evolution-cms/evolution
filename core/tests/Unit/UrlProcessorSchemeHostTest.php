<?php

use EvolutionCMS\Core;
use EvolutionCMS\UrlProcessor;

/*
| makeUrl() with an explicit scheme ('http' / 'https', e.g. [~id~] in a template that forces https)
| built the host from the raw Host header. define.inc.php validates that header (and pins it to
| EVO_SITE_HOSTNAMES when configured) before it becomes the site URL; makeUrl() bypassed both, so a
| forged request put another domain into links - and into every page cached from that request.
*/

function schemeHostProcessor(string $siteUrl): UrlProcessor
{
    $config = [
        'site_url' => $siteUrl,
        'base_url' => '/',
        'site_start' => 1,
        'friendly_urls' => 0,
    ];
    $core = test()->getMockBuilder(Core::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getConfig', 'invokeEvent'])
        ->getMock();
    $core->documentListing = [];
    $core->aliasListing = [];
    $core->virtualDir = '';
    $core->method('getConfig')->willReturnCallback(static fn ($name, $default = null) => $config[$name] ?? $default);
    $core->method('invokeEvent')->willReturn(false);

    return new UrlProcessor($core);
}

beforeEach(function () {
    $this->server = $_SERVER;
    $_SERVER['HTTP_HOST'] = 'attacker.example';
    $_SERVER['HTTPS'] = '';
});

afterEach(function () {
    $_SERVER = $this->server;
});

it('takes the host of a scheme link from the site URL, not the Host header', function () {
    $url = schemeHostProcessor('http://www.example.test/')->makeUrl(5, '', '', 'https');

    expect($url)->toBe('https://www.example.test/index.php?id=5')
        ->and($url)->not->toContain('attacker.example');
});

it('keeps the port of the site URL', function () {
    expect(schemeHostProcessor('http://127.0.0.1:8104/')->makeUrl(5, '', '', 'http'))
        ->toBe('http://127.0.0.1:8104/index.php?id=5')
        ->and(schemeHostProcessor('http://[::1]:8080/')->makeUrl(5, '', '', 'https'))
        ->toBe('https://[::1]:8080/index.php?id=5');
});

it('leaves links without a scheme relative', function () {
    expect(schemeHostProcessor('http://www.example.test/')->makeUrl(5))->toBe('/index.php?id=5');
});
