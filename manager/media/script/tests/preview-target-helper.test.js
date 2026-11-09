const test = require('node:test');
const assert = require('node:assert/strict');
const helper = require('../preview-target-helper');

const SITE = 'https://example.com/';
const MANAGER = 'https://example.com/manager/index.php?a=27&id=5';
const SUB_SITE = 'https://example.com/sub/';
const SUB_MANAGER = 'https://example.com/sub/manager/index.php?a=27&id=5';

function sandboxed(target, site = SITE, manager = MANAGER) {
    return helper.resolve(target, site, manager);
}

test('the resource itself and document ids open in the sandbox', () => {
    assert.deepEqual(sandboxed(SITE + 'index.php?id=5&z=manprev'), {
        mode: 'sandbox',
        url: 'https://example.com/index.php?id=5&z=manprev',
    });
    assert.deepEqual(sandboxed('12'), { mode: 'sandbox', url: 'https://example.com/index.php?id=12' });
    assert.deepEqual(sandboxed('12', SUB_SITE, SUB_MANAGER), {
        mode: 'sandbox',
        url: 'https://example.com/sub/index.php?id=12',
    });
});

test('relative same-origin weblinks resolve against the site and open in the sandbox', () => {
    const cases = {
        'page': 'https://example.com/page',
        'page.html?x=1': 'https://example.com/page.html?x=1',
        '/page': 'https://example.com/page',
        '//example.com/page': 'https://example.com/page',
        '\\\\example.com\\page': 'https://example.com/page',
        '/\\example.com/page': 'https://example.com/page',
        '?q=1': 'https://example.com/?q=1',
        '#top': 'https://example.com/#top',
        './page': 'https://example.com/page',
        '../../page': 'https://example.com/page',
    };

    for (const [target, url] of Object.entries(cases)) {
        assert.deepEqual(sandboxed(target), { mode: 'sandbox', url }, target);
    }
});

test('absolute same-origin weblinks open in the sandbox whatever their spelling', () => {
    for (const target of [
        'https://example.com/page',
        'HTTPS://EXAMPLE.COM/page',
        'https://example.com:443/page',
        'https://Example.com/page',
        ' https://example.com/page ',
        'https:\\\\example.com\\page',
        'https://user:pass@example.com/page',
        'https://exam\tple.com/page',
    ]) {
        assert.equal(sandboxed(target).mode, 'sandbox', target);
    }
});

test('a subdirectory installation sandboxes its whole origin, not only the installation path', () => {
    for (const target of [
        'https://example.com/sub/page',
        'https://example.com/other/page',
        'https://example.com/',
        '/other/page',
        '//example.com/sub/manager/index.php?a=8',
        'page',
    ]) {
        assert.equal(sandboxed(target, SUB_SITE, SUB_MANAGER).mode, 'sandbox', target);
    }

    assert.equal(sandboxed('page', SUB_SITE, SUB_MANAGER).url, 'https://example.com/sub/page');
    assert.equal(sandboxed('/page', SUB_SITE, SUB_MANAGER).url, 'https://example.com/page');
});

test('the manager origin is sandboxed even when the site is configured on another origin', () => {
    const managerOnly = 'https://admin.example.com/manager/index.php?a=27';

    assert.equal(helper.resolve('https://admin.example.com/page', SITE, managerOnly).mode, 'sandbox');
    assert.equal(helper.resolve('https://example.com/page', SITE, managerOnly).mode, 'sandbox');
    // a site defined without a host is taken from the manager page
    assert.deepEqual(helper.resolve('/page', '/', managerOnly), {
        mode: 'sandbox',
        url: 'https://admin.example.com/page',
    });
});

test('external weblinks open a plain window', () => {
    const cases = {
        'https://other.example/page': 'https://other.example/page',
        'http://example.com/page': 'http://example.com/page',
        'https://example.com:8443/page': 'https://example.com:8443/page',
        'https://sub.example.com/page': 'https://sub.example.com/page',
        'https://example.com.evil.test/page': 'https://example.com.evil.test/page',
        // the host is evil.test; example.com is only the user name
        'https://example.com@evil.test/page': 'https://example.com@evil.test/page',
        '//evil.test/page': 'https://evil.test/page',
        '\\\\evil.test\\page': 'https://evil.test/page',
    };

    for (const [target, url] of Object.entries(cases)) {
        assert.deepEqual(sandboxed(target), { mode: 'window', url }, target);
    }

    // a link that only starts with the site URL as text is still another origin
    assert.equal(sandboxed('https://example.com.evil.test/', 'https://example.com', MANAGER).mode, 'window');
});

test('unsupported schemes and unparsable targets are rejected', () => {
    for (const target of [
        'javascript:alert(1)',
        ' JavaScript:alert(1)',
        'java\tscript:alert(1)',
        'data:text/html,<script>alert(1)</script>',
        'vbscript:msgbox(1)',
        'blob:https://example.com/uuid',
        'file:///etc/passwd',
        'about:blank',
        'mailto:user@example.com',
        'ftp://example.com/file',
        'http://',
        'https://exa mple.com/',
        '',
        '   ',
        null,
        undefined,
    ]) {
        assert.deepEqual(sandboxed(target), { mode: 'reject', url: '' }, String(target));
    }

    assert.equal(helper.resolve('page', 'not a url').mode, 'reject');
});
