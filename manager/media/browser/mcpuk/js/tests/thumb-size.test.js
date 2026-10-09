const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const browserSource = fs.readFileSync(
  path.join(__dirname, '..', 'browser', 'files.js'),
  'utf8'
);
const browserTemplate = fs.readFileSync(
  path.join(__dirname, '..', '..', 'tpl', 'tpl_browser.php'),
  'utf8'
);
const uploaderSource = fs.readFileSync(
  path.join(__dirname, '..', 'browser', 'uploader.js'),
  'utf8'
);

function loadThumbSizing() {
  const browser = {};
  const updates = [];
  const $ = (selector) => ({
    css(property, value) {
      updates.push({ selector, property, value });
      return this;
    },
    text(value) {
      updates.push({ selector, text: value });
      return this;
    },
  });

  vm.runInNewContext(browserSource, { browser, $ });

  return { browser, updates };
}

test('thumb size updates the content cards and their thumbnails', () => {
  const { browser, updates } = loadThumbSizing();

  assert.equal(browser.applyThumbSize('240'), true);
  assert.deepEqual(JSON.parse(JSON.stringify(updates)), [
    { selector: 'div.thumb', property: { width: '240px', height: '240px' } },
    { selector: 'div.thumb.folder-icon', property: 'font-size', value: '192px' },
    { selector: 'div.thumb img', property: { width: '240px', height: '240px' } },
    { selector: 'div.file', property: 'width', value: '240px' },
    { selector: '.thumbsize', text: '240px' },
  ]);
});

test('thumb size changes while dragging and is reapplied after file cards render', () => {
  assert.match(browserTemplate, /\.rangeThumb'\)\.bind\('input'/);
  assert.match(browserTemplate, /\.change\(applyThumbSize\)/);
  assert.match(browserTemplate, /browser\.applyThumbSize\(\$\(this\)\.val\(\)\)/);
  assert.match(browserSource, /browser\.applyThumbSize\(\$\('#rangeThumb'\)\.val\(\)\)/);
});

test('event bindings use APIs available in bundled jQuery 1.6', () => {
  assert.match(uploaderSource, /upload\.change\(function/);
  assert.doesNotMatch(browserTemplate, /\.on\(/);
  assert.doesNotMatch(uploaderSource, /\.on\(/);
});

test('invalid thumb sizes do not change the current layout', () => {
  const { browser, updates } = loadThumbSizing();

  assert.equal(browser.applyThumbSize('not-a-size'), false);
  assert.equal(browser.applyThumbSize(0), false);
  assert.deepEqual(updates, []);
});
