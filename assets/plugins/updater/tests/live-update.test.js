// Runs the live update script of plugin.updater.php against a minimal DOM: node --test
const test = require('node:test');
const assert = require('node:assert');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'plugin.updater.php'), 'utf8');
const match = source.match(/<<<'HTML'\r?\n<script>\r?\n([\s\S]*?)<\/script>\r?\nHTML;/);
assert.ok(match, 'live update script not found in plugin.updater.php');
const script = match[1];

function element(tag) {
    const el = {
        tagName: tag, id: '', innerHTML: '', textContent: '', children: [], parentNode: null, listeners: {},
        appendChild(child) { child.parentNode = el; el.children.push(child); return child; },
        removeChild(child) { el.children = el.children.filter((c) => c !== child); child.parentNode = null; },
        addEventListener(type, fn) { el.listeners[type] = fn; },
        querySelector(selector) {
            if (selector === '[data-role=content]') {
                el.content = el.content || element('div');
                return el.content;
            }
            return null;
        },
    };
    return el;
}

function setup({ activeTask = null, responses = [] } = {}) {
    const body = element('body');
    const head = element('head');
    const requests = [];
    const timers = [];
    const intervals = [];
    const clock = { now: 1_000_000 };
    let reloaded = 0;
    const payload = element('script');
    payload.textContent = JSON.stringify({ config: { token: 't', csrfToken: 'c', activeTask }, labels: {} });

    const document = {
        body, head,
        getElementById(id) {
            if (id === 'updater-system-task-payload') return payload;
            return [...body.children, ...head.children].find((c) => c.id === id) || null;
        },
        createElement: element,
        querySelector: () => null,
    };
    const context = {
        document,
        Date: { now: () => clock.now, parse: Date.parse },
        setInterval(fn) { intervals.push(fn); return intervals.length; },
        clearInterval(handle) { intervals[handle - 1] = null; },
        setTimeout(fn, ms) { timers.push({ fn, ms }); return timers.length; },
        clearTimeout(handle) { if (timers[handle - 1]) timers[handle - 1].fn = null; },
        location: { reload() { reloaded++; } },
        fetch(url, options) {
            const action = /updater_task_action=(\w+)/.exec(options.body)[1];
            requests.push({ action, body: options.body });
            const next = action === 'refresh_session' ? JSON.stringify({ ok: true, changed: true }) : responses.shift();
            if (next instanceof Error) return Promise.reject(next);
            return Promise.resolve({ text: () => Promise.resolve(next === undefined ? '' : next) });
        },
    };
    context.window = context;
    context.top = context;
    vm.createContext(context);
    vm.runInContext(script, context);

    const modal = () => body.children.find((c) => c.id === 'updater-system-task-modal');
    return {
        context, requests, clock,
        reloads: () => reloaded,
        text: () => (modal() && modal().content ? modal().content.innerHTML : ''),
        pending: () => timers.filter((t) => t.fn),
        ticking: () => intervals.filter(Boolean).length,
        async settle() {
            for (let i = 0; i < 20; i++) await new Promise((r) => setImmediate(r));
        },
        async tick() {
            const due = timers.filter((t) => t.fn);
            due.forEach((t) => { const fn = t.fn; t.fn = null; fn(); });
            await this.settle();
        },
    };
}

const task = (status, extra = {}) => ({ id: 5, status, step: status === 'succeeded' ? 'completed' : 'site_update', progress: status === 'succeeded' ? 100 : 40, message: status, logs: [], ...extra });
const answer = (status) => JSON.stringify({ ok: true, task: task(status) });

test('a reloaded page follows the running update instead of offering a new one', async () => {
    const page = setup({ activeTask: task('running'), responses: [answer('running')] });
    await page.settle();

    assert.deepStrictEqual(page.requests.map((r) => r.action), ['result']);
    assert.match(page.requests[0].body, /task_id=5/);
    assert.match(page.text(), /running/);
    assert.doesNotMatch(page.text(), /data-role="confirm"/);
    assert.strictEqual(page.pending().length, 1, 'keeps polling');

    // the widget button shows the same task rather than the confirm dialog
    page.context.EvoUpdaterSystemTask.openConfirm();
    assert.doesNotMatch(page.text(), /data-role="confirm"/);
});

