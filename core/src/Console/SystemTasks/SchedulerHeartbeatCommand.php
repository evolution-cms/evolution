<?php namespace EvolutionCMS\Console\SystemTasks;

use EvolutionCMS\Services\SystemTasks\SchedulerHealthService;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;

class SchedulerHeartbeatCommand extends Command
{
    protected $signature = 'system:scheduler-heartbeat
                            {--mode=scheduler : Runtime mode label such as schedule-run or schedule-work}';

    protected $description = 'Record a scheduler heartbeat for system task health checks';

    public function handle()
    {
        $service = new SchedulerHealthService();
        $record = $service->recordHeartbeat($this->resolveHostName(), (string) $this->option('mode'));

        $this->line(sprintf(
            '[system:scheduler-heartbeat] heartbeat recorded at %s',
            $record->last_heartbeat_at ? $record->last_heartbeat_at->toDateTimeString() : 'unknown'
        ));

        return self::SUCCESS;
    }

    public function schedule(Schedule $schedule)
    {
        $schedule
            ->command('system:scheduler-heartbeat --mode=scheduler')
            ->everyMinute()
            ->withoutOverlapping()
            ->name('system:scheduler-heartbeat');
    }

    protected function resolveHostName()
    {
        $host = function_exists('gethostname') ? gethostname() : '';

        return is_string($host) && $host !== '' ? $host : php_uname('n');
    }
}
