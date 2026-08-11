# Evolution CMS — Agent Guide

Evolution CMS is a community-maintained fork of MODX Evolution, rebuilt on Laravel 12 components.
It is a PHP CMS and application framework with a tree-based document/resource model,
a template variable (TV) system, plugins, snippets, chunks, and a modular extras ecosystem.

**PHP requirement:** >= 8.3 (8.4 recommended) · **License:** GPL-3.0-or-later

---

## Repository Layout

```
/                        ← web root (Apache/Nginx document root)
├── index.php            ← front-end entry point
├── manager/             ← admin panel entry point (manager/index.php)
├── assets/              ← public uploads, cache, plugins, snippets, templates
│   ├── cache/           ← compiled template/TV cache (git-ignored)
│   ├── files/           ← user-uploaded files
│   ├── images/          ← user-uploaded images
│   ├── plugins/         ← installed plugin JS/CSS assets
│   └── templates/       ← front-end HTML templates
├── themes/              ← manager UI themes (default: demo)
├── views/               ← Blade layouts for the manager UI
├── core/                ← CMS core (NOT web-accessible; block in nginx/Apache)
│   ├── artisan          ← CLI entry point: php core/artisan <command>
│   ├── bootstrap.php    ← application bootstrap
│   ├── composer.json    ← core dependencies (Laravel 12, Pest, etc.)
│   ├── config/          ← Laravel-style config files
│   ├── custom/          ← LOCAL OVERRIDES — copy examples here, never edit originals
│   │   ├── composer.json.example  → custom/composer.json (add extra packages)
│   │   ├── define.php.example     → custom/define.php   (constants override)
│   │   ├── routes.php.example     → custom/routes.php   (add Laravel routes)
│   │   └── config/                → override any core config key per-subdirectory
│   ├── database/
│   │   ├── migrations/  ← Eloquent migrations (single consolidated migration)
│   │   └── seeders/
│   ├── functions/       ← autoloaded global helpers (helper.php, nodes.php, etc.)
│   ├── lang/            ← locale files (en, ru, de, fr, …)
│   ├── modifiers/       ← output modifier include files (mdf_*.inc.php)
│   ├── src/             ← PSR-4 namespace EvolutionCMS\
│   │   ├── Console/     ← Artisan commands
│   │   ├── Controllers/ ← manager action controllers
│   │   ├── Extensions/  ← Router, Collection extensions
│   │   ├── Facades/
│   │   ├── Legacy/      ← legacy shim classes (Cache, ManagerApi, Modifiers…)
│   │   ├── Middleware/
│   │   ├── Models/      ← Eloquent models (SiteContent, SiteTemplate, SitePlugin…)
│   │   ├── Providers/   ← ~30 Laravel service providers
│   │   ├── Services/    ← thin service layer (AuthServices, ConfigService…)
│   │   └── Support/
│   ├── storage/         ← Laravel storage (logs, cache, compiled views)
│   └── tests/           ← Pest test suite
│       ├── Feature/     ← feature/integration tests
│       └── Unit/        ← unit tests (Console/, CoreTest.php)
├── composer.json        ← root composer (thin; delegates to core/)
├── phpstan.neon         ← static analysis config (level 0, excludes Legacy/)
└── publiccode.yml       ← structured information about version and project
```

---

## Commit Message Convention

All commits in this project use a bracketed prefix:

```
type(scope): short description (#issue)
```

Allowed lowercase types:
- `add` — net-new file, feature, surface, contract, rule, or generated capability
- `feat` — net-new user-facing feature or capability where feature wording is clearest
- `fix` — bug fix or defect repair
- `upd` — update to existing behavior, content, generated output, or workflow
- `ref` — refactor or restructure without changing intended product meaning
- `del` — explicit removal of obsolete code, docs, links, generated files, or flows

Examples:
- `add(ex42): add feed source worker (#69)`
- `ref(source): split API source transport (#69)`
- `fix(ex42): repair source run diagnostics`

