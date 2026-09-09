<?php
/**
 * Asserts that a freshly installed site carries the data the installer is
 * supposed to have put there.
 *
 * Reads the connection the installer just wrote, so a site whose config file
 * points somewhere unusable fails here rather than in the manager. Deliberately
 * does not boot the CMS: the point is what reached the database, and a
 * bootstrap failure would hide it behind a stack trace.
 *
 * Usage: php smoke.php [repository root]
 */

$root = rtrim($argv[1] ?? dirname(__DIR__, 3), '/');

/** Stand in for Laravel's helper so the config file can be read on its own. */
if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        $value = getenv($key);

        return $value === false || $value === '' ? $default : $value;
    }
}

$failures = [];
$checks = 0;

function check(string $what, bool $ok, string $detail = ''): void
{
    global $failures, $checks;

    $checks++;
    if ($ok) {
        echo '  ok   ' . $what . PHP_EOL;

        return;
    }

    $line = $what . ($detail !== '' ? ' (' . $detail . ')' : '');
    echo '  FAIL ' . $line . PHP_EOL;
    $failures[] = $line;
}

$configFile = $root . '/core/config/database/connections/default.php';
if (!is_file($configFile)) {
    fwrite(STDERR, 'smoke: the installer wrote no connection config at ' . $configFile . PHP_EOL);
    exit(1);
}

$config = require $configFile;
$prefix = $config['prefix'];
$driver = $config['driver'];

$dsn = match ($driver) {
    'mysql' => 'mysql:host=' . $config['host'] . ';port=' . $config['port'] . ';dbname=' . $config['database'] . ';charset=' . $config['charset'],
    'pgsql' => 'pgsql:host=' . $config['host'] . ';port=' . $config['port'] . ';dbname=' . $config['database'],
    'sqlite' => 'sqlite:' . $config['database'],
    default => null,
};
if ($dsn === null) {
    fwrite(STDERR, 'smoke: unsupported driver ' . $driver . PHP_EOL);
    exit(1);
}

