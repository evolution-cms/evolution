const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const filesSource = fs.readFileSync(path.join(__dirname, '..', 'browser', 'files.js'), 'utf8');

// just enough jQuery for the menu: the dialog's markup, click handlers by selector, the current tree node
function loadMenu(state) {
  const calls = [];
  const handlers = {};
  let dialog = '';
  const node = { get: () => (state.treeNode ? {} : undefined), name: 'current-node' };
  const $ = (selector) => {
    if (selector === '#dialog') return { html: (markup) => { dialog = markup; } };
    if (selector === 'div.folder > a > span.folder.current') return { parent: () => node };
    return { click: (fn) => { handlers[selector] = fn; } };
  };
  const browser = {
    label: (text, data) => text.replace('{count}', data ? data.count : ''),
    access: { files: { copy: true, move: true }, dirs: { create: true } },
    support: { zip: true },
    showMenu: () => calls.push('showMenu'),
    hideDialog: () => {},
    refresh: () => calls.push('refresh'),
    refreshDir: (dir) => calls.push('refreshDir:' + dir.name),
    copyClipboard: (dir) => calls.push('copy:' + dir),
    moveClipboard: (dir) => calls.push('move:' + dir),
    post: (url, data) => calls.push('post:' + url + ':' + data.dir),
    baseGetData: (act) => act,
    fileNameDialog: () => calls.push('newDir'),
  };
  vm.runInNewContext(filesSource, { browser, $, _: {} });
  Object.assign(browser, state);
  browser.menuCurrentDir({});

  return { browser, calls, handlers, dialog: () => dialog };
}

test('the empty space offers what acts on the folder on screen, never on the folder itself', () => {
  const { dialog } = loadMenu({ dir: 'images/team', dirWritable: true, clipboard: [], treeNode: true });

  assert.match(dialog(), /kcact:refresh/);
  assert.match(dialog(), /kcact:mkdir"/);
  assert.match(dialog(), /kcact:download/);
  assert.doesNotMatch(dialog(), /kcact:(cpdir|mvdir|rmdir|cpcbd|mvcbd)/);
});

test('refresh reloads the folder on screen: its subfolders and its files', () => {
  const { handlers, calls } = loadMenu({ dir: 'images/team', dirWritable: true, clipboard: [], treeNode: true });
  handlers['.menu a[href="kcact:refresh"]']();

  assert.deepEqual(calls.slice(1), ['refreshDir:current-node', 'refresh']);
});

test('paste and download go to the folder on screen', () => {
  const { handlers, calls, dialog } = loadMenu({
    dir: 'images/team', dirWritable: true, clipboard: [{}, {}], treeNode: true,
  });
  assert.match(dialog(), /Copy 2 files/);
  handlers['.menu a[href="kcact:cpcbd"]']();
  handlers['.menu a[href="kcact:mvcbd"]']();
  handlers['.menu a[href="kcact:download"]']();

  assert.deepEqual(calls.slice(1), ['copy:images/team', 'move:images/team', 'post:downloadDir:images/team']);
});

test('a folder it may not write to gets denied entries that do nothing', () => {
  const { handlers, calls, dialog } = loadMenu({
    dir: 'images', dirWritable: false, clipboard: [{}], treeNode: true,
  });

  assert.match(dialog(), /kcact:mkdir" class="denied"/);
  assert.match(dialog(), /kcact:cpcbd" class="denied"/);
  assert.equal(handlers['.menu a[href="kcact:cpcbd"]'], undefined);
  assert.equal(handlers['.menu a[href="kcact:mkdir"]'](), false);
  assert.deepEqual(calls, ['showMenu']);
});

test('the list opens it only on its empty space, not on a file, folder or link card', () => {
  const start = filesSource.indexOf("$('#files').bind('contextmenu'");
  assert.ok(start > 0);
  const handler = filesSource.slice(start, filesSource.indexOf('});', start));

  assert.match(handler, /closest\('\.file, \.file-link'\)\.length\)\s*return false;/);
  assert.match(handler, /browser\.menuCurrentDir\(e\)/);
});
