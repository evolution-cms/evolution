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

/*
| save_settings, run for real: sys_files_checksum is derived from check_files_onlogin, so it is
| computed and stored only for a manager who may edit that list. Without the permission the
| hidden tab sends neither key, and a crafted request that does changes nothing.
*/

function saveSettingsRun(string $site, array $permissions, array $post): array
{
    $output = evoRunPhp(dirname(__DIR__, 2) . '/Fixtures/save-settings-security.php', [
        $site, base64_encode(json_encode(['permissions' => $permissions, 'post' => $post])),
    ]);
    $decoded = json_decode($output, true);
    expect($decoded)->toBeArray($output);

    return $decoded;
}

function saveSettingsChecksum(string $site, array $files): string
{
    $sums = [];
    foreach ($files as $file) {
        $sums[$site . '/' . $file] = md5_file($site . '/' . $file);
    }

    return serialize($sums);
}

beforeEach(function () {
    $this->site = str_replace('\\', '/', sys_get_temp_dir()) . '/evo-save-settings-' . bin2hex(random_bytes(6));
    mkdir($this->site . '/manager', 0777, true);
    file_put_contents($this->site . '/index.php', '<?php // site');
    file_put_contents($this->site . '/manager/index.php', '<?php // manager');
});

afterEach(function () {
    @unlink($this->site . '/manager/index.php');
    @unlink($this->site . '/index.php');
    @rmdir($this->site . '/manager');
    @rmdir($this->site);
});

it('saves an ordinary form with the Security tab hidden and leaves the security settings alone', function () {
    $result = saveSettingsRun($this->site, ['settings'], ['site_name' => 'New name']);

    expect($result['notices'])->toBe([])
        ->and($result['settings']['site_name'])->toBe('New name')
        ->and($result['settings']['check_files_onlogin'])->toBe('index.php')
        ->and($result['settings']['sys_files_checksum'])->toBe('stored-checksum')
        ->and($result['settings']['use_udperms'])->toBe('1');
});

it('ignores security fields and the checksum in a crafted request without the permission', function () {
    $result = saveSettingsRun($this->site, ['settings'], [
        'site_name' => 'New name',
        'use_udperms' => '0',
        'allow_eval' => 'everywhere',
        'check_files_onlogin' => '',
        'sys_files_checksum' => 'forged',
    ]);

    expect($result['notices'])->toBe([])
        ->and($result['settings']['site_name'])->toBe('New name')
        ->and($result['settings']['use_udperms'])->toBe('1')
        ->and($result['settings']['allow_eval'])->toBe('with_scan')
        ->and($result['settings']['check_files_onlogin'])->toBe('index.php')
        ->and($result['settings']['sys_files_checksum'])->toBe('stored-checksum');
});

it('saves the security settings and recomputes the checksum for a manager with the permission', function () {
    $result = saveSettingsRun($this->site, ['settings', 'security_settings'], [
        'site_name' => 'New name',
        'use_udperms' => '0',
        'check_files_onlogin' => "index.php\nmanager/index.php",
        // the stored checksum is always the one computed from the list
        'sys_files_checksum' => 'forged',
    ]);

    expect($result['notices'])->toBe([])
        ->and($result['settings']['use_udperms'])->toBe('0')
        ->and($result['settings']['check_files_onlogin'])->toBe("index.php\nmanager/index.php")
        ->and($result['settings']['sys_files_checksum'])
        ->toBe(saveSettingsChecksum($this->site, ['index.php', 'manager/index.php']));
});

it('computes the checksum from the stored list when a permitted save does not carry it', function () {
    $result = saveSettingsRun($this->site, ['settings', 'security_settings'], ['site_name' => 'New name']);

    expect($result['notices'])->toBe([])
        ->and($result['settings']['check_files_onlogin'])->toBe('index.php')
        ->and($result['settings']['sys_files_checksum'])->toBe(saveSettingsChecksum($this->site, ['index.php']));
});
