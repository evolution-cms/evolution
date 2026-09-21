# Evolution CMS manager: making document save the fastest in the phramark admin workload

Date: 2026-09-21
Source data: `c:\projects\Opensource\phramark\docs\results.json` (generated 2026-09-20T14:43Z),
live reproduction against the `evo-parser` container on http://127.0.0.1:8080.

## 1. What the benchmark measures

`serverMs` for a step is the sum of every top-level document request (HTML navigations and
form posts, including redirects) between the click and the settled editor, plus known CMS
XHR endpoints (Winter's `X-Winter-Request-Handler`, MODX's `/connectors/index.php`) — see
`benchmark/workloads/admin/lib/document-timer.mjs`.

For Evolution a save is **POST `a=5` + the 302'd GET `a=27`** (full editor re-render).
For Winter it is one XHR. Evo pays for two requests and a page render where the leader pays
for one JSON call.

### Admin results, JIT off, median serverMs

| stack            | login | open-edit | **save-edit** | open-create | save-create | total wall |
|------------------|------:|----------:|--------------:|------------:|------------:|-----------:|
| evo-parser       |   243 |       102 |       **439** |          84 |         502 |    11.6 s |
| evo-latte        |   252 |       100 |       **422** |          83 |         529 |    12.0 s |
| evo-latte-parser |   253 |       100 |       **414** |          84 |         509 |    11.9 s |
| evo-phalcon      |   251 |       104 |       **424** |          84 |         518 |    11.7 s |
| drupal-11        |   323 |       124 |       **350** |          44 |         313 |     6.0 s |
| typo3            |  1241 |       155 |       **399** |         111 |         763 |    18.5 s |
| winter           |   193 |        33 |        **36** |          34 |          67 |     4.3 s |
| modx             |   367 |       211 |       **138** |         169 |         355 |    15.5 s |
| wordpress-gantry |   263 |        72 |       **132** |          79 |         141 |     9.1 s |

Observations before touching any framework question:

1. **All four Evo stacks are identical on admin.** aPhalcon and aLatteX only touch the front
   end. The admin cost is CMS logic, not the DB adapter or the template engine.
2. **Save costs 4x an editor load, and `phpPeakMb` jumps 1.2 -> 5.9 MB only on save**
   (every other step stays ~1.2 MB). That is a compile spike, not data.
3. Login is 1 808 ms wall for 243 ms server: the frameset renders a **27 228-node DOM**
   (Winter 5 230, Drupal 2 498). Every step also carries ~60 ms client-side overhead.

## 2. Root cause, reproduced

`manager/processors/save_content.processor.php:467-470` (and `:672-675` for the update
branch) call `$modx->clearCache('full')` whenever `syncsite=1`, which is the form default.
That runs `Legacy\Cache::emptyCache()` (`core/src/Legacy/Cache.php:112-144`), which on
**every document save** does:

- `Illuminate\Support\Facades\Cache::flush()` — wipes the whole Laravel cache store
- unlinks every `assets/cache/*.pageCache.php`
- **`opcache_reset()`** — discards the compiled bytecode of the entire Laravel + Evo tree
- `buildCache()` — regenerates `siteCache.idx.php`; with default settings
  (`full_aliaslisting=0`) that enumerates all documents (10 000 in the fixture). The benchmark
  sets `full_aliaslisting=1` (`benchmark/fixtures/setup.php:204`), so this part is skipped
  there — real-world sites are hit *harder* than the benchmark shows.

The redirected GET then runs on a cold opcache (`opcache.validate_timestamps=0`,
`memory_consumption=512` in `benchmark/config/php.ini` — nothing was warming it back).

### Live measurement (curl, same session, editor form payload replayed verbatim)

| scenario                         | POST a=5     | GET a=27 after | sum           |
|----------------------------------|-------------:|---------------:|--------------:|
| `syncsite=1` (form default)      | 260–500 ms   | 420–940 ms     | ~750–1200 ms  |
| `syncsite=0`                     | 130–280 ms   | 100–170 ms     | ~300 ms       |
| warm editor load, no save        | —            | ~160 ms        |               |
| unknown action `a=999` (bootstrap only) | —     | ~90–120 ms     |               |

