const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const filesSource = fs.readFileSync(path.join(__dirname, '..', 'browser', 'files.js'), 'utf8');

test('list view applies the text size slider value on every render', () => {
  assert.match(filesSource, /browser\.applyTextSize\(\$\('#rangeText'\)\.val\(\)\);/);
  assert.match(filesSource, /\$\('\.textsize'\)\.text\(size \+ 'px'\)/);
});
