const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const filesSource = fs.readFileSync(
  path.join(__dirname, '..', 'browser', 'files.js'),
  'utf8'
);
const foldersSource = fs.readFileSync(
  path.join(__dirname, '..', 'browser', 'folders.js'),
  'utf8'
);

function loadBrowser() {
  const browser = {};
  vm.runInNewContext(filesSource, { browser, $: () => ({}), _: { unselect() {} } });
  vm.runInNewContext(foldersSource, { browser, $: () => ({}) });
  return browser;
}

test('content and list folder clicks select; double-click opens the folder', () => {
  const browser = loadBrowser();
  let selected = 0;
  let opened = 0;
  const folder = { data: () => true };
  browser.selectFolder = () => selected++;
  browser.openDir = () => opened++;

  browser.handleFileClick(folder);
  assert.equal(selected, 1);
  assert.equal(opened, 0);

  browser.handleFileDoubleClick(folder);
  assert.equal(opened, 1);
});

test('single-clicking a tree folder opens its contents without toggling the branch', () => {
  const browser = loadBrowser();
  const actions = [];
  const brace = {
    hasClass(name) {
      return name === 'opened';
    },
  };
  const folder = { children: () => brace };
  browser.expandDir = () => actions.push('toggle');
  browser.changeDir = () => actions.push('open');

  browser.openTreeFolder(folder);

  assert.deepEqual(actions, ['open']);
});

test('double-clicking a tree folder toggles its branch without changing contents', () => {
  const browser = loadBrowser();
  const actions = [];
  const brace = {
    hasClass(name) {
      return name === 'opened';
    },
  };
  const folder = { children: () => brace };
  browser.expandDir = () => actions.push('toggle');
  browser.changeDir = () => actions.push('open');

  browser.toggleTreeFolder(folder);

  assert.deepEqual(actions, ['toggle']);
});

test('double-clicking a leaf tree folder has no branch to toggle', () => {
  const browser = loadBrowser();
  let toggled = false;
  const folder = { children: () => ({ hasClass: () => false }) };
  browser.expandDir = () => { toggled = true; };

  browser.toggleTreeFolder(folder);

  assert.equal(toggled, false);
});
