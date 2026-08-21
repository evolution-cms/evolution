<?php namespace EvolutionCMS\Auth;

/**
 * What travels through the login pipeline.
 *
 * A plain array would have been enough for the credentials alone, but a pipe that
 * authenticates a user by itself (social login, SSO) needs a way to finish the login
 * without going through the pipeline a second time — hence `$manager`.
 */
final class LoginAttempt
{
    /**
     * @param string $stage login|loginById|hashLogin — which entry point is running
     * @param array $data the credentials/payload the entry point was called with
     * @param object $manager the PipelineUserManager handling this attempt
     * @param bool $events whether the caller asked for events to be fired
     * @param bool $cache whether the caller asked for the cache to be cleared
     */
    public function __construct(
        public string $stage,
        public array $data,
        public object $manager,
        public bool $events = true,
        public bool $cache = true
    ) {
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }
}
