# Repository Guidelines

## Project Structure & Module Organization
- `core/` holds the PHP framework and CMS runtime. Main source lives in `core/src/`, with legacy code isolated in `core/src/Legacy`.
- `manager/` contains the admin UI and its assets, including Tailwind entry/output files under `manager/media/style/<theme>/css`.
- `views/` contains front-end templates.
- `assets/` contains public assets and package-specific resources.
- `install/` contains the installer and setup controllers.
- Repo root includes the front controller `index.php`, a configuration template `config.php.example`, and a local dev DB `database.sqlite`.

## Build, Test, and Development Commands
- `composer install` installs PHP dependencies for the project.
- `composer run analyze` runs PHPStan static analysis (configured for `core/src`).
- `npm i -D tailwindcss@^4 daisyui@latest @tailwindcss/typography` installs manager UI build tooling.
- `php core/artisan tailwind:build theme:default --force` builds manager Tailwind CSS into `manager/media/style/default/css/tailwind.min.css`.
- Runtime: serve the repo root with PHP 8.3+ and point your web server to `index.php`.

## Coding Style & Naming Conventions
- Follow the existing PHP style: namespaces, 4-space indentation, and PSR-12-like formatting.
- Use PascalCase for classes and camelCase for methods/properties, matching `core/src` conventions.
- Keep new framework code in `core/src`; touch `core/src/Legacy` only when maintaining legacy behavior.

## Testing Guidelines
- There is no automated test suite in this repository.
- Run `composer run analyze` before shipping changes and perform manual smoke tests for installer, manager UI, and front-end flows relevant to your change.

## Commit & Pull Request Guidelines
- Commit messages follow a short bracketed tag + description pattern, e.g. `[FIX] handle empty theme`, `[UPD] styles`, `[DEL] remove unused files`.
- PRs should include a concise summary, explicit test steps, linked issues (if any), and screenshots for UI changes (manager or views).

## Configuration & Security Tips
- Copy `config.php.example` to `config.php` for local configuration; never commit secrets.
- Treat `database.sqlite` as local-only data; avoid committing production or sensitive databases.
