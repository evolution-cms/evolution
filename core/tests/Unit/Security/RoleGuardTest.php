<?php

use EvolutionCMS\Support\RoleGuard;

/*
| Saving, deleting or assigning a role is reached by the ordinary role/user rights, so a
| non-administrator could otherwise grant a role (its own, a new one, or a user) a permission it
| does not hold, or tamper with a stronger role. RoleGuard caps every such action at the actor's
| own reach; role 1 bypasses.
*/

$actor = ['edit_document', 'save_document', 'publish_document'];

it('lets the administrator manage any role', function () {
    expect(RoleGuard::mayManageRole(1, [], 1, ['save_plugin', 'settings']))->toBeTrue()
        ->and(RoleGuard::mayManageRole(1, [], 5, ['save_plugin']))->toBeTrue();
});

it('never lets a non-administrator touch the administrator role', function () use ($actor) {
    expect(RoleGuard::mayManageRole(3, $actor, 1, $actor))->toBeFalse();
});

it('refuses a role that holds a permission the actor lacks', function () use ($actor) {
    expect(RoleGuard::mayManageRole(3, $actor, 5, ['save_document', 'save_plugin']))->toBeFalse();
});

it('allows a role whose permissions are within the actor reach', function () use ($actor) {
    expect(RoleGuard::mayManageRole(3, $actor, 5, ['edit_document', 'save_document']))->toBeTrue();
});

it('allows a brand new role', function () use ($actor) {
    expect(RoleGuard::mayManageRole(3, $actor, 0, []))->toBeTrue();
});

it('grants only the permissions the actor holds, baseline ones included', function () use ($actor) {
    $result = RoleGuard::grantablePermissions(
        3,
        [...$actor, 'frames'],
        ['edit_document', 'save_plugin', 'settings'],
        ['frames', 'home']
    );

    // home is always-on, but this actor does not hold it
    expect($result)->toEqualCanonicalizing(['edit_document', 'frames'])
        ->and($result)->not->toContain('save_plugin')
        ->and($result)->not->toContain('settings')
        ->and($result)->not->toContain('home');
});

it('lets the administrator grant everything requested', function () {
    $result = RoleGuard::grantablePermissions(1, [], ['save_plugin', 'settings'], ['frames']);

    expect($result)->toEqualCanonicalizing(['save_plugin', 'settings', 'frames']);
});
