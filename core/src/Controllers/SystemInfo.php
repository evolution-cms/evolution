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
        return $this->managerTheme->getCore()->hasPermission('logs');
    }

    public function getParameters(array $params = []): array
    {
        return [
            'serverArr' => $this->parameterServerArr(),
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

    protected function parameterServerArr(): Collection
    {
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
                'data' => $this->opcacheStatus(),
                'render' => 'manager::' . $this->getView() . '.html'
            ],
            'jit' => [
                'is_lexicon' => true,
                'data' => $this->jitStatus()
            ],
            'access_permissions' => [
                'is_lexicon' => true,
                'data' => $this->managerTheme->getLexicon(
                    (bool)$this->managerTheme->getCore()->getConfig('use_udperms') ? 'enabled' : 'disabled'
                )
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

    protected function opcacheStatus(): string
    {
        if (!$this->isOpcacheEnabled()) {
            return $this->managerTheme->getLexicon('disabled');
        }

        $resetResult = $this->resetOpcacheIfRequested();
        $status = $this->opcacheStatusData();
        $memory = $status['memory_usage'] ?? [];
        $statistics = $status['opcache_statistics'] ?? [];

        $used = (int)($memory['used_memory'] ?? 0);
        $free = (int)($memory['free_memory'] ?? 0);
        $hits = (int)($statistics['hits'] ?? 0);
        $misses = (int)($statistics['misses'] ?? 0);

        $label = $this->managerTheme->getLexicon('enabled');
        if (function_exists('opcache_reset') && $this->canUseOpcacheApi()) {
            $label = '<a href="index.php?a=53&opcache_reset=1" class="text-underline">' . $label . '</a>';
        }
        $details = sprintf($this->managerTheme->getLexicon('opcache_memory_details'),
            $this->formatBytes($used),
            $this->formatBytes($free),
            number_format($hits, 0, '.', ' '),
            number_format($misses, 0, '.', ' ')
        );

        return $label . $details . $resetResult;
    }

    protected function jitStatus(): string
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

        return $this->managerTheme->getLexicon($enabled ? 'enabled' : 'disabled') . ' (' . $this->formatBytes($bufferSize) . ')';
    }

    protected function resetOpcacheIfRequested(): string
    {
        if (!request()->boolean('opcache_reset')) {
            return '';
        }

        if (!function_exists('opcache_reset') || !$this->canUseOpcacheApi()) {
            return ' - ' . $this->managerTheme->getLexicon('opcache_reset_unavailable');
        }

        return ' - ' . $this->managerTheme->getLexicon(@opcache_reset() ? 'opcache_reset_complete' : 'opcache_reset_failed');
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
