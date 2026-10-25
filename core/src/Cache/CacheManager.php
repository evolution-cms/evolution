<?php

namespace EvolutionCMS\Cache;

/**
 * Replace only Laravel's file store with the scalar-clock implementation.
 *
 * Repository events, lock configuration and non-file drivers stay upstream.
 *
 * @since 3.7.0
 */
class CacheManager extends \Illuminate\Cache\CacheManager
{
    /**
     * Build the file repository with the same permissions, class policy and locks.
     *
     * @param array<string, mixed> $config File driver configuration.
     * @return \Illuminate\Contracts\Cache\Repository The upstream repository wrapper.
     * @since 3.7.0
     */
    protected function createFileDriver(array $config)
    {
        return $this->repository(
            (new FileStore(
                $this->app['files'],
                $config['path'],
                $config['permission'] ?? null,
                $this->getSerializableClasses($config)
            ))->setLockDirectory($config['lock_path'] ?? null),
            $config
        );
    }
}
