<?php namespace EvolutionCMS\Console;

use EvolutionCMS\Events\VendorTagPublished;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Local\LocalFilesystemAdapter as LocalAdapter;
use League\Flysystem\MountManager;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\search;
use function Laravel\Prompts\select;

/**
 * @see: https://github.com/laravel-zero/foundation/blob/12.x/src/Illuminate/Foundation/Console/VendorPublishCommand.php
 */

#[AsCommand(name: 'vendor:publish')]
class VendorPublishCommand extends Command
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The provider to publish.
     *
     * @var string|null
     */
    protected $provider = null;

    /**
     * The tags to publish.
     *
     * @var array
     */
    protected $tags = [];

    /**
     * The tag currently being published.
     *
     * @var string|null
     */
    protected $currentTag = null;

    /**
     * The time the command started.
     *
     * @var \Illuminate\Support\Carbon|null
     */
    protected $publishedAt;

    /**
     * The publish manifest entries collected during the command run.
     *
     * @var array
     */
    protected $publishManifestEntries = [];

    /**
     * Composer package versions keyed by package name.
     *
     * @var array|null
     */
    protected $composerPackageVersions = null;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'vendor:publish
                    {--existing : Publish and overwrite only the files that have already been published}
                    {--force : Overwrite any existing files}
                    {--all : Publish assets for all service providers without prompt}
                    {--provider= : The service provider that has assets you want to publish}
                    {--tag=* : One or many tags that have assets you want to publish}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish any publishable assets from vendor packages';

    /**
     * Indicates if migration dates should be updated while publishing.
     *
     * @var bool
     */
    protected static $updateMigrationDates = true;

    /**
     * Create a new command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->publishedAt = now();
        $this->determineWhatShouldBePublished();

        try {
            foreach ($this->tags ?: [null] as $tag) {
                $this->publishTag($tag);
            }
        } finally {
            $this->writePublishManifest();
        }
    }

    /**
     * Determine the provider or tag(s) to publish.
     *
     * @return void
     */
    protected function determineWhatShouldBePublished()
    {
        if ($this->option('all')) {
            return;
        }

        [$this->provider, $this->tags] = [
            $this->option('provider'), (array)$this->option('tag'),
        ];

        if (!$this->provider && !$this->tags) {
            $this->promptForProviderOrTag();
        }
    }

    /**
     * Prompt for which provider or tag to publish.
     *
     * @return void
     */
    protected function promptForProviderOrTag()
    {
        $choices = $this->publishableChoices();

        $choice = windows_os()
            ? select(
                "Which provider or tag's files would you like to publish?",
                $choices,
                scroll: 15,
            )
            : search(
                label: "Which provider or tag's files would you like to publish?",
                placeholder: 'Search...',
                options: fn ($search) => array_values(array_filter(
                    $choices,
                    fn ($choice) => str_contains(strtolower($choice), strtolower($search))
                )),
                scroll: 15,
            );

        if ($choice == $choices[0] || is_null($choice)) {
            return;
        }

        $this->parseChoice($choice);
    }

    /**
     * The choices available via the prompt.
     *
     * @return array
     */
    protected function publishableChoices()
    {
        return array_merge(
            ['All providers and tags'],
            preg_filter('/^/', '<fg=gray>Provider:</> ', Arr::sort(ServiceProvider::publishableProviders())),
            preg_filter('/^/', '<fg=gray>Tag:</> ', Arr::sort(ServiceProvider::publishableGroups()))
        );
    }

    /**
     * Parse the answer that was given via the prompt.
     *
     * @param  string  $choice
     * @return void
     */
    protected function parseChoice($choice)
    {
        [$type, $value] = explode(': ', strip_tags($choice));

        if ($type === 'Provider') {
            $this->provider = $value;
        } elseif ($type === 'Tag') {
            $this->tags = [$value];
        }
    }

    /**
     * Publishes the assets for a tag.
     *
     * @param  string  $tag
     * @return mixed
     */
    protected function publishTag($tag)
    {
        $this->currentTag = $tag;
        $pathsToPublish = $this->pathsToPublish($tag);

        if ($publishing = count($pathsToPublish) > 0) {
            $this->components->info(sprintf(
                'Publishing %sassets',
                $tag ? "[$tag] " : '',
            ));
        }

        foreach ($pathsToPublish as $from => $to) {
            $this->publishItem($from, $to);
        }

        if ($publishing === false) {
            $this->components->info('No publishable resources for tag ['.$tag.'].');
        } else {
            if (class_exists(VendorTagPublished::class)) {
                $this->laravel['events']->dispatch(new VendorTagPublished($tag, $pathsToPublish));
            }

            $this->newLine();
        }
    }

    /**
     * Get all of the paths to publish.
     *
     * @param  string  $tag
     * @return array
     */
    protected function pathsToPublish($tag)
    {
        return ServiceProvider::pathsToPublish(
            $this->provider, $tag
        );
    }

    /**
     * Publish the given item from and to the given location.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    protected function publishItem($from, $to): void
    {
        // Prefix the publish source with "symlink:" to link it instead of copying.
        // If links are unavailable, vendor:publish falls back to a normal copy.
        if ($this->isSymlinkPublish($from)) {
            $this->publishSymlink($this->symlinkPublishPath($from), $to);
            return;
        }

        if ($this->files->isFile($from)) {
            $this->publishFile($from, $to);
            return;
        } elseif ($this->files->isDirectory($from)) {
            $this->publishDirectory($from, $to);
            return;
        }

        $this->components->error("Can't locate path: <{$from}>");
    }

    /**
     * Determine if the given publish source should be exposed as a symlink.
     *
     * @param  string  $from
     * @return bool
     * @since 3.5.7
     */
    protected function isSymlinkPublish($from)
    {
        return is_string($from) && str_starts_with($from, 'symlink:');
    }

    /**
     * Get the source path from a symlink publish declaration.
     *
     * @param  string  $from
     * @return string
     * @since 3.5.7
     */
    protected function symlinkPublishPath($from)
    {
        return substr($from, strlen('symlink:'));
    }

    /**
     * Publish the given source as a symlink.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     * @since 3.5.7
     */
    protected function publishSymlink($from, $to)
    {
        if (!$this->files->exists($from)) {
            $this->components->error("Can't locate path: <{$from}>");
            return;
        }

        if (is_link($to) && realpath($to) === realpath($from) && !$this->option('force')) {
            $this->components->twoColumnDetail(sprintf(
                'Symlink [%s] already exists',
                str_replace(base_path().'/', '', $to),
            ), '<fg=yellow;options=bold>SKIPPED</>');
            return;
        }

        if ((!$this->option('existing') && (!$this->files->exists($to) || is_link($to) || $this->option('force')))
            || ($this->option('existing') && $this->files->exists($to))) {
            $existedBefore = $this->files->exists($to) || is_link($to);
            $this->createParentDirectory(dirname($to));

            if (is_link($to) || $this->option('force')) {
                $this->files->delete($to);
            } elseif ($this->files->exists($to)) {
                $this->components->twoColumnDetail(sprintf(
                    'File [%s] already exists',
                    str_replace(base_path().'/', '', realpath($to)),
                ), '<fg=yellow;options=bold>SKIPPED</>');
                return;
            }

            if (@symlink($this->relativePath(dirname($to), $from), $to)) {
                $this->status($from, $to, 'symlink');
                $this->recordPublishedItem(
                    $from,
                    $to,
                    'symlink',
                    $existedBefore ? 'overwritten' : 'created',
                    $existedBefore
                );
                return;
            }

            $this->components->warn("Can't create symlink: <{$to}>. Copying file instead.");
            $this->copySymlinkFallback($from, $to);
        } else {
            if ($this->option('existing')) {
                $this->components->twoColumnDetail(sprintf(
                    'Symlink [%s] does not exist',
                    str_replace(base_path().'/', '', $to),
                ), '<fg=yellow;options=bold>SKIPPED</>');
            } else {
                $this->components->twoColumnDetail(sprintf(
                    'Symlink [%s] already exists',
                    str_replace(base_path().'/', '', realpath($to)),
                ), '<fg=yellow;options=bold>SKIPPED</>');
            }
        }
    }

    /**
     * Copy a symlink source when the filesystem does not allow links.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     * @since 3.5.7
     */
    protected function copySymlinkFallback($from, $to)
    {
        if ($this->files->isFile($from)) {
            $existedBefore = $this->files->exists($to);
            $this->files->copy($from, $to);
            $this->status($from, $to, 'file');
            $this->recordPublishedItem(
                $from,
                $to,
                'file',
                $existedBefore ? 'overwritten' : 'created',
                $existedBefore
            );
            return;
        }

        if ($this->files->isDirectory($from)) {
            $this->publishDirectory($from, $to);
            return;
        }

        $this->components->error("Can't locate path: <{$from}>");
    }

    /**
     * Build a relative path from a directory to a target.
     *
     * @param  string  $fromDirectory
     * @param  string  $toPath
     * @return string
     * @since 3.5.7
     */
    protected function relativePath($fromDirectory, $toPath)
    {
        $from = explode(DIRECTORY_SEPARATOR, trim(str_replace('\\', DIRECTORY_SEPARATOR, realpath($fromDirectory)), DIRECTORY_SEPARATOR));
        $to = explode(DIRECTORY_SEPARATOR, trim(str_replace('\\', DIRECTORY_SEPARATOR, realpath($toPath)), DIRECTORY_SEPARATOR));

        while ($from && $to && $from[0] === $to[0]) {
            array_shift($from);
            array_shift($to);
        }

        return str_repeat('..' . DIRECTORY_SEPARATOR, count($from)) . implode(DIRECTORY_SEPARATOR, $to);
    }

    /**
     * Publish the file to the given path.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    protected function publishFile($from, $to)
    {
        if ((!$this->option('existing') && (!$this->files->exists($to) || $this->option('force')))
            || ($this->option('existing') && $this->files->exists($to))) {

            $to = $this->ensureMigrationNameIsUpToDate($from, $to);
            $this->createParentDirectory(dirname($to));
            $existedBefore = $this->files->exists($to);
            $this->files->copy($from, $to);
            $this->status($from, $to, 'file');
            $this->recordPublishedItem(
                $from,
                $to,
                'file',
                $existedBefore ? 'overwritten' : 'created',
                $existedBefore
            );
        } else {
            if ($this->option('existing')) {
                $this->components->twoColumnDetail(sprintf(
                    'File [%s] does not exist',
                    str_replace(base_path().'/', '', $to),
                ), '<fg=yellow;options=bold>SKIPPED</>');
            } else {
                $this->components->twoColumnDetail(sprintf(
                    'File [%s] already exists',
                    str_replace(base_path().'/', '', realpath($to)),
                ), '<fg=yellow;options=bold>SKIPPED</>');
            }
        }
    }

    /**
     * Publish the directory to the given directory.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    protected function publishDirectory($from, $to)
    {
        $visibility = PortableVisibilityConverter::fromArray([], Visibility::PUBLIC);

        $this->moveManagedFiles($from, $to, new MountManager([
            'from' => new Flysystem(new LocalAdapter($from)),
            'to' => new Flysystem(new LocalAdapter($to, $visibility)),
        ]));

        $this->status($from, $to, 'directory');
    }

    /**
     * Move all the files in the given MountManager.
     *
     * @param  string  $from
     * @param  string  $to
     * @param  \League\Flysystem\MountManager  $manager
     * @return void
     */
    protected function moveManagedFiles($from, $to, $manager)
    {
        foreach ($manager->listContents('from://', true)->sortByPath() as $file) {
            $path = Str::after($file['path'], 'from://');

            if (
                $file['type'] === 'file'
                && (
                    (! $this->option('existing') && (! $manager->fileExists('to://'.$path) || $this->option('force')))
                    || ($this->option('existing') && $manager->fileExists('to://'.$path))
                )
            ) {
                $source = rtrim($from, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;
                $path = $this->ensureMigrationNameIsUpToDate($from, $path);
                $destination = rtrim($to, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;
                $existedBefore = $manager->fileExists('to://'.$path);

                $manager->write('to://'.$path, $manager->read($file['path']));
                $this->recordPublishedItem(
                    $source,
                    $destination,
                    'file',
                    $existedBefore ? 'overwritten' : 'created',
                    $existedBefore
                );
            }
        }
    }

    /**
     * Record a publish operation for the storage manifest.
     *
     * @param  string  $from
     * @param  string  $to
     * @param  string  $type
     * @param  string  $action
     * @param  bool  $existedBefore
     * @return void
     */
    protected function recordPublishedItem($from, $to, $type, $action, $existedBefore)
    {
        $package = $this->packageForPath($from);

        $this->publishManifestEntries[] = [
            'published_at' => $this->publishedAt?->toIso8601String(),
            'provider' => $this->provider,
            'tag' => $this->currentTag,
            'tags' => $this->tags,
            'source' => $this->normalizeManifestPath($from),
            'destination' => $this->normalizeManifestPath($to),
            'type' => $type,
            'action' => $action,
            'existed_before' => $existedBefore,
            'force' => (bool) $this->option('force'),
            'existing' => (bool) $this->option('existing'),
            'package' => $package['name'],
            'package_version' => $package['version'],
        ];
    }

    /**
     * Append collected publish records to the storage manifest.
     *
     * @return void
     */
    protected function writePublishManifest()
    {
        if ($this->publishManifestEntries === []) {
            return;
        }

        $manifestPath = $this->publishManifestPath();
        $this->createParentDirectory(dirname($manifestPath));

        $manifest = [
            'version' => 1,
            'published' => [],
        ];

        if ($this->files->exists($manifestPath)) {
            $existingManifest = json_decode((string) $this->files->get($manifestPath), true);

            if (is_array($existingManifest)) {
                $manifest = $existingManifest + $manifest;
                $manifest['published'] = is_array($existingManifest['published'] ?? null)
                    ? $existingManifest['published']
                    : [];
            }
        }

        $manifest['version'] = 1;
        $manifest['published'] = array_merge($manifest['published'], $this->publishManifestEntries);

        $this->files->put(
            $manifestPath,
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
        );

        $this->publishManifestEntries = [];
    }

    /**
     * Get the storage path for the vendor publish manifest.
     *
     * @return string
     */
    protected function publishManifestPath()
    {
        if (function_exists('storage_path')) {
            return storage_path('vendor-publish/manifest.json');
        }

        return rtrim(EVO_STORAGE_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'vendor-publish' . DIRECTORY_SEPARATOR . 'manifest.json';
    }

    /**
     * Normalize paths for portable manifest entries.
     *
     * @param  string  $path
     * @return string
     */
    protected function normalizeManifestPath($path)
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * Resolve Composer package metadata for a vendor source path.
     *
     * @param  string  $path
     * @return array{name: string|null, version: string|null}
     */
    protected function packageForPath($path)
    {
        $normalizedPath = $this->normalizeManifestPath($path);

        if (!preg_match('#/vendor/([^/]+/[^/]+)/#', $normalizedPath, $matches)) {
            return ['name' => null, 'version' => null];
        }

        $package = $matches[1];

        return [
            'name' => $package,
            'version' => $this->composerPackageVersions()[$package] ?? null,
        ];
    }

    /**
     * Get Composer package versions from the core lock file.
     *
     * @return array
     */
    protected function composerPackageVersions()
    {
        if ($this->composerPackageVersions !== null) {
            return $this->composerPackageVersions;
        }

        $this->composerPackageVersions = [];
        $lockPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'composer.lock';

        if (!$this->files->exists($lockPath)) {
            return $this->composerPackageVersions;
        }

        $lock = json_decode((string) $this->files->get($lockPath), true);

        foreach (array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []) as $package) {
            if (isset($package['name'], $package['version'])) {
                $this->composerPackageVersions[$package['name']] = $package['version'];
            }
        }

        return $this->composerPackageVersions;
    }

    /**
     * Create the directory to house the published files if needed.
     *
     * @param  string  $directory
     * @return void
     */
    protected function createParentDirectory($directory)
    {
        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }
    }

    /**
     * Ensure the given migration name is up-to-date.
     *
     * @param  string  $from
     * @param  string  $to
     * @return string
     */
    protected function ensureMigrationNameIsUpToDate($from, $to)
    {
        if (static::$updateMigrationDates === false) {
            return $to;
        }

        $from = realpath($from);

        foreach (ServiceProvider::publishableMigrationPaths() as $path) {
            $path = realpath($path);

            if ($from === $path && preg_match('/\d{4}_(\d{2})_(\d{2})_(\d{6})_/', $to)) {
                $this->publishedAt->addSecond();

                return preg_replace(
                    '/\d{4}_(\d{2})_(\d{2})_(\d{6})_/',
                    $this->publishedAt->format('Y_m_d_His').'_',
                    $to,
                );
            }
        }

        return $to;
    }

    /**
     * Write a status message to the console.
     *
     * @param  string  $from
     * @param  string  $to
     * @param  string  $type
     * @return void
     */
    protected function status($from, $to, $type)
    {
        $from = str_replace(base_path().'/', '', realpath($from));
        $to = str_replace(base_path().'/', '', is_link($to) ? $to : realpath($to));

        $this->components->task(sprintf(
            '%s %s [%s] to [%s]',
            $type === 'symlink' ? 'Linking' : 'Copying',
            $type,
            $from,
            $to,
        ));
    }

    /**
     * Instruct the command to not update the dates on migrations when publishing.
     *
     * @return void
     */
    public static function dontUpdateMigrationDates()
    {
        static::$updateMigrationDates = false;
    }
}
