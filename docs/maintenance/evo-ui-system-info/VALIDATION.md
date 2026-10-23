# Validation — core Evo UI / System Info (#2515)

Validated locally on 2026-10-23 against the Laravel 13 `3.6.x` base `ee341135a`.
One installed site, one persistent SQLite database, one Composer lock and vendor
were reused. PHP runtimes were switched sequentially; no cloned installations,
parallel PHP legs or ignored platform requirements were used.

## Runtime matrix

| Portable PHP (macOS arm64) | Pest | PHPStan | Composer platform/validate | Repeated SQLite update + data smoke |
|---|---|---|---|---|
| 8.4.27 | 1,968 passed; 11 skipped; 19,936 assertions | 0 errors | Pass | Pass |
| 8.5.12 | 1,968 passed; 11 skipped; 19,936 assertions | 0 errors | Pass | Pass |
| 8.6.0RC4 | 1,968 passed; 11 skipped; 19,936 assertions | 0 errors | Pass | Pass |

Pest ran with `--fail-on-warning --fail-on-risky --fail-on-deprecation` and
`E_ALL`. Tracy was enabled for manager browser checks and disabled only in the
local CLI test configuration: its error handler interferes with PHPUnit's own
handler tracking. No production Tracy defaults were changed.

The 11 existing platform/layout skips cover five Windows junction cases, three
case-sensitive filesystem cases, one Git-only archive comparison and two retired
committed-vendor classmap checks. They are not new System Info skips.

`composer audit --locked`: no known advisories. Lock SHA-256:
`97703ccbacc5daf06c870adb78a055e901419a4719c6596f892838deaf53d47b`.
No existing production package versions changed; seven required runtime packages
were added. A real standalone Composer `install --no-dev` was followed by CLI,
HTTP and interactive Livewire checks on all three runtimes before restoring dev
tools for the full suite.

## UI, actions and security

- Both themes were selected and saved through the manager settings UI. `default`
  returns the original System Info; `evo-ui` selects the override and keeps other
  screens on the existing fallback. Shared tree AJAX and shell assets work.
- Only Evo UI CSS/JS plus one Livewire runtime load in the native page; it does
  not import legacy Bootstrap, jQuery or `main.js`.
- PHP Info was opened and closed through the real core Livewire component on all
  three production runtimes. The URL stays on System Info, the PHP version is
  correct, and no temporary probe route is required. Fullscreen/exit/Cancel were
  exercised. The sandbox has no script or same-origin permissions.
- PHP Info uses the effective page theme at opening, validated against the four
  package themes on the server. No extra runtime is loaded into its document.
- OPcache reset uses the real API and reports `reset complete` inside its button,
  then restores `Reset OPcache` after 2.5 seconds. Feedback stays in the fixed-width
  button and adds no result paragraph. The final two-line statistics row measures
  54.32px at 1280px; time elements and the full copy source were inspected in DOM.
- Denied permission, restricted API, successful/failed API return, read-only
  PHP Info open/close and the existing conditional GET CSRF contract have tests.
  Native renders cannot invoke a GET-triggered reset.
- Both manager endpoint aliases return login anonymously. Router tests verify
  GET and POST use `mgr` middleware for both aliases. Existing CSRF tests remain
  green; no second token scheme was added.
- Final page: no duplicate h1, no card/frame, transparent table, zero final-row
  border on both header and value cells, wrapping long paths. The observed 1280px
  page has scrollWidth 1280px. Narrow manager iframe checked visually; the earlier
  responsive pass also exercised 360/580/840/1280px.
- Manager browser/HTTP logs on the production runtime passes contained no PHP
  warnings/notices/deprecations/fatals. The PHP 8.5 null-offset deprecation found
  during integration was fixed in the existing CSRF guard and has a regression
  test. Temporary diagnostic probes were removed.

Core JavaScript tests: **102 passed** (41 manager script + 61 browser script).
Final `git diff --check`: clean.

## Known upstream SDK failures

The released Evo UI 1.3.0 standalone source-contract runner was also executed.
It reports **3 failures / 1,644 assertions**. These are package contracts outside
this pilot, not hidden or patched in vendor:

1. Form save action expects saved feedback through the title.
2. Typography contract expects `--evo-ui-issue-page-title-font-size: 20px`, while
   the released stylesheet uses 21px.
3. Form dirty-state contract expects accessible saved feedback text.

The core suite and actual System Info actions pass; this does not establish that
the entire Evo UI SDK suite is green. Fixing those released form/issue contracts
belongs in Evo UI, with a new package release and separate scope.

## Limits and local evidence

This is macOS arm64 + SQLite qualification. It does not claim Windows/Linux,
MySQL/PostgreSQL, renamed-manager end-to-end or third-party extras coverage.
PHP 8.6 is RC4, not a final stable release. The rest of the manager is deliberately
not migrated by this issue.

Local ignored evidence is in `output/evo-ui-2515/`: runtime result JSON, full Pest
and PHPStan logs, production CLI checks and screenshots. Disposable credentials,
SQLite data, vendor and custom diagnostic configuration are not source changes.

## Final UI refinement

The committed presentation places Reset OPcache before a two-line summary. It
shows success/danger status colors, a rounded hit rate and used/total memory
(including wasted memory in the total); the tooltip retains the original detailed
statistics. Version values use the primary color. Server/local times use semantic
`time` elements with Tabler clock icons. Database name displays its basename with
an ellipsis for very long filenames, retains the full value in its tooltip and
escaped data attribute, and has an Evo UI copy button using the Clipboard API.
Copy success/error feedback is local and accessible; no server action is added.

Both alert CSS selection and settings CSS-cache invalidation use the same physical
asset theme as the rest of the manager. The final source is qualified with the
complete sequential runtime matrix above. Production dependency installation and
interactive modal/reset checks were performed earlier with the same locked graph;
these later presentation changes add no runtime dependencies.

## Copy and tab follow-up

After the initial integration commit `935371033`, the view was refined to keep
path prefixes and suffixes, expose compact right-hand copy buttons for every
path/URL and the table prefix, label the PHP button `phpinfo`, color Reset OPcache,
and let its summary occupy one line when space allows. A shared Blade partial
keeps full values separate from their visual clipping. Copy uses an explicit
per-button value, a selection/copy event plus the Clipboard API, and visible
check/error icons.

The actual Clipboard API was read back through a temporary local test field after
each click: all seven values matched exactly (database name, table prefix, base
path/URL, manager path/URL and site URL). Copying the manager URL from the real
manager iframe also matched exactly. The temporary check page was removed.
Browser-tool virtual clipboard snapshots were insufficient for repeated reads;
these checks read the browser's native API through the test UI instead.

The missing tab icon was traced to the manager replacing its initial action icon
when the iframe has no h1. It now preserves that icon, and System Info resolves
the existing info-circle SVG. Reload shows exactly one SVG in the tab. Core JS
regression tests: 41 passed. This follow-up changes views, translations and the
existing tab handler; the full core PHP matrix above belongs to the integration
commit, while the final rendered page is checked again on the portable runtimes.

Final follow-up renders on 8.4.27 → 8.5.12 → 8.6.0RC4 used the same installation.
Each has seven copy buttons, a 47px OPcache row at 1280px, and scrollWidth equal
to clientWidth. HTTP logs have no PHP warnings/notices/deprecations/fatals. JIT
configuration was not changed by this work. Local evidence: `copy-render-checks.json`.
