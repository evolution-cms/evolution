<?php

/*
|--------------------------------------------------------------------------
| generate_password()
|--------------------------------------------------------------------------
|
| The password proposed when an account is created is mailed to its owner, so it has to be
| unguessable. It used to come from mt_rand() re-seeded with mt_srand((float)microtime() *
| 1000000) on every call - a seed anyone who receives such a mail can narrow down to the second
| the account was created. These tests pin the properties that fix depends on.
|
*/

beforeEach(function () {
    require_once dirname(__DIR__, 3) . '/functions/helper.php';
});

test('it returns the requested length from the intended alphabet', function () {
    foreach ([1, 8, 10, 64] as $length) {
        $password = generate_password($length);

        expect(strlen($password))->toBe($length)
            ->and($password)->toMatch('/^[abcdefghjkmnpqrstuvxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789]+$/');
    }
});

test('it does not repeat itself within a single second', function () {
    // The clock-seeded version returned the same password for every call that landed in the
    // same microsecond, which is what a loop like this reproduces.
    $passwords = [];
    for ($i = 0; $i < 200; $i++) {
        $passwords[] = generate_password(10);
    }

    expect(count(array_unique($passwords)))->toBe(200);
});

test('it spreads across the alphabet rather than a seeded sequence', function () {
    $sample = '';
    for ($i = 0; $i < 200; $i++) {
        $sample .= generate_password(10);
    }

    // 2000 characters over a 54-character alphabet: a generator stuck on a narrow seed shows up
    // here as a handful of distinct characters.
    expect(count(array_unique(str_split($sample))))->toBeGreaterThan(40);
});

test('guids are unique across calls in the same request', function () {
    $guids = [];
    for ($i = 0; $i < 200; $i++) {
        $guids[] = createGUID();
    }

    expect(count(array_unique($guids)))->toBe(200)
        ->and($guids[0])->toMatch('/^[0-9a-f]{32}$/');
});
