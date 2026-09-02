const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const state = require('../manager-tab-state.js');
const options = {
    base: '/manager/', origin: 'https://example.test', token: 'new-session-token',
    modules: { commerce: { views: ['', 'dashboard', 'orders', 'order'], params: { i: '^\\d+$', page: '^\\d+$' } } }
};

test('the top-level frameset supplies token, account scope and rules before the restoration script', () => {
    const frame = fs.readFileSync(path.join(__dirname, '../../../views/frame/1.blade.php'), 'utf8');
    assert.match(frame, /name="csrf-token" content="\{\{ csrf_token\(\) \}\}"/);
    assert.match(frame, /tab_restore_user:.*mgrInternalKey/);
    assert.match(frame, /tab_restore_modules:.*cms.manager_tab_restore.modules/);
    assert.ok(frame.indexOf('manager-tab-state.js') < frame.indexOf("'js/evo.js'"));
    assert.match(frame, /@revision\(MGR_DIR .*js\/evo.js/);
});

test('persists a read route without the old token; restores with the current session token', () => {
    const stored = state.storage('?a=112&id=commerce&get=orders&_token=old', options);
    assert.equal(stored, '?a=112&id=commerce&get=orders');
    assert.equal(state.restore(stored, options), stored + '&_token=new-session-token');
    assert.equal(state.restore(stored, { ...options, token: 'another-login' }), stored + '&_token=another-login');
    assert.equal(state.restore(stored, { ...options, token: '' }), '');
});

for (const url of [
    '#?a=112&id=commerce&get=orders&_token=old',
    'https://example.test/manager/#?a=112&id=commerce&get=orders&_token=old',
    'https://example.test/manager/index.php#?a=112&id=commerce&get=orders&_token=old',
    'index.php?a=112&id=commerce&get=orders&_token=old',
]) test('validates startup URL ' + url, () => {
    assert.equal(state.restore(url, options), '?a=112&id=commerce&get=orders&_token=new-session-token');
});

for (const url of [
    '?a=6&id=3', '#?a=112&id=commerce&get=orderDelete&i=3',
    '?a=112&id=commerce&get=orderSave', '?a=112&id=unknown&get=orders',
    '?a=112&id=commerce&get=orders&operation=delete', '?a=112&id=commerce&get=order&i=1&back=evil',
    '?a=112&id=commerce&get=order&i[]=1', '?a=112&id=commerce&get=order&i=1%0Aevil',
    '?a=112&id=commerce&get=order&i=1%0A', '?a=2&a=6', '?a=2&%61=6',
    '?a=2&_token=one&_token=two', '?a=2&filemanager=1', '?a=2&r=1',
    '?a=2&_method=POST', 'https://evil.test/manager/?a=2',
    'https://user:pass@example.test/manager/?a=2', '/other/?a=2',
    'javascript:alert(1)', '?a=112&id=__proto__&get=orders', '?a=2#?a=6',
]) test('never re-signs unsafe or ambiguous URL ' + url, () => {
    assert.equal(state.storage(url, options), '');
    assert.equal(state.restore(url, options), '');
});

// Execute the real theme methods without loading an authenticated manager or a browser.
for (const [theme, name] of [['default', 'evo']]) {
    test(theme + ' uses the same safe restoration path and does not restore stored title HTML', () => {
        const source = fs.readFileSync(path.join(__dirname, '../../style', theme, 'js', name + '.js'), 'utf8');
        const manager = { config: {}, extended(methods) { Object.assign(this, methods); } };
        const doc = { baseURI: options.origin + options.base, querySelector: () => ({ getAttribute: () => options.token }), querySelectorAll: () => [], addEventListener() {} };
        const win = { tree: {}, location: { origin: options.origin }, evoManagerTabState: state, addEventListener() {} };
        const sandbox = { window: win, document: doc, navigator: { userAgent: '', appVersion: '' }, Element: function () {}, [name]: manager, URL, console };
        vm.createContext(sandbox);
        vm.runInContext(source, sandbox);
        manager.config = { global_tabs: true, tab_restore_modules: options.modules };
        manager.EVO_MANAGER_URL = manager.MODX_MANAGER_URL = options.base;
        assert.equal(manager.tabUrlForRestore('?a=6'), '');
        assert.equal(manager.tabUrlForRestore('#?a=112&id=commerce&get=orders'), '?a=112&id=commerce&get=orders&_token=new-session-token');
        const opened = [];
        manager.tabs = tab => opened.push(tab);
        win.localStorage = sandbox.localStorage = { getItem: () => JSON.stringify({ tabs: [
            { url: '?a=6', title: 'unsafe' },
            { url: '?a=112&id=commerce&get=orders&_token=old', title: '<img onerror=evil()>' }
        ] }) };
        assert.equal(manager.tabsRestore(), true);
        assert.equal(opened.length, 1);
        assert.equal(opened[0].title, 'blank');
        assert.equal(opened[0].url.includes('_token=old'), false);
        win.localStorage.getItem = () => JSON.stringify({ tabs: [
            { url: '?a=112&id=commerce&get=orders', title: '<i class="fa fa-store"></i> Комерція' }
        ] });
        opened.length = 0;
        manager.tabsRestore();
        assert.equal(opened[0].title, '<i class="fa fa-store"></i> Комерція');

        // A server-rendered menu repairs a title previously persisted as blank.
        const menuTitle = '<i class="fa fa-store"></i> Комерція';
        doc.querySelectorAll = () => [{ getAttribute: () => 'index.php?a=112&id=commerce', innerHTML: menuTitle }];
        win.localStorage.getItem = () => JSON.stringify({ tabs: [
            { url: '?a=112&id=commerce&get=orders', title: 'blank' }
        ], active: '?a=112&id=commerce&get=orders' });
        opened.length = 0;
        manager.tabsRestore();
        assert.equal(opened.length, 2);
        assert.ok(opened.every(tab => tab.title === menuTitle));
        assert.match(source, /startupUrl = (evo|modx)\.tabUrlForRestore\(w.location.href\)/);
        assert.match(source, /EVO_Tabs:.*tab_restore_user/);

        for (const method of ['get', 'post']) {
            const headers = {};
            manager.XHR = () => ({ open() {}, setRequestHeader: (key, value) => { headers[key] = value; }, send() {} });
            manager[method]('?a=67');
            assert.equal(headers['X-CSRF-TOKEN'], options.token);
            delete headers['X-CSRF-TOKEN'];
            manager[method]('https://other.test/');
            assert.equal(headers['X-CSRF-TOKEN'], undefined);
        }

        // Exercise actual startup with fresh server state and a stale browser hash.
        for (const component of ['mainmenu', 'resizer', 'moduleViewport', 'search']) manager[component] = { init() {} };
        manager.config.tab_restore_user = '42';
        win.localStorage = sandbox.localStorage = { getItem() { return null; }, setItem() {}, removeItem() {} };
        win.history = { replaceState() {} };
        manager.EVO_MANAGER_URL = manager.MODX_MANAGER_URL = options.origin + options.base;
        opened.length = 0;
        win.location.href = options.origin + options.base + '#?a=112&id=commerce&get=orders&_token=old';
        manager.init();
        assert.equal(opened.at(-1).url, '?a=112&id=commerce&get=orders&_token=new-session-token');
        assert.equal(opened.at(-1).title, menuTitle);
        assert.ok(manager.tabsStorageKey.endsWith(':42'));
        opened.length = 0;
        win.location.href = options.origin + options.base + '#?a=112&id=commerce&get=orderDelete&i=1';
        manager.init();
        assert.equal(opened.length, 1);
        assert.equal(opened[0].url, '?a=2');
    });
}

test('stored labels retain text and safe icons without restoring executable HTML', () => {
    assert.equal(state.title('<i class="fa fa-store"></i> Комерція'), '<i class="fa fa-store"></i> Комерція');
    assert.equal(state.title('Назва <small>(12)</small>'), 'Назва (12)');
    assert.equal(state.title('A &amp; B "quoted"'), 'A &amp; B &quot;quoted&quot;');
    assert.equal(state.title('<img src=x onerror=alert(1)>Комерція'), 'Комерція');
    assert.equal(state.title('<script>alert(1)</script>Комерція'), 'Комерція');
    assert.equal(state.title('<svg onload=alert(1)><script>alert(1)</script></svg>Комерція'), 'Комерція');
    assert.equal(state.title('<i class="fa fa-store" onclick="alert(1)"></i>Комерція'), 'Комерція');
    assert.equal(state.title('<i class="fa fa-store hidden"></i>Комерція'), '<i class="fa fa-store"></i> Комерція');
    assert.equal(state.title('" onmouseover="alert(1)'), '&quot; onmouseover=&quot;alert(1)');
    assert.equal(state.title('blank'), '');
    assert.equal(state.title({ html: 'Комерція' }), '');
    const label = '<i class="fa fa-store"></i> A &amp; B';
    assert.equal(state.title(state.title(label)), label);
});

test('module titles use only matching same-origin audited menu links', () => {
    const url = '?a=112&id=commerce&get=orders';
    const menu = (href, innerHTML) => ({ getAttribute: () => href, innerHTML });
    const trustedTitle = '<svg viewBox="0 0 24 24"><path d="M0 0"></path></svg> Комерція';
    const doc = { querySelectorAll: () => [
        menu('https://evil.test/manager/?a=112&id=commerce', 'Wrong origin'),
        menu('index.php?a=112&id=other', 'Wrong module'),
        menu('index.php?a=112&id=commerce&get=orderDelete', 'Mutation'),
        menu('index.php?a=112&id=commerce', trustedTitle)
    ] };
    assert.equal(state.restoreTitle(url, 'blank', options, doc), trustedTitle);
    assert.equal(state.restoreTitle('?a=6', 'unsafe', options, doc), 'blank');
});
