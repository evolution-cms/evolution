const test = require('node:test');
const assert = require('node:assert/strict');
const queue = require('../upload-queue');

const KB = 1024;
const MB = 1024 * KB;

test('30 files go up in batches of at most max_file_uploads, in the order chosen', () => {
    const sizes = Array.from({ length: 30 }, () => 10 * KB);

    const { batches, skipped } = queue.plan(sizes, 20, 8 * MB, 2 * MB);

    assert.deepEqual(batches.map((b) => b.length), [20, 10]);
    assert.deepEqual(batches.flat(), sizes.map((s, i) => i));
    assert.deepEqual(skipped, []);
});

test('a batch also stays under post_max_size with room for the form', () => {
    const sizes = [3 * MB, 3 * MB, 3 * MB, 1 * MB];

    const { batches } = queue.plan(sizes, 20, 8 * MB, 0);

    assert.deepEqual(batches, [[0, 1], [2, 3]]);
});

test('a file over the file limit, or too large for any request, is left out and named', () => {
    const sizes = [1 * MB, 3 * MB, 1 * MB, 9 * MB];

    assert.deepEqual(queue.plan(sizes, 20, 8 * MB, 2 * MB), { batches: [[0, 2]], skipped: [1, 3] });
    assert.deepEqual(queue.plan(sizes, 20, 8 * MB, 0), { batches: [[0, 1, 2]], skipped: [3] });
});

test('no limits: one batch', () => {
    assert.deepEqual(queue.plan([1, 2, 3], 0, 0, 0), { batches: [[0, 1, 2]], skipped: [] });
});

test('each request uses the token the previous answer brought', async () => {
    const seen = [];
    const send = (batch, token) => {
        seen.push([batch, token]);
        return Promise.resolve({ report: '<p>' + batch.join() + '</p>', uploaded: batch.length, token: token + '+' });
    };
    const progress = [];

    const result = await queue.run([[0, 1], [2]], 't', send, (i, n) => progress.push(i + '/' + n));

    assert.deepEqual(seen, [[[0, 1], 't'], [[2], 't+']]);
    assert.deepEqual(progress, ['0/2', '1/2']);
    assert.equal(result.uploaded, 3);
    assert.deepEqual(result.reports, ['<p>0,1</p>', '<p>2</p>']);
    assert.deepEqual(result.notSent, []);
});

test('a failed request stops the queue and names every file not sent', async () => {
    let calls = 0;
    const send = (batch, token) => {
        calls++;
        if (calls === 2) return Promise.reject(new Error('HTTP 500'));
        return Promise.resolve({ report: 'ok', uploaded: batch.length, token: 'next' });
    };

    const result = await queue.run([[0, 1], [2, 3], [4]], 't', send);

    assert.equal(calls, 2);
    assert.equal(result.uploaded, 2);
    assert.deepEqual(result.notSent, [2, 3, 4]);
    assert.equal(result.reason, 'HTTP 500');
});

test('an answer without a token for the next request stops the queue after it', async () => {
    const send = (batch) => Promise.resolve({ report: 'ok', uploaded: batch.length, token: '' });

    const result = await queue.run([[0], [1]], 't', send);

    assert.equal(result.uploaded, 1);
    assert.deepEqual(result.notSent, [1]);
});

test('messages are formatted and file names escaped', () => {
    assert.equal(queue.format('Uploaded %1$s of %2$s files.', [20, 30]), 'Uploaded 20 of 30 files.');
    assert.equal(queue.escapeHtml('<img src=x onerror="a">&\''), '&lt;img src=x onerror=&quot;a&quot;&gt;&amp;&#39;');
});
