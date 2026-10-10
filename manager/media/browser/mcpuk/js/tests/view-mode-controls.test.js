const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const settingsSource = fs.readFileSync(
  path.join(__dirname, '..', 'browser', 'settings.js'),
  'utf8'
);

function loadViewControls() {
  const browser = {};
  const classNames = new Map();
  const $ = (selector) => {
    if (!classNames.has(selector))
      classNames.set(selector, new Set());
    const classes = classNames.get(selector);

    return {
      addClass(name) {
        classes.add(name);
        return this;
      },
      removeClass(name) {
        classes.delete(name);
        return this;
      },
    };
  };

  vm.runInNewContext(settingsSource, { browser, $ });

  return { browser, classNames };
}

test('saved view mode initializes its radio button and related controls together', () => {
  assert.match(
    settingsSource,
    /if \(viewEl\) viewEl\.checked = true;\s*browser\.updateViewControls\(_\.kuki\.get\('view'\)\)/
  );
});

test('content view highlights content mode and shows the thumbnail size slider', () => {
  const { browser, classNames } = loadViewControls();

  browser.updateViewControls('thumbs');

  assert.equal(classNames.get('label.radio-thumbs').has('labelchecked'), true);
  assert.equal(classNames.get('label.radio-list').has('labelchecked'), false);
  assert.equal(classNames.get('.rangeThumbContainer').has('hiddenrange'), false);
  assert.equal(classNames.get('.rangeTextContainer').has('hiddenrange'), true);
});

test('list view highlights list mode and hides the thumbnail size slider', () => {
  const { browser, classNames } = loadViewControls();

  browser.updateViewControls('list');

  assert.equal(classNames.get('label.radio-list').has('labelchecked'), true);
  assert.equal(classNames.get('label.radio-thumbs').has('labelchecked'), false);
  assert.equal(classNames.get('.rangeThumbContainer').has('hiddenrange'), true);
  assert.equal(classNames.get('.rangeTextContainer').has('hiddenrange'), false);
});
