const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const read = (file) => fs.readFileSync(path.join(__dirname, '..', 'browser', file), 'utf8');
const foldersSource = read('folders.js');
const filesSource = read('files.js');
const miscSource = read('misc.js');

// a few elements and just the jQuery the tree and the file list use
function el(tag, classes, parent) {
  const node = { tag, cls: new Set(classes || []), attrs: {}, store: {}, style: {}, kids: [], parent: null };
  if (parent) {
    node.parent = parent;
    parent.kids.push(node);
  }
  return node;
}

function matches(node, selector) {
  const [tag, ...classes] = selector.split('.');
  return (!tag || node.tag === tag) && classes.every((name) => node.cls.has(name));
}

function descendants(node) {
  return node.kids.flatMap((kid) => [kid, ...descendants(kid)]);
}

function wrap(nodes) {
  const set = {
    length: nodes.length,
    get: (i) => nodes[i],
    first: () => wrap(nodes.slice(0, 1)),
    each(fn) { nodes.forEach((node, i) => fn.call(node, i, node)); return set; },
    filter: (fn) => wrap(nodes.filter((node, i) => fn.call(node, i, node))),
    children: (selector) => wrap(nodes.flatMap((node) => node.kids.filter((kid) => !selector || matches(kid, selector)))),
    parent: () => wrap(nodes.map((node) => node.parent).filter(Boolean)),
    parents(selector) {
      const found = [];
      nodes.forEach((node) => {
        for (let up = node.parent; up; up = up.parent)
          if (matches(up, selector)) found.push(up);
      });
      return wrap(found);
    },
    hasClass: (name) => nodes.some((node) => node.cls.has(name)),
    addClass(name) { nodes.forEach((node) => node.cls.add(name)); return set; },
    removeClass(name) { nodes.forEach((node) => node.cls.delete(name)); return set; },
    css(key, value) {
      if (typeof value == 'undefined') return nodes[0] ? (nodes[0].style[key] || '') : undefined;
      nodes.forEach((node) => { node.style[key] = value; });
      return set;
    },
    data(key, value) {
      const node = nodes[0];
      if (typeof key == 'undefined') return node.store;
      if (typeof key == 'object') { nodes.forEach((n) => Object.assign(n.store, key)); return set; }
      if (typeof value == 'undefined') return node ? node.store[key] : undefined;
      nodes.forEach((n) => { n.store[key] = value; });
      return set;
    },
    html() { return set; },
    text() { return set; },
  };
  return set;
}

function treeNode(container, dirPath, opts = {}) {
  const folder = el('div', ['folder'], container);
  const a = el('a', [], folder);
  a.attrs.href = 'kcdir:/' + dirPath;
  a.store = { path: dirPath, name: dirPath.split('/').pop(), hasDirs: !!opts.hasDirs };
  el('span', ['brace'].concat(opts.brace ? [opts.brace] : []), a);
  el('span', ['folder', opts.current ? 'current' : 'regular'], a);
  return { folder, a };
}

function subfolders(node, hidden) {
  const folders = el('div', ['folders'], node.folder);
  if (hidden) folders.style.display = 'none';
  return folders;
}

function setup() {
  const root = el('div', [], null);
  const tree = el('div', [], root);
  const files = el('div', [], root);

  const images = treeNode(tree, 'images', { current: true, hasDirs: true, brace: 'opened' });

  const page = {
    root, tree, files, images,
    card(name, isDir, selected) {
      const card = el('div', ['file'].concat(isDir ? ['folder'] : []).concat(selected ? ['selected'] : []), files);
      card.store = { name, isDir };
      return card;
    },
    treeSpans: () => descendants(tree).filter((n) => n.tag === 'span' && n.cls.has('folder')),
    selectedInTree: () => page.treeSpans().filter((n) => n.cls.has('selected')).map((n) => n.parent.store.path),
    $: null,
  };

  const $ = (selector) => {
    if (selector && typeof selector == 'object') return wrap([selector]);
    let match = /^#folders a\[href="(.*)"\]$/.exec(selector);
    if (match) return wrap(descendants(tree).filter((n) => n.tag === 'a' && n.attrs.href === match[1]));
    if (selector === 'div.folder > a > span.folder') return wrap(page.treeSpans());
    if (selector === '#files .file.selected') return wrap(files.kids.filter((n) => n.cls.has('file') && n.cls.has('selected')));
    if (selector === '#files .file' || selector === '.file') return wrap(files.kids.filter((n) => n.cls.has('file')));
    if (selector === '#files') return wrap([files]);
    if (selector === '#folders') return wrap([tree]);
    return wrap([]);
  };
  $.each = (items, fn) => (Array.isArray(items) ? items : Object.values(items)).forEach((item, i) => fn(i, item));
  page.$ = $;

  const browser = {};
  const context = { browser, $, document: {}, _: { unselect() {}, escapeDirs: (p) => p, htmlData: (t) => t } };
  vm.runInNewContext(foldersSource, context);
  vm.runInNewContext(filesSource, context);
  Object.assign(browser, {
    dir: 'images',
    scrolled: [],
    scrollIntoPane(pane, element) { browser.scrolled.push([pane, element]); },
    statusDir() {},
    updateDeleteButton() {},
    updateMobileActions() {},
    clearMobileActions() {},
  });

  return { browser, page };
}

