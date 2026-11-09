<?php

use EvolutionCMS\Support\SecuritySettings;
use EvolutionCMS\Support\SystemSettingName;

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
        ->and(SecuritySettings::isGated('site_name'))->toBeFalse()
        // the database finds the use_udperms row by these too
        ->and(SecuritySettings::isGated('USE_UDPERMS'))->toBeTrue()
        ->and(SecuritySettings::isGated('Allow_Eval'))->toBeTrue();
});

test('only one spelling of a setting name is storable', function (string $name, bool $storable) {
    $existing = ['site_name', 'jpegQuality', 'use_udperms'];

    expect(SystemSettingName::storable($name, $existing))->toBe($storable);
})->with([
    'a stored name'                      => ['site_name', true],
    'a stored mixed-case name'           => ['jpegQuality', true],
    'a new name'                         => ['my_setting', true],
    'another case of a stored name'      => ['USE_UDPERMS', false],
    'another case of a mixed-case name'  => ['jpegquality', false],
    'an accented spelling'               => ['use_udpérms', false],
    'a trailing space'                   => ['use_udperms ', false],
    'a zero-width character'             => ["use_udperms\u{200B}", false],
    'a dot'                              => ['session.cookie', false],
    'empty'                              => ['', false],
]);

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

// the install stub seeds a fresh site, the core seeder repairs the baseline on every update
it('defines security_settings and grants it to the administrator role only', function (string $dir) {
    $source = b11Source($dir . '/UserRolesTableSeeder.php');

    expect(b11Source($dir . '/UserPermissionsTableSeeder.php'))->toMatch("/'key'\s*=>\s*'security_settings'/");

    preg_match_all(
        "/'permission'\\s*=>\\s*'security_settings',\\s*'role_id'\\s*=>\\s*(\\d+)/",
        $source,
        $matches
    );

    expect($matches[1])->toBe(['1']);
})->with(['core/database/seeders', 'install/stubs/seeds/install']);

/*
| save_settings, run for real: sys_files_checksum is derived from check_files_onlogin, so it is
| computed and stored only for a manager who may edit that list. Without the permission the
| hidden tab sends neither key, and a crafted request that does changes nothing.
|
| MySQL and MariaDB match setting_name by collation, so `USE_UDPERMS` or `use_udpérms` would
| update the `use_udperms` row while the permission check saw another name. Each case runs on
| SQLite with NOCASE and, when EVO_TEST_MYSQL_HOST is set, on MySQL/MariaDB with the installer's
| default utf8mb4_general_ci and with utf8mb4_unicode_ci (EVO_TEST_MYSQL_COLLATIONS overrides;
| EVO_TEST_MYSQL_PORT, _DATABASE - an existing database, default evo_test - _USER, _PASSWORD).
*/

/** @return array<string, array{0: ?array}> */
function saveSettingsBackends(): array
{
    $backends = ['sqlite NOCASE' => [null]];
    $host = getenv('EVO_TEST_MYSQL_HOST');
    if ($host === false || $host === '') {
        return $backends;
    }
    $collations = getenv('EVO_TEST_MYSQL_COLLATIONS') ?: 'utf8mb4_general_ci,utf8mb4_unicode_ci';
    foreach (array_filter(array_map('trim', explode(',', $collations))) as $collation) {
        $backends['mysql ' . $collation] = [[
            'host' => $host,
            'port' => (int) (getenv('EVO_TEST_MYSQL_PORT') ?: 3306),
            'database' => getenv('EVO_TEST_MYSQL_DATABASE') ?: 'evo_test',
            'username' => getenv('EVO_TEST_MYSQL_USER') ?: 'root',
            'password' => (string) getenv('EVO_TEST_MYSQL_PASSWORD'),
            'collation' => $collation,
        ]];
    }

    return $backends;
}

