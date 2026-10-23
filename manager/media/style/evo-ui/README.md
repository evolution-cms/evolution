# Evo UI manager theme (3.6)

Select `evo-ui` under System configuration → Interface & Features → Manager theme,
save and reload the manager. Select `default` to restore the existing interface.
The default installation and existing sites keep `default`.

The theme overrides the topbar and `manager::page.sysinfo` (action 53). The frame,
tree, AJAX, snippets, chunks, login and legacy screens share the default theme's
physical assets; they are not copied. The topbar loads EvoUI's CSS tokens and
components plus its own scoped CSS/JavaScript. It does not load a second Livewire
or Alpine runtime into the frame. Native EvoUI iframe documents own their runtime.

The topbar uses the final menu produced by the existing Frame controller after
`OnManagerMenuPrerender`. Plugin-added roots, nested groups, positions, links,
handlers and optional buttons keep their indexed-array contract. Element lists
load through the existing guarded AJAX endpoint. See
[`docs/manager/evo-ui-topbar.md`](../../../../docs/manager/evo-ui-topbar.md).

The bar is compact, has no arrows on its standard root groups, and shows icons
with accessible names on screens up to 600px wide. Nested menus retain labels
and separate expansion controls. The existing theme button cycles the four
Evolution modes; there is no palette selector.
The old palette/Spectrum/Bootstrap assets are excluded even with `show_picker`
enabled, and the legacy recursive menu builder no longer runs for this theme.
Dynamic lists use native `fetch` with the existing manager session/CSRF contract.
Legacy left-menu preferences do not change the new horizontal layout.

`ManagerTheme::getTheme()` identifies the logical selection;
`getThemeDir()` / `getThemeUrl()` identify its shared physical asset directory.
New screen overrides belong in this theme's `views/`, not in the default views.

System Info uses the core `evo-core.system-info` Livewire component registered
through Evo UI. PHP Info opens lazily in an Evo UI modal with a sandboxed document;
OPcache reset reports its result temporarily inside its button. The read-only
render does not perform a reset. Both operations require `settings` on each request
and use the existing guarded manager endpoint. The page is a flat, transparent
table without a duplicate heading or outer card.