curl through Docker Desktop adds roughly 40–60 ms per request compared to what Playwright
records in-container, so relative numbers are the point.

Cost breakdown of a save today:

- **~60 %** cache flush + opcache reset + cold recompile on the redirect
- **~25 %** fixed manager bootstrap, paid twice (POST + GET): ~75–85 ms per request
- **~15 %** the processor's own work: ~15–25 independent queries, TV loop with per-row
  `find()->update()`, `DocumentGroup` reconciliation, `secure_web_documents.inc.php` include

## 3. Plan

### Phase 1 — Stop nuking the cache on save (1–2 days, ~2x faster)

Target: save-edit 439 -> ~200 ms. Ahead of Drupal/TYPO3, level with MODX/WordPress.

- `Legacy\Cache::emptyCache()`: remove `opcache_reset()` from the document-save path. Keep it
  for element saves (snippet/plugin/chunk/module code) and the explicit "Clear cache" action.
  This is the one deliberate `core/src/Legacy/` edit; it fixes a concrete bug.
- Replace `Cache::flush()` with keyed/tagged invalidation (`site_settings`, `doc:{id}`,
  `siteCache`).
- `buildCache()` on document save: update the single alias row in the listing, or mark
  `siteCache.idx.php` stale and rebuild lazily on the next *front-end* request where it is
  actually consumed. No synchronous 10k-row rebuild inside the manager request.
- Page cache: delete `docid_{id}` plus ancestors/siblings whose menus render it, not
  `*.pageCache.php` wholesale.
- Unit tests for the invalidation matrix (document save / element save / settings save /
  explicit clear). Re-run `benchmark/scripts/bench` on the `evo-parser` admin cell.

### Phase 2 — Processor diet (2–3 days)

Target: POST work ~40 -> ~15 ms.

- One transaction around the save.
- TVs: one query for current values, one bulk upsert, one bulk delete. Today: a `distinct()`
  join over all TVs at the top (`:204-220`), then `find()->update()` per TV (`:577`).
- Skip `DocumentGroup` reconciliation and `secureWebDocument()` / `secureMgrDocument()`
  when privacy/group fields are unchanged; `$existingDocument` (`:267`) is already loaded to
  diff against.
- Drop `SiteContent::max('id')` / the `id + 1` self-join id hunting on create (`:333-342`)
  where auto-increment already answers.
- Move the 700-line processor into `core/src/Services/DocumentSaveService.php`; leave a thin
  controller in `manager/processors/`. Prerequisite for Phase 3 and for any fast lane.

### Phase 3 — AJAX save, no editor reload (2–3 days)

Target: save-edit ~= one request ~= 90 ms; then bootstrap-bound.

- `a=5` answers JSON when called with `X-Requested-With: XMLHttpRequest` (id, alias,
  `editedon`, refreshed CSRF token); the editor JS updates the form in place. The form POST
  remains as the no-JS fallback. CSRF: send `_token` from `<meta name="csrf-token">` (see
  AGENTS.md, CSRF rules).
- `save-create` still needs one navigation to `a=27&id=<new>`, same as Winter.
- **Fairness:** phramark counts Winter's XHR by header and MODX's by URL
  (`document-timer.mjs:12-13`). Add the Evo endpoint to that list in the same way so the XHR
  *is* counted. Otherwise the improvement would be a measurement artefact, not a win.

### Phase 4 — Manager bootstrap diet (1–2 weeks; this is what makes Evo first)

Target: fixed cost ~80 -> ~35 ms per request. Winter's whole save is 36 ms.

- Profile first: add php-spx (or xhprof) to `benchmark/images/php`, trace `a=27` and `a=5`.
  Candidates, all unverified until profiled:
  - ~30 service providers booted eagerly on every request -> defer the manager-irrelevant ones.
  - Lexicon: full language arrays per request -> per-action files.
  - `OnManagerPageInit` / theme init, `ManagerTheme` action map, Blade compile checks.
  - Settings and user-permission loading: must hit cache, not DB, per request.
  - Session: `session.lazy_write`, no unnecessary `session_regenerate_id`.
- Autoload is 44 ms cold on CLI in the container; under FPM with opcache it should be ~2 ms.
  Verify the deployed vendor is `classmap-authoritative` in the benchmark image and that the
  Phase 1 cache changes never invalidate it.

