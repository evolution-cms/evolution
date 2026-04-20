<?php namespace EvolutionCMS;

use Illuminate\Console\Application as Artisan;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Console\Events;
use Illuminate\Http\Request;
use Symfony\Component\Console\Application as SymfonyApplication;

class Console extends Artisan
{
    /**
     * {@inheritDoc}
     */
    public function __construct(Container $laravel, Dispatcher $events, $version)
    {
        SymfonyApplication::__construct($laravel->getVersionData('branch'), $version);
        $this->laravel = $laravel;
        $this->events = $events;
        $this->setAutoExit(false);
        $this->setCatchExceptions(false);
        $this->SetRequestForConsole();
        $this->events->dispatch(new Events\ArtisanStarting($this));

        $laravel->loadDeferredProviders();

        parent::bootstrap();
    }

    /**
     * @{inheritDoc}
     */
    protected function getDefaultInputDefinition(): \Symfony\Component\Console\Input\InputDefinition
    {
        return SymfonyApplication::getDefaultInputDefinition();
    }

    private function SetRequestForConsole()
    {
        $uri = evo()->getConfig('site_url', '');

        $components = parse_url($uri);

        $server = $_SERVER;

        if (isset($components['path'])) {
            $server = array_merge($server, [
                'SCRIPT_FILENAME' => $components['path'],
                'SCRIPT_NAME' => $components['path'],
            ]);
        }

        evo()->instance('request', Request::create(
            $uri, 'GET', [], [], [], $server
        ));
    }
}
