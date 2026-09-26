<?php namespace EvolutionCMS;

use EvolutionCMS\Interfaces\DatabaseInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Log\LogServiceProvider;

/** @phpstan-consistent-constructor */
abstract class AbstractLaravel extends Container implements ApplicationContract
{
    /** @var list<array{key: string, path: string}> */
    private array $dynamicConfigurationFiles = [];

    /**
     * Indicates if the application has "booted".
     *
     * @var bool
     */
    protected $booted = false;

    /**
     * Container aliases registered by the Evolution CMS core.
     *
     * @var array<string, list<class-string>>
     */
    protected $coreAliases = [
        'app' => [
            Interfaces\CoreInterface::class,
            \Illuminate\Contracts\Container\Container::class,
            \Illuminate\Contracts\Foundation\Application::class,
            \Psr\Container\ContainerInterface::class
        ],
        'blade.compiler' => [
            \Illuminate\View\Compilers\BladeCompiler::class
        ],
        'cache' => [
            \Illuminate\Cache\CacheManager::class,
            \Illuminate\Contracts\Cache\Factory::class
        ],
        'cache.store' => [
            \Illuminate\Cache\Repository::class,
            \Illuminate\Contracts\Cache\Repository::class
        ],
        'config' => [
            \Illuminate\Config\Repository::class,
            \Illuminate\Contracts\Config\Repository::class
        ],
        'db' => [
            \Illuminate\Database\DatabaseManager::class
        ],
        'db.connection' => [
            \Illuminate\Database\Connection::class,
            \Illuminate\Database\ConnectionInterface::class
        ],
        'events' => [
            \Illuminate\Events\Dispatcher::class,
            \Illuminate\Contracts\Events\Dispatcher::class
        ],
        'files' => [
            \Illuminate\Filesystem\Filesystem::class
        ],
        'filesystem' => [
            \Illuminate\Filesystem\FilesystemManager::class,
            \Illuminate\Contracts\Filesystem\Factory::class
        ],
        'filesystem.disk' => [
            \Illuminate\Contracts\Filesystem\Filesystem::class
        ],
        'filesystem.cloud' => [
            \Illuminate\Contracts\Filesystem\Cloud::class
        ],
        'translator' => [
            \Illuminate\Translation\Translator::class,
            \Illuminate\Contracts\Translation\Translator::class
        ],
        'log' => [
            \Illuminate\Log\LogManager::class,
            \Psr\Log\LoggerInterface::class
        ],
        'view' => [
            \Illuminate\View\Factory::class,
            \Illuminate\Contracts\View\Factory::class
        ]
    ];

    /**
     * All the registered service providers.
     *
     * @var array
     */
    protected $serviceProviders = [];

    /**
     * The names of the loaded service providers.
     *
     * @var array
     */
    protected $loadedProviders = [];

    /**
     * The deferred services and their providers.
     *
     * @var array
     */
    protected $deferredServices = [];

    /**
     * The array of booting callbacks.
     *
     * @var array
     */
    protected $bootingCallbacks = [];
    /**
     * The array of booted callbacks.
     *
     * @var array
     */
    protected $bootedCallbacks = [];

    /**
     * Deprecated Evolution CMS property names mapped to container abstracts.
     *
     * @var array<string, string|null>
     */
    protected $evolutionProperty = [];

    /**
     * Get the requested Evolution CMS version value.
     *
     * @param string|null $data Version data key.
     * @return mixed
     */
    abstract public function getVersionData($data = null);

    /**
     * Get an Evolution CMS configuration value.
     *
     * @param string $name Configuration key.
     * @param mixed $default Value returned when the key is not configured.
     * @return mixed
     */
    abstract public function getConfig($name = '', $default = null);

    /**
     * Get the path to the application's bootstrap directory.
     *
     * @param string $path Path relative to the bootstrap directory.
     * @return string
     */
    abstract public function bootstrapPath($path = '');

