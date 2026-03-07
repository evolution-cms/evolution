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
[ADD]  New feature or file
[FIX]  Bug fix
[REF]  Refactor / code quality improvement
[UPD]  Dependency or content update
[DEL]  Removal of code or files
```

Examples from the log:
- `[FIX] Fix confusing dropdown showing rich text editor when editor is disabled`
- `[REF] Deprecation warnings for store installer.`
- `[ADD] Add command to list the code marked as deprecated`

Always use one of these prefixes. Write short, imperative present-tense descriptions.

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
php core/artisan site:update             # run pending migrations / updates
php core/artisan package:extras          # manage extras/packages
php core/artisan deprecated:list         # list deprecated code by semver
php core/artisan translations:sync       # sync translation files
php core/artisan doc:list                # list content documents
php core/artisan route:list              # list registered routes
php core/artisan tpl:list                # list registered templates
php core/artisan tv:list                 # list registered TVs
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
- **Do not use `$modx`** — it is deprecated. Use `EvolutionCMS()` or the `evo()` helper.
- **PHPStan error count must not increase.** Run `composer analyze` before submitting a PR.