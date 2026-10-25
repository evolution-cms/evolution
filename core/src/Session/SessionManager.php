<?php

namespace EvolutionCMS\Session;

/**
 * Retain Laravel session construction and replace only the native file handler.
 *
 * @since 3.7.0
 */
class SessionManager extends \Illuminate\Session\SessionManager
{
    /**
     * Build the native session store without changing serialization or encryption.
     *
     * @return \Illuminate\Session\Store The configured session store.
     * @since 3.7.0
     */
    protected function createNativeDriver()
    {
        return $this->buildSession(new FileSessionHandler(
            $this->container->make('files'),
            $this->config->get('session.files'),
            $this->config->get('session.lifetime')
        ));
    }
}
