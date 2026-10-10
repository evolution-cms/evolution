const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const browserSource = fs.readFileSync(
  path.join(__dirname, '..', 'browser', 'files.js'),
  'utf8'
);

function loadBrowser() {
  const browser = {
    humanSize(size) {
      return `${size} B`;
    },
  };
  vm.runInNewContext(browserSource, { browser, $: () => ({}) });
  return browser;
}

test('folder sizes are calculated only when the Size link is clicked', () => {
  const browser = loadBrowser();

  assert.equal(
    browser.directorySizeDisplay({ isDir: true }),
    '<a href="#" class="dirSize">Size</a>'
  );
  assert.match(browserSource, /url: browser\.baseGetData\('dirSize'\)/);
  assert.match(browserSource, /data: \{dir:dir\}/);
});

test('folder dates and lazy sizes render in both list and content views', () => {
  assert.equal((browserSource.match(/'\n?\s*<td class="time">' \+ file\.date/g) || []).length, 1);
  assert.equal((browserSource.match(/'\n?\s*<div class="time">' \+ file\.date/g) || []).length, 1);
  assert.equal((browserSource.match(/browser\.directorySizeDisplay\(file\)/g) || []).length, 2);
});
