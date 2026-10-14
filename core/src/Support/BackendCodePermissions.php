<?php namespace EvolutionCMS\Support;

/**
 * The one answer to "may this manager author backend code?" - code the server itself executes:
 * PHP, and SQL, which reaches PHP (it can store a plugin, raise a role, or write a file where
 * INTO OUTFILE / COPY TO is not restricted).
 *
 * It is not about client-side code. JavaScript or HTML in a chunk, template or document runs in
 * the visitor's browser and is governed by the permission of that element.
 *
 * save_snippet, save_plugin and save_module each store PHP that the server executes, so whoever
 * holds any one of them can already do anything PHP can. Every other place that lets a manager
 * store backend code - an executable file in the file manager or media browser, a template file
 * under a view path, an @EVAL / @SELECT / @INCLUDE TV binding, a database restore - asks this
 * class instead of picking permissions of its own, so the bar is the same everywhere.
 *
 * save_template is deliberately not one of them: a database template only calls existing snippets.
 *
 * @since 3.5.9
 */
final class BackendCodePermissions
{
    /** @var string[] */
    public const PERMISSIONS = ['save_snippet', 'save_plugin', 'save_module'];

    /**
     * @param callable(string): bool $hasPermission
     */
    public static function grantedBy(callable $hasPermission): bool
    {
        foreach (self::PERMISSIONS as $permission) {
            if ($hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param object|null $evo anything exposing hasPermission(); defaults to the running instance
     */
    public static function granted($evo = null): bool
    {
        $evo = $evo ?? evolutionCMS();

        return self::grantedBy(static fn (string $permission): bool => (bool) $evo->hasPermission($permission));
    }
}
