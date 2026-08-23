<?php namespace EvolutionCMS\Services\SystemTasks;

use EvolutionCMS\Interfaces\SystemTaskHandlerInterface;
use InvalidArgumentException;

/**
 * What `system_cli_tasks` may hold, and who runs each kind.
 *
 * Before this existed the worker dispatched on a hardcoded switch over three
 * types and answered everything else with TASK_TYPE_NOT_ALLOWED, so a package
 * could not put work on the queue at all — it had to ship its own table, its
 * own lease protocol and its own cron entry beside the CMS's. That is a lot of
 * duplicated machinery for "run this later", and none of it appears on the
 * worker health page.
 *
 * Registration is in-process and belongs in a service provider's `register()`:
 *
 *     SystemTaskRegistry::register('aimage_batch', ImageBatchHandler::class, [
 *         'mode' => SystemTaskRegistry::MODE_CONCURRENT,
 *         'parallelism' => 3,
 *         'permissions' => ['aimage'],
 *         'creator' => [ImageBatchHandler::class, 'queue'],
 *     ]);
 *
 * Two defaults are deliberately strict. An unregistered type is refused rather
 * than attempted, so a package uninstalled while its work was queued fails
 * those tasks with a clear code instead of leaving them to be picked forever.
 * And a registration that does not say otherwise is *exclusive*, because the
 * three built-in types are, and a caller that has not thought about
 * concurrency should inherit the conservative answer.
 */
final class SystemTaskRegistry
{
    /**
     * Only one exclusive task runs at a time, across every type.
     *
     * This is what console installs and site updates need: they rewrite files
     * under the document root and run Composer, so a second one alongside is a
     * corrupted install.
     */
    public const MODE_EXCLUSIVE = 'exclusive';

    /**
     * Ordinary background work. Bounded by the type's own `parallelism`, and
     * not blocked by — nor blocking — tasks of other types.
     */
    public const MODE_CONCURRENT = 'concurrent';

    /** The `type` column is varchar(64); a longer type would be silently truncated. */
    public const MAX_TYPE_LENGTH = 64;

    /**
     * The types this class ships, which a package may never redefine.
     *
     * Allowing a re-registration here would let any package that boots first
     * substitute its own handler for `site_update` — which runs Composer and
     * rewrites the core — and inherit the super-admin gate that makes the real
     * one safe.
     */
    private const BUILT_IN = ['console_install', 'console_uninstall', 'site_update'];

    /** @var array<string,array> */
    private static array $definitions = [];

    private static bool $defaultsRegistered = false;

    /**
     * Declare a task type.
     *
     * @param string $type stored verbatim in `system_cli_tasks.type`
     * @param class-string<SystemTaskHandlerInterface>|SystemTaskHandlerInterface|callable $handler
     *        resolved lazily — a class string is not instantiated until a
     *        worker actually picks a task of this type
     * @param array{
     *     mode?: string,
     *     parallelism?: int,
     *     permissions?: string[],
     *     super_admin?: bool,
     *     creator?: callable,
     *     label?: string
     * } $options
     *
     * @throws InvalidArgumentException on a malformed type, an unknown mode, or
     *         an attempt to redefine a built-in type
     */
    public static function register(string $type, $handler, array $options = []): void
    {
        $type = trim($type);

        if ($type === '' || strlen($type) > self::MAX_TYPE_LENGTH) {
            throw new InvalidArgumentException(
                'A system task type must be between 1 and ' . self::MAX_TYPE_LENGTH . ' characters.'
            );
        }

        if (!preg_match('/^[a-z0-9][a-z0-9_.\-]*$/', $type)) {
            throw new InvalidArgumentException(
                'A system task type may only contain lowercase letters, digits, underscore, dot and hyphen: ' . $type
            );
        }

        self::registerDefaults();

        if (in_array($type, self::BUILT_IN, true) && isset(self::$definitions[$type])) {
            throw new InvalidArgumentException('The built-in system task type "' . $type . '" cannot be redefined.');
        }

        $mode = (string) ($options['mode'] ?? self::MODE_EXCLUSIVE);

        if (!in_array($mode, [self::MODE_EXCLUSIVE, self::MODE_CONCURRENT], true)) {
            throw new InvalidArgumentException('Unknown system task mode "' . $mode . '" for type "' . $type . '".');
        }

        self::$definitions[$type] = [
            'type' => $type,
            'handler' => $handler,
            'mode' => $mode,
            // Parallelism is meaningless for an exclusive type, which is
            // capped at one by the mode itself. Pinning it to 1 keeps callers
            // from reading a number that does not apply.
            'parallelism' => $mode === self::MODE_CONCURRENT
                ? max(1, (int) ($options['parallelism'] ?? 1))
                : 1,
            'permissions' => array_values(array_filter(array_map(
                static fn ($permission) => trim((string) $permission),
                (array) ($options['permissions'] ?? [])
            ))),
            'super_admin' => (bool) ($options['super_admin'] ?? false),
            'creator' => isset($options['creator']) && is_callable($options['creator'])
                ? $options['creator']
                : null,
            'label' => trim((string) ($options['label'] ?? $type)),
        ];
    }

