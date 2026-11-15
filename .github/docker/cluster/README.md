# Cluster check

Two Evolution CMS nodes (`php1`, `php2`) behind a round-robin nginx
(`balancer`), sharing one PostgreSQL database (`pgsql`), one Redis that holds
the PHP sessions (`redis`), and the folders below. Runs on demand in
`.github/workflows/cluster.yml` (Actions -> Cluster -> Run workflow).

The node image is the published one: `.github/docker/Dockerfile` on a Salo
apache runtime, with phpredis (and exif, where missing) added by
`Dockerfile.runtime`. The site is
installed once into `build/` and that installed release is baked into the
image, so both nodes run identical code and configuration, the way a
deployment ships one release to every server.

## What a cluster has to share

A folder must be shared when one node writes to it and the other has to see
it. Everything else stays in the image.

| Path | Why it is shared |
|---|---|
| `assets/images`, `assets/files`, `assets/media` | Uploads of both file managers. A file uploaded on one node is linked from pages either node serves. |
| `assets/image`, `assets/file` | The TinyMCE names of the same KCFinder types; KCFinder creates them on first use. |
| `assets/.thumbs` | KCFinder thumbnails of those uploads. |
| `assets/cache` | phpThumb output (`cache/images`), package store staging (`cache/store`), updater and RSS caches. |
| `assets/backup` | Database snapshots: a snapshot taken on one node has to be listed and restorable on the other. Also any `snapshot_path` moved elsewhere, such as `temp/backup`. |
| `assets/export`, `assets/import` | Static export output and import input. |
| `core/storage/bootstrap` | `siteCache.idx.php`, `sitePublishing.idx.php` and the page cache (`docid_*.pageCache.php`). Saving a document or refreshing the site on one node purges them; a node with its own copy keeps serving the old page and settings. |
| `core/storage/cache` | Laravel's file cache store (`CACHE_DRIVER=file`), including its locks, and `env.php`, the compiled configuration (see below). |
| `core/storage/logs` | One log for the site rather than half of it per node, and `sTask.heartbeat`: the worker on the scheduler node touches it, and the manager on any node reads it to tell whether the queue is alive. |
| `core/storage/stask` | Task logs, files uploaded for a task and the files a task produces (exports): the manager that takes or offers them may run on a node other than the one running the worker. |

Not shared:

| Path | Why not |
|---|---|
| `core/storage/sessions`, PHP's `session.save_path` | Sessions are in Redis (`SESSION_STORAGE=redis`). File sessions would need this folder shared instead, with locking the shared file system may not provide. |
| `core/storage/blade` | Compiled views. Blade writes them in place, keyed on the template file time, and identical code compiles identical views; a node reading a view the other node is still writing would run half of it. |
| Code: `core/` (`vendor`, `custom`, `config`), `manager/`, `assets/plugins`, `assets/snippets`, `assets/modules`, `assets/templates`, `assets/tvs`, `assets/js`, `views/` | Part of the release. Installing an extra from the manager, `package:installrequire`, `make:site update` or editing these with the file manager changes one node only: on a cluster, make those changes in the release and redeploy every node. |

With more than one host, the shared folders go on a network file system (NFS,
EFS, a CSI volume). Page cache rebuilds lock `siteCache.idx.php.lock` with
`flock()`, which some network file systems do not honour across hosts.

Other things one node cannot decide for the cluster:

- **Compiled configuration**: the first request after a full cache clear
  compiles every config file, with the `env()` values it sees at that moment,
  into `core/storage/cache/env.php`, and every node reads that file from then
  on. Give all nodes the same configuration environment, and clear the cache
  (`php core/artisan cache:clear-full`, or Refresh site in the manager) after
  changing it; restarting the containers with new variables is not enough.
- **Scheduler**: exactly one node runs `php artisan schedule:work`
  (`EVO_SCHEDULER=0` on the others, see `../entrypoint.sh`); here `php1`.
- **Session cookie name**: derived from the install path, so every node must
  serve the site from the same path (or set `SESSION_COOKIE_NAME`).
