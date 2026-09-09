# Install and upgrade checks

Two scripts, one set of assertions:

- `install-and-smoke.sh` installs this tree onto a database and checks what it
  put there, then runs the updater over it to catch anything not idempotent.
- `upgrade-and-smoke.sh` installs an **older release**, copies this tree over it
  the way unpacking a release archive would, and runs the updater. That is the
  only check that replays the migration chain over a schema this branch did not
  create - what every existing site does when it moves to a new release.

Both take a `sqlite`, `mysql` or `pgsql` database from `EVO_DB_TYPE`.

## Installing

Run either database leg from the repository root:

```sh
docker compose -f .github/docker/ci/docker-compose.yml --profile mysql up \
    --build --abort-on-container-exit --exit-code-from install-mysql

docker compose -f .github/docker/ci/docker-compose.yml --profile pgsql up \
    --build --abort-on-container-exit --exit-code-from install-pgsql
```

Clean up with `docker compose -f .github/docker/ci/docker-compose.yml --profile
<mysql|pgsql> down -v`. The container works on a copy of the tree, not a bind
mount, so a run leaves the working tree alone.

sqlite needs no container at all:

```sh
EVO_DB_TYPE=sqlite EVO_APP_DIR="$PWD" .github/docker/ci/install-and-smoke.sh
```

`PHP_VERSION`, `MYSQL_IMAGE_TAG`, `POSTGRES_IMAGE_TAG`, `MYSQL_PORT` and
`POSTGRES_PORT` override the defaults (PHP 8.3, MySQL 9.7 LTS, PostgreSQL 18.6
LTS, the servers' own ports).

## Upgrading

`upgrade-and-smoke.sh` needs two trees: the older one it installs and upgrades
in place, and this one to copy over it. The older tree is left dirty, so give it
a checkout that can be thrown away.

```sh
git clone --depth 1 --branch 3.5.7 https://github.com/evolution-cms/evolution /tmp/evo-3.5.7
EVO_FROM_DIR=/tmp/evo-3.5.7 EVO_NEW_DIR="$PWD" EVO_DB_TYPE=mysql EVO_DB_HOST=127.0.0.1 EVO_DB_USER=root EVO_DB_PASSWORD=secret     .github/docker/ci/upgrade-and-smoke.sh
```

Two things about the source version are worth knowing:

- **No released version installs on sqlite.** `cli-install.php` offered only
  `pgsql` and `mysql` through 3.5.7; the sqlite branch is new in this tree. So a
  sqlite upgrade leg has to take its source from a ref that carries it - the
  `nightly-3.5.x` tag, or any 3.5.x commit after it landed. A tag works here
  like any other ref once a release ships sqlite support.
- **No `--skipComposer` before 3.5.8 either.** An older `composerUpdate()` runs
  whatever it finds at `core/vendor/bin/composer` and only warns when that is
  missing, so the script moves the shim aside: `core/vendor` is committed at
  every tag, and a composer update would test the network rather than the CMS.

The source install is checked for hard failures only. A release run on a newer
PHP may emit deprecations that are not this branch's to fix, and they are not
what is under test - the updater's own log is checked strictly.

## What CI runs

The `install` job in `.github/workflows/ci.yml` does not use the image above.
It starts only the database service from this compose file — an official image,
pulled, never built — and runs `install-and-smoke.sh` directly on the runner,
whose PHP `shivammathur/setup-php` already provides with `pdo_mysql` and
`pdo_pgsql` prebuilt. Building the image there would spend two or three minutes
per leg compiling extensions the runner hands over ready-made, on every push.

The script only needs `EVO_DB_*` and a reachable server, so both paths run the
same checks; the image is what makes a local run reproducible on a machine with
no PHP on it, and what pins the PHP version when a version question is the one
being investigated.

One PHP version, every database: what the job proves is the installer's database
paths, which do not vary with the PHP minor. 8.3 and 8.4 are both covered by the
analysis and unit test jobs. The sqlite leg starts no container.

The upgrade checks live in their own workflow, `.github/workflows/upgrade.yml`,
off `push` and `pull_request` on purpose: an upgrade regression comes from a
change to the migrations or the seeders, not from every commit, and each leg
costs a full install plus a full update. It runs nightly, and on demand with the
versions to go from and to as inputs (Actions -> Upgrade -> Run workflow):
`from` takes one or more refs of any repository, `to` a ref of this one or blank
for the checked out tree, which is the default - so the unreleased version is
what an upgrade is tested against unless you say otherwise.

## What a run asserts

`install-and-smoke.sh` drives it:

1. The installer creates the database itself — neither server image pre-creates
   the one being installed into — and runs the whole migration and seed chain.
2. Nothing PHP would call a diagnostic was printed. The installer runs the
   migrations inside an output buffer it later discards, so the image also
   routes `error_log` to stderr; a warning raised during a migration cannot be
   swallowed.
3. `smoke.php --mode=install` checks what reached the database, reading the connection the
   installer just wrote: every table created, the columns the core migrations
   add, the seeded document, template, event names, settings, roles and
   permissions, the admin account with a hashed password and the Administrator
   role, and the bundled plugins and modules.
4. The updater (`--typeInstall=2`) replays the same chain over the installed
   site, and `smoke.php` runs again — its row counts have to match the first
   run, which is what catches a migration or seeder that is not idempotent.
5. The site answers over HTTP: the front page renders the seeded document, and
   the manager renders its login form.

An upgrade run asserts the same things through `smoke.php --mode=upgrade`, with
the expectations that belong to a fresh install relaxed - the content and the
settings are whichever ones the older site had, not the ones this installer
would have chosen. Two checks replace them, and they are the point of the mode:

- the counts recorded from the older site by `--mode=baseline`, taken before the
  copy, must not have **dropped** anywhere. A seeded catalogue may grow, because
  topping those up is what the update seeder is for; losing rows is a bug.
- `site_content`, `users` and `user_attributes` must be **unchanged**. Those are
  the site, not the release, and an upgrade may not touch them.

`manager_language` and `emailsender` stay exact in both modes: they are answers
the operator gave the installer, and a seeder writing defaults over them would
reset a live site.