    /**
     * Drop a registration.
     *
     * Exists for tests and for a package that unregisters itself on teardown.
     * Built-in types cannot be removed — a queue that has forgotten how to run
     * a site update is worse than one that refuses to forget.
     */
    public static function forget(string $type): void
    {
        self::registerDefaults();

        if (in_array($type, self::BUILT_IN, true)) {
            return;
        }

        unset(self::$definitions[$type]);
    }

    public static function has(string $type): bool
    {
        self::registerDefaults();

        return isset(self::$definitions[trim($type)]);
    }

    /** @return array|null the whole definition, or null when the type is unknown */
    public static function definition(string $type): ?array
    {
        self::registerDefaults();

        return self::$definitions[trim($type)] ?? null;
    }

    /** @return string[] every registered type */
    public static function types(): array
    {
        self::registerDefaults();

        return array_keys(self::$definitions);
    }

    /** @return string[] the types whose tasks must run alone */
    public static function exclusiveTypes(): array
    {
        self::registerDefaults();

        return array_keys(array_filter(
            self::$definitions,
            static fn (array $definition) => $definition['mode'] === self::MODE_EXCLUSIVE
        ));
    }

    /**
     * The handler for a type, instantiated now.
     *
     * @throws InvalidArgumentException when the type is unknown or its handler
     *         does not satisfy the interface — both are programming errors in
     *         the registrant, not runtime conditions the worker should retry
     */
    public static function handler(string $type): SystemTaskHandlerInterface
    {
        $definition = self::definition($type);

        if ($definition === null) {
            throw new InvalidArgumentException('No handler is registered for system task type "' . $type . '".');
        }

        $handler = $definition['handler'];

        if (is_string($handler) && class_exists($handler)) {
            $handler = new $handler();
        } elseif (!$handler instanceof SystemTaskHandlerInterface && is_callable($handler)) {
            $handler = $handler();
        }

        if (!$handler instanceof SystemTaskHandlerInterface) {
            throw new InvalidArgumentException(
                'The handler registered for system task type "' . $type . '" is not a '
                . SystemTaskHandlerInterface::class . '.'
            );
        }

        return $handler;
    }

    /**
     * The callable that turns a manager request into a queued task, if the
     * type declared one. A type without a creator can still be queued by the
     * package's own code — it just is not reachable through the generic
     * "create task from store request" entry point.
     */
    public static function creator(string $type): ?callable
    {
        return self::definition($type)['creator'] ?? null;
    }

    public static function mode(string $type): string
    {
        return (string) (self::definition($type)['mode'] ?? self::MODE_EXCLUSIVE);
    }

    /**
     * Whether a task of this type must run alone.
     *
     * An unknown type answers true. That is the safe direction: a task nothing
     * claims to understand should not be assumed harmless to run alongside a
     * site update.
     */
    public static function isExclusive(string $type): bool
    {
        return self::mode($type) === self::MODE_EXCLUSIVE;
    }

    public static function parallelism(string $type): int
    {
        return max(1, (int) (self::definition($type)['parallelism'] ?? 1));
    }

    /** @return string[] permissions required *in addition* to `exec_module` */
    public static function permissions(string $type): array
    {
        return (array) (self::definition($type)['permissions'] ?? []);
    }

    public static function requiresSuperAdmin(string $type): bool
    {
        return (bool) (self::definition($type)['super_admin'] ?? false);
    }

    public static function label(string $type): string
    {
        return (string) (self::definition($type)['label'] ?? $type);
    }

    /**
     * The three flows the CMS ships, declared rather than hardcoded.
     *
     * They keep the exact permissions and the exact super-admin gate the
     * switch statements applied before, so an installation with no packages
     * behaves identically.
     */
    private static function registerDefaults(): void
    {
        if (self::$defaultsRegistered) {
            return;
        }

        self::$defaultsRegistered = true;

        self::$definitions['console_install'] = [
            'type' => 'console_install',
            'handler' => ConsoleInstallFlowService::class,
            'mode' => self::MODE_EXCLUSIVE,
            'parallelism' => 1,
            'permissions' => ['system_tasks.manage_packages'],
            'super_admin' => false,
            'creator' => null,
            'label' => 'Console package install',
        ];

        self::$definitions['console_uninstall'] = [
            'type' => 'console_uninstall',
            'handler' => ConsoleUninstallFlowService::class,
            'mode' => self::MODE_EXCLUSIVE,
            'parallelism' => 1,
            'permissions' => ['system_tasks.manage_packages'],
            'super_admin' => false,
            'creator' => null,
            'label' => 'Console package uninstall',
        ];

        self::$definitions['site_update'] = [
            'type' => 'site_update',
            'handler' => SiteUpdateFlowService::class,
            'mode' => self::MODE_EXCLUSIVE,
            'parallelism' => 1,
            'permissions' => ['system_tasks.site_update'],
            'super_admin' => true,
            'creator' => null,
            'label' => 'Site update',
        ];
    }
}
