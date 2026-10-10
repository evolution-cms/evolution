const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const browserSource = fs.readFileSync(
  path.join(__dirname, '..', 'browser', 'files.js'),
  'utf8'
);
const evoStyle = fs.readFileSync(
  path.join(__dirname, '..', '..', 'themes', 'evo', 'style.css'),
  'utf8'
);
const oxygenStyle = fs.readFileSync(
  path.join(__dirname, '..', '..', 'themes', 'oxygen', 'style.css'),
  'utf8'
);

test('content folders use the tree font glyph instead of a folder image', () => {
  assert.match(browserSource, /var thumbClass = file\.isDir \? 'thumb folder-icon'/);
  assert.match(browserSource, /var thumbData = file\.isDir \? ''/);
  assert.match(browserSource, /var fileClass = file\.isDir \? 'file folder'/);
  assert.match(browserSource, /div\.thumb\.folder-icon'\)\.css\('font-size', Math\.round\(size \* 0\.8\)/);
  assert.match(browserSource, /'<div class="' \+ thumbClass \+ '"' \+ thumbData \+ '><\/div>' \+\s*'<div class="name">' \+ _.htmlData\(file\.name\)/);
  assert.doesNotMatch(browserSource, /big\/folder\.png/);
  assert.match(evoStyle, /div\.file \.thumb\.folder-icon:before[^\n]*content: "\\f07b"/);
  assert.match(oxygenStyle, /div\.file \.thumb\.folder-icon:before\s*\{[^}]*content: "\\f07b"/);
  assert.match(evoStyle, /div\.file \.thumb\.folder-icon:before[^\n]*color: #3687e2/);
  assert.match(oxygenStyle, /div\.file \.thumb\.folder-icon:before\s*\{[^}]*color: #3687e2/);
  assert.match(evoStyle, /div\.file\.folder \.name \{[^}]*height: auto;[^}]*white-space: normal/);
  assert.match(oxygenStyle, /div\.file\.folder \.name \{[^}]*height: auto;[^}]*white-space: normal/);
});

test('content folder icon backgrounds match each theme settings panel', () => {
  assert.match(evoStyle, /div\.file \.thumb\.folder-icon \{[^}]*background-color: #fff/);
  assert.match(oxygenStyle, /div\.file \.thumb\.folder-icon \{[^}]*background-color: #e0dfde/);
});
