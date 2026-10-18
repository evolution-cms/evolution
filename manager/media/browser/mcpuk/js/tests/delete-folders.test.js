const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const read = (file) => fs.readFileSync(path.join(__dirname, '..', 'browser', file), 'utf8');

// a selection of cards, the requests it sends, and what is read again afterwards
function load(selected, responses = {}) {
  const calls = [];
  const cards = selected.map((data) => ({ data }));
  const $ = (target) => {
    if (target === '.file.selected')
      return { get: () => cards, filter: (fn) => ({ get: () => cards.filter((card) => fn.call(card)) }) };
    if (target === 'div.folder > a > span.folder.current')
      return { parent: () => ({ get: () => ({}), name: 'current-node' }) };
    return { data: (key) => (key === undefined ? target.data : target.data[key]) };
  };
  $.each = (items, fn) => items.forEach((item, i) => fn(i, item));
  $.ajax = (options) => {
    calls.push(options.url + ' ' + JSON.stringify(options.data));
    const response = responses[options.url];
    if (response === 'fail') options.error();
    else options.success(response === undefined ? true : response);
  };
  const browser = {};
  vm.runInNewContext(read('files.js'), { browser, $, _: {} });
  Object.assign(browser, {
    dir: 'images/team',
    dirWritable: true,
    access: { files: { delete: true }, dirs: { delete: true } },
    label: (text, data) => text.replace('{count}', data ? data.count : ''),
    baseGetData: (act) => act,
    fadeFiles: () => {},
    refresh: () => calls.push('refresh'),
    refreshDir: (dir) => calls.push('refreshDir:' + dir.name),
    alert: (msg) => calls.push('alert:' + msg),
    confirm: (question, go) => { calls.push('confirm:' + question); go(); },
    deleteFiles: () => calls.push('deleteFiles'),
  });
  return { browser, calls };
}

test('the toolbar button counts a removable folder, not only files', () => {
  const { browser } = load([]);

  assert.equal(browser.deleteButtonEnabled(true, [{ isDir: true, removable: true }], true), true);
  assert.equal(browser.deleteButtonEnabled(true, [{ isDir: true, removable: false }], true), false);
  assert.equal(browser.deleteButtonEnabled(true, [{ isDir: true, removable: true }], false), false);
  // a folder may go even where files may not, and the other way round
  assert.equal(browser.deleteButtonEnabled(false, [{ isDir: true, removable: true }], true), true);
  assert.equal(browser.deleteButtonEnabled(true, [{ writable: true }], false), true);
  browser.dirWritable = false;
  assert.equal(browser.deleteButtonEnabled(true, [{ isDir: true, removable: true }], true), false);
});

test('deleting a selected folder asks once, deletes it and reads the folder on screen again', () => {
  const { browser, calls } = load([{ isDir: true, name: 'old', removable: true }]);

  assert.equal(browser.deleteSelectedFiles(), true);
  assert.deepEqual(calls, [
    'confirm:Are you sure you want to delete this folder and all its content?',
    'deleteDir {"dir":"images/team/old"}',
    'refreshDir:current-node',
    'refresh',
  ]);
});

test('folders and files go together, what may not go is left and counted, errors come in one report', () => {
  const { browser, calls } = load([
    { isDir: true, name: 'a', removable: true },
    { isDir: true, name: 'locked', removable: false },
    { name: 'x.jpg', writable: true },
  ], { deleteDir: { error: 'Cannot delete the folder.' } });

  browser.deleteSelectedFiles();
  assert.deepEqual(calls, [
    'confirm:1 selected files are not removable. Do you want to delete the rest?',
    'deleteDir {"dir":"images/team/a"}',
    'rm_cbd {"files":["images/team/x.jpg"]}',
    'refreshDir:current-node',
    'refresh',
    'alert:Cannot delete the folder.',
  ]);
});

test('a selection of files only still takes the file path', () => {
  const { browser, calls } = load([{ name: 'x.jpg', writable: true }]);

  browser.deleteSelectedFiles();
  assert.deepEqual(calls, ['deleteFiles']);
});

test('deleting a folder from its menu reads the folder on screen again when it was listed there', () => {
  const folders = read('folders.js');
  const start = folders.indexOf('$(\'.menu a[href="kcact:rmdir"]\')');
  const handler = folders.slice(start, folders.indexOf('browser.refreshDir = function', start));

  assert.match(handler, /if \(pDir\.data\('path'\) == browser\.dir\)\s*browser\.refresh\(\);/);
});