function saveSettingsRun(string $site, array $permissions, array $post, ?array $mysql): array
{
    $output = evoRunPhp(dirname(__DIR__, 2) . '/Fixtures/save-settings-security.php', [
        $site, base64_encode(json_encode(['permissions' => $permissions, 'post' => $post, 'mysql' => $mysql])),
    ]);
    $decoded = json_decode($output, true);
    expect($decoded)->toBeArray($output);
    // fields the fixture's form always carries; the processor stores them as it always did
    foreach (['filemanager_path', 'friendly_urls', 'rb_base_dir', 'stay'] as $formField) {
        unset($decoded['settings'][$formField]);
    }

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

/** Request names that a case- or accent-insensitive collation folds onto a stored or protected setting. */
const SAVE_SETTINGS_VARIANTS = [
    'USE_UDPERMS' => '0',
    'Allow_Eval' => 'everywhere',
    'use_udpérms' => '0',
    'ENABLE_BINDINGS' => '1',
    'Check_Files_Onlogin' => '',
    'SYS_FILES_CHECKSUM' => 'forged',
    'Settings_Version' => '0.0.1',
    'JPEGQUALITY' => '10',
];

beforeEach(function () {
    $this->site = str_replace('\\', '/', sys_get_temp_dir()) . '/evo-save-settings-' . bin2hex(random_bytes(6));
    mkdir($this->site . '/manager', 0777, true);
    file_put_contents($this->site . '/index.php', '<?php // site');
    file_put_contents($this->site . '/manager/index.php', '<?php // manager');
    $this->stored = [
        'site_name' => 'Old name',
        'use_udperms' => '1',
        'allow_eval' => 'with_scan',
        'check_files_onlogin' => 'index.php',
        'sys_files_checksum' => 'stored-checksum',
        'jpegQuality' => '85',
    ];
});

afterEach(function () {
    @unlink($this->site . '/manager/index.php');
    @unlink($this->site . '/index.php');
    @rmdir($this->site . '/manager');
    @rmdir($this->site);
});

it('runs on a table that folds setting names the way the database does', function (?array $mysql) {
    $folds = saveSettingsRun($this->site, ['settings'], [], $mysql)['folds'];

    expect($folds['USE_UDPERMS'])->toBeTrue();
    if ($mysql !== null) {
        // accents and trailing spaces fold too: what the name rule has to keep out
        expect($folds['use_udpérms'])->toBeTrue()
            ->and($folds['use_udperms '])->toBeTrue();
    }
})->with(saveSettingsBackends());

it('saves an ordinary form with the Security tab hidden and leaves the security settings alone', function (?array $mysql) {
    $result = saveSettingsRun($this->site, ['settings'], ['site_name' => 'New name', 'jpegQuality' => '90'], $mysql);

    expect($result['notices'])->toBe([])
        ->and($result['settings'])->toEqualCanonicalizing(
            ['site_name' => 'New name', 'jpegQuality' => '90'] + $this->stored
        );
})->with(saveSettingsBackends());

it('ignores security fields, the checksum and other spellings of them without the permission', function (?array $mysql) {
    $result = saveSettingsRun($this->site, ['settings'], [
        'site_name' => 'New name',
        'use_udperms' => '0',
        'allow_eval' => 'everywhere',
        'check_files_onlogin' => '',
        'sys_files_checksum' => 'forged',
    ] + SAVE_SETTINGS_VARIANTS, $mysql);

    // no row changed but the ordinary one, and none was added under another spelling
    expect($result['notices'])->toBe([])
        ->and($result['settings'])->toEqualCanonicalizing(['site_name' => 'New name'] + $this->stored);
})->with(saveSettingsBackends());

it('saves the security settings and recomputes the checksum for a manager with the permission', function (?array $mysql) {
    $result = saveSettingsRun($this->site, ['settings', 'security_settings'], [
        'site_name' => 'New name',
        'use_udperms' => '0',
        'check_files_onlogin' => "index.php\nmanager/index.php",
        // the stored checksum is always the one computed from the list
        'sys_files_checksum' => 'forged',
    ] + SAVE_SETTINGS_VARIANTS, $mysql);

    // only the stored spellings are written, for the administrator too
    expect($result['notices'])->toBe([])
        ->and($result['settings'])->toEqualCanonicalizing([
            'site_name' => 'New name',
            'use_udperms' => '0',
            'allow_eval' => 'with_scan',
            'check_files_onlogin' => "index.php\nmanager/index.php",
            'sys_files_checksum' => saveSettingsChecksum($this->site, ['index.php', 'manager/index.php']),
            'jpegQuality' => '85',
        ]);
})->with(saveSettingsBackends());

it('computes the checksum from the stored list when a permitted save does not carry it', function (?array $mysql) {
    $result = saveSettingsRun($this->site, ['settings', 'security_settings'], ['site_name' => 'New name'], $mysql);

    expect($result['notices'])->toBe([])
        ->and($result['settings']['check_files_onlogin'])->toBe('index.php')
        ->and($result['settings']['sys_files_checksum'])->toBe(saveSettingsChecksum($this->site, ['index.php']));
})->with(saveSettingsBackends());
