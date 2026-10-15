<?php namespace {
    // Load dependencies without booting the application or connecting to a database.
    require dirname(__DIR__, 2) . '/vendor/autoload.php';
}
namespace Seiger\sTask\Services {
    class TaskProgress {
        public static ?array $snapshot = null;
        public static int $reads = 0;
        public static function readProgress($id): ?array {
            self::$reads++;
            return self::$snapshot;
        }
    }
}
namespace Seiger\sTask\Models {
    class sTaskModel extends \Illuminate\Database\Eloquent\Model {
        protected $casts = ['meta' => 'array', 'progress' => 'integer'];
        public function notifyRetrieved(): void { $this->fireModelEvent('retrieved', false); }
    }
}
namespace {
    use EvolutionCMS\Providers\EventServiceProvider;
    use Illuminate\Container\Container;
    use Illuminate\Events\Dispatcher;
    use Illuminate\Support\Facades\Event;
    use Seiger\sTask\Models\sTaskModel;
    use Seiger\sTask\Services\TaskProgress;

    $container = new Container();
    $events = new Dispatcher($container);
    Event::swap($events);
    sTaskModel::setEventDispatcher($events);
    (new EventServiceProvider($container))->boot();
    function checkLiveBackup(bool $condition, string $message): void {
        if (!$condition) { throw new RuntimeException($message); }
    }
    function liveTask(int $status, int $progress, array $meta = []): sTaskModel {
        $task = new sTaskModel();
        $task->setRawAttributes(['id' => 106, 'status' => $status, 'progress' => $progress,
            'meta' => json_encode($meta)], true);
        return $task;
    }
    TaskProgress::$snapshot = ['progress' => 42, 'eta' => '12s'];
    $regular = liveTask(50, 65);
    $regular->notifyRetrieved();
    checkLiveBackup($regular->progress === 65 && TaskProgress::$reads === 0,
        'Other workers must not change progress semantics or read snapshots.');
    $backup = liveTask(50, 0, ['progress_source' => 'snapshot']);
    $backup->notifyRetrieved();
    checkLiveBackup($backup->progress === 42 && !$backup->isDirty(), 'Live progress must hydrate without writes.');
    checkLiveBackup(TaskProgress::$snapshot['eta'] === '12s', 'Explicit ETA must stay intact.');
    foreach ([10, 30, 80, 100] as $status) {
        $task = liveTask($status, 75, ['progress_source' => 'snapshot']);
        $task->notifyRetrieved();
        checkLiveBackup($task->progress === 75, 'Inactive tasks must retain DB state.');
    }
    TaskProgress::$snapshot = null;
    $task = liveTask(50, 15, ['progress_source' => 'snapshot']);
    $task->notifyRetrieved();
    checkLiveBackup($task->progress === 15, 'Missing snapshots must retain DB progress.');
    echo "PASS: actual event provider, optional task integration, live progress, unchanged other workers, ETA, no dirty attributes, inactive states, missing snapshots.\n";
}
