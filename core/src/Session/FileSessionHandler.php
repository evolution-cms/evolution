<?php

namespace EvolutionCMS\Session;

/**
 * Read native file sessions without starting Carbon for a scalar expiry check.
 *
 * Writes, garbage collection, session IDs and file locking remain upstream.
 *
 * @since 3.7.0
 */
class FileSessionHandler extends \Illuminate\Session\FileSessionHandler
{
    /**
     * Retain the inclusive lifetime boundary and shared-lock read behavior.
     *
     * Configured Carbon clocks and unusual lifetime values use the parent path.
     * Missing/expired files return an empty payload exactly as Laravel does.
     *
     * @param string $sessionId Session identifier supplied by the store.
     * @return string|false Stored session payload or upstream read failure.
     * @since 3.7.0
     */
    public function read($sessionId): string|false
    {
        if (class_exists(\Carbon\FactoryImmutable::class, false)
            || class_exists(\Carbon\Carbon::class, false)
            || class_exists(\Illuminate\Support\Carbon::class, false)
            || !(is_int($this->minutes) || (is_string($this->minutes) && ctype_digit($this->minutes)))) {
            return parent::read($sessionId);
        }

        if ($this->files->isFile($path = $this->path.'/'.$sessionId)
            && $this->files->lastModified($path) >= time() - $this->minutes * 60) {
            return $this->files->sharedGet($path);
        }

        return '';
    }
}
