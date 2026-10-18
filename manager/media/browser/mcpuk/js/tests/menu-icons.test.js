const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const base = path.join(__dirname, '..', '..');
const scripts = ['files.js', 'folders.js', 'clipboard.js']
  .map((name) => fs.readFileSync(path.join(base, 'js', 'browser', name), 'utf8'))
  .join('\n');

// every action a context menu offers, as written in its markup
const menuActions = [...new Set(
  [...scripts.matchAll(/<a href="kcact:([a-z_]+)"/g)].map((match) => match[1])
)].filter((action) => !['about', 'maximize', 'settings', 'upload', 'delete'].includes(action));

for (const theme of ['evo', 'oxygen']) {
  test(`every context menu item has an icon in the ${theme} theme`, () => {
    const css = fs.readFileSync(path.join(base, 'themes', theme, 'style.css'), 'utf8');
    const missing = menuActions.filter((action) => !css.includes(`.menu a[href="kcact:${action}"]`));

    assert.ok(menuActions.includes('cpdir') && menuActions.includes('rmlink'));
    assert.deepEqual(missing, []);
  });
}
