<?php namespace EvolutionCMS\Traits;

use Closure;
use Illuminate\Contracts\Foundation\Application;

trait Path
{
    /**
     * The custom storage path defined by the developer.
     *
     * @var string
     */
    protected $storagePath;

    /**
     * The custom database path defined by the developer.
     *
     * @var string
     */
    protected $databasePath;

    /**
     * Get the path to the core directory.
     *
     * @param string $path Optionally, a path to append to the core path
     * @return string
     */
    public function path($path = '')
    {
        return rtrim(EVO_CORE_PATH, DIRECTORY_SEPARATOR) . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * {@inheritdoc}
     */
    public function basePath($path = '')
    {
        return EVO_CORE_PATH . $path;
    }

    /**
     * Get the path to the language files.
     *
     * @return string
     */
    public function langPath($path = '')
    {
        return $this->path('lang');
    }

    /**
     * Get the path to the application configuration files.
     *
     * @param string $path Optionally, a path to append to the config path
     * @return string
     */
    public function configPath($path = '')
    {
        return $this->path('config');
    }


    /**
     * Get the path to the environment file directory.
     *
     * @return string
     */
    public function environmentPath()
    {
        return '';
    }

    /**
     * Get the path to the public / web directory.
     *
     * @return string
     */
    public function publicPath($path = '')
    {
        return EVO_BASE_PATH . $path;
    }

    /**
     * Get the path to the storage directory.
     *
     * @return string
     */
    public function storagePath($path = '')
    {
        return $this->storagePath ?: EVO_STORAGE_PATH;
    }

    /**
     * Set the storage directory.
     *
     * @param string $path
     * @return $this
     */
    public function useStoragePath($path)
    {
        $this->storagePath = $path;
        $this->instance('path.storage', $path);
        return $this;
    }

    /**
     * Get the path to the database directory.
     *
     * @param string $path Optionally, a path to append to the database path
     * @return string
     */
    public function databasePath($path = '')
    {
        return $this->databasePath ?: $this->path('database' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
    }

    /**
     * Set the database directory.
     *
     * @param string $path
     * @return $this
     */
    public function useDatabasePath($path)
    {
        $this->databasePath = $path;

        $this->instance('path.database', $path);

        return $this;
    }

    /**
     * Get the path to the resources directory.
     *
     * @param string $path
     * @return string
     */
    public function resourcePath($path = '')
    {
        return rtrim($this->publicPath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'assets' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Get the path to the bootstrap directory.
     *
     * @param string $path Optionally, a path to append to the bootstrap path
     * @return string
     */
    public function bootstrapPath($path = '')
    {
        return $this->storagePath() . 'bootstrap' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * @return string
     */
    public function getCacheFolder()
    {
        return 'cache/';
    }

    /**
     * @param $key
     * @return string
     */
    public function getHashFile($key)
    {
        return $this->bootstrapPath('docid_' . $key . '.pageCache.php');
    }

    /**
     * @deprecated
     */
    public function getManagerPath()
    {
        return $this->getManagerUrl();
    }

    /**
     * Returns the manager relative URL/path with respect to the site root.
     *
     * @return string The complete URL to the manager folder
     * @global string $base_url
     */
    public function getManagerUrl()
    {
        return EVO_MANAGER_URL;
    }

    /**
     * Returns the cache relative URL/path with respect to the site root.
     *
     * @return string The complete URL to the cache folder
     * @global string $base_url
     */
    public function getCachePath()
    {
        return EVO_STORAGE_PATH . $this->getCacheFolder();
    }

    /**
     * @return string
     * @deprecated
     */
    public function getSiteCachePath()
    {
        return $this->bootstrapPath();
    }

    /**
     * @return string
     */
    public function getSiteCacheFilePath()
    {
        return $this->bootstrapPath('siteCache.idx.php');
    }

    /**
     * @return string
     */
    public function getSitePublishingFilePath()
    {
        return $this->bootstrapPath('sitePublishing.idx.php');
    }

    /**
     * @param array $bootstrappers
     * @return array
     */
    public function bootstrapWith(array $bootstrappers)
    {
        return [];
    }

    /**
     * Determine if the application configuration is cached.
     *
     * @return bool
     */
    public function configurationIsCached()
    {
        return false;
    }

    /**
     * Detect the application's current environment.
     *
     * @param \Closure $callback
     * @return string
     */
    public function detectEnvironment(Closure $callback)
    {
        return '';
    }

    /**
     * Get the environment file the application is using.
     *
     * @return string
     */
    public function environmentFile()
    {
        return '';
    }

    /**
     * Get the fully qualified path to the environment file.
     *
     * @return string
     */
    public function environmentFilePath()
    {
        return '';
    }

    /**
     * Get the path to the configuration cache file.
     *
     * @return string
     */
    public function getCachedConfigPath()
    {
        return '';
    }

    /**
     * Get the path to the routes cache file.
     *
     * @return string
     */
    public function getCachedRoutesPath()
    {
        return '';
    }

    /**
     * Get the current application locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this['config']->get('app.locale');
    }

    /**
     * Get the application namespace.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getNamespace()
    {
        return 'EvolutionCMS';
    }

    /**
     * Get the registered service provider instances if any exist.
     *
     * @param \Illuminate\Support\ServiceProvider|string $provider
     * @return array
     */
    public function getProviders($provider)
    {
        return [];
    }

    /**
     * Determine if the application has been bootstrapped before.
     *
     * @return bool
     */
    public function hasBeenBootstrapped()
    {
        return true;
    }

    /**
     * Set the environment file to be loaded during bootstrapping.
     *
     * @param string $file
     * @return $this
     */
    public function loadEnvironmentFrom($file)
    {
        return $this;
    }

    /**
     * Determine if the application routes are cached.
     *
     * @return bool
     */
    public function routesAreCached()
    {
        return true;
    }

    /**
     * Set the current application locale.
     *
     * @param string $locale
     * @return void
     */
    public function setLocale($locale)
    {
        $this['config']->set('app.locale', $locale);
        $this['translator']->setLocale($locale);
    }

    /**
     * Determine if middleware has been disabled for the application.
     *
     * @return bool
     */
    public function shouldSkipMiddleware()
    {
        return true;
    }

    /**
     * Terminate the application.
     *
     * @return void
     */
    public function terminate()
    {

    }
}
