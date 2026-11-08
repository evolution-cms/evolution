<?php

use EvolutionCMS\Support\SecuritySettings;

/*
|--------------------------------------------------------------------------
| The Security settings tab needs its own permission
|--------------------------------------------------------------------------
|
| Anyone with the generic `settings` right could change the settings on the
| manager's Security tab - including use_udperms / udperms_allowroot (the document
| ACL itself) and allow_eval / enable_bindings / safe_functions_at_eval (server-side
| code execution). The default Publisher role holds `settings`, so this was reachable
| below the administrator. These settings now need the dedicated `security_settings`
| permission: the form hides the tab without it and save_settings skips the keys, and
| the permission is granted to the administrator role only by default.
|
*/

function b11Source(string $relative): string
{
    return (string) file_get_contents(dirname(__DIR__, 4) . '/' . $relative);
}

/** @return string[] the setting keys the Security tab renders */
function securityTabKeys(): array
{
    preg_match_all(
        "/'name'\\s*=>\\s*'([a-z0-9_]+)'/",
        b11Source('manager/views/page/system_settings/security.blade.php'),
        $matches
    );

    return array_values(array_unique($matches[1]));
}

test('the gated list matches exactly the keys the Security tab renders', function () {
    $tab = securityTabKeys();
    $gated = SecuritySettings::GATED;
    sort($tab);
    sort($gated);

    expect($gated)->toBe($tab);
});

test('isGated answers for the decisive keys', function () {
    expect(SecuritySettings::isGated('use_udperms'))->toBeTrue()
        ->and(SecuritySettings::isGated('allow_eval'))->toBeTrue()
        ->and(SecuritySettings::isGated('safe_functions_at_eval'))->toBeTrue()
        // an ordinary setting is not gated
        ->and(SecuritySettings::isGated('site_name'))->toBeFalse();
});

it('skips the gated keys in save_settings without the permission', function () {
    $source = b11Source('manager/processors/save_settings.processor.php');

    expect($source)->toContain("hasPermission('security_settings')")
        ->and($source)->toContain('SecuritySettings::isGated($k)')
        ->and($source)->toContain('continue;');
});

it('hides the Security tab without the permission', function () {
    $source = b11Source('manager/views/page/system_settings.blade.php');

    expect($source)->toContain('@if($canEditSecuritySettings)')
        ->and($source)->toContain("@include('manager::page.system_settings.security')");
});

it('grants security_settings to the administrator role only', function () {
    $source = b11Source('core/database/seeders/UserRolesTableSeeder.php');

    preg_match_all(
        "/'permission'\\s*=>\\s*'security_settings',\\s*'role_id'\\s*=>\\s*(\\d+)/",
        $source,
        $matches
    );

    expect($matches[1])->toBe(['1']);
});
