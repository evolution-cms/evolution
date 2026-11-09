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

// The install stub seeds a fresh install; the core seeders repair the baseline on every update.
const GRANT_SEEDER_DIRS = [
    'core seeders' => 'core/database/seeders',
    'install stub' => 'install/stubs/seeds/install',
];

function seederSource(string $file, string $dir = 'core/database/seeders'): string
{
    return (string) file_get_contents(dirname(__DIR__, 4) . '/' . $dir . '/' . $file);
}

/** @return string[] */
function seededPermissionKeys(string $dir = 'core/database/seeders'): array
{
    preg_match_all(
        "/'key'\\s*=>\\s*'([^']+)'/",
        seederSource('UserPermissionsTableSeeder.php', $dir),
        $matches
    );

    return array_values(array_unique($matches[1]));
}

/** @return string[] */
function grantedPermissionKeys(string $dir = 'core/database/seeders'): array
{
    preg_match_all(
        "/'permission'\\s*=>\\s*'([^']+)'/",
        seederSource('UserRolesTableSeeder.php', $dir),
        $matches
    );

    return array_values(array_unique($matches[1]));
}

it('grants only permissions that are actually defined', function (string $dir) {
    $undefined = array_diff(grantedPermissionKeys($dir), seededPermissionKeys($dir));

    expect($undefined)->toBe([]);
})->with(GRANT_SEEDER_DIRS);

it('no longer defines or grants the dead action_ok permission', function (string $dir) {
    expect(seededPermissionKeys($dir))->not->toContain('action_ok')
        ->and(grantedPermissionKeys($dir))->not->toContain('role_actionok')
        ->and(grantedPermissionKeys($dir))->not->toContain('action_ok');
})->with(GRANT_SEEDER_DIRS);
