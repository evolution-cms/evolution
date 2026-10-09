const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const browserRoot = path.join(__dirname, '..', '..');
const commonCss = fs.readFileSync(path.join(browserRoot, 'css.php'), 'utf8');
const browserTemplate = fs.readFileSync(
  path.join(browserRoot, 'tpl', 'tpl_browser.php'),
  'utf8'
);
const initSource = fs.readFileSync(
  path.join(browserRoot, 'js', 'browser', 'init.js'),
  'utf8'
);
const toolbarSource = fs.readFileSync(
  path.join(browserRoot, 'js', 'browser', 'toolbar.js'),
  'utf8'
);
const miscSource = fs.readFileSync(
  path.join(browserRoot, 'js', 'browser', 'misc.js'),
  'utf8'
);

test('toolbar controls can wrap within a mobile viewport in both themes', () => {
  ['evo', 'oxygen'].forEach((theme) => {
    const themeCss = fs.readFileSync(
      path.join(browserRoot, 'themes', theme, 'style.css'),
      'utf8'
    );

    assert.match(themeCss, /#toolbar\s*>\s*div\s*\{[^}]*flex-wrap:\s*wrap/s);
    assert.match(themeCss, /#toolbar\s+a\s*\{[^}]*float:\s*none/s);
  });

  assert.match(commonCss, /#toolbar\s*\{[^}]*white-space:\s*normal/s);
  assert.match(browserTemplate, /name="viewport"\s+content="width=device-width, initial-scale=1"/);
  assert.match(fs.readFileSync(path.join(browserRoot, 'themes', 'evo', 'style.css'), 'utf8'), /#toolbar\s*\{[^}]*min-width:\s*0/s);
});

test('the toolbar grows to fit wrapped actions so the files panel stays below it', () => {
  assert.match(initSource, /_\('toolbar'\)\.style\.height\s*=\s*'auto'/);
  assert.match(initSource, /#left'\)\.outerHeight\(\)\s*-\s*\$\('#toolbar'\)\.outerHeight\(\)/);
  assert.match(toolbarSource, /browser\.updateMobileActions\s*=\s*function/);
  assert.match(miscSource, /#mobileActions'\)\.empty\(\)\.append\(menu\)\.addClass\('active'\)/);
  assert.match(browserTemplate, /id="mobileActions"/);
  assert.match(toolbarSource, /#toolbar\s*>\s*div\s*>\s*a/);
  assert.match(miscSource, /if\s*\(this\.initKeyboard\)\s*this\.initKeyboard\(\)/);
});
