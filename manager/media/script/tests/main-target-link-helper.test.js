const test = require('node:test');
const assert = require('node:assert/strict');
const helper = require('../main-target-link-helper');

test('getMainTargetLink returns the anchor when the click is active and targets main', () => {
    const anchor = { tagName: 'A', target: 'main' };
    const event = {
        defaultPrevented: false,
        button: 0,
        target: anchor
    };

    assert.equal(helper.getMainTargetLink(event), anchor);
});

test('getMainTargetLink ignores clicks already cancelled by inline confirm handlers', () => {
    const anchor = { tagName: 'A', target: 'main' };
    const event = {
        defaultPrevented: true,
        button: 0,
        target: anchor
    };

    assert.equal(helper.getMainTargetLink(event), null);
});

test('getMainTargetLink supports icon clicks inside a main-target anchor', () => {
    const anchor = { tagName: 'A', target: 'main' };
    const icon = { tagName: 'I', parentNode: anchor };
    const event = {
        defaultPrevented: false,
        button: 0,
        target: icon
    };

    assert.equal(helper.getMainTargetLink(event), anchor);
});
