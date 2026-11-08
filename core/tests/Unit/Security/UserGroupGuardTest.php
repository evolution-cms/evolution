<?php

use EvolutionCMS\Support\UserGroupGuard;

/*
| Setting a user's groups is reached by save_user alone. Without manage_groups a manager must not
| grant a group it does not hold (self-elevation into another group's document access) nor strip a
| group it cannot see. With manage_groups the posted set stands.
*/

it('lets a manager with manage_groups set any groups', function () {
    expect(UserGroupGuard::authorizedGroups([2], [1], [1, 2, 3], true))
        ->toEqualCanonicalizing([1, 2, 3]);
});

it('keeps groups beyond the actor reach and ignores groups it does not hold', function () {
    // actor is only in group 2; target currently in 1 and 2; actor posts 2,3,4
    $result = UserGroupGuard::authorizedGroups([2], [1, 2], [2, 3, 4], false);

    // group 1 kept (actor can't touch it), group 2 kept (own + posted), 3/4 ignored (not actor's)
    expect($result)->toEqualCanonicalizing([1, 2]);
});

it('lets the actor remove its own group', function () {
    // actor in group 2; target in 1 and 2; actor posts only 1 (drops 2)
    $result = UserGroupGuard::authorizedGroups([2], [1, 2], [1], false);

    expect($result)->toEqualCanonicalizing([1]);
});

it('does not let a groupless actor change anything', function () {
    $result = UserGroupGuard::authorizedGroups([], [1, 2], [1, 2, 3], false);

    expect($result)->toEqualCanonicalizing([1, 2]);
});

it('cannot add itself to a foreign group (the live save_user hole)', function () {
    // actor in group 1 only; posts 1 and 2 for itself; group 2 must not be granted
    $result = UserGroupGuard::authorizedGroups([1], [1], [1, 2], false);

    expect($result)->not->toContain(2)
        ->and($result)->toEqualCanonicalizing([1]);
});
