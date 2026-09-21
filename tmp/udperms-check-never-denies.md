# `Legacy\Permissions::checkPermissions()` never denies a document

Date: 2026-09-21. Found while porting the target-folder permission check into the tree
drag-and-drop handler (`fix-move-document-missing-checks`, bd4342d83). Not fixed there —
it is a separate Legacy bug and deserves its own branch.

## Where

`core/src/Legacy/Permissions.php:69-81` (`checkPermissions()`), used by:

- `Controllers\MoveDocument::checkNewParentPermission()` / `processDisplay()` (a=51)
- `Support\MoveDocumentTargetGuard::deniedForUser()` (tree drag-and-drop, since bd4342d83)
- `manager/actions/mutate_menuindex_sort.dynamic.php:19-25`
- `core/functions/actions/mutate_content.php` and the other manager actions that instantiate
  `new Permissions()` with `->document = $id` (grep `udperms->document`)

## What is wrong

The query that is supposed to answer "may this user see document `$this->document`"
never filters by that id:

```php
$query = SiteContent::query()->select('id');
if (!empty($docgrp)) {
    $query = $query->leftJoin('document_groups', 'site_content.id', '=', 'document_groups.document')
        ->where(function ($q) use ($docgrp) {
            $q->where('document_groups.document_group', $docgrp)   // (2)
              ->orWhere('site_content.privatemgr', 0);
        });
} else {
    $query->where('privatemgr', 0);
}
if ($query->count() > 0) {          // (1)
    $permissionsok = true;
}
```

1. No `->where('site_content.id', $this->document)`. `count() > 0` is true as soon as *any*
   non-private (or any group-accessible) document exists, i.e. always on a real site. For every
   non-admin role with `use_udperms = 1` the check returns true regardless of the target.
2. `$docgrp` is `implode(' || dg.document_group = ', $_SESSION['mgrDocgroups'])` — a leftover
   raw-SQL fragment from the original MODX query. Bound as a single value in `where(...)`, it
   only matches when the user is in exactly one group (`"3"`); with two groups the bound value
   is the literal string `"3 || dg.document_group = 5"` and matches nothing, so the
   `privatemgr = 0` branch carries the whole result.

Result: udperms cannot deny moving/creating into a private folder, and cannot deny opening the
move page for a private document. The only denial that still fires is the root case before the
query — and it fires for the wrong reason (see the global below).

## Fix outline

- Add `->where('site_content.id', (int) $this->document)` and use `whereIn('document_groups.document_group', $_SESSION['mgrDocgroups'])`.
- Keep the `privatemgr = 0` OR-branch (public documents stay visible), and keep the root
  short-circuits above the query unchanged.
- `global $udperms_allowroot` (`Permissions.php:33`) is never assigned anywhere in core or
  manager (grep), so it is always null: the "allowed at root" short-circuit (`== 1`) never
  fires and the root denial (`== 0`, loose) fires for every non-admin creating/duplicating at
  root even when the setting is on. Read `evo()->getConfig('udperms_allowroot')` instead, as
  `ajax.php:657` already does.
- Tests: in-memory `site_content` + `document_groups` with a private folder the user is not in
  (expect false), one they are in (expect true), a public one (expect true), user in two groups.
  Then a manager-level check for a=51 and the tree `movedocument` with a private target.