### Phase 5 — Front-end wall time (parallel track, biggest user-visible win)

`totalWallMs` 11.6 s vs Winter 4.3 s; login 1 808 ms wall for 243 ms server.

- Lazy document tree: render root + expanded path only, fetch children on expand (the AJAX
  expand endpoint already exists — make it the default instead of pre-rendering 27k nodes).
- Audit per-step client overhead (~60 ms on every action): scripts loaded in the mainframe
  on every navigation, `stay=2` re-initialising editors.
- JS/Blade work in `manager/media/style/*` and the tree views; independent of the PHP phases.

## 4. Phalcon in the manager — assessment

Recommendation: do not port the manager to Phalcon. The benchmark itself is the evidence.

- **evo-phalcon == evo-parser on every admin metric** (424 vs 439 save-edit). Phalcon is
  loaded in that container and buys nothing for the manager, because the manager cost is Evo
  logic (cache flush, processor, bootstrap), not framework dispatch speed.
- **The guest win (p50 51 ms vs 393 ms) is not "Phalcon is fast", it is "takeover skips the
  CMS".** aPhalcon's `frontend.takeover` renders the content row straight into a Latte view:
  no parser pass, no snippets, no plugin events, no `documentListing`
  (`c:\projects\Opensource\evo\aPhalcon\README.md`, "takeover" section). A plain-PHP takeover
  without the extension lands in the same ballpark. Porting the manager to Phalcon without
  rewriting what it does reproduces evo-phalcon's admin numbers: unchanged.
- Cost of doing it anyway: a C extension most shared hosts do not ship, a second dispatch
  model to maintain under `/manager`, and every extra hooking `OnManagerPageInit`,
  `OnDocFormSave`, etc. needs a shim.

Where Phalcon can be tested cheaply and fairly: after Phase 2 extracts
`DocumentSaveService`, build a **fast-lane entry point** (`manager/api.php`) that boots only
session + DB + the service for the AJAX save.

1. Implement it in plain PHP / Illuminate first and measure.
2. Optionally implement the same lane as a `Phalcon\Mvc\Micro` inside aPhalcon behind a config
   flag (`manager.fastlane => true`) and measure again.

If the plain lane reaches ~35 ms the extension has nothing left to add. Either way Phalcon
stays an opt-in accelerator in the sibling plugin, not a core dependency — consistent with
how aPhalcon is positioned today.

## 5. Expected trajectory — save-edit, serverMs

| after       | estimate | rank                                  |
|-------------|---------:|---------------------------------------|
| today       |      439 | 8 / 8                                 |
| Phase 1     |     ~200 | 4 (behind Winter, WordPress, MODX)    |
| Phase 2 + 3 |      ~90 | 2 (behind Winter)                     |
| Phase 4     |   ~35–45 | 1–2, tied with Winter                 |

Phase 1 is a small PR and pays back on every real site immediately — more than in the
benchmark, given `full_aliaslisting=0` defaults. Start there, land the profiler in the phramark
PHP image alongside it, and let the Phase 4 profile decide the order of the bootstrap work.

## Appendix — how the reproduction was done

```sh
B=http://127.0.0.1:8080/manager/index.php
H=(-A Mozilla/5.0 -H 'Accept: text/html' -H 'Accept-Language: en' -e "$B")
# manager/index.php returns 404 without Accept-Language; CSRF check needs a Referer
curl -s "${H[@]}" -c cj.txt -b cj.txt -d 'username=benchmark&password=benchmark-admin&ajax=0' "$B?a=0"
curl -s "${H[@]}" -c cj.txt -b cj.txt -o edit.html "$B?a=27&id=10104"
# scrape every input/textarea/select of the editor form into post.txt (stay=2), then:
curl -s "${H[@]}" -b cj.txt -c cj.txt -o /dev/null -w '%{time_total}\n' --data-binary @post.txt "$B"
curl -s "${H[@]}" -b cj.txt -c cj.txt -o /dev/null -w '%{time_total}\n' "$B?a=27&id=10104&r=1&stay=2"
# repeat with syncsite=0 in post.txt
```
