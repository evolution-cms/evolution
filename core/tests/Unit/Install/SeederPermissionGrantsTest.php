<?php

/*
|--------------------------------------------------------------------------
| Role grants must reference real permissions
|--------------------------------------------------------------------------
|
| UserRolesTableSeeder granted 'role_actionok' to every default role, but the
| permission it meant was keyed 'action_ok' (the 'role_actionok' string is only
| the permission's lang_key). So the grant matched no permission, nothing ever
| checked 'action_ok', and the permission was dead. These tests parse the two
| seeders and pin that every granted permission is a defined key - the invariant
| that the dangling grant broke - and that the dead permission is gone.
|
*/

function seederSource(string $file): string
{
    return (string) file_get_contents(dirname(__DIR__, 3) . '/database/seeders/' . $file);
}

/** @return string[] */
function seededPermissionKeys(): array
{
    preg_match_all(
        "/'key'\\s*=>\\s*'([^']+)'/",
        seederSource('UserPermissionsTableSeeder.php'),
        $matches
    );

    return array_values(array_unique($matches[1]));
}

/** @return string[] */
function grantedPermissionKeys(): array
{
    preg_match_all(
        "/'permission'\\s*=>\\s*'([^']+)'/",
        seederSource('UserRolesTableSeeder.php'),
        $matches
    );

    return array_values(array_unique($matches[1]));
}

it('grants only permissions that are actually defined', function () {
    $undefined = array_diff(grantedPermissionKeys(), seededPermissionKeys());

    expect($undefined)->toBe([]);
});

it('no longer defines or grants the dead action_ok permission', function () {
    expect(seededPermissionKeys())->not->toContain('action_ok')
        ->and(grantedPermissionKeys())->not->toContain('role_actionok')
        ->and(grantedPermissionKeys())->not->toContain('action_ok');
});
