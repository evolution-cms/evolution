const test = require('node:test');
const assert = require('node:assert/strict');
const helper = require('../tree-parent-guard-helper');

test('isBlockedParentTarget blocks nodes the user may not create in', () => {
    assert.equal(helper.isBlockedParentTarget({ dataset: { canaddchild: '0' } }), true);
});

test('isBlockedParentTarget allows nodes the user may create in', () => {
    assert.equal(helper.isBlockedParentTarget({ dataset: { canaddchild: '1' } }), false);
});

test('isBlockedParentTarget allows nodes rendered without the flag', () => {
    assert.equal(helper.isBlockedParentTarget(null), false);
    assert.equal(helper.isBlockedParentTarget({}), false);
    assert.equal(helper.isBlockedParentTarget({ dataset: {} }), false);
});

test('acceptsChild keeps nodes selectable when a plugin leaves the placeholder unresolved', () => {
    assert.equal(helper.acceptsChild({ dataset: { canaddchild: '[+canAddChild+]' } }), true);
});
