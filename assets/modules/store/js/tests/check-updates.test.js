const test = require('node:test');
const assert = require('node:assert');
const { noticeFor } = require('../check-updates.js');

const texts = { done: 'DONE: newest versions', partial: 'PARTIAL: list may be outdated', failed: 'FAILED' };

test('a refreshed catalog reloads the module with the full-success notice', () => {
    assert.deepStrictEqual(
        noticeFor({ ok: true, updater_cache_dropped: 1, catalog_refreshed: true }, texts),
        { state: 'done', text: texts.done, dashboard: true, reload: true }
    );
});

test('a catalog that was not refreshed never shows the full-success notice', () => {
    const notice = noticeFor({ ok: true, updater_cache_dropped: 1, catalog_refreshed: false }, texts);

    assert.strictEqual(notice.state, 'partial');
    assert.strictEqual(notice.text, texts.partial);
    assert.notStrictEqual(notice.text, texts.done);
    assert.strictEqual(notice.reload, false, 'a reload would show the old cached list as current');
    assert.strictEqual(notice.dashboard, true, 'the dropped update caches still make the dashboard current');
});

test('an answer without the flag counts as not refreshed', () => {
    assert.strictEqual(noticeFor({ ok: true }, texts).state, 'partial');
});

test('a refusal shows its message, an empty answer the generic failure', () => {
    assert.deepStrictEqual(
        noticeFor({ ok: false, error_code: 'ACL_DENIED', message: 'Only administrators can check for updates.' }, texts),
        { state: 'failed', text: 'Only administrators can check for updates.', dashboard: false, reload: false }
    );
    assert.strictEqual(noticeFor(null, texts).text, texts.failed);
    assert.strictEqual(noticeFor({ ok: false }, texts).text, texts.failed);
});
