<?php

test('niceCount keeps small quantities readable', function () {
    expect(niceCount(0))->toBe('0')
        ->and(niceCount(999))->toBe('999')
        ->and(niceCount(-42))->toBe('-42');
});

test('niceCount compacts large quantities with SI suffixes', function () {
    expect(niceCount(1000))->toBe('1K')
        ->and(niceCount(1500))->toBe('1.5K')
        ->and(niceCount(100000))->toBe('100K')
        ->and(niceCount(125000))->toBe('125K')
        ->and(niceCount(125500))->toBe('125.5K')
        ->and(niceCount(999999))->toBe('1M')
        ->and(niceCount(5000000))->toBe('5M')
        ->and(niceCount(2500000000))->toBe('2.5B');
});

test('niceCount supports configurable precision', function () {
    expect(niceCount(1234567, 2))->toBe('1.23M')
        ->and(niceCount(1234567, 0))->toBe('1M');
});
