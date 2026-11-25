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

        // StartSession saves the store it started; a second save would age the flash data twice.
        if ($request->hasSession()) {
            \EvoSessionProxy::syncStore();
        } else {
            \EvoSessionProxy::syncBack();
        }

        return $response;
    }
}