Use a short concrete scope, write the description in English, do not add a final period,
and append `(#issue)` only when the commit is tied to an issue.

---

## Branching

- **`3.5.x`** — main stable branch; open PRs against this branch
- Feature/fix branches are named descriptively: `fix-rich-text-selection-when-editor-disabled`
- Do not push directly to `3.5.x`; always open a PR

---

## Running the Test Suite

Tests use [Pest](https://pestphp.com/) and live in `core/tests/`.

```bash
# From the core/ directory:
composer test

# Or directly:
cd core && vendor/bin/pest
```

PHPUnit sources cover: `factory/`, `functions/`, `includes/`, `modifiers/`, `src/`.

The SQLite test database is `core/database/evo-test.sqlite`.

---

## Static Analysis

```bash
# From the repository root:
composer analyze
# Equivalent: php -d xdebug.mode=off vendor/bin/phpstan --memory-limit=512M

# Scope: core/src/ (Legacy/ is excluded)
# Level: 0 (introductory — PRs should not increase error count)
```

---

## Artisan CLI

The CMS uses a subset of Laravel's Artisan. The entry point is `core/artisan`.

```bash
php core/artisan list                    # list all commands
php core/artisan cache:clear-full        # clear all caches
php core/artisan make:site update        # update site files and run pending migrations / updates
php core/artisan package:extras          # manage extras/packages
php core/artisan deprecated:list         # list deprecated code by semver
php core/artisan translations:sync       # sync translation files
php core/artisan doc:list                # list content documents
php core/artisan route:list              # list registered routes
php core/artisan tpl:list                # list registered templates
php core/artisan tv:list                 # list registered TVs
```

When a site was installed from a branch/ref that ends with `.x`, web and CLI site updates
must target the same `.x` branch/ref unless the operator explicitly selects another target.
Update migrations, seeders, and data fixes must be trackable or idempotent so repeated
`make:site update` runs do not break already-updated installations.

### Installing Evolution CMS packages

Install Composer packages from the `core/` directory through the Evolution CMS package installer:

```bash
cd core
php artisan package:installrequire vendor/package "*"
```

Do not use `composer require` directly and do not manually add packages to the root
`composer.json`. The Artisan command registers the dependency in `core/custom/composer.json` and
runs Composer in the correct Evolution CMS context.

After installation, run `vendor:publish` only when the package documents a provider or publish tag,
then apply package migrations when required:

```bash
php artisan vendor:publish --provider="Vendor\\Package\\PackageServiceProvider"
php artisan migrate
```

---

## Customisation Points (core/custom/)

`core/custom/` is the sanctioned location for all site-specific overrides. Core files must **never** be edited directly.

| File to create | Copy from | Purpose |
|---|---|---|
| `core/custom/define.php` | `define.php.example` | Override CMS constants (paths, session config, etc.) |
| `core/custom/routes.php` | `routes.php.example` | Register Laravel routes; use `Route::fallbackToParser()` to preserve CMS routing |
| `core/custom/composer.json` | `composer.json.example` | Add extra Composer packages (namespace: `EvolutionCMS\Custom\`) |
| `core/custom/config/cms/settings.php` | `config/cms/settings.php.example` | Override CMS settings |
| `core/custom/config/app/providers.php` | — | Register additional service providers |
| `core/custom/config/database/connections/` | — | Add database connections |

The merge-plugin in `core/composer.json` automatically includes `core/custom/composer.json`.

---

## Key Namespaces and Extension Points

| Namespace | Location | Purpose |
|---|---|---|
| `EvolutionCMS\` | `core/src/` | Core CMS classes |
| `EvolutionCMS\Custom\` | `core/custom/src/` | Project-specific extensions |
| `EvolutionCMS\Models\` | `core/src/Models/` | Eloquent models |
| `Database\Seeders\` | `core/database/seeders/` | Database seeders |
| `Tests\` | `core/tests/` | Test classes |

### Important Models

- `SiteContent` — resources/documents (the page tree)
- `SiteTemplate` — templates
- `SitePlugin` — plugins (event-driven PHP)
- `SiteSnippet` — snippets (callable PHP fragments)
- `SiteHtmlsnippet` — chunks (reusable HTML)
- `SiteTmplvar` / `SiteTmplvarContentvalues` — template variables (TVs)
- `User` / `UserAttribute` — manager users
- `Category` — categorisation for all element types

### Service Providers

There are ~30 service providers in `core/src/Providers/`. Notable ones:

- `RoutingServiceProvider` — registers the CMS front-end parser as a route fallback
- `TemplateProcessorServiceProvider` — registers the template tag parser
- `ManagerThemeServiceProvider` — manager UI
- `TracyServiceProvider` — Tracy debug bar integration
- `ModifiersServiceProvider` — output modifiers

---

## CMS Concepts for Agents

- **Resources/Documents** — tree-based pages; each has a template, TVs, and content
- **Chunks** — reusable HTML fragments called with `{{ChunkName}}`
- **Snippets** — PHP code blocks called with `[[SnippetName? &param=`value-one`; &param2=`value-two`]]`
- **Plugins** — PHP code triggered by system events (OnPageNotFound, OnLoadWebDocument, etc.)
- **Template Variables (TVs)** — custom fields attached to templates `[*tvName*]`
- **Output Modifiers** — pipe-chained filters on tag output: `[*field*:modifier]`
- **Templates** — whole page HTML with chunks, snippets, TVs

---

### Blade Directives

Application developers can use these directives in Blade views instead of duplicating their underlying CMS logic:

| Directive | Purpose |
|---|---|
| `@evoConfig('key')` | Output an Evolution CMS configuration value |
| `@makeUrl($id)` | Build a CMS URL for a resource identifier |
| `@revision('path/to/file.css')` | Build a public static-file URL with an mtime-based cache revision |
| `@evoParser($value)` | Process Evolution CMS parser tags in a value |
| `@evoRole('role')` / `@evoElseRole('role')` / `@evoEndRole` | Conditionally render content for a manager role |
| `@auth` / `@guest` | Conditionally render content for authenticated or guest users |
| `@lang('key')` | Output a localized translation string |

Blade also provides its standard Laravel syntax. The most commonly used directives are:

| Group | Directives |
|---|---|
| Layouts | `@extends`, `@section`, `@yield`, `@include`, `@component`, `@slot`, `@push`, `@stack` |
| Conditions | `@if`, `@elseif`, `@else`, `@unless`, `@isset`, `@empty`, `@switch`, `@case`, `@default` |
| Loops | `@for`, `@foreach`, `@forelse`, `@while`, `@break`, `@continue` |
| Forms and security | `@csrf`, `@method`, `@error` |
| PHP and escaping | `{{ $value }}`, `{!! $html !!}`, `@php`, `@verbatim` |

### Escaping rules for manager views

`{!! !!}` disables escaping for the whole expression and is the usual cause of stored XSS in the
manager. Prefer these, in this order:

| Situation | Use |
|---|---|
| Plain text, attribute values, CSS class lists | `{{ $value }}` |
| Anything inside a `<script>` element or an inline event handler | `@js($value)` (or `js_json()` for a raw JSON island) |
| Stored short text rendered in a cell or label (element descriptions, captions) | `{{ sanitize_inline_html($value) }}` |
| Stored rich markup that must keep looking the way it does (Event Log reports) | `{{ sanitize_rich_html($value) }}` |
| Theme icons | `{{ icon_html($_style['icon_x']) }}` / `icon_markup()` for string concatenation |
| Theme style blocks and lexicon entries that ship their own markup | `{{ ManagerTheme::styleHtml('key') }}` / `{{ ManagerTheme::lexiconHtml('key', [...]) }}` |
| Markup a PHP producer builds itself | return `Illuminate\Support\HtmlString` and print it with `{{ }}` |

`{{ }}` compiles to `e()`, which leaves `Htmlable` values untouched - so a producer that returns
`HtmlString` is printed verbatim and is never encoded twice. Reach for `{!! !!}` only for output
that is HTML by contract and outside the CMS's control (plugin event results, `phpinfo()`,
registered client scripts).

### CSRF rules for manager actions

Manager routes run through `EvolutionCMS\Middleware\VerifyCsrfToken` (registered in the `mgr`
group in `core/config/app.php`). It **fails closed**: once a manager session exists, every
non-GET request must present a `_token` that matches `$_SESSION['_token']`, or it is rejected
with 403. Adding a state-changing entry point without a token does not degrade quietly — it
breaks.

When you add or change a manager action, work through this:

| If you are adding… | You must |
|---|---|
| A form that changes anything | Emit `@csrf` (Blade) or `<?= csrf_field() ?>` (legacy `.php` / `.phtml`) inside the `<form>` |
| JS that posts to `index.php` | Send `_token` in the body, or set the `X-CSRF-TOKEN` header. Read it from `<meta name="csrf-token">` (in `manager/views/partials/header.blade.php`) or from a form field on the page |
| An action that changes state and reads `$_GET` or `$_REQUEST` | Add its action id to `VerifyCsrfToken::MUTATING_GET_ACTIONS`, **and** append `&_token=` to every link that triggers it |
| Code that walks the whole `$_POST` body | Skip the `_token` key, or it will be saved as data |

Two traps worth stating outright, because both have already caused bugs here:

- **Page controllers count, not just processors.** An action id can map straight to a class in
  `ManagerTheme::$actions` with no file under `manager/processors/`. Auditing only the processor
  directory misses them — that is how `a=90` (`DeleteUser`, deletes a user straight from
  `$_GET`), `a=92`, `a=52` and `a=26` were initially left unguarded.
- **`$_REQUEST` means GET works.** A processor whose UI only ever posts is still reachable by
  query string if it reads `$_REQUEST`, so it belongs in `MUTATING_GET_ACTIONS` too.

Already covered, do not add a second scheme: requests with no manager session (the login flow is
deliberately exempt), the theme AJAX endpoints that require `X-Requested-With: XMLHttpRequest`
plus POST (`manager/media/style/*/ajax.php`), and the file manager's own single-use
`checkToken()` / `makeToken()` pair in `core/functions/actions/files.php`.

Both halves are pinned by `core/tests/Unit/Security/ManagerCsrfCoverageTest.php`, which scans the
shipped views for untokenised forms and links. It checks against hand-maintained lists, so extend
those lists when you add an action — a green suite is not proof that a new action is guarded.

---

## Dependencies of Note

- **Laravel 12** components (illuminate/*) — container, ORM, routing, events, cache, queue
- **Pest 4** — test framework
- **Tracy 2** — debug/profiling bar
- **doctrine/dbal 4** — schema inspection for migrations
- **evolutioncms-services/document-manager** and **user-manager** — official service packages
- **phpmailer/phpmailer 7** — mail sending
- **guzzlehttp/guzzle 7** — HTTP client

---

## What to Avoid

- **Do not edit files in `core/src/Legacy/`** unless fixing a specific legacy bug. This code is excluded from static analysis and is being gradually deprecated.
- **Do not commit to `3.5.x` directly.** Open a PR.
- **Do not modify `core/config/`** for site-specific settings — use `core/custom/config/` instead.
- **Do not add files to `assets/cache/`** — it is auto-generated and git-ignored.
- **Do not use `$modx`** — it is deprecated. Use `evo()` helper.
- **Do not add a manager action that changes state without a CSRF token.** See *CSRF rules for manager actions*. A state-changing action reachable by GET must also be listed in `VerifyCsrfToken::MUTATING_GET_ACTIONS`.
- **PHPStan error count must not increase.** Run `composer analyze` before submitting a PR.
