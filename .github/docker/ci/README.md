# Install checks on MySQL and PostgreSQL

Installs Evolution CMS onto a real database server and asserts the result, so
the MySQL and PostgreSQL paths of `install/cli-install.php` get the coverage the
sqlite path already gets from the build workflow.

Run either leg from the repository root:

```sh
docker compose -f .github/docker/ci/docker-compose.yml --profile mysql up \
    --build --abort-on-container-exit --exit-code-from install-mysql

docker compose -f .github/docker/ci/docker-compose.yml --profile pgsql up \
    --build --abort-on-container-exit --exit-code-from install-pgsql
```

Clean up with `docker compose -f .github/docker/ci/docker-compose.yml --profile
<mysql|pgsql> down -v`. The container works on a copy of the tree, not a bind
mount, so a run leaves the working tree alone.

`PHP_VERSION`, `MYSQL_IMAGE_TAG` and `POSTGRES_IMAGE_TAG` override the defaults
(PHP 8.4, MySQL 9.7 LTS, PostgreSQL 18.6 LTS). CI runs both databases against
PHP 8.3 and 8.4 — see the `install` job in `.github/workflows/ci.yml`.

## What a run asserts

`install-and-smoke.sh` drives it:

1. The installer creates the database itself — neither server image pre-creates
   the one being installed into — and runs the whole migration and seed chain.
2. Nothing PHP would call a diagnostic was printed. The installer runs the
   migrations inside an output buffer it later discards, so the image also
   routes `error_log` to stderr; a warning raised during a migration cannot be
   swallowed.
3. `smoke.php` checks what reached the database, reading the connection the
   installer just wrote: every table created, the columns the core migrations
   add, the seeded document, template, event names, settings, roles and
   permissions, the admin account with a hashed password and the Administrator
   role, and the bundled plugins and modules.
4. The updater (`--typeInstall=2`) replays the same chain over the installed
   site, and `smoke.php` runs again — its row counts have to match the first
   run, which is what catches a migration or seeder that is not idempotent.
5. The site answers over HTTP: the front page renders the seeded document, and
   the manager renders its login form.
