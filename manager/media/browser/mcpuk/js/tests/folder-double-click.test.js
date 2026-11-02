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

test('single-clicking a tree folder opens and selects it without toggling the branch', () => {
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
  browser.selectTreeFolder = () => actions.push('select');

  browser.openTreeFolder(folder);

  assert.deepEqual(actions, ['open', 'select']);
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
  browser.selectTreeFolder = () => actions.push('select');

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

function treeFolder(braceClass) {
  return { children: () => ({ hasClass: (name) => name === braceClass }) };
}

test('a single click on the arrow of a tree folder only toggles its branch', () => {
  for (const state of ['opened', 'closed']) {
    const browser = loadBrowser();
    const actions = [];
    browser.expandDir = () => actions.push('toggle');
    browser.changeDir = () => actions.push('open');
    browser.selectTreeFolder = () => actions.push('select');
  browser.selectTreeFolder = () => actions.push('select');

    browser.handleTreeClick(treeFolder(state), true);

    assert.deepEqual(actions, ['toggle'], state);
  }
});

test('a single click on the name of a tree folder opens it', () => {
  const browser = loadBrowser();
  const actions = [];
  browser.expandDir = () => actions.push('toggle');
  browser.changeDir = () => actions.push('open');
  browser.selectTreeFolder = () => actions.push('select');

  browser.handleTreeClick(treeFolder('opened'), false);

  assert.deepEqual(actions, ['open', 'select']);
});

test('the arrow area of a folder without subfolders opens it', () => {
  const browser = loadBrowser();
  const actions = [];
  browser.expandDir = () => actions.push('toggle');
  browser.changeDir = () => actions.push('open');
  browser.selectTreeFolder = () => actions.push('select');

  browser.handleTreeClick(treeFolder(''), true);

  assert.deepEqual(actions, ['open', 'select']);
});

test('collapsing a branch does not change the folder shown in the file list', () => {
  const browser = loadBrowser();
  const calls = [];
  const brace = {
    hasClass: (name) => name === 'opened',
    removeClass() { return brace; },
    addClass() { return brace; },
  };
  const folders = { hide: (ms, done) => { calls.push('hide'); if (done) done(); } };
  const dir = {
    data: () => 'images',
    children: () => brace,
    parent: () => ({ children: () => folders }),
  };
  browser.dir = 'images/team';
  browser.changeDir = () => calls.push('open');

  browser.expandDir(dir);

  assert.deepEqual(calls, ['hide']);
});
