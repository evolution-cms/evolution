"""HTTP isolation checks against an installed, disposable single-worker image."""
import http.cookiejar
import json
import os
import subprocess
import sys
import time
import urllib.error
import urllib.request
import urllib.parse

base = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8898'
container = sys.argv[sys.argv.index('--container') + 1] if '--container' in sys.argv else None
alias = sys.argv[sys.argv.index('--alias') + 1] if '--alias' in sys.argv else None
keep_app = '--keep-app' in sys.argv


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None


def client():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))


def get(path, opener=None, expected=200, headers=None):
    request = urllib.request.Request(base + path, headers=headers or {})
    try:
        response = (opener or client()).open(request, timeout=15)
    except urllib.error.HTTPError as error:
        response = error
    with response:
        body = response.read().decode()
        assert response.status == expected, (path, response.status, body[:500])
        return body, response.headers


if '--recycle' in sys.argv:
    workers = set()
    for _ in range(6):
        _, headers = get('/')
        workers.add(headers['X-Evo-Worker'])
        assert 1 <= int(headers['X-Evo-Request']) <= 2
    assert len(workers) == 3, workers
    print('PASS: workers recycle after exactly two requests')
    sys.exit(0)


for attempt in range(30):
    try:
        get('/')
        break
    except (OSError, AssertionError):
        if attempt == 29:
            raise
        time.sleep(1)

alice, bob = client(), client()
first, headers = get('/__worker/state', alice)
first = json.loads(first)
worker = headers['X-Evo-Worker']
assert worker
get('/__worker/login', alice)
get('/__worker/mutate', alice)
second, headers = get('/__worker/state', alice)
second = json.loads(second)
assert headers['X-Evo-Worker'] == worker
assert second['counter'] == 2 and second['user'] == 1, second
for key in ('placeholder', 'scripts', 'core_config', 'config', 'global', 'environment', 'locale', 'precision'):
    assert second[key] == first[key], (key, first, second)
guest, headers = get('/__worker/state', bob)
guest = json.loads(guest)
assert guest['counter'] == 1 and guest['user'] is None, guest
assert headers['X-Evo-Worker'] == worker
assert json.loads(get('/__worker/state', alice)[0])['user'] == 1
redirected, headers = get('/__worker/redirect', client())
assert json.loads(redirected)['redirected'] is True
assert headers['X-Evo-Worker'] == worker
assert 'worker-demo:' in get('/worker-demo')[0]
assert 'worker-demo:' in get('/__worker/forward')[0]
assert 'worker-error' in get('/missing-worker-demo', expected=404)[0]
get('/__worker/shutdown')
get('/__worker/transaction')
state, headers = get('/__worker/state')
state = json.loads(state)
assert state['shutdowns'] == first['shutdowns'] + 1, state
assert state['transaction'] == 'original', state
assert headers['X-Evo-Worker'] == worker
updated, _ = get('/__worker/update-setting')
state, headers = get('/__worker/state')
assert json.loads(state)['setting'] == updated
# A full cache clear calls opcache_reset(), which recent FrankenPHP versions use to recycle threads.
worker = headers['X-Evo-Worker']
for value in ('first payload', 'second payload'):
    request = urllib.request.Request(base + '/__worker/post', data=urllib.parse.urlencode({'value': value}).encode())
    with alice.open(request, timeout=15) as response:
        assert json.load(response)['value'] == value
        assert response.headers['X-Evo-Worker'] == worker
get('/', expected=421, headers={'Host': 'another-site.invalid'})
if alias:
    # Another EVO_SITE_HOSTNAMES entry is sent to the worker origin; the origin in another letter case is served.
    try:
        urllib.request.build_opener(NoRedirect).open(urllib.request.Request(base + '/worker-demo?x=1', headers={'Host': alias}), timeout=15)
        raise AssertionError('the alias hostname was not redirected')
    except urllib.error.HTTPError as error:
        assert error.status == 308 and error.headers['Location'] == base + '/worker-demo?x=1', (error.status, error.headers)
    upper = urllib.parse.urlsplit(base).netloc.upper()
    assert 'worker-demo:' in get('/worker-demo', headers={'Host': upper})[0]
get('/core/includes/worker.runtime.php', expected=404)
get('/core/composer.json', expected=404)
get('/assets/files/worker-test.php', expected=404)
_, manager_headers = get('/manager/index.php')
assert manager_headers.get('X-Evo-Worker') is None
error, _ = get('/__worker/failure', expected=500)
assert 'worker-test-private-detail' not in error
_, headers = get('/__worker/state')
assert headers['X-Evo-Worker'] != worker
worker = headers['X-Evo-Worker']
legacy = client()
assert get('/__worker/native-exit', legacy)[0] == 'legacy response'
state, headers = get('/__worker/state', legacy)
assert json.loads(state)['redirected'] is True
assert json.loads(state)['native_cleanup'] is True
assert headers['X-Evo-Worker'] != worker
worker = headers['X-Evo-Worker']
memory = json.loads(state)['memory']
for _ in range(100):
    state, headers = get('/__worker/state')
    state = json.loads(state)
    assert state['user'] is None and state['counter'] == 1, state
    assert headers['X-Evo-Worker'] == worker
assert state['memory'] <= memory + 8 * 1024 * 1024, (memory, state['memory'])
if keep_app:
    assert headers['X-Evo-Worker-App'] == 'kept', headers
if container:
    # A CLI process shares no OPcache with the worker, which must still read the rewritten site cache.
    subprocess.run([os.environ.get('DOCKER', 'docker'), 'exec', '-w', '/var/www/html', container, 'php',
                    '/tmp/worker-ci/external-setting.php', '/var/www/html', 'external'], check=True)
    state, headers = get('/__worker/state')
    assert json.loads(state)['setting'] == 'external', state
    assert headers['X-Evo-Worker'] == worker
print(('PASS (kept application): ' if keep_app else 'PASS: ') + 'persistent worker, session/auth isolation, request state reset, redirects, forwards, 404, cleanup, rollback, host guard, classic manager, errors, native exit, 100-request memory check'
      + (', alias redirect' if alias else '') + (', external settings change' if container else ''))
