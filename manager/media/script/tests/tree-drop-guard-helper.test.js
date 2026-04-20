const test = require('node:test');
const assert = require('node:assert/strict');
const helper = require('../tree-drop-guard-helper');

test('canDropIntoTarget blocks deleted tree nodes', () => {
    assert.equal(helper.canDropIntoTarget({
        dataset: {
            deleted: '1'
        }
    }), false);
});

test('canDropIntoTarget allows active tree nodes', () => {
    assert.equal(helper.canDropIntoTarget({
        dataset: {
            deleted: '0'
        }
    }), true);
});

test('canDropIntoTarget allows targets without dataset metadata', () => {
    assert.equal(helper.canDropIntoTarget(null), true);
    assert.equal(helper.canDropIntoTarget({}), true);
});
