<?php

/*
 * Drives the optional sTask worker EvolutionCMS\Workers\DatabaseBackup and the schedule
 * authorisation of EvolutionCMS\Services\BackgroundDatabaseBackup against stub sTask classes,
 * a stub evo() and an in-memory SQLite user table, and prints the outcome as JSON. The worker class
 * only exists when sTask's BaseWorker does, so the stubs must be declared before the autoloader
 * reaches it - which only a fresh process allows.
 *
 * Users: 1 holds bk_manager through role 1, 2 does not (role 2), 3 is blocked with role 1.
 *
 * Usage: php database-backup-worker-harness.php create <session user> <0|1 session has bk_manager>
 *        php database-backup-worker-harness.php schedule <backup_authorized_by stored on the worker>
 *        php database-backup-worker-harness.php run <started_by user id> [<0|1 scheduled task> <backup_authorized_by stored now>]
 *        php database-backup-worker-harness.php authorize <session user> <0|1 bk_manager> <0|1 schedule on>
 *                                                         <stored authorized_by> <posted authorized_by> [identifier]
 */

namespace Seiger\sTask\Models {
    class sTaskModel
    {
        public int $id = 7;
        public $started_by = 0;
        public $meta = [];
        public function update(array $attributes)
        {
            if (array_key_exists('progress', $attributes)) {
                // Reached only once the permission guard of taskMake() let the task through.
                throw new \Seiger\sTask\GuardPassed();
            }
            foreach ($attributes as $key => $value) {
                $this->{$key} = $value;
            }
            return true;
        }
    }
}

namespace Seiger\sTask\Models {
    // Read-through of the stored worker settings, as DatabaseBackup::currentSettings() asks for them.
    class sWorker
    {
        public static function query() { return new self(); }
        public function where($column, $value) { return $this; }
        public function value($column) { return json_encode(\Seiger\sTask\Workers\BaseWorker::$settings); }
    }
}

namespace Seiger\sTask {
    class GuardPassed extends \RuntimeException {}
}

namespace Seiger\sTask\Workers {
    abstract class BaseWorker
    {
        public static array $settings = [];
        public function settings(): array { return self::$settings; }
        public function getConfig(string $key, $default = null) { return self::$settings[$key] ?? $default; }
        public function createTask(string $action, ?array $options = null): \Seiger\sTask\Models\sTaskModel
        {
            return new \Seiger\sTask\Models\sTaskModel();
        }
    }
}

namespace {
    ini_set('display_errors', 'stderr');

    use Illuminate\Cache\ArrayStore;
    use Illuminate\Cache\Repository;
    use Illuminate\Database\Capsule\Manager as Capsule;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Cache;

    final class DatabaseBackupHarnessEvo
    {
        public array $events = [];
        public function __construct(public int $userId, public bool $mayBackup) {}
        public function getLoginUserID($context = '') { return $this->userId > 0 ? $this->userId : false; }
        public function hasPermission($permission, $context = '') { return (int) ($permission === 'bk_manager' && $this->mayBackup); }
        public function logEvent($id, $type, $message, $source = '') { $this->events[] = $message; }
    }

    $mode = $argv[1] ?? '';
    $withSession = in_array($mode, ['create', 'authorize'], true);
    $GLOBALS['harnessEvo'] = new DatabaseBackupHarnessEvo(
        $withSession ? (int) ($argv[2] ?? 0) : 0,
        $withSession && ($argv[3] ?? '0') === '1'
    );
    function evo() { return $GLOBALS['harnessEvo']; }
    function __($key = null, $replace = [], $locale = null) { return $key; }

    require dirname(__DIR__, 2) . '/vendor/autoload.php';

    Cache::swap(new Repository(new ArrayStore()));

    $capsule = new Capsule();
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    Model::setConnectionResolver($capsule->getDatabaseManager());
    $schema = $capsule->getConnection()->getSchemaBuilder();
    $schema->create('users', function (Blueprint $table) { $table->increments('id'); $table->string('username'); });
    $schema->create('user_attributes', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('internalKey');
        $table->integer('role')->default(0);
        $table->integer('blocked')->default(0);
        $table->integer('blockeduntil')->default(0);
        $table->integer('blockedafter')->default(0);
    });
    $schema->create('role_permissions', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('role_id');
        $table->string('permission');
    });
    $db = $capsule->getConnection();
    $db->table('users')->insert([['id' => 1, 'username' => 'admin'], ['id' => 2, 'username' => 'editor'], ['id' => 3, 'username' => 'blocked']]);
    $db->table('user_attributes')->insert([
        ['internalKey' => 1, 'role' => 1, 'blocked' => 0],
        ['internalKey' => 2, 'role' => 2, 'blocked' => 0],
        ['internalKey' => 3, 'role' => 1, 'blocked' => 1],
    ]);
    $db->table('role_permissions')->insert([['role_id' => 1, 'permission' => 'bk_manager'], ['role_id' => 2, 'permission' => 'stask']]);

    $result = ['passed' => false, 'error' => null];
    try {
        if ($mode === 'authorize') {
            // Stands in for sTask's sWorker: the listener only needs an Eloquent model with a cast.
            $worker = new class extends Model {
                protected $table = 's_workers';
                protected $guarded = [];
                protected $casts = ['settings' => 'array'];
            };
            $worker->setRawAttributes(['identifier' => $argv[7] ?? 'DatabaseBackup',
                'settings' => json_encode(['schedule' => ['enabled' => false], 'backup_authorized_by' => (int) $argv[5]])], true);
            $worker->exists = true;
            $worker->setAttribute('settings', ['schedule' => ['enabled' => $argv[4] === '1'], 'backup_authorized_by' => (int) $argv[6]]);
            \EvolutionCMS\Services\BackgroundDatabaseBackup::authorizeSchedule($worker);
            $result['passed'] = true;
            $result['authorized_by'] = $worker->getAttribute('settings')['backup_authorized_by'] ?? null;
        } elseif ($mode === 'create') {
            (new \EvolutionCMS\Workers\DatabaseBackup())->createTask('make', []);
            $result['passed'] = true;
        } elseif ($mode === 'schedule') {
            \Seiger\sTask\Workers\BaseWorker::$settings = ['schedule' => ['enabled' => true], 'backup_authorized_by' => (int) $argv[2]];
            // The scheduler asks on every tick; a refusal must be logged once, not every time.
            for ($tick = 0; $tick < 3; $tick++) {
                try {
                    $task = (new \EvolutionCMS\Workers\DatabaseBackup())->createTask('make', ['manual' => false]);
                    $result['passed'] = true;
                    $result['started_by'] = $task->started_by;
                    $result['scheduled_flag'] = $task->meta['backup_scheduled'] ?? null;
                } catch (\RuntimeException $e) {
                    $result['error'] = $e->getMessage();
                }
            }
        } else {
            $task = new \Seiger\sTask\Models\sTaskModel();
            $task->started_by = (int) $argv[2];
            \Seiger\sTask\Workers\BaseWorker::$settings = ['backup_authorized_by' => (int) ($argv[4] ?? 0)];
            (new \EvolutionCMS\Workers\DatabaseBackup())->taskMake($task, ($argv[3] ?? '0') === '1' ? ['backup_scheduled' => true] : []);
        }
    } catch (\Seiger\sTask\GuardPassed $e) {
        $result['passed'] = true;
    } catch (\Throwable $e) {
        $result['error'] = $e->getMessage();
    }
    $result['events'] = evo()->events;
    echo json_encode($result);
}
