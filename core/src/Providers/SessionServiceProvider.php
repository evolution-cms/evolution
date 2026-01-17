<?php

namespace EvolutionCMS\Providers;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Session\SessionServiceProvider as IlluminateSessionServiceProvider;
use Illuminate\Session\SessionManager;

/**
 * @deprecated
 * @since 3.5.0
 *
 * Class is not used. Session restore is not needed at the core level and may be implemented in the user snippets
 *
 * @todo [remove@3.7] Remove in Evolution CMS 3.7
 * @property \DocumentParser $app
 */
class SessionServiceProvider extends IlluminateSessionServiceProvider
{
    /**
     * Register the session manager instance.
     *
     * @return void
     */
    protected function registerSessionManager()
    {
        parent::registerSessionManager();
        $this->app->alias('session', SessionManager::class);
        //register_shutdown_function([$this, 'handleExitWithSession']);
    }

    /**
     * Handle shutdown from parser.
     *
     * @return void
     */
    public function handleExitWithSession()
    {
        if (!$this->app->has('request')) {
            return;
        }

        $request = request();

        if (!$this->sessionConfigured() ||
            !$request->getSession() ||
            !$this->exitedFromParser($request)) {
            return;
        }

        $this->storeCurrentUrl($request, $request->session());

        $this->saveSession();
    }

    /**
     * Store the current URL for the request if necessary.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Contracts\Session\Session  $session
     * @return void
     */
    protected function storeCurrentUrl(Request $request, $session)
    {
        if ($request->method() === 'GET' &&
            $request->route() instanceof Route &&
            ! $request->ajax() &&
            ! $request->prefetch()) {
            $session->setPreviousUrl($request->fullUrl());
        }
    }

    /**
     * Save the session data to storage.
     *
     * @return void
     */
    protected function saveSession()
    {
        $this->app->session->driver()->save();
    }

    /**
     * Determine if a session driver has been configured.
     *
     * @return bool
     */
    protected function sessionConfigured()
    {
        return !is_null($this->app->session->getSessionConfig()['driver'] ?? null);
    }

    /**
     * Determine if request was fell back to parser.
     *
     * @return bool
     */
    protected function exitedFromParser(Request $request)
    {
        if (!empty($this->app->documentObject)) {
            return true;
        }

        $route = $request->route();
        return empty($route) || $route->isFallback;
    }
}
