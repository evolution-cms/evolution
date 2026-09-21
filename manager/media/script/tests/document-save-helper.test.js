const test = require('node:test');
const assert = require('node:assert/strict');
const helper = require('../document-save-helper');

function form(values) {
    const elements = {};
    Object.keys(values).forEach((name) => { elements[name] = { value: values[name] }; });
    return { elements };
}

test('usesAjax only for "save and keep editing" of an existing resource', () => {
    assert.equal(helper.usesAjax(form({ id: '12', stay: '2', refresh_preview: '0' })), true);
    // a new resource needs the navigation to its own editor
    assert.equal(helper.usesAjax(form({ id: '0', stay: '2', refresh_preview: '0' })), false);
    assert.equal(helper.usesAjax(form({ id: '', stay: '2' })), false);
    // close and "add another" navigate away
    assert.equal(helper.usesAjax(form({ id: '12', stay: '', refresh_preview: '0' })), false);
    assert.equal(helper.usesAjax(form({ id: '12', stay: '1', refresh_preview: '0' })), false);
    // the preview flow redirects to the site
    assert.equal(helper.usesAjax(form({ id: '12', stay: '2', refresh_preview: '1' })), false);
    assert.equal(helper.usesAjax(form({ id: '12', stay: '2' }), { formData: false }), false);
    assert.equal(helper.usesAjax(null), false);
});

test('requestUrl puts the action into the query string', () => {
    assert.equal(helper.requestUrl('5'), 'index.php?a=5');
    assert.equal(helper.requestUrl(5, 'index.php'), 'index.php?a=5');
    assert.equal(helper.requestUrl(undefined), 'index.php?a=5');
});

test('requestBody drops the action and carries the token of the page', () => {
    const entries = { a: '5', _token: 'stale', pagetitle: 'x' };
    const body = {
        delete: (name) => { delete entries[name]; },
        set: (name, value) => { entries[name] = value; },
    };
    const meta = { getAttribute: () => 'fresh' };

    assert.equal(helper.requestBody(body, meta), body);
    assert.deepEqual(entries, { _token: 'fresh', pagetitle: 'x' });

    // without a meta tag the hidden field of the form stays
    helper.requestBody(body, null);
    assert.equal(entries._token, 'fresh');
});

test('parseResponse tells a save apart from a refusal and from a non-JSON answer', () => {
    const saved = helper.parseResponse(200, '{"success":true,"id":12,"alias":"a"}');
    assert.equal(saved.ok, true);
    assert.equal(saved.result.id, 12);

    const denied = helper.parseResponse(422, '{"success":false,"message":"duplicate alias"}');
    assert.deepEqual(denied, { ok: false, message: 'duplicate alias', unconfirmed: false });

    const csrf = helper.parseResponse(403, '{"error":"CSRF token mismatch","code":"csrf_token_mismatch"}');
    assert.deepEqual(csrf, { ok: false, message: '', unconfirmed: false });

    // the login page, a plugin that exited, a fatal error: the save may have run and is never replayed
    assert.deepEqual(helper.parseResponse(200, '<html>login</html>'), { ok: false, message: '', unconfirmed: true });
    assert.deepEqual(helper.parseResponse(500, '<html>error</html>'), { ok: false, message: '', unconfirmed: true });
    assert.deepEqual(helper.parseResponse(200, ''), { ok: false, message: '', unconfirmed: true });
});

test('applyResult writes back the generated alias, the substituted title and the heading', () => {
    const f = form({ alias: '', pagetitle: '', id: '12' });
    const title = { nodeType: 3, nodeValue: 'old' };
    const heading = { childNodes: [{ nodeType: 1 }, { nodeType: 3, nodeValue: '\n   ' }, title, { nodeType: 1 }] };

    const changed = helper.applyResult(f, { alias: 'untitled-resource', pagetitle: 'Untitled Resource' }, heading);

    assert.deepEqual(changed, ['alias', 'pagetitle', 'heading']);
    assert.equal(f.elements.alias.value, 'untitled-resource');
    assert.equal(f.elements.pagetitle.value, 'Untitled Resource');
    assert.equal(title.nodeValue, 'Untitled Resource');
});

test('applyResult rotates the CSRF token in the form and the meta tag', () => {
    const f = form({ alias: 'a', pagetitle: 'A', _token: 'old' });
    let content = 'old';
    const meta = { getAttribute: () => content, setAttribute: (name, value) => { content = value; } };

    assert.deepEqual(helper.applyResult(f, { alias: 'a', pagetitle: 'A', token: 'new' }, null, meta), ['meta', '_token']);
    assert.equal(f.elements._token.value, 'new');
    assert.equal(content, 'new');
    // no token in the answer leaves both alone
    assert.deepEqual(helper.applyResult(f, { alias: 'a', pagetitle: 'A' }, null, meta), []);
});

test('applyResult leaves unchanged fields alone and survives a missing heading', () => {
    const f = form({ alias: 'same', pagetitle: 'Same' });

    assert.deepEqual(helper.applyResult(f, { alias: 'same', pagetitle: 'Same' }, null), []);
    assert.deepEqual(helper.applyResult(form({}), { alias: 'x', pagetitle: 'y' }), []);
});

test('shortTitle truncates like the editor heading does', () => {
    assert.equal(helper.shortTitle('short'), 'short');
    assert.equal(helper.shortTitle('x'.repeat(60)), 'x'.repeat(50) + '...');
    assert.equal(helper.shortTitle(undefined), '');
});