test('polls lost while the update replaces the manager are retried until the task finishes', async () => {
    const page = setup({
        activeTask: task('running'),
        responses: [new Error('Failed to fetch'), '<b>Fatal error</b>: Class not found', answer('running'), answer('succeeded')],
    });
    await page.settle();

    assert.match(page.text(), /did not answer while the update replaces its files/);
    assert.strictEqual(page.pending().length, 1, 'retries after a network error');

    await page.tick();
    assert.match(page.text(), /did not answer/);
    assert.strictEqual(page.pending().length, 1, 'retries after a non-JSON answer');

    await page.tick();
    assert.match(page.text(), /Status<\/small><br><strong>running/);

    await page.tick();
    assert.match(page.text(), /Update completed/);
    assert.deepStrictEqual(page.requests.map((r) => r.action), ['result', 'result', 'result', 'result', 'refresh_session']);
    assert.strictEqual(page.pending().length, 0, 'stops once the task finished');
});

test('the operator is told to reload only after the manager stayed silent for the whole retry window', async () => {
    const page = setup({ activeTask: task('running'), responses: Array.from({ length: 50 }, () => new Error('down')) });
    await page.settle();

    page.clock.now += 9 * 60 * 1000;
    await page.tick();
    assert.match(page.text(), /did not answer/);
    assert.strictEqual(page.pending().length, 1);

    page.clock.now += 2 * 60 * 1000;
    await page.tick();
    assert.match(page.text(), /The manager response changed while the update was running/);
    assert.strictEqual(page.pending().length, 0, 'gives up');
});

test('closing the window stops polling and reloads the whole manager after a lost poll', async () => {
    const page = setup({ activeTask: task('running'), responses: [new Error('down')] });
    await page.settle();

    page.context.EvoUpdaterSystemTask.close();
    assert.strictEqual(page.pending().length, 0);
    assert.strictEqual(page.reloads(), 1);
});

test('while the manager answers, the window shows how long ago the worker last reported', async () => {
    const running = (heartbeat) => JSON.stringify({ ok: true, task: task('running', { heartbeat_at: heartbeat, server_time: '2026-11-18T12:00:20+00:00' }) });
    const page = setup({ activeTask: task('running'), responses: [running('2026-11-18T12:00:00+00:00')] });
    await page.settle();

    page.clock.now += 5000;
    await page.tick();
    assert.match(page.text(), /Last server activity: 0:25 ago\./, '20 s on the server clock plus 5 s since that answer');
    assert.strictEqual(page.ticking(), 1, 'the line is refreshed every second');
});

test('while the manager is silent, the window counts the silence and the next check', async () => {
    const page = setup({
        activeTask: task('running', { heartbeat_at: '2026-11-18T12:00:00+00:00', server_time: '2026-11-18T12:00:03+00:00' }),
        responses: [new Error('down'), new Error('down'), new Error('down'), new Error('down'), new Error('down')],
    });
    await page.settle();
    assert.match(page.text(), /No answer from the manager for 0:00 \(attempts: 1\)\. Next attempt in 3 s\./);
    // the last known state stays on screen
    assert.match(page.text(), /Status<\/small><br><strong>running/);

    for (let i = 0; i < 4; i++) {
        page.clock.now += 10000;
        await page.tick();
    }
    assert.match(page.text(), /No answer from the manager for 0:40 \(attempts: 5\)\. Next attempt in 10 s\./, 'never more than 10 s apart');
    assert.match(page.text(), /Last server activity: 0:43 ago\./);
});

test('the ticker stops once the task is finished', async () => {
    const page = setup({ activeTask: task('running'), responses: [answer('succeeded')] });
    await page.settle();

    assert.match(page.text(), /Update completed/);
    assert.strictEqual(page.ticking(), 0);
});

test('a manager that sends no server time is measured by the browser clock', async () => {
    const page = setup({ activeTask: task('running'), responses: [JSON.stringify({ ok: true, task: task('running', { heartbeat_at: new Date(1_000_000 - 7000).toISOString() }) })] });
    await page.settle();

    assert.match(page.text(), /Last server activity: 0:07 ago\./);
});
