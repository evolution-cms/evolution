<?php namespace EvolutionCMS\Support;

/**
 * The engines the manager will scaffold a template file for.
 *
 * A template whose alias resolves to a file under a view path is rendered by
 * that file's engine, and the parser is skipped for it - see
 * TemplateProcessor::getBladeDocumentContent(). Laravel's view factory resolves
 * whatever extension is registered with it, so this class decides nothing about
 * rendering; it only answers what the template form may offer, and keeps that
 * answer honest by dropping anything the factory could not actually render.
 *
 * @since 3.5.9
 */
class TemplateFileEngines
{
    /** @var array<string, array{label: string, processor: string|null}> */
    private array $engines;

    /** @var string[] */
    private array $viewPaths;

    /**
     * @param array<string, mixed> $declared         extension => ['label' => ..., 'processor' => ...]
     * @param string[]|null        $renderable       extensions the view factory knows, in resolution
     *                                               order; null means "cannot ask", and every
     *                                               declaration is taken at face value
     * @param string[]             $viewPaths
     */
    public function __construct(array $declared, ?array $renderable, array $viewPaths)
    {
        $normalised = [];
        foreach ($declared as $extension => $engine) {
            $extension = ltrim((string) $extension, '.');
            if ($extension === '') {
                continue;
            }

            $normalised[$extension] = [
                'label' => (string) (is_array($engine) ? ($engine['label'] ?? $extension) : $extension),
                'processor' => is_array($engine) && isset($engine['processor']) && $engine['processor'] !== ''
                    ? (string) $engine['processor']
                    : null,
            ];
        }

        if ($renderable === null) {
            $this->engines = $normalised;
        } else {
            // An engine declared but never registered would scaffold a file
            // nothing renders, so it is not offered. Ordering follows the
            // factory, which is what decides who wins when two files share an
            // alias.
            $ordered = [];
            foreach ($renderable as $extension) {
                $extension = ltrim((string) $extension, '.');
                if (isset($normalised[$extension])) {
                    $ordered[$extension] = $normalised[$extension];
                }
            }
            $this->engines = $ordered;
        }

        $this->viewPaths = $viewPaths === [] ? [EVO_BASE_PATH . 'views/'] : array_values($viewPaths);
    }

    /**
     * Build from the running application.
     */
    public static function make(): self
    {
        $declared = [];
        $viewPaths = [];
        if (function_exists('config')) {
            try {
                $declared = (array) config('view.template_engines', []);
                $viewPaths = (array) config('view.paths', []);
            } catch (\Throwable) {
                $declared = [];
                $viewPaths = [];
            }
        }

        return new self($declared, static::renderableExtensions(), $viewPaths);
    }

    /**
     * @return array<string, array{label: string, processor: string|null}>
     */
    public function all(): array
    {
        return $this->engines;
    }

    /**
     * The extension the form preselects: the one belonging to the active chunk
     * processor, or else the first offered.
     */
    public function defaultExtension(?string $chunkProcessor = null): ?string
    {
        if ($this->engines === []) {
            return null;
        }

        $chunkProcessor = (string) $chunkProcessor;
        if ($chunkProcessor !== '') {
            foreach ($this->engines as $extension => $engine) {
                if ($engine['processor'] !== null
                    && mb_strtolower($engine['processor']) === mb_strtolower($chunkProcessor)) {
                    return $extension;
                }
            }
        }

        // Falling through to whatever happens to be first would preselect an
        // engine that belongs to a different chunk processor - a Latte file for
        // a DLTemplate site - so a general purpose engine is preferred.
        foreach ($this->engines as $extension => $engine) {
            if ($engine['processor'] === null) {
                return $extension;
            }
        }

        return (string) array_key_first($this->engines);
    }

    /**
     * Whether an extension may be written. Everything that reaches the file
     * system goes through here: the form posts an extension, and an extension
     * is half of a filename in the web root.
     */
    public function isRegistered(?string $extension): bool
    {
        return $extension !== null && isset($this->engines[ltrim($extension, '.')]);
    }

    /**
     * The file an alias would be scaffolded to, or null when the alias cannot
     * safely be one - the same rule the form applies while typing.
     */
    public function filename(string $alias, string $extension): ?string
    {
        if (!$this->isRegistered($extension)) {
            return null;
        }

        $name = preg_replace('/\s*/', '', $alias);
        $name = preg_replace('/[^a-zA-Z0-9_-]+/', '', (string) $name);

        if ($name === '' || $name !== $alias) {
            return null;
        }

        return $name . '.' . ltrim($extension, '.');
    }

    /**
     * Files that already exist for an alias, in resolution order - the first is
     * the one that renders, the rest are shadowed.
     *
     * @return array<string, string> extension => absolute path
     */
    public function existing(string $alias): array
    {
        $found = [];
        foreach (array_keys($this->engines) as $extension) {
            $filename = $this->filename($alias, $extension);
            if ($filename === null) {
                continue;
            }

            foreach ($this->viewPaths as $path) {
                $candidate = rtrim((string) $path, "/\\") . '/' . $filename;
                if (is_file($candidate)) {
                    $found[$extension] = $candidate;
                    break;
                }
            }
        }

        return $found;
    }

    /**
     * The path of one engine's file for an alias, if it is there.
     */
    public function pathFor(string $alias, ?string $extension): ?string
    {
        if ($extension === null || $extension === '') {
            return null;
        }

        $filename = $this->filename($alias, ltrim($extension, '.'));
        if ($filename === null) {
            return null;
        }

        foreach ($this->viewPaths as $path) {
            $candidate = rtrim((string) $path, "/\\") . '/' . $filename;
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * The extension that actually renders an alias: the one the template pinned
     * when it was saved, or - with nothing pinned, or the pinned file gone -
     * whichever the view factory would reach first. '' when no file exists.
     */
    public function winner(string $alias, ?string $pinned = null): string
    {
        $pinned = $pinned === null ? '' : ltrim($pinned, '.');
        if ($pinned !== '' && $this->pathFor($alias, $pinned) !== null) {
            return $pinned;
        }

        $existing = $this->existing($alias);

        return $existing === [] ? '' : (string) array_key_first($existing);
    }

    /**
     * The directories views are looked for in.
     *
     * @return string[]
     */
    public function viewPaths(): array
    {
        return $this->viewPaths;
    }

    /**
     * The directory a scaffolded file is written to.
     */
    public function scaffoldPath(): string
    {
        return rtrim((string) ($this->viewPaths[0] ?? (EVO_BASE_PATH . 'views/')), "/\\");
    }

    /**
     * Extensions the view factory knows, most significant first, or null when
     * there is no factory to ask (console tooling, tests).
     *
     * @return string[]|null
     */
    protected static function renderableExtensions(): ?array
    {
        if (!function_exists('app')) {
            return null;
        }

        try {
            $factory = app('view');
        } catch (\Throwable) {
            return null;
        }

        if (!is_object($factory) || !method_exists($factory, 'getExtensions')) {
            return null;
        }

        return array_keys($factory->getExtensions());
    }
}
