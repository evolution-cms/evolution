<?php

namespace EvolutionCMS\Extensions;

use Illuminate\Contracts\Routing\BindingRegistrar;
use Illuminate\Contracts\Routing\Registrar as RegistrarContract;
use Illuminate\Routing\Router as IlluminateRouter;

class Router extends IlluminateRouter implements BindingRegistrar, RegistrarContract
{
    /**
     * Is the fallback to parser already assigned?
     * @var boolean
     */
    protected $hasFallbackToParser = false;

    /**
     * Custom method for calling evolution parser,
     * it adds a fallback route to executeParser method
     */
    public function fallbackToParser()
    {
        if (!$this->hasFallbackToParser) {
            $this->addRoute(['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'], '{any}', function () {
                EvolutionCMS()->executeParser();
                evo_exit();
            })->where('any', '.*')->fallback();

            $this->hasFallbackToParser = true;
        }
    }
}
