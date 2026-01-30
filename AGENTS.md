# Repository Guidelines

## Architecture Overview
Evolution CMS (Evo) is a MODX Evolution legacy CMS modernized with a Laravel 12 runtime. Legacy parser/API behavior lives in `core/includes`, `core/functions`, and `core/src/Legacy`; modern services and app layers live in `core/src` with Illuminate components. The manager UI is Livewire + MaryUI (daisyUI + Tailwind v4) and ships from `manager/`.

## Project Structure & Module Organization
- `core/` Laravel runtime + Evo services. Key dirs: `core/src` (modern services), `core/src/Legacy` (compat layer), `core/includes` (legacy bootstrap/defines), `core/functions` (parser/actions/helpers), `core/config` (Laravel-style config), `core/custom` (project overrides; see `core/custom/*.example`), `core/database` (migrations/seeders), `core/storage` (cache/logs).
- `assets/` MODX-era ecosystem and public files: `assets/snippets`, `assets/plugins`, `assets/modules`, `assets/tvs`, `assets/templates`, plus `assets/js`, `assets/css`, `assets/images`, `assets/files`.
- `manager/` admin panel UI and assets; Tailwind entry/output at `manager/media/style/<theme>/css`. Manager integrations publish assets under `manager/media`.
- `install/` installer UI/controllers and environment checks.
- `views/` Blade templates for front-end; `index.php` is the front controller. Local config is `config.php` (copy from `config.php.example`).

## Specs & Legacy Documentation
- Product specs live in `PRD.md`, `SPEC.md`, and `TASKS.md` in the repo root. If you maintain a dedicated test plan, place it in `TESTS.md` alongside them.
- Legacy documentation for MODX/Evo APIs, events, snippets, TVs, and components is in `../docs` (see `../docs/en`, `../docs/ru`, `../docs/ua`).

## Build, Test, and Development Commands
- `composer install` installs PHP dependencies; `composer run analyze` runs PHPStan.
- `php core/artisan tailwind:build theme:default --force` builds manager Tailwind CSS.
- For MaryUI/Livewire local dev, add path repos in `core/composer.json` and run `composer require robsontenorio/mary livewire/livewire` (see `README.md`).

## EVO 5 Major Strategy (break-first)
- Goal: standard Laravel/MaryUI manager + clean core; we optimize for correctness, speed, and maintainability first.
- Compatibility work is **evidence-driven**: only after inventory + confirmed breakage; no “just in case” proxies.
- Legacy removal: if something is 100% dead, delete it; if a real case appears later, add a minimal adapter/proxy.
- Any compat layer must be isolated (`core/src/Legacy` or a dedicated compat namespace) and documented in `BREAKING_CHANGES.md` + `DEPRECATIONS.md`.
- Deprecated usage must be logged before expanding compatibility so we can prioritize by real impact.

## Baseline Invariants (non-negotiable for 5.0)
- Frame + iframe architecture stays: tree + main + resizer + mainframe are always present and functional.
- Critical DOM IDs must exist: `#mainMenu`, `#tree`, `#main`, `#resizer`, `#mainframe`, `#actions`, `#Button1`, `#mx_contextmenu`.
- Manager auth/ACL/i18n cannot be bypassed: `hasPermission`, `$_SESSION['mgrRole']`, `manager_language`, `$_lang`/lexicon.
- `/manager/api/*` must remain protected (auth + CSRF for state changes + validation + JSON-only).
- Deprecated usage is always logged (no compat/proxy work without evidence).

## Release Gate (minimum)
- PHPStan (`composer run analyze`) + Tailwind build for manager.
- Smoke scenarios: login, frame shell load, iframe navigation, tree actions, save hotkey.
- Contract-test for critical DOM IDs (`#mainMenu`, `#tree`, `#main`, `#resizer`, `#mainframe`, `#actions`, `#Button1`, `#mx_contextmenu`).
- Doc-sync rule: any code change that affects behavior/contracts/routing/flows/deps must include matching doc updates in PRD/SPEC/TASKS/TESTS/BREAKING_CHANGES/DEPRECATIONS/COMPAT_LOGGING/README. A PR without doc updates is not ready.

## Doc Impact Checklist (required)
- Routing/entrypoints changed → update `PRD.md`, `SPEC.md`, `TASKS.md`, `BREAKING_CHANGES.md`.
- DOM IDs / JS hooks changed → update `SPEC.md` (NORMATIVE DOM/JS) + `TESTS.md` (Contract Test).
- Compat/proxy/adapter added/changed → update `DEPRECATIONS.md` + `COMPAT_LOGGING.md`.
- Build/deps changed (composer/node/tailwind) → update `README.md` + `AGENTS.md` build commands.
- Security changes (/manager/api or iframe allowlist) → update `SPEC.md` (NORMATIVE security) + `TASKS.md` security stream.

## Doc-sync Playbook (control scenarios)
- Routing change (`/manager/*`, entrypoints) → update `PRD.md` (Scope/Routing), `SPEC.md` (Routing), `TASKS.md` (Routing streams), `BREAKING_CHANGES.md` (status=done).
- DOM/JS contract change (IDs/hooks) → update `SPEC.md` (NORMATIVE DOM/JS) + `TESTS.md` (Contract Test IDs).
- API surface change (`/manager/api/*`) → update `SPEC.md` (Security requirements) + `TASKS.md` (Security stream) + `BREAKING_CHANGES.md` if breaking.
- Theme system change (sync, storage keys) → update `SPEC.md` (Theme sync) + `TESTS.md` if behavior changes.
- Icon system change (MaryUI vs style.php) → update `PRD.md` + `BREAKING_CHANGES.md`.

## Ecosystem Packages & Integration (Workspace)
- This repo is part of a local workspace in `../` with Evo packages used via Composer path repos and `vendor:publish`.
- Key local packages: `eTinyMCE`, `eCodemirror`, `eFilemanager`, `ePasskeys`, `sLang`, `sGallery`, `sSettings`, `sSeo`, `sOffers`, `sTask`, `sCommerce`, `example-package`, `evoDemo`, `mary`, `livewire`.
- Local paths used in this workspace:
  - `mary` (MaryUI): `../mary`
  - `paper.mary-ui.com`: `../paper.mary-ui.com`
  - `multifields` (workspace): `../multifields-master`
  - `templatesedit3` (workspace): `../templatesedit3-3.1.x`

## Coding Style & Naming Conventions
- Follow PSR-12-like PHP style, 4-space indentation, and existing namespace conventions.
- Keep new framework code in `core/src`; touch `core/src/Legacy` only when maintaining legacy behavior.

## Testing Guidelines
There is no automated test suite in this repo. Run PHPStan and perform manual smoke tests for installer, manager UI, and any affected modules or legacy flows.

## Commit & Pull Request Guidelines
- Commit messages use short tags: `[FIX]`, `[UPD]`, `[DEL]` + description.
- PRs should include scope, test steps, linked issues (if any), and screenshots for UI changes.

## Docs Index
- `PRD.md` — product requirements and scope
- `SPEC.md` — technical specification
- `TASKS.md` — execution plan
- `TESTS.md` — smoke checklist + contract test
- `BREAKING_CHANGES.md` — major change log
