const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const css = fs.readFileSync(path.join(__dirname, '..', '..', 'themes', 'evo', 'style.css'), 'utf8')
  .replace(/\/\*[\s\S]*?\*\//g, '');
const rules = [...css.matchAll(/([^{}]+)\{([^{}]*)\}/g)]
  .map(([, selector, body]) => ({ selector: selector.trim(), body }));

// evo draws its icons as masks on ::before, painted with background-color: currentColor.
// Any other background there recolours the glyph itself (white on white, blue on blue).
test('no ::before icon in the evo theme gets a background colour of its own', () => {
  const offending = rules
    .filter(({ selector }) => /:before\b/.test(selector))
    .filter(({ body }) => [...body.matchAll(/background(?:-color)?\s*:\s*([^;]+)/g)]
      .some(([, value]) => value.trim() !== 'currentColor'))
    .map(({ selector }) => selector);

  assert.deepEqual(offending, []);
});

test('the list/grid toggle draws its button on the label, so both states stay visible', () => {
  const declared = (selector, property) => rules
    .filter((rule) => rule.selector.split(',').map((s) => s.trim()).includes(selector))
    .some(({ body }) => new RegExp(`(^|;|\\s)${property}\\s*:`).test(body));

  for (const label of ['label.radio-thumbs', 'label.radio-list']) {
    assert.ok(declared(label, 'border'), `${label} has the button border`);
    assert.ok(declared(label, 'background-color'), `${label} has the button background`);
    assert.ok(declared(label, 'color'), `${label} sets the glyph colour`);
    assert.ok(declared(`${label}.labelchecked`, 'color'), `${label}.labelchecked recolours the glyph`);
    assert.ok(!declared(`${label}:before`, 'border'), `${label}:before is not the button: a mask would clip its border`);
  }
});
