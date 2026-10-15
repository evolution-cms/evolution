const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const uploaderSource = fs.readFileSync(path.join(__dirname, '..', 'browser', 'uploader.js'), 'utf8');

test('an error status shows the message the server sent', () => {
    assert.match(uploaderSource, /typeof response\.message === 'string' && response\.message !== ''\s*\? response\.message/);
});

test('a 413 the server could not answer in JSON names the size limit', () => {
    assert.match(uploaderSource, /xhr\.status === 413\s*\? browser\.label\("The uploaded file exceeds \{size\} bytes\.", \{size: browser\.maxFileSize\}\)/);
});

test('an answer behind a PHP startup warning is still read', () => {
    assert.match(uploaderSource, /var start = xhr\.responseText\.lastIndexOf\('\{"success"'\);/);
    assert.match(uploaderSource, /response = start > 0 \? \$\.parseJSON\(xhr\.responseText\.slice\(start\)\) : null;/);
});
