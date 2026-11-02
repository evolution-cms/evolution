<?php namespace EvolutionCMS;

use Exception;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;

/** Reuse current provider declarations without autoloading deferred classes on every request. */
class ProviderRepository
{
    /** @var string|null Cheap Composer metadata signature shared by validation and compilation. */
    private ?string $autoloadSignature = null;

    /**
     * Detect Composer installs/dumps across registered root and custom vendor trees.
     * Inspect stat metadata only; provider classes and generated file contents stay unloaded.
     * Composer's post-autoload-dump package:discover deletes services.php explicitly, covering
     * equal-size, same-second rewrites that filesystem timestamps alone cannot distinguish.
     *
     * @return string Versioned digest of paths, file identity, size and change timestamps.
     * @since 3.7.0
     */
    protected function autoloadSignature(): string
    {
        if ($this->autoloadSignature !== null) {
            return $this->autoloadSignature;
        }
        $files = [];
        if (class_exists(\Composer\Autoload\ClassLoader::class, false)) {
            foreach (array_keys(\Composer\Autoload\ClassLoader::getRegisteredLoaders()) as $vendor) {
                foreach (['installed.php', 'installed.json', 'autoload_classmap.php', 'autoload_psr4.php', 'autoload_static.php'] as $file) {
                    $path = $vendor.'/composer/'.$file;
                    $stat = @stat($path);
                    $files[$path] = $stat === false ? null : [
                        $stat['dev'], $stat['ino'], $stat['mode'],
                        $stat['size'], $stat['mtime'], $stat['ctime'],
                    ];
                }
            }
        }
        ksort($files);
        return $this->autoloadSignature = 'metadata-v2:'.hash('sha256', serialize($files));
    }

    /**
     * The application implementation.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The path to the manifest file.
     *
     * @var string
     */
    protected $manifestPath;

    /**
     * Create a new service repository instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $manifestPath
     * @return void
     */
    public function __construct(ApplicationContract $app, Filesystem $files, $manifestPath)
    {
        $this->app = $app;
        $this->files = $files;
        $this->manifestPath = $manifestPath;
    }

    /**
     * Register eager providers and deferred mappings from the validated manifest.
     * Existing eager classes load at their registration point, not in a global preflight.
     *
     * @param  array  $providers
     * @param  array<string, true>  $waiting Eager providers the application registers later, on demand.
     * @return void
     */
    public function load(array $providers, array $waiting = [])
    {
        $manifest = $this->loadManifest();
        // First we will load the service manifest, which contains information on all
        // service providers registered with the application and which services it
        // provides. This is used to know which services are "deferred" loaders.
        $recompile = $this->shouldRecompile($manifest, $providers);
        if ($recompile) {
            $manifest = $this->compileManifest($providers);
        }
        // Next, we will register events to load the providers for each of the events
        // that it has requested. This allows the service provider to defer itself
        // while still getting automatically loaded when a certain event occurs.
        foreach ($manifest['when'] as $provider => $events) {
            $this->registerLoadEvents($provider, $events);
        }
        // We will go ahead and register all of the eagerly loaded providers with the
        // application so their services can be registered with the application as
        // a provided service. Then we will set the deferred service list on it.
        foreach ($manifest['eager'] as $provider) {
            if (isset($waiting[$provider])) {
                continue;
            }
            if (!class_exists($provider)) {
                // Rare out-of-band removal: repair the manifest and skip stale declarations.
                $manifest = $this->compileManifest($providers);
                foreach ($manifest['eager'] as $available) {
                    if (!isset($waiting[$available])) $this->app->register($available);
                }
                break;
            }
            $this->app->register($provider);
        }
        $this->app->addDeferredServices($manifest['deferred']);
    }

