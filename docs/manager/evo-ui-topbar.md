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
exposes this final list as `menuItems`. The legacy HTML `menu` is built only for
themes using the legacy renderer; EvoUI consumes `menuItems` directly. A plugin
can add, remove, reorder or reparent items before rendering.

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
Keyed optional controls retain their key in `data-evo-menu-extra`. Only the
stock `refresh_site_in_window` control clears the cache in the background; extra
plugin buttons on that same row keep their own handlers.

The new renderer accepts any available operation in column 6, matching core
entries such as `new_chunk,edit_chunk`. A denied parent hides its branch. The
shipped sidebar toggle's historical `return false;` sentinel is preserved.
Routes/actions still enforce authorization independently of menu visibility.

Static children are rendered recursively. On desktop, hovering a submenu row
opens its next level; clicking its label still navigates. The expansion control
stays on the right within the same highlighted row. Touch and keyboard users
can expand it with that control. A parent with a real link retains
that link and has a separate expansion control. If it has no static children,
the existing `dropdown-toggle` class requests its dynamic element list through
the manager's physical asset-theme AJAX endpoint. Native `fetch` sends the
session, XHR header and CSRF token; the bar does not depend on `evo.post`.
Search in a dynamic list can be changed and cleared; stale search responses are
discarded. Failed requests show a retry control and release the busy state.

## Interaction and appearance

- Primary stock groups have no down arrows. Nested menus keep their labels and
  expansion controls, including on mobile.
- At widths up to 600px the primary bar shows icons only. Titles and accessible
  labels remain; text-only plugin items receive a fallback icon.
- Native button appearance is reset and primary controls have an explicit 32px
  height and 20px line height for Safari/Chrome alignment. The bar measures
  approximately 39px on a normal desktop viewport; the logo is 22px. Existing
  frame height measurement also accommodates wrapped rows.
- Arrow keys and Escape operate the menu. Text fields retain their editing keys;
  closing a parent resets expanded descendants. Focusing outside the bar or clicking
  outside closes it.
- The existing theme button cycles `evolightness`, `evolight`, `evodark` and
  `evodarkness`, using the manager's editor/iframe/cookie synchronization.
- The cache item's blue optional control immediately posts the existing tokenized
  action URL. A small result notice beside the control disappears after five
  seconds. There is no confirmation or new tab. The main cache link still opens
  the full result page. Failed/login responses are never reported as success.
- EvoUI always uses the horizontal bar, even if a site previously selected the
  legacy left menu. The setting remains available when switching back to default.

## Incremental legacy removal

The EvoUI frame skips the old recursive HTML menu builder and the old mainmenu
initializer. Palette markup, Spectrum, color-switcher CSS/JS and its Bootstrap
script are excluded even if `show_picker` is enabled. Those optional controls
live in separate `frame.color-picker-*` partials for legacy themes. EvoUI's theme
control is a native keyboard-operable button and still cycles the four modes.

The shared `evo.js` and default stylesheet remain necessary for the tree,
resizer, search, tabs, theme synchronization and legacy iframe screens. The
right-hand action list still shares its permission/CSRF-aware Blade partial.
These are the next migration boundaries; removing their assets globally would
break screens that have not yet moved to EvoUI. Native Sysinfo remains a theme
override with its own Livewire runtime.

## Validation

`core/tests/Unit/Manager/ManagerMenuTest.php` covers extension ordering,
reparenting/removal, nested items, permissions, optional controls, lazy menus,
the sidebar sentinel and malformed/cyclic input. `EvoUiThemeTest.php` covers
the selected bar and the shared frame/assets. Existing manager and CSRF tests
continue to cover the common runtime.

Browser verification should include a real event listener that adds nested
items and optional controls, moves/removes stock items and supplies a denied
permission. Also exercise dynamic element lists, main-target navigation with
global tabs, both themes, the four modes, the quick-cache result notice and a
mobile viewport.
Also verify tabs disabled, clearing a dynamic filter, failed-request retry,
editing filter text with the arrow keys, and `show_picker=1` with both themes.

The sidebar toggle restores the configured default width when a collapsed
cookie/saved width is zero. The default rem width is converted to pixels before
resizing; modules requesting a hidden tree respect an explicit manual open.
The global tab click listener closes the narrow drawer only for actual
navigation, and respects links cancelled/handled by other listeners.
Node regressions live in `manager/media/script/tests/responsive-tree-drawer.test.js`
and `evo-ui-topbar.test.js` (including quick-action CSRF, duplicate-click guarding,
expired-login/error feedback, auto-dismiss, hover and parent navigation).
