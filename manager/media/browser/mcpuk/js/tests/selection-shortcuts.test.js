const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(
  path.join(__dirname, '..', 'browser', 'files.js'),
  'utf8'
);
const toolbarSource = fs.readFileSync(
  path.join(__dirname, '..', 'browser', 'toolbar.js'),
  'utf8'
);
const browserTemplate = fs.readFileSync(
  path.join(__dirname, '..', '..', 'tpl', 'tpl_browser.php'),
  'utf8'
);

function loadBrowser() {
  const browser = {};
  const selection = {
    length: 1,
    filter() {
      return this;
    },
    removeClass(name) {
      this.removedClass = name;
      return this;
    },
    toggleClass(name, enabled) {
      this.classChanges.push({ selector: this.selector, name, enabled });
      return this;
    },
    attr(name, value) {
      this.attributes.push({ selector: this.selector, name, value });
      return this;
    },
    classChanges: [],
    attributes: [],
    is() {
      return false;
    },
  };
  browser.selectionStub = selection;
  const $ = (selector) => {
    selection.selector = selector;
    return selection;
  };
  $.inArray = (item, items) => items.indexOf(item);
  vm.runInNewContext(source, { browser, $ });
  vm.runInNewContext(toolbarSource, { browser, $ });
  browser.updateMobileActions = () => {};
  return browser;
}

function loadFileSelection() {
  const browser = {};
  const files = [
    { name: 'first.jpg', isDir: false },
    { name: 'folder', isDir: true },
    { name: 'second.jpg', isDir: false },
    { name: 'third.jpg', isDir: false },
  ];
  const list = {
    get() {
      return files;
    },
    removeClass(name) {
      files.forEach((file) => { file[name] = false; });
      return this;
    },
  };
  const wrap = (file) => ({
    get() { return file; },
    data(key) { return typeof key === 'undefined' ? file : file[key]; },
    hasClass(name) { return !!file[name]; },
    addClass(name) { file[name] = true; return this; },
    removeClass(name) { file[name] = false; return this; },
  });
  const $ = (selector) => selector === '.file' ? list : wrap(selector);
  $.inArray = (item, items) => items.indexOf(item);
  $.each = (items, callback) => items.forEach((item, index) => callback(index, item));
  vm.runInNewContext(source, { browser, $, _: { unselect() {} } });
  browser.statusDir = () => {};
  browser.updateDeleteButton = () => {};
  browser.updateSelectionStatus = () => {};
  browser.updateMobileActions = () => {};
  return { browser, files, wrap };
}

test('file selection distinguishes range, toggle, and regular clicks', () => {
  const browser = loadBrowser();

  assert.equal(browser.selectionMode({ shiftKey: true }), 'range');
  assert.equal(browser.selectionMode({ ctrlKey: true }), 'toggle');
  assert.equal(browser.selectionMode({ metaKey: true }), 'toggle');
  assert.equal(browser.selectionMode({}), 'replace');
});

test('shift selection ranges include both endpoints in either direction', () => {
  const browser = loadBrowser();

  assert.deepEqual(Array.from(browser.selectionRange(1, 4)), [1, 2, 3, 4]);
  assert.deepEqual(Array.from(browser.selectionRange(4, 1)), [1, 2, 3, 4]);
});

test('folder selection preserves a file anchor and a missing shift anchor is restored', () => {
  const browser = loadBrowser();
  const files = [{}, {}, {}, {}];
  const originalAnchor = files[0];
  browser.lastSelectedFile = originalAnchor;
  browser.statusDir = () => {};
  browser.updateDeleteButton = () => {};

  browser.selectFolder({ addClass() { return this; } });
  assert.equal(browser.lastSelectedFile, originalAnchor);

  browser.lastSelectedFile = null;
  assert.equal(browser.selectionAnchorIndex(files[1], files, 1), 1);
  assert.equal(browser.selectionAnchorIndex(files[3], files, 3), 1);
  assert.equal(browser.lastSelectedFile, files[1]);
});

test('Ctrl and Command toggles keep a working anchor after clicking a folder', () => {
  const { browser, files, wrap } = loadFileSelection();

  browser.handleFileClick(wrap(files[1]));
  browser.handleFileClick(wrap(files[0]), { ctrlKey: true });
  browser.handleFileClick(wrap(files[2]), { metaKey: true });

  assert.equal(files[0].selected, true);
  assert.equal(files[2].selected, true);
  assert.equal(browser.lastSelectedFile, files[2]);

  browser.handleFileClick(wrap(files[3]), { shiftKey: true });
  assert.equal(files[0].selected, false);
  assert.equal(files[1].selected, false);
  assert.equal(files[2].selected, true);
  assert.equal(files[3].selected, true);
});

test('regular clicks keep one file selected and establish the next shift anchor', () => {
  const { browser, files, wrap } = loadFileSelection();

  browser.handleFileClick(wrap(files[1]));
  browser.handleFileClick(wrap(files[0]), {});
  browser.handleFileClick(wrap(files[2]), {});

  assert.equal(files[0].selected, false);
  assert.equal(files[1].selected, false);
  assert.equal(files[2].selected, true);
  assert.equal(browser.lastSelectedFile, files[2]);

  browser.handleFileClick(wrap(files[3]), { shiftKey: true });
  assert.equal(files[2].selected, true);
  assert.equal(files[3].selected, true);
});

