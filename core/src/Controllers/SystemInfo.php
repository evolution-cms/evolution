<?php namespace EvolutionCMS\Controllers;

use EvolutionCMS\Interfaces\ManagerThemeInterface;
use EvolutionCMS\Interfaces\ManagerTheme;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SystemInfo extends AbstractController implements ManagerTheme\PageControllerInterface
{
    protected $view = 'page.sysinfo';

    /**
     * @var \EvolutionCMS\Interfaces\DatabaseInterface
     */
    protected $database;

    public function __construct(ManagerThemeInterface $managerTheme, array $data = [])
    {
        parent::__construct($managerTheme, $data);
        $this->database = $this->managerTheme->getCore()->getDatabase();
    }

    public function checkLocked(): ?string
    {
        return null;
    }

    public function canView(): bool
    {
        return $this->managerTheme->getCore()->hasPermission('settings');
    }

    public function getParameters(array $params = []): array
    {
        return [
            'serverArr' => $this->parameterServerArr(),
            'opcacheInfo' => $this->opcacheInfo(),
        ];
    }

    public function getReadOnlyParameters(): array
    {
        return [
            'serverArr' => $this->parameterServerArr(false),
            'opcacheInfo' => $this->opcacheInfo(),
        ];
    }

    /**
     * Resolve current database charset/encoding.
     */
    protected function resolveCharset(): string
    {
        $driver = (string)($this->database->getConfig()['driver'] ?? '');

        switch ($driver) {
            case 'pgsql':
                $row = DB::selectOne("SELECT setting FROM pg_settings WHERE name = 'client_encoding'");
                return isset($row->setting) ? (string) $row->setting : 'none';
            case 'mysql':
                $row = DB::selectOne("SHOW VARIABLES LIKE 'character_set_database'");
                return isset($row->Value) ? (string) $row->Value : 'none';
            case 'sqlite':
                // SQLite stores encoding in the database header; SQLite PRAGMA doesn't expose it reliably.
                // Return UTF-8 as the practical invariant for modern SQLite usage.
                return 'UTF-8';
            default:
                return 'none';
        }
    }

    /**
     * Resolve current database collation.
     */
    protected function resolveCollation(): string
    {
        $driver = (string)($this->database->getConfig()['driver'] ?? '');

        switch ($driver) {
            case 'pgsql':
                $row = DB::selectOne("SELECT datcollate FROM pg_database WHERE datname = current_database()");
                return isset($row->datcollate) ? (string)$row->datcollate : 'none';
            case 'mysql':
                $row = DB::selectOne("SHOW VARIABLES LIKE 'collation_database'");
                return isset($row->Value) ? (string) $row->Value : 'none';
            case 'sqlite':
                // SQLite collations are per-column and depend on build/extensions.
                // Common built-in collations: BINARY, NOCASE, RTRIM.
                // Expose a sane default.
                return 'BINARY';
            default:
                return 'none';
        }
    }

    protected function parameterServerArr(bool $allowReset = true): Collection
    {
        $jit = $this->jitInfo();
        $permissionsEnabled = (bool)$this->managerTheme->getCore()->getConfig('use_udperms');

        return new Collection([
            'evo_version' => [
                'is_lexicon' => true,
                'data' => implode(' ', [
                    $this->managerTheme->getCore()->getVersionData('version'),
                    $this->managerTheme->getCore()->getVersionData('new_version')
                ])
            ],
            'release_date' => [
                'is_lexicon' => true,
                'data' => $this->managerTheme->getCore()->getVersionData('release_date')
            ],
            'PHP Version' => [
                'data' => phpversion(),
                'render' => 'manager::' . $this->getView() . '.phpversion'
            ],
            'opcache' => [
                'is_lexicon' => true,
                'data' => $this->opcacheStatus($allowReset),
                'render' => 'manager::' . $this->getView() . '.html'
            ],
            'jit' => [
                'is_lexicon' => true,
                'data' => $this->managerTheme->getLexicon($jit['enabled'] ? 'enabled' : 'disabled') . ' (' . $jit['details'] . ')',
                'status' => $jit['enabled'],
                'details' => '(' . $jit['details'] . ')'
            ],
            'worker_mode' => $this->workerModeRow(),
            'access_permissions' => [
                'is_lexicon' => true,
                'data' => $this->managerTheme->getLexicon(
                    $permissionsEnabled ? 'enabled' : 'disabled'
                ),
                'status' => $permissionsEnabled
            ],
            'servertime' => [
                'is_lexicon' => true,
                'data' => date('H:i:s', time())
            ],
            'localtime' => [
                'is_lexicon' => true,
                'data' => date('H:i:s', time() + $this->managerTheme->getCore()->getConfig('server_offset_time'))
            ],
            'serveroffset' => [
                'is_lexicon' => true,
                'data' => $this->managerTheme->getCore()->getConfig('server_offset_time') / (60 * 60) . ' h'
            ],
            'database_name'      => [
                'is_lexicon' => true,
                'data'       => $this->managerTheme->getCore()->getService('config')->get('database.connections.default.database')
            ],
            'database_server' => [
                'is_lexicon' => true,
                'data' => $this->managerTheme->getCore()->getService('config')->get('database.connections.default.host')
            ],
            'database_version' => [
                'is_lexicon' => true,
                'data' => $this->database->getVersion()
            ],
            'database_charset' => [
                'is_lexicon' => true,
                'data' => $this->resolveCharset()
            ],
            'database_collation' => [
                'is_lexicon' => true,
                'data' => $this->resolveCollation()
            ],
            'table_prefix' => [
                'is_lexicon' => true,
                'data' => $this->managerTheme->getCore()->getService('config')->get('database.connections.default.prefix')
            ],
            'cfg_base_path' => [
                'is_lexicon' => true,
                'data' => EVO_BASE_PATH
            ],
            'cfg_base_url' => [
                'is_lexicon' => true,
                'data' => EVO_BASE_URL
            ],
            'cfg_manager_url' => [
                'is_lexicon' => true,
                'data' => EVO_MANAGER_URL
            ],
            'cfg_manager_path' => [
                'is_lexicon' => true,
                'data' => EVO_MANAGER_PATH
            ],
            'cfg_site_url' => [
                'is_lexicon' => true,
                'data' => EVO_SITE_URL
            ]
        ]);
    }

    protected function opcacheStatus(bool $allowReset = true): string
    {
        $info = $this->opcacheInfo();
        if (!$info['enabled']) {
            return $this->managerTheme->getLexicon('disabled');
        }

        $resetResult = $allowReset ? $this->resetOpcacheIfRequested() : '';
        $label = $this->managerTheme->getLexicon('enabled');
        if ($info['can_reset']) {
            $label = '<a href="index.php?a=53&opcache_reset=1&_token=' . e(csrf_token()) . '" class="text-underline">' . $label . '</a>';
        }

        return $label . ' ' . $info['details'] . $resetResult;
    }

    public function opcacheInfo(): array
    {
        $status = $this->opcacheStatusData();
        $memory = $status['memory_usage'] ?? [];
        $statistics = $status['opcache_statistics'] ?? [];

        $used = (int)($memory['used_memory'] ?? 0);
        $free = (int)($memory['free_memory'] ?? 0);
        $hits = (int)($statistics['hits'] ?? 0);
        $misses = (int)($statistics['misses'] ?? 0);

        $details = sprintf($this->managerTheme->getLexicon('opcache_memory_details'),
            $this->formatBytes($used),
            $this->formatBytes($free),
            number_format($hits, 0, '.', ' '),
            number_format($misses, 0, '.', ' ')
        );

        $enabled = $this->isOpcacheEnabled();
        return [
            'enabled' => $enabled,
            'details' => $enabled ? $details : '',
            'can_reset' => $enabled && function_exists('opcache_reset') && $this->canUseOpcacheApi(),
            'memory_used' => $this->formatBytes($used),
            'memory_total' => $this->formatBytes($used + $free + (int)($memory['wasted_memory'] ?? 0)),
            'has_statistics' => $memory !== [],
            'hit_rate' => $hits + $misses > 0 ? number_format(100 * $hits / ($hits + $misses), 1, '.', '') . '%' : null,
        ];
    }

    protected function jitInfo(): array
    {
        $bufferSize = $this->iniBytes((string)ini_get('opcache.jit_buffer_size'));
        $enabled = $this->isOpcacheEnabled()
            && $this->iniEnabled('opcache.enable_cli')
            && strtolower((string)ini_get('opcache.jit')) === 'tracing'
            && $bufferSize > 0;

        $status = $this->opcacheStatusData();
        if (isset($status['jit']['enabled'])) {
            $enabled = (bool)$status['jit']['enabled'];
        }

        return ['enabled' => $enabled, 'details' => $this->formatBytes($bufferSize)];
    }

    protected function workerModeRow(): array
    {
        $info = $this->workerModeInfo();
        $details = implode(', ', array_map(
            fn (array $hint) => vsprintf($this->managerTheme->getLexicon($hint[0]), array_slice($hint, 1)),
            $info['hints']
        ));

        return [
            'is_lexicon' => true,
            'data' => $this->managerTheme->getLexicon($info['enabled'] ? 'enabled' : 'disabled') . ' (' . $details . ')',
            'status' => $info['enabled'],
            'details' => '(' . $details . ')',
        ];
    }

    /**
     * The manager itself always runs classic, so this reports the configuration the front-end workers
     * start with (index.php and the image entrypoint read the same variables), not a live worker.
     *
     * @return array{enabled: bool, hints: list<array>}
     */
    public function workerModeInfo(): array
    {
        $requested = $this->environment('EVO_WORKER_MODE') === '1';
        if ($this->serverApi() !== 'frankenphp') {
            return ['enabled' => false, 'hints' => [[$requested ? 'sysinfo_worker_ignored' : 'sysinfo_worker_unavailable']]];
        }
        if (!$requested) {
            return ['enabled' => false, 'hints' => [['sysinfo_worker_available']]];
        }
        if (trim((string) $this->environment('EVO_WORKER_ORIGIN')) === '') {
            return ['enabled' => false, 'hints' => [['sysinfo_worker_no_origin']]];
        }
        $maxRequests = (string) $this->environment('EVO_WORKER_MAX_REQUESTS');

        return ['enabled' => true, 'hints' => [
            [$this->environment('EVO_WORKER_KEEP_APP') === '1' ? 'sysinfo_worker_kept_app' : 'sysinfo_worker_new_app'],
            ['sysinfo_worker_recycle', $maxRequests === '' ? '500' : $maxRequests],
        ]];
    }

    protected function serverApi(): string
    {
        return PHP_SAPI;
    }

    protected function environment(string $name): string|false
    {
        return getenv($name);
    }

    protected function resetOpcacheIfRequested(): string
    {
        if (!request()->boolean('opcache_reset')) {
            return '';
        }

        return ' - ' . $this->managerTheme->getLexicon($this->resetOpcache());
    }

    /** Both the legacy token-protected action and Livewire use this operation. */
    public function resetOpcache(): string
    {
        if (!$this->canView()) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(403);
        }
        if (!$this->isOpcacheEnabled() || !function_exists('opcache_reset') || !$this->canUseOpcacheApi()) {
            return 'opcache_reset_unavailable';
        }
        return $this->performOpcacheReset() ? 'opcache_reset_complete' : 'opcache_reset_failed';
    }

    protected function performOpcacheReset(): bool
    {
        return @opcache_reset();
    }

    protected function isOpcacheEnabled(): bool
    {
        $status = $this->opcacheStatusData();
        if (isset($status['opcache_enabled'])) {
            return (bool)$status['opcache_enabled'];
        }

        return $this->iniEnabled('opcache.enable');
    }

    protected function opcacheStatusData(): array
    {
        if (!function_exists('opcache_get_status') || !$this->canUseOpcacheApi()) {
            return [];
        }

        $status = @opcache_get_status(false);

        return is_array($status) ? $status : [];
    }

    protected function canUseOpcacheApi(): bool
    {
        $restrictApi = trim((string)ini_get('opcache.restrict_api'));

        return $restrictApi === '' || mb_stripos(__FILE__, $restrictApi) === 0;
    }

    protected function iniEnabled(string $key): bool
    {
        return in_array(strtolower((string)ini_get($key)), ['1', 'on', 'true', 'yes'], true);
    }

    protected function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $bytes = (int)$value;

        return match ($unit) {
            'g' => $bytes * 1024 * 1024 * 1024,
            'm' => $bytes * 1024 * 1024,
            'k' => $bytes * 1024,
            default => $bytes,
        };
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int)floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return round($value, $power === 0 ? 0 : 2) . ' ' . $units[$power];
    }
}
