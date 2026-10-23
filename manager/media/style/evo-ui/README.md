# Evo UI manager theme (3.6)

Select `evo-ui` under System configuration → Interface & Features → Manager theme,
save and reload the manager. Select `default` to restore the existing interface.
The default installation and existing sites keep `default`.

This first slice overrides only `manager::page.sysinfo` (action 53). Both themes
use the same controller, permission checks and data. Other views resolve through
the existing `manager/views` fallback. The shell, tree, AJAX, snippets, chunks,
login and legacy screens share the default theme's physical assets; they are not
copied. Evo UI CSS/JavaScript are loaded only by the native iframe document.

`ManagerTheme::getTheme()` identifies the logical selection;
`getThemeDir()` / `getThemeUrl()` identify its shared physical asset directory.
New screen overrides belong in this theme's `views/`, not in the default views.

System Info uses the core `evo-core.system-info` Livewire component registered
through Evo UI. PHP Info opens lazily in an Evo UI modal with a sandboxed document;
OPcache reset reports its result temporarily inside its button. The read-only
render does not perform a reset. Both operations require `settings` on each request
and use the existing guarded manager endpoint. The page is a flat, transparent
table without a duplicate heading or outer card.