test('the subfolder of a folder that leads to a deeper path', () => {
  const { browser } = setup();

  assert.equal(browser.subDirOnPath('images', 'images/team/2024'), 'team');
  assert.equal(browser.subDirOnPath('images', 'images/team'), 'team');
  assert.equal(browser.subDirOnPath('images', 'images'), undefined);
  assert.equal(browser.subDirOnPath('images', 'images-old/team'), undefined);
  assert.equal(browser.subDirOnPath('images/team', 'images'), undefined);
  assert.equal(browser.subDirOnPath('images', undefined), undefined);
});

test('a listed folder is found in the tree without loading anything when its branch is there', () => {
  const { browser, page } = setup();
  treeNode(subfolders(page.images), 'images/team');
  let loads = 0;
  browser.loadTreeBranch = () => { loads++; return true; };

  assert.equal(browser.treeSubDir('team').get(0).store.path, 'images/team');
  assert.equal(loads, 0);
});

test('a listed folder whose branch was never loaded loads the branch of the folder on screen', () => {
  const { browser, page } = setup();
  const loaded = [];
  browser.loadTreeBranch = (dir) => {
    loaded.push(dir.data('path'));
    treeNode(subfolders(page.images), 'images/team');
    return true;
  };

  assert.equal(browser.treeSubDir('team').get(0).store.path, 'images/team');
  assert.deepEqual(loaded, ['images']);
});

test('a loaded branch without the folder is not read again on every selection', () => {
  const { browser, page } = setup();
  treeNode(subfolders(page.images), 'images/other');
  let loads = 0;
  browser.loadTreeBranch = () => { loads++; return true; };

  assert.equal(browser.treeSubDir('team').length, 0);
  assert.equal(loads, 0);
});

test('a folder selected in the file list is selected in the tree, and only that one', () => {
  const { browser, page } = setup();
  const folders = subfolders(page.images);
  treeNode(folders, 'images/team');
  const news = treeNode(folders, 'images/news');
  news.a.kids[1].cls.add('selected');
  page.card('team', true, true);
  page.card('news', true, false);
  page.card('photo.jpg', false, true);

  browser.syncTreeSelection();

  assert.deepEqual(page.selectedInTree(), ['images/team']);
});

test('clearing the file list selection clears the tree selection', () => {
  const { browser, page } = setup();
  const team = treeNode(subfolders(page.images), 'images/team');
  team.a.kids[1].cls.add('selected');
  page.card('team', true, true);

  browser.clearSelection();

  assert.deepEqual(page.selectedInTree(), []);
});

test('selecting a folder in the file list opens the collapsed tree branch that holds it', () => {
  const { browser, page } = setup();
  page.images.a.kids[0].cls.delete('opened');
  page.images.a.kids[0].cls.add('closed');
  const team = treeNode(subfolders(page.images, true), 'images/team');
  const card = page.card('team', true, false);

  browser.selectFolder(wrap([card]));

  assert.deepEqual(page.selectedInTree(), ['images/team']);
  assert.equal(page.images.folder.kids[1].style.display, '');
  assert.ok(page.images.a.kids[0].cls.has('opened'));
  assert.ok(!page.images.a.kids[0].cls.has('closed'));
  assert.deepEqual(browser.scrolled.at(-1), ['#folders', team.a.kids[1]]);
});

test('right-clicking a tree folder that the file list shows selects it there', () => {
  const { browser, page } = setup();
  const team = treeNode(subfolders(page.images), 'images/team');
  page.card('photo.jpg', false, true);
  const card = page.card('team', true, false);

  assert.equal(browser.selectListedFolder(wrap([team.a])), true);

  assert.ok(card.cls.has('selected'));
  assert.ok(!page.files.kids[0].cls.has('selected'));
  assert.deepEqual(page.selectedInTree(), ['images/team']);
  assert.deepEqual(browser.scrolled.at(-1), ['#files', card]);
});