    /**
     * Load the service provider manifest JSON file.
     *
     * @return array|null
     */
    public function loadManifest()
    {
        // The service manifest is a file containing a JSON representation of every
        // service provided by the application and whether its provider is using
        // deferred loading or should be eagerly loaded on each request to us.
        if ($this->files->exists($this->manifestPath)) {
            $manifest = $this->files->getRequire($this->manifestPath);
            if (is_array($manifest)) {
                return array_merge(['when' => []], $manifest);
            }
        }
    }

    /**
     * Recompile changed declarations or Composer metadata, while allowing missing providers to reappear.
     *
     * @param  array  $manifest
     * @param  array  $providers
     * @return bool
     */
    public function shouldRecompile($manifest, $providers)
    {
        if (!is_array($manifest) || ($manifest['providers'] ?? null) != $providers
            || !is_array($manifest['eager'] ?? null) || !is_array($manifest['deferred'] ?? null)
            || !is_array($manifest['when'] ?? null)
            || ($manifest['autoload_signature'] ?? null) !== $this->autoloadSignature()) return true;
        // Reinstalled custom PSR providers may appear without a Composer dump.
        foreach ($manifest['missing'] ?? [] as $provider) {
            if (class_exists($provider)) return true;
        }
        return false;
    }

    /**
     * Register the load events for the given provider.
     *
     * @param  string  $provider
     * @param  array  $events
     * @return void
     */
    protected function registerLoadEvents($provider, array $events)
    {
        if (count($events) < 1) {
            return;
        }
        $this->app->make('events')->listen($events, function () use ($provider) {
            if (class_exists($provider)) {
                $this->app->register($provider);
            } else {
                error_log('[EvolutionCMS] Skipped missing service provider "' . $provider . '"');
                $this->files->delete($this->manifestPath);
            }
        });
    }

    /**
     * Compile the application service manifest file.
     *
     * @param  array  $providers
     * @return array
     */
    protected function compileManifest($providers)
    {
        // The service manifest should contain a list of all the providers for
        // the application so we can compare it on each request to the service
        // and determine if the manifest should be recompiled or is current.
        $manifest = $this->freshManifest($providers);
        foreach ($providers as $provider) {
            if (class_exists($provider)) {
                $instance = $this->createProvider($provider);
                // When recompiling the service manifest, we will spin through each of the
                // providers and check if it's a deferred provider or not.
                // If so, we'll add it's provided services to the manifest and note the provider.
                if ($instance->isDeferred()) {
                    foreach ($instance->provides() as $service) {
                        $manifest['deferred'][$service] = $provider;
                    }
                    $manifest['when'][$provider] = $instance->when();
                }
                // If the service providers are not deferred, we will simply add it to an
                // array of eagerly loaded providers that will get registered on every
                // request to this application instead of "lazy" loading every time.
                else {
                    $manifest['eager'][] = $provider;
                }
            } else {
                $manifest['missing'][] = $provider;
                error_log('[EvolutionCMS] Skipped missing service provider "' . $provider . '"');
            }
        }
        return $this->writeManifest($manifest);
    }

    /**
     * Create a fresh service manifest data structure.
     *
     * @param  array  $providers
     * @return array
     */
    protected function freshManifest(array $providers)
    {
        return ['providers' => $providers, 'eager' => [], 'deferred' => [], 'when' => [],
            'missing' => [], 'autoload_signature' => $this->autoloadSignature()];
    }

    /**
     * Write the service manifest file to disk.
     *
     * @param  array  $manifest
     * @return array
     *
     * @throws \Exception
     */
    public function writeManifest($manifest)
    {
        if (! is_writable(dirname($this->manifestPath))) {
            throw new Exception('The bootstrap/cache directory must be present and writable.');
        }
        $this->files->put(
            $this->manifestPath, '<?php return '.var_export($manifest, true).';'
        );
        return array_merge(['when' => []], $manifest);
    }

    /**
     * Create a new provider instance.
     *
     * @param  string  $provider
     * @return \Illuminate\Support\ServiceProvider
     */
    public function createProvider($provider)
    {
        return new $provider($this->app);
    }
}
