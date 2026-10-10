const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const browserSource = fs.readFileSync(
  path.join(__dirname, '..', 'browser', 'files.js'),
  'utf8'
);

function loadBrowser(opener, windowObject) {
  const browser = { opener: opener || {} };
  vm.runInNewContext(browserSource, {
    browser,
    $: () => ({}),
    window: windowObject || { opener: null, parent: null },
  });
  return browser;
}

test('file return control is enabled for editor and callback openers', () => {
  [
    { TinyMCE4: 'imageField' },
    { TinyMCE: true },
    { CKEditor: {} },
    { FCKeditor: true },
    { callBack() {} },
    { callBackMultiple() {} },
  ].forEach((opener) => {
    assert.equal(loadBrowser(opener).canReturnFile(), true);
  });
});

test('file return control is hidden in standalone admin mode', () => {
  assert.equal(loadBrowser({}).canReturnFile(), false);
});

test('active TinyMCE editor is treated as a file picker', () => {
  const windowObject = {
    opener: null,
    parent: { tinymce: { activeEditor: {} } },
  };

  assert.equal(loadBrowser({}, windowObject).canReturnFile(), true);
});

test('content cards render the plus control only for selectable files', () => {
  assert.match(browserSource, /var showSelectButton = this\.canReturnFile\(\)/);
  assert.match(browserSource, /var selectControl = \(!file\.isDir && showSelectButton\)[\s\S]*?<div class="selectThis">\+/);
  assert.match(browserSource, /selectControl \+/);
});
