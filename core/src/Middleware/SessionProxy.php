<?php

namespace EvolutionCMS\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\Response;

class SessionProxy
{
    public function handle($request, Closure $next)
    {
        \EvoSessionProxy::init();

        $response = $next($request);

        if (!$response instanceof Response) {
            $response = response($response);
        }

        \EvoSessionProxy::syncBack();

        return $response;
    }
}