$pdo = new PDO($dsn, $config['username'], $config['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

/** Quote a prefixed table name the way the server in front of us wants it. */
function t(string $table): string
{
    global $prefix, $driver;

    $name = $prefix . $table;

    return $driver === 'mysql' ? '`' . $name . '`' : '"' . $name . '"';
}

/** Quote a column name; pgsql folds unquoted identifiers to lower case. */
function c(string $column): string
{
    global $driver;

    return $driver === 'mysql' ? '`' . $column . '`' : '"' . $column . '"';
}

function scalar(string $sql, array $bindings = [])
{
    global $pdo;

    $statement = $pdo->prepare($sql);
    $statement->execute($bindings);
    $value = $statement->fetchColumn();

    return $value === false ? null : $value;
}

function count_rows(string $table): int
{
    return (int) scalar('SELECT COUNT(*) FROM ' . t($table));
}

function has_column(string $table, string $column): bool
{
    global $pdo;

    try {
        $pdo->query('SELECT ' . c($column) . ' FROM ' . t($table) . ' WHERE 1 = 0');
    } catch (PDOException $e) {
        return false;
    }

    return true;
}

function setting(string $name)
{
    return scalar('SELECT setting_value FROM ' . t('system_settings') . ' WHERE setting_name = ?', [$name]);
}

$admin = getenv('EVO_ADMIN') ?: 'admin';
$adminEmail = getenv('EVO_ADMIN_EMAIL') ?: 'admin@evo.local';
$adminPassword = getenv('EVO_ADMIN_PASSWORD') ?: '';
$language = getenv('EVO_LANGUAGE') ?: 'en';

echo 'Smoke test: ' . $driver . ' ' . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION)
    . ", prefix '" . $prefix . "'" . PHP_EOL;

echo PHP_EOL . 'Schema' . PHP_EOL;
// Every table the migration chain creates has to be there: a migration that
// silently skipped its Schema::create leaves the site half installed.
$expected = [
    'active_user_locks', 'active_user_sessions', 'active_users', 'categories',
    'document_groups', 'documentgroup_names', 'event_log', 'file_groups',
    'manager_log', 'member_groups', 'membergroup_access', 'membergroup_names',
    'migrations_install', 'permissions', 'permissions_groups', 'role_permissions',
    'site_content', 'site_content_closure', 'site_htmlsnippets', 'site_module_access',
    'site_module_depobj', 'site_modules', 'site_plugin_events', 'site_plugins',
    'site_snippets', 'site_templates', 'site_tmplvar_access',
    'site_tmplvar_contentvalues', 'site_tmplvar_templates', 'site_tmplvars',
    'system_cli_task_logs', 'system_cli_tasks', 'system_eventnames',
    'system_scheduler_health', 'system_settings', 'system_worker_health',
    'user_attributes', 'user_role_vars', 'user_roles', 'user_settings',
    'user_values', 'users',
];
$missing = [];
foreach ($expected as $table) {
    try {
        $pdo->query('SELECT 1 FROM ' . t($table) . ' WHERE 1 = 0');
    } catch (PDOException $e) {
        $missing[] = $prefix . $table;
    }
}
check(count($expected) . ' tables created', $missing === [], 'missing: ' . implode(', ', $missing));

// Columns added by the core only migrations. Those run after the install stubs
// chain, so their absence means that second migrate call did nothing.
$lateColumns = [
    'site_templates' => ['templatefileextension', 'templatesource'],
    'users' => ['cachepwd_valid_to'],
];
foreach ($lateColumns as $table => $columns) {
    foreach ($columns as $column) {
        check($table . '.' . $column . ' exists (core migration applied)', has_column($table, $column));
    }
}

// The installer records its whole chain - the install stubs and the core
// migrations it runs afterwards - in migrations_install; the `migrations` table
// of a plain Laravel app stays unused here (core/config/database/migrations.php).
$applied = (int) scalar('SELECT COUNT(*) FROM ' . t('migrations_install'));
check('migrations were recorded', $applied > 0, 'recorded: ' . $applied);

echo PHP_EOL . 'Seeded content' . PHP_EOL;
check('one document seeded', count_rows('site_content') === 1, 'rows: ' . count_rows('site_content'));
$home = $pdo->query('SELECT * FROM ' . t('site_content') . ' ORDER BY id LIMIT 1')->fetch() ?: [];
check('the document is the install success page', ($home['alias'] ?? '') === 'minimal-base', 'alias: ' . ($home['alias'] ?? 'none'));
check('the document is published', (int) ($home['published'] ?? 0) === 1);
check('the document uses the seeded template', (int) ($home['template'] ?? 0) === 1);
check('a template was seeded', count_rows('site_templates') >= 1);
check('event names were seeded', count_rows('system_eventnames') > 50, 'rows: ' . count_rows('system_eventnames'));
check('settings were seeded', count_rows('system_settings') >= 40, 'rows: ' . count_rows('system_settings'));
check('permission groups were seeded', count_rows('permissions_groups') >= 14, 'rows: ' . count_rows('permissions_groups'));
check('permissions were seeded', count_rows('permissions') > 0);
check('role permissions were seeded', count_rows('role_permissions') > 0);

echo PHP_EOL . 'Settings written by the installer' . PHP_EOL;
check("manager_language is '" . $language . "'", setting('manager_language') === $language, 'got: ' . var_export(setting('manager_language'), true));
check("manager_theme is 'default'", setting('manager_theme') === 'default', 'got: ' . var_export(setting('manager_theme'), true));
check('site_id was generated', is_string(setting('site_id')) && setting('site_id') !== '');
check('emailsender is the admin email', setting('emailsender') === $adminEmail, 'got: ' . var_export(setting('emailsender'), true));
check('auto_template_logic is on', (string) setting('auto_template_logic') === '1');

echo PHP_EOL . 'Admin account' . PHP_EOL;
$statement = $pdo->prepare('SELECT * FROM ' . t('users') . ' WHERE username = ?');
$statement->execute([$admin]);
$user = $statement->fetch() ?: [];
check('the admin user exists', $user !== []);
$hash = (string) ($user['password'] ?? '');
check('the password was hashed', $hash !== '' && $hash !== $adminPassword && strlen($hash) >= 32, 'length: ' . strlen($hash));

$statement = $pdo->prepare('SELECT * FROM ' . t('user_attributes') . ' WHERE ' . c('internalKey') . ' = ?');
$statement->execute([$user['id'] ?? 0]);
$attributes = $statement->fetch() ?: [];
check('the admin has attributes', $attributes !== []);
check('the admin email was stored', ($attributes['email'] ?? '') === $adminEmail, 'got: ' . ($attributes['email'] ?? 'none'));
check('the admin is verified', (int) ($attributes['verified'] ?? 0) === 1);
$administrator = scalar('SELECT id FROM ' . t('user_roles') . ' WHERE name = ?', ['Administrator']);
check('the Administrator role exists', $administrator !== null);
check('the admin holds the Administrator role', (int) ($attributes['role'] ?? -1) === (int) $administrator, 'role: ' . ($attributes['role'] ?? 'none'));

echo PHP_EOL . 'Bundled extras' . PHP_EOL;
// installModulesAndPlugins() parses assets/plugins and assets/modules; a parse
// that quietly found nothing would leave a site with no plugins at all.
check('plugins were installed', count_rows('site_plugins') > 0, 'rows: ' . count_rows('site_plugins'));
check('plugin events were bound', count_rows('site_plugin_events') > 0, 'rows: ' . count_rows('site_plugin_events'));
check('modules were installed', count_rows('site_modules') > 0, 'rows: ' . count_rows('site_modules'));

echo PHP_EOL . 'Stability' . PHP_EOL;
// The entrypoint runs this script once after installing and again after the
// updater has been over the same site. The second run compares against the
// counts the first one recorded: a migration or seeder that is not idempotent
// shows up here as rows appearing twice.
$baselineFile = getenv('EVO_SMOKE_BASELINE') ?: sys_get_temp_dir() . '/evo-smoke-baseline.json';
$counted = [
    'migrations_install', 'site_content', 'site_templates', 'system_eventnames',
    'system_settings', 'permissions', 'permissions_groups', 'role_permissions',
    'user_roles', 'users', 'user_attributes', 'site_plugins', 'site_plugin_events',
    'site_modules', 'site_snippets', 'site_htmlsnippets',
];
$counts = [];
foreach ($counted as $table) {
    $counts[$table] = count_rows($table);
}

if (!is_file($baselineFile)) {
    file_put_contents($baselineFile, json_encode($counts, JSON_PRETTY_PRINT));
    echo '  --   baseline recorded in ' . $baselineFile . PHP_EOL;
} else {
    $baseline = json_decode((string) file_get_contents($baselineFile), true) ?: [];
    $drifted = [];
    foreach ($counts as $table => $rows) {
        if (($baseline[$table] ?? $rows) !== $rows) {
            $drifted[] = $prefix . $table . ': ' . $baseline[$table] . ' -> ' . $rows;
        }
    }
    check('row counts unchanged since the first run', $drifted === [], implode(', ', $drifted));
}

echo PHP_EOL;
if ($failures === []) {
    echo 'All ' . $checks . ' checks passed.' . PHP_EOL;
    exit(0);
}

echo count($failures) . ' of ' . $checks . ' checks failed:' . PHP_EOL
    . '  - ' . implode(PHP_EOL . '  - ', $failures) . PHP_EOL;
exit(1);
