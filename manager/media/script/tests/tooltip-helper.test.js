const test = require('node:test');
const assert = require('node:assert/strict');
const helper = require('../tooltip-helper');

test('getTooltipContent returns the inline data-tooltip text', () => {
    const target = {
        dataset: {
            tooltip: 'Inline tooltip'
        },
        getAttribute(name) {
            return name === 'title' ? 'Legacy title' : null;
        }
    };

    assert.equal(helper.getTooltipContent(target), 'Inline tooltip');
});

test('getTooltipContent resolves tooltip content from a selector reference', () => {
    const target = {
        dataset: {
            tooltip: '#help-target'
        }
    };

    const querySelector = (selector) => selector === '#help-target'
        ? { innerHTML: '<strong>Shared help</strong>' }
        : null;

    assert.equal(helper.getTooltipContent(target, querySelector), '<strong>Shared help</strong>');
});

test('getTooltipContent falls back to legacy title attributes', () => {
    const target = {
        dataset: {},
        getAttribute(name) {
            return name === 'title' ? 'Blocked until tomorrow' : null;
        }
    };

    assert.equal(helper.getTooltipContent(target), 'Blocked until tomorrow');
});
