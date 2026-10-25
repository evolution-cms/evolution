<?php

namespace EvolutionCMS\Cache;

/**
 * Avoid initializing Carbon just to compare integer file-cache expiry timestamps.
 *
 * File payloads, locking, serialization, errors and events remain inherited from
 * Laravel. Once Carbon's clock machinery is loaded, use it without approximation.
 *
 * @since 3.7.0
 */
class FileStore extends \Illuminate\Cache\FileStore
{
    /**
     * Use the system clock only before any Carbon clock can have been configured.
     *
     * @return int Current Unix timestamp, including an upstream frozen clock.
     * @since 3.7.0
     */
    protected function currentTime()
    {
        return $this->usesCarbonClock() ? parent::currentTime() : time();
    }

    /**
     * Add integral seconds without constructing a date; delegate other inputs.
     *
     * Absolute dates, intervals, fractional inputs and configured Carbon clocks
     * keep Laravel's original arithmetic and validation.
     *
     * @param \DateTimeInterface|\DateInterval|int $delay Upstream expiry input.
     * @return int Expiration Unix timestamp.
     * @since 3.7.0
     */
    protected function availableAt($delay = 0)
    {
        if (!is_int($delay) || $this->usesCarbonClock()) {
            return parent::availableAt($delay);
        }

        return time() + $delay;
    }

    /**
     * Detect an initialized clock without autoloading Carbon on the fast path.
     *
     * @return bool Whether upstream clock semantics must be retained.
     * @since 3.7.0
     */
    private function usesCarbonClock(): bool
    {
        return class_exists(\Carbon\FactoryImmutable::class, false)
            || class_exists(\Carbon\Carbon::class, false)
            || class_exists(\Illuminate\Support\Carbon::class, false);
    }
}
