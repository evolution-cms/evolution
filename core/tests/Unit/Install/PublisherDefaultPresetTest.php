<?php

/*
|--------------------------------------------------------------------------
| The default Publisher preset must not ship settings or bk_manager
|--------------------------------------------------------------------------
|
| Publisher is "Editor with expanded permissions" and sits below the
| administrator. It used to be seeded with `settings` and `bk_manager`:
|
|  - `bk_manager` lets the Backup manager download a full database dump,
|    which exposes every user's password hash and every secret stored in
|    the settings table, and (with a code permission) restore one.
|  - `settings` reaches the non-Security tabs that stay outside the
|    `security_settings` gate - the File Manager tab (global filemanager_path,
|    upload allow-lists) and the Mail tab (SMTP).
|
| Neither belongs in a content role by default, so the default preset no
| longer grants them. This only changes fresh installs: the core
| baseline-repair seeder only inserts missing rows, so an existing site that
| already granted them keeps them.
|
| Two seeders define the preset and must agree: the install stub
| (install/stubs/seeds/install) seeds a fresh install, and the idempotent
| core baseline-repair seeder (core/database/seeders) runs on every core
| migrate afterwards. If the core seeder still listed them, it would re-add
| them on the next update, so both are checked here.
|
*/

function presetSource(string $relative): string
{
    return (string) file_get_contents(dirname(__DIR__, 4) . '/' . $relative);
}

/**
 * @return array<int, string[]> role id => sorted, unique permission keys it is granted
 */
function presetGrants(string $relative): array
{
    preg_match_all(
        "/'permission'\\s*=>\\s*'([a-z0-9_]+)',\\s*'role_id'\\s*=>\\s*(\\d+)/",
        presetSource($relative),
        $matches,
        PREG_SET_ORDER
    );

    $byRole = [];
    foreach ($matches as $match) {
        $byRole[(int) $match[2]][] = $match[1];
    }
    foreach ($byRole as &$permissions) {
        $permissions = array_values(array_unique($permissions));
        sort($permissions);
    }

    return $byRole;
}

const PRESET_SEEDERS = [
    'install/stubs/seeds/install/UserRolesTableSeeder.php',
    'core/database/seeders/UserRolesTableSeeder.php',
];

it('does not grant the Publisher role settings or bk_manager', function (string $seeder) {
    $publisher = presetGrants($seeder)[3] ?? [];

    expect($publisher)->not->toContain('settings')
        ->and($publisher)->not->toContain('bk_manager')
        // the role is still a working content role, not gutted
        ->and($publisher)->toContain('save_document')
        ->and($publisher)->toContain('delete_chunk')
        ->and($publisher)->toContain('save_user');
})->with(PRESET_SEEDERS);

it('keeps settings and bk_manager with the administrator role', function (string $seeder) {
    $administrator = presetGrants($seeder)[1] ?? [];

    expect($administrator)->toContain('settings')
        ->and($administrator)->toContain('bk_manager');
})->with(PRESET_SEEDERS);

test('the install stub and the core baseline-repair seeder agree on every role preset', function () {
    $stub = presetGrants(PRESET_SEEDERS[0]);
    $core = presetGrants(PRESET_SEEDERS[1]);

    expect(array_keys($stub))->toBe(array_keys($core));
    foreach ($stub as $roleId => $permissions) {
        expect($core[$roleId])->toBe($permissions, "role {$roleId} preset drifted between the two seeders");
    }
});