    /**
     * Create a new Evolution CMS application instance.
     *
     * @throws \Exception When the application configuration cannot be loaded.
     */
    public function __construct()
    {
        static::setInstance($this);
        $this->instance('app', $this);
        $this->instance(Container::class, $this);

        $this->register(new EventServiceProvider($this));
        $this->register(new LogServiceProvider($this));

        $this->registerCoreContainerAliases();

        $cachedConfiguration = Bootstrap\EnvCacheLoader::configuration();
        $items = $cachedConfiguration['items'] ?? [];
        $this->instance('config', $config = new Repository($items));

        if ($cachedConfiguration === null) {
            $this->loadConfiguration($config, EVO_CORE_PATH . 'config');
            $this->loadConfiguration($config, EVO_CORE_PATH . 'custom/config');

            $cacheableItems = $config->all();
            unset($cacheableItems['session']);
            unset($cacheableItems['app']);
            unset($cacheableItems['database']['migrations']);
            Bootstrap\EnvCacheLoader::cacheConfiguration($cacheableItems, $this->dynamicConfigurationFiles);
        } else {
            foreach ($cachedConfiguration['dynamic_files'] as $file) {
                $this->loadConfigurationEntry($config, $file['key'], $file['path']);
            }
        }

        if (null === $this['config']->get('app')) {
            throw new \Exception('Unable to load the "app" configuration file.');
        }

        if (defined('IN_INSTALL_MODE') && IN_INSTALL_MODE) {
            $this['env'] = 'install';
        } else {
            $this['env'] = $this['config']->get('app.env',  'production');
        }

        mb_internal_encoding('UTF-8');

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this);


        //$this->register(new LogServiceProvider($this));
        //$this->register(new RoutingServiceProvider($this));

        //$this->register(new \App\Providers\AppServiceProvider($this));
        //$this->register(new \App\Providers\EventServiceProvider($this));


        AliasLoader::getInstance($this['config']->get('app.aliases'), $this->bootstrapPath())->register();

        $this->registerConfiguredProviders();

