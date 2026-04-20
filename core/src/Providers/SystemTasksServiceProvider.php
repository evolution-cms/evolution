<?php namespace EvolutionCMS\Providers;

use EvolutionCMS\Console\SystemTasks\SchedulerHeartbeatCommand;
use EvolutionCMS\Console\SystemTasks\TaskWorkerCommand;
use EvolutionCMS\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class SystemTasksServiceProvider extends ServiceProvider
{
    protected $scheduled = false;

    public function register()
    {
        if (!$this->app->bound(Schedule::class)) {
            $this->app->singleton(Schedule::class, function () {
                return new Schedule(now()->timezoneName);
            });
        }
    }

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->app->afterResolving(Schedule::class, function (Schedule $schedule) {
            if ($this->scheduled) {
                return;
            }

            $this->scheduled = true;
            $this->schedule($schedule->useCache('file'));
        });
    }

    public function schedule(Schedule $schedule)
    {
        $commands = [
            SchedulerHeartbeatCommand::class,
            TaskWorkerCommand::class,
        ];

        foreach ($commands as $command) {
            $instance = $this->app->make($command);
            if (method_exists($instance, 'schedule')) {
                $instance->schedule($schedule);
            }
        }
    }
}
