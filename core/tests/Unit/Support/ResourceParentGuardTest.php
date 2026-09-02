<?php

use EvolutionCMS\Support\ResourceParentGuard;

test('administrators and disabled permissions skip the document group lookup', function () {
    expect(ResourceParentGuard::grantsWithoutLookup(1, 1))->toBeTrue()
        ->and(ResourceParentGuard::grantsWithoutLookup('1', '1'))->toBeTrue()
        ->and(ResourceParentGuard::grantsWithoutLookup(2, 0))->toBeTrue()
        ->and(ResourceParentGuard::grantsWithoutLookup(2, ''))->toBeTrue()
        ->and(ResourceParentGuard::grantsWithoutLookup(2, null))->toBeTrue()
        ->and(ResourceParentGuard::grantsWithoutLookup(2, 1))->toBeFalse()
        ->and(ResourceParentGuard::grantsWithoutLookup(null, 1))->toBeFalse();
});

test('the site root follows the allow root setting instead of an undefined global', function () {
    expect(ResourceParentGuard::allowsRoot(1))->toBeTrue()
        ->and(ResourceParentGuard::allowsRoot('1'))->toBeTrue()
        ->and(ResourceParentGuard::allowsRoot(0))->toBeFalse()
        ->and(ResourceParentGuard::allowsRoot('0'))->toBeFalse()
        ->and(ResourceParentGuard::allowsRoot(''))->toBeFalse()
        ->and(ResourceParentGuard::allowsRoot(null))->toBeFalse();
});

test('duplicating a root level resource is blocked only while the root is denied', function () {
    expect(ResourceParentGuard::duplicateBlockedAtRoot(0, 0))->toBeTrue()
        ->and(ResourceParentGuard::duplicateBlockedAtRoot(0, null))->toBeTrue()
        ->and(ResourceParentGuard::duplicateBlockedAtRoot(1, 0))->toBeFalse()
        ->and(ResourceParentGuard::duplicateBlockedAtRoot(0, 12))->toBeFalse();
});

test('document access combines the private flag with the document groups of the user', function () {
    expect(ResourceParentGuard::documentIsAccessible(1, 1, 1, 7, []))->toBeTrue()
        ->and(ResourceParentGuard::documentIsAccessible(2, 0, 1, 7, []))->toBeTrue()
        ->and(ResourceParentGuard::documentIsAccessible(2, 1, 0, 7, []))->toBeTrue()
        ->and(ResourceParentGuard::documentIsAccessible(2, 1, 1, 7, [3, 9]))->toBeFalse()
        ->and(ResourceParentGuard::documentIsAccessible(2, 1, 1, 7, [3, 7, 9]))->toBeTrue()
        ->and(ResourceParentGuard::documentIsAccessible(2, 1, '1', '7', [7]))->toBeTrue();
});

test('only reachable and untrashed nodes accept a new child', function () {
    expect(ResourceParentGuard::nodeAcceptsChild(1, 0))->toBeTrue()
        ->and(ResourceParentGuard::nodeAcceptsChild('1', '0'))->toBeTrue()
        ->and(ResourceParentGuard::nodeAcceptsChild(1, 1))->toBeFalse()
        ->and(ResourceParentGuard::nodeAcceptsChild(0, 0))->toBeFalse();
});

test('a new resource lands in the root only when the root is available', function () {
    expect(ResourceParentGuard::pickDefaultParent(true, 42))->toBe(0)
        ->and(ResourceParentGuard::pickDefaultParent(false, 42))->toBe(42)
        ->and(ResourceParentGuard::pickDefaultParent(false, '42'))->toBe(42)
        ->and(ResourceParentGuard::pickDefaultParent(false, null))->toBe(0);
});
