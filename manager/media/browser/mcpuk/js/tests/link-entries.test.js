const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const browserSource = fs.readFileSync(
  path.join(__dirname, '..', 'browser', 'files.js'),
  'utf8'
);

function loadLinks(links) {
  const escape = (value) => String(value)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  const browser = { label: (text) => 'L:' + text };
  const _ = { htmlData: escape, htmlValue: escape };
  const $ = { each: (list, callback) => list.forEach((item, i) => callback(i, item)) };

  vm.runInNewContext(browserSource, { browser, _, $ });
  browser.links = links;

  return browser;
}

test('links render as cards of their own class, never as selectable files', () => {
  const browser = loadLinks([
    { name: 'current', target: 'releases/v2', broken: false, date: '10/18/2026' },
  ]);
  const html = browser.linksHtml(false);

  assert.match(html, /<div class="file-link" title="current → releases\/v2">/);
  assert.match(html, /<div class="target">→ releases\/v2<\/div>/);
  assert.doesNotMatch(html, /class="file[ "]/);
  assert.doesNotMatch(html, /href|selectThis|data-src/);
});

test('a hidden target is named, never shown as a path, and never called broken', () => {
  const browser = loadLinks([
    { name: 'escape', target: null, broken: false, date: '' },
    // the server sends broken only beside a shown target; even if it did not, nothing is told
    { name: 'outside', target: null, broken: true, date: '' },
    { name: 'gone', target: 'releases/v9', broken: true, date: '' },
  ]);
  const html = browser.linksHtml(true);

  assert.match(html, /<table summary="links">/);
  assert.match(html, /escape <span class="target">\u2192 L:Outside the file manager or not accessible<\/span>/);
  assert.match(html, /outside <span class="target">\u2192 L:Outside the file manager or not accessible<\/span>/);
  assert.match(html, /gone <span class="target">\u2192 releases\/v9 \(L:Broken link\)<\/span>/);
  assert.match(html, /<tr class="file-link"/);
});

test('the folder copy report escapes link names and targets for the HTML alert', () => {
  const browser = loadLinks([]);
  const report = browser.copyReport({
    skipped: 2,
    links: [
      { name: '<img src=x onerror=alert(1)>', target: '../<b>x</b>' },
      { name: 'escape', target: null },
    ],
  });

  assert.doesNotMatch(report, /<img|<b>/);
  assert.match(report, /&lt;img src=x onerror=alert\(1\)&gt; \u2192 \.\.\/&lt;b&gt;x&lt;\/b&gt;/);
  assert.match(report, /escape \u2192 L:Outside the file manager or not accessible/);
  assert.match(report, /^L:\{count\} files\/folders were not copied/);
});

test('link names and targets are escaped', () => {
  const browser = loadLinks([
    { name: '<img src=x onerror=alert(1)>', target: '"><script>', broken: false, date: '' },
  ]);
  const html = browser.linksHtml(false);

  assert.doesNotMatch(html, /<img|<script/);
  assert.match(html, /&lt;img src=x onerror=alert\(1\)&gt;/);
});

test('no links, no markup', () => {
  assert.equal(loadLinks([]).linksHtml(false), '');
});
