<?php namespace EvolutionCMS\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [];
    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [];
    /**
     * Register the application's event listeners.
     *
     * @return void
     */
    public function boot()
    {
        // The string event stays inert when sTask is not installed.
        Event::listen('eloquent.retrieved: Seiger\\sTask\\Models\\sTaskModel', [
            \EvolutionCMS\Services\BackgroundDatabaseBackup::class, 'hydrateProgress',
        ]);
        Event::listen('eloquent.saving: Seiger\\sTask\\Models\\sWorker', [
            \EvolutionCMS\Services\BackgroundDatabaseBackup::class, 'authorizeSchedule',
        ]);
        foreach ($this->listens() as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
        foreach ($this->subscribe as $subscriber) {
            Event::subscribe($subscriber);
        }
    }
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        //
    }
    /**
     * Get the events and handlers.
     *
     * @return array
     */
    public function listens()
    {
        return $this->listen;
    }
}
