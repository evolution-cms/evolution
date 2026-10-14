<?php namespace EvolutionCMS\Support;

use EvolutionCMS\TemplateProcessor;

/**
 * Who may make a template keep its code in a file.
 *
 * A template file is not data: it is rendered by an engine that executes it.
 * Core ships the `php` engine (a bare `include`) and `blade.php` (compiled to
 * PHP), and a plugin can register `latte`, `twig` and others through
 * `view.template_engines`. So any file a template save writes or scaffolds under
 * a view path is server-side code, whatever its extension - there is no "safe"
 * template engine to allow-list, because executing the file is the point.
 *
 * `save_template` is deliberately not a code permission (database templates only
 * call existing snippets). Writing a template file therefore needs the same bar
 * the file manager puts on `views/`, `core/` and `manager/`:
 * `fileManagerMayRunCode()` - one of save_snippet / save_plugin / save_module.
 *
 * @since 3.5.9
 */
class TemplateFileGuard
{
    /**
     * The permissions whose holder may store code the server runs. Kept in step
     * with fileManagerMayRunCode() in core/functions/actions/files.php.
     *
     * @var string[]
     */
    public const CODE_PERMISSIONS = ['save_snippet', 'save_plugin', 'save_module'];

    /**
     * Whether a template save would write or scaffold a file under a view path -
     * either because the code is posted to live in a file, or because the form
     * asked for the file to be created. Both reach the filesystem; both author
     * code.
     */
    public static function writesFile(?string $source, bool $wantsFileCreated): bool
    {
        return (string) $source === TemplateProcessor::SOURCE_FILE || $wantsFileCreated;
    }

    /**
     * Whether the current manager may author code the server runs.
     *
     * @param object|null $evo anything exposing hasPermission(); defaults to the
     *                         running instance so the processor can call it bare.
     */
    public static function authorMayRunCode($evo = null): bool
    {
        $evo = $evo ?? evolutionCMS();

        foreach (self::CODE_PERMISSIONS as $permission) {
            if ($evo->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }
}
