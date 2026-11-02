<?php

namespace EvolutionCMS\Support;

/**
 * Providers that a frontend request registers only when it needs them.
 *
 * Declared in `app.on_demand_providers` under the key of the provider in `app.providers`:
 * `paths` (URL prefixes served by the provider's routes), `services` (container keys bound by it,
 * such as facade accessors) and `events` (Evolution events its plugins answer). Classes of the
 * provider's namespace load it as well. A declaration of `false` registers the provider on every request.
 *
 * A request to any declared path registers all of them: the manager pages of these packages render
 * through each other (EvoUI and its Livewire), and Livewire cannot boot halfway through a request.
 *
 * @since 3.6.0
 */
final class OnDemandProviders
{
    /** @var array<string, string> provider class => namespace prefix */
    private array $namespaces = [];

    /** @var array<string, string> service => provider class */
    private array $services = [];

    /** @var array<string, list<string>> event => provider classes */
    private array $events = [];

    /**
     * @param array<string, mixed> $providers `app.providers`
     * @param array<string, mixed> $declarations `app.on_demand_providers`
     * @param string $path Request path relative to the site root, without leading or trailing slashes.
     */
    public function __construct(array $providers, array $declarations, string $path)
    {
        foreach ($declarations as $declaration) {
            if (is_array($declaration) && self::matchesPath($path, (array) ($declaration['paths'] ?? []))) {
                return;
            }
        }
        foreach ($declarations as $key => $declaration) {
            $provider = $providers[$key] ?? null;
            if (!is_array($declaration) || !is_string($provider) || $provider === '') {
                continue;
            }
            $namespace = strrpos($provider, '\\');
            $this->namespaces[$provider] = $namespace === false ? '' : substr($provider, 0, $namespace + 1);
            foreach ((array) ($declaration['services'] ?? []) as $service) {
                $this->services[(string) $service] = $provider;
            }
            foreach ((array) ($declaration['events'] ?? []) as $event) {
                $this->events[(string) $event][] = $provider;
            }
        }
    }

    /**
     * @return array<string, true> Providers that are not registered yet.
     */
    public function pending(): array
    {
        return array_fill_keys(array_keys($this->namespaces), true);
    }

    public function isEmpty(): bool
    {
        return $this->namespaces === [];
    }

    /**
     * Whether a pending provider declares this service.
     */
    public function declaresService(string $service): bool
    {
        return isset($this->services[$service]);
    }

    /**
     * The pending provider that binds a service or owns a class, if any.
     */
    public function forService(string $abstract): ?string
    {
        if (isset($this->services[$abstract])) {
            return $this->services[$abstract];
        }
        if (str_contains($abstract, '\\')) {
            $abstract = ltrim($abstract, '\\');
            foreach ($this->namespaces as $provider => $namespace) {
                if ($namespace !== '' && str_starts_with($abstract, $namespace)) {
                    return $provider;
                }
            }
        }
        return null;
    }

    /**
     * @return list<string> Pending providers whose plugins answer the event.
     */
    public function forEvent(string $event): array
    {
        return $this->events[$event] ?? [];
    }

    /**
     * Drop a provider once it is registered.
     */
    public function forget(string $provider): void
    {
        unset($this->namespaces[$provider]);
        $this->services = array_filter($this->services, static fn (string $owner): bool => $owner !== $provider);
        foreach ($this->events as $event => $owners) {
            $owners = array_values(array_diff($owners, [$provider]));
            if ($owners === []) {
                unset($this->events[$event]);
            } else {
                $this->events[$event] = $owners;
            }
        }
    }

    /**
     * A prefix matches the whole path or its leading segments; letter case is ignored.
     *
     * @param list<string> $prefixes
     */
    public static function matchesPath(string $path, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            $prefix = trim((string) $prefix, '/');
            $length = strlen($prefix);
            if ($prefix !== '' && strncasecmp($path, $prefix, $length) === 0
                && (strlen($path) === $length || $path[$length] === '/')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Path of a request URI relative to the site base URL.
     */
    public static function requestPath(string $uri, string $baseUrl): string
    {
        $path = rawurldecode((string) parse_url($uri, PHP_URL_PATH));
        $base = '/' . trim($baseUrl, '/');
        if ($base !== '/' && strncasecmp($path, $base, strlen($base)) === 0
            && (strlen($path) === strlen($base) || $path[strlen($base)] === '/')) {
            $path = substr($path, strlen($base));
        }
        return trim($path, '/');
    }
}
