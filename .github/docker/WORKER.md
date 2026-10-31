# Experimental FrankenPHP frontend workers

The default image still serves each request in classic PHP mode. On a FrankenPHP
image, opt in with:

```bash
docker.exe run -d --name evo-worker -p 8080:80 \
  -e EVO_WORKER_MODE=1 \
  -e EVO_WORKER_ORIGIN=http://localhost:8080 \
  ghcr.io/evolution-cms/evolution:3.5.x-php8.4-frankenphp
```

This command requires an image built with the worker implementation. The site
must already be installed. `EVO_WORKER_ORIGIN` is the exact public scheme, hostname
and port, without a path. Behind TLS termination, configure the trusted proxy to
send the public Host and X-Forwarded-Proto.

## Hostnames

The first request of a worker fixes `EVO_SITE_URL` and the constants derived from
it, so a worker serves exactly one origin:

- A request for `EVO_WORKER_ORIGIN` is served. Its Host header is replaced by the
  configured one first, so a client cannot choose the letter case of the site URL
  (and of the pages cached from it).
- A request for another entry of `EVO_SITE_HOSTNAMES` with the same scheme receives
  a 308 redirect to the same path on `EVO_WORKER_ORIGIN` (`www.example.com` to
  `example.com`). Classic PHP serves every listed hostname under its own name;
  a worker makes the origin canonical. Another scheme is not redirected: behind
  a proxy that drops X-Forwarded-Proto, that would loop.
- Any other origin receives 421.

When `EVO_SITE_HOSTNAMES` (environment, `.env` or `core/custom/define.php`) does
not list `EVO_WORKER_ORIGIN`, or a defined `EVO_SITE_URL` points elsewhere, the
worker refuses to start: its pages would link to a site it does not serve. Run
separate worker instances for unrelated origins.

## Application lifetime

The PHP worker, autoloader and loaded classes survive requests. By default each
request gets a **new Evo container**, providers, settings, parser, authorization
and session. It does not install or require Laravel Octane.

`EVO_WORKER_KEEP_APP=1` keeps the booted container instead (experimental). Providers
register and boot once per worker; each request starts from that post-boot state
and builds its own settings, parser, authorization and session as before. After
every request the properties of the container, of Core and of every service
resolved while providers booted (and of the objects those services hold directly,
such as the view finder and the route collection) return to their post-boot values,
so whatever a request bound, resolved, registered or set on them is dropped. Kept
services with a public `flushState()` (Laravel's and Livewire's convention) or
Symfony's `ResetInterface` are reset through it. In debug mode, responses carry
`X-Evo-Worker-App: new|kept` and the log lists the kept services.

A provider that reads the request, the session or the user while booting sees
only the first request of each worker in this mode. An extension service resolved
during boot that keeps request state deeper than one object level must implement
one of the reset hooks. Test the installed extensions with this mode before using
it in production; without it, workers behave as described above.

Only the root `index.php` is registered as a worker. Manager, installer and other
PHP entry points remain classic. `EVO_WORKER_MODE=1` alone does not activate the
runtime under Apache, FPM, CLI or a classic FrankenPHP request. To disable workers,
remove the setting and restart the container.

Settings:

| Environment variable | Default | Meaning |
| --- | --- | --- |
| `EVO_WORKER_COUNT` | `2` | Number of frontend workers in the supplied Caddyfile |
| `EVO_WORKER_MAX_REQUESTS` | `500` | Recycle each worker after this many requests; 1–100000 |
| `EVO_WORKER_KEEP_APP` | off | `1` keeps one booted container per worker (see above) |
| `EVO_WORKER_DEBUG` | off | `1` adds X-Evo-Worker, X-Evo-Request and X-Evo-Worker-App response headers |

With a custom Caddyfile, register `/var/www/html/index.php` as the worker and
retain the normal PHP handler for the manager. The supplied `worker.Caddyfile`
contains friendly-URL rewrites and blocks private CMS files. Keep equivalent
access rules when adapting it to another document root or HTTPS.

## Extension compatibility

Core request exits use `evo_exit($status)` and request shutdown jobs use
`evo_register_shutdown($callback)`. In classic PHP these delegate to `exit` and
`register_shutdown_function`. In a worker, exit raises a request termination
signal; cleanup runs once at the end of that request. Shutdown callbacks run
before returning the response to FrankenPHP, so they must not rely on the
connection already being detached. Core deferred jobs retain their existing
finish-request call where the SAPI supports it.

A legacy snippet/plugin that calls native `exit` still finishes its response, but
restarts the worker and loses the reuse benefit. Native shutdown handlers execute
when that worker ends, not on each request; migrate per-request jobs to the helper.
An extension must not catch and swallow `EvolutionCMS\Runtime\RequestTerminated`
when catching `Throwable` or `Error`.

The worker resets CMS singletons, model boot state, session flags, global request
variables, environment superglobal changes, mutable PHP INI settings, error handlers and timezone;
open database transactions are rolled back and connections closed. With a new
container per request, it also empties the static callback lists that providers
append to on every boot (Artisan bootstrappers, the TrimStrings and
ConvertEmptyStringsToNull skip callbacks), whose closures would otherwise keep every
previous container in memory. Exceptions
return a generic 500, are logged server-side and recycle the worker. Project PHP
statics, function statics, constants, custom autoloaders and process-wide changes
made by extensions cannot be reset automatically. Validate installed extensions
before enabling workers on a production site. Restart workers after code,
custom definitions or environment changes; CMS cache/settings changes are read
again on the next request. OPcache revalidates timestamps against the request start
time, which a worker does not advance, so before every request the worker rechecks
the PHP cache files that another process may rewrite (site cache, publishing
schedule, env cache): a change saved through the CLI or another server is seen at
once. Other PHP files, such as custom routes, keep their compiled version until the
worker restarts. A full CMS cache clear also calls `opcache_reset()`; FrankenPHP
versions that restart PHP threads on that call will recycle workers.

## Measurements

Local Docker Desktop, PHP 8.4.24, SQLite, one FrankenPHP image with the default
providers (including EvoUI, Livewire, dTui editor, sGallery and sTask) in all three
modes; one keep-alive connection, 30 warmup requests followed by 300 sequential
requests per page. These measure latency, not concurrent throughput:

| Page | Classic | Worker | Worker, kept app | Kept vs classic |
| --- | ---: | ---: | ---: | ---: |
| Cached homepage | 7.69 ms | 6.87 ms | 3.33 ms | 2.31x |
| Uncached page with a CMS snippet | 9.14 ms | 6.99 ms | 3.90 ms | 2.35x |
| Cached page with a cached snippet | 6.84 ms | 5.69 ms | 2.24 ms | 3.05x |

Of about 5 ms of PHP time per warm worker request, registering and booting the
providers took 3.4 ms; a kept application skips them. The new-container mode saves
autoloader and class-loading work only. Measure a representative site before
choosing a mode; these results do not predict gains for SQL-heavy pages.

The FrankenPHP CI legs run `ci/worker-smoke.py` against a disposable seeded
single-worker container, once per application lifetime, including alternating
user/guest sessions, state reset, redirects, forwards, a real 404, transaction
rollback, native exit, exception recovery, a classic manager, a 100-request memory
check, the hostname redirect and a settings change made by a CLI process. A second container
verifies recycling after exactly two requests. Test routes are installed only by
`ci/seed-worker.php` and must never be copied into a production site's custom routes.
