const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function setup(fetch) {
    const handlers = {};
    const message = {textContent: ''};
    const dismiss = {addEventListener(type, callback) { this.click = callback; }};
    const notice = {dataset: {}, style: {}, hidden: true,
        querySelector: selector => selector === '[data-evo-cache-message]' ? message : dismiss,
        getBoundingClientRect: () => ({width: 300, height: 60})};
    const link = {href: 'http://evo.test/manager/index.php?a=26&_token=test-token', title: 'Clear cache', target: 'main'};
    const attrs = new Map();
    const extra = {getBoundingClientRect: () => ({left: 100, bottom: 80}),
        setAttribute: (key, value) => attrs.set(key, value), removeAttribute: key => attrs.delete(key)};
    const menu = {dataset: {evoMenuError: 'Error'},
        addEventListener(type, callback, capture) { handlers[type + (capture ? ':capture' : '')] = callback; },
        querySelector: () => link, querySelectorAll: () => []};
    const document = {querySelector: selector => selector === '[data-evo-topbar]' ? menu : {content: 'test-token'},
        getElementById: () => notice, addEventListener() {}, documentElement: {classList: {contains: value => value === 'dark'}}};
    const timers = new Map();
    const tabs = [];
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../style/evo-ui/js/topbar.js'), 'utf8'), {
        document, window: {innerWidth: 1200, innerHeight: 800, evo: {tabs: options => tabs.push(options)}, addEventListener() {}},
        location: {href: 'http://evo.test/manager/', origin: 'http://evo.test'}, URL, URLSearchParams, fetch,
        localStorage: {setItem() {}}, MutationObserver: class {observe() {}},
        setTimeout: (callback, delay) => { timers.set(delay, callback); return delay; }, clearTimeout: id => timers.delete(id),
        DOMParser: class {parseFromString(html) { return {querySelector: () => html.includes('data-manager-cache-result')
            ? {childNodes: [{textContent: 'Cache: deleted 1 file'}], querySelector: () => null, querySelectorAll: () => []} : null}; }}
    });
    const click = target => ({target, preventDefault() {this.defaultPrevented = true;}, stopImmediatePropagation() {this.stopped = true;}});
    return {handlers, notice, message, attrs, timers, tabs, link,
        quickClick() {const event = click({closest: () => extra}); handlers['click:capture'](event); return event;},
        mainClick() {const event = click({closest: selector => selector === 'a' ? link : null}); handlers['click:capture'](event); handlers.click(event); return event;}};
}

const tick = () => new Promise(resolve => setImmediate(resolve));

test('quick clear posts once with CSRF and reports the result without opening a tab or confirmation', async () => {
    let complete;
    const requests = [];
    const fixture = setup((url, options) => {requests.push({url, options}); return new Promise(resolve => {complete = resolve;});});
    fixture.quickClick();
    fixture.quickClick();
    assert.equal(requests.length, 1, 'a second click while clearing must not repeat the operation');
    assert.equal(requests[0].options.method, 'POST');
    assert.equal(requests[0].options.headers['X-CSRF-TOKEN'], 'test-token');
    assert.equal(requests[0].options.body.get('_token'), 'test-token');
    assert.equal(fixture.attrs.get('aria-busy'), 'true');
    complete({ok: true, redirected: false, text: async () => '<div data-manager-cache-result>Cache result</div>'});
    await tick();
    assert.equal(fixture.notice.dataset.state, 'success');
    assert.equal(fixture.message.textContent, 'Cache: deleted 1 file');
    assert.equal(fixture.attrs.has('aria-busy'), false);
    assert.equal(fixture.tabs.length, 0);
    fixture.timers.get(5000)();
    assert.equal(fixture.notice.hidden, true, 'successful feedback disappears automatically');
});

test('an expired login page is not presented as a successful cache clear', async () => {
    const fixture = setup(async () => ({ok: true, redirected: false, text: async () => '<form>Login</form>'}));
    fixture.quickClick();
    await tick();
    assert.equal(fixture.notice.dataset.state, 'error');
    assert.equal(fixture.message.textContent, 'Error');
    assert.equal(fixture.attrs.has('aria-disabled'), false);
});

test('a failed request releases the quick action so it can be retried', async () => {
    let requests = 0;
    const fixture = setup(async () => {requests++; throw new Error('Offline');});
    fixture.quickClick();
    await tick();
    fixture.quickClick();
    await tick();
    assert.equal(requests, 2);
    assert.equal(fixture.notice.dataset.state, 'error');
});

test('the main clear-cache link retains normal manager navigation', () => {
    const fixture = setup(() => {throw new Error('Quick transport should not run');});
    fixture.mainClick();
    assert.equal(fixture.tabs.length, 1);
    assert.equal(fixture.tabs[0].url, fixture.link.href);
    assert.equal(fixture.notice.hidden, true);
});

test('hover opens the next submenu for a mouse but does not steal touch navigation', () => {
    const fixture = setup(() => {});
    const classes = new Set();
    const panel = {style: {}, getBoundingClientRect: () => ({width: 220})};
    const row = {getBoundingClientRect: () => ({left: 100, right: 300, top: 70, bottom: 102})};
    const owner = {classList: {contains: value => classes.has(value), toggle(value, enabled) {enabled ? classes.add(value) : classes.delete(value);}},
        contains: () => false, hasAttribute: () => false, closest: () => null,
        parentElement: {classList: {contains: value => value === 'evo-manager-submenu'}, closest: () => ({})},
        querySelectorAll: () => [], querySelector: selector => selector === ':scope > ul' ? panel : selector === ':scope > .evo-manager-item__row' ? row : trigger};
    const trigger = {closest: () => owner};
    const target = {closest: () => owner};
    fixture.handlers.pointerover({pointerType: 'touch', target, relatedTarget: null});
    assert.equal(classes.has('is-open'), false);
    fixture.handlers.pointerover({pointerType: 'mouse', target, relatedTarget: null});
    assert.equal(classes.has('is-open'), true);
    assert.equal(fixture.tabs.length, 0, 'hover must not navigate to the parent page');
});
