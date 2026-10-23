const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const base = path.join(__dirname, '..');
const read = (file) => fs.readFileSync(path.join(base, file), 'utf8');

test('the loading indicator is one line: the spinner (from CSS) and the word', () => {
  const browser = {};
  vm.runInNewContext(read('browser/misc.js'), { browser, $: () => ({}), _: {}, document: {}, window: {} });
  browser.label = (text) => 'L:' + text;

  assert.equal(browser.loadingHtml(), '<div class="kc-loading">L:Loading...</div>');
  assert.equal(browser.loadingHtml('loadingDirs'), '<div class="kc-loading" id="loadingDirs">L:Loading...</div>');
});

test('every place that loads shows it, and none says folders or files', () => {
  const scripts = ['browser/init.js', 'browser/folders.js', 'browser/files.js'].map(read).join('\n');

  assert.doesNotMatch(scripts, /Loading folders\.\.\.|Loading files\.\.\./);
  assert.equal((scripts.match(/loadingHtml\(/g) || []).length, 4);
  // it appears in place, it does not slide in or out
  assert.doesNotMatch(scripts, /#loadingDirs'\)\.(show|hide)\(/);
});

test('the spinner turns the refresh icon', () => {
  const css = fs.readFileSync(path.join(base, '..', 'css.php'), 'utf8');

  assert.match(css, /\.kc-loading \{[^}]*display: flex;[^}]*align-items: center;/);
  assert.match(css, /\.kc-loading:before \{[^}]*mask:var\(--evo-icon-refresh\)[^}]*animation: kc-loading-spin 1s linear infinite;/);
  assert.match(css, /@keyframes kc-loading-spin/);
});