test('right-clicking a tree folder that the file list does not show leaves its selection alone', () => {
  const { browser, page } = setup();
  const photo = page.card('photo.jpg', false, true);

  assert.equal(browser.selectListedFolder(wrap([page.images.a])), false);
  assert.ok(photo.cls.has('selected'));
});

test('the tree context menu selects the folder in the file list before it opens', () => {
  const { browser } = setup();
  const handlers = {};
  const calls = [];
  const span = {};
  const chain = new Proxy(function () {}, {
    get: (target, prop) => (prop === 'rightClick'
      ? (fn) => { handlers.rightClick = fn; return chain; }
      : () => chain),
  });
  const context = {
    browser,
    $: Object.assign((target) => (target === span ? { parent: () => 'node' } : chain), { browser: {} }),
    _: { unselect() {} },
  };
  vm.runInNewContext(foldersSource, context);
  browser.selectListedFolder = (dir) => calls.push('select:' + dir);
  browser.menuDir = (dir) => calls.push('menu:' + dir);

  browser.initFolders();
  handlers.rightClick.call(span, {});

  assert.deepEqual(calls, ['select:node', 'menu:node']);
});

test('double-clicking a listed folder opens it even when the tree branch was not loaded', () => {
  const { browser, page } = setup();
  const opened = [];
  browser.loadTreeBranch = () => {
    treeNode(subfolders(page.images), 'images/team');
    return true;
  };
  browser.changeDir = (dir) => opened.push(dir.data('path'));

  assert.equal(browser.openDir(wrap([page.card('team', true, false)])), true);
  assert.deepEqual(opened, ['images/team']);
});

function openWithChDir(browser, page, target) {
  const ordered = [];
  page.$.ajax = (options) => options.success({ files: [], links: [], dirWritable: true });
  browser.loadingHtml = () => '';
  browser.check4errors = () => false;
  browser.updateWriteControls = () => {};
  browser.opener = {};
  browser.orderFiles = (callBack, selected) => ordered.push(selected);
  browser.baseGetData = (act) => act;
  browser.changeDir(wrap([target.a]));
  return ordered;
}

test('going up the tree selects the folder it came from in the file list', () => {
  const { browser, page } = setup();
  page.images.a.kids[1].cls.delete('current');
  page.images.a.kids[1].cls.add('regular');
  browser.dir = 'images/team/2024';

  const ordered = openWithChDir(browser, page, page.images);

  assert.deepEqual(ordered, ['team']);
  assert.equal(browser.dir, 'images');
});

test('going down or sideways selects nothing in the file list', () => {
  const { browser, page } = setup();
  const folders = subfolders(page.images);
  const team = treeNode(folders, 'images/team');
  const news = treeNode(folders, 'images/news');

  assert.deepEqual(openWithChDir(browser, page, team), [undefined]);
  assert.deepEqual(openWithChDir(browser, page, news), [undefined]);
});

test('opening a folder moves the tree highlight: current on it, nothing left selected', () => {
  const { browser, page } = setup();
  const folders = subfolders(page.images, true);
  const team = treeNode(folders, 'images/team');
  team.a.kids[1].cls.add('selected');

  openWithChDir(browser, page, team);

  assert.ok(team.a.kids[1].cls.has('current'));
  assert.deepEqual(page.selectedInTree(), []);
  // a folder opened from the file list is shown in the tree as well
  assert.equal(folders.style.display, '');
});

test('a pane scrolls just enough to show an element, and only when it is outside', () => {
  const browser = {};
  vm.runInNewContext(miscSource, { browser, $: (pane) => ({ get: () => pane }), _: {}, document: {}, window: {} });
  const rect = (top, bottom, left = 0, right = 100) => ({ top, bottom, left, right });
  const pane = { scrollTop: 50, scrollLeft: 0, getBoundingClientRect: () => rect(100, 300) };

  browser.scrollIntoPane(pane, { getBoundingClientRect: () => rect(150, 170) });
  assert.equal(pane.scrollTop, 50);

  browser.scrollIntoPane(pane, { getBoundingClientRect: () => rect(80, 100) });
  assert.equal(pane.scrollTop, 30);

  browser.scrollIntoPane(pane, { getBoundingClientRect: () => rect(310, 330) });
  assert.equal(pane.scrollTop, 60);

  browser.scrollIntoPane(pane, { getBoundingClientRect: () => rect(150, 170, 90, 140) });
  assert.equal(pane.scrollLeft, 40);

  assert.doesNotThrow(() => browser.scrollIntoPane(pane, undefined));
});