        $this->boot();
    }

    /**
     * Load PHP configuration files from a directory into the configuration repository.
     *
     * On a cache miss, native iterators collect files recursively while preserving
     * the hidden/VCS exclusions and natural key order. The compiled values are
     * stored with the environment cache. Request/process-dependent groups are
     * still evaluated from their PHP files on every request.
     *
     * Invalid custom configuration files are skipped and reported to the PHP error log.
     *
     * @param Repository $config Configuration repository.
     * @param string $dir Configuration directory.
     * @return void
     * @throws \Throwable When a core configuration file cannot be loaded.
     */
    protected function loadConfiguration($config, $dir)
    {
        $files = [];

        $configPath = realpath($dir);
        if ($configPath !== false) {
            $source = new \RecursiveDirectoryIterator($configPath, \FilesystemIterator::SKIP_DOTS);
            $filtered = new \RecursiveCallbackFilterIterator($source, static function (\SplFileInfo $file): bool {
                $name = $file->getFilename();

                return $name[0] !== '.' && !in_array($name, ['_svn', 'CVS', '_darcs'], true);
            });

            foreach (new \RecursiveIteratorIterator($filtered) as $file) {
                if ($file->isLink() || !$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $directory = $file->getPath();
                if ($directory = trim(str_replace($configPath, '', $directory), DIRECTORY_SEPARATOR)) {
                    $directory = str_replace(DIRECTORY_SEPARATOR, '.', $directory) . '.';
                }
                $files[$directory . $file->getBasename('.php')] = $file->getRealPath();
            }
            ksort($files, SORT_NATURAL);

            foreach ($files as $key => $path) {
                if ($this->loadConfigurationEntry($config, $key, $path)
                    && $this->isDynamicConfigurationKey($key)) {
                    $this->dynamicConfigurationFiles[] = ['key' => $key, 'path' => $path];
                }
            }
        }
    }

    /**
     * Identify configuration groups that depend on the request, PHP process, or install mode.
     *
     * @since 3.5.9
     */
    private function isDynamicConfigurationKey(string $key): bool
    {
        return $key === 'session' || str_starts_with($key, 'session.')
            || $key === 'app' || str_starts_with($key, 'app.')
            || $key === 'database.migrations' || str_starts_with($key, 'database.migrations.');
    }

    /**
     * Evaluate one PHP config file under the same error handling on both cache paths.
     *
     * @since 3.5.9
     */
    private function loadConfigurationEntry(Repository $config, string $key, string $path): bool
    {
        try {
            $value = (static function (string $configPath) {
                return require $configPath;
            })($path);

            $config->set($key, $value);
            return true;
        } catch (\Throwable $exception) {
            if (!$this->isCustomConfigPath($path)) {
                throw $exception;
            }

            error_log(sprintf(
                '[EvolutionCMS] Skipped invalid custom config file "%s": %s',
                $path,
                $exception->getMessage()
            ));
            return false;
        }
    }

    /**
     * Determine whether a path belongs to the custom configuration directory.
     *
     * @param string $path Absolute configuration file path.
     * @return bool
     */
    protected function isCustomConfigPath(string $path): bool
    {
        return str_contains(str_replace('\\', '/', $path), '/custom/config/');
    }

    /**
     * {@inheritdoc}
     */
    public function version()
    {
        return $this->getVersionData('version');
    }

    /**
     * {@inheritdoc}
     */
    public function environment(...$environments)
    {
        if (count($environments) > 0) {
            $patterns = is_array($environments[0]) ? $environments[0] : $environments;

            return Str::is($patterns, $this['env']);
        }

        return $this['env'];
    }

    /**
     * Determine whether the application is running in the production environment.
     *
     * @return bool
     */
    public function isProduction() : bool
    {
        return $this->environment() === 'production';
    }

    /**
     * {@inheritdoc}
     */
    public function runningInConsole()
    {
        return is_cli();
    }

    /**
     * {@inheritdoc}
     */
    public function runningUnitTests()
    {
        return $this->environment() === 'testing';
    }

    /**
     * {@inheritdoc}
     */
    public function isDownForMaintenance()
    {
        return (int)$this->getConfig('site_status', 0) === 0;
    }

    /**
     * Get the registered service provider instances if any exist.
     *
     * @param \Illuminate\Support\ServiceProvider|string $provider
     * @return array
     */
    public function getProviders($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return Arr::where($this->serviceProviders, function ($value) use ($name) {
            return $value instanceof $name;
        });
    }

    /**
     * Get the registered service provider instance if it exists.
     *
     * @param \Illuminate\Support\ServiceProvider|string $provider
     * @return \Illuminate\Support\ServiceProvider|null
     */
    public function getProvider($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        foreach ($this->serviceProviders as $registeredProvider) {
            if ($registeredProvider instanceof $name) {
                return $registeredProvider;
            }
        }

        return null;
    }

    /**
     * Resolve a service provider instance from the class name.
     *
     * @param string $provider
     * @return \Illuminate\Support\ServiceProvider
     */
    public function resolveProvider($provider)
    {
        return new $provider($this);
    }

    /**
     * {@inheritdoc}
     * @TODO: Завантажити спочатку Illuminate, потім EvolutionCMS, а потім все інше
     */
    public function registerConfiguredProviders()
    {
        $providers = Collection::make($this['config']->get('app.providers'))
            ->filter(function ($provider) {
                if (!is_string($provider) || class_exists($provider)) {
                    return true;
                }
                // Stale custom/config/app/providers/*.php left behind by a removed package
                error_log('[EvolutionCMS] Skipped missing service provider "' . $provider . '"');
                return false;
            })
            ->partition(function ($provider) {
                return Str::startsWith($provider, 'Illuminate\\');
            });
        (new ProviderRepository($this, new Filesystem, $this->getCachedServicesPath()))
            ->load($providers->collapse()->toArray());
    }

    /**
     * {@inheritdoc}
     */
    public function register($provider, $options = [], $force = false)
    {
        if (($registered = $this->getProvider($provider)) && !$force) {
            return $registered;
        }
        // If the given "provider" is a string, we will resolve it, passing in the
        // application instance automatically for the developer. This is simply
        // a more convenient way of specifying your service provider classes.
        if (is_string($provider)) {
            $provider = $this->resolveProvider($provider);
        }
        if (method_exists($provider, 'register')) {
            $provider->register();
        }
        // If there are bindings / singletons set as properties on the provider we
        // will spin through them and register them with the application, which
        // serves as a convenience layer while registering a lot of bindings.
        if (property_exists($provider, 'bindings')) {
            foreach ($provider->bindings as $key => $value) {
                $this->bind($key, $value);
            }
        }
        if (property_exists($provider, 'singletons')) {
            foreach ($provider->singletons as $key => $value) {
                $this->singleton($key, $value);
            }
        }
        $this->markAsRegistered($provider);
        // If the application has already booted, we will call this boot method on
        // the provider class so it has an opportunity to do its boot logic and
        // will be ready for any usage by this developer's application logic.
        if ($this->booted) {
            $this->bootProvider($provider);
        }
        return $provider;
    }

    /**
     * Mark the given provider as registered.
     *
     * @param \Illuminate\Support\ServiceProvider $provider
     * @return void
     */
    protected function markAsRegistered($provider)
    {
        $this->serviceProviders[] = $provider;
        $this->loadedProviders[get_class($provider)] = true;
    }

    /**
     * Load and boot all the remaining deferred providers.
     *
     * @return void
     */
    public function loadDeferredProviders()
    {
        // We will simply spin through each of the deferred providers and register each
        // one and boot them if the application has booted. This should make each of
        // the remaining services available to this application for immediate use.
        foreach ($this->deferredServices as $service => $provider) {
            $this->loadDeferredProvider($service);
        }
        $this->deferredServices = [];
    }

    /**
     * Load the provider for a deferred service.
     *
     * @param string $service
     * @return void
     */
    public function loadDeferredProvider($service)
    {
        if (!isset($this->deferredServices[$service])) {
            return;
        }
        $provider = $this->deferredServices[$service];
        // If the service provider has not already been loaded and registered, we can
        // register it with the application and remove the service from this list
        // of deferred services, since it will already be loaded on later.
        if (!isset($this->loadedProviders[$provider])) {
            $this->registerDeferredProvider($provider, $service);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function registerDeferredProvider($provider, $service = null)
    {
        // Once the provider that provides the deferred service has been registered, we
        // will remove it from our local list of the deferred services with related
        // providers so that this container does not try to resolve it out again.
        if ($service) {
            unset($this->deferredServices[$service]);
        }
        $this->register($instance = new $provider($this));
        if (!$this->booted) {
            $this->booting(function () use ($instance) {
                $this->bootProvider($instance);
            });
        }
    }

    /**
     * Get the application's deferred services.
     *
     * @return array
     */
    public function getDeferredServices()
    {
        return $this->deferredServices;
    }

    /**
     * Set the application's deferred services.
     *
     * @param array $services
     * @return void
     */
    public function setDeferredServices(array $services)
    {
        $this->deferredServices = $services;
    }

    /**
     * Add an array of services to the application's deferred services.
     *
     * @param array $services
     * @return void
     */
    public function addDeferredServices(array $services)
    {
        $this->deferredServices = array_merge($this->deferredServices, $services);
    }

    /**
     * Determine if the given service is a deferred service.
     *
     * @param string $service
     * @return bool
     */
    public function isDeferredService($service)
    {
        return isset($this->deferredServices[$service]);
    }

    /**
     * Resolve the given type from the container.
     *
     * (Overriding Container::make)
     * @template TClass of object
     *
     * @param string|class-string<TClass> $abstract
     * @param array $parameters
     * @return ($abstract is class-string<TClass> ? TClass : mixed)
     */
    public function make($abstract, array $parameters = [])
    {
        $abstract = $this->getAlias($abstract);
        if (isset($this->deferredServices[$abstract]) && !isset($this->instances[$abstract])) {
            $this->loadDeferredProvider($abstract);
        }
        return parent::make($abstract, $parameters);
    }

    /**
     * Determine if the given abstract type has been bound.
     *
     * (Overriding Container::bound)
     *
     * @param string $abstract
     * @return bool
     */
    public function bound($abstract)
    {
        return isset($this->deferredServices[$abstract]) || parent::bound($abstract);
    }

    /**
     * Boot the given service provider.
     *
     * @param \Illuminate\Support\ServiceProvider $provider
     * @return void
     */
    protected function bootProvider(ServiceProvider $provider)
    {
        if (method_exists($provider, 'boot')) {
            $this->call([$provider, 'boot']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        if ($this->booted) {
            return;
        }
        // Once the application has booted, we will also fire some "booted" callbacks
        // for any listeners that need to do work after this initial booting gets
        // finished. This is useful when ordering the boot-up processes we run.
        $this->fireAppCallbacks($this->bootingCallbacks);
        array_walk($this->serviceProviders, function ($provider) {
            $this->bootProvider($provider);
        });
        $this->booted = true;
        $this->fireAppCallbacks($this->bootedCallbacks);
    }

    /**
     * {@inheritdoc}
     */
    public function booting($callback)
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function booted($callback)
    {
        $this->bootedCallbacks[] = $callback;
        if ($this->isBooted()) {
            $this->fireAppCallbacks([$callback]);
        }
    }

    /**
     * Determine if the application has booted.
     *
     * @return bool
     */
    public function isBooted()
    {
        return $this->booted;
    }

    /**
     * Call the booting callbacks for the application.
     *
     * @param array $callbacks
     * @return void
     */
    protected function fireAppCallbacks(array $callbacks)
    {
        foreach ($callbacks as $callback) {
            call_user_func($callback, $this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCachedServicesPath()
    {
        return $this->bootstrapPath('/services.php');
    }

    /**
     * {@inheritdoc}
     */
    public function getCachedPackagesPath()
    {
        return $this->bootstrapPath('/packages.php');
    }

    /**
     * Register the core class aliases in the container.
     *
     * @return void
     */
    public function registerCoreContainerAliases(): void
    {
        foreach ($this->coreAliases as $key => $aliases) {
            foreach ($aliases as $alias) {
                $this->alias($key, $alias);
            }
        }
    }

    /**
     * Resolve a service from the container.
     *
     * @param string $name Service abstract or alias.
     * @return mixed
     */
    public function getService($name)
    {
        return $this->get($name);
    }

    /**
     * Determine whether a service is bound in the container.
     *
     * @param string $name Service abstract or alias.
     * @return bool
     */
    public function hasService($name)
    {
        return $this->has($name);
    }

    /**
     * Get the legacy database service.
     *
     * @return Database
     */
    public function getDatabase(): Database
    {
        return $this->getService('DBAPI');
    }

    /**
     * Get the legacy mail service.
     *
     * @return Mail
     */
    public function getMail(): Mail
    {
        return $this->getService('MODxMailer');
    }

    /**
     * Get the legacy PHP compatibility service.
     *
     * @return Legacy\PhpCompat
     */
    public function getPhpCompat(): Legacy\PhpCompat
    {
        return $this->getService('PHPCOMPAT');
    }

    /**
     * Get the legacy password hashing service.
     *
     * @return Legacy\PasswordHash
     */
    public function getPasswordHash(): Legacy\PasswordHash
    {
        return $this->getService('phpass');
    }

    /**
     * Get the legacy table rendering service.
     *
     * @return Support\MakeTable
     */
    public function getMakeTable(): Support\MakeTable
    {
        return $this->getService('makeTable');
    }

    /**
     * Get the legacy deprecated-core proxy.
     *
     * @return mixed
     */
    public function getDeprecatedCore()
    {
        return $this->getService('DEPRECATED');
    }

    /**
     * Get the legacy ManagerAPI service.
     *
     * @return mixed
     */
    public function getManagerApi()
    {
        return $this->getService('ManagerAPI');
    }

    /**
     * Get the legacy output modifiers service.
     *
     * @return mixed
     */
    public function getModifiers()
    {
        return $this->getService('MODIFIERS');
    }

    /**
     * Register a deprecated Evolution CMS property alias for a container abstract.
     *
     * @param string|null $abstract Container abstract resolved through the property.
     * @param string $property Deprecated property name.
     * @return void
     */
    public function setEvolutionProperty(?string $abstract, string $property)
    {
        $this->evolutionProperty[$property] = $abstract;
    }

    /**
     * Determine whether an Evolution CMS property is registered.
     *
     * @param string $property Property name.
     * @return bool
     */
    public function isEvolutionProperty(string $property)
    {
        return $this->hasEvolutionProperty($property);
    }

    /**
     * Determine whether an Evolution CMS property is registered.
     *
     * @param string $property Property name.
     * @return bool
     */
    public function hasEvolutionProperty(string $property)
    {
        return isset($this->evolutionProperty[$property]);
    }

    /**
     * Resolve the service registered for an Evolution CMS property.
     *
     * @param string $property Property name.
     * @return mixed|null
     */
    public function getEvolutionProperty(string $property)
    {
        $abstract = Arr::get($this->evolutionProperty, $property, null);
        return $abstract === null ? null : $this->get($abstract);
    }
}
