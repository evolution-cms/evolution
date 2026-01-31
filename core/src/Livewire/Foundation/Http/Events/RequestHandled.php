<?php

namespace EvolutionCMS\Livewire\Foundation\Http\Events;

class RequestHandled
{
    public $app;
    public $request;
    public $response;

    public function __construct($app, $request, $response)
    {
        $this->app = $app;
        $this->request = $request;
        $this->response = $response;
    }
}
