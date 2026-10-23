# EvoUI manager topbar

Select `evo-ui` as the manager theme and reload the manager. Selecting `default`
restores the established renderer. Both use `manager::frame.1`; the stock bar is
now `manager::frame.topbar`, with the action controls in
`manager::frame.topbar-actions`. The EvoUI theme overrides the bar and contributes
`manager::frame.topbar-assets`. Other custom themes retain the fallback bar.

The change keeps the tree, resizer, search implementation, global tabs, AJAX
endpoint, manager permissions, project version display and plugin events shared.
The theme owns dropdown interaction and uses EvoUI CSS tokens, icons and buttons.
It does not introduce MaryUI or another Livewire/Alpine instance into the frame.

## Adding or changing menu items

Continue listening to `evolution.OnManagerMenuPrerender`. Return a serialized
complete menu, merging additions with `$params['menu']`, as existing extras do:

```php
Event::listen('evolution.OnManagerMenuPrerender', function ($params) {
    $menu = $params['menu'];
    $menu['my_extra'] = [
        'my_extra', 'tools', '<span>My extra</span>',
        'index.php?a=53', 'My extra', '', 'settings', 'main', 0, 12, '',
    ];
    return serialize($menu);
});
```

The event's replacement/merge behavior has not changed. The Frame controller
also exposes this final list as `menuItems`; the legacy HTML `menu` remains
available. A plugin can add, remove, reorder or reparent items before rendering.

| Index | Meaning |
| --- | --- |
| 0 | Unique item ID; existing DOM IDs are retained |
| 1 | Parent ID, or `main` for the primary bar |
| 2 | Trusted plugin-authored label/icon HTML |
| 3 | Link, including any required CSRF token |
| 4 | Title and accessible label |
| 5 | Trusted plugin-authored inline handler |
| 6 | Permission name or comma-separated alternative operations |
| 7 | Link target; `main` uses the existing iframe/global-tabs navigation |
| 8 | Separator before the item |
| 9 | Sort position; equal positions retain insertion order |
| 10 | Extra item classes |
| 11 | Optional controls: one tuple or a list/keyed list of tuples |

Optional controls use `[tag, href, class, onclick, title, html]`, with `a` or
`button` as the tag. Names and handlers remain a trusted extension API; stored
user text must be escaped by its producer. Attributes are escaped by Blade.
External `_blank` links receive `noopener noreferrer`.

The new renderer accepts any available operation in column 6, matching core
entries such as `new_chunk,edit_chunk`. A denied parent hides its branch. The
shipped sidebar toggle's historical `return false;` sentinel is preserved.
Routes/actions still enforce authorization independently of menu visibility.

Static children are rendered recursively. A parent with a real link retains
that link and has a separate expansion control. If it has no static children,
the existing `dropdown-toggle` class requests its dynamic element list through
the manager's physical asset-theme AJAX endpoint. Search in a dynamic list can
be changed and cleared; stale search responses are discarded.

## Interaction and appearance

- Primary stock groups have no down arrows. Nested menus keep their labels and
  expansion controls, including on mobile.
- At widths up to 600px the primary bar shows icons only. Titles and accessible
  labels remain; text-only plugin items receive a fallback icon.
- The bar measures approximately 43px on a normal desktop viewport; the logo
  is 22px. Existing frame height measurement also accommodates wrapped rows.
- Arrow keys and Escape operate the menu. Focusing outside the bar or clicking
  outside closes it. Modified link clicks keep normal browser behavior.
- The existing theme button cycles `evolightness`, `evolight`, `evodark` and
  `evodarkness`, using the manager's editor/iframe/cookie synchronization.
- The cache item's optional control opens a confirmation dialog; confirmation
  uses the existing tokenized action URL.

## Validation

`core/tests/Unit/Manager/ManagerMenuTest.php` covers extension ordering,
reparenting/removal, nested items, permissions, optional controls, lazy menus,
the sidebar sentinel and malformed/cyclic input. `EvoUiThemeTest.php` covers
the selected bar and the shared frame/assets. Existing manager and CSRF tests
continue to cover the common runtime.

Browser verification should include a real event listener that adds nested
items and optional controls, moves/removes stock items and supplies a denied
permission. Also exercise dynamic element lists, main-target navigation with
global tabs, both themes, the four modes, the cache dialog and a mobile viewport.