test('toolbar delete is enabled only for writable selected files in a writable folder', () => {
  const browser = loadBrowser();
  browser.dirWritable = true;

  assert.equal(browser.deleteButtonEnabled(true, [{ writable: true }]), true);
  assert.equal(browser.deleteButtonEnabled(false, [{ writable: true }]), false);
  assert.equal(browser.deleteButtonEnabled(true, [{ isDir: true, writable: true }]), false);
  assert.equal(browser.deleteButtonEnabled(true, [{ writable: false }]), false);
  browser.dirWritable = false;
  assert.equal(browser.deleteButtonEnabled(true, [{ writable: true }]), false);
});

test('upload toolbar reflects current folder write access', () => {
  const browser = loadBrowser();
  browser.access = { files: { upload: true } };
  browser.dirWritable = false;
  browser.updateDeleteButton = () => {};

  browser.updateWriteControls();
  browser.dirWritable = true;
  browser.updateWriteControls();

  assert.deepEqual(browser.selectionStub.classChanges, [
    { selector: '#toolbar a[href="kcact:upload"]', name: 'disabled', enabled: true },
    { selector: '#toolbar a[href="kcact:upload"]', name: 'disabled', enabled: false },
  ]);
  assert.deepEqual(browser.selectionStub.attributes, [
    { selector: '#toolbar a[href="kcact:upload"]', name: 'aria-disabled', value: 'true' },
    { selector: '#toolbar a[href="kcact:upload"]', name: 'aria-disabled', value: 'false' },
  ]);
});

test('file mutations require both file and current-folder write access', () => {
  const browser = loadBrowser();

  browser.dirWritable = true;
  assert.equal(browser.canModifyFile(true, true), true);
  assert.equal(browser.canModifyFile(false, true), false);
  assert.equal(browser.canModifyFile(true, false), false);
  browser.dirWritable = false;
  assert.equal(browser.canModifyFile(true, true), false);
});

test('toolbar provides a Delete action beside the browser commands', () => {
  assert.match(browserTemplate, /kcact:refresh[\s\S]*kcact:delete/);
  assert.match(browserTemplate, /label\("Delete"\)/);
});

test('keyboard shortcuts recognize Ctrl and Command selection and deletion', () => {
  const browser = loadBrowser();

  assert.equal(browser.keyboardAction({ key: 'a', ctrlKey: true }), 'selectAll');
  assert.equal(browser.keyboardAction({ key: 'a', metaKey: true }), 'selectAll');
  assert.equal(browser.keyboardAction({ keyCode: 65, ctrlKey: true }), 'selectAll');
  assert.equal(browser.keyboardAction({ key: 'Delete' }), 'delete');
  assert.equal(browser.keyboardAction({ key: 'Backspace', metaKey: true }), 'delete');
  assert.equal(browser.keyboardAction({ keyCode: 8, ctrlKey: true }), 'delete');
  assert.equal(browser.keyboardAction({ key: 'Escape' }), 'clearSelection');
  assert.equal(browser.keyboardAction({ key: 'Backspace' }), null);
});

test('keyboard shortcuts leave editable controls alone', () => {
  const browser = loadBrowser();

  assert.equal(browser.isEditableTarget({ tagName: 'INPUT' }), true);
  assert.equal(browser.isEditableTarget({ tagName: 'textarea' }), true);
  assert.equal(browser.isEditableTarget({ isContentEditable: true }), true);
  assert.equal(browser.isEditableTarget({ tagName: 'BODY' }), false);
});

test('keyboard delete invokes the selected-file delete action once', () => {
  const browser = loadBrowser();
  let deleteCalls = 0;
  browser.isEditableTarget = () => false;
  browser.keyboardAction = () => 'delete';
  browser.deleteSelectedFiles = () => {
    deleteCalls++;
    return true;
  };

  assert.equal(browser.handleFileKeydown({ target: {}, repeat: false }), true);
  assert.equal(browser.handleFileKeydown({ target: {}, repeat: true }), true);
  assert.equal(deleteCalls, 1);
});

test('Escape clears selection through the keyboard handler', () => {
  const browser = loadBrowser();
  let clearCalls = 0;
  browser.keyboardAction = () => 'clearSelection';
  browser.isEditableTarget = () => false;
  browser.clearSelection = () => {
    clearCalls++;
  };

  assert.equal(browser.handleFileKeydown({ target: {} }), true);
  assert.equal(clearCalls, 1);
});

test('clearing selection removes its highlight and resets the range anchor', () => {
  const browser = loadBrowser();
  let statusCalls = 0;
  let buttonCalls = 0;
  browser.lastSelectedFile = {};
  browser.statusDir = () => statusCalls++;
  browser.updateDeleteButton = () => buttonCalls++;

  browser.clearSelection();

  assert.equal(browser.selectionStub.removedClass, 'selected');
  assert.equal(browser.lastSelectedFile, null);
  assert.equal(statusCalls, 1);
  assert.equal(buttonCalls, 1);
});
