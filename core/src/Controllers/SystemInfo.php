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
}
