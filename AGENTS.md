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

## Ecosystem Packages & Integration (Workspace)
- This repo is part of a local workspace in `../` with Evo packages used via Composer path repos and `vendor:publish`.
- Key local packages: `eTinyMCE`, `eCodemirror`, `eFilemanager`, `ePasskeys`, `sLang`, `sGallery`, `sSettings`, `sSeo`, `sOffers`, `sTask`, `sCommerce`, `example-package`, `evoDemo`, `mary-main`, `livewire`.

## Coding Style & Naming Conventions
- Follow PSR-12-like PHP style, 4-space indentation, and existing namespace conventions.
- Keep new framework code in `core/src`; touch `core/src/Legacy` only when maintaining legacy behavior.

## Testing Guidelines
There is no automated test suite in this repo. Run PHPStan and perform manual smoke tests for installer, manager UI, and any affected modules or legacy flows.

## Commit & Pull Request Guidelines
- Commit messages use short tags: `[FIX]`, `[UPD]`, `[DEL]` + description.
- PRs should include scope, test steps, linked issues (if any), and screenshots for UI changes.
