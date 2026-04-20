const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

test('custom KCFinder callbacks take priority over the generic TinyMCE fallback', () => {
  const source = fs.readFileSync(
    path.join(__dirname, '..', 'browser', 'files.js'),
    'utf8'
  );

  const tinyMcePopup = source.indexOf("} else if (this.opener.TinyMCE && (typeof tinyMCEPopup !== 'undefined')) {");
  const callBack = source.indexOf('} else if (this.opener.callBack) {');
  const callBackMultiple = source.indexOf('} else if (this.opener.callBackMultiple) {');
  const hasTinyMce = source.indexOf('} else if (hasTinyMCE) {');

  assert.notEqual(tinyMcePopup, -1);
  assert.notEqual(callBack, -1);
  assert.notEqual(callBackMultiple, -1);
  assert.notEqual(hasTinyMce, -1);
  assert.ok(tinyMcePopup < callBack);
  assert.ok(callBack < callBackMultiple);
  assert.ok(callBackMultiple < hasTinyMce);
});
