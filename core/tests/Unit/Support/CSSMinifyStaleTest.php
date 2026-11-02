<?php

use EvolutionCMS\Support\Formatter\CSSMinify;

beforeEach(function () {
    $this->dir = sys_get_temp_dir() . '/evo-cssminify-' . bin2hex(random_bytes(4));
    mkdir($this->dir);
    $this->source = $this->dir . '/tree.css';
    $this->bundle = $this->dir . '/styles.min.css';
    file_put_contents($this->source, '.a { color: red; }');
});

afterEach(function () {
    array_map('unlink', glob($this->dir . '/*'));
    rmdir($this->dir);
});

test('a missing bundle is stale', function () {
    expect(CSSMinify::isStale($this->bundle, [$this->source]))->toBeTrue();
});

test('a bundle built after its sources is fresh', function () {
    file_put_contents($this->bundle, '.a{color:red}');
    touch($this->source, time() - 60);
    touch($this->bundle, time());
    clearstatcache();

    expect(CSSMinify::isStale($this->bundle, [$this->source]))->toBeFalse();
});

test('a source changed after the bundle was built makes it stale', function () {
    file_put_contents($this->bundle, '.a{color:red}');
    touch($this->bundle, time() - 60);
    touch($this->source, time());
    clearstatcache();

    expect(CSSMinify::isStale($this->bundle, [$this->source]))->toBeTrue();
});

test('missing or invalid source entries do not mark the bundle stale', function () {
    file_put_contents($this->bundle, '.a{color:red}');
    touch($this->source, time() - 60);
    clearstatcache();

    expect(CSSMinify::isStale($this->bundle, [$this->source, $this->dir . '/gone.css', null, []]))->toBeFalse();
});