- **Database configuration**: the installer writes it into
  `core/config/database/connections/default.php`, which is why the site is
  installed before the image is built rather than on a node.

## Sessions in Redis

Both ways the CMS can keep sessions in Redis are configured on the nodes, and
`EVO_SESSION` picks the one in effect. The workflow runs both unless told
otherwise (input `session`):

| `EVO_SESSION` | Session store | Settings |
|---|---|---|
| `1` (default) | Laravel's session store, which the CMS mirrors into `$_SESSION` | `SESSION_DRIVER=redis`, `SESSION_CONNECTION=default`, the `redis.default` connection of `core/config/database.php` (`REDIS_HOST`, `REDIS_PORT`, `REDIS_USER`, `REDIS_PASS`, `REDIS_DATABASE`) |
| `0` | PHP's native session with the Redis handler of `startCMSSession()` | `SESSION_STORAGE=redis`, `REDIS_HOST`, `REDIS_PORT`, `REDIS_USER`, `REDIS_PASS`, `REDIS_SESSION_DATABASE` |

`SESSION_STORAGE=redis` alone does nothing while `EVO_SESSION` is on (the
default), and leaves the sessions in files on each node.

## What a run asserts

`smoke.sh`, through the balancer:

1. Consecutive requests alternate between the nodes, and both render the
   seeded document.
2. Every shared path is mounted on both nodes and writable by `www-data`;
   `core/storage/blade` and `/tmp` are not shared.
3. A manager login on one node is a valid session on the other; the session is
   in Redis and neither node keeps it in a file.
4. The file manager's single-use token, issued on one node, is accepted by an
   upload on the other, and both nodes serve the uploaded file.
5. With the document changed in the database, both nodes still serve the
   cached page; a site refresh on one node purges it for both.
6. A logout on one node ends the session on the other.
7. Only `php1` runs the scheduler.

Taking `storage-bootstrap` off the nodes fails step 5, and
`SESSION_DRIVER=file` fails step 3.

The same workflow also runs the Redis session integration tests
(`RedisSessionStorageTest`, `RedisConnectionConfigTest`) against a Redis
service, in `Dockerfile.redis-tests`: PHP CLI with a phpredis built with lzf,
zstd and lz4, so the compressed session cases run instead of skipping. Both
jobs keep their output as artifacts (`cluster-smoke-<mode>`,
`redis-session-tests`, with a JUnit report).

## Running it locally

From the repository root, with Docker and a clone of
`evolution-cms/salo2`:

```sh
export PHP_VERSION=8.4 CLUSTER_PORT=8080 EVO_SESSION=1
COMPOSE=.github/docker/cluster/docker-compose.yml

docker build -t evo-salo-runtime:${PHP_VERSION}-apache ../salo2/runtimes/${PHP_VERSION}
docker compose -f $COMPOSE --profile install build installer
docker compose -f $COMPOSE up -d --wait pgsql redis
bash .github/docker/cluster/prepare.sh
# 3.6 and later only, where core/vendor is not committed:
EVO_UID=$(id -u) EVO_GID=$(id -g) docker compose -f $COMPOSE --profile install run --rm --no-deps \
    -e COMPOSER_HOME=/tmp/composer -w /var/www/html/core --entrypoint composer \
    installer install --no-dev --no-interaction --prefer-dist
EVO_UID=$(id -u) EVO_GID=$(id -g) docker compose -f $COMPOSE --profile install run --rm installer
docker compose -f $COMPOSE build php1
docker compose -f $COMPOSE up -d --wait php1 php2 balancer
bash .github/docker/cluster/smoke.sh
```

The site is then at `http://127.0.0.1:8080/` (manager `admin` /
`Passw0rd123`). `build/` is a scratch copy of the tree; remove it together
with the cluster:

```sh
docker compose -f $COMPOSE --profile install down -v && rm -rf build
```

`DOCKER=docker.exe` makes `smoke.sh` use another Docker client binary, and
`COMPOSE_FILE` points it at the compose file by another path (a Windows one,
say). To switch session mode on a running cluster, recreate the nodes with the
new `EVO_SESSION` and clear the cache, for the compiled configuration above.
